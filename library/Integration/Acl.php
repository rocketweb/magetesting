<?php

class Integration_Acl extends Zend_Acl
{
    /**
     * Set up access control lists
     */
    public function __construct()
    {
        /**
         * Set up rolesz
         */
        $this->addRole(new Zend_Acl_Role('guest'))
        ->addRole(new Zend_Acl_Role('free-user'))
        ->addRole(new Zend_Acl_Role('awaiting-user'))
        ->addRole(new Zend_Acl_Role('commercial-user'))
        ->addRole(new Zend_Acl_Role('admin'));

        /**
         * Set up resources
         */
        $this->add(new Zend_Acl_Resource('default_braintree'));
        $this->add(new Zend_Acl_Resource('default_pay-pal'));
        $this->add(new Zend_Acl_Resource('default_error'));
        $this->add(new Zend_Acl_Resource('default_index'));
        $this->add(new Zend_Acl_Resource('default_user'));
        $this->add(new Zend_Acl_Resource('default_queue'));
        $this->add(new Zend_Acl_Resource('default_extension'));
        $this->add(new Zend_Acl_Resource('default_my-account'));
        /**
         * Deny for all (we use white list)
         */
        $this->deny();

        /**
         * Set up privileges for admin
         */
        $this->allow('admin');
        $this->deny('admin', 'default_user', array(
                'login', 'register'
        ));
        $this->deny('admin', 'default_my-account');

        /**
         * Set up privileges for guest
         */
        $this->allow('guest', 'default_error', array('error'));
        $this->allow('guest', 'default_index', array('index'));
        $this->allow('guest', 'default_user', array(
                'login', 'password-recovery', 'register', 'activate', 'reset-password', 'set-new-password'
        ));
        $this->allow('guest', 'default_braintree', array('webhook'));
        $this->allow('guest', 'default_pay-pal');

        /**
         * Set up privileges for free-user
         */
        $this->allow('free-user', 'default_error', array('error'));
        $this->allow('free-user', 'default_index', array('index'));
        $this->allow('free-user', 'default_queue', array(
                'add','add-clean', 'close', 'getVersions', 'edit','extensions','getstatus'
        ));
        $this->allow('free-user', 'default_user', array(
                'index', 'logout', 'dashboard', 'edit'
        ));
        $this->allow('free-user', 'default_my-account');

        $this->allow('free-user', 'default_pay-pal', array('cancel', 'success'));
        
        $this->allow('free-user', 'default_braintree', array('payment','result','subscribe','unsubscribe'));

        /**
         * Set up privileges for commercial-user
         */
        $this->allow('commercial-user', 'default_error', array('error'));
        $this->allow('commercial-user', 'default_index', array('index'));
        $this->allow('commercial-user', 'default_queue', array(
                'add','add-custom','add-clean', 'close', 'getVersions', 'edit','extensions','getstatus'
        ));
        $this->allow('commercial-user', 'default_user', array(
                'index', 'logout', 'dashboard', 'edit'
        ));
        $this->allow('commercial-user', 'default_my-account');
        $this->allow('commercial-user', 'default_pay-pal', array('cancel', 'success'));
        $this->allow('commercial-user', 'default_braintree', array('payment','result','subscribe','unsubscribe'));
        
        /**
         * Set up privileges for awaiting-user
         */
        $this->allow('awaiting-user', 'default_error', array('error'));
        $this->allow('awaiting-user', 'default_index', array('index'));
        $this->allow('awaiting-user', 'default_queue', array(
                'add','add-custom','add-clean', 'close', 'getVersions', 'edit','extensions','getstatus'
        ));
        $this->allow('awaiting-user', 'default_user', array(
                'index', 'logout', 'dashboard', 'edit'
        ));
        $this->allow('awaiting-user', 'default_my-account');
        $this->allow('awaiting-user', 'default_pay-pal', array('cancel', 'success'));
        $this->allow('awaiting-user', 'default_braintree', array('payment','result','subscribe','unsubscribe'));
        
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
