<?php

class Integration_Mail_UserRegisterActivation extends Integration_Mail
{
    protected $_template = '_emails/user-register-activation.phtml';
    
    protected $_userObject;
    protected $_config;
    protected $_ppid;

    public function __construct()
    {
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
        $this->view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
        $activationUrlParams = array();
        $string_to_hash = $this->_userObject->getLogin().$this->_userObject->getEmail().$this->_userObject->getAddedDate();

        $activationUrlParams += array(
            'controller' => 'user',
            'action'     => 'activate',
            'id'         => $this->_userObject->getId(),
            'hash'       => substr(sha1($string_to_hash),0,20)
        );
        $activationUrl = $this->view->url(
            $activationUrlParams
        );

        $this->view->activationLink = $this->view->serverUrl().$activationUrl;
    }

    protected function _setBody(){
        $msg = $this->view->render($this->_template);
        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));
    }
}