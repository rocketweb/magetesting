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
        $queueModel = new Application_Model_Queue();
        $this->view->queue = $queueModel->getAll();
    }


}

