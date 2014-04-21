<?php

/**
 * @category   RocketWeb
 * @package    RocketWeb_Service
 * @subpackage Papertrail
 * @copyright  Copyright (c) 2012 RocketWeb USA Inc. (http://www.rocketweb.com)
 * @author     Marcin Kazimierczak <marcin@rocketweb.com>
 */
class RocketWeb_Service_Papertrail {
    
    /**
     * Base URI for the REST client
     */
    const URI_BASE = 'https://papertrailapp.com';
    
    /** 
     * Query paths 
     */
    const API_PATH_USER   = '/api/v1/distributors/accounts';
    const API_PATH_SYSTEM = '/api/v1/distributors/systems';

    /**
     * Reference to REST client object
     *
     * @var Zend_Rest_Client
     */
    protected $_restClient = null;

    /**
     * Constructor
     * 
     * @param string $username
     * @param string $password
     */
    public function __construct($username = null, $password = null) {
        $this->_restClient = $this->getRestClient();
        $this->getRestClient()->getUri()->setPort('443');
        
        if(!is_null($username)) {
            $this->setUsername($username);
        }
        
        if(!is_null($password)) {
            $this->setPassword($password);
        }
    }
    
    /**
     * Retrieve username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->_restClient->getUri()->getUsername();
    }

    /**
     * Set username
     *
     * @param  string $value
     * @return Zend_Service_Papertrail
     */
    public function setUsername($value)
    {
        $this->_restClient->getUri()->setUsername($value);
        return $this;
    }
    
    /**
     * Retrieve password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->_restClient->getUri()->getPassword();
    }

    /**
     * Set Password
     *
     * @param  string $value
     * @return Zend_Service_Papertrail
     */
    public function setPassword($value)
    {
        $this->_restClient->getUri()->setPassword($value);
        return $this;
    }

    /**
     * Returns a reference to the REST client, instantiating it if necessary
     *
     * @return Zend_Rest_Client
     */
    public function getRestClient()
    {
        if (null === $this->_restClient) {
            /**
             * @see Zend_Rest_Client
             */
            require_once 'Zend/Rest/Client.php';
            $this->_restClient = new Zend_Rest_Client(self::URI_BASE);
        }

        return $this->_restClient;
    }
    
    /**
     * Create new user in Papertrail service
     * 
     * @param  string|int $id
     * @param  string $name
     * @param  array $user
     * @param  string $plan
     * @return object (properties: log_data_transfer, api_token, plan, log_data_transfer_limit, name, id)
     * @throws RocketWeb_Service_Papertrail_Exception
     * @throws Zend_Service_Exception
     */
    public function createUser($id, $name, array $user, $plan = 'free') {
        $this->getRestClient()->getHttpClient()->resetParameters();
        
        if(!array_key_exists('id', $user)) {
            /**
             * @see RocketWeb_Service_Papertrail_Exception
             */
            require_once 'RocketWeb/Service/Papertrail/Exception.php';
            throw new RocketWeb_Service_Papertrail_Exception("The key 'id' must set in 'user' array!");
        }
        
        if(!array_key_exists('email', $user)) {
            /**
             * @see RocketWeb_Service_Papertrail_Exception
             */
            require_once 'RocketWeb/Service/Papertrail/Exception.php';
            throw new RocketWeb_Service_Papertrail_Exception("The key 'id' must set in 'user' array!");
        }
                
        $options = array(
            'id'   => (string)$id,
            'name' => $name,
            'plan' => $plan,
            'user' => $user
        );
        
        $response = $this->getRestClient()->restPost(self::API_PATH_USER, $options);

        $data = $this->_getDataResponse($response->getBody());
        
        if ($response->isError()) {
            /**
             * @see Zend_Service_Exception
             */
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception(
                isset($data->message) ? $data->message : '' . ' (' .$response->getMessage() . '. Status code: ' . $response->getStatus() . ')', $response->getStatus()
            );
        }
        
        return $data;
    }
    
    /**
     * Remove user in Papertrail
     * 
     * @param  string|int $id
     * @return string The status
     * @throws Zend_Service_Exception
     */
    public function removeUser($id) {
        $this->getRestClient()->getHttpClient()->resetParameters();

        $response = $this->getRestClient()->restDelete(self::API_PATH_USER . '/' . $id);

        $data = $this->_getDataResponse($response->getBody());
        
        if ($response->isError()) {
            /**
             * @see Zend_Service_Exception
             */
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception(
                isset($data->message) ? $data->message : '' . ' (' .$response->getMessage() . '. Status code: ' . $response->getStatus() . ')', $response->getStatus()
            );
        }
        
        return $data->status;
    }
    
    /**
     * Get account usage from Papertrail
     * 
     * @param  string|int $id
     * @return object Std_Object
     * @throws Zend_Service_Exception
     */
    public function getAccountUsage($id) {
        $this->getRestClient()->getHttpClient()->resetParameters();
        
        $response = $this->getRestClient()->restGet(self::API_PATH_USER . '/' . $id);
        
        $data = $this->_getDataResponse($response->getBody());
        
        if ($response->isError()) {
            /**
             * @see Zend_Service_Exception
             */
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception(
                isset($data->message) ? $data->message : '' . ' (' .$response->getMessage() . '. Status code: ' . $response->getStatus() . ')', $response->getStatus()
            );
        }
        
        return $data;
    }
    
    /**
     * Create system in Papertrail service
     * 
     * @param string|int $id
     * @param string $name
     * @param string $accountId
     * @return object (properties: id, name, syslog_hostname, syslog_port)   
     * @throws Zend_Service_Exception
     */
    public function createSystem($id, $name, $accountId) {
        $this->getRestClient()->getHttpClient()->resetParameters();
   
        $options = array(
            'id'         => (string)$id,
            'name'       => $name,
            'account_id' => (string)$accountId
        );
        
        $response = $this->getRestClient()->restPost(self::API_PATH_SYSTEM, $options);
        
        $data = $this->_getDataResponse($response->getBody());

        if ($response->isError()) {
            /**
             * @see Zend_Service_Exception
             */
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception(
                isset($data->message) ? $data->message : '' . ' (' .$response->getMessage() . '. Status code: ' . $response->getStatus() . ')'
            );
        }
        
        return $data;
    }
    
    /**
     * Remove system in Papertrail
     * 
     * @param  string|int $id
     * @return string The status
     * @throws Zend_Service_Exception
     */
    public function removeSystem($id) {
        $this->getRestClient()->getHttpClient()->resetParameters();
        
        $response = $this->getRestClient()->restDelete(self::API_PATH_SYSTEM . '/' . $id);

        $data = $this->_getDataResponse($response->getBody());
        
        if ($response->isError()) {
            /**
             * @see Zend_Service_Exception
             */
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception(
                isset($data->message) ? $data->message : '' . ' (' .$response->getMessage() . '. Status code: ' . $response->getStatus() . ')'
            );
        }
        
        return $data->status;
    }
    
    /**
     * Get system data from Papertrail
     * 
     * @param  string|int $id
     * @return object Data
     * @throws Zend_Service_Exception
     */
    public function getSystemData($id) {
        $this->getRestClient()->getHttpClient()->resetParameters();
        
        $response = $this->getRestClient()->restGet(self::API_PATH_SYSTEM . '/' . $id);
        
        $data = $this->_getDataResponse($response->getBody());
        
        if ($response->isError()) {
            /**
             * @see Zend_Service_Exception
             */
            require_once 'Zend/Service/Exception.php';
            throw new Zend_Service_Exception(
                isset($data->message) ? $data->message : '' . ' (' .$response->getMessage() . '. Status code: ' . $response->getStatus() . ')'
            );
        }
        
        return $data;
    }

    /**
     * Return an REST response body as stdClass object
     * 
     * @return stdClass
     */
    protected function _getDataResponse($body) {
        return json_decode($body);
    }
}