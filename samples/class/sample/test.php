<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Test object (for globals)
 *
 * @package tgisamples
 * @subpackage testing
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
/**
 * Test object (for globals loading)
 *
 * @package tgisamples
 * @subpackage testing
 */
class sample_test {
    private $_foo = 'spam';
    public $bar = 'alot';
    function __construct()
    {
        printf('%s:__construct() called',get_class($this));
    }
    function __wakeup()
    {
        printf('%s:wakeup() called',get_class($this));
    }
}
?>
