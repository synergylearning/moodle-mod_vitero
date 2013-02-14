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
 * Library of SOAP functions specific to Vitero.
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the vitero specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod
 * @subpackage vitero
 * @copyright  2012 Yair Spielmann, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('class.ilViteroSoapConnector.php');
require_once('class.ilViteroSoapWsseAuthHeader.php');

class ilViteroAvatarSoapConnector extends ilViteroSoapConnector {
    const WSDL_NAME = 'mtom.wsdl';
    const FILE_TYPE_NORMAL = 0;
    const FILE_TYPE_SMILE = 1;

    /**
     * Store avatar picture
     * @param int $a_vuserid
     * @param array $a_file_info array('name' => filename, 'type' => 0|1, 'file' => path)
     */
    public function storeavatar($a_vuserid, $a_file_info) {
        try {
            $this->initclient('cid:myid', 'file');

            $avatar = new stdClass();
            $avatar->userid = $a_vuserid;
            $payload =
                '<ns1:storeAvatarRequest xmlns:ns1="http://www.vitero.de/schema/mtom">' .
                    '<ns1:userid>' . $a_vuserid . '</ns1:userid>' .
                    '<ns1:filename>' . $a_file_info['name'] . '</ns1:filename>' .
                    '<ns1:type>' . $a_file_info['type'] . '</ns1:type>' .
                    '<ns1:file><xop:Include xmlns:xop="http://www.w3.org/2004/08/xop/include" href="cid:myid"/></ns1:file>' .
                    '</ns1:storeAvatarRequest>';
            $inputheaders = array(
                ilViteroSoapWsseAuthHeader::getwsfheader($this->getSettings()->AdminUser, $this->getSettings()->AdminPass)
            );
            $attachments = array(
                'myid' => $a_file_info['contents']
            );
            $message = new WSMessage(
                $payload,
                array(
                     'inputHeaders' => $inputheaders,
                     'attachments' => $attachments
                )
            );
            $resp = $this->client->request($message);
        } catch (Exception $e) {
            if ($e instanceof WSFault) {
                if (isset($e->Detail)) {
                    $detail = $e->Detail;
                    if (strpos($detail, '</errorCode>') !== false) {
                        $detailxml = simplexml_load_string($detail);
                        if (isset($detailxml->errorCode)) {
                            vitero_errorstring($detailxml->errorCode);
                        }
                    }
                }
                vitero_errorstring($e->Reason);
            } else {
                if ($e instanceof moodle_exception) {
                    throw $e;
                }
                //otherwise do nothing
            }
        }
    }

    protected function initclient($a_file_id, $a_file) {
        $serverurl = $this->getSettings()->ServerUrl . '/';
        $clientconf = array(
            'to' => $serverurl,
            'useSOAP' => '1.1',
            'useMTOM' => true,
            'responseXOP' => true,
        );
        //Add CACertificate location if HTTPS:
        if (strpos($serverurl, 'https://') !== false || strpos($serverurl, ':443') !== false) {
            $certpath = '/var/www/cert/vms_vitero_de.crt';
            if (!file_exists($certpath)) {
                throw new moodle_exception(get_string('missingcertificate', 'vitero', $certpath));
            }
            $clientconf['CACert'] = $certpath;
        }
        $this->client = new WSClient($clientconf);
    }

    protected function getwsdlname() {
        return self::WSDL_NAME;
    }

}