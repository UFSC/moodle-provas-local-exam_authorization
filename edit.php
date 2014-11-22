<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
//
// Este bloco é parte do Moodle Provas - http://tutoriais.moodle.ufsc.br/provas/
// Este projeto é financiado pela
// UAB - Universidade Aberta do Brasil (http://www.uab.capes.gov.br/)
// e é distribuído sob os termos da "GNU General Public License",
// como publicada pela "Free Software Foundation".

/**
 * This file contains the Manage Remote Moodles page.
 *
 * @package    local_exam_authorization
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/local/exam_authorization/edit_form.php');

if (!is_siteadmin($USER)) {
    print_error('onlyadmins');
}

$navtitle = get_string('pluginname', 'local_exam_authorization');
$syscontext = context_system::instance();
$url = new moodle_url('/local/exam_authorization/edit.php');
$returnurl = new moodle_url('/admin/settings.php', array('section'=>'local_exam_authorization_settings'));

if ($id = optional_param('id', 0, PARAM_INT)) {
    $moodle = $DB->get_record('exam_authorization', array('id'=>$id), '*', MUST_EXIST);
} else {
    $moodle = new stdClass();
    $moodle->id = 0;
    $moodle->identifier = '';
    $moodle->description = '';
    $moodle->url = '';
    $moodle->token = '';
}

$action = optional_param('action', false, PARAM_TEXT);

if ($action == 'confirmdelete' && confirm_sesskey() && $id) {
    $DB->delete_records('exam_authorization', array('id'=>$id));
    redirect($returnurl);
} else if ($action == 'enable' && $id) {
    $DB->set_field('exam_authorization', 'enable', 1, array('id'=>$id));
    redirect($returnurl);
} else if ($action == 'disable' && $id) {
    $DB->set_field('exam_authorization', 'enable', 0, array('id'=>$id));
    redirect($returnurl);
}

$PAGE->set_pagelayout('standard');
$PAGE->set_context($syscontext);
$PAGE->set_url($url);
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_title($navtitle);

if ($action == 'delete' && $id) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('remote_moodle', 'local_exam_authorization'));
    $yesurl = new moodle_url('/local/exam_authorization/edit.php', array('id'=>$id, 'action'=>'confirmdelete', 'sesskey'=>sesskey()));
    $message = get_string('confirmdelete', 'local_exam_authorization', $moodle->identifier);
    echo $OUTPUT->confirm($message, $yesurl, $returnurl);
    echo $OUTPUT->footer();
    exit;
}

$editform = new exam_authorization_form(null, array('data'=>$moodle));

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    if ($data->id) {
        $data->timemodified = time();
        $DB->update_record('exam_authorization', $data);
    } else {
        $data->timeadded = time();
        $data->timemodified = time();
        $data->enable = 1;
        $id = $DB->insert_record('exam_authorization', $data);
        $data->id = $id;
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('remote_moodle', 'local_exam_authorization'));
echo $editform->display();
echo $OUTPUT->footer();
