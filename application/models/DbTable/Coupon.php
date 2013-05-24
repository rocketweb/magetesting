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
                       ->joinLeft(array('u' => 'user'), 'u.id = c.user_id', array('user' => 'u.login', 'user_id' => 'u.id'))
                       ->joinLeft(array('p' => 'plan'), 'p.id = c.plan_id', array('plan' => 'p.name'));
        return $select->query()->fetchAll();
    }

    public function fetchNextFreeTrialDate($couponsPerDay) {
        $select =
            $this->select()
                 ->setIntegrityCheck(false)
                 ->from(
                     $this->_name,
                     array('coupons' => new Zend_Db_Expr('count(id)'), 'date' => new Zend_Db_Expr('date(active_to)'))
                 )
                 ->where('code LIKE ?', 'free-trial-%')
                 ->where('date(active_to) >= ?', new Zend_Db_Expr('date(CURRENT_TIMESTAMP)'))
                 ->group('date')
                 ->order('date DESC');
        return $this->fetchRow($select);
    }
}
