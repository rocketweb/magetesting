<?php

class Application_Model_Queue {

    protected $_id;

    protected $_store_id;
    
    protected $_status;
    
    protected $_user_id;
    
    protected $_extension_id;
    
    protected $_parent_id;
    
    protected $_server_id;
    
    protected $_task;
    
    protected $_task_params;
    
    protected $_retry_count;

    protected $_added_date;
    
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

    public function setStoreId($value)
    {
        $this->_store_id = $value;
        return $this;
    }

    public function getStoreId()
    {
        return $this->_store_id;
    }

    public function setStatus($value)
    {
        $this->_status = $value;
        return $this;
    }

    public function getStatus()
    {
        return $this->_status;
    }
    
    
    public function setUserId($value)
    {
        $this->_user_id = $value;
        return $this;
    }

    public function getUserId()
    {
        return $this->_user_id;
    }
    
    public function setExtensionId($value)
    {
        $this->_extension_id = $value;
        return $this;
    }

    public function getExtensionId()
    {
        return $this->_extension_id;
    }
    
    /**
     * Currently supported tasks:
     * - MagentoInstall
     * - MagentoDownload
     * - MagentoRemove
     * - ExtensionInstall
     * - ExtensionOpenSource
     * - RevisionRollback
     * - RevisionCommit
     * - RevisionDeploy
     * - RevisionInit
     * @param string $value
     * @return Application_Model_Queue
     */
    public function setTask($value)
    {
        $this->_task = $value;
        return $this;
    }

    public function getTask()
    {
        return $this->_task;
    }
    
    public function setTaskParams($value,$serialize=true)
    {
        if ($serialize === true){
            $serialized = Zend_Json::encode($value);
            $this->_task_params = $serialized;
        } else {
            $this->_task_params = $value;
        }
        
        return $this;
    }

    /**
     * 
     * @param type $unserialize
     * @return array/serialized
     */
    public function getTaskParams($unserialize = true)
    {
        if ($unserialize){
            $unserialized = Zend_Json::decode($this->_task_params);
            return $unserialized;
        } else {
            return $this->_task_params;
        }
        
        
    }
    
    public function getRetryCount()
    {
        return $this->_retry_count;
    }
    
    public function setRetryCount($value)
    {
        $this->_retry_count = (int)$value;
        return $this;
    }
    
    public function setServerId($value)
    {
        $this->_server_id = $value;
        return $this;
    }

    public function getServerId()
    {
        return $this->_server_id;
    }
    
    public function setParentId($value)
    {
        $this->_parent_id = $value;
        return $this;
    }

    public function getParentId()
    {
        return $this->_parent_id;
    }
    
    public function setAddedDate($value){
        $this->_added_date = $value;
        return $this;
    }
    
    public function getAddedDate(){
        return $this->_added_date;
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
                'id' => $this->getId(),
                'store_id' => $this->getStoreId(),
                'status' => $this->getStatus(),
                'user_id' => $this->getUserId(),
                'extension_id' => $this->getExtensionId(),
                'task' => $this->getTask(),
                'task_params' => $this->getTaskParams(),
                'retry_count' => $this->getRetryCount(),
                'parent_id' => $this->getParentId(),
                'server_id' => $this->getServerId(),
        );
    }
    
    public function getForServer($worker_id=null,$type='all'){
        return $this->getMapper()->getForServer($worker_id,$type);
    }
    
    public function getParentIdForExtensionInstall($store_id){
        return $this->getMapper()->getParentIdForExtensionInstall($store_id);
    }
    
    public function countForStore($storeId){
        return $this->getMapper()->countForStore($storeId);
    }
    
    public function findPositionByName($storeName) {
        return $this->getMapper()->findPositionByName($storeName);
    }
    
}
