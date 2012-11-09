<?php

/**
 * helps in getting path to the image ( absolute and relative )
 * @author Grzegorz <grzegorz@rocketweb.com>
 *
 */
class Zend_View_Helper_ImagePath
{
    /**
     * 
     * @param string $image
     * @param string $subpath
     * @param boolean $absolute
     * @param boolean $withFileName
     */
    public function ImagePath($image, $subpath, $absolute = true, $withFileName = true)
    {
        $front = Zend_Controller_Front::getInstance();

        $uploadPath = 'assets/img/'.trim($subpath, '/').'/';
        $imagePath = (
            $absolute ?
                rtrim(APPLICATION_PATH, '/').'/../public/'.$uploadPath :
                rtrim($front->getBaseUrl(), '/').'/public/'.$uploadPath
        );
        preg_match('/^(.)(.)?.*?\..*$/i', $image, $match);
        $dir_prefix = '';

        switch(count($match)) {
            case 2:
                $dir_prefix = $match[1].'/';
                break;
            case 3:
                $dir_prefix = $match[1].'/'.$match[2].'/';
                break;
        }

        $path = rtrim($imagePath, '/').'/'.$dir_prefix.($withFileName ? $image : '');

        return $path;
    }
}