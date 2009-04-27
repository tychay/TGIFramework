<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tag_global_loader}
 *
 * @package tgiframework
 * @subpackage global
 * @copyright c.2007 Tagged, Inc., c.2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 * @todo support memcache queues
 * @todo support database (via data access objects)
 * @todo support database queues
 */
// imports {{{
if (!function_exists('apc_fetch')) {
    /**
     * Need apc_* user cache functions (or emulators)
     */
    require_once TGIF_FUNC_DIRD.'cache.php';
}
// }}}
// {{{ tgif_global_loader
// comments {{{
/**
 * Load globals automagically
 *
 * All possible parameters (defaults):
 * - params (0): if non-zero this defines the nesting of globals as array objects
 * - construct (): The callback function to call as the constructor. This must
 *      always be provided to bootstrap the system.
 * - shouldShard (false): If true, the built in cache key generator will create
 *      keys unique to the installation checkout (different dev systems will
 *      have different keys) for that object
 * - loaderLoader (): method of the constructed object to pass the loader to
 *      after construction. This is done so the loader has access to the
 *      shared memory storage and other functions of the loader class. Note that
 *      it is bad mojo to serialize this with the object, so you should consider
 *      using __sleep() to filter this parameter out.
 * - version (0): the version number to append to the cache keys to allow
 *      for hot updates of the information.
 * - manualCache (false): if set to true, it will not write the memcache
 *      automatically at all.
 * - deferCache (true): if set to true, it will not write the memcache until
 *      page shutdown (via event system).
 * - checkCache (): method to call that returns a boolean on whether or not
 *      (after wakeup) the wakeup function may have changed the data and
 *      need to be set.
 * - isSmemable (false): whether or not this global can be stored into
 *      the shared memory segment of the server's user cache. Note: never
 *      serialize any user/profile data, only sitewide globals, etc.
 * - smemKey (false): the callback to generate the shared memory key. If false,
 *     then it will use a built-in generator.
 * - isMemcacheable (false): whether or not this global can be stored into
 *      memcache.
 * - memcacheChannel (___): the memcache pool to use. Note that if this is
 *      an array, then the first element specifies which parameter provides
 *      the pool affinity key. If it is two elements, then the second element
 *      is the character to specify an independant pool (other than the profile
 *      pool which is 'p').
 * - memcacheKey (false): the callback to generate the memory cache key. This
 *      must return an array (key,channel). If false, then it will use a
 *      built-in generator.
 * - memcacheLifetime (false): the amount of time to store the value in memcache.
 *      By default, this uses the default value of the memcachePool
 * - smemcacheLifetime (false): the amount of time to store value in shared memory.
 *      By default, this is forever (i.e., until service restart).
 * - memcacheGet (unserialize): a callback to run after data is returned from
 *      memcache. Since we are using the memcache extension, we shouldn't
 *      need to use unserialize() here, but we do because if you pass an
 *      object, it saves it as an associative array
 * - memcacheSet (serialize): a callback to run before data is stored to
 *      memcache. Since we are using the memcache extension, we shouldn't need
 *      to use serialize() here, but we do becasue it saves objects as
 *      associative arrays.
 *
 * There is a special rule for callbacks in that if the callback is an array
 * with a single function it will call "new" on the element instead of
 * {@link call_user_func_array()}. This means if the constructor has multiple
 * paramters in the constructor, it can only have a single parameter(!) which
 * is an array of the parameterization.
 *
 * @package tgiframework
 * @subpackage global
 */
// }}}
class tgif_global_loader extends tgif_global_object
{
    // {{{ - _global_version
    /**
     * A global version character
     *
     * Added a single character can be changed when we change default behaviors
     * of the engine, or basic routines in the loader object. Think of it as
     * global versioning, but not done on every release. If you want to do this
     * on release, just keep cyling it through base-64 numbers 0…9a…zA…z-_
     *
     * Only use one character to keep the keys small.
     *
     * Note that this code is only called when using the autokey generator. To
     * allow for backward compatibility, this is not called when the generator
     * is bypassed.
     *
     *  Previous versions:
     * - "": original
     * - "0": use memcaches internal serialize and deserialize by default,
     *       default version is an empty string instead of 0.
     */
    const _global_version = '0';
    // }}}
    // PUBLIC PROPERTIES
    // {{{ - $self
    /**
     * The object/data itself.
     * Unless the loader is unloaded, there will remain a reference to it here.
     * Note that unless this is an object, this will be a copy of the data,
     * not the data itself so be warned when working in
     * {@link tgif_global_loader::$_deferCache deferred cache mode}
     * @var mixed
     */
    public $self = null;
    // }}}
    // CONFIGURABLE PRIVATE PROPERTIES
    // {{{ - $_ids
    /**
     * This is used to identify the object when it is part of a collection.
     * most often this is used in constructing cache keys and the constructor of
     * the object itself.
     * @var array|0
     */
    private $_ids = 0;
    // }}}
    // {{{ - $_name
    /**
     * The name of the object.
     *
     * This is used in constructing keys, it will usually be the class name.
     * @var string
     */
    private $_name = '';
    // }}}
    // {{{ - $_configPrefix
    /**
     * The configuration prefix
     *
     * This is used in constructing keys, it will be a unique id for the
     * checkout or '___' by default.
     * @var string
     */
    private $_configPrefix = '___';
    // }}}
    // {{{ - $_construct
    /**
     * The callback to call to create the object from scratch when it can�t
     * be found in the other stores (including the database!)
     * @var mixed
     */
    private $_construct = false;
    // }}}
    // {{{ - $_shouldShard
    /**
     * When storing variables into the volatile stores (smem, memcache), should
     * we separate this by the config prefix in order to prevent overlap with
     * other installs?
     */
    private $_shouldShard = false;
    // }}}
    // {{{ - $_loaderLoader
    /**
     * Set this to a value and it will assume on construction that this is
     * the method it should pass the loader to the object that was just
     * constructed.
     *
     * Don't use this variable if you are constructing a non-object
     * (obviously).
     *
     * @var string
     */
    private $_loaderLoader = 0;
    // }}}
    // {{{ - $_version
    /**
     * The version number.
     *
     * This is used as part of the key to allow dynamic upgrades.
     * @var string
     */
    private $_version = '';
    // }}}
    // {{{ - $_manualCache
    /**
     * Set to true if you want the object to manually control its caching.
     * @var boolean
     */
    private $_manualCache = false;
    // }}}
    // {{{ - $_useUpdateChecker
    /**
     * Check to see if data changed before doing an update. This also
     * throws an object notification on change.
     *
     * This only works if the _loaderLoader is set, if the data
     * stored is an object, and for memcache get/sets
     */
    private $_useUpdateChecker = true;
    // }}}
    // {{{ - $_deferCache
    /**
     * Set to true to defer writing of cache to page shutdown
     */
    private $_deferCache = true;
    // }}}
    // {{{ - $_checkCache
    /**
     * Set to a method to call to check if force update
     * on referesh
     * @var string
     */
    private $_checkCache = '';
    // }}}
    // {{{ - $_isSmemable
    /**
     * Should we persist this object into the web server's shared memory?
     */
    private $_isSmemable = false;
    // }}}
    // {{{ - $_smemKey
    /**
     * Function to call when getting the key from memory.
     *
     * When false, this uses its own generator.
     * @var mixed
     */
    private $_smemKey = false;
    // }}}
    // {{{ - $_isMemcacheable
    /**
     * Should we persist this object into memcached?
     * @var boolean
     */
    private $_isMemcacheable = false;
    // }}}
    // {{{ - $_memcacheChannel
    /**
     * Which memcache pool to refer to.
     *
     * If this is an array, then it will do memcache affinity. The first
     * element in the array will be the index into the id of the object
     * to do the affinity for. The second one will be a prefix character.
     *
     * For instance, if the object is indexed by user id and this parameter
     * is array(0,'crapola'); then the channel generated would be something
     * for a user in the 37th partition would be: c37 (c from "c" in crapola).
     *
     * If no second paramter is specified it will use the character "p".
     *
     * If no memcache partition exists it will use the default channel "___".
     * @var string|array
     */
    private $_memcacheChannel = '___';
    // }}}
    // {{{ - $_memcacheKey
    /**
     * Function to call to get the memcache key.
     *
     * When false, this uses its own generator.
     * @var mixed.
     */
    private $_memcacheKey = false;
    // }}}
    // {{{ - $_memcacheLifetime
    /**
     * The lifetime of the memcache key in seconds.
     *
     * When false, this uses the default of the memcachePool.
     * @var mixed.
     */
    private $_memcacheLifetime = false;
    // }}}
    // {{{ - $_smemcacheLifetime
    /**
     * The lifetime of the smemcache key in seconds.
     *
     * When false, the key lives until service restart.
     * @var mixed.
     */
    private $_smemcacheLifetime = false;
    // }}}
    // {{{ - $_memcacheGet
    /**
     * Function to execute when getting data from memcache.
     *
     * Note for old key support you should put this as 'serialize'
     * @var mixed if false, does nothing, else it does {@link call_user_func()}
     */
    private $_memcacheGet = false;
    // }}}
    // {{{ - $_memcacheSet
    /**
     * function to execute when putting data into memcache.
     *
     * Note for old key support you should put this as 'unserialize'
     * @var mixed if false, does nothing, else it does {@link call_user_func()}
     */
    private $_memcacheSet = false;
    // }}}
    // {{{ - $_deleteAction
    /**
     * Function to execute before deleting object from cache.
     *
     * This allows the system to delete any related persistent stores
     *
     * Note for old key support you should put this as 'unserialize'
     * @var mixed if false, does nothing, else it does {@link call_user_func()}
     *  If the first element in the array is empty, it inserts $this into that
     *  element.
     */
    private $_deleteAction = false;
    // }}}
    // {{{ - $_lockedUpdates
    /**
     * Whether all updates to this object should be performed inside a lock
     * @var bool
     */
    private $_lockedUpdates = false;
    // }}}
    // {{{ - $_dataVersion
    /**
     * Name of the method in the object that returns the version of the object
     * Only need if $_lockedUpdates is true
     * @var string
     */
    private $_dataVersion = 'getDataVersion';
    // }}}
    // VERY PRIVATE PROPERTIES
    // {{{ - $__dataSignature
    /**
     * If {@link $_useUpdateChecker} this stores
     * the signature of the data
     * @var string
     */
    private $__dataSignature;
    // }}}
    // {{{ - $__data
    /**
     * The data received from calling a store
     * @var string
     */
    private $__data;
    // }}}
    // {{{ - $__needsUpdate
    /**
     * Flag to say that we need to update the caches with the new data.
     *
     * I had to add a check before updating this flag so it only updates if
     * isSemable or isMemcacheble is true (tychay 20080903)
     * @var boolean
     */
    private $__needsUpdate = false;
    // }}}
    // {{{ - $__callback
    /**
     * What to do with return data in order to start the object.
     * @var string
     */
    private $__callback;
    // }}}
    // {{{ - $__exception
    /**
     * A problem occurred in the loading of the object
     * @var tgif_global_exception|null
     */
    private $__exception;
    // }}}
    // {{{ - __stopped
    /**
     * How far the object loaded before stopping.
     * @var string
     */
    private $__stopped;
    // }}}
    // CREATION AND DESTRUCTION
    // {{{ __construct($params)
    /**
     * The constructor.
     *
     * @param $params a hash of information the global system uses to figure
     * out how to load the stuff as transparently as possible.
     *
     * Unsupported parameters:
     * - smemCallback: like memcacheCallback but for shared memory segment
     * - onDispatch: set this if you need to do work after dispatch (ex. loading
     *      class libraries
     * - dirtyCallback: what call hander to register when it receives a cache
     *      dirty (this should either update itself to smem,memcache, or it
     *      should delete itself from cache).
     * - isPersist: does this have a way to save/persist to database
     * - dbCallback: what to call to grab data from database and make global
     * @var mixed The callback to call when there is no variable. This should
     *     be an array or string.
     */
    function __construct($params)
    {
        foreach($params as $key=>$value)
        {
            $propname = '_'.$key;
            if (isset($this->$propname)) {
                $this->$propname = $value;
            }
        }
    }
    // }}}
    // {{{ __sleep()
    /**
     * Prevent self serialization.
     *
     * Since {@link $_loaderLoader} may be a property of a memcached object
     * it may self serialize. This prevents it
     */
    function __sleep()
    {
        return array();
    }
    // }}}
    // ACCESSORS
    // {{{ - _defaultKeyGen()
    /**
     * Generate a simple key including support for configurable parameter passing
     *
     * @return string
     */
    private function _defaultKeyGen() {
        $return = self::_global_version;
        $return .= ($this->_shouldShard)
                  ? $this->_configPrefix.'-'
                  : '';
        $return .= $this->_name;
        if ($this->_ids) { $return .= '_'.implode('_',$this->_ids); }
        // memcache keys cannot contain control characters or whitespace so we urlencode
        return urlencode($return . '.' . $this->_version);
    }
    // }}}
    // {{{ - smemKey()
    /**
     * Grab the key for shared memory access
     */
    function smemKey()
    {
        if ($this->_smemKey) {
            return call_user_func($this->_smemKey,$this->_ids);
        } else {
            return $this->_defaultKeyGen();
        }
    }
    // }}}
    // {{{ - memcacheKey()
    /**
     * Grab the key used to store data into memcached.
     */
    function memcacheKey()
    {
        // grab the key either with a user function or the default _defaultKeyGen {{{
        if ($this->_memcacheKey) {
            $userKey = call_user_func($this->_memcacheKey,$this->_ids);
            if (is_array($userKey)) {
                return $userKey;
            }
            $key = $userKey;
        } else {
            $key = $this->_defaultKeyGen();
        }
        // }}}
        // handle channel affinity {{{
        $channel = $this->_memcacheChannel;
        if (is_array($channel)) {
            $channel = tgif_memcache::affine_channel(
                      $this->_ids[$channel[0]],                             // affinity parameter
                      (count($channel) > 1) ? substr($channel[1],0,1) : 'p' // prefix defaults to 'p'
                      );
        }
        // }}}
        return array($key,$channel);
    }
    // }}}
    // PUBLIC METHODS
    // {{{ - dispatch([$stopAt])
    /**
     * Grab the data from the quickest persistence store.
     *
     * This doesn't actually create an object, that is left for {@link ready()}
     *
     * @return void
     * @todo memcache (upgrade): add flag support on get()
     */
    function dispatch($stopAt='')
    {
        global $_TAG;
        // we may be reloading, so clear the "last" exception
        $this->__exception = null;
//        $old_error_handler = set_error_handler(array('tgif_global_loader','error_handler'), E_ALL&~E_NOTICE&~E_USER_NOTICE);
        try {
            // get from shared memory {{{
            if ($this->_isSmemable) {
                $return = apc_fetch($this->smemKey());
                if ($return !== false) {
                    $this->__data = $return;
                    $this->__callback = false;
                    return;
                }
            }
            // }}}
            if ($stopAt && strcmp($stopAt,'smem')===0) { $this->__stopped = 'smem'; return; }
            // get from memcached {{{
            if ($this->_isMemcacheable) {
                try {
                    $memcache_pool = $_TAG->memcachePool;
                    $key = $this->memcacheKey();
                    $cache = $memcache_pool->getMemcache($key[0],$key[1]);
                    $data = $cache->get($key[0]);
                    // handle you can save strings only (temporary) {{{
                    if (is_array($data)) {
                        $data = (array_key_exists($key[0],$data))
                            ? $data[$key[0]]
                            : false;
                    }
                    // }}}
                    if ($data !== false) {
                        $this->__data = $data;
                        $this->__callback = $this->_memcacheGet;
                        return;
                    }
                } catch (tgif_global_exception $e) {
                    // If we get a cache exception, make a note but
                    // don't otherwise do anything.  We want to
                    // proceed as if caching were enabled.
                    trigger_error( $e->getMessage() );
                }
            }
            // }}}
            if ($stopAt && strcmp($stopAt,'memcache')===0) { $this->__stopped = 'memcache';  return; }
            if ($stopAt && strcmp($stopAt,'db')===0) { $this->__stopped = 'db';  return; }
            // constructor {{{
            if ($this->_construct) {
                if (!is_array($this->_ids)) {
                    $this->__data = array();
                } elseif (count($this->_ids)==1) {
                    $this->__data = $this->_ids[0];
                } else {
                    $this->__data = $this->_ids;
                }
                $this->__callback = $this->_construct;
                // only update if it is in a memory cache
                if ($this->_isSmemable || $this->_isMemcacheable) {
                    $this->__needsUpdate = true;
                }
            }
            // }}}
        } catch (tgif_global_exception $e) {
            echo $e->getMessage(); die;
            $this->__exception = $e;
        }
//        restore_error_handler();
    }
    // }}}
    // {{{ - canConstruct()
    /**
     * Have we loaded enough data to construct the object?
     * @return boolean
     */
    function canConstruct()
    {
        if ($this->__stopped) { return false; }
        if ($this->__exception) { return false; }
        return true;
    }
    // }}}
    // {{{ - ready()
    /**
     * Load the object from the data abstract.
     *
     * Note that this does different things depending on how {@link $__callback}
     * got written:
     * - empty: returns the pure data
     * - string: {@link call_user_func_array} on data (dangerous because
     *      function may not be in memory)
     * - array (with one parameter): call a constructor, pass data as param
     * - array (more than one parameter): {@link call_user_func_array} on data
     *
     * @return mixed the actually loaded object or data or null if it failed
     *      due to exception.
     */
    function ready()
    {
        global $_TAG;
        if ($this->__stopped) {
            $this->dispatch();
            $this->__stopped = '';
        }
        if ($this->__exception) {
           trigger_error(sprintf(
               'Try to load a failed construction of %s(%s). Failed due to %s on "+%d %s"',
               $this->name,
               implode(',',$this->_ids),
               $this->__exception->getMessage(),
               $this->__exception->getLine(),
               $this->__exception->getFile()), E_USER_WARNING);
           return null;
        }
        // run callback constructor on data {{{
        if (!$this->__callback) {
            $return = $this->__data;
        } elseif(is_array($this->__callback) && (count($this->__callback)==1)) {
            $return = new $this->__callback[0]($this->__data);
        } else {
            $return = call_user_func_array($this->__callback, $this->__data);
        }
        unset($this->__data);
        unset($this->__callback);
        if(empty($return)) { return $return; }
        // }}}
        if ($this->_loaderLoader) {
            if ($this->_useUpdateChecker) {
                $this->__dataSignature = md5(serialize($return));
            }
            call_user_func(array($return,$this->_loaderLoader), $this);
        }
        if ($this->_checkCache && call_user_func(array($return,$this->_checkCache))) {
            $this->__needsUpdate = true;
        }
        if ($this->__needsUpdate) {
            $this->self = $return;
            if (!$this->_manualCache) {
                if ($this->_deferCache) {
                    $_TAG->queue->subscribe('shutdown',array($this,'cacheSelf'),100,false,false);
                } elseif (!$this->_manualCache) {
                    $this->cacheSelf($return);
                }
            }
        }
        return $return;
    }
    // }}}
    // CACHING
    // {{{ - cacheSelf([$deferSmem,$deferMemcache])
    /**
     * Updates the loader to the memory caches.
     *
     * When loading, the cache may be missing from certain volatile stores,
     * let's make sure it's there and updated.
     *
     * Note this function is PUBLIC only in order to be called when
     * {@link $_deferCache} is set. Don't use this function, you are looking
     * for $setToCache($data) instead. :-)
     *
     * @return boolean success or failure.
     */
    public function cacheSelf($deferSmem=false, $deferMemcache=false)
    {
        global $_TAG;
        $result = true;
        $this->__needUpdate = false;
        // save into smem {{{
        if ($this->_isSmemable && !$deferSmem) {
            $result = $result && apc_store($this->smemKey(), $this->self,
                !empty($this->_smemcacheLifetime)? $this->_smemcacheLifetime: 0);
        }
        // }}}
        // save into memcache {{{
        if ($this->_isMemcacheable && !$deferMemcache) {
            try {
                $memcache_pool = $_TAG->memcachePool;
                $key = $this->memcacheKey();
                $cache = $memcache_pool->getMemcache($key[0],$key[1]);
                $cacheLifetime = ($this->_memcacheLifetime===false) ? $memcache_pool->lifetime : $this->_memcacheLifetime;

                // if custom memcache setter is configured
                if ($this->_memcacheSet) {
                    $data = call_user_func($this->_memcacheSet, $this->self);
                } else {
                    $data = $this->self;
                }
                
                if ($this->_lockedUpdates) {
                    require_once(LIB_FUNC_DIR . DIRECTORY_SEPARATOR . 'locked_memcache_update.php');
                    $result = $result && locked_memcache_update($cache, $key[0], array($this, 'lockedCacheUpdate'), 0, $cacheLifetime);
                } else {
                    $result = $result && $cache->set($key[0], $data, 0, $cacheLifetime);
                }
            } catch (tgif_global_exception $e) {
                // If we get a cache exception, make a note but
                // don't otherwise do anything.  We want to
                // proceed as if caching were enabled.
                trigger_error($e->getMessage());
            }
        }
        // }}}
        return $result;
    }
    // }}}
    // {{{ - lockedCacheUpdate($oldValue)
    /**
     * Checks old value's data version against new value's data version for a locked update
     * 
     * Note: this function is public so that it can be called by locked_memcache_update
     * DO NOT CALL THIS FUNCTION DIRECTLY
     * 
     * @param $oldValue mixed value currently in memcache
     * @return mixed new value if update is OK, otherwise false
     */
    public function lockedCacheUpdate($oldValue) {
        // make sure we have a more recent copy
        if ($oldValue) {
            $oldVersion = call_user_func(array($oldValue, $this->_dataVersion));
            $newVersion = call_user_func(array($this->self, $this->_dataVersion));
            if ($oldVersion >= $newVersion) {
                return false;
            }
        }
        // return our copy for setting to cache
        return $this->self;
    }
    // }}}
    // {{{ - setToCache($data[,$deferSmem,$deferMemcache])
    /**
     * Allow cache to be updated.
     *
     * An example of how this should be used when you have _setLoader defined.
     * <code>$this->_loader->setToCache($this);</code>
     */
    public function setToCache($data, $deferSmem=false, $deferMemcache=false)
    {
        global $_TAG;
        // Don't bother updating caches if nothing changed {{{
        if ($this->_useUpdateChecker && $this->_loaderLoader && (md5(serialize($data))==$this->__dataSignature)) {
            return true;
        }
        // }}}
        // Throw object update notification {{{
        if (is_object($data) && $_TAG->queue) {
            $_TAG->queue->publish(array('object',get_class($data),'updateCache'), array('obj'=>$data));
        }
        // }}}
        $this->self = $data;
        return $this->cacheSelf($deferSmem,$deferMemcache);
    }
    // }}}
    // {{{ - deleteFromCache($smemOnly)
    /**
     * Allow cache to be deleted
     *
     * Skip can occur when the loader never got the object from cache the first
     * time. That's because this is called whether or not the obejct is actually
     * in cache.
     */
    public function deleteFromCache()
    {
        global $_TAG;
        $result = true;
        // handle deleteAction {{{
        if ($this->_deleteAction) {
            $skip = false;
            if (is_array($this->_deleteAction) && !$this->_deleteAction[0]) {
                if ($this->self) {
                    $this->_deleteAction[0] = $this->self;
                } else {
                    $skip = true;
                }
            }
            if (!$skip) {
                call_user_func($this->_deleteAction);
            }
        }
        // }}}
        // delete from sMem {{{
        if ($this->_isSmemable) {
            $result = $result && apc_delete($this->smemKey());
        }
        // }}}
        // delete from memcache {{{
        if ($this->_isMemcacheable) {
            try {
                $memcache_pool = $_TAG->memcachePool;
                $key = $this->memcacheKey();
                $cache = $memcache_pool->getMemcache($key[0],$key[1]);
                $result = $result && $cache->delete($key[0]);
            } catch (tgif_global_exception $e) {
                // If we get a cache exception, make a note but
                // don't otherwise do anything.  We want to
                // proceed as if caching were enabled.
                trigger_error( $e->getMessage() );
            }
        }
        // }}}
        return $result;
    }
    // }}}
    // {{{ + get_from_cache($key, $fromSmem, $fromMemcache)
    /**
     * Grab the data from the quickest persistence store.
     *
     * @param $key array key and pool id
     * @todo memcache (upgrade): add flag support on get()
     */
    static function get_from_cache($key, $fromSmem, $fromMemcache)
    {
        global $_TAG;
        // we may be reloading, so clear the "last" exception
        try {
            // get from shared memory {{{
            if ($fromSmem) {
                $return = apc_fetch($key[0]);
                if ($return !== false) {
                    return $return;
                }
            }
            // }}}
            // get from memcached {{{
            if ($fromMemcache) {
                $memcache_pool = $_TAG->memcachePool;
                if (empty($key[1])) { trigger_error(sprintf('tgif_global_loader::get_from_cache()  Missing complete key for %s.',$key[0])); }
                $cache = $memcache_pool->getMemcache($key[0],$key[1]);
                $data = $cache->get($key[0]);
                if (!$data) { return false; }
                return unserialize($data);
            }
            // }}}
        } catch (tgif_global_exception $e) {
            trigger_error($e->getMessage());
            return false;
        }
    }
    // }}}
    // {{{ + save_to_cache($key, $value, $toSmem, $toMemcache[, $ttl = 0])
    /**
     * Save data to all presistence stores
     *
     * @param $key array key and pool id
     */
    static function save_to_cache($key, $value, $toSmem, $toMemcache, $ttl = 0)
    {
        global $_TAG;
        // we may be reloading, so clear the "last" exception
        try {
            // set to shared memory {{{
            if ($toSmem) {
                apc_store($key[0], $value, $ttl);
            }
            // }}}
            // set to memcached {{{
            if ($toMemcache) {
                 $memcache_pool = $_TAG->memcachePool;
                 $cache = $memcache_pool->getMemcache($key[0],$key[1]);
                 $data = $cache->set($key[0], serialize($value), 0, $memcache_pool->lifetime);
            }
            // }}}
        } catch (tgif_global_exception $e) {
            trigger_error($e->getMessage());
        }
    }
    // }}}
    // {{{ + error_handler($errNo,$errStr[,$errFile,$errLine,$errContext]))
    static function error_handler($errNo,$errStr,$errFile='',$errLine=0,$errContext=array())
    {
        if(false == error_reporting()) return true; // handle @function calls
        throw new tgif_global_exception($errStr,$errNo,$errFile,$errLine);
        return false; //to populate $php_errormsg
    }
    // }}}
}
// }}}
?>
