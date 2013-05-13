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

        $status_options = array(
            'active' => 'Active',
            'inactive' => 'Inactive',
        );
        $this->addElement('select', 'status', array(
            'label'       => 'Status',
            'required'    => true,
            'validators' => array(
                new Zend_Validate_InArray(array_keys($status_options))
            ),
            'class'      => 'span4'
        ));
        $this->status->addMultiOptions($status_options);
        $this->status->setValue('active');

        $group_options = array(
            'admin' => 'admin',
            'free-user' => 'free-user',
            'awaiting-user' => 'awaiting-user',
            'commercial-user' => 'commercial-user'
        );
        $this->addElement('select', 'group', array(
            'label'       => 'Group',
            'required'    => true,
            'validators' => array(
                new Zend_Validate_InArray(array_keys($group_options))
            ),
            'class'      => 'span4'
        ));
        $this->group->addMultiOptions($group_options);
        $this->group->setValue('free-user');

        $unique = new Zend_Validate_Db_NoRecordExists(array('table' => 'user', 'field' => 'email'));
        $unique->setMessage('This email is already registered.');
        $this->email->addValidator($unique);

        // Add a server element
        $this->addElement('select', 'server_id', array(
                'label'      => 'Server',
                'tabindex'   => 6,
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'class'      => 'span4'
        ));
        $server_model = new Application_Model_Server();
        $servers = array();
        foreach($server_model->fetchAll() as $row) {
            $servers[$row->getId()] = $row->getName();
        }
        $this->server_id->setMultiOptions($servers);

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