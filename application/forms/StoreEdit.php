<?php

class Application_Form_StoreEdit extends Integration_Form{

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

        $this->addElement('text', 'store_name', array(
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
        $this->addElement('submit', 'storeSave', array(
                'ignore'   => true,
                'label'    => 'Save',
        ));

        if($this->add_custom_field) {
            $this->backend_password->setAttrib('readonly', null);
            $this->backend_password->setIgnore(false);
            
            $this->addElement('select', 'custom_protocol', array(
                'label'      => 'Protocol',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_InArray(array('ftp','ssh'))
                ),
                'class'      => 'span4'
        ));
        $this->custom_protocol->addMultiOptions(array(
                'ftp' => 'FTP',
                'ssh' => 'SSH',
        ));
        
        

            $this->addElement('text', 'custom_host', array(
                'label'    => 'Host',
                'required' => true,
                'filters'    => array('StripTags', 'StringTrim'),
            ));
            
            $this->addElement('text', 'custom_port', array(
                'label'       => 'Port',
                'required'    => false,
                'label_class' => 'radio inline',
                'placeholder' => 'leave blank for default',
                'class'      => 'span4'
        ));
            
            $this->addElement('text', 'custom_login', array(
                'label'    => 'Login',
                'required' => true,
                'filters'    => array('StripTags', 'StringTrim'),
            ));
            $this->addElement('password', 'custom_pass', array(
                'label'    => 'Password',
                'required' => true,
                'renderPassword' => true,
                'filters'    => array('StripTags', 'StringTrim'),
            ));
            $this->addElement('password', 'custom_pass_confirm', array(
                'label'    => 'Password Confirmation',
                'required' => true,
                'renderPassword' => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_Identical('custom_pass'),
                ),
            ));
            $this->addElement('text', 'custom_remote_path', array(
                'label'    => 'Remote Path',
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => true,
            ));
            
            $this->addElement('text', 'custom_sql', array(
                'label'    => 'SQL Backup',
                'required' => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => false,
            ));
            
                        $this->addElement('text', 'custom_file', array(
                'label'    => 'Remote Path',
                'filters'    => array('StripTags', 'StringTrim'),
                'allowEmpty' => true,
            ));
            
        }
        
        $this->addElement('radio', 'do_hourly_db_revert', array(
        		'label'       => 'Revert database hourly',
        		'required'    => false,
        		'label_class' => 'radio inline'
        ));
        $this->do_hourly_db_revert->addMultiOptions(array(
        		1 => 'Yes',
        		0 => 'No',
        ));
        
        $this->do_hourly_db_revert->setValue(0)
        ->setSeparator(' ');

        $this->_setDecorators();

        $this->storeSave->removeDecorator('HtmlTag');
        $this->storeSave->removeDecorator('overall');
        $this->storeSave->setAttrib('class','btn btn-primary');
        $this->storeSave->removeDecorator('Label');
        
        /**
         * this needs to be done after _setDecorators(), 
         * the same way it is done in StoreAddCustom Form
         */
        if($this->add_custom_field) {
            $this->custom_remote_path->removeDecorator('HtmlTag');
            $this->custom_remote_path->removeDecorator('overall');

            $this->custom_sql->removeDecorator('HtmlTag');
            $this->custom_sql->removeDecorator('overall');

            $this->custom_file->removeDecorator('HtmlTag');
            $this->custom_file->removeDecorator('overall');
        }
    }
}

