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
 */

/**
 *  Class wrapper for manipulating encryption using OpenSSL.
 *
 *  @category   Kizano
 *  @package    Crypt
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */
class Kizano_Crypt_Openssl extends Kizano_Crypt_Abstract
{

    /**
     *  Constructs this encryption module.
     *  
     *  @return void
     */
    public function __construct()
    {
        
    }

    public function encrypt()
    {
        return (string)null;
    }

    public function decrypt()
    {
        return (string)null;
    }
}

