<?php
// {{{ dummy2
/**
 * another dummy test class to test globalmock
 *
 * @package test
 * @subpackage class
 * @author diego matute <dmatute@tagged.com>
*/
class dummy2 {
    // {{{ - MY_CONST
    /**
     * define some const for testing
     */
    const MY_CONST = 'DUMMY2';
    // }}}

    // {{{ + __construct()
    /**
     * constructor
     *
     */
    function __construct() {
    }
    // }}}
    // {{{ + _X_create_object()
    /**
     * build the dummy object
     * 
     * @return dummy
     */
    public static function _X_create_object() {
        return new dummy2();
    }
    // }}}
    // {{{ + publicFunction()
    /**
     * return place holder string for public function
     *
     * @return string
     */
    public function publicFunction() {
        return 'dummy2' . 'publicFunction';
    }
    // }}}
    // {{{ + publicFunctionCrossCall($key)
    /**
     * test calling a public function on another global function
     *
     * @param $key string
     * 
     * @return resulting public cross method call
     */
    public function publicFunctionCrossCall($key) {
        return $_TAG->dummyparams[$key]->publicFunctionCrossCall();
    }
    // }}}
    // {{{ + getMyConst()
    /**
     * return const
     *
     * @return string MY_CONST
     */
    public function getMyConst() {
        return self::MY_CONST;
    } 
    // }}}
}
//}}}
?>
