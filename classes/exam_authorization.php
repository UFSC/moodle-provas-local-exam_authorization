<?php

namespace local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

class exam_authorization {

    private static $moodles = null;

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
}
