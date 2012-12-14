<?php

class QueueController extends Integration_Controller_Action {

    public function init() {
//        $this->_helper->noSslSwitch();
        parent::init();
    }

    public function indexAction() {
        $this->view->headScript()->appendFile($this->view->baseUrl('/public/js/queue-index.js'), 'text/javascript');
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
        
        $userModel = new Application_Model_User();
        $userModel->find($this->auth->getIdentity()->id);
        
        $planModel = new Application_Model_Plan();
        $planModel->find($userModel->getPlanId());
        
        $this->view->userGroup = $this->auth->getIdentity()->group;
        $this->view->userPlan = $planModel;
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
                            ->setServerId($this->auth->getIdentity()->server_id)
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
                            ->setStatus('installing-magento')
                            ->setType('clean');
                                       
                    $instanceId = $instanceModel->save();
                    
                    unset($queueModel);
                    //Add queue item with MagentoInstall
                    $queueModel = new Application_Model_Queue();                    
                    $queueModel->setInstanceId($instanceId);
                    $queueModel->setTask('MagentoInstall');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId(0);  
                    $queueModel->save();
                    
                    $installId = $queueModel->getId();
                    
                    unset($queueModel);
                    //Add queue item with RevisionInit
                    $queueModel = new Application_Model_Queue();                    
                    $queueModel->setInstanceId($instanceId);
                    $queueModel->setTask('RevisionInit');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId($installId);  
                    $queueModel->save();
                    $initId = $queueModel->getId();
                    
                    //Add queue item with RevisionCommit
                    $queueModel = new Application_Model_Queue();                    
                    $queueModel->setInstanceId($instanceId);
                    $queueModel->setTask('RevisionCommit');
                    $queueModel->setTaskParams(
                            array(
                                'commit_comment' => 'Initial Magento Commit',
                                'commit_type' => 'magento-init'                               
                                )
                        );
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId($initId);  
                    $queueModel->save();
                    
                    unset($queueModel);
                    //Add queue create user in Papertrail
                    if(!$this->auth->getIdentity()->has_papertrail_account) {
                        $queueModel = new Application_Model_Queue();                    
                        $queueModel->setInstanceId($instanceId);
                        $queueModel->setTask('PapertrailUserCreate');
                        $queueModel->setStatus('pending');
                        $queueModel->setUserId($this->auth->getIdentity()->id);
                        $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                        $queueModel->setExtensionId(0);  
                        $queueModel->setParentId($installId);  
                        $queueModel->save();
                    }
                    
                    unset($queueModel);
                    $queueModel = new Application_Model_Queue();                    
                    $queueModel->setInstanceId($instanceId);
                    $queueModel->setTask('PapertrailSystemCreate');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId($installId);  
                    $queueModel->save();
                    
                    $this->_helper->FlashMessenger('New installation added to queue');

                    //magetesting user creates database
                    try {
                        $log = $this->getLog();
                        $log->log($this->auth->getIdentity()->login . '_' . $instanceModel->getDomain(),LOG_DEBUG);
                        $db = Zend_Db_Table::getDefaultAdapter();
                        $DbManager = new Application_Model_DbTable_Privilege($db, $this->getInvokeArg('bootstrap')
                                                ->getResource('config'));
                        $DbManager->createDatabase($this->auth->getIdentity()->login . '_' . $instanceModel->getDomain());

                        if (!$DbManager->checkIfUserExists($this->auth->getIdentity()->login)) {
                            $DbManager->createUser($this->auth->getIdentity()->login);
                        }
                    } catch (PDOException $e) {
                        $message = 'Could not create database for store, aborting';
                        echo $message;
                        if ($log = $this->getLog()) {
                            $log->log($message, LOG_ERR);
                        }
                        throw $e;
                    }
                } else {
                    $this->_helper->FlashMessenger(array('type' => 'notice', 'message' => 'You cannot have more stores.'));
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
        $this->view->editions = $editionModel->fetchAll();
        $this->view->form = $form;
        
        $this->view->headScript()->appendFile($this->view->baseUrl('/public/js/queue-addclean.js'), 'text/javascript');
    }

    public function addCustomAction() {

        $userGroup = $this->auth->getIdentity()->group;

        //deny this action for demo users
        if ($userGroup == 'demo') {
            $this->_helper->FlashMessenger(array('type' => 'error', 'message' => 'You are not allowed to have custom store.'));
            return $this->_helper->redirector->gotoRoute(array(
                        'module' => 'default',
                        'controller' => 'user',
                        'action' => 'dashboard',
                            ), 'default', true);
        }

        $request = $this->getRequest();

        $form = new Application_Form_InstanceAddCustom();
        $form->populate($request->getParams());

        $this->view->input_radio = 'remote_path';
        if ($request->isPost()) {

            $path_type = $this->_getParam('input-radio');
            if(!in_array($path_type, array('remote_path', 'file'))) {
                $path_type = 'remote_path';
            }
            if($path_type == 'remote_path') {
                $form->custom_remote_path->setRequired(true);
                $form->custom_file->setRequired(false);
            } elseif($path_type == 'file') {
                $form->custom_remote_path->setRequired(false);
                $form->custom_file->setRequired(true);
            }
            $this->view->input_radio = $path_type;
            if ($form->isValid($request->getParams())) {
                $form->version->setValue(substr($form->version->getValue(),2));
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
                            ->setServerId($this->auth->getIdentity()->server_id)
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
                            ->setStatus('downloading-magento')
                            ->setCustomProtocol($form->custom_protocol->getValue())
                            ->setCustomHost($form->custom_host->getValue())
                            ->setCustomRemotePath($form->custom_remote_path->getValue())
                            ->setCustomLogin($form->custom_login->getValue())
                            ->setCustomPass($form->custom_pass->getValue())
                            ->setCustomSql($form->custom_sql->getValue())
                            ->setType('custom')
                            ->setCustomFile($form->custom_file->getValue());
                    $newInstanceId = $instanceModel->save();
                    
                    $queueModel = new Application_Model_Queue();
                    //TODO: Add queue item with MagentoDownload
                    $queueModel->setInstanceId($newInstanceId);
                    $queueModel->setTask('MagentoDownload');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setParentId(0);
                    $queueModel->setExtensionId(0);
                    $queueModel->save();
                    $installId = $queueModel->getId();
                    
                    unset($queueModel);
                    
                    $queueModel = new Application_Model_Queue();                    
                    $queueModel->setInstanceId($newInstanceId);
                    $queueModel->setTask('RevisionInit');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId($installId);  
                    $queueModel->save();
                    unset($queueModel);
                    
                    $queueModel = new Application_Model_Queue();                    
                    $queueModel->setInstanceId($newInstanceId);
                    $queueModel->setTask('RevisionCommit');
                    $queueModel->setTaskParams(
                            array(
                                'commit_comment' => 'Initial Magento Commit',
                                'commit_type' => 'magento-init'                               
                                )
                    );
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId($installId);  
                    $queueModel->save();
                                       
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
                        $message = 'Could not create database for store, aborting';
                        echo $message;
                        if ($log = $this->getLog()) {
                            $log->log($message, LOG_ERR);
                        }
                        throw $e;
                    }
                    //stop adding store
                    $this->_helper->FlashMessenger(array('type' => 'success', 'message' => 'You have successfully added your custom store to queue.'));
                    return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'user',
                                'action' => 'dashboard',
                                    ), 'default', true);
                } else {
                    $this->_helper->FlashMessenger(array('type' => 'notice', 'message' => 'You cannot have more stores.'));
                }
            } else {
                $this->_helper->FlashMessenger(array('type' => 'error', 'message' => 'Form invalid'));
            }
        }

        // Getting mesages from session namespace - because we don't redirect user after form-post request.
        $this->view->messages = $this->_helper->FlashMessenger->getCurrentMessages() + $this->_helper->FlashMessenger->getMessages();
        $this->_helper->FlashMessenger->clearMessages();
        $this->_helper->FlashMessenger->clearCurrentMessages();

        $this->view->form = $form;
        $this->view->headScript()->appendFile($this->view->baseUrl('/public/js/queue-addcustom.js'), 'text/javascript');
    }

    public function closeAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $form = new Application_Form_InstanceClose();

        $domain = $this->getRequest()->getParam('domain');

        $response = new stdClass();
        $response->status = 'error';

        if ($this->getRequest()->isPost()) {

            $close = (int) $this->getRequest()->getParam('close');

            if ($close AND $domain) {
                
                $instanceModel = new Application_Model_Instance();
                $instanceModel->setUserId($this->auth->getIdentity()->id)
                        ->setDomain($domain);
                $byAdmin = $this->auth->GetIdentity()
                        ->group == 'admin' ? true : false;

                $instanceModel->changeStatusToClose($byAdmin);
                              
                $currentInstance = $instanceModel->findByDomain($domain);

                $instanceModel->find($currentInstance->id);
                $instanceModel->setStatus('removing-magento')->save();
                
                //removing system from Papertrail
                $queueModel = new Application_Model_Queue();                    
                $queueModel->setInstanceId($instanceId);
                $queueModel->setTask('PapertrailSystemRemove');
                $queueModel->setStatus('pending');
                $queueModel->setUserId($this->auth->getIdentity()->id);
                $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                $queueModel->setExtensionId(0);  
                $queueModel->setParentId($installId);  
                $queueModel->save();
                
                unset($queueModel);
                //add remove task to queue
                $queueModel = new Application_Model_Queue();
                $queueModel->setTask('MagentoRemove')
                        ->setInstanceId($currentInstance->id)
                        ->setUserId($this->auth->getIdentity()->id)
                        ->setParentId(0)
                        ->setExtensionId(0)
                        ->setServerId($this->auth->getIdentity()->server_id)
                        ->setStatus('pending')
                        ->save();

                $this->_helper->FlashMessenger('Store has been removed.');
            }
        }
        $redirect_to = array(
            'module' => 'default',
            'controller' => 'user',
            'action' => 'dashboard',
        );
        if($this->_getParam('redirect') == 'admin') {
            $redirect_to['controller'] = 'queue';
            $redirect_to['action'] = 'index';
        }
        return $this->_helper->redirector->gotoRoute($redirect_to, 'default', true);
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
        $this->view->instance = $instance = $instanceModel->find($id);

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

        $form = new Application_Form_InstanceEdit($instance->getStatus() == 'pending');
        $populate = array_merge(
            array(
                'instance_name' => $instance->getInstanceName(),
                'backend_password' => $instance->getBackendPassword(),
                'backend_login' => $this->auth->getIdentity()->login
            ),
            $instance->__toArray(),
            array('custom_pass_confirm' => $instance->getCustomPass())
        );
        $form->populate($populate);

        if ($this->_request->isPost()) {

            if ($form->isValid($this->_request->getPost())) {
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
        $extensionCategoryModel = new Application_Model_ExtensionCategory();
        $this->view->extensions = $extensions = $extensionModel->fetchInstanceExtensions($instance_name);
        $this->view->categories = $extensionCategoryModel->fetchAll();

        $this->view->instance_name = $instance_name;
        if ($request->isPost()) {
            $not_installed = true;
            foreach($extensions as $extension) {
                if($extension['id'] == $request->getParam('extension_id') AND (int)$extension['instance_id']) {
                    $not_installed = false;
                }
            }
            if($not_installed) {
                

                //fetch queue data
                $instanceModel = new Application_Model_Instance();
                $instance = $instanceModel->findByDomain($instance_name);

                if ((int)$request->getParam('extension_id') > 0) {
                    $instanceRow = $instanceModel->find($instance->id);
                    if ($instanceRow->getStatus() == 'ready') {
                        $instanceRow->setStatus('installing-extension');
                        $instanceRow->save();
                    }

                    /* Adding extension to queue */
                    try {
                        
                        /** 
                         * Find if we have any other ExtensionInstall tasks 
                         * for this store 
                         */
                        $extensionQueueItem = new Application_Model_Queue();
                        $extensionParent = $extensionQueueItem->getParentIdForExtensionInstall($instance->id);
                        
                        $extensionId = $request->getParam('extension_id');
                        $extensionQueueItem->setInstanceId($instance->id);
                        $extensionQueueItem->setStatus('pending');
                        $extensionQueueItem->setUserId($instance->user_id);
                        $extensionQueueItem->setExtensionId($extensionId);
                        $extensionQueueItem->setParentId($extensionParent);
                        $extensionQueueItem->setServerId($instance->server_id);
                        $extensionQueueItem->setTask('ExtensionInstall');
                        $extensionQueueItem->save();

                        /* Get extension data and add commit task */
                        $extensionModel = new Application_Model_Extension();
                        $extensionModel->find($request->getParam('extension_id'));

                        $queueId = $extensionQueueItem->getId();
                        $queueModel = new Application_Model_Queue();
                        $queueModel->setInstanceId($instance->id);
                        $queueModel->setStatus('pending');
                        $queueModel->setUserId($instance->user_id);
                        $queueModel->setExtensionId($extensionId);
                        $queueModel->setParentId($queueId);
                        $queueModel->setServerId($instance->server_id);
                        $queueModel->setTask('RevisionCommit');
                        $queueModel->setTaskParams(
                                array(
                                    'commit_comment' => 'Adding ' . $extensionModel->getName() . ' (' . $extensionModel->getVersion() . ')',
                                    'commit_type' => 'extension-install'
                                )
                        );
                        $queueModel->save();

                        //add row to instance_extension
                        $instanceExtensionModel = new Application_Model_InstanceExtension();
                        $instanceExtensionModel->setInstanceId($instance->id);
                        $instanceExtensionModel->setExtensionId($extensionId);
                        $instanceExtensionModel->save();

                        echo 'done';
                    } catch (Exception $e) {
                        if ($log = $this->getLog()) {
                            $log->log('Error while adding extension to queue - ' . $e->getMessage(), LOG_ERR);
                        }
                        echo 'error';
                    }
                }
            } else {
                echo 'already_installed';
            }
            $this->_helper->layout()->disableLayout(); 
            $this->_helper->viewRenderer->setNoRender(true);
        }
    }
   
    public function getstatusAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $domain = $request->getParam('domain', null);
        if ($request->isPost() && $domain!=null) {
            $instanceModel = new Application_Model_Instance();
            $instanceItem = $instanceModel->findByDomain($domain);
            
            echo Zend_Json_Encoder::encode($instanceItem->status);
        } 
    }
    
    public function getminutesleftAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $domain = $request->getParam('domain', null);
        if ($request->isPost() && $domain!=null) {
            
            $timeExecution = $this->getInvokeArg('bootstrap')
                        ->getResource('config')
                ->magento
                ->instanceTimeExecution;
            
            $instanceModel = new Application_Model_Instance();
            $instanceItem = $instanceModel->findPositionByName($domain);
            
            echo Zend_Json_Encoder::encode($instanceItem->num*$timeExecution);
        } 
    }
    
    public function commitAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $domain = $this->getRequest()->getParam('domain');        
        $instanceModel=  new Application_Model_Instance();
        $instance = $instanceModel->findByDomain($domain);      
                
        $queueModel = new Application_Model_Queue();
        $queueModel->setTask('RevisionCommit');
        $queueModel->setTaskParams(
            array(
                'commit_type'=>'manual',
                'commit_comment' => $this->getRequest()->getParam('commit_comment')
            )
        );
        $queueModel->setInstanceId($instance->id);
        $queueModel->setServerId($instance->server_id);
        $queueModel->setParentId(0);
        $queueModel->setExtensionId(0);
        $queueModel->setAddedDate(date("Y-m-d H:i:s"));
        $queueModel->setStatus('pending');
        $queueModel->setUserId($this->auth->getIdentity()->id);
        $queueModel->save();
        
        $instanceModel->find($instance->id);
        $instanceModel->setStatus('committing-revision')->save();
        $this->_helper->FlashMessenger('Files has been scheduled to commit them into repository.');
        return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'user',
                'action' => 'dashboard',
        ), 'default', true);
    }

    public function deployAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        // $this->_getParam('domain');
        // $this->_getParam('deploy'); - revision id
                
        $instanceModel->setStatus('deploying-revision')->save();
        
        $this->_helper->FlashMessenger('Deployment action has been added to queue.');
        return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'user',
                'action' => 'dashboard',
        ), 'default', true);
    }

    public function rollbackAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        
        $domain = $this->getRequest()->getParam('domain');
        
        /* get last revision for this domain */
        $instanceModel = new Application_Model_Instance();
        $instance = $instanceModel->findByDomain($domain);
        
        $revisionModel = new Application_Model_Revision();
        $revisionModel->getLastForInstance($instance->id);
        
        /* add task with RevisionRollback */
        $queueModel = new Application_Model_Queue();
        $queueModel->setInstanceId($instance->id);
        $queueModel->setStatus('pending');
        $queueModel->setUserId($instance->user_id);
        $queueModel->setExtensionId($revisionModel->getExtensionId());
        $queueModel->setParentId(0);
        $queueModel->setServerId($instance->server_id);
        $queueModel->setTask('RevisionRollback');
        $queueModel->setTaskParams(
            array(
                'rollback_files_to' => $revisionModel->getHash(),
                'rollback_db_to' => $revisionModel->getDbBeforeRevision(),
                )

        );
        $queueModel->save();
        
        $instanceModel->find($instance->id);
        $instanceModel->setStatus('rolling-back-revision')->save();
        
        $this->_helper->FlashMessenger('Rollback action has been added to queue.');
        return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'user',
                'action' => 'dashboard',
        ), 'default', true);
    }

    public function requestDeploymentAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $domain = $this->getParam('domain');
        $revision = $this->getParam('revision', 0);
        $instance = null;
        if($domain) {
            $model = new Application_Model_Instance();
            $instance = $model->findByDomain($domain);
        }
        if(
                is_object($instance) AND
                (int)$instance->id AND
                $instance->user_id == $this->auth->getIdentity()->id AND
                is_numeric($revision) AND
                (int)$revision > 0
        ) {
            $revisionModel = new Application_Model_Revision;
            $revisionModel->find($this->_getParam('revision'));

            /* Disallow requesting initial commits */
            if ($revisionModel->getType()=='magento-init'){
                
                $this->_helper->FlashMessenger(array('type' => 'notice', 'message' => 'There is no such revision.'));

                return $this->_helper->redirector->gotoRoute(array(
                    'module' => 'default',
                    'controller' => 'user',
                    'action' => 'dashboard',
                ), 'default', true);
            }
            
            
            $domain = $this->_getParam('domain');        
            $instanceModel=  new Application_Model_Instance();
            $instance = $instanceModel->findByDomain($domain);      

            $queueModel = new Application_Model_Queue();
            $queueModel->setTask('RevisionDeploy');
            $queueModel->setTaskParams(
                array(
                    'revision_id'=> $this->_getParam('revision')
                )
            );
           
            $queueModel->setInstanceId($instance->id);
            $queueModel->setServerId($instance->server_id);
            $queueModel->setParentId(0);
            $queueModel->setExtensionId($revisionModel->getExtensionId());
            $queueModel->setAddedDate(date("Y-m-d H:i:s"));
            $queueModel->setStatus('pending');
            $queueModel->setUserId($this->auth->getIdentity()->id);
            $queueModel->save();
            
            $instanceModel->find($instance->id);
            $instanceModel->setStatus('deploying-revision')->save();
            
            $this->_helper->FlashMessenger('Requested.');
        }
        return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'user',
                'action' => 'dashboard',
        ), 'default', true);
    }

    public function fetchDeploymentListAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $domain = $this->getParam('domain');
        $instance = null;
        if($domain) {
            $model = new Application_Model_Instance();
            $instance = $model->findByDomain($domain);
        }
        $content = '';
        if(
            is_object($instance) AND
            (int)$instance->id AND
            $instance->user_id == $this->auth->getIdentity()->id
        ) {
            $model = new Application_Model_Revision();
            foreach($model->getAllForInstance($instance->id) as $revision) {
                if($revision['type']=='magento-init'){
                    continue;
                }
                $content .= '<tr>'.PHP_EOL;
                $content .= '<td>'.$revision['comment'].'</td>'.PHP_EOL;
                $content .= '<td>'.PHP_EOL;
                    //<button class="btn" type="submit" name="deploy" value="'.$revision['id'].'">Deploy</button>
                $download_button = '<a class="btn btn-primary download-deployment" href="'.
                    $this->view->url(array('module' => 'default', 'controller' => 'instance', 'action' => $domain), 'default', true).'/var/deployment/'.$revision['filename']
                .'">Download</a>'.PHP_EOL;
                $request_button = '<button type="submit" class="btn request-deployment" name="revision" value="'.$revision['id'].'">Request Deployment</a>'.PHP_EOL;
                $content .= (!$revision['filename'] ? $request_button : $download_button).PHP_EOL;
                $content .= '</td>'.PHP_EOL;
                $content .= '</tr>'.PHP_EOL;
            }
        }
        echo $content;
    }
}
