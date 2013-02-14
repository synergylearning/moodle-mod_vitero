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

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Abstract vitero soap connector
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroSoapConnector.php 33586 2012-03-07 13:12:56Z smeyer $
 */
abstract class ilViteroSoapConnector {
    const ERR_WSDL = 2001;

    const WS_TIMEZONE = 'Africa/Ceuta';
    const CONVERT_TIMZONE = 'Africa/Ceuta';
    const CONVERT_TIMEZONE_FIX = 'Africa/Ceuta';

    private $settings;

    private $client = null;

    /**
     * Get instance
     * @param object settings - required connection settings
     */
    public function __construct($settings) {
        $this->settings = $settings;
    }

    /**
     * Get wsdl name
     * @return string
     */
    abstract protected function getwsdlname();

    /**
     *
     * @return <type>
     */

    /**
     * Get vitero settings
     * @return ilViteroSettings
     */
    public function getsettings() {
        return $this->settings;
    }

    /**
     * Get soap client
     * @return SoapClient
     */
    public function getclient() {
        return $this->client;
    }

    /*
     * Set client
     */
    public function setclient($client) {
        $this->client = $client;
    }

    /**
     * init soap client
     * @return void
     * @throws ilViteroConnectorException
     */
    protected function initclient($a_file_id = 'cid:myid', $a_file = 'file') {

        try {
            $this->client = new SoapClient(
                $this->getSettings()->ServerUrl.'/'.$this->getWsdlName(),
                array(
                     'cache_wsdl' => 0,
                     'trace' => 1,
                     'exceptions' => true,
                     'classmap'
                )
            );
            $this->client->__setSoapHeaders(
                $head = new ilViteroSoapWsseAuthHeader(
                    $this->getSettings()->AdminUser,
                    $this->getSettings()->AdminPass
                )
            );

            return;
        } catch (SoapFault $e) {
            print_error('VITERO: '.$e->getMessage());
        }
    }

    protected function parseerrorcode(Exception $e) {
        return (int)$e->detail->error->errorCode;
    }
}