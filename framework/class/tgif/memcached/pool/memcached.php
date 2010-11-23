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
 * @todo Don't bother with server keys unless it is specified (makes it more
 * efficient and parallelizeable
 */
// {{{ tgif_memcached_pool_memcached
// docs {{{
/**
 * Facade over the libmemcached pool
 *
 * @package tgiframework
 * @subpackage global
 */
// }}}
class tgif_memcached_pool_memcached extends tgif_memcached_pool
{
    // PRIVATE PROPERTIES
    // {{{ - $ _obj
    /**
     * The memcached object
     * @var memcached
     */
    private $_obj;
    // }}}
    // {{{ - $ _creation_data
    /**
     * @var array
     */
    private $_config;
    // }}}
    // {{{ - $ _servers
    /**
     * @var array
     */
    private $_servers;
    // }}}
    // {{{ - $ _name
    /**
     * @var string
     */
    private $name;
    // }}}
    // {{{ - $ _totalWeight
    /**
     * @var integer
     */
    private $_totalWeight = 0;
    // }}}
    // CREATION AND SERIALIZATION
    // {{{ __construct($config,$servers,$name)
    /**
     * 
     * @todo consider adding the symbol to the $name to prevent conflicts
     */
    function __construct($config, $servers, $name)
    {
        $this->_config  = $config;
        $this->_servers = $servers;
        $this->_name    = $name;
        $this->_obj = self::_makeObject($config,$servers,$name);
    }
    // }}}
    // {{{ __sleep()
    /**
     */
    function __sleep()
    {
        return array('_config','_servers','_name','_totalWeight');
    }
    // }}}
    // {{{ __wakeup()
    /**
     */
    function __wakeup()
    {
        $this->_obj = self::_makeObject($this->_config,$this->_servers,$this->_name);
    }
    // }}}
    // {{{ + _makeObject($config,$servers,$name)
    private static function _makeObject($config, $servers, $name)
    {
        if ($config['persist']) {
            $m = new memcached($name);
        } else {
            $m = new memcached();
        }
        // compression {{{
        $threshold = $config['compressThreshold'];
        if ( $threshold === false ) {
            // Memcached::OPT_COMPRESSION: default=true (100 bytes)
            $m->setOption(Memcached::OPT_COMPRESSION, false);
        }
        // }}}
        // serializer {{{
        switch ($config['serializer']) {
            case 'igbinary': 
            // Memcached::OPT_SERIALIZER default=Memcached::SERIALIZER_PHP
            if ( Memcached::HAVE_IGBINARY ) {
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);
            }
            break;
            case 'json': 
            if ( Memcached::HAVE_JSON ) {
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_JSON);
            }
            break;
        }
        // }}}
        // Auto prepend the symbol on all requests. This saves cpu and ensures
        // this cache is independent of other applications 
        $m->setOption(Memcached::OPT_PREFIX_KEY, $_TAG->symbol());
        // hashing {{{
        if ( $hash = $config['hashing'] ) {
            // Memcached::OPT_HASH default=Memcached::HASH_DEFAULT (Jenkins one-at-a-time)
            switch (strtolower($hash)) {
                case 'md5': 
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::HASH_MD5);
                break;
                case 'crc': 
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::HASH_CRC);
                break;
                case 'fnv1_64': 
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::HASH_FNV1_64);
                break;
                case 'fnv1a_64': 
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::HASH_FNV1A_64);
                break;
                case 'fnv1_32': 
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::HASH_FNV1_32);
                break;
                case 'fnv1a_32': 
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::HASH_FNV1A_32);
                break;
                case 'hseih': 
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::HASH_HSEIH);
                break;
                case 'murmur': 
                $m->setOption(Memcached::OPT_SERIALIZER, Memcached::HASH_MURMUR);
                break;
                //case 'DEFAULT':
                //default:
            }
        }
        // }}}
        // distribution {{{
        switch ($config['distribution']) {
            // Memcached::OPT_DISTRIBUTION default=Memcached::DISTRIBUTION_MODULA (MAY CHANGE IN THE FUTURE)
            case 'modula': 
            case 'mod':
            $m->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_MODULA);
            break;
            case 'libketama': 
            case 'ketama': 
            case 'libketama_compatible': 
            $m->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_LIBKETAMA_COMPATIBLE);
            break;
            case 'consistent': 
            default:
            $m->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
        }
        // }}}
        //Memcached::OPT_BUFFER_WRITES
        // Enable binary protocol:
        $m->setOption(Memcached::OPT_BINARY_PROTOCOL, true);;
        // Enable non-blocking IO:
        $m->setOption(Memcached::OPT_NO_BLOCK, true);;
        // Enable no-delay feature on sockets:
        $m->setOption(Memcached::OPT_TCP_NODELAY, true);;
        // non-blocking timeout {{{
        $timeout = $config['timeout'];
        if ( $timeout != 1 ) {
            // Memcached::OPT_CONNECT_TIMEOUT: default=1000 (1 second)
            $m->setOption(Memcached::OPT_CONNECT_TIMEOUT, (int) ($timeout*1000));
            // Memcached::OPT_SEND_TIMEOUT: using non-blocking IO
            // Memcached::OPT_RECV_TIMEOUT: using non-blocking IO
        }
        // }}}
        // retryTimeout {{{
        $timeout == $config['retryTimeout'];
        if ( $timeout != 0 ) {
            // Memcached::OPT_RETRY_TIMEOUT: default=0 (no wait)
            // Memcached::OPT_POLL_TIMEOUT: default=1000 (1 second)
            // Memcached::OPT_SERVER_FAILURE_LIMIT default=0
            $m->setOption(Memcached::OPT_RETRY_TIMEOUT, 1);
            $m->setOption(Memcached::OPT_POLL_TIMEOUT, (int) ($timeout*1000));
            $m->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 3);
        }
        // }}}
        // enable caching of DNS lookups (for speed)
        $m->setOption(Memcached::OPT_CACHE_LOOKUPS, true);;

        $m->addServers($servers);
        return $m;
    }
    // }}}
    // PUBLIC METHODS
    // {{{ - getServerByKey($serverKey)
    /**
     */
    function getServerByKey($serverKey)
    {
        return $this->_obj->getServerByKey($serverKey);
    }
    // }}}
    // DATA METHODS
    // {{{ - get($key[,$group])
    function get($key, $group='')
    {
        $this->_formatKeyAsArray($key,$group);
        return $this->_obj->getByKey( $key[1], $key[0] );
    }
    // }}}
    // {{{ - set($key,$var[,$group,$expire])
    function set($key, $var, $group='', $expire=-1)
    {
        $this->_formatKeyAsArray($key,$group);
        if ($expire <= 0) {
            $expire = $this->_config['lifetime'];
        }
        return $this->_obj->setByKey( $key[1], $key[0], $var, $expire );
    }
    // }}}
}
// }}}
?>
