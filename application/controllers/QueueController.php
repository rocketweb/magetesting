<?php

class QueueController extends Integration_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {
        $instanceModel = new Application_Model_Instance();

        $timeExecution = $this->getInvokeArg('bootstrap')
                        ->getResource('config')
                ->magento
                ->instanceTimeExecution;
        $queueCounter = $instanceModel->getPendingItems($timeExecution);

        $page = (int) $this->_getParam('page', 0);
        $paginator = $instanceModel->getWholeQueue();
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);

        $this->view->queue = $paginator;
        $this->view->queueCounter = $queueCounter;
    }

    public function addAction() {
        $this->view->userGroup = $this->auth->getIdentity()->group;
    }

    public function addCleanAction() {
        $form = new Application_Form_InstanceAddClean();
        $form->populate($this->getRequest()->getParams());

        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->isPost()) {

            $userGroup = $this->auth->getIdentity()->group;

            if ($this->auth->getIdentity()->group != 'admin') {

                $versionModel = new Application_Model_Version();
                $version = $versionModel->find((int) $request->getParam('version', 0));

                if ($version->getEdition() != 'CE') {
                    $this->_helper->FlashMessenger('Hacking forbidden.');
                    return $this->_helper->redirector->gotoRoute(
                                    array(), 'default', false
                    );
                }
            }

            if ($form->isValid($this->getRequest()->getParams())) {
                //needs validation!
                $instanceModel = new Application_Model_Instance();
                $userId = $this->auth->getIdentity()->id;

                $userInstances = $instanceModel->countUserInstances($userId);

                if ($userGroup == 'free-user') {
                    $maxInstances = (int) $this->getInvokeArg('bootstrap')
                                    ->getResource('config')
                            ->magento
                            ->standardUser
                            ->instances;
                } else {
                    $modelUser = new Application_Model_User();
                    $user = $modelUser->find($this->auth->getIdentity()->id);

                    $modelPlan = new Application_Model_Plan();
                    $plan = $modelPlan->find($user->getPlanId());

                    $maxInstances = $plan->getInstances();
                }


                if ($userInstances < $maxInstances || $userGroup == 'admin') {

                    $instanceModel->setVersionId($form->version->getValue())
                            ->setEdition($form->edition->getValue())
                            ->setUserId($userId)
                            ->setSampleData($form->sample_data->getValue())
                            ->setInstanceName($form->instance_name->getValue())
                            ->setDomain(
                                    substr(
                                            str_shuffle(
                                                    str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 5)
                                            )
                                            , 0, 5) . substr(
                                            str_shuffle(
                                                    str_repeat('0123456789', 5)
                                            )
                                            , 0, 4)
                            )
                            ->setStatus('pending')
                            ->setType('clean');
                                       
                    $instanceId = $instanceModel->save();
                    
                    //TODO: Add queue item with MagentoInstall
                    $queueModel = new Application_Model_Queue();                    
                    $queueModel->setInstanceId($instanceId);
                    $queueModel->setTask('MagentoInstall');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId(1); /* TODO: select this somehow, server model method select server? */
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId(0);  
                    $queueModel->save();
                    
                    
                    $this->_helper->FlashMessenger('New installation added to queue');

                    //magetesting user creates database
                    try {
                        $db = Zend_Db_Table::getDefaultAdapter();
                        $DbManager = new Application_Model_DbTable_Privilege($db, $this->getInvokeArg('bootstrap')
                                                ->getResource('config'));
                        $DbManager->createDatabase($this->auth->getIdentity()->login . '_' . $instanceModel->getDomain());

                        if (!$DbManager->checkIfUserExists($this->auth->getIdentity()->login)) {
                            $DbManager->createUser($this->auth->getIdentity()->login);
                        }
                    } catch (PDOException $e) {
                        $message = 'Could not create database for instance, aborting';
                        echo $message;
                        if ($log = $this->getLog()) {
                            $log->log($message, LOG_ERR);
                        }
                        throw $e;
                    }
                } else {
                    $this->_helper->FlashMessenger(array('type' => 'notice', 'message' => 'You cannot have more instances.'));
                }

                return $this->_helper->redirector->gotoRoute(array(
                            'module' => 'default',
                            'controller' => 'user',
                            'action' => 'dashboard',
                                ), 'default', true);
            } else {
                $this->_helper->FlashMessenger('Form needs verification');
            }
        }
        //assign to templates
        $editionModel = new Application_Model_Edition();
        $this->view->editions = $editionModel->getAll();
        $this->view->form = $form;
    }

    public function addCustomAction() {

        $userGroup = $this->auth->getIdentity()->group;

        //deny this action for demo users
        if ($userGroup == 'demo') {
            $this->_helper->FlashMessenger(array('type' => 'error', 'message' => 'You are not allowed to have custom instance.'));
            return $this->_helper->redirector->gotoRoute(array(
                        'module' => 'default',
                        'controller' => 'user',
                        'action' => 'dashboard',
                            ), 'default', true);
        }

        $request = $this->getRequest();

        $form = new Application_Form_InstanceAddCustom();
        $form->populate($request->getParams());

        if ($request->isPost()) {

            if ($form->isValid($request->getParams())) {
                //needs validation!
                $instanceModel = new Application_Model_Instance();
                $userId = $this->auth->getIdentity()->id;

                $userInstances = $instanceModel->countUserInstances($userId);

                if ($userGroup == 'free-user') {
                    $maxInstances = (int) $this->getInvokeArg('bootstrap')
                                    ->getResource('config')
                            ->magento
                            ->standardUser
                            ->instances;
                } else {
                    $modelUser = new Application_Model_User();
                    $user = $modelUser->find($this->auth->getIdentity()->id);

                    $modelPlan = new Application_Model_Plan();
                    $plan = $modelPlan->find($user->getPlanId());

                    $maxInstances = $plan->getInstances();
                }


                if ($userInstances < $maxInstances || $userGroup == 'admin') {

                    
                    
                    //start adding instance
                    $instanceModel->setVersionId($form->version->getValue())
                            ->setEdition($form->edition->getValue())
                            ->setSampleData(1)
                            ->setInstanceName($form->instance_name->getValue())
                            ->setUserId($userId)
                            ->setDomain(
                                    substr(
                                            str_shuffle(
                                                    str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 5)
                                            )
                                            , 0, 5) . substr(
                                            str_shuffle(
                                                    str_repeat('0123456789', 5)
                                            )
                                            , 0, 4)
                            )
                            ->setStatus('pending')
                            ->setCustomProtocol($form->custom_protocol->getValue())
                            ->setCustomHost($form->custom_host->getValue())
                            ->setCustomRemotePath($form->custom_remote_path->getValue())
                            ->setCustomLogin($form->custom_login->getValue())
                            ->setCustomPass($form->custom_pass->getValue())
                            ->setCustomSql($form->custom_sql->getValue())
                            ->setType('custom');
                    $newInstanceId = $instanceModel->save();
                    $this->_helper->FlashMessenger('New installation added to queue');

                    
                    $queueModel = new Application_Model_Queue();
                    //TODO: Add queue item with MagentoDownload
                    $queueModel->setInstanceId($newInstanceId);
                    $queueModel->setTask('MagentoDownload');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId(1); /* TODO: select this somehow, server model method select server? */
                    $queueModel->setParentId(0);
                    $queueModel->setExtensionId(0);
                    $queueModel->save();
                    
                    //TODO: Add queue Item with MagentoInstall and parent id from above
                    
                    //magetesting user creates database
                    try {
                        $db = Zend_Db_Table::getDefaultAdapter();
                        $DbManager = new Application_Model_DbTable_Privilege($db, $this->getInvokeArg('bootstrap')
                                                ->getResource('config'));
                        $DbManager->createDatabase($this->auth->getIdentity()->login . '_' . $instanceModel->getDomain());

                        if (!$DbManager->checkIfUserExists($this->auth->getIdentity()->login)) {
                            $DbManager->createUser($this->auth->getIdentity()->login);
                        }
                    } catch (PDOException $e) {
                        $message = 'Could not create database for instance, aborting';
                        echo $message;
                        if ($log = $this->getLog()) {
                            $log->log($message, LOG_ERR);
                        }
                        throw $e;
                    }
                    //stop adding instance
                    $this->_helper->FlashMessenger(array('type' => 'success', 'message' => 'You have successfully added your custom instance.'));
                    return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'user',
                                'action' => 'dashboard',
                                    ), 'default', true);
                } else {
                    $this->_helper->FlashMessenger(array('type' => 'notice', 'message' => 'You cannot have more instances.'));
                }
            } else {
                $this->_helper->FlashMessenger('Form invalid');
            }
        }

        $this->view->form = $form;
    }

    public function closeAction() {
        $form = new Application_Form_InstanceClose();
        $this->view->form = $form;

        $domain = $this->getRequest()->getParam('domain');

        if ($this->getRequest()->isPost()) {

            $close = (int) $this->getRequest()->getParam('close');

            if ($close AND $domain) {                      
                
                $instanceModel = new Application_Model_Instance();
                $instanceModel->setUserId($this->auth->getIdentity()->id)
                        ->setDomain($domain);
                $byAdmin = $this->auth->GetIdentity()
                        ->group == 'admin' ? true : false;

                $instanceModel->changeStatusToClose($byAdmin);
                              
                $currentInstance = $instanceModel->findByName($domain);

                //add remove task to queue
                $queueModel = new Application_Model_Queue();
                $queueModel->setTask('MagentoRemove')
                        ->setInstanceId($currentInstance->id)
                        ->setUserId($this->auth->getIdentity()->id)
                        ->setParentId(0)
                        ->setExtensionId(0)
                        ->setServerId(1)
                        ->setStatus('pending')
                        ->save();

                $this->_helper->FlashMessenger('Store added to close queue.');
            }

            $controller = 'user';
            $action = 'dashboard';
            if ($this->_getParam('redirect', 0) === 'admin') {
                $controller = 'queue';
                $action = 'index';
            }
            return $this->_helper->redirector->gotoRoute(array(
                        'module' => 'default',
                        'controller' => $controller,
                        'action' => $action,
                    ), 'default', true);
        }
    }

    public function getversionsAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $edition = $request->getParam('edition', 'CE');
        if ($request->isPost()) {
            $versionModel = new Application_Model_Version();
            $versions = $versionModel->getAllForEdition($edition);
            if (empty($versions)) {
                $versions = array(
                    array(
                        'id' => '',
                        'version' => 'no version found')
                );
            }
            echo Zend_Json_Encoder::encode($versions);
        }
    }

    public function editAction() {
        $id = (int) $this->_getParam('id', 0);

        $instanceModel = new Application_Model_Instance();
        $instance = $instanceModel->find($id);

        if ($instance->getUserId() == $this->auth->getIdentity()->id) {
            //its ok to edit
        } else {
            if ($this->auth->getIdentity()->group != 'admin') {
                return $this->_helper->redirector->gotoRoute(array(
                            'module' => 'default',
                            'controller' => 'user',
                            'action' => 'dashboard',
                                ), 'default', true);
            }
        }

        $form = new Application_Form_InstanceEdit();
        $populate = array(
            'instance_name' => $instance->getInstanceName(),
            'backend_password' => $instance->getBackendPassword(),
            'backend_login' => $this->auth->getIdentity()->login
        );
        $form->populate($populate);

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();

            if ($form->isValid($formData)) {
                $instance->setOptions($form->getValues());
                $instance->save();

                $this->_helper->FlashMessenger('Store data has been changed successfully');
                return $this->_helper->redirector->gotoRoute(array(
                            'module' => 'default',
                            'controller' => 'user',
                            'action' => 'dashboard',
                                ), 'default', true);
            }
        }
        $this->view->form = $form;
    }

    public function extensionsAction() {

        $request = $this->getRequest();
        $instance_name = $request->getParam('instance');
        $extensionModel = new Application_Model_Extension();
        $extensions = $extensionModel->getAllForInstance($instance_name);
        if (empty($extensions)) {
            $extensions = array(
                '' => 'no extensions found'
            );
        }

        $form = new Application_Form_ExtensionInstall($extensions);
        $form->extension->setMultiOptions($extensions);

        $form->instance_name->setValue($instance_name);
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {

                //fetch queue data
                $instanceModel = new Application_Model_Instance();
                $instance = $instanceModel->findByName($instance_name);

                if (count($form->extension->getValue()) > 0) {
                    $instanceRow = $instanceModel->find($instance->id);
                    $instanceRow->setStatus('installing-extension');
                    $instanceRow->save();

                    foreach ($form->extension->getValue() as $ext) {

                        /* Adding extension to queue */
                        try {
                            $extensionQueueItem = new Application_Model_Queue();
                            $extensionQueueItem->setInstanceId($instance->id);
                            $extensionQueueItem->setStatus('pending');
                            $extensionQueueItem->setUserId($instance->user_id);
                            $extensionQueueItem->setExtensionId($ext);
                            $extensionQueueItem->setParentId(0);
                            $extensionQueueItem->setServerId(1);
                            $extensionQueueItem->setTask('ExtensionInstall');
                            $extensionQueueItem->save();
                        } catch (Exception $e) {

                            $this->_helper->FlashMessenger('Error while adding extension to queue');
                            return $this->_helper->redirector->gotoRoute(array(
                                        'module' => 'default',
                                        'controller' => 'user',
                                        'action' => 'dashboard',
                                            ), 'default', true);
                        }
                    }
                }

                $this->_helper->FlashMessenger('Extension successfully added to queue');
                return $this->_helper->redirector->gotoRoute(array(
                            'module' => 'default',
                            'controller' => 'user',
                            'action' => 'dashboard',
                                ), 'default', true
                );
                
            } else {
                $this->_helper->FlashMessenger('Error while adding extension to queue, please check the form');
            }
        }

        $installed = $extensions = $extensionModel->getInstalledForInstance($instance_name);

        $this->view->installed_extensions = $installed;
        $this->view->form = $form;
    }

    public function devextensionsAction() {
        $request = $this->getRequest();
        $instance_name = $request->getParam('instance');

        $devExtensionModel = new Application_Model_DevExtension();
        $devExtensions = $devExtensionModel->getAllForInstance($instance_name);

        $form = new Application_Form_DevExtensionInstall($devExtensions);
        $form->extension->setMultiOptions($devExtensions);
        $form->instance_name->setValue($instance_name);
        if ($request->isPost()) {
            if ($form->isValid($request->getPost())) {

                $instanceModel = new Application_Model_Instance();
                $instanceInfo = $instanceModel->findByName($this->getRequest()->getParam('instance'));

                //navigate through file list and download them
                foreach ($form->extension->getValue() as $ext) {

                    $devExt = new Application_Model_DevExtensionQueue();
                    $devExt->setDevExtensionId($ext);
                    $devExt->setQueueId($instanceInfo->id);
                    $devExt->setUserId($instanceInfo->user_id);
                    $devExt->setStatus('pending');
                    $devExt->save();
                    //////
                }
            }
        }

        //get dev extension list from database
        $this->view->form = $form;
    }
    
    public function getstatusAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $domain = $request->getParam('domain', null);
        if ($request->isPost() && $domain!=null) {
            $instanceModel = new Application_Model_Instance();
            $instanceItem = $instanceModel->findByName($domain);
            
            echo Zend_Json_Encoder::encode($instanceItem->status);
        } 
    }
    
    public function getminutesleftAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $domain = $request->getParam('domain', null);
        if ($request->isPost() && $domain!=null) {
            
            $instanceModel = new Application_Model_Instance();
            $instanceItem = $instanceModel->findPositionByName($domain);
            
            echo Zend_Json_Encoder::encode($instanceItem->num);
        } 
    }
}
