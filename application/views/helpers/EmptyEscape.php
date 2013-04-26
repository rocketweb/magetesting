<?php

/**
 * 
 * @author Grzegorz Gasiecki <grzegorz@rocketweb.com>
 *
 */
class Zend_View_Helper_EmptyEscape
    extends Zend_View_Helper_Abstract
{
    /**
     * 
     * @param string $string - string to be escaped
     * @param string $empty_supplement - will be returned if $string is empty
     * @return string 
     */
    public function EmptyEscape($string, $empty_supplement = '&nbsp;')
    {
        if($string) {
            return $this->view->escape($string);
        }
        return $empty_supplement;
    }
}