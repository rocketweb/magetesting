<?php

class Application_Form_DevExtensionInstall extends Integration_Form {

    private static $matchingExtensions = '';

    public function __construct($matchingExtensions='') {
        self::$matchingExtensions = $matchingExtensions;
        parent::__construct($matchingExtensions);
    }

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');
        $this->setAttrib('id', 'devextension-install-form');
        
        $this->addElement('hidden', 'instance_name', array(
                'label'      => '',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),                
        ));
         
        if (!empty(self::$matchingExtensions)){
            
            var_dump(array_keys(self::$matchingExtensions));
            
        $this->addElement('multiselect', 'extension', array(
                'label'      => 'Extension',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_InArray(array_keys(self::$matchingExtensions))
                )
        ));
        
        $emptyVersion = array('' => 'Choose...');
        $this->extension->addMultiOptions($emptyVersion);
        
        }

        
        
        // Add the submit button
        $this->addElement('submit', 'extensionAdd', array(
                'ignore'   => true,
                'label'    => 'Install',
        ));

        $this->_setDecorators();

        $this->extensionAdd->removeDecorator('HtmlTag');
        $this->extensionAdd->removeDecorator('overall');
        $this->extensionAdd->setAttrib('class','btn btn-primary');
        $this->extensionAdd->removeDecorator('Label');

        $this->extension->removeDecorator('HtmlTag');
        $this->extension->removeDecorator('overall');
        $this->extension->removeDecorator('Label');

        $this->extension->addDecorator('Label', array('escape' => false));
        $this->extension->addDecorator('Overall', array('tag' => 'div', 'class' => 'control-group gray-menu'));

    }

}

