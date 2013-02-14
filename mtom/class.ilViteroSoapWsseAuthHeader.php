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
 * Generates an WSSE header
 * 
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * $Id: class.ilViteroSoapWsseAuthHeader.php 32250 2011-12-21 11:43:49Z smeyer $
 */
class ilViteroSoapWsseAuthHeader extends SoapHeader {
    const WSS_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    const WSU_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';

    public function __construct($a_user, $a_pass) {

        $auth = new stdClass();
        $auth->Username = new SoapVar(
                        $a_user,
                        XSD_STRING,
                        null,
                        self::WSS_NS,
                        null,
                        self::WSS_NS
        );
        $auth->Password = new SoapVar(
                        $a_pass,
                        XSD_STRING,
                        null,
                        self::WSS_NS,
                        null,
                        self::WSS_NS
        );

        $auth->Nonce = new SoapVar(
                        base64_encode(sha1($a_pass . $a_user . microtime(true))),
                        XSD_STRING,
                        null,
                        self::WSS_NS,
                        null,
                        self::WSS_NS
        );

        $auth->Created = new SoapVar(
                        date('Y-m-d\TH:i:s\Z'),
                        XSD_STRING,
                        null,
                        self::WSU_NS,
                        null,
                        self::WSU_NS
        );
        $un_token = new stdClass();
        $un_token->UsernameToken = new SoapVar(
                        $auth,
                        SOAP_ENC_OBJECT,
                        null,
                        self::WSS_NS,
                        'UsernameToken',
                        self::WSS_NS
        );

        $security = new SoapVar(
                        new SoapVar(
                                $un_token,
                                SOAP_ENC_OBJECT,
                                null,
                                self::WSS_NS,
                                'UsernameToken',
                                self::WSS_NS
                        ),
                        SOAP_ENC_OBJECT,
                        null,
                        self::WSS_NS,
                        'Security',
                        self::WSS_NS
        );
        parent::__construct(self::WSS_NS, 'Security', $security, true);
    }

    public static function getwsfheader($user, $pass) {
        $datamain = array(
            new WSHeader(
                    array(
                        'name' => 'Username',
                        'ns' => self::WSS_NS,
                        'prefix' => 'wsse',
                        'data' => $user
                    )
            ),
            new WSHeader(
                    array(
                        'name' => 'Password',
                        'ns' => self::WSS_NS,
                        'prefix' => 'wsse',
                        'data' => $pass
                    )
            ),
            new WSHeader(
                    array(
                        'name' => 'Nonce',
                        'ns' => self::WSS_NS,
                        'prefix' => 'wsse',
                        'data' => base64_encode(sha1($pass . $user . microtime(true)))
                    )
            ),
            new WSHeader(
                    array(
                        'name' => 'Created',
                        'ns' => self::WSU_NS,
                        'prefix' => 'wsu',
                        'data' => date('Y-m-d\TH:i:s\Z')
                    )
            )
        );
        $sec = new WSHeader(
                        array(
                            'name' => 'Security',
                            'ns' => self::WSS_NS,
                            'prefix' => 'wsse',
                            'mustunderstand' => true,
                            'data' => array(
                                new WSHeader(
                                        array(
                                            'name' => 'UsernameToken',
                                            'ns' => self::WSS_NS,
                                            'prefix' => 'wsse',
                                            'data' => $datamain
                                        )
                                )
                            )
                        )
        );
        return $sec;
    }

}


