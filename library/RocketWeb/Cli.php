<?php

class RocketWeb_Cli
{
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
        'git' => 'Git'
    );
    /**
     * Returns query object
     * @param string $string - full command ( with arguments )
     * @param string $values - values to bind into the '?' sign
     * @return RocketWeb_Cli_Query
     */
    public function createQuery($string = '', $values = null)
    {
        return new RocketWeb_Cli_Query($string, $values);
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
            throw new RocketWeb_Cli_Exception($kit . ' kit does not exist.');
        }

        return new $kit();
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
        $this->_last_exec_status = NULL;

        try {
            exec($query, $this->_last_output, $this->_last_exec_status);
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
        return $this->_last_exec_status;
    }
    public function getLastOutput()
    {
        return $this->_last_output;
    }
}