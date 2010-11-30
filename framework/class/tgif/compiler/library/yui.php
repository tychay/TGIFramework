<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tag_compiler_library_yui}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2010 terry chay (parts of the code may be c.2008 Tagged Inc.)
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
// {{{ tgif_compiler_library_yui
/**
 * (abstract) class for compiling files using YUI Compressor with support for
 * YUI libraries.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 * @todo add support for verison YUI 3
 */
class tgif_compiler_library_yui implements tgif_compiler_library
{
    // {{{ - $_options
    /**
     * Configuration options for this. Here are the keys:
     * - version (string): The YAHOO User Interface Library version. Currently
     *   3 is not supported.
     * - use_cdn (string): If not set, it will use local files. If set, it
     *   must be 'yahoo' or 'google' or the base url of a cdn repository set
     *   up similarly.
     * - use_combine (boolean): If use_cdn is yahoo, then there is a new trick
     *   up its sleeve--the system can combine the remote cdn into a single
     *   call. Google doesn't offer it. If anything else, it is assumed that
     *   there is a combo script set up identically to Yahoo's repository.
     *   Note this only kicks in if "use_cat" is done on the parent.
     * - use_optional (boolean): include optional packages when including the
     *   main.
     * - filter (string): you can put "debug" here. Note that if compile is
     *   called, the filter is assumed to be "min". There is no such thing
     *   as "beta" as "-beta" is part of the name.
     * - base_path (string): If not using cdn, then this is the path to use
     *   to find the path in the file system.
     * - base_url (string): If not using cdn, then this is how to reference
     *   the file in the base path (because it is not in res dir but is marked
     *   as a resource.
     *
     * Unsupported:
     * - use_rollup (boolean): prefer the rollup javascripts instead of the
     *   non-packaged javascripts.
     * @var array
     */
    protected $_options = array(
        'version'       => '2.8.2r1',
        'use_cdn'       => 'yahoo',
        'use_combine'   => true,
        'use_rollup'    => true, //does nothing currently
        'filter'        => '',
        'use_optional'  => false,
        'base_dir'      => '.',
        'base_url'      => '', 
    );
    // }}}
    // {{{ - $_moduleInfo
    /**
     * Dependency tree for YUI libraries
     *
     * Note that this isn't entirely accurate because earlier
     * versions of YUI might have slightly different dependencies that were
     * created before a YAHOO.loader existed for it (and automated).
     *
     * This is needed to figure what libraries are available and their
     * dependencies. In YUI 2.x, it is in YAHOO.loader.moduleInfo.
     *
     * This is the main compatibility issue between this and YUI 3, as the
     * latter has a very different format
     *
     * To build your own script:
     * 1) Load {@link jsonifyyui.html} in a web browser
     * 2) Append the version number ot the query string "?(version)"
     * 3) Copy the text box
     * 4) create a resource file (yui-(version).json) as loaded by {@link
     *    _loadYuiModuleInfo()} 
     * 5) paste and save.
     *
     * @var array
     */
    protected $_moduleInfo;
    // }}}
    // CONSTRUCT
    // {{{ __construct($options,$type)
    /**
     * Save the options and load the $_moduleInfo.
     */
    function __construct($options, $type)
    {
        $this->_options = array_merge($this->_options, $options);
        // google doesn't have combo app
        if ( $this->_options['use_combine']
          && $this->_options['use_cdn'] == 'google' )
        {
                $this->_options['use_combine'] = false;
        }
        // test filter
        if ( !in_array($this->_options['filter'], array('min','','debug')) ) {
            $this->_options['filter'] = 'min';
        }

        $this->_moduleInfo = $this->_loadYuiModuleInfo($this->_options['version'], $type);
    }
    // }}}
    // {{{ _loadYuiModuleInfo($version[,$type])
    private function _loadYuiModuleInfo($version, $type='')
    {
        $returns = json_decode(tgif_file::get_contents(sprintf('%s/yui/yui-%s.json',TGIF_RES_DIR, $version)), true);
        if (!$type) { return $returns; }

        foreach ($returns as $lib=>$data) {
            if ($data['type'] !== $type) {
                unset($returns[$lib]);
            }
        }
        return $returns;
    }
    // }}}
    // UTILITY FUNCTIONS
    // {{{ - _extractYui2Module($libName, $type)
    /**
     * Turn a YUI (2.x) library into file data
     *
     * The stucture of moduleInfo in YUI 2.x is a hash indexed by $libName
     * Each can have the following keys:
     * - type: js or css
     * - path: the relative location of the file (min version)
     * - requires: packages dependencies
     * - optional: other package dependencies that max out the library
     * - supersedes: if this package is in, then these packges dont need to be
     *      included since they already are!
     * - pkg: this is a subpackage of something else. This is not supported.
     * - rollup: the number of libraries this rolls up
     *
     * @param string $libName YUI module
     * @param string $type If set, only allow this file type through
     * @return array either empty array or file data type array
     */
    protected function _extractYui2Module($libName, $type='')
    {
        // no such module? {{{
        if ( !array_key_exists($libName, $this->_moduleInfo)) {
            return array();
        }
        $module_info = $this->_moduleInfo[$libName];
        // }}}
        // only support a certain file type {{{
        if ( $type && strcmp($type,$module_info['type'])!==0 ) {
            return array();
        }
        // }}}
        // figure dependencies {{{
        $dependencies = array();
        if ( array_key_exists('requires',$module_info) ) {
            foreach($module_info['requires'] as $lib) {
                $dependencies[] = 'YAHOO/'.$lib;
            }
        }
        // optional packages
        if ( $this->_options['use_optional']
          && array_key_exists('optional',$module_info)
           ) {
            foreach($module_info['optional'] as $lib) {
                $dependencies[] = 'YAHOO/'.$lib;
            }
        }
        // }}}
        // figure provides {{{
        $provides = array();
        if ( array_key_exists('provides',$module_info) ) {
            foreach($module_info['supersedes'] as $lib) {
                $provides[] = 'YAHOO/'.$lib;
            }
        }
        // }}}
        // generate path {{{
        $path = sprintf('%s/build/%s', $this->_options['version'], $module_info['path']);
        switch ($this->_options['filter']) {
            case ''      : $path = str_replace('-min','',$path); break;
            case 'debug' : $path = str_replace('-min','-debug',$path); break;
            case 'min'   :
            default      : break;
        }
        // }}}

        $file_name = 'YAHOO/'.$libName;
        return array(
            'name'          => $file_name,
            'is_resource'   => true,
            'library'       => get_class($this),
            'dependencies'  => $dependencies,
            'file_path'     => '', //append later
            'path'          => $path,
            'provides'      => $provides,
        );
    }
    // }}}
    // SIGNATURE METHODS:
    // {{{ - generateSignature($fileName,$compileObj)
    /**
     * What YUI version number
     *
     * The old system used to not set the signature, this meant you had
     * to clear the cache (usually smem) when updating Yahoo. Now the
     * signature is the YUI version number.
     *
     * @param string $fileName the name of the library file
     * @return string the signature
     */
    public function generateSignature($fileName, $compileObj)
    {
        //var_dump('generateSignature',$fileName,$this);die;
        return $this->_options['version'];
    }
    // }}}
    // {{{ - generateFileData($fileName,$compileObj)
    /**
     * Override this.
     */
    public function generateFileData($fileName, $compileObj) { }
    // }}}
    // {{{ - compileFile($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * Override this.
     */
    public function compileFile(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj) { }
    // }}}
    // {{{ - compileFileService($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * Forward to compileFile()
     */
    public function compileFileService(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj) {
        return $this->compileFile($sourceFileData,$targetFileName,$targetFilePath,$compilerObj);
    }
    // }}}
    // {{{ - catFiles($fileDatas,$compilerObj)
    /**
     * Allow you to catenate files at the front (or in place).
     *
     * If it is a use_cdn and use_combine is true, then this replaces the
     * YUI file datas with a single combo call YUI filedata
     *
     * If not a cdn, then this replaces the YUI file_data with local file data
     * the next step will roll these up! WARNING: this might have unintended
     * consequences if the library is by itself, hence the 'url' parameter
     * is added just in case.
     *
     * @param array $fileDatas a list of file data to catenate together. You
     * can manipulate this result set however you want. But be warned, if you
     * do no purge all library instances, this will get ugly.
     * @return array a list of file data that is separate from regular file
     * catenation.
     */
    public function catFiles(&$fileDatas,$compilerObj)
    {
        $library_name = get_class($this);
        //var_dump('catFiles',$fileDatas,$this->_options);die;
        if ( $this->_options['use_cdn'] ) {
            // extract list of files
            $yui_files  = array();
            $paths      = array();
            foreach ($fileDatas as $key=>$fileData) {
                if ( $fileData['library'] != $library_name ) { continue; }
                $yui_files[$key] = $fileData;
                $paths[] = $fileData['path'];
                unset($fileDatas[$key]);
            }
            if ( !$this->_options['use_combine'] ) {
                return $yui_files;
            }
            if (count($paths) == 0) {
                return array();
            } elseif (count($paths) == 1) {
                $path = $path[0];
                $file_name = $fileDatas[0]['name'];
            } else {
                $path = 'combo?'.implode('&', $paths);
                sort($paths);
                $file_name = implode('_',$paths);
            }
            $return = array(
                'name'          => $file_name,
                'is_resource'   => false,
                'library'       => $library_name,
                'dependencies'  => array(), //not needed at this point
                'signature'     => $this->generateSignature($file_name,$compilerObj),
                'file_path'     => '', //not needed in this point
                'path'          => $path,
                'provides'      => array(), //not needed at this point
            );
            return array( 'yui_combo' => $return );
        }
        // local file system: replace with local version in place
        foreach ($fileDatas as &$fileData) {
            if ( $fileData['library'] != $library_name ) { continue; }
            $url       = $this->generateUrl($fileData);
            $file_path = $this->_options['base_dir'].'/'.$fileData['path'];
            $fileData['library']    = '';
            $fileData['file_path']  = $file_path;
            $fileData['url']        = $url; //pre-empt if caught alone
            // do signature last
            $fileData['signature']  = $compilerObj->signature($fileData);
        }
        return array();
    }
    // }}}
    // {{{ - generateUrl($fileData)
    /**
     * Turn a file data into a full URL.
     *
     * Note if the resource is really a local file. then it is suggested you
     * modify {@link cat_files()} to remove the 'library' property for these
     * files and let the automated routine handle it.
     *
     * @param string $fileName the name of the library file
     * @return string the url
     */
    public function generateUrl($fileData)
    {
        //var_dump('generateUrl',$fileData,$this->_options);die;
        if ( $this->_options['use_cdn'] ) {
            switch ( $this->_options['use_cdn'] ) {
                case 'yahoo': $base = 'http://yui.yahooapis.com/'; break;
                case 'google': $base = 'http://ajax.googleapis.com/ajax/libs/yui/'; break;
                default: $base = $this->_options['use_cdn']; break;
            }
            return $base.$fileData['path'];
        }
        // must be a local file
        return $this->_options['base_url'].$fileData['path'];
    }
    // }}}
}
// }}}
?>
