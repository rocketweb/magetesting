<?php

class Integration_Mail_Contact
{

    protected $data;
    protected $view;
    protected $mail;

    public function __construct($config, $data)
    {
        $this->data = $data;

        $from = $config->from;
        //headers set up here
        $mail = $this->mail = new Zend_Mail('utf-8');
        $mail->setFrom( $from->email, $from->desc );
        $mail->addTo($config->email);
        $mail->setSubject($config->subject);
        
        $this->view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
    }

    public function send()
    {
        $this->view->subject = $this->data->subject;
        $this->view->name = $this->data->sender_name;
        $this->view->email = $this->data->sender_email;
        $this->view->message = $this->data->message;

        //body setup here
        $msg = $this->view->render('_emails/data-contact.phtml');

        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));

        $result =  $this->mail->send();
        return $result;
    }


}