<?php

class Integration_Mail_Contact extends Integration_Mail
{
    protected $_template = '_emails/data-contact.phtml';
    
    protected $_data;
    protected $_config;

    public function __construct() {
        parent::__construct();
    }
    
    public function setup($appConfig, $data){
        $this->_config = $appConfig;
        $this->_formData = $data['formData'];
        
        $this->_setHeaders($data['formData']);
        $this->_setView();
        $this->_setBody();
    } 
    
    protected function _setHeaders($formData){
        $from = $this->_config->contact->message->from;
        $this->mail->setFrom($from->email, $from->desc);
        $emails = $this->_config->contact->message->emails;
        $emails = !is_object($emails)
                  ? array()
                  : $emails->toArray();
        $this->mail->addTo(array_shift($emails));
        if($emails) {
            foreach($emails as $ccEmail) {
                $this->mail->addCC($ccEmail);
            }
        }

        $this->mail->setSubject($this->_config->contact->message->subject);
        $this->mail->setReplyTo( $formData->sender_email, $formData->sender_name );
        $this->mail->setReturnPath($from->email);
    }
    
    protected function _setView(){
        $this->view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
        $this->view->subject = $this->_formData->subject;
        $this->view->name = $this->_formData->sender_name;
        $this->view->email = $this->_formData->sender_email;
        $this->view->message = $this->_formData->message;
        
        $this->view->storeUrl = $this->_config->magento->storeUrl;
    }
    
    protected function _setBody(){
        $msg = $this->view->render($this->_template);

        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));
    }
}