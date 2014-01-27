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
        $form = new Application_Form_Contact();
        
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();

            if ($form->isValid($formData)) {

                $appConfig = $this->getInvokeArg('bootstrap')
                                 ->getResource('config');
                
                $mail = new Integration_Mail_Contact();
                $mail->setup($appConfig, array('formData' => (object)$formData));

                try {
                    $mail->send();
                } catch (Zend_Mail_Transport_Exception $e){
                    $log = $this->getInvokeArg('bootstrap')->getResource('log');
                    $log->log('Contact - Unable to send email', Zend_Log::CRIT, json_encode($e->getTraceAsString()));
                }
                
                $this->_helper->FlashMessenger(array(
                    'message' => 'You successfully sent your message.',
                    'type'    => 'success',
                ));
                
                return $this->_helper->redirector->gotoRoute(array(), 'contact', true);
            }
        }
        
        $this->view->form = $form;
    }

    public function ourPlansAction() {
        if($this->_isLoggedNotAdminUser()) {
            return $this->_helper->redirector->gotoRoute(array('controller' => 'my-account', 'action' => 'compare'), 'default', true);
        }
        if ($this->auth->hasIdentity()) {
            $this->view->user = new Application_Model_User();
            $this->view->user->find($this->auth->getIdentity()->id);
        }
        $plans = new Application_Model_Plan();
        $versions = new Application_Model_Version();
        $this->view->plans = $plans->fetchAll();
        $this->view->versions = $versions->fetchAll();
        $this->renderScript('my-account/compare.phtml');
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

    /**
     * @return bool
     */
    protected function _isLoggedNotAdminUser()
    {
        return $this->auth->hasIdentity() && $this->auth->getIdentity()->id && Zend_Auth::getInstance()->getIdentity()->group != 'admin';
    }
}