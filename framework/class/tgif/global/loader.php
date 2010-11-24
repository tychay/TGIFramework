<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tag_global_loader}
 *
 * @package tgiframework
 * @subpackage global
 * @copyright c.2007 Tagged, Inc., c.2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 * @todo consider adding a cache for smemkey and memcachekey computation
 * @todo add checkandset flag support
 * @todo support memcached getMulti or nonblocking queues
 * @todo support datastore event/queues
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
 * - params (0): if non-zero this defines the nesting levels of globals as array
 *   objects
 * - construct (): The callback function to call as the constructor. This must
 *   always be provided to bootstrap the system which is needed when the
 *   volatile stores return nothing. There is a special rule here in that if
 *   the callback is an array with a single element it will call "new" on the
 *   element instead of {@link call_user_func_array()}. This means if the
 *   constructor has multiple paramters, it can only have a single parameter!
 *   If it the call has only a single element that is passed through, but if it
 *   has more, it will be passed in as an array.
 * - version (0): the version number to append to the cache keys to allow
 *   for staling of the cache/hot updates of the information.
 * - loaderLoader (''): method of the constructed object to pass the loader to
 *   after construction. This is done so the loader has access to the
 *   shared memory storage and other functions of the loader class. Note that
 *   it is bad mojo to serialize this with the object, so you should consider
 *   using __sleep() to filter this parameter out.
 * - manualCache (false): if set to true, it will not write the memcache
 *   automatically at all. The object must do the work manually.
 * - deferCache (false): if set to true, it will not write the memcache until
 *   page shutdown (via event system).
 * - checkCache (''): method to call that returns a boolean on whether or not
 *   (after wakeup) to force a cache update. A wakeup function or whatever
 *   executed after 'memcacheGet' called, have messed with the object. If that
 *   is the case, we need to always force an update on reading from the cache.
 * - cacheUpdateOnSet (false): if set to true, when a global collection is
 *   externally assigned (_set)), this tells the system to make sure the
 *   object is stored in the caches also. By default, it does not attempt
 *   to update cache (it only binds a loader).
 * - deleteAction (false): If set, this is the callback to execute before
 *   deleting object from cache. This allows the system to delete any related
 *   volatile stores. Note that if it is an array and the first element is
 *   empty, then $this is implied.
 * - isSmemable (false): whether or not this global can be stored into the
 *   shared memory segment of the server's user cache. Note: never serialize any
 *   user/profile data because you will blow through this cache! Instead resere
 *   smem for sitewide globals, and small things that are shared at the
 *   application level.
 * - smemKey (false): the callback to generate the shared memory key. If false,
 *   then it will use a built-in generator.
 * - smemcacheLifetime (0): the amount of time to store value in shared
 *   memory. By default, this is forever (i.e., until service restart).
 * - isMemcacheable (false): whether or not this global can be stored into
 *   memcache.
 * - memcacheKey (false): the callback to generate the memory cache key. This
 *   must may return an array (key,serverkey[,pool]). If false, then it will
 *   use a built-in generator.
 * - memcacheLifetime (false): the amount of time to store the value in
 *   memcache. By default, this uses the default value of {@link memcached
 *   memcache pool}
 * - memcacheGet (null): a callback to run after data is returned from
 *   memcache. Since we are using the php extension, there is no need to use
 *   unserialize() here (for backward compatibility to the original Tagged
 *   system for instance). 
 * - memcacheSet (null): a callback to run before data is stored to  memcache.
 *   Since we are using the php extension, there is no need to use serialize()
 *   here.
 *
 * Automatically generated parameters;
 * - name: This is used in constructing keys, it will usually be the class
 *   name.
 * - ids: This is used to identify the object when it is part of a collection.
 *   most often this is used in constructing cache keys and the constructor of
 *   the object itself.
 * - configPrefix: three letter symbol
 *
 * Unsupported parameters:
 * - shouldShard (false): When storing variables into the volatile stores (smem,
 *   memcache), should we prepend the config prefix in order to prevent overlap
 *   with other installs? Now this is always on by default.
 * - deferCache (true): This used to default to true, but now it is false as
 *   it simplifies interaction.
 * - memcacheChannel (___): the memcache pool to use. This is no longer
 *   supported as we assume the pool is the default pool for everything.
 * - useUpdateChecker (true): Check to see if the data changed before doing
 *   an update. This also throws an object notification on change. This would
 *   only work if _laoderLoader is set, if the data stored is an object, and
 *   for memcache get/sets. This will be replaced by a checkAndSet option.
 * - lockedUpdates (false): whehter all updates to this object should be
 *   performed inside a lock. This is eliminated until checkAndSet is resolved.
 * - getDataVersion ('getDataVersion'): name of the method in the object that
 *   returns the version of the object. Only need if $_lockedUpdates is true.
 *   cheeckAndSet eliminated this nastiness.
 * - smemCallback (): like memcacheCallback but for shared memory segment. It
 *   is decided that since smem stores objects natively, there is no need to
 *   fiddle with the callback system
 * - onDispatch: set this if you need to do work after dispatch (ex. loading
 *   class libraries)
 * - dirtyCallback: what call hander to register when it receives a cache
 *   dirty (this should either update itself to smem,memcache, or it
 *   should delete itself from cache).
 * - isPersist: does this have a way to save/persist to database
 * - dbCallback: what to call to grab data from database and make global
 *
 * @package tgiframework
 * @subpackage global
 */
// }}}
class tgif_global_loader extends tgif_global_object
{
    // CONSTANTS
    // {{{ - _global_version
    /**
     * A global version character
     *
     * Added a single character can be changed when we change default behaviors
     * of entire tag global system, or basic routines in the loader object.
     * Think of it as global versioning, but not done on every release. If you
     * want to do this on release, just keep cyling it through base-64 numbers
     * 0…9a…zA…z-_
     *
     * Only use one character to keep the keys small.
     *
     * Note that this code is only called when using the default generator. To
     * allow for backward compatibility, this is not called when the generator
     * is bypassed.
     *
     * Previous versions:
     * - "": original
     */
    const _global_version = '';
    // }}}
    // PUBLIC PROPERTIES
    // {{{ - $self
    /**
     * The object/data itself.
     *
     * Unless the loader is unloaded, there will remain a reference to it here.
     * Note that unless this is an object, this will be a copy of the data,
     * not the data itself so be warned when working in {@link
     * tgif_global_loader::$_deferCache deferred cache mode}
     * @var mixed
     */
    public $self = null;
    // }}}
    // CONFIGURABLE PRIVATE PROPERTIES
    // {{{ - $_name
    /**
     * The name of the object.
     *
     * @var string
     */
    private $_name = '';
    // }}}
    // {{{ - $_ids
    /**
     * Identify the object when it is part of a collection.
     * @var array|0
     */
    private $_ids = 0;
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
     * The callback to call to create the object from scratch when it can't
     * be found in the other stores (including the database!)
     * @var mixed
     */
    private $_construct = false;
    // }}}
    // {{{ - $_version
    /**
     * The version number.
     *
     * Appended to the default key in order to do stale keys
     *
     * @var string
     */
    private $_version = '';
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
    private $_loaderLoader = '';
    // }}}
    // {{{ - $_manualCache
    /**
     * Set to true if you want the object to manually control its caching.
     * @var boolean
     */
    private $_manualCache = false;
    // }}}
    // {{{ - $_deferCache
    /**
     * Set to true to defer writing of cache to page shutdown
     */
    private $_deferCache = false;
    // }}}
    // {{{ - $_checkCache
    /**
     * Set to a method to call to check if force update
     * on referesh
     * @var string
     */
    private $_checkCache = '';
    // }}}
    // {{{ - $_cacheUpdateOnSet
    /**
     * If the variable is externally set (as part of a collection),
     * This will force the object to update caches also.
     * This allows the system to delete any related persistent stores.
     *
     * @var boolean
     */
    private $_cacheUpdateOnSet = false;
    // }}}
    // {{{ - $_deleteAction
    /**
     * Callback to execute before deleting object from cache.
     *
     * This allows the system to delete any related persistent stores.
     *
     * @var mixed if false, does nothing, else it does {@link call_user_func()}
     *  If the first element in the array is empty, it inserts $this into that
     *  element.
     */
    private $_deleteAction = false;
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
    // {{{ - $_smemcacheLifetime
    /**
     * The lifetime of the smemcache key in seconds.
     *
     * When 0, the key lives until service restart.
     * @var string.
     */
    private $_smemcacheLifetime = 0;
    // }}}
    // {{{ - $_isMemcacheable
    /**
     * Should we persist this object into memcached?
     * @var boolean
     */
    private $_isMemcacheable = false;
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
    // VERY PRIVATE PROPERTIES
    // {{{ - $__data
    /**
     * The data received from a persistent store
     * @var mixed
     */
    private $__data;
    // }}}
    // {{{ - $__callback
    /**
     * What to do with return data in order to start the object.
     * @var string
     */
    private $__callback;
    // }}}
    // {{{ - $__needsUpdate
    /**
     * Flag to say that we need to update the caches with the new data.
     *
     * I had to add a check before updating this flag so it only updates if
     * isSemable or isMemcacheble is true (tychay 20080903)
     *
     * @var boolean
     */
    private $__needsUpdate = false;
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
     * The constructor (builds configuration and overrides defaults).
     *
     * @param $params a hash of information the global system uses to figure
     * out how to load the stuff as transparently as possible.
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
     * Generate a simple key including support for configurable parameter
     * passing.
     *
     * @return string
     */
    private function _defaultKeyGen() {
        $return = self::_global_version . $this->_name;
        if ($this->_ids) { $return .= '_'.implode('_',$this->_ids); }
        return $return . '.' . $this->_version;
    }
    // }}}
    // {{{ - smemKey()
    /**
     * Grab the key for shared memory access
     *
     * If 'smemKey' is specified in the configuration, it will call that
     * function (passing in two parameters: the object name and the ids
     * generate the key. Therefore the function should have the structure:
     * mixed function memcache_key(string $name, array $ids, integer $version)
     *
     * @uses _defaultKeyGen() To generate key in default case. It will then
     * prepend the config prefix to ensure no conflict ont the same server.
     * @return string The key to use reading this object from shared memory.
     */
    function smemKey()
    {
        if ($this->_smemKey) {
            return call_user_func($this->_smemKey,$this->_name,$this->_ids,$this->_version);
        } else {
            return $this->_configPrefix.$this->_defaultKeyGen();
        }
    }
    // }}}
    // {{{ - memcacheKey()
    /**
     * Grab the key used to store data into memcached.
     *
     * If 'memcachKey' is specified in the configuration, it will call that
     * function (passing in two parameters: the object name and the ids
     * generate the key. Therefore the function should have the structure:
     * mixed function memcache_key(string $name, array $ids, integer $version)
     *
     * @return array|string The key used for storing this object into memcache.
     *  Note that if you return an array, the second parameter is the server    
     *  key.
     */
    function memcacheKey()
    {
        // grab the key either with a user function or the default _defaultKeyGen {{{
        if ($this->_memcacheKey) {
            return call_user_func($this->_memcacheKey,$this->_name,$this->_ids,$this->_version);
        } else {
            // memcache keys cannot contain control characters or whitespace
            // so we urlencode
            return urlencode($this->_defaultKeyGen());
        }
        // }}}
    }
    // }}}
    // PUBLIC METHODS
    // {{{ - dispatch([$stopAt])
    /**
     * Grab the data from the quickest persistence store.
     *
     * This doesn't actually create an object, that is left for {@link ready()}.
     * Instead it stores itself in $__data and $__callback.
     *
     * @param string $stopAt How far to go with the dispatch before stopping.
     * @return void
     * @todo when resuming stopped we should skip parts that have already been
     *  done
     */
    function dispatch($stopAt='')
    {
        //global $_TAG;

        // we may be reloading object, so clear the "last" exception
        $this->__exception = null;
        $this->__stopped = ''; // no stopping us...

        try {
            // get from shared memory {{{
            if ($this->_isSmemable) {
                //$return = apc_fetch($this->smemKey(), $success);
                //if ($success) {
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
                    $key = $this->memcacheKey();
                    $data = $_TAG->memcached->get($key, 'global');
                    if ($data !== false) {
                        $callback = $this->_memcacheGet;
                        $this->__callback = $callback;
                        // need to nest data as array for callbacks
                        $this->__data = ($callback) ? array($data) : $data;
                        return;
                    }
                } catch (tgif_global_exception $e) {
                    // If we get a cache exception, make a note but don't
                    // otherwise stop processing.  We want to proceed as if
                    // caching were enabled and returned nothing
                    trigger_error( $e->getMessage() );
                }
            }
            // }}}
            if ($stopAt && strcmp($stopAt,'memcache')===0) { $this->__stopped = 'memcache';  return; }
            // TODO: db gathering code :-)
            if ($stopAt && strcmp($stopAt,'db')===0) { $this->__stopped = 'db';  return; }
            // constructor {{{
            if ($this->_construct) {
                // If constructor must be called make sure the data is saved
                // as an array (makes it easier to deal with)
                $this->__data = (is_array($this->_ids))
                              ? $this->_ids
                              : array();
                $this->__callback = $this->_construct;

                // only force update if it should save to a volatile cache
                if ($this->_isSmemable || $this->_isMemcacheable) {
                    $this->__needsUpdate = true;
                }
            }
            // }}}
        } catch (tgif_global_exception $e) {
            trigger_error($e->getMessage());
            $this->__exception = $e;
        }
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
     *      if data has a single element, pass it directly, else wrap in in
     *      an array so it is a single parameter pass.
     * - array (more than one parameter): {@link call_user_func_array} on data
     *
     * @return mixed the actually loaded object or data or null if it failed
     *      due to exception.
     */
    function ready()
    {
        global $_TAG;
        // If we stopped, finish dispatch and clear the stopped variable {{{
        if ($this->__stopped) {
            $this->dispatch();
        }
        // }}}
        // There was a problem with the dispatch {{{
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
        // }}}
        // Run callback constructor on data {{{
        // Note: If you don't see any errors, then check to make sure
        // __autoload() doesn't have error suppression
        if ( !$this->__callback ) {
            $return = $this->__data;
        } elseif (is_array($this->__callback) && (count($this->__callback)==1)) {
            // special case, if 1 parameter, call the "new" explicitly {{{
            $constructor = $this->__callback[0];
            switch ( count($this->__data) ) {
                case 0: $return = new $constructor; break;
                case 1: $return = new $constructor($this->__data[0]); break;
                default: $return = new $constructor($this->__data); break;
            }
            // }}}
        } else {
            $return = call_user_func_array($this->__callback, $this->__data);
        }
        // clear temporary stuff because we are done with them.
        unset($this->__data);
        unset($this->__callback);
        if( empty($return) ) { return $return; }
        // }}}
        // Bind loader 
        if ($this->_loaderLoader) {
            call_user_func(array($return,$this->_loaderLoader), $this);
        }
        // Allow object to figure out if wakeup function has messed with the
        // cache and it needs updating.
        if ($this->_checkCache && call_user_func(array($return,$this->_checkCache))) {
            $this->__needsUpdate = true;
        }
        // If update, then update itself
        if ($this->__needsUpdate) {
            $this->self = $return;
            if (!$this->_manualCache) {
                if ($this->_deferCache) {
                    $_TAG->queue->subscribe('shutdown',array($this,'cacheSelf'),100,false,false);
                } elseif (!$this->_manualCache) {
                    $this->cacheSelf();
                }
            }
        }
        return $return;
    }
    // }}}
    // CACHING
    // {{{ - setToCache($data[,$deferSmem,$deferMemcache])
    /**
     * Allow cache to be updated.
     *
     * An example of how this should be used when you have loaderLoader defined
     * to add the loader to $this->_loader.
     *
     * <code>$this->_loader->setToCache($this);</code>
     *
     * @param boolean $deferSmem Skip the smem step (if defined)
     * @param boolean $deferMemcache Skip the memcache step (if defined)
     * @return boolean success or failure.
     * @todo Don't bother updating caches if nothing changed
     */
    public function setToCache($data, $deferSmem=false, $deferMemcache=false)
    {
        //global $_TAG;
        // Throw object update notification {{{
        if (is_object($data) && $_TAG->queue) {
            $_TAG->queue->publish(array('object',get_class($data),'updateCache'), array('obj'=>$data));
        }
        // }}}
        $this->self = $data;
        return $this->cacheSelf($deferSmem,$deferMemcache);
    }
    // }}}
    // {{{ - cacheSelf([$deferSmem,$deferMemcache])
    /**
     * Updates the loader to the memory caches.
     *
     * When loading, the cache may be missing from certain volatile stores,
     * let's make sure it's there and updated.
     *
     * Note this function is PUBLIC only in order to be called when
     * {@link $_deferCache} is set. Don't use this function -- you are looking
     * for setToCache($data) instead. :-)
     *
     * @param boolean $deferSmem Skip the smem step (if defined)
     * @param boolean $deferMemcache Skip the memcache step (if defined)
     * @return boolean success or failure.
     */
    public function cacheSelf($deferSmem=false, $deferMemcache=false)
    {
        //global $_TAG;
        $result = true;
        $this->__needUpdate = false;
        // save into smem {{{
        if ($this->_isSmemable && !$deferSmem) {
            $result = $result && apc_store(
                $this->smemKey(),
                $this->self,
                (int) $this->_smemcacheLifetime
            );
        }
        // }}}
        // save into memcache {{{
        if ($this->_isMemcacheable && !$deferMemcache) {
            try {
                $key = $this->memcacheKey();
                $lifetime = ($this->_memcacheLifetime===false)
                          ? -1 //use pool default
                          : $this->_memcacheLifetime;
                // if custom memcache setter is configured
                if ($this->_memcacheSet) {
                    $data = call_user_func($this->_memcacheSet, $this->self);
                } else {
                    $data = $this->self;
                }
                $result = $result && $_TAG->memcached->set($key, $data, 'global', $lifetime);
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
    // {{{ - deleteFromCache([$deferSmem,$deferMemcache])
    /**
     * Allow cache to be deleted
     *
     * Skip can occur when the loader never got the object from cache the first
     * time. That's because this can be called whether or not the object is
     * actually in cache.
     *
     * @param boolean $deferSmem Skip the smem step (if defined)
     * @param boolean $deferMemcache Skip the memcache step (if defined)
     * @return boolean success or failure.
     */
    public function deleteFromCache($deferSmem=false,$deferMemcache=false)
    {
        //global $_TAG;
        $result = true;
        // handle deleteAction {{{
        if ($this->_deleteAction) {
            $skip = false;
            // append self variable to delete action {{{
            if (is_array($this->_deleteAction) && !$this->_deleteAction[0]) {
                if ($this->self) {
                    $this->_deleteAction[0] = $this->self;
                } else {
                    $skip = true;
                }
            }
            // }}}
            if (!$skip) {
                call_user_func($this->_deleteAction);
            }
        }
        // }}}
        // delete from sMem {{{
        if ($this->_isSmemable && !$deferSmem) {
            $result = $result && apc_delete($this->smemKey());
        }
        // }}}
        // delete from memcache {{{
        if ($this->_isMemcacheable && !$deferMemcache) {
            try {
                $key = $this->memcacheKey();
                $result = $result && $_TAG->delete($key, 'global');
                $cache = $memcache_pool->getMemcache($key[0],$key[1]);
                $result = $result && $cache->delete($key[0]);
            } catch (tgif_global_exception $e) {
                // If we get a cache exception, make a note but don't otherwise
                // do anything.  We want to proceed as if caching were enabled.
                trigger_error( $e->getMessage() );
            }
        }
        // }}}
        return $result;
    }
    // }}}
}
// }}}
?>
