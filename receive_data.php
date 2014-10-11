<?php
require('../../config.php');

$ip = isset($_SERVER["HTTP_MOODLE_PROVAS_IP"]) ? $_SERVER["HTTP_MOODLE_PROVAS_IP"] : '';
$network = isset($_SERVER["HTTP_MOODLE_PROVAS_NETWORK"]) ? $_SERVER["HTTP_MOODLE_PROVAS_NETWORK"] : '';

$ip_pattern = "|^([0-9]{1,3}\.){3}[0-9]{1,3}$|";
$network_pattern = "|^([0-9]{1,3}\.){3}[0-9]{1,3}/[0-9]{1,2}$|";

if(preg_match($ip_pattern, $ip) && preg_match($network_pattern, $network)) {
    $real_ip = $_SERVER["REMOTE_ADDR"];
    $rec = new stdClass();
    $rec->timemodified = time();

    if($id = $DB->get_field('exam_client_hosts', 'id', array('ip'=>$ip, 'network'=>$network, 'real_ip'=>$real_ip))) {
        $rec->id = $id;
        $DB->update_record('exam_client_hosts', $rec);
    } else {
        $rec->ip      = $ip;
        $rec->network = $network;
        $rec->real_ip = $real_ip;
        $DB->insert_record('exam_client_hosts', $rec);
    }
}
?>
