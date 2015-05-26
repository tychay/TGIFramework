<?php
/**
 * Container for {@link autoload()} spl_autoload class loading replacement
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2014 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
/**
 * PSR-0/4-compliant autoloader
 * 
 * @uses   TGIF_CLASS_DIR to find where the base directory is
 * @param  string $class_name the FQN of the class to load
 * @return void
 */
function tgif_autoload($class_name) {
	if ( strcmp(substr($class_name,0,5),'tgif_')!==0 ) { return; }
    $filename = TGIF_CLASS_DIR . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
    include $filename;
}