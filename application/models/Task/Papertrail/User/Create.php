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

    public function process(Application_Model_Queue $queueElement = null) {
        $this->_updateStoreStatus('creating-papertrail-user');
        
        $this->logger->log('Creating papertrail user.', Zend_Log::INFO);

        if($this->_userObject->getHasPapertrailAccount() 
            && $this->_userObject->getPapertrailApiToken()){
            $this->logger->log('User already exist in papertrail, will not try to create it through API.', Zend_Log::INFO);
            return true;
        }
        
        $id = $this->config->papertrail->prefix . (string) $this->_userObject->getId();
        
        $output = array(
            (string) $id,
            (string) $this->_userObject->getLogin(),
            (string) $this->_userObject->getEmail()
        );
        $message = var_export($output, true);
        $this->logger->log($message, Zend_Log::DEBUG);

        try { 
            $response = $this->_service->createUser(
                (string) $id, 
                (string) $this->_userObject->getLogin(), 
                array(
                    'id'    => $id,
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
        
    }
    
}