<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_memcached_pool_memcache}.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007 Tagged Inc. 2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net> 
 */
// {{{ tgif_memcached_pool
// docs {{{
/**
 * Base class of a memcache pool (mostly for commenting).
 *
 * @package tgiframework
 * @subpackage global
 */
// }}}
class tgif_memcached_pool
{
    // CREATION AND SERIALIZATION
    // {{{ - getServerByKey($serverKey)
    /**
     * Emulate {@link Memcached::getServerByKey()}.
     *
     * @param string $serverKey The key identifying the server to store the
     * value on.
     * @return array Hash with host, port, weight
     */
    function getServerByKey($serverKey)
    {
    }
    // }}}
    // DATA METHODS
    // {{{ - get($key,$group)
    /**
     * Get data from a memcache pool
     *
     * @param mixed $key A key by which data in cache is identified.
     * @param string $group If provided this is used for logging purposes and to
     * keep keys from colliding.
     * @param string $pool the string to allow the pool mapping to be used.
     * @return mixed data
     */
    function get($key, $group='')
    {
    }
    // }}}
    // {{{ - set($key,$var[,$group,$expire])
    /**
     * Set data to a memcache pool
     *
     * @param mixed $key A key by which data in cache is identified.
     * @param mixed $var The value to set it to
     * @param string $group If provided this is used for logging purposes and to
     * keep keys from colliding.
     * @param integer $expire expire time for an itemp. You can also use Unix
     * timestamp or a number of seconds from the current time. 0 = nexver
     * expire. If not provided (or negative), use the default expire time
     * @return boolean success or failure
     */
    function set($key, $var, $group='', $expire=-1)
    {
    }
    // }}}
    // TODO: add more behaviors here (with comments)

    // PRIVATE UTILITY METHODS
    // {{{ - _formatKeyAsArray($key,$group)
    /**
     * Turn a key into a ($key, $server_key) pair
     *
     * This also puts in the $group into the key part.
     *
     * @param mixed $key This gets turned into a two part key.
     * @param string $group A prefix to apply to the $key in order to keep it
     *  from namespacing wrong (does not apply to the $server_key part).
     */
    protected function _formatKeyAsArray(&$key, $group)
    {
        //global $_TAG;
        if (!is_array($key)) {
            $key = array($key,$key);
        }
        $key[0] = $group.$key[0];
    }
    // }}}
}
// }}}
?>
