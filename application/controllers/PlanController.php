<?php

class PlanController extends Integration_Controller_Action {

    public function init() {
        /* Initialize action controller here */
        $this->_helper->sslSwitch();
    }

    public function indexAction() {
        # display grid of all plans
        /* index action is only alias for list extensions action */
        $this->listAction();
    }

    public function listAction() {
        $planModel = new Application_Model_Plan();
        $paginator = $planModel->fetchList();
        $page = (int) $this->_getParam('page', 0);
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(10);
        $this->view->plans = $paginator;
        $this->render('list');
    }

    public function addAction() {
        /* add and edit actions should have the same logic */
        $this->editAction('Add');
    }
    
    /**
     * Render plan form
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
                'controller' => 'plan',
                'action'     => 'index',
            ), 'default', true);
        }

        $form_type = 'Application_Form_Plan'.$action;
        $form = new $form_type();

        $form_data = array();

        $planModel = new Application_Model_Plan();
        if($id) {
            $planModel->find($id);
            if(!strlen($planModel->getName())) {
                $this->_helper->FlashMessenger('Plan does not exist.');
                return $this->_helper->redirector->gotoRoute(array(
                    'module'     => 'default',
                    'controller' => 'plan',
                    'action'     => 'index',
                ), 'default', true);
            }
            $form_data = $planModel->__toArray();
        }

        if($this->_request->isPost()) {
            $form_data = $this->_request->getPost();

            if($form->isValid($form_data)) {
                $planModel->setOptions($form_data)->save();
                if('Edit' == $action) {
                    $this->_helper->FlashMessenger('Plan has been updated properly.');
                } else {
                    $this->_helper->FlashMessenger('Plan has been added properly.');
                }

                return $this->_helper->redirector->gotoRoute(array(
                        'module'     => 'default',
                        'controller' => 'plan',
                        'action'     => 'index',
                ), 'default', true);
            }
        }

        $form->populate($form_data);
        foreach($form_data as $key => $val) {
            $this->view->$key = $val;
        }

        $this->view->form = $form;
    
    }

    public function deleteAction()
    {
        // array with redirect to grid page
        $redirect = array(
                'module'      => 'default',
                'controller'  => 'plan',
                'action'      => 'index'
        );

        // init form object
        $form = new Application_Form_PlanDelete();

        // shorten request
        $request = $this->getRequest();

        // if request is without proper id param
        // redirect to grid with information message 
        if(((int)$request->getParam('id', 0)) == 0) {
            // set message
            $this->_helper->FlashMessenger(
                array(
                    'type' => 'error',
                    'message' => 'You cannot delete plan with specified id.'
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
                if($request->getParam('confirm') == 1) {
                    $flash_message = array(
                        'type' => 'success',
                        'message' => 'You have deleted plan successfully.'
                    );
                    $plan = new Application_Model_Plan();
                    // set news id to the one passed by get param
                    try {
                        $plan->delete($request->getParam('id'));
                    } catch(Exception $e) {
                        $this->getLog()->log('Admin - plan delete', Zend_Log::ERR, $e->getMessage());
                        $flash_message = array(
                            'type' => 'error',
                            'from_scratch' => 1,
                            'message' => 'We couldn\'t delete plan.<span class="hidden">'.$e->getMessage().'</span>'
                        );
                    }
                    // set message
                    $this->_helper->FlashMessenger(
                        $flash_message
                    );
                } else {
                    // deletion cancelled
                    // set message
                    $this->_helper->FlashMessenger(
                        array(
                            'type' => 'notice',
                            'message' => 'Plan deletion cancelled.'
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
}