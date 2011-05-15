<?php
/**
 *  Kizano_View_Helper_Settings
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
 *  @package    View
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */

/**
 *  A helper to allow printing of settings in various formats.
 *
 *  @category   Kizano
 *  @package    View
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */
class Kizano_View_Helper_Settings extends Zend_View_Helper_Abstract
{
    /**
     *  Formats the settings according to the needs of the arguments.
     *  
     *  @return Array
     */
    public function settings()
    {
        return $this;
    }

    /**
     *  Performs the mass-active replacement on all the user-defined settings.
     *  
     *  @param String  $content  The content to replace.
     *  
     *  @return String
     */
    public function replace($content)
    {
        foreach (Zend_Registry::get('settings')->getArrayCopy() as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return $content;
    }
}

