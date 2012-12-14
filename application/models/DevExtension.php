<?php

class Application_Model_DevExtension {

    protected $_id;

    protected $_name;
    
    protected $_repo_type;
    
    protected $_repo_url;
    
    protected $_repo_user;
    
    protected $_repo_password;
    
    protected $_edition;
    
    protected $_from_version;
    
    protected $_to_version;
    
    protected $_version;
    
    protected $_extension_config_file;

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

    public function setName($value)
    {
        $this->_name = $value;
        return $this;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setRepoType($name)
    {
        $this->_repo_type= $name;
        return $this;
    }

    public function getRepoType()
    {
        return $this->_repo_type;
    }
    
    
    public function setRepoUrl($value)
    {
        $this->_repo_url = $value;
        return $this;
    }

    public function getRepoUrl()
    {
        return $this->_repo_url;
    }
    
    public function setRepoUser($value)
    {
        $this->_repo_user = $value;
        return $this;
    }

    public function getRepoUser()
    {
        return $this->_repo_user;
    }
    
    public function setRepoPassword($value)
    {
        $this->_repo_password = $value;
        return $this;
    }
    
    public function getRepoPassword()
    {
        return $this->_repo_password;
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
    
    public function setVersion($value)
    {
        $this->_to_version = $value;
        return $this;
    }
    
    public function getVersion()
    {
        return $this->_to_version;
    }
    
    public function setExtensionConfigFile($value)
    {
        $this->_extension_config_file = $value;
        return $this;
    }
    
    public function getExtensionConfigFile()
    {
        return $this->_extension_config_file;
    }
    

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_DevExtensionMapper());
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
            'name' => $this->getName(),
            'repo_type' => $this->getRepoType(),
            'repo_url' => $this->getRepoUrl(),
            'repo_user' => $this->getRepoUser(),
            'repo_password' => $this->getRepoPassword(),
            'edition' => $this->getEdition(),
            'from_version' => $this->getFromVersion(),
            'to_version' => $this->getToVersion(),
            'extension_config_file' => $this->getExtensionConfigFile(),
        );
    }

    public function getKeys()
    {
        return $this->getMapper()->getKeys();
    }

    public function getOptions()
    {
        return $this->getMapper()->getOptions();
    }
    
    public function getAllForStore($store_name){
        return $this->getMapper()->getAllForStore($store_name);
    }
    
    public function findByFilters(array $filters){
        return $this->getMapper()->findByFilters($filters ,$this);
    }
    
}