<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Configuration for compiler (testing)
 *
 * @package tgisamples
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 * @todo find a better way to generate the static url?
 */
return array(
    //'_readConfig'   => true,
    'dir_static'                => APP_DIR.'/www/m',
    'url_static'                => '/tgif/m', //TODO: EDIT ME
    // $_TAG->css {{{
    //'gld_css.ids.signature_method'  => array('tgif_compiler','sign_md5'), //try using md5 hash
    //'gld_css.ids.signature_method'  => array('tgif_compiler','sign_global'), //try using a global version
    'gld_css.isSmemable'        => false,   //store compiler config in
    //'gld_css.isMemcacheable'    => true,    // memcache instead of smem

    'gld_css.ids.use_compiler'  => true,    //try using a compiler
    'gld_css.ids.use_cat'       => true,    //try concatenation
    'gld_css.ids.use_smem'      => true,    //store file info in smem
    //'gld_css.ids.use_memcache'  => true,    //store in memcache
    'gld_css.ids.libraries'     => array(
        'tgif_compiler_library_yuicss'  => 'yui.css',
    ),
    'yui.css.use_cdn'           => false,
    'yui.css.base_dir'          => '{{{dir_static}}}/res/yui',
    'yui.css.base_url'          => '{{{url_static}}}/res/yui/',
    /* */
    // }}}
    // $_TAG->css {{{
    //'gld_js.ids.signature_method'  => array('tgif_compiler','sign_md5'), //try using md5 hash
    'gld_js.isSmemable'         => false,   //store compiler config in
    //'gld_js.isMemcacheable'     => true,    // memcache instead of smem

    'gld_js.ids.use_compiler'   => true,    //try using a compiler
    'gld_js.ids.use_cat'        => true,    //try concatenation
    'gld_js.ids.use_smem'       => true,    //store file info in smem
    'gld_js.ids.libraries'   => array(
        'tgif_compiler_library_yuijs'  => 'yui.js',
    ),
    /* */
    // }}}

);
?>
