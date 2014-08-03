<?php

class Application_Model_ExtensionScreenshot {

    protected $_id;

    protected $_extension_id;

    protected $_image;

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

    public function setExtensionId($id)
    {
        $this->_extension_id = (int)$id;
        return $this;
    }

    public function getExtensionId()
    {
        return $this->_extension_id;
    }

   public function setImage($image)
    {
        $this->_image = $image;
        return $this;
    }

    public function getImage()
    {
        return $this->_image;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    /**
     * @return Application_Model_ExtensionScreenshotMapper
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_ExtensionScreenshotMapper());
        }
        return $this->_mapper;
    }

    public function save()
    {
        return $this->getMapper()->save($this);
    }

    public function delete($id = 0)
    {
        $id = (int)$id ? (int)$id : (int)$this->getId();
        if(!$id) {
            return false;
        }
        $this->getMapper()->delete($id);
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }

    public function fetchByExtensionId($id)
    {
        $id = (int)$id;
        if(!$id) {
            return false;
        }

        return $this->getMapper()->fetchByExtensionId($id);
    }

    public function __toArray()
    {
        return array(
                'id' => $this->getId(),
                'extension_id' => $this->getExtensionId(),
                'image' => $this->getImage()
        );
    }

}