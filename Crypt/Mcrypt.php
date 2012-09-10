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

class_exists('Kizano_Crypt_Abstract') || require 'Kizano/Crypt/Abstract.php';

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
    public $cipher_state = false;
    public $cipher_mode = MCRYPT_MODE_CBC;
    public $cipher;
    public $iv;
    public $key;
    public $text;

    protected $_mCrypt;
    protected $_options = array();

    /**
     *  This is a list of ciphers that are available and valid for the current system. Be sure to update
     *  this array based on what works for your machine as each mCrypt module provides different availble
     *  encryption methods based on the configuration/available resources on that system.
     *  
     *  @var Array
     *  @static
     */
    public static $valid_ciphers = array(
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
     * @param Array     $options        The list of options to startup this class.
     * @member string   $cipher         The encryption cipher to use. One of self::$valid_ciphers.
     * @member boolean  $cipher_state   The current state of the encryption being passed into the object.
     * @member string   $cipher_mode    The mode we'll use to encrypt the data. ECB | CBC
     * @member string   $text           The data to enforce the encryption.
     * @member string   $key            The Key used for the encryption.
     * @member string   $iv             The IV used in the encryption.
     * 
     * @return void
     */
    public function __construct($options)
    {
        $options instanceof Zend_Config && $options = $options->toArray();
        
        if (!empty($options) && !is_array($options)) {
            throw new InvalidArgumentException('Argument 1 not a set of options. Expected array.');
        }

        // Setup some default options if they aren't already set.
        isset($options['cipher'])       || $options['cipher'] = MCRYPT_DES;
        isset($options['cipher_state']) || $options['cipher_state'] = false;
        isset($options['cipher_mode'])  || $options['cipher_mode'] = MCRYPT_MODE_CBC;
        isset($options['text'])         || $options['text'] = null;
        isset($options['key'])          || $options['key'] = null;
        isset($options['iv'])           || $options['iv'] = null;
        $this->setOptions($options);

        if (!empty($text) && !is_string($text)) {
            throw new InvalidArgumentException('Argument 4 ($text) must be a string if given.');
        }

        $this->_mCrypt = @mCrypt_module_open($this->cipher, '', $this->cipher_mode, '');
        if ($this->_mCrypt === false) {
            throw new RuntimeException("Could not open the mCrypt module for `$cipher'");
        }

        if (empty($this->iv)) {
            if (isset($this->_options['use_mcrypt_iv'])) {
                # This is time expensive, only use if you really want a genuine key
                $this->iv = mcrypt_create_iv(mcrypt_get_iv_size($this->cipher, $this->cipher_mode), MCRYPT_DEV_URANDOM);
            } else {
                $this->iv = Kizano_Strings::strRandHex(mcrypt_get_iv_size($this->cipher, MCRYPT_MODE_CBC));        # i'm kewl w/ the hex
            }
        } else {
            if (strlen($this->iv) > ($size = mCrypt_get_iv_size($this->cipher, $this->cipher_mode))) {
                $this->iv = substr($this->iv, 0, $size);
            }
        }

        if (empty($this->key)) {
            $this->key = Kizano_Strings::strRandHex(mcrypt_get_key_size($this->cipher, MCRYPT_MODE_CBC));
        } else {
            if (strlen($this->key) > ($size = mCrypt_get_key_size($this->cipher, $this->cipher_mode))) {
                $this->key = substr($this->key, 0, $size);
            }
        }
    }

    /**
     * Closes the encryption module and garbage collection.
     * 
     * @return void
     */
    public function __destruct()
    {
        if (!empty($this->_mCrypt) && is_resource($this->_mCrypt)) {
            mCrypt_module_close($this->_mCrypt);
            unset($this->_mCrypt);
        }

        foreach (array('_mCrypt', 'cipher', 'cipher_mode', 'cipher_state', 'iv', 'key', 'text') as $p) {
            unset($this->$p);
        }
    }

    /**
     * Closes the resource when the class goes to sleep.
     *
     * @return string
     */
    public function __sleep()
    {
        if (!empty($this->_mCrypt)) {
            mCrypt_module_close($this->_mCrypt);
            $this->_mCrypt = null;
        }

        return serialize(array(
            'cipher',
            'cipher_state',
            'cipher_mode',
            'key',
            'iv',
            'text',
        ));
    }
    
    /**
     * Reopens the resources when the class is unserialized.
     *
     * @return Array
     */
    public function __wakeup()
    {
        $this->_mCrypt = @mCrypt_module_open($this->cipher, '', $this->cipher_mode, '');
        if ($this->_mCrypt === false) {
            throw new RuntimeException("Could not open the mCrypt module for `$cipher'");
        }
    }

    /**
     * Sets instance-configurable options.
     *
     * @param String    $name   The option name to set.
     * @param Mixed     $value  The value of the option to assign.
     *
     * @return Kizano_Crypt_Abstract
     * @throws InvalidArgumentException
     */
    public function setOption($option, $value = null)
    {
        if (!empty($option) && !is_string($option)) {
            throw InvalidArgumentException('Argument 1 ($option) must be a string.');
        }

        switch (strToLower($option)) {
            case 'cipher':
                if (empty($value) || !is_string($value)) {
                    throw new InvalidArgumentException('Option for `cipher\' not a string.');
                }

                $this->cipher = isset(self::$valid_ciphers[$value])? self::$valid_ciphers[$value]: $value;
                break;
            case 'cipher_state':
                $this->cipher_state = (bool)$value;
                break;
            case 'cipher_mode':
                $this->cipher_mode = $value;
                break;
            case 'text':
                $this->text = $value;
                break;
            case 'key':
                if (!empty($value) && !is_string($value)) {
                    throw new InvalidArgumentException('Option for `key\' not a string.');
                }

                $this->key = $value;
                break;
            case 'iv':
                if (!empty($value) && !is_string($value)) {
                    throw new InvalidArgumentException('Option for `iv\' not a string.');
                }

                $this->iv = $value;
                break;
            default:
                $this->_options[$option] = $value;
        }

        return $this;
    }

    /**
     * Sets options.
     *
     * @param Array|Zend_Config     $options    The options to set.
     * 
     * @return Kizano_Crypt_mCrypt
     */
    public function setOptions($options)
    {
        $options instanceof Zend_Config && $options = $options->toArray();
        if (!is_array($options)) {
            throw new InvalidArgumentException('Argument 1 ($options) must be an array or Zend_Config');
        }

        foreach ($options as $name => $option) {
            $this->setOption($name, $option);
        }

        return $this;
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
     * @param boolean $target_cipher_state The target cipher state.
     * @return string
     */
    public function crypt($target_cipher_state)
    {
        // If our target cipher state is to be encrypted,
        if ($target_cipher_state) {
            // ... and we are not encrypted, perform the encryption.
            if (!$this->cipher_state) {
                $this->text = base64_encode(mCrypt_generic($this->_mCrypt, $this->text));
            }
        } else { // Otherwise, if we are targeting plaintext
            // ... and we are encrypted, then perform the decryption.
            if ($this->cipher_state) {
                empty($this->text) || $this->text = rtrim(mDecrypt_generic($this->_mCrypt, base64_decode($this->text)), "\0");
            }
        }
        // Otherwise, if we are already in our requested state, then do not overperform the ciphers.

        $this->cipher_state = (bool)$target_cipher_state;
        return $this->text;
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
     * Parses a string and attempts to populate this object. Does the reverse of {@function __toString}
     *
     * @param String    $string     The string to parse.
     * 
     * @return Kizano_Crypt_Mcrypt
     * @throws InvalidArgumentException
     */
    public static function fromString($string)
    {
        if (!is_string($string)) {
            throw new InvalidArgumentException('Argument 1 ($string) expected string.');
        }

        if (empty($string)) {
            throw new InvalidArgumentException('Empty string passed.');
        }

        $self = new self;
        list($self->cipher, $self->cipher_state, $self->cipher_mode, $self->key, $self->iv, $self->text) = explode($self->DELIMIT, trim($string));
        $self->cipher_state = (bool)$self->cipher_state;
        $self->iv = base64_decode($self->iv);
        return $self;
    }

    /**
     * Magick method to render this class as a string.
     * 
     * @return string
     */
    public function __toString()
    {
        return serialize($this);
        /*
        return join(self::DELIMIT, array(
            $this->cipher,
            $this->cipher_state? '1': '0',
            $this->cipher_mode,
            $this->key,
            base64_encode($this->iv),
            $this->text
        ));
        //*/
    }
}

