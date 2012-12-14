<?php

class Application_Model_StoreExtension {
    
    protected $_id;
    protected $_store_id;
    protected $_extension_id;
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

    public function setExtensionId($value)
    {
        $this->_extension_id = $value;
        return $this;
    }

    public function getExtensionId()
    {
        return $this->_extension_id;
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
            $this->setMapper(new Application_Model_StoreExtensionMapper());
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
            'id'          => $this->getId(),
            'store_id'   => $this->getStoreId(),
            'extension_id'    => $this->getExtensionId(),
            'added_date'       => $this->getAddedDate()
        );
    }
}