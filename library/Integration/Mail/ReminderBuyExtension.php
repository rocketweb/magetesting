<?php

class Integration_Mail_ReminderBuyExtension
{
    protected $data;
    protected $view;
    protected $mail;

    public function __construct($config, $data)
    {
        $this->data = $data;

        $from = $config->from;

        //headers set up here
        $this->mail = new Zend_Mail('utf-8');
        $this->mail->setFrom($from->email, $from->desc);
        $this->mail->addTo($this->data->email);
        $this->mail->setSubject($config->subject);

        $this->view = Zend_Layout::getMvcInstance()->getView();
    }

    public function send()
    {
        $this->view->firstname = $this->data->firstname;
        $this->view->extension_id = $this->data->extension_id;
        $this->view->domain = $this->data->domain;
        $this->view->url = $this->data->url;
        $this->view->addScriptPath(APPLICATION_PATH. '/views/scripts');

        //body setup here
        $msg = $this->view->render('_emails/reminder-buy-extension.phtml');

        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));

        $result =  $this->mail->send();
        return $result;
    }


}