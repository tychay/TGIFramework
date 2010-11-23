<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Configuration for debugging related stuff
 *
 * @package tgiframework
 * @subpackage debugging
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
// {{{ readConfig: set to true to cache configuration (instead of reparse)
'readConfig'    => false,
// }}}
// {{{ $_TAG->firephp
'gld_firephp' => array(
    'params'            => 0,
    'construct'         => array('tgif_debug_firephp','_X_create_object'),
    'version'           => 0,
    'shouldShard'       => false,
    'isSmemable'        => false,
    'isMemcacheable'    => false,
),
'firephp'   => array(
    'enable'        => false, //set to true to turn on firephp debugging
    'diagnostics'   => false, //set to true to log diagnostics to firephp
),
// }}}
);
?>
