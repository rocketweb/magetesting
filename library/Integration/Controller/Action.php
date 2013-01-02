<?php

class Integration_Controller_Action extends Zend_Controller_Action
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;
    protected $acl;
    protected $auth;


    public function init()
    {
        $this->_helper->redirector->setUseAbsoluteUri(true);
    }

    protected function _determineTopMenu()
    {
        
        $module = $this->getRequest()->getModuleName();
        $type = $this->auth->getIdentity();
        $type = $type ? $type->group : 'guest';
        $this->_helper->layout()->showDashboard =
        $this->acl->isAllowed(
                $type,
                $module . '_' . 'user',
                'dashboard'
        );
    }

    /**
     * Getting mesages from session namespace and ACL handling.
     */

    public function preDispatch()
    {

        // ACL
        $acl = new Integration_Acl();
        $auth = Zend_Auth::getInstance();
        $request = $this->getRequest();

        // Getting mesages from session namespace.
        $this->view->messages = $this->_helper->FlashMessenger->getCurrentMessages() + $this->_helper->FlashMessenger->getMessages();
        $this->_helper->FlashMessenger->clearMessages();
        $this->_helper->FlashMessenger->clearCurrentMessages();

        $controller = $request->getControllerName();
        $action = $request->getActionName();
        $module = $request->getModuleName();

        $type = (is_null($auth->getIdentity()))
            ? 'guest' : $auth->getIdentity()->group;

        // for navigation purposes
        $this->view->navigation()->setAcl($acl);
        $this->view->navigation()->setRole($type);

        if ($controller == 'error' && $action == 'stop') {
            return $request;
        }

        $resource = $module . '_' . $controller;

        if (!$acl->has($resource)) {
            throw new Zend_Controller_Action_Exception("Resource '" . $resource . "' doesn't exist.", 404);
        }

        if ($auth->hasIdentity()) {
            $user = new Application_Model_User();
            $user->find($auth->getIdentity()->id);
            $this->view->loggedUser = $user;
            $auth->getStorage()->write((object)$user->__toArray());

            /* if user:
             * - haven't choose plan yet or
             * - his 7 days plan expired
             * redirect his to compare plan page.
             */
            if ($user->getGroup() != 'admin' AND !$user->hasPlanActive()
                AND ($controller != 'my-account' || $action != 'compare')
                AND ($controller != 'my-account' || $action != 'edit-account')
                AND ($controller != 'braintree' || $action != 'form')
                AND ($controller != 'braintree' || $action != 'response')
                AND ($controller != 'user' || $action != 'logout')
                ) {
                $this->_helper->FlashMessenger(array(
                    'from_scratch' => true,
                    'type'=> 'notice', 
                    'message' => '<strong>You don\'t have any active plan. </strong> Either your 7 days plan expired or you haven\'t choose plan yet. Please choose plan now.'));
                return $this->_helper->redirector->gotoRoute(array(
                        'module' => 'default',
                        'controller' => 'my-account',
                        'action' => 'compare',
                ), 'default', true);
            }
        }

        $this->acl = $acl;
        $this->auth = $auth;
        $this->db = Zend_Db_Table::getDefaultAdapter();

        $this->_determineTopMenu();

        // set google analytics id
        $googleId = $this->getInvokeArg('bootstrap')
                         ->getResource('config')
                         ->google->analyticsId;
        $this->view->googleAnalyticsId = $googleId;

        if ($acl->isAllowed($type, $resource, $action)) {
            return $request;
        }

        /**
         * Default redirect
         */
        $goTo = 'error/stop';

        /**
         * redirect not logged user to login form
         */
        if ('guest' == $type) {
            $goTo = 'user/login';
        }

        /**
         * redirect
         */
        $redirectHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
        return $redirectHelper->gotoUrl($goTo);
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }

}

