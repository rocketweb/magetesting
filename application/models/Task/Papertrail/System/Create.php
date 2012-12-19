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
class Application_Model_Task_Papertrail_System_Create 
extends Application_Model_Task_Papertrail 
implements Application_Model_Task_Interface {

    public function process(Application_Model_Queue $queueElement = null) {
        $this->_updateStoreStatus('creating-papertrail-system');

        $this->logger->log('Creating papertrail system.', Zend_Log::INFO);
        
        $id = $this->config->papertrail->prefix . $this->_storeObject->getDomain();
        $accountId = $this->config->papertrail->prefix . (string) $this->_userObject->getId();
        
        $output = array(
            (string) $id,
            (string) $this->_storeObject->getDomain(),
            (string) $accountId
        );
        $message = var_export($output, true);
        $this->logger->log($message, Zend_Log::DEBUG);

        try { 
            $response = $this->_service->createSystem(
                (string) $id, 
                (string) $this->_storeObject->getDomain(), 
                (string) $accountId
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
        
    }

}