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
        'construct'         => array('tag_compiler_css'),
        'isSmemable'        => false,
        'isMemcacheable'    => false,
        'ids'               => array(
            'resource_dir'      => '{{{dir_static}}}/res/css',
            'target_dir'        => '{{{dir_static}}}/dyn/css',
            'use_cat'           => true,
            'use_compiler'      => true,
            'use_service'       => true,
            'use_smem'          => true,
            'use_memcache'      => false,
            'signature_mode'    => 'global',
            'java_cmd'          => '{{{bin_java}}}',
            'yui_compressor'    => '{{{jar_yuicompressor}}}',
        ),
    ),
    // }}}
    // {{{ $_TAG->js: js compiler
    'gld_js' => array(
        'construct'         => array('tag_compiler_js'),
        'isSmemable'        => false,
        'isMemcacheable'    => false,
        'ids'               => array(
            'resource_dir'      => '/res/js',
            'target_dir'        => '/dyn/js',
            'use_cat'           => true,
            'use_compiler'      => true,
            'use_service'       => true,
            'use_smem'          => true,
            'use_memcache'      => false,
            'signature_mode'    => 'global',
            'java_cmd'          => '{{{bin_java}}}',
            'yui_compressor'    => '{{{jar_yuicompressor}}}',
            'yui_version'       => '{{{yui_ver}}}',
            'yui_combine'       => true,
        ),
    ),
    // }}}
    'bin_java'              => '/usr/bin/java',
    'jar_yuicompressor'     => TGIF_BIN_DIR.'/yuicompressor-2.4.2.jar',
);
?>
