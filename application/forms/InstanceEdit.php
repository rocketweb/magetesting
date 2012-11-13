<?php

class Application_Form_InstanceEdit extends Integration_Form{

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
                'readonly' => true
        ));

        $this->addElement('text', 'backend_password', array(
                'label'    => 'Backend password',
                'ignore'   => true,
                'readonly' => true
        ));

        // Add the submit button
        $this->addElement('submit', 'instanceSave', array(
                'ignore'   => true,
                'label'    => 'Save',
        ));

        $this->_setDecorators();

        $this->instanceSave->removeDecorator('HtmlTag');
        $this->instanceSave->removeDecorator('overall');
        $this->instanceSave->setAttrib('class','btn btn-primary');
        $this->instanceSave->removeDecorator('Label');

    }

}

