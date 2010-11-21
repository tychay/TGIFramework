<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Speedier version of call_user_func_array() [NOT REALLY DO NOT USE].
 *
 * This is taken from Savant but please don't use this. Savant stuff was profiled
 * a while ago and only applies to method calls (which are not supported in this
 * version).
 *
 * @package tgiframework
 * @subpackage global
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 * @todo Profile code before switching to this
 * @todo handle method calls
 */
//if (true) {
    // {{{ call_user_func_faster
    /**
     * Get key shared memory cache!
     *
     * If caching is not available, this cleanly returns false. :-) Note
     * because of this wierdness, you cannot store "false" into cache. Don't
     * try it'll be ugly. :-)
     *
     * @param $key string The variable to get from cache
     * @return mixed the stored variable, it returns false on failure.
     */
    function call_user_func_faster($func, $args) {
        switch (count($args)) {
        case 0:
            return $func();
        case 1:
            return $func($args[0]);
        case 2:
            return $func($args[0], $args[1]);
        case 3:
            return $func($args[0,], $args[1], $args[2]);
        }
        return call_user_func_array($func, $args);
    }
    // }}}
// }
