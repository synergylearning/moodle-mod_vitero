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
 * User details updater class for Vitero.
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vitero\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Class user_updated event observer.
 */
class user_updated
{
    /**
     * observe_user_updated
     * @param  \core\event\user_updated $event [description]
     * @return void
     */
    public static function observe_user_updated(\core\event\user_updated $event) {
        global $CFG, $DB;

        $data = $event->get_data();
        $user = $event->get_record_snapshot('user', $data['objectid']);
        if (!$existing = $DB->get_record('vitero_remusers', array('userid' => $user->id))) {
            // Not a tracked user, nothing to do.
            return;
        }

        // Check if anything has changed.
        $checkfields = array('email', 'firstname', 'lastname');
        $changed = false;
        foreach ($checkfields as $fieldname) {
            if ($existing->{'last'.$fieldname} != $user->{$fieldname}) {
                $changed = true;
                break;
            }
        }
        if (!$changed) {
            // No need to update.
            return;
        }

        require_once($CFG->dirroot . '/mod/vitero/locallib.php');
        vitero_update_remote_details($existing->viteroid, $user);
        vitero_update_remuser($user);
    }
}
