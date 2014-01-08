<?php

/**
 * Creates form fields for editing existing plans
 * 
 * @access public
 * @author Grzegorz( golaod )
 * @method init - auto called
 * @package Application_Form_PlanEdit
 */
class Application_Form_PlanEdit extends Integration_Form
{

    public function init()
    {
        $this->setLegend('Edit Plan');

        // Add name element
        $this->addElement('text', 'name', array(
                'label'      => 'Plan Name',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 45)),
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add stores element
        $this->addElement('text', 'stores', array(
                'label'      => 'Stores',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'Int'),
                        array('validator' => 'StringLength', 'options' => array(1, 3)),
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add price element
        $this->addElement('text', 'price', array(
                'label'      => 'Price',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'Float', 'options' => array('locale' => 'en_US'))
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));
        // Add price description element
        $this->addElement('text', 'price_description', array(
                'label'      => 'Price description',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 20)),
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add billing period element
        $this->addElement('text', 'billing_period', array(
                'label'      => 'Billing period',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 20)),
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));
        // Add billing description element
        $this->addElement('text', 'billing_description', array(
                'label'      => 'Billing description',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 20)),
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add stores element
        $this->addElement('text', 'max_stores', array(
            'label'      => 'Max amount of additional stores',
            'required'   => true,
            'filters'    => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('validator' => 'Int'),
                array('validator' => 'StringLength', 'options' => array(1, 3)),
            ),
            'allowEmpty' => false,
            'class'      => 'span4'
        ));

        // Add price element
        $this->addElement('text', 'store_price', array(
            'label'      => 'Additional store price',
            'required'   => true,
            'filters'    => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('validator' => 'Float', 'options' => array('locale' => 'en_US'))
            ),
            'allowEmpty' => false,
            'class'      => 'span4'
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