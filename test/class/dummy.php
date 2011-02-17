<?php
// {{{ dummy
/**
 * dummy test class to test globalmock
 *
 * @package test
 * @subpackage class
 * @author diego matute <dmatute@tagged.com>
*/
class dummy {
    // {{{ - MY_CONST
    /**
     * define some const for testing
     */
    const MY_CONST = 'DUMMY';
    // }}}
    // {{{ - $publicParam
    /**
     * some public property for testing
     */
    public $publicParam;
    // }}}
    // {{{ - $_privateParam1    
    /**
     * some private property for testing
     */
    private $_privateParam1;
    // }}}
    // {{{ - $_privateParam2    
    /**
     * some other private property for testing
     */
    private $_privateParam2;
    // }}}
    // {{{ - $getcalled
    /**
     * some other public property for testing simple assignments in __get
     */
    public $getcalled;
    // }}}
    // {{{ - $assign
    /**
     * some public property for testing advanced assignments in __get
     */
    public $assign;
    // }}}
    // {{{ __construct($p1[,$p2])
    /**
     * constructor
     *
     * @param $p1 string
     * @param $p2 string
     */
    function __construct($p1 = 'none', $p2 = 'none2') {
        $this->_privateParam1 = 'dummy' . $p1;
        $this->_privateParam2 = 'dummy' . $p2;

        $this->publicParam = 'dummy' . 'publicParam';
        $this->getcalled = 'dummy' . 'no';

        $this->assign = 'defaultassign';
    }
    // }}}
    // {{{ + __get($key)
    /**
     * implement __get to test accessing properties which are not explicitly declared
     *
     * @param $key string name of property requested
     * @return mixed|'unknownget'
     */
    public function __get($key) {
        switch($key) {
            case 'testget1':
                $this->getcalled = 'dummy' . 'yes';
                return 'dummy' . 'yestestget1';
            case 'testget2':
                $this->assign = $this->testget3;
                return 'testget2';
            case 'testget3':
                return 'testget3';
        }
        return 'unknownget';
    }
    // }}}
    // {{{ + __set($key, $value)
    /**
     * implement __set to test setting properties
     *
     * @param $key string name of property to set
     * @param $value mixed value to set
     */
    public function __set($key, $value) {
        switch($key) {
            case 'testset1':
                $this->$key = $value;
        }
    }
    // }}}
    // {{{ + __call($method, $args)
    /**
     * implement __call to test calls which may not exist
     *
     * @param $method string name of function requested
     * @param $args mixed arguments for method
     * @return mixed|'unknowncall'
     */
    public function __call($method, $args) {
        switch($method) {
            case 'testcall1':
                return 'testcall1';
            case 'testcall2':
                return 'testcall2' . $args[0];
            case 'testcall3':
                return $this->testcall1();
            case 'testcall4':
                return $this->testcall2($args[0]);
            case 'testcall5':
                return $this->privateFunction();
            case 'testcall6':
                return $this->privateFunctionCall();
        }
        return 'unknowncall';
    }
    // }}}
    // {{{ + _X_create_object($p1[,$p2])
    /**
     * build the dummy object
     *
     * @param $p1 string
     * @param $p2 string
     * 
     * @return dummy
     */
    public static function _X_create_object($p1, $p2 = null) {
        return new dummy($p1, $p2);
    }
    // }}}
    // {{{ + publicGetPublicParam()
    /**
     * return value of public property
     *
     * @return string
     */
    public function publicGetPublicParam() {
        return $this->publicParam;
    }
    // }}}
    // {{{ + publicGetPrivateParam()
    /**
     * return value of public property
     *
     * @return string
     */
    public function publicGetPrivateParam() {
        return $this->_privateParam1;
    }
    // }}}
    // {{{ + publicGetPrivateParam2()
    /**
     * return value of private property
     *
     * @return string
     */
    public function publicGetPrivateParam2() {
        return $this->_privateParam2;
    }
    // }}}
    // {{{ + publicFunction()
    /**
     * Return place holder string for public function
     *
     * @return string
     */
    public function publicFunction() {
        return 'dummy' . 'publicFunction';
    }
    // }}}
    // {{{ + publicFunctionCallPrivateFunction()
    /**
     * call another private function using $this
     *
     * @return string
     */
    public function publicFunctionCallPrivateFunction() {
        return $this->privateFunction();
    }
    // }}}
    // {{{ - privateFunction()
    /**
     * return place holder string for private function
     *
     * @return string
     */
    private function privateFunction() {
        return 'dummy' . 'privateFunction';
    }
    // }}}
    // {{{ + publicFunctionCrossCall()
    /**
     * test call from another mocked class to public method
     *
     * @return string
     */
    public function publicFunctionCrossCall() {
        return 'dummy' . 'publicFunctionCrossCall';
    }
    // }}}
    // {{{ - privateFunctionCall()
    /**
     * test private function call from _call
     *
     * @return string
     */
    private function privateFunctionCall() {
        return $this->testcall1();
    }
    // }}}
    // {{{ + publicFunctionPassArgByReference(&$array)
    /**
     * public function modify array passed by reference
     *
     * @param $array mixed pass by reference array
     *
     */
    public function publicFunctionPassArgByReference(&$array) {
        array_unshift($array, 'a');
    }
    // }}}
    // {{{ + publicFunctionCallPrivateFunctionPassArgByReference()
    /**
     * test returning value of array after call to private method with pass by reference
     *
     * @return mixed
     */
    public function publicFunctionCallPrivateFunctionPassArgByReference() {
        $array = array('a');
        $this->privateFunctionPassArgByReference($array);
        return $array;
    }
    // }}}
    // {{{ - privateFunctionPassArgByReference(&$array)
    /**
     * private function modify array passed by reference
     *
     * @param $array mixed pass by reference array
     */
    private function privateFunctionPassArgByReference(&$array) {
        array_unshift($array, 'b');
    }
    // }}}
    // {{{ getMyConst()
    /**
     * return const
     *
     * @return string MY_CONST
     */
    public function getMyConst() {
        return self::MY_CONST;
    }
    // }}}
    // {{{ getPublicStaticFunction()
    /**
     * self call to public static function
     *
     * @return string public static function placeholder string
     */
    public function getPublicStaticFunction() {
        return self::publicStaticFunction();
    }
    // }}}
    // {{{ + publicStaticFunction()
    /**
     * simple public static function
     * 
     * @return string public static function placeholder string
     */
    public static function publicStaticFunction() {
        return 'dummy' . 'publicStaticFunction';
    }
    // }}}
    // {{{ + typeHintArg(dummy $dummy)
    /**
     * public function with type hinting
     * 
     * @param $dummy dummy
     * 
     * @return 
     */
    public function typeHintArg(dummy $dummy) {
        return $dummy->publicFunction();
    }
    // }}}
}
// }}}
?>
