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
            /* // extend functionality
            'libraries'         => array(
                'tgif_compiler_library_yuicss'  => 'yui.css',
                'tgif_compiler_library_jquery'  => 'jquery',
            ),
            /* */
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
            /* // extend functionality
            'libraries'         => array(
                'tgif_compiler_library_yuijs'   => 'yui.js',
                'tgif_compiler_library_jquery'  => 'jquery',
            ),
            /* */
        ),
    ),
    // }}}
    // {{{ - yui
    'yui' => array(
        // http://developer.yahoo.com/yui/compressor/
        'compressor_jar'    => TGIF_RES_DIR.'/yuicompressor-2.4.2.jar',
        'js'                => array(
            'version'       => '2.8.2r1',
            //'version'     => '2.5.2',
            'use_cdn'       => 'yahoo', //If you make your own repository,
                                        // this is the base url
            'use_combine'   => true,    //yahoo must be cdn, or make own combo
            'use_rollup'    => false,   //prefer rollup javascripts (not supportd)
        ),
        'css'               => array(
            'version'       => '2.8.2r1',
            'use_cdn'       => 'yahoo',
            'use_combine'   => true, //can only work when yahoo is use_cdn
        ),
    ),
    // }}}
    // {{{ - jquery
    'jquery' => array(
        'version'           => '1.4.4',
        'ui_version'        => '1.8.6',
    ),
    // }}}
    
    'bin_java'              => '/usr/bin/java',
    'dir_static'            => '.',
    'url_static'            => '',
);
?>
