<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Framework to include on every page
 *
 * This does the following:
 * - define a few constants to make referencing easier
 * 
 * @package tgiframework
 * @subpackage global
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// start timer {{{
if (!isset($GLOBALS['_start_time'])) {
    /**
     * As near as we can get to the start time of the script.
     *
     * If we don't start it manually, it will start when {@link preinclude.php}
     * loads.
     *
     * @global string $GLOBALS['_start_time']
     * @name $_start_time
     */
    $GLOBALS['_start_time'] = microtime();
}
// }}}
// define constants {{{
// {{{ TGIF_DIR
if (!defined('TGIF_DIR')) {
    /**
     * The directory where the framework code is stored
     */
    define('TGIF_DIR',dirname(dirname(realpath(__FILE__))));
}
// }}}
// TGIF_FUNC_DIRD {{{
/**
 * The directory where functions used by the framework are stored
 */
define('TGIF_FUNC_DIRD', TGIF_DIR.DIRECTORY_SEPARATOR.'func'.DIRECTORY_SEPARATOR);
// }}}
// TGIF_CLASS_DIR {{{
/**
 * The directory where the "tgif_" namespace classes are stored
 */
define('TGIF_CLASS_DIR', TGIF_DIR.DIRECTORY_SEPARATOR.'class');
// }}}
// TGIF_BIN_DIR {{{
/**
 * The directory where framework binaries are stored.
 */
define('TGIF_CLASS_DIR', TGIF_DIR.DIRECTORY_SEPARATOR.'bin');
// }}}
// }}}
// bind autoload {{{
/**
 * Lookup function for objects
 */
if (!function_exists('__autoload')) {
    // Load the default callback so that the framework works
    require_once TGIF_FUNC_DIRD.'__autoload.php';
} // else you must have written and defined your own autoloader
if (strcmp(ini_get('unserialize_callback_func'),'__autoload')!==0) {
    // Didn't set the serialze to autoload in php.ini
    ini_set('unserialize_callback_func','__autoload');
}
// }}}
// global config and variables {{{
if (empty($symbol)) {
    if (!defined('SYMBOL_FILE')) { die('Define a SYMBOL_FILE to point to a php file that returns a three letter code!'); }
    $symbol = @include(SYMBOL_FILE);
    if (empty($symbol)) { die(sprintf('Set up %s!',$SYMBOL_FILE)); }
}
// This should be superglobaled already by runkit (let's hope so!)
//$GLOBALS['_TAG'] = tgif_global::get_instance($symbol);
//$_TAG = $GLOBALS['_TAG'];
$_TAG = tgif_global::get_instance($symbol);
// }}}
// Turn on queue and diagnostics {{{
$_TAG->queue = new tgif_queue();
register_shutdown_function(array($_TAG->queue,'publish'),'shutdown');
// diagnostics {{{
if (defined('DISABLE_DIAGNOSTICS') && DISABLE_DIAGNOSTICS) {
    // tgif_diagnostics starts up it's own buffer
    ob_start();
    // skip logging when running mail_queus, etc. (remember you have to include
    // this prepend script manually so you have ample time to set the diagnostic
    // variable off
    $_TAG->diagnostics = new tgif_diagnostics_null();
} else {
    $_TAG->diagnostics = new tgif_diagnostics();
    // contemplate moving this line to the tag_queue system
    register_shutdown_function(array($_TAG->diagnostics,'shutdown'));
    $_TAG->diagnostics->setPageTimer($GLOBALS['_start_time']);
    unset($GLOBALS['_start_time']);
}
// }}}
// }}}
// Exceptions and error handling
?>
