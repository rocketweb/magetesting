<?php

/**
 * It works similar to SEO friendly url
 * @author Grzegorz <grzegorz@rocketweb.com>
 *
 */
class Zend_View_Helper_NiceString
{
    /**
     * Sanitizes string - as SEO friendly url
     * @param string $string
     */
    public function NiceString($string)
    {
        return preg_replace('/[^a-z0-9\._-]/i', '-', $string);
    }
}