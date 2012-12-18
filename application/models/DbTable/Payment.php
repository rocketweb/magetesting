<?php
/**
 * Class gets database data from payment table
 * @author Grzegorz (golaod)
 * @package Application_Model_DbTable_Payment
 */
class Application_Model_DbTable_Payment extends Zend_Db_Table_Abstract
{

    protected $_name = 'payment';

    public function fetchPaymentsByUser($id, $limit = false)
    {
        $select = $this->select()
                       ->where('user_id = ?', $id)
                       ->order('date DESC');
        if($limit) {
            $select->limit(1);
        }

        return $this->fetchAll($select);
    }
}
