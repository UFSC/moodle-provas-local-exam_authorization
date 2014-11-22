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
 * Plugin settings
 *
 * @package    local_exam_authorization
 * @author     Antonio Carlos Mariani
 * @copyright  2010 onwards Universidade Federal de Santa Catarina (http://www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig && isset($ADMIN)) {
    $settings = new admin_settingpage('local_exam_authorization_settings', get_string('pluginname', 'local_exam_authorization'));

    $settings->add(new admin_setting_configcheckbox('local_exam_authorization/disable_header_check',
                            get_string('disable_header_check', 'local_exam_authorization'),
                            get_string('disable_header_check_desc', 'local_exam_authorization'),
                            '0'));

    $settings->add(new admin_setting_configtext('local_exam_authorization/header_version',
                            get_string('header_version', 'local_exam_authorization'),
                            get_string('header_version_descr', 'local_exam_authorization'),
                            '', PARAM_TEXT));

    $client_host_timeout_options = array(0=>0, 1=>1, 2=>2, 3=>3, 5=>5, 10=>10, 15=>15, 30=>30);
    $settings->add(new admin_setting_configselect('local_exam_authorization/client_host_timeout',
                            get_string('client_host_timeout', 'local_exam_authorization'),
                            get_string('client_host_timeout_descr', 'local_exam_authorization'),
                            10, $client_host_timeout_options));

    $settings->add(new admin_setting_configtext('local_exam_authorization/ip_ranges_editors',
                            get_string('ip_ranges_editors', 'local_exam_authorization'),
                            get_string('ip_ranges_editors_descr', 'local_exam_authorization'),
                            '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_exam_authorization/ip_ranges_students',
                            get_string('ip_ranges_students', 'local_exam_authorization'),
                            get_string('ip_ranges_students_descr', 'local_exam_authorization'),
                            '', PARAM_TEXT));

    $context = context_system::instance();
    $role_names = role_fix_names(get_all_roles($context), $context);
    $sql = "SELECT *
              FROM {role}
             WHERE shortname NOT IN ('manager', 'guest', 'user', 'frontpage', 'student', 'editingteacher', 'coursecreator')";
    $roles = $DB->get_records_sql($sql);
    $roles_menu = array(0=>get_string('none'));
    foreach ($roles as $r) {
        $roles_menu[$r->id] = $role_names[$r->id]->localname;
    }

    $settings->add(new admin_setting_configselect('local_exam_authorization/proctor_roleid',
                            get_string('proctor_roleid', 'local_exam_authorization'),
                            get_string('proctor_roleid_descr', 'local_exam_authorization'),
                            0, $roles_menu));

    $settings->add(new admin_setting_configselect('local_exam_authorization/monitor_roleid',
                            get_string('monitor_roleid', 'local_exam_authorization'),
                            get_string('monitor_roleid_descr', 'local_exam_authorization'),
                            0, $roles_menu));

    $auth_menu = array();
    $auth_menu[''] = get_string('default_auth_plugin', 'local_exam_authorization');
    foreach(get_enabled_auth_plugins() AS $n => $auth) {
        $auth_menu[$auth] = $auth;
    }

    $settings->add(new admin_setting_configselect('local_exam_authorization/auth_plugin',
                            get_string('auth_plugin', 'local_exam_authorization'),
                            get_string('auth_plugin_descr', 'local_exam_authorization'),
                            '', $auth_menu));

    $settings->add(new admin_setting_configcheckbox('local_exam_authorization/update_password',
                            get_string('update_password', 'local_exam_authorization'),
                            get_string('update_password_descr', 'local_exam_authorization'),
                            '1'));
    // -------------------------------------------------------------------------------------------------

    $table = new html_table();
    $table->head  = array(get_string('identifier', 'local_exam_authorization') . $OUTPUT->help_icon('identifier', 'local_exam_authorization'),
                          get_string('description', 'local_exam_authorization') . $OUTPUT->help_icon('description', 'local_exam_authorization'),
                          get_string('url', 'local_exam_authorization') . $OUTPUT->help_icon('url', 'local_exam_authorization'),
                          get_string('token', 'local_exam_authorization') . $OUTPUT->help_icon('token', 'local_exam_authorization'),
                          get_string('edit'));
    $table->id = 'exam_authorization';
    $table->attributes['class'] = 'admintable generaltable';

    $table->data = array();
    $configs = $DB->get_records('exam_authorization');
    foreach ($configs AS $cfg) {
        if ($cfg->enable) {
            $line = array($cfg->identifier, $cfg->description, $cfg->url, $cfg->token);
        } else {
            $line = array();
            $line[] = html_writer::tag('span', $cfg->identifier, array('class'=>'dimmed_text'));
            $line[] = html_writer::tag('span', $cfg->description, array('class'=>'dimmed_text'));
            $line[] = html_writer::tag('span', $cfg->url, array('class'=>'dimmed_text'));
            $line[] = html_writer::tag('span', $cfg->token, array('class'=>'dimmed_text'));
        }

        $buttons = array();
        if (!$DB->record_exists_sql("SELECT 1 FROM {course} WHERE shortname LIKE '{$cfg->identifier}_%'")) {
            $buttons[] = html_writer::link(new moodle_url('/local/exam_authorization/edit.php', array('id'=>$cfg->id, 'action'=>'delete')),
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>get_string('delete'), 'title'=>get_string('delete'), 'class'=>'iconsmall')));
        }
        $buttons[] = html_writer::link(new moodle_url('/local/exam_authorization/edit.php', array('id'=>$cfg->id)),
            html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('edit'), 'title'=>get_string('edit'), 'class'=>'iconsmall')));
        if ($cfg->enable) {
            $buttons[] = html_writer::link(new moodle_url('/local/exam_authorization/edit.php', array('id'=>$cfg->id, 'action'=>'disable')),
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/show'), 'alt'=>get_string('disable'), 'title'=>get_string('disable'), 'class'=>'iconsmall')));
        } else {
            $buttons[] = html_writer::link(new moodle_url('/local/exam_authorization/edit.php', array('id'=>$cfg->id, 'action'=>'enable')),
                html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/hide'), 'alt'=>get_string('enable'), 'title'=>get_string('enable'), 'class'=>'iconsmall')));
        }
        $line[] = implode(' ', $buttons);

        $table->data[] = $line;
    }

    $str = html_writer::table($table);
    $str .= html_writer::link(new moodle_url('/local/exam_authorization/edit.php'), get_string('add'));

    $settings->add(new admin_setting_heading('local_exam_authorization_table', get_string('remote_moodles', 'local_exam_authorization'), $str));

    $ADMIN->add('localplugins', $settings);
}
