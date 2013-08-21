<?php

class Api_CouponController extends Integration_Controller_Action
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
        $this->getResponse()->setBody(json_encode($this->_response_object));
    }
    public function getAction()
    {
        $this->indexAction();
    }
    public function putAction()
    {
        $this->indexAction();
    }
    public function deleteAction()
    {
        $this->indexAction();
    }

    public function postAction()
    {
        $login = $this->getParam('username', '');
        $apikey = $this->getParam('apikey', '');
        $userObject = new Application_Model_User();
        if($this->_authenticate($login, $apikey, $userObject)) {
            if($this->_isAdmin($userObject)) {
                $this->_addCoupon();
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
    protected function _isAdmin(Application_Model_User $userObject)
    {
        if('admin' === $userObject->getGroup() && (int)$userObject->getPlanId()) {
            return true;
        }
        // we don't want to inform hacker that he has credentials to account
        // but without admin privileges
        $this->_response_object['message'] = 'Username or apikey is invalid.';
        return false;
    }
    protected function _addCoupon()
    {
        $form = new Application_Form_CouponAdd();
        if($form->isValid($this->getRequest()->getParams())) {
            $coupon = new Application_Model_Coupon();
            $coupon->setOptions($form->getValues());
            $coupon->save();
            $this->_response_object['type'] = 'success';
            $this->_response_object['message'] = 'Coupon has been added successfully';
        } else {
            $fields = $form->getMessages();
            if(!$fields) { $fields == array(); }
            $fields = array_keys($fields);
            $this->_response_object['message'] = 'Following field values are invalid: '. implode(', ',$fields).'. Please fix them and try again.';
        }
    }
}