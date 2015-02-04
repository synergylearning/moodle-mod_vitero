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
 * Prints a particular instance of vitero
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage vitero
 * @copyright  2015 Yair Spielmann, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/// (Replace vitero with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // vitero instance ID - it should be named as the first character of the module

if ($id) {
    $cm         = get_coursemodule_from_id('vitero', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $vitero  = $DB->get_record('vitero', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $vitero  = $DB->get_record('vitero', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $vitero->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('vitero', $vitero->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

$context = context_module::instance($cm->id);

/// Print the page header
$PAGE->set_context($context);
$PAGE->set_url('/mod/vitero/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($vitero->name));
$PAGE->set_heading(format_string($course->fullname));

require_login($course, true, $cm);

//Assign role
if (has_capability('mod/vitero:teamleader', $context)) {
    $roleassign = VITERO_ROLE_TEAMLEADER;
} else {
    $roleassign = VITERO_ROLE_PARTICIPANT;
}

$params = array(
    'objectid' => $vitero->id,
    'context' => $context,
    'courseid' => $course->id,
);
$event = \mod_vitero\event\course_module_viewed::create($params);
$event->trigger();


// Output starts here
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('viteroappointment', 'vitero'));

if ($vitero->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('vitero', $vitero, $cm->id), 'generalbox mod_introbox', 'viterointro');
}

//Display times:
$timesbox = get_string('starttime', 'vitero').': ' . userdate($vitero->starttime) . '<br />'
    . get_string('endtime', 'vitero') . ': '.userdate($vitero->endtime);
echo $OUTPUT->box($timesbox, 'generalbox mod_introbox');

if (has_capability('mod/vitero:participant', $context)
        || has_capability('mod/vitero:teamleader', $context)) {
    $linkbox = '';
    if ($vitero->starttime - (int)$vitero->startbuffer * 60 > time()) {
        $linkbox = get_string('notstartedyet', 'vitero');
    } else if ($vitero->endtime + (int)$vitero->endbuffer * 60 < time()) {
        $linkbox = get_string('alreadyover', 'vitero');
    } else {
        //Get session code:
        if (!$sessioncode = vitero_get_my_sessioncode($vitero, $roleassign)) {
            print_error('cannotobtainsessioncode', 'vitero');
        }
        $baseurl = vitero_get_baseurl();
        $fullurl = $baseurl.'/start.htm?sessionCode='.$sessioncode;
        $linkbox = '<a href="'.$fullurl.'" target="_blank">'.get_string('clickhereformeeting', 'vitero').'</a>';
    }
    echo $OUTPUT->box($linkbox, 'generalbox mod_introbox');
}

// Finish the page
echo $OUTPUT->footer();
