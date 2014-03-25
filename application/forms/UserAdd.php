<?php 

class Application_Form_UserAdd extends Application_Form_UserEdit
{

    public function init()
    {
        parent::init();

        $this->email->setRequired(true);
        $this->login->setRequired(true);
        $this->login->setAttrib('disabled', null);
        $unique = new Zend_Validate_Db_NoRecordExists(array('table' => 'user', 'field' => 'login'));
        $unique->setMessage('This login is already registered.');
        $this->login->addValidator($unique);

        $this->setAttrib('label', 'Add account');
    }
}