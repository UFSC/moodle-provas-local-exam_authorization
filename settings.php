<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig && isset($ADMIN)) {
    $settings = new admin_settingpage('local_exam_authorization_settings', get_string('pluginname', 'local_exam_authorization'));

    $settings->add(new admin_setting_configcheckbox('local_exam_authorization/disable_header_check',
                            get_string('disable_header_check', 'local_exam_authorization'),
                            get_string('disable_header_check_desc', 'local_exam_authorization'),
                            0));

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
    foreach($configs AS $cfg) {
        $line = array($cfg->identifier, $cfg->description, $cfg->url, $cfg->token);

        $buttons = array();
        $buttons[] = html_writer::link(new moodle_url('/local/exam_authorization/edit.php', array('id'=>$cfg->id, 'delete'=>1)),
            html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>get_string('delete'), 'title'=>get_string('delete'), 'class'=>'iconsmall')));
        $buttons[] = html_writer::link(new moodle_url('/local/exam_authorization/edit.php', array('id'=>$cfg->id)),
            html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/edit'), 'alt'=>get_string('edit'), 'title'=>get_string('edit'), 'class'=>'iconsmall')));
        $line[] = implode(' ', $buttons);

        $table->data[] = $line;
    }

    $str = html_writer::table($table);
    $str .= html_writer::link(new moodle_url('/local/exam_authorization/edit.php'), get_string('add'));

    $settings->add(new admin_setting_heading('local_exam_authorization_table', get_string('remote_moodles', 'local_exam_authorization'), $str));

    $ADMIN->add('localplugins', $settings);
}
