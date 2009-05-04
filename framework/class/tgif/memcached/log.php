<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_memcached_log}.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007 Tagged Inc. 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net> 
 * @todo need to build tgif_log to activate this code
 */
// {{{ tgif_memcached_log
/**
 * Proxies a standard memcache object with this one to allow logging.
 * @package tgiframework
 * @subpackage global
 */
class tgif_memcached_log
{
    /**
     * Memcache extension object proxied through this interface
     * @var Memcache
     */
    private $_obj;
    private $_host;
    private $_port;
    private $_protocol;
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
    function __call($function,$args)
    {
        global $_TAG;
        // start hooks {{{
        switch ($function) {
        case 'connect':
        case 'pconnect':
            $serverString = explode(':', str_replace('/', '', $args[0]));

            if(strcasecmp($serverString[0], 'udp') == 0 or strcasecmp($serverString[0], 'tcp') == 0) {
                $this->_protocol = array_shift($serverString);
            } else {
                $this->_protocol = 'tcp';
            }

            $this->_host = array_shift($serverString);

            if(sizeof($serverString) == 1) {
                $this->_port = array_shift($serverString);
            } elseif(isset($args[1])) {
                $this->_port = $args[1];
            } else {
                $this->_port = 11211;
            }                
            break;
        case 'get':
            $_TAG->diagnostics->startTimer('memcache', $args[0], array('get',$args[0]));
        case 'set':
        case 'replace':
            $size = strlen(self::_size_of($args[1]));
            $_TAG->diagnostics->startTimer('memcache', $args[0], array($function,$args[0],$size));
            tgif_log::log_message('pageview', 'mcache', array($this->_host, $this->_port, $function, $args[0], $size));
            break;
        case 'delete':
            tgif_log::log_message('pageview', 'mcache', array($this->_host, $this->_port, $function, $args[0]));
            break;
        case 'close':
            trigger_error(sprintf('close called on %s:%s',$this->_host,$this->_port),E_USER_WARNING);
            return;
        }
        // }}}
        $return = call_user_func_array(array($this->_obj,$function),$args);
        // end hooks {{{
        switch ($function) {
        case 'get':
            $size = $return === false ? 0 : self::_size_of($return);
            $_TAG->diagnostics->stopTimer('memcache', array($size));
            tgif_log::log_message('pageview', 'mcache', array($this->_host, $this->_port, $function, $args[0], $size));
            break;
        case 'set':
            if ($return === false) {
                trigger_error(sprintf('couldnt set key %s on memcache server %s:%s for web server %s', $args[0], $this->_host, $this->_port, $_SERVER['SERVER_ADDR']));
            }
            //break;
            //passthru
        case 'replace':
            $_TAG->diganostics->stopTimer('memcache');
        }
        // }}}
        return $return;
    }
    // }}}
    // {{{ + _size_of
    private static function _size_of($data) {
        return
            (is_string($data) || is_int($data)) ?
            strlen($data) :
            strlen(serialize($data));
    }
    // }}}
}
// }}}
?>
