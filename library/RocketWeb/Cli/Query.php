<?php

class RocketWeb_Cli_Query
{
    protected $_query = '';
    protected $_super_user = false;

    public function __construct($string = '', $values = null)
    {
        if(!is_string($string)) {
            throw new RocketWeb_Cli_Exception('No query string passed.');
        }

        if(null === $values) {
            $this->_query = trim($string);
        } else {
            $this->_query = $this->_bindValues(trim($string), $values);
        }
    }

    /**
     * clears query string
     * @return RocketWeb_Cli_Query
     */
    public function clear()
    {
        $this->_query = '';
        return $this;
    }

    /**
     * @return RocketWeb_Cli_Query
     */
    final public function arg($name, $values = null)
    {
        if(!is_string($name)) {
            throw new RocketWeb_Cli_Exception('Argument name has to be string.');
        }

        $this->_query .= ' '.$this->_bindValues($name, $values);
        return $this;
    }

    final protected function _bindValues($string, $values)
    {
        if(!is_array($values)) {
            $values = array($values);
        }

        foreach($values as $value) {
            $position = strpos($string, '?');
            if(false !== $position) {
                $string = substr_replace($string, escapeshellarg($value), $position, 1);
            }
        }

        return $string;
    }

    /**
     * @return RocketWeb_Cli_Query
     */
    final public function asSuperUser($value)
    {
        $this->_super_user = ((int)$value ? true : false);
        return $this;
    }

    final public function toString()
    {
        if($this->_super_user) {
            $replaced = preg_replace('/^sudo\s*/i', '', $this->_query);
            if(NULL === $replaced) {
                throw new RocketWeb_Cli_Exception('PREG Replace Error');
            }
            $this->_query = 'sudo '.$replaced;
        }

        $replaced = preg_replace('/\s*2\s*>\s*&1$/i', '', $this->_query);
        if(NULL === $replaced) {
            throw new RocketWeb_Cli_Exception('PREG Replace Error');
        }
        $this->_query .= ' 2>&1';
        return $this->_query;
    }
    final public function __toString()
    {
        return $this->toString();
    }
}