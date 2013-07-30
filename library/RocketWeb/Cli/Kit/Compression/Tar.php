<?php

class RocketWeb_Cli_Kit_Compression_Tar
    extends RocketWeb_Cli_Query
{
    protected $_is_compressed = false;

    public function pack($path, $files)
    {
        return $this->tar('tar :$gzipcvf ? ?', array($path, $files));
    }
    public function unpack($path, $move = '')
    {
        return
            $this
                ->tar('tar :$gzipxvf ?:$move')
                ->bindAssoc(
                    ':$move',
                    ($move ? ' -C '.$this->escape($move) : ''),
                    false
                );
    }
    public function isCompressed($value)
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
    public function toString()
    {
        $this->bindAssoc(':$gzip',($this->_is_compressed ? 'z' : ''), false);
        return parent::toString();
    }
}