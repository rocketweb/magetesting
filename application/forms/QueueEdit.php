<?php

class Application_Form_QueueEdit extends Integration_Form{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');

        $this->addElement('text', 'instance_name', array(
                'label'      => 'Name or note',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
        ));

        $this->addElement('text', 'backend_login', array(
                'label'    => 'Backend login',
                'ignore'   => true,
                'disabled' => true
        ));

        $this->addElement('text', 'backend_password', array(
                'label'    => 'Backend password',
                'ignore'   => true,
                'disabled' => true
        ));

        // Add the submit button
        $this->addElement('submit', 'queueSave', array(
                'ignore'   => true,
                'label'    => 'Save',
        ));

        $this->_setDecorators();

        $this->queueSave->removeDecorator('HtmlTag');
        $this->queueSave->removeDecorator('overall');
        $this->queueSave->setAttrib('class','btn btn-primary');
        $this->queueSave->removeDecorator('Label');

    }

}

