<?php
/**
 * Responsible for remove system in Papertrail
 * 
 * @category   Application
 * @package    Model_Task
 * @subpackage Papertrail_System
 * @copyright  Copyright (c) 2012 RocketWeb USA Inc. (http://www.rocketweb.com)
 * @author     Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail_System_Remove 
extends Application_Model_Task_Papertrail 
implements Application_Model_Task_Interface {

    public function process(Application_Model_Queue $queueElement = null) {
        $this->_updateStoreStatus('removing-papertrail-system');

        $this->logger->log('Removing papertrail system.', Zend_Log::INFO);

        $id = $this->config->papertrail->prefix . $this->_storeObject->getDomain();
        
        $output = array((string)$id);
        $message = var_export($output, true);
        $this->logger->log($message, Zend_Log::DEBUG);

        try { 
            $response = $this->_service->removeSystem((string)$id);
        } catch(Zend_Service_Exception $e) {
            $this->logger->log($e->getMessage(), Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($e->getMessage());
        }

        if(isset($response->status) && $response->status == 'ok') {
            //success
            $this->_storeObject->setPapertrailSyslogHostname(null);
            $this->_storeObject->setPapertrailSyslogPort(null);
            $this->_storeObject->save();
        }
        
        $this->_removeRsyslogFile();

    }
    
    public function _removeRsyslogFile(){
        $systemUser = $this->config->magento->userprefix.$this->_userObject->getLogin();
        $this->_domain = $this->_storeObject->getDomain();
        $filename = '/etc/rsyslog.d/'.$systemUser.'_'.$this->_domain.'.conf';
        if (file_exists($filename)){
            $this->cli('file')->remove($filename)->call();
        }

        $this->logger->log('Restarting rsyslog.', Zend_Log::INFO);

        $command = $this->cli('service')->restart('rsyslog');
        $output = $command->call()->getLastOutput();

        $message = var_export($output, true);
        $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
        unset($output);
    }

}