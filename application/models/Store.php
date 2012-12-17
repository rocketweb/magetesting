<?php

class Application_Model_Store {

    protected $_id;

    protected $_edition;

    protected $_status;

    protected $_version_id;

    protected $_sample_data;

    protected $_user_id;
    
    protected $_server_id;

    protected $_domain;

    protected $_store_name;

    protected $_description;

    protected $_backend_password;
    
    protected $_type;
    protected $_custom_protocol;
    protected $_custom_host;
    protected $_custom_remote_path;
    protected $_custom_login;
    protected $_custom_pass;
    protected $_custom_sql;
    
    protected $_error_message;
    
    protected $_revision_count;
    
    protected $_mapper;
    
    protected $_custom_file;
    
    protected $_papertrail_syslog_hostname;
    
    protected $_papertrail_syslog_port;

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
    
    public function getServerId()
    {
        return $this->_server_id;
    }
    
    public function setServerId($server_id)
    {
        $this->_server_id = $server_id;
        return $this;
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

    public function setStoreName($name)
    {
        $this->_store_name = $name;
        return $this;
    }

    public function getStoreName()
    {
        return $this->_store_name;
    }

    public function setDescription($name)
    {
        $this->_description = $name;
        return $this;
    }

    public function getDescription()
    {
        return $this->_description;
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
            $this->setMapper(new Application_Model_StoreMapper());
        }
        return $this->_mapper;
    }

    public function save()
    {
        return $this->getMapper()->save($this);
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
                'store_name'    => $this->getStoreName(),
                'description'      => $this->getDescription(),
                'backend_password' => $this->getBackendPassword(),
                'sample_data'      => $this->getSampleData(),
                'custom_protocol'  => $this->getCustomProtocol(),
                'custom_host'      => $this->getCustomHost(),
                'custom_remote_path' => $this->getCustomRemotePath(),
                'custom_login'     =>  $this->getCustomLogin(),
                'custom_pass'      => $this->getCustomPass(),
                'custom_sql'       => $this->getCustomSql(),
                'error_message'       => $this->getErrorMessage(),
                'revision_count'       => $this->getRevisionCount(),
                'type'       => $this->getType(),
                'custom_file'      => $this->getCustomFile(),
                'papertrail_syslog_hostname' => $this->getPapertrailSyslogHostname(),
                'papertrail_syslog_port' => $this->getPapertrailSyslogPort(),
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

    public function countUserStores( $user_id )
    {
        return $this->getMapper()->countUserStores( $user_id );
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
    
    public function getErrorMessage(){
      return $this->_error_message;
    }
    
    public function setErrorMessage($value){
      $this->_error_message = $value;
      return $this;
    }
    
    public function getRevisionCount(){
      return $this->_revision_count;
    }
    
    public function setRevisionCount($value){
      $this->_revision_count = $value;
      return $this;
    }
    
    public function getPapertrailSyslogHostname(){
      return $this->_papertrail_syslog_hostname;
    }
    
    public function setPapertrailSyslogHostname($value){
      $this->_papertrail_syslog_hostname = $value;
      return $this;
    }
    
    public function getPapertrailSyslogPort(){
      return $this->_papertrail_syslog_port;
    }
    
    public function setPapertrailSyslogPort($value){
      $this->_papertrail_syslog_port = $value;
      return $this;
    }
    
    public function findByDomain( $domain )
    {
        return $this->getMapper()->findByDomain( $domain );
    }
    
    public function findPositionByName( $store_name )
    {
        return $this->getMapper()->findPositionByName( $store_name );
    }
    
    public function getCustomFile(){
      return $this->_custom_file;
    }
    
    public function setCustomFile($value){
      $this->_custom_file = $value;
      return $this;
    }
}