<?php

class Application_Form_UserEdit extends Integration_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');
        $this->setAttrib('id', 'user-edit-form');

        // Add a firstname element
        $this->addElement('text', 'firstname', array(
                'label'      => 'First name',
                'tabindex'   => 1,
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
        // Add a lastname element
        $this->addElement('text', 'lastname', array(
                'label'      => 'Last name',
                'tabindex'   => 2,
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
                'tabindex'   => 3,
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                        new Zend_Validate_EmailAddress()
                ),
                'class'      => 'span4'
        ));

        // Add a password element
        $this->addElement('password', 'password', array(
                'label'      => 'Password',
                'tabindex'   => 4,
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(6, 45)),
                ),
                'allowEmpty' => true,
                'class'      => 'span4'
        ));

        // Add a repeated password element
        $this->addElement('password', 'password_repeat', array(
                'label'      => 'Repeat Password',
                'tabindex'   => 5,
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(6, 45)),
                        new Zend_Validate_Identical('password'),
                ),
                'allowEmpty' => true,
                'class'      => 'span4'
        ));

        // Add a server element
        $this->addElement('select', 'server_id', array(
                'label'      => 'Server',
                'tabindex'   => 6,
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'class'      => 'span4'
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'tabindex' => 7,
                'ignore'   => true,
                'label'    => 'Save',
        ));

        $this->_setDecorators();

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');

    }


}

