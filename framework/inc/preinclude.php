<?php
/**
 * Framework to include on every page
 *
 * This does the following:
 * - start the timer (if not already)
 * - define a few constants to make referencing easier
 * - bind the class loader
 * - get the application symbol and create the application global ($_TAG)
 * - create the event queue ($_TAG->queue)
 * - create the diagnostics object ($_TAG->diagnostics)
 * 
 * @package tgiframework
 * @subpackage global
 * @copyright 2009-2015 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 * @todo rename and mov the spl register function
 */
// start timer
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

// define constants
// TGIF_DIR
if (!defined('TGIF_DIR')) {
    /**
     * The directory where the framework code is stored
     */
    define('TGIF_DIR',dirname(realpath(__DIR__)));
}
// TGIF_FUNC_DIRD
/**
 * The directory where functions used by the framework are stored
 */
define('TGIF_FUNC_DIRD', TGIF_DIR.DIRECTORY_SEPARATOR.'func'.DIRECTORY_SEPARATOR);
// TGIF_CLASS_DIR
/**
 * The directory where the "tgif_" namespace classes are stored
 */
define('TGIF_CLASS_DIR', TGIF_DIR.DIRECTORY_SEPARATOR.'class');
// TGIF_RES_DIR
/**
 * The directory where framework resources are stored
 */
define('TGIF_RES_DIR', TGIF_DIR.DIRECTORY_SEPARATOR.'res');
// TGIF_CONF_PATH
if (!defined('TGIF_CONF_PATH')) {
    /**
     * where to get configururation overrides
     */
    define('TGIF_CONF_PATH','');
}

// composer autoload (should be already done as it loads this)
//require_once dirname(TGIF_DIR).'/vendor/autoload.php';

// bind autoload
require_once TGIF_FUNC_DIRD.'autoload.php';
spl_autoload_register('tgif_autoload');

// global config and variables
global $symbol;
if (empty($symbol)) {
    if (!defined('SYMBOL_FILE')) { die('Define a SYMBOL_FILE to point to a php file that returns a three letter code!'); }
    $symbol = @include(SYMBOL_FILE);
    if (empty($symbol)) { die(sprintf('Set up %s!',$SYMBOL_FILE)); }
}
$GLOBALS['_TAG'] = tgif_global::get_instance($symbol);
$_TAG = $GLOBALS['_TAG'];

// Turn on queue and diagnostics
$_TAG->queue = new tgif_queue();
register_shutdown_function(array($_TAG->queue,'publish'),'shutdown');
// diagnostics
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

// Exceptions and error handling
?>
