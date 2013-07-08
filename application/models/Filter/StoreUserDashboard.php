<?php

class Application_Model_Filter_StoreUserDashboard
    implements Zend_Filter_Interface
{
    public function filter($items)
    {
        foreach($items as & $row) {
            if('error' === $row['status']) {
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();
                $select =
                    $db
                        ->select()
                        ->from('store_log')
                        ->where('store_id = ?', $row['id'])
                        ->where('lvl = 3')
                        ->limit(1)
                        ->order('id DESC');

                $result = $db->fetchRow($select);
                if($result) {
                    $row['error_message'] = $result['msg'];
                }
            }
        }

        return $items;
    }
}