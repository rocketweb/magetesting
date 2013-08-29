<?php

class RocketWeb_Cli_Kit_Compression_Tar
    extends RocketWeb_Cli_Query
{
    protected $_is_compressed = false;
    protected $_redirect_to_output = false;

    public function pack($path, $files, $verbose = true)
    {
        return
            $this
                ->append('tar :$gzipc:verbosef :path ?', $files)
                ->bindAssoc(':verbose', ($verbose ? 'v' : ''), false)
                ->bindAssoc(':path', $path, ('-' === $path) ? false : true); // whether wrote to stdout instead of file
    }
    public function unpack($path, $move = '', $verbose = true)
    {
        return
            $this
                ->append('tar :$gzipx:verboseof :$path:$move:$output')
                ->bindAssoc(
                    ':$move',
                    ($move ? ' -C '.$this->escape($move) : ''),
                    false
                )
                ->bindAssoc(':verbose', ($verbose ? 'v' : ''), false)
                ->bindAssoc(
                    ':$path',
                    $path,
                    ('-' === $path) ? false : true // whether read from stdin instead of file
                );
    }
    public function strip($number)
    {
        return $this->append('--strip-components=?', (int)$number);
    }
    public function isCompressed($value = true)
    {
        $this->_is_compressed = (bool) $value;
        return $this;
    }
    /**
     * Best way is to check whether exec status == 0 AND
     * output array is not empty
     * @param string $path
     * @return RocketWeb_Cli_Kit_Compression_Tar
     */
    public function test($path)
    {
        return $this->append('tar tf ?', $path);
    }

    public function redirectToOutput($value = true)
    {
        $this->_redirect_to_output = (bool)$value;
        return $this;
    }
    public function exclude($paths)
    {
        if(is_string($paths)) {
            $paths = array($paths);
        }
        foreach($paths as $path) {
            $this->append('--exclude=?', $path);
        }
        return $this;
    }
    public function clear()
    {
        $this->_is_compressed = false;
        $this->_redirect_to_output = false;
        return parent::clear();
    }
    public function toString()
    {
        $this->bindAssoc(':$gzip',($this->_is_compressed ? 'z' : ''), false);
        $this->bindAssoc(':$output', ($this->_redirect_to_output ? ' -O' : ''), false);
        return parent::toString();
    }
}