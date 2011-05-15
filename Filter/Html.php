<?php
/**
 *  Kizano_Filter_Html
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
 *  @package    Filter
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */

/**
 *  Filters input and only allows certain elements/attributes
 *
 *  @category   Kizano
 *  @package    Filter
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */
class Kizano_Filter_Html implements Zend_Filter_Interface
{
    /**
     *  Holds config options.
     *  
     *  @var Array
     */
    protected $_options;

    /**
     *  Bootstraps this filter.
     *  
     *  @param Array|Zend_Config    $options    Config options for this filter.
     *  
     *  @return void
     */
    public function __construct($options = array())
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        if ($options !== null && !is_array($options)) {
            throw new InvalidArgumentException('Argument 1 ($options) must be an array or Zend_Config');
        }

        if ($options !== null) {
            $this->setOptions($options);
        }
    }

    /**
     *  Allows the setting of configuration options.
     *  
     *  @return Kizano_Filter_Html
     */
    public function setOptions(array $options)
    {
        foreach ($options as $option => $value) {
            $method = 'set' . ucFirst($option);
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                $this->_options[$option] = $value;
            }
        }

        return $this;
    }
}

