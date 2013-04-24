<?php

class PaymentController extends Integration_Controller_Action {

    public function init() {
        /* Initialize action controller here */
        $this->_helper->sslSwitch();
    }

    public function indexAction() {
        # display grid of all payments
        /* index action is only alias for list extensions action */
        $this->listAction();
    }

    public function listAction() {
        $planModel = new Application_Model_Payment();
        $paginator = $planModel->fetchList();
        $page = (int) $this->_getParam('page', 0);
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);
        $this->view->payments = $paginator;
        $this->render('list');
    }
}