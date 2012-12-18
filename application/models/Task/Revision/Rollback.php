<?php

class Application_Model_Task_Revision_Rollback 
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {

    
    public function process(Application_Model_Queue $queueElement = null) {
        
        $this->_updateStoreStatus('rolling-back-revision');
        
        $this->_revertFiles();
        
        $this->_revertDatabase();
        
        $this->_cleanup();
        
        $this->_updateRevisionCount('-1');
        
        $this->_clearStoreCache();        
    }

    protected function _revertFiles(){
        $this->logger->log('Reverting files.', Zend_Log::INFO);

        $startCwd = getcwd();
        
        chdir($this->_storeFolder.'/'.$this->_storeObject->getDomain());
        
        $params = $this->_queueObject->getTaskParams();
       
        //revert files using rollback_files_to param, prevent opening commit message
        exec('git revert '.$params['rollback_files_to'].' --no-edit');
        chdir($startCwd);
    }

    protected function _revertDatabase(){
	    $this->logger->log('Unpacking database backup file. ',Zend_Log::INFO);
        $startCwd = getcwd();
        $params = $this->_queueObject->getTaskParams();
        chdir($this->_storeFolder.'/'.$this->_storeObject->getDomain().'/var/db');
        
        exec('sudo tar -zxf '.$params['rollback_db_to'].'');

        $unpackedName = str_replace('.tgz','',$params['rollback_db_to']);

        $privilegeModel = new Application_Model_DbTable_Privilege($this->db,$this->config);
        $privilegeModel->dropDatabase($this->_userObject->getLogin().'_'.$this->_storeObject->getDomain());
        $privilegeModel->createDatabase($this->_userObject->getLogin().'_'.$this->_storeObject->getDomain());

        $this->logger->log('Reverting database from backup file. ',Zend_Log::INFO);
        $command = 'sudo mysql -u'.$this->config->resources->db->params->username.' -p'.$this->config->resources->db->params->password.' '.$this->config->magento->storeprefix.$this->_userObject->getLogin().'_'.$this->_storeObject->getDomain().' < '.$unpackedName;
        exec($command,$output);
        $message = var_export($output, true);
        $this->logger->log($message, Zend_Log::DEBUG);
       
        //finish process
        chdir($startCwd);       
    }
    
    protected function _cleanup(){
        //remove extension id from store_extension if there was extension in this commit.
        if ($this->_queueObject->getExtensionId()!=0){
            $this->db->delete('store_extension',array(
                'store_id = ' . $this->_queueObject->getStoreId(),
                'extension_id  ='. $this->_queueObject->getExtensionId()
                )
            );
        }

        //remove last entry from revision table
        $revisionModel = new Application_Model_Revision();
        $revisionModel->getLastForStore($this->_storeObject->getId());
        
        $startCwd = getcwd();
        $storeDir = $this->_storeFolder.'/'.$this->_storeObject->getDomain();
        chdir($storeDir);
        
        if ($revisionModel->getFilename()){
            /* remove revision deployment file */
            if (file_exists($storeDir.'/var/deployment/'.$revisionModel->getFilename())){
                unlink($storeDir.'/var/deployment/'.$revisionModel->getFilename());
            }
        }

        /* remove database file */
        if (file_exists($storeDir.'/var/db/'.$revisionModel->getDbBeforeRevision())){
            unlink($storeDir.'/var/db/'.$revisionModel->getDbBeforeRevision());
        }

        /* remove database entry */
        $this->db->delete('revision',
            array('id = '.$revisionModel->getId())
        );

        chdir($startCwd);       
    }

}
