<?php

$version = $_GET['version'];

$config_12 = <<< EOF
{
  "online_config_version":"1.2",
  "livecd_minimum_version":"3.2",
  "require_institution_confirm":"no",
  "institutions":[
    {
      "institution_name":"Universidade Federal de Santa Catarina",
      "institution_moodle_support_email":"moodle@contato.ufsc.br",
      "moodle_provas_url":"https://provas3.moodle.ufsc.br",
      "moodle_provas_receive_data_path":"/local/exam_authorization/receive_data.php",
      "allowed_tcp_out_ipv4":"150.162.1.49#443 150.162.1.141#443",
      "allowed_tcp_out_ipv6":"",
      "ntp_servers":"ntp.ufsc.br a.ntp.br b.ntp.br",
      "show_institution_name_in_desktop":"yes",
      "diagnostic_server_settings":{
        "diag_allow_send_logs":"no",
        "diag_allow_send_screenshots":"no",
        "diag_script_receive_file_path":"/~juliao/diagnostic_receive_data.php",
        "diag_script_token":"b49837d1fea65f892651057ae5c31f6f",
        "diag_system_files_to_copy":"/etc/X11/xorg* /var/log/*",
        "diag_upload_timeout":"30"
      }
    },
    {
      "institution_name":"UFSC - Olimpíada",
      "institution_moodle_support_email":"antonio.c.mariani@ufsc.br",
      "moodle_provas_url":"https://olimpiada.moodle.ufsc.br",
      "moodle_provas_receive_data_path":"/local/exam_authorization/receive_data.php",
      "allowed_tcp_out_ipv4":"150.162.1.49#443 150.162.1.141#443",
      "allowed_tcp_out_ipv6":"",
      "ntp_servers":"ntp.ufsc.br a.ntp.br b.ntp.br",
      "show_institution_name_in_desktop":"yes",
      "diagnostic_server_settings":{
        "diag_allow_send_logs":"no",
        "diag_allow_send_screenshots":"no",
        "diag_script_receive_file_path":"/~juliao/diagnostic_receive_data.php",
        "diag_script_token":"b49837d1fea65f892651057ae5c31f6f",
        "diag_system_files_to_copy":"/etc/X11/xorg* /var/log/*",
        "diag_upload_timeout":"30"
      }
    }
  ]
}
EOF;

$config_13 = <<< EOF
{
  "online_config_version":"1.3",
  "livecd_minimum_version":"3.2",
  "require_institution_confirm":"no",
  "institutions":[
    {
      "institution_name":"Universidade Federal de Santa Catarina",
      "institution_moodle_support_email":"moodle@contato.ufsc.br",
      "moodle_provas_url":"https://150.162.9.156/~juliao/moodle29provas",
      "moodle_webservices_token":"a443f7870ef8cf5ef3e12f1150d8b287",
      "allowed_tcp_out_ipv4":"150.162.1.49#443 150.162.1.141#443",
      "allowed_tcp_out_ipv6":"",
      "ntp_servers":"ntp.ufsc.br a.ntp.br b.ntp.br",
      "show_institution_name_in_desktop":"yes",
      "diagnostic_server_settings":{
        "diag_allow_send_logs":"no",
        "diag_allow_send_screenshots":"no",
        "diag_system_files_to_copy":"/etc/X11/xorg* /var/log/*",
        "diag_upload_timeout":"30"
      }
    },
    {
      "institution_name":"UFSC - Olimpíada",
      "institution_moodle_support_email":"antonio.c.mariani@ufsc.br",
      "moodle_provas_url":"https://olimpiada.moodle.ufsc.br",
      "moodle_webservices_token":"a443f7870ef8cf5ef3e12f1150d8b287",
      "allowed_tcp_out_ipv4":"150.162.1.49#443 150.162.1.141#443",
      "allowed_tcp_out_ipv6":"",
      "ntp_servers":"ntp.ufsc.br a.ntp.br b.ntp.br",
      "show_institution_name_in_desktop":"yes",
      "diagnostic_server_settings":{
        "diag_allow_send_logs":"no",
        "diag_allow_send_screenshots":"no",
        "diag_system_files_to_copy":"/etc/X11/xorg* /var/log/*",
        "diag_upload_timeout":"30"
      }
    }
  ]
}
EOF;


$latest = $config_13;
header('Content-Type: application/json');

switch ($version) {
    case '1.2':
        echo $config_12;
        break;
    case '1.3':
        echo $config_13;
        break;
    default:
        echo $latest;
        break;
}
