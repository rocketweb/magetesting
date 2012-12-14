<?php
/**
 * Responsible for create system in Papertrail
 * 
 * @category   Application
 * @package    Model_Task
 * @subpackage Papertrail_System
 * @copyright  Copyright (c) 2012 RocketWeb USA Inc. (http://www.rocketweb.com)
 * @author     Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail_System_Create extends Application_Model_Task_Papertrail implements Application_Model_Task_Interface {

    public function process() {
        $this->_updateStatus('creating-papertrail-system');
        
        try { 
            $response = $this->_service->createSystem(
                (string)$this->_storeObject->getDomain(), 
                (string)$this->_storeObject->getStoreName(), 
                (string)$this->_userObject->getId()
            );
        } catch(Zend_Service_Exception $e) {
            $this->logger->log($e->getMessage(), Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($e->getMessage());
        }
        
        if(isset($response->id)) {
            //success
            $this->_storeObject->setPapertrailSyslogHostname($response->syslog_hostname)
                                  ->setPapertrailSyslogPort($response->syslog_port)
                                  ->save();
        }
        
        $this->_updateStatus('ready');
    }

}