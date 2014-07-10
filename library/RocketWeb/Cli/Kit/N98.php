<?php

class RocketWeb_Cli_Kit_N98
    extends RocketWeb_Cli_Query
{
    private $_cacheFile;
    private function setup($login)
    {


        $this->append(realpath(APPLICATION_PATH.'/../scripts').'/n98-magerun.phar');
        $this->_cacheFile = realpath(APPLICATION_PATH.'/../data/cache').'/conflicts';
        if(!file_exists($this->_cacheFile)){
            mkdir($this->_cacheFile, 0777, true);
        }
        $this->_cacheFile .= '/'.strtolower($login).'.'.date('Ymd-His').'.cache';

        return $this;
    }
    public function conflict($rootDir, $login)
    {
        chdir($rootDir);
        $this->setup($login)
            ->append(' dev:module:rewrite:conflicts --log-junit="'.$this->_cacheFile.'"');
        return $this;
    }

    public function parseConflict($output = array())
    {
        $conflicts = array();

       $xml=simplexml_load_file($this->_cacheFile);

        $child = $xml->testsuite->testcase;
        $failures = (int)$child->attributes()->failures;

        if($failures == 0) return $conflicts;

        $parts = array(
            'Rewrite conflict: Type ',
            ' | Class: ',
            ', Rewrites: ',
            ' | Loaded class: '
        );
        foreach($child->failure as $failure){
            $conflict = array(
                'type' => $this->stringBetween($failure,$parts[0],$parts[1]),
                'class' => $this->stringBetween($failure,$parts[1],$parts[2]),
                'rewrites' => str_replace(',', ', ',$this->stringBetween($failure,$parts[2],$parts[3])),
                'loaded' => $this->stringBetween($failure,$parts[3])
            );
            $conflicts[] = $conflict;
        }
        return $conflicts;
    }

    private function stringBetween($string,$partA,$partB = '')
    {
        $start = strpos($string,$partA) + strlen($partA);
        $length = $partB == '' ? strlen($string)-$start : strpos($string,$partB)-$start;
        return substr($string,$start,$length);
    }
}