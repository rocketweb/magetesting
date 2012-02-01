<?php

class IndexController extends Integration_Controller_Action
{

    public function init()
    {

    }

    public function indexAction()
    {
        // action body
        $this->_determineTopMenu();
    }

}