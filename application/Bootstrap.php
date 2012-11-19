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

    protected function _initNavigation()
    {
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view = $layout->getView();
        $config = new Zend_Config_Xml(APPLICATION_PATH.'/configs/navigation.xml');
    
        $navigation = new Zend_Navigation($config);
        $view->navigation($navigation);
    }

    protected function _initConfig()
    {
        $config = new Zend_Config_Ini(
                APPLICATION_PATH . '/configs/local.ini',
                APPLICATION_ENV
        );

        Zend_Registry::set('config', $config);
        
        return $config;
    }

    /*
     * SQL Updater
     */
    protected function _initSqlUpdater()
    {
        $sqlUpdater = new RocketWeb_SqlUpdater();
        $sqlUpdater->setDb($this->getPluginResource('db')->getDbAdapter());
        $sqlUpdater->setConfig($this->getResource('config'));
        if(!$sqlUpdater->syncData()) {
            /* @var $log Zend_Log */
            $log = $this->getResource('log');
            if($log) {
                echo $sqlUpdater->getError();
                $log->log($sqlUpdater->getError(), Zend_Log::ERR, 'a');
            }
        }
    }
}
