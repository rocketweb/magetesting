<?php

/**
 * TODO: revise all statuses
 */

class Application_Model_Task {

    /**
     *  @var Application_Model_Queue 
     */
    protected $_queueObject = '';
    
    /**
     *  @var Application_Model_Store 
     */
    protected $_storeObject = '';
    
    /** 
     * @var Application_Model_User 
     */
    protected $_userObject = '';
    
    /**
     *  @var Application_Model_Version 
     */
    protected $_versionObject = '';
    
    /**
     *  @var Application_Model_Server 
     */
    protected $_serverObject ='';
    
    protected $_storeFolder = '';
    protected $config;
    protected $db;
    protected $filePrefix;

    protected $_cli;
    protected $_fileKit;
    /**
     *
     * @var Zend_Log
     */
    protected $logger;
    protected $revisionLogger;
    
    public function __construct(&$config,&$db) {
        
        $this->config = $config;
        $this->db = $db;
        $this->filePrefix = array(
            'CE' => 'magento',
            'EE' => 'enterprise',
            'PE' => 'professional',
        );

        $this->dbPrivileged = Zend_Db::factory('PDO_MYSQL',
            $config->dbPrivileged->params
        );

        $this->_cli = new RocketWeb_Cli();

        $this->_fileKit = $this->cli('file');
    }

    public function cli($kit = '')
    {
        if($kit) {
            return $this->_cli->kit($kit);
        }
        return $this->_cli;
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
        
        $serverModel = new Application_Model_Server();
        $serverModel->find($storeModel->getServerId());
        
        $this->_storeObject = $storeModel;
        $this->_userObject = $userModel;
        $this->_versionObject = $versionModel;
        $this->_serverObject = $serverModel;
        
        $this->_storeFolder = $this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_userObject->getLogin() . '/public_html';

        //setup logggers
        $logger = new Zend_Log();
        $logger->setEventItem('store_id', $this->_storeObject->getId());

        // setup file writer
        $writerFile = new Zend_Log_Writer_Stream(
            APPLICATION_PATH . '/../data/logs/' . $this->_userObject->getLogin() . '_' . $this->_storeObject->getId() . '_' . $this->_storeObject->getDomain() . '.log'
        );
        $writerFile->addFilter(Zend_Log::DEBUG);

        $logger->addWriter($writerFile);

        // setup formatter to add custom field in mail writer
        $format = '%timestamp% %priorityName% (Store #%store_id%): %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);

        // setup mail writer
        $mail = new Zend_Mail();
        $mail->setFrom($this->config->admin->errorEmail->from->email);
        
        $email = $this->config->admin->errorEmail->to->email;
        
        /* $email is Zend_Config Object */
        $emails = $email->toArray();
        
        if (!is_array($emails)){
            $emails = array($emails);
        }
        
        if($emails) {
            $mail->addTo(array_shift($emails));
        }
        
        if($emails) {
            foreach($emails as $ccEmail) {
                $mail->addCc($ccEmail);
            }
        }

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
        $fileKit = $this->_fileKit;

        $this->_cli->setLogger($logger);
        $this->_cli->enableLogging(
            (bool) $this->config->execWrapper->logEnabled
        );

        try{
            $revisionLogPath = '/home/'.$this->config->magento->userprefix . $this->_userObject->getLogin() . 
                '/public_html/'.$this->_storeObject->getDomain().'/var/log/';

            if (!file_exists($revisionLogPath)){
                $fileKit->clear()->create($revisionLogPath, $fileKit::TYPE_DIR)->call();
            }

            $revisionLogFilePath = $revisionLogPath.'revision.log';

            if (!file_exists($revisionLogFilePath)){
                $fileKit->clear()->create($revisionLogFilePath, $fileKit::TYPE_FILE)->call();
                $fileKit->clear()->fileMode($revisionLogFilePath, 777)->call();
            }

            $formatter = new Zend_Log_Formatter_Simple('%message%' . PHP_EOL);

            $writerFile = new Zend_Log_Writer_Stream($revisionLogFilePath);
            $writerFile->setFormatter($formatter);
            $revisionLogger = new Zend_Log($writerFile);
            $this->revisionLogger = $revisionLogger;
        } catch (Zend_Log_Exception $e){
            $this->logger->log('Creating writed for revisions failed : ' . $e->getMessage(), Zend_Log::EMERG);
        }

    }
    
    /**
     * 
     */
    protected function _generateAdminPass() {
        return Integration_Generator::generateRandomString(7, 5, false);
    }

    protected function _getIgnoredConflicts()
    {
        $storeId = $this->_storeObject->getId();
        $storeConflictModel = new Application_Model_StoreConflict();

        $currentConflicts = $storeConflictModel->fetchUserStoreConflicts(
            $this->_userObject->getId(),
            $storeId
        );

        $ignoreConflicts = array();

        foreach($currentConflicts[$storeId]['ignore'] as $ignore){
            $ignoreConflicts[] = md5($ignore['type'].$ignore['class'].$ignore['rewrites'].$ignore['loaded']);
        }
        return $ignoreConflicts;
    }

    protected function _checkForConflicts($getOldIgnoreConflicts = false)
    {
        $ignoreConflicts = array();
        if($getOldIgnoreConflicts){
            $ignoreConflicts = $this->_getIgnoredConflicts();
        }

        $storeId = $this->_storeObject->getId();
        $storeConflictModel = new Application_Model_StoreConflict();

        $storeConflictModel->removeStoreConflicts($storeId);

        $conflicts = $storeConflictModel->getConflicts($this->_storeFolder . '/' . $this->_storeObject->getDomain(), $this->_userObject->getLogin());

        foreach($conflicts as $c){
            $conflict = new Application_Model_StoreConflict();
            $conflict->setOptions($c);
            $conflict->setStoreId($storeId);
            $hash = md5($conflict->getType().$conflict->getClass().$conflict->getRewrites().$conflict->getLoaded());
            $ignore = in_array($hash,$ignoreConflicts);
            $conflict->setIgnore($ignore);
            $conflict->save();
        }
    }
    
    protected function _clearStoreCache(){
        $this->logger->log('Clearing store cache.', Zend_Log::INFO);

        $cacheFolder = $this->config->magento->systemHomeFolder . '/' . $this->config->magento->userprefix . $this->_userObject->getLogin() . '/public_html/'.$this->_storeObject->getDomain().'/var/cache';
        if(file_exists($cacheFolder) && is_dir($cacheFolder)){
            $this->_fileKit->clear()->remove($cacheFolder)->append('/*', null, false);
            $message = var_export($this->_fileKit->call()->getLastOutput(), true);
            $this->logger->log("\n".$this->_fileKit->toString()."\n" . $message, Zend_Log::DEBUG);
        } else {
            $this->logger->log('Store cache has not been cleared, cache directory does not exist.', Zend_Log::INFO);
        }
    }
    
    
    //leaving it here because we might want to apply it even after extension install
    protected function _applyXmlRpcPatch(){
        $this->logger->log('Applying XML RPC patch.', Zend_Log::INFO);

        $file = $this->_fileKit;

        if ($this->_versionObject->getVersion() > '1.3.2.3' AND $this->_versionObject->getVersion() < '1.4.1.2'){
            //we're somewhere between 1.3.2.4 and 1.4.1.1
            $file->clear()->copy(
                APPLICATION_PATH . '/../data/fixes/1400_1411/Request.php',
                $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Request.php'
            )->call();
            $file->clear()->copy(
                APPLICATION_PATH . '/../data/fixes/1400_1411/Response.php',
                $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Request.php'
            )->call();
            
        } elseif(
            $this->_versionObject->getVersion() == '1.4.2.0'
            || ($this->_versionObject->getVersion() > '1.4.9.9' AND $this->_versionObject->getVersion() < '1.7.0.2')
        ){
            $file->clear()->copy(
                APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php',
                $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Request.php'
            )->call();
            $file->clear()->copy(
                APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php',
                $this->_storeFolder . '/' . $this->_storeObject->getDomain() . '/lib/Zend/XmlRpc/Request.php'
            )->call();
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
            'removing-papertrail-system',
            'extension-conflict'
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
