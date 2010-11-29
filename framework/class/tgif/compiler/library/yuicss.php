<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tag_compiler_library_yuicss}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// {{{ tgif_compiler_library_yuicss
/**
 * Compiling Javascript files using YUI Compressor with support for YUI
 * libraries.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
class tgif_compiler_library_yuicss extends tgif_compiler_library_yui
{
    // CONSTRUCT
    // {{{ __construct($options)
    /**
     * Save the options and load the $_moduleInfo.
     */
    function __construct($options)
    {
        parent::__construct($options,'css');
    }
    // }}}
    // SIGNATURE METHODS:
    // {{{ - generateFileData($fileName)
    /**
     * Turn a file name into file data
     *
     * This used to be part of {@link tgif_compiler_js::_generateFileData()}
     * on Tagged.
     *
     * @param string $fileName the name of the library file
     * @return array The library file's filedata, empty if no match.
     */
    public function generateFileData($fileName)
    {
        //var_dump('generateFileData',$fileName,$this);die;
        if ( strcmp(substr($fileName,0,6),'YAHOO/') === false ) {
            return array();
        }
        $lib_name = substr($fileName, 6); // $fileName = YAHOO/$lib_name
        //'file_path'     => '', //append later
        return $this->_extractYui2Module($lib_name, 'css');
    }
    // }}}
    // {{{ - compileFile($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * This just points a file to the -min version of the same file (it's
     * precompiled).
     */
    public function compileFile(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj)
    {
        //var_dump('compileFile',$sourceFileData, $targetFileName, $targetFilePath, $compilerObj, $this);die;
        $path =& $sourceFileData['path'];
        switch ($this->_options['filter']) {
            case 'min': break; //do nothing
            case 'debug':
                $path = str_replace('-min','-debug',$path);
                break;
            default:
                $path = substr($path, 0, -4).'-min'.substr($path, -4);
                //break;
        }
        return true;
    }
    // }}}
}
// }}}
?>
