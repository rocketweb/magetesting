<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /**
     * Init logger
     * 
     */
    protected function _initLog()
    {
        // init logger
        $log = new Zend_Log();

        // init db writer
        $db = $this->getPluginResource('db')->getDbAdapter();

        Zend_Db_Table_Abstract::setDefaultAdapter($db);

        $columnMapping = array(
            'lvl'  => 'priority', 
            'type' => 'priorityName',
            'msg'  => 'message',
            'time' => 'timestamp',
            'info' => 'info', 
        );

        $writerDb = new Zend_Log_Writer_Db($db, 'log', $columnMapping);
        $log->addWriter($writerDb);

        return $log;
    }

    /**
     * Init cache, set instance for use in other components. 
     * 
     */
    protected function _initCache()
    {
        $db = $this->getPluginResource('db')->getDbAdapter();
        $cache = $this->getPluginResource('cachemanager')->getCacheManager()->getCache('database');

        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        Zend_Date::setOptions(array('cache' => $cache));
        Zend_Translate::setCache($cache);
        Zend_Locale::setCache($cache);

        return $cache;
    }

    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('HTML5');
    }
    
    protected function _initConfig()
    {
        $config = new Zend_Config($this->getOptions(), true);
        Zend_Registry::set('config', $config);
        return $config;
    }
    
    
    protected function _initViewHelpers() 
    {
        //required for jquery integration
        $view = new Zend_View();
        $viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
        $view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
        $viewRenderer->setView($view);
        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
    }

}

