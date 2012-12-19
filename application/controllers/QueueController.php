<?php

class QueueController extends Integration_Controller_Action {

    public function init() {
//        $this->_helper->noSslSwitch();
        parent::init();
    }

    public function indexAction() {
        $this->view->headScript()->appendFile($this->view->baseUrl('/public/js/queue-index.js'), 'text/javascript');
        $storeModel = new Application_Model_Store();

        $timeExecution = $this->getInvokeArg('bootstrap')
                        ->getResource('config')
                ->magento
                ->storeTimeExecution;
        $queueCounter = $storeModel->getPendingItems($timeExecution);

        $page = (int) $this->_getParam('page', 0);
        $paginator = $storeModel->getWholeQueue();
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
        $form = new Application_Form_StoreAddClean();
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
                $storeModel = new Application_Model_Store();
                $userId = $this->auth->getIdentity()->id;

                $userStores = $storeModel->countUserStores($userId);

                if ($userGroup == 'free-user') {
                    $maxStores = (int) $this->getInvokeArg('bootstrap')
                                    ->getResource('config')
                            ->magento
                            ->standardUser
                            ->stores;
                } else {
                    $modelUser = new Application_Model_User();
                    $user = $modelUser->find($this->auth->getIdentity()->id);

                    $modelPlan = new Application_Model_Plan();
                    $plan = $modelPlan->find($user->getPlanId());

                    $maxStores = $plan->getStores();
                }


                if ($userStores < $maxStores || $userGroup == 'admin') {

                    $storeModel->setVersionId($form->version->getValue())
                            ->setEdition($form->edition->getValue())
                            ->setUserId($userId)
                            ->setServerId($this->auth->getIdentity()->server_id)
                            ->setSampleData($form->sample_data->getValue())
                            ->setStoreName($form->store_name->getValue())
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
                                       
                    $storeId = $storeModel->save();
                    
                    unset($queueModel);
                    //Add queue item with MagentoInstall
                    $queueModel = new Application_Model_Queue();                    
                    $queueModel->setStoreId($storeId);
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
                    $queueModel->setStoreId($storeId);
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
                    $queueModel->setStoreId($storeId);
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
                        $queueModel->setStoreId($storeId);
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
                    $queueModel->setStoreId($storeId);
                    $queueModel->setTask('PapertrailSystemCreate');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId($installId);  
                    $queueModel->save();
                    
                    $this->_helper->FlashMessenger('New installation added to queue');

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

        $form = new Application_Form_StoreAddCustom();
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
                $storeModel = new Application_Model_Store();
                $userId = $this->auth->getIdentity()->id;

                $userStores = $storeModel->countUserStores($userId);

                if ($userGroup == 'free-user') {
                    $maxStores = (int) $this->getInvokeArg('bootstrap')
                                    ->getResource('config')
                            ->magento
                            ->standardUser
                            ->stores;
                } else {
                    $modelUser = new Application_Model_User();
                    $user = $modelUser->find($this->auth->getIdentity()->id);

                    $modelPlan = new Application_Model_Plan();
                    $plan = $modelPlan->find($user->getPlanId());

                    $maxStores = $plan->getStores();
                }


                if ($userStores < $maxStores || $userGroup == 'admin') {

                    
                    
                    //start adding store
                    $storeModel->setVersionId($form->version->getValue())
                            ->setEdition($form->edition->getValue())
                            ->setSampleData(1)
                            ->setStoreName($form->store_name->getValue())
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
                    $newStoreId = $storeModel->save();
                    
                    $queueModel = new Application_Model_Queue();
                    //TODO: Add queue item with MagentoDownload
                    $queueModel->setStoreId($newStoreId);
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
                    $queueModel->setStoreId($newStoreId);
                    $queueModel->setTask('RevisionInit');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId($installId);  
                    $queueModel->save();
                    unset($queueModel);
                    
                    $queueModel = new Application_Model_Queue();                    
                    $queueModel->setStoreId($newStoreId);
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

        $form = new Application_Form_StoreClose();

        $domain = $this->getRequest()->getParam('domain');

        $response = new stdClass();
        $response->status = 'error';

        if ($this->getRequest()->isPost()) {

            $close = (int) $this->getRequest()->getParam('close');

            if ($close AND $domain) {
                
                $storeModel = new Application_Model_Store();
                $storeModel->setUserId($this->auth->getIdentity()->id)
                        ->setDomain($domain);
                $byAdmin = $this->auth->GetIdentity()
                        ->group == 'admin' ? true : false;

                $storeModel->changeStatusToClose($byAdmin);
                              
                $currentStore = $storeModel->findByDomain($domain);

                $storeModel->find($currentStore->id);
                $storeModel->setStatus('removing-magento')->save();
                
                //removing system from Papertrail
                $queueModel = new Application_Model_Queue();
                $queueModel->setStoreId($currentStore->id);
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
                        ->setStoreId($currentStore->id)
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

            $this->_response->setBody(Zend_Json_Encoder::encode($versions));
        }
    }

    public function editAction() {
        $id = (int) $this->_getParam('id', 0);

        $storeModel = new Application_Model_Store();
        $this->view->store = $store = $storeModel->find($id);

        if ($store->getUserId() == $this->auth->getIdentity()->id) {
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

        $form = new Application_Form_StoreEdit($store->getStatus() == 'pending');
        $populate = array_merge(
            array(
                'store_name' => $store->getStoreName(),
                'backend_password' => $store->getBackendPassword(),
                'backend_login' => $this->auth->getIdentity()->login
            ),
            $store->__toArray(),
            array('custom_pass_confirm' => $store->getCustomPass())
        );
        $form->populate($populate);

        if ($this->_request->isPost()) {

            if ($form->isValid($this->_request->getPost())) {
                $store->setOptions($form->getValues());
                $store->save();

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
        $store_name = $request->getParam('store');
        $extensionModel = new Application_Model_Extension();
        $extensionCategoryModel = new Application_Model_ExtensionCategory();
        $this->view->extensions = $extensions = $extensionModel->fetchStoreExtensions($store_name);
        $this->view->categories = $extensionCategoryModel->fetchAll();

        $this->view->store_name = $store_name;
        if ($request->isPost()) {
            $not_installed = true;
            foreach($extensions as $extension) {
                if($extension['id'] == $request->getParam('extension_id') AND (int)$extension['store_id']) {
                    $not_installed = false;
                }
            }
            if($not_installed) {
                

                //fetch queue data
                $storeModel = new Application_Model_Store();
                $store = $storeModel->findByDomain($store_name);

                if ((int)$request->getParam('extension_id') > 0) {
                    $storeRow = $storeModel->find($store->id);
                    if ($storeRow->getStatus() == 'ready') {
                        $storeRow->setStatus('installing-extension');
                        $storeRow->save();
                    }

                    /* Adding extension to queue */
                    try {
                        
                        /** 
                         * Find if we have any other ExtensionInstall tasks 
                         * for this store 
                         */
                        $extensionQueueItem = new Application_Model_Queue();
                        $extensionParent = $extensionQueueItem->getParentIdForExtensionInstall($store->id);
                        
                        $extensionId = $request->getParam('extension_id');
                        $extensionQueueItem->setStoreId($store->id);
                        $extensionQueueItem->setStatus('pending');
                        $extensionQueueItem->setUserId($store->user_id);
                        $extensionQueueItem->setExtensionId($extensionId);
                        $extensionQueueItem->setParentId($extensionParent);
                        $extensionQueueItem->setServerId($store->server_id);
                        $extensionQueueItem->setTask('ExtensionInstall');
                        $extensionQueueItem->save();

                        /* Get extension data and add commit task */
                        $extensionModel = new Application_Model_Extension();
                        $extensionModel->find($request->getParam('extension_id'));

                        $queueId = $extensionQueueItem->getId();
                        $queueModel = new Application_Model_Queue();
                        $queueModel->setStoreId($store->id);
                        $queueModel->setStatus('pending');
                        $queueModel->setUserId($store->user_id);
                        $queueModel->setExtensionId($extensionId);
                        $queueModel->setParentId($queueId);
                        $queueModel->setServerId($store->server_id);
                        $queueModel->setTask('RevisionCommit');
                        $queueModel->setTaskParams(
                                array(
                                    'commit_comment' => 'Adding ' . $extensionModel->getName() . ' (' . $extensionModel->getVersion() . ')',
                                    'commit_type' => 'extension-install'
                                )
                        );
                        $queueModel->save();

                        //add row to store_extension
                        $storeExtensionModel = new Application_Model_StoreExtension();
                        $storeExtensionModel->setStoreId($store->id);
                        $storeExtensionModel->setExtensionId($extensionId);
                        $storeExtensionModel->save();

                        $this->_response->setBody('done');
                    } catch (Exception $e) {
                        if ($log = $this->getLog()) {
                            $log->log('Error while adding extension to queue - ' . $e->getMessage(), LOG_ERR);
                        }
                        $this->_response->setBody('error');
                    }
                }
            } else {
                $this->_response->setBody('already_installed');
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
            $storeModel = new Application_Model_Store();
            $storeItem = $storeModel->findByDomain($domain);
            
            $this->_response->setBody(Zend_Json_Encoder::encode($storeItem->status));
        } 
    }
    
    public function gettimeleftAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $domain = $request->getParam('domain', null);
        if ($request->isPost() && $domain!=null) {
            
            $timeExecution = $this->getInvokeArg('bootstrap')
                                  ->getResource('config')
                                  ->magento
                                  ->storeTimeExecution;
            
            $storeModel = new Application_Model_Store();
            $storeItem = $storeModel->findPositionByName($domain);
            
            $this->_response->setBody(Zend_Json_Encoder::encode($storeItem->num * $timeExecution));
        } 
    }
    
    public function commitAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $domain = $this->getRequest()->getParam('domain');        
        $storeModel=  new Application_Model_Store();
        $store = $storeModel->findByDomain($domain);      
                
        $queueModel = new Application_Model_Queue();
        $queueModel->setTask('RevisionCommit');
        $queueModel->setTaskParams(
            array(
                'commit_type'=>'manual',
                'commit_comment' => $this->getRequest()->getParam('commit_comment')
            )
        );
        $queueModel->setStoreId($store->id);
        $queueModel->setServerId($store->server_id);
        $queueModel->setParentId(0);
        $queueModel->setExtensionId(0);
        $queueModel->setAddedDate(date("Y-m-d H:i:s"));
        $queueModel->setStatus('pending');
        $queueModel->setUserId($this->auth->getIdentity()->id);
        $queueModel->save();
        
        $storeModel->find($store->id);
        $storeModel->setStatus('committing-revision')->save();
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
                
        $storeModel->setStatus('deploying-revision')->save();
        
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
        $storeModel = new Application_Model_Store();
        $store = $storeModel->findByDomain($domain);
        
        $revisionModel = new Application_Model_Revision();
        $revisionModel->getLastForStore($store->id);
        
        /* add task with RevisionRollback */
        $queueModel = new Application_Model_Queue();
        $queueModel->setStoreId($store->id);
        $queueModel->setStatus('pending');
        $queueModel->setUserId($store->user_id);
        $queueModel->setExtensionId($revisionModel->getExtensionId());
        $queueModel->setParentId(0);
        $queueModel->setServerId($store->server_id);
        $queueModel->setTask('RevisionRollback');
        $queueModel->setTaskParams(
            array(
                'rollback_files_to' => $revisionModel->getHash(),
                'rollback_db_to' => $revisionModel->getDbBeforeRevision(),
                )

        );
        $queueModel->save();
        
        $storeModel->find($store->id);
        $storeModel->setStatus('rolling-back-revision')->save();
        
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
        $store = null;
        if($domain) {
            $model = new Application_Model_Store();
            $store = $model->findByDomain($domain);
        }
        if(
                is_object($store) AND
                (int)$store->id AND
                $store->user_id == $this->auth->getIdentity()->id AND
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
            
            // disallow deploying not paid extension
            if($revisionModel->getExtensionId()) {
                $extension = new Application_Model_StoreExtension();
                $extension->fetchStoreExtension($revisionModel->getStoreId(), $revisionModel->getExtensionId());
                if(!(int)$extension->getBraintreeTransactionConfirmed()) {
                    $this->_helper->FlashMessenger(array('type' => 'notice', 'message' => 'Deploying not paid extension is forbidden.'));
                    
                    return $this->_helper->redirector->gotoRoute(array(
                            'module' => 'default',
                            'controller' => 'user',
                            'action' => 'dashboard',
                    ), 'default', true);
                }
            } 
            $domain = $this->_getParam('domain');        
            $storeModel=  new Application_Model_Store();
            $store = $storeModel->findByDomain($domain);      

            $queueModel = new Application_Model_Queue();
            $queueModel->setTask('RevisionDeploy');
            $queueModel->setTaskParams(
                array(
                    'revision_id'=> $this->_getParam('revision')
                )
            );
           
            $queueModel->setStoreId($store->id);
            $queueModel->setServerId($store->server_id);
            $queueModel->setParentId(0);
            $queueModel->setExtensionId($revisionModel->getExtensionId());
            $queueModel->setAddedDate(date("Y-m-d H:i:s"));
            $queueModel->setStatus('pending');
            $queueModel->setUserId($this->auth->getIdentity()->id);
            $queueModel->save();
            
            $storeModel->find($store->id);
            $storeModel->setStatus('deploying-revision')->save();
            
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
        $store = null;
        if($domain) {
            $model = new Application_Model_Store();
            $store = $model->findByDomain($domain);
        }
        $content = '';
        if(
            is_object($store) AND
            (int)$store->id AND
            $store->user_id == $this->auth->getIdentity()->id
        ) {
            $model = new Application_Model_Revision();
            foreach($model->getAllForStore($store->id) as $revision) {
                if($revision['type']=='magento-init'){
                    continue;
                }
                $content .= '<tr>'.PHP_EOL;
                $content .= '<td>'.$revision['comment'].'</td>'.PHP_EOL;
                $content .= '<td>'.PHP_EOL;
                    //<button class="btn" type="submit" name="deploy" value="'.$revision['id'].'">Deploy</button>
                $download_button = '<a class="btn btn-primary download-deployment" href="'.
                    $this->view->url(array('module' => 'default', 'controller' => 'store', 'action' => $domain), 'default', true).'/var/deployment/'.$revision['filename']
                .'">Download</a>'.PHP_EOL;
                if((int)$revision['extension_id'] AND !$revision['braintree_transaction_id']) {
                    $request_button = '<button type="submit" data-store-domain="'.$domain.'" class="btn request-deployment request-buy" name="revision" value="'.$revision['extension_id'].'">Buy To Request Deployment</a>'.PHP_EOL;
                } else {
                    $request_button = '<button type="submit" class="btn request-deployment" name="revision" value="'.$revision['id'].'">Request Deployment</a>'.PHP_EOL;
                }
                $content .= (!$revision['filename'] ? $request_button : $download_button).PHP_EOL;
                $content .= '</td>'.PHP_EOL;
                $content .= '</tr>'.PHP_EOL;
            }
        }
        $this->_response->setBody($content);
    }
}
