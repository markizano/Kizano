<?php
/**
 *  Kp_Auth_Adapter_Login
 *  Authentication adapter for allowing an end-user to login.
 *  Needs to be overloaded per site to determine where to take a user.
 * 
 *  @category   KissPHP/Library
 *  @package    Auth/Adapter/Login
 *  @copyright  Copyright (c) 2008-2012 W3Evolutions LLC  <http://www.w3evolutions.com>
 *  @author	W3Evolutions Kiss PHP Dev Team [kissphpdevteam@w3evolutions.com]
 *  @license	http://www.w3evolutions.com/license/license.html
 *  @link	http://www.w3evolutions.com
 */

if (!extension_loaded('hash')) {
    throw new RuntimeException(__FILE__  . ' requires the \'hash\' extension');
}

class Kizano_Auth_Adapter_Kp extends Kp_Auth_Adapter_Login
{

    /**
     *  Bootstraps this class.
     *
     *  @return void
     */
    public function __construct()
    {
        $this->_request = Zend_Controller_Front::getInstance()->getRequest();
        $this->_response = Zend_Controller_Front::getInstance()->getResponse();
        $this->_login = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
		$this->_userAuthLog = Kp::get('User_Auth_Log');
    }

    /**
     * Attempts to authenticate the user.
     *
     * @return boolean
     */
    public function authenticate()
    {
        # First gather the necessary data.
        $params = $this->_request->getParams();
        $result = false; # A user should fail by default, they are authenticating here.

		// If we specified a password, which we should have done, use it instead of assuming we want getParams all the time
		$username = !empty($this->_identity) ? $this->_identity : $params['username'];
		$password = !empty($this->_credential) ? $this->_credential : $params['password'];

        # If the given credentials are not provided by the end-user
        if (!isset($username) || !isset($password)) {
            $result = new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE,
                null,
                array('Empty login credentials!')
            );
        }

        $user = Kp::get('User')->loadByUsername($username); /*
			array(
				'username',
				'active',
			),
            array(
				array('eq' => $username),
				array('eq' => '1')
			)
		);
		//*/
        if (!$user->hasData() || !$user->getUserId() || $user->getUserId() === null) {
            $result = new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                $username,
                array('Invalid login credentials.')
            );
        }

        # Mock up the hashed password and compare results.
        $hash = Kp_Strings::hashPass($password, $user->getSalt());
        if ($hash === $user->getPassword()) {
            if ($user['active']) {
                $this->_login = $user;
				$group_id = Kp::get('User_Groups')
					->getResource()
					->fetchRow(array('user_id = ?' => $this->_login->getUserId() ))
					->group_id;

				$this->_login->setData(array(
					'type' => $user->getGroups()
					->getResource()
					->fetchRow(array('group_id = ?' => $group_id))->name,
					'group_id' => $group_id,
				));

				$result = new Zend_Auth_Result(
					Zend_Auth_Result::SUCCESS,
					$this->_login,
					array('Successful Login!')
				);
				
            } else {
                $result = new Zend_Auth_Result(
                    Zend_Auth_Result::FAILURE_UNCATEGORIZED,
                    $username,
                    array('Login pass, but your account is inactive.')
                );
            }
        } else {
            $result = new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                $username,
                array('Invalid login credentials.')
            );
        }

        # If all the above checks fail, then the user is invalid.
        if (!$result) {
            $result = new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                $username,
                array('Invalid login credentials.')
            );
        }

		// Log the result
		$messages = $result->getMessages();
		if(count($messages) && is_array($messages)) {
			$this->_userAuthLog->setData('username', $username);
			$this->_userAuthLog->setData('ip', $_SERVER['REMOTE_ADDR']);
			$this->_userAuthLog->setData('result', $messages[0]);
			$this->_userAuthLog->setData('active', '1');
			$this->_userAuthLog->save();
		}

        return $result;
    }

    /**
     * Retrieves the username from the attempted login.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->_login['username'];
    }

    /**
     * Retrieves the username from the attempted login.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->_login['email'];
    }

    /**
     * Gets the user's active status
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->_login['active'];
    }

    /**
     * Gets the user's paid status
     *
     * @return boolean
     */
    public function getPaid()
    {
        return $this->_login['paid'];
    }
}
