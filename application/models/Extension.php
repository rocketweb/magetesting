<?php

class Application_Model_Extension {

    protected $_id;

    protected $_name;
    
    protected $_description;

    protected $_category_id;

    protected $_author;

    protected $_logo;
    
    protected $_version;
    
    protected $_extension;
    
    protected $_extension_encoded;
    
    protected $_namespace_module;
    
    protected $_from_version;
    
    protected $_to_version;
    
    protected $_edition;

    protected $_is_dev;
    
    protected $_price;
    
    /* var Application_Model_ExtensionMapper */
    protected $_mapper;
        
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options)
    {
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . $filter->filter($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    public function setId($id)
    {
        $this->_id = (int)$id;
        return $this;
    }

    public function getId()
    {
        return $this->_id;
    }

   public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->_name;
    }
    
    public function setDescription($value)
    {
        $this->_description = $value;
        return $this;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function setCategoryId($value)
    {
        $this->_category_id = $value;
        return $this;
    }

    public function getCategoryId()
    {
        return $this->_category_id;
    }

    public function setAuthor($value)
    {
        $this->_author = $value;
        return $this;
    }

    public function getAuthor()
    {
        return $this->_author;
    }
    

    public function setLogo($logo)
    {
        $this->_logo = $logo;
        return $this;
    }

    public function getLogo()
    {
        return $this->_logo;
    }

    public function setExtension($value)
    {
        $this->_extension = $value;
        return $this;
    }

    public function getExtension()
    {
        return $this->_extension;
    }
    
    public function setExtensionEncoded($value)
    {
        $this->_extension_encoded = $value;
        return $this;
    }
    
    public function getExtensionEncoded()
    {
        return $this->_extension_encoded;
    }
    
    public function setNamespaceModule($value)
    {
        $this->_namespace_module = $value;
        return $this;
    }

    public function getNamespaceModule()
    {
        return $this->_namespace_module;
    }

    public function setVersion($value)
    {
        $this->_version = $value;
        return $this;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function setFromVersion($value)
    {
        $this->_from_version = $value;
        return $this;
    }

    public function getFromVersion()
    {
        return $this->_from_version;
    }
    
    public function setToVersion($value)
    {
        $this->_to_version = $value;
        return $this;
    }

    public function getToVersion()
    {
        return $this->_to_version;
    }
    
    public function setEdition($value)
    {
        $this->_edition = $value;
        return $this;
    }

    public function getEdition()
    {
        return $this->_edition;
    }
    
    public function setIsDev($value)    {
        $this->_is_dev = $value;
        return $this;
    }

    public function getIsDev()
    {
        return $this->_is_dev;
    }   

    public function setPrice($price)
    {
        $this->_price = $price;
        return $this;
    }

    public function getPrice()
    {
        return $this->_price;
    }

    public function setMapper($mapper)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    /**
     * @return Application_Model_ExtensionMapper
     */
    public function getMapper()
    {
        if (null === $this->_mapper) {
            $this->setMapper(new Application_Model_ExtensionMapper());
        }
        return $this->_mapper;
    }

    public function save()
    {
        $this->getMapper()->save($this);
    }

    public function delete($id)
    {
        $this->getMapper()->delete($id);
    }

    public function find($id)
    {
        $this->getMapper()->find($id, $this);
        return $this;
    }

    public function findByNamespaceAndEdition($namespace, $edition)
    {
        return $this->getMapper()->findByNamespaceAndEdition($namespace, $edition);
    }

    public function fetchAll()
    {
        $extensions = array();
        foreach($this->getMapper()->fetchAll() as $row) {
            $extensions[] = array(
                'item' => $row,
                'screenshots' => $row->fetchScreenshots()
            );
        }
        return $extensions;
    }

    public function __toArray()
    {
        return array(
                'id' => $this->getId(),
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'category_id' => $this->getCategoryId(),
                'author' => $this->getAuthor(),
                'version' => $this->getVersion(),
                'logo' => $this->getLogo(),
                'extension' => $this->getExtension(),
                'extension_encoded' => $this->getExtensionEncoded(),
                'namespace_module' => $this->getNamespaceModule(),
                'from_version' => $this->getFromVersion(),
                'to_version' => $this->getToVersion(),
                'edition' => $this->getEdition(),
                'is_dev' => $this->getIsDev(),
                'price' => $this->getPrice(),
        );
    }

    public function getKeys()
    {
        return $this->getMapper()->getKeys();
    }

    public function getOptions()
    {
        return $this->getMapper()->getOptions();
    }
    
    public function getAllForStore($store_name){
        return $this->getMapper()->getAllForStore($store_name);
    }
    
    /**
     * fetches available and installed extensions for given store
     * @param string $store_name
     */
    public function fetchStoreExtensions($store_name){
        $extensions = $this->getMapper()->fetchStoreExtensions($store_name)->toArray();
        foreach($extensions as $key => $extension) {
            $extensions[$key]['screenshots'] = array();
            $screenshots = $this->fetchScreenshots($extension['id']);
            if($screenshots) {
                foreach($screenshots as $screenshot) {
                    $extensions[$key]['screenshots'][] = $screenshot->getImage();
                }
            }
        }
        return $extensions;
    }

    public function fetchFullListOfExtensions(){
        $extensions = $this->getMapper()->fetchFullListOfExtensions()->toArray();
        foreach($extensions as $key => $extension) {
            $extensions[$key]['screenshots'] = array();
            $screenshots = $this->fetchScreenshots($extension['id']);
            if($screenshots) {
                foreach($screenshots as $screenshot) {
                    $extensions[$key]['screenshots'][] = $screenshot->getImage();
                }
            }
        }
        return $extensions;
    }
    public function getInstalledForStore($store_name){
        return $this->getMapper()->getInstalledForStore($store_name);
    }
    
    public function findByFilters($filters){
        return $this->getMapper()->findByFilters($filters,  $this);
    }

    /**
     * @param int $id
     * @return int|string
     * @see Application_Model_ExtensionVersionSynchronizer::checkVersion
     */
    public function synchronizeReleases($id = 0) {
        $id = (int)$id ? (int)$id : (int)$this->getId();
        if(!$id) {
            return Application_Model_ExtensionVersionSynchronizer::EXTENSION_DOES_NOT_EXIST;
        }
        $this->find($id);
        $sync = new Application_Model_ExtensionVersionSynchronizer();
        return $sync->checkVersion($this->getNamespaceModule(), $this->getVersion());
    }

    public function fetchScreenshots($id = 0) {
        $id = (int)$id ? (int)$id : (int)$this->getId();
        if(!$id) {
            return array();
        }
        $model = new Application_Model_ExtensionScreenshot();
        return $model->fetchByExtensionId($id);
    }

    /**
     * Clones extension and sets new version for cloned extension<br />
     * You can specify id of extension to clone or just call that method on
     * loaded extension
     * @param int $id
     * @param str $version - method requires version because it is important value
     * @return boolean
     */
    public function addVersionToExtension($id = 0, $version) {
        $id = (int)$id ? (int)$id : (int)$this->getId();
        if(!$id) {
            return false;
        }

        if((int)!$this->getId()) {
            $this->find($id);
        } else {
            return false;
        }

        // reset id to create new record
        $this->_id = NULL;
        $this->setVersion($version);
        $this->setExtension('');
        $this->setExtensionEncoded('');
        $this->save();

        $new_extension_id = $this->getId();
        // new version of given extension couldn't be saved
        if(!$new_extension_id) {
            return false;
        }

        try {
            $image_path = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer')->getActionController()->view;
            // copy logo image
            $new_logo_filename = preg_replace('/\-(' . $id . ')\.(.*)$/i', '-'.$new_extension_id.'.$2', $this->getLogo());
            if($new_logo_filename != $this->getLogo() && $new_logo_filename !== FALSE) {
                $copy_from = $image_path->ImagePath($this->getLogo(), 'extension/logo');
                $copy_to = $image_path->ImagePath($new_logo_filename, 'extension/logo');
                if(copy($copy_from, $copy_to)) {
                    $this->setLogo($new_logo_filename);
                    $this->save();
                }
            }

            // copy screenshots in table and in file structure
            $screenshots = $this->fetchScreenshots($id);
            foreach($screenshots as $screenshot) {
                $new_screenshot_filename = preg_replace('/\-(' . $id . ')\.(.*)$/i', '-'.$new_extension_id.'.$2', $screenshot->getImage());
                if($new_screenshot_filename != $screenshot->getImage() && $new_screenshot_filename !== FALSE) {
                    $copy_from = $image_path->ImagePath($screenshot->getImage(), 'extension/screenshots');
                    $copy_to = $image_path->ImagePath($new_screenshot_filename, 'extension/screenshots');
                    if(copy($copy_from, $copy_to)) {
                        $new_screenshot = new Application_Model_ExtensionScreenshot();
                        $new_screenshot->setExtensionId($new_extension_id);
                        $new_screenshot->setImage($new_screenshot_filename);
                        $new_screenshot->save();
                    }
                }
            }
        }
        catch(Exception $e) {
            // revert changes
            $this->delete($this->getId());
            return false;
        }
        return true;
    }
}