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
 * This file contains the plugin edit form.
 *
 * @package    local_exam_authorization
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class exam_authorization_form extends moodleform {

    public function definition() {

        $mform = $this->_form;
        $moodle = $this->_customdata['data'];

        $mform->addElement('text', 'identifier', get_string('identifier', 'local_exam_authorization'), 'maxlength="20" size="20"');
        $mform->addRule('identifier', get_string('required'), 'required', null, 'client');
        $mform->setType('identifier', PARAM_TEXT);
        if($moodle->id) {
            $mform->freeze('identifier');
        }

        $mform->addElement('text', 'description', get_string('description', 'local_exam_authorization'), 'maxlength="254" size="50"');
        $mform->addRule('description', get_string('required'), 'required', null, 'client');
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('text', 'url', get_string('url', 'local_exam_authorization'), 'maxlength="254" size="50"');
        $mform->addRule('url', get_string('required'), 'required', null, 'client');
        $mform->setType('url', PARAM_URL);

        $mform->addElement('text', 'token', get_string('token', 'local_exam_authorization'), 'maxlength="128" size="50"');
        $mform->addRule('token', get_string('required'), 'required', null, 'client');
        $mform->setType('token', PARAM_TEXT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();

        $this->set_data($moodle);
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $split = explode(' ', $data['identifier']);
        if(count($split) > 1) {
            $errors['identifier'] = get_string('invalid_identifier', 'local_exam_authorization');
        }

        $keys = array('identifier', 'description', 'url', 'token');
        $params = array('id'=>$data['id']);
        foreach($keys AS $key) {
            $params[$key] = $data[$key];
            $sql = "SELECT id FROM {exam_authorization} WHERE {$key} = :{$key} AND id != :id";
            if($DB->record_exists_sql($sql, $params)) {
                $errors[$key] = get_string('already_exists', 'local_exam_authorization');
            }
        }

        return $errors;
    }

}
