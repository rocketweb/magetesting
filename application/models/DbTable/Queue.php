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
                    ->where($this->_name.'.store_id = ?',$storeId)
                    ->where('task = ?','RevisionCommit')
                    ->order(array($this->_name.'.id DESC'))
                    ->limit(1);
        $row = $this->fetchRow($select);
        
        if($row && isset($row['id'])){
            return $row['id'];
        } else {
            return 0;
        }
    }
    
    /**
     * Counts tasks for specified $storeId 
     * Used to determine if we can set store status to ready
     * @param integer $storeId
     * @return boolean
     */
    public function countForStore($storeId){

        /* TODO: replace this with count to prevent performance issues later */
        $select = $this->select()
                       ->from($this->_name)
                       ->where($this->_name.'.store_id = ?',$storeId);

        if(count($this->fetchAll($select)) > 0){
            return true;
        } else {
            return false;
        }
    }
    
    public function findPositionByName($store_name)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from($this->_name, array('num' => 'count(store_id)'))
                        ->where("store_id <= (SELECT id FROM store WHERE domain = '".$store_name."')")
                        ->where('status != ?', 'ready')
                        ->where('status != ?', 'error')
                        ->where('retry_count < ?', 4);

        return $this->fetchRow($select);
    }
    
    public function findPositionByUserAndId($user_id,$queue_id){
            
        $select = $this->select()
                    ->setIntegrityCheck(false)
                    ->from($this->_name, array('num' => 'count(id)'))
                    ->where("id <= '".$queue_id."' ")
                    ->where("user_id <= '".$user_id."' ")
                    ->where('status != ?', 'ready')
                    ->where('status != ?', 'error')
                    ->where('retry_count < ?', 4);

        return $this->fetchRow($select);
    }
    
    public function alreadyExists($taskType,$storeId,$extensionId,$serverId)
    {
        $select = $this->select()
                        ->setIntegrityCheck(false)
                        ->from($this->_name)
                        ->where("store_id = ?", $storeId)
                        ->where('task = ?', $taskType)
                        ->where('extension_id = ?', $extensionId)
                        ->where('server_id = ?', $serverId);

        if(count($this->fetchAll($select)) > 0){
            return true;
        } else {
            return false;
        }
    }
    
    public function getNextForStore($storeId){

        $select = $this->select()
                       ->from($this->_name)
                       ->where($this->_name.'.store_id = ?',$storeId)
                       ->order(array('queue.id ASC', 'parent_id asc'))
                       ->limit(1);

        return $this->fetchRow($select);
    }
}
