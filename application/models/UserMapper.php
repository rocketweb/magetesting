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

    public function save(Application_Model_User $user, $savePassword = false)
    {
        $data = $user->__toArray();

        if (null === ($id = $user->getId())) {
            unset($data['id']);
            unset($data['has_system_account']);
            unset($data['status']);
            unset($data['plan_id']);
            unset($data['group']);
            unset($data['downgraded']);
            $data['added_date'] = date('Y-m-d H:i:s');
            $user->setAddedDate($data['added_date']);
            $data['password'] = sha1($user->getPassword());
            $server = new Application_Model_Server();
            $data['server_id'] = $server->fetchMostEmptyServerId();
            $user->setId($this->getDbTable()->insert($data));
        } else {
            unset($data['added_date']);
            if($savePassword) {
                $data['password'] = $user->getPassword();
            }
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        
        return $user;
    }

    public function find($id, Application_Model_User $user, $returnPassword = false)
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
             ->setStreet($row->street)
             ->setPostalCode($row->postal_code)
             ->setCity($row->city)
             ->setState($row->state)
             ->setCountry($row->country)
             ->setGroup($row->group)
             ->setAddedDate($row->added_date)
             ->setStatus($row->status)
             ->setSubscrId($row->subscr_id)
             ->setPlanId($row->plan_id)
             ->setPlanActiveTo($row->plan_active_to)
             ->setHasSystemAccount($row->has_system_account)
             ->setSystemAccountName($row->system_account_name)
             ->setDowngraded($row->downgraded)
             ->setBraintreeVaultId($row->braintree_vault_id)
             ->setBraintreeSubscriptionId($row->braintree_subscription_id)
             ->setServerId($row->server_id)
             ->setHasPapertrailAccount($row->has_papertrail_account)
             ->setPapertrailApiToken($row->papertrail_api_token);

        if($returnPassword) {
            $user->setPassword($row->password);
        }

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
                  ->setStreet($row->street)
                  ->setPostalCode($row->postal_code)
                  ->setCity($row->city)
                  ->setState($row->state)
                  ->setCountry($row->country)
                  ->setGroup($row->group)
                  ->setAddedDate($row->added_date)
                  ->setStatus($row->status)
                  ->setSubscrId($row->subscr_id)
                  ->setPlanId($row->plan_id)
                  ->setPlanActiveTo($row->plan_active_to)
                  ->setHasSystemAccount($row->has_system_account)
                  ->setSystemAccountName($row->system_account_name)
                  ->setDowngraded($row->downgraded)
                  ->setBraintreeVaultId($row->braintree_vault_id)
                  ->setBraintreeSubscriptionId($row->braintree_subscription_id)
                  ->setHasPapertraillAccount($row->has_papertrail_account)
                  ->setPapertrailApiToken($row->papertrail_api_token);

            $entries[] = $entry;
        }
        return $entries;
    }
    
    public function fetchList(){
        
        $select = $this->getDbTable()
                ->select()
                ->setIntegrityCheck(false)
                ->from(array('u'=>'user'),array(
                    'login' => 'login',
                    'status' => 'status',
                    'id' => 'id',
                    'group' => 'group',
                    'firstname' => 'firstname',
                    'lastname' => 'lastname',
                    'server_id' => 'server_id'
                    )
                )
                ->joinLeft('instance','instance.user_id = u.id',array('instances'=>'COUNT(instance.id)'))
                ->joinLeft('server', 'server.id = u.server_id', array('server_label' => 'server.name'))
                ->group('u.id')
                ->query();
        $adapter = new Zend_Paginator_Adapter_Array($select->fetchAll());
        
        return new Zend_Paginator($adapter);
    }

    /**
     * Gets user by specified id and checks if<br />
     * given hash is equal to hash created from found user data
     * @method activateUser
     * @param int $id
     * @param sha1-string $hash
     * @return number
     */
    public function activateUser($id, $hash)
    {
        if((int)$id > 0) {
            $user = $this->find($id, new Application_Model_User());
            if($user AND $user->getId()) {
                if('active' == $user->getStatus()) {
                    // user already activated
                    return 2;
                }
                $user_hash = sha1($user->getLogin().$user->getEmail().$user->getAddedDate());
                if($user_hash == $hash) {
                    // activate user
                    $user->setStatus('active');
                    $user->save();
                    return 0;
                }
            }
            // wrong data user does not exist
        }
        // wrong data
        return 1;
    }

    public function resetPassword($email, $userObject)
    {
        $row = $this->getDbTable()->findByEmail($email);
        $newPassword = '';
        if($row) {
            $userObject->setOptions($row->toArray());
            $newPassword = sha1(time().$userObject->getLogin().$userObject->getId());
            $userObject->setPassword($newPassword);
            $userObject->save(true);
        }
        return $newPassword;
    }
    
    public function findByBraintreeSubscriptionId($subscription_id, Application_Model_User $userObject){
        
        $row = $this->getDbTable()->findByBraintreeSubscriptionId($subscription_id);
         if($row) {
             $userObject->setOptions($row->toArray());
         }
        return $userObject;
    }
    /**
     * Fetches users by plan id,
     * can also take array of plan ids
     * @param type $plan_id
     * @return \Application_Model_User
     */
    public function getAllByPlanId($plan_id){
        
        if (!is_array($plan_id)){
            $plan_id = array($plan_id);
        }
        
        /* Just in case someone would like to pass string as a plan_id */
        foreach ($plan_id as &$id){
            (int)$id;
        }
               
        $resultSet = $this->getDbTable()->fetchAll($this->getDbTable()->select()->where('plan_id IN (?)',$plan_id));

        $entries   = array();
        foreach ($resultSet as $row) {

            $entry = new Application_Model_User();
            
            /*This one doesn't have other fields set on purpose,
             * User::_rebuildPhpmyadminRules only needs logins
             */
            $entry->setId($row->id)
                  ->setLogin($row->login);

            $entries[] = $entry;
        }
        return $entries;
        
    }
  
}