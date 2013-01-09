<?php

class Integration_Mail {
    
    protected $mail;
    protected $view;
        
    public function __construct() {
        $this->mail = $this->mail = new Zend_Mail('utf-8');
    } 
      
    public function send()
    {
        return $this->mail->send();
    }
    
    public function getMail() {
        return $this->mail;
    }
    
    
}