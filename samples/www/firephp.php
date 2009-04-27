<?php
/**
 * Test firephp logging
 *
 * @package tgiframework
 * @subpackage samples
 */
// No longer needed (see prepend)
//require_once('FirePHPCore/FirePHP.class.php');
//ob_start();

//echo 'This is a test of FirePHP';
//$firephp = FIREPHP::getInstance(true);
$var = array('i'=>10, 'j'=>20);
//$firephp->log($var, 'Iterators');
$_TAG->firephp->log($var, 'Iterators');
?>
