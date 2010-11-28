<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Configuration for ui related stuff
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    // {{{ $_TAG->css : css compiler
    'gld_css' => array(
        'construct'         => array('tgif_compiler_css'),
        'isSmemable'        => true, //recommended cache configuration
        //'isMemcacheable'    => false,
        'ids'               => array(
            'resource_dir'      => '{{{dir_static}}}/res/css',
            'target_dir'        => '{{{dir_static}}}/dyn/css',
            'resource_url'      => '{{{url_static}}}/res/css',
            'target_url'        => '{{{url_static}}}/dyn/css',
            // add other options
            //'libraries'         => array('tgif_compiler_library_yuicss'), // add css libraries
        ),
    ),
    // }}}
    // {{{ $_TAG->js : js compiler
    'gld_js' => array(
        'construct'         => array('tgif_compiler_js'),
        'isSmemable'        => true, //recommended cache configuration
        //'isMemcacheable'    => false,
        'ids'               => array(
            'resource_dir'      => '{{{dir_static}}}/res/js',
            'target_dir'        => '{{{dir_static}}}/dyn/js',
            'resource_url'      => '{{{url_static}}}/res/js',
            'target_url'        => '{{{url_static}}}/dyn/js',
            //'libraries'         => array('tgif_compiler_library_yuijs','tgif_compiler_library_jquery'),
        ),
    ),
    // }}}
    // {{{ - yui
    'yui' => array(
        'version'           => '2.4.0',
        // http://developer.yahoo.com/yui/compressor/
        'compressor_jar'    => TGIF_BIN_DIR.'/yuicompressor-2.4.2.jar',
        'use_service'       => true,
        'cdn'               => 'yahoo',
        'use_combine'       => true, //can only work with yahoo as cdn
    ),
    // }}}
    // {{{ - yuiCss
    'yuiCss' => array(
        'version'           => '2.8.2r1',
        'cdn'               => 'yahoo',
        'use_combine'       => false,
    ),
    // }}}
    // {{{ - jquery
    'jquery' => array(
        'version'           => '1.4.4',
        'ui_version'        => '1.8.6',
        'cdn'               => 'google',
    ),
    // }}}
    
    'bin_java'              => '/usr/bin/java',
    'dir_static'            => '.',
    'url_static'            => '',
);
?>
