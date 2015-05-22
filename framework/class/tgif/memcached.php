<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_memcached}.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007 Tagged Inc. 2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net> 
 * @todo add memcache add behavior
 * @todo add memcache delete behavior
 * @todo add memcache flush behavior
 * @todo add memcache replace behavior
 * @todo add memcache checkAndSet support
 * @todo add memcache getDelayed support
 * @todo add memcache getMulti/setMulti support
 */
// {{{ tgif_memcached
// docs {{{
/**
 * Handle memcache and memcache pooling.
 *
 * Usage:
 * The memcache system is a special case since it is so instrumental in the
 * storing and reading of objects. Because of that, it's gld_* configuration
 * should not be messed with, and its configurations come from elsewhere.
 *
 * If you would like to use a default pool, you can access the global
 * directly:
 * <code>
 * $result = $_TAG->memcache->get($key,$group);
 * </code>
 * But you can also use named pools.
 * <code>
 * $result = $_TAG->memcache[$pool_name]->get($key,$group);
 * </code>
 *
 * Note that if $pool_name (aka channel) does not exist, it will assume that
 * the pool name is 'default' which should be created always.
 *
 * Note that if $key is an array, instead of a single element than the first
 * part of the array is assumed to be the key and the second part of the array
 * is assumed to be the $serverkey.
 *
 * Note that $group is allowed to prevent key conflicts (two keys can have the
 * same name, but will not conflict if they have a different group), it is also
 * used for logging purposes.
 *
 * Configuration:
 * The following config variables can be set: They are contained in the
 * "memcached" configuration. (XXX = $pool_name):
 * - memcached.extension (string): Which extension to use for reading and
 *   writing to memcache. The two values allowed are memcache and memcached (or
 *   libmemcached). Note that the behaviour of the two are radically
 *   different. Default is memcache.
 * - memcached.default_port (integer): the default port number to use. Default
 *   11211.
 * - memcached.config_XXX (array): The config settings for various pools note
 *   that since memcache objects are shared, the actual configuration may not match exactly
 *   depending on how things are instantiated and where the config variable is
 *   used. {@link $_defaultConfig description of the config elements}.
 * - memcached.pool_XXX (array): containing the server, port, and weights of various
 *   servers memcache objects can be attached to. Depending on how this
 *   is marshalled this may be cached slightly differently. The array elements
 *   contain an array of up to three elements: host, port, and weight. If no
 *   port is given the memcache.default_port is used. If no weight is given
 *   weight defaults to 1.
 *
 * History:
 * This is a near entire rewrite. The old version of this code was
 * tag_memcache. This was changed to tgif_memcached instead of tgif_memcache
 * because the new code never allows working with a memcache object directly.
 * All actions must occur through this facade which handles the pool. The old
 * version required some API compatibility in order to work with legacy code.
 * In the past, things wrapped a socket-based user-space emulator known as
 * MemcacheSJ26. This version works by wrapping the
 * {@link http://php.net/memcache/ memcache extension} which was very similar.
 * Later version of the tag system used a UDP library that was hacked into
 * the memcache extension. This code has been removed in this verison.
 *
 * An alternate version wraps the
 * {@link http://php.net/memcached/ memcached extension} that uses libmemcached
 * (see below).
 *
 * The memcache version does correct (though inefficient) pooling, instead of
 * the pooling system built into the memcache library. The reason for this
 * is that affinity (server keys) need to be optionally supported and the
 * extension does not support it.
 *
 * The consisting hashing mechamism used, is designed to better surive server
 * deaths. At Tagged, the system was dependent on a netscaler to modify the
 * VIP dynamically to do this. If you set memcache.hash_strategy at runtime to
 * "Consistent", it is far less versitile and {@link http://pecl.php.net/bugs/bug.php?id=14563 it's buggy}.
 * 
 * The way the Tagged website currently works was that affinity. This was 
 * done via a second parameter which had to be run through a utility method
 * known as affine_channel() in order to handle affinity pooling. This worked
 * by mapping things onto one of 64 channels with a one letter prefix in front.
 * Each channel would have its own pool and, if not provided, would go to the
 * default pool. This was unnecessarily complicated, and the newer version is
 * closer to the way the memcached extension works: a second serverKey can
 * be provided in order to getByServer.
 *
 * The hashing function used in Tagged used a cryptographic hash. This is
 * overridden by default (breaking backward compatibility). It now attempts to
 * emulate the algorithm in memcache extension:
 * (binascii.crc32(key) >> 16) & 0x7fff 
 *
 * If we use libmemcached (memcached extension), then there are some extra
 * features we should enable at somet time:
 * - support in the above two for libketama which is used to ensure
 *   compatibility with non php-based memcaching. {@link http://blog.chris.de/archives/288-libketama-a-consistent-hashing-algo-for-memcache-clients.html}
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
 */
// }}}
class tgif_memcached implements ArrayAccess
{
    // PRIVATE PROPERTIES
    // {{{ - $ _extension
    /**
     * Which php extension to use (memcache or memcached)
     *
     * @var string
     */
    private $_extension = 'memcache';
    // }}}
    // {{{ - $ _defaultPort
    /**
     * The default TCP port to use when connecting to memcache server.
     *
     * This can be set with the memcached.default_port configuration variable
     * and if not used it will use the default set by memcache.default_port
     * php.ini. nd if not that it will use 11211.
     *
     * @var integer
     */
    private $_defaultPort = 11211;
    // }}}
    // {{{ - $ _defaultConfig
    /**
     * The pool configuration of the default pool
     *
     * This is used as the defaults of all named pools also.
     *
     * - persist (boolean): When creating new connections, should we use
     *   persistent connections or not? Default true.
     * - lifetime (integer): default lifetime for the keys. If 0 then persist
     *   forever. This is also known as "expire". Default is 0.
     * - timeout (integer): default wait for connection to return in seconds
     *   Default is 1.
     * - retryTimeout (integer): how long should we disable a server from the
     *   pool before adding it back in. If memcached, it trys the connection 3
     *   times with a 1msec gap before removing from pool. Default is 100.
     * - compressThreshold (false|integer): byte size before
     *   zip is turned on automatically. Threshould values are not setable in
     *   memcached version. Default is false
     * - compressMinSaving (float, memcache-only): savings fraction or turn off.     *   Default is 0.2
     *   (20%)
     * - checkStatus (boolean, memcache-only): should we check the server
     *   status before  using. Default to false.
     * - logRandom (float|false, memcache-only): What is the probability for
     *   instantiating a {@link tgif_memcached_memache_log logging memcache
     *   proxy} instead of a regular one. Default is false.
     * - diagnostics (boolean): should we do diagnostic logging of memcache
     *   calls? Default is false.
     * - hashing (mixed) : if set, you can override the hashing algorithm used
     *   for generating server hashkeys with by this system (not recommended
     *   unless you need to be backward compatible with another system).
     *   Note that if memache then the serializer may be an abritrary function,
     *   but you must create that function. If memcached, the options are
     *   {@link http://php.net/manual/en/memcached.constants.php the * part of
     *   Memcached::HASH_*}. WARNING: this means the algorithm used by memcache:
     *   ({@link http://en.wikipedia.org/wiki/Cyclic_redundancy_checka crc32}
     *   mapped onto more bits) is different from the one used by memcached:
     *   {@link http://en.wikipedia.org/wiki/Jenkins_hash_function the Jenkins
     *   one-at-a-time}. Default to ''.
     * - strategy (integer, memcache-only) : You can choose one of two mapping
     *   strategies. If zero, it will use the "mod" strategy. If set to a
     *   number then it will use the ketama-like map, and the number is the
     *   maxint of the hash function. Note this means you can only use a "mod"
     *   strategy with a hashing of md5 as the max value is larger than MAX_INT.
     *   Default is 0x7fff. Remember, it will not be libketama compatible
     *   unless you modify hashing parameter to be md5 above, and modify the
     *   library to support integers as high as the hash will go.
     * - distribution (string, memcached-only): algorithm for mapping keys
     *   across servers types. Currently support igbinary, json, and php.
     *   Default is 'consistent'. To make it libketama compatibe, don't
     *  set 'hashing' but instead set this to 'libketama';
     * - serializer (string, memcached-only): object serializer for non-native
     *   types. Currently support igbinary, json, and php. Default is php
     * @var array
     */
    private $_defaultConfig = array(
            'persist'           => true,
            'lifetime'          => 0,
            'timeout'           => 1,
            'retryTimeout'      => 100,
            'compressThreshold' => false,
            'compressMinSaving' => 0.2,
            'checkStatus'       => false,
            'logRandom'         => false,
            'diagnostics'       => false,
            'hashing'           => '',
            'strategy'          => 0x7fff,
            'serializer'        => 'php',
            'distribution'      => 'consistent',
        );
    // }}}
    // {{{ - $ _defaultServers
    /**
     * The default server pool.
     *
     * This is an array of arrays. The elements are the servers (and weights)
     * associated with the default pool
     *
     * @var array
     */
    private $_defaultServers = array();
    // }}}
    // PUBLIC PROPERTIES 
    // {{{ - $ _pools
    /**
     * Array of {@link tgif_memcached_pool}s indexed by pool names.
     * @var array
     */
    private $_pools = array();
    // }}}
    // CREATION AND SERIALIZATION
    // {{{ __construct()
    /**
     * Creates the default pool.
     */
    function __construct()
    {
        global $_TAG;
        if ( $port = $_TAG->config('memcached.default_port',true) ) {
            $this->_defaultPort = $port;
        } elseif ( $port = ini_get('memcache.default_port') ) {
            $this->_defaultPort = $port;
        }
        $this->_extension = ( in_array($_TAG->config('memcached.extension',true),array('memcached','libmemcached')) && extension_loaded('memcached') )
                           ? 'memcached'
                           : 'memcache';
        $this->_loadDefaultConfig();
    }
    // }}}
    // {{{ - _loadDefaultConfig()
    /**
     * Loads default memcache pool into internal variables.
     *
     * It will load the default config and pool and save it to
     * {@link $_defaultConfig} (which also defines the default values if none
     * of these are overridden in memcached.config_default.
     */
    private function _loadDefaultConfig()
    {
        global $_TAG;
        $configs = $_TAG->config('memcached.config_default',true);
        if ( $configs ) {
            $this->_defaultConfig = array_merge($this->_defaultConfig, $configs);
        }
        $servers = $_TAG->config('memcached.pool_default',true);
        $this->_defaultServers = ( $servers )
                               ? $servers
                               : array(array('127.0.0.1',11211,1)); // make local machine the default
        $this->_sanitizeServers($this->_defaultServers);
        $this->_addPool('default',$this->_defaultConfig,$this->_defaultServers);
    }
    // }}}
    // {{{ ___sleep()
    /**
     * Used to allow most of the work to be saved into shared memory cache.
     *
     * Don't serialize memcache objects (they have resources and are probably
     * not serializeable).
     *
     * @return array the list of params to serialize
     */
    function __sleep()
    {
        return array('_extension','_defaultPort','_defaultConfig','_defaultServers','_pools');
    }
    // }}}
    // ARRAYACCESS POOLS
    // {{{ - offsetExists($offset)
    /**
     * @param $offset mixed
     * @return boolean
     */
    public function offsetExists ( $offset )
    {
        return isset($this->_pools[$offset]);
    }
    // }}}
    // {{{ - offsetGet($offset)
    /**
     * Used to allow most of the work to be saved into shared memory cache.
     *
     * @param $offset mixed
     * @return tgif_memcached_pool
     */
    public function offsetGet ( $offset )
    {
        if ( !isset($this->_pools[$offset]) ) {
            $this->_load($offset);
        }
        return $this->_pools[$offset];
    }
    // }}}
    // {{{ - offsetSet($offset,$value)
    /**
     * Not allowed
     *
     * @param $offset mixed
     * @param $value mixed
     */
    public function offsetSet($offset,$value)
    {
        trigger_error(sprintf('%s:offsetSet() Illegal attempt to externally bind a memcache pool.', get_class($this), E_USER_ERROR));
        
    }
    // }}}
    // {{{ - offsetUnset($offset)
    /**
     * @param $offset mixed
     */
    public function offsetUnset($offset)
    {
        unset($this->_pools[$offset]);
        
    }
    // }}}
    // {{{ - _load($poolName)
    /**
     * Loads a memcache pool into internal variables.
     *
     * If not overridden in memcached.config_{{pool_name}}, then the default
     * values are stored in {@link $_defaultConfig}.
     *
     * @param string $poolName The id of the pool (to use memcached parliance)
     * The only special pool is 'default' which is loaded using
     * {@link _loadDefaultConfig() a different function}.
     */
    private function _load($poolName)
    {
        $configs = $_TAG->config('memcached.config_'.$poolName, true);
        $configs = ( $configs )
                 ? array_merge($this->_defaultConfigs,$configs)
                 : $this->defaultConfigs;
        $servers = $_TAG->config('memcached.pool_'.$poolName, true);
        if ( !$servers ) {
            $servers = $this->_defaultServers;
        } else {
            $this->_sanitizeServers($servers);
        }
        return $this->_addPool($poolName,$configs,$servers);
    }
    // }}}
    // {{{ - _addPool($poolName,$config,$servers)
    /**
     * Adds a pool to this object.
     *
     * @param string $poolName the name of the pool
     * @param array $config the configuration to add.
     * @param array $servers the servers in the pool (and weights)
     * @return tgif_memcached_pool The pool added.
     */
    private function _addPool($poolName, $config, $servers)
    {
        if ($this->_extension == 'memcached') {
            $this->_pools[$poolName] = new tgif_memcached_pool_memcached( $config, $servers, $poolName );
        } else {
            $this->_pools[$poolName] = new tgif_memcached_pool_memcache( $config, $servers );
        }
        if ($config['diagnostics']) {
            $this->_pools[$poolName] = new tgif_memcached_pool_proxy( $this->_pools[$poolName] );
        }
    }
    // }}}
    // {{{ - _sanitizeServers($servers)
    /**
     * Ensure all servers have a host, port, and weight
     *
     * @param array $servers List of servers to sanitize
     */
    private function _sanitizeServers(&$servers)
    {
        foreach ($servers as &$server) {
            if ( empty($server[1]) ) {
                $server[1] = $this->_defaultPort;
            }
            if ( !isset($server[2]) ) {
                $server[2] = 1; //default weight
            }
        }
    }
    // }}}

    // DEFAULT POOL
    // {{{ - __call($name,$arguments)
    /**
     * Pass through all undefined calls to the default memcache pool.
     *
     * For the most part, these mimic the wp_cache_*() functions. The only
     * exception is that $expire=-1 binds expire time to server config default.
     *
     * @param string $name the function called
     * @param array $arguments the parameters passed
     */
    function __call($name, $arguments)
    {
        return call_user_func_array( array($this->_pools['default'], $name), $arguments );
    }
    // }}}

    // SERVER PROPERTIES (unsaved)
    // {{{ - $ _memcaches
    /**
     * {@link tgif_memcached_memcache} Memcache objects indexed by host:port.
     *
     * Note that since the memcache objects are shared across the pools if
     * using memcache as your storing function, settings may vary from machine
     * to machine. If that is the case, it's first come, first serve. In othe
     * words, don't vary this!
     *
     * @var array
     */
    private $_memcaches = array();
    // }}}
    // SERVER METHODS
    // {{{ - getMemcacheByServer($serverInfo,$config)
    /**
     * Get {@link tgif_memcached_memcache memcache object} from a registry.
     * 
     * Unlike the old {@link MemcachePool}, this code does not handle the
     * special session server pool rules or the WORKING_KEY_PREFIX rules.
     * Why? It messes with my chi. This used to be getMemcache() and
     * serverByKey() and getMemcacheByServer() but the code has been merged
     * because of the new way of handling it (the abstractions covers these).
     *
     * @param array $serverInfo host, port, and weight.
     * @param array $config memcache configuration parameters
     * @return tgif_memcached_memcache object that is connected to a
     * any cache server based on the serverInfo.
     */
    function getMemcacheByServer($server_info, $config)
    {
        $server_id = $server_info['host'].':'.$server_info['port'];
        // Look for a cached memcache object {{{
        if (isset($this->_memcaches[$server_id])) {
            $memcache_obj = $this->_memcaches[$server_id];
            // check memcache status {{{
            if ($config['checkStatus']) {
                // Check that memcache is set correctly.  If not, dump a
                // message and disable.
                $status = $memcache_obj->getServerStatus($server_info['host'],$server_info['port']);
                if (!$status) {
                    if ($config['persist']) {
                        $memcache_obj->close();
                    }
                    $memcache_obj = new tgif_memcached_memcache_null();
                    $this->_memcaches[$server_id] = $memcache_obj;
                    $this->_serverSetDisabled($server_id,true,$config['retryTimeout']);
                    trigger_error(sprintf('%s:_getMemcache() Inconsistent state for %s.', get_class($this), $server_id),E_USER_WARNING);
                }
            }
            // }}}
            return $memcache_obj;
        }
        // }}}
        // If the server is disabled return a "null" do-nothing proxy {{{
        if ($this->_serverDisabled($server_id)) {
            return tgif_memcached_null();
        }
        // }}}
        $memcache_obj = new Memcache;
        // should we use a logging object? {{{
        if ( (($prob = $config['logRandom']) !== false) && (rand()/getrandmax() < $prob) ) {
            $memcache_obj = new tgif_memcached_log($memcache_obj);
        }
        // }}}
        // No need for diagnostics proxy in this version, that is handled
        // upstream.
        // connect to memcache server {{{
        if ( $config['persist'] ) {
            $success = $memcache_obj->pconnect($server_info['host'], $server_info['port'], $config['timeout']);
        } else {
            $success = $memcache_obj->connect($server_info['host'], $server_info['port'], $config['timeout']);
        }
        // }}}
        if ($success) {
            // Should not need to check server status
            $this->_serverSetDisabled($server_id,false);
            // Set compression {{{
            $threshold = $config['compressThreshold'];
            if ($threshold !== false) {
                $memcache_obj->setCompressThreshold($threshold, $config['compressMinSaving']);
            }
            // }}}
        } else {
            // failed to connect {{{
            $this->_serverSetDisabled($server_id,true,$config['retryTimeout']);
            trigger_error(sprintf('%s:_getMemcache() Could not connect to server %s',get_class($this), $serverInfo),E_USER_WARNING);
            $memcache_obj = new tgif_memcached_null();
            // }}}
        }
        // register object
        $this->_memcaches[$server_id] = $memcache_obj;
        return $memcache_obj;

        /* //using getServerStatus() now
        // Make sure that we're OK. {{{
        if (!$memcache_obj) {
            $ok = $memcache->getVersion();
            if (!$ok) {
                $memcache = $this->_connect($server_data[0], $server_data[1], $config['timeout'], $config['persist']);
            }
        }
        // }}}
        /* */
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
        global $_TAG;
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
