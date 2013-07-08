<?php

class Api_StoreStatusController extends Integration_Controller_Action
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
        $login = $this->getParam('username', '');
        $apikey = $this->getParam('apikey', '');
        $domain = $this->getParam('domain');
        $userObject = new Application_Model_User();
        if($this->_authenticate($login, $apikey, $userObject)) {
            $storeObject = new Application_Model_Store();
            if(
                $this->_findStore($domain, $storeObject) &&
                $this->_isUserStore($userObject, $storeObject)
            ) {
                $this->_response_object['type'] = 'success';
                $this->_response_object['message'] = $storeObject->getStatus();
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
    protected function _findStore($domain, Application_Model_Store $storeObject)
    {
        if(!$domain) {
            $this->_response_object['message'] = 'Wrong domain.';
            return false;
        }

        $store_data = $storeObject->findByDomain($domain);
        if(is_object($store_data)) {
            $store_data = $store_data->toArray();
            // do not use encrypt for these fields
            $storeObject->setBackendPassword($store_data['backend_password'], false);
            $storeObject->setCustomPass($store_data['custom_pass'], false);
            unset($store_data['backend_password'], $store_data['custom_pass']);
            $storeObject->setOptions($store_data);
            return true;
        }

        $this->_response_object['message'] = 'Store does not exist.';
        return false;
    }
    protected function _isUserStore(Application_Model_User $userObject, Application_Model_Store $storeObject)
    {
        if((int)$userObject->getId() === (int)$storeObject->getUserId()) {
            return true;
        }

        $this->_response_object['message'] = 'Store does not belong to user.';
        return false;
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