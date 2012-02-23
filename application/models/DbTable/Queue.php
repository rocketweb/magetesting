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
        $timeExecution = (int)Zend_Controller_Front::getInstance()
                                    ->getParam('bootstrap')
                                    ->getResource('config')
                                    ->magento
                                    ->instanceTimeExecution;

        $id_col = $this->_name.'.id';
        $timeExecutionSubSql = new Zend_Db_Expr(
                $timeExecution.' * ( '.$id_col.' - ( SELECT '.$id_col.' FROM '.$this->_name.'
                WHERE status =  \'pending\' ORDER BY '.$id_col.' ASC LIMIT 1) +1 ) AS queue_time'
        );

        $select = $this->select()
                        ->from($this->_name)
                        ->setIntegrityCheck(false)
                        ->columns($timeExecutionSubSql)
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
        $timeExecution = (int)Zend_Controller_Front::getInstance()
                                ->getParam('bootstrap')
                                ->getResource('config')
                                ->magento
                                ->instanceTimeExecution;
        
        $id_col = $this->_name.'.id';
        $timeExecutionSubSql =
                $timeExecution.' * ( '.$id_col.' - ( SELECT '.$id_col.' FROM '.$this->_name.'
                WHERE status =  \'pending\' ORDER BY '.$id_col.' ASC LIMIT 1 ) +1 ) AS queue_time';
        
        return $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this->_name)
                    ->columns($timeExecutionSubSql)
                    ->join('user', 'user.id = queue.user_id', 'login')
                    ->join('version', 'queue.version_id = version.id', 'version');
    }
}