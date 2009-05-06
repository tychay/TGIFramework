#!/usr/bin/env php
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
$latest_version = max(explode(':',exec('svnversion -n'))); //sometimes you get back a range...
$data = sprintf('<?php return array(\'global_version\'=>%d); ?>',$latest_version);
/**
 * Bootstrap tgif_file for writing
 */
include_once(dirname(dirname(__FILE__)).'/class/tgif/file.php');
tgif_file::put_contents($_SERVER['argv'][1], $data);
?>
