<?php
/**
 * Holder of {@link tgif_compiler_css}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2008-2009 Tagged, Inc, 2009-2015 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
/**
 * Rendering css files (compressed using external compressor)
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
class tgif_compiler_css extends tgif_compiler
{
    // - _regex_import
    /**
     * Regular expression for extracting css files
     */
    const _regex_import = '!@import\s+url\(["\']([^ ]+)["\']\)!';

    // OVERRIDES
    // - _findDependencies($filePath)
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

    //  _generateTargetFileName($fileDatas)
    /**
     * Adds .css to the target name, and put it in a subdirectory.
     */
    protected function _generateTargetFileName($fileDatas)
    {
        $signature = parent::_generateTargetFileName($fileDatas);
        return sprintf('%s/%s.css', substr($signature,0,1), substr($signature,1,10));
    }

    // - _generateHtmls($urls,$properties,$queue)
    /**
     * Make a sequence of link tags.
     *
     * This is the old version that generated css style tag as a sequence of
     * @imports. Due to performance issues in some browsers, it is better to do a
     * sequence of link tags so let's use the default behavior
     *
     * @deprecated
     */
    /*
    protected function _generateHtmls($urls, $properties, $queue)
    {
        $attributes = '';
        if ( !isset($properties['id']) ) {
            $properties['id'] = 'css'.$queue;
        }
        foreach ($properties as $key=>$value) {
            $attributes .= sprintf(' %s="%s"', $key, htmlentities($value));
        }

        $html = '<style type="text/css"'.$attributes.'>';
        foreach($urls as $url) {
            $html .= sprintf('@import url(%s);', escapeshellarg($url));
        }
        $html .= '</style>';
        return array($html);
    }
    /* */

    // - _generateHtml($url,$properties)
    /**
     * Make a css style tag include
     *
     * Other attributes:
     * - crossorigin (html5): how to handle cross-origin requests
     * - hreflang: language code for linked document
     * - media: device linked document will be displayed
     * - sizes (html5): size of linked resource (for rel=icon)
     */
    protected function _generateHtml($url, $properties)
    {
        // patch the id to prepend css-
        if ( array_key_exists('id', $properties) ) {
            $properties['id'] = 'css-'.$properties['id'];
        }
        // set default properties rel=stylesheet, type=text/css
        $properties = array_merge(
            array(
                'rel'  => 'stylesheet',
                'href' => '',
                'type' => 'text/css',
            ),
            $properties
        );
        // always override href
        $properties['href'] = $url;

        $attributes = '';
        foreach ($properties as $key=>$value) {
            $attributes .= sprintf(' %s="%s"', $key, htmlentities($value));
        }

        return sprintf('<link%s />', $attributes);
    }

    // - _compileFileExec($sourcePath, $destPath, $backgroundPath)
    /**
     * Call compressor to compile one file to antoher
     */
    protected function _compileFileExec($sourcePath, $destPath, $backgroundPath='')
    {
        return call_user_func(array($this->_options['compressor'],'compress'), 'css', $sourcePath, $destPath, $backgroundPath);
    }

    // DEPRECATED
    // + compile_file_service(&$sourceFileData, $targetFilePath)
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
        global $_TAG;
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
}
