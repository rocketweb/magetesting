<?php

/**
 * Class helps to keep database synced with application schemas
 * @author golaod <grzegorz@rocketweb.com>
 *
 */
class RocketWeb_SqlUpdater
{
    protected $_db;
    protected $_config = null;
    protected $_directoryPath = null;
    protected $_error = '';
    protected $_prefix = 'mysql';
    
    /* for log purposes */
    protected $_currentFile = '';

    /**
     * @param Zend_Db_Adapter_Abstract $db
     * @throws Exception
     * @return RocketWeb_SqlUpdater
     */
    public function setDb($db)
    {
        if(!$db instanceof Zend_Db_Adapter_Abstract) {
            throw new Exception('Db connection must be an instance of Zend_Db_Adapter_Abstract');
        }

        $this->_db = $db;

        return $this;
    }

    /**
     * 
     * @param unknown_type $config
     * @throws Exception
     * @return RocketWeb_SqlUpdater
     */
    public function setConfig($config)
    {
        if($config instanceof Zend_Config) {
            $this->_config = $config;
        } else {
            throw new Exception('Config object must be an instance of Zend_Config');
        }

        $this->_directoryPath = $config->sqlUpdater->directoryPath;

        return $this;
    }
    /**
     * synchronizes database with sql schema files
     * @method syncData
     * @return
     * true - success|nothing new<br />
     * false - failure <br />
     */
    public function syncData()
    {
        $result = false;
        try {
            if(!$this->_db) {
                throw new Exception('Database connection must be set.');
            }
            if(!$this->_directoryPath) {
                throw new Exception('You must specify path to sql updates.');
            }

            $last_version = '';
            try {
                $resource = $this->_db->query('SELECT * FROM sql_updater');
                $data = $resource->fetchAll();
                if($data) {
                    $last_version = $data[0]['version'];
                } 
            } catch(Exception $e) {
                $needle = ($this->_prefix == 'oracle' ? 'does not exist' : 'doesn\'t exist');
                if(!stristr($e->getMessage(), $needle)) { 
                    throw new Exception($e->getMessage());
                } else {
                    /* create sql_updater table */
                    $updaterSql = array();
                    $updaterSql[] = 'CREATE TABLE IF NOT EXISTS sql_updater(
                        version VARCHAR(5) NOT NULL
                    )';
                
                    $this->_executeSql($updaterSql);
                }
            }

            $directory = new DirectoryIterator($this->_directoryPath);
            $sorted_directory = array();
            foreach($directory as $file) {
                if(!$file->isDot() AND !$file->isDir()) {
                    $sorted_directory[$file->getFilename()] = clone $file;
                }
            }
            ksort($sorted_directory, SORT_STRING);
            $update_from_now = false;
            
            foreach($sorted_directory as $file) {              
                $this->_currentFile = $file->getFilename();
                
                $sql = array();
                $version = '';
                $is_good_file = preg_match('#^'.$this->_prefix.'-(?:(?:install\.php)|(?:update-(\d\.\d\.\d)\.php))$#i', $file->getFilename(), $match);
                $found_version = '';
                if(isset($match[1])) {
                    $found_version = $match[1];
                }
                
                // if version does not exist, find install sql
                if($file->getFilename() == $this->_prefix.'-install.php') {
                    include $file->getPathname();

                    if(!isset($version) OR !$version) {
                        throw new Exception('File '.$file->getFilename().' should contain variable $version.');
                    }
                    if(!$last_version) {
                        $update_from_now = true;
                    } elseif($last_version == $version) {
                        $update_from_now = true;
                        continue;
                    }
                    // otherwise update from next update file
                } elseif($file->getFilename() == $this->_prefix.'-update-'.$last_version.'.php') {
                    $update_from_now = true;
                    continue;
                }
                
                if($update_from_now AND $is_good_file AND ($last_version != $found_version OR $last_version == '')) {
                    $this->_db->beginTransaction();
                    
                    /* do not include install file for the second time */
                    if ($file->getFilename() != $this->_prefix.'-install.php'){
                    include $file->getPathname();
                    }
                                      
                    if($found_version) {
                        $sql[] = array('UPDATE sql_updater SET version = ?', 'bind' => array($found_version));
                    } else {
                        $sql[] = array('INSERT INTO sql_updater VALUES(?)', 'bind' => array($version));
                        $last_version = $version;
                    }
                    
                    $this->_executeSql($sql);
                    $last_file = $file->getPathname();
                    $this->_db->commit();
                }

            }
            $result = true;
        } catch(Exception $e) {
            $message = 'Error in version file:'.$this->_currentFile;
            $this->_error = $message . $this->_error;
            $this->_db->rollBack();
        }
        return $result;
    }

    protected function _executeSql($execute)
    {
        foreach($execute as $query) {
            if(is_array($query) AND isset($query['bind'])) {
                //calls to sql updater table
                $this->_db->query($query[0], $query['bind']);
            } else {
                try {
                    $this->_db->query($query);
                } catch (Exception $e){
                    $message = "\nat query:";
                    $message .= "\n".$query;
                    $message .= "\nError message: ";
                    $message .= var_export($e->getMessage(),true);
                    $this->_error = $message;
                    throw $e;
                }
            }
        }
    }

    public function getError()
    {
        return (strlen($this->_error) ? $this->_error : false);
    }
}