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
class Kizano_Crypt_mCrypt
{

    public $Cipher = null;
    public $IV = null;
    public $Key = null;
    public $PlainText = '';
    public $CipherText = '';
    public $CipherState = false;
    private $_mCrypt = null;

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
    public static $Ciphers = array (
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
     * @param string    $Cipher         The encryption cipher to use. One of self::$validCiphers.
     * @param boolean   $CipherState    The current state of the encryption being passed into the object.
     * @param string    $CipherMode     The mode we'll use to encrypt the data. ECB | CBC
     * @param string    $Text           The data to enforce the encryption.
     * @param string    $Key            The Key used for the encryption.
     * @param string    $IV             The IV used in the encryption.
     * 
     * @return void
     */
    public function __construct(
        $Cipher = MCRYPT_DES,
        $CipherState = false,
        $CipherMode = MCRYPT_MODE_CBC,
        $Text = null,
        $Key = null,
        $IV = null
    ) {
        $this->CipherState = $CipherState;
        $this->Cipher = $Cipher;

        if (isset($Text) && !is_string($Text)) {
            throw new InvalidArgumentException('Argument 4 ($Text) must be a string if given.');
        }

        if ($CipherState) {
            $this->CipherText = $Text;
        } else {
            $this->PlainText = $Text;
        }

        if (isset(self::$validCiphers[$Cipher])) {
            $Cipher = self::$validCiphers[$Cipher];
        }

        $this->_mCrypt = @mCrypt_module_open($Cipher, '', $CipherMode, '');
        if ($this->_mCrypt === false) {
            throw new RuntimeException("Could not open the mCrypt module for `$Cipher'");
        }

        if (empty($IV)) {
            $size = mcrypt_get_iv_size($Cipher, MCRYPT_MODE_CBC);
            # This is time expensive, only use if you really want a genuine key
            #$this->IV = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
            $this->IV = strRandHex($size);        # i'm kewl w/ the hex
        } else {
            $this->IV = $IV;
        }

        if (empty($Key)) {
            $size = mcrypt_get_key_size($Cipher, MCRYPT_MODE_CBC);
            $this->Key = strRandHex($size);
        } else {
            $this->Key = $Key;
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
        return mCrypt_generic_init($this->_mCrypt, $this->Key, $this->IV);
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
        $this->CipherState = $tCipherState;
        if ($tCipherState) {
            $this->CipherText = base64_encode(mCrypt_generic($this->_mCrypt, $this->PlainText));
            $this->PlainText = null;
            return $this->CipherText;
        } else {
            $this->PlainText = mDecrypt_generic($this->_mCrypt, base64_decode($this->CipherText));
            $this->CipherText = null;
            return $this->PlainText;
        }
    }

    /**
     *  Handles encryption.
     *  
     *  @return String
     */
    public function encrypt()
    {
        return $this->Crypt(true);
    }

    /**
     *  Handles decryption.
     *  
     *  @return String
     */
    public function decrypt()
    {
        return $this->Crypt(false);
    }

    /**
     * Magick method to render this class as a string.
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->Cipher . Delimit . $this->Key . Delimit . base64_encode($this->IV) . chr(10);
    }
}

