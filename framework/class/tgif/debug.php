<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Debug-related library
 *
 * @package tgiframework
 * @subpackage debugging
 * @copyright 2008. Tagged, Inc.
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author (unknown)
 */
class tgif_debug
{
    // {{{ + hex_dump($v)
    /**
     * Take a binary string and turn it into something an engineer can look at.
     * Leaves all printable characters as is and turns the rest into 2-char
     * hex codes.
     */
    public static function hex_dump($v)
    {
        $s = strval($v);
        $buff = '';
        for ($i = 0; $i < strlen($s); $i++) {
            $c = $s[$i];
            //if ($c == "\x00") { $buff .= '00'; } else { $buff .= $c; }
            if (ctype_print($c)) {
                $buff .= $c;
            } else {
                $o = ord($c);
                $buff .= sprintf('%02x',$o);
            }
        }
        return $buff;
    }
    // }}}
}
