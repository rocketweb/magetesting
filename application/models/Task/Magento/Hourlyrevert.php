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
        
        $privilegeModel = new Application_Model_DbTable_Privilege($this->db,$this->config);
        $privilegeModel->dropDatabase($dbName);

        //create database again
        $privilegeModel->createDatabase($dbName);

		//get revision to know which file we revert to
		$select = new Zend_Db_Select($this->db);
        $sql = $select
        ->from('revision')
        ->where('store_id = ?', $this->_storeObject->getId())
        ->order(array('id desc'))
        ->limit(1);
        $revision = $db->fetchRow($sql);

    	//insert db dump from tar.gz one-liner
        exec('tar xfzO '.$this->config->magento->systemHomeFolder.'/'.$config->magento->userprefix.$this->_userObject->getLogin().'/public_html/'.$this->_storeObject->getDomain().'/var/db/'.$revision['db_before_revision'].' | mysql -u'.$this->config->resources->db->params->username.' -p'.$this->config->resources->db->params->password.' '.$this->config->magento->storeprefix.$dbName.'');

    }

   
    
}
