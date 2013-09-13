<?php

class QueueController extends Integration_Controller_Action {

    public function init() {
        /* following two variables used during ftp credentials validation */
        $this->_ftpStream = '';
        $this->_sshStream = '';
        $this->_customHost = '';
        $this->_sshWebrootPath='';

        $sslSwitch = true;
        if('login-to-store-backend' == $this->getRequest()->getActionName()) {
            $sslSwitch = false;
        }
        $this->_helper->sslSwitch($sslSwitch);
        parent::init();
    }

    public function indexAction() {
        $this->view->headScript()->appendFile($this->view->baseUrl('/public/js/queue-index.js'), 'text/javascript');
        $storeModel = new Application_Model_Store();

        $page = (int) $this->_getParam('page', 0);
        $this->view->page = $page;
        $paginator = $storeModel->getWholeQueue();
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);

        $this->view->queue = $paginator;
    }

    public function addAction() {
        
        $this->checkStoreLimit();
        
        $this->view->userGroup = $this->auth->getIdentity()->group;
        
        $userModel = new Application_Model_User();
        $userModel->find($this->auth->getIdentity()->id);
        
        $planModel = new Application_Model_Plan();
        $planModel->find($userModel->getPlanId());
        
        $this->view->userGroup = $this->auth->getIdentity()->group;
        $this->view->userPlan = $planModel;
    }

    public function addCleanAction() {
        $this->checkStoreLimit();
        
        $version = new Application_Model_Version();
        $versions = $version->fetchAll();
        $this->view->versions = $versions;
        
        $form = new Application_Form_StoreAddClean();
        $form->populate($this->getRequest()->getParams());

        $request = Zend_Controller_Front::getInstance()->getRequest();
        
        $modelUser = new Application_Model_User();
        $user = $modelUser->find($this->auth->getIdentity()->id);
        $modelPlan = new Application_Model_Plan();
        $plan = $modelPlan->find($user->getPlanId());
        
        if ($request->isPost()) {

            $userGroup = $this->auth->getIdentity()->group;

            if ($this->auth->getIdentity()->group != 'admin') {

                $versionModel = new Application_Model_Version();
                $version = $versionModel->find((int) $request->getParam('version', 0));

                if ($version->getEdition() != 'CE') {
                    $this->_helper->FlashMessenger('Please select magento editon.');
                    return $this->_helper->redirector->gotoRoute(
                                    array(), 'default', false
                    );
                }
            }


            
            if ($form->isValid($this->getRequest()->getParams())) {
                //needs validation!
                $storeModel = new Application_Model_Store();
                $userId = $this->auth->getIdentity()->id;

                $storeModel->setVersionId($form->version->getValue())
                        ->setEdition($form->edition->getValue())
                        ->setUserId($userId)
                        ->setServerId($this->auth->getIdentity()->server_id)
                        ->setSampleData($form->sample_data->getValue())
                        ->setStoreName($form->store_name->getValue())
                        ->setDomain(Integration_Generator::generateRandomString(5, 4))
                        ->setBackendName('admin')
                        ->setStatus('installing-magento')
                        ->setType('clean');
                
                if($plan->getCanDoDbRevert()){
                	$storeModel->setDoHourlyDbRevert($form->do_hourly_db_revert->getValue());
                }
                                   
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
                            'commit_type' => 'magento-init',
                            'send_store_ready_email' => true
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
                    
                    $installId = $queueModel->getId();
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
        
        $this->view->userplan = $plan;
        
        $this->view->headScript()->appendFile($this->view->baseUrl('/public/js/queue-addclean.js'), 'text/javascript');
    }

    public function addCustomAction() {
        $this->checkStoreLimit();
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
        
        $version = new Application_Model_Version();
        $versions = $version->fetchAll();
        $this->view->versions = $versions;

        $this->view->input_radio = 'remote_path';
        
        $modelUser = new Application_Model_User();
        $user = $modelUser->find($this->auth->getIdentity()->id);
        
        $modelPlan = new Application_Model_Plan();
        $plan = $modelPlan->find($user->getPlanId());
        
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

                //start adding store
                $storeModel->setVersionId($form->version->getValue())
                        ->setEdition($form->edition->getValue())
                        ->setSampleData(1)
                        ->setStoreName($form->store_name->getValue())
                        ->setUserId($userId)
                        ->setServerId($this->auth->getIdentity()->server_id)
                        ->setDomain(Integration_Generator::generateRandomString(5, 4))
                        ->setStatus('downloading-magento')
                        ->setCustomProtocol($form->custom_protocol->getValue())
                        ->setCustomHost($form->custom_host->getValue())
                        ->setCustomPort($form->custom_port->getValue())
                        ->setCustomRemotePath($form->custom_remote_path->getValue())
                        ->setCustomLogin($form->custom_login->getValue())
                        ->setCustomPass($form->custom_pass->getValue())
                        ->setCustomSql($form->custom_sql->getValue())
                        ->setType('custom')
                        ->setBackendName('admin')
                        ->setCustomFile($form->custom_file->getValue());
                
                if($plan->getCanDoDbRevert()){
                	$storeModel->setDoHourlyDbRevert($form->do_hourly_db_revert->getValue());
                }
                
                $storeId = $storeModel->save();
                
                $queueModel = new Application_Model_Queue();
                //TODO: Add queue item with MagentoDownload
                $queueModel->setStoreId($storeId);
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
                $queueModel->setStoreId($storeId);
                $queueModel->setTask('RevisionInit');
                $queueModel->setStatus('pending');
                $queueModel->setUserId($this->auth->getIdentity()->id);
                $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                $queueModel->setExtensionId(0);  
                $queueModel->setParentId($installId);  
                $queueModel->save();
                unset($queueModel);
                
                $queueModel = new Application_Model_Queue();                    
                $queueModel->setStoreId($storeId);
                $queueModel->setTask('RevisionCommit');
                $queueModel->setTaskParams(
                        array(
                            'commit_comment' => 'Initial Magento Commit',
                            'commit_type' => 'magento-init',
                            'send_store_ready_email' => true
                            )
                );
                $queueModel->setStatus('pending');
                $queueModel->setUserId($this->auth->getIdentity()->id);
                $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                $queueModel->setExtensionId(0);  
                $queueModel->setParentId($installId);  
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

                    $installId = $queueModel->getId();
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

                //stop adding store
                $this->_helper->FlashMessenger(
                    array(
                        'type' => 'success',
                        'message' => 'You have successfully added your custom store to queue.'
                    )
                );
                return $this->_helper->redirector->gotoRoute(array(
                    'module' => 'default',
                    'controller' => 'user',
                    'action' => 'dashboard',
                ), 'default', true);
            } else {
                $this->_helper->FlashMessenger(array('type' => 'error', 'message' => 'Form data is invalid. Please check fields below and correct them.'));
            }
        }

        // Getting mesages from session namespace - because we don't redirect user after form-post request.
        $this->view->messages = $this->_helper->FlashMessenger->getCurrentMessages() + $this->_helper->FlashMessenger->getMessages();
        $this->_helper->FlashMessenger->clearMessages();
        $this->_helper->FlashMessenger->clearCurrentMessages();

        $this->view->form = $form;
        $this->view->userplan = $plan;
        
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
                $byAdmin = $this->auth->getIdentity()
                        ->group == 'admin' ? true : false;

                $storeModel->changeStatusToClose($byAdmin);

                $currentStore = $storeModel->findByDomain($domain);

                $storeModel->find($currentStore->id);
                
                /**
                 * Remove not yet finished 
                 * and not yet started tasks for that store 
                 */
                $queueModel=  new Application_Model_Queue();
                $queueModel->removePendingForStore($currentStore->id);
                
                $storeModel->setStatus('removing-magento')->save();
                
                //removing system from Papertrail
                $removingId = 0;

                if (strlen($storeModel->getPapertrailSyslogHostname())) {
                    $queueModel = new Application_Model_Queue();
                    $queueModel->setStoreId($currentStore->id);
                    $queueModel->setTask('PapertrailSystemRemove');
                    $queueModel->setStatus('pending');
                    $queueModel->setUserId($this->auth->getIdentity()->id);
                    $queueModel->setServerId($this->auth->getIdentity()->server_id); 
                    $queueModel->setExtensionId(0);  
                    $queueModel->setParentId(0);  
                    $queueModel->save();

                    $removingId = $queueModel->getId();
                }

                unset($queueModel);
                //add remove task to queue
                $queueModel = new Application_Model_Queue();
                $queueModel->setTask('MagentoRemove')
                        ->setStoreId($currentStore->id)
                        ->setUserId($byAdmin ? $currentStore->user_id : $this->auth->getIdentity()->id)
                        ->setParentId($removingId)
                        ->setExtensionId(0)
                        ->setServerId($this->auth->getIdentity()->server_id)
                        ->setStatus('pending')
                        ->save();

                $user = new Application_Model_User();
                $user->find($storeModel->getUserId());
                if(in_array((int)$user->getDowngraded(), array(3, 4))) {
                    $plan = new Application_Model_Plan();
                    $plan->find($user->getPlanId());
                    $user_max_stores = (int)$user->getAdditionalStores()-(int)$user->getAdditionalStoresRemoved()+(int)$plan->getStores();
                    $user_stores =  new Application_Model_Store();

                    $user->setAdditionalStores((int)$user->getAdditionalStores()-1);
                    if(0 > $user->getAdditionalStores()) {
                        $user->getAdditionalStores(0);
                    }
                    $user->setAdditionalStoresRemoved((int)$user->getAdditionalStoresRemoved()-1);
                    if(0 > $user->getAdditionalStoresRemoved()) {
                        $user->getAdditionalStoresRemoved(0);
                    }

                    if($user_stores->getAllForUser($user->getId())->count() <= $user_max_stores) {
                        $user->setDowngraded(5);
                    }
                    $user->save();
                    include APPLICATION_PATH . '/../scripts/restore_user_from_forced_removing_stores.php';
                }
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

        /* still support to display fields on two statuses */
        $form = new Application_Form_StoreEdit(in_array($store->getStatus(),array('downloading-magento','error')));
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
            $form->setAttrib('label', 'Edit Magento Store');
        }

        if (in_array($store->getStatus(),array('downloading-magento','error'))){
            $this->view->headScript()->appendFile($this->view->baseUrl('/public/js/queue-edit-connection.js'), 'text/javascript');
        }

        $user = new Application_Model_user();
        $user = $user->find($store->getUserId());
        $populate = array_merge(
            $store->__toArray(),
            array(
                'store_name' => $store->getStoreName(),
                'backend_password' => $store->getBackendPassword(),
                'backend_login' => $user->getLogin(),
                'custom_pass' => $store->getCustomPass(),
            )
        );

        $form->populate($populate);

        $queueModel = new Application_Model_Queue();
        $magentoQueueItem = $queueModel->findMagentoTaskForStore($store->getId());

        if ($magentoQueueItem){
            $this->view->has_download_task = true;
        } else {
            $this->view->has_download_task = false;
            $form->removeElement('custom_protocol');
            $form->removeElement('custom_host');
            $form->removeElement('custom_login');
            $form->removeElement('custom_pass');
            $form->removeElement('custom_sql');
        }

        if ($this->_request->isPost()) {

            if ($form->isValid($this->_request->getPost())) {
                $store->setOptions($form->getValues());

                /* updateQueueItem to try once again if it failed before edit */
                if ($store->getStatus()=='error'){
                    $magentoQueueItem = $queueModel->findMagentoTaskForStore($store->getId());
                    if($magentoQueueItem) {
                        $magentoQueueItem->setStatus('pending');
                        $config = Zend_Registry::get('config');
                        $queueTypeConfig = $config->queueRetry->{$magentoQueueItem->getTask()};
                        if(isset($queueTypeConfig->retries)) {
                            $magentoQueueItem->setRetryCount($queueTypeConfig->retries);
                        } else {
                            $magentoQueueItem->setRetryCount($config->queueRetry->global->retries);
                        }
                        /*also update store to reflect change on dashboard*/
                        $store->setStatus($storeModel->getStatusFromTask($magentoQueueItem->getTask()));
                        $magentoQueueItem->setNextExecutionTime(new Zend_Db_Expr('CURRENT_TIMESTAMP'));
                        $magentoQueueItem->save();
                    }

                }

                $store->save();

                $this->_helper->FlashMessenger('Store data has been changed successfully');
                $queue_pagination_index = (int)$this->_getParam('admin-edit', 0);
                if($queue_pagination_index) {
                    return $this->_helper->redirector->gotoRoute(array(
                        'module' => 'default',
                        'controller' => 'queue',
                        'action' => 'index',
                        'page' => $queue_pagination_index
                    ), 'default', true);
                } else {
                    return $this->_helper->redirector->gotoRoute(array(
                        'module' => 'default',
                        'controller' => 'user',
                        'action' => 'dashboard',
                    ), 'default', true);
                }
            }
        }

        if($store->getCustomRemotePath()!=''){
            $this->view->input_radio = 'remote_path';
        } else {
            $this->view->input_radio = 'file';
        }

        $this->view->render_extension_grid = false;
        if('admin' == $this->auth->getIdentity()->group) {
            $this->view->render_extension_grid = true;
            $extensions = new Application_Model_Extension();
            $this->view->extensions = $extensions->getInstalledForStore($store->getId(), 'premium');
        }

        $this->view->form = $form;
    }

    public function setPaymentForExtensionAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $store_extension_id = (int)$this->_getParam('store_extension_id', 0);
        $paid = (int)$this->_getParam('payment', 0);
        if($store_extension_id > 0 && $this->getRequest()->isPost()) {
            $storeExtension = new Application_Model_StoreExtension();
            try {
                $storeExtension->markAsPaid($paid, $store_extension_id);
                $this->getResponse()->setBody('done');
            } catch(Exception $e) {
                if ($log = $this->getLog()) {
                    $log->log('Set extension payment- ', LOG_ERR, $e->getMessage());
                }
                $this->getResponse()->setBody('error');
            }
        } else {
            return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'user',
                'action' => 'dashboard',
            ), 'default', true);
        }
    }

    public function extensionsAction() {

        $request = $this->getRequest();
        $filter = $request->getParam('filter', array());
        if(!is_array($filter)) {
            $filter = array();
        }
        $order = $request->getParam('order', array());
        if(!is_array($order)) {
            $order = array();
        }
        $offset = $request->getParam('offset', 0);
        $offset = !is_numeric($offset) ? 0 : (int)$offset;
        $limit = 50;

        $store_name = $request->getParam('store');
        $extensionModel = new Application_Model_Extension();
        $extensionCategoryModel = new Application_Model_ExtensionCategory();

        unset($filter['restricted']);
        $group = $this->auth->getIdentity();
        $group = is_object($group) ? $group->group : '';
        if('admin' != $group) {
            $filter['restricted'] = true;
        }
        $this->view->extensions = $extensionModel->fetchStoreExtensions($store_name, $filter, $order, $offset, $limit);
        $this->view->extensions_counter = $extensionModel->getMapper()->fetchStoreExtensions($store_name, $filter, $order, $offset, $limit, true);
        $this->view->categories = $extensionCategoryModel->fetchAll();

        //fetch store data
        $storeModel = new Application_Model_Store();
        $store = $storeModel->findByDomain($store_name);
        $this->view->store_name = $store->store_name;
        $this->view->store_domain = $store->domain;

        if($request->isPost() && $request->isXmlHttpRequest()) {
            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            $this->getResponse()->setBody(
                json_encode(
                    array(
                        'count' => $this->view->extensions_counter,
                        'tiles' => $this->view->render('extension/tiles.phtml')
                    )
                )
            );
        } else {
            $this->renderScript('extension/list.phtml');
        }
    }

    public function installExtensionAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $store_name = $request->getParam('store');
            $storeModel = new Application_Model_Store();
            $storeRow = $storeModel->findByDomain($store_name)->toArray();
            // do not use encrypt for these fields
            $backend_password = $storeRow['backend_password'];
            $custom_password = $storeRow['custom_pass'];
            unset($storeRow['backend_password']);
            unset($storeRow['custom_pass']);
            $storeRow = $storeModel->setOptions($storeRow);
            $storeRow->setBackendPassword($backend_password, false);
            $storeRow->setCustomPass($custom_password, false);

            $extensionModel = new Application_Model_Extension();
            $extensions = $extensionModel->getInstalledForStore($storeRow->getId());
            $not_installed = true;
            foreach($extensions as $extension) {
                if($extension['id'] == $request->getParam('extension_id') AND (int)$extension['store_id']) {
                    $not_installed = false;
                }
            }
            if($not_installed) {
                if ((int)$request->getParam('extension_id') > 0) {
                    if ($storeRow->getStatus() == 'ready') {
                        $storeRow->setStatus('installing-extension');
                        $storeRow->save();
                    }
        
                    /* Adding extension to queue */
                    try {
                        $extensionId = $request->getParam('extension_id');
        
                        //add row to store_extension
                        $storeExtensionModel = new Application_Model_StoreExtension();
                        $storeExtensionModel->setStoreId($storeRow->getId());
                        $storeExtensionModel->setExtensionId($extensionId);
                        $storeExtensionModel->setStatus('pending');
                        $storeExtensionModel = $storeExtensionModel->save();
        
                        /**
                         * Find if we have any other ExtensionInstall tasks
                         * for this store
                        */
                        $extensionQueueItem = new Application_Model_Queue();
                        $extensionParent = $extensionQueueItem->getParentIdForExtensionInstall($storeRow->getId());
        
                        $extensionQueueItem->setStoreId($storeRow->getId());
                        $extensionQueueItem->setStatus('pending');
                        $extensionQueueItem->setUserId($storeRow->getUserId());
                        $extensionQueueItem->setExtensionId($extensionId);
                        $extensionQueueItem->setParentId($extensionParent);
                        $extensionQueueItem->setServerId($storeRow->getServerId());
                        $extensionQueueItem->setTask('ExtensionInstall');
                        $extensionQueueItem->save();
        
                        /* Get extension data and add commit task */
                        $extensionModel = new Application_Model_Extension();
                        $extensionModel->find($request->getParam('extension_id'));
        
                        $queueId = $extensionQueueItem->getId();
                        $queueModel = new Application_Model_Queue();
                        $queueModel->setStoreId($storeRow->getId());
                        $queueModel->setStatus('pending');
                        $queueModel->setUserId($storeRow->getUserId());
                        $queueModel->setExtensionId($extensionId);
                        $queueModel->setParentId($queueId);
                        $queueModel->setServerId($storeRow->getServerId());
                        $queueModel->setTask('RevisionCommit');
                        $queueModel->setTaskParams(
                                array(
                                        'commit_comment' => 'Adding ' . $extensionModel->getName() . ' (' . $extensionModel->getVersion() . ')',
                                        'commit_type' => 'extension-install'
                                )
                        );
                        $queueModel->save();
        
                        $this->_response->setBody($storeExtensionModel->getId());
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
        } else {
            return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'user',
                'action' => 'dashboard',
            ), 'default', true);
        }
    }

    public function getstatusAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $domain = $request->getParam('domain', null);
        $extension_id = (int)$request->getParam('extension_id', null);
        if ($request->isPost() && ($domain!=null || $extension_id)) {
            if($extension_id) {
                $model = new Application_Model_StoreExtension();
                $model = (object)$model->find($extension_id)->__toArray();
            } else {
                $model = new Application_Model_Store();
                $model = $model->findByDomain($domain);
            }
            if(is_object($model)) {
                $this->_response->setBody(Zend_Json_Encoder::encode($model->status));
            } else {
                $this->_response->setBody('"error"');
            }
        } else {
            return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'user',
                'action' => 'dashboard',
            ), 'default', true);
        }
    }
    
    public function gettimeleftAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $userid = $request->getParam('user', null);
        $queueid = $request->getParam('queue', null);
        
        if ($request->isPost() && $userid != null && $queueid != null) {
            $this->_response->setBody(
                Application_Model_Queue::getTimeLeftByUserAndId($userid,$queueid)
            );
        } else {
            return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'user',
                'action' => 'dashboard',
            ), 'default', true);
        }
    }
    
    public function commitAction() {
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
        if ($revisionModel->getExtensionId()){
            $extensionId = $revisionModel->getExtensionId();
        } else { 
            $extensionId = 0;
        }
        
        $queueModel = new Application_Model_Queue();
        
        /**
         * When user will click 2 times for rollback, using middle button, 
         * script will add 2 rollback task and one will hang infinitely.
         * Following lines are here to prevent it 
         */
        
        if ($queueModel->alreadyExists('RevisionRollback', $store->id, $extensionId, $store->server_id)){
            return $this->_helper->redirector->gotoRoute(array(
                'module' => 'default',
                'controller' => 'user',
                'action' => 'dashboard',
            ), 'default', true);
        }
        
        
        
        /* add task with RevisionRollback */
        
        $queueModel->setStoreId($store->id);
        $queueModel->setStatus('pending');
        $queueModel->setUserId($store->user_id);
        $queueModel->setExtensionId($extensionId);
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
                $extension = $extension->fetchStoreExtension($revisionModel->getStoreId(), $revisionModel->getExtensionId());

                $extension_data = new Application_Model_Extension();
                $extension_data = $extension_data->find($extension->getExtensionId());
                if((float)$extension_data->getPrice() > 0) {
                    // check whether user paid for extension
                    if(!$extension->getBraintreeTransactionId()) {
                        $this->_helper->FlashMessenger(array('type' => 'notice', 'message' => 'Deploying not paid extension is forbidden.'));
                        
                        return $this->_helper->redirector->gotoRoute(array(
                                'module' => 'default',
                                'controller' => 'user',
                                'action' => 'dashboard',
                        ), 'default', true);
                    }
                }
            } 

            $parent_id = 0;
            $is_paid_encoded_extension = false;
            if(isset($extension) && (int)$extension->getBraintreeTransactionId()) {
                if(!preg_match('/\(Open Source\)\s*$/', $revisionModel->getComment())) {
                    $found_open_source = false;
                    foreach($revisionModel->getAllForStore($store->id) as $store_revision) {
                        if(
                            $store_revision->extension_id = $extension->getId()
                            && preg_match('/\(Open Source\)\s*$/', $store_revision->comment)
                        ) {
                            $found_open_source = true;
                            break;
                        }
                    }
                    if(!$found_open_source) {
                        $is_paid_encoded_extension = true;
                    }
                }
            }
            // request deployement for paid extension but without opened source
            // in that case add task to open it first
            if($is_paid_encoded_extension) {
                $extensionModel = new Application_Model_Extension();
                $extensionModel->find($revisionModel->getExtensionId());

                $queueModel = new Application_Model_Queue();
                $queueModel->setStoreId($store->id);
                $queueModel->setTask('ExtensionOpensource');
                $queueModel->setStatus('pending');
                $queueModel->setUserId($store->user_id);
                $queueModel->setServerId($store->server_id);
                $queueModel->setExtensionId($extension->getExtensionId());
                $queueModel->setParentId(0);
                $queueModel->save();
                $parent_id = $queueModel->getId();
                unset($queueModel);

                $queueModel = new Application_Model_Queue();
                $queueModel->setStoreId($store->id);
                $queueModel->setTask('RevisionCommit');
                $queueModel->setTaskParams(
                    array(
                        'commit_comment' => $extension_data->getName() . ' (Open Source)',
                        'commit_type' => 'extension-decode'
                    )
                );
                $queueModel->setStatus('pending');
                $queueModel->setUserId($store->user_id);
                $queueModel->setServerId($store->server_id);
                $queueModel->setExtensionId($extension->getExtensionId());
                $queueModel->setParentId($parent_id);
                $queueModel->save();
                $parent_id = $queueModel->getId();

            }

            $queueModel = new Application_Model_Queue();
            $queueModel->setTask('RevisionDeploy');
            $queueModel->setTaskParams(
                array(
                    'revision_id' => $is_paid_encoded_extension ? 'paid_open_source' : $this->_getParam('revision')
                )
            );

            $queueModel->setStoreId($store->id);
            $queueModel->setServerId($store->server_id);
            $queueModel->setParentId($parent_id);
            $queueModel->setExtensionId($revisionModel->getExtensionId());
            $queueModel->setAddedDate(date("Y-m-d H:i:s"));
            $queueModel->setStatus('pending');
            $queueModel->setUserId($this->auth->getIdentity()->id);
            $queueModel->save();

            $storeModel = new Application_Model_Store();
            $storeModel->find($store->id);
            $storeModel->setStatus('deploying-revision')->save();

            $this->_helper->FlashMessenger(array('type' => 'success', 'message' => 'Deployment package will be created soon and will be available to download on Deployment list.'));
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
            $serverModel = new Application_Model_Server();
            $server = $serverModel->find($store->server_id);
        }
        $content = '';
        if(
            is_object($store) AND
            (int)$store->id AND
            $store->user_id == $this->auth->getIdentity()->id
        ) {
            $model = new Application_Model_Revision();
            $deployment_list = $model->getAllForStore($store->id);
            foreach($deployment_list as $revision) {
                var_dump($revision->toArray());
                if($revision['type']=='magento-init'){
                    continue;
                }

                $row = '';
                $row .= '<tr>'.PHP_EOL;
                $row .= '<td>'.$revision['comment'].'</td>'.PHP_EOL;
                $row .= '<td>'.PHP_EOL;
                    //<button class="btn" type="submit" name="deploy" value="'.$revision['id'].'">Deploy</button>
                $download_button = '<a class="btn btn-primary download-deployment" href="'.
                    'http://'.$this->auth->getIdentity()->login.'.'.$server->getDomain().'/'.$domain.'/var/deployment/'.$revision['filename']
                .'">Download</a>'.PHP_EOL;
                if((int)$revision['extension_id']) {
                    // check whether extension has open source revision
                    // and do not display old revision if open source exists
                    if(!preg_match('/\(Open Source\)\s*$/', $revision['comment'])) {
                        $open_source = false;
                        foreach(clone $deployment_list as $deployment) {
                            if($deployment['extension_id'] == $revision['extension_id']
                               && $deployment['id'] != $revision['id']
                               && preg_match('/\(Open Source\)\s*$/', $deployment['comment'])
                            ) {
                                $open_source = true;
                            }
                        }
                        if($open_source) {
                            continue;
                        }
                    }
                    if(!$revision['braintree_transaction_id'] && $revision['price']>0) {
                        $request_button = '<button type="submit" data-store-domain="'.$domain.'" class="btn request-deployment request-buy" name="revision" value="'.$revision['extension_id'].'">Buy To Request Deployment</a>'.PHP_EOL;
                    } else {
                        $request_button = '<button type="submit" class="btn request-deployment" name="revision" value="'.$revision['id'].'">Request Deployment</a>'.PHP_EOL;
                    }
                }

                $row .= (($revision['filename'] AND
                    (
                        $revision['braintree_transaction_id'] OR $revision['price'] == 0
                    )
                ) ? $download_button : $request_button) . PHP_EOL;

                $row .= '</td>'.PHP_EOL;
                $row .= '</tr>'.PHP_EOL;

                $content .= $row;
            }
        }
        $this->_response->setBody($content);
    }
    
    /**
     * Prevent from going forward if max stores have been reached - start 
     * @return boolean
     */
    protected function checkStoreLimit(){
        
        $userId = $this->auth->getIdentity()->id;
        $userGroup = $this->auth->getIdentity()->group;

        $storeModel = new Application_Model_Store();
        $userStores = $storeModel->countUserStores($userId);

        if ('free-user' === $userGroup) {
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

            $maxStores = (int)$plan->getStores() + (int)$user->getAdditionalStores();
        }

        if ($userStores >= $maxStores && 'admin' !== $userGroup) {

            $this->_helper->FlashMessenger(array('type' => 'notice', 'message' => 'You cannot have more stores.'));
            return $this->_helper->redirector->gotoRoute(array(
                    'module' => 'default',
                    'controller' => 'user',
                    'action' => 'dashboard',
            ), 'default', true);
        }
        /* Prevent from going forward if max stores have been reached - stop */
    }

    public function loginToStoreBackendAction() {
        $request = $this->getRequest();
        $domain = $request->getParam('store');
        $store = new Application_Model_Store();
        $server = new Application_Model_Server();
        if($domain) {
            $store = $store->findByDomain($domain);
            if($store->server_id) {
                $server = $server->find($store->server_id);
            }
        }
        if($store->id && ($this->auth->getIdentity()->id == $store->user_id || 'admin' == $this->auth->getIdentity()->group) && $server->getDomain()) {
            $this->view->item = $store->toArray();
            $user = new Application_Model_User();
            $user = $user->find($store->user_id);
            $this->view->user = $user->getLogin();
            $this->view->store_url = 'http://' . $this->view->user . '.' . $server->getDomain() . '/' . $domain . '/' . $store->backend_name;
            $this->view->form = $this->_getStoreBackendLoginForm($this->view->store_url);
        } else {
            // not post or store does not exists or user does not have that store
            return $this->_helper->redirector->gotoRoute(array(
                    'module' => 'default',
                    'controller' => 'user',
                    'action' => 'dashboard',
            ), 'default', true);
        }
    }

    protected function _getStoreBackendLoginForm($store_url) {
        $store_url_parsed = parse_url($store_url); 
        if(is_array($store_url_parsed) && stristr($store_url_parsed['host'], 'magetesting.com')) {
            $client = new Zend_Http_Client();
            $client->setAdapter(new Zend_Http_Client_Adapter_Curl());
            $client->setUri($store_url);
    
            try {
                $response = $client->request();
                if($body = $response->getBody()) {
                    preg_match('/(\<form.*\<\/form\>)/ims', $body, $match);
                    if(2 == count($match)) {
                        return $match[1];
                    }
                }
            } catch(Exception $e) {
                if ($log = $this->getLog()) {
                    $log->log('Error while fetching backend form - ' . $e->getMessage(), LOG_ERR);
                }
            }
        }
        return '';
    }

    /* FTP VALIDATION SECTION */
    // validate whether ftp credentials are valid and we can connect to server
    public function validateFtpCredentialsAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $response = array(
            'status' => 'error',
            'message' => 'Ftp Credentials are not valid.'
        );
        $request = $this->getRequest();
        switch($request->getParam('custom_protocol')){
            case 'ftp':
                if($this->_validateFtpCredentials()) {
                    if(($response['value'] = $this->_findWebrootOnFtp())) {
                        $response['status'] = 'success';
                        $response['message'] = 'Webroot has been found successfully.';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'Credentials are correct. We haven\'t been able to find magento root path, please fill fields below then';
                    }
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Credentials are incorrect.';
                }
            break;
            case 'ssh':
                if ($this->_validateSshCredentials()) {
                    if (($response['value'] = $this->_findWebrootOnSsh())) {
                        $response['status'] = 'success';
                        $response['message'] = 'Webroot has been found successfully.';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'Credentials are correct. We haven\'t been able to find magento root path, please fill fields below then';
                    }
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Credentials are incorrect.';
                }
            break;
            default:
            /*do nothing, error is set*/
        
        }
        $response['message'] = $this->_prepareFlashMessage($response);
        $this->getResponse()->setBody(json_encode($response));
    }
    
    protected function _validateFtpCredentials(){
               
        $request = $this->getRequest();
        
        if (trim($request->getParam('custom_host',''))=='' 
            || trim($request->getParam('custom_login',''))==''
            || trim($request->getParam('custom_pass',''))=='' )
        {
            return false;
        } 
        
        $this->_customHost = $request->getParam('custom_host');
        if ($protocol = strstr($this->_customHost,'://',true)){
            $this->_customHost = str_replace($protocol.'://','',$this->_customHost);
        }
        $this->_customHost = trim($this->_customHost,'/');
        
        $this->_ftpStream = @ftp_connect($this->_customHost,(int)$request->getParam('custom_port'),3600); 
        if ($this->_ftpStream){
            if (@ftp_login($this->_ftpStream,$request->getParam('custom_login'),$request->getParam('custom_pass'))){
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    protected function _validateSshCredentials(){
        
        $request = $this->getRequest();
        if (trim($request->getParam('custom_host',''))=='' 
            || trim($request->getParam('custom_login',''))==''
            || trim($request->getParam('custom_pass',''))=='' )
        {
            return false;
        } 
               
        $this->_customHost = $request->getParam('custom_host');
        if ($protocol = strstr($this->_customHost,'://',true)){
            $this->_customHost = str_replace($protocol.'://','',$this->_customHost);
        }
        $this->_customHost = trim($this->_customHost,'/');
        
        $customPort = (int)$request->getParam('custom_port',22);
        if (trim($customPort) == ''){
            $customPort = 22;
        }
        
        /**
         * [jan] I had to add erro supression, 
         * otherwise ajax actions returns errors from these functions 
         * and cannot display error message
         */
        $this->_sshStream = @ssh2_connect($this->_customHost,$customPort); 
        if ($this->_sshStream){
            if (@ssh2_auth_password($this->_sshStream,$request->getParam('custom_login'),$request->getParam('custom_pass'))){
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /*
     * finds magento webroot in folders like www, public_html, htdocs, web
     * and look for familiar magento files/folder like 'app' or smth
     */
    protected function _findWebrootOnFtp() {
    
        $baseFolders = array('www','public_html','web','htdocs',$this->_customHost);
        // get contents of the current directory
        $contents = ftp_nlist($this->_ftpStream, ".");

	if(!$contents){
	    /*try passive transfer*/
	    ftp_pasv($this->_ftpStream, true);
	    $contents = ftp_nlist($this->_ftpStream,".");
	}

	if (!$contents){
	    return false;
	}

        $res = array_values(array_intersect($baseFolders,$contents));
        if ($res){
            ftp_chdir($this->_ftpStream,$res[0]);
            $contents = ftp_nlist($this->_ftpStream, ".");
            $res = array_values(array_intersect($baseFolders,$contents));

            if ($res){
                /*in case we have second folder*/
                ftp_chdir($this->_ftpStream,$res[0]);
                $contents = ftp_nlist($this->_ftpStream, ".");
                //$res = array_values(array_intersect($baseFolders,$contents));
               
                //find magento files
                return $this->_checkForMagentoFoldersOnFtp();
                
            } else {
            /*we are in web dir*/
            
                //find magento files
                return $this->_checkForMagentoFoldersOnFtp();
            }
            
            //find magento files
            return $this->_checkForMagentoFoldersOnFtp();
        }
        
        return false;
    }
    
    protected function _findWebrootOnSsh() {
         $request = $this->getRequest();
         $customPort = (int)$request->getParam('custom_port',22);
        if (trim($customPort) == ''){
            $customPort = 22;
        }

        $ssh = new RocketWeb_Cli();
        $ssh = $ssh->kit('ssh');
        /* @var $ssh RocketWeb_Cli_Kit_Ssh */
        $ssh->connect(
            $request->getParam('custom_login'),
            $request->getParam('custom_pass'),
            trim($this->_customHost,'/'),
            $customPort
        );

        /* @var $find RocketWeb_Cli_Kit_File */
        $find = $ssh->kit('file');
        $find->find('app', $find::TYPE_DIR, '.')->printPaths()->sortNatural();
        $remoteFind = $ssh->cloneObject()->remoteCall($find);

        $appPaths = $skinPaths = $libPaths = $jsPaths = array();

        // app folder
        $appPaths = $remoteFind->call()->getLastOutput();

        // skin folder 
        $skinPaths = $remoteFind->bindAssoc("'app'", 'skin')->call()->getLastOutput();
        
        // lib folder
        $libPaths = $remoteFind->bindAssoc("'skin'", 'lib')->call()->getLastOutput();

        // js folder
        $jsPaths = $remoteFind->bindAssoc("'lib'", 'js')->call()->getLastOutput();

        /* get rid of paths that does not have all mentioned folders */
        $possibleOptions = array_values(array_intersect($appPaths,$skinPaths,$libPaths,$jsPaths));

        if (count($possibleOptions)==0){
          return false;
        }

        /* additional check - check remaining paths for these that contain app/Mage.php file */
        foreach ($possibleOptions as $path){
          $findmageapp =
              $find
                  ->clear()
                  ->find('Mage.php', $find::TYPE_FILE, str_replace('./','',$path))
                  ->printPaths(true)
                  ->sortNatural();

          $validPaths =
              $ssh
                  ->cloneObject()
                  ->remoteCall($findmageapp)
                  ->call()
                  ->getLastOutput();

          if (isset($validPaths[0])){
            $path = str_replace('/app','',$validPaths[0]);
            $this->_sshWebrootPath = $path;
            return $path;
          } else {
            return false;
          }
        }
    }
    
    protected function _checkForMagentoFoldersOnFtp(){
        $contents = ftp_nlist($this->_ftpStream, ".");
        
        if (in_array('app',$contents) 
            && in_array('lib',$contents)
            && in_array('js',$contents)
            && in_array('skin',$contents)
        ) {
            return ftp_pwd($this->_ftpStream);
        }
        return false;
    }

    // finds sql dump only when webroot is given and ftp credentials are valid
        public function findSqlFileAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        
        $response = array(
                'status' => 'error',
                'message' => 'Ftp Credentials are not valid.'
        );
        
        switch ($request->getParam('custom_protocol')){
            case 'ftp':
                if ($this->_validateFtpCredentials()) {
                    $response['message'] = 'Webroot path has to be specified.';
                    //if($this->getParam('webroot')) {
                    $response['message'] = 'Sql dump couldn\'t be found';
                    if (($response['value'] = $this->_findSqlDumpOnFtp())) {
                        $response['status'] = 'success';
                        $response['message'] = 'Sql dump file has been found successfully.';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'Credentials are correct, but Sql dump file has not been found.';
                    }
                    //}
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Credentials are incorrect.';
                }
            break;
            case 'ssh':
                if ($this->_validateSshCredentials()) {
                    $response['message'] = 'Webroot path has to be specified.';
                    //if($this->getParam('webroot')) {
                    $response['message'] = 'Sql dump couldn\'t be found';
                    if (($response['value'] = $this->_findSqlDumpOnSsh())) {
                        $response['status'] = 'success';
                        $response['message'] = 'Sql dump file has been found successfully.';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'Credentials are correct, but Sql dump file has not been found.';
                    }
                    //}
                } else {
                    $response['status'] = 'error';
                    $response['message'] = 'Credentials are incorrect.';
                }
            break;
            default:
                /*invalid response is already set before switch*/
        }
        
        $response['message'] = $this->_prepareFlashMessage($response);
        $this->getResponse()->setBody(json_encode($response));
    }

    protected function _findSqlDumpOnFtp() {
        $basePath = $this->_findWebrootOnFtp();

        $raw = ftp_rawlist($this->_ftpStream,rtrim($basePath,'/').'/var/backups/');
        if (!$raw){
            /* try passive mode */
            ftp_pasv($this->_ftpStream,true);
            $raw = ftp_rawlist($this->_ftpStream,rtrim($basePath,'/').'/var/backups/');
        }

        $filetimes = array();
        if ($raw){
            foreach ($raw as $file){
                
                /* number of spaces is inconsistent among server, hence preg_replace */
                $file = preg_replace('!\s\s+!', ' ', $file);

                $parts = explode(" ",$file);
                if (isset($parts[8]) && $parts[8]!='.' && $parts[8]!='..'){
                    $filetimes[$parts[8]]= strtotime($parts[5].' '.$parts[6].' '.$parts[7]);
                }
            }
        }
        
        $maxtime = 0;
        $newestFile = '';
        if ($filetimes){
            foreach ($filetimes as $file => $filetime){
                if ($filetime > $maxtime){
                    $newestFile = $file;
                    $maxtime = $filetime;
                }
            }
        } else {
            return false;
        }
               
        return rtrim($basePath,'/').'/var/backups/'.$newestFile;
        
    }
    
    protected function _findSqlDumpOnSsh(){
        
        if ($this->_sshWebrootPath==''){
            $this->_sshWebrootPath = $this->_findWebrootOnSsh();
        }

        $request = $this->getRequest();
        $customPort = (int)$request->getParam('custom_port',22);
        if (trim($customPort) == ''){
            $customPort = 22;
        }
        
        $backupPath = $this->_sshWebrootPath.'/var/backups/';

        $ssh = new RocketWeb_Cli();
        $ssh = $ssh->kit('ssh');
        /* @var $ssh RocketWeb_Cli_Kit_Ssh */
        $ssh->connect(
                $request->getParam('custom_login'),
                $request->getParam('custom_pass'),
                trim($this->_customHost,'/'),
                $customPort
        );
        
        /* @var $file RocketWeb_Cli_Kit_File */
        $file = $ssh->kit('file');
        $list = $ssh->cloneObject()->remoteCall($file->listAll($backupPath));
        $raw = $list->call()->getLastOutput();

        $filetimes = array();
        if ($raw){
            foreach ($raw as $file){
                /* number of spaces is inconsistent among server, hence preg_replace */
                $file = preg_replace('!\s\s+!', ' ', $file);
                $parts = explode(" ",$file);
                
                if (isset($parts[8]) && $parts[8]!='.' && $parts[8]!='..'){
                    $filetimes[$parts[8]]= strtotime($parts[5].' '.$parts[6].' '.$parts[7]);
                }
            }
        }

        $maxtime = 0;
        $newestFile = '';
        if ($filetimes){
            foreach ($filetimes as $file => $filetime){
                if ($filetime > $maxtime && $file!='.htaccess'){
                    $newestFile = $file;
                    $maxtime = $filetime;
                }
            }
        } else {
            return false;
        }
               
        return rtrim($this->_sshWebrootPath,'/').'/var/backups/'.$newestFile;

    }

    protected function _prepareFlashMessage($data) {
        return '<a class="close" data-dismiss="alert" href="#">×</a><div class="popover-font-fix">'.$data['message'].'</div>';
    }
}
