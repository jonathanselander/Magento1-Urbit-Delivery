<?php

if (version_compare(PHP_VERSION, '5.3', '<')) {
    echo 'Magento Unit Tests can run only on PHP version higher then 5.3';
    exit(1);
}

$_baseDir = getcwd();

// Include Mage file by detecting app root
require_once $_baseDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';

/* Replace server variables for proper file naming */
$_SERVER['SCRIPT_NAME'] = $_baseDir . DS . 'index.php';
$_SERVER['SCRIPT_FILENAME'] = $_baseDir . DS . 'index.php';
$_SERVER['RUNNING_TESTS'] = true;

Mage::app('admin');
Mage::getConfig()->init();

register_shutdown_function(function () {
    Mage::app()->cleanCache();
});