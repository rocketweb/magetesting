<?php
/**
 * Responsible for remove system in Papertrail
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

    public function process() {
        $this->_updateStatus('removing-papertrail-system');

        $this->logger->log('Removing papertrail system.', Zend_Log::INFO);
        
        try { 
            $response = $this->_service->removeSystem(
                (string)$this->_instanceObject->getDomain()
            );
        } catch(Zend_Service_Exception $e) {
            $this->logger->log($e->getMessage(), Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($e->getMessage());
        }

        if(isset($response->status) && $response->status == 'ok') {
            //success
            $this->_instanceObject->setPapertrailSyslogHostname(null);
            $this->_instanceObject->setPapertrailSyslogPort(null);
            $this->_instanceObject->save();
        }

        $this->_updateStatus('ready');
    }

}