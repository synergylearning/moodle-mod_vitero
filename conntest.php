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
 * @package mod
 * @subpackage vitero
 * @author Yair Spielmann, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/accesslib.php');

global $PAGE, $USER, $CFG, $DB, $OUTPUT;

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('connectiontest', 'vitero'));
$PAGE->set_url($CFG->wwwroot.'/mod/vitero/conntest.php');

require_login(SITEID, false);
if (!is_siteadmin()) {
    require_capability('moodle/site:config', $systemcontext);
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('center');
if (vitero_connection_test()) {
    echo get_string('success');
} else {
    echo "<br />".get_string('conntest_failed', 'vitero');
}

echo '<center>'. "\n";
echo '<input type="button" onclick="self.close();" value="' . get_string('closewindow') . '" />';
echo '</center>';

echo $OUTPUT->box_end();
