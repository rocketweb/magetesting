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
        
        $this->_createRsyslogFile();
    }

    protected function _createRsyslogFile(){
        $systemUser = $this->config->magento->userprefix.$this->_userObject->getLogin();
        $this->_domain = $this->_storeObject->getDomain();
        $filename = '/etc/rsyslog.d/'.$systemUser.'_'.$this->_domain.'.conf';
        if (!file_exists($filename)){
            exec('sudo touch '.$filename);

            $lines = '$ModLoad imfile'.
            PHP_EOL.'$InputFileName /home/'.$systemUser.'/public_html/'.$this->_domain.'/var/log/system.log'.
            PHP_EOL.'$InputFileTag system-'.$this->_domain.':'.
            PHP_EOL.'$InputFileStateFile papertrail-system-'.$this->_domain.''.
            PHP_EOL.'$InputRunFileMonitor'.
            PHP_EOL.'$InputFileName /home/'.$systemUser.'/public_html/'.$this->_domain.'/var/log/exception.log'.
            PHP_EOL.'$InputFileTag exception-'.$this->_domain.':'.
            PHP_EOL.'$InputFileStateFile papertrail-exception-'.$this->_domain.''.
            PHP_EOL.'$InputRunFileMonitor'.
            PHP_EOL.''.
            PHP_EOL.'if $programname == \'system-'.$this->_domain.'\' then @'.$this->_storeObject->getPapertrailSyslogHostname().':'.$this->_storeObject->getPapertrailSyslogPort().''.
            PHP_EOL.'& ~'.
            PHP_EOL.''.
            PHP_EOL.'if $programname == \'exception-'.$this->_domain.'\' then @'.$this->_storeObject->getPapertrailSyslogHostname().':'.$this->_storeObject->getPapertrailSyslogPort().''.
            PHP_EOL.'& ~';
            
            file_put_contents($filename, $lines);
            
        }
        
        exec('sudo /etc/init.d/rsyslog restart');
        
    }
    
}