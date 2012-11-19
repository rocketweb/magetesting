<?php

class Application_Model_Extension {

    protected $_id;

    protected $_name;
    
    protected $_description;
    
    protected $_logo;
    
    protected $_version;
    
    protected $_extension;
    
    protected $_extension_encoded;
    
    protected $_namespace_module;
    
    protected $_from_version;
    
    protected $_to_version;
    
    protected $_edition;

    protected $_is_dev;
    
    protected $_price;
    
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

   public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->_name;
    }
    
    public function setDescription($value)
    {
        $this->_description = $value;
        return $this;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function setLogo($logo)
    {
        $this->_logo = $logo;
        return $this;
    }

    public function getLogo()
    {
        return $this->_logo;
    }

    public function setExtension($value)
    {
        $this->_extension = $value;
        return $this;
    }

    public function getExtension()
    {
        return $this->_extension;
    }
    
    public function setExtensionEncoded($value)
    {
        $this->_extension_encoded = $value;
        return $this;
    }
    
    public function getExtensionEncoded()
    {
        return $this->_extension_encoded;
    }
    
    public function setNamespaceModule($value)
    {
        $this->_namespace_module = $value;
        return $this;
    }

    public function getNamespaceModule()
    {
        return $this->_namespace_module;
    }

    public function setVersion($value)
    {
        $this->_version = $value;
        return $this;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function setFromVersion($value)
    {
        $this->_from_version = $value;
        return $this;
    }

    public function getFromVersion()
    {
        return $this->_from_version;
    }
    
    public function setToVersion($value)
    {
        $this->_to_version = $value;
        return $this;
    }

    public function getToVersion()
    {
        return $this->_to_version;
    }
    
    public function setEdition($value)
    {
        $this->_edition = $value;
        return $this;
    }

    public function getEdition()
    {
        return $this->_edition;
    }
    
    public function setIsDev($value)    {
        $this->_is_dev = $value;
        return $this;
    }

    public function getIsDev()
    {
        return $this->_is_dev;
    }   

    public function setPrice($price)
    {
        $this->_price = $price;
        return $this;
    }

    public function getPrice()
    {
        return $this->_price;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_ExtensionMapper());
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
        $extensions = array();
        foreach($this->getMapper()->fetchAll() as $row) {
            $extensions[] = array(
                'item' => $row,
                'screenshots' => $row->fetchScreenshots()
            );
        }
        return $extensions;
    }

    public function __toArray()
    {
        return array(
                'id' => $this->getId(),
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'version' => $this->getVersion(),
                'logo' => $this->getLogo(),
                'extension' => $this->getExtension(),
                'extension_encoded' => $this->getExtensionEncoded(),
                'namespace_module' => $this->getNamespaceModule(),
                'from_version' => $this->getFromVersion(),
                'to_version' => $this->getToVersion(),
                'edition' => $this->getEdition(),
                'is_dev' => $this->getIsDev(),
                'price' => $this->getPrice(),
        );
    }

    public function getAll()
    {
        return $this->getMapper()->fetchAll();
    }

    public function getKeys()
    {
        return $this->getMapper()->getKeys();
    }

    public function getOptions()
    {
        return $this->getMapper()->getOptions();
    }
    
    public function getAllForInstance($instance_name){
        return $this->getMapper()->getAllForInstance($instance_name);
    }
    
    public function getInstalledForInstance($instance_name){
        return $this->getMapper()->getInstalledForInstance($instance_name);
    }
    
    public function findByFilters($filters){
        return $this->getMapper()->findByFilters($filters,  $this);
    }

    public function fetchScreenshots($id = 0) {
        $id = (int)$id ? (int)$id : (int)$this->getId();
        if(!$id) {
            return array();
        }
        $model = new Application_Model_ExtensionScreenshot();
        return $model->fetchByExtensionId($id);
    }
}