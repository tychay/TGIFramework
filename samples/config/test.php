<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Test configuration used for features
 *
 * @package tgisamples
 * @subpackage testing
 * @copyright 2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    'readConfig'        => false, // don't keep loading config
    //test variables {{{
    'gld_testGlobal'    => array(
        'construct'         => array('sample_test'),
        'version'           => 2,
        'isSmemable'        => false,
        'isMemcacheable'    => true,
    ),
    'gld_testCollection'=> array(
        'params'            => 1, //index
        'construct'         => array('sample_member'),
        'version'           => 1,
        'isSmemable'        => false,
        'isMemcacheable'    => true,
    ),
    'testConf'          => 'testing',
    'testConfMacros'    => array(
        'testConf'      => 'nested {{{testConf}}}',
        'subproperty'   => 'nested {{{gld_testGlobal.version}}}',
    ),
    // }}}
    'firephp.enable'    => true,
);
?>
