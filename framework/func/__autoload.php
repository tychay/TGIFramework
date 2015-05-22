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

// __autoload($class_name)
/**
 * See {@link http://php.net/autoload}
 *
 * I am using this to minimize unnecessary file inclusion and reduce memory
 * usage of the page (in PHP 5, we are using 20MB to display the homepage, 22MB
 * to display my profile page).
 *
 * This also supports a backward compatibility map, that allows you to load
 * includes that are not in the standard namespace naming convention of this
 * framework (pear standard, force lowercase). This map consists a hash mapping
 * the name of the class (lowercased) with the path to the file containing its
 * definition.
 *
 * To specify a classmaps hash, the recommended way is simply to create it as
 * a configuration called "classmaps". But if not defined, the system allows
 * you to use an arbitrary function to generate this classmaps table. For
 * backward compatibility with an original codebase (the original Tagged
 * framework), I have the default global generate load using the
 * {@link APP_CLASSMAP_PATH} define by default. This map table (hash) is
 * stored in var_export format as a free energy include.
 *
 * Until a hook is written, probably the best way to know what is loading your
 * classmap table is to use {@link http://php.net/manual/en/book.inclued.php
 * inclued}. in a live site.
 *
 * For obvious reasons, it's best to write this function anyway in order to bind
 * it as the unserialize_callback_func of the site. There is no need to
 * {@link require_once()} in the code as this is only called when a class
 * definition is missing.
 *
 * All classes (and files) must be lowercase and in a "namespace" (as per
 * pre-PHP 5.3 convention) for the autoloader to work without a map table.
 * The only exception is the PEAR style naming convention (case sensitive)
 * which isn't recommended because of case-insensitive file systems (Mac)
 * and casing issues in old versions of PHP. Furthermore, PEAR-style will
 * generate at least one fstat call too many (as it navigates your load path).
 *
 * There is a parameter "autoload_stubs" that you can set to true to have this
 * system throw an exception and generate a stub class on class load failure.
 * Normally it just triggers a user error.
 *
 * @author terry chay <tychay@php.net>
 * @param $class_name string The name of a class that is needed but not loaded
 * @uses TGIF_CLASS_DIR  for framework load path
 * @uses APP_CLASS_DIR for non-framework load path
 * @uses $_TAG->classmaps if normal loading fails for backward compatibility of
 *  framework.
 * @uses APP_CLASSMAP_PATH when $_TAG->classmaps is not set (should not happen).
 * @todo Make a switch to enable alternate "stub" class load
 * @todo Log what class forces a load of classmaps table (use inclued)
 */
function __autoload($class_name)
{
    global $_TAG; //runkit enabled superglobals
    static $map_table;
    // No need to require_once since this will only be called when the
    // class doesn't exist.
    $lower_class_name = strtolower($class_name);
    //if (isset($_TAG) && isset($_TAG->firephp)) {
    //    $_TAG->firephp->log($lower_class_name,'__autoload class');
    //}
    // Every class must be in a "namespace" (in pre-5.3 convention)
    $has_namespace = (strpos($class_name,'_')!==false);
    if ($has_namespace) {
        // new standard load {{{
        if (strcmp(substr($class_name,0,5),'tgif_')===0) {
            if (__autoload_xform($lower_class_name, TGIF_CLASS_DIR.DIRECTORY_SEPARATOR)) { return; }
        } else {
            if (__autoload_xform($lower_class_name, APP_CLASS_DIR.DIRECTORY_SEPARATOR)) { return; }
        }
        // }}}
        // PEAR style {{{
        // PEAR_Error is messed because it has a '_' which triggers the system
        // to try to find the file. Except it's actually in PEAR.php!
        if ($lower_class_name == 'pear_error') {
            require_once 'PEAR.php';
            return;
        }
        if (__autoload_xform($class_name)) { return; }
        // }}}
    }
    // Backward compatibility map table load {{{
    // check config variable (recommend way of storing backward compatibility
    // map:
    if (empty($map_table)) {
        $map_table = $_TAG->config('classmaps');
    }
    // This uses the config system to call __autoload_maptable below
    if ($map_table === false) {
        //sprintf('__autoload(): %s forced load of classmaps',$class_name);
       $map_table = $_TAG->classmaps;
        // if we have an error then it will get ugly
    }
    if (array_key_exists($lower_class_name,$map_table)) {
        require($map_table[$lower_class_name]);
        return;
    }
    // }}}
    if ($_TAG->config('autoload_stubs')) {
        eval(sprintf('class %s {}',$class_name));
        throw new Exception(sprintf('Cannot find class: %s',$class_name));
        return;
    }
    trigger_error(sprintf('Cannot find class: %s',$class_name));
}

// __autoload_maptable()
/**
 * Callback handler for the "constructor" of the maptable autoloader.
 *
 * This may be called during the construction of $_TAG->classmaps so I have to
 * put it in this file just in case
 *
 * The file_exists() call is okay here because this will probably be pulled from
 * smem cache on a good day.
 *
 * @return array The old classes mapping table.
 * @uses APP_CLASSMAP_PATH which is the full path to the var_export return of
 * the class map table (hash).
 */
function __autoload_maptable()
{
    if (!defined('APP_CLASSMAP_PATH')) { return array(); }
    $filename = APP_CLASSMAP_PATH;
    if (!file_exists($filename)) { return array(); }
    return include($filename);
}

// __autoload_xform($class_name[,$base_dird])
/**
 * Does a simple load and return.
 *
 * We have strict class names -> mapping rules. This implements it. Note that
 * if you try to require something that doesn't exist, you’ll get a fatal error
 * so there will be issues in the framework code as the file_exists() has been
 * eliminated in order to get the apc.stat=0 speedup.
 *
 * @param $class_name string the class directory
 * @param $base_dird string the base directory (with an ending dir separator)
 *      of the class path
 * @return if file found
 * @todo if something has already been compiled in apc, will fstat be bypassed?
 *  if not, then change this code to allow an "override" ability.
 */
function __autoload_xform($class_name ,$base_dird='')
{
    $filename = $base_dird.str_replace('_',DIRECTORY_SEPARATOR,$class_name).'.php';
    require($filename); //fatal error so not... @require($filename);
    return (class_exists($class_name,false));
    /* // fstat calls are slow
    if (file_exists($filename)) {
        require($filename);
        return true;
    }
    return false;
     */
}
