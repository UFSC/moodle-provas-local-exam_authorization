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
 * Plugin webservices - functions and services definitions
 *
 * @package    local_exam_authorization
 * @author     Antonio Carlos Mariani
 * @author     Juliao Gesse Fernandes
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
        'local_exam_authorization_receive_data' => array(
                'classname'   => 'local_exam_authorization_external',
                'methodname'  => 'receive_data',
                'classpath'   => 'local/exam_authorization/externallib.php',
                'description' => 'Write data received by POST from the LiveCD client.',
                'type'        => 'write',
        ),

        'local_exam_authorization_receive_file' => array(
                'classname'   => 'local_exam_authorization_external',
                'methodname'  => 'receive_file',
                'classpath'   => 'local/exam_authorization/externallib.php',
                'description' => 'Write data and file received by POST from the LiveCD client.',
                'type'        => 'read',
        )
);

$services = array(
       'Moodle Exam' => array(
                'functions' => array ('local_exam_authorization_receive_data',
                                      'local_exam_authorization_receive_file',
                                     ),
                'restrictedusers' => 1,
                'enabled'=>1,
        )
);
