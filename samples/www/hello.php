<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link __autoload()} auto class loading system
 *
 * @package tgiframework
 * @subpackage samples
 * @copyright 2009 terry chay <tychay@php.net>
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
$page = new tgif_page();
$page->assign('title','Test');
$page->assign('greeting','Hello world!');
$page->render('hello.php');
?>
