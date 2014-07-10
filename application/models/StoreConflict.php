<?php

class Application_Model_StoreConflict {
    
    protected $_id;
    protected $_store_id;
    protected $_type;
    protected $_class;
    protected $_rewrites;
    protected $_loaded;
    protected $_ignore;

    protected $_mapper;
    protected $_cli;
    
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
    
    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }
    public function getType()
    {
        return $this->_type;
    }

    public function setLoaded($loaded)
    {
        $this->_loaded = $loaded;
        return $this;
    }
    public function getLoaded()
    {
        return $this->_loaded;
    }

    public function setClass($class)
    {
        $this->_class = $class;
        return $this;
    }
    public function getClass()
    {
        return $this->_class;
    }

    public function setRewrites($rewrites)
    {
        $this->_rewrites = $rewrites;
        return $this;
    }
    public function getRewrites()
    {
        return $this->_rewrites;
    }

    public function setIgnore($ignore)
    {
        $this->_ignore = $ignore === true ? true : false;
        return $this;
    }
    public function getIgnore()
    {
        return $this->_ignore;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    /**
     * @return Application_Model_StoreConflictMapper
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_StoreConflictMapper());
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
        return $this->getMapper()->find($id,$this);
    }

    public function fetchStoreConflicts($store_id)
    {
        return $this->getMapper()->fetchStoreConflicts($store_id,$this);
    }

    public function fetchUserStoreConflicts($user_id, $store_id = false)
    {
        return $this->getMapper()->fetchUserStoreConflicts($user_id, $store_id);
    }

    public function removeStoreConflicts($store_id)
    {
        $this->getMapper()->removeStoreConflicts($store_id);
    }

    public function __toArray()
    {
        return array(
            'id'          => $this->getId(),
            'store_id'   => $this->getStoreId(),
            'type'    => $this->getType(),
            'class'       => $this->getClass(),
            'rewrites' => $this->getRewrites(),
            'loaded' => $this->getLoaded(),
            'ignore' => $this->getIgnore()
        );
    }

    public function getConflicts($dirPath, $login)
    {
        $this->_cli = new RocketWeb_Cli();

        $dirPath = '/sites/gregor.vps/magento.gregor.vps';

        $command = $this->_cli->kit('n98')->conflict($dirPath, $login);
        $output = $command->call()->getLastOutput();
        $conflicts = $command->parseConflict($output);
        return $conflicts;
    }
}