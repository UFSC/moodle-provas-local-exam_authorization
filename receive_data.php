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
 * A script to receive data from CD.
 *
 * Obtém dados da rede local e funciona também como keep-alive.
 *
 * @package    local_exam_authorization
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$ip = isset($_SERVER["HTTP_MOODLE_PROVAS_IP"]) ? $_SERVER["HTTP_MOODLE_PROVAS_IP"] : '';
$network = isset($_SERVER["HTTP_MOODLE_PROVAS_NETWORK"]) ? $_SERVER["HTTP_MOODLE_PROVAS_NETWORK"] : '';

$ip_pattern = "|^([0-9]{1,3}\.){3}[0-9]{1,3}$|";
$network_pattern = "|^([0-9]{1,3}\.){3}[0-9]{1,3}/[0-9]{1,2}$|";

if (preg_match($ip_pattern, $ip) && preg_match($network_pattern, $network)) {
    $real_ip = \local_exam_authorization\authorization::get_remote_addr();
    $rec = new stdClass();
    $rec->timemodified = time();

    if ($id = $DB->get_field('exam_client_hosts', 'id', array('ip'=>$ip, 'network'=>$network, 'real_ip'=>$real_ip))) {
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
