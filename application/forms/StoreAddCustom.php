<?php

class Application_Form_StoreAddCustom extends Integration_Form{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setAttrib('class', 'form-stacked');
        $this->setAttrib('id', 'custom-store-form');
        //TODO: move model usage to controller

        
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
        $emptyVersion = array();
        $versions = array_merge($emptyVersion,$editionModel->getOptions());

        $this->edition->addMultiOptions($versions);

        $versionModel = new Application_Model_Version();
        $this->addElement('select', 'version', array(
                'label'      => 'Version',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_InArray($versionModel->getKeys(true))
                ),
                'class'      => 'span4'
        ));
        $versions = array();
        $authGroup = Zend_Auth::getInstance()->getIdentity()->group;
        foreach($versionModel->fetchAll() as $row) {
            if($row->getEdition() == 'CE' OR $authGroup == 'admin') {
                $versions[$row->getEdition().$row->getId()] = $row->getVersion();
            }
        }
        $this->version->addMultiOptions($versions);
        
        $this->addElement('select', 'custom_protocol', array(
                'label'      => 'Protocol',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        new Zend_Validate_InArray(array('ftp','ssh'))
                ),
                'class'      => 'span4'
        ));
        $this->custom_protocol->addMultiOptions(array(
                'ftp' => 'FTP',
                'ssh' => 'SSH',
        ));

        $this->addElement('text', 'custom_host', array(
                'label'       => 'Host',
                'required'    => true,
                'label_class' => 'radio inline',
                'placeholder' => 'ie. ftp.my-company.com',
                'class'      => 'span4'
        ));
        
        $this->addElement('text', 'custom_port', array(
                'label'       => 'Port',
                'required'    => false,
                'label_class' => 'radio inline',
                'placeholder' => 'leave blank for default',
                'class'      => 'span4'
        ));
//        $this->custom_host->setValue('ie. ftp.my-company.com');
        
        $this->addElement('text', 'custom_remote_path', array(
                'label'       => 'Remote Path to Magento Root',
                'required'    => true,
                'label_class' => 'radio inline',
                'placeholder' => 'ie. /website/html',
                'class'      => 'span7'
        ));
        
//        $this->custom_remote_path->setValue('ie. /website/html');
        
        $this->addElement('text', 'custom_sql', array(
                'label'       => 'Remote Path to SQL dump',
                'required'    => true,
                'label_class' => 'radio inline',
                'placeholder' => 'ie. /website/html/dump.sql',
                'class'      => 'span8'
        ));
//        $this->custom_sql->setValue('ie. /website/html/dump.sql');
        
        $this->addElement('text', 'custom_login', array(
                'label'       => 'Login',
                'required'    => true,
                'label_class' => 'radio inline',
                'class'      => 'span4'
        ));
        
        $this->addElement('password', 'custom_pass', array(
                'label'       => 'Password',
                'required'    => true,
                'label_class' => 'radio inline',
                'class'      => 'span4'
        ));
        
        // Add the submit button
        $this->addElement('submit', 'storeAdd', array(
                'ignore'   => true,
                'label'    => 'Install',
        ));
        
        $this->addElement('text', 'custom_file', array(
                'label'       => 'Remote Path to .zip or .tar.gz package containing all store files',
                'required'    => false,
                'label_class' => 'radio inline',
                'placeholder' => 'ie. /website/html/store_backup.tar.gz',
                'class'      => 'span7'
        ));
        
//        $this->custom_file->setValue('ie. /website/html/store_backup.tar.gz');

        $this->_setDecorators();

        $this->storeAdd->removeDecorator('HtmlTag');
        $this->storeAdd->removeDecorator('overall');
        $this->storeAdd->setAttrib('class','btn btn-primary');

        $this->custom_remote_path->removeDecorator('HtmlTag');
        $this->custom_remote_path->removeDecorator('overall');
        
        $this->custom_sql->removeDecorator('HtmlTag');
        $this->custom_sql->removeDecorator('overall');

        $this->custom_file->removeDecorator('HtmlTag');
        $this->custom_file->removeDecorator('overall');
    }

}

