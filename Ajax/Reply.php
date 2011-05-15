<?php
/**
 *  Kizano_Ajax_Reply
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
 *  @package    Ajax
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */

/**
 *  Handles a dynamic call to the system via AJAX
 *
 *  @category   Kizano
 *  @package    Ajax
 *  @copyright  Copyright (c) 2009-2011 Markizano Draconus <markizano@markizano.net>
 *  @license    http://framework.zend.com/license/new-bsd     New BSD License
 *  @author     Markizano Draconus <markizano@markizano.net>
 */
class Kizano_Ajax_Reply
{
    /**
     * Holds the front controller request
     * 
     * @var Zend_Controller_Request_Http
     */
    protected $_request;

    /**
     * Holds the front controller response
     * 
     * @var Zend_Controller_Response_Http
     */
    protected $_response;

    /**
     * Holds the layout
     * 
     * @var Zend_Layout
     */
    protected $_layout;

    /**
     * Status of this response.
     * 
     * @var string
     */
    protected $_status = false;

    /**
     * Data for the response
     * 
     * @var string
     */
    protected $_data;

    /**
     * Header messages to send to the client.
     * 
     * @var Array
     */
    protected $_messages;

    /**
     * Static accessor to chain ajax responses.
     * 
     * @return Kizano_Ajax_Response
     */
    public static function factory()
    {
        return new self;
    }

    /**
     * Bootstraps this class and sets up the necessary dependencies.
     * 
     * @return void
     */
    public function __construct()
    {
        $front = Zend_Controller_Front::getInstance();
        $this->_request = $front->getRequest();
        $this->_response = $front->getResponse();
        $this->_layout = Zend_Layout::getMvcInstance();
    }

    /**
     * Magick method to access the internal data pointer.
     * 
     * @param $key    string    The key of the variable to obtain.
     * 
     * @return void
     */
    public function __get($key)
    {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        return false;
    }

    /**
     * Magick method to set data to the internal pointer.
     * 
     * @param $key        string    The value to name.
     * @param $value    Mixed    The value to set.
     * 
     * @return void
     */
    public function __set($key, $value = null)
    {
        $this->_data[$key] = $value;
    }

    /**
     * Returns a string-representation for this class.
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->send();
    }

    /**
     * Changes the layout, the headers and sends the AJAX response.
     * 
     * @return void
     */
    public function send()
    {
        $this->_response->setHeader('Content-Type', 'application/json');
        $this->_layout->setLayout('json');
        $result = array(
            'status' => $this->_status,
            'messages' => $this->_messages,
            'data' => $this->_data,
        );
        $this->_layout->getView()->content = Zend_Json::encode($result);
        $body = $this->_layout->getView()->render('index/ajax.phtml');
        $this->_response->setBody($body);
        return $body;
    }

    /**
     * Sets the status to successful.
     * 
     * @return Kizano_Ajax_Reply
     */
    public function setPass()
    {
        $this->_status = true;
        return $this;
    }

    /**
     * Sets the status to fail.
     * 
     * @return Kizano_Ajax_Reply
     */
    public function setFail()
    {
        $this->_status = false;
        return $this;
    }

    /**
     * Checks to see if the request has passed or not.
     * 
     * @return boolean
     */
    public function getStatus()
    {
        return (bool)$this->_status;
    }

    /**
     * Adds a message to the queue.
     * 
     * @param $message
     * 
     * @return Kizano_Ajax_Reply
     */
    public function addMessage($message)
    {
        $this->_messages[] = $message;
        return $this;
    }

    /**
     * Clears messages from the queue.
     * 
     * @return Kizano_Ajax_Reply
     */
    public function clearMessages()
    {
        $this->_messages = array();
        return $this;
    }

    /**
     * Gets all messages in the queue.
     * 
     * @return Array
     */
    public function getMessages()
    {
        return $this->_messages;
    }
}

