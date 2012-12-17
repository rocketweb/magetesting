<?php

class Application_Model_Transport {
    
    /* In Bytes */
    protected $_sqlFileLimit = '60000000'; // move to config and set during setup?
    
    protected $_protocol = '';
    protected $_host = '';
    protected $_user = '';
    protected $_pass = '';
    protected $_errorMessage = '';
    
    public function setup(Application_Model_Store &$store){
        $this->setConnection($store);
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
     * Checks if username is in valid alphanumeric format
     * @param type $value
     * @return boolean
     */
    protected function setUser($value){
        
        $regex = new Zend_Validate_Alnum();
        if (!$regex->isValid($value)){
            return false;
        }
        
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
                        
                        return false;
                    }
                } else {
                    $this->_errorMessage = 'Username has to be alphanumeric';
                    var_dump($this->_errorMessage);
                    return false;
                }
            } else {
                $this->_errorMessage = 'Hostname is invalid';
                var_dump($this->_errorMessage);
                return false;
            }
        } else {
            $this->_errorMessage = 'Protocol not supported';
            var_dump($this->_errorMessage);
            return false;
        }
    }
    
    /* return transport model for specified protocol */
    public static function factory(Application_Model_Store &$store){
        
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $classSuffix = $filter->filter($store->getCustomProtocol());
        $className = 'Application_Model_Transport_' . $classSuffix;
        
        if (class_exists($className)){
            $customTransportModel = new $className();
            $customTransportModel->setup($store);
            return $customTransportModel;
        }
        var_dump('model transport '.$className.' doesnt exist');
        return false;
    }
       
}
