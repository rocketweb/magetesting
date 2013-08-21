<?php

class RocketWeb_Cli_Kit_Wget
    extends RocketWeb_Cli_Query
{
    protected $_asSpider = true;
    public function ftpConnect($user, $password, $host, $port)
    {
        $this->append(
            'wget --passive-ftp :host::port:$ftp-path --user=? --password=?:$spider',
            array($user, $password)
        );
        $this->bindAssoc(':host', $host);
        $this->bindAssoc(':port', $port);

        return $this;
    }

    /**
     * method adds timeout and tries options to wget query
     * @param int $seconds
     * @param int $tries
     * @return RocketWeb_Cli_Kit_Wget
     */
    public function addLimits($seconds = 60, $tries = 1)
    {
        $this->append('--timeout=? --tries=?', array($seconds, $tries));
        return $this;
    }

    public function setRootPath($path)
    {
        $this->bindAssoc(':$ftp-path', $path);
        return $this;
    }

    public function downloadRecursive($include = array(), $exclude = array('.htaccess'))
    {
        if(!is_array($exclude)) {
            $exclude = array($exclude);
        }
        if(!is_array($include)) {
            $include = array($include);
        }

        $this->append('-nH -Q300m -m -np -R \'sql,tar,gz,zip,rar\' -N');

        if($exclude) {
            $this->append('-X ?', implode(',', $exclude));
        }
        if($include) {
            $this->append('-I ?', implode(',', $include));
        }

        $this->checkOnly(false);

        return $this;
    }
    public function downloadFile($path)
    {
        $this->append('-N');
        $this->setRootPath($path);

        $this->checkOnly(false);

        return $this;
    }
    /**
     * whether only check paths/files existence or download them
     * @param bool $value
     */
    public function checkOnly($value = true)
    {
        $this->_asSpider = (bool) $value;
        return $this;
    }

    protected function _clearPathifNotSet()
    {
        $this->bindAssoc(':$ftp-path', '', false);
        return $this;
    }

    public function getFileSize()
    {
        $this->checkOnly(true);
        $this->pipe('grep ?', 'SIZE');
        $this->pipe('awk ?', '$5 ~ /[0-9]+/ {print $5}');
        return $this;
    }

    public function toString()
    {
        $this->bindAssoc(':$spider', ((bool)$this->_asSpider ? ' --spider' : ''), false);
        $this->_clearPathifNotSet();
        return parent::toString();
    }
}