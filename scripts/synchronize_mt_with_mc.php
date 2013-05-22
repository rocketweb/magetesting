<?php

include 'init.console.php';

Zend_Auth::getInstance()->getStorage()->write((object)array('group' => 'admin'));
try {
    include_once APPLICATION_PATH . '/views/helpers/ImagePath.php';
    $mt_extensions = new Application_Model_ExtensionVersionSynchronizer();
    $mt_extensions = $mt_extensions->getExtensionList();

    $mc_extensions = new Application_Model_Extension();
    $mc_extensions = $mc_extensions->getMapper()->fetchFullListOfExtensions(array('edition' => 'CE'),array(),array(),array());

    $new_extension = 0;
    $new_release = 0;
    $without_change = 0;

    $extension_files_directory = APPLICATION_PATH.'/../data/extensions/CE/open/';

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

    foreach($mt_extensions as $mt_extension => $mt_extension_v) {
        $existing_extension = false;
        foreach($mc_extensions as $mc_extension) {
            if($mt_extension == $mc_extension->extension_key) {
                $existing_extension = $mc_extension;
            }
        }
        $last_without_change = $without_change;
        if(!$existing_extension) {
            if(isset($mt_extension_v['s'])) {
                $new_extension++;
                $extension_url = 'http://connect20.magentocommerce.com/community/' . $mt_extension . '/' . $mt_extension_v['s'] . '/';
                $extensionModel = new Application_Model_Extension();
                // download extension info
                $http = new Zend_Http_Client($extension_url . 'package.xml');
                $response = $http->request();
                if(!$response->isError()) {
                    sleep(mt_rand(2, 3));
                    $xml = new SimpleXMLElement($response->getBody());
                    $extensionModel->setName(ucwords(str_replace('_', ' ', $mt_extension)));
                    $extensionModel->setExtensionKey($mt_extension);
                    $extensionModel->setDescription((string)$xml->summary[0]);
                    $extensionModel->setAuthor((string)$xml->authors->author->user[0]);
                    $extensionModel->setEdition('CE');
                    $extensionModel->setFromVersion('1.4.0.0');
                    $extensionModel->setToVersion('1.8.0.0');
                    $extensionModel->setVersion($mt_extension_v['s']);
                    $extensionModel->setPrice(0);
                    $extensionModel->setIsVisible(0);
                    $extensionModel->setSort(0);
                    $extensionModel->setCategoryId($category_id);

                    // download extension file
                    $extension_file = $mt_extension . '-' . $mt_extension_v['s'] . '.tgz';
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
            $compare = array($existing_extension->version, array_pop($mt_extension_v));
            natsort($compare);
            $new_version = array_pop($compare);
            if($existing_extension->version != $new_version) {
                sleep(mt_rand(2, 3));
                $new_release++;
                $extensionModel = new Application_Model_Extension();
                $extensionModel->addVersionToExtension($existing_extension->id, $new_version);
            } else {
                $without_change++;
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
    $log->log('Syncing MT with MC', Zend_Log::INFO, var_export($sync_info, true));
} catch(Exception $e) {
    $log->log('Syncing MT with MC', Zend_Log::ERR, $e->getMessage());
}

Zend_Auth::getInstance()->getStorage()->clear();