<?php
/**
 * This class is used to trap, suppress, and keep track of certain errors that occur when testing.
 * Especially useful when negative testing, where we *want* to trigger certain error conditions.
 * 
 * By suppressing the output, we keep our test output clean of expected errors, meaning that real
 * problems that crop up won't be lost in the clutter.  We can also keep track of if an error was
 * triggered, and if so, how many times, which can also be useful for testing purposes.
 *
 * Sample usage:
 *
 * $error_handler = new testerrorhandler();
 * $error_handler->suppressError('shared/class/tgif/dao.php', E_USER_NOTICE);
 * 
 * ; // do something here that triggers the error in question
 * 
 * $this->assertEquals(1, $error_handler->numErrorsSuppressed('shared/class/tag/dao.php', E_USER_NOTICE));
 * 
 * $error_handler->restorePreviousHandler();
 * 
 * Any other errors that occur, besides the ones we're specifically looking for, will be passed on
 * to the default error handler as usual.
 *
 * @package test
 * @subpackage include
 * @author erik johannessen <ejohannessen@tagged.com>
 */
class testerrorhandler {
    
    // {{{ - $_suppressedErrors
    /**
     * An array that keeps track of which errors this handler is suppressing.
     * Each element in the array will be an array of three elements:
     *      'filename' - the name of the file where the error is triggered.
     *      'errorno'  - a number representing the type of error triggered.
     *      'numCalls' - the number of times this error handler has suppressed this error.
     */
    private $_suppressedErrors = array();
    // }}}
    
    // CONSTRUCTOR
    // {{{ + __construct()
    /**
     * Sets this object to be the current error handler.
     */
    public function __construct() {
        set_error_handler($this, error_reporting());
    }
    // }}}

    // DESTRUCTOR
    // {{{ + __destruct()
    /**
     * Restores the original error handler if the user forgets to.
     */
    public function __destruct() {
        $this->restorePreviousHandler();
    }
    // }}}

    // {{{ + suppressError()
    /**
     * Adds the given error (filename and error number) to the suppressed errors list.
     */
    public function suppressError($filename, $errorno) {
        $this->_suppressedErrors[] = array(
            'filename'  => $filename,
            'errorno'   => $errorno,
            'numCalls'  => 0
        );
    }
    // }}}

    // {{{ + __invoke()
    /**
     * This function is called when an error is triggered.  This is a PHP magic method called when this object is called as though it were a function.
     * 
     * Goes through the list of suppressed errors.  If the error that triggered this method matches a suppressed error,
     * increment that error count and return true, which halts the scope of this error.
     * Otherwise, we simply pass the error on the default error handler.
     */
    public function __invoke() {
        list($errno, $errstr, $errfile, $errline, $errcontext) = func_get_args();
        foreach ($this->_suppressedErrors as &$error) {
            if ( (strpos($errfile, $error['filename']) !== false) && ($errno === $error['errorno']) ) {
                $error['numCalls'] += 1;
                return true;
            }
        }
        return false;
    }
    // }}}

    // {{{ + numErrorsSuppressed()
    /**
     * Returns the number of times this error handler suppressed the given error.
     * 
     * If no error is given, returns the total number of errors suppressed by this error handler.
     */
    public function numErrorsSuppressed($filename = null, $errorno = -1) {
        if (is_null($filename)) {
            $totalErrors = 0;
            foreach ($this->_suppressedErrors as $error) {
                $totalErrors += $error['numCalls'];
            }
            return $totalErrors;
        } else {
            foreach ($this->_suppressedErrors as $error) {
                if ( ($filename === $error['filename']) && ($errorno === $error['errorno']) ) {
                    return $error['numCalls'];
                }
            }
        }
        return 0;
    }
    // }}}

    // {{{ + restorePreviousHandler()
    /**
     * Restores the original error handler.
     */
    public function restorePreviousHandler() {
        restore_error_handler();
    }
    // }}}
}
?>
