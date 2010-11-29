<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tag_compiler_js}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2008-2009 Tagged, Inc, 2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_compiler_js
/**
 * Compiling Javascript files using YUI Compressor.
 *
 * YUI libraries support was moved to {@link tgif_compiler_library_yuijs} which
 * can be added by adding the class name to the 'libraries' config.
 *
 * Localization was removed
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 * @todo add back in l10n (through external library)
 */
class tgif_compiler_js extends tgif_compiler
{
    // {{{ - _regex_import
    /**
     * Regular expression for extracting js files
     *
     * Must be in a jsdoc style @requires comment
     */
    const _regex_import = '!\*\s+@requires\s+([^\s]+)!';
    // }}}
    // OVERRIDES
    // {{{ __construct($options)
    /**
     * Same as parent, but allows presets 'bin_java' and 'yui_compressor'
     * variables.
     *
     * To make life easier, overwrite 'use_compiler' if something goes wrong.
     */
    function __construct($options)
    {
        //global $_TAG;
        if ( !isset($options['bin_java']) ) {
            $options['bin_java'] = $_TAG->config('bin_java');
        }
        if ( !isset($options['yui_compressor']) ) {
            $options['yui_compressor'] = $_TAG->config('yui.compressor_jar', true);
        }

        parent::__construct($options);

        $this->_options['use_compiler'] = $this->_options['use_compiler'] && file_exists($options['bin_java']) && file_exists($options['yui_compressor']);
    }
    // }}}
    // {{{ - _findDependencies($filePath)
    /**
     * Find all the embeded dependencies in the codebase.
     *
     * JS file dependencies must be in a jsdoc comment block and have the
     * following format:<pre>
     * * @requires filename.js Some comment here.
     * </pre>
     */
    protected function _findDependencies($filePath,$fileName)
    {
        $data = file_get_contents($filePath);
        if ( !preg_match_all(self::_regex_import, $data, $matches, PREG_PATTERN_ORDER) ) {
            return array();
        }
        foreach ($matches[1] as $key=>$value) {
            $matches[1][$key] = trim($matches[1][$key]);
        }
        return $matches[1];
    }
    // }}}
    // {{{ - _generateTargetFileName($fileDatas)
    /**
     * Add .js to the target name, and put it in a subdirectory
     */
    protected function _generateTargetFileName($fileDatas)
    {
        $signature = parent::_generateTargetFileName($fileDatas);
        return sprintf('%s/%s.js', substr($signature,0,1), substr($signature,1,10));
    }
    // }}}
    // {{{ - _generateHtml($url,$properties)
    /**
     * Make a js style tag
     */
    protected function _generateHtml($url, $properties)
    {
        $attributes = '';
        foreach ($properties as $key=>$value) {
            if ( $key == 'id' ) {
                $value = 'js'.$value;
            }
            $attributes .= sprintf(' %s="%s"', $key, htmlentities($value));
        }
        return sprintf('<script language="javascript" type="text/javascript" src="%s"%s></script>',
            htmlentities($url),
            $attributes
        );
    }
    // }}}
    // {{{ - _compileFileExec($sourcePath, $destPath, $backgroundPath)
    /**
     * Exec command to compile from one file to another
     *
     * This version uses YUI compressor and java to compile the file.
     */
    protected function _compileFileExec($sourcePath, $destPath, $backgroundPath='')
    {
        //global $_TAG;
        $cmd = sprintf('%s -jar %s --type js -o %s %s',
            $this->_options['bin_java'],
            $this->_options['yui_compressor'],
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
            get_class($this).'::_compileFileExec',
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
    // }}}
    // DEPRECATED
    // {{{ + compile_file_service(&$sourceFileData, $targetFilePath)
    /**
     * Service command to compile a file list.
     *
     * This service should block until the file is written. The file should
     * not be written until the service is done. If you run into an error
     * condition, just return the $sourceFileData.
     *
     * This is an adaptation of what Tagged was using to compile files.
     *
     * @deprecated untested
     * @author Nate Kresge <nkgresge@tagged.com> added hessian service
     * @author Itai Zukerman <izukerman@tagged.com> add hessian UDP protocol
     * @author terry chay <tychay@php.net> modified for compatibility
     * @param $destPath string where to dump the final output to
     * @param $sourcePaths array the files to compile (in order)
     * @return boolean success
     */
    public static function compile_file_service($sourceFileData, $targetFilePath)
    {
        //global $_TAG;
        $h = new tag_service_hessianClient(
            $_TAG->config('js_css_compiler_udp_host'),
            array('use_udp'  => true)
        );
        try {
            $return = $h->compile('js', array($sourceFileData['file_path']), $targetPath);
        } catch (Exception $e) {
            trigger_error("Exception in js compiler: " . $e->getMessage(), E_USER_NOTICE);
            return false;
        }
        // we find the compiled files are still missing suggesting fstat is
        // cached a workaround is to always return failure mode.
        return false;
    }
    // }}}
    // L10N DEPRECATED
    // i18n support
    // {{{ X__construct($options)
    /**
     * @deprecated
     */
    function X__construct($options)
    {
        foreach ($options as $key=>$value) {
            switch($key) {
                case 'l10n_dir'         : $this->_l10nDir       = $value; break;
                case 'l10n_target_dir'  : $this->_l10nTargetDir = $value; break;
                case 'l10n_target_url'  : $this->_l10nTargetUrl = $value; break;
                case 'l10n_generator'   : $this->_l10nGenerator = $value; break;
            }
        }
    }
    // }}}
    // {{{ X__wakeup()
    /**
     * Restore missing defaults
     * @deprecated
     */
    function X__wakeup()
    {
        parent::__wakeup();
        $this->_strings = array();
    }
    // }}}
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
    // {{{ - X_buildFiles($fileListData[, $forceCat])
    /**
     * Returns a list of files into urls.
     *
     * This has special handling for the case where we have YUI libraries.
     * It also has handles the case where files have localizations. Note
     * that in order for this to work, we assume (as is the case) that you've
     * already called {@link tag_intl::set_locale()}.
     * @deprecated
     */
    protected function X_buildFiles($fileListData, $forceCat = false)
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
    // {{{ - X_generateFileData($fileName)
    /**
     * Generate file data.
     *
     * This version works differently if the library is YUI. It also checks for
     * localization stuff.
     *
     * Note that since signature is not modified, be sure to clear the cache
     * storing the file data (usually smem) before adding a localization string
     * file.
     * @deprecated
     */
    protected function X_generateFileData($fileName)
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
