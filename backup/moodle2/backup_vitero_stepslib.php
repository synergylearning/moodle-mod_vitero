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
 * Define all the backup steps that will be used by the backup_vitero_activity_task
 *
 * @package    mod_vitero
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete vitero structure for backup, with file and id annotations
 */
class backup_vitero_activity_structure_step extends backup_activity_structure_step {

    /**
     * define_structure
     * @return object Root element (vitero), wrapped into standard activity structure.
     */
    protected function define_structure() {
        // Define each element separated.
        $vitero = new backup_nested_element('vitero', array('id'), array(
            'name', 'intro', 'introformat',  'timemodified', 'meetingid', 'starttime', 'endtime', 'startbuffer',
             'endbuffer', 'teamid', 'roomsize', 'teamname'));

        // Define sources.
        $vitero->set_source_table('vitero', array('id' => backup::VAR_ACTIVITYID));

        // Define file annotations.
        $vitero->annotate_files('mod_vitero', 'intro', null);

        // Return the root element (vitero), wrapped into standard activity structure.
        return $this->prepare_activity_structure($vitero);
    }
}
