<?php 

class Application_Form_UserEdit extends Application_Form_EditAccount
{

    public function init()
    {
        parent::init();
        
        $this->setAttrib('label', 'Edit account');
        $this->setAttrib('id', 'user-edit-form');
        $this->removeElement('submit');
        $this->getElement('email')->setAttrib('disabled', null);
        $this->getElement('country')->setRequired(false);
        $this->getElement('city')->setRequired(false);
        $this->getElement('state')->setRequired(false);
        $this->getElement('postal_code')->setRequired(false);
        $this->getElement('street')->setRequired(false);

        $unique = new Zend_Validate_Db_NoRecordExists(array('table' => 'user', 'field' => 'email'));
        $unique->setMessage('This email is already registered.');
        $this->email->addValidator($unique);

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
        
        $this->state->removeDecorator('Label');
        $this->state->removeDecorator('overall');
        $this->state->removeDecorator('HtmlTag');

    }


}