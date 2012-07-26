<?php

class Application_Model_Queue {

    protected $_id;

    protected $_edition;

    protected $_status;

    protected $_version_id;

    protected $_sample_data;

    protected $_user_id;

    protected $_domain;

    protected $_instance_name;

    protected $_backend_password;
    
    protected $_type;
    protected $_custom_protocol;
    protected $_custom_host;
    protected $_custom_remote_path;
    protected $_custom_login;
    protected $_custom_pass;
    protected $_custom_sql;
    

    protected $_mapper;

    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options)
    {
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    public function setId($id)
    {
        $this->_id = (int)$id;
        return $this;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setEdition($edition)
    {
        $this->_edition = $edition;
        return $this;
    }

    public function getEdition()
    {
        return $this->_edition;
    }

    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function setVersionId($version_id)
    {
        $this->_version_id = $version_id;
        return $this;
    }

    public function getVersionId()
    {
        return $this->_version_id;
    }

    public function setUserId($user_id)
    {
        $this->_user_id = $user_id;
        return $this;
    }

    public function getUserId()
    {
        return $this->_user_id;
    }

    public function setSampleData($sample_data)
    {
        $this->_sample_data = $sample_data;
        return $this;
    }
    
    public function getSampleData()
    {
        return $this->_sample_data;
    }

    public function setDomain($domain)
    {
        $this->_domain = $domain;
        return $this;
    }

    public function getDomain()
    {
        return $this->_domain;
    }

    public function setInstanceName($name)
    {
        $this->_instance_name = $name;
        return $this;
    }

    public function getInstanceName()
    {
        return $this->_instance_name;
    }

    public function setBackendPassword($password)
    {
        $this->_backend_password = $password;
        return $this;
    }

    public function getBackendPassword()
    {
        return $this->_backend_password;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_QueueMapper());
        }
        return $this->_mapper;
    }

    public function save()
    {
        $this->getMapper()->save($this);
    }

    public function delete($id)
    {
        $this->getMapper()->delete($id);
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }

    public function fetchAll()
    {
        return $this->getMapper()->fetchAll();
    }

    public function __toArray()
    {
        return array(
                'id'               => $this->getId(),
                'edition'          => $this->getEdition(),
                'status'           => $this->getStatus(),
                'version_id'       => $this->getVersionId(),
                'user_id'          => $this->getUserId(),
                'domain'           => $this->getDomain(),
                'instance_name'    => $this->getInstanceName(),
                'backend_password' => $this->getBackendPassword(),
                'sample_data'      => $this->getSampleData(),
                'custom_protocol'  => $this->getCustomProtocol(),
                'custom_host'      => $this->getCustomHost(),
                'custom_remote_path' => $this->getCustomRemotePath(),
                'custom_login'     =>  $this->getCustomLogin(),
                'custom_pass'      => $this->getCustomPass(),
                'custom_sql'       => $this->getCustomSql(),
                'type'       => $this->getType(),
        );
    }

    public function getAll()
    {
        return $this->getMapper()->getAll();
    }

    public function getAllForUser( $user_id )
    {
        return $this->getMapper()->getAllForUser( $user_id );
    }

    public function countUserInstances( $user_id )
    {
        return $this->getMapper()->countUserInstances( $user_id );
    }

    public function changeStatusToClose($byAdmin = false)
    {
        $this->getMapper()->changeStatusToClose($this, $byAdmin);
        return $this;
    }
    
    public function getWholeQueue()
    {
        return $this->getMapper()->getWholeQueue();
    }

    public function getPendingItems($timeExecution)
    {
        return $this->getMapper()->getPendingItems($timeExecution);
    }
    
    public function getCustomProtocol(){
      return $this->_custom_protocol;
    }
    
    public function setCustomProtocol($value){
      $this->_custom_protocol = $value;
      return $this;
    }
    
    public function getCustomHost(){
      return $this->_custom_host;
    }
    
    public function setCustomHost($value){
      $this->_custom_host = $value;
      return $this;
    }
    
    public function getCustomRemotePath(){
      return $this->_custom_remote_path;
    }
    
    public function setCustomRemotePath($value){
      $this->_custom_remote_path = $value;
      return $this;
    }
    
    public function getCustomLogin(){
      return $this->_custom_login;
    }
    
    public function setCustomLogin($value){
      $this->_custom_login = $value;
      return $this;
    }
    
    public function getCustomPass(){
      return $this->_custom_pass;
    }
    
    public function setCustomPass($value){
      $this->_custom_pass = $value;
      return $this;
    }
    
    public function getCustomSql(){
      return $this->_custom_sql;
    }
    
    public function setCustomSql($value){
      $this->_custom_sql = $value;
      return $this;
    }
    
    public function getType(){
      return $this->_type;
    }
    
    public function setType($value){
      $this->_type = $value;
      return $this;
    }
}
