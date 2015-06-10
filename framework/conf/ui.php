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
            // extend functionality
            'libraries'         => array(
                'tgif_compiler_library_ext'     => 'css_ext',
                //'tgif_compiler_library_yuicss'  => 'yui.css',
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
        'base_path'      => '{{{dir_static}}}/ext/',
        'base_url'       => '{{{url_static}}}/ext',
        'use_cdn'        => true,
        'use_compressor' => true,
        'compress_ext'   => '.min',
        'modules'        => array(
            'bootstrap' => array(
                'name'         => 'bootstrap/3.3.4/bootstrap.css',
                'version'      => '3.3.4',
                'url_map'      => 'http%1$s://maxcdn.bootstrapcdn.com/bootstrap/%3$s/css/bootstrap%2$s.css',
                'dependencies' => array(
                ),
                'provides'     => array(
                    'bootstrap',
                    'bootstrap3',
                    'bootstrap-3.3.4',
                    'ext/bootstrap.css',
                    'ext/bootstrap-3.3.4.css',
                ),
            ),
            'font-awesome' => array(
                'name'         => 'font-awesome/4.3.0/font-awesome.css',
                'version'      => '4.3.0',
                'url_map'      => 'http%1$s://maxcdn.bootstrapcdn.com/font-awesome/%3$s/css/font-awesome%2$s.css',
                'dependencies' => array(
                ),
                'provides'     => array(
                    'fontawesome',
                    'font-awesome',
                    'font-awesome.css',
                    'font-awesome-4.3.0',
                    'ext/font-awesome.css',
                    'ext/font-awesome-4.3.0.css',
                ),
            ),
            'bootstrap-material-design' => array(
                'name'         => 'bootstrap-material-design/0.3.0/material.css',
                'version'      => '0.3.0',
                'url_map'      => 'http%1$s://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/%3$s/css/material%2$s.css',
                'use_cdn'      => true, // because of relative path references to icon font
                'dependencies' => array(
                    'bootstrap',
                ),
                'provides'     => array(
                    'bootstrap-material-design',
                    'bootstrap-material-design.css',
                    'bootstrap-material-design-0.3.0',
                    'ext/material.css',
                    'ext/material-0.3.0.css',
                ),
            ),
            'bootstrap-material-design-ripples' => array(
                'name'         => 'bootstrap-material-design/0.3.0/ripples.css',
                'version'      => '0.3.0',
                'url_map'      => 'http%1$s://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/%3$s/css/ripples%2$s.css',
                'dependencies' => array(
                    'bootstrap-material-design',
                ),
                'provides'     => array(
                    'bootstrap-material-design-ripples',
                    'bootstrap-material-design-ripples.css',
                    'bootstrap-material-design-ripples-0.3.0',
                    'ext/material-ripples.css',
                    'ext/material-ripples.0.3.0.css',
                ),
            ),
            'angular-motion' => array(
                'name'         => 'angular-motion/0.4.2/angular-motion.css',
                'version'      => '0.4.2',
                'url_map'      => 'http%1$s://cdnjs.cloudflare.com/ajax/libs/angular-motion/%3$s/angular-motion%2$s.css',
                'dependencies' => array(
                ),
                'provides'     => array(
                    'angularmotion',
                    'angular-motion',
                    'angular-motion.css',
                    'angular-motion-0.4.2',
                    'ext/angular-motion.css',
                    'ext/angular-motion-0.4.2.css',
                ),
            ),
            'bootstrap-additions' => array(
                'name'         => 'bootstrap-additions/0.3.1/bootstrap-additions.css',
                'version'      => '0.3.1',
                'url_map'      => 'https://raw.githubusercontent.com/mgcrea/bootstrap-additions/master/dist/bootstrap-additions%2$s.css',
                'use_cdn'      => false, // using github directly is bad mojo
                'dependencies' => array(
                    'bootstrap',
                ),
                'provides'     => array(
                    'bootstrap-additions',
                    'bootstrap-additions.css',
                    'bootstrap-additions-0.3.1',
                    'ext/bootstrap-additions.css',
                    'ext/bootstrap-additions-0.3.1.css',
                ),
            ),
            /* TODO Probably move all google fonts into own library? */
            'roboto-draft-font' => array(
                'name'         => 'RobotoDraft.css',
                'version'      => '',
                'url_map'      => 'http%1$s://fonts.googleapis.com/css?family=RobotoDraft:300,400,500,700,400italic',
                'dependencies' => array(
                ),
                'provides'     => array(
                    'roboto-draft-font',
                ),
            ),
            'angular-material' => array(
                'name'         => 'angular_material/0.9.4/angular-material.css',
                'version'      => '0.9.4',
                'url_map'      => 'http%1$s://ajax.googleapis.com/ajax/libs/angular_material/%3$s/angular-material%2$s.css',
                //'url_map'      => 'https://rawgit.com/angular/bower-material/master/angular-material.css',
                //'use_cdn'      => false, //grabbbing from github, not good for mojo
                'dependencies' => array(
                    'roboto-draft-font',
                ),
                'provides'     => array(
                    'angular-material',
                    'angular-material.css',
                    'angular-material-0.9.4',
                    'ext/angular-material.css',
                    'ext/angular-material-0.9.4.css',
                ),
            ),
        ),
    ),

    'js_ext' => array(
        'base_path'      => '{{{dir_static}}}/ext/',
        'base_url'       => '{{{url_static}}}/ext',
        'use_cdn'        => true,
        'use_compressor' => true,
        'compress_ext'   => '.min',
        //'url_callback'  => false,
        'modules'        => array(
            'jquery'      => array(
                'name'         => 'jquery/1.11.3/jquery.js',
                'version'      => '1.11.3',
                //'url_map'      => 'http%1$s://ajax.googleapis.com/ajax/libs/jquery/%3$s/jquery%2$s.js',
                'url_map'      => 'http%1$s://code.jquery.com/jquery-%3$s%2$s.js',
                'dependencies' => array(
                ),
                'provides'     => array(
                    'jquery',
                    'jquery1',
                    'jquery-1.11.3',
                    'ext/jquery.js',
                    'ext/jquery1',
                    'ext/jquery-1.11.3',
                ),
            ),
            'bootstrapjs' => array(
                'name'         => 'bootstrap/3.3.4/bootstrap.js',
                'version'      => '3.3.4',
                'url_map'      => 'http%1$s://maxcdn.bootstrapcdn.com/bootstrap/%3$s/js/bootstrap%2$s.js',
                'dependencies' => array(
                    'jquery',
                ),
                'provides'     => array(
                    'bootstrap',
                    'bootstrapjs',
                    'bootstrap3',
                    'bootstrap-3.3.4',
                    'ext/bootstrap.js',
                    'ext/bootstrap-3.3.4.js',
                ),
            ),
            'bootstrap-material-design' => array(
                'name'         => 'bootstrap-material-design/0.3.0/material.js',
                'version'      => '0.3.0',
                'url_map'      => 'http%1$s://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/%3$s/js/material%2$s.js',
                'dependencies' => array(
                    'bootstrapjs',
                ),
                'provides'     => array(
                    'bootstrap-material-design',
                    'bootstrap-material-design.js',
                    'bootstrap-material-design-0.3.0',
                    'ext/material.js',
                    'ext/material-0.3.0.js',
                ),
            ),
            'bootstrap-material-design-ripples' => array(
                'name'         => 'bootstrap-material-design/0.3.0/ripples.js',
                'version'      => '0.3.0',
                'url_map'      => 'http%1$s://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/%3$s/js/ripples%2$s.js',
                'dependencies' => array(
                    'bootstrap-material-design',
                ),
                'provides'     => array(
                    'bootstrap-material-design-ripples',
                    'bootstrap-material-design-ripples.js',
                    'bootstrap-material-design-ripples-0.3.0',
                    'ext/material-ripples.js',
                    'ext/material-ripples.0.3.0.js',
                ),
            ),
            'angularjs' => array(
                'name'         => 'angularjs/1.3.16/angular.js',
                'version'      => '1.3.16',
                'url_map'      => 'http%1$s://ajax.googleapis.com/ajax/libs/angularjs/%3$s/angular%2$s.js',
                'dependencies' => array(
                ),
                'provides'     => array(
                    'angular',
                    'angularjs',
                    'angularjs-1.3.16',
                    'ext/angular.js',
                    'ext/angular-1.3.16.js',
                ),
            ),
            'angular-animate' => array(
                'name'         => 'angularjs/1.3.16/angular-animate.js',
                'version'      => '1.3.16',
                'url_map'      => 'http%1$s://ajax.googleapis.com/ajax/libs/angularjs/%3$s/angular-animate%2$s.js',
                'dependencies' => array(
                    'angularjs',
                ),
                'provides'     => array(
                    'ngAnimate',
                    'angular-animate',
                    'angular-animate-1.3.16',
                    'ext/angular-animate.js',
                    'ext/angular-animate-1.3.16.js',
                ),
            ),
            'angular-aria' => array(
                'name'         => 'angularjs/1.3.16/angular-aria.js',
                'version'      => '1.3.16',
                'url_map'      => 'http%1$s://ajax.googleapis.com/ajax/libs/angularjs/%3$s/angular-aria%2$s.js',
                'dependencies' => array(
                    'angularjs',
                ),
                'provides'     => array(
                    'ngAria',
                    'angular-aria',
                    'angular-aria-1.3.16',
                    'ext/angular-aria.js',
                    'ext/angular-aria-1.3.16.js',
                ),
            ),
            //https://developers.google.com/speed/libraries/#angular-material
            'angular-material' => array(
                'name'         => 'angular_material/0.9.4/angular-material.js',
                'version'      => '0.9.4',
                'url_map'      => 'http%1$s://ajax.googleapis.com/ajax/libs/angular_material/%3$s/angular-material%2$s.js',
                'use_cdn'      => true,
                //'url_map'      => 'https://rawgit.com/angular/bower-material/master/angular-material.js',
                //'use_cdn'      => false, //grabbbing from github, not good for mojo
                'dependencies' => array(
                    'angularjs',
                    'angular-animate',
                    'angular-aria',
                ),
                'provides'     => array(
                    'ngMaterial',
                    'angular-material',
                    'angular-material-0.9.4',
                    'ext/angular-material.js',
                    'ext/angular-material-0.9.4.js',
                ),
            ),
            'angular-strap' => array(
                'name'         => 'angular-strap/1.3.16/angular-strap.js',
                'version'      => '2.1.2',
                'url_map'      => 'http%1$s://cdnjs.cloudflare.com/ajax/libs/angular-strap/%3$s/angular-strap%2$s.js',
                'dependencies' => array(
                    'angularjs',
                ),
                'provides'     => array(
                    'angularstrap',
                    'angular-strap',
                    'angular-strap-2.1.2',
                    'ext/angular-strap.js',
                    'ext/angular-strap-2.1.2.js',
                ),
            ),
            'angular-strap-tpl' => array(
                'name'         => 'angular-strap/1.3.16/angular-strap.tpl.js',
                'version'      => '2.1.2',
                'url_map'      => 'http%1$s://cdnjs.cloudflare.com/ajax/libs/angular-strap/%3$s/angular-strap.tpl%2$s.js',
                'dependencies' => array(
                    'angular-strap'
                ),
                'provides'     => array(
                    'angularstrap.tpl',
                    'angular-strap-tpl',
                    'angular-strap-tpl.2.1.2',
                    'ext/angular-strap.tpl.js',
                    'ext/angular-strap.tpl-2.1.2.js',
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
