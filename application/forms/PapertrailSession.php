<?php 

class Application_Form_PapertrailSession extends Integration_Form {

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAction('https://papertrailapp.com/distributors/session');
        
        $this->addElement('hidden', 'account_id', array(
                'label'      => '',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),                
        ));
        
        $this->addElement('hidden', 'user_id', array(
                'label'      => '',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),                
        ));
        
        $this->addElement('hidden', 'system_id', array(
                'label'      => '',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),                
        ));
        
        $this->addElement('hidden', 'timestamp', array(
                'label'      => '',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),                
        ));
        
        $this->addElement('hidden', 'token', array(
                'label'      => '',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),                
        ));
        
        $this->addElement('hidden', 'distributor', array(
                'label'      => '',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),                
        ));
        
        $this->addElement('hidden', 'email', array(
                'label'      => '',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 100)),
                        new Zend_Validate_EmailAddress()
                ),
        ));

        $this->_setDecorators();

    }


}