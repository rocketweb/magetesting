<?php

class Application_Form_StoreAddClean extends Integration_Form{

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
                ),
                'class'      => 'span4'
        ));

        $this->addElement('radio', 'sample_data', array(
                'label'       => 'Install sample data',
                'required'    => true,
                'label_class' => 'radio inline'
        ));
        $this->sample_data->addMultiOptions(array(
                1 => 'Yes',
                0 => 'No',
        ));
        
        $this->sample_data->setValue(0)
        ->setSeparator(' ');

        $this->addElement('text', 'store_name', array(
                'label'      => 'Name',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'class'      => 'span4'
        ));

        $this->addElement('text', 'description', array(
                'label'      => 'Description',
                'required'   => false,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                    array('validator' => 'StringLength', 'options' => array('max' => 300))
                ),
                'class'      => 'span4'
        ));
        $this->edition->addMultiOptions($editionModel->getOptions());

        $versionModel = new Application_Model_Version();
        $this->addElement('select', 'version', array(
                'label'      => 'Version',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_InArray($versionModel->getKeys())
                ),
                'class'      => 'span4'
        ));


         
        // Add the submit button
        $this->addElement('submit', 'storeAdd', array(
                'ignore'   => true,
                'label'    => 'Install',
        ));

        $this->_setDecorators();

        $this->storeAdd->removeDecorator('HtmlTag');
        $this->storeAdd->removeDecorator('overall');
        $this->storeAdd->setAttrib('class','btn btn-primary');
        $this->storeAdd->removeDecorator('Label');

        $this->edition->removeDecorator('HtmlTag');
        $this->edition->removeDecorator('overall');

        $this->version->removeDecorator('HtmlTag');
        $this->version->removeDecorator('overall');
    }

}

