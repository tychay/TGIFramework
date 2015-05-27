<?php
/**
 * Configuration for debugging related stuff
 *
 * This contains the following configuration parameters
 * - _readConfig: Set this to true to cache the configuration file instead
 *   of forcing a reparse every page load. On the other hand, you need to
 *   restart the server to force another config file load.
 * - firephp.enable: whether firephp debugging should be turned on
 * - firephp.diagnostics: whether we should log diagnostics summaries to firephp
 *
 * This contains the following globals
 * - $_TAG->firephp:
 *
 * @package tgiframework
 * @subpackage debugging
 * @copyright 2009-2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
// {{{ _readConfig
'_readConfig'    => false,
// }}}
// firephp {{{
'firephp'   => array(
    'enable'        => false,
    'diagnostics'   => false,
),
// }}}
// {{{ $_TAG->firephp
'gld_firephp' => array(
    'params'            => 0,
    'construct'         => array('tgif_debug_firephp','_X_create_object'),
    'version'           => 0,
    'shouldShard'       => false,
    'isSmemable'        => false,
    'isMemcacheable'    => false,
),
// }}}
);
