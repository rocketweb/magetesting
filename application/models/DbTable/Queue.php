<?php

class Application_Model_DbTable_Queue extends Zend_Db_Table_Abstract
{

    protected $_name = 'queue';

    public function getForServer($server_id,$type)
    {  
        switch($type){
            
            case 'allbutdownload':
                $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this->_name)
                    ->join('instance', 'instance.id = queue.instance_id',array('edition','domain','instance_name'))
                    ->join('user', 'user.id = instance.user_id', 'login')
                    ->join('version', 'instance.version_id = version.id', 'version')
                    ->where('queue.server_id = ?',$server_id)
                    ->where('task <> ?','MagentoDownload')
                    ->where('queue.status = ?','pending')
                    ->where('retry_count <=','3')
                    ->where('parent_id = ?',0)
                    ->order(array('queue.id ASC', 'parent_id asc'));
            break;
            
            case 'download':
                $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this->_name)
                    ->join('instance', 'instance.id = queue.instance_id',array('edition','domain','instance_name'))
                    ->join('user', 'user.id = instance.user_id', 'login')
                    ->join('version', 'instance.version_id = version.id', 'version')
                    ->where('queue.server_id = ?',$server_id)
                    ->where('task = ?','MagentoDownload')
                    ->where('queue.status = ?','pending')
                    ->where('retry_count <=','3')
                    ->where('parent_id = ?',0)
                    ->order(array('queue.id ASC', 'parent_id asc'));
            break;
            
            case 'all':
            default:
                $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this->_name)
                    ->join('instance', 'instance.id = queue.instance_id',array('edition','domain','instance_name'))
                    ->join('user', 'user.id = instance.user_id', 'login')
                    ->join('version', 'instance.version_id = version.id', 'version')
                    ->where('queue.server_id = ?',$server_id)
                    ->where('queue.status = ?','pending')
                    ->where('retry_count <=','3')
                    ->where('parent_id = ?',0)
                    ->order(array('queue.id ASC', 'parent_id asc'));
            break;
            
                
        }       
        
        return $this->fetchAll($select);
    }
}
