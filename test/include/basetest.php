<?php 
/*
 * basetest - the base class for all tests -- all tests should inherit from this class
 * 
 * Provides three classes of functionality.
 * 1) defined setUp and tearDown methods, as well as a storage array for all mocks
 *    to ensure that they are unmocked at the end of the test
 * 2) assertions for the test cases to use.  a pass is any assertion that doesn't throw
 *    an exception. the total assertion count for a test case is also kept
 * 3) miscellaneous utility functions usable across a broad range of test.  currently
 *    only get_global_object is defined, but the possibility to place other functions
 *    here (such as those mocking widely-used components) is open
 *
 * @package test
 * @subpackage include
 * @author erik johannessen <ejohannessen@tagged.com>
 */
class basetest {

    // {{{ - $_mocks
    /**
     * Array to store mock objects so that they will automatically be unmocked at the
     * end of the test. Can store instances of either globalmock or dynamically-created
     * mock classes.
     */
    protected $_mocks = array();
    // }}}

    // {{{ - $_assertionCount
    /**
     * The number of assertions made on this test case.
     */
    private $_assertionCount = 0;
    // }}}

    // {{{ + setUp()
    /**
     * Override this method to perform setup at the beginning of each test case.
     * 
     * This method is invoked before each test method.
     */
    public function setUp() {
        
    }
    // }}}

    // {{{ + tearDown()
    /**
     * Override this method to perform cleanup at the end of each test case.
     * 
     * This method is invoked after each test method.
     * 
     * WARNING: if you do override this function, be sure to call parent::tearDown()
     * or your mock classes won't be automatically unmocked.
     */
    public function tearDown() {
        foreach ($this->_mocks as $mockObject) {
            $mockObject->unmock();
        }
    }
    // }}}

    // assertion functions

    // {{{ + assertTrue($expression, $message = '')
    /*
     * @param $expression
     * @param $message
     * 
     * @throws Exception with $message if $expression does not evaluate to true.
     */
    public function assertTrue($expression, $message = '') {
        $this->_assertionCount += 1;
        if (!$expression) {
            $message .= "\nFailed asserting that $expression is true.";
            throw new Exception($message);
        }
    }
    // }}}

    // {{{ + assertFalse($expression, $message = '')
    /*
     * @param $expression
     * @param $message
     * 
     * @throws Exception with $message if $expression does not evaluate to false.
     */
    public function assertFalse($expression, $message = '') {
        $this->_assertionCount += 1;
        if ($expression) {
            $message .= "\nFailed asserting that $expression is false.";
            throw new Exception($message);
        }
    }
    // }}}

    // {{{ + assertEquals($expected, $actual, $message = '')
    /*
     * @param $expected
     * @param $actual
     * @param $message
     * 
     * @throws Exception with $message if $expected is not equal to $actual.
     */
    public function assertEquals($expected, $actual, $message = '') {
        $this->_assertionCount += 1;
        if ($expected !== $actual) {
            $message .= "\nFailed asserting that $expected equals $actual.";
            throw new Exception($message);
        }
    }
    // }}}

    // {{{ + getAssertionCount()
    /*
     * Get the total number of assertions made during the run of this test case
     */
    public function getAssertionCount() {
        return $this->_assertionCount;
    }
    // }}}

    // {{{ + get_global_object($configName, $params = array())
    /*
     * @param $configName
     * @param $params
     * 
     * Simulate getting an object from the global loader.
     * In this instance, however, we ignore whatever might be in memcache,
     * and we mock the loader so that this object never gets put into memcache.
     */
    public static function get_global_object($configName, $params = array()) {
        $params = is_array($params) ? $params : array($params);
        $config = $_TAG->config('gld_' . $configName);
        $construct = $config['construct'];
        $className = $construct[0];

        if (isset($config['ids']) && is_array($config['ids'])) {
            $params = array_merge($params, $config['ids']);
        }
        $numParams = isset($config['params']) ? $config['params'] : count($params);
        if (count($params) < $numParams) {
            trigger_error("Not enough arguments supplied to instantiate $className.",  E_USER_ERROR);
        }

        if (count($construct) > 1) {
            $constructMethod = $construct[1];
            $object = call_user_func_array(array($className, $constructMethod), $params);
        } elseif ($numParams == 0) {
            $object = new $className();
        } elseif ($numParams == 1) {
            $object = new $className($params[0]);
        } else {
            $object = new $className($params);
        }

        if (isset($config['loaderLoader'])) {
            $loaderMethod = $config['loaderLoader'];
            $object->$loaderMethod(new LoaderMock());
        }

        return $object;
    }
    // }}}

}
?>