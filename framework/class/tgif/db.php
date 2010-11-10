<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_db}
 *
 * @package tgiframework
 * @subpackage utilities
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_db
// docs {{{
/**
 * Right now just a wrapper to pdo so it pdo handles can be created directly from globals
 *
 * @package tgiframework
 * @subpackage utilities
 * @author terry chay <tychay@php.net>
 */
// }}}
class tgif_db
{
    // {{{ + pdo(…)
    /**
     * wrapper to PDO constructor so PDOs can be globals directly
     */
    static function pdo()
    {
        $args = func_get_args();
        switch( func_num_args() ) {
        case 1:
            return new PDO($args[0]);
        case 2:
            return new PDO($args[0],$args[1]);
        case 3:
            return new PDO($args[0],$args[1],$args[2]);
        case 4:
            return new PDO($args[0],$args[1],$args[2],$args[3]);
        }
    }
    // }}}
}
// }}}
?>
