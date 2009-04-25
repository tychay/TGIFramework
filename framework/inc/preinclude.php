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
     * @global $_start_time string
     */
    $GLOBALS['_start_time'] = microtime();
}
// }}}
// define constants {{{
// {{{ TGIF_DIR
if (!defined('TGIF_DIR')) {
    /**
     * The directory where the framework code is stored
     * @const string
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
// }}}
// bind autoload {{{
/**
 * Lookup function for objects
 */
$callback_func = ini_get('unserialize_callback_func');
if (strcmp($callback_func,'__autoload')===0) {
    // set in php.ini, but need to load
    require_once TGIF_FUNC_DIRD.'__autoload.php';
} elseif (!$callback_func) {
    // load default callback so that framework works
    require_once TGIF_FUNC_DIRD.'__autoload.php';
    ini_set('unserialize_callback_func','__autoload');
} // else you must have decided to write your own autoloader
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
/*
// diagnostics {{{
if (defined('DISABLE_DIAGNOSTICS') && DISABLE_DIAGNOSTICS) {
    // skip logging when running mail_queus, etc.
    $_TAG->diagnostics = new tgif_diagnostics_null();
} else {
    // think of moving this to the tag_queue system
    register_shutdown_function(array($_TAG->diagnostics,'shutdown'));
    $_TAG->diagnostics->setPageTimer($_start_time);
    unset($_start_time);
}
// }}}
*/
// }}}
// Exceptions and error handlign
?>
