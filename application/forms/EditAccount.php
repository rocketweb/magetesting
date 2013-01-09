<?php
/**
 * Form to change details information in my-account/edit-account
 * @author Grzegorz (golaod)
 * @method Application_Form_EditAccount
 *
 */
class Application_Form_EditAccount extends Integration_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked form-input-select');
        $this->setAttrib('id', 'edit-account-form');

        // Add a firstname element
        $this->addElement('text', 'firstname', array(
                'label'      => 'First name',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 50)),
                        new Zend_Validate_Alpha()
                ),
                'class'      => 'span4'
        ));

        $regex = new Zend_Validate_Regex("/^[a-z' -]+$/i");
        $regex->setMessage('Allowed chars: a-z, space, dash, apostrophe', 'regexNotMatch');
        // Add a firstname element
        $a=$this->addElement('text', 'lastname', array(
                'label'      => 'Last name',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 50)),
                        $regex
                ),
                'class'      => 'span4'
        ));
        
        // Add a street element
        $this->addElement('text', 'street', array(
                'label'      => 'Street',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 50)),
                ),
                'class'      => 'span4'
        ));
        
        // Add a postal code element
        $this->addElement('text', 'postal_code', array(
                'label'      => 'Postal Code',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 10)),
                ),
                'class'      => 'span4'
        ));

        $states = RocketWeb_List::getStatesOfAmerica();
        $states = array_merge(array('Select State'), $states);
        // Add a state element
        $this->addElement('select', 'state', array(
                'label'      => 'State',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                ),
                'class'      => 'select-state span4',
                'multiOptions' => array_combine($states, $states),
        ));
        
        // Add a city element
        $this->addElement('text', 'city', array(
                'label'      => 'City',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 50)),
                ),
                'class'      => 'span4'
        ));

        $countries = RocketWeb_List::getCountries();
        // Add a city element
        $this->addElement('select', 'country', array(
                'label'      => 'Country',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                ),
                'class'      => 'select-country span4',
                'multiOptions' => array_combine($countries, $countries),
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'ignore'   => true,
                'label'    => 'Save',
        ));
        
        $regex = new Zend_Validate_Regex("/^[a-z0-9_-]+$/i");
        $regex->setMessage('Allowed chars: a-z, digits, dash, underscore', 'regexNotMatch');
        $this->addElement('text', 'login', array(
                'label'      => 'Username',
                'disabled'   => true,
                'required'   => false,
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
                'required'   => false,
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
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_Identical('password'),
                ),
                'class'      => 'span4'
        ));
        
        // Add a email element
        $this->addElement('text', 'email', array(
                'label'      => 'E-mail',
                'required'   => false,
                'disabled'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                        new Zend_Validate_EmailAddress()
                ),
                'class'      => 'span4'
        ));

        $this->_setDecorators();

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');
        $this->state->removeDecorator('Label');
        $this->state->removeDecorator('overall');
        $this->state->removeDecorator('HtmlTag');
    }


}

