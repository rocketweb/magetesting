<?php

class Integration_Acl extends Zend_Acl
{
    /**
     * Set up access control lists
     */
    public function __construct()
    {
        /**
         * Set up roles
         */
        $this->addRole(new Zend_Acl_Role('guest'))
        ->addRole(new Zend_Acl_Role('standard-user'))
        ->addRole(new Zend_Acl_Role('commercial-user'))
        ->addRole(new Zend_Acl_Role('admin'));

        /**
         * Set up resources
         */
        $this->add(new Zend_Acl_Resource('default_error'));
        $this->add(new Zend_Acl_Resource('default_index'));
        $this->add(new Zend_Acl_Resource('default_user'));
        $this->add(new Zend_Acl_Resource('default_queue'));
        /**
         * Deny for all (we use white list)
         */
        $this->deny();

        /**
         * Set up privileges for guest
         */
        $this->allow('guest', 'default_error', array('error'));
        $this->allow('guest', 'default_index', array('index'));
        $this->allow('guest', 'default_user', array(
                'login', 'password-recovery', 'register'
        ));

        /**
         * Set up privileges for admin
         */
        $this->allow('admin', 'default_error', array('error'));
        $this->allow('admin', 'default_index', array('index'));
        $this->allow('admin', 'default_queue', array(
                'add', 'close', 'index', 'getVersions'
        ));

        $this->allow('admin', 'default_user', array(
                'index', 'logout', 'dashboard','list','edit','remove'
        ));

        /**
         * Set up privileges for admin
         */
        $this->allow('standard-user', 'default_error', array('error'));
        $this->allow('standard-user', 'default_index', array('index'));
        $this->allow('standard-user', 'default_queue', array(
                'add', 'close', 'getVersions'
        ));

        $this->allow('standard-user', 'default_user', array(
                'index', 'logout', 'dashboard'
        ));

    }

    /**
     * Override to add default role.
     *
     * @param string $role
     * @param string $resource
     * @param string $privilege
     * @return boolean
     */
    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        if (is_null($role)) {
            $role = 'guest';
        }


        return parent::isAllowed($role, $resource, $privilege);
    }
}
