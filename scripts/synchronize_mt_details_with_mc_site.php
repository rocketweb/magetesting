<?php

include 'init.console.php';

try {
    $parser = new MagentoConnectParser();
    $parser->setDebug(true);
    $parser->setLogger($log);
    $parser->setCache($bootstrap->getResource('cache'));
    $parser->process();

} catch(Exception $e) {
    $log->log('Syncing extension detailed data from magento connect site.', Zend_Log::ERR, $e->getMessage());
}

class MagentoConnectParser 
{
    protected $_cache;
    
    protected $_categories = array(
        'customer-experience' => array(
            'url' => 'http://www.magentocommerce.com/magento-connect/customer-experience.html',
            'id'  => 1
        ),
        'site-management' => array(
            'url' => 'http://www.magentocommerce.com/magento-connect/site-management.html',
            'id'  => 2
        ),
        'integrations' => array(
            'url' => 'http://www.magentocommerce.com/magento-connect/integrations.html',
            'id'  => 5
        ),
        'marketing' => array(
            'url' => 'http://www.magentocommerce.com/magento-connect/marketing.html',
            'id'  => 3
        ),
        'utilities' => array(
            'url' => 'http://www.magentocommerce.com/magento-connect/utilities.html',
            'id'  => 6
        ),
    );
    
    protected $_stats = array(
        'linksCount' => 0,
        'processedCategories' => array(),
    );

    public function setCache($cache)
    {
        $this->_cache = $cache;
    }

    public function getCache()
    {
        return $this->_cache;
    }

    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    public function getLogger()
    {
        return $this->_logger;
    }

    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }

    public function getDebug()
    {
        return $this->_debug;
    }

    public function process() 
    {
        foreach ($this->_categories as $categoryKey => $category) {
            $this->_stats['processedCategories'][] = $categoryKey; 
            $this->_processCategory($category);
        }

        $msg = 'Magento Connect Details Sync';
        $info =  'Processed links: ' . $this->_stats['linksCount'] . "\n"
              . 'Processed categories: ' . implode($this->_stats['processedCategories'], ', ') . "\n";

        $this->getLogger()->log($msg, Zend_Log::DEBUG, $info);
    }
    
    /**
     * @param $category
     * @throws Exception
     */
    protected function _processCategory($category)
    {
        $extensionLinksFound = 1;
        $page = 1;

        while ($extensionLinksFound == 1) {

            $categoryPage = $this->_fetchUrl($category['url'] . '?p=' . $page);

            if ($this->getDebug()) {
                echo 'Category: ' . $category['url'] . "\n";
            }

            $linkPattern = '/<h2 class=\"featured-extension-title\">(.*?)<\/h2>/s';

            preg_match_all($linkPattern, $categoryPage, $anchorTexts);

            $anchorTexts = $anchorTexts[1];

            foreach ($anchorTexts as $anchor) {
                $link = $this->_extractExtensionLink($anchor);

                $this->_stats['linksCount']++;

                if ($this->_validateExtensionLink($link)) {
                    // fetch extension page
                    $extensionPage = $this->_fetchUrl($link);

                    if ($this->getDebug()) {
                        echo 'Extension details: ' . $link . ' (Page ' . $page . ')' . "\n";
                    }

                    // parse extension page and extract config json
                    $config = $this->_extractExtensionConfig($extensionPage);

                    // check if extension key exist on page
                    if (!isset($config['extensionKey20']) || !strlen($config['extensionKey20'])) {
                        echo 'Extension page does not contain extension key.' . "\n";
                        continue;
                    }

                    // update data in mage testing database
                    $key = $this->_extractExtensionKey($config['extensionKey20']);

                    $extension = new Application_Model_Extension();

                    $extensions = $extension->findByExtensionKeyAndEdition($key);

                    foreach ($extensions as $extension) {
                        $extension->setCategoryId($category['id']);
                        $extension->setExtensionDetail($config['currentPageUrl']);
                        $extension->save();

                        if ($this->getDebug()) {
                            echo 'Saved extension: ' . $extension->getId() . "\n";
                        }
                    }

                    //break 2;
                }
            }
 
            if (!count($anchorTexts)) {
                $extensionLinksFound = 0;
            }

            $page++;
        }
    }

    /**
     * Fetch one url from magento connect.
     * 
     * It saves result in cache for one week.
     * 
     * It makes additional sleep if it fetches data from url. 
     * It doesn't make sleep if data is loaded from cache. 
     * 
     * @param $category
     * @return string
     * @throws Exception
     */
    protected function _fetchUrl($url)
    {
        $id = md5($url);

        if (!($this->getCache()->test($id))) {
            $http = new Zend_Http_Client($url);
            $response = $http->request();

            if ($response->isError()) {
                throw new Exception('Fetching mage connect page failed (' . $url . ').');
            }

            $data = $response->getBody();
            $this->getCache()->save($data, $id, array(), 604800);

            $this->_sleep();

            return $data;
        }

        return $this->getCache()->load($id);
    }

    /**
     * halt for 0.5 sec, 0.74 sec, 1 sec or 2 sec
     */
    protected function _sleep()
    {
        $rand = array(500000, 740000, 1000000, 2000000); 
        shuffle($rand);
        usleep(array_pop($rand));
    }

    protected function _extractExtensionLink($data)
    {
        $link = '';

        $data = trim($data);

        $urlPattern = '/href=\"(.*?)\"/';

        preg_match_all($urlPattern, $data, $link);

        return array_pop($link[1]);
    }
    
    protected function _extractExtensionConfig($data)
    {
        $config = '';

        $data = trim($data);

        $pattern = '/var config \=(.*?)\;/s';

        preg_match($pattern, $data, $config);
        
        $config = $config[1];

        return Zend_Json::decode($config);
    }

    protected function _extractExtensionKey($key20)
    {
        $prefix = 'http://connect20.magentocommerce.com/community/';

        return substr($key20, strlen($prefix));
    }

    protected function _validateExtensionLink($link) 
    {
        $pattern = 'http://www.magentocommerce.com/magento-connect/';

        if (strpos($link, $pattern) === false) {
            return false;
        }

        return true;
    }
}