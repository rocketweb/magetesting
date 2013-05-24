<?php

class Api_UserController extends Integration_Controller_Action
{
    protected $_response_object = array(
            'type' => 'error',
            'message' => 'Wrong REST api call'
    );

    public function init()
    {
        parent::init();
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function indexAction()
    {
        $login = $this->getParam('login', '');
        $apikey = $this->getParam('apikey', '');
        $userObject = new Application_Model_User();
        if($this->_authenticate($login, $apikey, $userObject)) {
            if($this->_checkUserPlan($userObject)) {
                $this->_getLeftStores($userObject);
            }
        }
        $this->getResponse()->setBody(json_encode($this->_response_object))->setHeader('Content-Type', 'text/json');
    }
    protected function _authenticate($username, $apikey, Application_Model_User $userObject)
    {
        $authenticated = $userObject->authenticateApiCall($username, $apikey);
        if(!$authenticated) {
            $this->_response_object['message'] = 'Username or apikey is invalid.';
        }
        return $authenticated;
    }
    protected function _checkUserPlan(Application_Model_User $userObject)
    {
        if($userObject->hasPlanActive() || ('admin' === $userObject->getGroup() && (int)$userObject->getPlanId())) {
            $this->_response_object['firstname'] = $userObject->getFirstname();
            $this->_response_object['lastname'] = $userObject->getLastname();
            return true;
        }
        $this->_response_object['message'] = 'You don\'t have an active plan.';
        return false;
    }
    protected function _getLeftStores(Application_Model_User $userObject)
    {
        if('free-user' == $userObject->getGroup()) {
            $maxStores =
            (int) $this->getInvokeArg('bootstrap')
                       ->getResource('config')
                       ->magento
                       ->standardUser
                       ->stores;
        } else {
            $planModel = new Application_Model_Plan();
            $planModel->find($userObject->getPlanId());
            $maxStores = (int) $planModel->getStores();
        }

        $storeModel = new Application_Model_Store();
        $userStores = $storeModel->countUserStores($userObject->getId());

        $this->_response_object['remainingStores'] = $userStores - $maxStores;
        if($userStores >= $maxStores) {
            $this->_response_object['message'] = 'You have reached number of stores limit, please remove any store in Mage Testing and try again.';
        } else {
            $this->_response_object['type'] = 'success';
            $this->_response_object['remainingStores'] = $this->_response_object['remainingStores'] * -1;
            $this->_response_object['message'] = 'You still have '.$this->_response_object['remainingStores']. ' stores left to use.';
        }
    }

    public function getAction()
    {
        $this->postAction();
    }
    public function putAction()
    {
        $this->postAction();
    }
    public function deleteAction()
    {
        $this->postAction();
    }

    public function postAction()
    {
        $this->getResponse()->setBody(json_encode($this->_response_object));
    }
}