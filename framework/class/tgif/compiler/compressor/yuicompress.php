<?php
/**
 * Holder of {@link tag_compiler_compressor_default}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
/**
 * Emulate a compressor that does nothing (copies the file)
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
class tgif_compiler_compressor_yuicompress extends tgif_compiler_compressor
{
	/**
	 * Assumes two types of commands, either the old style with /usr/bin/java -jar yuicompress.jar
	 * or just a path to the installed compressor.
	 * 
	 * @todo  what if it's not a full filepath?
	 */
	static public function works() {
		global $_TAG;
		$compressor_cmd = $_TAG->config('compressors.yui_compressor', true);
		$parts = array_map('trim', explode('-jar', $compressor_cmd));
		foreach ($parts as $cmd) {
			if ( !file_exists($cmd) ) { return false; }
		}
		return true;
	}
    /**
     * Use YUI compressor to turn one into the other
     *
     */
    static public function compress($type, $sourcePath, $destPath, $backgroundPath='') {
        global $_TAG;

        // old
        //$compressor_cmd = sprintf('%s -jar %s', $_TAG->config('bin_java'), $_TAG->config('yui.compressor_jar', true));
        // new
        $compressor_cmd = $_TAG->config('compressors.yui_compressor', true);

        $cmd = sprintf('%s --type %s -o %s %s',
        	$compressor_cmd,
            $type,
            ($backgroundPath) ? escapeshellarg($backgroundPath) : escapeshellarg($destPath),
            escapeshellarg($sourcePath)
        );
        if ($backgroundPath) {
            // chain command
            $cmd = sprintf('%s;mv %s %s', $cmd, escapeshellarg($backgroundPath), escapeshellarg($destPath));
            // background and nohub command chain
            $cmd = sprintf('nohup sh -c %s &', escapeshellarg($cmd));
        }
        $_TAG->diagnostics->startTimer(
            'exec',
            __CLASS__.'::compress',
            array( 'cmd' => $cmd )
        );
        exec($cmd,$output,$return_var);
        $_TAG->diagnostics->stopTimer(
            'exec',
            array(
                'output' => implode("\n", $output),
                'return' => $return_var,
            )
        );
        return ($backgroundPath) ? false : true;
    }	
}