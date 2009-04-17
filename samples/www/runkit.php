<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Test whether runkit is working
 *
 * @package tgiframework
 * @subpackage samples
 * @copyright 2009 terry chay <tychay@php.net>
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@tagged.com>
 */
function testme() {
    echo "_TAG is $_TAG\n";
    echo "Bar is $bar\n";
    echo "Baz is $baz\n";
}
$_TAG = 1;
$bar = 2;
$baz = 3;
echo '<plaintext>';
testme(); 

echo '
Expected:
31 	_TAG is 1
32 	Bar is
33 	Baz is 
';
?>
