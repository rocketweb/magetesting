<?php

class Integration_Mail_QueueItemReady
{
    protected $_mail;
    protected $_user;
    private $template;
    private $templateVars;

    public function __construct($config, $view, $user)
    {
        $this->_user = $user;
        $this->_view = $view;

        //headers set up here
        $mail = $this->_mail = new Zend_Mail('utf-8');
        $mail->setFrom(
            $config->cron->queueItemReady->from->email,
            $config->cron->queueItemReady->from->desc
        );
        $mail->addTo($user->getEmail());
        $mail->setSubject($config->cron->queueItemReady->subject);
    }

    /**
     * path to template relative to views/scripts e.g:
     * '_emails/order-fulfillment-assignment.phtml'
     * @param type $template
     */
    public function setTemplate($template){
        $this->template = $template;
    }

    /**
     * Variables used in used template
     * @param type $vars
     */
    public function setTemplateVars($vars){
        $this->templateVars = $vars;
    }

    public function send()
    {

        $emailBody = clone $this->_view;
        $emailBody->user = $this->_user;
        $emailBody->domain = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';

        foreach ($this->templateVars as $key => $var){
            if (!isset($this->$key))
                $emailBody->$key = $var;
        }

        //body setup here
        $msg = $emailBody->render($this->template);
        $this->_mail->setBodyHtml($msg);
        $this->_mail->setBodyText(strip_tags($msg));

        if ($this->_user->getStatus()=='inactive'){
            return false;
        }
        $result =  $this->_mail->send();
        return $result;
    }


}
