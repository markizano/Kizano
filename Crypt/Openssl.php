<?php

/**
 *  Kizano_Crypt_Openssl
 *
 *  LICENSE
 *
 *  This source file is subject to the new BSD license that is bundled
 *  with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://framework.zend.com/license/new-bsd
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to license@zend.com so we can send you a copy immediately.
 *
 *  @category   Kizano
 *  @package    Crypt
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 *  @link       https://github.com/markizano/Kizano/blob/master/Crypt/Mcrypt.php
 */

if (!extension_loaded('openssl')) {
    throw new RuntimeException('Module `OpenSSL\' not installed. Make sure you have OpenSSL installed '.
        'before attempting to use this component.');
}

/**
 *  Class wrapper for handling the OpenSSL module.
 *
 *  @category   Kizano
 *  @package    Crypt
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */
class Kizano_Crypt_OpenSSL extends Kizano_Crypt_Abstract
{
    /**
     *  Holds the string of data we will be using to encrypt/decrypt the data.
     *  
     *  @var String
     */
    protected $_data = '';

    /**
     *  TRUE if the current data is encrypted; FALSE if the current stream is decrypted.
     *  
     *  @var Boolean
     */
    protected $_cipherState = false;

    /**
     *  Holds the current key/password we are using to crypt $this->_data;
     *  
     *  @var String
     */
    protected $_key = '';

    /**
     *  Constructs a new instance of this class.
     *  
     *  @param String   $data           The data to deal with encryption/decryption
     *  @param Boolean  $cipher_state   Whether this stream is encrypted or not.
     *  @param String   $key            The key/password to use during encryption.
     *  @param String   $iv             The non-null initialization vector to use.
     *  
     *  @return void
     */
    public function __construct($data, $cipher_state = false, $key = null, $iv = null)
    {
        if (!is_string($data)) {
            throw new InvalidArgumentException('Argument 1 ($data) must be a string.');
        }

        if (($key !== null && !is_string($key)) || ($iv !== null && !is_string($iv))) {
            throw new InvalidArgumentException('The key and IV must be a string, if supplied.');
        }

        $this->_data = $data;
        $this->_cipherState = (bool)$cipher_state;
        if ($key === null) {
            // OpenSSL to generate a key.
        }
        if ($iv === null) {
            // OpenSSL to generate an IV.
        }
    }

    /**
     *  Magick method to retrieve protected properties.
     *  
     *  @param String   $method     The method/item to obtain.
     *  @param Array    $args       The arguments to the method.
     *  
     *  @return Mixed
     */
    public function __call($method, array $args)
    {
        if (subStr(strToLower($method), 0, 3) === 'get') {
            $var = lcFirst(subStr($method, 3));
            if (isset($this->{"_$var"})) {
                return $this->{"_$var"};
            }
        }
    }

    /**
     *  Performs encryption.
     *  
     *  @return string
     */
    public function encrypt()
    {
        // Encrypt the datar.
        $this->_cipherState = true;
    }

    public function decrypt()
    {
        // Decrypt the datar.
        $this->_cipherState = false;
    }
}

