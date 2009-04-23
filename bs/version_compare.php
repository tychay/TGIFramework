#!/usr/bin/env php
<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Returns 1 if php is newer than $1 is installed
 * @package tgiframework
 * @subpackage bootstrap
 * @copyright 2000 terry chay <tychay@php.net>
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
$test_version = explode('.',$_SERVER['argv'][1]);
$php_version = explode('.',phpversion());
foreach ($test_version as $idx=>$version_part) {
    if (!isset($php_version[$idx])) { return; }
    if ((int) $version_part < (int) $php_version[$idx]) { echo '1'; return; }
    if ((int) $version_part > (int) $php_version[$idx]) { return; }
}
echo '1'; return;
?>
