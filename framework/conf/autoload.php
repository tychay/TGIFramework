<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Configuration for autoload related stuff.
 *
 * This sets the following config varables:
 * - autoload_stubs: don't throw exceptions or make stub classes on unknown class error
 *
 * This configures the following global variables:
 * - $_TAG->classmaps: autoloader for loading a free energy class map table from APP_CLASSMAP_PATH
 *
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    // {{{ autoload_stubs
    /**
     * Set to true if you want {@link __autoload()} to throw exceptions and
     * make a stub class on failure to launch.
     */
    'autoload_stubs'    => false,
    // }}}
    // $_TAG->classmaps {{{
    /**
     * A maptable that allows you to do backward compatible remappings of class
     * names to file names for {@link __autoload()} to work.
     * @global array
     * @name $_TAG->classmaps
     */
    'gld_classmaps'     => array(
        'construct'         => '__autoload_maptable',
        'version'           => 0,
        'isSmemable'        => true,
        'isMemcacheable'    => false, // I tried this with TRUE for testing. Works only if we deal with the commenting issues elsewhere
        'deferCache'        => false, //don't try to call a defer cache. it's ugly
    ),
    // }}}
);
?>
