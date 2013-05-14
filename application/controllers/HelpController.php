<?php

class HelpController extends Integration_Controller_Action
{

    public function init()
    {
        $this->_helper->sslSwitch(false);
    }

    public function indexAction() 
    {
        //index action
    }
    
    public function categoryAction()
    {
        $request = $this->getRequest();
        $category = $request->getParam('category');
        
        if (file_exists(APPLICATION_PATH . '/views/scripts/help/' . $category . '.phtml')) {
            $this->renderScript('help/' . $category . '.phtml');
        }
    }
    
    public function pageAction()
    {
        $request = $this->getRequest();
        $category = $request->getParam('category');
        $page = $request->getParam('page');

        if (file_exists(APPLICATION_PATH . '/views/scripts/help/' . $category . '/' . $page.'.phtml')) {
            $this->renderScript('help/' . $category . '/' . $page . '.phtml');
        } 
    }
    
}