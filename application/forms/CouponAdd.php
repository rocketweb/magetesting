<?php

/**
 * Creates form fields for adding coupons
 * 
 * @access public
 * @author Grzegorz( golaod )
 * @method init - auto called
 * @package Application_Form_CouponAdd
 */
class Application_Form_CouponAdd extends Application_Form_CouponEdit
{
    /**
     * @author Grzegorz Gasiecki<grzegorz@rocketweb.com>
     */
    public function init() {
        parent::init();
        
        $this->setLegend('Add Coupon');
    }
}