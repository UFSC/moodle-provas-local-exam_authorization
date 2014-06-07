<?php

require('../../config.php');
require_once($CFG->dirroot . '/local/exam_authorization/edit_form.php');

if (!is_siteadmin($USER)) {
    print_error('onlyadmins');
}

$navtitle = get_string('pluginname', 'local_exam_authorization');
$syscontext = context_system::instance();
$url = new moodle_url('/local/exam_authorization/edit.php');
$returnurl = new moodle_url('/admin/settings.php', array('section'=>'local_exam_authorization_settings'));

if($id = optional_param('id', 0, PARAM_INT)) {
    $moodle = $DB->get_record('exam_authorization', array('id'=>$id), '*', MUST_EXIST);
} else {
    $moodle = new stdClass();
    $moodle->id = 0;
    $moodle->identifier = '';
    $moodle->description = '';
    $moodle->url = '';
    $moodle->token = '';
}

if (optional_param('confirmdelete', 0, PARAM_BOOL) && confirm_sesskey() && $id) {
    $DB->delete_records('exam_authorization', array('id'=>$id));
    redirect($returnurl);
}

$PAGE->set_pagelayout('standard');
$PAGE->set_context($syscontext);
$PAGE->set_url($url);
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_title($navtitle);

if (optional_param('delete', 0, PARAM_BOOL) && $id) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('remote_moodle', 'local_exam_authorization'));
    $yesurl = new moodle_url('/local/exam_authorization/edit.php', array('id'=>$id, 'confirmdelete'=>1, 'sesskey'=>sesskey()));
    $message = get_string('confirmdelete', 'local_exam_authorization', $moodle->identifier);
    echo $OUTPUT->confirm($message, $yesurl, $returnurl);
    echo $OUTPUT->footer();
    exit;
}

$editform = new exam_authorization_form(null, array('data'=>$moodle));

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    if($data->id) {
        $data->timemodified = time();
        $DB->update_record('exam_authorization', $data);
    } else {
        $data->timeadded = time();
        $data->timemodified = time();
        $id = $DB->insert_record('exam_authorization', $data);
        $data->id = $id;
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('remote_moodle', 'local_exam_authorization'));
echo $editform->display();
echo $OUTPUT->footer();
