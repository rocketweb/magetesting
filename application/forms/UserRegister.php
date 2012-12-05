<?php

class Application_Form_UserRegister extends Integration_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked form-register');

        // Add a login element
        $regex = new Zend_Validate_Regex("/^[a-z0-9_-]+$/i");
        $regex->setMessage('Allowed chars: a-z, digits, dash, underscore', 'regexNotMatch');
        $this->addElement('text', 'login', array(
                'label'      => 'Username',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                    /**
                     * 13 used because of mysql username limit 
                     * 13 + 3 from (magento.userprefix)
                     */ 
                        array('validator' => 'StringLength', 'options' => array(3, 13)),
                    $regex
                ),
                'class'      => 'span4'
        ));

        // Add a password element
        $this->addElement('password', 'password', array(
                'label'      => 'Password',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(6, 45)),
                ),
                'allowEmpty' => true,
                'class'      => 'span4'
        ));

        // Add a password element
        $this->addElement('password', 'password_repeat', array(
                'label'      => 'Repeat password',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_Identical('password'),
                ),
                'class'      => 'span4'
        ));

        // Add a firstname element
        $this->addElement('text', 'firstname', array(
                'label'      => 'First name',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                        new Zend_Validate_Alpha()
                ),
                'class'      => 'span4'
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
                ),
                'class'      => 'span4'
        ));

        // Add a email element
        $this->addElement('text', 'email', array(
                'label'      => 'E-mail',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                        new Zend_Validate_EmailAddress()
                ),
                'class'      => 'span4'
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'ignore'   => true,
                'label'    => 'Register',
        ));

        $this->login->setAttribs(array('autocomplete'=>'off'));
        $this->password->setAttribs(array('autocomplete'=>'off'));

        $this->_setDecorators();

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');

    }


}

