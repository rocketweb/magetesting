<?php

class Zend_View_Helper_GetScheme extends Zend_View_Helper_ServerUrl
{
    /**
     * Gets http:// or https:// based on current server setting
     * @return string
     */
    public function GetScheme()
    {
        switch (true) {
            case (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] === true)):
            case (isset($_SERVER['HTTP_SCHEME']) && ($_SERVER['HTTP_SCHEME'] == 'https')):
            case (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == 443)):
                $scheme = 'https';
                break;
            default:
            $scheme = 'http';
        }
        return $scheme.'://';
    }
}