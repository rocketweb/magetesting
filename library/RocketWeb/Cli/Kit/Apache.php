<?php

class RocketWeb_Cli_Kit_Apache
    extends RocketWeb_Cli_Query
{
    public function enableSite($site)
    {
        return $this->append('a2ensite ?', $site);
    }

    public function disableSite($site)
    {
        return $this->append('a2dissite ?', $site);
    }
}