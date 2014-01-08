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
                        array('validator' => 'StringLength', 'options' => array(3, 45)),
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
        
        $planModel = new Application_Model_Plan();
        $plans = array();
        foreach($planModel->fetchAll() as $plan) {
            $plans[$plan->getId()] = $plan->getName();
        }
        $this->plan_id->addValidator(
            new Zend_Validate_InArray(array_keys($plans))
        );

        $this->plan_id->addMultiOptions(array('' => '') + $plans);

        $check_duration_callback = new Zend_Validate_Callback(array('callback' => array($this, 'check_duration')));
        $check_duration_callback->setMessage('\'%value%\' is not valid duration format.', Zend_Validate_Callback::INVALID_VALUE);
        // Add duration element
        $this->addElement('text', 'duration', array(
                'label'      => 'Duration',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 20)),
                        $check_duration_callback,
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
                'class'      => 'span4 datepicker'
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'ignore'   => true,
                'label'    => 'Save'
        ));

        // setters for class have to be before setdecorators method call which adds class and not overwrite them
        $this->_setDecorators();

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('Overall');
        $this->submit->setAttrib('class', 'btn btn-inverse pull-right');
    }

    public function check_duration($value) {
        if('1970-01-01' == date('Y-m-d', strtotime($value))) {
            return false;
        }

        return true;
    }

    public function disableFields() {
        foreach($this->getElements() as $key => $element) {
            $element->setAttribs(array('disabled' => 'disabled'));
        }
    }

    public function addUniqueCodeValidator() {
        $this->code->addValidator(new Zend_Validate_Db_NoRecordExists(array(
                'table' => 'coupon',
                'field' => 'code'
        )));
    }
}