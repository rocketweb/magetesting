<?php

/**
 * User can see and edit his data used in payment
 * @author Grzegorz(golaod)
 * @package MyAccountController
 */
class MyAccountController extends Integration_Controller_Action
{
    /**
     * My account dashboard with payment and plan data
     * @method indexAction
     */
    public function indexAction()
    {
        $this->view->user = $this->auth->getIdentity();
    }
}