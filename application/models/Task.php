<?php

/**
 * TODO: revise all statuses
 */

class Application_Model_Task {

    protected $_queueObject = '';
    protected $_instanceObject = '';
    protected $_userObject = '';
    protected $_versionObject = '';
    protected $_instanceFolder = '';
       
    /* Needs to be private so tasks could use it */
    private static $config;
    private static $db;
    private static $filePrefix;
    private static $logger;
    
    public function __construct($config,$db) {
        self::$config = $config;
        self::$db = $db;
        
        self::$filePrefix = array(
            'CE' => 'magento',
            'EE' => 'enterprise',
            'PE' => 'professional',
        );
    }
     
    /* Runs specific method depending on task type */
    public function process(Application_Model_Queue &$queueElement){
               
        $filter = new Zend_Filter_Word_CamelCaseToUnderscore();
        $classSuffix = $filter->filter($queueElement->getTask());
        
        $className = __CLASS__ . '_'.$classSuffix; 
        
        $customTaskModel = new $className();       
        $customTaskModel->setup($queueElement);
        $customTaskModel->process();
        
        /* only remove database row when no error was registered */
        if ($queueElement->getStatus()=='ready'){
            self::$db->update('queue', array('parent_id' => '0'), 'parent_id = ' . $queueElement->getId());
            
            self::$db->delete('queue', array('id=' . $queueElement->getId()));
        }

    }
    /**
     * Sets class's object we'll be working on
     * TODO: if possible, create method to get all info with one sql
     */
    public function setup(Application_Model_Queue &$queueElement) {
        $this->_queueObject = $queueElement;
        
        //setup other model objects (user/version/instance)
        $instanceModel = new Application_Model_Instance();
        $instanceModel->find($queueElement->getInstanceId());
        
        $userModel = new Application_Model_User();
        $userModel->find($queueElement->getUserId());
        
        $versionModel = new Application_Model_Version();
        $versionModel->find($instanceModel->getVersionId());
        
        $this->_instanceObject = $instanceModel;
        $this->_userObject = $userModel;
        $this->_versionObject = $versionModel;
        
        $this->_instanceFolder = self::$config->magento->systemHomeFolder . '/' . self::$config->magento->userprefix . $this->_userObject->getLogin() . '/public_html';

        //setup logggers
        $logger = new Zend_Log();
        $logger->setEventItem('store_id', $this->_instanceObject->getId());

        // setup file writer
        $writerFile = new Zend_Log_Writer_Stream(
            APPLICATION_PATH . '/../data/logs/' . $this->_userObject->getLogin() . '_' . $this->_instanceObject->getDomain() . '.log'
        );
        $writerFile->addFilter(Zend_Log::DEBUG);

        $logger->addWriter($writerFile);

        // setup mail writer
        $mail = new Zend_Mail();
        $mail->setFrom(self::$config->admin->errorEmail->from->email)
             ->addTo(self::$config->admin->errorEmail->to->email);

        $writerMail = new Zend_Log_Writer_Mail($mail);
        $writerMail->setSubjectPrependText(self::$config->admin->errorEmail->subject);
        $writerMail->addFilter(Zend_Log::CRIT);

        $logger->addWriter($writerMail);

        // setup db writer
        $columnMapping = array(
            'lvl'  => 'priority',
            'type' => 'priorityName',
            'msg'  => 'message',
            'time' => 'timestamp',
            'store_id' => 'store_id'
        );

        $writerDb = new Zend_Log_Writer_Db(self::$db, 'store_log', $columnMapping);
        $writerDb->addFilter(Zend_Log::ERR);

        $logger->addWriter($writerDb);

        self::$logger = $logger;
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
    
    protected function _clearInstanceCache(){
        exec('sudo rm -R '.self::$config->magento->systemHomeFolder . '/' . self::$config->magento->userprefix . $this->_userObject->getLogin() . '/public_html/'.$this->_instanceObject->getDomain().'/var/cache/*');
    }
    
    
    //leaving it here because we might want to apply it even after extension install
    protected function _applyXmlRpcPatch(){
        
        if ($this->_versionObject->getVersion() > '1.3.2.3' AND $this->_versionObject->getVersion() < '1.4.1.2'){
            //we're somewhere between 1.3.2.4 and 1.4.1.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Request.php ' . $this->_instanceFolder . '/' . $this->_instanceObject->getDomain() . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1400_1411/Response.php ' . $this->_instanceFolder . '/' . $this->_instanceObject->getDomain() . '/lib/Zend/XmlRpc/Response.php');
            
        } elseif ($this->_versionObject->getVersion() == '1.4.2.0'){
            //1.4.2.0 - thank you captain obvious
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $this->_instanceFolder . '/' . $this->_instanceObject->getDomain() . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $this->_instanceFolder . '/' . $this->_instanceObject->getDomain() . '/lib/Zend/XmlRpc/Response.php');
            
        } elseif ($this->_versionObject->getVersion() > '1.4.9.9' AND $this->_versionObject->getVersion() < '1.7.0.2') {
            //we're somewhere between 1.5.0.0 and 1.7.0.1
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Request.php ' . $this->_instanceFolder . '/' . $this->_instanceObject->getDomain() . '/lib/Zend/XmlRpc/Request.php');
            exec('sudo cp ' . APPLICATION_PATH . '/../data/fixes/1500_1701/Response.php ' . $this->_instanceFolder . '/' . $this->_instanceObject->getDomain() . '/lib/Zend/XmlRpc/Response.php');
        }
        
    }
       
    protected function _updateStatus($status,$errorMessage=null){
        
        /**
         * There are three status groups: 
         * - instance (store) statuses - used on user/dashboard
         * - queue statuses - used by worker.php
         * - instance_extension statuses (not yet implemented) - used on queue/extensions
         */        
        
        /* INSTANCE */
        $instanceStatuses = array(   
            'ready',
            'removing-magento',
            'error',
            'installing-extension',
            'installing-magento',
            'committing-revision',
            'deploying-revision',
            'rolling-back-revision',
            'creating-papertrail-user',
            'creating-papertrail-system',
        );

        $queueStatuses = array(
            'pending' => 'pending',
            'processing' => 'processing',
            'ready' => 'ready',
            
            /* to support status update on items */
            'removing-magento' => 'processing',
            'installing-extension' => 'processing',
            'installing-magento' => 'processing',
            'committing-revision' => 'processing',
            'deploying-revision' => 'processing',
            'rolling-back-revision' => 'processing',
            'creating-papertrail-user' => 'processing',
            'creating-papertrail-system' => 'processing',
        );
                       
        /* update instance if status is supported */
        if(in_array($status,$instanceStatuses) ){
            try {
            
                $this->_instanceObject->setStatus($status);
                self::$db->update('instance', array('status' => $status), 'id=' . $this->_instanceObject->getId());

            } catch (Exception $e){
                self::$logger->log('Saving store status failed: ' . $e->getMessage(), Zend_Log::EMERG);
            }
        }
        
        /* update queue if status is supported */
        if(in_array($status,$queueStatuses) ){
            try {
                $this->_queueObject->setStatus($queueStatuses[$status]);
                self::$db->update('queue', array('status' => $queueStatuses[$status]), 'id=' . $this->_queueObject->getId());
                
            } catch (Exception $e){
                self::$logger->log('Saving queue status failed: ' . $e->getMessage(), Zend_Log::EMERG);
            }
        }
        
        
        if ($errorMessage!=null){
            self::$db->update('instance', array('error_message' => $errorMessage), 'id=' . $this->_instanceObject->getId());
        
            //TODO: send email to admin?
        }
        self::$logger->log($errorMessage, Zend_Log::DEBUG);
                
        return true;
    }
    
    /**
     * Returns config Object
     * @return Zend_Config
     */
    protected function _getConfig(){
        return self::$config;
    }
    
    protected function _getDb(){
        return self::$db;
    }
    
    protected function _getFilePrefix(){
        return self::$filePrefix;
    }
    
    protected function _getLogger(){
        return self::$logger;
    }
    
    protected function _getInstanceFolder(){
        if(isset($this->_instanceFolder)) {
            return $this->_instanceFolder;
        }
        return $this->_instanceFolder = self::$config->magento->systemHomeFolder . '/' . self::$config->magento->userprefix . $this->_userObject->getLogin() . '/public_html';
    }

//move clearinstancecache here
}
