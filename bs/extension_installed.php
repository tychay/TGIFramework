#!/usr/bin/env php
<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Returns 1 if a php extension specified as $1 in installed.
 *
 * @package tgiframework
 * @subpackage bootstrap
 * @copyright 2000 terry chay <tychay@php.net>
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
echo extension_loaded($_SERVER['argv'][1]);
?>
