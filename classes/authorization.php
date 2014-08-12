<?php

namespace local_exam_authorization;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

class authorization {

    private static $moodles = null;
    private static $config = null;
    private static $errors = null;

    public static function user_loggedin(\core\event\user_loggedin $event) {
        if(is_siteadmin($event->userid) || isguestuser($event->userid)) {
            return true;
        }

        $user = new \stdClass();
        $user->id = $event->userid;
        $user->username = $event->other['username'];

        self::check_permissions($user);
        self::sync_enrols($user->id);

        return true;
    }

    public static function has_function_except_student($username) {
        $ws_function = 'local_exam_remote_has_exam_capability';
        $params = array('username'=>$username);

        self::$errors = array();
        foreach(self::get_moodles() AS $m) {
            try {
                if(self::call_remote_function($m->identifier, $ws_function, $params)) {
                    return true;
                }
            } catch (\Exception $e) {
                self::$errors[$ident] = $e->getMessage();
            }
        }
        return false;
    }

    private static function check_permissions($user) {
        global $SESSION;

        if (isset($SESSION->exam_access_key)) {
            $access_key = $SESSION->exam_access_key;
            unset($SESSION->exam_access_key);
            self::check_student_permission($user, $access_key);
        } else {
            $SESSION->exam_courses = self::get_remote_courses($user->username);
            self::calculate_functions($user->username);
            if(empty($SESSION->exam_functions)) {
                self::print_error('no_access_permission');
            }
        }
    }

    private static function sync_enrols($userid) {
        global $DB, $SESSION;

        // suspend all user enrolments
        $ues = $DB->get_records('user_enrolments', array('userid'=>$userid, 'status'=>ENROL_USER_ACTIVE));
        foreach($ues AS $ue) {
            if($enrol = $DB->get_record('enrol', array('id'=>$ue->enrolid))) {
                if($plugin = enrol_get_plugin($enrol->enrol)) {
                    $plugin->update_user_enrol($enrol, $userid, ENROL_USER_SUSPENDED);
                }
            }
        }

        $roleids = array();
        if(!empty(self::$config->proctor_roleid)) {
            $roleids['proctor'] = self::$config->proctor_roleid;
        }
        if(!empty(self::$config->monitor_roleid)) {
            $roleids['monitor'] = self::$config->monitor_roleid;
        }

        $roleids['student'] = $DB->get_field('role', 'id', array('shortname'=>'student'), MUST_EXIST);
        $roleids['editor'] = $DB->get_field('role', 'id', array('shortname'=>'editingteacher'), MUST_EXIST);

        if (!enrol_is_enabled('manual')) {
            self::print_error('enrol_not_active');
        }
        if (!$enrol = enrol_get_plugin('manual')) {
            self::print_error('enrol_not_active');
        }

        // activate only the necessary enrolments
        foreach($SESSION->exam_functions AS $f=>$courses) {
            if(isset($roleids[$f])) {
                $roleid = $roleids[$f];
                foreach($courses AS $shortname=>$courseid) {
                    $instances = $DB->get_records('enrol', array('enrol'=>'manual', 'courseid'=>$courseid), 'id ASC');
                    if($instance = reset($instances)) {
                        if($instance->status == ENROL_INSTANCE_DISABLED) {
                            $enrol->update_status($instance, ENROL_INSTANCE_ENABLED);
                        }
                    } else {
                        $course = new \stdClass();
                        $course->id = $courseid;
                        $enrolid = $enrol->add_instance($course);
                        $instance = $DB->get_record('enrol', array('id'=>$enrolid));
                    }
                    $enrol->enrol_user($instance, $userid, $roleid, 0, 0, ENROL_USER_ACTIVE);
                }
            }
        }
    }

    private static function check_student_permission($user, $access_key) {
        global $DB, $SESSION;

        if (!$rec_key = $DB->get_record('exam_access_keys', array('access_key'=>$access_key))) {
            self::add_to_log($access_key, $user->id, 'access_key_unknown');
            self::print_error('access_key_unknown');
        }
        if ($rec_key->timecreated + $rec_key->timeout*60 < time()) {
            self::add_to_log($access_key, $user->id, 'access_key_timedout');
            self::print_error('access_key_timedout');
        }

        try {
            self::check_ip_range_student();
            self::check_version_header();
            self::check_ip_header();
            self::check_network_header();
            self::check_client_host($rec_key);
        } catch(Exception $e) {
            self::add_to_log($access_key, $user->id, $e->getMessage());
            self::print_error($e->getMessage());
        }

        $local_course = $DB->get_record('course', array('id'=>$rec_key->courseid), 'id, shortname, visible');
        if($local_course && $local_course->visible) {
            list($identifier, $shortname) = explode('_', $local_course->shortname, 2);
            if($course = self::get_remote_course($user->username, $identifier, $shortname)) {
                if(!in_array('student', $course->functions)) {
                    self::add_to_log($access_key, $user->id, 'no_student_permission');
                    self::print_error('no_student_permission');
                } else if(count($course->functions) > 1) {
                    self::add_to_log($access_key, $user->id, 'more_than_student_permission');
                    self::print_error('more_than_student_permission');
                } else {
                    $course->local_course = $local_course;
                    $SESSION->exam_courses = array($identifier=>array($course));
                    $SESSION->exam_functions = array('student'=>array($local_course->shortname=>$local_course->id));
                    self::add_to_log($access_key, $user->id, 'ok');
                }
            } else {
                self::add_to_log($access_key, $user->id, 'no_student_permission');
                self::print_error('no_student_permission');
            }
        } else {
            self::add_to_log($access_key, $user->id, 'course_not_avaliable');
            self::print_error('course_not_avaliable');
        }
    }

    private static function add_to_log($access_key, $userid, $info='') {
        global $DB;

        $rec = new \stdClass();
        $rec->access_key = $access_key;
        $rec->userid = $userid;
        $rec->ip = $_SERVER["REMOTE_ADDR"];
        $rec->time = time();
        $rec->info = $info;
        if(isset($_SERVER["HTTP_MOODLE_PROVAS_VERSION"]) ) {
            $rec->header_version = $_SERVER["HTTP_MOODLE_PROVAS_VERSION"];
        } else {
            $rec->header_version = '';
        }
        if(isset($_SERVER["HTTP_MOODLE_PROVAS_IP"]) ) {
            $rec->header_ip = $_SERVER["HTTP_MOODLE_PROVAS_IP"];
        } else {
            $rec->header_ip = '';
        }
        if(isset($_SERVER["HTTP_MOODLE_PROVAS_NETWORK"]) ) {
            $rec->header_network = $_SERVER["HTTP_MOODLE_PROVAS_NETWORK"];
        } else {
            $rec->header_network = '';
        }

        $DB->insert_record('exam_access_keys_log', $rec);
    }

    public static function calculate_functions($username) {
        global $DB, $SESSION;

        if(!isset($SESSION->exam_courses)) {
            $SESSION->exam_courses = self::get_remote_courses($username);
        }

        $ip_range_editor_ok = self::check_ip_range_editor(false);
        $functions = array();
        foreach($SESSION->exam_courses AS $identifier=>$cs) {
            foreach($cs AS $course) {
                $shortname = "{$identifier}_{$course->shortname}";
                if($local_course = $DB->get_record('course', array('shortname'=>$shortname), 'id, shortname, visible')) {
                    foreach($course->functions AS $f) {
                        if($f == 'editor') {
                            if($ip_range_editor_ok) {
                                $functions[$f][$shortname] = $local_course->id;
                            }
                        } else if($f != 'student') {
                            $functions[$f][$shortname] = $local_course->id;
                        }
                    }
                }
            }
        }
        ksort($functions);
        $SESSION->exam_functions = $functions;
    }

    // ========================================================================================
    // Private functions

    public static function get_remote_courses($username, $identifier='') {
        global $DB;

        $ws_function = 'local_exam_remote_get_user_courses';
        $params = array('username'=>$username);

        $moodles = empty($identifier) ? self::get_moodles() : array(self::get_moodle($identifier));

        $courses = array();
        self::$errors = array();
        foreach($moodles AS $m) {
            $courses[$m->identifier] = array();
            try {
                $response = self::call_remote_function($m->identifier, $ws_function, $params);
                if(is_array($response) ) {
                    foreach($response AS $course) {
                        $courses[$m->identifier][$course->shortname] = $course;
                    }
                }
            } catch (\Exception $e) {
                self::$errors[$m->identifier] = $e->getMessage();
            }
        }
        return $courses;
    }

    public static function get_remote_course($username, $identifier, $shortname) {
        $courses = self::get_remote_courses($username, $identifier);
        foreach($courses[$identifier] AS $c) {
            if($c->shortname == $shortname) {
                return $c;
            }
        }
        return false;
    }

    // ============================================================================
    // Funções de suporte

    public static function get_moodle($identifier) {
        self::get_moodles();
        if(isset(self::$moodles[$identifier])) {
            return self::$moodles[$identifier];
        } else {
            return false;
        }
    }

    public static function get_moodles() {
        global $DB;

        if(self::$moodles == null) {
            self::$moodles = $DB->get_records('exam_authorization', null, null, 'identifier, description, url, token');
        }

        return self::$moodles;
    }

    public static function call_remote_function($identifier, $ws_function, $params) {
        global $DB;

        if(!$moodle = self::get_moodle($identifier)) {
            throw new \Exception(get_string('unknown_identifier', 'local_exam_authorization', $identifier));
        }

        $curl = new \curl;
        $curl->setopt(array('CURLOPT_SSL_VERIFYHOST'=>0, 'CURLOPT_SSL_VERIFYPEER'=>0));
        $serverurl = "{$moodle->url}/webservice/rest/server.php?wstoken={$moodle->token}&wsfunction={$ws_function}&moodlewsrestformat=json";

        // formating (not recursive) an array in POST parameter.
        // We try to use 'format_array_postdata_for_curlcall' from filelib.php, but there's some troubles with stored_files
        foreach($params AS $key=>$value) {
            if(is_array($value)) {
                unset($params[$key]);
                foreach($value AS $i=>$v) {
                    $params[$key.'['.$i.']'] = $v;
                }
            }
        }

        $result = json_decode($curl->post($serverurl, $params));
        if(is_object($result) && isset($result->exception)) {
            if($result->exception == 'webservice_access_exception') {
                throw new \Exception($result->message . ': ' . $result->debuginfo);
            } else {
                throw new \Exception($result->message);
            }
        } else if(is_null($result)) {
            throw new \Exception(get_string('return_null', 'local_exam_authorization', $moodle->description));
        }
        return $result;
    }

    // ------------------------------------------------------------------------------------------

    private static function load_config() {
        if(self::$config == null) {
            self::$config = get_config('local_exam_authorization');
            if(!isset(self::$config->disable_header_check)) {
                self::$config->disable_header_check = false;
            }
            if(!isset(self::$config->header_version)) {
                self::print_error('not_configured');
            }
            if(!isset(self::$config->client_host_timeout)) {
                self::$config->client_host_timeout = '10';
            }
            if(!isset(self::$config->ip_ranges_editors)) {
                self::$config->ip_ranges_editors = '';
            }
            if(!isset(self::$config->ip_ranges_students)) {
                self::$config->ip_ranges_students = '';
            }
            if(!isset(self::$config->proctor_roleid)) {
                self::$config->proctor_roleid = 0;
            }
            if(!isset(self::$config->monitor_roleid)) {
                self::$config->monitor_roleid = 0;
            }
        }
    }

    public static function is_header_check_disabled() {
        self::load_config();
        return self::$config->disable_header_check;
    }

    public static function check_version_header() {
        global $_SERVER;

        if(self::is_header_check_disabled()) {
            return true;
        }

        $version = self::$config->header_version;
        $pattern = '/^[0-9]+\.[0-9]+$/';

        if(! isset($_SERVER["HTTP_MOODLE_PROVAS_VERSION"]) ) {
            throw new Exception('browser_no_version_header');
        }
        if(!preg_match($pattern, $_SERVER['HTTP_MOODLE_PROVAS_VERSION'])) {
            throw new Exception('browser_invalid_version_header');
        }
        if (!empty($version)) {
            if ($_SERVER["HTTP_MOODLE_PROVAS_VERSION"] < $version) {
                throw new Exception('browser_old_version');
            }
        }
        return true;
    }

    public static function check_ip_header() {
        global $_SERVER;

        if(self::is_header_check_disabled()) {
            return true;
        }

        if(!isset($_SERVER["HTTP_MOODLE_PROVAS_IP"]) ) {
            throw new Exception('browser_unknown_ip_header');
        }
        $oct = explode('.', $_SERVER["HTTP_MOODLE_PROVAS_IP"]);
        if(!filter_var($_SERVER["HTTP_MOODLE_PROVAS_IP"], FILTER_VALIDATE_IP) || empty($oct[0]) || empty($oct[3])) {
            throw new Exception('browser_invalid_ip_header');
        }
        return true;
    }

    public static function check_network_header() {
        global $_SERVER;

        if(self::is_header_check_disabled()) {
            return true;
        }

        $netmask_octet_pattern = "[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5]";
        $netmask_pattern = "({$netmask_octet_pattern})(\.({$netmask_octet_pattern})){3}";
        $pattern = "/^{$netmask_pattern}\/[1-9][0-9]?$/";

        if(! isset($_SERVER["HTTP_MOODLE_PROVAS_NETWORK"]) ) {
            throw new Exception('browser_unknown_network_header');
        }
        if(!preg_match($pattern, $_SERVER['HTTP_MOODLE_PROVAS_NETWORK'])) {
            throw new Exception('browser_invalid_network_header');
        }
        return true;
    }

    public static function check_ip_range_student() {
        self::load_config();
        try {
            self::check_ip_range(self::$config->ip_ranges_students);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function check_ip_range_editor() {
        self::load_config();
        try {
            self::check_ip_range(self::$config->ip_ranges_editors);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function check_ip_range($str_ranges='') {
        global $_SERVER;

        $str_ranges = trim($str_ranges);
        $ranges = explode(';', $str_ranges);
        if(!empty($str_ranges) && !empty($ranges)) {
            foreach($ranges AS $range) {
                if (IPTools::ip_in_range($_SERVER['REMOTE_ADDR'], trim($range))) {
                    return true;
                }
            }
            throw new Exception('out_of_ip_ranges');
        }
        return true;
    }

    public static function check_client_host($access_key) {
        global $DB, $_SERVER;

        if(self::is_header_check_disabled()) {
            return true;
        }

        $timeout = self::$config->client_host_timeout;

        if(!empty($access_key->verify_client_host) && !empty($timeout)) {
            $sql = "SELECT *
                      FROM {exam_client_hosts}
                     WHERE real_ip = '{$_SERVER['REMOTE_ADDR']}'
                  ORDER BY timemodified DESC
                     LIMIT 1";
            if(!$client = $DB->get_record_sql($sql)) {
                throw new Exception('unknow_client_host');
            }
            if ($client->timemodified + $timeout * 60 < time()) {
                throw new Exception('client_host_timeout');
            }

            if($access_key->ip != $client->real_ip && !$this->ipCIDRCheck($access_key->ip, $client->network)) {
                throw new Exception('client_host_out_of_subnet');
            }
        }
        return true;
    }

    // Check if $ip belongs to $cidr
    public static function ipCIDRCheck($ip, $cidr='0.0.0.0/24') {
        list ($net, $mask) = split ("/", $cidr);

        $ip_net = ip2long ($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);
        $ip_ip = ip2long ($ip);
        $ip_ip_net = $ip_ip & $ip_mask;
        return ($ip_ip_net == $ip_net);
    }

    public static function print_error($errorcode, $print_error=true) {
        global $DB;

        if($print_error) {
            $user = guest_user();
            \core\session\manager::set_user($user);
            redirect(new \moodle_url('/local/exam_authorization/print_error.php', array('errorcode'=>$errorcode)));
        } else {
            return false;
        }
    }
}
