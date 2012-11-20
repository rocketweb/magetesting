<?php

interface Application_Model_Task_Interface
{
    /**
     * Runs specified Task
     * this method is called by worker, so each class must have it
     */
    public function process();
}