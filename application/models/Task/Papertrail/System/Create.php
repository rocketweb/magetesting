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

        /* send email to store owner start */
        $taskParams = $this->_queueObject->getTaskParams();
        if(isset($taskParams['send_store_ready_email'])) {
            $this->_sendStoreReadyEmail();
        }
        /* send email to store owner stop */
    }

    protected function _createRsyslogFile(){
        $systemUser = $this->config->magento->userprefix.$this->_userObject->getLogin();
        $papertrailPrefix = $this->config->papertrail->prefix;
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
	    	PHP_EOL.'$InputFileName /home/'.$systemUser.'/public_html/'.$this->_domain.'/var/log/revision.log'.
            PHP_EOL.'$InputFileTag revision-'.$this->_domain.':'.
            PHP_EOL.'$InputFileStateFile papertrail-revision-'.$this->_domain.''.
            PHP_EOL.'$InputRunFileMonitor'.
            /* 4 lines for user own errorlog */
            PHP_EOL.'$InputFileName /home/'.$systemUser.'/error.log'.
            PHP_EOL.'$InputFileTag php-'.$this->_domain.':'.
            PHP_EOL.'$InputFileStateFile papertrail-php-'.$this->_domain.''.
            PHP_EOL.'$InputRunFileMonitor'.        
                    
            PHP_EOL.''.
            PHP_EOL.'$template Template-'.$this->_domain.', "%timestamp% '.$papertrailPrefix.$this->_domain.' %syslogtag% %msg%\n"'.
            PHP_EOL.''.
            PHP_EOL.'if $programname == \'system-'.$this->_domain.'\' then @'.$this->_storeObject->getPapertrailSyslogHostname().':'.$this->_storeObject->getPapertrailSyslogPort().';Template-'.$this->_domain.
            PHP_EOL.'& ~'.
            PHP_EOL.''.
            PHP_EOL.'if $programname == \'exception-'.$this->_domain.'\' then @'.$this->_storeObject->getPapertrailSyslogHostname().':'.$this->_storeObject->getPapertrailSyslogPort().';Template-'.$this->_domain.
            PHP_EOL.'& ~'.
            PHP_EOL.''.
	    	PHP_EOL.'if $programname == \'revision-'.$this->_domain.'\' then @'.$this->_storeObject->getPapertrailSyslogHostname().':'.$this->_storeObject->getPapertrailSyslogPort().';Template-'.$this->_domain.
            PHP_EOL.'& ~'.
			PHP_EOL.''.
            
            /* apache error log, ignore sudo lines and mysql to prevent password logging */
            PHP_EOL.'if $programname == \'php-'.$this->_domain.'\' and $msg contains \'/home/'.$systemUser.'/public_html/'.$this->_domain.'\' and not ($msg contains \'mysql\' or $msg contains \'sudo\') then @'.$this->_storeObject->getPapertrailSyslogHostname().':'.$this->_storeObject->getPapertrailSyslogPort().';Template-'.$this->_domain.
            PHP_EOL.'& ~'.
            PHP_EOL.'';
            
            file_put_contents($filename, $lines);
            
        }
        
        exec('sudo /etc/init.d/rsyslog restart');
        
    }
    
    /**
     * Sends email about successful install to store owner
     * used by MagentoInstall and MagentoDownload Tasks
     */
    protected function _sendStoreReadyEmail(){
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/views/scripts/');
    
        // assign values
        $html->assign('domain', $this->_storeObject->getDomain());
    
        $serverModel = new Application_Model_Server();
        $serverModel->find($this->_storeObject->getServerId());
    
        //our store url
        $html->assign('installedUrl', 'http://'.$this->_userObject->getLogin().'.'.$serverModel->getDomain());
    
        //storeUrl variable from local.ini
        $html->assign('storeUrl', $this->config->magento->storeUrl);
    
        $html->assign('backend_name', $this->_storeObject->getBackendName());
        $html->assign('admin_login', $this->_userObject->getLogin());
        $html->assign('admin_password', $this->_storeObject->getBackendPassword());
    
        // render view
        try{
            $bodyText = $html->render('_emails/queue-item-ready.phtml');
        } catch(Zend_View_Exception $e) {
            $this->logger->log('Store ready mail could not be rendered.', Zend_Log::CRIT, $e->getTraceAsString());
        }
    
        // create mail object
        $mail = new Zend_Mail('utf-8');
        // configure base stuff
        $mail->addTo($this->_userObject->getEmail());
        $mail->setSubject($this->config->cron->queueItemReady->subject);
        $mail->setFrom($this->config->cron->queueItemReady->from->email, $this->config->cron->queueItemReady->from->desc);
        $mail->setBodyHtml($bodyText);
    
        try {
            $mail->send();
        } catch (Zend_Mail_Transport_Exception $e){
            $this->logger->log('Store ready mail could not be sent.', Zend_Log::CRIT, $e->getTraceAsString());
        }
    
    }
}
