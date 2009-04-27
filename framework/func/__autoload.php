<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link __autoload()} auto class loading system
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007 Tagged, Inc. 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// {{{  __autoload($class_name)
// comments {{{
/**
 * See {@link http://www.php.net/autoload}
 *
 * I am using this to minimize unnecessary file inclusion and reduce memory
 * usage of the page (in PHP 5, we are using 20MB to display the homepage, 22MB
 * to display my profile page). In order to ensure backward compatibility with
 * an original codebase (the original Tagged framework), I allow a map table as
 * possible check. This map table is stored in var_export format as a free
 * energy include.
 *
 * For obvious reasons, it's best to write this function anyway in order to bind
 * it as the unserialize_callback_func of the site
 *
 * There is no need to {@link require_once()} as this is only called when
 * the class definition is missing.
 *
 * Until a hook is written, probably the best way to get at the classmap table
 * in a live site is to use
 * {@link http://php.net/manual/en/book.inclued.php inclued}.
 *
 * All classes (and files) must be lowercase and in a "namespace" (as per
 * pre-PHP 5.3 convention) for the autoloader to work without a map table.
 *
 * @author terry chay <tychay@php.net>
 * @param $class_name string The name of a class that is needed but not loaded
 * @uses TGIF_CLASS_DIR  for framework load path
 * @uses APP_CLASS_DIR for non-framework load path
 * @uses APP_INC_DIR for backward compatibility load path
 *      framework)
 * @uses $_TAG->classmaps if normal loading fails
 * @todo log what forces a load of classmaps (use inclued
 */
// }}}
function __autoload($class_name)
{
    //global $_TAG; //runkit enabeld superglobals
    static $map_table;
    //printf('Autoloading %s...',$class_name);
    // No need to require_once since this will only be called when the
    // class doesn't exist.
    $lower_class_name = strtolower($class_name);
    // Every class must be in a "namespace" (in pre-5.3 convention)
    if (strpos($class_name,'_') === false) {
        // backward compatibily map table load {{{
        if (empty($map_table)) {
            //sprintf('__autoload(): %s forced load of classmaps',$class_name);
            //$map_table = include(APP_INC_DIR.DIRECTORY_SEPARATOR.'class_map_table.php');
            if (!$map_table = @$_TAG->classmaps) {
                // this should never be called.
                $map_table = (defined('APP_INC_DIR'))
                           ? include(APP_INC_DIR.DIRECTORY_SEPARATOR.'class_map_table.php')
                           : array();
            }
        }
        if (array_key_exists($lower_class_name,$map_table)) {
            require($map_table[$lower_class_name]);
            return;
        }
        // }}}
    } else {
        // new standard load {{{
        if (strcmp(substr($class_name,0,5),'tgif_')===0) {
            if (__autoload_xform($lower_class_name, TGIF_CLASS_DIR.DIRECTORY_SEPARATOR)) { return; }
        } else {
            if (__autoload_xform($lower_class_name, APP_CLASS_DIR.DIRECTORY_SEPARATOR)) { return; }
        }
        // }}}
    }
    // PEAR style {{{
    if (__autoload_xform($class_name)) { return; }
    // PEAR_Error is messed because it has a '_' which triggers the system to
    // try to find the file. Except it's actually in PEAR.php!
    if ($lower_class_name == 'pear_error') { return; }
    // }}}
    trigger_error(sprintf('Cannot find class: %s',$class_name));
    /* //alternate way of loading a stub class
    eval(sprintf('class %s {}',$class_name));
    throw new Exception(sprintf('Cannot find class: %s',$class_name));
    /* */
}
// }}}
// {{{ __autoload_maptable()
/**
 * Callback handler for the "constructor" of the maptable autoloader.
 *
 * This may be called during the construction of $_TAG->classmaps so I have to
 * put it in this file just in case
 *
 * @return array The old classes mapping table.
 * @uses APP_INC_DIR where a local class map table needs to be located
 */
function __autoload_maptable()
{
    if (!defined('APP_INC_DIR')) { return array(); }
    $filename = APP_INC_DIR.DIRECTORY_SEPARATOR.'class_map_table.php';
    if (!file_exists($filename)) { return array(); }
    return include($filename);
}
// }}}
// {{{ __autoload_xform($class_name[,$base_dird])
/**
 * Does a simple load and return.
 *
 * We have strict class names -> mapping rules. This implements it.
 * @param $class_name string the class directory
 * @param $base_dird string the base directory (with an ending dir separator)
 *      of the class path
 * @return if file found
 */
function __autoload_xform($class_name ,$base_dird='')
{
    $filename = $base_dird.str_replace('_',DIRECTORY_SEPARATOR,$class_name).'.php';
    if (file_exists($filename)) {
        require($filename);
        return true;
    }
    return false;
}
// }}}
?>
