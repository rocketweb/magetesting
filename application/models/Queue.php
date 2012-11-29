<?php

class Application_Model_Queue {

    protected $_id;

    protected $_instance_id;
    
    protected $_status;
    
    protected $_user_id;
    
    protected $_extension_id;
    
    protected $_task;
    
    protected $_task_params;
    
    protected $_server_id;
    
    protected $_parent_id;
    
    protected $_version_id;
    
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

    public function setInstanceId($value)
    {
        $this->_instance_id = $value;
        return $this;
    }

    public function getInstanceId()
    {
        return $this->_instance_id;
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
     * - ExtensionRemove (FFU)
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
        if ($serialize){
        $serialized = serialize($value);
        $this->_task_params = $serialized;
        } else {
            $this->_task_params = $value;
        }
        
        return $this;
    }

    public function getTaskParams($unserialize = true)
    {
        if ($unserialize){
            $unserialized = unserialize($this->_task_params);
            return $unserialized;
        } else {
            return $this->_task_params;
        }
        
        
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
                'id' => $this->getId(),
                'instance_id' => $this->getInstanceId(),
                'status' => $this->getStatus(),
                'user_id' => $this->getUserId(),
                'extension_id' => $this->getExtensionId(),
                'task' => $this->getTask(),
                'task_params' => $this->getTaskParams(),
                'parent_id' => $this->getParentId(),
                'server_id' => $this->getServerId(),
        );
    }

    public function getAll()
    {
        return $this->getMapper()->fetchAll();
    }
    
    public function getForServer($worker_id=null,$type='all'){
        return $this->getMapper()->getForServer($worker_id,$type);
    }
    
}