<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_global_collection}
 *
 * @package tgiframework
 * @subpackage global
 * @copyright c.2007 Tagged, Inc., c.2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// {{{ tgif_global_collection
/**
 * A composite of {@link tgif_global_object}.
 *
 * I could avoid introspection by copying between the two interal arrays.
 *
 * This is used for parameterized loads of globals, but unlike
 * {@link tgif_global_loader} this is <strong>also</strong> stored as the tag
 * global itself!
 *
 * @package tgiframework
 * @subpackage global
 */
class tgif_global_collection extends tgif_global_object implements ArrayAccess
{
    // {{{ - $_params
    /**
     * The data used to construct loaders
     *
     * We have to save it in case the sparse array in the collection isn't
     * entirely preloaded.
     */
    private $_params = array();
    // }}}
    // {{{ - $_loaders
    /**
     * Holder for the objects or loader object
     */
    private $_loaders = array();
    // }}}
    // {{{ - $_array
    /**
     * The data to emulate as an array.
     */
    private $_array = array();
    // }}}
    // {{{ __construct($params, $arguments)
    /**
     * @param $arguments array the stuff to use in a constructor of an initial
     *      member in a subclass. Note that if one isn't provided the collection
     *      is created but there is no submember loaded.
     */
    function __construct($params, $arguments=array())
    {
        $this->_params = $params;
        $init_member = (count($arguments) != 0);
        if ($init_member) {
            $key = array_shift($arguments);
        } else {
            return;
        }
        if (array_key_exists('ids',$params)) {
            array_push($params['ids'],$key);
        } else {
            $params['ids'] = array($key);
        }
        if ($params['params'] > 1) {
            --$params['params'];
            $this->_loaders[$key] = new tgif_global_collection($params,$arguments);
        } else {
            unset($params['params']);
            $this->_loaders[$key] = new tgif_global_loader($params);
        }
    }
    // }}}
    // {{{ - dispatch()
    /**
     * Make sure the data is loaded up.
     */
    function dispatch()
    {
        foreach ($this->_loaders as $object) {
            $object->dispatch();
        }
    }
    // }}}
    // {{{ - ready()
    /**
     * We return the self since this object doubles both as the loader and
     * the {@link ArrayObject} for the data.
     *
     * @return tgif_gloabl_collection self
     */
    function ready()
    {
        foreach ($this->_loaders as $key=>$object) {
            $this->_array[$key] = $object->ready();
            unset($this->_loaders[$key]);
        }
        return $this;
    }
    // }}}
    // IMPLEMENTS ARRAYACCESS
    // {{{ - offsetExists($offset)
    /**
     * Does this exist as an array?
     *
     * This routine is a little flakey because we can build arrays on demands
     * so even it returns false, that doesnÕt mean you canÕt access it anyway.
     *
     * @param $offset integer|string offset to check
     * @returns boolean whether the offset exists
     */
    function offsetExists($offset)
    {
        if (array_key_exists($offset,$this->_array)) { return true; }
        if (array_key_exists($offset,$this->_loaders)) { return true; }
        return false;
    }
    // }}}
    // {{{ - offsetGet($offset)
    /**
     * Get the object if it exists, if it doesn't, create it.
     *
     * @param $offset integer|string offset to retrieve
     * @returns mixed value at given offset
     */
    function offsetGet($offset)
    {
        // if it's in the collection, return it.
        if (array_key_exists($offset,$this->_array)) {
            return $this->_array[$offset];
        }

        $params = $this->_params;
        // if it isnt in the collection, add it to the loaders {{{
        if (!array_key_exists($offset,$this->_loaders)) {
            if (array_key_exists('ids',$params)) {
                array_push($params['ids'],$offset);
            } else {
                $params['ids'] = array($offset);
            }
            // a collection of collections is already "done" {{{
            if ($params['params'] > 1) {
                --$params['params'];
                $this->_array[$offset] = new tgif_global_collection($params);
                return $this->_array[$offset];
            }
            // }}}
            unset($params['params']);
            $this->_loaders[$offset] = new tgif_global_loader($params);
        }
        // }}}
        $this->_loaders[$offset]->dispatch();
        $this->_array[$offset] = $this->_loaders[$offset]->ready();
        unset($this->_loaders[$offset]);
        return $this->_array[$offset];
    }
    // }}}
    // {{{ - offsetSet($offset,$value)
    /**
     * Bind an object to the collection from outside {@link tgif_global}.
     *
     * During binding, it will also update the caches, but only if the
     * 'loaderLoader' parameter is defined. It is assumed that if no such
     * one is defined, then the cache updates are not needed.
     *
     * @param $offset integer|string offset to modify
     * @param $value mixed new value
     */
    function offsetSet($offset,$value)
    {
        if ( is_a($value,'tgif_global_collection') ) {
            trigger_error('Binding a collection object not supported yet! Make a routine that initialized the base loader first');die;
        }
        $this->_array[$offset] = $value;

        $params =& $this->_params;
        // bind the loader or update the cache on set {{{
        // since the default values are nil, we can use isset here
        if ( isset($params['loaderLoader']) || isset($params['cacheUpdateOnSet']) ) {
            $params['ids'] = array($offset);
            unset($params['params']);
            $loader_obj = new tgif_global_loader($params);
            if ( !empty($params['cacheUpdateOnSet']) ) {
                $loader_obj->setToCache($value);
            }
            // bind the loader object
            if ( !empty($params['laoderLoader']) ) {
                $value->{$params['loaderLoader']} = $loader_obj;
            }
        }
        // }}}
    }
    // }}}
    // {{{ - offsetUnset($offset)
    /**
     * @param $offset integer|string offset to delete
     */
    function offsetUnset($offset)
    {
        if (array_key_exists($offset,$this->_array)) {
            unset($this->_array[$offset]);
        }
    }
    // }}}
}
// }}}
?>
