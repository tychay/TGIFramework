<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Configuration for global related stuff
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    // class loading {{{
    // $_TAG->classmaps {{{
    /**
     * A maptable that allows you to do backward compatible remappings of class
     * names to file names for {@link __autoload()} to work.
     * @global array
     * @name $_TAG->classmaps
     */
    'gld_classmaps'     => array(
        'construct'         => '__autoload_maptable',
        'version'           => 1,
        'shouldShard'       => true,  //different installs should be seperate'
        'isSmemable'        => true,
        'isMemcacheable'    => false, // I tried this with TRUE for testing. Works only if we deal with the commenting issues elsewhere
        'memcacheChannel'   => '___',
        'memcacheKey'       => false,
        'deferCache'        => false, //don't try to call a defer cache. it's ugly
    ),
    // }}}
    // {{{ autoload_stubs
    /**
     * Set to true if you want {@link __autoload()} to throw exceptions and
     * make a stub class on failure to launch.
     */
    'autoload_stubs'    => false,
    // }}}
    // }}}
    // diagnostics {{{
    // {{{ diagnostics_monitor
    /**
     * Should we send diagnostics information to the monitoring service
     */
    'diagnostics_monitor' => false,
    // }}}
    // {{{ diagnostics_monitorEvent
    /**
     * If diagnostics_monitor is true, should we send diagnostics information 
     * of the events to the monitoring service? (was
     * diagnostics_should_send_event)
     */
    'diagnostics_monitorEvent' => false,
    // }}}
    // {{{ diagnostics_memcache
    /**
     * Should we do diagnostics timing on memcache calls (timings will also
     * be logged when logging is on.
     *
     * In my experience, this uses less than a fraction of a millisecond so
     * there is no harm in turning it on.
     */
    'diagnostics_memcache' => false,
    // }}}
    // }}}
    // debugging {{{
    'firephp_diagnostics'    => true,    //log diagnostics to firephp
    // }}}
    // memcache {{{
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
        'version'           => 1,
        'shouldShard'       => true, //different installs should be seperate
        'isSmemable'        => true,
        'smemKey'           => false,
        'isMemcacheable'    => false,
    ),
    // }}}
    // {{{ memcached_extension
    /**
     * which php extension to use
     */
    'memcached_extension'  => 'memcached',
    // }}}
    // {{{ memcached_default_port
    /**
     * default port
     */
    //'memcached_default_port'  => 11211,
    // }}}
    // }}}
);
?>
