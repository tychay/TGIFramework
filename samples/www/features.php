<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Overview of various features in the site.
 *
 * Test global config and variables
 *
 * @package tgisamples
 * @subpackage testing
 * @copyright 2009 terry chay <tychay@php.net>
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
?>
<h1>Features test page</h1>
<p>This contains a list of various framework features and what they look like to the developer.</p>

<h2>Errors</h2>
<p>Trigger a user error for testing purposes.</p>
<?php
trigger_error('Testing user error');
?>

<h2>Runkit</h2>
<p>Test whether Runkit superglobals are working.</p>
<p>Expect:</p>
<ul>
<li>_TAG->foo is bar</li>
<li>foo is (null) [+ undefined error]</li>
</ul>
<p>Got:</p>
<?php
/**
 * Just a dummy test function to test superglobals
 */
function test_runkit()
{
?>
<ul>
<li>'_TAG->foo' config is <?php var_dump($_TAG->foo); ?></li>
<li>'foo' global is <?php var_dump($foo); ?></li>
</ul>
<?php
}
$_TAG->foo = 'bar';
$foo = 'bar';
test_runkit();
?>

<h2>Configuration & Globals</h2>
<p>Make sure $_TAG global system works and configuration system is working and properly instantiated.</p>
<ul>
<li>Cache configuration? <?php var_dump($_TAG->config('readConfig')); ?></li>
<li>Files read for configuration: <?php var_dump($_TAG->config('configFiles')); ?></li>
<li>'gld_firephp' global config is <?php var_dump($_TAG->config('gld_firephp')); ?></li>
<li>'testConf' local config is <?php var_dump($_TAG->config('testConf')); ?></li>
<li>'testGlobal' global is <?php var_dump($_TAG->testGlobal); ?></li>
</ul>

<h2>FirePHP debugging</h2>
<p>You need Firefox with firebug and firephp installed and enabled. If you have this then open up console to see a variable called "Iterators" sent to you.</p>
<?php
// No longer needed (see config.php)
//require_once('FirePHPCore/FirePHP.class.php');
//ob_start();
//echo 'This is a test of FirePHP';
//$firephp = FIREPHP::getInstance(true);
$var = array('i'=>10, 'j'=>20);
//$firephp->log($var, 'Iterators');
$_TAG->firephp->log($var, 'Iterators');
?>
