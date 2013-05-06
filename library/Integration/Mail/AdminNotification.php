<?php

class Integration_Mail_AdminNotification extends Integration_Mail
{
    protected $_template;
    protected $_mailType;
    protected $_config;

    public function __construct() {
        parent::__construct();
    }
    
    public function setup($mailType, $data){
        $this->_config =
            Zend_Controller_Front::getInstance()
                ->getParam('bootstrap')
                ->getResource('config')
                ->adminNotification;

        $this->_mailType = $mailType;
        $this->_template = '_emails/admin_notification/'
            . $this->_config->{$mailType}->template;
        
        $this->_setHeaders();
        $this->_setView($data);
        $this->_setBody();
    }
    
    protected function _setHeaders(){
        $globalEmails = $this->_config->globalEmails;
        $globalEmails = !is_object($globalEmails)
                            ? array()
                            : $globalEmails->toArray();
        $specificEmails = $this->_config->{$this->_mailType}->emails;
        $specificEmails = !is_object($specificEmails)
                            ? array()
                            : $specificEmails->toArray();
        $emails = array_unique(
            array_merge(
                $globalEmails,
                $specificEmails
            )
        );
        if($emails) {
            $this->mail->addTo(array_shift($emails));
        }
        if($emails) {
            foreach($emails as $ccEmail) {
                $this->mail->addCc($ccEmail);
            }
        }

        $from = $this->_config->from;
        $this->mail->setFrom( $from->email, $from->desc );
        $this->mail->setSubject($this->_config->{$this->_mailType}->subject);
        $this->mail->setReplyTo( $from->email, $from->desc );
        $this->mail->setReturnPath($from->email);
    }
    
    protected function _setView($data){
        /**
         * TODO: check if it is faster than 
         * $this->view = Zend_Layout::getMvcInstance()->getView();
         */
        $this->view = Zend_Controller_Front::getInstance()
                        ->getParam('bootstrap')->getResource('view');
        if(is_array($data)) {
            foreach($data as $key => $value) {
                if(is_string($key)) {
                    $this->view->$key = $value;
                }
            }
        }
    }
    
    protected function _setBody(){
        $msg = $this->view->render($this->_template);

        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));
    }
}