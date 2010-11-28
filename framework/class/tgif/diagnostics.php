<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * {@link tgif_diagnostics Page timers for diagnostics}.
 *
 * @package tgiframework
 * @subpackage debugging
 * @copyright Copyright 2007 Tagged, Inc, 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_diagnostics
// docs {{{
/**
 * Page timer class.
 *
 * This should be included to diagnose performance problems on the live site
 * on the suggestion of Johann after a site outage on 20070306. The code to
 * do filesystem logging of this was eliminated during the port to tgiframework
 * as <b>that</b> code is too abusive on the system and violated SRP. Wheee!
 * This has been replaced with a {@link tgif_log UDP logging system}.
 *
 * It is not necesarily ideal these timers exist in PHP userspace, but it is
 * quick, dirty, and effective.
 *
 * The following config variables are used
 * - diagnostics.diagnostics (false): should we bother doing diagnostics on the 
 *   diagnostics itself.
 * - diagnostics.monitor (false): Should we send diagnostics information to the
 *   monitoring service (was diagnostics_monitor)
 * - diagnostics.monitorEvent: If diagnostics_monitor is true, should we send
 *   diagnostics information of the events (instead of just the page times) to
 *   the monitoring service? (was diagnostics_should_send_event)
 *   all events (instead of just page events).
 * - firephp.diagnostics (false): should firephp output diagnostics?
 * - firephp.diagnostics_snaps (false): should firephp output snapshots
 *
 * @package tgiframework
 * @subpackage debugging
 * @author terry chay <tychay@php.net>
 */
// }}}
class tgif_diagnostics
{
    // {{{ - $guid
    /**
     * A globally unique identifier of the process
     *
     * Note, that this isn't exactly globally unique, but the possibility of
     * collision on this id is effectively zero and it has no possibility
     * of being generated the same way twice (like a guid).
     *
     * @var string
     */
    public $guid;
    // }}}
    // {{{ - $parentGuid
    /**
     * Parent GUID
     */
    public $parentGuid = 0;
    // }}}
    // {{{ - $pid
    /**
     * What process id we're running on
     * @var integer
     */
    public $pid;
    // }}}
    // {{{ - $memory
    /**
     * This is the peak memory usage. Pretty much valid only in PHP 5, but
     * during diagnostics we may sample at multiple locations to keep upping it.
     * @var integer
     */
    public $memory = 0;
    // }}}
    // {{{ - $server
    /**
     * The IP of the server, or its uname if run from the command line.
     * @var string
     */
    public $server;
    // }}}
    // {{{ - $url
    /**
     * The url of this page.
     *
     * If called from the command line, it will contain the name of the binary
     * @var string
     */
    public $url;
    // }}}
    // {{{ - $timerInfo
    /**
     * @var array
     * This consists of an array of arrays representing the timer names and
     * each timer in that array and then a hash of contents in that.
     */
    public $timerInfo = array();
    // }}}
    // PRIVATE INTERNALS
    // {{{ - $_config
    /**
     * Storage of configuration variables (for quicker reference).
     * 
     * This contains the default values so that params always exist.
     * @var array
     */
    private $_config = array(
        'diagnostics'   => false,
        'monitor'       => false,
        'monitorEvent'  => false,
    );
    // }}}
    // {{{ - $_timers
    /**
     * Storage of all <b>currently running</b> timers by name.
     * @var array
     */
    private $_timers = array();
    // }}}
    // {{{ - $_timerStuff
    /**
     * Storage of stuff to record with the timer
     * @var array
     */
    private $_timerStuff = array();
    // }}}
    // {{{ - $_snaps
    /**
     * @var array
     * Storage of snapshot data
     */
    private $_snaps = array();
    // }}}
    // RESERVED METHODS
    // {{{- __construct()
    /**
     * @uses tgif_http::self_url() url generator
     * @uses tgif_encode::create_key() uniq id generator
     */
    public function __construct()
    {
        //global $_TAG; // runkit superglobal
        // read configuration and override defaults
        if ( is_array($config = $_TAG->config('diagnostics')) ) {
            $this->_config = array_merge( $this->_config, $config );
        }
        ob_start();
        $this->startTimer('page'); //the time here will be modded ex post facto
                                   // by setPageTimer()
        $this->pid = getmypid();
        // generate url and server{{{
        if (isset($_SERVER['SERVER_ADDR'])) {
            $this->server = $_SERVER['SERVER_ADDR'];
            $this->url    = tgif_http::self_url();
        } else {
            $this->server = php_uname('n');
            $this->url    = $_SERVER['argv'][0];
        }
        // }}}
        // Globally unique identifier is a hash of:
        //      microtime + entropy + serverip + pid
        // store the first 10 base-64 digits of hash
        $this->guid = tgif_encode::create_key(uniqid(rand(),true).$this->server.$this->pid);
    }
    // }}}
    // {{{ - __sleep()
    /**
     * This is left over from when we'd serialize this to the filesystem
     */
    function __sleep()
    {
        return array('guid','parentGuid','pid','memory','server','url','timerInfo','_config');
    }
    // }}}
    // {{{ - __wakeup()
    /**
     */
    function __wakeup()
    {
        $this->_timers = array();
        $this->_timerStuff = array();
        $this->_logFiles = array();
    }
    // }}}
    // {{{ - shutdown()
    public function shutdown()
    {
        //global $_TAG; //runkit
        if (empty($_TAG)) { return; } //sometimes $_TAG is missing
        $datasize = strlen(ob_get_contents());
        $this->setPeakMemory();
        // UDP Log page statistics {{{
        // Must call logger before stopTimer because we need the $_timers
        // variable to exist.
        if ( $this->_config['monitor'] ) {
            self::_monitor_event('end', array(
                'guid'          => $this->guid,
                'server'        => $this->server,
                'process-id'    => $this->pid,
                'end-time'      => tgif_benchmark_timer::microtime_float(microtime()),
                'output-size'   => (int) $datasize,
                'memory'        => $this->memory,
                'error'         => 'false',
                'start-time'    => $this->_timers['page']->startTime,
                'url'           => $this->url
            ));
        }
        // }}}
        $this->stopTimer('page');
        // firephp summary {{{
        // Have to have move ob_end_flush() here because we can't let
        // destruction happen before ob_flush on api
        if ( $_TAG->config('firephp.diagnostics', true) ) {
            // you can log this instead if you want a simpler diagnostic returned.
            $this->summary('totals');
            $_TAG->firephp->log($this,'diagnostics');
        }
        // }}}
        ob_end_flush();
        //return $buffer;
    }
    // }}}
    // STATIC METHODS
    // {{{ + memory_usage()
    /**
     */
    static function memory_usage()
    {
        if (function_exists('memory_get_peak_usage')) {
            return memory_get_peak_usage();
        }
        return memory_get_usage();
    }
    // }}}
    // ACCESSORS
    // {{{ - guid([$ofParent])
    function guid($ofParent=false)
    {
        return ($ofParent && $this->parentGuid) ? $this->parentGuid : $this->guid;
    }
    // }}}
    // {{{ - setParentGuid($guid)
    function setParentGuid($guid)
    {
        $this->parentGuid = $guid;
    }
    // }}}
    // MEMORY
    // {{{ - setPeakMemory()
    /**
     * This archaeism is leftover from the pre PHP 5.2 days when memory
     * usage was not always peak.
     * @return integer the peak memory usage in bytes
     */
    function setPeakMemory()
    {
        $mem = self::memory_usage();
        if ($mem > $this->memory) { $this->memory = $mem; }
        return $this->memory;
    }
    // }}}
    // TIMERS
    // {{{ - setPageTimer($time)
    /**
     * Set a timer start time ex post facto.
     *
     * This is used to get the real page start time.
     *
     * @param $timer_name string the queue to put the timer in.
     */
    function setPageTimer($time)
    {
        $this->_diagStart();
        $this->_timers['page']->startTime = $time;
        // UDP log page start event
        if ( $this->_config['monitor'] ) {
            self::_monitor_event('start', array(
                'guid'          => $this->guid,
                'server'        => $this->server,
                'process-id'    => $this->pid,
                'start-time'    => $this->_timers['page']->startTime, //it's been transformed on get!
                'url'           => $this->url
            ));
        }
        $this->_diagStop();
    }
    // }}}
    // {{{ - isRunning($timer_name)
    /**
     * Check to see if a timer is already running (to prevent nesting).
     *
     * Example:<code>
     * if ( !$isRunning = $_TAG->diagnostics->isRunning($timer_name) ) {
     *      $_TAG->diagnostics->startTimer($timer_name);
     * }
     * // ... do work ...
     * if ( !$isRunning ) {
     *      $_TAG->diagnostics->stopTimer($timer_name);
     * }
     * </code>
     * @param $timer_name string the queue to put the timer in.
     */
    function isRunning($timer_name)
    {
        return array_key_exists($timer_name, $this->_timers);
    }
    // }}}
    // {{{ - startTimer($timer_name[,$name,$stuff])
    /**
     * Start a timer.
     * @param $timer_name string the queue to put the timer in.
     * @param $name string store stuff in this bin
     * @param $stuff array stuff to be stored (NOT a hash)
     */
    function startTimer($timer_name, $name='', $stuff=array())
    {
        //global $_TAG; //runkit
        //if ($_TAG->config('firephp.diagnostics', true) && ($timer_name!='diagnostics')) { $_TAG->firephp->log(sprintf('started %s',$timer_name),'timer'); }
        // don't record if page already shut down
        if ((strcmp($timer_name,'page')!==0)  && !array_key_exists('page',$this->_timers)) { return; }
        $this->_diagStart();
        $this->_timerStuff[$timer_name] = array($name, $stuff);
        $this->_timers[$timer_name] = new tgif_benchmark_timer(true);
        $this->_diagStop();
    }
    // }}}
    // {{{ - stopTimer($timer_name[,$more_stuff])
    /**
     * Stops a timer.
     *
     * Note, I have to handle "hanging" diagnostic timer, on page end.
     * as long as I don't record it I'm fine.
     * @param $more_stuff array more values to add onto array (NOT a hash)
     */
    function stopTimer($timer_name,$more_stuff=array())
    {
        //global $_TAG; //runkit
        //if ($_TAG->config('firephp.diagnostics', true) && ($timer_name!='diagnostics')) { $_TAG->firephp->log(sprintf('stopped %s',$timer_name),'timer'); }
        // don't record if page already shut down
        if (!array_key_exists('page',$this->_timers)) { return; }
        if(!array_key_exists($timer_name,$this->_timers)){ return;}
        $this->_diagStart();
        // compute execute time {{{
        // gmp_sub is broken
        $this->_timers[$timer_name]->stop();
        $timer = $this->_timers[$timer_name];
        unset($this->_timers[$timer_name]);
        // }}}
        // initialize timerInfo element {{{
        if (!array_key_exists($timer_name,$this->timerInfo)) {
            $this->timerInfo[$timer_name] = array();
        }
        // }}}
        // save data {{{
        $data = array(
            'time_stamp'    => $timer->startTime,
            'time_taken'    => $timer->timeTaken,
            'name'          => $this->_timerStuff[$timer_name][0],
            'stuff'         => array_merge(
                is_array($this->_timerStuff[$timer_name][1])?
                    $this->_timerStuff[$timer_name][1]:
                    array(),
                $more_stuff),
            );
        $this->timerInfo[$timer_name][] = $data;
        // }}}
        unset($this->_timerStuff[$timer_name]);
        if (strcmp($timer_name,'page')===0) {
            $this->_diagStop();
        } elseif ( $this->_config['monitor'] 
                && $this->_config['monitorEvent']
                && (strcmp($timer_name,'diagnostic')!==0) ) {
            // record to monitor unless it is a page end event {{{
            // (recorded elsewhere)
            $send_data = array_merge($data['stuff'], array(
                'name'          => $data['name'],
                'time_taken'    => $data['time_taken'],
                'guid'          => $this->guid,
                'server'        => $this->server,
                'process-id'    => $this->pid,
                'memory'        => $this->memory,
                'start-time'    => $data['time_stamp'],
                'end-time'      => tgif_benchmark_timer::microtime_float(microtime()),
                'url'           => $this->url,
                //'user-id'       => tgif_login::get_user_id(),
                ));
            self::_monitor_event($timer_name, $send_data);
            // }}}
            $this->_diagStop();
        }
    }
    // }}}
    // {{{ - _diagStart()
    /**
     * Time time spent on diagnostics
     */
    private function _diagStart()
    {
        if ( !$this->_config['diagnostics'] ) { return; }
        $this->_timers['diagnostic'] = new tgif_benchmark_timer(true);
        //$this->setPeakMemory();
    }
    // }}}
    // {{{ - _diagStop()
    /**
     * Time time spent on diagnostics
     */
    private function _diagStop()
    {
        if ( !$this->_config['diagnostics'] ) { return; }
        if ( !array_key_exists('page',$this->_timers) ) { return; }
        $timer = $this->_timers['diagnostic'];
        unset($this->_timers['diagnostic']);
        $timer->stop();
        $this->timerInfo['diagnostic'][] = array(
            'time_stamp'    => $timer->startTime,
            'time_taken'    => $timer->timeTaken,
            'name'          => '-',
            'stuff'         => array()
            );
    }
    // }}}
    // SNAPSHOT
    // {{{ - beginSnapshot($snapshotId[,$params])
    /**
     * Start a special timer known as a "snapshot"
     *
     * @param $snapshotId string the name of the timer
     */
    function beginSnapshot($snapshotId, $params=array())
    {
        //global $_TAG; //runkit
        $this->_diagStart();
        //if ($_TAG->config('firephp.diagnostics', true)) { $_TAG->firephp->log(sprintf('started %s',$snapshotId),'snap'); }
        // don't overwrite a snap!
        if (array_key_exists($snapshotId, $this->_snaps)) { $this->_diagStop(); return; }
        $this->_snaps[$snapshotId] = array(
            'time'      => microtime(),
            'snapshot'  => $this->timerInfo,
            'params'    => $params
        );
        $this->_diagStop();
    }
    // }}}
    // {{{ - setSnapshotParam($snapshotId, $key, $value)
    /**
     * Reconfigure snapshot param
     *
     * @param $snapshotId string the name of the timer
     */
    function setSnapshotParam($snapshotId, $key, $value)
    {
        //global $_TAG; //runkit
        $this->_diagStart();
        // test for existence
        if (!array_key_exists($snapshotId, $this->_snaps)) {
            $_TAG->firephp->log(sprintf('No such snapshot: %s!',$snapshotId));
            $this->_diagStop();
            return;
        }
        $this->_snaps[$snapshotId]['params'][$key] = $value;
        $this->_diagStop();
    }
    // }}}
    // {{{ - endSnapshot($snapshotId)
    /**
     * Stop a special timer
     *
     * If there is param known as "callback" then it will call that as a
     * function with the snapshot results returned as an array.
     *
     * @param $snapshotId string the name of the timer
     * @return the snapshot data
     */
    function endSnapshot($snapshotId)
    {
        //global $_TAG; //runkit
        $this->_diagStart();
        //if ($_TAG->config('firephp.diagnostics', true)) { $_TAG->firephp->log(sprintf('stopped %s',$snapshotId),'snap'); }
        // test for existence
        if (!array_key_exists($snapshotId, $this->_snaps)) { $this->_diagStop(); return; }
        $returns = $this->_snaps[$snapshotId];
        unset($this->_snaps[$snapshotId]);
        $returns['time'] = tgif_benchmark_timer::microtime_subtract(microtime(),$returns['time']);
        $old_info = $returns['snapshot'];
        $current_info = $this->timerInfo;
        $returns['snapshot'] = array();
        foreach ($current_info as $key=>$value) {
            $new_count = count($value);
            $old_count = (array_key_exists($key,$old_info))
                       ? count($old_info[$key])
                       : 0;
            //if ($_TAG->config('firephp.diagnostics', true)) { $_TAG->firephp->log(sprintf('%s:%d:%d',$key,$new_count,$old_count),'counts'); }
            if ($new_count != $old_count) {
                $returns['snapshot'][$key] = array_slice($value,$old_count);
            }
        }
        if (array_key_exists('callback',$returns['params'])) {
            call_user_func($returns['params']['callback'],$returns);
        }
        if ($_TAG->config('firephp.diagnostics_snaps', true)) {
            $_TAG->firephp->log($returns,sprintf('snap %s',$snapshotId));
        }
        $this->_diagStop();
        return $returns;
    }
    // }}}
    // OUTPUT
    // {{{ - summary([$type])
    /**
     * Returns some diagnostics of the page
     *
     * @param string $type Can be various things:
     * <b>simple</b>: (string)
     * This replaces the old quick_diag() function. It is also slightly more
     * accurate of a timer. The idea is to embed some diagnostics into
     * an HTML in a relatively safe manner. Mouseover the copyright symbol
     * on Facebook.com for an idea.
     *
     * The numbers:
     * 1. is the time to generate the page (in msec), the second
     * 2. is the peak memory usage (if possible) in MB
     * 3. last two digits of IP of server that served the page
     *
     * <b>totals</b>: (array)
     * This returns the total number of calls and total times for each
     * timer category. This was used to output diagnostics into
     * firephp.
     *
     * <b>data</b>: (array)
     * Array containing complete diagnostics.
     * The idea is to take this output and serialize into json
     * for rendering in the browser.
     * 
     * @return mixed diagnostics (see $type above)
     */
    function summary($type = 'simple')
    {
        switch ($type) {
        case 'totals':
            return $this->_totalSummary();
        case 'data':
            return $this->_dataSummary();
        case 'simple':
        default:
            return $this->_simpleSummary();
        }
    }
    // }}}
    // {{{ - _simpleSummary([$timerName])
    /**
     * Returns some diagnostics of the page
     *
     * The numbers:
     * 1. is the time to generate the page (in msec), the second
     * 2. is the peak memory usage (if possible) in MB
     * 3. last two digits of IP of server that served the page
     *
     * @return string diagnostics
     */
    private function _simpleSummary()
    {
        $timer_name = 'page';
        //if peak memory is not installed, then append a ?
        $peak = (function_exists('memory_get_peak_usage')) ? '' : '?';
        $server_parts = explode('.',$this->server);
        $server = (count($server_parts) > 3)
                ? sprintf('%d.%d', $server_parts[2],$server_parts[3])
                : $this->server;
        $this->_timers[$timer_name]->stop(); // stop is the same as a "lap" timer
        return sprintf(
            '%d %.1f%sMB %s',
            $this->_timers[$timer_name]->timeTaken*1000,
            $this->setPeakMemory()/1024/1024,
            $peak,
            $server
            );
    }
    // }}}
    // {{{ - _dataSummary()
    /**
     * Generates an array containing a complete diagnostics.
     *
     * @return array complete diagnostics suitable for analysis
     */
    private function _dataSummary()
    {
        $this->_timers['page']->stop(); // stop is the same as a "lap" timer

        $times = $this->timerInfo;
        // don't clutter results with diagnostic timers
        unset($times['diagnostic']);
        $returns = array(
            'url'           => $this->url,
            'server'        => $this->server,
            'pid'           => $this->pid,
            'guid'          => $this->guid,
            'parent guid'   => $this->parentGuid,
            'peak memory'   => $this->setPeakMemory(),
            'summary'       => $this->_totalSummary(),
            'total_time'    => $this->_timers['page']->timeTaken,
            'times'         => $times,
        );
        return $returns;
    }
    // }}}
    // {{{ - _totalSummary()
    /**
     * Note that in addition to returning the summary data,
     * this saves the totals to a public variable ($this->summary)
     *
     * @return array $this->summary.
     */
    private function _totalSummary()
    {
        $summaries = array();
        foreach ($this->timerInfo as $timer_name=>$timer_data) {
            $time = 0;
            for ($i=0,$count=count($timer_data); $i<$count; ++$i) {
                if (isset($timer_data[$i]['time_taken'])) {
                    $time += $timer_data[$i]['time_taken'];
                }
            }
            $summaries[$timer_name] = array('calls'=>$count, 'time'=>$time);
        }
        $this->summary = $summaries;
        // so little time spend on diagnostics, let's not record it.
        unset($this->timerInfo['diagnostic']);
        return $this->summary;
    }
    // }}}
    // DATA
    // {{{ - dataByType()
    /**
     * @access public
     * @return array data in a format that can be plotted with phpswfcharts as
     *  pie chart
     */
    function dataByType()
    {
        $sums = array();
        foreach ($this->timerInfo as $type=>$times) {
            $time = 0;
            foreach ($times as $time_info) {
                $time += $time_info['time_taken'];
            }
            $sums[$type] = $time;
        }
        $total_time = $sums['page'];
        unset($sums['page']);
        foreach ($sums as $time) {
            $total_time -= $time;
        }
        $sums['php'] = $total_time;
        return array(
            array_merge(array(''),array_keys($sums)),
            array_merge(array(''),array_values($sums))
            );
    }
    // }}}
    // PRIVATE: UDP log page start/page end events
    // {{{ + _monitor_event($eventType,$packetData)
    /**
     * Send UDP message of events to the platform monitor server.
     *
     * @access private
     * @param string $eventType the name of the event, start and stop are
     *      special ones related to the page
     * @param array $packetData additional key-values of the packet
     * @todo need to write {@link tgif_log::log_message()} routine
     */
    private static function _monitor_event($eventType,$packetData)
    {
        $logger = tgif_log::log_message('platform_monitor', $eventType, $packetData, true, array('tgif_log_monitorformatter', 'format'));
    }
    // }}}
}
// }}}
?>
