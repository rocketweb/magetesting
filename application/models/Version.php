<?php

class Application_Model_Version {

    protected $_id;

    protected $_edition;

    protected $_version;
    
    protected $_sample_data_version;

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

    public function setEdition($edition)
    {
        $this->_edition = $edition;
        return $this;
    }

    public function getEdition()
    {
        return $this->_edition;
    }

    public function setVersion($version)
    {
        $this->_version = $version;
        return $this;
    }

    public function getVersion()
    {
        return $this->_version;
    }
    
    public function setSampleDataVersion($version)
    {
        $this->_sample_data_version = $version;
        return $this;
    }

    public function getSampleDataVersion()
    {
        return $this->_sample_data_version;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    /**
     * @return Application_Model_VersionMapper
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_VersionMapper());
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
                'edition' => $this->getEdition(),
                'version' => $this->getVersion(),
                'sample_data_version' => $this->getSampleDataVersion()
        );
    }

    public function getAllForEdition( $edition )
    {
        return $this->getMapper()->getAllForEdition( $edition );
    }

    //TODO: prepare mapper for this
    public function getKeys($with_edition = false)
    {
        return $this->getMapper()->getKeys($with_edition);
    }
    
    /**
     * Finds the close
     * @param type $versionString eg '1.7.0.2'
     * @return array
     */
    public function getClosestVersion($versionString){
        return $this->getDbTable()->getClosestVersion($versionString);
    }

}