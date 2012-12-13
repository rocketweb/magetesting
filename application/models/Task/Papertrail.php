<?php

/**
 * Responsible for service Papertrail API
 *
 * @author Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Model_Task_Papertrail extends Application_Model_Task {
    /**
     * Configuration for Zend_Http
     * @var array 
     */
    protected $_config = array();
    
    /**
     * 
     * @var Zend_Http_Client 
     */
    protected $_client;
    
    /**
     *
     * @var string|null 
     */
    protected $_uri = null;
    
    protected $_url_suffix;

    public function setup(\Application_Model_Queue &$queueElement) {
        parent::setup($queueElement);
        
        $this->_config = array(
            'adapter'     => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => array(CURLOPT_FOLLOWLOCATION => true),
        );
    }
    
    /**
     * Create the connect to Papertrail API
     * 
     * @param string $uri
     * @param string $method
     * @return Application_Model_Task
     */
    protected function _init($uri, $method = 'GET') {
        $this->_client = new Zend_Http_Client($uri, $this->_config);
        
        $this->_client->setMethod($method);
        $this->_client->setAuth(
            $this->config->papertrail->username, 
            $this->config->papertrail->password
        );
        
        return $this;
    }
    
    /**
     * Create and get the URI
     * 
     * @param string $name
     * @return string
     */
    private function _createUri($name) {
        $this->_uri = (string)$this->config->papertrail->url . $name;
        
        return $this->_uri;
    }
    
    /**
     * Send the HTTP request and return an HTTP response object
     * 
     * @return \Zend_Http_Response
     */
    protected function _response() {
        return $this->_client->request();
    }
    
    /**
     * Return an HTTP response body as stdClass object
     * 
     * @return stdClass
     */
    protected function _getDataResponse() {
        return json_decode($this->_response()->getBody());
    }

    /**
     * Get the URI string
     * 
     * @param string $extra Extra parameter to URI
     * @return string
     */
    public function getUri($extra = null) {
        if(is_null($this->_uri)) {
            $name = '';

            if($this->_url_suffix) {
                $name = $this->_url_suffix;
            }
            
            if(!is_null($extra)){
                $name .= '/' . $extra;
            }
            
            $this->_createUri($name);
        }
        
        return $this->_uri;
    }
    
    
}