<?php
/**
 * Retrieves and saves data from plan table
 * @package Application_Model_Plan
 * @author Grzegorz (golaod)
 */
class Application_Model_Plan {

    protected $_id;

    protected $_name;

    protected $_stores;

    protected $_price;

    protected $_price_description;

    protected $_ftp_access;
    
    protected $_phpmyadmin_access;
    
    protected $_can_add_custom_store;
    
    protected $_billing_period;

    protected $_billing_description;

    protected $_is_hidden;

    protected $_auto_renew;
    
    protected $_can_do_db_revert;

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

    public function setStores($stores)
    {
        $this->_stores = (int)$stores;
        return $this;
    }

    public function getStores()
    {
        return $this->_stores;
    }

    
    public function setPrice($price)
    {
        $this->_price = (float)$price;
        return $this;
    }
    
    public function getPrice()
    {
        return $this->_price;
    }

    public function setPriceDescription($value)
    {
        $this->_price_description = $value;
        return $this;
    }

    public function getPriceDescription()
    {
        return $this->_price_description;
    }

    public function setFtpAccess($value)
    {
        $this->_ftp_access = $value;
        return $this;
    }
    
    public function getFtpAccess()
    {
        return $this->_ftp_access;
    }
    
    public function setPhpmyadminAccess($value)
    {
        $this->_phpmyadmin_access = $value;
        return $this;
    }
    
    public function getPhpmyadminAccess()
    {
        return $this->_phpmyadmin_access;
    }
    
    public function setCanAddCustomStore($value)
    {
        $this->_can_add_custom_store = $value;
        return $this;
    }
    
    public function getCanAddCustomStore()
    {
        return $this->_can_add_custom_store;
    }
    
    public function setBillingPeriod($value)
    {
        $this->_billing_period = $value;
        return $this;
    }
    
    public function getBillingPeriod()
    {
        return $this->_billing_period;
    }

    public function setBillingDescription($value)
    {
        $this->_billing_description = $value;
        return $this;
    }

    public function getBillingDescription()
    {
        return $this->_billing_description;
    }

    public function setIsHidden($value)
    {
        $this->_is_hidden = ((int)$value ? 1 : 0);
        return $this;
    }
    
    public function getIsHidden()
    {
        return $this->_is_hidden;
    }

    public function setAutoRenew($value)
    {
        $this->_auto_renew = ((int)$value ? 1 : 0);
        return $this;
    }
    
    public function getAutoRenew()
    {
        return $this->_auto_renew;
    }
    
    public function setCanDoDbRevert($value)
    {
    	$this->_auto_renew = ((int)$value ? 1 : 0);
    	return $this;
    }
    
    public function getCanDoDbRevert()
    {
    	return $this->_can_do_db_revert;
    }
    
    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    /**
     * @return Application_Model_PlanMapper
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_PlanMapper());
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
            'name'      => $this->getName(),
            'stores' => $this->getStores(),
            'ftp_access' => $this->getFtpAccess(),
            'phpmyadmin_access' => $this->getPhpmyadminAccess(),
            'can_add_custom_store' => $this->getCanAddCustomStore(),
            'billing_period' => $this->getBillingPeriod(),
            'billing_description' => $this->getBillingDescription(),
            'price'     => $this->getPrice(),
            'price_description'     => $this->getPriceDescription(),
            'is_hidden' => $this->getIsHidden(),
            'auto_renew' => $this->getAutoRenew(),
        	'can_do_db_revert' => $this->getCanDoDbRevert() 	
        );
    }
    
    /**
     * 
     * @param type $has
     */
    public function getAllByPhpmyadminAccess($has){
        return $this->getMapper()->getAllByPhpmyadminAccess($has);
    }
    
    public function findByBraintreeId($braintree_id){
        return $this->getMapper()->findByBraintreeId($braintree_id);
    }

}