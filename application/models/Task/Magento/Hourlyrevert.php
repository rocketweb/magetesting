<?php

class Application_Model_Task_Magento_HourlyRevert
extends Application_Model_Task_Magento 
implements Application_Model_Task_Interface {

    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
    }
    
    public function process(Application_Model_Queue $queueElement = null) {
        $startCwd = getcwd();

        $this->_updateStoreStatus('hourly-reverting-magento');
        
        //drop database
        
        $dbName = $this->_userObject->getLogin().'_'.$this->_storeObject->getDomain();
        
        //create mainteance flag
        $lockfile = $this->config->magento->systemHomeFolder.'/'.$this->config->magento->userprefix.$this->_userObject->getLogin().'/public_html/'.$this->_storeObject->getDomain().'/db_revert_in_progress.lock';
        $command = 'touch '.$lockfile;
 		exec($command);
        
        $privilegeModel = new Application_Model_DbTable_Privilege($this->db,$this->config);
        $privilegeModel->dropDatabase($dbName);

        //create database again
        $privilegeModel->createDatabase($dbName);

		//get revision to know which file we revert to
		$revisionModel = new Application_Model_Revision();
		$revision = $revisionModel->getLastForStore($this->_storeObject->getId());

    	//insert db dump from tar.gz one-liner
        exec('tar xfzO '.$this->config->magento->systemHomeFolder.'/'.$this->config->magento->userprefix.$this->_userObject->getLogin().'/public_html/'.$this->_storeObject->getDomain().'/var/db/'.$revision->getDbBeforeRevision().' | mysql -u'.$this->config->resources->db->params->username.' -p'.$this->config->resources->db->params->password.' '.$this->config->magento->storeprefix.$dbName.'');
        
        //remove mainteance flag
        $command = 'rm '.$lockfile;
 		exec($command);
    }   
}
