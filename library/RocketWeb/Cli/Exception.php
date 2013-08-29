<?php

class RocketWeb_Cli_Exception
    extends Exception
{
    protected $lastQuery = '';

    public function __construct($message, $lastQuery = '', $code = 0, Exception $previous = null)
    {
        if($lastQuery) {
            $this->_lastQuery = $lastQuery;
        }
        parent::__construct($message, $code, $previous);
    }

    public function getLastQuery()
    {
        return $this->_lastQuery;
    }
}