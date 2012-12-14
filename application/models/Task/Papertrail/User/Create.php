<?php
/**
 * Responsible for create user in Papertrail
 * 
 * @category   Application
 * @package    Model_Task
 * @subpackage Papertrail_User
 * @copyright  Copyright (c) 2012 RocketWeb USA Inc. (http://www.rocketweb.com)
 * @author     Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail_User_Create 
extends Application_Model_Task_Papertrail 
implements Application_Model_Task_Interface {

    public function process() {
        $this->_updateStatus('creating-papertrail-user');

        $this->logger->log('Creating papertrail user.', Zend_Log::INFO);

        try { 
            $response = $this->_service->createUser(
                (string)$this->_userObject->getId(), 
                (string)$this->_userObject->getLogin(), 
                array(
                    'id'    => $this->_userObject->getId(),
                    'email' => $this->_userObject->getEmail()
            ));
        } catch(Zend_Service_Exception $e) {
            $this->logger->log($e->getMessage(), Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($e->getMessage());
        }

        if(isset($response->api_token)) {
            //success
            $this->_userObject->setPapertrailApiToken($response->api_token)
                              ->setHasPapertrailAccount(1)
                              ->save();
        }
        
        $this->_updateStatus('ready');
    }
    
}