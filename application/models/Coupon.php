<?php

class Application_Model_Coupon {

    protected $_id;

    protected $_code;

    protected $_used_date;

    protected $_user_id;

    protected $_plan_id;

    protected $_duration;

    protected $_active_to;

    protected $_mapper;
    
    protected $_error;
    
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
   
    public function getCode()
    {
        return $this->_code;
    }
    
    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }
    
    public function getUsedDate()
    {
        return $this->_used_date;
    }
    
    public function setUsedDate($used_date)
    {
        $this->_used_date = $used_date;
        return $this;
    }

    public function getUserId()
    {
        return $this->_user_id;
    }
    
    public function setUserId($user_id)
    {
        $this->_user_id = $user_id;
        return $this;
    }
    
    public function getPlanId()
    {
        return $this->_plan_id;
    }
    
    public function setPlanId($plan_id)
    {
        $this->_plan_id = $plan_id;
        return $this;
    }
    
    public function getDuration()
    {
        return $this->_duration;
    }
    
    public function setDuration($duration)
    {
        $this->_duration = $duration;
        return $this;
    }
    
    public function getActiveTo()
    {
        return $this->_active_to;
    }
    
    public function setActiveTo($active_to)
    {
        $this->_active_to = $active_to;
        return $this;
    }
    
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_CouponMapper());
        }
        return $this->_mapper;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }
    
    public function getError(){
        return $this->getMapper()->getError();
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
    
    public function findByCode($code)
    {
        return $this->getMapper()->findByCode($code, $this);
    }

    public function fetchAll($activeOnly=false)
    {
        return $this->getMapper()->fetchAll($activeOnly);
    }
    
    public function fetchList()
    {
        return $this->getMapper()->fetchList();
    }

    public function apply($coupon_id, $user_id)
    {
        return $this->getMapper()->apply($coupon_id, $user_id);
    }
    
    public function isUnused()
    {
        if ($this->getUserId()){
            return false;
        }
        
        if ($this->getUsedDate()){
            return false;
        }
    }

    public function __toArray()
    {
        return array(
            'id'          => $this->getId(),
            'code'   => $this->getCode(),
            'used_date'    => $this->getUsedDate(),
            'user_id'       => $this->getUserId(),
            'plan_id'       => $this->getPlanId(),
            'duration'      => $this->getDuration(),
            'active_to' => $this->getActiveTo(),
        );
    }
    
    
}