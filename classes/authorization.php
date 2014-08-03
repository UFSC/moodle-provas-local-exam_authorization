<?php

namespace local_exam_authorization;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

class authorization {

    private static $moodles = null;
    private static $config = null;
    private static $errors = null;

    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $SESSION;

        if(is_siteadmin($event->userid) || isguestuser($event->userid)) {
            return true;
        }

        $userid = $event->userid;
        $username = $event->other['username'];

        self::check_permission($username);
        self::sync_enrols($userid);

        return true;
    }

    public static function has_function_except_student($username) {
        $functions = self::get_remote_user_functions($username);
        foreach($functions AS $f->$identifiers) {
            if($f != 'student') {
                return true;
            }
        }
        return false;
    }

    public static function check_permission($username) {
        global $SESSION;

        if (isset($SESSION->exam_access_key)) {
            $access_key = $SESSION->exam_access_key;
            unset($SESSION->exam_access_key);
            self::check_student_permission($username, $access_key);
        } else {
            $courses = self::get_courses($username);
            if(empty($courses)) {
                self::print_error('no_access_permission');
            } else {
                $SESSION->exam_courses = $courses;
            }
        }
    }

    protected static function sync_enrols($userid) {
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

        if(!$studentid = $DB->get_field('role', 'id', array('shortname'=>'student'))) {
            self::print_error('role_unknown', 'student');
        }

        if(!$editorid = $DB->get_field('role', 'id', array('shortname'=>'editingteacher'))) {
            self::print_error('role_unknown', 'editingteacher');
        }

        // activate only the necessary enrolments
        if (!enrol_is_enabled('manual')) {
            self::print_error('enrol_not_active');
        }
        if (!$enrol = enrol_get_plugin('manual')) {
            self::print_error('enrol_not_active');
        }

        $functions = array();
        foreach($SESSION->exam_courses AS $identifier=>$courses) {
            foreach($courses AS $c) {
                $functions = array_merge($functions, $c->functions);
                $roleid = false;
                if(in_array('editor', $c->functions)) {
                    if($c->ip_range_editor_ok) {
                        $roleid = $editorid;
                    }
                } else if(in_array('student', $c->functions)) {
                    $roleid = $studentid;
                }
                if($roleid) {
                    if(isset($c->local_course)) {
                        $course = $c->local_course;
                    } else {
                        $shortname = "{$identifier}_{$c->shortname}";
                        if($course = $DB->get_record('course', array('shortname'=>$shortname), 'id, shortname, visible')) {
                            $c->local_course = $course;
                        }
                    }
                    if($course) {
                        $instances = $DB->get_records('enrol', array('enrol'=>'manual', 'courseid'=>$course->id), 'id ASC');
                        if($instance = reset($instances)) {
                            if($instance->status == ENROL_INSTANCE_DISABLED) {
                                $enrol->update_status($instance, ENROL_INSTANCE_ENABLED);
                            }
                        } else {
                            $id = $enrol->add_instance($course);
                            $instance = $DB->get_record('enrol', array('id'=>$id));
                        }
                        $enrol->enrol_user($instance, $userid, $roleid, 0, 0, ENROL_USER_ACTIVE);
                    }
                }
            }
        }
        $functions = array_unique($functions);
        sort($functions);
        $SESSION->exam_functions = $functions;
    }

    private static function check_student_permission($username, $access_key) {
        global $DB, $SESSION;

        if (!$rec_key = $DB->get_record('exam_access_keys', array('access_key'=>$access_key))) {
            self::print_error('access_key_unknown');
        }
        if ($rec_key->timecreated + $rec_key->timeout*60 < time()) {
            self::print_error('access_key_timedout');
        }

        self::check_ip_range_student();
        self::check_version_header();
        self::check_ip_header();
        self::check_network_header();
        self::check_client_host($rec_key);

        $local_course = $DB->get_record('course', array('id'=>$rec_key->courseid), 'id, shortname, visible');
        if($local_course && $local_course->visible) {
            list($identifier, $shortname) = explode('_', $local_course->shortname, 2);
            if($course = self::get_course($username, $identifier, $shortname)) {
                if(in_array('student', $course->functions)) {
                    $course->functions = array('student');
                    $course->local_course = $local_course;
                    $SESSION->exam_courses = array($identifier=>array($course));
                    return;
                }
            }
            self::print_error('no_student_permission');
        } else {
            self::print_error('course_not_avaliable');
        }
    }

    private static function get_remote_user_functions($username, $identifier='') {
        $ws_function = 'local_exam_remote_get_remote_user_functions';
        $params = array('username'=>$username);

        $moodles = empty($identifier) ? self::get_moodles() : array(self::get_moodle($identifier));
        $functions = array();
        self::$errors = array();
        foreach($moodles AS $m) {
            try {
                $funcs = self::call_remote_function($m->identifier, $ws_function, $params);
                foreach($funcs AS $f) {
                    $functions[$f][] = $ident;
                }
            } catch (\Exception $e) {
                self::$errors[$ident] = $e->getMessage();
            }
        }
        return $functions;
    }

    public static function get_courses($username, $identifier='') {
        global $DB;

        $ws_function = 'local_exam_remote_get_user_courses';
        $params = array('username'=>$username);

        $ip_range_editor_ok = self::check_ip_range_editor(false);

        $moodles = empty($identifier) ? self::get_moodles() : array(self::get_moodle($identifier));
        $courses = array();
        self::$errors = array();
        foreach($moodles AS $m) {
            try {
                $response = self::call_remote_function($m->identifier, $ws_function, $params);
                if(is_array($response) ) {
                    foreach($response AS $course) {
                        $functions = array_flip($course->functions);
                        $shortname = "{$m->identifier}_{$course->shortname}";
                        if($local_course = $DB->get_record('course', array('shortname'=>$shortname), 'id, shortname, visible')) {
                            $course->local_course = $local_course;
                            if($local_course->visible) {
                                if(isset($functions['editor'])) {
                                    $course->ip_range_editor_ok = $ip_range_editor_ok;
                                    if(isset($functions['student'])) {
                                        unset($course->functions[$functions['student']]);
                                    }
                                    $courses[$m->identifier][] = $course;
                                } else if(isset($functions['student'])) {
                                    $course->functions = array('student');
                                    $courses[$m->identifier][] = $course;
                                } else {
                                    $courses[$m->identifier][] = $course;
                                }
                            } else if(isset($functions['editor'])) {
                                $course->ip_range_editor_ok = $ip_range_editor_ok;
                                $course->functions = array('editor');
                                $courses[$m->identifier][] = $course;
                            }
                        } else {
                            if(isset($functions['editor'])) {
                                // curso não existe no Moodle Provas. Acesso somente para editor
                                $course->ip_range_editor_ok = $ip_range_editor_ok;
                                $course->functions = array('editor');
                                $courses[$m->identifier][] = $course;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                self::$errors[$m->identifier] = $e->getMessage();
            }
        }
        return $courses;
    }

    public static function get_course($username, $identifier, $shortname) {
        $courses = self::get_courses($username, $identifier);
        foreach($courses AS $ident=>$cs) {
            foreach($cs AS $c) {
                if($c->shortname == $shortname) {
                    return $c;
                }
            }
        }
        return false;
    }

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

        return array_values(self::$moodles);
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
        } else if($result == null) {
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
        }
    }

    public static function is_header_check_disabled() {
        self::load_config();
        return self::$config->disable_header_check;
    }

    public static function check_version_header($print_error=true) {
        global $_SERVER;

        if(self::is_header_check_disabled()) {
            return true;
        }

        $version = self::$config->header_version;
        $pattern = '/^[0-9]+\.[0-9]+$/';

        if(! isset($_SERVER["HTTP_MOODLE_PROVAS_VERSION"]) ) {
            return self::print_error('browser_no_version_header', $print_error);
        }
        if(!preg_match($pattern, $_SERVER['HTTP_MOODLE_PROVAS_VERSION'])) {
            return self::print_error('browser_invalid_version_header', $print_error);
        }
        if (!empty($version)) {
            if ($_SERVER["HTTP_MOODLE_PROVAS_VERSION"] < $version) {
                return self::print_error('browser_old_version', $print_error);
            }
        }
        return true;
    }

    public static function check_ip_header($print_error=true) {
        global $_SERVER;

        if(self::is_header_check_disabled()) {
            return true;
        }

        if(!isset($_SERVER["HTTP_MOODLE_PROVAS_IP"]) ) {
            return self::print_error('browser_unknown_ip_header', $print_error);
        }
        $oct = explode('.', $_SERVER["HTTP_MOODLE_PROVAS_IP"]);
        if(!filter_var($_SERVER["HTTP_MOODLE_PROVAS_IP"], FILTER_VALIDATE_IP) || empty($oct[0]) || empty($oct[3])) {
            return self::print_error('browser_invalid_ip_header', $print_error);
        }
        return true;
    }

    public static function check_network_header($print_error=true) {
        global $_SERVER;

        if(self::is_header_check_disabled()) {
            return true;
        }

        $netmask_octet_pattern = "[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5]";
        $netmask_pattern = "({$netmask_octet_pattern})(\.({$netmask_octet_pattern})){3}";
        $pattern = "/^{$netmask_pattern}\/[1-9][0-9]?$/";

        if(! isset($_SERVER["HTTP_MOODLE_PROVAS_NETWORK"]) ) {
            return self::print_error('browser_unknown_network_header', $print_error);
        }
        if(!preg_match($pattern, $_SERVER['HTTP_MOODLE_PROVAS_NETWORK'])) {
            return self::print_error('browser_invalid_network_header', $print_error);
        }
        return true;
    }

    public static function check_ip_range_student($print_error=true) {
        self::load_config();
        return self::check_ip_range(self::$config->ip_ranges_students, $print_error);
    }

    public static function check_ip_range_editor($print_error=true) {
        self::load_config();
        return self::check_ip_range(self::$config->ip_ranges_editors, $print_error);
    }

    private static function check_ip_range($str_ranges='', $print_error=true) {
        global $_SERVER;

        $str_ranges = trim($str_ranges);
        $ranges = explode(';', $str_ranges);
        if(!empty($str_ranges) && !empty($ranges)) {
            foreach($ranges AS $range) {
                if (IPTools::ip_in_range($_SERVER['REMOTE_ADDR'], trim($range))) {
                    return true;
                }
            }
            return self::print_error('out_of_ip_ranges', $print_error);
        }
        return true;
    }

    public static function check_client_host($access_key, $print_error=true) {
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
                return self::print_error('unknow_client_host', $print_error);
            }
            if ($client->timemodified + $timeout * 60 < time()) {
                return self::print_error('client_host_timeout', $print_error);
            }

            if($access_key->ip != $client->real_ip && !$this->ipCIDRCheck($access_key->ip, $client->network)) {
                return self::print_error('client_host_out_of_subnet', $print_error);
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
