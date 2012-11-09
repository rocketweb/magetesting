<?php

/**
 * Creates form fields for adding supported extensions
 * 
 * @access public
 * @author Grzegorz( golaod )
 * @method init - auto called
 * @package Application_Form_ExtensionEdit
 */
class Application_Form_ExtensionEdit extends Integration_Form
{

    public function init()
    {
        // Add title element
        $this->addElement('text', 'title', array(
                'label'      => 'Title',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 45))
                ),
                'allowEmpty' => false
        ));

        // Add description element
        $this->addElement('textarea', 'description', array(
                'label'      => 'Description',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 500))
                ),
                'allowEmpty' => false
        ));

        // Add description element
        $this->addElement('hidden', 'directory_hash', array(
                'id'         => 'directory_hash',
                'label'      => 'Directory hash',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(24)),
                        array('validator' => 'Regex', 'options' => array('/^\d{10}\-[a-z0-9]{13}$/i'))
                ),
                'allowEmpty' => false
        ));

        // Add price element
        $this->addElement('text', 'price', array(
                'label'      => 'Price',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(1, 10)),
                        array('validator' => 'Float', 'options' => array('locale' => 'en')),
                ),
                'allowEmpty' => false
        ));

        // Add logo element
        $this->addElement('hidden', 'logo', array(
                'label'      => 'Logo',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => false
        ));
        $this->addElement('hidden', 'old_logo', array(
                'ignore'   => true,
                'label'    => 'Old logo',
        ));

        // Add screenshots element
        $this->addElement('hidden', 'screenshots', array(
                'label'      => 'Screenshot',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => false
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'form'     => 'extension-form',
                'ignore'   => true,
                'label'    => 'Save changes',
        ));

        $this->title->setAttrib('class', 'span9');
        $this->description->setAttrib('class', 'span9');
        $this->description->setAttrib('rows', '5');
        $this->price->setAttrib('class', 'span2');

        // setters for class have to be before setdecorators method call which adds class and not overwrite them
        $this->_setDecorators();

        $this->old_logo->removeDecorator('HtmlTag');
        $this->old_logo->removeDecorator('Overall');
        $this->directory_hash->removeDecorator('HtmlTag');
        $this->directory_hash->removeDecorator('Overall');
        $this->directory_hash->removeDecorator('Label');
        $this->directory_hash->removeDecorator('Errors');
        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('Overall');

    }
}