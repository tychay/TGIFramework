<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_global}
 *
 * @package tgiframework
 * @subpackage global
 * @copyright c.2007-2009 Tagged, Inc., c.2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// imports {{{
if (!function_exists('apc_fetch')) {
    /**
     * Need apc_* user cache functions (or emulators)
     */
    require_once LIB_FUNC_DIRD.'cache.php';
}
// }}}
// {{{ tgif_global
// comments {{{
/**
 * This contains an interface for reading and writing application globals.
 *
 * The path by which it reads application globals is from an internal registry
 * (the "global"), then the shared memory segment (apc_cache or zend_cache),
 * then from memcache, then from a data access object or other resource.
 *
 * Think of it as L1, L2 (shared memory), RAM (memcache), and disk (database)
 * Obviously, since the resource may or may not be there, the resource may not
 * be automatic, hence the special exception handling which may or may not be
 * caught internally depending on what system is used for accessing the element.
 *
 * This uses a three letter variable prefix to determine which "channel" the
 * global variable gets data (from memcache and shared memory) from. This is
 * used to prevent conflicts if two applications from the same framework are
 * running on the same machine or sharing memcache store (i.e. development).
 * This "channel" is specified on construction (getting the singleton) so
 * is set in file or by direct before the {@link preinclude.php} initializes
 * the global system.
 *
 * All channels are all pretty much treated the same except a special channel
 * for configuration globals which are treated special in order to bootstrap
 * them from the filesystem and store them into the shared memory segment.
 *
 * Similarly definitions for the global system are prefixed with a special
 * prefix ('gld_') in the configuration system. This streatement is in order
 * to boostrap from the filesystem and store them into the shared memory segment
 * (as opposed to from the "RAM" (memcache) or the "disk" (database) as neither
 * has been defined by this point.
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
    // {{{ - $_configPrefix
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
    private $_configPrefix;
    // }}}
    // {{{ - $_globals
    /**
     * The store for configuration parameters.
     *
     * Using accessors, these are accessed directly and their action is
     * transparent to the user. Note that the first three characters (followed
     * by an underscore) are special because the define the "channel" the
     * variable is stored in.
     */
    private $_globals = array();
    // }}}
    // {{{ - $_requires
    /**
     * The store for variables that need to be preloaded
     *
     * This is a hash of the {@link tgif_global_loader}s indexed by the global
     * name.
     *
     * In order to work, these globals are stored in configuration parameters.
     * To prevent a conflict, the name of the config parameter must be prefixed
     * by 'gld_' (stands for global loader data).
     */
    private $_requires = array();
    // }}}
    // CONSTRUCTOR
    // {{{ - __construct($config_prefix)
    /**
     * Set up the global object to be ready for handling of magic.
     *
     * Sets the identifier prefix for configuration parameters and autodetects
     * what cache store we have.
     *
     * It also sets the flag that determines if the config files have been read
     * into shared memory cache already. If we have a lot of files, this will
     * take a lot of time and we want to do this as rarely as possible (e.g.
     * only once per server restart unless we are on development). If you are
     * developmetn set the 'readConfig' param to false and we will rehash the
     * config on every reuest, else set it to true and we'll only do it after
     * the shared memory has been cleared (server restart et. al.).
     *
     * @param $config_prefix string The three letter code to put in front of
     *     any configuration parameter. If less than three characters are there
     *     it will pad them with "_".
     */
    private function __construct($config_prefix)
    {
        $this->_configPrefix = sprintf('%\'_3s',$config_prefix);
        $readconfig_name = $this->_configPrefix.'_readConfig';
        $this->_globals[$readconfig_name] = apc_fetch($readconfig_name);
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
     * @param $config_prefix string A three letter "channel" to use to prevent
     * conflicts with other instances of the framework or other installs of
     * the app.
     * @param $create_fresh boolean Set to true to re-initialize the global
     * system (in general, a really bad idea)
     */
    static function get_instance($config_prefix='___', $create_fresh=false)
    {
       static $single;
       if (!isset($single) || $create_fresh) {
           $single = new tgif_global($config_prefix);
       }
       return $single;
    }
    // }}}
    // {{{ + reinit()
    /**
     * Quick code to reinitialize the global system
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
    // ACCESSING: MAGIC METHODS
    // {{{ - __set($name,$value)
    /**
     * @param $name string the property to set (normal property naming rules
     * apply).
     * @param $value mixed
     * @see http://php.net/manual/en/language.oop5.overloading.php
     */
    function __set($name, $value)
    {
       $this->_globals[$name] = $value;
    }
    // }}}
    // {{{ - __get($name)
    /**
     * Magic method for property get to pull from cache as needed.
     *
     * When trying to get from a config file, it will try to pull it from shared
     * memory cache. If it's not there (determined by returning a typed false)
     * then it will {@link _loadConfigs() load all the config files}. The lesson
     * here is <em>do not store typed "false" into configuration variables</em>,
     * use 0 or something that evaluates to false instead! If you have a config
     * value that is false, then in development you might theoretically force
     * reload of the entire config system from the file system!
     *
     * In the case of all other "globals" it first goes to shared memory cache,
     *
     * @param $name string the global to get (normal property naming rules
     * apply). The first three characters are special since they define the
     * channel type used
     * @return mixed the value
     * @todo make debugger event for forcing loading of config file
     */
    function __get($name)
    {
        // if it's there, just return it. Simpler (logically) this way even
        // though it's an opcode less efficient in some cases.
        if (isset($this->_globals[$name])) { return $this->_globals[$name]; }
        // special case: configuration variable {{{
        // smem has returned false already
        if ($this->_isConfig($name)) {
            // check if stored in shared memory cache {{{
            $return = apc_fetch($name);
            if ($return !== false) {
                $this->_globals[$name] = $return;
                return $return;
            }
            // }}}
            // no need to check if $name is the readConfig flag because this is
            // stored in $_globals during object construction.
            if ($this->__get($this->_configPrefix.'_readConfig')) {
                $this->_globals[$name] = false;
                return false;
            } else {
                //printf('%s: %s forced loading of config files',getclass($this), $name);
                $this->_loadConfigs();
                if (!isset($this->_globals[$name])) { $this->_globals[$name] = false; }
                return $this->_globals[$name];
            }
        }
        // }}}
        // We want to use the variable now, so add it to the requires and load
        // it asap.
        $exists = $this->requires($name);
        // throw error if we are trying to get something that doesn't exist
        if (!$exists) {
            trigger_error(sprintf('%s: global "%s" requested but doesn\'t exist.', get_class($this), $name). E_USER_NOTICE);
            return null;
        }
        // just do one, not all of them.
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
    // PUBLIC: Accessors
    // {{{ - symbol()
    /**
     * @return string three letter config symbol
     */
    function symbol()
    {
        return $this->_configPrefix;
    }
    // }}}
    // {{{ - config($name[,$subproperty])
    /**
     * Access config parameters
     *
     * @param $name string the config parameter to get
     * @param $subproperty string Some config parameters are more like
     *     namespaces with multiple subconfigurations. If specified, then it
     *     will grab the subproperty. This allows quick access to subproperties
     *     without you having to write heinous code.
     * @return mixed configuration. This will call {@link __get()} to actually
     *      get the data.
     */
    function config($name,$subproperty=null)
    {
        $var = $this->_configPrefix.'_'.$name;
        $return = $this->$var;
        return (is_null($subproperty))
               ? $return
               : $return[$subproperty];
    }
    // }}}
    // PUBLIC: requires() system
    // {{{ - requires($varname[,...])
    /**
     * Register a global to be pulled from memory
     *
     * @param $varname string the global variable to preload.
     * @param $arguments mixed arguments to pull in
     * @return boolean successfully can require or not�
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
        $this->_requires[$varname] = $this->_getLoader($varname,$arguments);
        if (!$this->_requires[$varname]) { unset($this->_requires[$varname]); }
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
    // PUBLIC METHODS: CACHE STUFF
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
     * @param $variableName string the global variable to look for
     * @param $params array If it is a collection, these are the parameters that define it.
     * @return object|null it returns null if not in cache.
     * variable is already loaded into the global store before resorting to
     * this.
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
     * @param $variableName string the global variable to look for
     * @param $params array If it is a collection, these are the paremters that define it.
     * @return boolean success or failure
     */
    function deleteFromCache($variableName, $params=array())
    {
        //global $_TAG; //superglobal biatch!
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
     * @param $variableName string the global variable to look for
     * @param $data mixed the data to save to cache.
     * @param $params array If it is a collection, these are the paremters that
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
     * @param $variableName string the name of the global to grab
     * @param $params array() paramterization of the global in the case of
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
    // PRIVATE METHODS: LOADERS
    // {{{ - adminGetLoader($variableName,$arguments)
    /**
     * Helper function for showglobals admin tool.
     *
     * @param $variableName string the global variable to look for
     * @param $arguments array If it is a collection, these are the paremters
     *  that define it.
     * @return tgif_global_object|false
     */
    function adminGetLoader($variableName, $arguments)
    {
         return $this->_getLoader($variableName,$arguments,true);
    }
    // }}}
    // {{{ - _getLoader($variableName,$arguments)
    /**
     * @param $variableName string the global variable to look for
     * @param $arguments array If it is a collection, these are the paremters that define it.
     * @return tgif_global_object|false
     */
    private function _getLoader($variableName, $arguments, $noCollections=false)
    {
        $params = $this->config('gld_'.$variableName);
        //var_dump(array($this->_globals,$variableName,$params));
        if (!$params) { return false; }
        $params['name']          = $variableName;
        $params['configPrefix']  = $this->_configPrefix;
        if ($noCollections) {
            $params['ids'] = $arguments;
            $params['params'] = 0;
            $arguments = array();
        }
        return tgif_global_object::get_loader($params, $arguments);
    }
    // }}}
    // PRIVATE: CONFIG ACCESSORS
    // {{{ - _isConfig($name)
    /**
     * Returns whether the parameter is a configurtion parameter
     *
     * @param $name string
     * @return boolean
     */
    private function _isConfig($name)
    {
        return (strcmp($this->_configPrefix, substr($name,0,3)) === 0);
    }
    // }}}
    // {{{ - _loadConfigs()
    /**
     * Read all the configuration files and save them into the local
     * configuration and into shared memory.
     *
     * The load order is set to the framework config and then any specified
     * in the {@link TGIF_CONF_PATH} symbol (":" separated).
     *
     * For instance, for tagged, TGIF_CONF_PATH would be:
     * LIB_CONF_DIR.'/common:/home/html/tagconfig:'.LIB_CONF_DIR.'/local:/etc/tagconfig-local'
     * This is because
     * 1) <LIB_CONF_DIR>/common: The application configuration common directory
     * 2) /home/html/tagconfig: nfsmount'd PROD, STAGE, or DEV config
     *      settings
     * 3) <LIB_CONF_DIR>/local: local dev checkout override
     * 4) /etc/tagconfig-local: PROD per machine overrride (life site debugging)
     *
     * This does some simple magic config file replacement where it will macro
     * expand {{{config_param}}} automagically. Note that it currently won't
     * introspect the array.
     *
     * @todo introspect 1 level of array in config
     */
    private function _loadConfigs()
    {
        //echo "called _loadConfigs()\n";
        $configs = array();
        $config_dirs = explode(':',TGIF_DIR.DIRECTORY_SEPARATOR.'conf'.':'.TGIF_CONF_PATH);
        //var_dump($config_dirs);
        foreach ($config_dirs as $config_dir) {
            $this->_loadConfigDir($config_dir, $configs);
        }
        // macro expansion {{{
        $this->_temps = $configs;
        $count = 0;
        foreach ($configs as $key=>$value) {
            if (!is_string($value)) { continue; } //on check top level
            do { //handle nesting of macros
             $value = preg_replace_callback(
                     '/{{{(\w*)}}}/',
                     array($this,'config_replace'),
                     $value, -1, $count);
            } while ($count > 0);
            $configs[$key] = $value;
        }
        unset($this->_temps);
        // }}}
        foreach ($configs as $key=>$value) {
            $name = $this->_configPrefix.'_'.$key;
            $this->_globals[$name] = $value;
            apc_store($name, $value, 0);
        }
    }
    // }}}
    // {{{ - config_replace($matches)
    /**
     * preg_replace_callback() for doing variable replacement in the config
     * system.
     *
     * Do not call from the outside. This is called internally but needs to be
     * public because it is called from callback.
     */
    public function config_replace($matches)
    {
        if (key_exists($matches[1],$this->_temps)) {
            return $this->_temps[$matches[1]];
        }
        throw new Exception(sprintf('Unknown config parameter %s via %s.',$matches[1],$matches[0]));
    }
    // }}}
    // {{{ - _loadConfigDir($dir,$configs)
    /**
     * Load all the configurations in a directory overriding all parameters that
     * have already been written to.
     *
     * @param $dir string the directory to load from
     * @param $configs array the array to load configs into
     * @todo consider reading through the nesting of arrays.
     */
    private function _loadConfigDir($dir, &$configs)
    {
        if (!is_dir($dir)) { return; }
        foreach (new DirectoryIterator($dir) as $item) {
            $file_data = $this->_readConfigFile($item);
            if (!is_array($file_data)) { trigger_error(sprintf('%s::_loadConfigDir(): Config file %s is improperly formatted',get_class($this), $item), E_USER_ERROR); }
            $configs = array_merge($configs, $file_data);
        }
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
     *     is the name of a file and loads a directory iterator on it.
     * @return array
     */
    private function _readConfigFile($file)
    {
        if (is_string($file)) { $file = new DirectoryIterator($file); }
        if (!$file->isFile()) { return array(); }
        $parts = explode('.',$file->getFilename());

        // only parse the file if it's not a hidden file (begins with '.', thus the first part is blank)
        if ($parts[0] != '') {
            $extension = $parts[count($parts)-1];
            switch ($extension) {
                case 'disabled' : return array();
                case 'php': return include($file->getPathname());
                case 'ini': parse_ini_file($file->getPathname());
            }
        }
        return array();
    }
    // }}}
}
// }}}
?>
