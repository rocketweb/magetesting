<?php

class Application_Model_TaskMysql
{
    protected $_db;
    protected $_tablePrefix = '';

    public function __construct($db, $prefix = '')
    {
        $this->setDbAdapter($db);
        $this->setTablePrefix($prefix);
    }
    public function setDbAdapter($db)
    {
        /* @var $db Zend_Db_Adapter_Abstract */
        $this->_db = $db;
        return $this;
    }
    public function getDbAdapter()
    {
        return $this->_db;
    }
    public function setTablePrefix($prefix)
    {
        $this->_tablePrefix = $prefix;
        return $this;
    }

    public function truncate($tables)
    {
        if(is_string($tables)) {
            $tables = array($tables);
        }
        $db = $this->getDbAdapter();
        foreach($tables as $table) {
            $db->query(
                'TRUNCATE TABLE '.$this->_table($table, true)
            );
        }
    }

    public function updateCoreConfig($domain, $email)
    {
        $updates = array();

        //update core_config_data with new url
        $updates[] = array(
            'data'  => array('value' => $domain),
            'where' => array('path = ?' => 'web/unsecure/base_url')
        );
        $updates[] = array(
            'data'  => array('value' => $domain),
            'where' => array('path = ?' => 'web/secure/base_url')
        );

        // reset cookie settings
        $updates[] = array(
            'data'  => array('value' => ''),
            'where' => array('path = ?' => 'web/cookie/cookie_path')
        );
        $updates[] = array(
            'data'  => array('value' => ''),
            'where' => array('path = ?' => 'web/cookie/cookie_domain')
        );

        // update contact emails
        $updates[] = array(
            'data'  => array('value' => $email),
            'where' => array('path = ?' => 'contacts/email/recipient_email')
        );
        $updates[] = array(
            'data'  => array('value' => $email),
            'where' => array('path = ?' => 'catalog/productalert_cron/error_email')
        );
        $updates[] = array(
            'data'  => array('value' => $email),
            'where' => array('path = ?' => 'sitemap/generate/error_email')
        );
        $updates[] = array(
            'data'  => array('value' => $email),
            'where' => array('path = ?' => 'sales_email/order/copy_to')
        );
        $updates[] = array(
            'data'  => array('value' => $email),
            'where' => array('path = ?' => 'sales_email/shipment/copy_to')
        );

        // Disable Google Analytics
        $updates[] = array(
            'data'  => array('value' => 0),
            'where' => array('path = ?' => 'google/analytics/active')
        );
        $updates[] = array(
            'data'  => array('value' => ''),
            'where' => array('path = ?' => 'google/analytics/account')
        );

        foreach($updates as $update) {
            $this->_db->update($this->_table('core_config_data'), $update['data'], $update['where']);
        }
    }

    public function createAdminUser($fname, $lname, $email, $login, $password)
    {
        /* Update all current users with @example.com emails 
         * this way, we wont duplicate emails 
         * eg. when imported store has same email as MT user email
         */
        $this->_db->update(
            $this->_table('admin_user'),
            array('email' => new Zend_Db_Expr("CONCAT('user',user_id,'@example.com')"))
        );

        /* add user */
        $user = array(
            $fname, $lname, $email, $login, $password, 'CURRENT_TIMESTAMP', 1
        );
        $this->_insertOrUpdate(
            'admin_user',
            'firstname,lastname,email,username,password,created,is_active',
            $user,
            'password = VALUES(password), email = VALUES(email)'
        );

        /* add role for that user */
        $subselect = new Zend_Db_Select($this->getDbAdapter());
        $subselect->from($this->_table('admin_user'), array('user_id'));
        $subselect->where('username = ?', $login);
        $table = $this->_table('admin_role');
        $data = array(
            'parent_id' => 1,
            'tree_level' => 2,
            'sort_order' => 0,
            'role_type' => 'U',
            'user_id' => new Zend_Db_Expr('('.$subselect->__toString().')'),
            'role_name' => $fname
        );
        $this->_db->insert($table, $data);
        return $this;
    }

    public function updateAdminAccount()
    {
        $this->_db->update(
            $this->_table('enterprise_admin_passwords'),
            array('expires' => strtotime('+1 year'))
        );
        return $this;
    }

    public function disableStoreCache()
    {
        $this->_db->update(
            $this->_table('core_cache_option'),
            array('value' => 0)
        );
        return $this;
    }

    public function enableLogging()
    {
        $data = array_merge(
            array('default', 0, 'dev/log/active', 1),
            array('default', 0, 'dev/log/file', 'system.log'),
            array('default', 0, 'dev/log/exception_file', 'exception.log')
        );
        $this->_insertOrUpdate(
            'core_config_data',
            'scope, scope_id, path, value',
            $data,
            'value = 1'
        );
        return $this;
    }

    public function activateDemoNotice()
    {
        $this->_insertOrUpdate(
            'core_config_data',
            'scope, scope_id, path, value',
            array('default', 0, 'design/head/demonotice', 1),
            'value = 1'
        );
        return $this;
    }

    public function updateDemoNotice()
    {
        $data = array(
            'string' => 'This is a demo store. Any orders placed through this store will not be honored or fulfilled.',
            'store_id' => 0,
            'translate' => 'This is a development store imported into Mage Testing. Please review our documentation to find out what was changed in the store in order to import that',
            'locale' => 'en_US'
        );
        $this->getDbAdapter()->insert(
            $this->_table('core_translate'),
            $data
        );
        return $this;
    }

    public function updateStoreConfigurationEmails($email)
    {
        $data = array_merge(
            array('default', 0, 'trans_email/ident_general/email', $email),
            array('default', 0, 'trans_email/ident_sales/email', $email),
            array('default', 0, 'trans_email/ident_support/email', $email),
            array('default', 0, 'trans_email/ident_custom1/email', $email),
            array('default', 0, 'trans_email/ident_custom2/email', $email),
            array('default', 0, 'contacts/email/recipient_email', $email)
        );
        $this->_insertOrUpdate(
            'core_config_data',
            'scope, scope_id, path, value',
            $data,
            'value = VALUES(value)'
        );
        return $this;
    }

    protected function _insertOrUpdate($table, $columns, $values, $update)
    {
        $setSize = $this->_prepareColumns($columns);
        $this->_db->query(
            'INSERT INTO '.$this->_table($table, true).' ('.$columns.')'.
            ' VALUES '.$this->_prepareBindingString($values, $setSize).
            ' ON DUPLICATE KEY UPDATE '.$update,
            $values
        );
        return $this;
    }
    protected function _prepareColumns(&$columns)
    {
        if(is_string($columns)) {
            $columns = explode(',', $columns);
        }
        foreach($columns as &$column) {
            $column = $this->_db->quoteIdentifier(trim($column));
        }
        $columnsCount = count($columns);
        $columns = implode(', ', $columns);
        return $columnsCount;
    }

    protected function _prepareBindingString(&$array, $set)
    {
        $set_str = '('.trim(str_repeat('?, ', $set), ', ').'), '; // (?, ?) etc.
        return trim(str_repeat($set_str, (int)(count($array)/$set)), ', ');
    }

    protected function _table($table, $escape = false)
    {
        if($escape) {
            $table = $this->getDbAdapter()->quoteIdentifier($table);
        }
        return ($this->_tablePrefix ? $this->_tablePrefix.'.'.$table : $table);
    }
}
