<?php

/**
 * It extends HtmlTag decorator to add error flag for the field container.
 *
 * @author     Rocket Web
 */
class Zend_Form_Decorator_Overall extends Zend_Form_Decorator_HtmlTag
{
    public function render($content)
    {
        if ($this->getElement()->hasErrors()) {
            $cls = $this->getOption('class');
            $this->setOption('class', $cls . ' error');
        }

        return parent::render($content);
    }

}
