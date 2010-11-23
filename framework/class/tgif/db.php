<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_db}
 *
 * @package tgiframework
 * @subpackage database
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_db
// docs {{{
/**
 * Right now just a wrapper to pdo so it pdo handles can be created directly from globals
 *
 * @package tgiframework
 * @subpackage database
 * @author terry chay <tychay@php.net>
 */
// }}}
class tgif_db
{
    // {{{ + pdo(…)
    /**
     * Wrapper to {@link tgif_db_pdo PDO constructor} so PDOs can be globals
     * directly
     */
    static function pdo()
    {
        return new tgif_db_pdo(func_get_args());
    }
    // }}}
}
// }}}
?>
