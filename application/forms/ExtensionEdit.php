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
        $this->setLegend('Edit Extension');

        // Add title element
        $this->addElement('text', 'title', array(
                'label'      => 'Title',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 45))
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add module namespace element
        $this->addElement('text', 'extension_key', array(
                'label'      => 'Extension key',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 255))
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add sort order element
        $this->addElement('text', 'sort', array(
                'label'      => 'Release sort order',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(1, 2)),
                        array('validator' => 'Int')
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add description element
        $this->addElement('textarea', 'description', array(
                'label'      => 'Description',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 500))
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add category element
        $this->addElement('select', 'category_id', array(
                'label'      => 'Extension Category',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));
        $this->category_id->addMultiOptions(array('' => 'Select Category:'));

        // Add Author element
        $this->addElement('text', 'author', array(
                'label'      => 'Author',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(1, 100))
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
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
                'allowEmpty' => false,
                'class'      => 'span4'
        ));

        // Add extension file element
        $this->addElement('file', 'extension_file', array(
                'label'      => 'Extension File',
                'required'   => false,
                'allowEmpty' => true,
                'validators' => array(
                        array('Extension', false, 'tgz,gz')
                ),
                'class'      => 'span4'
        ));
        // Add extension encoded file element
        $this->addElement('file', 'extension_encoded_file', array(
                'label'      => 'Extension Encoded File',
                'required'   => false,
                'allowEmpty' => true,
                'validators' => array(
                        array('Extension', false, 'tgz,gz')
                ),
                'class'      => 'span4'
        ));
        Zend_Validate_Abstract::setDefaultTranslator(new Zend_Translate('array', array(
            Zend_Validate_File_Extension::FALSE_EXTENSION => 'File \'%value%\' has an invalid extension'
        )));

        // Add logo element
        $this->addElement('hidden', 'logo', array(
                'label'      => 'Logo',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => true
        ));
        $this->addElement('hidden', 'old_logo', array(
                'ignore'   => true,
                'label'    => 'Old logo',
        ));

        $this->addElement('text', 'version', array(
                'label'      => 'Extension Version',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => true,
                'class'      => 'span4'
        ));
        $this->addElement('select', 'edition', array(
                'label'      => 'Edition',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => true,
                'class'      => 'span4'
        ));
        $this->addElement('select', 'from_version', array(
                'label'      => 'Supported from',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => true,
                'class'      => 'span4'
        ));
        $this->addElement('select', 'to_version', array(
                'label'      => 'Supported to',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => true,
                'class'      => 'span4'
        ));

        // Add screenshots element
        $this->addElement('hidden', 'screenshots', array(
                'label'      => 'Screenshot',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => true,
                'form'       => 'extension-form',
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'form'     => 'extension-form',
                'ignore'   => true,
                'label'    => 'Save changes'
        ));

        $this->addElement('select', 'is_visible', array(
            'label'       => 'Is visible',
            'required'    => true,
            'class'       => 'span4'
        ));
        
        $this->addElement('text', 'extension_detail', array(
                'label'      => 'Extension Detail',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 255))
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));
        
        $this->addElement('text', 'extension_documentation', array(
                'label'      => 'Extension Documentation',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 255))
                ),
                'allowEmpty' => false,
                'class'      => 'span4'
        ));
        
        $this->is_visible->addMultiOptions(array(
            1 => 'Yes',
            0 => 'No',
        ));
        
        $this->is_visible->setValue(1);

        $this->title->setAttrib('class', 'span4');
        $this->description->setAttrib('class', 'span8');
        $this->description->setAttrib('rows', '5');
        $this->price->setAttrib('class', 'span4');

        // setters for class have to be before setdecorators method call which adds class and not overwrite them
        $this->_setDecorators();

        $this->extension_file->setDecorators(array('File', array('ViewScript', array('viewScript' => '_partials/bootstrap_file_input.phtml', 'placement' => false))));
        $this->extension_encoded_file->setDecorators(array('File', array('ViewScript', array('viewScript' => '_partials/bootstrap_file_input.phtml', 'placement' => false))));

        $this->old_logo->removeDecorator('HtmlTag');
        $this->old_logo->removeDecorator('Overall');
        $this->directory_hash->removeDecorator('HtmlTag');
        $this->directory_hash->removeDecorator('Overall');
        $this->directory_hash->removeDecorator('Label');
        $this->directory_hash->removeDecorator('Errors');
        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('Overall');
        $this->submit->setAttrib('class', 'btn btn-inverse pull-right');
    }

    public function checkFileType($file) {
        var_dump($file);die;
    }
}