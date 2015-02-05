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
 *
 *
 * @copyright 2014 Yair Spielmann, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * A single (static) SOAP client.
 * @package    mod
 * @subpackage vitero
 * @copyright  2015 Yair Spielmann, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_vitero_singlesoapclient {

    /** @var mod_vitero_soapclient the soap client container */
    private static $client;

    /*
     * Returns the SOAP client, initialises if needed.
     * @param bool $alwaysdebug
     * @return mod_vitero_soapclient
     */
    public static function getclient($alwaysdebug = false) {
        if (!isset(self::$client) || is_null(self::$client)) {
            global $CFG;

            $config = get_config('vitero');
            $baseurl = vitero_get_baseurl();
            $debug = false;
            if ($CFG->debug > DEBUG_NORMAL || $alwaysdebug) {
                $debug = true;
            }
            self::$client = new mod_vitero_soapclient($baseurl, $config->adminusername, $config->adminpassword, $debug);
        }
        return self::$client;
    }

    /*
     * Refreshes the client.
     * @return void
     */
    public static function refreshclient() {
        self::$client = null;
    }
}