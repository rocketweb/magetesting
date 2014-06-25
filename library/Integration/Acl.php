<?php

class Integration_Acl extends Zend_Acl
{
    protected $_test;
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
        ->addRole(new Zend_Acl_Role('commercial-user'))
        ->addRole(new Zend_Acl_Role('admin'));

        /**
         * Set up resources
         */
        $this->addResource(new Zend_Acl_Resource('api_store'));
        $this->addResource(new Zend_Acl_Resource('api_user'));
        $this->addResource(new Zend_Acl_Resource('api_store-status'));
        $this->addResource(new Zend_Acl_Resource('api_coupon'));
        $this->addResource(new Zend_Acl_Resource('default_error'));
        $this->addResource(new Zend_Acl_Resource('default_index'));
        $this->addResource(new Zend_Acl_Resource('default_user'));
        $this->addResource(new Zend_Acl_Resource('default_queue'));
        $this->addResource(new Zend_Acl_Resource('default_extension'));
        $this->addResource(new Zend_Acl_Resource('default_extensions'));
        $this->addResource(new Zend_Acl_Resource('default_my-account'));
        $this->addResource(new Zend_Acl_Resource('default_my-extensions'));
        $this->addResource(new Zend_Acl_Resource('default_coupon'));
        $this->addResource(new Zend_Acl_Resource('default_plan'));
        $this->addResource(new Zend_Acl_Resource('default_payment'));
        $this->addResource(new Zend_Acl_Resource('default_help'));
        /**
         * Deny for all (we use white list)
         */
        $this->deny();

        /* allow api calls for all */
        $this->allow('admin', 'api_store');
        $this->allow('guest', 'api_store');
        $this->allow('free-user', 'api_store');
        $this->allow('commercial-user', 'api_store');

        $this->allow('admin', 'api_user');
        $this->allow('guest', 'api_user');
        $this->allow('free-user', 'api_user');
        $this->allow('commercial-user', 'api_user');
        
        $this->allow('admin', 'api_coupon');
        $this->allow('guest', 'api_coupon');
        $this->allow('free-user', 'api_coupon');
        $this->allow('commercial-user', 'api_coupon');

        $this->allow('admin', 'api_store-status');
        $this->allow('guest', 'api_store-status');
        $this->allow('free-user', 'api_store-status');
        $this->allow('commercial-user', 'api_store-status');
        
        $this->allow('admin', 'api_coupon');
        $this->allow('guest', 'api_coupon');
        $this->allow('free-user', 'api_coupon');
        $this->allow('commercial-user', 'api_coupon');
        /**
         * Set up privileges for admin
         */
        $this->allow('admin');
        $this->deny('admin', 'default_user', array(
                'login', 'register'
        ));

        /**
         * Set up privileges for guest
         */
        $this->allow('guest', 'default_error', array('error'));
        $this->allow('guest', 'default_index', array('index', 'about-us', 'contact-us', 'partners', 'privacy', 'terms-of-service', 'our-plans'));
        $this->allow('guest', 'default_extensions', array('index'));
        $this->allow('guest', 'default_user', array(
                'login', 'password-recovery', 'register', 'activate', 'reset-password', 'set-new-password'
        ));
        $this->allow('guest','default_help',array('index','category','page'));

        /**
         * Set up privileges for free-user
         */
        $this->allow('free-user', 'default_error', array('error'));
        $this->allow('free-user', 'default_index', array('index', 'about-us', 'contact-us', 'partners', 'privacy', 'terms-of-service','our-plans'));
        $this->allow('free-user', 'default_extensions', array('index'));
        $this->allow('free-user', 'default_queue', array(
                'add','add-clean', 'close', 'getVersions', 'edit','extensions','getstatus', 'login-to-store-backend', 'request-reindex'
        ));
        $this->allow('free-user', 'default_user', array(
                'index', 'logout', 'dashboard', 'edit'
        ));
        $this->allow('free-user', 'default_my-account');

        $this->allow('free-user', 'default_payment', array('payment', 'change-plan', 'additional-stores'));
        $this->allow('free-user','default_help',array('index','category','page'));
        
        /**
         * Set up privileges for commercial-user
         */
        $this->allow('commercial-user', 'default_error', array('error'));
        $this->allow('commercial-user', 'default_index', array('index', 'about-us', 'contact-us', 'partners', 'privacy', 'terms-of-service','our-plans'));
        $this->allow('commercial-user', 'default_extensions', array('index'));
        $this->allow('commercial-user', 'default_queue', array(
                'add','add-custom','add-clean', 'close', 'getVersions', 'edit',
                'extensions','getstatus', 'fetch-deployment-list', 'rollback', 
                'commit', 'deploy','gettimeleft', 'request-deployment',
                'validate-ftp-credentials', 'find-sql-file', 'login-to-store-backend', 'install-extension', 'request-reindex'
        ));
        $this->allow('commercial-user', 'default_user', array(
                'index', 'logout', 'dashboard', 'edit', 'papertrail'
        ));
        $this->allow('commercial-user', 'default_my-account');
        $this->allow('commercial-user', 'default_payment', array('payment', 'change-plan', 'additional-stores'));
        $this->allow('commercial-user','default_help',array('index','category','page'));
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
