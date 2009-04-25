<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Test global config and variables
 *
 * @package tgiframework
 * @subpackage samples
 * @copyright 2009 terry chay <tychay@php.net>
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
?>
'testConf' config is <?php var_dump($_TAG->config('testConf')); ?>

'testGlobal' global is <?php var_dump($_TAG->testGlobal); ?>
