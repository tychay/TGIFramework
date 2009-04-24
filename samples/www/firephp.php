<?php
require_once('FirePHPCore/FirePHP.class.php');
ob_start();

echo 'This is a test of FirePHP';
$firephp = FIREPHP::getInstance(true);
$var = array('i'=>10, 'j'=>20);
$firephp->log($var, 'Iterators');
?>
