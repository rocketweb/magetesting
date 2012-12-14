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
                    ->join('store', 'store.id = queue.store_id',array('edition','domain','store_name'))
                    ->join('user', 'user.id = store.user_id', 'login')
                    ->join('version', 'store.version_id = version.id', 'version')
                    ->where('queue.server_id = ?',$server_id)
                    ->where('task <> ?','MagentoDownload')
                    ->where('queue.status = ?','pending')
                    ->where('retry_count <= ?','3')
                    ->where('parent_id = ?',0)
                    ->order(array('queue.id ASC', 'parent_id asc'))
                    ->limit(1);
            break;
            
            case 'download':
                $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this->_name)
                    ->join('store', 'store.id = queue.store_id',array('edition','domain','store_name'))
                    ->join('user', 'user.id = store.user_id', 'login')
                    ->join('version', 'store.version_id = version.id', 'version')
                    ->where('queue.server_id = ?',$server_id)
                    ->where('task = ?','MagentoDownload')
                    ->where('queue.status = ?','pending')
                    ->where('retry_count <= ?','3')
                    ->where('parent_id = ?',0)
                    ->order(array('queue.id ASC', 'parent_id asc'))
                    ->limit(1);
            break;
            
            case 'all':
            default:
                $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this->_name)
                    ->join('store', 'store.id = queue.store_id',array('edition','domain','store_name'))
                    ->join('user', 'user.id = store.user_id', 'login')
                    ->join('version', 'store.version_id = version.id', 'version')
                    ->where('queue.server_id = ?',$server_id)
                    ->where('queue.status = ?','pending')
                    ->where('retry_count <= ?','3')
                    ->where('parent_id = ?',0)
                    ->order(array('queue.id ASC', 'parent_id asc'))
                    ->limit(1);
            break;
            
                
        }       
        $result = $this->fetchRow($select);
        if (count($result)){
            return $result;
        } else {
            return false;
        }
    }
    
    /**
     * every etension install has to wait for all other install+commit tasks
     * @param type $storeId
     * @return int
     */
     
    public function getParentIdForExtensionInstall($storeId){
        $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this->_name)
                    ->where('queue.store_id = ?',$storeId)
                    ->where('task = ?','RevisionCommit')
                    ->order(array('queue.id DESC'))
                    ->limit(1);
        $row = $this->fetchRow($select);
        
        if($row && isset($row['id'])){
            return $row['id'];
        } else {
            return 0;
        }
    }
}
