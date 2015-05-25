<?php
/**
 * Holder of {@link tgif_compiler_compressor}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright c.2015 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
/**
 * Default function for compressing/transforming javascripts or stylesheets
 *
 * Override these static methods to change the functionality of a compressor.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
class tgif_compiler_compressor
{
    /*
    public function __construct($options)
    {
        global $_TAG;
        $this->_options = array_merge($this->_options, $options);
    }
    */
    static public function works() {
        return true;
    }
    /**
     * Turn one file into another file.
     *
     * This version does a straight copy.
     *
     * @param  string $type either 'css' or 'js'
     * @param  string $sourcePath the file to compile
     * @param  string $destPath where to dump the final output to
     * @param  string $backgroundPath if specified, then do the work in the
     *         background and this is the intermediate file.
     * @return boolean Has the compiled file been created (backgrounding may
     *         prevent this from happening.
     */
    static public function compress($type, $sourcePath, $destPath, $backgroundPath='') {
       return tgif_file::copy($sourcePath, $destPath); 
    }
}
