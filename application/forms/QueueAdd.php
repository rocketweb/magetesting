<?php

class Application_Form_QueueAdd extends Integration_Form{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');
        //TODO: move model usage to controller

        $editionModel = new Application_Model_Edition();

        $this->addElement('select', 'edition', array(
                'label'      => 'Edition',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_InArray(array_keys($editionModel->getKeys()))
                )
        ));

        $emptyVersion = array('' => 'Choose...');
        $versions = array_merge($emptyVersion,$editionModel->getOptions());

        $this->edition->addMultiOptions($versions);

        $versionModel = new Application_Model_Version();
        $this->addElement('select', 'version', array(
                'label'      => 'Version',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_InArray($versionModel->getKeys())
                )
        ));
        //$this->version->addMultiOptions();

         
        // Add the submit button
        $this->addElement('submit', 'queueAdd', array(
                'ignore'   => true,
                'label'    => 'Install',
        ));

        $this->_setDecorators();

        $this->queueAdd->removeDecorator('HtmlTag');
        $this->queueAdd->removeDecorator('overall');
        $this->queueAdd->setAttrib('class','btn primary large');
        $this->queueAdd->removeDecorator('Label');

        $this->edition->removeDecorator('HtmlTag');
        $this->edition->removeDecorator('overall');
        $this->edition->removeDecorator('Label');

        $this->edition->addDecorator(array('AddLi' => 'HtmlTag'), array('tag' => 'li'));
        $this->edition->addDecorator(array('AddUl' => 'HtmlTag'), array('tag' => 'ul', 'class' => 'inputs-list'));
        $this->edition->addDecorator(array('AddDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input'));
        $this->edition->addDecorator('Label', array('escape' => false));
        $this->edition->addDecorator('Overall', array('tag' => 'div', 'class' => 'clearfix'));


        $this->version->removeDecorator('HtmlTag');
        $this->version->removeDecorator('overall');
        $this->version->removeDecorator('Label');

        $this->version->addDecorator(array('AddLi' => 'HtmlTag'), array('tag' => 'li'));
        $this->version->addDecorator(array('AddUl' => 'HtmlTag'), array('tag' => 'ul', 'class' => 'inputs-list'));
        $this->version->addDecorator(array('AddDiv' => 'HtmlTag'), array('tag' => 'div', 'class' => 'input'));
        $this->version->addDecorator('Label', array('escape' => false));
        $this->version->addDecorator('Overall', array('tag' => 'div', 'class' => 'clearfix'));
    }

}

