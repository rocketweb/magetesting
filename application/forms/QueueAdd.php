<?php

class Application_Form_QueueAdd extends Integration_Form{
    
    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');   
              
            $this->addElement('select', 'edition', array(
                'label'      => 'Edition',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                    new Zend_Validate_InArray(array_keys(Application_Model_Edition::getKeys()))
                )
            ));
            
            $emptyVersion = array('' => 'Choose...');
            $versions = array_merge($emptyVersion,Application_Model_Edition::getOptions());
            
            $this->edition->addMultiOptions($versions);
        
            
            $this->addElement('select', 'version', array(
                'label'      => 'Version',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                    new Zend_Validate_InArray(array_keys(Application_Model_Version::getKeys()))
                )
            ));
            //$this->version->addMultiOptions();

       
        // Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
            'label'    => 'Save changes',
        ));

        $this->_setDecorators();
        
        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');
        
        $this->edition->removeDecorator('HtmlTag');
        $this->edition->removeDecorator('overall');
        $this->edition->removeDecorator('Label');
        
        $this->edition->addDecorator(array('AddLi' => 'HtmlTag'), array('tag' => 'li'));
        $this->edition->addDecorator(array('AddUl' => 'HtmlTag'), array('tag' => 'ul', 'class' => 'inputs-list'));
        $this->edition->addDecorator(array('AddDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input'));
        $this->edition->addDecorator('Label', array('escape' => false));
        $this->edition->addDecorator('Overall', array('tag' => 'div', 'class' => 'clearfix'));
        
        
        $this->version->removeDecorator('HtmlTag');
        $this->version->removeDecorator('overall');
        $this->version->removeDecorator('Label');
        
        $this->version->addDecorator(array('AddLi' => 'HtmlTag'), array('tag' => 'li'));
        $this->version->addDecorator(array('AddUl' => 'HtmlTag'), array('tag' => 'ul', 'class' => 'inputs-list'));
        $this->version->addDecorator(array('AddDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input'));
        $this->version->addDecorator('Label', array('escape' => false));
        $this->version->addDecorator('Overall', array('tag' => 'div', 'class' => 'clearfix'));
    }
    
}

