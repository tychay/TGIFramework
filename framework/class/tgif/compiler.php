<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tgif_compiler}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2008-2009 Tagged, Inc., c.2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_compiler
/**
 * (Abstract) base class for managing files that can be concatenated and
 * compiled for efficiency.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 * @author Nate Kresge <nkresge@tagged.com> added caching for file not found
 * @author Mark Jen <markjen@tagged.com> replaced hashing algorithm
 */
class tgif_compiler
{
    // {{{ + CACHE_FILE_NOT_FOUND_TTL
    /**
     * To spare requests, cache compiled file not found for how long? (seconds)
     */
    const CACHE_FILE_NOT_FOUND_TTL = 10;
    // }}}
    // PRIVATE PROPERTIES
    // options {{{
    // {{{ - $_resourceDir
    /**
     * @var string path to the base directory of resources
     */
    protected $_resourceDir = '';
    // }}}
    // {{{ - $_targetDir
    /**
     * @var string path to the base directory to put compiled files
     */
    private $_targetDir = '';
    // }}}
    // {{{ - $_trackFile
    /**
     * If set, this is the path to a file to keep a log compiled file combos
     * @var string|false
     */
    private $_trackFile = false;
    // }}}
    // {{{ - $_useCat
    /**
     * @var boolean whether or not we should use concatenation
     */
    protected $_useCat = true;
    // }}}
    // {{{ - $_useCompiler
    /**
     * @var boolean whether or not we should use compiler
     */
    protected $_useCompiler = false;
    // }}}
    // {{{ - $_useService
    /**
     * @var boolean whether or not we should call a service to compile
     */
    protected $_useService = false;
    // }}}
    // {{{ - $_useSmem
    /**
     * Whether or not we should save intermediate data to shared memory segments
     * @var boolean
     */
    protected $_useSmem = false;
    // }}}
    // {{{ - $_useMemcache
    /**
     * Whether or not we should save intermediate data to memcache
     * @var boolean
     */
    protected $_useMemcache = true;
    // }}}
    // {{{ - $_useReleaseName
    /**
     * Whether or not we should use the release name as part of the file path
     * @var boolean
     */
    protected _useReleaseName = false;
    // }}}
    // {{{ - $_signatureMode
    /**
     * the way to determine signature stalenmess
     * - global: use the global static version
     * - filemtime: use the last modified time
     * - md5: use an md5 of the file content
     * @var string
     */
    protected $_signatureMode = 'filemtime';
    // }}}
    // }}}
    // {{{ - $_fileData
    /**
     * An array indexed byi filename of the loading data associated with the
     * file. The following hash keys:
     * - name (required): identical to the index
     * - is_file (required): if it is a file in the filesystem (and should be
     *   cached)
     * - dependencies (required): a list of other files in the array that must
     *   be loaded for this to work
     * - signature: a signature representation of the file
     * - file_path: path to file in filesystem (via {@link _generateFilePath()}
     *
     * @var array
     */
    private $_fileData = array();
    // }}}
    // {{{ - $_queues
    /**
     * @var array
     * An array indexed by queuename of the files to be loaded in a given queue
     *
     * Note that this is not the actual files since some may be preloaded in
     * other outputted queues and there is a dependency tree to deal with.
     */
    private $_queues = array();
    // }}}
    // {{{ - $_outputList
    /**
     * @var array
     * An array of files that have already been outputted soemwhere else
     */
    protected $_outputList = array();
    // }}}
    // RESERVED FUNCTIONS
    // {{{ __construct($options)
    /**
     * This is protected because this class is actually abstract
     * @param $options array A bunch of options to set
     * @param $targetDir string The directory to write concatenated/compiled files to
     * @param $trackFile string If specified, this generates a updates a PHP file at runtime with a compilation list
     * @param $signatureMode string What sort of methodology to use when determining file info staleness
     * @param $shouldCatenate boolean Should we attempt to turn everything into a single file as often as possible?
     * @param $shouldCompile boolean Should we try to use the compiler whenever possible?
     * @param $useSmem boolean Should we cache file info into the shared memory segment?
     * @param $useMemecache boolean Should we cahce file info into the memcache?
     */
    protected function __construct($options)
    {
        foreach ($options as $key=>$value) {
            switch ($key) {
                case 'resource_dir' : $this->_resourceDir   = $value; break;
                case 'target_dir'   : $this->_targetDir     = $value; break;
                case 'track_file'   : $this->_trackFile     = $value; break;
                case 'use_cat'      : $this->_useCat        = $value; break;
                case 'use_compiler' : $this->_useCompiler   = $value; break;
                case 'use_service'  : $this->_useService    = $value; break;
                case 'use_smem'     : $this->_useSmem       = $value; break;
                case 'use_memcache' : $this->_useMemcache   = $value; break;
                case 'use_release_name' : $this->_useReleaseName = $value; break;
                case 'signature_mode' :
                if (in_array($value, array('global','filemtime','md5'))) {
                    $this->_signatureMode = $value;
                }
                break;
            }
        }
    }
    // }}}
    // {{{ __sleep()
    /**
     * Make sure temporary structures aren't stored between saves.
     */
    function _sleep()
    {
        return array('_resourceDir','_targetDir','_trackFile','_useCat','_useCompiler','_useService','_useSmem','_useMemcache','_useReleaseName','_signatureMode');
    }
    // }}}
    // {{{ __wakeup()
    /**
     * Restore missing defaults
     */
    function __wakeup()
    {
        $this->_fileData    = array();
        $this->_queues      = array();
        $this->_outputList  = array();
    }
    // }}}
    // PUBLIC METHODS
    // {{{ - add($fileName[,$queue])
    /**
     * Add a file to a compile queue.
     */
    function add($fileName, $queue='_default')
    {
        if (!array_key_exists($queue,$this->_queues)) {
            $this->_queues[$queue] = array();
        }
        if ($this->_grabFileData($fileName)) {
            $this->_queues[$queue][] = $fileName;
        }
    }
    // }}}
    // {{{ - generateSingle($files)
    /**
     * Generate the url of the a single file to call (no dependencies)
     * @param $files string|array a list of files to turn into a single file
     * @return array The first parameter is either true or false. If true,
     * then the second param is the URL to redirect to. If false, then the
     * second param is the data itself.
     */
    function generateSingle($files)
    {
        $file_list_data = array();
        // build list of files and dependencies {{{
        if (!is_array($files)) { $files = array($files); }
        foreach ($files as $file) {
            $this->_grabFileData($file);
            if (!array_key_exists($file,$this->_fileData)) { continue; }
            $file_list_data[$file] = $this->_fileData[$file];
        }
        // }}}
        if (empty($file_list_data)) { return array(false,''); }
        $files = $this->_buildFiles($file_list_data, true);
        if (count($files) > 1) {
            // more than one file? return the contents of all of them {{{
            $data = '';
            foreach ($files as $file) {
                if ($file[1]) {
                    $data .= file_get_contents($file[0]);
                } else {
                    $file_data = $file[0];
                    if ($file_data['is_file']) {
                        $data .= file_get_contents($file_data['file_path']);
                    }
                }
            }
            return array (false, $data);
            // }}}
        } else {
            // one file? return its url {{{
            $file = $files[0];
            if ($file[1]) {
                return array(true, $this->_generateTargetUrl($file[0]));
            } else {
                return array(true, $this->_generateSourceUrl($file[0]));
            }
            // }}}
        }
    }
    // }}}
    // {{{ - generate([$queue])
    /**
     * Generate the output to call for a given queue
     * @return array a bunch of individual calls (or one call)
     */
    function generate($queue='_default')
    {
        if (!array_key_exists($queue,$this->_queues)) {
            return array();
        }
        //printf("<pre>queue: %s\n, list:%s, output:%s pos:%s</pre>", $queue, var_export($this->_queues[$queue], true), var_export($this->_outputList, true),var_export(debug_backtrace(true)));
        $file_list_data = array();
        $returns = array();
        // build list of files and dependencies
        $this->_buildFileList($this->_queues[$queue], $file_list_data);
        //printf("<pre>queue: %s\n, filelistdata:%s</pre>", $queue, var_export($file_list_data, true));
        // queue has been emptied
        unset($this->_queues[$queue]);
        $urls = $this->_buildUrls($file_list_data);
        // add results to output list {{{
        foreach ($file_list_data as $filename=>$file_data) {
            $this->_outputList[] = $filename;
        }
        // }}}
        // special case - if single file, inject queue name as id {{{
        if ((count($urls) == 1) && (strcmp($queue,'_default')!==0)) {
            return array(sprintf($this->_html_with_id, $urls[0], $queue));
        }
        // }}}
        $returns = array();
        foreach ($urls as $url) {
            $returns[] = sprintf($this->_html, $url);
        }
        return $returns;
    }
    // }}}
    // {{{ - generateFlush()
    /**
     * Generate the output to call for all outstanding queues
     * @return array a bunch of individual calls (or one call)
     */
    function generateFlush($queue='_default')
    {
        $returns = array();
        foreach($this->_queues as $queueName=>$queue) {
            $returns = array_merge($returns, $this->generate($queueName));
        }
        return $returns;
    }
    // }}}
    // {{{ - printQueue([$queue])
    /**
     * Print out the HTML of a file
     */
    function printQueue($queue='_default')
    {
        echo implode("\n",$this->generate($queue));
        echo "\n";
    }
    // }}}
    // {{{ - printAll()
    /**
     * Print out the HTML of a file
     */
    function printAll()
    {
        echo implode("\n",$this->generateFlush());
        echo "\n";
    }
    // }}}
    // PRIVATE/PROTECTED METHODS
    // {{{ - _isValid($fileData)
    /**
     * Determine whether or not a file is valid.
     *
     * This only gets called if the filedata "is_file" param is true.
     * @return boolean
     */
    private function _isValid($fileData)
    {
        return ($this->_generateSignature($fileData['file_path']) == $fileData['signature']);
    }
    // }}}
    // {{{ - _grabFileData($fileName)
    /**
     * Make sure we load the filedata for a given file
     * @return true if the file exists and filedata exist
     */
    private function _grabFileData($fileName)
    {
        if (array_key_exists($fileName,$this->_fileData)) {
            return true;
        }
        // grab from cache {{{
        $key = $this->_generateKey($fileName);
        if ($file_data = tgif_global_loader::get_from_cache($key,$this->_useSmem,$this->_useMemcache)) {
            if ($this->_isValid($file_data)) {
                $this->_registerFileData($file_data);
                return true;
            }
        }
        // }}}
        // create file data
        $file_data = $this->_generateFileData($fileName);
        if (!$file_data) { return false; }
        if ($file_data['is_file']) {
            tgif_global_loader::save_to_cache($key, $file_data, $this->_useSmem, $this->_useMemcache);
        }
        $this->_registerFileData($file_data);
        return true;
    }
    // }}}
    // {{{ - _registerFileData($fileData)
    /**
     * Make sure we load all filedata that is dependent on this
     */
    private function _registerFileData($fileData)
    {
        $this->_fileData[$fileData['name']] = $fileData;
        foreach ($fileData['dependencies'] as $filename) {
            $this->_grabFileData($filename);
        }
    }
    // }}}
    // {{{ - _buildFileList($queue,$fileListData)
    /**
     * Recursive function to list of files to compile
     *
     * @param $queue array a list of files to render to html
     * @param $fileDataList array a list of filedata objeccts that need to be compiled
     * @return true if the file exists and filedata exist
     */
    private function _buildFileList($queue, &$fileListData)
    {
        foreach ($queue as $filename) {
            // don't compile files that don't exist
            if (!array_key_exists($filename, $this->_fileData)) { continue; }
            // handle case where file is already outputted
            if (in_array($filename, $this->_outputList)) { continue; }
            $file_data = $this->_fileData[$filename];
            if (!empty($file_data['dependencies'])) {
                $this->_buildFileList($file_data['dependencies'], $fileListData);
            }
            // add file to list if it isn't already there {{{
            if (array_key_exists($filename, $fileListData)) { continue; }
            $fileListData[$filename] = $file_data;
            // }}}
        }
    }
    // }}}
    // {{{ - _writeTrackFile($fileListData)
    /**
     * Make sure a track file gets written to.
     *
     * No need to check for {@link $_trackFile} because that is done before this
     * is called.
     *
     * This may sometimes produce redundancies as there are multiple ways to
     * have the same file list and be correct dependency orders with the same
     * file group. However, if the trackfile and the target directory is cleared
     * periodically, this will be unique since this should only be called when
     * the dyn file hasn't been written (even if it contains the same content
     * with different ordering).
     *
     * This is vulnerable to a race condition when used on the live site as
     * the file can be created (and thus this isn't called) by another process
     * during the time this file is being written to.
     */
    private function _writeTrackFile($fileListData)
    {
        // build the file list (without non-specific data) {{{
        $files = array();
        foreach ($fileListData as $file_list) {
            $files[] = $file_list['name'];
        }
        // }}}
        $idx = $this->_generateKeyFromFiles($files);
        // read existing track file data {{{
        $track_data = (file_exists($this->_trackFile))
                    ? include($this->_trackFile)
                    : array();
        // }}}
        // don't bother if it's already there
        if (array_key_exists($idx, $track_data)) { return; }
        // add track to data
        $track_data[$idx] = $files;
        // write file back with atomic commit {{{
        $tmpfile = tempnam(dirname($this->_trackFile),'trk_');
        $fp = fopen($tmpfile, 'w');
        // if file isn't writeable, don't bother
        if (!$fp) { return; }
        fwrite ($fp, sprintf('<?php return %s; ?>', var_export($track_data, true)));
        fclose($fp);
        if (file_exists($this->_trackFile)) {
            unlink($this->_trackFile);
        }
        link($tmpfile, $this->_trackFile);
        unlink($tmpfile);
        chmod($this->_trackFile,0666);
        // }}}
    }
    // }}}
    // {{{ - _generateKeyFromFiles($files)
    /**
     * @param array $files a list of files to be compiled
     */
    private function _generateKeyFromFiles($files)
    {
        sort($files);
        return tgif_encode::create_key(serialize($files));
    }
    // }}}
    // {{{ - _generateKey($fileName)
    /**
     * Turn a filename into a key array
     *
     * This is now protected because tag_compiler_js needs this function to
     * update the filedata when handling i18n intermediate compile
     * 
     * @return array
     */
    protected function _generateKey($fileName)
    {
        //global $_TAG; //runkit
        return array(get_class($this).'_'.tgif_encode::create_key($fileName), $_TAG->symbol());
    }
    // }}}
    // {{{ - _generateSignature($filePath)
    /**
     * Generate a signature from the file.
     *
     * This only gets called if the file data "is_file" parameter is true.
     *
     * @return string
     */
    private function _generateSignature($filePath)
    {
        //global $_TAG; //runkit
        switch ($this->_signatureMode) {
            case 'md5':
            if (!file_exists($filePath)) { return false; }
            return md5(file_get_contents($filePath));
            case 'global':
            return $_TAG->config('global_version');
            case 'filemtime':
            default:
            if (!file_exists($filePath)) { return false; }
            return filemtime($filePath);
        }
    }
    // }}}
    // {{{ - _generateFilePath($fileName)
    /**
     * @return mixed if return false then no file exists else
     *  it returns a filepath to the resource in question
     */
    private function _generateFilePath($fileName)
    {
        //global $_TAG; //runkit
        if($this->_useReleaseName) {
            $file_path = sprintf('%s/%s%s/%s',
                $_TAG->config('release_dir'),
                $_TAG->config('release_name'),
                $this->_resourceDir,
                $fileName);
        } else {
            $file_path = sprintf('%s%s/%s', $_TAG->config('dir_static'), $this->_resourceDir, $fileName);
        }
        if (!file_exists($file_path)) { return false; }
        return $file_path;
    }
    // }}}
    // {{{ - _generateTargetFileName($fileListData)
    /**
     * Figure out target file name in a normalized manner.
     *
     * Override this to do break into different paths and add file type
     * extensions.
     *
     * @return string This version generates a hash key based on the file list
     *  data, the compiler. This doesn't use the {@link $_useCat} because if it
     *  is catenated then the file list data will look different if multiple
     *  files.  This doesn't use the {@link $_useService} so that it can allow
     *  generation of files using an offline command line script that will
     *  allow service bypass.
     */
    protected function _generateTargetFileName($fileListData)
    {
        ksort($fileListData);
        return tgif_encode::create_key(serialize(array($this->_useCompiler, $fileListData)));
    }
    // }}}
    // {{{ - _generateSourceUrl($fileName)
    /**
     * Turn a filename into a url (when it is a soruce url
     * @return string
     */
    protected function _generateSourceUrl($fileName)
    {
        //global $_TAG; //runkit
        return (empty($fileData['url']))
               ? $_TAG->url->chrome($this->_resourceDir.'/'.$fileData['name'].'?v='.$_TAG->config('global_version'))
               : $_TAG->url->chrome($fileData['url'].'?v='.$_TAG->config('global_version'));
    }
    // }}}
    // {{{ - _generateTargetUrl($fileName)
    /**
     * Turn a filename into a url (when it is a target url)
     * @return string
     */
    private function _generateTargetUrl($fileName)
    {
        //global $_TAG; //runkit
        return $_TAG->url->chrome($this->_targetDir.'/'.$fileName);
    }
    // }}}
    // PROTECTED METHODS (FINAL)
    // {{{ - _compileFiles($fileName, $fileListData)
    /**
     * Enables the compiling of files to a destination file.
     *
     * If the file exists it will not compile, but will return true instead.
     * To determine existance, it will check the shared memory or memcache
     * (depending on configuration) for that existence. This avoids a
     * {@link file_exists()} call which on remote mounted volumes with
     * linux overloaded may be slow(?), or, at the very least, the speed
     * is undetermined whereas calls to shared memory or memcache are
     * strictly determined. On successful compile, it updates the cache
     * with this.
     *
     * Note that if the {@link $_signatureMode} is changed, then this
     * will assume the file doesn't exist!
     *
     * @param $fileName string the name of the file to use for the compiled
     *      file. This is unique across a file list.
     * @param $fileListData array the data of the files that need to be compiled
     *      in an order such that dependencies are resolved correctly.
     * @return boolean returns success or failure
     */
    protected function _compileFiles($fileName, $fileListData)
    {
        //global $_TAG; //runkit
        $file_path = sprintf('%s%s/%s', $_TAG->config('dir_static'), $this->_targetDir, $fileName);
        // check shared cache for whether file has been marked as existing {{{
        $cache_key = $this->_generateKey($file_path);
        $file_exists_signature = tgif_global_loader::get_from_cache($cache_key,$this->_useSmem,$this->_useMemcache);
        if ($file_exists_signature) {
            if ($file_exists_signature == $this->_generateSignature($file_path)) {
                return true;
            }
            if ('file_not_found' === $file_exists_signature) {
                return false;
            }
        }
        // }}}
        // check if file exists {{{
        if (file_exists($file_path)) {
            tgif_global_loader::save_to_cache($cache_key, $this->_generateSignature($file_path), $this->_useSmem, $this->_useMemcache);
            return true;
        } else {
            // temporarily cache file-not-founds to spare fs ops
            tgif_global_loader::save_to_cache($cache_key, 'file_not_found', $this->_useSmem, $this->_useMemcache, self::CACHE_FILE_NOT_FOUND_TTL);
        }
        // }}}
        if ($this->_trackFile) {
            $this->_writeTrackFile($fileListData);
        }
        $source_files = array();
        foreach ($fileListData as $key=>$file_data) {
            $source_files[] = $file_data['file_path'];
        }
        if ($this->_useCompiler && $this->_useService) {
            $success = $this->_compileFileService($file_path, $source_files);
        } else {
            $success = $this->_compileFileUsingFiles($file_path, $source_files);
        }
        // mark file as existing {{{
        if ($success) {
            tgif_global_loader::save_to_cache($cache_key, $this->_generateSignature($file_path), $this->_useSmem, $this->_useMemcache);
        }
        // }}}
        return $success;
    }
    // }}}
    // {{{ - _compileFileUsingFiles($filePath,$sourceFiles)
    /**
     * Enables the compiling of files using a safe temp file-based method
     * that calls {@link _compileFileExec()} for the key step.
     *
     * No need to check the cat flag but we do need to check the compile flag.
     * This is because upstream processing handles that case.
     * @param $filePath string the path to the file to use for the compiled file
     * @param $sourceFiles array the paths of the files that need to be compiled
     *      in an order such that dependencies are resolved correctly.
     * @return boolean always returns sucess
     */
    final protected function _compileFileUsingFiles($filePath, $sourceFiles)
    {
        //global $_TAG; //runkit
        $base_dir = dirname($filePath);
        // ensure path to source exists
        if (!file_exists($base_dir)) {
            mkdir($base_dir, 0777, true);
        }
        // generate temp file in the path
        // cat all data together {{{
        $cat_path = tempnam($base_dir,'cat_');
        $fp = fopen($cat_path, 'w');
        foreach ($sourceFiles as $paths) {
            fwrite($fp, file_get_contents($paths));
        }
        fclose($fp);
        // }}}
        // compile if necessary {{{
        if ($this->_useCompiler) {
            $com_path = tempnam($base_dir,'com_');
            $success = $this->_compileFileExec($cat_path, $com_path);
            unlink($cat_path);
            $source_file = $com_path;
        } else {
            $source_file = $cat_path;
            $success = true;
        }
        // }}}
        // atomic file copy {{{
        if ($success) {
            // link effectively copies files atomically in linux. however,
            // when running in a local vmware environment on windows host,
            // link doen't work. thus, we fail through to copy (which works :))
            if (!link($filePath, $source_file)) {
                copy($source_file, $filePath);
            }
            chmod($filePath,0666);
        }
        unlink($source_file);
        //chmod($com_path,0666);
        //chmod($cat_path,0666);
        // }}}
        return $success;
    }
    // }}}
    // OVERRIDES
    // {{{ + $_html, $_html_with_id
    /**
     * Mapping when outputting a queue to html as a single file
     * @var string
     */
    protected $_html_with_id = '%1$s id:%2$s';
    /**
     * Mapping when outputting a url to html
     * @var string
     */
    protected $_html = '%s';
    // }}}
    // {{{ - _findDependencies($filePath)
    /**
     * Find all the embeded dependencies in the codebase.
     *
     * @return array list of "files" that depend on this one
     */
    protected function _findDependencies($filePath)
    {
        return array();
    }
    // }}}
    // {{{ - _buildUrls($fileListData)
    /**
     * Turns a list of files into urls.
     *
     * This code assumes that all {@link $fileListData} has 'is_file' set to
     * true (they are all files). So if this is the case, there is no need to
     * ovverride this function.
     *
     * @return array a list of urls to include
     */
    protected function _buildUrls($fileListData)
    {
        // don't even think of generating an empty compiled file!
        $returns = array();
        if (empty($fileListData)) { return $returns; }
        $files = $this->_buildFiles($fileListData);
        foreach ($files as $file_parts) {
            if(!is_array($file_parts)) {
                $returns[] = $file_parts;
            } else if ($file_parts[1]) {
                $returns[] = $this->_generateTargetUrl($file_parts[0]);
            } else {
                $returns[] = $this->_generateSourceUrl($file_parts[0]);
            }
        }
        return $returns;
    }
    // }}}
    // {{{ - _buildFiles($fileListData[,$forceCat])
    /**
     * Turns a list of files into a new list of files (compiled and catted)
     *
     * This code assumes that all {@link $fileListData} has 'is_file' set to
     * true (they are all files). So if this is the case, there is no need to
     * ovverride this function.
     *
     * @param array $fileListData the list of files to compile/catenate
     * @param boolean $forceCat Set to true to force catenation (used by i18n
     * code) to turn the urls into a single file.
     * @return array a list of urls consisting of two parts, the second part
     * is true for target file or false for source files. If the second part
     * is true, then the first part is the target filename, if the second part
     * is false, then the second part is the file data (it seems weird but this
     * allows building via {@link _generateSourceUrl()} and
     * {@link _generateTargetUrl}.
     */
    protected function _buildFiles($fileListData, $forceCat=false)
    {
        // don't even think of generating an empty compiled file!
        $returns = array();
        if (empty($fileListData)) { return $returns; }
        if ($this->_useCat || $forceCat) {
            $target_file = $this->_generateTargetFileName($fileListData);
            $success = $this->_compileFiles($target_file,$fileListData);
            if ($success) {
                $returns[] = array($target_file,true);
            } else {
                foreach($fileListData as $filename=>$file_data) {
                    $returns[] = array($file_data,false);
                }
            }
            return $returns;
        }
        if ($this->_useCompiler) {
            foreach($fileListData as $filename=>$file_data) {
                $temp = array($filename=>$file_data);
                $target_file = $this->_generateTargetFileName($temp);
                $success = $this->_compileFiles($target_file,$temp);
                if ($success) {
                    $returns[] = array($target_file,true);
                } else {
                    $returns[] = array($file_data,false);
                }
            }
        } else {
           foreach($fileListData as $filename=>$file_data) {
               $returns[] = array($file_data,false);
            }
        }
        return $returns;
    }
    // }}}
    // {{{ - _compileFileExec($sourcePath, $destPath)
    /**
     * Exec command to compile from one file to another
     *
     * This version does nothing but copy the file.
     *
     * @param $sourcePath string the catenated file to compile
     * @param $destPath string where to dump the final output to
     */
    protected function _compileFileExec($sourcePath, $destPath)
    {
        link($destPath, $sourcePath);
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
        return false;
    }
    // }}}
    // {{{ - _generateFileData($fileName)
    /**
     * Generate file data.
     *
     * This version grabs the file from the file system and returns an array
     * with 'name', 'is_file, 'file_path', 'signature' and 'dependencies'. As
     * such, it doesn't actually need to be overridden if you don't need it
     * @return mixed if false there is no file data, else it
     *      returns an array hash
     */
    protected function _generateFileData($fileName)
    {
        $file_path = $this->_generateFilePath($fileName);
        if (!$file_path) { return false; }
        return array(
            'name'          => $fileName,
            'is_file'       => true,
            'file_path'     => $file_path,
            'signature'     => $this->_generateSignature($file_path),
            'dependencies'  => $this->_findDependencies($file_path),
            );
    }
    // }}}
}
// }}}
