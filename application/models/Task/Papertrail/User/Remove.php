<?php
/**
 * Responsible for remove user in Papertrail
 * 
 * @category   Application
 * @package    Model_Task
 * @subpackage Papertrail_User
 * @copyright  Copyright (c) 2012 RocketWeb USA Inc. (http://www.rocketweb.com)
 * @author     Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail_User_Remove 
extends Application_Model_Task_Papertrail 
implements Application_Model_Task_Interface {

    public function process(Application_Model_Queue $queueElement = null) {
        $this->_updateStoreStatus('removing-papertrail-user');

        $this->logger->log('Removing papertrail user.', Zend_Log::INFO);

        $id = $this->config->papertrail->prefix . (string) $this->_userObject->getId();
        
        $output = array((string)$id);
        $message = var_export($output, true);
        $this->logger->log($message, Zend_Log::DEBUG);

        try { 
            $response = $this->_service->removeUser((string)$id);
        } catch(Zend_Service_Exception $e) {
            // invalid response or connection problem eg. timeout
            $message = 'Could not remove Paper Trail user.';
            $this->logger->log($message, Zend_Log::CRIT);
            $this->logger->log($e->getMessage(), Zend_Log::DEBUG);
            throw new Application_Model_Task_Exception($message);
        }
        
        if(isset($response->status) && $response->status == 'ok') {
            //success
            $this->_userObject->setPapertrailApiToken(null);
            $this->_userObject->setHasPapertrailAccount(0);
            $this->_userObject->save();
        }

    }
    
}