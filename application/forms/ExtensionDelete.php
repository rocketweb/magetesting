<?php

/**
 * Creates form fields for deleting news
 * 
 * @access public
 * @author Grzegorz( golaod )
 * @method init - auto called
 * @package PressReleases_Form_NewsDelete
 */
class Application_Form_ExtensionDelete extends Integration_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');

        // Add the submit button
        $this->addElement('submit', 'submit1', array(
                'ignore'   => false,
                'label'      => 'Yes',
                'name'    => 'submit',
        ));
         // Add the submit button
        $this->addElement('submit', 'submit2', array(
                'ignore'   => false,
                'label'      => 'No',
                'name'    => 'submit'
        ));

        $this->_setDecorators();

        $this->submit1->removeDecorator('HtmlTag');
        $this->submit1->removeDecorator('overall');
        $this->submit1->removeDecorator('Label');
        $this->submit1->setAttrib('class','btn btn-primary');
        $this->submit2->removeDecorator('HtmlTag');
        $this->submit2->removeDecorator('overall');
        $this->submit2->setAttrib('class','btn');
        $this->submit2->removeDecorator('Label');
    }
    
}
