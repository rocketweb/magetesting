<?php

class IndexController extends Integration_Controller_Action
{

    public function init()
    {

    }

    public function indexAction()
    {
        var_dump('initsql');
        // action body
        $auth = $this->auth->getIdentity();
        $this->view->user_logged = isset($auth->id) AND $auth->id ? true : false;
    }

}