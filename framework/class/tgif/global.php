<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_global}
 *
 * @package tgiframework
 * @subpackage global
 * @copyright c.2007-2009 Tagged, Inc., c.2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 * @todo the requires/dispatch() system may be broken
 * @todo setToCache, deleteFromCache, getObjectIfInCache may or may not be set
 */
// imports {{{
if (!function_exists('apc_fetch')) {
    /**
     * Need apc_* user cache functions (or emulators)
     */
    require_once TGIF_FUNC_DIRD.'cache.php';
}
// }}}
// {{{ tgif_global
// comments {{{
/**
 * This contains an interface for reading and writing application globals
 * (and configurations).
 *
 * Application globals are found through a superglobal (via runkit) like so
 * <code>$_TAG->global_variable_name</code>
 * $_TAG stands for "TGIFramework Application Globals." :-)
 *
 * When an application global is accessed, it looks for the global in the
 * following order:
 *
 * 1. defined in an internal property
 * 2. in a shared memory segment (apc_cache or zend_cache) if configured
 * 3. in a memcached pool if configured
 * 4. from a persistent data store (database or other resoure) via a
 *    construction function.
 *
 * Think of it as L1, L2 (shared memory), RAM (memcache), and disk (database)
 * Obviously, since the resource may or may not be there, the resource may not
 * be automatic, hence the special exception handling which may or may not be
 * caught internally depending on what system is used for accessing the element.
 *
 * <b>The symbol prefix</b>
 *
 * A special three letter variable prefix is defined by the instance of the
 * application and is passed to the constructor when the {@link $_TAG this
 * application global system ($TAG)}. This symbol, in turn, is set in a file
 * or directly before the {@link preinclude.php} initializes the global system.
 *
 * What the prefix does is serve as a "channel" to prevent this application
 * instance from conflicting with others on a shared resources. For instance,
 * two different applications instances (or applications built with the $TGIF
 * framework) will not conflict in shared memory segments, or variable
 * collision will not occur unless desired on a shared memcached, etc. In other
 * words, this prefix is prepended to all keys in shared memory and memcached,
 * so unless two copies share the same symbol, it will be like two ships
 * passing in the night.
 *
 * <b>The Configuration system</b>
 *
 * Configuration variables are loaded as necessary from all php files in
 * predefined directories and saved to shared memory. They can be accessed
 * from the application global as:
 * <code>$_TAG->config('configuration_constant_name');</code>
 *
 * Note that for ease of use, there is a special "reserved" char in the constant
 * name. That is ".". Instead of being part of the name, this is a separator
 * to array parts of a configuration. This allows later config files to modify
 * parts of a config space without overwriting the entire config space. It is
 * also used access another part of the configuraiton from the configuration
 * during macro expansion. For instance {{{config_space.name}}} refers to
 * the configuration. $configs['config_space']['name']. You access this like
 * so
 * <code>$_TAG->config('config_space.name', true);</code>
 *
 * Parsing is done at the beginning of every request at first access, unless a
 * special configuration variable {@link tgif_global::_READ_CONFIG
 * '_readConfig'} is set. In that case, requests know to not do any file
 * reading/parsing and depend only on the value stored in shared memory. This
 * allows you to have a quite intricate configuration system -- lots of
 * directories, files, and macros -- without affecting performance in
 * production.
 *
 * In development, you most likely want this _readConfig to be unset. If you
 * don't than changes to the configuration will not be reflected unless the
 * server is restarted or the configuration system cleared. The latter can be
 * done using the following command:
 * <code>$_TAG->reloadConfig();</code>
 *
 * Remember, when saved to shared memory space, the symbol prefix is prepended
 * so changing the symbol will effectively mean your sandbox (even on the same
 * machine will not share configurations). In order to prevent conflict with
 * any globals, a " " is prepended to the key.
 *
 * Note that the configuration cannot be stored in memcached because {@link 
 * tag_memcached the memcache system} itself configured with the configuration
 * (chicken-egg).
 *
 * <b>Global variable configuration</b>
 *
 * Definitions configurating global variables are prefixed in configuration
 * files with a special prefix ('gld_'). This treatement is in order to know
 * which configurations define variables, and which ones are just configurations
 * to be accessed with {@link tgif::config() $_TAG->config()}.
 *
 * "gld" stands for Global Loader Data. In the original version, due to
 * conflicts in sharing variabel space, no prefix symbol was allowed to use
 * the "gld". This is no longer true, but please don't try this at home (or
 * in production) as this edge case is untested.
 *
 * <b>Violations</b>
 *
 * The purists among you may notice {@link config() configuration constants}
 * are handled differently than other globals in the system. That is technically
 * a violation is single responsibility principle, but I like the way it works
 * as the only reason I violate it is because of the bootstrapping probem. I
 * don't feel the violation should be too bad.
 *
 * In many ways this object is already too clever for it's own good (PHP5
 * specific). It uses Overloading to make accessing members transparent and
 * "intuitive." It reads configurations via the directory iterator and can
 * detect different file types within the directory. I apologize for the
 * over cleverness of this package.
 *
 * @package tgiframework
 * @subpackage global
 */
// }}}
class tgif_global
{
    // {{{ + _READ_CONFIG
    /**
     * The name of the READ CONFIG variable
     */
    const _READ_CONFIG = '_readConfig';
    // }}}
    // {{{ - $_prefix
    /**
     * Three letter prefix for config variables.
     *
     * Because of bootstrapping rules and location of resources, globals
     * preceded by configuration parameters are treated differently. This will
     * make sure different checkouts will not use overlapping names in shared
     * structures (e.g. shared memory and memcache).
     *
     * @var string
     */
    private $_prefix;
    // }}}
    // {{{ - $_configs
    /**
     * The store for configuration parameters.
     *
     * Where the configuration is locally cached (so subsequent requests do not
     * go to shared memory or don't force config file rereading). This is
     * indexed by the name.
     */
    private $_configs = array();
    // }}}
    // {{{ - $_globals
    /**
     * The store for global variables
     *
     * Using overloading, these can be accessed directly and their action is
     * transparent to the user.
     */
    private $_globals = array();
    // }}}
    // {{{ - $_requires
    /**
     * The store for variables that are in the process of loading
     *
     * This is a hash of the {@link tgif_global_loader}s indexed by the global
     * name. This contains loaders that have been passed the global loader data
     * 'gld_' parameters.
     */
    private $_requires = array();
    // }}}
    // CONSTRUCTOR
    // {{{ - __construct($config_prefix)
    /**
     * Set up the global object to be ready for handling of magic.
     *
     * Sets the identifier symbol for configuration parameters.
     *
     * It also sets the flag that determines if the config files have been read
     * into shared memory cache already. If we have a lot of files, reading the
     * configuration (especially if complex) will take a lot of time and we
     * want to do this as rarely as possible (e.g. only once per server restart
     * unless we are on development). If you are developmet set the
     * {@link tgif_global::_READ_CONFIG _readConfig config} to false (or don't
     * set it at all) and we will rehash the config on every reuse. Or set it
     * to true and we'll only do it after the shared memory has been cleared
     * (server restart et. al.) or an explicit request is made.
     *
     * @param string $config_prefix The three letter code to put in front of
     *     any key that read or writes to shared memory/memcached. If less
     *     than three characters are there it will pad them with "_".
     */
    private function __construct($config_prefix)
    {
        $this->_prefix = sprintf('%\'_3s',$config_prefix);
        // note: apc_fetch returns false on failure to read (by default)
        $this->_configs[self::_READ_CONFIG] = apc_fetch(' '.$this->_prefix.self::_READ_CONFIG);
    }
    // }}}
    // {{{ + get_instance([$config_prefix,$create_Fresh])
    /**
     * Singleton for {@link tgif_global}
     *
     * This is just here to enforce the singleton instances and make future
     * calling for me easier. For performance reasons, you should call this once
     * and save it into the PHP globals as {@link $_TAG}. This is done
     * automatically by {@link preinclude.php}.
     *
     * Note that other parts of the application will assume that this is stored
     * in a global called {@link $_TAG} so be forewarned! In fact, tgiframework
     * assumes that $_TAG is a superglobal (via runkit)!
     *
     * @param string $prefix_symbol A three letter "channel" to use to prevent
     * conflicts with other instances of the framework or other installs of
     * the app.
     * @param boolean $create_fresh Set to true to re-initialize the global
     * system (in general, a really bad idea)
     * @return tgif_global singleton instance
     */
    static function get_instance($prefix_symbol='___', $create_fresh=false)
    {
       static $single;
       if (!isset($single) || $create_fresh) {
           $single = new tgif_global($prefix_symbol);
       }
       return $single;
    }
    // }}}
    // TODO: {{{ + reinit()
    /**
     * Quick code to reinitialize the global system
     * @author Mark Jen <markjen@tagged.com>
     * @author terry chay <tychay@tagged.com> reinit the queue shutdown function
     */
    public static function reinit()
    {
        $symbol = $_TAG->symbol();
        $_TAG = self::get_instance($symbol, true);
        //$GLOBALS['_TAG'] = $_TAG; // superglobal biatch!
        $_TAG->queue = new tgif_queue();
        register_shutdown_function(array($_TAG->queue,'publish'),'shutdown');
    }
    // }}}
    // PUBLIC: SYMBOL
    // {{{ - symbol()
    /**
     * @return string three letter config symbol
     */
    function symbol()
    {
        return $this->_prefix;
    }
    // }}}
    // GLOBALS: magic methods
    // {{{ - __set($name,$value)
    /**
     * Allows you to externally set a global :-)
     *
     * You might think it woul be best to allow onfigurations for the
     * '_loaderLoader' and 'cacheUpdateOnSet' assignements. However
     * since this is unparameterized, there is only one way to access
     * this variable. If you are explicitly setting this variable,
     * it doesn't make sense to implicit set also. Therefore support
     * for the two parameters is skipped.
     *
     * @param string $name the property to set (normal property naming rules
     * apply).
     * @param mixed $value
     * @see http://php.net/manual/en/language.oop5.overloading.php
     */
    function __set($name, $value)
    {
       $this->_globals[$name] = $value;
    }
    // }}}
    // {{{ - __get($name)
    /**
     * Magic method for global variable get to pull from cache as needed.
     *
     *
     * In the case of all other "globals" it first goes to shared memory cache,
     *
     * @param string $name the global to get (normal property naming rules
     * apply). The first three characters are special since they define the
     * channel type used
     * @return mixed the value
     */
    function __get($name)
    {
        // if it's there, just return it. Simpler (logically) this way even
        // though it's an opcode less efficient in some cases.
        if (isset($this->_globals[$name])) { return $this->_globals[$name]; }

        // We want to use the variable now, so add it to the requires and load
        // it asap.
        $exists = $this->requires($name);
        // throw error if we are trying to get something that doesn't exist
        if (!$exists) {
            trigger_error(sprintf('%s: global "%s" requested but doesn\'t exist.', get_class($this), $name). E_USER_NOTICE);
            return null;
        }

        // just load this one, not all of them.
        $this->_requires[$name]->dispatch();
        $this->_globals[$name] = $this->_requires[$name]->ready();
        unset($this->_requires[$name]); // don't load twice.

        if (isset($this->_globals[$name])) { return $this->_globals[$name]; }
        // throw an error if we loaded it but it somehow isn't there!
        trigger_error(sprintf('%s: global "%s" requested but failed to load.', get_class($this), $name). E_USER_NOTICE);
        return null;
    }
    // }}}
    // {{{ - __isset($name)
    /**
     * Magic method for isset.
     *
     * This is a little weird because isset may return false even though when
     * you access it directly, it'll be there! It just means has it has already
     * been loaded into the internal global.
     */
    function __isset($name)
    {
       return isset($this->_globals[$name]);
    }
    // }}}
    // {{{ - __unset($name)
    /**
     * Handle unsetting of parameters.
     *
     * Note that after unsetting, you still may be able to to get the global
     * because it will restart/reconstruct it.
     */
    function __unset($name)
    {
        unset($this->_globals[$name]);
    }
    // }}}
    // GLOBALS: requires/dispatch system
    // {{{ - requires($varname[,...])
    /**
     * Register a global to be pulled from memory
     *
     * @param string $varname the global variable to preload.
     * @param mixed $arguments arguments to pull in
     * @return boolean successfully can require or not?
     */
    function requires($varname)
    {
        $arguments = func_get_args(); array_shift($arguments);
        // don't add it if we already have it...
        if (isset($this->_globals[$varname])
         || isset($this->_requires[$varname])) {
            // TODO: handle second requires() here.
            return true;
        }
        $loader = $this->_getLoader($varname,$arguments);
        if (!$loader) { return false; }
        $this->_requires[$varname] = $loader;
        return true;
    }
    // }}}
    // {{{ - dispatch()
    /**
     *
     */
    function dispatch()
    {
        foreach ($this->_requires as $loader) {
            $loader->dispatch();
        }
        //$this->dispatchMemcache();
        //$this->dispatchDatabase();
        /* */
    }
    // }}}
    // {{{ - ready()
    /**
     *
     */
    function ready()
    {
        foreach ($this->_requires as $varname=>$loader)
        {
            $this->_globals[$varname] = $loader->ready();
            // don't load it twice.
            unset($this->_requires[$varname]);
        }
    }
    // }}}
    // GLOBALS:: LOADERS
    // {{{ - adminGetLoader($variableName,$arguments)
    /**
     * Helper function for showglobals admin tool.
     *
     * @param string $variableName the global variable to look for
     * @param array $arguments If it is a collection, these are the paremters
     *  that define it.
     * @return tgif_global_object|false
     * @author Rahul Caprihan <rahulcap@gmail.com>
     */
    function adminGetLoader($variableName, $arguments)
    {
         return $this->_getLoader($variableName,$arguments,true);
    }
    // }}}
    // {{{ - _getLoader($variableName,$arguments)
    /**
     * @param string $variableName the global variable to look for
     * @param array $arguments If it is a collection, these are the paremters
     * that define it.
     * @param boolean $noCollections ???
     * @return tgif_global_object|false
     */
    private function _getLoader($variableName, $arguments, $noCollections=false)
    {
        $params = $this->config('gld_'.$variableName);
        //var_dump(array($variableName,$params));
        if (!$params) { return false; }
        $params['name']          = $variableName;
        $params['configPrefix']  = $this->_prefix;
        if ($noCollections) {
            $params['ids'] = $arguments;
            $params['params'] = 0;
            $arguments = array();
        }
        return tgif_global_object::get_loader($params, $arguments);
    }
    // }}}
    // GLOBALS: CACHE STUFF
    // {{{ - getObjectIfInCache($variableName[,$params])
    /**
     * This returns an object IFF it is in a smem or memcache.
     *
     * This feature may not seem very useful since global objects are already
     * properties and are automatically instantiated, but the time it is used
     * is when you have a event handler necessary to keep complementary cache
     * objects in sync, but don't want to force a memcache write unless it is
     * already there!
     *
     * @param string $variableName the global variable to look for
     * @param array $params If it is a collection, these are the parameters that define it.
     * @return object|null it returns null if not in cache.
     * variable is already loaded into the global store before resorting to
     * this.
     * @author Mark Jen <markjen@tagged.com> added getFromLocalCache() call
     */
    function getObjectIfInCache($variableName, $params=array())
    {
        if ($localObj = $this->_getFromLocalCache($variableName, $params)) {
            return $localObj;
        }

        // we didn't find the object in the static local registry, so we need
        // to check memcache
        $loader_obj = $this->_getLoader($variableName,$params,true);
        // load up to memcache
        $loader_obj->dispatch('memcache');
        if ($loader_obj->canConstruct()) {
            return $loader_obj->ready();
        } else {
            return null;
        }
    }
    // }}}
    // {{{ - deleteFromCache($variableName[,$params])
    /**
     * Clear a global from memcache and smemcache.
     *
     * It is best to avoid this and use the loadLoader property to allow the
     * object to maintain itself as this is not so fast.
     *
     * @param string $variableName the global variable to look for
     * @param array $params If it is a collection, these are the paremters
     * that define it.
     * @return boolean success or failure
     * @author Mark Jen <markjen@tagged.com>
     */
    function deleteFromCache($variableName, $params=array())
    {
        global $_TAG; //superglobal biatch!
        if (isset($this->_globals[$variableName])) {
            // TODO: make this smarter by only clearing the one object specified by $params
            unset($this->_globals[$variableName]);
        }

        $loaderobj = $this->_getLoader($variableName,$params,true);
        $_TAG->queue->publish(sprintf('cache %s delete', $variableName), array('params'=>$params));
        return $loaderobj->deleteFromCache();
    }
    // }}}
    // {{{ - setToCache($variableName,$data[,$params])
    /**
     * Update a global in memcache and smemcache.
     *
     * It is best to avoid this and use the loadLoader property to allow the
     * object to maintain itself as this is not so fast.
     *
     * @param string $variableName the global variable to look for
     * @param mixed $data the data to save to cache.
     * @param array $params If it is a collection, these are the paremters that
     *      define it.
     * @return boolean success or failure
     */
    function setToCache($variableName, $data, $params=array())
    {
        $loaderobj = $this->_getLoader($variableName,$params,true);
        return $loaderobj->setToCache($data);
    }
    // }}}
    // {{{ - _getFromLocalCache($variableName,$params)
    /**
     * Attempt to grab somehting if it's already in the global registry
     * @param string $variableName the name of the global to grab
     * @param array $params paramterization of the global in the case of
     *  a colletction
     */
    private function _getFromLocalCache($variableName, $params)
    {
        // see if we can find it in our current static registry
        if (isset($this->_globals[$variableName])) {
            // grab what's in the global registry for the variable we're looking for
            $requestedObject = $this->_globals[$variableName];

            // use the parameters to drill down into the tgif_global_collection
            foreach ($params as $param) {
                // only drill down if it's a collection. otherwise exit
                if ('tgif_global_collection' == get_class($requestedObject) &&
                    $requestedObject->offsetExists($param)) {
                    $requestedObject = $requestedObject[$param];
                } else {
                    break;
                }
            }

            // if we found an object at the end of the param list that's
            // neither a global collection or loader, then we've found what
            // we're looking for in the local static registry
            if ('tgif_global_collection' != get_class($requestedObject) &&
                'tgif_global_loader' != get_class($requestedObject)) {
                return $requestedObject;
            }
        }
        return false;
    }
    // }}}
    // CONFIGURATION
    // {{{ - config($name[,$accessSubproperty])
    /**
     * Access config parameters
     *
     * Some config parameters are more like namespaces with multiple
     * subconfigurations. This can return those instead of the whole space.
     *
     * When trying to get from a config file, it will try to pull it from shared
     * memory cache. If it's not there, it will {@link _loadConfigs() load all
     * the config files} if {@link tgif_global::_READ_CONFIG _readConfig} isn't
     * set. Note that the action, {@link _loadConfigs()} will always set
     * _readConfig to true in the variable space but not save that to the shared
     * memory cache. This prevents _loadConfigs() from being called multiple
     * times in the same request.
     *
     * The Tagged version of this code would reload config files on any "false'
     * received (unless _readConfig was set).
     *
     * @param string $name the config parameter to get. If an empty string is
     * provided, it will return all config variables currently loaded.
     * @param boolean $accessSubProperty If true, then it will parses name for
     *  subproperties. For performance reasons, this defaults to off.
     * @return mixed configuration. This will call {@link __get()} to actually
     *      get the data.
     */
    function config($name='',$accessSubproperty=false)
    {
        if (empty($name)) { return $this->_configs; }
        // high performance access
        if ( !$accessSubproperty ) { 
            return $this->_getConfig($name);
        }

        $name_parts = explode('.',$name);
        $config = $this->_getConfig($name_parts[0]);
        unset($name_parts[0]);
        return self::_get_from_array( $config, $name_parts );
    }
    // }}}
    // {{{ - reloadConfig()
    /**
     * Force a reload of the configuration files (clears all parameters)
     */
    public function reloadConfig()
    {
        $this->_configs = array(
            self::_READ_CONFIG  => false
        );
        // stale all elements in the cache (concurrent processes)
        apc_delete(' '.$this->_prefix.self::_READ_CONFIG);
        $this->_loadConfigs(); //overwrite everything locally
    }
    // }}}
    // {{{ + _get_from_array($config_array,$keys)
    /**
     * Run down the heirarchy of $config_array looking for the element defined
     * by $keys
     *
     * This should not be used externally, it is only public so that an
     * anonymous function on a different scope can access it.
     *
     * @param array $config_array the array to be seached
     * @param array $keys the hiearchy of keys to drill down.
     * @return mixed the element of array. If not found, it returns false.
     */
    static function _get_from_array($config_array, $keys)
    {
        if (!is_array($keys) || count($keys) == 0) {
            return $config_array;
        }
        foreach ($keys as $key) {
            if ( !isset($config_array[$key]) ) {
                return false;
            }
            $config_array = $config_array[$key];
        }
        return $config_array;
    }
    // }}}
    // {{{ - _getConfig($name)
    /**
     * Loads the config variable into the property from wherever it may be
     * stored.
     *
     * Note that in some cases, this may force a parse of the entire configs
     * system.
     *
     * @param string $name Must be a string. This is the config variable to
     *  find.
     * @return mixed The configuration parameter. If not found, it returns
     *  false.
     */
    private function _getConfig($name)
    {
        if ( isset($this->_configs[$name]) ) {
            return $this->_configs[$name];
        }
        // check shared memory cache {{{
        $return = apc_fetch(' '.$this->_prefix.$name, $success);
        if ( $success ) {
            $this->_configs[$name] = $return;
            return $return;
        }
        // }}}

        // If this far, it is not be in cache.
        // Case: _readConfig set {{{
        // Remember: if $name = readConfig. this is already stored in $_configs
        // during object construction.
        if ( $this->_configs[self::_READ_CONFIG] ) {
            // no config exists, return false
            $this->_configs[$name] = false;
            return false;
        }
        // }}}
        //trigger_error(sprintf('%s::_getConfig: %s forced loading of config files',get_class($this), $name), E_USER_NOTICE);
        $this->_loadConfigs();
        if (isset($this->_configs[$name])) {
            $return = $this->_configs[$name];
        } else {
            $this->_configs[$name] = false;
            $return = false;
        }
        return $return;
    }
    // }}}
    // {{{ - _loadConfigs()
    /**
     * Read all the configuration files, parse them, and save them into the
     * local configuration and into shared memory.
     *
     * The directory load order is set to the framework config and then any
     * specified in the {@link TGIF_CONF_PATH} define (":", PATH_SEPARATOR
     * separated).
     *
     * For instance, for Tagged, TGIF_CONF_PATH was something like:
     * <code>
     * define('TGIF_CONF_PATH', LIB_CONF_DIR.'/common:/home/html/tagconfig:'.LIB_CONF_DIR.'/local:/etc/tagconfig-local');
     * </code>
     *
     * This is because:
     * 1. <LIB_CONF_DIR>/common: The application configuration common directory
     * 2. /home/html/tagconfig: nfsmount'd PROD, STAGE, or DEV config
     *      settings
     * 3. <LIB_CONF_DIR>/local: local dev checkout override
     * 4. /etc/tagconfig-local: PROD per machine overrride (live site debugging)
     *
     * This does some magic config file replacement where it will macro expand
     * {{{config_param}}} automagically. Note that it currently won't
     * introspect the array. If you need to do that, then use the "." key to
     * access and override a specific part of the configuration in the setting.
     * If you need to nest, you may use the "." key here.
     *
     * Macro expansion uses an {@link http://docs.php.net/functions.anonymous
     * anonymous function. This requires PHP 5.3.
     *
     * Note that there are two very special config parameters:
     * - {@link tgif_global::_READ_CONFIG _readConfig}: set to true to prevent
     *   the config files from getting reparsed
     * - configFiles: contains a list of config files that have been parsed
     *   to generate config
     */
    private function _loadConfigs()
    {
        $configs = array();
        $filelist = array();
        // place the framework configuration at the beginning of the path always
        $config_dirs = explode(PATH_SEPARATOR, TGIF_DIR.DIRECTORY_SEPARATOR.'conf'.PATH_SEPARATOR.TGIF_CONF_PATH);

        foreach ($config_dirs as $config_dir) {
            $this->_loadConfigDir($config_dir, $configs, $filelist);
        }
        
        if (defined('TEST_ENV') && TEST_ENV && defined('TGIF_TEST_GLOBAL_DIR')) {
            $this->_loadConfigDir(TGIF_TEST_GLOBAL_DIR, $configs, $filelist); // testing
        }
        
        // After everything has been loaded, expand the macros {{{
        //temporary variable to store unprocessed config
        do {
            $num_expansions = $this->_expandMacros($configs,$configs);
        } while ( $num_expansions !== 0 );
        // }}}

        // set "configFiles" configuration variable
        $configs['configFiles'] = $filelist;

        // store configs in and in global space (and smem) {{{
        foreach ($configs as $key=>$value) {
            $this->_configs[$key] = $value;
            apc_store(' '.$this->_prefix.$key, $value, 0);
        }
        // }}}
        // add "_readConfig" in the local cache after storing so that we don't
        // _loadConfigs() (reparase) on every missing configuration variable.
        $this->_configs[self::_READ_CONFIG] = true;
    }
    // }}}
    // {{{ - _expandMacros(&$configs, &$current)
    /**
     * Handle a single pass of macro expansion.
     *
     * Unlike older versions, this will drill further down than the top level.
     *
     * @param array $configs the root level element
     * @param mixed $current the current level element being examined for
     *   macros.
     * @return integer number of macros replace
     */
    private function _expandMacros(&$configs, &$current)
    {
        $count = 0;
        if ( is_array($current) ) {
            foreach ($current as &$value) {
                $count += $this->_expandMacros($configs, $value);
            }
        } elseif ( is_string($current) ) {
            // macro expansion callback {{{
            /**
             * expands macros
             * @todo handle nesting in key value
             */
            $callback = function($matches) use (&$configs)
            {
                if ( strpos($matches[1],'.') === false ) {
                    if (key_exists($matches[1],$configs)) {
                        return $configs[$matches[1]];
                    }
                    throw new Exception(sprintf('Unknown config parameter %s via %s.',$matches[1],$matches[0]));
                    return '---'.$matches[1].'---';
                } else {
                    $parts = explode('.',$matches[1]);
                    $value = tgif_global::_get_from_array($configs, $parts);
                    if ( $value !== false ) {
                        return $value;
                    }
                    throw new Exception(sprintf('Unknown config parameter %s via %s.',$matches[1],$matches[0]));
                    return '---'.$matches[1].'---';
                }
            };
            // }}}
            $current = preg_replace_callback(
                '/{{{([\w.]+)}}}/',
                $callback,
                $current,
                -1, //default value
                $new_count
            );
            $count += $new_count;
        }
        // integers, objects etc are not introspected and do not increment
        // counter.
        return $count;
    }
    // }}}
    // {{{ - _loadConfigDir($dir,$configs,$files)
    /**
     * Load all the configurations in a directory overriding all parameters that
     * have already been written to.
     *
     * Remember that "." in root level names are a special case as it implies
     * an overwrite of a subconfiguration parameter.
     *
     * Warning: it turns out PHP 5.3 breaks b.c. with DirectoryIterator
     * on some file systems (Parallels Filesystem). To get around do not use
     * these filesystems and instead NFS mount (or copy the files).
     *
     * @param string $dir the directory to load from
     * @param array $configs the array to load configs into
     * @return array a list of file names that were processed
     * @author terry chay <tychay@php.net>
     * @author Joshual Ball (added warning if configs overlap in same directory)
     */
    private function _loadConfigDir($dir, &$configs, &$files)
    {
        if (!is_dir($dir)) { return; }
        $dir_configs = array();
        foreach (new DirectoryIterator($dir) as $item) {
        //$iterator = new DirectoryIterator($dir);
        //foreach ($iterator as $item) {
            $file_data = $this->_readConfigFile($item);
            if (!is_array($file_data)) {
                trigger_error(sprintf('%s::_loadConfigDir(): Config file %s is improperly formatted',get_class($this), $item), E_USER_ERROR);
            } elseif($item) {
                // file sucessfully recognized and read (else null)
                $files[] = $item->getPathname();
            }
            $overlap = array_intersect_key($dir_configs, $file_data);
            if (count($overlap) > 0) {
                trigger_error(sprintf('%s::_loadConfigDir(): Config file %s has overlapping keys %s',get_class($this), $item, var_export(array_keys($overlap), true)), E_USER_ERROR);
            }
            $dir_configs = array_merge($dir_configs, $file_data);
        }
        $configs = array_merge($configs, $dir_configs);
        // "." root level names case {{{
        $subkeys = array_keys($configs);
        foreach ($subkeys as $search_key) {
            if ( strpos($search_key, '.') !== false) {
                $key_parts      = explode('.',$search_key);
                $found          = true;
                $config         = &$configs;
                foreach ($key_parts as $key) {
                    // create a config if it's not there
                    if ( !isset($config[$key]) ) {
                        $config[$key] = array();
                    }
                    $config =& $config[$key];
                }
                if ( $found ) {
                    // replace value, never merge here.
                    $config = $configs[$search_key];
                }
                unset($configs[$search_key]);
            }
        }
        // }}}
    }
    // }}}
    // {{{ - _readConfigFile($file)
    /**
     * Read all the parameters in a config file.
     *
     * I created this as a separate file because I think that this might have
     * a very general usage. Consider making this a public file later.
     *
     * Note that the extension is used to determine the file format.
     *
     * This supports the following filetypes
     * - .php = php free energy
     * - .ini = ini configuration files (no processing of sections)
     *
     * @param DirectoryIterator|string If it is a string, it assumes this
     *     is the name of a file and loads a directory iterator on it. After
     *     running, this will be transformed it into a DirectoryIterator. If
     *     not parsed, this will return a null
     * @return array
     */
    private function _readConfigFile(&$file)
    {
        if (is_string($file)) { $file = new DirectoryIterator($file); }
        if (!$file->isFile()) { $file=null; return array(); }
        $parts = explode('.',$file->getFilename());

        // Only parse the file if it's not a hidden file (begins with '.',
        // thus the first part is blank)
        if ($parts[0] != '') {
            $extension = $parts[count($parts)-1];
            switch ($extension) {
                case 'disabled' : $file=null; return array();
                case 'php': return include($file->getPathname());
                case 'ini': parse_ini_file($file->getPathname());
            }
        }
        // failed to recognize extension
        $file = null;
        return array();
    }
    // }}}
}
// }}}
?>
