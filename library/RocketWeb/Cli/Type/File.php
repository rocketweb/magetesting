<?php

class RocketWeb_Cli_Type_File
    extends RocketWeb_Cli_Query
{
    /**
     * @param string $from - path to move
     * @param string $to - destination path
     * @param boolean $forceOverwrite - whether use mv or rsync -a
     * @return RocketWeb_Cli_Type_File
     */
    public function mv($from, $to, $forceOverwrite = false)
    {
        $mv_type = 'mv';
        if($forceOverwrite) {
            $mv_type = 'rsync -a';
        }

        $this->arg($mv_type . ' ? ?', array($from, $to));

        return $this;
    }
}