<?php

/**
 * 
 * @author Marcin Kazimierczak <marcin@rocketweb.com>
 *
 */
class Zend_View_Helper_LeftTime
{
    /**
     * 
     * @param string|int $seconds
     * @return string 
     */
    public function leftTime($seconds)
    {
        $denominator = 60;
        $seconds = (int)$seconds;
        
        if($seconds > $denominator) {
            $sec = $seconds % $denominator;
            $min = floor($seconds / $denominator);

            $string = $min . ' minute';

            if($min > 1) {
                $string .= 's';
            }

            $string .= ' ' . $sec . ' seconds left';

        } else {
            $string = $seconds . ' seconds left';
        }
        
        return $string;
    }
}