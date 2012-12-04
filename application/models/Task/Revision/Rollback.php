<?php

class Application_Model_Task_Revision_Rollback 
extends Application_Model_Task_Revision 
implements Application_Model_Task_Interface {

    private $db;
    private $config;
    
    /* Prevents from running contructor of Application_Model_Task */
    public function __construct(){
        
        $this->db = $this->_getDb();
        $this->config = $this->_getConfig();
    }
    
    public function process(Application_Model_Queue &$queueElement = null) {
        
        $this->_rollback();
        
    }

    protected function _checkCredentials() {
        
    }

    protected function _rollback() {
        
        $startCwd = getcwd();
        
        chdir($this->_instanceFolder.'/'.$this->_instanceObject->getDomain());
        
        $params = $this->_queueObject->getTaskParams();
       
        //revert files using rollback_files_to param
        exec('git revert '.$params['rollback_files_to']);
        
        //restore database backup using rollback_db_to param     
        exec('tar -zxf '.$params['rollback_database_to']);
        
        $unpackedName = str_replace('.tgz','',$params['rollback_database_to']);
        $command = 'sudo mysql -u'.$this->config->resources->db->params->username.' -p'.$this->config->resources->db->params->password.' '.$this->config->magento->instanceprefix.$this->_userObject->getLogin().'_'.$this->_instanceObject->getDomain().' < '.$unpackedName;
        exec($command);
       
        //finish process
        chdir($startCwd);
             
        //remove extension id from instance_extension if there was extension in this commit.
        if ($this->_queueObject->getExtensionId()!=0){
            $this->db->delete('instance_extension',array(
                'instance_id' => $this->_queueObject->getInstanceId(),
                'extension_id' => $this->_queueObject->getExtensionId()
                )
            );
        }
        
        //lower revision_counter of instance
        $this->_instanceObject->setRevisionCount($this->_instanceObject->getRevisionCount()-1)->save();
        
        $this->_updateStatus('ready');
    }

}
