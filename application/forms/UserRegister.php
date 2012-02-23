<?php

class Application_Form_UserRegister extends Integration_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');

        // Add a login element
        $this->addElement('text', 'login', array(
                'label'      => 'Username',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 255)),
                ),
        ));

        // Add a password element
        $this->addElement('password', 'password', array(
                'label'      => 'Password',
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
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_Identical('password'),
                ),
        ));

        // Add a firstname element
        $this->addElement('text', 'firstname', array(
                'label'      => 'First name',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                        new Zend_Validate_Alpha()
                )
        ));

        $regex = new Zend_Validate_Regex("/^[a-z' -]+$/i");
        $regex->setMessage('Allowed chars: a-z, space, dash, apostrophe', 'regexNotMatch');
        // Add a firstname element
        $this->addElement('text', 'lastname', array(
                'label'      => 'Last name',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                        $regex
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
                'label'    => 'Register',
        ));

        // Add the reset button
        $this->addElement('reset', 'reset', array(
                'ignore'   => true,
                'label'    => 'Clear form',
                'class'    => 'btn'
        ));

        $this->login->setAttribs(array('autocomplete'=>'off'));
        $this->password->setAttribs(array('autocomplete'=>'off'));

        $this->_setDecorators();

        $this->reset->removeDecorator('HtmlTag');
        $this->reset->removeDecorator('overall');
        $this->reset->removeDecorator('label');

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');

    }


}

