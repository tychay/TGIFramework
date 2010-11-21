<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Configuration for everything in samples
 *
 * @package tgisamples
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    // {{{ memcached_config_default
    /**
     * Array containing config for default pool
     */
    'memcached_config_default'  => array(
        'persist'       => true,    //persist the connection between requests
        'lifetime'      => 0,       //default lifetime in requests (== expire)
        'timeout'       => 1,       //wait i1 second for the connection to
                                    // timeout
        'retryTimeout'  => 100,     //let server be disabled for 100 sec before
                                    // trying to put it back on pool
        'compressThreshold' => 8192,//8K to turn on auto compressioc
        'compressMinSaving' => 0.2, //20% savings required (memcache-only)
        //'checkStatus'       => false, //don't verify connection (memcache-only)
        //'logRandom'         => false, //don't log memcaches at random (memcache-only)
        'diagnostics'       => true,  //allow diagnostics to time memcache
        'hashing'           => '',    //default hashing function
        //'strategy'          => 32757, //default strategy
        'serializer'        => 'igbinary', //use igbinary if available
        'distribution'      => 'consistent', //use libketama-like
    ),
    // }}}
    // {{{ memcached_pool_default
    /**
     * Array containing list of hosts in pool.
     *
     * It contains a list of servers where each server is an array:
     * 0. IP of server
     * 1. port of server
     * 2. selection weighting (currently unused).
     */
    'memcached_pool_default'  => array(
        array('127.0.0.1',11211,1),
        array('127.0.0.1',11212,1),
    ),
    // }}}
);
?>
