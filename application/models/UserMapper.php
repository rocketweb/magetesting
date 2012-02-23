<?php

class Application_Model_UserMapper {

    protected $_dbTable;

    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable('Application_Model_DbTable_User');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_User $user)
    {
        $data = array(
            'id'         => $user->getId(),
            'firstname'  => $user->getFirstname(),
            'lastname'   => $user->getLastname(),
            'email'      => $user->getEmail(),
            'login'      => $user->getLogin(),
            'group'      => $user->getGroup(),
            'status'     => $user->getStatus()
        );

        if($user->getPassword() !== null && $user->getPassword() !== '')
            $data['password'] = sha1($user->getPassword());

        if (null === ($id = $user->getId())) {
            unset($data['id']);
            $data['added_date'] = $user->getAddedDate();
            $data['status'] = 'inactive';
            $data['group'] = 'standard-user';
            $this->getDbTable()->insert($data);
        } else {
            unset($data['added_date']);
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    public function find($id, Application_Model_User $user)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        $row = $result->current();
        $user->setId($row->id)
            ->setFirstname($row->firstname)
            ->setLastname($row->lastname)
            ->setEmail($row->email)
            ->setLogin($row->login)
            ->setGroup($row->group)
            ->setAddedDate($row->added_date)
            ->setStatus($row->status);
        return $user;
    }

    public function fetchAll($activeOnly = false)
    {
        if ($activeOnly===true){
            $resultSet = $this->getDbTable()->fetchAll($this->getDbTable()->select()->where('status = ?', 'active'));
        } else{
            $resultSet = $this->getDbTable()->fetchAll();
        }

        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Application_Model_User();
            $entry->setId($row->id)
                    ->setFirstname($row->firstname)
                    ->setLastname($row->lastname)
                    ->setEmail($row->email)
                    ->setLogin($row->login)
                    ->setGroup($row->group)
                    ->setAddedDate($row->added_date)
                    ->setStatus($row->status);
            $entries[] = $entry;
        }
        return $entries;
    }
}