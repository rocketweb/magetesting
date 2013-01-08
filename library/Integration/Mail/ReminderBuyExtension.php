<?php

class Integration_Mail_ReminderBuyExtension
{
    protected $data;
    protected $view;
    protected $mail;

    public function __construct($config, $data)
    {
        $this->data = $data;
        $current = current($this->data);
        
        $from = $config->from;

        //headers set up here
        $this->mail = new Zend_Mail('utf-8');
        $this->mail->setFrom($from->email, $from->desc);
        $this->mail->addTo($current['email']);
        $this->mail->setSubject($config->subject);
        $this->mail->setReplyTo( $from->email, $from->desc );
        $this->mail->setReturnPath($from->email);

        $this->view = Zend_Layout::getMvcInstance()->getView();
    }

    public function send()
    {
        $this->view->data = $this->data;
        $this->view->addScriptPath(APPLICATION_PATH. '/views/scripts');

        //body setup here
        $msg = $this->view->render('_emails/reminder-buy-extension.phtml');

        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));

        $result =  $this->mail->send();
        return $result;
    }
    
    public function getMail() {
        return $this->mail;
    }

}