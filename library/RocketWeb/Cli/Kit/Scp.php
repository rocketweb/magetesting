<?php

class RocketWeb_Cli_Kit_Scp
    extends RocketWeb_Cli_Query
{
    protected $_upload = '';
    protected $_download = '';
    protected $_remotePath = '';

    protected $_recursive = false;

    public function connect($user, $password, $host, $port)
    {
        $this->append('sshpass -p :password scp -o LogLevel=FATAL -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no');
        $this->append('-P :port :$upload :user@:host:$remotePath :$download');
        $this->bindAssoc(':user', $user)
             ->bindAssoc(':password', $password)
             ->bindAssoc(':host', $host)
             ->bindAssoc(':port', $port);
        return $this;
    }

    public function download($from, $to)
    {
        $this->_remotePath = $from;
        $this->_download = $to;

        return $this;
    }
    public function upload($from, $to)
    {
        $this->_upload = $from;
        $this->_remotePath = $to;

        return $this;
    }

    public function recursive($value = true)
    {
        $this->_recursive = (bool) $value;
        return $this;
    }

    protected function _escapePath($path)
    {
        if(!$path) {
            return '';
        }

        $path = trim($path);
        if('*' === substr($path, -1)) {
            return $this->escape(rtrim($path, '*')).'*';
        }

        return $this->escape($path);
    }

    public function toString()
    {
        $this->bindAssoc(
            array(
                ':$upload'     => $this->_upload,
                ':$remotePath' => $this->_remotePath,
                ':$download'   => $this->_download
            ),
            '',
            false
        );
        return parent::toString();
    }
}