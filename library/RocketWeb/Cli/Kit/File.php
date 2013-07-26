<?php

class RocketWeb_Cli_Kit_File
    extends RocketWeb_Cli_Query
{
    const TYPE_DIR = 0;
    const TYPE_FILE = 1;
    /**
     * @param string $from - path to move
     * @param string $to - destination path
     * @param boolean $forceOverwrite - whether use mv or rsync -a
     * @return RocketWeb_Cli_Type_File
     */
    public function move($from, $to, $forceOverwrite = false)
    {
        $mv_type = 'mv';
        if($forceOverwrite) {
            $mv_type = 'rsync -a';
        }

        $this->arg($mv_type . ' ? ?', array($from, $to));

        return $this;
    }

    /**
     * @param string $name
     * @param int $type self::TYPE_DIR | self::TYPE_FILE
     * @param string $mode - chmod mode(numeric) used to create directory ( not file )
     * @return RocketWeb_Cli_Kit_File
     */
    public function create($name, $type, $mode = '')
    {
        if($type === self::TYPE_DIR) {
            $this->append('mkdir -p :name');
            if($mode) {
                $this->append('-m ?', $mode);
            }
        } else {
            $this->append('touch :name');
        }

        return $this->bindAssoc(':name', $name);
    }

    public function fileMode($path, $mode, $recursive = true)
    {
        $this->append('chmod :mode :path:recursive');
        $this->bindAssoc(':mode', $mode);
        $this->bindAssoc(':path', $path, ($path ? true : false));
        $this->bindAssoc(':recursive', ($recursive ? ' -R' : ''), false);
        return $this;
    }
    public function fileOwner($path, $owner, $recursive = true)
    {
        $this->append('chown :owner :path:recursive');
        $this->bindAssoc(':owner', $owner);
        $this->bindAssoc(':path', $path, ($path ? true : false));
        $this->bindAssoc(':recursive', ($recursive ? ' -R' : ''), false);
        return $this;
    }
    /**
     * @param string $path
     * @return RocketWeb_Cli_Kit_File
     */
    public function copy($path)
    {
        return $this->append('cp ?', $path);
    }
    /**
     * @param string $path
     * @param boolean $recursive
     * @return RocketWeb_Cli_Kit_File
     */
    public function delete($path, $recursive = false)
    {
        return $this->append('rm ? ?', array(($recursive ? '-R' : ''),$path));
    }
    /**
     * 
     * @param string $name name of file|directory to find
     * @param int $type - self::TYPE_FILE | self::TYPE_DIR
     * @param string $printf - format of how find should printf results
     * @return RocketWeb_Cli_Kit_File
     */
    public function find($name, $type, $printf = '')
    {
        $values = array($name);

        $print = '';
        if($printf && is_string($printf)) {
            $print = ' -printf ?';
            $values[] = $printf;
        }
        return
            $this
                ->append('find -type :type -name ?' . $print, $values)
                ->bindAssoc(':type', (($type === self::TYPE_DIR) ? 'd' : 'f'), false);
    }
    public function followSymlinks()
    {
        return $this->append('-L');
    }
}