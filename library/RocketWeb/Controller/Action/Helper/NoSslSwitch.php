<?php

/**
 * Action controller helper for no-SSL redirecting.
 */
class RocketWeb_Controller_Action_Helper_NoSslSwitch extends Zend_Controller_Action_Helper_Abstract {
    
    public function direct() {
        if(isset($_SERVER['HTTPS'])) {
            $request = $this->getRequest();
            $address = 'http://' . $_SERVER['HTTP_HOST'] . $request->getRequestUri();
            $redirect = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
            $redirect->gotoUrl($address);
        }
    }
}