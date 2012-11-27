<?php

class Application_Form_UserSetNewPassword extends Integration_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');
        // Add a password element

        $this->addElement('password', 'password', array(
                'label'      => 'Password',
                'class'      => 'span6',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(6, 45)),
                ),
                'allowEmpty' => true
        ));

        // Add a password element
        $this->addElement('password', 'password_repeat', array(
                'label'      => 'Repeat password',
                'class'      => 'span6',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_Identical('password'),
                ),
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'ignore'   => true,
                'label'    => 'Confirm',
        ));

        //$this->addDisplayGroup(array( 'login', 'submit'),
        //        'accountLogin', array('legend' => 'Your Login Details')
        //);

        $this->_setDecorators();

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');
    }


}

