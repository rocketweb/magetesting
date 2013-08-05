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
    public function listAll($path)
    {
        return $this->append('ls -al ?', $path);
    }
    /**
     * @param string $path
     * @param string $destination
     * @param boolean $recursive
     * @param boolean $preserve - whether keep filemode/owners/etc after copy
     * @return RocketWeb_Cli_Kit_File
     */
    public function copy($path, $destination, $recursive = true, $preserve = false)
    {
        return
            $this
                ->append('cp :recursive:preserve? ?', array($path, $destination))
                ->bindAssoc(':recursive', ($recursive ? '-R ' : ''), false)
                ->bindAssoc(':preserve', ($preserve ? '-p ' : ''), false);
    }
    /**
     * @param string $path
     * @return RocketWeb_Cli_Kit_File
     */
    public function delete($path)
    {
        return $this->append('rm -R ?', $path);
    }
    /**
     * 
     * @param string $name name of file|directory to find
     * @param int $type - self::TYPE_FILE | self::TYPE_DIR
     * @param string $path - start path
     * @return RocketWeb_Cli_Kit_File
     */
    public function find($name, $type, $path = '')
    {
        return
            $this
                ->append('find :path -type :type -name :name', $name)
                ->bindAssoc(':path', $path, ($path ? true : false))
                ->bindAssoc(':type', (($type === self::TYPE_DIR) ? 'd' : 'f'), false)
                ->bindAssoc(':name', $name);
    }
    public function printPaths($absolute = false)
    {
        return
            $this
                ->append('-printf ":absolute%h\n"')
                ->bindAssoc(':absolute', ($absolute ? '`pwd`/' : ''), false);
    }
    public function printFiles($safeEscape = false)
    {
        return
            $this
                ->append('-print:safe')
                ->bindAssoc(':safe', ($safeEscape ? '0' : ''), false);
    }
    public function followSymlinks()
    {
        return $this->append('-L');
    }
}