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
 * Plugin webservices.
 *
 * @package    local_exam_authorization
 * @author     Antonio Carlos Mariani
 * @author     Juliao Gesse Fernandes
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/externallib.php');

class local_exam_authorization_external extends external_api {
    // --------------------------------------------------------------------------------------------------------

    /**
     * Describes the parameters for receive_data.
     *
     * @return external_function_parameters
     */
    public static function receive_data_parameters() {
        return new external_function_parameters(
            array(
                'exam_client_ip' => new external_value(PARAM_TEXT, 'exam_client_ip'),
                'exam_client_network' => new external_value(PARAM_TEXT, 'exam_client_network')
            )
        );
    }

    /**
     * Returns the status resulting from the operation, plus a descriptive message.
     *
     * @uses $DB This function uses the global $DB variable.
     * @param string $exam_client_ip
     * @param string $exam_client_network
     * @return array[string]
     */
    public static function receive_data($exam_client_ip, $exam_client_network) {
        $params = self::validate_parameters(self::receive_data_parameters(), array(
            'exam_client_ip' => $exam_client_ip,
            'exam_client_network' => $exam_client_network)
        );

        $ip_pattern = "|^([0-9]{1,3}\.){3}[0-9]{1,3}$|";
        $network_pattern = "|^([0-9]{1,3}\.){3}[0-9]{1,3}/[0-9]{1,2}$|";

        if (preg_match($ip_pattern, $params['exam_client_ip']) && preg_match($network_pattern, $params['exam_client_network'])) {
            global $DB;
            $real_ip = \local_exam_authorization\authorization::get_remote_addr();
            $rec = new stdClass();
            $rec->timemodified = time();

            if ($id = $DB->get_field('exam_client_hosts', 'id', array('ip' => $params['exam_client_ip'], 'network' => $params['exam_client_network'], 'real_ip' => $real_ip))) {
                $rec->id = $id;
                $DB->update_record('exam_client_hosts', $rec);
            } else {
                $rec->ip = $params['exam_client_ip'];
                $rec->network = $params['exam_client_network'];
                $rec->real_ip = $real_ip;
                $DB->insert_record('exam_client_hosts', $rec);
            }

            $reply = array('status' => 0, 'message' => get_string('receive_data_success', 'local_exam_authorization'));
        } else {
            $reply = array('status' => 1, 'message' => get_string('receive_data_invalid', 'local_exam_authorization'));
        }

        return $reply;
    }

    /**
     * Describes the receive_data return value.
     *
     * @return external_single_structure
     */
    public static function receive_data_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status'),
                'message' => new external_value(PARAM_TEXT, 'message')
            )
        );
    }

    // --------------------------------------------------------------------------------------------------------

    /**
     * Describes the parameters for receive_file.
     *
     * @return external_function_parameters
     */
    public static function receive_file_parameters() {
        return new external_function_parameters(
            array(
                'exam_client_livecd_version' => new external_value(PARAM_TEXT, 'exam_client_livecd_version'),
                'exam_client_livecd_build' => new external_value(PARAM_TEXT, 'exam_client_livecd_build'),
                'exam_client_ip' => new external_value(PARAM_TEXT, 'exam_client_ip'),
                'exam_client_network' => new external_value(PARAM_TEXT, 'exam_client_network'),
                'exam_client_user_email' => new external_value(PARAM_TEXT, 'exam_client_email'),
                'exam_client_user_description' => new external_value(PARAM_TEXT, 'exam_client_description')
            )
        );
    }

    /**
     * Returns the status resulting from the operation, plus a descriptive message.
     *
     * @param string $exam_client_livecd_version
     * @param string $exam_client_livecd_build
     * @param string $exam_client_ip
     * @param string $exam_client_network
     * @param string $exam_client_user_email
     * @param string $exam_client_user_description
     * @return array[string]
     */
    public static function receive_file($exam_client_livecd_version, $exam_client_livecd_build, $exam_client_ip, $exam_client_network, $exam_client_user_email, $exam_client_user_description) {
        $params = self::validate_parameters(self::receive_file_parameters(), array(
            'exam_client_livecd_version' => $exam_client_livecd_version,
            'exam_client_livecd_build' => $exam_client_livecd_build,
            'exam_client_ip' => $exam_client_ip,
            'exam_client_network' => $exam_client_network,
            'exam_client_user_email' => $exam_client_user_email,
            'exam_client_user_description' => $exam_client_user_description)
        );

        $upload_max_size_dir = get_config('local_exam_authorization', 'upload_max_size_dir');
        if (empty($upload_max_size_dir)) {
            $upload_max_size_dir = 100;
        }
        $upload_max_size_dir = $upload_max_size_dir * 1024 * 1024;

        $upload_dir = get_config('local_exam_authorization', 'upload_files_dir');

        if (empty($upload_dir)) {
            $upload_dir = '/var/tmp/moodle';
        }

        if (! file_exists($upload_dir)) {
            if (! mkdir($upload_dir)) {
                $reply = array('status' => 1, 'message' => get_string('upload_error_create_dir', 'local_exam_authorization', $upload_dir));
                return $reply;
            }
        }

        if (! is_writable($upload_dir)) {
            $reply = array('status' => 1, 'message' => get_string('upload_error_no_permission', 'local_exam_authorization', $upload_dir));
            return $reply;
        }

        if (isset($_FILES['file'])) {
            $current_dir_size = self::get_dir_size($upload_dir);
            if ($current_dir_size < $upload_max_size_dir) {
                $real_ip = \local_exam_authorization\authorization::get_remote_addr();
                $timestamp = date("Ymd-H\hi\ms\s");
                $host = gethostbyaddr($real_ip);
                $filename = $timestamp . '_' . $real_ip . '_' . $params['exam_client_ip'] . '_' . $_FILES['file']['name'];
                $size = $_FILES['file']['size'];

                if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . '/' . $filename)) {
                    $log_file = $upload_dir . '/' . $filename . '.txt';
                    $timestamp = date("d/m/Y - H:i:s");
                    $data = get_string('report_date', 'local_exam_authorization') . $timestamp . "\n" .
                            get_string('report_client_valid_ip', 'local_exam_authorization') . $real_ip . "\n" .
                            get_string('report_client_host', 'local_exam_authorization') . $host . "\n" .
                            get_string('report_client_ip', 'local_exam_authorization') . $params['exam_client_ip'] . "\n" .
                            get_string('report_client_network', 'local_exam_authorization') . $params['exam_client_network'] . "\n" .
                            get_string('report_client_livecd_version', 'local_exam_authorization') . $params['exam_client_livecd_version'] . " Build " . $params['exam_client_livecd_build'] . "\n" .
                            get_string('report_client_user_email', 'local_exam_authorization') . $params['exam_client_user_email'] . "\n" .
                            get_string('report_client_user_description', 'local_exam_authorization') . $params['exam_client_user_description'] . "\n";
                    file_put_contents($log_file, $data, 0);

                    $file = $filename . " (" . intval($size/1024) . " KiB)";
                    $reply = array('status' => 0, 'message' => get_string('upload_success', 'local_exam_authorization', $file));
                } else {
                    $reply = array('status' => 1, 'message' => get_string('upload_error_saving', 'local_exam_authorization'));
                }
            } else {
                $current_dir_size = intval($current_dir_size/(1024*1024));
                $reply = array('status' => 1, 'message' =>  get_string('upload_error_over_quota', 'local_exam_authorization', $current_dir_size));
            }
        } else {
            $reply = array('status' => 1, 'message' => get_string('upload_error_no_file', 'local_exam_authorization'));
        }

        return $reply;
    }

    /**
     * Describes the receive_file return value.
     *
     * @return external_single_structure
     */
    public static function receive_file_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'status'),
                'message' => new external_value(PARAM_TEXT, 'message')
            )
        );
    }

    // --------------------------------------------------------------------------------------------------------

    // Auxiliary functions

    /**
     * Returns the total size from the sum of the size of all files contained in the specified directory.
     *
     * @param string $dir Directory
     * @return int Total bytes.
     */
    private static function get_dir_size($dir) {
        $total_size = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file)
            $total_size += $file->getSize();

        return $total_size;
    }

}
