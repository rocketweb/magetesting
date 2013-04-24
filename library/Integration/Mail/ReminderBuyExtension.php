<?php

class Integration_Mail_ReminderBuyExtension extends Integration_Mail
{
    protected $_template = '_emails/reminder-buy-extension.phtml';
    
    protected $_config;
    protected $_data;

    public function __construct(){
        parent::__construct();
    }
    
    public function setup($config, $data){
        $this->_config = $config;
        $this->_data = $data;
        
        $this->_setHeaders();
        $this->_setView();
        $this->_setBody();
    }
    
    protected function _setHeaders(){
        $current = current($this->_data);
        $from = $this->_config->from;
        
        $this->mail->setFrom($from->email, $from->desc);
        $this->mail->addTo($current['email']);
        $extensions_to_buy = array();
        foreach($this->_data as $ext_row) {
            $extensions_to_buy[] = $ext_row['name'];
        }
        $this->mail->setSubject(sprintf($this->_config->subject, implode(', ', $extensions_to_buy)));
        $this->mail->setReplyTo( $from->email, $from->desc );
        $this->mail->setReturnPath($from->email);
    }
    
    protected function _setView(){
        $this->view = Zend_Layout::getMvcInstance()->getView();
        $this->view->data = $this->_data;
        
        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('config');
        $this->view->storeUrl = $config->magento->storeUrl;
    }
    
    protected function _setBody(){
        /*TODO: check if this line is necessary*/
        $this->view->addScriptPath(APPLICATION_PATH. '/views/scripts');
        
        $msg = $this->view->render($this->_template);
        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));
    }

}