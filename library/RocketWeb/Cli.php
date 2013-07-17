<?php

class RocketWeb_Cli
{
    public function query($string, $values = null)
    {
        return new RocketWeb_Cli_Query($string, $values);
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