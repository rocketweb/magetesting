<?php

$fp = fopen("dev_extension_install_lock.txt", "c");

if (flock($fp, LOCK_EX | LOCK_NB)) { // do an exclusive lock
    include 'init.console.php';
    //fetch custom instances start

    $select = new Zend_Db_Select($db);

    //do stuff
    $select->from('dev_extension_queue')
            ->where('status = ?', 'pending');

    $extensions = $select->query()->fetchAll();

    foreach ($extensions as $ext) {
        
        $db->update('dev_extension_queue', array('status' => 'installing'), 'id=' . $ext['id']);
        $db->update('queue', array('status' => 'installing-extension'), 'id=' . $ext['queue_id']);

        //get instance data
        $modelQueue = new Application_Model_Queue();
        $queueItem = $modelQueue->find($ext['queue_id']);

        //get extension data
        $devExtensionModel = new Application_Model_DevExtension();
        $repository = $devExtensionModel->find($ext['dev_extension_id']);

        //get user data
        $userModel = new Application_Model_User();
        $userInfo = $userModel->find($ext['user_id']);

        //prepare a logger
        $writer = new Zend_Log_Writer_Stream(APPLICATION_PATH . '/../data/logs/' . $userInfo->getLogin() . '_' . $queueItem->getDomain() . '.log');
        $log = new Zend_Log($writer);

        //////

        $fileToParse = pathinfo($repository->getExtensionConfigFile(), PATHINFO_BASENAME);

        exec('svn export --force ' .
                $repository->getRepoUrl() . '' . $repository->getExtensionConfigFile() .
                ' --username ' . $repository->getRepoUser() .
                ' --password ' . $repository->getRepoPassword() .
                ' ' . $config->magento->systemHomeFolder . '/' . $config->magento->userprefix . $userInfo->getLogin() . '/public_html/' . $queueItem->getDomain() . '/' . $fileToParse);

        $doc = new DOMDocument();
        $doc->load($config->magento->systemHomeFolder . '/' . $config->magento->userprefix . $userInfo->getLogin() . '/public_html/' . $queueItem->getDomain() . '/' . $fileToParse);

        $contents = $doc->getElementsByTagName("contents");

        for ($i = 0; $i < $contents->length; $i++) {

            $targets = $contents->item($i)->getElementsByTagName("target");
            for ($targetIterator = 0; $targetIterator < $targets->length; $targetIterator++) {
                walktree($targets->item($targetIterator), 0, '', $targets->item($targetIterator)->getAttribute("name"),$queueItem->getDomain());
            }
        }

        //clear instance cache
        exec('sudo rm -R ' . $config->magento->systemHomeFolder . '/' . $config->magento->userprefix . $userInfo->getLogin() . '/public_html/' . $queueItem->getDomain() . '/var/cache/*');

        //set extension as installed
        $db->update('queue', array('status' => 'ready'), 'id=' . $ext['queue_id']);
        $db->update('dev_extension_queue', array('status' => 'ready'), 'id=' . $ext['id']);
    }
    //finish

    flock($fp, LOCK_UN); // release the lock
    exit;
} else {
    //echo "Couldn't get the lock!";
}

fclose($fp);

function handle_node(DomNode $node, $nesting, $dir, $target,$instance_name) {
   
    global $repository,$config;
    
    $magePaths = array(
        'magelocal' => 'app/code/local',
        'magecommunity' => 'app/code/community',
        'magecore' => 'app/code/core',
        'magedesign' => 'app/design',
        'mageetc' => 'app/etc',
        'magelib' => 'lib',
        'magelocale' => 'app/locale',
        'magemedia' => 'media',
        'mageskin' => 'skin',
        'mageweb' => '',
        'magetest' => 'tests',
        'mage' => '',
    );

    if (!isset($magePaths[$target])) {
        die('Please enable support for ' . $target . ' path!!!!');
    }

    if ($node->nodeType == XML_ELEMENT_NODE) {
        if ($node->tagName == 'dir') {
            return $node->getAttribute('name');
        } elseif ($node->tagName == 'file') {

            //get necessary data
            $instanceModel = new Application_Model_Queue();
            $instanceInfo = $instanceModel->findByName($instance_name);

            $userModel = new Application_Model_User();
            $userInfo = $userModel->find($instanceInfo->user_id);

            //mkdir from pathinfo
            $pathInfo = pathinfo($magePaths[$target] . $dir . $node->getAttribute('name'));
            $newPath = $config->magento->systemHomeFolder . '/' . $config->magento->userprefix . $userInfo->getLogin() . '/public_html/' . $instanceInfo->domain . '/' . '/' . $pathInfo['dirname'];
            if (!file_exists($newPath)) {
                mkdir($newPath, 0777, true);
            }

            //copy file there
            exec('sudo svn export ' .
                    $repository->getRepoUrl() . '' . $magePaths[$target] . $dir . $node->getAttribute('name') . ' ' .
                    ' ' . $config->magento->systemHomeFolder . '/' . $config->magento->userprefix . $userInfo->getLogin() . '/public_html/' . $instanceInfo->domain . '/' .
                    $magePaths[$target] . $dir . $node->getAttribute('name') .
                    ' --username ' . $repository->getRepoUser() .
                    ' --password \'' . $repository->getRepoPassword() . '\'' .
                    ' --force');
        }
    }
}

function walktree(DomNode $node, $level = 0, $dir = '', $target,$instance_name) {

    //handle current $node 
    $dir .= handle_node($node, $level, $dir, $target,$instance_name) . '/';


    //go through kids
    if ($node->hasChildNodes()) {
        $children = $node->childNodes;
        foreach ($children as $child) {
            //echo $dir;
            if ($child->nodeType == XML_ELEMENT_NODE) {
                walktree($child, $level + 1, $dir, $target,$instance_name);
            }
        }
    }
}