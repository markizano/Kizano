<?php
/**
 *  Kizano_Validate_Login
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
 *  @package    Validate
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */

/**
 *  Validator to provide login authenticity.
 *
 *  @category   Kizano
 *  @package    Validate
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */
class Kizano_Validate_Login extends Zend_Validate_Abstract
{
    const NOT_AUTHORIZED = 'notAuthorized';
    const UNKNOWN_ERROR = 'unknownError';

    protected $_messageTemplates = array(
        self::NOT_AUTHORIZED   => "Those login credentials were incorrect.",
        self::UNKNOWN_ERROR    => "There was an unexpected error. Please try again.",
    );

    /**
     *    Validates a user's login
     *    @param data        The data to validate.
     *    @return boolean
     */
    public function isValid($data)
    {
        $auth = Zend_Auth::getInstance();
        $adapter = new Kizano_Auth_Adapter_Login;

        $valid = $auth->authenticate($adapter);
        if ($valid->isValid()) {
            return true;
        } else {
            $session = Zend_Registry::get('session');

            // Initialize the number of login attempts.
            isset($session->login_tries) || $session->login_tries = 0;

            $this->_error(self::NOT_AUTHORIZED, $this->_messageTemplates[self::NOT_AUTHORIZED]);

            // Increment the failed number of login attempts.
            $session->login_tries++;
            return false;
        }
    }
}

