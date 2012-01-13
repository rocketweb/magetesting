<?php

class UserController extends Zend_Controller_Action
{

    public function init()
    {
    	$this->user_session = new Zend_Session_Namespace( 'user_data' );
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    }
	
	public function loginAction()
    {
    	if( $this->reqies)
        echo 'ready to login';
    }

}

