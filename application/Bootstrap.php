<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initConfig()
    {
        $config = new Zend_Config_Ini(
            APPLICATION_PATH . '/configs/local.ini',
            APPLICATION_ENV
        );

        Zend_Registry::set('config', $config);

        return $config;
    }

    /**
     * Start session
     */
    public function _initCoreSession()
    {
        $this->bootstrap('db');
        $this->bootstrap('session');

        Zend_Session::start();
        $db = $this->getPluginResource('db')->getDbAdapter();
        Zend_Registry::set('db', $db);
    }

    /** Setting mail-transport for testing **/
    protected function _initMailTransport()
    {
        //change the mail transport only if dev or test
        if (APPLICATION_ENV == 'testing') {
            $callback = function()
            {
                return 'ZendMail_' . microtime(true) .'.tmp';
            };
            $fileTransport = new Zend_Mail_Transport_File(
                array('path' => realpath(APPLICATION_PATH . '/../data/cache'),
                    'callback'=>$callback)
            );
            Zend_Mail::setDefaultTransport($fileTransport);
        }
    }

    /**
     * Init logger
     *
     */

    protected function _initLog()
    {
        // init logger
        $log = new Zend_Log();

        $config = $this->getResource('config');

        /// init admin email writer
        // setup formatter to add custom field in mail writer
        $format = '%timestamp% %priorityName%: %message%' . PHP_EOL . PHP_EOL . ' %info%';
        $formatter = new Zend_Log_Formatter_Simple($format);

        // setup mail writer
        $mail = new Zend_Mail();
        $mail->setFrom($config->admin->errorEmail->from->email);

        $email = $config->admin->errorEmail->to->email;

        /* $email is Zend_Config Object */
        $emails = $email->toArray();

        if (!is_array($emails)){
            $emails = array($emails);
        }

        if($emails) {
            $mail->addTo(array_shift($emails));
        }

        if($emails) {
            foreach($emails as $ccEmail) {
                $mail->addCc($ccEmail);
            }
        }

        $writerMail = new Zend_Log_Writer_Mail($mail);
        $writerMail->setSubjectPrependText($config->admin->errorEmail->subject);
        $writerMail->addFilter(Zend_Log::CRIT);
        $writerMail->setFormatter($formatter);

        $log->addWriter($writerMail);

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
        $log->setEventItem('info', '');

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
    
    protected function _initRouting() {
    	$front = Zend_Controller_Front::getInstance();

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/routes.ini');
        $router = new Zend_Controller_Router_Rewrite();
        $router->addConfig($config, 'routes');
        $front->setRouter($router);
        return $router;
    }

    protected function _initRestRoute()
    {
        $frontController = Zend_Controller_Front::getInstance();
        $restRoute = new Zend_Rest_Route($frontController, array(), array('api'));
        $frontController->getRouter()->addRoute('rest', $restRoute);
    }
}
