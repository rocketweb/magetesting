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

}

