<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_memcached_proxy}.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007 Tagged Inc. 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net> 
 */
// {{{ tgif_memcached_proxy
/**
 * Proxies a standard memcache object with this one to allow injection of
 * logging dynamics.
 * @package tgiframework
 * @subpackage global
 */
class tgif_memcached_proxy
{
    /**
     * Memcache extension object proxied through this interface
     * @var memcache
     */
    private $_obj;
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
        // start timers {{{
        switch ($function) {
            case 'get':
            //save the key
            $_TAG->diagnostics->startTimer('memcache', $args[0], array('get',$args[0]));
            break;
            case 'set':
            case 'replace':
            $size = strlen(serialize($args[1]));
            $_TAG->diagnostics->startTimer('memcache', $args[0], array($function,$args[0],$size));
            break;
        }
        // }}}
        $return = call_user_func_array(array($this->_obj,$function),$args);
        // end timers {{{
        switch ($function) {
            case 'get':
            //save the key
            $size = (is_string($return)) ? strlen($return) : strlen(serialize($return));
            $_TAG->diagnostics->stopTimer('memcache', array($size));
            break;
            case 'set':
            case 'replace':
            //save the key
            $_TAG->diagnostics->stopTimer('memcache');
            break;
        }
        // }}}
        return $return;
    }
    // }}}
}
// }}}
?>
