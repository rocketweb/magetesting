<?php

class Application_Model_DbTable_LogReindex extends Zend_Db_Table_Abstract
{

    protected $_name = 'log_reindex';

    public function countForStore($storeId, $period)
    {
        $select = $this->select()
            ->from($this->_name, 'count(id) as count')
            ->where('store_id = ?', $storeId)
            ->where(new Zend_Db_Expr('`time` > DATE_SUB("'.date("Y-m-d H:i:s").'", INTERVAL '. (int) $period.' HOUR)'));

        $row = $this->fetchRow($select);

        return (int) $row->count;
    }
}
