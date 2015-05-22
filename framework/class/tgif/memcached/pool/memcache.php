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
// {{{ tgif_memcached_pool_memcache
// docs {{{
/**
 * Emulates memcached pooling functionality using the memcache extension
 *
 * Note that in order to conserve connections, connections are marshalled
 * through the server functions in {@link tgif_memcached}.
 *
 * @package tgiframework
 * @subpackage global
 * @todo This class is untested and unfished since I started to use memcached
 * extension during development.
 */
// }}}
class tgif_memcached_pool_memcache extends tgif_memcached_pool
{
    // PRIVATE PROPERTIES
    // {{{ - $ _config
    /**
     * This is a hash.  It contains the following values in each element:
     * - persist (boolean): When creating new connections, shoudl we use
     *   persistent connections or not? Default true.
     * - lifetime (integer): default lifetime for the keys. If 0 then persist
     *   forever. This is also known as "expire".
     * - timeout (integer): default wait for connection to return in seconds
     * - retryTimeout (integer): how long to wait before putting server back in
     *   pool.
     * - compressThreshold (false|integer): byte size before gzip is turned on
     *   automatically.
     * - compressMinSaving (float): savings fraction or turn off.
     * - checkStatus (boolean): should we check the server status on usage?
     * - retryTimeout (integer): how long to disable a server before trying to
     *   add it back into the pool?
     * - logRandom (float|false): what probability should we use for    
     *   instantiating
     *   a logging memcache object?
     * - diagnostics (boolean): should we do diagnostic logging for memcache
     *   calls?
     * - hashing (mixed): if set, you can override the hashing algorithm
     *   used  by this system (not recommended unless you need to be backward
     *   compatible with another system).
     * @var array
     */
    private $_config = array();
    // }}}
    // {{{ - $ _servers
    /**
     * An array of arrays containing the server, port and weights
     *
     * @var array
     */
    private $_servers = array();
    // }}}
    // {{{ - $ _totalWeight
    /**
     * @var integer
     */
    private $_totalWeight = 0;
    // }}}
    // CREATION AND SERIALIZATION
    // {{{ __construct($config,$servers)
    /**
     * Do the work
     */
    function __construct($config,$servers)
    {
        $this->_config = $config;
        $this->_servers = $servers;
    }
    // }}}
    // {{{ - getServerByKey($serverKey)
    /**
     */
    function getServerByKey($serverKey)
    {
        // cache $total_weight {{{
        if ($this->_totalWeight == 0) {
            $total_weight = 0;
            foreach ($this->_servers as $server) {
                $total_weight += $server[2];
            }
            $this->_totalWeight = $total_weight;
        } else {
            $total_weight = $this->_totalWeight;
        }
        // }}}
        // compute hash {{{
        if ($func = $this->_config['hashing']) {
            $hash = call_user_func($func, $serverKey);
        } else {
            $hash  = (crc32($serverKey) >> 16) & 0x7fff;
        }
        // }}}
        // compute $index [0,$total_weight) {{{
        if ($max = $this->_config['strategy']) {
            $index = ($hash / $max) * $total_weight;
        } else {
            // mod hashing
            $index = $hash % $total_weight;
        }
        // }}}
        foreach ($this->_servers as $server_info) {
            if ( $index < $server_info[2] ) { break; }
            $index -= $server_info[2];  //reduce by weight
        }
        return array(
            'host'  => $server_info[0],
            'port'  => $server_info[1],
            'weight'=> $server_info[2],
        );
        
    }
    // }}}
    // DATA METHODS
    // {{{ - get($key[,$group])
    function get($key, $group='')
    {
        global $_TAG;
        $this->_formatKeyAsArray($key, $group);
        var_dump($key);

        $server_info = $this->getServerByKey($key[1]);
        $memcache = $_TAG->memcached->getMemcacheByServer($server_info, $this->_config);
        return $memcache->get($key[0]);
    }
    // }}}
    // {{{ - set($key,$var[,$group,$expire])
    function set($key, $var, $group='', $expire=-1)
    {
        global $_TAG;
        $this->_formatKeyAsArray($key, $group);

        if ($expire <= 0) {
            $expire = $this->_config['lifetime'];
        }

        $server_info = $this->getServerByKey($key[1]);
        $memcache = $_TAG->memcached->getMemcacheByServer($server_info, $this->_config);
        // auto compression turned on in configuration set $flag=0
        return $memcache->set($key[0], $var, 0, $expire);
    }
    // }}}
    // PRIVATE UTILITY METHODS
    // {{{ - _formatKeyAsArray($key,$group)
    /**
     * Turn a key into a ($key, $server_key) pair
     *
     * This also puts in the $group and {@link $_TAG->symbol()} into the key
     * part.
     */
    protected function _formatKeyAsArray(&$key,$group)
    {
        parent::_formatKeyAsArray($key,$group);
        $key[0] = $_TAG->symbol().$key[0];
    }
    // }}}
}
// }}}
?>
