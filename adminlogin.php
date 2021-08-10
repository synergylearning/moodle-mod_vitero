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
 * Admin login page.
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'../../../config.php');

global $DB, $PAGE, $CFG, $OUTPUT;

require_once(__DIR__.'/locallib.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->libdir.'/accesslib.php');


$cmid = required_param('cm', PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('connectiontest', 'vitero'));
$PAGE->set_url($CFG->wwwroot.'/mod/vitero/adminlogin.php');

// Load module.
$cm = get_coursemodule_from_id('vitero', $cmid, 0, false, MUST_EXIST);
$vitero = $DB->get_record('vitero', array('id' => $cm->instance), '*', MUST_EXIST);

// Capability check.
require_login($cm->course, false, $cm);
$cmcontext = context_module::instance($cmid);
require_capability('mod/vitero:addinstance', $cmcontext);

// Create user if not exists, assign to team as teamleader and get session code.
if ($sessioncode = vitero_get_my_sessioncode($vitero, VITERO_ROLE_TEAMLEADER, 'vms')) {
    $url = vitero_get_baseurl() . '/user/cms/groupfolder.htm?groupId=' . $vitero->teamid . '&code=' . $sessioncode
        .'&fl=1&action=reload';
    redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('center');
echo get_string('novmssessioncode', 'vitero');
echo '<input type="button" onclick="self.close();" value="' . get_string('closewindow') . '" />';

echo $OUTPUT->box_end();
