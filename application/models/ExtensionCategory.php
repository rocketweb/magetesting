<?php

class Application_Model_ExtensionCategory {

    protected $_id;

    protected $_name;

    protected $_class;
    
    protected $_logo;

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
   
    public function getName()
    {
        return $this->_name;
    }
    
    public function setName($value)
    {
        $this->_name = $value;
        return $this;
    }
    
    public function getClass()
    {
        return $this->_class;
    }
    
    public function setClass($value)
    {
        $this->_class = $value;
        return $this;
    }
    
    public function getLogo()
    {
        return $this->_logo;
    }
    
    public function setLogo($value)
    {
        $this->_logo = $value;
        return $this;
    }

    /**
     * @return Application_Model_ExtensionCategoryMapper
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_ExtensionCategoryMapper());
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

    public function __toArray()
    {
        return array(
            'id'    => $this->getId(),
            'name'  => $this->getName(),
            'class' => $this->getClass(),
            'logo' => $this->getLogo()
        );
    }

}