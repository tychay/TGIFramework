<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Module dependencies for YAHOO User Interface Library version 2.5.3
 *
 * Note that this version isn't entirely accurate because it is based on
 * 2.6.0 and then modified because cookies is beta.
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2008 Tagged, Inc. <http://www.tagged.com/>, All Rights Reserved
 * @author terry chay <tychay@tagged.com>
 */
return array(
    // YUI Javascript Core {{{
    'yahoo'         => array(
        'isCore'        => true,
        'isLoaderCore'  => true,
        'isUtil'        => true,
        'isExperimental'=> false,
        'dependencies'  => array(),
        'optionals'     => array(),
    ),
    'event'         => array(
        'isCore'        => true,
        'isLoaderCore'  => true,
        'isUtil'        => true,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo'),
        'optionals'     => array(),
    ),
    'dom'           => array(
        'isCore'        => true,
        'isLoaderCore'  => true,
        'isUtil'        => true,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo'),
        'optionals'     => array(),
    ),
    // }}}
    // YUI Javascript Utilities {{{
    'animation'     => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => true,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/event','YAHOO/dom'),
        'optionals'     => array(),
    ),
    'connection'    => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/event'),
        'optionals'     => array(),
    ),
    'cookie'        => array(
        'map'           => 'cookie-beta',
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo'),
        'optionals'     => array(),
    ),
    'datasource'    => array(
        'map'           => 'datasource-beta',
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/event'),
        'optionals'     => array('YAHOO/connection'),
    ),
    'dragdrop'      => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => true,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/event','YAHOO/dom'),
        'optionals'     => array(),
    ),
    'element'       => array(
        'map'           => 'element-beta',
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => true,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/event','YAHOO/dom'),
        'optionals'     => array(),
    ),
    'get'           => array(
        'isCore'        => false,
        'isLoaderCore'  => true,
        'isUtil'        => true,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo'),
        'optionals'     => array(),
    ),
    'history'       => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/event'),
        'optionals'     => array(),
    ),
    'imageloader'   => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/event','YAHOO/dom'),
        'optionals'     => array(),
    ),
    'json'          => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo'),
        'optionals'     => array(),
    ),
    'paginator'     => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => true,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/event','YAHOO/dom','YAHOO/element'),
        'optionals'     => array(),
    ),
    'resize'        => array(
        'map'           => 'resize-beta',
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO-dragdrop','YAHOO/element'),
        'optionals'     => array('YAHOO/animation'),
    ),
    'selector'      => array(
        'map'           => 'selector-beta',
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom'),
        'optionals'     => array(),
    ),
    'yuiloader'     => array(
        'isCore'        => false,
        'isLoaderCore'  => true,
        'isUtil'        => true,
        'isExperimental'=> false,
        'dependencies'  => array(),
        'optionals'     => array(),
    ),
    // }}}
    // YUI User Interface Widgets {{{
    'autocomplete'      => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/datasource'),
        'optionals'     => array('YAHOO/animation'),
    ),
    'button'            => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/element'),
        'optionals'     => array('YAHOO/container','YAHOO/menu'),
    ),
    'calendar'          => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event'),
        'optionals'     => array(),
    ),
    'carousel'          => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/element'),
        'optionals'     => array('YAHOO/animation'),
    ),
    'charts'            => array(
        'map'           => 'charts-experimental',
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> true,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/element','YAHOO/json','YAHOO/datasource'),
        'optionals'     => array(),
    ),
    'colorpicker'       => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/dragdrop','YAHOO/slider','YAHOO/element'),
        'optionals'     => array(),
    ),
    'container'         => array(
        'map'           => 'container', //set this to 'container_core' if you don't need tooltip, panel, dialog, or simpledialog
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event'),
        'optionals'     => array('YAHOO/dragdrop','YAHOO/animation','YAHOO/connection'), //if 'container_core' then this is empty
    ),
    'datatable'         => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/element','YAHOO/datasource'),
        'optionals'     => array('YAHOO/calendar','YAHOO/dragdrop','YAHOO/paginator'),
    ),
    'editor'            => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/container','YAHOO/menu','YAHOO/element','YAHOO/button'),
        'optionals'     => array('YSHOO snimsyion','YAHOO/dragdrop'),
    ),
    'imagecropper'      => array(
        'map'           => 'imagecropper-beta',
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/dragdrop','YAHOO/element','YAHOO/resize'),
        'optionals'     => array('YAHOO/animation'),
    ),
    'layout'            => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/element'),
        'optionals'     => array('YAHOO/animation','YAHOO/dragdrop','YAHOO/resize','YAHOO/selector'),
    ),
    'menu'              => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/container'),
        'optionals'     => array(),
    ),
    'simpleeditor'      => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/element'),
        'optionals'     => array('YAHOO/container','YAHOO/menu','YAHOO/animation','YAHOO/dragdrop'),
    ),
    'slider'            => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/dragdrop'),
        'optionals'     => array('YAHOO/animation'),
    ),
    'tabview'           => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/element'),
        'optionals'     => array('YAHOO/connection'),
    ),
    'treeview'          => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event'),
        'optionals'     => array(),
    ),
    'uploader'          => array(
        'map'           => 'uploader-expeirmental',
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> true,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/element'),
        'optionals'     => array(),
    ),
    // }}}
    // YUI Developer Tools {{{
    'logger'            => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event'),
        'optionals'     => array('YAHOO/dragdrop'),
    ),
    'profiler'          => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo'),
        'optionals'     => array(),
    ),
    'profilerviewer' => array(
        'map'           => 'profileviewer-beta',
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yuiloader','YAHOO/profiler','YAHOO/dom','YAHOO/event','YAHOO/element'),
        'optionals'     => array(),
    ),
    'yuitest'           => array(
        'isCore'        => false,
        'isLoaderCore'  => false,
        'isUtil'        => false,
        'isExperimental'=> false,
        'dependencies'  => array('YAHOO/yahoo','YAHOO/dom','YAHOO/event','YAHOO/logger'),
        'optionals'     => array('YAHOO/dragdrop'),
    ),
    // }}}
    // YUI aggregate (rollup) {{{
    'yuiloader-dom-event' => array(
        'lookup'        => 'isLoaderCore',
    ),
    'yui-dom-event'     => array(
        'lookup'        => 'isCore',
    ),
    'utilities'         => array(
        'lookup'        => 'isUtil',
    ),
    // }}}
);
?>
