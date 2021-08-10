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
 * Library of interface functions and constants for module vitero
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the vitero specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/locallib.php');

global $CFG;

require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * VITERO_ROLE_PARTICIPANT - DB role id to represent Vitero parcitipant.
 */
define('VITERO_ROLE_PARTICIPANT', 0);

/**
 * VITERO_ROLE_ASSISTANT - DB role id to represent Vitero assistant.
 */
define('VITERO_ROLE_ASSISTANT', 1);

/**
 * VITERO_ROLE_TEAMLEADER - DB role id to represent Vitero team leader.
 */
define('VITERO_ROLE_TEAMLEADER', 2);

/**
 * VITERO_ROLE_AUDIENCE - role id of DB role id to represent Vitero audience.
 */
define('VITERO_ROLE_AUDIENCE', 3);

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function vitero_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the vitero into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param  stdClass $vitero An object from the form in mod_form.php
 * @param  mod_vitero_mod_form|null $mform
 * @return int The id of the newly inserted vitero record
 */
function vitero_add_instance(stdClass $vitero, mod_vitero_mod_form $mform = null) {
    global $DB;

    $vitero->timecreated = time();

    // Append random number to the teamname.
    $vitero->teamname .= '_MOODLE_'.random_string(10);

    // Create team.
    if (!$vitero->teamid = vitero_create_team($vitero->teamname)) {
        throw new moodle_exception('cannotcreateteam', 'mod_vitero');
    }

    try {
        // Create meeting.
        if (!$vitero->meetingid = vitero_create_meeting($vitero)) {
            throw new moodle_exception('cannotcreatemeeting', 'mod_vitero');
        }
    } catch (moodle_exception $exception) {
        try {
            vitero_delete_team($vitero->teamid);
        } catch (Exception $e) {
            // It's more important to report cannot create meeting than cannot delete team.
            throw $exception;
        }
        throw $exception;
    }

    if (!$recid = $DB->insert_record('vitero', $vitero)) {
        return false;
    }

    // Add event to calendar.
    $event = new stdClass();
    $event->name = $vitero->name;
    $event->description = format_module_intro('vitero', $vitero, $vitero->coursemodule);
    $event->courseid = $vitero->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->instance = $recid;
    $event->eventtype = 'vitero';
    $event->timestart = $vitero->starttime;
    $event->timeduration = $vitero->endtime - $vitero->starttime;
    $event->visible = 1;
    $event->modulename = 'vitero';
    calendar_event::create($event);

    return $recid;
}

/**
 * Updates an instance of the vitero in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param  stdClass $vitero An object from the form in mod_form.php
 * @param  mod_vitero_mod_form|null $mform
 * @return boolean Success/Fail
 */
function vitero_update_instance(stdClass $vitero, mod_vitero_mod_form $mform = null) {
    global $DB;

    $vitero->timemodified = time();
    $vitero->id = $vitero->instance;

    // Get old details.
    if (!$old = $DB->get_record('vitero', array('id' => $vitero->id))) {
        return false;
    }
    $vitero->meetingid = $old->meetingid;

    // Update team name if needed.
    if ($vitero->teamname != $old->teamname) {
        if (!vitero_update_team($old->teamid, $vitero->teamname)) {
            return false;
        }
    }

    // Update calendar.
    $param = array(
        'courseid' => $vitero->course,
        'instance' => $vitero->id,
        'groupid' => 0,
        'modulename' => 'vitero'
    );
    $eventid = $DB->get_field('event', 'id', $param);
    if (!empty($eventid)) {
        $event = new stdClass();
        $event->id = $eventid;
        $event->name = $vitero->name;
        $event->description = format_module_intro('vitero', $vitero, $vitero->coursemodule);
        $event->courseid = $vitero->course;
        $event->groupid = 0;
        $event->userid = 0;
        $event->instance = $vitero->id;
        $event->eventtype = 'vitero';
        $event->timestart = $vitero->starttime;
        $event->timeduration = $vitero->endtime - $vitero->starttime;
        $event->visible = 1;
        $event->modulename = 'vitero';
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->update($event);
    }

    // Update meeting.
    if (!vitero_update_meeting($vitero)) {
        return false;
    }

    return $DB->update_record('vitero', $vitero);
}

/**
 * Removes an instance of the vitero from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function vitero_delete_instance($id) {
    global $DB;

    if (!$vitero = $DB->get_record('vitero', array('id' => $id))) {
        return false;
    }

    // Update calendar event.
    $param = array('courseid' => $vitero->course, 'instance' => $vitero->id,
        'groupid' => 0, 'modulename' => 'vitero');
    $eventid = $DB->get_field('event', 'id', $param);

    if (!empty($eventid)) {
        $event = calendar_event::load($eventid);
        $event->delete();
    }

    $DB->delete_records('vitero', array('id' => $vitero->id));

    // Delete meeting, if no other activity links to this.
    $allmeetings = $DB->count_records('vitero', array('meetingid' => $vitero->meetingid));

    if (!$allmeetings) {
        if (!vitero_delete_meeting($vitero)) {
            return false;
        }
        // Delete team.
        vitero_delete_team($vitero->teamid);
    }
    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param  object $course
 * @param  object $user
 * @param  object $mod
 * @param  object $vitero
 * @return object;
 */
function vitero_user_outline($course, $user, $mod, $vitero) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $vitero the module instance record
 * @return void, is supposed to echp directly
 */
function vitero_user_complete($course, $user, $mod, $vitero) {

}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in vitero activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param  object $course
 * @param  bool   $viewfullnames
 * @param  int    $timestart
 * @return bool
 */
function vitero_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  // True if anything was printed, otherwise false.
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@see vitero_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function vitero_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {

}

/**
 * Prints single activity item prepared by {@see vitero_get_recent_mod_activity()}
 *
 * @param int $activity
 * @param int $courseid
 * @param string $detail
 * @param array $modnames
 * @param bool $viewfullnames
 * @return void
 */
function vitero_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {

}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return bool
 * */
function vitero_cron() {
    return true;
}

/**
 * Returns an array of users who are participanting in this vitero
 *
 * Must return an array of users who are participants for a given instance
 * of vitero. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $viteroid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function vitero_get_participants($viteroid) {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * @return array
 */
function vitero_get_extra_capabilities() {
    return array();
}

// Gradebook API.

/**
 * Is a given scale used by the instance of vitero?
 *
 * This function returns if a scale is being used by one vitero
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $viteroid ID of an instance of this module
 * @param int $scaleid ID of scale
 * @return bool true if the scale is used by the given vitero instance
 */
function vitero_scale_used($viteroid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of vitero.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid int
 * @return boolean true if the scale is used by any vitero instance
 */
function vitero_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Creates or updates grade item for the give vitero instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $vitero instance object with extra cmidnumber and modname property
 * @return void
 */
function vitero_grade_item_update(stdClass $vitero) {
}

/**
 * Update vitero grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $vitero instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function vitero_update_grades(stdClass $vitero, $userid = 0) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $grades = array(); // Populate array of grade objects indexed by userid.

    grade_update('mod/vitero', $vitero->course, 'mod', 'vitero', $vitero->id, 0, $grades);
}

// File API.

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@see file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function vitero_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * Serves the files from the vitero file areas
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return void this should never return to the caller
 */
function vitero_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload) {
    send_file_not_found();
}

// Navigation API.

/**
 * Extends the global navigation tree by adding vitero nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param  navigation_node $navref An object representing the navigation tree node of the vitero module instance
 * @param  stdclass        $course
 * @param  stdclass        $module
 * @param  cm_info         $cm
 * @return void
 */
function vitero_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {

}

/**
 * Extends the settings navigation with the vitero settings
 *
 * This function is called when the context for the page is a vitero module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@see settings_navigation}
 * @param navigation_node $viteronode {@see navigation_node}
 */
function vitero_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $viteronode=null) {

}
