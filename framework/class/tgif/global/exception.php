<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_global_execption}
 *
 * It was moved here so that it loads on demand instead on every instance.
 *
 * @package tgiframework
 * @subpackage global
 * @copyright c.2007 Tagged, Inc., c.2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// {{{ tgif_global_exception
/**
 * Uncaught exceptions and errors in the loader library
 *
 * @package tgiframework
 * @subpackage global
 */
 */
class tgif_global_exception extends Exception
{
    // {{{ __construct(…)
    /**
     * Added handling of file and line if passed in through
     * {@link tgif_global_loader::error_handler}
     *
     * @param string $message
     * @param integer $code
     * @param string $file if provided, the file where exception thrown
     * @param integer $line if provided, the line where exception thrown
     */
    function __construct($message,$code,$file='',$line=0)
    {
        parent::__construct($message,$code);
        if ($file) { $this->file = $file; }
        if ($line) { $this->line = $line; }
    }
    // }}}
}
// }}}
?>
