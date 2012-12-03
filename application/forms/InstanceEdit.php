<?php

class Application_Form_InstanceEdit extends Integration_Form{

    protected $add_custom_field;
    public function __construct($custom_fields = false)
    {
        $this->add_custom_field = ($custom_fields ? true : false);
        parent::__construct();
    }

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');

        $this->addElement('text', 'instance_name', array(
                'label'      => 'Name',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
        ));

        $this->addElement('text', 'description', array(
                'label'      => 'Description',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                    array('validator' => 'StringLength', 'options' => array('max' => 300))
                )
        ));

        $this->addElement('text', 'backend_login', array(
                'label'    => 'Backend login',
                'ignore'   => true,
                'readonly' => true
        ));

        $this->addElement('text', 'backend_password', array(
                'label'    => 'Backend password',
                'ignore'   => true,
                'readonly' => true
        ));

        // Add the submit button
        $this->addElement('submit', 'instanceSave', array(
                'ignore'   => true,
                'label'    => 'Save',
        ));

        if($this->add_custom_field) {
            $this->backend_password->setAttrib('readonly', null);
            $this->backend_password->setIgnore(false);

            $this->addElement('text', 'custom_host', array(
                'label'    => 'FTP Host',
                'required' => true,
                'filters'    => array('StripTags', 'StringTrim'),
            ));
            $this->addElement('text', 'custom_login', array(
                'label'    => 'FTP Login',
                'required' => true,
                'filters'    => array('StripTags', 'StringTrim'),
            ));
            $this->addElement('password', 'custom_pass', array(
                'label'    => 'FTP Password',
                'required' => true,
                'renderPassword' => true,
                'filters'    => array('StripTags', 'StringTrim'),
            ));
            $this->addElement('password', 'custom_pass_confirm', array(
                'label'    => 'FTP Password Confirmation',
                'required' => true,
                'renderPassword' => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_Identical('custom_pass'),
                ),
            ));
            $this->addElement('text', 'custom_remote_path', array(
                'label'    => 'FTP Remote Path',
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => true,
            ));
            $this->addElement('text', 'custom_sql', array(
                'label'    => 'FTP SQL Backup',
                'required' => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => false,
            ));
        }

        $this->_setDecorators();

        $this->instanceSave->removeDecorator('HtmlTag');
        $this->instanceSave->removeDecorator('overall');
        $this->instanceSave->setAttrib('class','btn btn-primary');
        $this->instanceSave->removeDecorator('Label');

    }

}

