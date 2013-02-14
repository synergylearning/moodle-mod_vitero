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
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/zend/Zend/Soap/Client.php');

class vitero_soapclient {

    protected $client = null;
    protected $username = '';
    protected $password = '';
    protected $baseurl = null;
    protected $debug = false;
    protected $lastfault = null;

    /* Creates a SOAP client object
     * @param string base url
     * @param string username
     * @param string password
     */

    public function __construct($baseurl, $username, $password, $debug = false) {
        $this->baseurl = trim($baseurl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;
        $options = array(
            'soap_version' => SOAP_1_1,
        );
        $this->client = new Zend_Soap_Client(null, $options);
    }

    /* Create a SOAP header that matches Vitero's WSS secutiry standards
     */

    private function wssecurity_header($username, $password) {
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $nonce = mt_rand();
        $auth = '
<wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.' .
                'org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
<wsse:UsernameToken>
    <wsse:Username>' . $username . '</wsse:Username>
    <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-' .
                'wss-username-token-profile-1.0#PasswordText">' . $password . '</wsse:Password>
    <wsse:Nonce>' . base64_encode(pack('H*', $nonce)) . '</wsse:Nonce>
    <wsu:Created xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-' .
                '200401-wss-wssecurity-utility-1.0.xsd">' . $timestamp . '</wsu:Created>
   </wsse:UsernameToken>
</wsse:Security>
        ';

        /* XSD_ANYXML (or 147) is the code to add xml directly into a SoapVar.
         * Using other codes such as SOAP_ENC, it's really difficult to set the
         * correct namespace for the variables, so the axis server rejects the
         * xml.
         */
        $authvalues = new SoapVar($auth, XSD_ANYXML);
        $header = new SoapHeader("http://docs.oasis-open.org/wss/2004/01/oasis-" .
                        "200401-wss-wssecurity-secext-1.0.xsd", "Security", $authvalues,
                        true);
        return $header;
    }

    /* Makes a SOAP call
     * @param string wsdlname - name of service (e.g. user, group, sessioncode)
     * @param string method
     * @param array params
     */

    public function call($wsdlname, $method, $params = array()) {
        $this->lastfault = null;
        $wwssheader = $this->wssecurity_header($this->username, $this->password);
        $this->client->addSoapInputHeader($wwssheader);
        $wsdl = $this->baseurl . '/services/' . $wsdlname . '.wsdl';
        $this->client->setWsdl($wsdl);
        try {
            $response = $this->client->__call($method, $params);
        } catch (Exception $ex) {
            $lastresponse = $this->client->getLastResponse();
            if ($lastfault = $this->get_soapfault($lastresponse)) {
                $this->lastfault = $lastfault;
            } else if ($this->debug) {
                echo $lastresponse;
            }
            return false;
        }
        return $response;
    }

    /*
     * Returns the last soap fault object, if exists, otherwise 0
     */
    public function getlasterrorcode() {
        if (is_null($this->lastfault)) {
            return 0;
        }
        return $this->lastfault->errorcode;
    }

    /* Returns an object with errorcode and faulstring from a soap fault envelope.
     * @param string xml
     */

    private function get_soapfault($xml) {
        try {
            $soapfault = new stdClass();
            $soapfault->faultstring = '';
            $soapfault->errorcode = 0;
            $cleanxml = str_replace('SOAP-ENV:', '', $xml);
            if (!$envelope = new SimpleXMLElement($cleanxml)) {
                return false;
            }
            if (!$body = $envelope->Body) {
                return false;
            }
            if (!$fault = $body->Fault) {
                return false;
            }
            if ($faultstring = $fault->faultstring) {
                $soapfault->faultstring = $faultstring;
            }
            if (!$detail = $fault->detail) {
                return false;
            }
            if (!$error = $detail->error) {
                return false;
            }
            if (!$errorcode = $error->errorCode) {
                return false;
            }
            $soapfault->errorcode = $errorcode;
        } catch (Exception $e) {
            return false;
        }

        return $soapfault;
    }

}

/*
 * Returns a single (static) SOAP client
 */
class vitero_singlesoapclient {
    private static $client;

    /*
     * Returns the SOAP client, initialises if needed
     */
    public static function getclient($alwaysdebug = false) {
        if (!isset(vitero_singlesoapclient::$client)) {
            global $CFG;

            $config = get_config('vitero');
            $baseurl = vitero_get_baseurl();
            $debug = false;
            if ($CFG->debug > DEBUG_NORMAL || $alwaysdebug) {
                $debug = true;
            }
            vitero_singlesoapclient::$client = new vitero_soapclient($baseurl, $config->adminusername, $config->adminpassword, $debug);
        }
        return vitero_singlesoapclient::$client;
    }
}