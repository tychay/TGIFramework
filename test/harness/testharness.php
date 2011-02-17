<?php
/*
 * Our test harness. Start here to run tests.
 *
 * @package test
 * @subpackage harness
 * @author diego matute <dmatute@tagged.com>
 * @author erik johannessen <ejohannessen@tagged.com>
 */
class testharness {

    // {{{ + run()
    /*
     * Creates a new testharness object, then invokes it with the passed arguments.
     */
    public static function run() {
        $harness = new testharness();
        $harness->run_tests($_SERVER['argv']);
    }
    // }}}

    // {{{ + run_tests($argv)
    /*
     * @param $argv
     * 
     * Runs all test cases specified by $argv, and prints the result.
     * 
     * First, we parse the passed arguments, determining if any options have
     * been specified. If so, we deal with those.
     * 
     * Then, we get the tests to run. If a file is specified, we run the tests
     * included in that file. If a directory is specified, we run the tests
     * contained in all file contained within that directory, recursively. Non-
     * test classes (classes that don't inherit from basetest) are ignored.
     * Only functions within test classes whose name begin with 'test*' are run.
     * 
     * Each test is run in turn. For each test, the test class is instantiated,
     * setUp() is called on the test object, then the test method, the finally
     * tearDown(). The test passes if no exceptions are thrown.
     * 
     * All test failures are recorded and printed to the screen. If all tests
     * pass, statistics about the test results are printed to the screen instead.
     */
    public function run_tests($argv) {
        $params = $this->_parse_parameters($argv);
        $uri_list = array();
        $filter = "/.*/";
        
        foreach($params as $key => $value) {
            if(is_numeric($key)) {
                $uri_list[] = $value;
            } else {
                switch($key) {
                    case 'filter':
                        $filter = "/.*".$value.".*/";
                        break;
                    case 'help':
                        $this->_usage();
                        break;
                    case 'version':
                        $this->_version();
                        break;
                    default:
                        $this->_usage();
                        break;
                }
            }
        }

        $files = $this->_get_files($argv);
        $before = get_declared_classes();
        $this->_load_files($files);
        $after = array_values(array_diff(get_declared_classes(), $before));
        
        $numTestsPassed = 0;
        $numTestsFailed = 0;
        $numAssertions = 0;
        $dots = 0;
        
        $start_time = microtime();
        
        echo "\n\n";
        
        foreach($after as $class) {
            if(is_subclass_of($class, 'basetest')) {
                try {
                    $testReflection = new ReflectionClass($class);
                } catch(Exception $e) {
                    // TODO: ignore exceptions and continue?
                    continue;
                }
                echo "." . ($dots++ % 30 == 0 ? "\n": "");
                
                foreach($testReflection->getMethods() as $method) {
                    $methodName = $method->name;
                    if (strpos($methodName, 'test') === 0 && preg_match($filter, $methodName) == 1) {
                        $obj = new $class;
                        $obj->setUp();
                        try {
                            $obj->$methodName();
                        } catch (Exception $e) {
                            $numTestsFailed += 1;
                            $message = $e->getMessage();
                            echo "Test $class::$methodName failed with message $message\n";
                        }
                        $obj->tearDown();
                        $numAssertions += $obj->getAssertionCount();
                        $numTestsPassed += 1;
                    }
                }
            }
        }
        
        echo "\n\n";
        
        $end_time = microtime();
        $time_in_seconds = round(( $end_time - $start_time ) / 1000);
        
        echo "Time: " . $time_in_seconds . ($time_in_seconds == 1 ? "": "s") . "\n";
        
        if($numTestsFailed == 0) {
            echo "OK ($numTestsPassed tests, $numAssertions assertions)\n";
        } else {
            echo "FAILURES!\n";
            echo "Tests: " . ( $numTestsPassed + $numTestsFailed) . ", Failures: $numTestsFailed.\n";
        }
        
    }
    // }}}

    // {{{ - _usage()
    /*
     * Displays helpful usage information, then exists the script
     */
    private function _usage() {
        echo "Usage: test [switches] <directory list>\n";
        echo "\n";
        echo "--filter <pattern> Filter which tests to run.\n";
        echo "\n";
        echo "--help             Prints this usage information.\n";
        echo "--version          Prints out version and exits.\n";
        exit;
    }
    // }}}

    // {{{ - _version()
    /*
     * Displayes the current version of this test harness, then exists the script.
     */
    private function _version() {
        echo "test: 0.1\n";
        exit;
    }
    // }}}

    // {{{ - _get_files($uri_list)
    /*
     * @param $uri_list
     * @return array
     * 
     * From a list of uris, recursively traverses down the directory structure, gathering
     * a list of all files contained therein.
     */
    private function _get_files($uri_list) {
        $files = array();
        
        foreach($uri_list as $uri) {
            if(!file_exists($uri)) {
                // TODO: should error?
                continue;
            }
            if(is_dir($uri)) {
                $rdi = new RecursiveDirectoryIterator($uri);
                $rii = new RecursiveIteratorIterator($rdi);
                $ri = new RegexIterator($rii, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
                foreach(iterator_to_array($ri) as $file) {
                    $files[] = $file[0];
                }
                
            } else {
                // simply add file
                $files[] = $uri;                
            }
        }
        
        return $files;
    }
    // }}}

    // {{{ - _load_files($files)
    /*
     * @param $files
     * 
     * Includes an array of files.
     */
    private function _load_files($files) {
        foreach($files as $file) {
            try {
                // TODO: ignore?
                @include_once $file;
            } catch(Exception $e) {
                // TODO: ignore?
            }
        }
    }
    // }}}

    // {{{ - _parse_parameters($argv)
    /*
     * @param $argv
     * @return array
     * 
     * thanks to mbirth at webwriters dot. http://php.net/manual/en/function.getopt.php.
     */
    private function _parse_parameters($argv) {
        $result = array();
        $params = $argv;
        reset($params);
        while (list($tmp, $p) = each($params)) {
            if ($p{0} == '-') {
                $pname = substr($p, 1);
                $value = true;
                if ($pname{0} == '-') {
                    // long-opt (--<param>)
                    $pname = substr($pname, 1);
                    if (strpos($p, '=') !== false) {
                        // value specified inline (--<param>=<value>)
                        list($pname, $value) = explode('=', substr($p, 2), 2);
                    }
                }
                // check if next parameter is a descriptor or a value
                $nextparm = current($params);
                if ($value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
                $result[$pname] = $value;
            } else {
                // param doesn't belong to any option
                $result[] = $p;
            }
        }
        return $result;
    }
    // }}}

}
?>
