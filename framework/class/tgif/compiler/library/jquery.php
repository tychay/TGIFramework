<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tag_compiler_library_jquery}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// imports {{{
/**
 * Some weirdness involved with implements not being loaded
 */
include_once(dirname(__FILE__).'/../library.php');
//include_once('../library.php');
// }}}
// {{{ tgif_compiler_library_jquery
/**
 * Javascript files includes using a remote jQuery CDN (Google).
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
class tgif_compiler_library_jquery implements tgif_compiler_library
{
    // PROPERTIES
    // {{{ - $_options
    /**
     * Configuration options for this. Here are the keys:
     * - version (string): The jQuery version.
     * - ui_version (string): The jQuery UI version.
     * @var array
     */
    protected $_options = array(
        'version'       => '1.4.4',
        'ui_version'    => '1.8.6',
    );
    // }}}
    // CONSTRUCT
    // {{{ __construct($options)
    /**
     * Save the options and load the $_moduleInfo.
     */
    function __construct($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }
    // }}}
    // SIGNATURE METHODS:
    // {{{ - generateSignature($fileName,$compileObj)
    /**
     * Figure a way of making a signature unique
     *
     * @param string $fileName the name of the library file
     * @return string the signature
     */
    public function generateSignature($fileName, $compileObj)
    {
        switch ($fileName) {
            case 'jquery'    : return $this->_options['version'];
            case 'jqueryui'  : return $this->_options['ui_version'];
        }
    }
    // }}}
    // {{{ - generateFileData($fileName,$compileObj)
    /**
     * Intercept any calls to jquery or jqueryui and replace with this.
     *
     * @param string $fileName the name of the library file
     * @return array The library file's filedata, empty if no match.
     */
    public function generateFileData($fileName,$compileObj)
    {
        switch ($fileName) {
            case 'jquery':
            case 'jquery.js':
            case 'ext/jquery.js':
                return array(
                    'name'          => 'jquery',
                    'signature'     => $this->_options['version'],
                    'is_resource'   => true,
                    'library'       => get_class($this),
                    'dependencies'  => array(),
                    'file_path'     => '', //not needed as it is remote
                    'url'           => sprintf('http://ajax.googleapis.com/ajax/libs/jquery/%s/jquery.js', $this->_options['version']),
                    'provides'      => array('jquery','jquery.js','ext/jquery.js'),
                );
            case 'jqueryui':
            case 'jqueryui.js':
            case 'ext/jqueryui.js':
                return array(
                    'name'          => 'jqueryui',
                    'signature'     => $this->_options['ui_version'],
                    'is_resource'   => true,
                    'library'       => get_class($this),
                    'dependencies'  => array('jquery'),
                    'file_path'     => '', //not needed as it is remote
                    'url'           => sprintf('http://ajax.googleapis.com/ajax/libs/jqueryui/%s/jqueryui.js', $this->_options['version']),
                    'provides'      => array('jqueryui','jqueryui.js','ext/jqueryui.js'),
                );
            default:
                return array();
        }
    }
    // }}}
    // {{{ - compileFile($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * Replace URL with .min version
     *
     * @param array $sourceFileData The file data of the resource. This will
     * be modified to the target file data if successful.
     * @param string $targetFileName The file name of the destination file
     * @param string $targetFilePath The path to a physically unique file to
     * place the destination file.
     * @param tgif_compiler $compilerObj for introspection as needed. For
     * instance it may be useful to call {@link
     * tgif_compiler::compileFileInternal() compileFileInternal()} to further
     * compress a file.
     * @return boolean success or failure
     */
    public function compileFile(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj)
    {
        $url = $sourceFileData['url'];
        $sourceFileData['url'] = substr($url,0,-3).'.min'.substr($url,-3);
        return true;
    }
    // }}}
    // {{{ - compileFileService($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * Forward to compileFile()
     */
    public function compileFileService(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj) {
        return $this->compileFile($sourceFileData,$targetFileName,$targetFilePath,$compilerObj);
    }
    // }}}
    // {{{ - catFiles(&$fileDatas,$compilerObj)
    /**
     * Pass it's data forward and remove
     *
     * @param array $fileDatas a list of file data to catenate together. You
     * can manipulate this result set however you want. But be warned, if you
     * do no purge all library instances, this will get ugly.
     * @param tgif_compiler $compilerObj for introspection as needed (for
     * instance, you are replacing the object in place with a local file
     * version)
     * @return array a list of file data that is separate from regular file
     * catenation.
     */
    public function catFiles(&$fileDatas, $compilerObj)
    {
        $returns = array();
        $library_name = get_class($this);
        foreach ($fileDatas as $key=>$filedata) {
            if ( $filedata['library'] != $library_name ) { continue; }
            $returns[$key] = $filedata;
            unset($fileDatas[$key]);
        }
        return $returns;
    }
    // }}}
    // {{{ - generateUrl($fileData)
    /**
     * This should never be called.
     *
     * @param string $fileData The data to extract the URL for
     * @return string the url
     */
    public function generateUrl($fileData) {
        return $fileData['url'];
    }
    // }}}
}
// }}}
?>
