<?php
/**
 * Class gets database data from payment table
 * @author Grzegorz (golaod)
 * @package Application_Model_DbTable_Payment
 */
class Application_Model_DbTable_Payment extends Zend_Db_Table_Abstract
{

    protected $_name = 'payment';

    public function fetchPaymentsByUser($id)
    {
        $select = $this->select()
                       ->where('user_id = ?', $id)
                       ->order('date DESC');

        return $this->fetchAll($select);
    }
}
