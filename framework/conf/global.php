<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Configuration for global related stuff
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    // $_TAG->classmaps {{{
    'gld_classmaps'     => array(
        'construct'         => '__autoload_maptable',
        'version'           => 1,
        'shouldShard'       => true,  //different installs should be seperate'
        'isSmemable'        => true,
        'isMemcacheable'    => false, // I tried this with TRUE for testing. Works only if we deal with the commenting issues elsewhere
        'memcacheChannel'   => '___',
        'memcacheKey'       => false,
        'deferCache'        => false, //don't try to call a defer cache. it's ugly
    ),
    // }}}
    'autoload_stubs'    => false, //set to true if you want __autoload to throw exceptions and make a stub class on failure to launch
);
?>
