<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Test configuration
 *
 * @package tgiframework
 * @subpackage samples
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    'readConfig'        => true, // don't keep loading config
    //test variables {{{
    'gld_testGlobal'    => array(
        'construct'         => array('sample_test'),
        'version'           => 1,
        'shouldShard'       => false,
        'isSmemable'        => true,
        'isMemcacheable'    => false,
    ),
    'testConf'          => 'testing',
    // }}}
    'firephp_enable;'   => true,
    // $classmaps {{{
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
);
?>
