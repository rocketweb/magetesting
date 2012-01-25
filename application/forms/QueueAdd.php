<?php

class Application_Form_QueueAdd extends Integration_Form{
    
    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');   

        // Add a comment element
        $this->addElement('textarea', 'text', array(
            'COLS' => '40',
            'ROWS' =>'3',
            'label'      => 'Your comment',
            'required'   => true,
            'filters'    => array('StripTags', 'StringTrim'),
            'validators' => array(
                array('validator' => 'StringLength', 'options' => array(2, 2000)),
            )
        ));
               
        if (!empty(self::$statusOptions)){
            $this->addElement('select', 'status', array(
                'label'      => 'Status',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                    new Zend_Validate_InArray(array_keys(self::$statusOptions))
                )
            ));
            $this->status->addMultiOptions(self::$statusOptions);
        }
        
        
        // add CSRF protection
        $myNamespace = new Zend_Session_Namespace('csrf_hash');
        $myNamespace->setExpirationSeconds(900);
        $myNamespace->authtoken = $hash = md5(uniqid(rand(),1));
        $auth = new Zend_Form_Element_Hidden('csrf_hash');
        $auth->setValue($hash)
            ->setRequired('true')
            ->removeDecorator('HtmlTag')
            ->removeDecorator('Label');    

        $this->addElement($auth);
        
        // Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
            'label'    => 'Save changes',
        ));


        

        $this->_setDecorators();

        
        
        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');
        
        $this->status->removeDecorator('HtmlTag');
        $this->status->removeDecorator('overall');
        $this->status->removeDecorator('Label');
        
        $this->status->addDecorator(array('AddLi' => 'HtmlTag'), array('tag' => 'li'));
        $this->status->addDecorator(array('AddUl' => 'HtmlTag'), array('tag' => 'ul', 'class' => 'inputs-list'));
        $this->status->addDecorator(array('AddDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input'));
        $this->status->addDecorator('Label', array('escape' => false));
        $this->status->addDecorator('Overall', array('tag' => 'div', 'class' => 'clearfix'));
    }
    
}

