<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tgif_compiler}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright 2008-2009 Tagged, Inc., c.2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_compiler
/**
 * (Abstract) base class for managing files that can be concatenated and
 * compiled for efficiency.
 *
 * In order for this to work, it assumes that there is a $_TAG->url global
 * set that has a method chrome() to take a path and create a url from it.
 * Also, you must set a config parameter dir_static.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 * @author Nate Kresge <nkresge@tagged.com> added caching for file not found
 * @author Mark Jen <markjen@tagged.com> replaced hashing algorithm
 */
class tgif_compiler
{
    // PRIVATE PROPERTIES
    // {{{ - $_options
    /**
     * The configuration for the compiler:
     * - resource_dir (string): where the source files can be found. Was
     *   $_resourceDir.
     * - resource_url (string): where the source directory can be found (via
     *   url). This can be bypassed with url_callback
     * - target_dir (string): The directory to write concatenated/compiled files
     *   to. Was $_targetDir.
     * - target_url (string): where the target_dir can be found (via url).
     * - url_callback (mixed): if specified, use this instead of the internal
     *   url generator to find the url position.
     * - use_cat (boolean): Should we attempt to turn everything into a single
     *   file (as much as as is possible)? Was $_useCat
     * - cat_add_separator (boolean): Should we add a separator between
     *   files?
     * - use_compiler (boolean): Should we try to use the compiler whenever
     *   possible? Was $_useCompiler
     * - use_service (boolean): Are we calling externals service to perform a
     *   compile? (or, if no service, then turn on uncompiler caching).
     * - service_callback (mixed): call user func of the service that can
     *   does the entire compile (use_service must be true). If blank, it
     *   uses the internal compile service (just in the background). This
     *   service can return immediately but might want to ignore multiple same
     *   requests until the service is complete then the file should written (or
     *   written atomically) at the end of the service request. If set
     *   the signature looks like:
     *   <pre>boolean (success) function service_callback(array &$sourceFileData, string $targetFilePath)</pre>
     * - use_smem (boolean) Should we cache file info into the shared memory
     *   segment? was $_useSmem
     * - use_memcache (boolean): Should we cache file info into the memcache?
     *   was $_useMemcache
     * - signature_method (mixed): generator for determining file info
     *   staleness. was a string called $_signatureMode with three possible
     *   values known as "global", "filemtime", "md5"
     * - file_not_found_ttl (integer): To keep from overloading the requests to
     *   a compiler, this will cache the uncompiled file for this long if
     *   use_service is set. was CACHE_FILE_NOT_FOUND_TTL.
     * - dir_chmod (integer): the chmod of any directories created
     * - file_chmod (integer): the chmod of any files created
     * - libraries (array): external libraries the compiler needs to know
     *   about. These are arrays where the key is is the class name of the
     *   object (that implements {@link tgif_compiler_library} and the value is
     *   the config variable to pass into its constructor.
     *
     * UNSUPPORTED:
     * - track_file (string): If set, this is the path to a file to keep a log
     *   compiled file combos. Was $_trackFile.
     * - track_db (string): This is the table to store the compilation list
     *   lookup.
     * - use_release_name: boolean Should we use the release name as part of
     *   the file path? Was called $_useReleaseName.
     * @var array
     */
    protected $_options = array(
        'resource_dir'      => '',
        'resource_url'      => '',
        'target_dir'        => '',
        'target_url'        => '',
        'url_callback'      => '',

        'use_cat'           => false,
        'cat_add_separator' => true,
        'use_compiler'      => false,
        'use_service'       => false,
        'service_callback'  => '',

        'use_smem'          => false,
        'use_memcache'      => false,
        'signature_method'  => array('tgif_compiler','sign_filemtime'),
        'file_not_found_ttl'=> 10,

        'dir_chmod'         => 0777,
        'file_chmod'        => 0666,

        'libraries'         => array(),
        /*
        'use_release_name'  => false,
        'track_file'        => '',
        'track_db'          => '',
        /* */
    );
    // }}}
    // {{{ - $_libraries
    /**
     * An array indexed by class name  of any external lirbaries for the
     * compiler
     *
     * @var array
     */
    protected $_libraries = array();
    // }}}
    // {{{ - $_fileDataList
    /**
     * An array indexed by filename of the loading data associated with the
     * file. The following hash keys:
     * - name (required): identical to the index
     * - library (required): either empty (it's just a file) or it is a pointer
     *   to a class that is the library handler for the file
     * - dependencies (required): a list of other files in the array that must
     *   be loaded prior to this library
     * - signature (required): a signature representation of the file (to handle
     *   changes)
     * - is_resource (required): This is set to true if it is an original
     *   resource, if the resource was dynamically built, it is set to false
     * - file_path (required): path to file in filesystem (written inside {@link
     *   _generateFileData()}
     * - provides (optional): packages that this one also obsoletes/supersedes
     * - url (optional): allows overriding of the url generator for this file
     *   to be this one
     * @var array
     */
    private $_fileDataList = array();
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
     * An array of files that have already been outputted somewhere else
     */
    protected $_outputList = array();
    // }}}
    // RESERVED FUNCTIONS
    // {{{ __construct($options)
    /**
     * This is protected because this class is actually abstract
     *
     * @param $options array A bunch of options to override {@link $_options}
     */
    protected function __construct($options)
    {
        //global $_TAG;
        $this->_options = array_merge($this->_options, $options);
        foreach ($this->_options['libraries'] as $class_name=>$config_name) {
            $this->_libraries[$class_name] = new $class_name($_TAG->config($config_name, true));
        }
    }
    // }}}
    // {{{ __sleep()
    /**
     * Make sure temporary structures aren't stored between saves.
     */
    function _sleep()
    {
        return array('_options','_libraries');
    }
    // }}}
    // {{{ __wakeup()
    /**
     * Restore missing defaults
     */
    function __wakeup()
    {
        $this->_fileDataList= array();
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
        // initialize queue
        if (!array_key_exists($queue,$this->_queues)) {
            $this->_queues[$queue] = array();
        }
        // add it to the queue if it's not already there (and there is a map)
        if ($this->_grabFileData($fileName)) {
            $this->_queues[$queue][] = $fileName;
        }
    }
    // }}}
    // {{{ - printAll([$properties])
    /**
     * Print out the HTML that includes all compiled queues (not already
     * rendered).
     * @param array $properties extra parameters to pass to outputter
     */
    function printAll($properties=array())
    {
        echo implode("\n",$this->generateFlush($properties));
        echo "\n";
    }
    // }}}
    // {{{ - printQueue([$queue,$properties])
    /**
     * Print out the HTML that includes the specified compiled queue
     * @param string $queue The queue to print out.
     * @param array $properties extra parameters to pass to outputter
     */
    function printQueue($queue='_default', $properties=array())
    {
        echo implode("\n",$this->generate($queue,$properties));
        echo "\n";
    }
    // }}}
    // {{{ - generateFlush([$properties])
    /**
     * Generate the output to call for all outstanding queues
     *
     * @param array $properties extra parameters to pass to outputter
     * @return array a bunch of individual call text (may be a single element).
     */
    function generateFlush($properties=array())
    {
        $returns = array();
        foreach($this->_queues as $queueName=>$queue) {
            $returns = array_merge($returns, $this->generate($queueName,$properties));
        }
        return $returns;
    }
    // }}}
    // {{{ - generate([$queue,$properties])
    /**
     * Generate the output to call for a given queue
     *
     * @param string $queue The queue to generate output
     * @param array $properties extra parameters to pass to outputter
     * @return array a bunch of individual call text (may contain a single
     *  element, or none at all).
     */
    function generate($queue='_default', $properties=array())
    {
        // empty queue case {{{
        if (!array_key_exists($queue,$this->_queues)) {
            return array();
        }
        // }}}
        // build the list of files and dependencies: {{{
        //printf("<pre>queue: %s\n, list:%s, output:%s pos:%s</pre>", $queue, var_export($this->_queues[$queue], true), var_export($this->_outputList, true),var_export(debug_backtrace(true)));
        $file_list_data = array();
        $this->_buildFileList($this->_queues[$queue], $file_list_data);
        //printf("<pre>queue: %s\n, filelistdata:%s</pre>", $queue, var_export($file_list_data, true));
        // }}}
        // queue has been emptied, files will be written
        unset($this->_queues[$queue]);
        $this->_updateOutput($file_list_data);
        
        $urls = $this->_buildUrls($file_list_data);

        return $this->_generateHtmls($urls,$properties,$queue);
    }
    // }}}
    // {{{ - generateSingle($files)
    /**
     * Generate the url of the a single file to call (bypass dependencies).
     *
     * The purpose of this is to have a receiver function so that ajax can
     * load extra scripts without having the dependency system kick in.
     *
     * This does not update the output list.
     *
     * @param $files string|array a list of files to turn into a single file
     * @return array two values The first is the type that explains the second
     * - redirect: value is the url to redirect to.
     * - data: value is the compiled file data itself.
     * @todo untested
     */
    function generateSingle($files)
    {
        $file_list_data = array();
        // build list of files and dependencies {{{
        if (!is_array($files)) { $files = array($files); }
        foreach ($files as $file) {
            $this->_grabFileData($file);
            if ( !array_key_exists($file,$this->_fileDataList) ) { continue; }
            $file_list_data[$file] = $this->_fileDataList[$file];
        }
        // }}}
        if ( empty($file_list_data) ) { return array('data',''); }

        if ( $this->_options['use_compiler'] ) {
            $file_list_data = $this->_compileFiles($file_list_data);
        }
        if ( empty($file_list_data) ) { return array('data',''); }
        if ( $this->_options['use_cat'] && (count($fileDatas) > 1) ) {
            $file_list_data = $this->_catFiles($file_list_data);
        }
        if ( empty($file_list_data) ) { return array('data',''); }

        if ( count($file_list_data) ==  1 ) {
            // one file, return it's url {{{
            return array(
                'redirect',
                $this->_generateUrl($file_list_data[0])
            );
            // }}}
        }
        // more than one file? return the contents of all of them {{{
        $data = '';
        foreach ($file_list_data as $file_data) {
            $data .= file_get_contents($file_data['file_path']);
        }
        // }}}
    }
    // }}}
    // SIGNATURE METHODS:
    // {{{ + sign_filemtime($filePath)
    /**
     * Take the last modified time of the file as the signature.
     *
     * This is a good one to use in development. This was part of the old
     * _generateSignature system when $_signatureMode was set to 'filemtime'.
     *
     * @param string $filePath path to file
     * @return string
     */
    public static function sign_filemtime($filePath)
    {
        // give a random signature if file doesn't exist.
        if (!file_exists($filePath)) { return uniqid(); }
        return filemtime($filePath);
    }
    // }}}
    // {{{ + sign_md5($filePath)
    /**
     * Take the md5 of the file contents as the signature.
     *
     * Use this when the file exists in a part of a filesystem that may have the
     * filemtime part disabled. This was part of the old _generateSignature
     * system when $_signatureMode was set to 'md5'.
     *
     * @param string $filePath path to file
     * @return string
     */
    public static function sign_md5($filePath)
    {
        // give a random signature if file doesn't exist.
        if (!file_exists($filePath)) { return uniqid(); }
        return md5(file_get_contents($filePath));
    }
    // }}}
    // {{{ + sign_global($filePath)
    /**
     * Use the global version as the signature.
     *
     * This is a good one to use on a live site because it saves a bunch of
     * wasted file system calls, but can be updated later. To do that, use
     * the {@link generate_global_version.php} after every deploy. This was
     * part of the old _generateSignature system when $_signatureMode was set
     * to 'global'.
     *
     * @param string $filePath path to file
     * @return string
     */
    public static function sign_global($filePath)
    {
        return $_TAG->config('global_version');
    }
    // }}}
    // {{{ - signature($fileData)
    /**
     * returns signature of $fileData
     *
     * @return string
     */
    public function signature($fileData)
    {
        return ( $class = $fileData['library'] )
             ? $this->_libraries[$class]->generateSignature( $fileData['name'], $this )
             : call_user_func($this->_options['signature_method'], $fileData['file_path']);
    }
    // }}}
    // {{{ - _isValid($fileData)
    /**
     * Determine whether or not a filedata is valid.
     *
     * The old version only got called if this was a file, the new version
     * can understand the library abstraction to know to use the library
     * to validate a signature.
     *
     * @return boolean
     */
    private function _isValid($fileData)
    {
        $sig = $this->signature($fileData);
        return ($sig == $fileData['signature']);
    }
    // }}}
    // {{{ - _generateKey($fileName)
    /**
     * Turn a filename into a cache key
     *
     * This is protected in case something else (i18n?) needs this function to
     * update filedata.
     *
     * The old version used to turn it into something messy, the new version
     * just encodes it well enough for storage. There is no need to add the
     * {@link tgif_global::symbol() symbol} because that is done automatically
     * when the same key is used for both smem and memcache.
     * 
     * @return string
     */
    protected function _generateKey($fileName)
    {
        return urlencode($fileName);
    }
    // }}}
    // PRIVATE METHODS: FILEDATA
    // {{{ - _grabFileData($fileName)
    /**
     * Make sure we load the filedata for a given file
     *
     * @return true if the file exists and filedata exist (and is loaded)
     */
    private function _grabFileData($fileName)
    {
        // if we already have the file data, we're good to go :-)
        if (array_key_exists($fileName,$this->_fileDataList)) {
            return true;
        }
        // grab from cache {{{
        $cache_key = array(
            'group' => 'compile',
            'key'   => $this->_generateKey($fileName),
        );
        if ( $file_data = tgif_global_loader::get_from_cache($cache_key, $this->_options['use_smem'], $this->_options['use_memcache']) ) {
            if ( $this->_isValid($file_data) ) {
                $this->_registerFileData($file_data);
                return true;
            }
        }
        // }}}
        // create file data
        $file_data = $this->_generateFileData($fileName);
        if (!$file_data) { return false; }
        tgif_global_loader::set_to_cache($cache_key, $file_data, $this->_options['use_smem'], $this->_options['use_memcache']);
        $this->_registerFileData($file_data);
        return true;
    }
    // }}}
    // {{{ - _generateFileData($fileName)
    /**
     * Generate file data.
     *
     * The old verison would add an 'is_file' and need to be overridden,
     * the new version knows how to handle standard files and can pass stuff
     * over to library interface.
     *
     * Thus the old stuff in _generateFilePath() is now contained here, however
     * we have wiped out the old crap related to _useReleasteName which was
     * confusing as hell.
     *
     * @return false|array if false there is no file data, else it
     *      returns an array hash containing the file data
     */
    private function _generateFileData($fileName)
    {
        // check if the file is in the file system {{{
        $file_path = $this->_options['resource_dir'].DIRECTORY_SEPARATOR.$fileName;
        if (file_exists($file_path)) {
            return array(
                'name'          => $fileName,
                'is_resource'   => true,
                'library'       => '',
                'dependencies'  => $this->_findDependencies($file_path, $fileName),
                'signature'     => call_user_func($this->_options['signature_method'], $file_path),
                'file_path'     => $file_path,
            );
        }
        // }}}
        // check if a library recognizes the file {{{
        foreach ($this->_libraries as $class=>$library_obj) {
            $file_data = $library_obj->generateFileData($fileName);
            if ($file_data) {
                $file_data['name']      = $fileName;
                $file_data['library']   = $class;
                if (!isset($file_data['signature'])) {
                    $file_data['signature'] = $library_obj->generateSignature($fileName);
                }
                return $file_data;
            }
        }
        // }}}
        return false;
    }
    // }}}
    // {{{ - _registerFileData($fileData)
    /**
     * Make sure we load all filedata that is dependent on this one (recursion).
     */
    private function _registerFileData($fileData)
    {
        $this->_fileDataList[$fileData['name']] = $fileData;
        foreach ($fileData['dependencies'] as $filename) {
            $this->_grabFileData($filename);
        }
    }
    // }}}
    // {{{ - _updateOutput($fileDatas)
    /**
     * Mark files as having been outputted to avoid redundant includes.
     *
     * @param array $fileDatas 
     */
    private function _updateOutput($fileDatas)
    {
        foreach ($fileDatas as $key=>$data) {
            $this->_outputList[] = $key;
            if (key_exists('provides',$data)) {
                foreach ($data['provides'] as $file) {
                    $this->_outputList[] = $file;
                }
            }
        }
    }
    // }}}
    // PRIVATE METHODS: COMPILING
    // {{{ - _buildFileList($queue,$fileDatas)
    /**
     * Recursive function generates list of files to compile
     *
     * @param array $queue a list of files to compile or cat
     * @param array $fileDataList a list of filedata objeccts that need to be
     *      compiled
     */
    private function _buildFileList($queue, &$fileDatas)
    {
        foreach ($queue as $filename) {
            // don't compile files that don't exist
            if (!array_key_exists($filename, $this->_fileDataList)) { continue; }

            // handle case where file is already outputted
            if (in_array($filename, $this->_outputList)) { continue; }

            $file_data = $this->_fileDataList[$filename];
            if (!empty($file_data['dependencies'])) {
                $this->_buildFileList($file_data['dependencies'], $fileDatas);
            }

            // add file to list if it isn't already there {{{
            if (array_key_exists($filename, $fileDatas)) { continue; }
            $fileDatas[$filename] = $file_data;
            // }}}
        }
    }
    // }}}
    // {{{ - _buildUrls($fileDatas)
    /**
     * Turns a list of files into urls.
     *
     * @param array $fileDatas The file data of all the files to compile
     * (in order).
     * @return array a list of urls to include
     */
    protected function _buildUrls($fileDatas)
    {
        //var_dump(array('_buildUrls',$fileDatas));
        // compilation step
        if ( $this->_options['use_compiler'] ) {
            $fileDatas = $this->_compileFiles($fileDatas);
        }
        //var_dump(array('_buildUrls::post_compile',$fileDatas));

        // don't even think of generating an empty file!
        if ( empty($fileDatas) ) { return array(); }

        // concatenation step
        if ( $this->_options['use_cat'] && (count($fileDatas) > 1) ) {
            $fileDatas = $this->_catFiles($fileDatas);
        }
        //var_dump(array('_buildUrls::post_cat',$fileDatas));

        $returns = array();
        foreach ($fileDatas as $filename=>$file_data) {
            $returns[] = $this->_generateUrl($file_data);
        }
        //var_dump(array('_buildUrls::return',$returns));

        return $returns;
    }
    // }}}
    // {{{ - _compileFiles($fileDatas)
    /**
     * This takes each file and generates the compiled file of each of them.
     *
     * Only call this if use_compiler flag is already set in {@link $_options}.
     * This also handles caching the success.
     *
     * This used to be _buildFiles($fileDatas,$forceCat) and the old
     * _compileFiles() was eliminated, but in this version, each file is
     * compiled then catted instead of catted then compiled. Therefore this only
     * handles the compilation of the file. When an external library compiles a
     * file into a local one than can be catted as part of the internal system,
     * it needs to add the 'file_path' <strong>and</strong> remove its name
     * from 'library'. This way,
     * a later part
     * knows this element can be added to the concatenation list. Thus, no more
     * need for the 'is_file' to be set and detected. The old forceCat file
     * was used by the i18n code to turn urls into a single file.
     *
     * @param array $fileDatas the list of file data to compile/catenate
     * indexed by their file name and ordered by their compile order.
     * @return array an array of file data (transformed $fileDatas)
     */
    private function _compileFiles(&$fileDatas)
    {
        // don't even think of generating an empty compiled file!
        if (empty($fileDatas)) {  return array(); }

        $use_smem       = $this->_options['use_smem'];
        $use_memcache   = $this->_options['use_memcache'];

        $returns = array();
        foreach ($fileDatas as $filename=>$file_data) {
            $target_file = $this->_generateTargetFileName(array($file_data));
            $cache_key = array(
                'group' => 'compile',
                'key'   => $this->_generateKey($target_file),
            );
            if ( $result = tgif_global_loader::get_from_cache($cache_key,$use_smem,$use_memcache) ) {
                $file_data = $result;
            } else {
                $success = $this->_compileFile($file_data,$target_file,$cache_key);
                $file_data['final'] = $success;
                tgif_global_loader::set_to_cache($cache_key, $file_data, $use_smem,$use_memcache);
            }
            $returns[$filename] = $file_data;
        }
        return $returns;
    }
    // }}}
    // {{{ - _compileFile($fileData,$targetFileName,$cacheKey)
    /**
     * Single file compile, see {@link _compileFiles()} for a complete
     * description.
     *
     * This assumes that you have looked in a cache (you can index by the target
     * file name) before going here. If that is not the case, it will still
     * not compile the file if it has already been compiled (if the target
     * file path exists).
     * This will not compile the file if it has already been compiled (it knows
     * this by setting the target file name as the cache key or, if the target
     * file already). This understands the concept of libraries also.
     *
     * Because target file name (most likely) uses a signature system, if
     * the signature is changed, the file ceases to exist and will be rebuilt.
     *
     * @param array $fileData the file data of the file to compile
     * @param string $targetFileName where to compile the file
     * @param array $cacheKey the cache to load/store manipulate.
     * @return boolean whether or not the compile suceeded or failed
     */
    private function _compileFile(&$fileData, $targetFileName, &$cacheKey)
    {
        $target_file_path = $this->_generateTargetFilePath($targetFileName);

        // it's already been compiled earlier, but not in cache {{{
        if ( file_exists($target_file_path) ) {
            $fileData['name']       = $targetFileName;
            $fileData['is_resource']= false;
            $fileData['library']    = '';
            $fileData['file_path']  = $target_file_path;
            return true;
        }
        // }}}

        if ( $this->_options['use_service'] ) {
            if ( $class = $fileData['library'] ) {
                $success = $this->_libraries[$class]->compileFileService($fileData, $targetFileName, $target_file_path, $this);
            } else {
                $this->_compileFileInternal($fileData, $targetFileName, $target_file_path, true);
                $success = false; 
            }
        } else  {
            if ( $class = $fileData['library'] ) {
                $success = $this->_libraries[$class]->compileFile($fileData, $targetFileName, $target_file_path, $this);
            } else {
                $success = $this->compileFileInternal($fileData, $targetFileName, $target_file_path);
            }
        }
        if ( !$success ) {
            // temporarily store the uncompiled file into cache for parallel
            // requests to give the compiler service time to complete.
            $cacheKey['smem_lifetime']      = $this->_options['cache_file_not_foud_ttl'];
            $cacheKey['memcache_lifetime']  = $this->_options['cache_file_not_foud_ttl'];
        }
    }
    // }}}
    // {{{ - _compileFileInternal($sourceFileData,$targetFileName,$targetFilePath[,$inBackground])
    /**
     * Enables the compiling of files using a safe temp file-based method
     * that calls {@link _compileFileExec()} for the key step.
     *
     * This is public static in order to allow it to be called by a library
     * compile if the internal compiler might be needed by the library system
     * to make a file even smaller.
     *
     * Was _compileFileUsingFiles() but since moved here since it is a single
     * file. Also added support for backgrounding instead of the old use
     * service hack. Thus _compileFileService() no longer exists at this level.
     *
     * @param array $sourceFileData the file data of the file to compile.
     *  will be transformed to the target file data.
     * @param string $targetFileName the file name to write on success
     * @param string $targetFilePath the path to file to compile to
     * @param boolean $inBackground should we background the call so it
     *  returns immediately?
     * @return array returns the updated file data
     */
    public function compileFileInternal( &$sourceFileData, $targetFileName, $targetFilePath, $inBackground=false )
    {
        $base_dir = dirname($targetFilePath);
        // ensure path to source exists {{{
        if (!file_exists($base_dir)) {
            mkdir($base_dir, $this->_options['dir_chmod'], true);
        }
        // }}}
        // special case: service callback {{{
        if ( $inBackground && $this->_options['use_service'] && $this->_options['service_callback'] ) {
            return call_user_func($this->_options['service_callback'], $sourceFileData, $targetFilePath);
        }
        // }}}
        // generate temp file in the path
        $com_path = tempnam($base_dir,'com_');
        if ( $inBackground ) {
            unlink($com_path); //delete it because of a race condition
            $success = $this->_compileFileExec( $sourceFileData['file_path'], $targetFilePath, $com_path );
            // service is in background, so $success should be false.
        } else {
            $success = $this->_compileFileExec( $sourceFileData['file_path'], $com_path );
            if ($success) {
                tgif_file::move($com_path, $targetFilePath, $this->_options['file_chmod']);
            }
        }
        if ( $success ) {
            $sourceFileData['name']         = $targetFileName;
            $sourceFileData['is_resource']= false;
            $sourceFileData['file_path']    = $targetFilePath;
            //$sourceFileData['library']      = ''; //already assumed by internal designation
        }
        return $success;
    }
    // }}}
    // {{{ - _catFiles($fileDatas)
    /**
     * This takes each file and generates a single (or pair) of files daata
     *
     * Only call this if use_cat flag is already set in {@link $_options}
     * This also handles caching the success.
     *
     * This understands the concept of libraries. But since libraries may
     * be made such that they generate a single external url or call local
     * files, this handles that also by having two calls to the external
     * libraries. :-)
     *
     * @param array $fileDatas the list of file data to compile/catenate
     * indexed by their file name and ordered by their compile order.
     * @return array an array of file data (transformed $fileDatas)
     */
    private function _catFiles(&$fileDatas)
    {
        //global $_TAG;
        // No need to check for empty case because this will never be called
        //   for an empty list or a single file concatenation.

        $use_smem           = $this->_options['use_smem'];
        $use_memcache       = $this->_options['use_memcache'];
        $target_file_name   = $this->_generateTargetFileName($fileDatas);
        $cache_key = array(
            'group' => 'compile',
            'key'   => $this->_generateKey($target_file_name),
        );

        // Check cache
        if ( $returns = tgif_global_loader::get_from_cache($cache_key,$use_smem,$use_memcache) ) {
            return $returns;
        }

        // Option to merge files within the library class :-)
        $returns = array();
        foreach ($this->_libraries as $obj) {
            $returns = array_merge($returns, $obj->catFiles($fileDatas,$this));
        }

        if ( count($fileDatas) <= 1 ) {
            $returns = array_merge($returns, $fileDatas);
        } else {
            // regenerate target file name because it may be outdated
            $target_file_name   = $this->_generateTargetFileName($fileDatas);
            $target_file_path   = $this->_generateTargetFilePath($target_file_name);
            if ( !file_exists($target_file_path) ) {
                // build the file {{{
                $_TAG->diagnostics->startTimer('file', 'cat', array(
                        'dest_file' => $target_file_path,
                    ));
                $source_files = array();
                $add_sep = $this->_options['cat_add_separator'];
                $base_dir = dirname($target_file_path);
                // ensure path to target exists {{{
                if (!file_exists($base_dir)) {
                    mkdir($base_dir, $this->_options['dir_chmod'], true);
                }
                // }}}
                $tmp_path = tempnam($base_dir,'cat_');
                $fp = fopen($tmp_path, 'w');
                foreach ($fileDatas as $filename=>$file_data) {
                    // Skip concatenation of stuff we don't know how to find
                    // (this is unnecessary as all of these should be pruned out
                    // by this point).
                    //if ( $file_data['library'] ) { continue; }
                    $source_file = $file_data['file_path'];
                    $source_files[] = $source_file;
                    if ($add_sep) {
                        fwrite($fp, $this->_generateSeparator($filename,$file_data));
                    }
                    fwrite($fp, file_get_contents($source_file));
                }
                fclose($fp);
                tgif_file::move($tmp_path,$target_file_path,$this->_options['file_chmod']);
                $_TAG->diagnostics->stopTimer('file', array(
                        'source_files'  => $source_files,
                    ));
                // }}}
            }
            $file_data = array(
                'name'          => $target_file_name,
                'is_resource'   => false,
                'library'       => '',
                'dependencies'  => array(),
                'signature'     => call_user_func($this->_options['signature_method'], $target_file_path),
                'file_path'     => $target_file_path,
            );

            // Can always write because we will generate a different key on
            // successful compile than one on a delayed compare because how
            // we manipulate the $fileData after a compile.
            $returns[] = $file_data;
        }
        tgif_global_loader::set_to_cache($cache_key, $returns, $use_smem, $use_memcache);
        return $returns;
    }
    // }}}
    // OVERRIDES
    // {{{ - _findDependencies($filePath,$fileName)
    /**
     * Find all the embeded dependencies in the codebase.
     *
     * @param string $filePath The path to the file
     * @param string $fileName The "name" of the file in the compiler
     * @return array list of "files" that depend on this one
     */
    protected function _findDependencies($filePath,$fileName)
    {
        return array();
    }
    // }}}
    // {{{ - _generateTargetFileName($fileDatas)
    /**
     * Figure out target file name in a normalized manner.
     *
     * Override this in order to add filetype extensions. The nature of the
     * function is that the parent may find it useful to call the parent.
     *
     * Note that even if the compile order of files is slightly different, this
     * generates the same key. The assumption is that the build order may
     * vary slightly differently, but roughly things should be okay.
     *
     * @param array $fileDatas This is an array of file data to sort. If you
     *  want to generate a file for a single file, then just wrap it in an
     *  array.
     * @return string This version generates a hash key based on the file list
     *  data. The old version used the use_compiler flag, but it is unnecessary
     *  now as this and the cat stage are now separate.
     */
    protected function _generateTargetFileName($fileDatas)
    {
        ksort($fileDatas);
        return tgif_encode::create_key(serialize($fileDatas));
    }
    // }}}
    // {{{ - _generateTargetFilePath($targetFileName)
    /**
     * Prepend the path onto the target file name
     *
     * @param string $targetFileName
     * @return string Full path to the target file
     */
    protected function _generateTargetFilePath($targetFileName)
    {
        return $this->_options['target_dir'].DIRECTORY_SEPARATOR.$targetFileName;
    }
    // }}}
    // {{{ - _generateUrl($fileData)
    /**
     * Figure out url in a normalized manner
     *
     * It is best not to override this, instead, it is probably better to
     * make a third party function for generating the url by passing in
     * the option url_callback()
     *
     * Note that even if the compile order of files is slightly different, this
     * generates the same key. The assumption is that the build order may
     * vary slightly differently, but roughly things should be okay.
     *
     * You can pre-empt this routine simply by embedding the url in the
     * 'url' key of the file data.
     *
     * @param array $fileData This is a single file to return the url for.
     * @return string full path url to the resource.
     */
    protected function _generateUrl($fileData)
    {
        if ( array_key_exists('url', $fileData) ) {
            return $fileData['url'];
        }
        if ( $class = $fileData['library'] ) {
            return $this->_libraries[$class]->generateUrl($fileData);
        }
        if ( $this->_options['url_callback'] ) {
            return call_user_func($this->_options['url_callback'], $fileData);
        }
        if ( $fileData['is_resource'] ) {
            return $this->_options['resource_url'].'/'.$fileData['name'];
        } else {
            return $this->_options['target_url'].'/'.$fileData['name'];
        }
    }
    // }}}
    // {{{ - _generateSeparator($filename, $fileData)
    /**
     * Between files in a concatenation, add a comment with a separtor
     *
     * @param array $fileData The file to put in the separtor
     * @return string The separator to write
     */
    protected function _generateSeparator($filename, $fileData)
    {
        return sprintf("\n\n/* key:%s, filename:%s %s */\n",
            $filename,
            $fileData['name'],
            (isset($fileData['copyright'])) ? 'copyright:'.$fileData['copyright'] : ''
        );
    }
    // }}}
    // {{{ - _generateHtmls($urls,$properties,$queue)
    /**
     * Generates the HTML for a bunch of $urls in a normalized manner
     *
     * You can override this in order to embed properties differently than
     * serially (example: css includes using @import).
     *
     * @param array $urls The urls to all the resources
     * @param array $properties the properties to add to the URL
     * @param string $queue the name of the queue rendering
     * @return array an array of html tags that can be imploded
     */
    protected function _generateHtmls($urls, $properties, $queue)
    {
        $count = 0;
        $returns = array();
        $add_properties = (!isset($properties['id']));
        foreach ($urls as $url) {
            if ( $add_properties ) {
                $properties['id'] = $queue;
                $properties['id'] .= ($count) ? '-'.$count : '';
            }
            $returns[] = $this->_generateHtml($url, $properties);
            ++$count;
        }
        return $returns;
    }
    // }}}
    // {{{ - _generateHtml($url,$properties)
    /**
     * Generates the HTML for a bunch of $urls in a normalized manner
     *
     * Override this (and call it) in order to change the way it renders
     *
     * @param string $url The full URL to link
     * @param array $properties the properties to add to the URL
     * @return string The HTML
     */
    protected function generateHtml($url, $properties)
    {
        $return = $url;
        foreach ($properties as $key=>$value) {
            $return .= ' '.$key.':'.$value;
        }
        return $return;
    }
    // }}}
    // {{{ - _compileFileExec($sourcePath,$destPath[,$backgroundPath])
    /**
     * Command to compile from one file to another.
     *
     * This version does nothing but copy the file. No such concept as
     * backgrounding. Please override.
     *
     * @param string $sourcePath the file to compile
     * @param string $destPath where to dump the final output to
     * @param stirng $backgroundPath if specified, then do the work in the
     *  background and this is the intermediate file.
     * @return boolean Has the compiled file been created (backgrounding may
     *  prevent this from happening.
     */
    protected function _compileFileExec($sourcePath, $destPath, $backgroundPath='')
    {
        return tgif_file::copy($sourcePath, $destPath);
    }
    // }}}

    // deprecated
    // PRIVATE/PROTECTED METHODS
    // {{{ - _writeTrackFile($fileDatas)
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
     * @deprecated
     */
    private function _writeTrackFile($fileDatas)
    {
        // build the file list (without non-specific data) {{{
        $files = array();
        foreach ($fileDatas as $file_list) {
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
     * @deprecated
     */
    private function _generateKeyFromFiles($files)
    {
        sort($files);
        return tgif_encode::create_key(serialize($files));
    }
    // }}}
}
// }}}
?>
