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
 * Vitero backup task.
 *
 * @package    mod_vitero
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/vitero/backup/moodle2/backup_vitero_stepslib.php'); // Because it exists (must).

/**
 * Provides all the settings and steps to perform one complete backup of the activity.
 */
class backup_vitero_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_vitero_activity_structure_step('vitero_structure', 'vitero.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links.
     * @param  string $content
     * @return string Encoded content.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of vitero.
        $search = '/('.$base."\/mod\/vitero\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@viteroINDEX*$2@$', $content);

        // Link to choice view by moduleid.
        $search = '/('.$base."\/mod\/vitero\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@viteroVIEWBYID*$2@$', $content);

        return $content;
    }
}
