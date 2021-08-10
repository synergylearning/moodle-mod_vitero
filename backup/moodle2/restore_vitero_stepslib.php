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
 * Define all the restore steps that will be used by the restore_vitero_activity_task.
 *
 * @package    mod_vitero
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one vitero activity
 * @return restore_path_element
 */
class restore_vitero_activity_structure_step extends restore_activity_structure_step {

    /**
     * define_structure
     * @return object Paths wrapped into standard activity structure.
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('vitero', '/activity/vitero');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process vitero data.
     *
     * @param array $data
     */
    protected function process_vitero($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = time();

        // Insert the vitero record.
        $newitemid = $DB->insert_record('vitero', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * after_execute
     *
     * Add vitero related files, no need to match by itemname (just internally handled context).
     */
    protected function after_execute() {
        $this->add_related_files('mod_vitero', 'intro', null);
        $this->add_related_files('mod_vitero', 'content', null);
    }
}
