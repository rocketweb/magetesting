<?php

class Application_Form_UserResetPassword extends Integration_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-horizontal');

        // Add a login element
        $this->addElement('text', 'login', array(
                'label'      => 'Username:',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 255))
                )
        ));

        // Add a email element
        $this->addElement('text', 'email', array(
                'label'      => 'E-mail',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                        new Zend_Validate_EmailAddress()
                )
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

