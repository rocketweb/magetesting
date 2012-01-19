<?php

class IndexController extends Integration_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
        $this->view->queue = Application_Model_Queue::getAll();
    }


}

