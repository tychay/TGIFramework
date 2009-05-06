<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_file}
 *
 * @package tgiframework
 * @subpackage utilities
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_file
// docs {{{
/**
 * File handling utility function
 *
 * @package tgiframework
 * @subpackage utilities
 * @author terry chay <tychay@php.net> refactored from {@link tgif_diagnostics}
 */
// }}}
class tgif_file
{
    // {{{ + put_contents($filename,$data)
    /**
     * A race safe version of
     * {@link http://php.net/file_put_contents/ file_put_contents()}.
     *
     * I don't implement any of the optional parameters.
     */
    static function put_contents($filename,$data)
    {
        $tmpfname = tempnam(basename($filename),'tf');
        $fp = fopen($tmpfname,'w');
        fwrite($fp, $data);
        fclose($fp);
        if (file_exists($filename)) {
            @unlink($filename);
        }
        link($tmpfname, $filename);
        unlink($tmpfname);
        chmod($filename,0666);
    }
    // }}}
}
// }}}
?>
