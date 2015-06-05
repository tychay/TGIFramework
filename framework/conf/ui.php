<?php
/**
 * Configuration for ui related stuff
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2009-2015 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    // $_TAG->css : css compiler
    'gld_css' => array(
        'construct'         => array('tgif_compiler_css'),
        'isSmemable'        => true, //recommended cache configuration
        //'isMemcacheable'    => false,
        'ids'               => array(
            'resource_dir'      => '{{{dir_static}}}/res/css',
            'target_dir'        => '{{{dir_static}}}/dyn/css',
            'resource_url'      => '{{{url_static}}}/res/css',
            'target_url'        => '{{{url_static}}}/dyn/css',
            'compressor'        => 'yuicompress',
            /* // extend functionality
            'libraries'         => array(
                'tgif_compiler_library_ext'     => 'css_ext',
                'tgif_compiler_library_yuicss'  => 'yui.css',
            ),
            /* */
        ),
    ),

    // $_TAG->js : js compiler
    'gld_js' => array(
        'construct'         => array('tgif_compiler_js'),
        'isSmemable'        => true, //recommended cache configuration
        //'isMemcacheable'    => false,
        'ids'               => array(
            'resource_dir'      => '{{{dir_static}}}/res/js',
            'target_dir'        => '{{{dir_static}}}/dyn/js',
            'resource_url'      => '{{{url_static}}}/res/js',
            'target_url'        => '{{{url_static}}}/dyn/js',
            'compressor'        => 'yuicompress',
            // extend functionality
            'libraries'         => array(
                'tgif_compiler_library_ext'     => 'js_ext',
                //'tgif_compiler_library_yuijs'   => 'yui.js',
                //'tgif_compiler_library_jquery'  => 'jquery',
            ),
        ),
    ),

    // - yui
    'yui' => array(
        //'compressor_jar'    => TGIF_RES_DIR.'/yuicompressor-2.4.2.jar',
        //defaults
        'js'                => array(
            'version'       => '2.9.0',
            'use_cdn'       => 'yahoo', //If you make your own repository,
                                        // this is the base url
            'use_combine'   => true,    //yahoo must be cdn, or make own combo
            'use_rollup'    => false,   //prefer rollup javascripts (not supportd)
        ),
        'css'               => array(
            'version'       => '2.9.0',
            'use_cdn'       => 'yahoo',
            'use_combine'   => true,    //can only work when yahoo is use_cdn
        ),
        /* */
    ),

    'jquery' => array( /* // defaults
        'version'           => '1.4.4',
        'ui_version'        => '1.8.6',
    /* */
    ),

    'css_ext' => array(
    ),

    'js_ext' => array(
        'base_path'         => '{{{dir_static}}}/ext/',
        'base_url'          => '{{{url_static}}}/ext',
        'use_cdn'           => true,
        'use_compiler'      => true,
        'compile_expansion' => '.min',
        //'url_callback'  => false,
        'modules'   => array(
            'ext/jquery.js'    => array(
                'name'          => 'jquery-1.11.3.js',
                'version'       => '1.11.3',
                'url_map'       => 'http%1$s://code.jquery.com/jquery-%3$s%2$s.js',
                'dependencies'  => array(
                ),
                'provides'      => array(
                    'jquery',
                    'jquery-1.11.3',
                    'jquery-1',
                    'ext/jquery.js',
                    'ext/jquery-1.11.3',
                    'ext/jquery-1',
                ),
            ),
        ),
    ),

    'compressors' => array(
        // http://developer.yahoo.com/yui/compressor/ (no longer maintained)
        //'yui_compressor' => '/usr/bin/java -jar '.TGIF_RES_DIR.'/yuicompressor-2.4.2.jar',
        'yui_compressor' => '/usr/bin/yui-compressor',
    ),
    
    //'bin_java'              => '/usr/bin/java',
    'dir_static'            => '.',
    'url_static'            => '',
);
