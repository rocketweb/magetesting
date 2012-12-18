<?php

/**
 * 
 * @author Marcin Kazimierczak <marcin@rocketweb.com>
 *
 */
class Zend_View_Helper_NiceStatus
{
    /**
     * Upper case and remove "-"
     * @param string $string
     * @return string 
     */
    public function niceStatus($string)
    {
        return ucwords(str_replace('-', ' ', $string));
    }
}