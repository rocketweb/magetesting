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
        $this->setAttrib('class', 'form-stacked');

        // Add a firstname element
        $this->addElement('text', 'firstname', array(
                'label'      => 'First name',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 50)),
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
                        array('validator' => 'StringLength', 'options' => array(3, 50)),
                        $regex
                )
        ));

        
        // Add a street element
        $this->addElement('text', 'street', array(
                'label'      => 'Street',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 50)),
                ),
        ));
        
        // Add a postal code element
        $this->addElement('text', 'postal_code', array(
                'label'      => 'Postal Code',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 10)),
                ),
        ));

        
        // Add a state element
        $this->addElement('text', 'state', array(
                'label'      => 'State',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                ),
        ));
        
        // Add a city element
        $this->addElement('text', 'city', array(
                'label'      => 'City',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 50)),
                ),
        ));

        // Add a city element
        $this->addElement('text', 'country', array(
                'label'      => 'Country',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                ),
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'ignore'   => true,
                'label'    => 'Edit',
        ));

        // Add the reset button
        $this->addElement('reset', 'reset', array(
                'ignore'   => true,
                'label'    => 'Clear form',
                'class'    => 'btn'
        ));

        $this->_setDecorators();

        $this->reset->removeDecorator('HtmlTag');
        $this->reset->removeDecorator('overall');
        $this->reset->removeDecorator('label');

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');

    }


}

