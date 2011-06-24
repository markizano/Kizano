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
 *  Class wrapper for handling the OpenSSL module. There are some notes that must be made about this
 *  class/extension before one simply goes and starts crypting data...
 *  
 *  Most important note: This class operates differently from mCrypt, keys are not just a specified
 *  length and can be generated on the fly. The console component to this extension is an important
 *  addition to the operations of this class.
 *  
 *  The keys associated with encryption and decryption depend upon OpenSSL's certificate strategy.
 *  A key used to encrypt the data cannot be used to decrypt the same data. The key used to encrypt
 *  the data is not the "privkey.pem" generated when OpenSSL generates the certificate, it is the actual
 *  certificate generated after signing.
 *  
 *  The private key generated upon creation of the Certificate Signing Request (CSR) is the private
 *  key used to decrypt the materials.
 *  
 *  To generate a self-signed certificate/private key pair, use the following commands:
 *  
 *  <code>
 *      mycert="{CERT NAME}";
 *      openssl req -new -out "${mycert}.csr"
 *      openssl rsa -in privkey.pem -out "${mycert}.key"
 *      openssl x509 -in "${mycert}.csr" -out ${mycert}.crt -signkey "${mycert}.key" -req -days 3650
 *  </code>
 *  
 *  Replace {CERT NAME} with the name of the certificate you wish to create. Filename extensions will
 *  be appended in the lines that follow.
 *  
 *  The first command will create a new CSR.
 *  The second command will generate a private key to associate with the CSR/Certificate.
 *  The third command will self-sign the certificate and keep it good for 10 years (3650 days).
 *  
 *  Once the certificate (.crt) and key (.key) files have been created, you can use the CRT as if it
 *  were a public key, and the KEY as if it were the private key. When encrypting data, use the CRT.
 *  When decrypting data, use the KEY.
 *  
 *  Another thing to consider:
 *  http://dev.modmancer.com/index.php/2010/07/07/php-and-openssl-key-format/
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
     *  Just a note that says what ciphers are available.
     *  
     *  @var Array
     * /
    public static $ciphers = array(
        'aes-128-cbc', 'aes-128-cfb', 'aes-128-cfb1', 'aes-128-cfb8', 'aes-128-ecb', 'aes-128-ofb',
        'aes-192-cbc', 'aes-192-cfb', 'aes-192-cfb1', 'aes-192-cfb8', 'aes-192-ecb', 'aes-192-ofb',
        'aes-256-cbc', 'aes-256-cfb', 'aes-256-cfb1', 'aes-256-cfb8', 'aes-256-ecb', 'aes-256-ofb',
        'bf-cbc', 'bf-cfb', 'bf-ecb', 'bf-ofb',
        'cast5-cbc', 'cast5-cfb', 'cast5-ecb', 'cast5-ofb',
        'des-cbc', 'des-cfb', 'des-cfb1', 'des-cfb8', 'des-ecb', 'des-ede', 'des-ede-cbc', 'des-ede-cfb',
        'des-ede-ofb', 'des-ede3', 'des-ede3-cbc', 'des-ede3-cfb', 'des-ede3-cfb1', 'des-ede3-cfb8',
        'des-ede3-ofb', 'des-ofb', 'desx-cbc',
        'idea-cbc', 'idea-cfb', 'idea-ecb', 'idea-ofb',
        'rc2-40-cbc', 'rc2-64-cbc', 'rc2-cbc', 'rc2-cfb', 'rc2-ecb', 'rc2-ofb', 'rc4', 'rc4-40'
    ); //*/

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
     *  Holds the current initialization vector we are using to crypt $this->_data;
     *  
     *  @var String
     */
    protected $_iv = '';

    /**
     *  The type of padding to use.
     *  
     *  @var String
     */
    protected $_padding = OPENSSL_NO_PADDING;

    /**
     *  Generates random characters for an IV.
     *  
     *  @param Integer  $len    The number of bytes to generate
     *  
     *  @return String
     */
    public static function generateIv($len = 8)
    {
        $random = range(chr(0), chr(255));
        $result = "";

        while (!isset($result{$len -1})) {
            srand(mt_rand());
            $result .= $random[array_rand($random, 1)];
        }

        return $result;
    }

    /**
     *  Generates an 8-byte key for use in this class.
     *  
     *  @param Integer  $len  The length of the key to generate.
     *  
     *  @return String
     */
    public static function generateKey($len = 8)
    {
        return sprintf(
            "-----BEGIN PUBLIC KEY-----\n%s\n-----END PUBLIC KEY-----",
            wordwrap(base64_encode(self::generateIV($len)), 64, chr(10), true)
        );
    }

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
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
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

        throw new Kizano_Crypt_Exception("Call to undefined method `$method' in __call().", 500);
    }

    /**
     *  Sets options for this class.
     *  
     *  @param Array    $options    The configurable options to accept. The following keys are accepted:
     *    @member String    $data           The data to deal with when crypting.
     *    @member String    $key            The cipher key to use during cryption.
     *    @member String    $iv             The Initialization Vector to use during cryption.
     *    @member Boolean   $cipherState    TRUE if the current $data is ciphertext.
     *                                      FALSE if the current $data is plaintext.
     *    @member ENUM      $padding        One of the OPENSSL_*_PADDING values.
     *  
     *  @return Kizano_Crypt_OpenSSL
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $option) {
            switch (strToLower($name)) {
                case 'data': case 'key': case 'iv':
                    if (empty($option) || !is_string($option)) {
                        throw new RuntimeException("Option `$name' must be a string and not empty.");
                    }

                    if (file_exists($option) && is_file($option) && is_readable($option)) {
                        $this->{"_$name"} = wordwrap(file_get_contents($option), 64, chr(10), true);
                    } else {
                        $this->{"_$name"} = $option;
                    }
                    break;
                case 'cipherstate':
                    $this->_cipherState = (bool)$option;
                    break;
                case 'padding':
                    $this->_padding = $option;
                default:
                    // Don't catch anything else
                    continue;
            }
        }

        return $this;
    }

    /**
     *  Performs encryption.
     *  
     *  @return Boolean
     *  @throws Kizano_Crypt_Exception
     */
    public function encrypt()
    {
        $this->_cipherState = true;
        $result = openssl_public_encrypt($this->_data, $this->_data, $this->_key, $this->_padding);

        $errs = array();
        while ($e = openssl_error_string()) { $errs[] = $e; }
        if (!empty($errs)) {
            throw new Kizano_Crypt_Exception(join((ini_get('html_errors')? "<br />": null) . PHP_EOL, $errs), 500);
        }

        return $result;
    }

    /**
     *  Performs decryption.
     *  
     *  @return Boolean
     *  @throws Kizano_Crypt_Exception
     */
    public function decrypt()
    {
        $this->_cipherState = false;
        $result = openssl_private_decrypt($this->_data, $this->_data, $this->_key, $this->_padding);

        $errs = array();
        while ($e = openssl_error_string()) { $errs[] = $e; }
        if (!empty($errs)) {
            throw new Kizano_Crypt_Exception(join((ini_get('html_errors')? "<br />": null) . PHP_EOL, $errs), 500);
        }

        return $result;
    }
}
