<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Shared memory cache voodoo.
 *
 * Basically this creates a uniform api that mimics the apc shared memory
 * variable cache. This replaces the tag_cache_* I wrote in Tagged's framework.
 *
 * It is untested (because I always have APC installed nowadays).
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2007 Tagged, Inc., 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 * @todo zend cache is segfaulting dev, may be unreliable. Someone please help!
 * This is because output cache is implemented impractically. Please see:
 * http://files.zend.com/help/Zend-Platform/partial_and_preemptive_page_caching.htm
 * @todo untested shm_* version of apc_cache
 * @todo consider moving function definitions to separate files. Not sure if
 * APC is properly opcode caching this because functions are conditionally
 * defined. (I don't know because this code does nothing if APC is installed.)
 */
if (function_exists('apc_fetch')) {
    //echo "apc\n";
    // apc cache available {{{
    /*
     * Get key shared memory cache!
     *
     * If caching is not available, this cleanly returns false. :-) Note
     * because of this wierdness, you cannot store "false" into cache. Don't
     * try it'll be ugly. :-)
     *
     * @param $key string The variable to get from cache
     * @return mixed the stored variable, it returns false on failure.
     */
    //function apc_fetch($key) { return apc_fetch($key); }
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
    return;
} elseif (function_exists('output_cache_get')) {
    //echo "zend\n";
    // zend cache available {{{
    /**
     * @ignore
     */
    function apc_fetch($key,$success)
    {
        // Bug: Zend didn't document {@link output_cache_get()}. The second
        // parameter is a time to live (which makes no sense here).
        return output_cache_get($key, time()+1000000);
    }
    /**
     * @ignore
     */
    function apc_store($key,$value,$ttl)
    {
        //$ttl is not implemented
        return output_cache_put($key,$value);
    }
    /**
     * @ignore
     */
    function apc_delete($key)
    {
        return output_cache_remove_key($key);
    }
    /**
     * @ignore
     */
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
    /**
     * Utility function to create a shared memory segment to emulate APC's
     */
    function _shm_init()
    {
        $GLOBALS['tgif_shm'] = shm_attach('tgif_shm',1024*1024*16,0666);
        register_shutdown_function('shm_detach',$GLOBALS['tgif_shm']);
    }
    // }}}
    // {{{ _shm_key($key)
    /**
     * Lookup function to get shared memory key if using shm extension to
     * emulate APC (sort of).
     *
     * Shared memory segments are indexed by integer. Unlike apc_* The shared
     * memory version will always have a variable in as an artifact of the
     * key generator.
     *
     * @return integer
     */
    function _shm_key($key)
    {
        global $tgif_shm;
        static $maps; //local store of maps array
        // initialize $maps {{{
        if (!is_array($maps)) {
            $maps = ( shm_has_var($tgif_shm,0) )
                  ? shm_get_var($tgif_shm,0)
                  : array();
        }
        // }}}
        if ( array_key_exists($key,$maps) ) {
            return $maps[$key];
        }

        $idx = count($maps)+1;
        $maps[$key] = $idx;
        shm_put_var($tgif_shm,0,$maps);
        shm_put_var($tgif_shm,$idx,null);
        return $idx;
    }
    // }}}
    // {{{ apc_fetch()
    /**
     * @ignore
     */
    function apc_fetch($key, &$success)
    {
        global $tgif_shm;
        $success = true; // artifact of key gnerator
        return shm_get_var($tgif_shm,_shm_key($key));
    }
    // }}}
    // {{{ apc_store()
    /**
     * @ignore
     */
    function apc_store($key, $value,$ttl=0)
    {
        return shm_put_var($GLOBALS['tgif_shm'],_shm_key($key),$value);
    }
    // }}}
    // {{{ apc_delete($key)
    /**
     * @ignore
     */
    function apc_delete($key)
    {
        return shm_remove_var($GLOBALS['tgif_shm'],_shm_key($key));
    }
    // }}}
    // {{{ apc_clear_cache()
    /**
     * @ignore
     */
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
    /**
     * @ignore
     */
    function apc_fetch() { return false; }
    /**
     * @ignore
     */
    function apc_store() { return false; }
    /**
     * @ignore
     */
    function apc_delete() { return false; }
    /**
     * @ignore
     */
    function apc_clear_cache() { return true; }
    // }}}
}
?>
