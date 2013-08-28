<?php

class RocketWeb_Cli_Kit_Ssh
    extends RocketWeb_Cli_Query
{
    public function connect($user, $password, $host, $port)
    {
        $this->append('sshpass -p :password ssh -o LogLevel=ERROR -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no');
        $this->append(':user@:host -p :port');
        $this->bindAssoc(':user', $user)
             ->bindAssoc(':password', $password)
             ->bindAssoc(':host', $host)
             ->bindAssoc(':port', $port);
        return $this;
    }
    public function remoteCall($query)
    {
        return $this->append('?', (string)$query);
    }
}