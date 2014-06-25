<?php

class RocketWeb_Cli_Kit_Pkill
    extends RocketWeb_Cli_Query
{
    public function pkill($systemName){
        $this->append('pkill -u '.$systemName.' pure-ftpd');
        return $this;
    }
}