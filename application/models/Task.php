<?php

/**
 * TODO: revise all statuses
 */

class Application_Model_Task {

    protected $_queueObject = '';
    protected $_storeObject = '';
    protected $_userObject = '';
    protected $_versionObject = '';
    
    protected $_storeFolder = '';
    protected $config;
    protected $db;
    protected $filePrefix;
    protected $logger;
    
    public function __construct(&$config,&$db) {
        
        $this->config = $config;
        $this->db = $db;
        
        $this->filePrefix = array(
            'CE' => 'magento',
            'EE' => 'enterprise',
            'PE' => 'professional',
        );
    }
     
    /**
     * Sets class's objects we'll be working on
     */
    public function setup(Application_Model_Queue &$queueElement) {
        $this->_queueObject = $queueElement;
        
        //setup other model objects (user/version/store)
        $storeModel = new Application_Model_Store();
        $storeModel->find($queueElement->getStoreId());
        
        $userModel = new Application_Model_User();
        $userModel->find($queueElement->getUserId());
        
        $versionModel = new Application_Model_Version();
        $versionModel->find($storeModel->getVersionId());
        
        $this->_storeObject = $storeModel;
        $this->_userObject = $userModel;
        $this->_versionObject = $versionModel;
        
        $this->_storeFolder = $this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_userObject->getLogin() . '/public_html';

        //setup logggers
        $logger = new Zend_Log();
        $logger->setEventItem('store_id', $this->_storeObject->getId());

        // setup file writer
        $writerFile = new Zend_Log_Writer_Stream(
            APPLICATION_PATH . '/../data/logs/' . $this->_userObject->getLogin() . '_' . $this->_storeObject->getDomain() . '.log'
        );
        $writerFile->addFilter(Zend_Log::DEBUG);

        $logger->addWriter($writerFile);

        // setup formatter to add custom field in mail writer
        $format = '%timestamp% %priorityName% (Store #%store_id%): %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);

        // setup mail writer
        $mail = new Zend_Mail();
        $mail->setFrom($this->config->admin->errorEmail->from->email)
             ->addTo($this->config->admin->errorEmail->to->email);

        $writerMail = new Zend_Log_Writer_Mail($mail);
        $writerMail->setSubjectPrependText($this->config->admin->errorEmail->subject);
        $writerMail->addFilter(Zend_Log::CRIT);
        $writerMail->setFormatter($formatter);

        $logger->addWriter($writerMail);

        // setup db writer
        $columnMapping = array(
            'lvl'  => 'priority',
            'type' => 'priorityName',
            'msg'  => 'message',
            'time' => 'timestamp',
            'store_id' => 'store_id'
        );

        $writerDb = new Zend_Log_Writer_Db($this->db, 'store_log', $columnMapping);
        $writerDb->addFilter(Zend_Log::ERR);

        $logger->addWriter($writerDb);

        $this->logger = $logger;
    }
      
    /**
     * 
     */
    protected function _generateAdminPass() {

        $part1 = substr(
                        str_shuffle(
                                str_repeat('0123456789', 5)
                        )
        , 0, 5);
        $part2 = substr(
                        str_shuffle(
                                str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 7)
                        )
        , 0, 7);
        
        return $part1.$part2;
    }
    
    protected function _clearStoreCache(){
        $this->logger->log('Clearing store cache.', Zend_Log::INFO);

        $cacheFolder = $this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_userObject->getLogin() . '/public_html/'.$this->_storeObject->getDomain().'/var/cache';
        if(file_exists($cacheFolder) && is_dir($cacheFolder)){
            $command = 'sudo rm -rf ' . $cacheFolder . '/*';
            exec($command, $output);
            $message = var_export($output, true);
            $this->logger->log("\n".$command."\n" . $message, Zend_Log::DEBUG);
            unset($output);
        }
    }
    
    
    //leaving it here because we might want to apply it even after extension install
    protected function _applyXmlRpcPatch(){
        $this->logger->log('Applying XML RPC patch.', Zend_Log::INFO);

        if ($this->_versionObject->getVersion() > '1.3.2.3' AND $this->_versionObject->getVersion() < '1.4.1.2'){
            //we're somewhere between 1.3.2.4 and 1.4.1.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Request.php ' . $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Response.php ' . $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Response.php');
            
        } elseif ($this->_versionObject->getVersion() == '1.4.2.0'){
            //1.4.2.0 - thank you captain obvious
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Response.php');
            
        } elseif ($this->_versionObject->getVersion() > '1.4.9.9' AND $this->_versionObject->getVersion() < '1.7.0.2') {
            //we're somewhere between 1.5.0.0 and 1.7.0.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Response.php');
        }
        
    }
       
    protected function _updateStoreStatus($status){
        /* store */
        $storeStatuses = array(
            'ready',
            'removing-magento',
            'error',
            'installing-extension',
            'installing-magento',
            'downloading-magento',
            'committing-revision',
            'deploying-revision',
            'rolling-back-revision',
            'creating-papertrail-user',
            'creating-papertrail-system',
            'removing-papertrail-user',
            'removing-papertrail-system'
        );
        
        if(in_array($status,$storeStatuses) ){
            try {
                $this->_storeObject->setStatus($status);
                $this->db->update('store', array('status' => $status), 'id=' . $this->_queueObject->getId());
            } catch (Exception $e){
                $this->logger->log('Saving store status failed: ' . $e->getMessage(), Zend_Log::EMERG);
            }
        }
    }
}
