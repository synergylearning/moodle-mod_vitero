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
 * The mod_vitero single soap client.
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vitero\local;

defined('MOODLE_INTERNAL') || die();

/**
 * A single (static) SOAP client.
 *
 * @package    mod_vitero
 * @copyright  2016 Yair Spielmann, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class singlesoapclient {

    /** @var soapclient the soap client container */
    private static $client;

    /**
     * Returns the SOAP client, initialises if needed.
     * @param bool $alwaysdebug
     * @return soapclient
     */
    public static function getclient($alwaysdebug = false) {
        if (null === self::$client) {
            global $CFG;

            $config = get_config('vitero');
            $baseurl = vitero_get_baseurl();
            $debug = false;
            if ($CFG->debug > DEBUG_NORMAL || $alwaysdebug) {
                $debug = true;
            }
            self::$client = new soapclient($baseurl, $config->adminusername, $config->adminpassword, $debug);
        }
        return self::$client;
    }

    /**
     * Refreshes the client.
     * @return void
     */
    public static function refreshclient() {
        self::$client = null;
    }
}
