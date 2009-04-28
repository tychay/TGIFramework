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
    // {{{ + _x_get_instance()
    /**
     * Bind the firephp system.
     *
     * Should only be called by global system
     */
    public static function _x_get_instance()
    {
        global $_TAG;
        if ($_TAG->config('firephp_enable')) {
            // explicitly require because the naming convention doesn't obey
            // PEAR's naming standards
            /**
             * When firephp_enable config is set, we need the FirePHPCore
             */
            require_once('FirePHPCore/FirePHP.class.php');
            // $_TAG->queue starts up an output buffer already
            //ob_start();
            return FirePHP::getInstance(true);
        }
        return new tgif_debug_firephp_null();
    }
    // }}}
}
// }}}
