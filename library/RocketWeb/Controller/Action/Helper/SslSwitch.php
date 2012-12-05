<?php

/**
 * Action controller helper for SSL redirecting.
 */
class RocketWeb_Controller_Action_Helper_SslSwitch extends Zend_Controller_Action_Helper_Abstract {
    
    public function direct() {
        if(!isset($_SERVER['HTTPS']) || !$_SERVER['HTTPS']) {
            $request = $this->getRequest();
            $address = 'https://' . $_SERVER['HTTP_HOST'] . $request->getRequestUri();
            $redirect = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
            $redirect->gotoUrl($address);
        }
    }
}