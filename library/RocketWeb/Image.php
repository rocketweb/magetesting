<?php

/**
 * This is based on the code of S. Mohammed Alsharaf, http://www.zfsnippets.com/snippets/view/id/44.
 * 
 * This class remains largely unchanged, although I used a much abbreviated piece of code for maintaining the aspect ratio. 
 * 
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU Public License
 * @author     Cameron Germein (cameron@dhmedia.com.au)
 * @version        1.0
 */
class RocketWeb_Image {

    protected $_filename = '';
    protected $_image = '';
    protected $_width = '';
    protected $_height = '';
    protected $_mime_type = '';
    protected $_view = null;

    const IMAGETYPE_GIF = 'image/gif';
    const IMAGETYPE_JPEG = 'image/jpeg';
    const IMAGETYPE_PNG = 'image/png';
    const IMAGETYPE_JPG = 'image/jpg';

    public function setView($view) {
        $this->_view = $view;
        return $this;
    }

    public function open($filename) {
        $this->_filename = $filename;
        $this->_setInfo();

        switch ($this->_mime_type) {
            case self::IMAGETYPE_GIF :
                $this->_image = imagecreatefromgif($this->_filename);
                break;
            case self::IMAGETYPE_JPEG :
            case self::IMAGETYPE_JPG :
                $this->_image = imagecreatefromjpeg($this->_filename);
                break;
            case self::IMAGETYPE_PNG :
                $this->_image = imagecreatefrompng($this->_filename);
                break;
            default :
                throw new Exception('Image extension is invalid or not supported.');
                break;
        }
        return $this;
    }

    protected function _output($save_in = null, $quality, $filters = null) {
        switch ($this->_mime_type) {
            case self::IMAGETYPE_GIF :
                return imagegif($this->_image, $save_in);
                break;
            case self::IMAGETYPE_JPEG :
            case self::IMAGETYPE_JPG :
                $quality = is_null($quality) ? 100 : $quality;
                return imagejpeg($this->_image, $save_in, $quality);
                break;
            case self::IMAGETYPE_PNG :
                $quality = is_null($quality) ? 9 : $quality;
                $filters = is_null($filters) ? null : $filters;
                return imagepng($this->_image, $save_in, $quality, $filters);
                break;
            default :
                throw new Exception('Image cannot be created.');
                break;
        }
    }

    public function display($quality = null, $filters = null) {
        if ($this->_view instanceof Zend_View) {
            $this->_view->getResponse()->setHeader('Content-Type', $this->_mime_type);
        } else {
            header('Content-Type', $this->_mime_type);
        }
        return $this->_output(null, $quality, $filters);
    }

    public function save($save_in = null, $quality = null, $filters = null) {
        return $this->_output($save_in, $quality, $filters);
    }

    public function __destruct() {
        @imagedestroy($this->_image);
    }

    protected function _setInfo() {
        $img_size = @getimagesize($this->_filename);
        if (!$img_size) {
            throw new Exception('Could not extract image size.');
        } elseif ($img_size[0] == 0 || $img_size[1] == 0) {
            throw new Exception('Image has dimension of zero.');
        }
        $this->_width = $img_size[0];
        $this->_height = $img_size[1];
        $this->_mime_type = $img_size['mime'];
    }

    public function getWidth() {
        return $this->_width;
    }

    public function getHeight() {
        return $this->_height;
    }

    protected function _refreshDimensions() {
        $this->_height = imagesy($this->_image);
        $this->_width = imagesx($this->_image);
    }

    /**
     * If image is GIF or PNG keep transparent colors
     * 
     * @credit http://github.com/maxim/smart_resize_image/tree/master
     * @param $image src of the image
     * @return the modified image
     */
    protected function _handleTransparentColor($image = null) {
        $image = is_null($image) ? $this->_image : $image;

        if (($this->_mime_type == self::IMAGETYPE_GIF) || ($this->_mime_type == self::IMAGETYPE_PNG)) {
            $trnprt_indx = imagecolortransparent($this->_image);

            // If we have a specific transparent color
            if ($trnprt_indx >= 0) {
                // Get the original image's transparent color's RGB values
                $trnprt_color = imagecolorsforindex($this->_image, $trnprt_indx);

                // Allocate the same color in the new image resource
                $trnprt_indx = imagecolorallocate($image, $trnprt_color ['red'], $trnprt_color ['green'], $trnprt_color ['blue']);

                // Completely fill the background of the new image with allocated color.
                imagefill($image, 0, 0, $trnprt_indx);

                // Set the background color for new image to transparent
                imagecolortransparent($image, $trnprt_indx);
            } elseif ($this->_mime_type == self::IMAGETYPE_PNG) {
                // Always make a transparent background color for PNGs that don't have one allocated already
                // Turn off transparency blending (temporarily)
                imagealphablending($image, false);

                // Create a new transparent color for image
                $color = imagecolorallocatealpha($image, 0, 0, 0, 127);

                // Completely fill the background of the new image with allocated color.
                imagefill($image, 0, 0, $color);

                // Restore transparency blending
                imagesavealpha($image, true);
            }
            return $image;
        }
    }

    /**
     * Resize image based on max width and height
     * 
     * @param integer $maxWidth
     * @param integer $maxHeight
     * @return resized image
     */
    public function resize($max_width, $max_height) {
        if ($this->_width < $max_width && $this->_height < $max_height) {
            $this->_handleTransparentColor();
            return $this;
        }

        //maintain the aspect ratio of the image. 
        $ratio_orig = $this->_width / $this->_height;

        if ($max_width / $max_height > $ratio_orig) {
            $max_width = $max_height * $ratio_orig;
        } else {
            $max_height = $max_width / $ratio_orig;
        }

        //$newWidth = $this->_newDimension ( 'w', $maxWidth, $maxHeight );
        //$newHeight = $this->_newDimension ( 'h', $maxWidth, $maxHeight );

        $new_image = imagecreatetruecolor($max_width, $max_height);
        $this->_handleTransparentColor($new_image);
        imagecopyresampled($new_image, $this->_image, 0, 0, 0, 0, $max_width, $max_height, $this->_width, $this->_height);

        $this->_image = $new_image;
        $this->_refreshDimensions();
        return $this;
    }

}
