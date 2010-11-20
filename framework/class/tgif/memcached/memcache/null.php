<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_memcached_memcache_null}.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007 Tagged Inc. 2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net> 
 */
// {{{ tgif_memcached_memcache_null
/**
 * Put in place when memcache isn't working.
 *
 * @package tgiframework
 * @subpackage global
 */
class tgif_memcached_memcache_null extends tgif_memcached_memcache
{
    function add() { return false; }
    function close() { }
    function decrement() { return false; }
    function delete() { return false; }
    function flush() { return false; }
    function get() { return false; }
    function getStats() { return false; }
    function getVersion() { return false; }
    function increment() { return false; }
    function replace() { return false; }
    function set() { return false; }
    /**
     * @return boolean return true here so we don't think this instance is
     * "down" and reset the disabled status. See the logic in
     * {@link tgif_memcached::_getMemcache()}.
     */
    function getServerStatus() { return true; }
}
// }}}
?>
