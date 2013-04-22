<?php
/**
 * Class gets database data from plan table
 * @author Grzegorz (golaod)
 * @package Application_Model_DbTable_Plan
 */
class Application_Model_DbTable_Plan extends Zend_Db_Table_Abstract
{
    protected $_name = 'plan';

    public function fetchList() {
        $select = $this->select();
        return $select->query()->fetchAll();
    }
}