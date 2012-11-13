<?php

class Application_Model_DevExtensionQueue {

    protected $_id;

    protected $_instance_id;
    
    protected $_status;
    
    protected $_user_id;
    
    protected $_dev_extension_id;

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
    
    public function setDevExtensionId($value)
    {
        $this->_dev_extension_id = $value;
        return $this;
    }

    public function getDevExtensionId()
    {
        return $this->_dev_extension_id;
    }
    
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_DevExtensionQueueMapper());
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
                'dev_extension_id' => $this->getDevExtensionId(),
        );
    }

    public function getAll()
    {
        return $this->getMapper()->fetchAll();
    }

}