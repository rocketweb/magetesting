<?php

class Application_Model_Transport {
    
    /* In Bytes */
    protected $_sqlFileLimit = '60000000'; // move to config and set during setup?
    
    protected $_protocol = '';
    protected $_host = '';
    protected $_user = '';
    protected $_pass = '';
    protected $_errorMessage = '';
    
    public function setup(Application_Model_Instance &$instance){
        $this->setConnection($instance);
    } 
    
    /**
     * Sets protocol, if supported
     * @param type $value
     * @return boolean
     */
    protected function setProtocol($value){
        $supportedProtocols = array(
            'ftp'
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
        if(!$hostname->isValid($value)){
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
   
    public function setConnection(Application_Model_Instance &$instance){
        
        if ($this->setProtocol($instance->getCustomProtocol())){
            if ($this->setHost($instance->getCustomHost())){
                if ($this->setUser($instance->getCustomLogin())){
                    if ($this->setPass($instance->getCustomPass())){
                        
                        return true;
                        
                    } else {
                        
                        return false;
                    }
                } else {
                    $this->_errorMessage = 'Username has to be alphanumeric';
                    return false;
                }
            } else {
                $this->_errorMessage = 'Hostname is invalid';
                return false;
            }
        } else {
            $this->_errorMessage = 'Protocol not supported';
            return false;
        }
    }
    
    /* return transport model for specified protocol */
    public static function factory(Application_Model_Instance &$instance){
        
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $classSuffix = $filter->filter($instance->getCustomProtocol());
        $className = 'Application_Model_Transport_' . $classSuffix;
        
        if (class_exists($className)){
            $customTransportModel = new $className();
            $customTransportModel->setup($instance);
            return $customTransportModel;
        }
        return false;
    }
       
}
