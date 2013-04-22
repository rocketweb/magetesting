<?php

/**
 * Creates form fields for adding plans
 * 
 * @access public
 * @author Grzegorz( golaod )
 * @method init - auto called
 * @package Application_Form_PlanAdd
 */
class Application_Form_PlanAdd extends Application_Form_PlanEdit
{
    /**
     * @author Grzegorz Gasiecki<grzegorz@rocketweb.com>
     */
    public function init() {
        parent::init();
        
        $this->setLegend('Add Plan');
    }
}