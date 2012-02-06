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

        $this->addElement('radio', 'sample_data', array(
                'label'      => 'Install sample data',
                'required'   => true
        ));
        $this->sample_data->addMultiOptions(array(
                1 => 'Yes',
                0 => 'No',
        ));
        
        $this->sample_data->setValue(0)
        ->setSeparator(' ');

        $this->addElement('text', 'instance_name', array(
                'label'      => 'Name or note',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
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
        $this->queueAdd->setAttrib('class','btn btn-primary');
        $this->queueAdd->removeDecorator('Label');

        $this->edition->removeDecorator('HtmlTag');
        $this->edition->removeDecorator('overall');
        $this->edition->removeDecorator('Label');

        $this->edition->addDecorator('Label', array('escape' => false));
        $this->edition->addDecorator('Overall', array('tag' => 'div', 'class' => 'control-group gray-menu'));

        $this->sample_data->addDecorator('Overall', array('tag' => 'div', 'class' => 'control-group sample-data'));

        $this->version->removeDecorator('HtmlTag');
        $this->version->removeDecorator('overall');
        $this->version->removeDecorator('Label');

        $this->version->addDecorator('Label', array('escape' => false));
        $this->version->addDecorator('Overall', array('tag' => 'div', 'class' => 'control-group gray-menu'));
    }

}

