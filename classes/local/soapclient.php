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
 * SOAP client for Vitero.
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the vitero specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_vitero
 * @copyright  2016 Synergy Learning
 * @author     Yair Spielmann <yair.spielmann@synergy-learning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_vitero\local;

defined('MOODLE_INTERNAL') || die();

/**
 * SOAP client suited for the Vitero server.
 * @package    mod_vitero
 * @copyright  2016 Yair Spielmann, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class soapclient {
    /** @var zsc\client the soap client */
    protected $client;

    /** @var string the soap username */
    protected $username = '';

    /** @var string the soap password */
    protected $password = '';

    /** @var string the soap base url */
    protected $baseurl;

    /** @var bool whether we're debugging */
    protected $debug = false;

    /** @var Exception the last fault thrown by soap client */
    protected $lastfault;

    /**
     * Creates a SOAP client object.
     * @param string $baseurl
     * @param string $username
     * @param string $password
     * @param bool $debug
     */
    public function __construct($baseurl, $username, $password, $debug = false) {
        $this->baseurl = trim($baseurl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
        );
        $this->client = new zsc\client(null, $options);
    }

    /**
     * Create a SOAP header that matches Vitero's WSS secutiry standards
     *
     * @param  string $username
     * @param  string $password
     * @return \SoapHeader
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
        $authvalues = new \SoapVar($auth, XSD_ANYXML);
        $header = new \SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-' .
                        '200401-wss-wssecurity-secext-1.0.xsd', 'Security', $authvalues,
                        true);
        return $header;
    }

    /**
     * Makes a SOAP call.
     * @param string $wsdlname - name of service (e.g. user, group, sessioncode).
     * @param string $method
     * @param array $params
     */
    public function call($wsdlname, $method, $params = array()) {
        $this->lastfault = null;
        $wwssheader = $this->wssecurity_header($this->username, $this->password);
        $this->client->addSoapInputHeader($wwssheader);
        $wsdl = $this->baseurl . '/services/' . $wsdlname . '.wsdl';
        $this->client->setWsdl($wsdl);
        try {
            $response = $this->client->__call($method, $params);
        } catch (\Exception $ex) {
            $lastresponse = $this->client->getLastResponse();
            if ($lastfault = $this->get_soapfault($lastresponse)) {
                $this->lastfault = $lastfault;
            } else if ($this->debug) {
                echo $ex->getMessage() . '|' . $lastresponse;
            }
            return false;
        }
        return $response;
    }

    /**
     * Returns the last soap fault object, if exists, otherwise 0
     * @return int
     */
    public function getlasterrorcode() {
        if (null === $this->lastfault) {
            return 0;
        }
        return $this->lastfault->errorcode;
    }

    /**
     * Returns an object with errorcode and faulstring from a soap fault envelope.
     * @param  string $xml
     * @return object
     */
    private function get_soapfault($xml) {
        try {
            $soapfault = new \stdClass();
            $soapfault->faultstring = '';
            $soapfault->errorcode = 0;
            $cleanxml = str_replace('SOAP-ENV:', '', $xml);
            if (!$envelope = new \SimpleXMLElement($cleanxml)) {
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
        } catch (\Exception $e) {
            return false;
        }

        return $soapfault;
    }
}
