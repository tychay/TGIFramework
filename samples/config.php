<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Initialize the configuration on every page in the TGIFramework samples. This is
 * called automatically on the page.
 *
 * @package tgiframework
 * @subpackage samples
 * @copyright 2009 terry chay <tychay@php.net>
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
$_start_time = microtime();
// Set file path {{{
// BASE_DIR {{{
/**
 * The directory where the code tree is stored
 */
define('BASE_DIR', dirname(dirname(realpath(__FILE__))));
// }}}
// TGIF_DIR {{{
/**
 * The directory where the framework code is stored
 */
define('TGIF_DIR', BASE_DIR.DIRECTORY_SEPARATOR.'framework');
// }}}
// TGIF_CLASS_DIR {{{
/**
 * The directory where the framework class is stored
 */
define('TGIF_CLASS_DIR', TGIF_DIR.DIRECTORY_SEPARATOR.'class');
// }}}
// APP_DIR {{{
/**
 * The directory where app code is stored
 */
define('APP_DIR', BASE_DIR.DIRECTORY_SEPARATOR.'samples');
// }}}
// }}}
// Set character encoding
// Bind autoload {{{
require_once TGIF_DIR.'/func/__autoload.php';
//ini_set('unserialize_callback_func','__autoload'); //already set in ini
// }}}
// Global config
// Turn on queue
// Start session
// PEAR + Smarty
// Exceptions and error handling
?>
