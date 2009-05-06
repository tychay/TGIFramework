<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_benchmark_iterate}
 *
 * @package tgiframework
 * @subpackage debugging
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_benchmark_iterate
// docs {{{
/**
 * Similar to PEAR {@link Benchmark_Iterate}, except it doesn’t violate the
 * substitution principle and doesn't have timer start/top overhead
 *
 * Run the date 100 times
 * <code>
 * <?php
 * $error_level = error_reporting(0); //mimic production
 *
 * ini_set('date.timezone',false);
 * $b1 = new tgif_benchmark_iterate(true);
 * $b1->run(100, 'date', 'c');
 * $b1->description = 'date("c") without date.timezone set'; //set after run
 * var_dump($b1->summary);
 * 
 * $b2 = new tgif_benchmark_iterate(true);
 * ini_set('date.timezone','America/Los_Angeles');
 * $b2->run(1000, 'date', 'c');
 * $b2->description = 'date("c") with date.timezone set';
 *
 * var_dump($b2->compare($b1));
 *
 * error_reporting($error_level); //restore errors
 * ?>
 * </code>
 *
 * @package tgiframework
 * @subpackage debugging
 * @author terry chay <tychay@php.net>
 */
// }}}
class tgif_benchmark_iterate
{
    // PRIVATE INTERNALS
    // {{{ - $_timer
    /**
     * The timer for the iteration
     * @var tgif_benchmark_timer
     */
    private $_timer;
    // }}}
    // {{{ - $_nullTimer
    /**
     * The timer that does a nop to compare it to
     * @var tgif_benchmark_timer
     */
    private $_nullTimer;
    // }}}
    // {{{ - $_numIteration
    /**
     * How many iterations did it do?
     * @var integer
     */
    private $_numIteration;
    // }}}
    // {{{ - $_functionName
    /**
     * $_functionName
     * @var string
     */
    private $_functionName;
    // }}}
    // RESERVED METHODS
    // {{{- __construct()
    /**
     * Constructor for object.
     *
     * @param boolean $trackRusage if true then it will track the resource usage
     * also
     * automatically
     */
    public function __construct($trackRusage=false)
    {
        $this->_timer     = new tgif_benchmark_timer(false,true);
        $this->_nullTimer = new tgif_benchmark_timer(false,true);
    }
    // }}}
    // COMMAND PATTERN
    // {{{ - run()
    /**
     * Benchmark a function or method
     *
     * The parameters are done through func_get_args() so they are defined
     * as follows:
     * 1. (int) the number of iterations to execute
     * 2. (mixed) the function to execute (will be parsed a la Benchmark_Iterate
     * 3. ... arguments to provide to the function
     */
    function run()
    {
        $args     = func_get_args();
        $max      = (int) array_shift($args);
        $function = array_shift($args);
        // generate functionName and callback {{{
        if (is_string($function)) {
            $this->_functionName = $function.'()';
            if (strstr($function, '::')) {
                $function = explode('::', $function_name);
            } elseif (strstr($function, '->')) {
                $function = explode('->', $function_name);
                $function[0] = $GLOBALS[$function[0]];
                $this->_functionName = '$'.$this->_functionName;
            }
        } else { //it must be an array
            if (is_string($function[0])) {
                $this->_functionName = sprintf('%s::%s()', $function[0], $function[1]);
            } else {
                $this->_functionName = sprintf('$_->%s()', $function[1]);
            }
        }
        // }}}
        $this->_numIteration = $max;
        // time run {{{
        $this->_timer->start();
        for ($i=0; $i<$max; ++$i) {
            call_user_func_array($function, $args);
        }
        $this->_timer->stop();
        // }}}
        // time null {{{
        $this->_nullTimer->start();
        for ($i=0; $i<$max; ++$i) {
        }
        $this->_nullTimer->stop();
        // }}}
    }
    // }}}
    // OUTPUT
    // {{{ - compare()
    /**
     * Benchmark a function or method
     *
     * The parameters are done through func_get_args() where you can provide
     * as many different comparisons as you want to other
     * {@link tgif_benchmark_iterate}s.
     * @return array information in human readable and computer parseable form
     */
    function compare()
    {
        $args      = func_get_args();
        $bench_ref = $this->summary;
        $count_ref = $bench_ref['count'];
        $bench_ref['compare'] = '0';
        $bench_ref['time_diff'] = 0;
        $bench_ref['rtime_diff'] = 0;
        $bench_ref['stime_diff'] = 0;
        $bench_ref['utime_diff'] = 0;
        $returns   = array($bench_ref);
        foreach ($args as $bench) {
            $bench = $bench->summary;
            $count = $bench['count'];
            $faster = ($bench_ref['rtime'] * $count) - ($bench['rtime'] * $count_ref);
            if ($faster > 0) {
                $bench['compare'] = '+';
            } elseif ($faster < 0) {
                $bench['compare'] = '-';
            } else {
                $bench['compare'] = 0;
            }
            foreach (array('time','rtime','stime','utime') as $key) {
                $ratio = 1 - abs(($bench[$key] * $count_ref) / ($bench_ref[$key] * $count));
                // if it is positive, then flip the denominator
                if ($ratio > 0) {
                    $ratio = 1/(1-$ratio);
                }
                $bench[$key.'_diff'] = $ratio;
            }
            $returns[] = $bench;
        }
        return $returns;
    }
    // }}}
    // {{{ + format($results)
    /**
     * Format a table of output from the compare function.
     *
     * @param array $results Output from {@link compare()}.
     * @return string HTML output
     */
    static function format($results)
    {
        //var_dump($results);
        $return = '<table class="benchmark_compare"><tr><th>mark</th><th>wall time</th><th>resource time</th></tr>';
        // seek max and min times {{{
        $times = array();
        $rtimes = array();
        foreach($results as $result) {
            $times[] = $result['time']/$result['count'];
            $rtimes[] = $result['rtime']/$result['count'];
        }
        $max_time  = max($times);
        $min_time  = min($times);
        $max_rtime = max($rtimes);
        $min_rtime = min($rtimes);
        // }}}
        unset ($times); unset($rtimes);
        // treat the first result special (it's a reference result) {{{
        $result = array_shift($results);
        $return .= sprintf(
            '<tr><td>%s</td><td style="background:#%s">%02fs</td><td style="background:#%s">%02fs</td>',
            $result['name'],
            self::_hex_color($result['time']/$result['count'], $min_time, $max_time),
            $result['time']/$result['count'],
            self::_hex_color($result['rtime']/$result['count'],$min_rtime, $max_rtime),
            $result['rtime']/$result['count']
        );
        // }}}
        foreach ($results as $result) {
            $return .= sprintf('<tr><td>%s</td><td style="background:#%s">%.2fx</td><td style="background:#%s">%.2fx</td>',
                $result['name'],
                self::_hex_color($result['time']/$result['count'], $min_time, $max_time),
                $result['time_diff'],
                self::_hex_color($result['rtime']/$result['count'],$min_rtime, $max_rtime),
                $result['rtime_diff']
            );
        }
        return $return . '</table>';
    }
    // }}}
    // {{{ + _hex_color($value,$min,$max)
    /**
     * @return string the hex code of the output on a red yellow green spectrum
     */
    static private function _hex_color($value, $min, $max)
    {
        $percentage = (($max-$value) / ($max-$min)); //distance from fastest
        return sprintf('%02x%02x00', 0xff*(1-$percentage), 0xff*$percentage);
    }
    // }}}
    // ACCESSORS
    // {{{ - __get($name)
    /**
     * Allow you to get values
     * @return mixed if false you didn't record that value
     */
    function __get($name)
    {
        switch (strtolower($name)) {
            case 'numiterations':
            case 'iterations':
            case 'numiteration':
            case 'count':
            return $this->_numIteration;
            case 'function':
            case 'functionname':
            case 'description':
            return $this->_functionName;
            case 'starttime':
            case 'begintime':
            case 'endtime':
            case 'stoptime':
            return $this->_timer->{$name};
            case 'timetaken':
            case 'timedifference':
            case 'rtimetaken':
            case 'stimetaken':
            case 'utimetaken':
            return $this->_timer->{$name} - $this->_nullTimer->{$name};
            case 'summary':
            return array(
                'name'  => $this->functionName,
                'count' => $this->numIteration,
                'time'  => $this->timeTaken,
                'rtime' => $this->rtimeTaken,
                'stime' => $this->stimeTaken,
                'utime' => $this->utimeTaken,
            );
        }
        trigger_error(sprintf('Unknown property %s',$name), E_USER_WARNING);
    }
    // }}}
    // {{{ - __set($name,$value)
    /**
     * Allow you to change the values
     */
    function __set($name, $value)
    {
        switch (strtolower($name)) {
            case 'function':
            case 'functionname':
            case 'description':
            $this->_functionName = $value;
            return;
        }
        trigger_error(sprintf('Unknown property %s',$name), E_USER_WARNING);
    }
    // }}}
}
// }}}
?>
