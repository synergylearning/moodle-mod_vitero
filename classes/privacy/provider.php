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
 * Privacy Subsystem implementation for mod_vitero.
 *
 * @package    mod_vitero
 * @copyright  2019 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vitero\privacy;

use core_privacy\local\request\contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the Vitero activity module.
 *
 * @copyright  2019 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider

{

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $items The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items) : collection {
        $items->add_database_table('vitero_remusers', [
            'userid' => 'privacy:metadata:vitero_remusers:userid',
            'viteroid' => 'privacy:metadata:vitero_remusers:viteroid',
            'lastemail' => 'privacy:metadata:vitero_remusers:lastemail',
            'lastfirstname' => 'privacy:metadata:vitero_remusers:lastfirstname',
            'lastlastname' => 'privacy:metadata:vitero_remusers:lastlastname',
            'timecreated' => 'privacy:metadata:vitero_remusers:timecreated',
            'timeupdated' => 'privacy:metadata:vitero_remusers:timeupdated',
        ], 'privacy:metadata:vitero_remusers');

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of Vitero, details are only stored at the system level
     *
     * @param   int         $userid     The user to search.
     * @return  contextlist $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists('vitero_remusers', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_system::class)) {
            return;
        }

        $sql = "SELECT userid FROM {vitero_remusers}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * includes_system_context
     *
     * @param  approved_contextlist $contextlist
     * @return bool
     */
    private static function includes_system_context(approved_contextlist $contextlist) {
        foreach ($contextlist->get_contexts() as $context) {
            if (is_a($context, \context_system::class)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!self::includes_system_context($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $data = $DB->get_record('vitero_remusers', ['userid' => $user->id]);
        writer::with_context(\context_system::instance())->export_data([], $data);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   \context                 $context   The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // Check that this is a context_system.
        if (!is_a($context, \context_system::class)) {
            return;
        }

        $DB->delete_records('vitero_remusers');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (!self::includes_system_context($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $DB->delete_records('vitero_remusers', ['userid' => $user->id]);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!is_a($context, \context_system::class)) {
            return;
        }
        $DB->delete_records_list('vitero_remusers', 'userid', $userlist->get_userids());
    }
}
