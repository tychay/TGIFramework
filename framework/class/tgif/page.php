<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_page}
 *
 * @package tgiframework
 * @subpackage templating
 * @copyright 2007 Tagged, Inc. <http://www.tagged.com/>, 2009 terry chay <tychay@php.net>
 * @author terry chay <tychay@php.net>
 * @author Mark Jen <markjen@tagged.net>
 * @todo Since Savant doesn't seem to be maintained anymore and I don't use
 * much of it's features, consider just forking the codebase.
 */
// imports {{{
/**
 * Uses savant library for View layer
 */
require_once 'Savant3.php';
// }}}
// {{{ tgif_page
/**
 * Handle the presentation layer when using Savant to display web pages.
 *
 * @package tgiframework
 * @subpackage templating
 */
class tgif_page
{
    // {{{ - $savant
    /**
     * @var Savant The view layer.
     */
    protected  $savant;
    // }}}
    // {{{ __construct()
    /**
     * 
     */
    public function __construct()
    {
        global $_TAG;
        $this->savant = new Savant3();
        $this->savant->addPath('template',APP_DIR.'/savant');
    }
    // }}}
    // {{{ - assign($key,$value)
    /**
     * Alias for Savant::assign()
     */
    public function assign($key,$value)
    {
        return $this->savant->assign($key,$value);
    }
    // }}}
    // {{{ - render($template)
    /**
     * Simple call to run template
     * @todo log error and redirect on savant errors
     * @todo see if Savant supports exceptions
     */
    function render($template)
    {

        //$_TAG->queue->publish('object tgif_page render');
        // already done in the header
        //$_TAG->queue->subscribe('tag_page output', 'ob_end_flush');

        // this buffer is caught below.
        ob_start();
        $result = $this->fetch($template);
        if ($this->savant->isError($result)) {
            // TODO: log error and redirect
            echo print_r($result, true);
            ob_end_flush();
            return;
        }
        echo $result;
        // this will call ob_end_flush() with priority -1
        //$_TAG->queue->publish('object tgif_page output', array('object'=>$this));
        ob_end_flush();

        // log pageviews to udp for successfully rendered requests
        //PageViewLogger::LogPageView($_SERVER['REQUEST_URI'], session_id(), isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null, tag_login::get_user_id());
        //
    }
    // }}}
    // {{{ - fetch($template)
    /**
     * Simple call to run template and fetch html
     * @return string html output
     */
    function fetch($template)
    {
        // setup the locale for the render
        //$locale = tag_intl::detect_locale();
        //tag_intl::set_locale($locale);
        // render the savant page
        $output = $this->savant->fetch($template);

        // reset locale back the english
        //tag_intl::set_locale('en_US');

        return $output;
    }
    // }}}
    // {{{ - isError($result)
    /**
     * Simple call to run template and fetch html
     * @return boolean
     */
    function isError($result)
    {
        return $this->savant->isError($result);
    }
    // }}}
    // {{{ + close_connection($event)
    /**
     * Inject connection_close into the content buffer.
     *
     * @todo something is rewriting the headers from Connection: close
     *      to "Cneonction: close." It was determined tht this is the
     *      Netscaler, we should investigate how to fix this.
     * @param $event array
     *      object: the tag page object
     */
    static function close_connection($event)
    {
        header(sprintf('Content-length: %d',ob_get_length()));
        header('Connection: close');
    }
    // }}}
}
// }}}
// note that the tag_queue callback for this is already in the config thing
// We need to ignore user abort because we will be queueing jobs ex post facto
//ignore_user_abort(true);
?>
