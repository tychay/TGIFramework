<?php
/**
 * Holder of {@link tag_compiler_library_ext}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2010-2015 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
// tgif_compiler_library_ext
/**
 * Includes to (javascript or css) files which grab external packages managed
 * (optionally) as local files.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
class tgif_compiler_library_ext implements tgif_compiler_library
{
    // PROPERTIES
    // - $_options
    /**
     * Settings for the system
     *
     * There is a setting called "modules" that is parsed into moduleInfo.
     *
     * - base_path (string): Path to the directory to download the missing
     *   files to
     * - base_url (string): Path to the directory as seen from a web browser
     *   This will be ignored if url_callback is set. This can optionally have
     *   three parameters: 1) https, 2) .min 3) version
     * - use_cdn (boolean): Whether or not to remote link the file
     * - use_compressor (boolean): Should we use the compiled version of the
     *    file (when using cdn)
     * - version (string): version number of file (parameter 3)
     * - compress_ext (string): the extension to use for remote linking a
     *   minified version (parameter 2)
     * - url_callback (mixed): If set, it's a call to a callback function
     *   for generating the url
     * - chmod (integer); the permissions set for the file
     * -
     * @var array
     */
    protected $_options = array(
        'base_path'        => '',
        'base_url'         => '/',
        'use_cdn'          => false,
        'use_compressor'   => false,
        'compress_ext'     => '.min',
        'url_callback'     => false,
        'chmod'            => 0666,
        'default_filedata' => array(
            'name'         => '', //specified in moduleInfo, or set same as key
            'is_resource'  => true,
            'library'      => '', //specified in constructor
            'dependencies' => array(),
            'signature'    => '', //bound late
            'file_path'    => '', //bound late
            //'provides'      => array(),
            //'url'           => sprintf('http://ajax.googleapis.com/ajax/libs/jquery/%s/jquery.js', $this->_options['version']),
        ), 
    );
    // - $_moduleInfo
    /**
     * Libraries that it knows about, indexed by file reference. This is stored
     * in the config options passed in uner the setting "modules"
     *
     * - name (string): the local path to the file (computed from base_dir)
     * - url (string): Where to grab the file
     * - url_map(string): Where to grab the file (support https, use_cdn_ compile_expansion)
     * - requires (array optional):
     * - provides (array optional):
     * - use_cdn (boolean): per-module override
     * - compress_ext (string): per-module override (param 2)
     * - version: version string (param 3)
     * @var array
     */
    protected $_moduleInfo = array();

    // CONSTRUCT
    /**
     * Save the options and load the $_moduleInfo.
     */
    function __construct($options)
    {
        if ( isset($options['modules']) ) {
            $modules = $options['modules'];
            unset($options['modules']);
        }
        $this->_options = array_merge($this->_options, $options);
        $this->_moduleInfo = array_merge($this->_moduleInfo, $modules);
        if ( !$this->_options['default_filedata']['library'] ) {
            $this->_options['default_filedata']['library'] = get_class($this);
        }
    }

    // SIGNATURE METHODS:
    // - generateSignature($fileName,$compileObj)
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

    // - generateFileData($fileName,$compileObj)
    /**
     * Intercept anything matching the keys in the loaded $_moduleInfo
     *
     * @param string $fileName the name of the library file
     * @return array The library file's filedata, empty if no match.
     */
    public function generateFileData($fileName,$compileObj)
    {
        // only libraries we've found
        if ( !array_key_exists($fileName,$this->_moduleInfo) ) { 
            return array();
        }
        //if ( !array_key_exists($fileName,$this->_moduleInfo) ) { return array(); }

        $return = array_merge( $this->_options['default_filedata'], $this->_moduleInfo[$fileName] );
        // add library defaults in
        if ( !array_key_exists('use_cdn', $return) ) {
            $return['use_cdn'] = $this->_options['use_cdn'];
        }
        if ( !array_key_exists('compress_ext', $return) ) {
            $return['compress_ext'] = $this->_options['compress_ext'];
        }
        if ( !array_key_exists('version', $return) ) {
            $return['version'] = '';
        }
        if ( !$return['name'] ) {
            $return['name'] = $fileName;
        }

        if ( !$return['use_cdn'] ) {
            $return['file_path'] = $this->_options['base_path'] . $return['name'];
            if ( !file_exists($return['file_path']) ) {
                // find the URL to grab, remember no need for ssl or compression
                $url = ( empty($return['url']) )
                    ? sprintf($return['url_map'], '', '', $return['version'])
                    : $return['url'];
                if ( !$result = tgif_http_client::fetch_into($url, $return['file_path'], $this->_options['chmod']) ) {
                    // unpredicatable things happen at this point!
                    return $return;
                }
            }

            // Update the signature (which is just the version of the file)
            //$return['signature'] = $this->generateSignature($return, $compileObj);
            $return['signature'] = $return['version'];

            // Update the url link
            if ( $callback = $this->_options['url_callback'] ) {
                $return['url'] = call_user_func($callback, $return);
            } elseif ( $base_url = $this->_options['base_url'] ) {
                $return['url'] = $base_url . '/'. $return['name'];
            }
            // Remove the library parameter (to make the system handle it as an
            // internal file going forward)
            $return['library'] = '';
        }
        return $return;
    }
    // - compileFile($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * This is never called, because the built in filesystem has taken over or
     * the generate url has handled it.
     */
    public function compileFile(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj)
    { }
    // - compileFileService($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * This is never called because the built in file system has taken over.
     */
    public function compileFileService(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj)
    { }
    // - catFiles(&$fileDatas,$compilerObj)
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
    // - generateUrl($fileData)
    /**
     * This is called when using cdn or when it fails to download
     *
     * @param string $fileData The data to extract the URL for
     * @return string the url
     */
    public function generateUrl($fileData) {
        if ( !empty($fileData['url']) ) {
            return $fileData['url'];
        }
        return sprintf(
            $fileData['url_map'],
            ( tgif_http::is_secure_request() ) ? 's' : '',
            ( $this->_options['use_compressor'] ) ? $fileData['compress_ext'] : '',
            $fileData['version']
        );
    }
}
