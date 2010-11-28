<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tgif_compiler_css}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2008-2009 Tagged, Inc, 2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_compiler_css
/**
 * Compiling CSS files using YUI Compressor.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
class tgif_compiler_css extends tgif_compiler
{
    // {{{ - _regex_import
    /**
     * Regular expression for extracting css files
     */
    const _regex_import = '!@import\s+url\(["\']([^ ]+)["\']\)!';
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
     * CSS files can use an @import line to embed dependencies. They must use
     * the format:
     * @import url(...)
     *
     * Note that it is recommended that you embed this in the comments of the
     * css file instead of the css file itself. If you do not, you will end
     * up with a double include of the css file.
     *
     * Also note that dependencies, no matter where they are placed in the
     * actual file, are assumed to be placed in the beginning.
     *
     * Currently there is no absolute URL or absolute uris that are currently
     * allowed. There are no plans to add it because of the fungible status
     * of the concept of absolute uris in the framework.
     */
    protected function _findDependencies($filePath,$fileName)
    {
        $data = file_get_contents($filePath);
        if ( !preg_match_all(self::_regex_import, $data, $matches, PREG_PATTERN_ORDER) ) {
            return array();
        }
        $this_dir = dirname($filePath).'/';
        $ignore_path_len = strlen($filePath)-strlen($fileName);
        foreach ($matches[1] as $key=>$value) {
            $depend_file = trim($matches[1][$key]);
            // prune out relative paths {{{
            $test_file = preg_replace('/\w+\/\.\.\//', '', $this_dir.$depend_file);
            if ( file_exists($test_file) ) {
                $depend_file = substr($test_file, $ignore_path_len);
            }
            // }}}
            $matches[1][$key] = $depend_file;
        }
        return $matches[1];
    }
    // }}}
    // {{{ - _generateTargetFileName($fileDatas)
    /**
     * Adds .css to the target name, and put it in a supdirectory.
     */
    protected function _generateTargetFileName($fileDatas)
    {
        $signature = parent::_generateTargetFileName($fileDatas);
        return sprintf('%s/%s.css', substr($signature,0,1), substr($signature,1,10));
    }
    // }}}
    // {{{ - _generateHtml($url,$properties)
    /**
     * Make a css style tag include
     */
    protected function _generateHtml($url, $properties)
    {
        $attributes = '';
        foreach ($properties as $key=>$value) {
            if ( $key == 'id' ) {
                $value = 'css'.$value;
            }
            $attributes .= sprintf(' %s="%s"', $key, htmlentities($value));
        }
        return sprintf('<link rel="stylesheet" type="text/css" href="%s"%s />',
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
        $cmd = sprintf('%s -jar %s --type css -o %s %s',
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
            $return = $h->compile('css', array($sourceFileData['file_path']), $targetPath);
        } catch (Exception $e) {
            trigger_error("Exception in css compiler: " . $e->getMessage(), E_USER_NOTICE);
            return false;
        }
        // we find the compiled files are still missing suggesting fstat is
        // cached a workaround is to always return failure mode.
        return false;
    }
    // }}}
}
// }}}
?>
