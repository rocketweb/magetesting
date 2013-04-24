<?php

class Integration_Mail_UserResetPassword extends Integration_Mail
{
    protected $_template = '_emails/user-reset-password.phtml';
    protected $_userObject;
    protected $_config;

    public function __construct() {
        parent::__construct();
    }
    
    public function setup($mailConfig, $data){
        $this->_config = $mailConfig;
        $this->_userObject = $data['user'];
        
        $this->_setHeaders();
        $this->_setView();
        $this->_setBody();
    }
    
    protected function _setHeaders(){
        $from = $this->_config->from;
        $this->mail->setFrom( $from->email, $from->desc );
        $this->mail->addTo($this->_userObject->getEmail());
        $this->mail->setSubject($this->_config->subject);
        $this->mail->setReplyTo( $from->email, $from->desc );
        $this->mail->setReturnPath($from->email);
    }
    
    protected function _setView(){
        /**
         * TODO: check if it is faster than 
         * $this->view = Zend_Layout::getMvcInstance()->getView();
         */
        $this->view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
        
        $resetUrl = $this->view->url(
            array(
                'controller' => 'user',
                'action'     => 'set-new-password',
                'id'         => $this->_userObject->getId(),
                'key'        => sha1($this->_userObject->getAddedDate().$this->_userObject->getPassword()),
            )
        );
        $this->view->resetLink = $this->view->serverUrl().$resetUrl;
        $this->view->login  = $this->_userObject->getLogin();
        
        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('config');
        $this->view->storeUrl = $config->magento->storeUrl;
    }
    
    protected function _setBody(){
        $msg = $this->view->render($this->_template);

        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));
    }
}
