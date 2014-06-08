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
         * Set up roles
         */
        $this->addRole(new Zend_Acl_Role('guest'))
        ->addRole(new Zend_Acl_Role('free-user'))
        ->addRole(new Zend_Acl_Role('commercial-user'))
        ->addRole(new Zend_Acl_Role('admin'))
        ->addRole(new Zend_acl_role('extension-owner'));

        /**
         * Set up resources
         */
        $this->add(new Zend_Acl_Resource('api_store'));
        $this->add(new Zend_Acl_Resource('api_user'));
        $this->add(new Zend_Acl_Resource('api_store-status'));
        $this->add(new Zend_Acl_Resource('api_coupon'));
        $this->add(new Zend_Acl_Resource('default_error'));
        $this->add(new Zend_Acl_Resource('default_index'));
        $this->add(new Zend_Acl_Resource('default_user'));
        $this->add(new Zend_Acl_Resource('default_queue'));
        $this->add(new Zend_Acl_Resource('default_extension'));
        $this->add(new Zend_Acl_Resource('default_extensions'));
        $this->add(new Zend_Acl_Resource('default_my-account'));
        $this->add(new Zend_Acl_Resource('default_my-extensions'));
        $this->add(new Zend_Acl_Resource('default_coupon'));
        $this->add(new Zend_Acl_Resource('default_plan'));
        $this->add(new Zend_Acl_Resource('default_payment'));
        $this->add(new Zend_Acl_Resource('default_help'));
        /**
         * Deny for all (we use white list)
         */
        $this->deny();

        /* allow api calls for all */
        $this->allow('admin', 'api_store');
        $this->allow('guest', 'api_store');
        $this->allow('free-user', 'api_store');
        $this->allow('commercial-user', 'api_store');
        $this->allow('extension-owner', 'api_store');

        $this->allow('admin', 'api_user');
        $this->allow('guest', 'api_user');
        $this->allow('free-user', 'api_user');
        $this->allow('commercial-user', 'api_user');
        $this->allow('extension-owner', 'api_store');
        
        $this->allow('admin', 'api_coupon');
        $this->allow('guest', 'api_coupon');
        $this->allow('free-user', 'api_coupon');
        $this->allow('commercial-user', 'api_coupon');
        $this->allow('extension-owner', 'api_coupon');

        $this->allow('admin', 'api_store-status');
        $this->allow('guest', 'api_store-status');
        $this->allow('free-user', 'api_store-status');
        $this->allow('commercial-user', 'api_store-status');
        $this->allow('extension-owner', 'api_store-status');
        
        /*
        $this->allow('admin', 'api_coupon');
        $this->allow('guest', 'api_coupon');
        $this->allow('free-user', 'api_coupon');
        $this->allow('commercial-user', 'api_coupon');
        $this->allow('extension-owner', 'api_coupon');
        */
        /**
         * Set up privileges for admin
         */
        $this->allow('admin');
        $this->deny('admin', 'default_user', array(
                'login', 'register'
        ));
        $this->deny('admin', 'default_index', 'our-plans');

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

        /**
         * Set up privileges for extension-owner
         */
        $this->allow('extension-owner', 'default_error', array('error'));
        $this->allow('extension-owner', 'default_index', array('index', 'about-us', 'contact-us', 'partners', 'privacy', 'terms-of-service','our-plans'));
        $this->allow('extension-owner', 'default_extensions', array('index'));
        $this->allow('extension-owner', 'default_queue', array(
            'add','add-custom','add-clean', 'close', 'getVersions', 'edit',
            'extensions','getstatus', 'fetch-deployment-list', 'rollback',
            'commit', 'deploy','gettimeleft', 'request-deployment',
            'validate-ftp-credentials', 'find-sql-file', 'login-to-store-backend', 'install-extension', 'request-reindex'
        ));
        $this->allow('extension-owner', 'default_user', array(
            'index', 'logout', 'dashboard', 'edit', 'papertrail'
        ));

        $this->allow('extension-owner', 'default_my-account');
        $this->allow('extension-owner', 'default_my-extensions',array('index','list','edit','delete','list-versions','add-version-to-extension'));
        $this->allow('extension-owner', 'default_payment', array('payment', 'change-plan', 'additional-stores'));
        $this->allow('extension-owner','default_help',array('index','category','page'));
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
