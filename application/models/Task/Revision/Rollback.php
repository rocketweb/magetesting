<?php

class Application_Model_Task_Revision_Rollback 
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {

    
    public function process(Application_Model_Queue &$queueElement = null) {
        
        $this->_updateStatus('rolling-back-revision');
        
        $this->_revertFiles();
        
        $this->_revertDatabase();
        
        $this->_cleanup();
        
        $this->_updateRevisionCount('-1');
        
        $this->_clearInstanceCache();
                
        $this->_updateStatus('ready');
        
    }

    protected function _revertFiles(){
        $this->logger->log('Reverting files.', Zend_Log::INFO);

        $startCwd = getcwd();
        
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        
        $params = $this->_queueObject->getTaskParams();
       
        //revert files using rollback_files_to param, prevent opening commit message
        exec('git revert '.$params['rollback_files_to'].' --no-edit');
        chdir($startCwd);
    }

    protected function _revertDatabase(){
	    $this->logger->log('Unpacking database backup file. ',Zend_Log::INFO);
        $startCwd = getcwd();
        $params = $this->_queueObject->getTaskParams();
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain().'/var/db');
        
        exec('sudo tar -zxf '.$params['rollback_db_to'].'');

        $unpackedName = str_replace('.tgz','',$params['rollback_db_to']);

        $privilegeModel = new Application_Model_DbTable_Privilege($this->db,$this->config);
        $privilegeModel->dropDatabase($this->_userObject->getLogin().'_'.$this->_instanceObject->getDomain());
        $privilegeModel->createDatabase($this->_userObject->getLogin().'_'.$this->_instanceObject->getDomain());

        $this->logger->log('Reverting database from backup file. ',Zend_Log::INFO);
        $command = 'sudo mysql -u'.$this->config->resources->db->params->username.' -p'.$this->config->resources->db->params->password.' '.$this->config->magento->instanceprefix.$this->_userObject->getLogin().'_'.$this->_instanceObject->getDomain().' < '.$unpackedName;
        exec($command,$output);
        $message = var_export($output, true);
        $this->logger->log($message, Zend_Log::DEBUG);
       
        //finish process
        chdir($startCwd);       
    }
    
    protected function _cleanup(){
        //remove extension id from instance_extension if there was extension in this commit.
        if ($this->_queueObject->getExtensionId()!=0){
            $this->db->delete('instance_extension',array(
                'instance_id = ' . $this->_queueObject->getInstanceId(),
                'extension_id  ='. $this->_queueObject->getExtensionId()
                )
            );
        }

        //remove last entry from revision table
        $revisionModel = new Application_Model_Revision();
        $revisionModel->getLastForInstance($this->_instanceObject->getId());
        
        $startCwd = getcwd();
        $instanceDir = $this->_instanceFolder.'/'.$this->_instanceObject->getDomain();
        chdir($instanceDir);
        
        if ($revisionModel->getFilename()){
            /* remove revision deployment file */
            if (file_exists($instanceDir.'/var/deployment/'.$revisionModel->getFilename())){
                unlink($instanceDir.'/var/deployment/'.$revisionModel->getFilename());
            }
        }

        /* remove database file */
        if (file_exists($instanceDir.'/var/db/'.$revisionModel->getDbBeforeRevision())){
            unlink($instanceDir.'/var/db/'.$revisionModel->getDbBeforeRevision());
        }

        /* remove database entry */
        $this->db->delete('revision',
            array('id = '.$revisionModel->getId())
        );

        chdir($startCwd);       
    }

}
