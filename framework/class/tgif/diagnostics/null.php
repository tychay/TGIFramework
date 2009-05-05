<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Holder for {@link tgif_diagnostics_null}
 *
 * @package tgiframework
 * @subpackage debugging
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @copyright 2008 Tagged, Inc. 2009 terry chay
 * @author terry chay <tychay@php.net>

 */
// {{{ tgif_diagnostics_null
/**
 * Null Object for {@link tgif_diagnostics}
 *
 * When running mail queues (and such), we shouldn't bother with the
 * diagnostics, but lets not put a bunch of if-then checks around code. This
 * way it works transparently.
 *
 * @package tgiframework
 * @subpackage debugging
 */
class tgif_diagnostics_null
{
    // FUNCTIONS
    public $guid;
    // ACCESSOR
    // {{{ - guid()
    /**
     * Returns a guid
     *
     * This does the creation on-demand instead of the constructor as a small
     * optimization.
     */
    function guid()
    {
        // only create on runtime {{{
        if (!$this->guid) {
            $server = (in_array('SERVER_ADDR',$_SERVER))
                    ? $_SERVER['SERVER_ADDR']
                    : php_uname('n');
            // Globally unique identifier is a hash of
            //      microtime + entropy + serverip + pid
            // store the first 10 base-64 digits of hash
            $this->guid = tgif_encode::create_key(uniqid(rand(),true).$server.getmypid());
        }
        // }}}
        return $this->guid;
    }
    // }}}
    // {{{ - __call($function, $args)
    /**
     * This is a shortcut to defining methods here that are also in
     * tag_diagnostics.
     */
    public function __call($function, $args)
    {
        return;
    }
    // }}}

}
// }}}
?>
