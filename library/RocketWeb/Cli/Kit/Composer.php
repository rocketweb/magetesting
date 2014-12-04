<?php

class RocketWeb_Cli_Kit_Composer
    extends RocketWeb_Cli_Query
{
    private function setup()
    {
        $this->newQuery();
        //$this->asSuperUser(true);
        $this->append('php ' . realpath(APPLICATION_PATH . '/../scripts') . '/composer.phar');
        return $this;
    }
    public function installRoot($targetDir)
    {
        chdir($targetDir);
        $this->setup()
            ->append(' install');
        return $this;
    }

    public function installSetup($targetDir)
    {
        chdir($targetDir);
        $this->setup()
            ->append(' install');
        return $this;
    }
}