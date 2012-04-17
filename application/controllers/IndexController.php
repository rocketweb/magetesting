<?php

class IndexController extends Integration_Controller_Action
{

    public function init()
    {

    }

    public function indexAction()
    {
        // action body
        $this->view->user_logged = $this->auth->getIdentity()->id ? true : false;
    }

}