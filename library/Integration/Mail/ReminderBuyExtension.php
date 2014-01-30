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
        $from = $this->_config->cron->buyStoreExtension->from;
        
        $this->mail->setFrom($from->email, $from->desc);
        $this->mail->addTo($current['email']);
        $extensions_to_buy = array();
        foreach($this->_data as $ext_row) {
            $extensions_to_buy[] = $ext_row['name'];
        }
        
        //this prevents too long subjects
        if (count($extensions_to_buy) > 3) {
            $extensions_to_buy = array_slice($extensions_to_buy, 0, 3);
            $this->mail->setSubject(sprintf($this->_config->cron->buyStoreExtension->subject, implode(', ', $extensions_to_buy) . ' and more'));
        } else {
            $this->mail->setSubject(sprintf($this->_config->cron->buyStoreExtension->subject, implode(', ', $extensions_to_buy)));
        }
        
        
        $this->mail->setReplyTo( $from->email, $from->desc );
        $this->mail->setReturnPath($from->email);
    }
    
    protected function _setView(){
        $this->view = Zend_Layout::getMvcInstance()->getView();
        $this->view->data = $this->_data;
        
        $this->view->storeUrl = rtrim($this->_config->magento->storeUrl, '/');
    }
    
    protected function _setBody(){
        /*TODO: check if this line is necessary*/
        $this->view->addScriptPath(APPLICATION_PATH. '/views/scripts');
        
        
        try{
            $msg = $this->view->render($this->_template);
        } catch(Zend_View_Exception $e) {
            $this->logger->log('Extension Buy Reminder mail could not be rendered.', Zend_Log::CRIT, $e->getTraceAsString());
        }
                
        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));
    }

}