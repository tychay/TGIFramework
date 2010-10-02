<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Initialize the configuration on every page in the TGIFramework samples. This
 * is called automatically on the page.
 *
 * You can use this script for other sites either as-is or copy and modify.
 * If you would like to use as-is, set the {@link APP_DIR} before calling this
 * script and you might want to drop in a symbol file (__symbol.php) in that
 * directory.
 *
 * If you want to copy this file, be sure to change {@link BASE_DIR},
 * {@link TGIF_DIR} etc.
 *
 * @package tgisamples
 * @copyright 2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
$_start_time = microtime();
// Set common symbols {{{
// BASE_DIR {{{
/**
 * The directory where the code tree is stored
 */
define('BASE_DIR', dirname(dirname(realpath(__FILE__))));
// }}}
// TGIF_DIR {{{
/**
 * The directory where the framework code is stored
 * @ignore
 */
define('TGIF_DIR', BASE_DIR.DIRECTORY_SEPARATOR.'framework');
// }}}
// APP_DIR {{{
if (!defined('APP_DIR')) {
    /**
     * The directory where app code is stored (this can be overridden before using this script)
     */
    define('APP_DIR', BASE_DIR.DIRECTORY_SEPARATOR.'samples');
}
// }}}
// APP_CLASS_DIR {{{
/**
 * The directory where app code is stored
 */
define('APP_CLASS_DIR', APP_DIR.DIRECTORY_SEPARATOR.'class');
// }}}
// APP_INC_DIR {{{
/**
 * The directory where free energy includes are stored
 */
define('APP_INC_DIR', APP_DIR.DIRECTORY_SEPARATOR.'inc');
// }}}
// APP_CLASSMAP_PATH {{{
/**
 * The file
 */
define('APP_CLASSMAP_PATH', APP_INC_DIR.DIRECTORY_SEPARATOR.'class_map_table.php');
// }}}
// $symbol {{{
$symbol_file = APP_DIR.DIRECTORY_SEPARATOR.'__symbol.php';
if (file_exists($symbol_file)) {
    /**
     * @global string
     * The symbol. This is a three letter code put in from of any configurations
     * that are cached in order to prevent two applications running on the same
     * machine and/or memcache from overwriting each others variables.
     */
    $symbol = include($symbol_file);
} else {
    $symbol = 'SAM'; //Samples
}
// }}}
// TGIF_CONF_PATH {{{
/*
 * Where to get config overrides
 */
define('TGIF_CONF_PATH', APP_DIR.DIRECTORY_SEPARATOR.'config');
// }}}
// }}}
// load common preinclude {{{
/**
 * Common symbols and objects
 */
include_once(TGIF_DIR.'/inc/preinclude.php');
// }}}
// Set character encoding
// Start session
// PEAR + Smarty
?>
