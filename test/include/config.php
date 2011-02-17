<?php

/**
 * config class
 *
 * @package test
 * @subpackage include
 * @author diego matute <dmatute@tagged.com>
*/

// TEST_ENV {{{
/**
 * toggle that in test mode
 */
define('TEST_ENV', true);
// }}}
// ROOT {{{
/**
 * root directory
 */
define('ROOT', dirname(__FILE__) . '/../..');
// }}}
// TGIF_TEST_ROOT {{{
/**
* test root directory
*/
define('TGIF_TEST_ROOT', ROOT.DIRECTORY_SEPARATOR.'test');
// }}}
//TGIF_TEST_GLOBAL_DIR {{{
/**
* test dummy global directory
*/
define('TGIF_TEST_GLOBAL_DIR', TGIF_TEST_ROOT . '/include/conf/globals');
// }}}
// SYMBOL_FILE {{{
/**
* default symbol file location
*/
define('SYMBOL_FILE', dirname(__FILE__) . '/symbol.php');
// }}}

require_once(ROOT . '/framework/inc/preinclude.php');
require_once(dirname(__FILE__) . '/globalmock.php');
require_once(dirname(__FILE__) . '/basetest.php');
require_once(dirname(__FILE__) . '/testerrorhandler.php');

require_once(TGIF_TEST_ROOT . '/class/dummy.php');
require_once(TGIF_TEST_ROOT . '/class/dummy2.php');
require_once(TGIF_TEST_ROOT . '/class/dummyChild.php');

?>
