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
        
        //create mainteance flag
        $lockfile = $this->config->magento->systemHomeFolder.'/'.$this->config->magento->userprefix.$this->_userObject->getLogin().'/public_html/'.$this->_storeObject->getDomain().'/maintenance.flag';
        $command = 'touch '.$lockfile;
        $file = $this->cli('file');
        $file->create($lockfile, $file::TYPE_FILE)->call();

        //drop database
        $dbName = $this->_userObject->getLogin().'_'.$this->_storeObject->getDomain();      
        $privilegeModel = new Application_Model_DbTable_Privilege($this->dbPrivileged,$this->config);
        if($privilegeModel->checkIfDatabaseExists($dbName)){
            $privilegeModel->dropDatabase($dbName);
        }

        //create database again
        $privilegeModel->createDatabase($dbName);

        //get revision to know which file we revert to
        $revisionModel = new Application_Model_Revision();
        $revision = $revisionModel->getLastForStore($this->_storeObject->getId());

        $mysql = $this->cli('mysql')->connect(
            $this->config->resources->db->params->username,
            $this->config->resources->db->params->password,
            $this->config->magento->storeprefix.$dbName
        );
        //insert db dump from tar.gz one-liner
        $this->cli('tar')->unpack(
            $this->config->magento->systemHomeFolder.'/'.$this->config->magento->userprefix.$this->_userObject->getLogin().'/public_html/'.$this->_storeObject->getDomain().'/var/db/'.$revision->getDbBeforeRevision(),
            '',
            false
        )->redirectToOutput()
         ->pipe($mysql)->call();

        //remove mainteance flag
        $file->clear()->remove($lockfile)->call();
    }
}
