<?php

class Application_Model_Task_Revision_Rollback 
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {


    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }
    
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
        $startCwd = getcwd();
        
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        
        $params = $this->_queueObject->getTaskParams();
       
        //revert files using rollback_files_to param, prevent opening commit message
        exec('git revert '.$params['rollback_files_to'].' --no-edit');
        chdir($startCwd);
    }
    
    protected function _revertDatabase(){
	$log = $this->_getLogger();
        $startCwd = getcwd();
        $params = $this->_queueObject->getTaskParams();
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        
        exec('sudo tar -zxf '.$params['rollback_db_to'].'');
        
        $pathinfo = pathinfo($params['rollback_db_to']);
        
        $unpackedName = str_replace('.tgz','',$pathinfo['basename']);
        
        $privilegeModel = new Application_Model_DbTable_Privilege($this->db,$this->config);
        $privilegeModel->dropDatabase($this->_userObject->getLogin().'_'.$this->_instanceObject->getDomain());
        $privilegeModel->createDatabase($this->_userObject->getLogin().'_'.$this->_instanceObject->getDomain());
                
        $command = 'sudo mysql -u'.$this->config->resources->db->params->username.' -p'.$this->config->resources->db->params->password.' '.$this->config->magento->instanceprefix.$this->_userObject->getLogin().'_'.$this->_instanceObject->getDomain().' < '.$unpackedName;
        exec($command,$output);
        $log->log('mysql insert ',Zend_Log::DEBUG,var_export($output,true));
       
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
        /* remove revision deployment file */
        
        
        if (file_exists($instanceDir.'/var/deployment/'.$revisionModel->getFilename())){
            unlink('var/deployment/'.$revisionModel->getFilename());
        }
        
        /* remove database file */
        if (file_exists($instanceDir.'/var/db/'.$revisionModel->getDbBeforeRevision())){
            unlink('var/db/'.$revisionModel->getDbBeforeRevision());
        }
        
        /* remove database entry */
        $this->db->delete('revision',
            array('id = '.$revisionModel->getId())
        );
               
        chdir($startCwd);       
    }

}
