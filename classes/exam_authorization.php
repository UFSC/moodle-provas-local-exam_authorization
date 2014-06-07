<?php

namespace local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

class exam_authorization {

    public static function is_teacher_or_monitor($username='') {
        global $DB;

        $function = 'local_exam_remote_is_teacher_or_monitor';
        if(empty($username)) {
            return false;
        }
        $params = array('username'=>$username);

        $curl = new \curl;
        $curl->setopt(array('CURLOPT_SSL_VERIFYHOST'=>0, 'CURLOPT_SSL_VERIFYPEER'=>0));

        $moodles = $DB->get_records('exam_authorization');
        foreach($moodles AS $m) {
            $serverurl = "{$m->url}/webservice/rest/server.php?wstoken={$m->token}&wsfunction={$function}&moodlewsrestformat=json";
            $response = $curl->post($serverurl, $params);
            $response = json_decode($response);
            if($response) {
                return true;
            }
        }
        return false;
    }
}
