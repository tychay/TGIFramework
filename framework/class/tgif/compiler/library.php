<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tgif_compiler_library}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright c.2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_compiler_library
/**
 * Interface for an external compiler library.
 *
 * Implement these static methods to extend the functionality of a compiler.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
interface tgif_compiler_library
{
    // SIGNATURE METHODS:
    // {{{ - generateSignature($fileName,$compileObj)
    /**
     * Figure a way of making a signature unique
     *
     * @param string $fileName the name of the library file
     * @param tgif_compiler $compilerObj for introspection as needed (for
     * instance, you want to recursively call generate signature.
     * @return string the signature
     */
    public function generateSignature($fileName, $compileObj);
    // }}}
    // {{{ - generateFileData($fileName,$compileObj)
    /**
     * Turn a file name into file data
     *
     * @param string $fileName the name of the library file
     * @param tgif_compiler $compilerObj for introspection as needed (for
     * instance, you want to recursively call generate signature.
     * @return array The library file's filedata, empty if no match
     */
    public function generateFileData($fileName,$compileObj);
    // }}}
    // {{{ - compileFile($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * Turn a file name into file data
     *
     * @param array $sourceFileData The file data of the resource. This will
     * be modified to the target file data if successful.
     * @param string $targetFileName The file name of the destination file
     * @param string $targetFilePath The path to a physically unique file to
     * place the destination file.
     * @param tgif_compiler $compilerObj for introspection as needed. For
     * instance it may be useful to call {@link
     * tgif_compiler::compileFileInternal() compileFileInternal()} to further
     * compress a file.
     * @return boolean success or failure
     */
    public function compileFile(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj);
    // }}}
    // {{{ - compileFileService($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * Turn a file name into file data via a "service" (delayed call).
     *
     * Note that if service is on, then even if the result is instanteous,
     * it is always assumed to have "failed".
     *
     * @param array $sourceFileData The file data of the resource. This will
     * be modified to the target file data if successful.
     * @param string $targetFileName The file name of the destination file
     * @param string $targetFilePath The path to a physically unique file to
     * place the destination file.
     * @param tgif_compiler $compilerObj for introspection as needed
     * @return boolean success or failure
     */
    public function compileFileService(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj);
    // }}}
    // {{{ - catFiles(&$fileDatas,$compilerObj)
    /**
     * Allow you to catenate files at the front (or in place).
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
    public function catFiles(&$fileDatas, $compilerObj);
    // }}}
    // {{{ - generateUrl($fileData)
    /**
     * Turn a file data into a full URL.
     *
     * Note if the resource is really a local file. then it is suggested you
     * modify {@link cat_files()} to remove the 'library' property for these
     * files and let the automated routine handle it.
     *
     * @param string $fileData The data to extract the URL for
     * @return string the url
     */
    public function generateUrl($fileData);
    // }}}
}
// }}}
?>
