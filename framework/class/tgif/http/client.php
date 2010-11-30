<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_file}
 *
 * @package tgiframework
 * @subpackage utilities
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_http_client
// docs {{{
/**
 * curl utilities
 *
 * @package tgiframework
 * @subpackage utilities
 * @author terry chay <tychay@php.net> refactored from {@link tgif_diagnostics}
 */
// }}}
class tgif_http_client
{
    // {{{ + fetch_into($url,$destFile,$chmod=false)
    /**
     * cURL fetch a url into a file (in an atomic manner).
     *
     * @param string $url where to grab data
     * @param string $destFile where to copy to
     * @param integer|false $chmod octal number for chmod of file with set.
     * @return boolean success or failure
     */
    static function fetch_into($url, $destFile, $chmod=false)
    {
        $isRunning = self::_diag_start('fetch_into', array(
                'url'       => $url,
                'dest_file' => $destFile,
                'chmod'     => $chmod,
            ));

        $base_path = dirname($destFile);
        if ( !file_exists($base_path) ) {
            $dir_chmod = ( $chmod!==false ) ? tgif_file::dir_chmod($chmod) : 0777;
            if ( !mkdir($base_path, $dir_chmod, true) ) {
                $success = false;
                self::_diag_stop($isRunning, array('success' => $success));
                return $success;
            }
        }
        
        $tmpfile = tempnam($base_path, 'thc_');
        $fh = fopen($tmpfile, 'w');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fh);
        $success = curl_exec($ch);
        curl_close($ch); //must close curl handle first
        fclose($fh);

        if ( !$success ) {
            self::_diag_stop($isRunning, array('success' => $success));
            return $success;
        }

        $success = tgif_file::move($tmpfile, $destFile, $chmod);
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
     */
    private static function _diag_start($function, $data=array())
    {
        //global $_TAG;
        if (!$isRunning = $_TAG->diagnostics->isRunning('curl')) {
            $_TAG->diagnostics->startTimer('curl', $function, $data);
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
     */
    private static function _diag_stop($isRunning, $data=array())
    {
        //global $_TAG;
        if ( $isRunning ) { return; }
        $_TAG->diagnostics->stopTimer('curl', $data=array());
    }
    // }}}
}
// }}}
?>
