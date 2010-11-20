<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_memcached_pool_proxy}.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007 Tagged Inc. 2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net> 
 */
// {{{ tgif_memcached_pool_proxy
/**
 * Proxies a pool object with this one in order to add diagnostics.
 *
 * @package tgiframework
 * @subpackage global
 */
class tgif_memcached_pool_proxy extends tgif_memcache_pool
{
    // {{{ - $_obj
    /**
     * Memcache extension object proxied through this interface
     * @var tgif_memcache_pool
     */
    private $_obj;
    // }}}
    // {{{ - __construct()
    function __construct($obj)
    {
        $this->_obj = $obj;
    }
    // }}}
    // {{{ - __get($var)
    function __get($var)
    {
        return $this->_obj->{$var};
    }
    // }}}
    // {{{ - __set($var, $value)
    function __set($var, $value)
    {
        return $this->_obj->{$var} = $value;
    }
    // }}}
    // {{{ - __call($function,$args)
    /**
     * The bytecount on the timers might be a little bit off because we may or
     * may not be using serialize() to get the data.
     */
    function __call($function,$args)
    {
        //global $_TAG;
        return call_user_func_array(array($this->_obj,$function),$args);
    }
    // }}}
    // {{{ - get($key[,$group])
    function get($key, $group='')
    {
        //global $_TAG;
        $_TAG->diagnostics->startTimer('memcache', 'get', array(
            'key'   => $key,
            'group' => $group
            ));
        $return = $this->_obj->get($key, $group);
        //$size = (is_string($return)) ? strlen($return) : strlen(serialize($return)); // not always accurate if using igbinary
        //$_TAG->diagnostics->stopTimer('memcache', array($size));
        $_TAG->diagnostics->stopTimer('memcache');
        return $return;
    }
    // }}}
    // {{{ - set($key,$var,[,$group,$expire])
    function set($key, $var, $group='', $expire=-1)
    {
        //global $_TAG;
        //$size = (is_string($var)) ? strlen($var) : strlen(serialize($var)); // not always accurate if using igbinary
        $_TAG->diagnostics->startTimer('memcache', 'set', array(
            'key'   => $key,
            'group' => $group
            'expire'=> $expire,
            ));
        $return = $this->_obj->get($key, $var, $group, $expire);
        $_TAG->diagnostics->stopTimer('memcache');
        return $return;
    }
    // }}}
}
// }}}
?>
