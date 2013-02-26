<?php

/**
 * Creates form fields for editing existing coupons
 * 
 * @access public
 * @author Grzegorz( golaod )
 * @method init - auto called
 * @package Application_Form_CouponEdit
 */
class Application_Form_CouponEdit extends Integration_Form
{

    public function init()
    {
        $this->setLegend('Edit Coupon');

        // Add code element
        $this->addElement('text', 'code', array(
                'label'      => 'Coupon Code',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 45))
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add used date element
        $this->addElement('text', 'used_date', array(
                'label'      => 'Used Date',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'Date')
                ),
                'allowEmpty' => true,
                'class'      => 'span4'
        ));

        // Add user id element
        $this->addElement('select', 'user_id', array(
                'label'      => 'User',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'Digits')
                ),
                'allowEmpty' => true,
                'class'      => 'span4'
        ));
        // Add plan id element
        $this->addElement('select', 'plan_id', array(
                'label'      => 'Plan',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'Digits')
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add duration element
        $this->addElement('text', 'duration', array(
                'label'      => 'Duration',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 20))
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add active to element
        $this->addElement('text', 'active_to', array(
                'label'      => 'Active to',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'Date')
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'ignore'   => true,
                'label'    => 'Save changes'
        ));

        // setters for class have to be before setdecorators method call which adds class and not overwrite them
        $this->_setDecorators();

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('Overall');
        $this->submit->setAttrib('class', 'btn btn-inverse pull-right');
    }
}