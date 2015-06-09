#!/usr/bin/php
<?php
/**
 * This code will generate a pretty random string that you can use as a pepper for password encryption.
 *
 * Pretty much the same way salt is created in {@see password_hash()}.
 *
 * @package tgiframework
 * @subpackage bin
 * @copyright 2015 terry chay <tychay@php.net>
 */
if (!isset($symbol) && !defined('SYMBOL_FILE')) {
    // bootstrap framework based include system
    $symbol = 'bin';
    include(dirname(dirname(__DIR__)).'/vendor/autoload.php');
}

$iv = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
$pepper = tgif_encode::hex_to_base64(bin2hex($iv));

echo $pepper . "\n";
?>