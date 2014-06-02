<?php

class Application_Model_Task_Magento_Reindex
extends Application_Model_Task_Magento 
implements Application_Model_Task_Interface {

    public function setup(Application_Model_Queue $queueElement){
        parent::setup($queueElement);
    }
    
    public function process(Application_Model_Queue $queueElement = null) {
        $startCwd = getcwd();
        $this->_updateStoreStatus('reindexing-magento');

        $this->logger->log('Started reindexing magento.', Zend_Log::INFO);

        // truncate cl tables
        $dbName = $this->_userObject->getLogin().'_'.$this->_storeObject->getDomain();      
        $privilegeModel = new Application_Model_DbTable_Privilege($this->dbPrivileged,$this->config);
        if($privilegeModel->checkIfDatabaseExists($dbName)){
            $result = $privilegeModel->cleanIndexTables($dbName);
        }

        if ($result) {
            $this->logger->log('Truncated *_cl tables properly.', Zend_Log::INFO);
        } else {
            $this->logger->log('Not truncated *_cl tables properly.', Zend_Log::INFO);
        }

        chdir($this->_storeFolder . '/' . $this->_storeObject->getDomain());

        $command = $this->cli()->createQuery(
            'su '.$this->config->magento->userprefix . $this->_userObject->getLogin().' -c "timeout 10m /usr/bin/php -f shell/indexer.php -- --reindexall"'
        );

        $output = $command->call()->getLastOutput();

        $lastOutput = end($output); 

        if (strpos($lastOutput, 'Product Attributes') === false) {
            $msg = 'Reached time limit and killed.';
        } else {
            $msg = 'Success';
        }

        $message = var_export($output, true);
        $this->logger->log("\n" . $command. "\n" . $message, Zend_Log::DEBUG);

        $this->logger->log('Finished reindexing magento ('.$msg.').', Zend_Log::INFO);
    }
}
