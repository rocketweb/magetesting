<?php

class Application_Model_DbTable_Store extends Zend_Db_Table_Abstract
{

    protected $_name = 'store';

    public function getAllJoinedWithVersions()
    {
        $select = $this->select()
                        ->from($this->_name)
                        ->setIntegrityCheck(false)
                        ->join('version', 'store.version_id = version.id',array('version'));
        return $this->fetchAll($select);
    }

    public function findAllByUser($user_id)
    {
        $select = $this->select()
                       ->from($this->_name)
                       ->setIntegrityCheck(false)
                       ->join('version', 'store.version_id = version.id',array('version'))
                       ->joinLeft(array('r' => new Zend_Db_Expr(
                                   '(SELECT r.comment, r.store_id 
                                   FROM revision r 
                                   WHERE user_id = '.$this->_db->quote($user_id).' ORDER BY ID DESC)'
                               )
                           ),
                           'r.store_id = '.$this->_name.'.id', 
                           array('r.comment')
                       )
                       ->where('user_id = ?', $user_id)
                       ->where('status != ?', 'removing-magento')
                       ->group(array('store.id'))
                       ->order(array('status asc', 'store.id asc'));
        return $select;
    }

    public function countUserStores($user_id)
    {
        $select = $this->select()
                       ->from($this->_name, 'count(user_id) as stores')
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
                    ->join('user', 'user.id = store.user_id', 'login')
                    ->join('version', 'store.version_id = version.id', 'version')
                    ->order(array('status asc', 'store.id asc'));
    }

    public function getPendingItems()
    {
        $select = $this->select()
        ->where('status = ?', 'pending');
    
        return $this->fetchAll($select);
    }
       
    public function findByDomain($domain){
        $select = $this->select()
                        ->setIntegrityCheck(false)
                       ->from($this->_name)
                       ->join('version', 'store.version_id = version.id', 'version')
                       ->where('domain = ?', $domain);
                       
        return $this->fetchRow($select);
    }
    
    public function findPositionByName($store_name)
    {
	/**
	SELECT COUNT( q.id )
	FROM `queue` `q`
	WHERE `q`.`id` <= (
	SELECT id
	FROM queue
	WHERE domain = '$store_name' )
	AND `status` = 'ready'
	*/
    
        $select = $this->select()
                        ->setIntegrityCheck(false)
                       ->from($this->_name, array('num' => 'count(store.id)'))
                       ->where("store.id <= (SELECT id FROM store WHERE domain = '".$store_name."')")
                ->where('status = ?','pending');
                       
	//Zend_Debug::Dump($select->assemble());
        return $this->fetchRow($select);
    }
}