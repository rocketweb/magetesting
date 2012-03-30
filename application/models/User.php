<?php

class Application_Model_User {

    protected $_id;

    protected $_firstname;

    protected $_lastname;

    protected $_email;

    protected $_login;

    protected $_password;

    protected $_street;

    protected $_postal_code;

    protected $_city;

    protected $_state;

    protected $_country;

    protected $_group;

    protected $_addedDate;

    protected $_status;

    protected $_departmentId;

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

    public function setFirstname($firstname)
    {
        $this->_firstname = $firstname;
        return $this;
    }

    public function getFirstname()
    {
        return $this->_firstname;
    }

    public function setLastname($lastname)
    {
        $this->_lastname = $lastname;
        return $this;
    }

    public function getLastname()
    {
        return $this->_lastname;
    }

    public function setEmail($email)
    {
        $this->_email = $email;
        return $this;
    }

    public function getEmail()
    {
        return $this->_email;
    }

    public function setLogin($login)
    {
        $this->_login = $login;
        return $this;
    }

    public function getLogin()
    {
        return $this->_login;
    }

    public function setPassword($password)
    {
        $this->_password = $password;
        return $this;
    }

    public function getPassword()
    {
        return $this->_password;
    }

    public function setStreet($street)
    {
        $this->_street = $street;
        return $this;
    }

    public function getStreet()
    {
        return $this->_street;
    }

    public function setPostalCode($postalCode)
    {
        $this->_postal_code = $postalCode;
        return $this;
    }

    public function getPostalCode()
    {
        return $this->_postal_code;
    }

    public function setCity($city)
    {
        $this->_city = $city;
        return $this;
    }

    public function getCity()
    {
        return $this->_city;
    }

    public function setState($state)
    {
        $this->_state = $state;
        return $this;
    }

    public function getState()
    {
        return $this->_state;
    }

    public function setCountry($country)
    {
        $this->_country = $country;
        return $this;
    }

    public function getCountry()
    {
        return $this->_country;
    }
    

    public function setGroup($group)
    {
        $this->_group = $group;
        return $this;
    }

    public function getGroup()
    {
        return $this->_group;
    }

    public function setAddedDate($addedDate)
    {
        $this->_addedDate = $addedDate;
        return $this;
    }

    public function getAddedDate()
    {
        return $this->_addedDate;
    }

    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_UserMapper());
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
        $this->getMapper()->find($id, $this);
        return $this;
    }

    public function fetchAll($activeOnly=false)
    {
        return $this->getMapper()->fetchAll($activeOnly);
    }
    
    public function fetchList()
    {
        return $this->getMapper()->fetchList();
    }

    /**
     * @method activateUser
     * @param int $id - User ID
     * @param sha1 $hash - sha1(login,email,added_date)
     * @return int [0-2]:<br />
     * 0 - successfully activated<br />
     * 1 - wrong data<br />
     * 2 - previously activated
     */
    public function activateUser($id, $hash)
    {
        return $this->getMapper()->activateUser($id, $hash);
    }

    public function __toArray()
    {
        return array(
            'id'          => $this->getId(),
            'firstname'   => $this->getFirstname(),
            'lastname'    => $this->getLastname(),
            'email'       => $this->getEmail(),
            'login'       => $this->getLogin(),
            'street'      => $this->getStreet(),
            'postal_code' => $this->getPostalCode(),
            'city'        => $this->getCity(),
            'state'       => $this->getState(),
            'country'     => $this->getCountry(),
            'group'       => $this->getGroup(),
            'added_date'  => $this->getAddedDate(),
            'status'      => $this->getStatus()
        );
    }
    
    
}