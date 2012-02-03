<?php

class Application_Form_QueueClose extends Integration_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');
        //TODO: move model usage to controller

        $this->addElement('hidden', 'close', array(
                'value' => 1
        ));

         
        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'ignore'   => true,
                'label'    => 'Yes',
        ));

        $this->_setDecorators();

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');
        $this->submit->setAttrib('class','btn btn-primary');

    }

    public function changeToNoForm()
    {
        $this->close->setValue(0);
        $this->submit->setLabel('No');
        $this->submit->setAttrib('class','btn');
    }
    
}
