<?php
/**
 * Form for contact 
 * 
 * @category   Application
 * @package    Form
 * @copyright  Copyright (c) 2012 RocketWeb USA Inc. (http://www.rocketweb.com)
 * @author     Marcin Kazimierczak <marcin@rocketweb.com>
 */
class Application_Form_Contact extends Integration_Form
{

    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');

        // Add a name element
        $this->addElement('text', 'sender_name', array(
                'label'      => 'Your name',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 150)),
                ),
                'class'      => 'span4'
        ));

        // Add a email element
        $this->addElement('text', 'sender_email', array(
                'label'      => 'Your email address',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(2, 50)),
                        new Zend_Validate_EmailAddress()
                ),
                'class'      => 'span4'
        ));
        
        $this->addElement('text', 'subject', array(
                'label'      => 'Subject',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 150)),
                ),
                'class'      => 'span8'
        ));
        
        $this->addElement('textarea', 'message', array(
                'label'      => 'Message',
                'required'   => true,
                'filters'    => array('StripTags', 'StringTrim'),
                'validators' => array(
                        array('validator' => 'StringLength', 'options' => array(3, 1050))
                ),
                'class'      => 'span8',
                'rows'       => 8,
        ));

        $this->_setDecorators();

        $this->sender_name->removeDecorator('HtmlTag');
        $this->sender_name->removeDecorator('Overall');
        $this->sender_name->removeDecorator('Label');
        
        $this->sender_email->removeDecorator('HtmlTag');
        $this->sender_email->removeDecorator('Overall');
        $this->sender_email->removeDecorator('Label');
        
        $this->subject->removeDecorator('HtmlTag');
        $this->subject->removeDecorator('Overall');
        $this->subject->removeDecorator('Label');
        
        $this->message->removeDecorator('HtmlTag');
        $this->message->removeDecorator('Overall');
        $this->message->removeDecorator('Label');

    }


}

