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
 * This file keeps track of upgrades to the vitero module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute vitero upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_vitero_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2013021400) {

        // Define table vitero_attendees to be dropped.
        $table = new xmldb_table('vitero_attendees');

        // Conditionally launch drop table for vitero_attendees.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Vitero savepoint reached.
        upgrade_mod_savepoint(true, 2013021400, 'vitero');
    }

    if ($oldversion < 2016072800) {

        // Define table vitero_remusers to be created.
        $table = new xmldb_table('vitero_remusers');

        // Adding fields to table vitero_remusers.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('viteroid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('lastemail', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table vitero_remusers.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table vitero_remusers.
        $table->add_index('userid', XMLDB_INDEX_UNIQUE, array('userid'));
        $table->add_index('viteroid', XMLDB_INDEX_UNIQUE, array('viteroid'));

        // Conditionally launch create table for vitero_remusers.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Vitero savepoint reached.
        upgrade_mod_savepoint(true, 2016072800, 'vitero');
    }

    if ($oldversion < 2016081900) {

        // Define field lastfirstname to be added to vitero_remusers.
        $table = new xmldb_table('vitero_remusers');

        // Conditionally launch add field lastfirstname.
        $field = new xmldb_field('lastfirstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'lastemail');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Conditionally launch add field lastlastname.
        $field = new xmldb_field('lastlastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'lastfirstname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Vitero savepoint reached.
        upgrade_mod_savepoint(true, 2016081900, 'vitero');
    }

    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
