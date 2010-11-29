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
                case 'l10n_dir'         : $this->_l10nDir       = $value; break;
                case 'l10n_target_dir'  : $this->_l10nTargetDir = $value; break;
                case 'l10n_target_url'  : $this->_l10nTargetUrl = $value; break;
                case 'l10n_generator'   : $this->_l10nGenerator = $value; break;
            }
        }
        $this->_useCat      = ($this->_yuiSettings['combine']);
        $this->_useCompiler = ($this->_javaCmd && $this->_yuiCompressor && (strcmp($this->_yuiSettings['filter'],'min')===0));
        //$this->_yuiModules  = include(sprintf('%s/maps/yui-%s.php',dirname(__FILE__),$this->_yuiSettings['version']));
        $this->_yuiModules  = $this->_loadYuiModuleInfo($this->_yuiSettings['version']);
        parent::__construct($options);
    }
    // }}}
    // {{{ __sleep()
    /**
     * Make sure temporary structures aren't stored between saves.
     *
     * This includes {@link $_strings}.
     */
    function _sleep()
    {
        return array_merge(parent::__sleep(), array( '_html', '_html_with_id', '_javaCmd', '_yuiCompressor','_l10nDir', '_l10nTargetDir', '_l10nTargetUrl', '_l10nGenerator','_yuiSettings','_yuiModules'));
    }
    // }}}
    // {{{ __wakeup()
    /**
     * Restore missing defaults
     */
    function __wakeup()
    {
        parent::__wakeup();
        $this->_strings = array();
    }
    // }}}
    // {{{ _loadYuiModuleInfo($version)
    private function _loadYuiModuleInfo($version)
    {
        return json_decode(file_get_contents(sprintf('%s/maps/yui-%s.json',dirname(__FILE__), $version)),true);
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
    // i18n support
    // {{{ - $_l10nDir
    /**
     * The base dir for finding localizations in PHP
     * @var string
     */
    private $_l10nDir = '';
    // }}}
    // {{{ - $_l10nTargetDir
    /**
     * The dir to write localized javascript strings
     * @var string
     */
    private $_l10nTargetDir = '';
    // }}}
    // {{{ - $_l10nTargetUrl
    /**
     * The dir of target url accessible from the web
     * @var string
     */
    private $_l10nTargetUrl = '';
    // }}}
    // {{{ - $_l10nGenerator
    /**
     * Name of function that will generate a localization into a target.
     * The function takes two values the source string table and the target
     * file name.
     * @var string|array
     */
    private $_l10nGenerator = array('tag_compiler_js','export_strings');
    // }}}
    // {{{ - _l10nFilename($fileData)
    /**
     * Generate a target filename to write the javascript file to given the
     * filedata to start with.
     *
     * Depending on the {@link $_signatureMode}:
     * global:    = filename + global version + language code
     * md5:       = signature of file data + language code
     * filemtime: = filename + lmtime + language code
     *
     * @param array $file_data the significant one is the l10n_path
     * @return string
     */
    private function _l10nFilename($fileData)
    {
        global $_TAG;
        $file_path = $fileData['l10n_path'];
        switch ($this->_signatureMode) {
            case 'md5':
                $base = md5(file_get_contents($file_path).tag_intl::get_locale());
                break;
            case 'create_key':
                $base = tag_encode::create_key(file_get_contents($file_path).tag_intl::get_locale());
                break;
            case 'global':
                $base = tag_encode::create_key($file_path.$_TAG->config('global_static_ver').tag_intl::get_locale());
                break;
            case 'filemtime':
            default:
                $base = tag_encode::create_key($file_path.filemtime($file_path).tag_intl::get_locale());
        }
        // Write to target directory
        return sprintf('%s/%s/%s.js', $this->_l10nTargetDir, substr($base,0,1), substr($base,1));
    }
    // }}}
    // {{{ - _l10nTargetToUrl($filePath)
    /**
     * Hack to generate URL from path name
     *
     * @param string $filePath Path to the file
     * @return string
     */
    private function _l10nTargetToUrl($filePath)
    {
        $basename_d = substr($filePath, strlen($this->_l10nTargetDir));
        return $this->_l10nTargetUrl.$basename_d;
    }
    // }}}
   // {{{ - export_strings($feFile,$outfile)
    /**
     * Generates an output javascript file for localized string export.
     *
     * @param string $feFile path to the input string file (free energy).
     * @param string $outfile path to the output file to write to.
     * @todo It's hard to read the tag_encode::js value (whcih is fragile
     * anyway). Let's change this to use json_encode and then do the heavy
     * lifting in javascript like in the strings.php file. :-)
     */
    public function export_strings($infile, $outfile)
    {
        // path to directory...
        $dirname = dirname($outfile);
        if (!file_exists($dirname)) {
            mkdir(dirname($outfile),0777,true);
        }
        $fp = fopen($outfile,'w');
        fwrite($fp,self::get_strings($infile));
        fclose($fp);
        // make sure the file is read/writeable by all
        chmod($outfile,0777);
    }
    // }}}
    // {{{ + get_strings($file)
    /**
     * Returns the json encoded contents of file.
     * 
     * @param string $file path to input string file
     * @return void
     */
    public static function get_strings($file) 
    {
        $strings = include($file);
        return sprintf('tagged.loadStringsDirect(%s);', json_encode($strings));
    }
    // }}}
    // {{{ - getString($stringPath[,$loadFile])
    /**
     * @param string $stringPath the string to lookup
     * @param string $loadFile The name of the js file it would have been located in
     */
    function getString($stringPath,$loadFile=null)
    {
        if (!is_null($loadFile)) {
            if (substr($loadFile,-3) == '.js') {
                $loadFile = substr($loadFile, 0, -3).'.php'; // strip .js
            }
            $loadFile = 'js' . DIRECTORY_SEPARATOR . $loadFile;
        }

        return tag_strings::get($stringPath, $loadFile);
    }
    // }}}
    // YUI support
    // {{{ - $_yuiSettings
    /**
     * Various settings for including YUI libraries
     * @var array
     */
    private $_yuiSettings = array(
        'version'       => '2.7.0',
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
     * The current version is 2.7.0, but we are using 2.5.3 because
     * find as you type needs to be updated using the script
     * {@link jsonifyyui.html} and {@link _loadYuiModuleInfo()} called in the
     * constructor.
     *
     * 
     * @var array
     */
    private $_yuiModules = array();
    // }}}
    // {{{ - _generateYuiUrls($fileListData)
    /**
     * Returns a list of urls for the YUI compile
     *
     * This has special handling for the case where we have YUI libs
     * @param $fileListData an array of libs consisting of...
     *
     * The new format is based on the YUI loader format ths is an array of libs
     * indexed by library name, and consisting of...
     * - name(added): the name of the library in the dependency tree
     * - dependencies: the dependencies of the library (already exhausted) from
     *   requires and optional below.
     * - type: should be "js" or ignored
     * - path: the path to the file (includes the -min version)
     * - requires (deleted): the dependencies of the library
     * - optional (merged into dependencies): optional dependencies of library
     * - supersedes: if a rollup these are the libraries to mark as already
     *   loaded
     * - rollup: the number of libraries this rolls up
     */
    private function _generateYuiUrls($fileListData)
    {
        global $_TAG;
        if (empty($fileListData)) { return array(); }
        $paths = array();
        foreach ($fileListData as $name => $file_data) {
            $path = $file_data['path'];
            // handle extension {{{
            switch ($this->_yuiSettings['filter']) {
                case 'min'   : break;
                case 'debug' : $path = str_replace('-min','-debug',$path);
                default      : $path = str_replace('-min','',$path);
            }
            // experimental libraries have no min/debug version (not true anymore?)
            // }}}
            // edge case double rollup: {{{
            // handle edge case where we include both a rollup and a file inside
            // a rollup in the same call. Note that if the file inside a rollup
            // happens to be called before the rollup, we'll have a redundancy
            // which may cause issues.
            if (in_array($file_data['name'],$this->_outputList)) {
                continue;
            }
            // only handle js files
            if (!strcmp('js',$file_data['type'])===0) { continue; }
            // }}}
            $paths[] = sprintf('%s/build/%s',
                $this->_yuiSettings['version'],
                $path
                );
            // if rollup, make sure output list is updated with all libraries {{{
            if (array_key_exists('rollup', $file_data)) {
                foreach ($file_data['supersedes'] as $library) {
                    $this->_outputList[] = 'YAHOO/'.$library;
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
            // }}}
        }
        foreach ($paths as $path) {
            $returns[] = $cdn.$path;
        }
        return $returns;
    }
    // }}}
    // OVERRIDES TO INJECT YUI and i18n
    // {{{ - _buildFiles($fileListData[, $forceCat])
    /**
     * Returns a list of files into urls.
     *
     * This has special handling for the case where we have YUI libraries.
     * It also has handles the case where files have localizations. Note
     * that in order for this to work, we assume (as is the case) that you've
     * already called {@link tag_intl::set_locale()}.
     */
    protected function _buildFiles($fileListData, $forceCat = false)
    {
        global $_TAG;
        $files = array();
        $yui_libs = array();
        // {{{ construct parallel lists of files (injecting and compiling strings)...
        foreach ($fileListData as $file_data) {
            if ($file_data['is_file']) {
                $files[] = $file_data;
                if (array_key_exists('l10n_path',$file_data)) {
                    // Generate target file name
                    $outfile = $this->_l10nFilename($file_data);
                    // Test existence of file
                    if (empty($file_data['exists'][$outfile])) {
                        // Compile file for locale
                        call_user_func($this->_l10nGenerator, $file_data['l10n_path'], $outfile);
                        $file_data['exists'][$outfile] = true;
                        // Update file_data in memcache
                        $key =  $this->_generateKey($file_data['name']);
                        tag_global_loader::save_to_cache($key, $file_data, $this->_useSmem, $this->_useMemcache);
                    }
                    // okay, now we can mess with the file_data hack the path
                    // to load in the l10n javascript this time. :-)
                    $file_data['dependencies'] = array($file_data['name']);
                    // TODO: probably should modify signature????
                    $file_data['name']        .= '.'.tag_intl::get_locale();
                    $file_data['url']          = $this->_l10nTargetToUrl($outfile);
                    $file_data['file_path']    = $outfile;
                    $file_data['exists']       = 'l10n self'; // clear it and self identify
                    $file_data['locale']       = tag_intl::get_locale(); // not really used
                    $files[] = $file_data;
                }
            } else {
                $yui_libs[] = $file_data;
            }
        }
        // }}}
        $lib_urls = array();
        // do YUI libs first!
        $return = array_merge($this->_generateYuiUrls($yui_libs), parent::_buildFiles($files, $forceCat));
        return $return;
    }
    // }}}
    // {{{ - _generateFileData($fileName)
    /**
     * Generate file data.
     *
     * This version works differently if the library is YUI. It also checks for
     * localization stuff.
     *
     * Note that since signature is not modified, be sure to clear the cache
     * storing the file data (usually smem) before adding a localization string
     * file.
     */
    protected function _generateFileData($fileName)
    {
        global $_TAG;
        if (strcmp(substr($fileName,0,6), 'YAHOO/')!==0) {
            $return = parent::_generateFileData($fileName);
            // inject in l10ns
            $file_path = sprintf('%s/%s.php', $this->_l10nDir, substr($fileName,0,-3)); //strip out .js
            if (!file_exists($file_path)) { return $return; }
            //echo("path=".$file_path."\n");
            $return['l10n_path'] = $file_path;
            $return['exists'] = array(); // list of lang files generated
            return $return;
        }
        // YAHOO UI Library case....
        // $fileName = YAHOO/$lib_name
        $lib_name = substr($fileName,6);
        if (!array_key_exists($lib_name, $this->_yuiModules)) {
            return false;
        }
        // JS support only
        $returns = $this->_yuiModules[$lib_name];
        if (strcmp('js',$returns['type'])!==0) { return false; }
        $returns['name'] = $fileName;
        $returns['is_file'] = false;
        $returns['dependencies'] = array();
        if (array_key_exists('requires',$returns)) {
            foreach($returns['requires'] as $lib) {
                $returns['dependencies'][] = 'YAHOO/'.$lib;
            }
            unset($returns['requires']);
        }
        if ($this->_yuiSettings['optional'] && !empty($returns['optional'])) {
            foreach($returns['optional'] as $lib) {
                $returns['dependencies'] = 'YAHOO/'.$lib;
            }
            unset($returns['optional']);
        }
        $returns['optional'] = array();
        return $returns;
    }
    // }}}
}
// }}}
?>
