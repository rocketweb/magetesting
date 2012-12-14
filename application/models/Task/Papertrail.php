<?php
/**
 * Responsible for service Papertrail API
 * 
 * @category   Application
 * @package    Model_Task
 * @subpackage Papertrail
 * @copyright  Copyright (c) 2012 RocketWeb USA Inc. (http://www.rocketweb.com)
 * @author     Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail extends Application_Model_Task {

    /**
     * Reference to REST Papertrail client object
     * 
     * @var RocketWeb_Service_Papertrail
     */
    protected $_service;
    
    public function setup(\Application_Model_Queue &$queueElement) {
        parent::setup($queueElement);
        
        $this->_connect();
    }

    /**
     * Create the connect to Papertrail Service
     * 
     * @return Application_Model_Task_Papertrail
     */
    protected function _connect() {
        $this->_service = new RocketWeb_Service_Papertrail(
            $this->config->papertrail->username,
            $this->config->papertrail->password    
        );

        return $this;
    }
}