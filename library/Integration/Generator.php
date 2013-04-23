<?php

/**
 * Hash password generator
 *
 * @author jan
 */
class Integration_Generator 
{

    public static function generateRandomString($lettercount, $numberCount, $lettersFirst=true)
    {
        
        $letters = substr(
                str_shuffle(
                        str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', 5)
                )
                , 0, $lettercount);
        $numbers = substr(
                str_shuffle(
                        str_repeat('0123456789', 5)
                )
                , 0, $numberCount);
        
        if ($lettersFirst){
            $string = $letters . $numbers;
        } else {
            $string = $numbers. $letters;
        }
        return $string;
    }
    
}
