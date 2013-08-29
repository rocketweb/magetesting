<?php

class RocketWeb_Cli_Kit_Service
    extends RocketWeb_Cli_Query
{
    public function restart($name)
    {
        return $this->asSuperUser(true)->append('/etc/init.d/? restart', $name);
    }
}