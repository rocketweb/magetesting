<?php

class Application_Model_CouponMapper {

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
            $this->setDbTable('Application_Model_DbTable_Coupon');
        }
        return $this->_dbTable;
    }

    public function save(Application_Model_Coupon $coupon)
    {
        $data = $coupon->__toArray();

        if (null === ($id = $coupon->getId())) {
            $coupon->setId($this->getDbTable()->insert($data));
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        
        return $coupon;
    }

    public function find($id, Application_Model_Coupon $coupon)
    {
        $result = $this->getDbTable()->find($id);
        if (0 == count($result)) {
            return;
        }
        
        $row = $result->current();
        $coupon->setId($row->id)
             ->setCode($row->code)
             ->setUsedDate($row->used_date)
             ->setUserId($row->user_id)
             ->setPlanId($row->plan_id)
             ->setDuration($row->duration)
             ->setActiveTo($row->active_to)
             ;
        return $coupon;
    }
    
    public function findByCode($code, Application_Model_Coupon $coupon)
    {
        $result = $this->getDbTable()->fetchAll($this->getDbTable()->select()->where('code = ?', $code));
        if (0 == count($result)) {
            return;
        }
        
        $row = $result->current();
        $coupon->setId($row->id)
             ->setCode($row->code)
             ->setUsedDate($row->used_date)
             ->setUserId($row->user_id)
             ->setPlanId($row->plan_id)
             ->setDuration($row->duration)
             ->setActiveTo($row->active_to)
             ;
        return $coupon;
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
            $entry = new Application_Model_Coupon();
            $entry->setId($row->id)
             ->setCode($row->code)
             ->setUsedDate($row->used_date)
             ->setUserId($row->user_id)
             ->setPlanId($row->plan_id)
             ->setDuration($row->duration)
             ->setActiveTo($row->active_to)
                ;
            $entries[] = $entry;
        }
        return $entries;
    }
    
    public function fetchList(){
        
        $select = $this->getDbTable()
                ->select()
                ->setIntegrityCheck(false)
                ->from(array('c'=>'coupon'),array(                             
                    'id' => 'id',
                    'code' => 'code',
                    'used_date' => 'used_date',
                    'user_id' => 'user_id',
                    'plan_id' => 'plan_id',
                    'duration' => 'duration',
                    'active_to' => 'active_to',
                    )
                )
                ->query();
                
        $adapter = new Zend_Paginator_Adapter_Array($select->fetchAll());
        
        return new Zend_Paginator($adapter);
    }

    public function apply($coupon_id, $user_id)
    {
        
        $modelUser = new Application_Model_User();
        $user = $modelUser->find($user_id);
        $coupon = $this->find($coupon_id, new Application_Model_Coupon());
        
        if ($coupon){
            if (!$coupon->getUserId()){
                if ($user->getGroup()=='free-user'){
                    if(strtotime($coupon->getActiveTo()) > time()){
                        
                        //update user with new data
                        $user->setPlanActiveTo(strtotime("now " . $coupon->getDuration() . ""));
                        
                        $user->setPlanId($coupon->getPlanId());
                        $user->save();
                       
                        //update coupon info
                        $coupon->setUserId($user->getId());
                        $coupon->setUsedDate(date("Y-m-d",time()));
                        $coupon->save();
                        
                        return 0; //everything ok
                        
                    } else {
                        return 4; //coupon expired
                    }                   
                } else {
                    return 3; //already paid, do not use coupon to not override current plan
                }
            } else {
                return 2; //coupon already taken
            }
        } else {
            return 1; //no such coupon
        }
    }
}