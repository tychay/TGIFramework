<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Test configuration
 *
 * @package tgisamples
 * @subpackage testing
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    'readConfig'        => false, // don't keep loading config
    //test variables {{{
    'gld_testGlobal'    => array(
        'construct'         => array('sample_test'),
        'version'           => 2,
        'shouldShard'       => false,
        'isSmemable'        => false,
        'isMemcacheable'    => true,
    ),
    'testConf'          => 'testing',
    'testConfMacros'    => array(
        'testConf'      => 'nested {{{testConf}}}',
        'subproperty'   => 'nested {{{gld_testGlobal.version}}}',
    ),
    // }}}
    'firephp_enable'   => true, //deprecated
    'firephp.enable'    => true,
);
?>
