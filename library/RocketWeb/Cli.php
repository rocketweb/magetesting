<?php

class RocketWeb_Cli
{
    protected $_kit_mapper = array(
        'file' => 'File',
        'ssh' => 'Ssh',
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
    public function getCommandsKit($type)
    {
        if(!is_string($type)) {
            throw new RocketWeb_Cli_Exception('Kit type has to be string.');
        }
        if(
            !isset($this->_kit_mapper[$type])
            || !class_exists(
                ($kit = 'RocketWeb_Cli_Type_'.$this->_kit_mapper[$type])
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
        $output = array();
        try {
            exec($query, $output);
        } catch(Exception $e) {
            throw new RocketWeb_Cli_Exception($e->getMessage(), $query, $e->getCode(), $e);
        }
        return $output;
    }
}