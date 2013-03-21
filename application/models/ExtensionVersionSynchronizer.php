<?php

/**
 * Model fetches all magento extensions and caches them
 * also model has method which allows to check new version for given extension
 * @author Grzegorz <grzegorz@rocketweb.com>
 *
 */
class Application_Model_ExtensionVersionSynchronizer
{
    const EXTENSION_DOES_NOT_EXIST = -1;
    const EXTENSION_IS_UP_TO_DATE = 0;
    protected $_extensions = array();
    protected $_extensions_cached_file = '';

    /**
     * config should contain cacheTime and serviceUri keys
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->_extensions_cached_file = APPLICATION_PATH . '/../data/magento-connect-extensions.json';

        if(!is_array($config)) {
            $config = array();
        }
        $cacheTime = isset($config['cacheTime']) ? $config['cacheTime'] : 3600;
        $serviceUri = isset($config['serviceUri']) ? $config['serviceUri'] : 'http://connect20.magentocommerce.com/community/packages.xml';

        if(file_exists($this->_extensions_cached_file)) {
            $file = json_decode(file_get_contents($this->_extensions_cached_file), true);
        } else {
            $file = array('cached_at' => 0, 'extensions' => array());
        }
        try {
            if(time()-$file['cached_at'] < $cacheTime) {
                $this->_extensions = $file['extensions'];
            } else {
                $connect = new Zend_Http_Client();
                $connect->setAdapter(new Zend_Http_Client_Adapter_Curl());
                $connect->setUri($serviceUri);
                $response = $connect->request();
                $xml = new SimpleXMLElement($response->getBody());
                foreach($xml->p as $extension) {
                    $versions = array();
                    foreach($extension->r[0] as $version) {
                        $versions[] = (string)$version;
                    }
                    natsort($versions);
                    $file['extensions'][(string)$extension->n[0]] = array_pop($versions);
                }
                $file['cached_at'] = time();
                file_put_contents($this->_extensions_cached_file, json_encode($file));
            }
        } catch(Exception $e) {
            if(isset($config['logger']) && is_object($config['logger'])) {
                $config['logger']->log('Syncing extensions', Zend_Log::ERR, $e->getMessage());
            }
        }
        $this->_extensions = $file['extensions'];
    }

    /**
     * @param string $extension - it should be namespace module from magento connect and not from extension file content
     * @param string $version - version of the actual extension
     * @return int|string - self::EXTENSION_DOES_NOT_EXIST|self::EXTENSION_IS_UP_TO_DATE|new version string
     */
    public function checkVersion($extension, $version)
    {
        if(array_key_exists($extension, $this->_extensions)) {
            $compare = array($version, $this->_extensions[$extension]);
            natsort($compare);
            $new_version = array_pop($compare);
            if($new_version != $version) {
                return $new_version;
            }
            return self::EXTENSION_IS_UP_TO_DATE;
        }
        return self::EXTENSION_DOES_NOT_EXIST;
    }
}