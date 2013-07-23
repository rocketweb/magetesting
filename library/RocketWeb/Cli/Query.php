<?php

class RocketWeb_Cli_Query
{
    protected $_query = '';
    protected $_super_user = false;

    public function __construct($string = '', $values = null)
    {
        $this->pipe($string, $values);
    }

    /**
     * method clones and clears current object
     * @param string $string
     * @param string $values
     * @return RocketWeb_Cli_Query
     */
    public function newQuery($string = '', $values = null)
    {
        return $this->cloneObject()->clear()->append($string, $values);
    }
    public function cloneObject()
    {
        return clone $this;
    }
    /**
     * appends query to the end of existing one
     * @param string $string
     * @param string $values
     * @return RocketWeb_Cli_Query
     */
    final public function append($string = '', $values = null)
    {
        $this->_append($string, $values);
        return $this;
    }

    /**
     * 
     * @param string $string
     * @param string $values
     * @param string $pipe - whether append using ' ' or ' | '
     * @throws RocketWeb_Cli_Exception
     * @return RocketWeb_Cli_Query
     */
    final protected function _append($string = '', $values = null, $pipe = false)
    {
        if(!is_string($string)) {
            throw new RocketWeb_Cli_Exception('No query string passed.');
        }
        
        $append = $this->_query;
        if(null === $values) {
            $this->_query = trim($string);
        } else {
            $this->_query = $this->_bindValues(trim($string), $values);
        }

        if($append) {
            if($pipe) {
                $this->_query = $this->_redirectStdErr($append) . ' | '. $this->_query;
            } else {
                $this->_query = $append . ' '. $this->_query;
            }
        }
    }

    /**
     * pipes new query to the existing one
     * @param string $string
     * @param string $values
     * @return RocketWeb_Cli_Query
     */
    final public function pipe($string = '', $values = null)
    {
        $this->_append($string, $values, true);

        return $this;
    }
    /**
     * clears query string
     * @return RocketWeb_Cli_Query
     */
    final public function clear()
    {
        $this->_query = '';
        return $this;
    }

    /**
     * @return RocketWeb_Cli_Query
     */
    final public function bindAssoc($name, $value = null, $escape = true)
    {
        if(!is_string($name)) {
            throw new RocketWeb_Cli_Exception('Argument name has to be string.');
        }

        $this->_query =
            str_replace(
                $name,
                ($escape ? escapeshellarg($value) : $value),
                $this->_query
            );
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

        return $this->_redirectStdErr($this->_query);
    }

    final public function __toString()
    {
        return $this->toString();
    }

    final protected function _redirectStdErr($string)
    {
        $replaced = preg_replace('/\s*2\s*>\s*&1\s*$/i', '', $string);
        if(NULL === $replaced) {
            throw new RocketWeb_Cli_Exception('PREG Replace Error');
        }

        return $replaced . ' 2>&1';
    }
}