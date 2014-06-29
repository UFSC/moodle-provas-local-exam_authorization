<?php

namespace local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

class exam_authorization {

    private static $moodles = null;
    private static $config = null;

    public static function is_teacher_or_monitor($username='') {
        global $USER;

        if(empty($username)) {
            $username = $USER->username;
        }

        $function = 'local_exam_remote_is_teacher_or_monitor';
        $params = array('username'=>$username);

        $moodles = self::get_moodle();
        foreach($moodles AS $m) {
            $response = self::call_remote_function($m->identifier, $function, $params);
            if($response) {
                return true;
            }
        }
        return false;
    }

    public static function get_remote_courses($rolename, $username='') {
        global $USER;

        if(empty($username)) {
            $username = $USER->username;
        }

        $function = 'local_exam_remote_get_courses';
        $params = array('username'=>$username, 'rolename'=>$rolename);

        $moodles = self::get_moodle();
        $courses = array();
        foreach($moodles AS $m) {
            $response = self::call_remote_function($m->identifier, $function, $params);
            if(is_array($response) ) {
                foreach($response AS $remote_course) {
                    $remote_course->local_shortname = "{$m->identifier}_{$remote_course->shortname}";
                    $courses[$m->identifier][] = $remote_course;
                }
            }
        }
        return $courses;
    }

    public static function get_remote_course($identifier, $shortname, $rolename='student', $username='') {
        global $USER;

        if(empty($username)) {
            $username = $USER->username;
        }

        $function = 'local_exam_remote_get_courses';
        $params = array('username'=>$username, 'rolename'=>$rolename);

        $remote_courses = self::call_remote_function($identifier, $function, $params);
        foreach($remote_courses AS $c) {
            if($c->shortname == $shortname) {
                $c->identifier = $m->identifier;
                $c->local_shortname = "{$m->identifier}_{$c->shortname}";
                return $c;
            }
        }
        return false;
    }

    public static function get_moodle($identifier=null) {
        global $DB;

        if(self::$moodles == null) {
            self::$moodles = $DB->get_records('exam_authorization', null, null, 'identifier, description, url, token');
        }

        if($identifier == null) {
            return self::$moodles;
        } else {
            if(isset(self::$moodles[$identifier])) {
                return self::$moodles[$identifier];
            } else {
                return false;
            }
        }
    }

    public static function call_remote_function($moodle_identifier, $function, $params) {
        global $DB;

        $moodle = self::get_moodle($moodle_identifier);
        $curl = new \curl;
        $curl->setopt(array('CURLOPT_SSL_VERIFYHOST'=>0, 'CURLOPT_SSL_VERIFYPEER'=>0));
        $serverurl = "{$moodle->url}/webservice/rest/server.php?wstoken={$moodle->token}&wsfunction={$function}&moodlewsrestformat=json";
        $response = $curl->post($serverurl, format_postdata_for_curlcall($params));
        return json_decode($response);
    }

    // ------------------------------------------------------------------------------------------

    private static function load_config() {
        if(self::$config == null) {
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
        }
    }

    public static function check_version_header($print_error=true) {
        global $_SERVER;

        self::load_config();
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

    public static function check_ip_range_teacher($print_error=true) {
        self::load_config();
        return self::check_ip_range(self::$config->ip_ranges_teachers, $print_error);
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

        self::load_config();
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
            $user = $DB->get_record('user', array('username'=>'guest'));
            \core\session\manager::set_user($user);
            redirect(new \moodle_url('/local/exam_authorization/print_error.php', array('errorcode'=>$errorcode)));
        } else {
            return false;
        }
    }
}
