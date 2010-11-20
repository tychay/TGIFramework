<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_memcached_memcache}.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net> 
 */
// {{{ tgif_memcached_memcache
/**
 * Base class for a memcache extension-like object.
 *
 * Pooling is disabled.
 *
 * @package tgiframework
 * @subpackage global
 */
class tgif_memcached_memcache
{
    // {{{ - addServer()
    /**
     * This is not allowed for memcache extension as these servers are handled
     * by {@link tgif_memcached}.
     */
    function addServer()
    {
        trigger_error( sprintf('Call to undefined method %s::addServer()', get_class($this)), E_USER_ERROR');
    }
    // }}}
}
// }}}
?>
