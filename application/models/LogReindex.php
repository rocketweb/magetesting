<?php

class Application_Model_LogReindex {

    protected $_id;

    protected $_store_id;

    protected $_time;

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

    public function getId()
    {
        return $this->_id;
    }

    public function setId($id)
    {
        $this->_id = (int)$id;
        return $this;
    }

    public function getStoreId()
    {
        return $this->_store_id;
    }

    public function setStoreId($value)
    {
        $this->_store_id = $value;
        return $this;
    }

    public function getTime()
    {
        return $this->_time;
    }

    public function setTime($value)
    {
        $this->_time = $value;
        return $this;
    }

    /**
     * @return Application_Model_LogReindex
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_LogReindexMapper());
        }
        return $this->_mapper;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
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

    public function canRun($storeId, $allowed = 3, $period = 1)
    {
        $count = $this->getMapper()->countForStore($storeId, $period);

        if ($count < $allowed) {
            return true;
        }

        return false;
    }

    public function __toArray()
    {
        return array(
            'id'    => $this->getId(),
            'store_id'  => $this->getStoreId(),
            'time' => $this->getTime(),
        );
    }

}