<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Container for {@link tgif_debug_firephp_null}
 *
 * @package tgiframework
 * @subpackage debugging
 * @copyright 2009. Tagged, Inc. 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// {{{ tgif_debug_firephp_null
/**
 * Null function for {@link FirePHP} created by {@link tgif_debug_firephp}.
 *
 * This gets used when you don't want to be using FirePHP
 */
class tgif_debug_firephp_null
{
    function __call($name,$arguments)
    {
        return;
    }
}
// }}}
