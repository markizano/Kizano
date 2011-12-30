<?php
/**
 *	Class for handling encryption over layers.
 *	
 *	@depends mCrypt
 */
class Kizano_Crypt_Cryptatraz
{
	public $cipher_state = false;                               # The current cipher state
	public $key = null;                                         # Sequence of encryption
	public $keys = array();                                     # List of keys for the mCrypt instances.
	public $ivs = array();                                      # List of IVs for the mCrypt instances.
	public $text = null;                                        # Text we are hiding

	protected $_crypts = array();                               # Child mCrypt classes
	protected $_options = array();                              # Additional configurable options.

    /**
     * Constructs a new instance of this class.
     *
     * @param Array     $options    The list of options to assign.
     * 
     * @return void
     */
	public function __construct(array $options = null)
	{
	    if (!isset($options['key']) || empty($options['key'])) {
	        trigger_error('Empty key passed into Cryptatraz.', E_USER_WARNING);
	    }

	    $this->setOptions($options);
	}

    public function __destruct()
    {
        foreach ($this->_crypts as $i => $crypt) {
            $crypt->__destruct();
            unset($this->_crypts[$i], $crypt);
        }
    }

    public function __sleep()
    {
        array_map('serialize', $this->_crypts);
        return array(
            'cipher_state',
            'key',
            'keys',
            'ivs',
            'text',
            '_crypts',
        );
    }

    public function __wakeup()
    {
        foreach (str_split($this->key, 1) as $i => $cipher) {
            $this->_crypts[$i] = new Kizano_Crypt_Mcrypt(array(
                'cipher' => $cipher,
                'cipher_state' => false,
                'text' => $this->text,
                'key' => $this->keys[$i],
                'iv' => $this->ivs[$i],
            ));
        }

        return false;
    }

    /**
     * To a string, you go.
     *
     * @return String
     */
	public function __toString()
	{
        return serialize($this);
	}

    /**
     * Gets us an option.
     *
     * @param String    $name       The option to fetch.
     * @param Mixed     $default    What to return if nothing else. [@phpunit]
     *
     * @return Mixed
     */
    public function getOption($name, $default = null)
    {
        if (isset($this->$name)) {
            return $this->$name;
        } elseif (isset($this->_options[$name])) {
            return $this->_options[$name];
        }

        return $default;
    }

    /**
     * Sets all options you could possibly want for this class.
     *
     * @param Array     $options    The key-value options to set.
     *
     * @return Cryptatraz
     */
    public function setOptions($options)
    {
        $options instanceof Zend_Config && $options = $options->toArray();
        if (empty($options) || !is_array($options)) {
            throw new InvalidArgumentException('Argument 1 ($options) expected to be array or instanceof Zend_Config');
        }

        foreach ($options as $name => $option) {
            $this->setOption($name, $option);
        }

        return $this;
    }

    /**
     * Sets an option.
     *
     * @param String    $name   The name of the  option to assign.
     * @param Mixed     $value  The value to assign that option.
     * 
     * @return Cryptatraz
     */
    public function setOption($name, $value = null)
    {
        if (!empty($name) && !is_string($name)) {
            throw new InvalidArgumentException('Argument 1 ($name) must be a string.');
        }

        switch (strToLower($name)) {
            case 'cipher_state':
                $this->cipher_state = (bool)$value;
                break;
            case 'key':
                if (empty($value) || !is_string($value)) {
                    throw new InvalidArgumentException("Option for key `$name' not valid type. Expected string.");
                }

                if (!Kizano_Strings::is_hex($value)) {
                    throw new InvalidArgumentException("Option for key `$name' expected hex.");
                }

                $this->key = $value;
                break;
            case 'keys':
                if (!empty($value) && !is_array($value)) {
                    throw new InvalidArgumentException("Option for key `$name' not a valid type. Expected array.");
                }

                $this->keys = $value;
                break;
            case 'ivs':
                if (!empty($value) && !is_array($value)) {
                    throw new InvalidArgumentException("Option for key `$name' not a valid type. Expected array.");
                }

                $this->ivs = $value;
                break;
            case 'text':
                if (!empty($value) && !is_string($value)) {
                    throw new InvalidArgumentException("Option for key `$name' not a valid type. Expected string.");
                }

                $this->text = $value;
                break;
            case 'crypts':
                if (empty($value) || !is_array($value)) {
                    throw new InvalidArgumentException("Option for key `$name' not a valid type. Expected array.");
                }

                foreach ($value as $i => $crypt) {
                    if (!empty($crypt) && is_string($crypt)) {
                        $crypt = Kizano_Crypt_Mcrypt::fromString($crypt);
                    }

                    if (!empty($crypt) && is_array($crypt)) {
                        if (!isset($crypt['iv']) && isset($this->ivs[$i])) {
                            $crypt['iv'] = $this->ivs[$i];
                        }

                        if (!isset($crypt['key']) && isset($this->keys[$i])) {
                            $crypt['key'] = $this->keys[$i];
                        }

                        isset($crypt['cipher_state']) || $crypt['cipher_state'] = $this->cipher_state;
                        isset($crypt['text']) || $crypt['text'] = $this->text;
                        $crypt = new Kizano_Crypt_Mcrypt($crypt);
                    }

                    if (!$crypt instanceof Kizano_Crypt_Mcrypt) {
                        throw new RuntimeException('Invalid data type for the crypt option at index: ' . $i);
                    }

                    $this->_crypts[$i] = $crypt;
                    $this->keys[$i] = $crypt->key;
                    $this->ivs[$i] = $crypt->iv;
                }
            default:
                $this->_options[$name] = $value;
        }

        return $this;
    }

    /**
     * Perform (de|en)cryption!
     *
     * @param Boolean   $target_cipher_state    The target cipher state.
     * 
     * @return String   Cipherd text.
     */
	public function crypt($target_cipher_state = true)
	{
		$this->cipher_state = $target_cipher_state;
		# 8EA312 -> array(0 => 8, 1 => 'E', 2 => 'A'...);
		$key = str_split($this->cipher_state? $this->key: strRev($this->key), 1);

        /* Should it really?
		if (!is_array($this->crypt)) {
		    throw new RuntimeException('Construct should populate $this->crypt');
	    }
	    //*/

		$buffer = $this->text;
		# array(0 => '8', 1 => 'E', 2 => 'A', 3 => '3', 4 => '1', 5 => '2', 6 => '9', 7 => '8', 8 => '0', 9 => '3');
		# array(0 => '3', 1 => '0', 2 => '8', 3 => '9', 4 => '2', 5 => '1', 6 => '3', 7 => 'A', 8 => 'E', 9 => '8');
		foreach ($key as $c => $cipher) {
			$k = $target_cipher_state? $c: strLen($this->key) - $c - 1;
			$mcrypt_key = isset($this->keys[$k])? $this->keys[$k]: null;
			$mcrypt_iv = isset($this->ivs[$k])? $this->ivs[$k]: null;

			if (!isset($this->_crypts[$cipher])) {
			    $this->_crypts[$cipher] = new Kizano_Crypt_Mcrypt(array(
			        'cipher'        => $cipher,
			        'cipher_state'  => !$target_cipher_state,
			        'text'          => $buffer,
			        'key'           => $mcrypt_key,
			        'iv'            => $mcrypt_iv
		        ));
	        } else {
	            $this->_crypts[$cipher]->setOptions(array(
	                'text' => $buffer,
	                'cipher_state' => !$target_cipher_state,
	                'key' => $mcrypt_key,
	                'iv' => $mcrypt_iv,
	            ));
	        }

#$before = $buffer;
			$this->_crypts[$cipher]->init();
			$buffer = $this->_crypts[$cipher]->crypt($target_cipher_state);
			$this->_crypts[$cipher]->deinit();
/*
var_dump(array(
    't_cipher_state' => $target_cipher_state,
    'cipher' => $this->_crypts[$cipher]->cipher,
    'before' => $before,
    'after' => $buffer,
    $mcrypt_key => $mcrypt_iv,
));
//*/
		}

		$this->cipher_state = $target_cipher_state;
		return $this->text = $buffer;
	}

    public function encrypt()
    {
        return $this->crypt(true);
    }

    public function decrypt()
    {
        return $this->crypt(false);
    }

    /**
     * Pulls an instance of this class from a string and returns the result.
     *
     * @param String    $cryptatraz     The algorithm to build.
     *
     * @return Cryptatraz
     */
    public static function fromString($cryptatraz)
    {
        $options = array();
        list($options['cipher_state'], $options['key'], $crypts) = explode(self::DELIMIT, $cryptatraz);

        foreach ($crypts as $i => $crypt) {
            $crypts[$i] = Kizano_Crypt_Mcrypt::fromString($crypt);
        }

        return new self($crypts);
    }
}

