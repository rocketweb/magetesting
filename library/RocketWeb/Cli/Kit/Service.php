<?php

class RocketWeb_Cli_Kit_Service
    extends RocketWeb_Cli_Query
{
    public function restart($name)
    {
        return $this->_service()->append('? restart', $name);
    }

    public function reload($name)
    {
        return $this->_service()->append('? reload', $name);
    }

    protected function _service()
    {
        # '/etc/init.d/'
        return $this->append('/usr/bin/service');
    }
}