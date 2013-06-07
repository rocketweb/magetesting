<?php
// IconMenu.php
class Zend_View_Helper_IconMenu extends Zend_View_Helper_Navigation_Menu
{
    protected $_iconPath = '/public/img';

    public function iconMenu(Zend_Navigation_Container $container = null)
    {
        if (null !== $container) {
            $this->setContainer($container);
        }

        return $this;
    }

    public function htmlify(Zend_Navigation_Page $page)
    {
        // get label and title for translating
        $label = $page->getLabel();
        $title = $page->getTitle();

        // translate label and title?
        if ($this->getUseTranslator() && $t = $this->getTranslator()) {
            if (is_string($label) && !empty($label)) {
                $label = $t->translate($label);
            }
            if (is_string($title) && !empty($title)) {
                $title = $t->translate($title);
            }
        }
		
		
        // get attribs for element
        $attribs = array(
            'id'     => $page->getId(),
            'title'  => $title,
            'class'  => $page->getClass()
        );
		
		// is a dropdown?
		$dropDownCaret = '';
		if($page->dropdown) {
			$attribs['data-toggle'] = 'dropdown';
			$dropDownCaret = ' <b class="caret caret-white"></b>';
		}
		
        // does page have a href?
        if ($href = $page->getHref()) {
            $element = 'a';
            $attribs['href'] = $href;
            $attribs['target'] = $page->getTarget();
        } else {
            $element = 'span';
        }
        
        // dodanie ikonki
        if (null !== $page->icon)
        {
	    if(strpos($page->icon,'.')){
	      $icon = '<img src="'.$this->view->baseUrl().$this->_iconPath.'/'.$page->icon.'" alt="" /> ';
	    } else {
	      $icon = '<i class="'.$page->icon.'"></i> ';
	    }
        } else {
            $icon = '';
        }

        return '<' . $element . $this->_htmlAttribs($attribs) . '>'
             . $icon
             . $this->view->escape($label) . $dropDownCaret
             . '</' . $element . '>';
    }
}