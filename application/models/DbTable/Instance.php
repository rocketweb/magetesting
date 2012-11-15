<?php

class Application_Model_DbTable_Instance extends Zend_Db_Table_Abstract
{

    protected $_name = 'instance';

    public function getAllJoinedWithVersions()
    {
        $select = $this->select()
                        ->from($this->_name)
                        ->setIntegrityCheck(false)
                        ->join('version', 'instance.version_id = version.id',array('version'));
        return $this->fetchAll($select);
    }

    public function findAllByUser($user_id)
    {
        $select = $this->select()
                       ->from($this->_name)
                       ->setIntegrityCheck(false)
                       ->join('version', 'instance.version_id = version.id',array('version'))
                       ->where( 'user_id = ?', $user_id )
                       ->order(array('status asc', 'instance.id asc'));
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
                    ->join('user', 'user.id = instance.user_id', 'login')
                    ->join('version', 'instance.version_id = version.id', 'version')
                    ->order(array('status asc', 'instance.id asc'));
    }

    public function getPendingItems()
    {
        $select = $this->select()
        ->where('status = ?', 'pending');
    
        return $this->fetchAll($select);
    }
    
    public function findByName($instance_name)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                       ->from($this->_name)
                       ->join('version', 'instance.version_id = version.id', 'version')
                       ->where('domain = ?', $instance_name);
                       
        return $this->fetchRow($select);
    }
    
    public function findPositionByName($instance_name)
    {
	/**
	SELECT COUNT( q.id )
	FROM `queue` `q`
	WHERE `q`.`id` <= (
	SELECT id
	FROM queue
	WHERE domain = '$instance_name' )
	AND `status` = 'ready'
	*/
    
        $select = $this->select()
                        ->setIntegrityCheck(false)
                       ->from($this->_name, array('num' => 'count(instance.id)'))
                       ->where("instance.id <= (SELECT id FROM instance WHERE domain = '".$instance_name."')")
                ->where('status = ?','pending');
                       
	//Zend_Debug::Dump($select->assemble());
        return $this->fetchRow($select);
    }
}