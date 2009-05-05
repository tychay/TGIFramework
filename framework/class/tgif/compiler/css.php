<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tgif_compiler_css}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2008-2009 Tagged, Inc, 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_compiler_css
/**
 * Compiling CSS files using YUI Compressor.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 * @author Nate Kresge <nkgresge@tagged.com> added hessian service
 * @author Itai Zukerman <izukerman@tagged.com> add hessian UDP protocol
 */
class tgif_compiler_css extends tgif_compiler
{
    // {{{ + $_html, $_html_with_id
    protected $_html_with_id = '<link rel="stylesheet" type="text/css" href="%1$s" id="%2$s" />';
    protected $_html = '<link rel="stylesheet" type="text/css" href="%s" />';
    // }}}
    // {{{ - $_javaCmd
    /**
     * file path of the java engine
     * @var string
     */
    private $_javaCmd;
    // }}}
    // {{{ - $_yuiCompressor
    /**
     * file path of yui compresor
     * @var string
     */
    private $_yuiCompressor;
    // }}}
    // {{{ __construct($options)
    /**
     */
    function __construct($options)
    {
        foreach ($options as $key=>$value) {
            switch($key) {
                case 'java_cmd'         : $this->_javaCmd = $value; break;
                case 'yui_compressor'   : $this->_yuiCompressor = $value; break;
            }
        }
        $this->_useCompiler = ($this->_javaCmd && $this->_yuiCompressor);
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
        //printf('%s -jar %s --type css -o %s %s', $this->_javaCmd, $this->_yuiCompressor, escapeshellarg($destPath), escapeshellarg($sourcePath));
        exec(sprintf('%s -jar %s --type css -o %s %s', $this->_javaCmd, $this->_yuiCompressor, escapeshellarg($destPath), escapeshellarg($sourcePath)));
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
            $return = $h->compile('css', $sourcePaths, $targetPath);
        } catch (Exception $e) {
            trigger_error("Exception in css compiler: " . $e->getMessage(), E_USER_NOTICE);
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
        if (!preg_match_all('!\*\s+@import\s+url\(["\']([^ ]+)["\']\)!', $data, $matches, PREG_PATTERN_ORDER)) {
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
        return sprintf('%s/%s.css', substr($signature,0,1), substr($signature,1,10));
    }
    // }}}
}
// }}}
?>
