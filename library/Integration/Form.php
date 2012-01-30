<?php


class Integration_Form extends Zend_Form
{
    /**
     * Set up decorators
     */
    protected function _setDecorators()
    {
        // set up path to custom decorators and validators
        $this->addElementPrefixPath('Integration_Form_Decorator', 'Integration/Form/Decorator', 'decorator');
        $this->addElementPrefixPath('Tourdesign_Validate', 'Tourdesign/Validate', 'validate');
        $this->addElementPrefixPath('Tourdesign_Filter', 'Tourdesign/Filter', 'filter');

        $this->setElementDecorators(array(
                array('ViewHelper'),
                array('Errors'),
                array('Description', array('tag' => 'span', 'class' => 'help-block', 'escape' => true)),
                array('HtmlTag', array('tag' => 'div', 'class' => 'input')),
                array('Label', array('escape' => false)),
                array('Overall', array('tag' => 'div', 'class' => 'clearfix')),
        ), array('csrf', 'id'), false);

        $this->setDecorators(array(
                array('FormElements'),
                array('Form', array('class' => 'zend_form')),
        ));

        $this->setDisplayGroupDecorators(array(
                array('FormElements'),
                array('Fieldset'),
        ));

        $submitButtons = array('next', 'save', 'submit');

        foreach ($submitButtons as $submit) {
            if ($this->{$submit} instanceof Zend_Form_Element) {
                $this->{$submit}->getDecorator('overall')->setOption('class', 'actions');
                $this->{$submit}->setAttrib('class', 'btn primary');
                $this->{$submit}->removeDecorator('Label');
            }
        }
    }
}
