#!/usr/bin/php
<?php
/**
 * This code will benchmark your server to determine how high of a cost you can afford.
 * 
 * You want to set the highest cost that you can without slowing down
 * you server too much. 8-10 is a good baseline, and more is good if your servers
 * are fast enough. The code below aims for ≤ 50 milliseconds stretching time,
 * which is a good baseline for systems handling interactive logins.
 *
 * This was taken from http://php.net/manual/en/function.password-hash.php
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

$timeTarget = 0.05; // 50 milliseconds 

$cost = 8;
do {
    $cost++;
    $start = microtime(true);
    password_hash("test", PASSWORD_BCRYPT, ["cost" => $cost]);
    $end = microtime(true);
} while (($end - $start) < $timeTarget);

echo "Appropriate Cost Found: " . $cost . "\n";
?>