<?php

class RocketWeb_Cli_Kit_Mysql
    extends RocketWeb_Cli_Query
{
    const EXPORT_SCHEMA = 0;
    const EXPORT_DATA = 1;
    const EXPORT_DATA_AND_SCHEMA = 2;

    public function connect($user, $password, $database)
    {
        return $this->append(
            'mysql -u ? -p? ?',
            array($user, $password, $database)
        );
    }

    /**
     * 
     * @param int $type
     * <br />RocketWeb_Cli_Kit_Mysql::EXPORT_SCHEMA
     * <br />RocketWeb_Cli_Kit_Mysql::EXPORT_DATA
     * <br />RocketWeb_Cli_Kit_Mysql::EXPORT_DATA_AND_SCHEMA
     * @param array $tables
     */
    public function export($type, $tables = array())
    {
        if(!is_array($tables)) {
            $tables = array($tables);
        }
        if($tables) {
            foreach($tables as $table) {
                $this->append('?', $table);
            }
        }

        switch($type) {
            case self::EXPORT_SCHEMA:
                $this->append('--no-data');
            break;
            case self::EXPORT_DATA:
                $this->append('--no-create-db --no-create-info');
            break;
            case self::EXPORT_DATA_AND_SCHEMA:
            default:
                // no need to append anything
            break;
        }
    }

    public function import($path)
    {
        return $this->append('< ?', $path);
    }
}