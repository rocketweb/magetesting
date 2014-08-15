<?php

include 'init.console.php';

try {
    $userModel = new Application_Model_User();
    $result = $userModel->fetchReadyActiveFromUsers();
    if($result) {
        foreach($result as $user) {
            $mail = new Integration_Mail_UserRegisterActivation();
            $mail->setup($config, array('user' => $user), $view);
            $mail->send();
            $user->setActiveFromReminded(1)->save();
        }
    }
    $log->log('Sending reminders for free trials', Zend_Log::INFO, 'Ok');
} catch(Exception $e) {
    $log->log('Sending reminders for free trials', Zend_Log::ERR, $e->getMessage());
}

Zend_Auth::getInstance()->getStorage()->clear();