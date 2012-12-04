<?php

class Application_Model_Revision {

    protected $_id;

    protected $_instance_id;

    protected $_user_id;
    
    protected $_extension_id;

    protected $_type;
    
    protected $_comment;
    
    protected $_hash;
    
    protected $_filename;
    
    protected $_db_before_revision;
    
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
    
    public function setId($value)
    {
        $this->_id = (int)$value;
        return $this;
    }

        public function getInstanceId()
    {
        return $this->_instance_id;
    }
    
    public function setInstanceId($value)
    {
        $this->_instance_id = (int)$value;
        return $this;
    }
    
    public function getUserId()
    {
        return $this->_user_id;
    }

    public function setUserId($value)
    {
        $this->_user_id = $value;
        return $this;
    }

    public function getExtensionId()
    {
        return $this->_extension_id;
    }

    public function setExtensionId($value)
    {
        $this->_extension_id = $value;
        return $this;
    }
    
    public function getType()
    {
        return $this->_type;
    }
    
    public function setType($value)
    {
        $this->_type =  $value;
        return $this;
    }   
    
    public function getComment()
    {
        return $this->_comment;
    }
    
    public function setComment($value)
    {
        $this->_comment =  $value;
        return $this;
    }   
    
    public function getHash()
    {
        return $this->_hash;
    }
    
    public function setHash($value)
    {
        $this->_hash =  $value;
        return $this;
    }   
    
    public function getFilename()
    {
        return $this->_filename;
    }
    
    public function setFilename($value)
    {
        $this->_filename = $value;
        return $this;
    }   
    
    public function getDbBeforeRevision()
    {
        return $this->_db_before_revision;
    }
    
    public function setDbBeforeRevision($value)
    {
        $this->_db_before_revision = $value;
        return $this;
    }  
    
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_RevisionMapper());
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

    /**
     * @param boolean $fetch_hidden - whether fetch also hidden plans
     */
    public function fetchAll($fetch_hidden = false)
    {
        return $this->getMapper()->fetchAll($fetch_hidden);
    }

    public function __toArray()
    {
        return array(
                'id'        => $this->getId(),
                'instance_id'      => $this->getInstanceId(),
                'user_id' => $this->getUserId(),
                'type' => $this->getType(),
                'comment' => $this->getComment(),
                'hash' => $this->getHash(),
                'filename' => $this->getFilename(),
                'db_before_revision'     => $this->getDbBeforeRevision(),
        );
    }

    /* TODO: rewrite to not use this, currently an alias */
    /**
     * @deprecated
     * @return type
     */
    public function getAll()
    {
        return $this->getMapper()->fetchAll();
    }

    /**
     * fetches array of all revisions for instance<br />
     * joined with extension table to get extension name
     * @param int $instance_id
     */
    public function getAllForInstance($instance_id)
    {
        return $this->getMapper()->getAllForInstance($instance_id);
    }
    
    public function getPreLastForInstance($instance_id){
        return $this->getMapper()->getPreLastForInstance($instance_id, $this);
    }
    
}