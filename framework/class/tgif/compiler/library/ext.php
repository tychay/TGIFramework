<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tag_compiler_library_ext}
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
// {{{ tgif_compiler_library_ext
/**
 * Includes to (javascript or css) files which grab external packages managed
 * as local files.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
class tgif_compiler_library_ext implements tgif_compiler_library
{
    // PROPERTIES
    // {{{ - $_options
    /**
     * Settings for the system
     *
     * There is a setting called "modules" that is parsed into moduleInfo.
     *
     * - base_path (string): Path to the directory to download the missing
     *   files to
     * - base_url (string): Path to the directory as seen from a web browser
     *   This will be ignored if url_callback is set.
     * - url_callback (mixed): If set, it's a call to a callback function
     *   for generating the url
     * - chmod (integer); the permissions set for the file
     * -
     * @var array
     */
    protected $_options = array(
        'base_path'         => '',
        'base_url'          => '/',
        'url_callback'      => false,
        'chmod'             => 0666,
        'default_filedata'  => array(
            'name'          => '', //specified in moduleInfo, or set same as key
            'is_resource'   => true,
            'library'       => '', //specified in constructor
            'dependencies'  => array(),
            'signature'     => '', //bound late
            'file_path'     => '', //bound late
            //'provides'      => array(),
            //'url'           => sprintf('http://ajax.googleapis.com/ajax/libs/jquery/%s/jquery.js', $this->_options['version']),
        ), 
    );
    // }}}
    // {{{ - $_moduleInfo
    /**
     * Libraries that it knows about, indexed by file reference. This is stored
     * in the config options passed in uner the setting "modules"
     *
     * - name (string): the local path to the file (computed from base_dir)
     * - url (string): Where to grab the file
     * @var array
     */
    protected $_moduleInfo = array();
    // }}}
    // CONSTRUCT
    // {{{ __construct($options)
    /**
     * Save the options and load the $_moduleInfo.
     */
    function __construct($options)
    {
        if ( isset($options['modules']) ) {
            $modules = $options['modules'];
            unset $options['modules'];
        }
        $this->_options = array_merge($this->_options, $options);
        $this->_moduleInfo = array_merge($this->_moduleInfo, $modules);
        if ( !$this->_options['default_filedata']['library'] ) {
            $this->_options['default_filedata']['library'] = get_class($this);
        }
    }
    // }}}
    // SIGNATURE METHODS:
    // {{{ - generateSignature($fileName,$compileObj)
    /**
     * Use the default file-based signature system to sign the data
     *
     * @param string $fileName the name of the library file
     * @return string the signature
     */
    public function generateSignature($fileName, $compileObj)
    {
        $sign_this = array(
            'library' => '',
            'file_path' => $this->_options['base_path'].$fileName,
        ); 
        return $compileObj->signature($sign_this);
    }
    // }}}
    // {{{ - generateFileData($fileName)
    /**
     * Intercept anything matching the keys in the loaded $_moduleInfo
     *
     * @param string $fileName the name of the library file
     * @return array The library file's filedata, empty if no match.
     */
    public function generateFileData($fileName)
    {
        // only libraries we've found
        if ( !array_key_exists($fileName,$this->_moduleInfo) ) { return array(); }

        $return = array_merge( $this->_options['default_filedata'], $this->_moduleInfo[$fileName] );
        if ( !$return['name'] ) { $return['name'] = $fileName; 
        $return['file_path'] = $this->options['base_path'] . $return['name'];

        if (!$result = tgif_http_client::fetch_into($return['url'], $return['file_path'], $this->_options['chmod']) {
            // unpredicatable things happen at this point!
            return $return;
        }

        // Update the signature
        $return['signature'] = $this->generateSignature($return);

        // Update the url link
        if ($base_url = $this->_options['base_url']) {
            $return['url'] = $base_url . $return['name'];
        }
        // Remove the library parameter (to make the intenal system handle the
        // file going forward
        $return['library'] = '';
        return array();
    }
    // }}}
    // {{{ - compileFile($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * This is never called, because the built in filesystem has taken over.
     */
    public function compileFile(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj)
    { }
    // }}}
    // {{{ - compileFileService($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * This is never called because the built in file system has taken over.
     */
    public function compileFileService(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj)
    { }
    // }}}
    // {{{ - catFiles(&$fileDatas,$compilerObj)
    /**
     * This is never called because the built in file system has taken over.
     * Just in case, forward the file through.
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
     * This should never be called since the url is always embeded.
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
