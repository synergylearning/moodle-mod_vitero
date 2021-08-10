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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'../../../config.php');
require_once(__DIR__.'/lib.php');

global $DB, $OUTPUT, $PAGE;

$id = required_param('id', PARAM_INT); // Course ID.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$coursecontext = context_course::instance($course->id);

$event = \mod_vitero\event\course_module_instance_list_viewed::create(array('context' => $coursecontext));
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/vitero/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

require_course_login($course);

// Trigger instances list viewed event.
$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_vitero\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();
echo $OUTPUT->header();

if (!$viteros = get_all_instances_in_course('vitero', $course)) {
    notice(get_string('noviteros', 'vitero'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

if ($course->format == 'weeks') {
    $table->head  = array(get_string('week'), get_string('name'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array(get_string('topic'), get_string('name'));
    $table->align = array('center', 'left', 'left', 'left');
} else {
    $table->head  = array(get_string('name'));
    $table->align = array('left', 'left', 'left');
}

foreach ($viteros as $vitero) {
    if (!$vitero->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/vitero.php', array('id' => $vitero->coursemodule)),
            format_string($vitero->name),
            array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/vitero.php', array('id' => $vitero->coursemodule)),
            format_string($vitero->name));
    }

    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = array($vitero->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', 'vitero'), 2);
echo html_writer::table($table);
echo $OUTPUT->footer();
