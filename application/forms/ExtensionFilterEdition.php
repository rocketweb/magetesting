<?php

class Application_Form_ExtensionFilterEdition extends Integration_Form
{

    public function init()
    {
        $this->setMethod('GET');
        $this->addElement('select', 'edition', array(
                'label'      => 'Edition',
                'onchange'   => 'this.form.submit()',
                'class'      => 'span1'
        ));
        $this->getElement('edition')->removeDecorator('Label');
    }
}