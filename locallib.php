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
 * Internal library of functions for module vitero
 *
 * All the vitero specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_vitero\local\singlesoapclient;

defined('MOODLE_INTERNAL') || die();

/**
 * Gets the Vitero id for the server time zone, or closest to it.
 * @return string
 */
function vitero_get_moodle_timezone() {
    $timezones = array(
        'Pacific/Apia' => -39600,
        'Pacific/Fakaofo' => -36000,
        'America/Adak' => -36000,
        'Pacific/Marquesas' => -30600,
        'Pacific/Gambier' => -32400,
        'America/Anchorage' => -32400,
        'America/Ensenada' => -28800,
        'Pacific/Pitcairn' => -28800,
        'America/Dawson' => -28800,
        'America/Chihuahua' => -25200,
        'America/Boise' => -25200,
        'America/Dawson_Creek' => -25200,
        'America/Belize' => -21600,
        'Pacific/Easter' => -21600,
        'America/Chicago' => -21600,
        'America/Cancun' => -21600,
        'America/Havana' => -18000,
        'America/Detroit' => -18000,
        'America/Atikokan' => -18000,
        'America/Caracas' => -12600,
        'America/Glace_Bay' => -14400,
        'America/Campo_Grande' => -14400,
        'America/Goose_Bay' => -14400,
        'America/Anguilla' => -14400,
        'America/Asuncion' => -14400,
        'America/Santiago' => -14400,
        'Atlantic/Stanley' => -14400,
        'America/St_Johns' => -9000,
        'America/Montevideo' => -10800,
        'America/Miquelon' => -10800,
        'America/Sao_Paulo' => -10800,
        'America/Godthab' => -10800,
        'America/Argentina/Buenos_Aires' => -10800,
        'America/Araguaina' => -10800,
        'America/Noronha' => -7200,
        'Atlantic/Cape_Verde' => -3600,
        'America/Scoresbysund' => -3600,
        'Atlantic/Canary' => 0,
        'Africa/Abidjan' => 0,
        'Africa/Ceuta' => 3600,
        'Africa/Windhoek' => 3600,
        'Africa/Algiers' => 3600,
        'Africa/Tunis' => 3600,
        'Africa/Cairo' => 7200,
        'Asia/Amman' => 7200,
        'Asia/Gaza' => 7200,
        'Asia/Beirut' => 7200,
        'Asia/Jerusalem' => 7200,
        'Europe/Kaliningrad' => 7200,
        'Asia/Damascus' => 7200,
        'Asia/Istanbul' => 7200,
        'Africa/Blantyre' => 7200,
        'Africa/Addis_Ababa' => 10800,
        'Europe/Moscow' => 10800,
        'Asia/Riyadh87' => 11220,
        'Asia/Tehran' => 12600,
        'Asia/Dubai' => 14400,
        'Asia/Baku' => 14400,
        'Indian/Mauritius' => 14400,
        'Asia/Yerevan' => 14400,
        'Asia/Kabul' => 16200,
        'Asia/Aqtau' => 18000,
        'Asia/Yekaterinburg' => 18000,
        'Asia/Calcutta' => 19800,
        'Asia/Katmandu' => 20700,
        'Asia/Novosibirsk' => 21600,
        'Asia/Almaty' => 21600,
        'Asia/Rangoon' => 23400,
        'Asia/Bangkok' => 25200,
        'Asia/Krasnoyarsk' => 25200,
        'Asia/Brunei' => 28800,
        'Asia/Irkutsk' => 28800,
        'Australia/Eucla' => 31500,
        'Asia/Dili' => 32400,
        'Asia/Yakutsk' => 32400,
        'Australia/Darwin' => 34200,
        'Australia/Adelaide' => 34200,
        'Asia/Sakhalin' => 36000,
        'Australia/Brisbane' => 36000,
        'Australia/ACT' => 36000,
        'Australia/LHI' => 37800,
        'Pacific/Efate' => 39600,
        'Asia/Magadan' => 39600,
        'Pacific/Norfolk' => 41400,
        'Asia/Anadyr' => 43200,
        'Pacific/Fiji' => 43200,
        'Pacific/Auckland' => 43200,
        'Pacific/Chatham' => 45900,
        'Pacific/Enderbury' => 46800,
        'Pacific/Kiritimati' => 50400,
    );

    $offset = gmmktime(0, 0, 0, 1, 1, 2000) - mktime(0, 0, 0, 1, 1, 2000);
    foreach ($timezones as $timezonename => $timezoneoffset) {
        if ($offset <= $timezoneoffset) {
            return $timezonename;
        }
    }

    return 'Africa/Abidjan';
}


/**
 * Makes out the full Vitero server url.
 * @return string
 */
function vitero_get_baseurl() {
    $config = get_config('vitero');
    $hostname = trim($config->hostname, '/');
    if (strpos($hostname, 'http') !== 0) {
        $hostname = 'http://' . $hostname;
    }
    $port = $config->port;
    if ($port != '') {
        $port = ':' . $port;
    }
    $root = trim($config->root, '/');
    if ($root != '') {
        $root = '/' . $root;
    }
    return $hostname . $port . $root;
}

/**
 * Convert from UNIX time to Vitero timestamp.
 * @param int $timestamp
 * @return string
 */
function vitero_time_unix_to_vitero($timestamp) {
    return date('YmdHi', $timestamp);
}

/**
 * Convert from Vitero timestamp to UNIX timestamp.
 * Returns int.
 * @param string $date
 */
function vitero_time_vitero_to_unix($date) {
    return strtotime($date);
}

/**
 * Throws an error string from error code.
 * @param int $errorid
 */
function vitero_errorstring($errorid) {
    $errorstring = get_string('errorcode', 'vitero') . ': ' . $errorid;
    $knownerrors = array(2, 3, 4, 51, 52, 53, 54, 101, 102, 103, 151, 152, 153, 302, 303, 304, 305, 306, 451, 452, 501,
                          502, 505, 506, 508, 601, 703, 1001);
    if (in_array($errorid, $knownerrors)) {
        $errorstring .= ': ' . get_string('errorcode' . $errorid, 'vitero');
    }
    throw new moodle_exception($errorstring);
}

/**
 * Get current user's session code. Create and assign as needed. Returns code or false.
 * @param object $vitero - a vitero db object
 * @param int $roleid - assign a role.
 * @param string $type - type of code (meeting or vms)
 * @return mixed bool|string
 */
function vitero_get_my_sessioncode($vitero, $roleid = VITERO_ROLE_PARTICIPANT, $type = 'meeting') {
    global $USER;
    $user = $USER;

    // Does user exist in Vitero? If not, create.
    if (!$viterouserid = vitero_get_remuserid_by_id($user->id)) {
        if ($viterouserid = vitero_get_remuserid_by_email($user->email)) {
            vitero_save_remuser($user, $viterouserid);
        } else {
            if (!$viterouserid = vitero_create_user($user)) {
                return false;
            }
        }
    }

    // Assign to team, just in case.
    if (!vitero_add_user_to_team($viterouserid, $vitero->teamid)) {
        return false;
    }

    // Assign role.
    if (!vitero_assign_team_role($viterouserid, $vitero->teamid, $roleid)) {
        return false;
    }

    // Create code.
    if ($type == 'vms') {
        if (!$code = vitero_create_vms_sessioncode($viterouserid)) {
            return false;
        }
    } else {
        if (!$code = vitero_create_meeting_sessioncode($viterouserid, $vitero->meetingid)) {
            return false;
        }
    }

    // Upload avatar.
    vitero_upload_avatar($user->id, $viterouserid);
    return $code;
}

/**
 * Create a user. Returns team ID or false.
 * @param object $user
 * @return mixed string|bool
 */
function vitero_create_user($user) {
    $client = singlesoapclient::getclient();
    $config = get_config('vitero');
    $customerid = trim($config->customerid);

    $siteshortname = preg_replace('/[^a-zA-Z]/', '', get_site()->shortname);

    $params = array(
        'createUserRequest' => array(
            'user' => array(
                'username' => $siteshortname . '_' . $user->username,
                'surname' => $user->lastname,
                'firstname' => $user->firstname,
                'email' => $user->email,
                'password' => generate_password(),
                'customeridlist' => $customerid,
            )
        )
    );
    $wsdl = 'user';
    $method = 'createUser';
    $result = $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }

    if (!is_object($result) && isset($result->userid)) {
        return false;
    }
    $viterouserid = $result->userid;

    // Upload avatar.
    vitero_upload_avatar($user->id, $viterouserid);

    // Save reference.
    vitero_save_remuser($user, $viterouserid);

    return $viterouserid;
}

/**
 * Updates user email in Vitero.
 *
 * @param  int $viteroid
 * @param  object $user
 * @return bool
 * @throws dml_exception
 * @throws moodle_exception
 */
function vitero_update_remote_details($viteroid, $user) {
    $client = singlesoapclient::getclient();

    $params = array(
        'updateUserRequest' => array(
            'user' => array(
                'id' => $viteroid,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'surname' => $user->lastname,
            )
        )
    );
    $wsdl = 'user';
    $method = 'updateUser';
    $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    return true;
}

/**
 * Upload avatar if possible.
 * @param int $moodleuserid
 * @param string $viterouserid
 * @return bool success
 */
function vitero_upload_avatar($moodleuserid, $viterouserid) {
    $config = get_config('vitero');
    if (!$config->syncavatars) {
        return false;
    }
    $client = singlesoapclient::getclient();
    $context = context_user::instance($moodleuserid);
    $fs = get_file_storage();
    $filename = 'f1';

    // Below code should remain identical to filelib.php file_pluginfile() where $component === 'user'.
    if (!$file = $fs->get_file($context->id, 'user', 'icon', 0, '/', $filename.'.png')) {
        if (!$file = $fs->get_file($context->id, 'user', 'icon', 0, '/', $filename.'.jpg')) {
            if ($filename === 'f3') {
                if (!$file = $fs->get_file($context->id, 'user', 'icon', 0, '/', 'f1.png')) {
                    $file = $fs->get_file($context->id, 'user', 'icon', 0, '/', 'f1.jpg');
                }
            }
        }
    }

    if (!$file) {
        return false;
    }
    $image = $file->get_content();
    $params = array(
        'storeAvatarUsingBase64StringRequest' => array(
            'userid' => $viterouserid,
            'type' => 0,
            'filename' => 'avatar_'.$viterouserid.'.png',
            'file' => base64_encode($image),
        )
    );
    $wsdl = 'user';
    $method = 'storeAvatarUsingBase64String';
    $client->call($wsdl, $method, $params);

    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    return true;
}

/**
 * Get all users for the customer. Returns array of users or false.
 * @return array
 */
function vitero_get_all_users() {
    $client = singlesoapclient::getclient();
    $config = get_config('vitero');
    $customerid = trim($config->customerid);

    $params = array(
        'getUserListByCustomerRequest' => array(
            'customerid' => $customerid,
        )
    );
    $wsdl = 'user';
    $method = 'getUserListByCustomer';
    $result = $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }

    if (!isset($result->user)) {
        return false;
    }

    // In case only one user exists.
    if (!is_array($result->user)) {
        return array($result->user);
    }

    return $result->user;
}

/**
 * Find whether a user's email address exists in Vitero, returns Vitero id if exists.
 * @param string $email
 * @return bool success
 */
function vitero_get_remuserid_by_email($email) {
    if (!$users = vitero_get_all_users()) {
        return false;
    }

    foreach ($users as $user) {
        if (isset($user->email) && strtolower($user->email) == strtolower($email)) {
            return $user->id;
        }
    }
    return false;
}

/**
 * Get remote (vitero) user id if saved locally.
 * @param int $userid
 */
function vitero_get_remuserid_by_id($userid) {
    global $DB;
    return $DB->get_field('vitero_remusers', 'viteroid', array('userid' => $userid));
}

/**
 * Save new user to reference table by vitero id. Assumed vitero id is not already saved.
 * @param object $user
 * @param int $viteroid
 * @return bool success
 */
function vitero_save_remuser($user, $viteroid) {
    global $DB;

    $DB->delete_records('vitero_remusers', array('userid' => $user->id)); // Just in case.
    $toinsert = (object)array(
        'userid' => $user->id,
        'viteroid' => $viteroid,
        'lastemail' => $user->email,
        'timecreated' => time(),
        'timeupdated' => time(),
    );
    return $DB->insert_record('vitero_remusers', $toinsert);
}

/**
 * Update the remote user after their details have changed.
 * @param object $user
 * @return bool success
 */
function vitero_update_remuser($user) {
    global $DB;

    if (!$rec = $DB->get_record('vitero_remusers', array('userid' => $user->id))) {
        return false;
    }
    $rec->lastemail = $user->email;
    $rec->lastfirstname = $user->firstname;
    $rec->lastlastname = $user->lastname;
    $rec->lastemail = $user->email;
    $rec->timeupdated = time();
    return $DB->update_record('vitero_remusers', $rec);
}

/**
 * Find whether a user's username exists in Vitero, returns Vitero id if exists
 * @param string $username
 * @return mixed string|bool
 */
function vitero_get_userid_by_username($username) {
    if (!$users = vitero_get_all_users()) {
        return false;
    }

    foreach ($users as $user) {
        if (isset($user->username) && strtolower($user->username) == $username) {
            return $user->id;
        }
    }
    return false;
}

/**
 * Add a user to team
 * @param int $viterouserid - the VITERO user id (not Moodle!)
 * @param int $teamid - the Vitero team (group) id
 * @return bool success
 */
function vitero_add_user_to_team($viterouserid, $teamid) {
    $client = singlesoapclient::getclient();

    $params = array(
        'addUserToGroupRequest' => array(
            'groupid' => $teamid,
            'userid' => $viterouserid,
        )
    );
    $wsdl = 'group';
    $method = 'addUserToGroup';
    $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    return true;
}

/**
 * Assign a role to a user in a team
 * @param int $viterouserid - the VITERO user id (not Moodle!)
 * @param int $teamid - the Vitero team (group) id
 * @param int $roleid - the id of the role to assign
 * @return bool success
 */
function vitero_assign_team_role($viterouserid, $teamid, $roleid) {
    $client = singlesoapclient::getclient();

    $params = array(
        'changeGroupRoleRequest' => array(
            'groupid' => $teamid,
            'userid' => $viterouserid,
            'role' => $roleid
        )
    );
    $wsdl = 'group';
    $method = 'changeGroupRole';
    $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    return true;
}

/**
 * Returns a list of available room sizes.
 * @return array
 */
function vitero_get_available_roomsizes() {
    $client = singlesoapclient::getclient();
    $config = get_config('vitero');
    $customerid = trim($config->customerid);

    $params = array(
        'getModulesForCustomerRequest' => array(
            'customerid' => $customerid,
        )
    );
    $wsdl = 'licence';
    $method = 'getModulesForCustomer';
    $result = $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return array();
    }
    if (!is_object($result) || !isset($result->modules)) {
        return array();
    }
    $rooms = array();
    foreach ($result->modules as $module) {
        foreach ($module as $room) {
            if (isset($room->roomsize)) {
                $rooms[$room->roomsize] = $room->roomsize;
            }
        }
    }
    ksort($rooms);
    return $rooms;
}

/**
 * Creates a session code per user per meeting. Returns code or false.
 * @param int $viterouserid - the VITERO user id (not Moodle!)
 * @param int $meetingid - the Vitero meeting (booking) id
 * @return bool success
 */
function vitero_create_meeting_sessioncode($viterouserid, $meetingid) {
    $client = singlesoapclient::getclient();

    $params = array(
        'createPersonalBookingSessionCodeRequest' => array(
            'sessioncode' => array(
                'userid' => $viterouserid,
                'bookingid' => $meetingid,
            )
        )
    );
    $wsdl = 'sessioncode';
    $method = 'createPersonalBookingSessionCode';
    $result = $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    if (is_object($result) && isset($result->code)) {
        return $result->code;
    }
    return false;
}

/**
 * Creates a VMS session code for a user. Returns code or false.
 * @param int $viterouserid - the VITERO user id (not Moodle!)
 * @return bool success
 */
function vitero_create_vms_sessioncode($viterouserid) {
    $client = singlesoapclient::getclient();

    $params = array(
        'createVmsSessionCode' => array(
            'sessioncode' => array(
                'userid' => $viterouserid,
            ),
        )
    );
    $wsdl = 'sessioncode';
    $method = 'createVmsSessionCode';
    $result = $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    if (is_object($result) && isset($result->code)) {
        return $result->code;
    }
    return false;
}

/**
 * Create a team. Returns team ID or false.
 * @param string $teamname
 * @return bool success
 */
function vitero_create_team($teamname) {
    $client = singlesoapclient::getclient();
    $config = get_config('vitero');
    $customerid = trim($config->customerid);

    $params = array(
        'createGroupRequest' => array(
            'group' => array(
                'groupname' => $teamname,
                'customerid' => $customerid,
            )
        )
    );
    $wsdl = 'group';
    $method = 'createGroup';
    $result = $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }

    if (is_object($result) && isset($result->groupid)) {
        return $result->groupid;
    }
    return false;
}

/**
 * Updates a team. Returns success.
 * @param int $teamid
 * @param string $teamname
 * @return bool success
 */
function vitero_update_team($teamid, $teamname) {
    $client = singlesoapclient::getclient();

    $params = array(
        'updateGroupRequest' => array(
            'group' => array(
                'id' => $teamid,
                'name' => $teamname,
            )
        )
    );
    $wsdl = 'group';
    $method = 'updateGroup';
    $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    return true;
}

/**
 * Deletes a team. Returns success.
 * @param int $teamid
 * @return bool success
 */
function vitero_delete_team($teamid) {
    $client = singlesoapclient::getclient();

    $params = array(
        'deleteGroupRequest' => array(
            'groupid' => $teamid,
        )
    );
    $wsdl = 'group';
    $method = 'deleteGroup';
    $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    return true;
}

/**
 * Create meeting. Returns meeting id or false.
 * @param object $vitero
 * @return bool success
 */
function vitero_create_meeting($vitero) {
    $client = singlesoapclient::getclient();

    // Get time zone.
    $timezone = vitero_get_moodle_timezone();

    $params = array(
        'createBookingRequest' => array(
            'booking' => array(
                'start' => vitero_time_unix_to_vitero($vitero->starttime),
                'end' => vitero_time_unix_to_vitero($vitero->endtime),
                'startbuffer' => $vitero->startbuffer,
                'endbuffer' => $vitero->endbuffer,
                'groupid' => $vitero->teamid,
                'roomsize' => $vitero->roomsize,
                'timezone' => $timezone
            )
        )
    );
    $wsdl = 'booking';
    $method = 'createBooking';
    $result = $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        // Refresh client.
        singlesoapclient::refreshclient();

        // Delete team.
        vitero_delete_team($vitero->teamid);

        vitero_errorstring($errorcode);
        return false;
    }
    if (is_object($result) && isset($result->bookingid)) {
        return $result->bookingid;
    }
    return false;
}

/**
 * Update meeting. Return success.
 * @param object $vitero
 * @return bool success
 */
function vitero_update_meeting($vitero) {
    $client = singlesoapclient::getclient();

    // Get time zone.
    $timezone = vitero_get_moodle_timezone();

    $params = array(
        'updateBookingRequest' => array(
            'bookingid' => $vitero->meetingid,
            'start' => vitero_time_unix_to_vitero($vitero->starttime),
            'end' => vitero_time_unix_to_vitero($vitero->endtime),
            'startbuffer' => $vitero->startbuffer,
            'endbuffer' => $vitero->endbuffer,
            'timezone' => $timezone,
        )
    );
    $wsdl = 'booking';
    $method = 'updateBooking';
    $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    return true;
}

/**
 * Deletes a meeting.
 * @param object $vitero
 * @return bool success
 */
function vitero_delete_meeting($vitero) {
    $client = singlesoapclient::getclient();

    $params = array(
        'deleteBookingRequest' => array(
            'bookingid' => $vitero->meetingid,
        )
    );
    $wsdl = 'booking';
    $method = 'deleteBooking';
    $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);
        return false;
    }
    return true;
}

/**
 * A Vitero connection test.
 * @return bool success
 */
function vitero_connection_test() {
    $client = singlesoapclient::getclient(true);
    $config = get_config('vitero');
    $customerid = trim($config->customerid);

    $params = array(
        'getUserListByCustomerRequest' => array(
            'customerid' => $customerid,
        )
    );
    $wsdl = 'user';
    $method = 'getUserListByCustomer';
    $result = $client->call($wsdl, $method, $params);
    if ($errorcode = $client->getlasterrorcode()) {
        vitero_errorstring($errorcode);

        return false;
    }

    if (!isset($result->user)) {
        return false;
    }
    return true;
}

/**
 * Returns a direct login url using a VMS session code.
 * @return string
 */
function vitero_get_admin_loginurl() {
    $config = get_config('vitero');

    $userid = vitero_get_userid_by_username($config->adminusername);
    if (!$userid) {
        vitero_errorstring(53);
    }

    if (!$code = vitero_create_vms_sessioncode($userid)) {
        return false;
    }

    $baseurl = vitero_get_baseurl();
    return $baseurl . '/admin/start.htm?&code=' . $code;
}
