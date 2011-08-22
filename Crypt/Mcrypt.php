<?php
/**
 *  Kizano_Crypt_Mcrypt
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
 */

if (!extension_loaded('mcrypt')) {
    throw new RuntimeException('Module `mCrypt\' not installed. Make sure you have mCrypt installed '.
        'before attempting to use this component.');
}

/**
 *  Class wrapper for handling the mCrypt module.
 *
 *  @category   Kizano
 *  @package    Crypt
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */
class Kizano_Crypt_mCrypt extends Kizano_Crypt_Abstract
{
    const DELIMIT = '<!>';
    public $cipher;
    public $iv;
    public $key;
    public $plainText = '';
    public $cipherText = '';
    public $cipherState = false;
    protected $_mCrypt;

    /**
     *  This is a list of ciphers that are available and valid for the current system. Be sure to update
     *  this array based on what works for your machine as each mCrypt module provides different availble
     *  encryption methods based on the configuration/available resources on that system.
     *  
     *  @var Array
     *  @static
     */
    public static $validCiphers = array(
        '1' => 'blowfish',
        '2' => 'blowfish-compat',
        '3' => 'cast-128',
        '4' => 'cast-256',
        '5' => 'des',
        '7' => 'gost',
        '8' => 'loki97',
        'A' => 'rc2',
        'B' => 'rijndael-128',
        'C' => 'rijndael-192',
        'D' => 'rijndael-256',
        'E' => 'saferplus',
        'F' => 'serpent',
        '10' => 'tripledes',
        '11' => 'twofish',
        '12' => 'xtea',
    );

    /**
     *  A list of available ciphers on the current system.
     *  
     *  @var Array
     *  @static
     */
    public static $ciphers = array (
        'arcfour',
        'blowfish',
        'blowfish-compat',
        'cast-128',
        'cast-256',
        'des',
        'enigma',
        'gost',
        'loki97',
        'rc2',
        'rijndael-128',
        'rijndael-192',
        'rijndael-256',
        'saferplus',
        'serpent',
        'tripledes',
        'twofish',
        'wake',
        'xtea',
    );

    /**
     * Sets up the mCrypt handler.
     * 
     * @param string    $cipher         The encryption cipher to use. One of self::$validCiphers.
     * @param boolean   $cipher_state    The current state of the encryption being passed into the object.
     * @param string    $cipher_mode     The mode we'll use to encrypt the data. ECB | CBC
     * @param string    $text           The data to enforce the encryption.
     * @param string    $key            The Key used for the encryption.
     * @param string    $iv             The IV used in the encryption.
     * 
     * @return void
     */
    public function __construct(
        $cipher = MCRYPT_DES,
        $cipher_state = false,
        $cipher_mode = MCRYPT_MODE_CBC,
        $text = null,
        $key = null,
        $iv = null
    ) {
        $this->cipherState = $cipher_state;

        if (isset($text) && !is_string($text)) {
            throw new InvalidArgumentException('Argument 4 ($text) must be a string if given.');
        }

        if ($cipher_state) {
            $this->cipherText = $text;
        } else {
            $this->plainText = $text;
        }

        if (isset(self::$validCiphers[$cipher])) {
            $this->cipher = self::$validCiphers[$cipher];
        }

        $this->_mCrypt = @mCrypt_module_open($this->cipher, '', $cipher_mode, '');
        if ($this->_mCrypt === false) {
            throw new RuntimeException("Could not open the mCrypt module for `$cipher'");
        }

        if (empty($iv)) {
            $size = mcrypt_get_iv_size($cipher, MCRYPT_MODE_CBC);
            # This is time expensive, only use if you really want a genuine key
            #$this->iv = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
            $this->iv = Kizano_Strings::strRandHex($size);        # i'm kewl w/ the hex
        } else {
            $this->iv = $iv;
        }

        if (empty($key)) {
            $size = mcrypt_get_key_size($cipher, MCRYPT_MODE_CBC);
            $this->key = Kizano_Strings::strRandHex($size);
        } else {
            $this->key = $key;
        }
    }

    /**
     * Closes the encryption module and garbage collection.
     * 
     * @return void
     */
    public function __destruct() {
        if ($this->_mCrypt != null) mCrypt_module_close($this->_mCrypt);
        $this->_mCrypt = null;
    }

    /**
     *  Initializes the encryption algorithm.
     *  
     *  @return Integer|False   See {@link http://us3.php.net/mcrypt_generic_init}
     */
    public function init()
    {
        return mCrypt_generic_init($this->_mCrypt, $this->key, $this->iv);
    }

    /**
     *  Deinitializes the encryption algorithm.
     *  
     *  @return Integer|False   See {@link http://us3.php.net/mcrypt_generic_deinit}
     */
    public function deinit()
    {
        return mCrypt_generic_deinit($this->_mCrypt);
    }

    /**
     * Performs (de|en)cryption
     * @param boolean $tCipherState The target cipher state.
     * @return string
     */
    public function Crypt($tCipherState = false)
    {
        $this->cipherState = $tCipherState;
        if ($tCipherState) {
            $this->cipherText = base64_encode(mCrypt_generic($this->_mCrypt, $this->plainText));
            $this->plainText = null;
            return $this->cipherText;
        } else {
            $this->plainText = mDecrypt_generic($this->_mCrypt, base64_decode($this->cipherText));
            $this->cipherText = null;
            return $this->plainText;
        }
    }

    /**
     *  Handles encryption.
     *  
     *  @return String
     */
    public function encrypt()
    {
        return $this->crypt(true);
    }

    /**
     *  Handles decryption.
     *  
     *  @return String
     */
    public function decrypt()
    {
        return $this->crypt(false);
    }

    /**
     * Sets instance-configurable options.
     *
     * @param String    $name   The option name to use.
     *
     * @return Kizano_Crypt_Abstract
     * @throws InvalidArgumentException
     */
    public function setOptions($options)
    {
        $options instanceof Zend_Config && $options = $options->toArray();
        if (!is_array($options)) {
            throw new InvalidArgumentException('Argument 1 ($options) must be an array or Zend_Config');
        }

        // Return early if empty array passed.
        if (empty($options)) {
            return $this;
        }

        foreach ($options as $name => $option) {
            switch (strToLower($name)) {
                case 'plaintext':
                    $this->plainText = $option;
                    break;
                case 'ciphertext':
                    $this->cipherText = $option;
                    break;
                case 'key':
                    $this->key = $option;
                    break;
                case 'iv':
                    $this->iv = $option;
                    break;
                default:
                    $this->_options[$nmae] = $value;
            }
        }
    }

    public function fromString($string)
    {
        if (!is_string($string)) {
            throw new InvalidArgumentException('Argument 1 ($string) expected string.');
        }

        if (empty($string)) {
            throw new InvalidArgumentException('Empty string passed.');
        }

        list($this->cipher, $this->key, $this->iv) = explode(self::DELIMIT, trim($string));
        $this->iv = base64_decode($this->iv);
        return $this;
    }

    /**
     * Magick method to render this class as a string.
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->cipher . self::DELIMIT . $this->key . self::DELIMIT . base64_encode($this->iv) . chr(10);
    }
}

