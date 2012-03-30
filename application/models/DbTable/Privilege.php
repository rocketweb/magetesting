<?php

class Application_Model_DbTable_Privilege {
    
    private $adapter  ='';
    private $config = '';
        
    public function __construct($adapter,$config)
    {
              
        $this->adapter = $adapter;
        $this->config = $config;
        
        
    }
    
    public function checkIfUserExists($login)
    {
        $select = $this->adapter->select()
                       ->from('mysql.user', array('User'))
                       ->where('User = ?',$this->config->magento->userprefix.$login)
                ->query();
        
        $users = $select->fetchall();

        if(count($users)){
            return true;
        }

        return false;
    }
    
    public function checkIfDatabaseExists($dbname)
    {
        try {
            $this->adapter->getConnection()->exec("use ".$this->config->magento->instanceprefix.$dbname);
        } catch (PDOException $e){
            return false;
        }
        return true;
    }
    
    
    /**
     * required:
     * GRANT CREATE, RELOAD, CREATE USER ON *.* TO 'mageintegration'@'localhost' WITH GRANT OPTION
     * GRANT DROP ON `INST_%`.* TO 'mageintegration'@'localhost'
	 * too much ? - GRANT ALL PRIVILEGES ON `mageintegration`.* TO 'mageintegration'@'localhost'
	 * GRANT ALL PRIVILEGES ON `INST_%`.* TO 'mageintegration'@'localhost'
	 * GRANT SELECT ON `mysql`.`user` TO 'mageintegration'@'localhost'
	 * 
     * this should be run upon registration for users in mysql and magentointegrations have the same passwords
     * @param type $login
     */
    public function createUser($login)
    {
        
        //add user 
        $this->adapter->getConnection()->exec("create user '".$this->config->magento->userprefix.$login."'@'localhost' identified by '". substr(sha1($this->config->magento->usersalt.$this->config->magento->userprefix.$login),0,10)."'");
        
        $this->adapter->getConnection()->exec("GRANT ALL ON `".$this->config->magento->instanceprefix.$login."_%`.* TO '".$this->config->magento->userprefix.$login."'@'localhost'");

        //$this->adapter->getConnection()->exec("FLUSH TABLES,PRIVILEGES");
        
    }
    
       
    /**
     * @todo: needs implementation
     * 
     * this should be run upon registration for users in mysql and magentointegrations have the same passwords
     * @param type $login
     * @param type $password 
     */
    public function changePassword(){}
    
    /**
     * @todo: needs implementation
     * implement this when have user management implemented, so you can delete mi_login when you remove account
     */
    public function dropUser($login){
        //'mi_'.$login
    }
    
    /**
     * @todo: needs implementation
     */
    public function createDatabase($dbname)
    {
        $this->adapter->getConnection()->exec("CREATE DATABASE ".$this->config->magento->instanceprefix.$dbname);  
    }
    
    /**
     * @todo: needs implementation
     */
    public function dropDatabase($dbname)
    {
        $this->adapter->getConnection()->exec("DROP DATABASE ".$this->config->magento->instanceprefix.$dbname);   
    }
    
}