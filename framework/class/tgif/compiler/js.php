<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tag_compiler_js}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2008-2009 Tagged, Inc, 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_compiler_js
/**
 * Compiling Javascript files using YUI Compressor with support for YUI
 * libraries.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 * @author Nate Kresge <nkgresge@tagged.com> added hessian service
 * @author Itai Zukerman <izukerman@tagged.com> added hessian UDP protocol
 */
class tgif_compiler_js extends tgif_compiler
{
    // CONSTANTS
    // {{{ + $_html, $_html_with_id
    protected $_html_with_id = '<script type="text/javascript" src="%1$s" id="%2$s"></script>';
    protected $_html = '<script type="text/javascript" src="%s"></script>';
    // }}}
    // {{{ - $_javaCmd
    /**
     * file path of the java engine
     * @var string
     */
    private $_javaCmd = '';
    // }}}
    // {{{ - $_yuiCompressor
    /**
     * file path of yui compresor
     * @var string
     */
    private $_yuiCompressor = '';
    // }}}
    // {{{ __construct($options)
    /**
     * @todo consider mapping a setting for the path to the yui settings
     */
    function __construct($options)
    {
        foreach ($options as $key=>$value) {
            switch($key) {
                case 'java_cmd'         : $this->_javaCmd = $value; break;
                case 'yui_compressor'   : $this->_yuiCompressor = $value; break;
                case 'yui_version'      : $this->_yuiSettings['version']    = $value; break;
                case 'yui_rollup'       : $this->_yuiSettings['rollup']     = $value; break;
                case 'yui_filter'       : $this->_yuiSettings['filter']     = $value; break;
                case 'yui_combine'      : $this->_yuiSettings['combine']    = $value; break;
                case 'yui_optional'     : $this->_yuiSettings['optional']   = $value; break;
                case 'yui_cdn'          : $this->_yuiSettings['cdn']        = $value; break;
            }
        }
        $this->_useCat      = ($this->_yuiSettings['combine']);
        $this->_useCompiler = ($this->_javaCmd && $this->_yuiCompressor && (strcmp($this->_yuiSettings['filter'],'min')===0));
        $this->_yuiModules  = include(sprintf('%s/yui-%s.php',dirname(__FILE__),$this->_yuiSettings['version']));
        parent::__construct($options);
    }
    // }}}
    // {{{ - _compileFileExec($targetPath, $sourcePath)
    /**
     * Exec command to compile from one file to another
     *
     * This version uses YUI compressor and java to compile the file.
     *
     * @param $sourcePath string the catenated file to compile
     * @param $destPath string where to dump the final output to
     */
    protected function _compileFileExec($sourcePath, $destPath)
    {
        if (!file_exists($this->_javaCmd)) { return false; }
        if (!file_exists($this->_yuiCompressor)) { return false; }
        exec(sprintf('%s -jar %s --type js -o %s %s', $this->_javaCmd, $this->_yuiCompressor, escapeshellarg($destPath), escapeshellarg($sourcePath)));
        return true;
    }
    // }}}
    // {{{ - _compileFileService($targetPath, $sourcePaths)
    /**
     * Service command to compile a file list.
     *
     * Concatenate and compile. This service should block until the file is
     * written. The file should not be written until the service is done. If
     * you run into an error condition, just return false.
     *
     * This version does nothing but return false.
     *
     * @param $destPath string where to dump the final output to
     * @param $sourcePaths array the files to compile (in order)
     */
    protected function _compileFileService($targetPath, $sourcePaths)
    {
        global $_TAG;
        $h = new tag_service_hessianClient(
            $_TAG->config('js_css_compiler_udp_host'),
            array('use_udp'  => true));
        try {
            $return = $h->compile('js', $sourcePaths, $targetPath);
        } catch (Exception $e) {
            trigger_error("Exception in js compiler: " . $e->getMessage(), E_USER_NOTICE);
            return false;
        }
        // return $return;
        // we find the compiled files are still missing suggesting fstat is cached
        // a workaround is to always return false;
        return false;
    }
    // }}}
    // {{{ - _findDependencies($filePath)
    /**
     * Find all the embeded dependencies in the codebase
     * @return array list of "files" that depend on this one
     */
    protected function _findDependencies($filePath)
    {
        $data = file_get_contents($filePath);
        if (!preg_match_all('!\*\s+@requires\s+([^ ]+)!', $data, $matches, PREG_PATTERN_ORDER)) {
            return array();
        }
        foreach ($matches[1] as $key=>$value) {
            $matches[1][$key] = trim($matches[1][$key]);
        }
        return $matches[1];
    }
    // }}}
    // {{{ - _generateTargetFileName($fileListData)
    /**
     * Figure out target file name in a normalized manner.
     *
     * @return string
     */
    protected function _generateTargetFileName($fileListData)
    {
        $signature = parent::_generateTargetFileName($fileListData);
        return sprintf('%s/%s.js', substr($signature,0,1), substr($signature,1,10));
    }
    // }}}
    // YUI support
    // {{{ - $_yuiSettings
    /**
     * Various settings for including YUI libraries
     * @var array
     */
    private $_yuiSettings = array(
        'version'       => '2.6.0',
        'rollup'        => true,    // does nothing currently
        'filter'        => 'min',   // could also be 'debug' or nothing
        'combine'       => true,    // when more than one file combine into a single
        'optional'      => false,   // include optional dependencies
        'cdn'           => 'yahoo', // can be 'google' or 'tagged' to use that cdn instead
    );
    // }}}
    // {{{ - $_yuiModules
    /**
     * Dependency tree for YUI libraries
     *
     * Note that this isn't entirely accurate because earlier
     * versions of YUI might have slightly different dependencies.
     *
     * The current version is 2.6.0, but we are using 2.5.3 because
     * find as you type needs to be updated.
     * @var array
     */
    private $_yuiModules = array();
    // }}}
    // {{{ - _buildUrls($fileListData)
    /**
     * Returns a list of files into urls.
     *
     * This has special handling for the case where we have YUI libs
     */
    protected function _buildUrls($fileListData)
    {
        $files = array();
        $yui_libs = array();
        foreach ($fileListData as $file_data) {
            if ($file_data['is_file']) {
                $files[] = $file_data;
            } else {
                $yui_libs[] = $file_data;
            }
        }
        $lib_urls = array();
        // do YUI libs first!
        return array_merge($this->_generateYuiUrls($yui_libs), parent::_buildUrls($files));
    }
    // }}}
    // {{{ - _generateFileData($fileName)
    /**
     * Generate file data.
     *
     * This version works differently if the library is YUI
     */
    protected function _generateFileData($fileName)
    {
        if (strcmp(substr($fileName,0,6), 'YAHOO/')!==0) {
            return parent::_generateFileData($fileName);
        }
        $lib_name = substr($fileName,6);
        if (!array_key_exists($lib_name, $this->_yuiModules)) {
            return false;
        }
        $returns = $this->_yuiModules[$lib_name];
        $returns['name'] = $fileName;
        $returns['is_file'] = false;
        if (!array_key_exists('core',$returns)) {
            $returns['core'] = $lib_name;
        }
        if (!array_key_exists('map',$returns)) {
            $returns['map'] = $lib_name;
        }
        // need to deal with rollup library missing things {{{
        if (!array_key_exists('dependencies',$returns)) {
            $returns['isExperimental'] = true; // already minable
            $returns['dependencies'] = array();
        }
        // }}}
        if ($this->_yuiSettings['optional'] && !empty($returns['optionals'])) {
            $returns['dependencies'] = array_merge($returns['dependencies'],$returns['optionals']);
        }
        return $returns;
    }
    // }}}
    // {{{ - _generateYuiUrls($fileListData)
    /**
     * Returns a list of urls for the YUI compile
     *
     * This has special handling for the case where we have YUI libs
     * @params $fileListData an array of libs consisting of...
     * - name: the name of the library in the dependency tree
     * - is_file: false
     * - map: the name of the library as a part of the url
     * - isCore: whether library is a core library
     * - isLoaderCore: whether library is yui laoder + core
     * - isUtil: whether library is part of utilities
     * - isExperimental: whether library is experimental (not min/debug able)
     * - dependencies: the dependencies of the library (already exhausted)
     * - optionals: optional dependencies of library (already exhasted)
     *
     * In some cases, the library is a rollup, in these cases the first three
     * params above are in there +:
     * - lookup: which of the names above are to be looked up for already
     *      marked dependencies
     */
    private function _generateYuiUrls($fileListData)
    {
        global $_TAG;
        if (empty($fileListData)) { return array(); }
        $paths = array();
        foreach ($fileListData as $file_data) {
            // handle extension {{{
            switch ($this->_yuiSettings['filter']) {
                case 'min'   : $extension =  '-min'; break;
                case 'debug' : $extension = '-debug'; break;
                default      : $extension = '';
            }
            // experimental libraries have no min/debug version
            if ($file_data['isExperimental']) { $extension = ''; }
            // }}}
            // edge case double rollup: {{{
            // handle edge case where we include both a rollup and a file inside
            // a rollup in the same call. Note that if the file inside a rollup
            // happens to be called before the rollup, we'll have a redundancy
            // which may cause issues.
            if (in_array($file_data['name'],$this->_outputList)) {
                continue;
            }
            // }}}
            $paths[] = sprintf('%s/build/%s/%s%s.js',
                $this->_yuiSettings['version'],
                $file_data['core'],
                $file_data['map'],
                $extension
                );
            // if rollup, make sure output list is updated with all libraries {{{
            if (array_key_exists('lookup', $file_data)) {
                $lookup = $file_data['lookup'];
                foreach ($this->_yuiModules as $key=>$temp_data) {
                    if (array_key_exists($lookup,$temp_data) && $temp_data[$lookup]) {
                        $this->_outputList[] = 'YAHOO/'.$key;
                    }
                }
            }
            // }}}
        }
        if (tag_http::is_secure_request()) {
            $cdn = $_TAG->config('url_secure_static').$_TAG->config('local_yui_relpath');
        } else {
            if ($this->_yuiSettings['combine'] && count($paths)!=1) {
                return array('http://yui.yahooapis.com/combo?'.implode('&',$paths));
            }
            $returns = array();
            // handle cdn {{{
            switch ($this->_yuiSettings['cdn']) {
                case 'google': $cdn = 'http://ajax.googleapis.com/ajax/libs/yui/'; break;
                case 'tagged': $cdn = $_TAG->url->chrome($_TAG->config('local_yui_relpath')); break;
                default: $cdn = 'http://yui.yahooapis.com/';
            }
        }
        // }}}
        foreach ($paths as $path) {
            $returns[] = $cdn.$path;
        }
        return $returns;
    }
    // }}}
}
// }}}
?>
