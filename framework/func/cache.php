<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Shared memory cache voodoo.
 *
 * Basically this creates a uniform api that mimics the apc shared memory
 * variable cache.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007-2009 Tagged, Inc. <http://www.tagged.com/>, 2009 terry chay <tychay@php.net>
 * @author terry chay <tychay@tagged.com>
 */
if (function_exists('apc_fetch')) {
    //echo "apc\n";
    // apc cache available {{{
    /*
     * Get key shared memory cache!
     *
     * If caching is not available, this cleanly returns false. :-)
     *
     * Note because of wierdness, you cannot store "false" into cache. Don't try
     * it'll be ugly.
     *
     * @param $key string The variable to get from cache
     * @return mixed the stored variable, it returns false on failure.
     */
    function apc_fetch($key) { return apc_fetch($key); }
    /*
     * Cache a variable to the shared memory cache
     *
     * If caching is not available, this does nothing.
     *
     * @param $key string the key to the variable in shared memory
     * @param $var mixed the variabel to store
     * @param $ttl in seconds
     * @return boolean success or failure
     */
    //function apc_store($key, $value, $ttl) { return apc_store($key, $value, $ttl); }
    /*
     * Remove a variable from cache.
     *
     * If caching is not available, this does nothing.
     *
     * @param $key string the key to the variable in shared memory
     * @return boolean success or failure
     */
    //function apc_delete($key) { return apc_delete($key); }
    /*
     * Clear entire shared memory class.
     *
     * If caching is not available, this does nothing, but returns true.
     * @return boolean success or failure. For instance, Zend cache cannot
     * be cleared without a server restart.
     */
    //function apc_clear() { return apc_clear_cache('user'); }
    // }}}
} elseif (function_exists('output_cache_get')) {
    //echo "zend\n";
    //zend cache is segfaulting dev, may be unreliable. Someone please help!
    // zend cache available {{{
    function apc_fetch($key)
    {
        // Bug: Zend didn't document {@link output_cache_get()}. The second
        // parameter is a time to live. So I'm setting it to zero.
        return output_cache_get($key,0);
    }
    function apc_store($key,$value,$ttl)
    {
        //$ttl is not implemented
        return output_cache_put($key,$value);
    }
    function apc_delete($key)
    {
        return output_cache_remove_key($key);
    }
    function apc_clear_cache($type)
    {
        // not implemented
        return false;
    }
    // }}}
/* */
} elseif (function_exists('shm_attach')) {
    //echo "shm\n";
    // I need semaphore locking for this code to work. Someone please help!
    // hack to use shm segments {{{
    // {{{ _shm_init()
    function _shm_init()
    {
        $GLOBALS['tgif_shm'] = shm_attach('tgif_shm',1024*1024*16,0666);
        register_shutdown_function('shm_detach',$GLOBALS['tgif_shm']);
    }
    // }}}
    // {{{ _shm_key($key)
    function _shm_key($key)
    {
        static $maps;
        if (!is_array($maps)) {
            $maps = @shm_get_var($GLOBALS['tgif_shm'],0);
            if (!$maps) {
                $maps = array();
            } else {
                $maps = unserialize($maps);
            }
        }
        if (array_key_exists($key,$maps)) {
            return $maps[$key];
        }
        $idx = count($maps)+1;
        $maps[$key] = $idx;
        shm_put_var($GLOBALS['tgif_shm'],0,$maps);
        shm_put_var($GLOBALS['tgif_shm'],$idx,null);
        return $idx;
    }
    // }}}
    // {{{ apc_fetch()
    function apc_fetch($key)
    {
        return shm_get_var($GLOBALS['tgif_shm'],_shm_key($key));
    }
    // }}}
    // {{{ apc_store()
    function apc_store($key, $value,$ttl=0)
    {
        return shm_put_var($GLOBALS['tgif_shm'],_shm_key($key),$value);
    }
    // }}}
    // {{{ apc_delete($key)
    function apc_delete($key)
    {
        return shm_remove_var($GLOBALS['tgif_shm'],_shm_key($key));
    }
    // }}}
    // {{{ apc_clear_cache()
    function apc_clear_cache()
    {
        return shm_remove($GLOBALS['tgif_shm']);
    }
    // }}}
    _shm_init();
    // }}}
/* */
} else {
    //echo "none\n";
    // no shared memory cache available {{{
    function apc_fetch() { return false; }
    function apc_store() { return false; }
    function apc_delete() { return false; }
    function apc_clear_cache() { return true; }
    // }}}
}
?>
