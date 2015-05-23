<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_debug_firephp}.
 *
 * @package tgiframework
 * @subpackage debugging
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net> 
 */
// {{{ tgif_debug_firephp
/**
 * Wrapper for the {@link http:www.firephp.org/ FirePHP} debugging interface.
 *
 * FirePHP is a Firebug extension for Firefox that uses mimetype multipart
 * to send debugging information back through a separate channel.
 *
 * @package tgiframework
 * @subpackage debugging
 */
class tgif_debug_firephp
{
    // {{{ + _X_create_object()
    /**
     * Bind the firephp system.
     *
     * Should only be called by global system
     */
    public static function _X_create_object()
    {
        global $_TAG;
        if ($_TAG->config('firephp.enable',true)) {
            // $_TAG->queue starts up an output buffer already
            //ob_start();
            return FirePHP::getInstance(true);
        }
        return new tgif_debug_firephp_null();
    }
    // }}}
}
// }}}
