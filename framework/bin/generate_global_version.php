#!/usr/bin/env php -drunkit.superglobal="_TAG"
<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Generate a config file that generates a {@link global_version} based on
 * the subversion version and it into the file specified by $1.
 *
 * This is done using the
 * {@link http://svnbook.red-bean.com/en/1.1/re57.html svnversion} command.
 *
 * @package tgiframework
 * @subpackage bootstrap
 * @copyright 2009 terry chay <tychay@php.net
 */
if (!isset($symbol) && !defined('SYMBOL_FILE')) {
    // bootstrap framework based include system
    $symbol = 'bin';
    include(dirname(dirname(__FILE__)).'/inc/preinclude.php');
}


//sometimes you get back a range, so just take the largest value
$latest_version = max(explode(':',exec('svnversion -n')));

$data = sprintf('<?php /** @package tgiframework */ return array(\'global_version\'=>%d); ?>',$latest_version);
/**
 * Bootstrap tgif_file for writing
 */
tgif_file::put_contents($_SERVER['argv'][1], $data);
?>
