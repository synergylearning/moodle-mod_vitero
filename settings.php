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

/**
 * Vitero module admin settings and defaults
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // General settings.
    $settings->add(
        new admin_setting_configtext(
            'vitero/adminusername',
            get_string('adminusername', 'vitero'),
            get_string('adminusername_desc', 'vitero'),
            'admin'
        )
    );
    $settings->add(
        new admin_setting_configpasswordunmask(
            'vitero/adminpassword',
            get_string('adminpassword', 'vitero'),
            get_string('adminpassword_desc', 'vitero'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'vitero/hostname',
            get_string('hostname', 'vitero'),
            get_string('hostname_desc', 'vitero'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'vitero/port',
            get_string('port', 'vitero'),
            get_string('port_desc', 'vitero'),
            '80'
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'vitero/root',
            get_string('root', 'vitero'),
            get_string('root_desc', 'vitero'),
            'vitero'
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'vitero/customername',
            get_string('customername', 'vitero'),
            get_string('customername_desc', 'vitero'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'vitero/customerlicense',
            get_string('customerlicense', 'vitero'),
            get_string('customerlicense_desc', 'vitero'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'vitero/customerid',
            get_string('customerid', 'vitero'),
            get_string('customerid_desc', 'vitero'),
            ''
        )
    );
    $settings->add(
        new admin_setting_configcheckbox(
            'vitero/syncavatars',
            get_string('syncavatars', 'vitero'),
            get_string('syncavatars_desc', 'vitero'),
            1
        )
    );

    // Connection test.
    $url = $CFG->wwwroot . '/mod/vitero/conntest.php';
    $url = htmlentities($url);
    $options = 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=0,width=700,height=300';
    $str = '<input type="button" onclick="window.open(\'' . $url . '\', \'\', \'' . $options . '\');" value="' .
            get_string('testconnection', 'vitero') . '" />';

    $settings->add(new admin_setting_heading('vitero_test', '', $str));
}
