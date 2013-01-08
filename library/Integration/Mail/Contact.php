<?php

class Integration_Mail_Contact extends Integration_Mail
{
    protected $_template = '_emails/data-contact.phtml';
    
    protected $_data;
    protected $_config;
    
    public function __construct() {
        parent::__construct();
    }
    
    public function setup($mailConfig, $data){
        $this->_config = $mailConfig;
        $this->_formData = $data['formData'];
        
        $this->_setHeaders();
        $this->_setView();
        $this->_setBody();
    } 
    
    protected function _setHeaders(){     
        $from = $this->_config->from;
        $this->mail->setFrom($from->email, $from->desc);
        $this->mail->addTo($this->_config->email);
        $this->mail->setSubject($this->_config->subject);
        $this->mail->setReplyTo( $from->email, $from->desc );
        $this->mail->setReturnPath($from->email);
    }
    
    protected function _setView(){
        $this->view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
        $this->view->subject = $this->_formData->subject;
        $this->view->name = $this->_formData->sender_name;
        $this->view->email = $this->_formData->sender_email;
        $this->view->message = $this->_formData->message;
    }
    
    protected function _setBody(){
        $msg = $this->view->render($this->_template);

        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));
    }
}