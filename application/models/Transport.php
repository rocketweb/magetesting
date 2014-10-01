<?php

class Application_Model_Transport {
    
    /* In Bytes */
    protected $_sqlFileLimit = ''; 
    protected $_storeFileLimit = ''; 

    protected $_wgetTimeout = 300; // default 5 minutes
    protected $_wgetTries = 3; // default 3 tries
    protected $_protocol = '';
    protected $_host = '';
    protected $_user = '';
    protected $_pass = '';
    protected $_errorMessage = '';
    protected $_cli;

    protected $_storeObject ='';
    protected $logger = NULL;

    public function __construct()
    {
        $this->_cli = new RocketWeb_Cli();
    }

    public function cli($kit = '')
    {
        if($kit) {
            return $this->_cli->kit($kit);
        }
        return $this->_cli;
    }

    public function setup(Application_Model_Store &$store, $logger = NULL,$config = NULL, $cli = NULL){
        $this->setConnection($store);

        $this->_sqlFileLimit = $config->magento->sqlDumpByteLimit;
        $this->_storeFileLimit = $config->magento->storeDumpByteLimit;

        if(isset($config->wget) && isset($config->wget->timeout)) {
            $this->_wgetTimeout = $config->wget->timeout;
        }
        if(isset($config->wget) && isset($config->wget->tries)) {
            $this->_wgetTries = $config->wget->tries;
        }

        if ($logger instanceof Zend_Log) {
            $this->logger = $logger;
        }
        if(!$cli instanceof RocketWeb_Cli) {
            $cli = new RocketWeb_Cli();
        }
        $this->_cli = $cli;
    } 
    
    /**
     * Sets protocol, if supported
     * @param type $value
     * @return boolean
     */
    protected function setProtocol($value){
        $supportedProtocols = array(
            'ftp',
            'ssh'
        );
        
        if (!in_array($value, $supportedProtocols)){
            return false;
        }
        return true;
    }
    
    /**
     * Checks if hostname provided matches format requirements
     * @param type $value
     * @return boolean
     */
    protected function setHost($value){
        
        $hostname = new Zend_Validate_Hostname();
        $ip = new Zend_Validate_Ip();
        if(!$hostname->isValid($value) && !$ip->isValid($value)){
            return false;
        }
        
        $this->_host = $value;
        return true;
    }
    
    /**
     * @param type $value
     * @return boolean
     */
    protected function setUser($value){
        $this->_user = $value;
        return true;
    }
    
    /**
     * Checks if password matches requirements
     * currently always return true
     * @param string $value
     * @return boolean
     */
    protected function setPass($value){
        $this->_pass = $value;
        return true;
    }
   
    public function setConnection(Application_Model_Store &$store){
        
        if ($this->setProtocol($store->getCustomProtocol())){
            if ($this->setHost($store->getCustomHost())){
                if ($this->setUser($store->getCustomLogin())){
                    if ($this->setPass($store->getCustomPass())){
                        
                        return true;
                        
                    } else {
                        $this->_errorMessage = 'Password is incorrect';
                        throw new Application_Model_Transport_Exception($this->_errorMessage);
                        return false;
                    }
                } else {
                    $this->_errorMessage = 'Username has to be alphanumeric';
                    throw new Application_Model_Transport_Exception($this->_errorMessage);
                    return false;
                }
            } else {
                $this->_errorMessage = 'Hostname is invalid';
                throw new Application_Model_Transport_Exception($this->_errorMessage);
                return false;
            }
        } else {
            $this->_errorMessage = 'Protocol not supported';
            throw new Application_Model_Transport_Exception($this->_errorMessage);
            //var_dump($this->_errorMessage);
            return false;
        }
    }
    
    /* return transport model for specified protocol */
    public static function factory(Application_Model_Store &$store, $logger = NULL,$config = NULL, $cli = NULL){
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $classSuffix = $filter->filter($store->getCustomProtocol());
        $className = 'Application_Model_Transport_' . $classSuffix;
        
        if (class_exists($className)){
            $customTransportModel = new $className();
            $customTransportModel->setup($store, $logger, $config, $cli);
            return $customTransportModel;
        }      
        throw new Application_Model_Transport_Exception('model transport '.$className.' doesnt exist');
        return false;
    }
       
    public function changePassOnStars($pass, $text) {
        return str_replace($pass, '********', $text);
    }
}
