<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/exam_authorization/classes/IPTools.php');
require_once($CFG->dirroot.'/local/exam_authorization/classes/exam_authorization.php');
require_once($CFG->libdir.'/filelib.php');

class local_exam_authorization_observer {

    protected static $user;
    protected static $remote_courses;
    protected static $role;
    protected static $config;

    public static function user_loggedin(\core\event\user_loggedin $event) {
        if(is_siteadmin($event->userid) || isguestuser($event->userid)) {
            return true;
        }

        self::$config = get_config('local_exam_authorization');
        if(!isset(self::$config->header_version)) {
            self::print_error('not_configured');
        }
        if(!isset(self::$config->client_host_timeout)) {
            self::$config->client_host_timeout = '10';
        }
        if(!isset(self::$config->ip_ranges_teachers)) {
            self::$config->ip_ranges_teachers = '';
        }
        if(!isset(self::$config->ip_ranges_students)) {
            self::$config->ip_ranges_students = '';
        }

        self::$user = new StdClass();
        self::$user->id = $event->userid;
        self::$user->username = $event->other['username'];

        self::check_permissions();
        self::sync_enrols();

        return true;
    }

    protected static function check_permissions() {
        global $SESSION;

        if (isset($SESSION->exam_access_key)) {
            self::check_student_permissions();
        } else {
            if(!self::check_teacher_permissions(false)) {
                self::check_monitor_permissions();
            }
        }
        $SESSION->exam_remote_courses = self::$remote_courses;
        $SESSION->exam_role = self::$role;
    }

    protected static function check_student_permissions() {
        global $DB, $SESSION;

        self::check_ip_range(self::$config->ip_ranges_students);
        self::check_version_header();
        self::check_ip_header();
        self::check_network_header();

        if (! $access_key = $DB->get_record('exam_access_keys', array('access_key' => $SESSION->exam_access_key))) {
            self::print_error('access_key_unknown');
        }
        if ($access_key->timecreated + $access_key->timeout*60 < time()) {
            self::print_error('access_key_timeout');
        }
        self::check_client_host($access_key);

        if($local_course = $DB->get_record('course', array('shortname'=>$access_key->shortname), 'id, shortname, visible') && $local_course->visible) {
            list($identifier, $shortname) = explode('_', $access_key->shortname, 2);
            if($remote_course = \local\exam_authorization::get_remote_course($identifier, $shortname, 'student')) {
                $remote_course->local_course = $local_course;
                self::$remote_courses[$identifier] = array($remote_course);
            } else {
                self::print_error('no_student_permission');
            }
        } else {
            self::print_error('course_not_avaliable');
        }

        unset($SESSION->exam_access_key);
        self::$role = 'student';
    }

    protected static function check_teacher_permissions($print_error=true) {
        if(self::check_ip_range(self::$config->ip_ranges_teachers, $print_error)) {
            self::$remote_courses = \local\exam_authorization::get_remote_courses('teacher');
            if(empty(self::$remote_courses)) {
                return self::print_error('no_teacher_permission', $print_error);
            } else {
                self::$role = 'teacher';
                return true;
            }
        } else {
            return false;
        }
    }

    protected static function check_monitor_permissions() {
        global $DB;

        self::$remote_courses = array();
        foreach(\local\exam_authorization::get_remote_courses('monitor') AS $identifier=>$courses) {
            foreach($courses AS $rc) {
                if($local_course = $DB->get_record('course', array('shortname'=>$rc->local_shortname), 'id, shortname, visible') && $local_course->visible) {
                    $rc->local_course = $local_course;
                    self::$remote_courses[$identifier][] = $rc;
                }
            }
        }

        if(empty(self::$remote_courses)) {
            self::print_error('no_access_permission');
        } else {
            self::$role = 'monitor';
        }
    }

    protected static function sync_enrols() {
        global $DB;

        // suspend all user enrolments
        $ues = $DB->get_records('user_enrolments', array('userid'=>self::$user->id, 'status'=>ENROL_USER_ACTIVE));
        foreach($ues AS $ue) {
            if($enrol = $DB->get_record('enrol', array('id'=>$ue->enrolid))) {
                if($plugin = enrol_get_plugin($enrol->enrol)) {
                    $plugin->update_user_enrol($enrol, self::$user->id, ENROL_USER_SUSPENDED);
                }
            }
        }

        $roleid = 0;
        if(self::$role == 'student') {
            if(!$roleid = $DB->get_field('role', 'id', array('shortname'=>'student'))) {
                self::print_error('role_unknown', 'student');
            }
        } else if(self::$role == 'teacher') {
            if(!$roleid = $DB->get_field('role', 'id', array('shortname'=>'editingteacher'))) {
                self::print_error('role_unknown', 'editingteacher');
            }
        } else {
            return;
        }

        // activate only the necessary enrolments
        if (!enrol_is_enabled('manual')) {
            self::print_error('enrol_not_active');
        }
        if (!$enrol = enrol_get_plugin('manual')) {
            self::print_error('enrol_not_active');
        }

        foreach(self::$remote_courses AS $identifier=>$remote_courses) {
            foreach($remote_courses AS $c) {
                if(isset($c->local_course)) {
                    $course = $c->local_course;
                } else {
                    $course = $DB->get_record('course', array('shortname'=>$c->local_shortname), 'id, shortname, visible');
                }
                if($course) {
                    $c->local_course = $course;
                    $instances = $DB->get_records('enrol', array('enrol'=>'manual', 'courseid'=>$course->id), 'id ASC');
                    if($instance = reset($instances)) {
                        if($instance->status == ENROL_INSTANCE_DISABLED) {
                            $enrol->update_status($instance, ENROL_INSTANCE_ENABLED);
                        }
                    } else {
                        $id = $enrol->add_instance($course);
                        $instance = $DB->get_record('enrol', array('id'=>$id));
                    }
                    $enrol->enrol_user($instance, self::$user->id, $roleid, 0, 0, ENROL_USER_ACTIVE);
                }
            }
        }
    }

    protected static function check_version_header() {
        global $_SERVER;

        $version = self::$config->header_version;
        $pattern = '/^[0-9]+\.[0-9]+$/';

        if (!empty($version)) {
            if(! isset($_SERVER["HTTP_MOODLE_PROVAS_VERSION"]) ) {
                self::print_error('browser_no_version_header');
            }
            if(!preg_match($pattern, $_SERVER['HTTP_MOODLE_PROVAS_VERSION'])) {
                self::print_error('browser_invalid_version_header');
            }
            if ($_SERVER["HTTP_MOODLE_PROVAS_VERSION"] < $version) {
                self::print_error('browser_old_version');
            }
        }
    }

    protected static function check_ip_range($str_ranges='', $print_error=true) {
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

    protected static function check_client_host($access_key) {
        global $DB, $_SERVER;

        $timeout = self::$config->client_host_timeout;

        if(!empty($access_key->verify_client_host) && !empty($timeout)) {
            $sql = "SELECT *
                      FROM {exam_client_hosts}
                     WHERE real_ip = '{$_SERVER['REMOTE_ADDR']}'
                  ORDER BY timemodified DESC
                     LIMIT 1";
            if(!$client = $DB->get_record_sql($sql)) {
                self::print_error('unknow_client_host');
            }
            if ($client->timemodified + $timeout * 60 < time()) {
                self::print_error('client_host_timeout');
            }

            if($access_key->ip != $client->real_ip && !$this->ipCIDRCheck($access_key->ip, $client->network)) {
                self::print_error('client_host_out_of_subnet');
            }
        }
    }

    // Check if $ip belongs to $cidr
    protected static function ipCIDRCheck($ip, $cidr='0.0.0.0/24') {
        list ($net, $mask) = split ("/", $cidr);

        $ip_net = ip2long ($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);
        $ip_ip = ip2long ($ip);
        $ip_ip_net = $ip_ip & $ip_mask;
        return ($ip_ip_net == $ip_net);
    }

    protected static function check_ip_header() {
        global $_SERVER;

        if(!isset($_SERVER["HTTP_MOODLE_PROVAS_IP"]) ) {
            self::print_error('browser_unknown_ip_header');
        }
        $oct = explode('.', $_SERVER["HTTP_MOODLE_PROVAS_IP"]);
        if(!filter_var($_SERVER["HTTP_MOODLE_PROVAS_IP"], FILTER_VALIDATE_IP) || empty($oct[0]) || empty($oct[3])) {
            self::print_error('browser_invalid_ip_header');
        }
    }

    protected static function check_network_header() {
        global $_SERVER;

        $netmask_octet_pattern = "[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5]";
        $netmask_pattern = "({$netmask_octet_pattern})(\.({$netmask_octet_pattern})){3}";
        $pattern = "/^{$netmask_pattern}\/[1-9][0-9]?$/";

        if(! isset($_SERVER["HTTP_MOODLE_PROVAS_NETWORK"]) ) {
            self::print_error('browser_unknown_network_header');
        }
        if(!preg_match($pattern, $_SERVER['HTTP_MOODLE_PROVAS_NETWORK'])) {
            self::print_error('browser_invalid_network_header');
        }
    }

    protected static function print_error($errorcode, $print_error=true) {
        global $DB;

        if($print_error) {
            $user = $DB->get_record('user', array('username'=>'guest'));
            \core\session\manager::set_user($user);
            redirect(new moodle_url('/local/exam_authorization/print_error.php', array('errorcode'=>$errorcode)));
        } else {
            return false;
        }
    }

}
