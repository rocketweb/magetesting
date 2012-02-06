<?php

class Application_Model_DbTable_Queue extends Zend_Db_Table_Abstract
{

    protected $_name = 'queue';

    public function getAllJoinedWithVersions()
    {
        $select = $this->select()
                        ->from($this->_name)
                        ->setIntegrityCheck(false)
                        ->join('version', 'queue.version_id = version.id',array('version'));
        return $this->fetchAll($select);
    }

    public function findAllByUser($user_id)
    {
        $select = $this->select()
                        ->from($this->_name)
                        ->setIntegrityCheck(false)
                        ->join('version', 'queue.version_id = version.id',array('version'))
                        ->where( 'user_id = ?', $user_id );
        return $select;
    }

    public function countUserInstances($user_id)
    {
        $select = $this->select()
                       ->from($this->_name, 'count(user_id) as instances')
                       ->where('user_id = ?', $user_id);

        return $this->fetchAll($select);
    }

    public function changeStatusToClose($userId, $domain)
    {
        $data = array('status' => 'closed');
        $where = array(
            'user_id = ?' => $userId,
            'domain = ?' => $domain
        );

        $this->update($data, $where);
    }
    
    public function getWholeQueueWithUsersName()
    {
        return $this->select()
                       ->setIntegrityCheck(false)
                       ->from($this->_name)
                       ->join('user', 'user.id = queue.user_id', 'login')
                       ->join('version', 'queue.version_id = version.id', 'version');
    }

}