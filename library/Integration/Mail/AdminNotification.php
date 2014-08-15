<?php

class Integration_Mail_AdminNotification extends Integration_Mail
{
    protected $_template;
    protected $_mailType;
    protected $_config;
    protected $_serverUrl;

    public function __construct() {
        parent::__construct();
    }
    
    public function setup($mailType, $data, $view = null){
        $config = new Zend_Config_Ini(
                APPLICATION_PATH . '/configs/local.ini',
                APPLICATION_ENV
        );
        $this->_serverUrl = $config->magento->storeUrl;
        $this->_config =
            $config->adminNotification;
        unset($config);

        $this->_mailType = $mailType;
        $this->_template = '_emails/admin_notification/'
            . $this->_config->{$mailType}->template;
        
        $this->_setHeaders();
        $this->_setView($data, $view);
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
    
    protected function _setView($data, $view = null){
        if ($view instanceof Zend_View_Abstract) {
            $this->view = $view;
        } else {
            $this->view = Zend_Layout::getMvcInstance()->getView();
        }

        $this->view->serverUrl = $this->_serverUrl;
        if(is_array($data)) {
            foreach($data as $key => $value) {
                if(is_string($key)) {
                    $this->view->$key = $value;
                }
            }
        }
    }
    
    protected function _setBody(){
        $this->view->addScriptPath(APPLICATION_PATH. '/views/scripts');
        $this->view->content = $this->view->render($this->_template);
        $msg = $this->view->render('_emails/admin_notification/layout.phtml');

        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));
    }
}