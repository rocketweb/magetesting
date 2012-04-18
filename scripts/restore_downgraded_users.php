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
    } catch (Zend_Console_Getopt_Exception $e) {
    );
    
    $opts->parse();
    exit($e->getMessage() . "\n\n" . $e->getUsageMessage());
 */

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
    ->from('user')
    ->joinLeft('queue','user.id = queue.user_id', 'domain')
    ->where('queue.status = ?', 'ready')
    ->where('TIMESTAMPDIFF(SECOND, CURRENT_TIMESTAMP, user.plan_active_to) > ?', 0)
    ->where('user.downgraded = ?', 1);

$result = $db->fetchAll($sql);
if($result) {
    $restore_by_id = array();
    foreach($result as $instance) {
        if(!isset($restore_by_id[$instance['id']])) {
            $restore_by_id[$instance['id']] = null;
        }
        if(!is_link(INSTANCE_PATH.$instance['domain'])) {
            $instanceFolder = $config->magento->systemHomeFolder.'/'.$config->magento->userprefix.$instance['login'].'/public_html';
            exec('ln -s '.$instanceFolder.'/'.$instance['domain'].' '.INSTANCE_PATH.$instance['domain']);
        }
    }
    if($restore_by_id) {
        $set = array(
                'group' => 'commercial-user',
                'downgraded' => 0
        );
        $where = array('id IN (?)' => array_keys($restore_by_id));
        echo 'Update: '.$db->update('user', $set, $where).PHP_EOL;
        echo 'Restored '.count($restore_by_id).' users'.PHP_EOL;
    }
} else {
    echo 'Nothing to restore from downgrade'.PHP_EOL;
}