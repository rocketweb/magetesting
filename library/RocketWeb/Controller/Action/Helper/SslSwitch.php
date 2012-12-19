<?php
/**
 * Action controller helper for SSL redirecting.
 * 
 * @category   RocketWeb
 * @package    RocketWeb_Controller
 * @subpackage Action_Helper
 * @copyright  Copyright (c) 2012 RocketWeb USA Inc. (http://www.rocketweb.com)
 * @author     Marcin Kazimierczak <marcin@rocketweb.com>
 */
class RocketWeb_Controller_Action_Helper_SslSwitch extends Zend_Controller_Action_Helper_Abstract {
    
    /**
     * Is active == true, redirect to https
     * 
     * @param boolean $active 
     */
    public function direct($active = true) {
        $active = (boolean)$active;
        $enable = (boolean)$this->getActionController()
                                ->getInvokeArg('bootstrap')
                                ->getResource('config')
                                ->ssl
                                ->active;

        if(!$enable) {
            return false;
        }
        
        $request = $this->getRequest();

        if($active && (!isset($_SERVER['HTTPS']) || !$_SERVER['HTTPS'])) {
            $address = 'https://';
        } else if(!$active && isset($_SERVER['HTTPS'])) {
            $address = 'http://';
        } else {
            return false;
        }
        
        $address .= $request->getHttpHost() . $request->getRequestUri();
        $redirect = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        
        return $redirect->gotoUrl($address);
    }
}