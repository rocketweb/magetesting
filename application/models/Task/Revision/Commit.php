<?php

/* revision classes might need abstract clas for overall repository handling (like creating one within store */

class Application_Model_Task_Revision_Commit 
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {
   
    protected $_dbBackupPath = '';
    protected $_git;

    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
        
        $extensionModel = new Application_Model_Extension();
        $extensionModel->find($queueElement->getExtensionId());
        $this->_extensionObject = $extensionModel;

        $this->_git = $this->cli('git');
    }

    protected function _git()
    {
        return $this->_git->clear();
    }

    public function process(Application_Model_Queue $queueElement = null) {
        $this->_updateStoreStatus('commiting-revision');
              
        $this->_createDbBackup();
        if ($this->_commit()){
            $hash = $this->_revisionHash;
            if('manual-commit' != $hash) {
                $this->_insertRevisionInfo();
                $this->_updateRevisionCount('+1');
            }

            /* send email to store owner start */
            $taskParams = $this->_queueObject->getTaskParams();
            if(isset($taskParams['send_store_ready_email'])) {
                $this->_sendStoreReadyEmail();
            }
            /* send email to store owner stop */
        }
    }

    protected function _commit() {
        $params = $this->_queueObject->getTaskParams();

        $startCwd = getcwd();

        chdir($this->_storeFolder.'/'.$this->_storeObject->getDomain());
        $result =  null;
        if ($params['commit_type']=='manual'){
            $result = $this->_commitManual(); 
        } else {
            $result = $this->_commitAutomatic();
        }

        chdir($startCwd);

        return $result;
    }
    
    /* For now just an alias */
    protected function _commitManual(){
        $this->logger->log('Making manual GIT commit.', Zend_Log::INFO);

        $this->_git()->addAll()->call();

        $output = '';
        $params = $this->_queueObject->getTaskParams(); 
        
        if (trim($params['commit_comment'])==''){
            $params['commit_comment']='No comment given for this commit';
        }

        $output = $this->_git()->commit($params['commit_comment'])->call()->getLastOutput();

        if (!count($output)){
            $message = 'No changes have been made, manual commit aborted';
            $this->logger->log($message, Zend_Log::NOTICE);
            // return false to avoid proceeding with this task processing
            // but to avoid setting store status as error
            return false;
        }

        // do nothing if there were no changes
        if(stristr(implode($output), 'nothing to commit')) {
            $this->_revisionHash = 'manual-commit';
            return true;
        }

        //get revision committed
        preg_match("#\[(.*?) ([a-z0-9]+)\]#is", $output[0],$matches);

	/* log lines to revision log */
        $linesToLog = array();
        $linesToLog[] = date("Y-m-d H:i:s").' - '.$params['commit_comment'];
        $candumpnow=0;
        foreach ($output as $line){
	    if(strstr($line,'git commit --amend --author=') || strstr($line,'git commit --amend --reset-author')){
	      $candumpnow=1;
	      continue;
	    }
	    if (!$candumpnow || trim($line)==''){
	      continue;
	    }
	    
	    $linesToLog[] = $line;
        }

        /** 
         * Split logger into parts and let it rest for a while 
         * with each iteration. This lets rsyslog send all lines to papertrail 
         */
        $revLogs = array_chunk($linesToLog,10);
        foreach($revLogs as $tenlines){
            $this->revisionLogger->info(implode("\n",$tenlines));
            usleep(3000);
        }
               
        if (!isset($matches[2])){
            $message = 'Could not find revision information, aborting';
            $this->logger->log($message, Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($message);
        }
        
        //insert revision entry
        $this->_revisionHash  = $matches[2];
        return true;
    }
    
    protected function _commitAutomatic(){
        $this->logger->log('Making automatic GIT commit.', Zend_Log::INFO);
        $command = $this->_git()->addAll();
        $output = $command->call()->getLastOutput();
        $message = var_export($output, true);
        $this->logger->log($command->toString(), Zend_Log::DEBUG);
        $this->logger->log($message, Zend_Log::DEBUG);
        unset($output);

        $params = $this->_queueObject->getTaskParams(); 
        
        if (trim($params['commit_comment'])==''){
            $params['commit_comment']='No comment given for this commit';
        }

        $command = $this->_git()->commit($params['commit_comment']);
        $output = $command->call()->getLastOutput();

        $message = var_export($output, true);
        $this->logger->log($command->toString(), Zend_Log::DEBUG);
        $this->logger->log($message, Zend_Log::DEBUG);

        /* log lines to revision log */
        if($params['commit_comment'] != 'Initial Magento Commit'){
            $linesToLog = array();
            $linesToLog[] = date("Y-m-d H:i:s").' - '.$params['commit_comment'];
            $candumpnow=0;
            foreach ($output as $line){
                if(strstr($line,'git commit --amend --author=') || strstr($line,'git commit --amend --reset-author')){
                    $candumpnow=1;
                    continue;
                }
                if (!$candumpnow || trim($line)==''){
                    continue;
                }

                $linesToLog[] = $line;
            }

            /** 
             * Split logger into parts and let it rest for a while 
             * with each iteration. This lets rsyslog send all lines to papertrail 
             */
            $revLogs = array_chunk($linesToLog,10);
            foreach($revLogs as $tenlines){
                $this->revisionLogger->info(implode("\n",$tenlines));
                usleep(3000);
            }
            
        }

        if (count($output) < 3 && isset($output[2]) && trim($output[2])=='nothing to commit (working directory clean)'){
            $message = 'No changes have been made, automatic commit aborted';
            $this->logger->log($message, Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($message);
        }
        //get revision committed
        preg_match("#\[(.*?) ([a-z0-9]+)\]#is", $output[0], $matches);
               
        if (!isset($matches[2])){
            $message = 'Could not find revision information, aborting';
            $this->logger->log($message, Zend_Log::CRIT);
            throw new Application_Model_Task_Exception($message);
        }
        
        //insert revision entry
        $this->_revisionHash  = $matches[2];
        return true;
    }

    /* Not used yet */
    protected function _push() {
        
    }
    
    protected function _createDbBackup(){
        $startCwd = getcwd();
        chdir($this->_storeFolder.'/'.$this->_storeObject->getDomain());
        
        //export backup
        $this->logger->log('Creating database backup.', Zend_Log::INFO);
        $dbDir = $this->_storeFolder.'/'.$this->_storeObject->getDomain().'/var/db/';
        $file = $this->cli('file');
        $file->create($dbDir, $file::TYPE_DIR)->asSuperUser()->call();
        chdir($dbDir);
        $dbFileName = 'db_backup_'.date("Y_m_d_H_i_s");
        $this->cli('mysql')->connect(
            $this->config->resources->db->params->username,
            $this->config->resources->db->params->password,
            $this->config->magento->storeprefix.$this->_userObject->getLogin().'_'.$this->_storeObject->getDomain()
        )->export()->removeDefiners()->append('> ?', $dbFileName)->asSuperUser()->call();

        //pack it up
        $pathinfo = pathinfo($dbFileName);
        /* tar backup file */
        $this->logger->log('Packing database backup.', Zend_Log::INFO);
        $this->cli('tar')->asSuperUser()->pack($pathinfo['filename'].'.tgz', $dbFileName)->isCompressed()->call();

        /* copy packed sql file to target dir */
        //exec('sudo mv '.$pathinfo['filename'].'.tgz '.$dbDir.$pathinfo['filename'].'.tgz');
        
        $this->logger->log('Removing not packed database backup.', Zend_Log::INFO);
        $file->clear()->remove($dbFileName)->asSuperUser()->call();

        chdir($startCwd);
        $this->_dbBackupPath = $dbFileName.'.tgz';
    }
    
    protected function _insertRevisionInfo(){
        $revisionModel = new Application_Model_Revision();
        $params = $this->_queueObject->getTaskParams();

        $revisionModel->setUserId($this->_userObject->getId());
        $revisionModel->setStoreId($this->_storeObject->getId());
        $revisionModel->setExtensionId($this->_queueObject->getExtensionId());
        $revisionModel->setHash($this->_revisionHash);
        $revisionModel->setType($params['commit_type']);
        $revisionModel->setDbBeforeRevision($this->_dbBackupPath);
        $revisionModel->setComment($params['commit_comment']);
        $revisionModel->setFileName('');    
        $revisionModel->save();
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
