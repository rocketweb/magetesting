<?php 

class Application_Form_UserEdit extends Application_Form_EditAccount
{

    public function init()
    {
        parent::init();
        
        
        $this->setAttrib('id', 'user-edit-form');
        $this->removeElement('submit');
        $this->getElement('email')->setAttrib('disabled', null);
        $this->getElement('country')->setAttrib('required', false);
        $this->getElement('city')->setAttrib('required', false);
        $this->getElement('state')->setAttrib('required', false);
        $this->getElement('postal_code')->setAttrib('required', false);
        $this->getElement('street')->setAttrib('required', false);
        
        // Add a server element
        $this->addElement('select', 'server_id', array(
                'label'      => 'Server',
                'tabindex'   => 6,
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim')
        ));

        // Add the submit button
        $this->addElement('submit', 'submit', array(
                'tabindex' => 7,
                'ignore'   => true,
                'label'    => 'Save',
        ));

        $this->_setDecorators();

        $this->submit->removeDecorator('HtmlTag');
        $this->submit->removeDecorator('overall');
        
        

    }


}