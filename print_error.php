<?php

include('../../config.php');

$errorcode = optional_param('errorcode', '', PARAM_TEXT);
if(empty($errorcode)) {
    require_logout();
    redirect(new moodle_url('/'));
} else {
    $url = new moodle_url('/local/exam_authorization/print_error.php');
    print_error($errorcode, 'local_exam_authorization', $url);
}
