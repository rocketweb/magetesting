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
                       ->join('version', 'store.version_id = version.id', array('version'))
                       ->joinLeft(array('r' => new Zend_Db_Expr(
                                   '(SELECT r.comment, r.store_id 
                                   FROM revision r 
                                   WHERE user_id = '.$this->_db->quote($user_id).' ORDER BY ID DESC)'
                               )
                           ),
                           'r.store_id = '.$this->_name.'.id', 
                           array('r.comment')
                       )
                       ->joinLeft(array('l' => new Zend_Db_Expr(
                                   '(SELECT l.msg, l.store_id
                                   FROM store_log l 
                                   WHERE l.lvl = 3 ORDER BY l.time DESC LIMIT 1)'
                               )
                           ),
                           'l.store_id = '.$this->_name.'.id', 
                           array('l.msg')
                       )
                ->joinLeft('server','server.id = '.$this->_name.'.server_id',array('server_domain'=>'domain'))
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
                       ->where('user_id = ?', $user_id)
                       ->where('status != ?', 'removing-magento');

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
       
    public function findByDomain($domain){
        $select = $this->select()
                        ->setIntegrityCheck(false)
                       ->from($this->_name)
                       ->join('version', 'store.version_id = version.id', 'version')
                       ->where('domain = ?', $domain);
                       
        return $this->fetchRow($select);
    }
}