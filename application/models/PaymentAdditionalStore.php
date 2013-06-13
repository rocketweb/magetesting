<?php

class Application_Model_PaymentAdditionalStore {

    protected $_id;

    protected $_user_id;

    protected $_purchased_date;

    protected $_braintree_transaction_id;

    protected $_braintree_transaction_confirmed;

    protected $_stores;

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
    
    public function setId($value)
    {
        $this->_id = (int)$value;
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

    public function setBraintreeTransactionId($value)
    {
        $this->_braintree_transaction_id = $value;
        return $this;
    }

    public function getBraintreeTransactionId()
    {
        return $this->_braintree_transaction_id;
    }

    public function setBraintreeTransactionConfirmed($value)
    {
        $this->_braintree_transaction_confirmed = $value;
        return $this;
    }

    public function getBraintreeTransactionConfirmed()
    {
        return $this->_braintree_transaction_confirmed;
    }

    public function getPurchasedDate()
    {
        return $this->_purchased_date;
    }
    
    public function setPurchasedDate($value)
    {
        $this->_purchased_date = $value;
        return $this;
    }

    public function getStores()
    {
        return $this->_stores;
    }

    public function setStores($value)
    {
        $this->_stores = $value;
        return $this;
    }

    public function getDowngraded()
    {
        return $this->_downgraded;
    }

    public function setDowngraded($value)
    {
        $this->_downgraded = $value;
        return $this;
    }

    /**
     * @return Application_Model_PaymentAdditionalStoreMapper
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_PaymentAdditionalStoreMapper());
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
    
    public function fetchWaitingForConfirmation()
    {
        return $this->getMapper()->fetchWaitingForConfirmation();
    }

    public function __toArray()
    {
        return array(
            'id'          => $this->getId(),
            'user_id'       => $this->getUserId(),
            'braintree_transaction_id' => $this->getBraintreeTransactionId(),
            'braintree_transaction_confirmed' => $this->getBraintreeTransactionConfirmed(),
            'purchased_date'    => $this->getPurchasedDate(),
            'stores'      => $this->getStores(),
            'downgraded' => $this->getDowngraded()
        );
    }
}