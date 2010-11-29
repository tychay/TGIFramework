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
    // {{{ + copy($source_file, $destination)
    /**
     * An atomic {@link http://php.net/copy copy()} (where possible).
     *
     * This does not support stream context.
     *
     * A link effecitvely copies files atomically in Linux, however, when
     * running in a local vmware environment on a windows hoast, link doesn't
     * work. Thus we fail through to a copy which does work. :-)
     *
     * {@link http://php.net/file_put_contents/ file_put_contents()}.
     *
     * @param string $sourceFile from where to copy
     * @param string $destFile where to copy to
     * @param integer|false $chmod octal number for chmod of file with set.
     * @return boolean success or failure
     */
    static function copy($sourceFile, $destFile, $chmod=false)
    {
        $isRunning = self::_diag_start('move', array(
                'source_file'   => $sourceFile,
                'dest_file'     => $destFile,
                'chmod'         => $chmod,
            ));

        if ( file_exists($destFile) ) {
            unlink($destFile);
        }
        if ( link($sourceFile, $destFile) ) {
            $success = true;
        } else {
            $success = copy($sourceFile, $destFile);
        }
        if ($chmod!==false) { chmod($destFile, $chmod); }

        self::_diag_stop($isRunning, array('success' => $success));
        return $success;
    }
    // }}}
    // {{{ + move($sourceFile, $destfile, $chmod)
    /**
     * An atomic {@link http://php.net/rename rename()} (where possible).
     *
     * Note that this doesn't support streams like rename does.
     *
     * @param string $sourceFile from where to copy
     * @param string $destFile where to copy to
     * @param integer|false $chmod octal number for chmod of file with set.
     * @return boolean success or failure
     * @uses tgif_file::copy()
     */
    static function move($sourceFile, $destFile, $chmod=false)
    {
        $isRunning = self::_diag_start('move', array(
                'source_file'   => $sourceFile,
                'dest_file'     => $destFile,
                'chmod'         => $chmod,
            ));

        $success = self::copy($sourceFile, $destFile, $chmod);
        if ( $success ) {
            unlink($sourceFile);
        }

        self::_diag_stop($isRunning, array('success' => $success));
        return $success;
    }
    // }}}
    // {{{ + get_contents($filename[,$use_include_path])
    /**
     * A wrapper for 
     * {@link http://php.net/file_put_contents/ file_get_context()}.
     *
     * I don't implement any of the following: context, offset, maxlen)
     *
     * @param string $filename the file to write to
     * @param string $data the data to put in there.
     * @param integer|false $chmod octal number for chmod of file of destination
     * @return success Unlike the native version, tis does not return the number
     *  of byptes writen
     * @todo implement context
     */
    static function get_contents($filename, $use_include_path=false)
    {
        $isRunning = self::_diag_start('get_contents', array(
                'source_file'       => $filename,
                'use_include_path'  => $use_include_path,
            ));

        $result = file_get_contents($filename, $use_include_path);

        self::_diag_stop($isRunning);
        return $result;
    }
    // }}}
    // {{{ + put_contents($filename,$data[,$chmod])
    /**
     * A race safe version of
     * {@link http://php.net/file_put_contents/ file_put_contents()}.
     *
     * I don't implement any of the flags or the context
     *
     * @param string $filename the file to write to
     * @param string $data the data to put in there.
     * @param integer|false $chmod octal number for chmod of file of destination
     * @return success Unlike the native version, tis does not return the number
     *  of byptes writen
     * @todo implement context
     */
    static function put_contents($filename, $data, $chmod=0666)
    {
        $isRunning = self::_diag_start('move', array(
                'dest_file'     => $filename,
                'chmod'         => $chmod,
            ));

        $tmpfname = tempnam(basename($filename),'tf_');
        $fp = fopen($tmpfname,'w');
        fwrite($fp, $data);
        fclose($fp);
        $success = self::move($tmpfname,$filename,$chmod);

        self::_diag_stop($isRunning, array('success' => $success));
        return $success;
    }
    // }}}
    // DIAGNOSTICS METHODS
    // {{{ + _diag_start($function[,$data])
    /**
     * Start diagnostics for this.
     *
     * @param string $function the function called
     * @param string $data $data to tack on
     * @return boolean is it already running?
     * @uses tgif_file::copy()
     */
    private static function _diag_start($function, $data=array())
    {
        //global $_TAG;
        if (!$isRunning = $_TAG->diagnostics->isRunning('file')) {
            $_TAG->diagnostics->startTimer('file', $function, $data);
        }
        return $isRunning;
    }
    // }}}
    // {{{ + _diag_stop($isRunning[,$data])
    /**
     * Stop diagnostics
     *
     * @param boolean $isRunning is diagnostics running at this level?
     * @param string $data $data to tack on
     * @uses tgif_file::copy()
     */
    private static function _diag_stop($isRunning, $data=array())
    {
        //global $_TAG;
        if ( $isRunning ) { return; }
        $_TAG->diagnostics->stopTimer('file', $data=array());
    }
    // }}}
}
// }}}
?>
