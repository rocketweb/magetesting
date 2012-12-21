<?php

class IndexController extends Integration_Controller_Action
{

    public function init()
    {
        $this->_helper->sslSwitch(false);
    }

    public function indexAction()
    {
        // action body
        $auth = $this->auth->getIdentity();
        $this->view->user_logged = isset($auth->id) AND $auth->id ? true : false;
    }
    
    public function aboutUsAction() {
        // action body
    }

    public function contactUsAction() {
        // action body
    }
    
    public function partnersAction() {
        // action body
    }
    
    public function privacyAction() {
        // action body
    }
    
    public function termsOfServiceAction() {
        // action body
    }
}