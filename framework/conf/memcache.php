<?php
/**
 * Configuration for memcached related stuff
 *
 * This sets the following config varables:
 * - memcached.extension: prefer memcached over memcache extension (if loaded).
 *
 * This configures the following global variables:
 * - $_TAG->memcached: singleton for the object that manages memcached access
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    // {{{ memcached.extension
    /**
     * which php extension to use
     */
    'memcached.extension'  => (extension_loaded('memcached')) ? 'memcached' : 'memcache',
    // }}}
    // {{{ $_TAG->memcached
    /**
     * Stub container for {@link tgif_memcache} objects (accessed by channel)
     * that provides memcache access to a pool of servers (distributed RAM
     * cache).
     *
     * There is a special memcache channel for all default queries known as
     * "___" and never needs to be explicitly called in calls.
     *
     * @global tgif_memcache
     * @name $_TAG->memcache
     */
    'gld_memcached'  => array(
        'params'            => 0,
        'construct'         => array('tgif_memcached'),
        'version'           => 0,
        'isSmemable'        => true,
    ),
    // }}}
);
