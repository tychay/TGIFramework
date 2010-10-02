<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_global_object}
 *
 * @package tgiframework
 * @subpackage global
 * @copyright c.2007 Tagged, Inc., c.2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// {{{ tgif_global_object
/**
 * This is the base class for all global object loading.
 *
 * I didn't call it a loader or abstract loader because it does double duty
 * in some cases as being the array object for a class of objects that may
 * be loaded later in the process (i.e. without dispatch).
 *
 * @package tgiframework
 * @subpackage global
 */
class tgif_global_object
{
    // OVERRIDES
    function dispatch() {}
    function ready() {}
    // STATIC METHODS
    // {{{ + get_loader($params,$arguments)
    /**
     * Get the loader for a set of global params
     *
     * This calls the constructors for all the {@link tgif_global_objects} so
     * don't call it yourself.
     *
     * @param $params array this contains a parameterization of settings from
     *      the global loader + a special paramter ['params'] which contains
     *      the parameter count
     * @param $arguments array this is the index arguments in order to handle
     *      collections when the global is parameterized by an index.
     * @return tgif_global_object the object which knows how to load a global
     *      parameter.
     */
    static function get_loader($params,$arguments)
    {
        if (!array_key_exists('params',$params)) {
            $params['params'] = 0;
        }
        if ($params['params'] == 0) {
            // special case: expand config params in "ids" {{{
            if (array_key_exists('ids',$params)) {
                foreach($params['ids'] as $key=>$value) {
                    $params['ids'][$key] = preg_replace_callback(
                        '/{{{(\w*)}}}/',
                         array('tgif_global_object','config_replace'),
                        $value, -1, $count
                    );
                }
            }
            // }}}
            return new tgif_global_loader($params);
        } else {
            return new tgif_global_collection($params, $arguments);
        }
    }
    // }}}
    // {{{ + config_replace($matches)
    public static function config_replace($matches)
    {
        global $_TAG;
        return $_TAG->config($matches[1]);
    }
    // }}}
}
// }}}
?>
