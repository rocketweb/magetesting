<?php

/*
 * IF YOU WILL NEED EXTRA OPTIONS, USE THIS
 *
    try {
    $opts = new Zend_Console_Getopt(
            array(
                    'help' => 'Displays help.',
                    'hello' => 'try it !',
                    'downgrade_expired_users' => 'disables users with expired active to date',
                    'restore_downgraded_users' => 'restores downgraded users if we noticed their payments',
                    'magentoinstall' => 'handles magento from install queue',
                    'magentoremove' => 'handles magento from remove queue',
            )
    );
    
    $opts->parse();
    } catch (Zend_Console_Getopt_Exception $e) {
    exit($e->getMessage() . "\n\n" . $e->getUsageMessage());
 */

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->joinLeft('queue','user.id = queue.user_id', 'domain')
    ->where('queue.status = ?', 'ready')
    ->where('TIMESTAMPDIFF(SECOND,user.plan_active_to, CURRENT_TIMESTAMP) > ?', 3*60*60*24)
    ->where('(user.group IN (?)', array('awaiting-user', 'commercial-user'))
    ->orwhere('user.downgraded = ?)', 2);

$result = $db->fetchAll($sql);
if($result) {
    $downgrade_by_id = array();
    foreach($result as $instance) {
        if(!isset($downgrade_by_id[$instance['id']])) {
            $downgrade_by_id[$instance['id']] = null;
        }
        if(is_link(INSTANCE_PATH.$instance['domain'])) {
            exec('sudo rm '.INSTANCE_PATH.$instance['domain']);
        }
    }
    if($downgrade_by_id) {
        $set = array(
                'group' => 'free-user',
                'downgraded' => 1
        );
        $where = array('id IN (?)' => array_keys($downgrade_by_id));
        echo 'Update: '.$db->update('user', $set, $where).PHP_EOL;
    }
    echo 'Downgraded '.count($downgrade_by_id).' users'.PHP_EOL;
} else {
    echo 'Nothing to downgrade'.PHP_EOL;
}