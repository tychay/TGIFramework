<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_memcached}.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007 Tagged Inc. 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net> 
 */
// {{{ tgif_memcached
// docs {{{
/**
 * Handle memcache and memcache pooling.
 *
 * This is a near entire rewrite. The old version of this code was
 * tag_memcache. This was changed to tgif_memcached instead of tgif_memcache
 * because the new code never allows working with a memcache object directly.
 * All actions must occur through this facade which handles the pool. The old
 * version required some API compatibility in order to work with legacy code.
 * In the past, things wrapped a socket-based user-space emulator known as
 * MemcacheSJ26. This was changed to work by wrapping the
 * {@link http://php.net/memcache/ memcache extension} which was very similar.
 *
 * The following config variables can be set:
 * - memcached_config_XXX: an array containing config settings for various pools
 *   note that since memcache objects are shared, the actual configuration may
 *   not match exactly depending on how things are instantiated and where the
 *   config variable is used. See {@link $_poolConfigs} for description.
 * - memcache_pool_XXX: an array containing the server and weights of various
 *   servvers memcache objects can be attached to. Depending on how this is
 *   marshalled this may be cached slightly differently. The array elements
 *   contain an array of up to three elements: host, port, and weight.
 * - memcached_checkStatus: should we check the server status on usage?
 * - memcached_retryTimeout: how long to disable a server before trying to
 *   add it back into the pool?
 * - memcache_logRandom: what probability should we use for instantiating
 *   a logging memcache object?
 * - diagnostics_memcache: should we do diagnostic logging for memcache calls?
 *
 * A later version will wrap the
 * {@link http://php.net/memcached/ memcached extension} that uses libmemcached.
 *
 * The current version does correct (though inefficient) pooling in the way
 * the Tagged website currently works with one major caveat which is the way
 * it handles affinity. In the Tagged version affinity was done via a second
 * parameter which had to be run through a utility method known as
 * affine_channel() in order to handle affinity pooling. This worked by mapping
 * things onto one of 64 channels with a one letter prefix in front. Each
 * channel would have its own pool and, if not provided, would go to the
 * default pool. This was unnecessarily complicated, and the newer version is
 * closer to the way the memcached extension works (a second serverKey can
 * be provided in order to getByServer.
 *
 * If we use libmemcached we can (and will) do the following:
 * - use a more efficient hashing function that the cryptographic one Tagged
 *   was using (breaking backward compatibility). The memcache algorithm in
 *   memcache extension is: (binascii.crc32(key) >> 16) & 0x7fff and this code
 *   attempts to emulate that.
 * - use a consistent hashing solution to better survive server deaths insead
 *   of depending on a netscaler to modify the VIP dynamically. You can do
 *   this if you set memcache.hash_strategy at runtime to "Consistent", but
 *   it is far less versitile and {@link http://pecl.php.net/bugs/bug.php?id=14563 it's buggy}.
 * - support in the above two for libketama which is used to ensure
 *   compatibility with non php-based memcaching. {@link http://blog.chris.de/archives/288-libketama-a-consistent-hashing-algo-for-memcache-clients.html}
 * - do server affinity by a separate server key. This is impossible with the
 *   current memcache library.
 * - use a non-blocking IO and callback functions
 * - use non-delaying sockets, IP caching, and other tricks
 * - use a binary based serialize instead of a slow text based one (breaking
 *   backward compatibility)
 * - compare and swap functionality
 *
 * Currently libmemcached doesn't support UDP except a
 * {@link http://blogs.sun.com/elambert/entry/basic_udp_support_in_libmemcached patch}
 * with {@link http://krow.livejournal.com/633401.html limited functionality}.
 * That is a big deficiency currently as there is no support of
 * MEMCACHED_BEHAVIOR_USE_UDP in the extension (yet). Note that the memcache
 * extension only does UDP on get requests but not sets, whereas the
 * libmemcache strategy is reversed.
 *
 * {@link http://www.facebook.com/note.php?note_id=39391378919&ref=mf High performance considerations from facebook.}
 *
 * @package tgiframework
 * @subpackage global
 * @todo config to allow memcache extension to manage pooling
 * @todo config to allow persistent hashing strategy
 * @todo add support fo weighting to strategy
 * @todo support for memcached extension
 * @todo config to allow memcached extension
 * @todo add more memcache behaviors here (currently only support get and set)
 */
// }}}
class tgif_memcached
{
    // PRIVATE PROPERTIES
    // {{{ - $ _defaultPort
    /**
     * The default TCP port to use when connecting to memcache server.
     *
     * @var integer
     */
    private $_defaultPort;
    // }}}
    // {{{ - $ _poolConfigs
    /**
     * The pool configuration parameters indexed by channel id
     *
     * Might as well store with the object to avoid repeated calls to
     * {@link tgif_global::config()}.
     *
     * This is a hash indexed by pool name. It contains the following values
     * in each element:
     * - persist (boolean): When creating new connections, shoudl we use
     *   persistent connections or not? Default true.
     * - lifetime (integer): default lifetime for the keys. If 0 then persist
     *   forever. This is also known as "expire". Default is 0.
     * - timeout (integer): default wait for connection to return in seconds
     *   Default is 1.
     * - retryTimeout (integer): how long to wait before putting server back in
     *   pool. Default is 100
     * - compressThreshold (false|integer): byte size before gzip is turned on
     *   automatically. Default is false
     * - compressMinSaving (float): savings fraction or turn off. Default is
     *   0.2 (20%)
     * @var array
     */
    private $_poolConfigs = array();
    // }}}
    // {{{ - $ _poolServers
    /**
     * The pool servers indexed by channel id
     *
     * Might as well store with the object to avoid repeated calls to
     * {@link tgif_global::config()}.
     *
     * @var array
     */
    private $_poolServers = array();
    // }}}
    // {{{ - $ _memcaches
    /**
     * Memcache objects (and config data) indexed by host:port. Here is what is
     * in the hash
     * - obj: the memcache object.
     * - expire: the default expiration for connection
     * - persist: is the connection persistent (in case of server down)
     *
     * @var array
     */
    private $_memcaches = array();
    // }}}
    // CREATION AND SERIALIZATION
    // {{{ __construct($ignore)
    /**
     * Creates the default pool.
     */
    function __construct()
    {
        //global $_TAG;
        $this->_loadServerConfig('default',true);
        $this->_defaultPort = ($port = ini_get('memcache.default_port'))
                            ? $port
                            : 11211;
    }
    // }}}
    // {{{ ___sleep()
    /**
     * Don't serialize memcache objects (they have resources and are probably
     * not serializeable).
     *
     * @return array the list of params to serialize
     */
    function __sleep()
    {
        return array('_defaultPort','_poolConfigs','_poolServers');
    }
    // }}}
    // {{{ - _loadServerConfig($channel[,$isDefault])
    /**
     * Loads a default channel and saves to internal cache
     * @todo there might be issue here with $server['id'] binding in the case of
     * using memcached extension
     */
    function _loadServerConfig($channel,$isDefault=false)
    {
        //global $_TAG;
        // set default $configs and $servers {{{
        $configs = $_TAG->config('memcached_config_'.$channel);
        $servers = $_TAG->config('memcached_pool_'.$channel);
        if ($isDefault) {
            $defaults = array(
                'persist'           => true,
                'lifetime'          => 0,
                'timeout'           => 1,
                'retryTimeout'      => 100,
                'compressThreshold' => false,
                'compressMinSaving' => 0.2,
            );
            if (!$servers) {// local machine
                $servers = array(array('127.0.0.1',11211));
            }
            $channel = '___';
        } else {
            $defaults = $this->_poolConfigs['___'];
            if (!$servers) {
                $servers = $this->_poolServers['___'];
            }
        }
        if ($configs) {
            $configs = array_merge($defaults, $configs);
        } else {
            $configs = $defaults;
        }
        // }}}
        // bind ID to server {{{
        foreach ($servers as &$server_data) {
            if (!isset($server_data[1])) {
                $server_data[1] = $this->_defaultPort;
            }
            $server_data['id'] = $server_data[0].':'.$server_data[1];
        }
        // }}}
        $this->_poolConfigs[$channel] = $configs;
        $this->_poolServers[$channel] = $servers;
        return;
    }
    // }}}
    // DATA METHODS
    // {{{ - get($key[,$serverKey,$pool])
    /**
     * Get data from a memcache pool
     *
     * @param string $key A key by which data in cache is identified.
     * @param string $serverKey If provided this represents the key by
     *  which a server in the pool is identified (affinity).
     * @param string $pool the string to allow the pool mapping to be used.
     * @return mixed data
     */
    function get($key, $serverKey='', $pool='___')
    {
        // Affinity is the server key if it works
        if (!$serverKey) { $serverKey = $key; }
        $memcache_info = $this->_getMemcache($key, $serverKey, $pool);
        if (!$memcache_info) { return false; }
        return $memcache_info['obj']->get($key);
    }
    // }}}
    // {{{ - set($key,$var[,$expire,$serverKey,$pool])
    /**
     * Set data to a memcache pool
     *
     * @param string $key A key by which data in cache is identified.
     * @param mixed $var The value to set it to
     * @param integer $expire expire time for an itemp. You can also use Unix
     * timestamp or a number of seconds from the current time. 0 = nexver
     * expire. If not provided (or negative), use the default expire time
     * @param string $serverKey If provided this represents the key by
     *  which a server in the pool is identified (affinity).
     * @param string $pool the string to allow the pool mapping to be used.
     * @return boolean success or failure
     */
    function set($key, $var, $expire=-1, $serverKey='', $pool='___')
    {
        // Affinity is the server key if it works
        if (!$serverKey) { $serverKey = $key; }
        $flag = 0;
        $memcache_info = $this->_getMemcache($key, $serverKey, $pool);
        if (!$memcache_info) { return false; }
        if ($expire <= 0) {
            $expire = $memcache_info['lifetime'];
        }
        return $memcache_info['obj']->set($key, $var, $flag, $expire);
    }
    // }}}
    // TODO: add more behaviors here
    // SERVER METHODS
    // {{{ - _getMemcache($key,$serverKey,$pool)
    /**
     * Returns a {@link memcache} object that is connected to a particular cache
     * server based on the $key.  (Cache keys are spread across a tier of
     * servers using an algorithm designed to help evenly distribute the data.)
     *
     * Unlike the old {@link MemcachePool}, this code does not handle the
     * special session server pool rules or the WORKING_KEY_PREFIX rules.
     * Why? It messes with my chi. This used to be getMemcache() and
     * serverByKey() and getMemcacheByServer() but the code has been merged
     * because of the new way of handling it (the abstractions covers these).
     *
     * The default time out period is 1 second.
     *
     * @param string $key A key by which data in cache is identified.
     * @param string $serverKey If provided this represents the key by
     *  which a server in the pool is identified (affinity).
     * @param string $pool the string to allow the pool mapping to be used.
     * @return array null if it failed to create, else it returns an array
     * containing the memcache object in "obj"
     */
    private function _getMemcache($key, $serverKey, $pool)
    {
        if (!array_key_exists($pool,$this->_poolServers)) {
            $this->_loadServerConfig($pool);
        }
        // find the server id {{{
        // regular CRC32 hash {{{
        $num_servers = count($this->_poolServers[$pool]);
        if ($num_servers < 1) { return null; }
        $hash  = (crc32($key) >> 16) & 0x7fff;
        // }}}
        // Mod hashing strategy
        $index = $hash % $num_servers;
        $server_data = $this->_poolServers[$pool][$index];
        $server_id   = $server_data['id'];
        $config      = $this->_poolConfigs[$pool];
        // }}}
        // Look for a cached memcache object {{{
        if (isset($this->_memcaches[$server_id])) {
            $memcache_data = $this->_memcaches[$server_id];
            $memcache_obj = $memcache_data['obj'];
            // check memcache status {{{
            if ($_TAG->config('memcached_checkStatus')) {
                // Check that memcache is set correctly.  If not, dump
                // a message and disable.
                $status = $memcache_obj->getServerStatus($server_data[0],$server_data[1]);
                if (!$status) {
                    if ($memcache_data['persist']) {
                        $memcache_obj->close();
                    }
                    $memcache_obj = new tgif_memcached_null();
                    $this->_memcaches[$server_id]['obj'] = $memcache_obj;
                    $this->_serverSetDisabled($server_id,true,$config['retryTimeout']);
                    trigger_error(sprintf('%s:_getMemcache() Inconsistent state for %s.', get_class($this), $server_id),E_USER_WARNING);
                }
            }
            // }}}
            return $memcache_data;
        }
        // }}}
        // If the server is disabled return a "null" do-nothing proxy {{{
        if ($this->_serverDisabled($server_id)) {
            return array(
                'obj'       => new tgif_memcached_null(),
                'persist'   => false,
                'lifetime'    => 0
            );
        }
        // }}}
        $memcache_obj = $this->_connect($server_data[0], $server_data[1], $config['timeout'], $config['persist']);
        /*
        // Make sure that we're OK. {{{
        if (!$memcache_obj) {
            $ok = $memcache->getVersion();
            if (!$ok) {
                $memcache = $this->_connect($server_data[0], $server_data[1], $config['timeout'], $config['persist']);
            }
        }
        // }}}
        /* */
        if ($memcache_obj) {
            $this->_serverSetDisabled($server_id,false);
            // Set compression.
            $threshold = $config['compressThreshold'];
            if ($threshold !== false) {
                $memcache_obj->setCompressThreshold($threshold, $config['compressMinSaving']);
            }
        } else {
            $this->_serverSetDisabled($server_id,true,$config['retryTimeout']);
            trigger_error(sprintf('%s:_getMemcache() Could not connect to server %s',get_class($this), $serverInfo),E_USER_WARNING);
            $memcache_obj = new tgif_memcached_null();
        }
        $this->_memcaches[$server_id] = array(
            'obj'       => $memcache_obj,
            'persist'   => $config['persist'],
            'lifetime'  => $config['lifetime'],
        );
        return $this->_memcaches[$server_id];
    }
    // }}}
    // {{{ - _connect($host,$port,$timeout,$shouldPersist)
    /**
     * Connect to a memcache instance.
     *
     * @param string $host IP of host (can use sockets if "host").
     * @param integer $port port of memcache, set to 0 if using domain sockets
     * in the first case
     * @param integer $timeout value in seconds for connecting to the daemon
     * @return object|false a memcache object on success, or false on failure.
     */
    private function _connect($host, $port, $timeout, $shouldPersist) 
    {
        //global $_TAG;
        $memcache = new memcache();
        if ((($prob = $_TAG->config('memcached_logRandom')) !== false) && (rand()/getrandmax() < $prob)) {
            $memcache = new tgif_memcached_log($memcache);
        } else if ($_TAG->config('diagnostics_memcache')) {
            $memcache = new tgif_memcached_proxy($memcache);
        }
        $ok = ($shouldPersist)
            ? $memcache->pconnect($host, $port, $timeout)
            : $memcache->connect($host, $port, $timeout);
        return ($ok) ? $memcache : false;
    }
    // }}}
    // {{{ - _serverDisabled($serverId)
    /**
     * Check if a memcache server is disabled in the local APC cache.
     *
     * I assume that {@link apc_fetch()} created.
     *
     * @param string $serverId identity of memcache server
     */
    private function _serverDisabled($serverId)
    {
        $key = 'memcache_server_'.$serverId; //slightly faster than sprintf()
        $disabled = apc_fetch($key);
        return
            ($disabled !== false) &&
            (time() < $disabled);
    }
    // }}}
    // {{{ - _serverSetDisabled($serverId,$isDisabled[,$retryTimeout])
    /**
     * Set the disabled status of a memcache server in the local APC cache.
     *
     * I assume that {@link apc_store()} and {@link apc_delete()} created.
     *
     * @param string $serverId identity of memcache server
     * @param boolean $isDisabled Set memcache to disable or not?
     * @param integer $retryTimeout If $isDisabled then this tells you how long
     * apache should wait before trying to reconnect to this server.
     */
    private function _serverSetDisabled($serverId, $isDisabled, $retryTimeout=100)
    {
        //global $_TAG;
        $key = 'memcache_server_'.$serverId; //slightly faster than sprintf()
        if ($isDisabled) {
            apc_store($key,time() + $retryTimeout);
        } else {
            apc_delete($key);
        }
    }
    // }}}
}
// }}}
?>
