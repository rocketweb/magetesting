<?php

class RocketWeb_Cli
{
    protected $_logger;
    protected $_log_enabled = false;
    protected $_last_status = NULL;
    protected $_last_output = array();

    protected $_kit_mapper = array(
        'file' => 'File',
        'ssh' => 'Ssh',
        'apache' => 'Apache',
        'service' => 'Service',
        'wget' => 'Wget',
        'gzip' => 'Compression_Gzip',
        'tar' => 'Compression_Tar',
        'git' => 'Git',
        'mysql' => 'Mysql',
        'user' => 'User'
    );
    /**
     * Returns query object
     * @param string $string - full command ( with arguments )
     * @param string $values - values to bind into the '?' sign
     * @return RocketWeb_Cli_Query
     */
    public function createQuery($string = '', $values = null)
    {
        return new RocketWeb_Cli_Query($string, $values, $this);
    }

    /**
     * returns specific query object which contains predefined commands
     * @param string $type
     * @throws RocketWeb_Cli_Exception
     * @return RocketWeb_Cli_Query
     */
    public function kit($type)
    {
        if(!is_string($type)) {
            throw new RocketWeb_Cli_Exception('Kit type has to be string.');
        }
        if(
            !isset($this->_kit_mapper[$type])
            || !class_exists(
                ($kit = 'RocketWeb_Cli_Kit_'.$this->_kit_mapper[$type])
            )
        ) {
            throw new RocketWeb_Cli_Exception($type . ' kit does not exist.');
        }

        return new $kit('', null, $this);
    }

    public function setLogger(Zend_Log $log)
    {
        $this->_logger = $log;
        return $this;
    }
    public function enableLogging($val)
    {
        $this->_log_enabled = (bool) $val;
        return $this;
    }
    /**
     * Executes passed string and returns it's value, if RocketWeb_Cli_Query was passed
     * <br /> method will cast object to string
     * @param mixed $query - string | RocketWeb_Cli_Query
     */
    public function exec($query = null)
    {
        $query = (string)$query;
        if(!$query) {
            throw new RocketWeb_Cli_Exception('Wrong query to execute.');
        }

        $this->_last_output = array();
        $this->_last_status = NULL;

        if($this->_log_enabled && $this->_logger instanceof Zend_Log) {
            $this->_logger->log('Exec Wrapper Query:'.$query, Zend_Log::DEBUG);
        }
        try {
            exec($query, $this->_last_output, $this->_last_status);
            if($this->_log_enabled && $this->_logger instanceof Zend_Log) {
                $this->_logger->log('Exec Wrapper Status:'.$this->_last_status, Zend_Log::DEBUG);
                $output = var_export($this->_last_output, true);
                $this->_logger->log('Exec Wrapper Output:'.$output, Zend_Log::DEBUG);
            }
        } catch(Exception $e) {
            throw new RocketWeb_Cli_Exception($e->getMessage(), $query, $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * @return mixed - NULL on no execution and INT value returned by executed command
     */
    public function getLastStatus()
    {
        return $this->_last_status;
    }
    public function getLastOutput()
    {
        return $this->_last_output;
    }
}