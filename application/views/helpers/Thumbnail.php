<?php
/**
 * Thumbnail helper
 *
 * Generate the thumbnail or get existing thumbanail of the given dimensions
 *
 * @author Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Zend_View_Helper_Thumbnail extends Zend_View_Helper_Abstract {
    
    protected $_name;
    protected $_thumb_config; 
    protected $_filesystem_path;
    protected $_img_src;
    protected $_thumb_src;
    protected $_full_image_path;
    protected $_file_name;
    protected $_thumb_file_name;
    protected $_image_dir_path;
    protected $_thumb_dir_path;
    protected $_thumbnail; //instance of Image;
    protected $_valid_mime = array ('image/png', 'image/jpeg', 'image/jpg', 'image/gif' );
    protected $_height;
    protected $_width;

    /**
     * Returns the img tag with thumbnail
     * 
     * @param string $file
     * @param int    $width
     * @param int    $height
     * @param string $path
     * @param array  $attribs
     * 
     * @expectedException Exception
     * 
     * @return string Img tag
     */
    public function thumbnail($name, $width = null, $height = null, $attribs = array(), $path = 'extensions') {
        $this->_height = (!is_null($height)) ? (string)$height : '';
        $this->_width = (!is_null($width)) ? (string)$width : '';
        
        //set paths
        $this->_setPaths($name, $path);

        //check that the image exists. 
        if (!is_file($this->_filesystem_path . $this->_full_image_path)) {
            $this->_setPaths('magetesting_placeholder.png', '/public/img/extensions/thumbs/');
        }

        //check the image is valid
        $this->_checkImage();
        
        //set attributes
        $this->_setAttributes ($attribs);

        //generate thumbnail
        $this->_generateThumbnail();

        return $this->_render();
    }
    
    protected function _setPaths($name, $path) {
        $this->_filesystem_path = rtrim(APPLICATION_PATH, '/') . '/..';
        $this->_full_image_path = $path . $name;
        $this->_name = $name;

//        $parts = pathinfo($path);
        $this->_file_name = $name;
        $this->_image_dir_path = $path;
        $this->_thumb_dir_path = str_replace('assets/img/', 'assets/img/thumbs/', $this->_image_dir_path);
        $this->_thumb_file_name = $this->_width . 'x' . $this->_height . '_' . $this->_file_name;

        $this->_img_src = $this->view->baseUrl() . $this->_image_dir_path . '/' . $this->_file_name;
        $this->_thumb_src = $this->view->baseUrl() . $this->_thumb_dir_path . '/' . $this->_thumb_file_name;
    }
    public function getThumbnailPath($name, $path, $width = null, $height = null) {
        $this->_height = (!is_null($height)) ? (string)$height : '';
        $this->_width = (!is_null($width)) ? (string)$width : '';
        $this->_setPaths($name, $path);
        return $full_thumb_path = rtrim($this->_filesystem_path, '/') . '/' . trim($this->_thumb_dir_path, '/') . '/' . ltrim($this->_thumb_file_name, '/');
    }
    protected function _generateThumbnail() {
        $full_thumb_path = rtrim($this->_filesystem_path, '/') . '/' . trim($this->_thumb_dir_path, '/') . '/' . ltrim($this->_thumb_file_name, '/');

        umask(0);
        //make sure the thumbnail directory exists. 
        
        if (!file_exists($this->_filesystem_path . $this->_thumb_dir_path)) {
            if (!mkdir($this->_filesystem_path . $this->_thumb_dir_path, 0777, true)) {
                throw new Exception('Cannot create thumbnail directory!');
            }
        }

        //if the thumbnail already exists, don't recreate it. 
        if (file_exists($full_thumb_path)) {
            $image = new RocketWeb_Image();
            $image->open($full_thumb_path);
            $this->_thumbnail = $image;
            return true;
        }

        // resize image
        $image = new RocketWeb_Image();
        $image->open($this->_filesystem_path . $this->_full_image_path)
                ->resize((!empty($this->_width)) ? $this->_width : 10000000, (!empty($this->_height)) ? $this->_height : 10000000)
                ->save($full_thumb_path);
        $this->_thumbnail = $image;
        return true;
    }
    
    protected function _checkImage() {
        
        if (!$img_info = getimagesize($this->_filesystem_path . '/' . $this->_full_image_path)) {
            throw new Exception('Image is invalid!');
        }

        if (!in_array($img_info['mime'], $this->_valid_mime)) {
            throw new Exception('Image has invalid mime type!');
        }
    }
    
    protected function _setAttributes($attribs) {
        $alt = '';
        $title = '';
        $map = '';
        $class = '';
        $style = '';
        
        if (isset($attribs['alt'])) {
            $alt = 'alt="' . $this->view->escape($attribs['alt']) . '" ';
        }

        if (isset($attribs['title'])) {
            $title = 'title="' . $this->view->escape($attribs['title']) . '" ';
        }

        if (isset($attribs['map'])) {
            $map = 'usemap="#' . $this->view->escape($attribs['map']) . '" ';
        }

        if (isset($attribs['class'])) {
            $class = 'class="' . $this->view->escape($attribs['class']) . '" ';
        }
        
        if (isset($attribs['style'])) {
            $style = 'style="' . $this->view->escape($attribs['style']) . '" ';
        }

        $this->_attribs = $alt . $title . $map . $class . $style;
    }
    
    /**
     * 
     * @return string
     */
    protected function _render() {
        $html = '<img width="' . $this->_thumbnail->getWidth() . '" height="' . $this->_thumbnail->getHeight() . '" src="' . $this->_thumb_src . '" ' . $this->_attribs . '';
        $endTag = ' />';
        
        if (($this->view instanceof Zend_View_Abstract) && !$this->view->doctype()->isXhtml()) {
            $endTag = '>';
        }
        
        $html .= $endTag;
        
        return $html;
    }

}

