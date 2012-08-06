<?php

class Application_Model_ExtensionQueue {

    protected $_id;

    protected $_queue_id;
    
    protected $_status;
    
    protected $_user_id;
    
    protected $_extension_id;

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

    public function setQueueId($value)
    {
        $this->_queue_id = $value;
        return $this;
    }

    public function getQueueId()
    {
        return $this->_queue_id;
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
    
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_ExtensionQueueMapper());
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
                'queue_id' => $this->getQueueId(),
                'status' => $this->getStatus(),
                'user_id' => $this->getUserId(),
                'extension_id' => $this->getExtensionId(),
        );
    }

    public function getAll()
    {
        return $this->getMapper()->fetchAll();
    }

}