<?php

include 'init.console.php';

Zend_Auth::getInstance()->getStorage()->write((object)array('group' => 'admin'));
try {
    include_once APPLICATION_PATH . '/views/helpers/ImagePath.php';
    $editions = array(
        'CE' => array('from' => '1.4.0.0', 'to' => '1.8.0.0'),
        'EE' => array('from' => '1.9.1.1', 'to' => '1.13.0.2'),
    );
    foreach ($editions as $edition => $range) {
        $mc_extensions = new Application_Model_ExtensionVersionSynchronizer();
        $mc_extensions = $mc_extensions->getExtensionList();

        $mt_extensions = new Application_Model_Extension();
        $mt_extensions = $mt_extensions->getMapper()->fetchFullListOfExtensions(array('price' => 'free', 'edition' => $edition),array(),0,0);

        $new_extension = 0;
        $new_release = 0;
        $without_change = 0;

        $extension_files_directory = APPLICATION_PATH.'/../data/extensions/'.$edition.'/open/';

        $categories = new Application_Model_ExtensionCategory();
        $category_id = 0;
        foreach($categories->fetchAll() as $row) {
            if('Other' == $row->getName()) {
                $category_id = $row->getId();
            }
        }
        if(!$category_id) {
            throw new Exception('There is no "other" extension category.');
        }

        foreach($mc_extensions as $mc_extension_key => $mc_extension_v) {
            $existing_extensions = array();
            foreach($mt_extensions as $mt_extension) {
                if($mc_extension_key == $mt_extension->extension_key) {
                    $existing_extensions[] = $mt_extension;
                }
            }

            $last_without_change = $without_change;
            if(!$existing_extensions) {
                if(isset($mc_extension_v['s'])) {
                    $new_extension++;
                    $extension_url = 'http://connect20.magentocommerce.com/community/' . $mc_extension_key . '/' . $mc_extension_v['s'] . '/';
                    $extensionModel = new Application_Model_Extension();
                    // download extension info
                    $http = new Zend_Http_Client($extension_url . 'package.xml');
                    $response = $http->request();
                    if(!$response->isError()) {
                        sleep(mt_rand(2, 3));
                        $xml = new SimpleXMLElement($response->getBody());
                        $extensionModel->setName(ucwords(str_replace('_', ' ', $mc_extension_key)));
                        $extensionModel->setExtensionKey($mc_extension_key);
                        $extensionModel->setDescription(preg_replace('/\&lt\;[^\&]*\&gt\;/i', '', (string)$xml->summary[0]));
                        $extensionModel->setAuthor((string)$xml->authors->author->name[0]);
                        $extensionModel->setEdition($edition);
                        $extensionModel->setFromVersion($range['from']);
                        $extensionModel->setToVersion($range['to']);
                        $extensionModel->setVersion($mc_extension_v['s']);
                        $extensionModel->setPrice(0);
                        $extensionModel->setIsVisible(0);
                        $extensionModel->setSort(0);
                        $extensionModel->setCategoryId($category_id);

                        // download extension file
                        $extension_file = $mc_extension_key . '-' . $mc_extension_v['s'] . '.tgz';
                        $http = new Zend_Http_Client($extension_url . $extension_file);
                        $response = $http->request();
                        if(!$response->isError()) {
                            if(!file_exists($extension_files_directory)) {
                                @mkdir($extension_files_directory, 0777, true);
                            }
                            file_put_contents($extension_files_directory . $extension_file, $response->getBody());
                            $extensionModel->setExtension($extension_file);
                        }
                        $extensionModel->save();
                    }
                } else {
                    $without_change++;
                }
            } else {
                foreach($existing_extensions as $update_extension) {
                    $compare = array($update_extension->version, array_pop($mc_extension_v));
                    natsort($compare);
                    $new_version = array_pop($compare);
                    if($update_extension->version != $new_version) {
                        sleep(mt_rand(2, 3));
                        $new_release++;
                        $extensionModel = new Application_Model_Extension();
                        $extensionModel->addVersionToExtension($update_extension->id, $new_version);
                    } else {
                        $without_change++;
                    }
                }
            }

            // it will skeep sleeping for extension without sleep
            // thanks to that checking 4k extensions without change will not execute
            // for 4k seconds
            if($last_without_change == $without_change) {
                sleep(1);
            }
        }

        $sync_info = array(
            'New extensions' => $new_extension,
            'New releases' => $new_release,
            'No change' => $without_change,
            'Checked extensions' => ($new_extension+$new_release+$without_change),
            'MT extensions' => count($mt_extensions)
        );
        $log->log('Syncing MT with MC for ' . $edition . ' edition.', Zend_Log::INFO, var_export($sync_info, true));
    }
} catch(Exception $e) {
    $log->log('Syncing MT with MC', Zend_Log::ERR, $e->getMessage());
}

Zend_Auth::getInstance()->getStorage()->clear();


/* script which was used to update extensions with author "auto-converted"

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql = $select
->from('extension')
->where('author = ?', 'auto-converted');

$update_info = array(
        'updated' => 0,
        'extensions_to_update' => 0,
        'errors' => 0,
);
$result = $db->fetchAll($sql);
if($result) {
    $update_info['extensions_to_update'] = count($result);
    $magento_url = 'http://connect20.magentocommerce.com/community/';
    foreach($result as $row) {
        try {
            $http = new Zend_Http_Client($magento_url . $row['extension_key'] . '/' . $row['version'] . '/package.xml');
            $response = $http->request();
            if(!$response->isError()) {
                $xml = new SimpleXMLElement($response->getBody());
                $set = array(
                    'author' => (string)$xml->authors->author->name[0]
                );
                $where = array(
                    'id = ?' => $row['id']
                );
                $db->update('extension', $set, $where);
                $update_info['updated'] += 1;
            }
            $rand = array(500000,740000, 1000000);
            shuffle($rand);
            usleep(array_pop($rand));
        } catch(Exception $e) {
            $log->log('Fixing "auto-converted" author in extensions', Zend_Log::ERR, $e->getMessage());
            $update_info['errors'] += 1;
        }
    }
}

$log->log('Fixing "auto-converted" author in extensions', Zend_Log::INFO, var_export($update_info, true));
*/

/* script which was used to remove html tags from description

include 'init.console.php';

$select = new Zend_Db_Select($db);
$sql =
    $select
        ->from('extension', array('id', 'description'))
        ->where('description REGEXP ?', '\&[a-z0-9A-Z]+\;');

$result = $db->fetchAll($sql);
if($result) {
    foreach($result as $row) {
        $db->update(
            'extension',
            array('description' => preg_replace('/\&lt\;[^\&]*\&gt\;/i', '', $row['description'])),
            array('id = ?' => $row['id'])
        );
    }
}
*/