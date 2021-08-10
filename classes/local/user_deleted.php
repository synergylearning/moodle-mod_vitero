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
 * User deleted event observer.
 */
class user_deleted {

    /**
     * Delete vetero users on user delete.
     *
     * @param  \core\event\user_deleted $event Deleted user event.
     */
    public static function observe_user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $data = $event->get_data();
        $user = $event->get_record_snapshot('user', $data['objectid']);

        // Remove all user tracking.
        $DB->delete_records('vitero_remusers', array('userid' => $user->id));
    }
}
