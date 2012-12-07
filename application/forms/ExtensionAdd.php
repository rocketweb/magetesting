<?php

/**
 * Creates form fields for adding supported extensions
 * 
 * @access public
 * @author Grzegorz( golaod )
 * @method init - auto called
 * @package Application_Form_ExtensionAdd
 */
class Application_Form_ExtensionAdd extends Application_Form_ExtensionEdit
{
    /**
     * @author Marcin Kazimierczak <marcin@rocketweb.com>
     */
    public function init() {
        parent::init();
        
        $this->setLegend('Add Extension');
    }
}