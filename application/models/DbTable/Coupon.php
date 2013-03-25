<?php

class Application_Model_DbTable_Coupon extends Zend_Db_Table_Abstract
{

    protected $_name = 'coupon';

    public function fetchList() {
        $select = $this->select()
                       ->setIntegrityCheck(false)
                       ->from(
                           array('c'=>'coupon'),
                           array(
                               'id' => 'id',
                               'code' => 'code',
                               'used_date' => new Zend_Db_Expr('IF(\'0000-00-00 00:00:00\' = used_date, \'\', used_date)'),
                               'duration' => 'duration',
                               'active_to' => 'active_to',
                           )
                       )
                       ->joinLeft(array('u' => 'user'), 'u.id = c.user_id', array('user' => 'u.login'))
                       ->joinLeft(array('p' => 'plan'), 'p.id = c.plan_id', array('plan' => 'p.name'));
        return $select->query()->fetchAll();
    }
}
