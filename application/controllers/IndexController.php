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

                $mailData = $this->getInvokeArg('bootstrap')
                                 ->getResource('config')
                                 ->contact
                                 ->message;
                
                $mail = new Integration_Mail_Contact($mailData, (object)$formData);

                try {
                    $mail->send();
                } catch (Zend_Mail_Transport_Exception $e){
                    $log = $this->getInvokeArg('bootstrap')->getResource('log');
                    $log->log('Contact - Unable to send email', Zend_Log::CRIT, json_encode($e->getTraceAsString()));
                }
                
                $this->_helper->FlashMessenger(array(
                    'message' => 'You succeffully sent your message.',
                    'type'    => 'success',
                ));
                
                return $this->_helper->redirector->gotoRoute(array(), 'contact', true);
            }
        }
        
        $this->view->form = $form;
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