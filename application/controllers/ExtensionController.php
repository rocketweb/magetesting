<?php

class ExtensionController extends Integration_Controller_Action {

    protected $_tempDir;

    public function init() {
        $this->_tempDir = rtrim(APPLICATION_PATH, '/').'/../public/assets/img/temp/';
        /* Initialize action controller here */
//        $this->_helper->noSslSwitch();
    }

    public function indexAction() {
        # display grid of all supported extensions
        /* index action is only alias for list extensions action */
        $this->listAction();
    }

    public function listAction() {
        $extensionModel = new Application_Model_Extension();
        // that should add extension screenshots to it
        $this->view->extensions = $extensionModel->fetchAll();
        
        $this->_helper->viewRenderer('grid');
    }

    public function addAction()
    {
        /* add and edit actions should have the same logic */
        $this->editAction('Add');
    }
    
    /**
     * Render extension form
     * 
     * @param string $action (Add or Edit)
     * @return mixed
     */
    public function editAction($action = 'Edit')
    {
        $id = (int) $this->_getParam('id', 0);

        if(($cancel = (int)$this->_getParam('cancel', 0)) AND $cancel) {
            return $this->_helper->redirector->gotoRoute(array(
                'module'     => 'default',
                'controller' => 'extension',
                'action'     => 'index',
            ), 'default', true);
        }

        $extension_data = array(
            'title'          => $this->_getParam('title', ''),
            'version'        => $this->_getParam('version', ''),
            'edition'        => $this->_getParam('edition', ''),
            'from_version'   => $this->_getParam('from_version', ''),
            'to_version'     => $this->_getParam('to_version', ''),
            'description'    => $this->_getParam('description', ''),
            'price'          => $this->_getParam('price', ''),
            'logo'           => $this->_getParam('logo', ''),
            'screenshots'    => $this->_getParam('screenshots', array()),
            'directory_hash' => $this->_getParam('directory_hash', time().'-'.uniqid()),
            'category_id'    => $this->_getParam('category_id', ''),
            'author'         => $this->_getParam('author', '')
        );
        $name = 'Application_Form_Extension'.$action;
        $form = new $name;
        $success_message = 'Extension has been added properly.';

        $extension = new Application_Model_Extension();
        $extension_entity_data = array();
        $screenshots = array();
        $screenshots_ids = array();

        $cat_model = new Application_Model_ExtensionCategory();
        $extension_categories = array();
        foreach($cat_model->fetchAll() as $category) {
            $extension_categories[$category->getId()] = $category->getName();
        }
        $form->category_id->addValidator(
            new Zend_Validate_InArray(array_keys($extension_categories))
        );

        $form->category_id->addMultiOptions($extension_categories);

        /* $id > 0 AND extension found in database */
        $noExtension = false;
        if($id) {
            $extension = $extension->find($id);
            if($extension->getId()) {
                foreach($extension->fetchScreenshots() as $row) {
                    $screenshots_ids[] = $row->getId();
                    $screenshots[] = $row->getImage();
                }
                $extension_entity_data = array(
                    'title'        => $extension->getName(),
                    'description'  => $extension->getDescription(),
                    'version'      => $extension->getVersion(),
                    'edition'      => $extension->getEdition(),
                    'from_version' => $extension->getFromVersion(),
                    'to_version'   => $extension->getToVersion(),
                    'price'        => $extension->getPrice(),
                    'logo'         => $extension->getLogo(),
                    'screenshots'  => $screenshots,
                    'author'       => $extension->getAuthor(),
                    'category_id'  => $extension->getCategoryId()
                );
                $success_message = 'Extension has benn changed properly.';
            } else {
                $noExtension = true;
            }
        } elseif(!$action == 'Add') {
            $noExtension = true;
        }

        $this->view->tempDir = $this->_tempDir;
        $this->view->directoryHash = $extension_data['directory_hash'];

        if($noExtension) {
            $this->_helper->FlashMessenger(array('type' => 'error', 'message' => 'Extension with given id, does not exist.'));
            return $this->_helper->redirector->gotoRoute(array(
                    'module'     => 'default',
                    'controller' => 'extension',
                    'action'     => 'index',
            ), 'default', true);
        }

        if ($this->_request->isPost()) {

            $extension_data['screenshots'] = $this->_getParam('screenshots', array());
            $extension_data['screenshots_ids'] = $this->_getParam('screenshots_ids', array());

            $this->view->logo = $this->_getParam('logo', '');

            $formData = $this->_request->getPost();
            if($form->isValid($formData)) {
                $old_logo = $extension->getLogo();
                $new_logo = $this->_getParam('logo', '');

                $formData['name'] = $formData['title'];
                if($extension->getId()) {
                    unset($formData['logo']);
                }
                
                $extension->setIsDev(0);

                $extension_new_name = (isset($_FILES["extension_file"]) && $_FILES["extension_file"]["name"] ? $_FILES["extension_file"]["name"] : '');
                $extension_encoded_new_name = (isset($_FILES["extension_encoded_file"]) && $_FILES["extension_encoded_file"]["name"] ? $_FILES["extension_encoded_file"]["name"] : '');

                $adapter = new Zend_File_Transfer_Adapter_Http();
                if($extension_new_name) {
                    $dir = APPLICATION_PATH.'/../data/extensions/'.$formData['edition'].'/open/';
                    if(!file_exists($dir)) {
                        @mkdir($dir, 0777, true);
                    }
                    $adapter->setDestination($dir);
                    $adapter->receive('extension_file');
                }
                if($extension_encoded_new_name) {
                    $dir = APPLICATION_PATH.'/../data/extensions/'.$formData['edition'].'/encoded/';
                    if(!file_exists($dir)) {
                        @mkdir($dir, 0777, true);
                    }
                    $adapter->setDestination($dir);
                    $adapter->receive('extension_encoded_file');
                }

                if($extension_new_name) {
                    if($extension->getExtension() AND $extension->getExtension() != $extension_new_name) {
                        $file_to_delete = APPLICATION_PATH.'/../data/extensions/'.$extension->getEdition().'/open/'.$extension->getExtension();
                        if(file_exists($file_to_delete)) {
                            @unlink($file_to_delete);
                        }
                    }
                    $extension->setExtension($extension_new_name);
                }
                if($extension_encoded_new_name) {
                    if($extension->getExtensionEncoded() AND $extension->getExtensionEncoded() != $extension_encoded_new_name) {
                        $file_to_delete = APPLICATION_PATH.'/../data/extensions/'.$extension->getEdition().'/encoded/'.$extension->getExtensionEncoded();
                        if(file_exists($file_to_delete)) {
                            @unlink($file_to_delete);
                        }
                    }
                    $extension->setExtensionEncoded($extension_encoded_new_name);
                }

                $extension->setOptions($formData);
                $extension->save();
                $extension_id = $extension->getId();
                if($old_logo != $new_logo) {
                    if($old_logo) {
                        @unlink($this->view->ImagePath($old_logo, 'extension/logo'));
                    }
                    if($new_logo) {
                        $new_file_name = $this->view->NiceString(substr_replace($new_logo, '-'.$extension_id, strrpos($new_logo, '.'), 0));
                        $new_path = $this->view->ImagePath($new_file_name, 'extension/logo', true, false);
                        if(!file_exists($new_path)) {
                            @mkdir($new_path, 0777, true);
                        }
                        @copy($this->_tempDir.$this->view->directoryHash.'/'.$new_logo, $new_path.$new_file_name);
                        $extension->setLogo($new_file_name);
                    }
                }
                $this->_saveImages($extension_id);
                $extension->save();

                $this->_helper->FlashMessenger($success_message);
                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'extension',
                        'action'     => 'index',
                ), 'default', true);
            }
        } else {
            $extension_data = array_merge($extension_data, $extension_entity_data);
            $this->view->old_logo = $extension->getLogo();
            $this->view->logo = $this->view->old_logo;
        }

        $form->populate($extension_data);

        $this->view->screenshots = $extension_data['screenshots'];
        $this->view->screenshots_ids = $screenshots_ids;
        $this->view->form = $form;
        $this->view->headScript()->appendFile('/public/js/extension-edit.js', 'text/javascript');
    
    }
    
    public function deleteAction()
    {
        // array with redirect to grid page
        $redirect = array(
                'module'      => 'default',
                'controller'  => 'extension',
                'action'      => 'index'
        );

        // init form object
        $form = new Application_Form_ExtensionDelete();

        // shorten request
        $request = $this->getRequest();

        // if request is without proper id param
        // redirect to grid with information message 
        if(((int)$request->getParam('id', 0)) == 0) {
            // set message
            $this->_helper->FlashMessenger(
                array(
                    'type' => 'error',
                    'message' => 'You cannot delete extension with specified id.'
                )
            );
            // redirect to grid
            return $this->_helper->redirector->gotoRoute(
                    $redirect, 'default', true
            );
        }

        if($request->isPost()) {
            // has post data and sent data is valid
            if($form->isValid($request->getParams())) {
                // someone agreed deletion 
                if($request->getParam('submit') == 'Yes') {
                    $extension = new Application_Model_Extension();
                    // set news id to the one passed by get param
                    
                    $extension->delete($request->getParam('id'));
                    // set message
                    $this->_helper->FlashMessenger(
                        array(
                            'type' => 'success',
                            'message' => 'You have deleted extension successfully.'
                        )
                    );
                } else {
                    // deletion cancelled
                    // set message
                    $this->_helper->FlashMessenger(
                        array(
                            'type' => 'notice',
                            'message' => 'Extension deletion cancelled.'
                        )
                    );
                }
                // redirect to grid if request is withou ajax
                return $this->_helper->redirector->gotoRoute(
                    $redirect, 'default', true
                );
            }
        }

        $this->view->form = $form;
    }

    public function uploadAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $directoryHash = $this->_getParam('directory_hash', '');
        if($directoryHash AND preg_match('/^\d{10}\-[a-z0-9]{13}$/i', $directoryHash)) {
            $as_logo = (int)$this->_getParam('checked', 0);
            $chmod = 0777;
            $uploader_options = array(
                    'upload_dir' => $this->_tempDir.$directoryHash.'/',
                    'upload_url'  => $this->view->baseUrl('public/assets/img/temp/'.$directoryHash.'/'),
                    'image_versions' => array(), // do not create thumbnails
                    'accept_file_types' => '/\.(gif|jpe?g|png)$/i',
                    'mkdir_mode' => $chmod
            );
            
            umask(0);
            
            if(!file_exists($this->_tempDir)) {
                @mkdir($this->_tempDir, $chmod, true);
            }
            
            /* delete old temp files */
            if(!file_exists($this->_tempDir)) {
                $dir = array();
            } else {
                $dir = new DirectoryIterator($this->_tempDir);
            }
            foreach($dir as $fileinfo) {
                if(!$fileinfo->isDot()) {
                    if($fileinfo->isDir()) {
                        if(preg_match('/^(\d{10})\-[a-z0-9]{13}$/i', $directoryHash, $match) AND time()-(int)$match[1] > 3600) {
                            $del_dir = new DirectoryIterator($this->_tempDir);
                            foreach($del_dir as $del_fileinfo) {
                                @unlink($this->_tempDir.$key.'/'.$del_fileinfo->getFilename());
                            }
                            @rmdir($this->_tempDir.$fileinfo->getFilename());
                        }
                    }
                }
            }
            $uploader = new Integration_Uploader($uploader_options, false);
            $result = $uploader->post(false);
            if(isset($result[0]) AND is_object($result[0])) {
                $result[0]->as_logo = $as_logo;
            }
            echo json_encode($result);
        } else {
            // empty response
            echo '{}';
        }
    }

    protected function _saveImages($extension_id) {
        umask(0);

        $this->_deleteRemovedImages($extension_id);

        $directory = $this->_getParam('directory_hash');
        $ids = $this->_getParam('screenshots_ids');
        $screenshots = $this->_getParam('screenshots');
        if(!$ids OR !is_array($ids)) {
            $ids = array();
        }
        if(!$screenshots OR !is_array($screenshots)) {
            $screenshots = array();
        }
        foreach($screenshots as $key => $image) {
            if(!isset($ids[$key]) OR !(int)$ids[$key]) {
                $old_path = $this->_tempDir.$directory.'/'.$image;
                $new_file_name = $this->view->NiceString(substr_replace($image, '-'.$extension_id, strrpos($image, '.'), 0));
                $new_path = $this->view->ImagePath($new_file_name, 'extension/screenshots', true, false);
                if(!file_exists($new_path)) {
                    @mkdir($new_path, 0777, true);
                }
                @copy($old_path, $new_path.$new_file_name);
                if(file_exists($old_path)) {
                    $screenshotModel = new Application_Model_ExtensionScreenshot();
                    $screenshotModel
                        ->setExtensionId($extension_id)
                        ->setImage($new_file_name)
                        ->save();
                }
            }
        }
    }

    protected function _deleteRemovedImages($extension_id) {
        umask(0);

        /* delete extension screenshots removed from form */
        $form_screenshots = $this->_getParam('screenshots_ids', array());
        $form_screenshots = (is_array($form_screenshots) ? $form_screenshots: array());

        $extension_screenshots = new Application_Model_ExtensionScreenshot();
        foreach($extension_screenshots->fetchByExtensionId($extension_id) as $screenshot) {
            if(!in_array($screenshot->getId(), $form_screenshots)) {
                $file_to_delete = $this->view->ImagePath($screenshot->getImage(), 'extension/screenshots');
                if(file_exists($file_to_delete)) {
                    unlink($file_to_delete);
                }
                $screenshot->delete();
            }
        }
    }
}