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
 * The main vitero configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_vitero_mod_form extends moodleform_mod {

    /**
     * Defines forms elements.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('viteroname', 'vitero'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'viteroname', 'vitero');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Adding the rest of vitero settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic.

        $mform->addElement('header', 'appointmentfields', get_string('appointmentfields', 'vitero'));

        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'vitero'),
                           array('step' => 15, 'optional' => 0));
        $mform->setDefault('starttime', time() + 3600);
        $mform->addElement('date_time_selector', 'endtime', get_string('endtime', 'vitero'), array('step' => 15, 'optional' => 0));
        $mform->setDefault('endtime', time() + 7200);
        $mform->addElement('text', 'startbuffer', get_string('startbuffer', 'vitero'), array('size' => '2'));
        $mform->setDefault('startbuffer', 15);
        $mform->setType('startbuffer', PARAM_INT);
        $mform->addElement('text', 'endbuffer', get_string('endbuffer', 'vitero'), array('size' => '2'));
        $mform->setDefault('endbuffer', 15);
        $mform->setType('endbuffer', PARAM_INT);
        $mform->addElement('select', 'roomsize', get_string('roomsize', 'vitero'));
        $mform->addElement('text', 'teamname', get_string('teamname', 'vitero'));
        $mform->addRule('teamname', null, 'required', null, 'client');
        $mform->setType('teamname', PARAM_TEXT);

        // Direct login.
        $mform->addElement('header', 'adminloginarea', get_string('adminlogin', 'vitero'));
        $mform->addElement('button', 'adminlogin', get_string('adminlogin', 'vitero'));
        $mform->addElement('static', 'nologinhint', '',  nl2br(s(get_string('nologinhint', 'vitero'))));

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Set the form data.
     *
     * @param object $default data.
     */
    public function set_data($default) {
        global $CFG;

        $mform = $this->_form;
        // Load or freeze room size.
        if ($default->instance) {
            $roomsizes = array();
            $roomsizes[$default->roomsize] = $default->roomsize;
            $roomsize = &$mform->getElement('roomsize');
            $roomsize->loadArray($roomsizes);
            $mform->freeze(array('roomsize'));
        } else {
            $roomsizes = vitero_get_available_roomsizes();
            $roomsize = &$mform->getElement('roomsize');
            $roomsize->loadArray($roomsizes);
        }

        // Freeze entire form if meeting is in the past.
        if (isset($default->endtime, $default->endbuffer) && $default->endtime > 0) {
            if ($default->endtime + (int)$default->endbuffer * 60 < time()) {
                $mform->freeze();
            }
        }

        // Give id to administration button (or hide if activity hasn't been created yet).
        if ($default->coursemodule) {
            $adminbutton = $mform->getElement('adminlogin');
            $url = $CFG->wwwroot . '/mod/vitero/adminlogin.php?cm=' . $default->coursemodule;
            $url = htmlentities($url);
            $btnstr = 'onclick="window.open(\'' . $url . '\', \'\', \'\');"';
            $adminbutton->updateAttributes($btnstr);
            $mform->removeElement('nologinhint');
        } else {
            $mform->removeElement('adminlogin');
        }

        parent::set_data($default);
    }

    /**
     * Validation
     *
     * @param  array $data
     * @param  array $files
     * @return array $errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate times.
        if (!$data['instance']) {
            if ($data['starttime'] >= $data['endtime']) {
                $errors['starttime'] = get_string('greaterstarttime', 'vitero');
            } else if ($data['starttime'] <= time()) {
                $errors['starttime'] = get_string('paststarttime', 'vitero');
            }
        }
        return $errors;
    }

}
