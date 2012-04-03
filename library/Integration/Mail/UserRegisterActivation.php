<?php

class Integration_Mail_UserRegisterActivation
{

    protected $user;
    protected $view;
    protected $mail;

    public function __construct($config, $user)
    {
        $this->user = $user;

        $from = $config->from;
        //headers set up here
        $mail = $this->mail = new Zend_Mail('utf-8');
        $mail->setFrom( $from->mail, $from->desc );
        $mail->addTo($user->getEmail());
        $mail->setSubject($config->subject);

        $this->view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
    }

    public function send()
    {
        $activationUrl = $this->view->url(
            array(
                'controller' => 'user',
                'action'     => 'activate',
                'id'         => $this->user->getId(),
                'hash'       => sha1($this->user->getLogin().$this->user->getEmail().$this->user->getAddedDate())
            )
        );
        $this->view->activationLink = $this->view->serverUrl().$activationUrl;

        //body setup here
        $msg = $this->view->render('_emails/user-register-activation.phtml');
echo $msg;die;
        $this->mail->setBodyHtml($msg);
        $this->mail->setBodyText(strip_tags($msg));

        $result =  $this->mail->send();
        return $result;
    }


}
