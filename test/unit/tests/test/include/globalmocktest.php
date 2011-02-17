<?php
// {{{ globalmocktest
/**
 * test globalmock using dummy classes
 *
 * @package test
 * @subpackage unit
 * @author diego matute <dmatute@tagged.com>
 * @author erik johannessen <ejohannessen@tagged.com>
*/
class globalmocktest extends basetest {

    // {{{ + testGetPublicProperty()
    /**
     * test retrieving public property
     *
     */
    public function testGetPublicProperty() {
        $publicParam = $_TAG->dummynoparams->publicParam;
        $gm = new globalmock('dummynoparams');
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals($publicParam, $m->publicParam, 'Did not get public parameter');
    }
    // }}}
    // {{{ + testGetPropertyWithGet()
    /**
     * test retrieving a property via __get
     *
     */
    public function testGetPropertyWithGet() {
        $testget1 = $_TAG->dummynoparams->testget1;
        $gm = new globalmock('dummynoparams');
        $this->_mocks[] = $m = $gm->mock();
        $getcalled = $m->getcalled;
        $this->assertEquals($testget1, $m->testget1, 'Did not get test1get parameter');
        $getcalled = $m->getcalled;
    }
    // }}}
    // {{{ + testGetPropertyWithGetLocalMod()
    /**
     * test retrieving property which gets modified in __get
     *
     */
    public function testGetPropertyWithGetLocalMod() {
        $gm = new globalmock('dummynoparams');
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals('dummy'.'no', $m->getcalled, 'get should not have been called for testget1 in mock');
        $m->testget1;
        $this->assertEquals('dummy'.'yes', $m->getcalled, 'get should have been called for testget1 in mock');
    }
    // }}}
    // {{{ + testGetPropertyNumCalls()
    /**
     * test numCalls works on __get
     *
     */
    public function testGetPropertyNumCalls() {
        $gm = new globalmock('dummynoparams');
        $this->_mocks[] = $m = $gm->mock();
        $m->testget1;
        $m->testget3;
        $m->getcalled;
        $this->assertEquals(1, $m->numCalls('__get', 'testget1'));
        $this->assertEquals(1, $m->numCalls('__get', 'testget3'));
        $this->assertEquals(0, $m->numCalls('__get', 'getcalled'));
        $this->assertEquals(2, $m->numCalls('__get'));
    }
    // }}}
    // {{{ + testSetPropertyNumCalls()
    /**
     * test numCalls works on __set
     *
     */
    public function testSetPropertyNumCalls() {
        $someval = 'someval';
        $someotherval = 'someotherval';
        $gm = new globalmock('dummynoparams');
        $this->_mocks[] = $m = $gm->mock();
        $m->testset1 = $someval;
        $m->assign = $someotherval;
        $this->assertEquals(1, $m->numCalls('__set', 'testset1', $someval));
        $this->assertEquals(0, $m->numCalls('__set', 'assign', $someotherval));
        $this->assertEquals(1, $m->numCalls('__set'));
    }
    // }}}
    // {{{ + testCallMethodNumCalls()
    /**
     * test numCalls works on __call
     *
     */
    public function testCallMethodNumCalls() {
        $arg = 'arg';
        $gm = new globalmock('dummynoparams');
        $this->_mocks[] = $m = $gm->mock();
        $m->testcall1();
        $m->testcall4($arg);
        $m->publicGetPublicParam();
        $this->assertEquals(1, $m->numCalls('__call', 'testcall1', array()));
        $this->assertEquals(1, $m->numCalls('__call', 'testcall2', array($arg)));
        $this->assertEquals(1, $m->numCalls('__call', 'testcall4', array($arg)));
        $this->assertEquals(0, $m->numCalls('__call', 'publicGetPublicParam', array()));
        $this->assertEquals(3, $m->numCalls('__call'));
    }
    // }}}
    // {{{ + testGetPublicPropertyTwoInstances()
    /**
     * test retrieving public property after mocking same object twice
     *
     */
    public function testGetPublicPropertyTwoInstances() {
        $publicParam = $_TAG->dummynoparams->publicParam;
        $gm = new globalmock('dummynoparams');
        $gn = new globalmock('dummynoparams');
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals($publicParam, $m->publicParam, 'Did not get public parameter');
    }
    // }}}
    // {{{ + testGetPublicPropertyFromPublicFunction()
    /**
     * test retrieving public property from public function
     *
     */
    public function testGetPublicPropertyFromPublicFunction() {
        $publicParam = $_TAG->dummynoparams->publicGetPublicParam();
        $gm = new globalmock('dummynoparams');
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals($publicParam, $m->publicGetPublicParam(), 'Did not get public parameter');
    }
    // }}}
    // {{{ + testGetPrivatePropertyFromPublicFunction()
    /**
     * test retrieving private property from public function 
     *
     */
    public function testGetPrivatePropertyFromPublicFunction() {
        $publicParam = $_TAG->dummynoparams->publicGetPrivateParam();
        $gm = new globalmock('dummynoparams');
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals($publicParam, $m->publicGetPrivateParam(), 'Did not get private parameter from public function');
    }
    // }}}
    // {{{ + testGetPrivatePropertyFromPublicFunctionTwoParams()
    /**
     * test retrieving public property from public function on global with two parameters, also tests global with two parameters
     *
     */
    public function testGetPrivatePropertyFromPublicFunctionTwoParams() {
        $privateParam2 = $_TAG->dummyparams2['a']['b']->publicGetPrivateParam2();
        $gm = new globalmock('dummyparams2', array('a', 'b'));
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals($privateParam2, $m->publicGetPrivateParam2(), 'Did not get private parameter 2 from public function on two param global mock');
    }
    // }}}
    // {{{ + testOverridePublicProperty()
    /**
     * test overriding a public property
     *
     */
    public function testOverridePublicProperty() {
        $oldvalue = $_TAG->dummynoparams->publicParam;
        $newvalue = 'newvalue' . rand();
        $gm = new globalmock('dummynoparams');
        $gm->override_property('publicParam', $newvalue);
        $m = $gm->mock();
        $this->assertEquals($newvalue, $m->publicParam, 'Did not get public param after override property in mock');
        $this->assertEquals($newvalue, $_TAG->dummynoparams->publicParam, 'Did not get public param after override property in mocked global');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->publicParam, 'Did not get original public param from public param after override after unmocking');
    }
    // }}}
    // {{{ + testOverridePrivateProperty()
    /**
     * test overriding a private property
     *
     */
    public function testOverridePrivateProperty() {
        $oldvalue = $_TAG->dummynoparams->publicGetPrivateParam();
        $newvalue = 'newvalue' . rand();
        $gm = new globalmock('dummynoparams');
        $gm->override_property('_privateParam1', $newvalue);
        $m = $gm->mock();
        $this->assertEquals($newvalue, $m->publicGetPrivateParam(), 'Did not get private param from public function after override method in mock');
        $this->assertEquals($newvalue, $_TAG->dummynoparams->publicGetPrivateParam(), 'Did not get private param from public function after override method in mocked global');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->publicGetPrivateParam(), 'Did not get original private param from public function after override method after unmocking');
    }
    // }}}
    // {{{ + testOverridePublicMethod()
    /**
     * test overriding a public method
     *
     */
    public function testOverridePublicMethod() {
        $oldvalue = $_TAG->dummynoparams->publicFunction();
        $newvalue = 'newvalue';
        $gm = new globalmock('dummynoparams');
        $gm->override_method('publicFunction', function() use ($newvalue) {
            return $newvalue;
        });
        $m = $gm->mock();
        $this->assertEquals($newvalue, $m->publicFunction(), 'Did not get correct return after overriding public method in mock');
        $this->assertEquals($newvalue, $_TAG->dummynoparams->publicFunction(), 'Did not get correct return after overriding public method in mocked global');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->publicFunction(), 'Did not get original return after overriding public method after unmocking');
    }
    // }}}
    // {{{ + testOverridePrivateMethod()
    /**
     * test overriding a private method
     *
     */
    public function testOverridePrivateMethod() {
        $oldvalue = $_TAG->dummynoparams->publicFunctionCallPrivateFunction();
        $newvalue = 'newvalue';
        $gm = new globalmock('dummynoparams');
        $gm->override_method('privateFunction', function() use ($newvalue) {
            return $newvalue;
        });
        $m = $gm->mock();
        $this->assertEquals($newvalue, $m->publicFunctionCallPrivateFunction(), 'Did not get correct return after overriding private method in mock');
        $this->assertEquals($newvalue, $_TAG->dummynoparams->publicFunctionCallPrivateFunction(), 'Did not get correct return after overriding private method in mocked object');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->publicFunctionCallPrivateFunction(), 'Did not get original return after overriding private method after unmocking');
    }
    // }}}
    // {{{ + testCallPublicMethodPassArgumentByReference()
    /**
     * test calling public method with pass by reference parameter
     *
     */
    public function testCallPublicMethodPassArgumentByReference() {
        $array = array();
        $_TAG->dummynoparams->publicFunctionPassArgByReference($array);
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $mockArray = array();
        $m->publicFunctionPassArgByReference($mockArray);
        $this->assertEquals($array, $mockArray, 'Did not get same change after mocking by passing argument by reference');
        $m->unmock();
        $unmockArray = array();
        $_TAG->dummynoparams->publicFunctionPassArgByReference($unmockArray);
        $this->assertEquals($array, $unmockArray, 'Did not get same change after unmocking by passing argument by reference');
    }
    // }}}
    // {{{ + testCallPrivateMethodPassArgumentByReference()
    /**
     * test calling private method with pass by reference parameter
     *
     */
    public function testCallPrivateMethodPassArgumentByReference() {
        $value = $_TAG->dummynoparams->publicFunctionCallPrivateFunctionPassArgByReference();
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $this->assertEquals($value, $m->publicFunctionCallPrivateFunctionPassArgByReference(), 'Did not get original return after mocking by passing argument by reference to private method');
        $m->unmock();
        $this->assertEquals($value, $_TAG->dummynoparams->publicFunctionCallPrivateFunctionPassArgByReference(), 'Did not get original return after unmocking by passing argument by reference to private method');
    }
    // }}}
    // {{{ + testOverridePublicMethodTwoInstances()
    /**
     * test override of public method when mocking same global twice
     *
     */
    public function testOverridePublicMethodTwoInstances() {
        $oldvalue = $_TAG->dummyparams['b']->publicFunction();
        $newvalue = 'newvalue';
        $gm = new globalmock('dummyparams', 'a');
        $gn = new globalmock('dummyparams', 'b');
        $gm->override_method('publicFunction', function() use ($newvalue) {
            return $newvalue;
        });
        $m = $gm->mock();
        $n = $gn->mock();
        $this->assertEquals($newvalue, $m->publicFunction(), 'Did not get correct return after overriding public method via method with two instances in mock');
        $this->assertEquals($newvalue, $_TAG->dummyparams['a']->publicFunction(), 'Did not get correct return after overriding public method via method with two instances in mocked global');
        $this->assertEquals($oldvalue, $n->publicFunction(), 'Did not get correct return after overriding public method via method with two instances in 2nd mock');
        $this->assertEquals($oldvalue, $_TAG->dummyparams['b']->publicFunction(), 'Did not get correct return after overriding public method via method with two instances in2nd  mocked global');
        $m->unmock();
        $n->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummyparams['a']->publicFunction(), 'Did not get original return after overriding public method via method with two instances in 1st global after unmocking');
        $this->assertEquals($oldvalue, $_TAG->dummyparams['b']->publicFunction(), 'Did not get original return after overriding public method via method with two instances in 2nd global after unmocking');
    }
    // }}}
    // {{{ + testOverridePublicMethodTwoDifferentInstances()
    /**
     * test override of public method when mocking two different globals
     *
     */
    public function testOverridePublicMethodTwoDifferentInstances() {
        $oldvalue = $_TAG->dummyparams['a']->publicFunction();
        $oldvalue2 = $_TAG->dummy2noparams->publicFunction();
        $newvalue = 'newvalue';
        $gm = new globalmock('dummyparams', 'a');
        $gn = new globalmock('dummy2noparams');
        $gm->override_method('publicFunction', $newvalue);
        $m = $gm->mock();
        $n = $gn->mock();
        $this->assertEquals($newvalue, $m->publicFunction(), 'Did not get correct return after overriding public method with two instances in mock');
        $this->assertEquals($newvalue, $_TAG->dummyparams['a']->publicFunction(), 'Did not get correct return after overriding public method with two instances in mocked global');
        $this->assertEquals($oldvalue2, $n->publicFunction(), 'Did not get correct return after overriding public method with two instances in 2nd mock');
        $this->assertEquals($oldvalue2, $_TAG->dummy2noparams->publicFunction(), 'Did not get correct return after overriding public method with two instances in2nd  mocked global');
        $m->unmock();
        $n->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummyparams['a']->publicFunction(), 'Did not get original return after overriding public method with two instances in 1st global after unmocking');
        $this->assertEquals($oldvalue2, $_TAG->dummy2noparams->publicFunction(), 'Did not get original return after overriding public method with two instances in 2nd global after unmocking');
    }
    // }}}
    // {{{ + testOverridePublicMethodTwoDifferentInstancesCrossCall()
    /**
     * test overriding a public method on two different global mock instances with cross function call between
     *
     */
    public function testOverridePublicMethodTwoDifferentInstancesCrossCall() {
        $oldvalue = $_TAG->dummyparams['a']->publicFunctionCrossCall();
        $oldvalue2 = $_TAG->dummy2noparams->publicFunctionCrossCall('a');
        $newvalue = 'newvalue';
        $gm = new globalmock('dummyparams', 'a');
        $gn = new globalmock('dummy2noparams');
        $gm->override_method('publicFunctionCrossCall', $newvalue);
        $m = $gm->mock();
        $n = $gn->mock();
        $this->assertEquals($newvalue, $m->publicFunctionCrossCall(), 'Did not get correct return after overriding public method with two instances cross call in mock');
        $this->assertEquals($newvalue, $_TAG->dummyparams['a']->publicFunctionCrossCall(), 'Did not get correct return after overriding public method with two instances cross call in mocked global');
        $this->assertEquals($newvalue, $n->publicFunctionCrossCall('a'), 'Did not get correct return after overriding public method with two instances cross call in 2nd mock');
        $this->assertEquals($newvalue, $_TAG->dummy2noparams->publicFunctionCrossCall('a'), 'Did not get correct return after overriding public method with two instances cross call in 2nd mocked global');
        $m->unmock();
        $n->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummyparams['a']->publicFunctionCrossCall(), 'Did not get original return after overriding public method with two instances cross call in 1st global after unmocking');
        $this->assertEquals($oldvalue2, $_TAG->dummy2noparams->publicFunctionCrossCall('a'), 'Did not get original return after overriding public method with two instances in cross call 2nd global after unmocking');
    }
    // }}}
    // {{{ + testConstTwoDifferentInstances()
    /**
     * test retrieving const from two difference globals
     *
     */
    public function testConstTwoDifferentInstances() {
        $value = $_TAG->dummyparams['a']->getMyConst();
        $value2 = $_TAG->dummy2noparams->getMyConst();
        $gm = new globalmock('dummyparams', 'a');
        $gn = new globalmock('dummy2noparams');
        $m = $gm->mock();
        $n = $gn->mock();
        $this->assertEquals($value, $m->getMyConst(), 'Did not get correct const value in 1st mock');
        $this->assertEquals($value, $_TAG->dummyparams['a']->getMyConst(), 'Did not get correct const value in 1st mocked global');
        $this->assertEquals($value2, $n->getMyConst(), 'Did not get correct const value in 2nd mock');
        $this->assertEquals($value2, $_TAG->dummy2noparams->getMyConst(), 'Did not get correct const value in 2nd mocked global');
        $m->unmock();
        $n->unmock();
        $this->assertEquals($value, $_TAG->dummyparams['a']->getMyConst(), 'Did not get original const value in 1st global after unmocking');
        $this->assertEquals($value2, $_TAG->dummy2noparams->getMyConst(), 'Did not get original const value in 2nd global after unmocking');
    }
    // }}}
    // {{{ + testOverrideConst()
    /**
     * test overriding a const
     *
     */
    public function testOverrideConst() {
        $oldvalue = $_TAG->dummynoparams->getMyConst();
        $newvalue = 'newvalue' . rand();
        $gm = new globalmock('dummynoparams');
        $gm->override_constant('MY_CONST', $newvalue);
        $m = $gm->mock();
        $this->assertEquals($newvalue, $m->getMyConst(), 'Did not get constant after override constant in mock');
        $this->assertEquals($newvalue, $_TAG->dummynoparams->getMyConst(), 'Did not get constant after override constant in mocked global');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->getMyConst(), 'Did not get original constant after override constant after unmocking');
    }
    // }}}
    // {{{ + testPublicStaticMethod()
    /**
     * test calling public static method
     *
     */
    public function testPublicStaticMethod() {
        $value = $_TAG->dummyparams['a']->getPublicStaticFunction();
        $gm = new globalmock('dummyparams', 'a');
        $m = $gm->mock();
        $this->assertEquals($value, $m->getPublicStaticFunction(), 'Did not get correct public static function value in mock');
        $m->unmock();
        $this->assertEquals($value, $_TAG->dummyparams['a']->getPublicStaticFunction(), 'Did not get original public static function value in global value after unmocking');
    }
    // }}}
    // {{{ + testSetMethod()
    /**
     * test __set
     *
     */
    public function testSetMethod() {
        $oldvalue = 'oldtestset' . rand();
        $newvalue = 'newtestset' . rand();
        $_TAG->dummynoparams->testset1 = $oldvalue;
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $m->testset1 = $newvalue;
        $this->assertEquals($newvalue, $m->testset1, 'Did not get correct value after set in mock');
        $this->assertEquals($newvalue, $_TAG->dummynoparams->testset1, 'Did not get correct value after set in mocked global');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testset1, 'Did not get original value after set in global after unmocking');
    }
    // }}}
    // {{{ + testNestedGet()
    /**
     * test __get which calls itself twice from one call
     *
     */
    public function testNestedGet() {
        $oldvalue = $_TAG->dummynoparams->assign;
        $oldvalue2 = 'testget2';
        $oldvalue3 = 'testget3';
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $this->assertEquals($oldvalue, $m->assign, 'Did not get correct value after mocking in mock');
        $this->assertEquals($oldvalue2, $m->testget2, 'Did not get correct value after mocking in mock');
        $this->assertEquals($m->assign, $m->testget3, 'Did not get correct value after mocking in mock');
        $this->assertEquals($oldvalue3, $m->testget3, 'Did not get correct value after mocking in mock');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->assign, 'Did not get correct value after unmocking in global');
        $this->assertEquals($oldvalue2, $_TAG->dummynoparams->testget2, 'Did not get correct value after unmocking in global');
        $this->assertEquals($_TAG->dummynoparams->assign, $_TAG->dummynoparams->testget3, 'Did not get correct value after unmocking in global');
        $this->assertEquals($oldvalue3, $_TAG->dummynoparams->testget3, 'Did not get correct value after unmocking in global');
    }
    // }}}
    // {{{ + testCallMethodNoParams()
    /**
     * test __call with no parameters
     *
     */
    public function testCallMethodNoParams() {
        $oldvalue = $_TAG->dummynoparams->testcall1();
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $this->assertEquals($oldvalue, $m->testcall1(), 'Did not get correct value after mocking in mock');
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall1(), 'Did not get correct value after mocking in mock');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall1(), 'Did not get correct value after unmocking in global');
    }
    // }}}
    // {{{ + testCallMethodParams()
    /**
     * test __call with parameters
     *
     */
    public function testCallMethodParams() {
        $param = 36;
        $oldvalue = $_TAG->dummynoparams->testcall2($param);
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $this->assertEquals($oldvalue, $m->testcall2($param), 'Did not get correct value after mocking in mock');
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall2($param), 'Did not get correct value after mocking in mock');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall2($param), 'Did not get correct value after unmocking in global');
    }
    // }}}
    // {{{ + testCallMethodNoParamsNested()
    /**
     * test __call with no parameters and nested calls
     *
     */
    public function testCallMethodNoParamsNested() {
        $oldvalue = $_TAG->dummynoparams->testcall3();
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $this->assertEquals($oldvalue, $m->testcall3(), 'Did not get correct value after mocking in mock');
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall3(), 'Did not get correct value after mocking in mock');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall3(), 'Did not get correct value after unmocking in global');
    }
    // }}}
    // {{{ + testCallMethodParamsNested()
    /**
     * test __call with parameters and nested calls
     *
     */
    public function testCallMethodParamsNested() {
        $param = 36;
        $oldvalue = $_TAG->dummynoparams->testcall4($param);
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $this->assertEquals($oldvalue, $m->testcall4($param), 'Did not get correct value after mocking in mock');
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall4($param), 'Did not get correct value after mocking in mock');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall4($param), 'Did not get correct value after unmocking in global');
    }
    // }}}
    // {{{ + testCallMethodNoParamsNestedPrivateMethod()
    /**
     * test __call with no parameters and a nested call to a private method
     *
     */
    public function testCallMethodNoParamsNestedPrivateMethod() {
        $oldvalue = $_TAG->dummynoparams->testcall5();
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $this->assertEquals($oldvalue, $m->testcall5(), 'Did not get correct value after mocking in mock');
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall5(), 'Did not get correct value after mocking in mock');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall5(), 'Did not get correct value after unmocking in global');
    }
    // }}}
    // {{{ + testCallMethodNoParamsNestedPrivateMethodNested()
    /**
     * test __call with no parameters and a nested call to a private method which then calls a private method
     *
     */
    public function testCallMethodNoParamsNestedPrivateMethodNested() {
        $oldvalue = $_TAG->dummynoparams->testcall6();
        $gm = new globalmock('dummynoparams');
        $m = $gm->mock();
        $this->assertEquals($oldvalue, $m->testcall6(), 'Did not get correct value after mocking in mock');
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall6(), 'Did not get correct value after mocking in mock');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummynoparams->testcall6(), 'Did not get correct value after unmocking in global');
    }
    // }}}
    // {{{ + testOverrideMethodWhenCallImplemented()
    /**
     * test overriding a method which does not exist with __call implemented
     *
     */
    public function testOverrideMethodWhenCallImplemented() {
        $overwrittenValue = 'overwritten';
        $__CallValue = 'unknowncall';
        $gm = new globalmock('dummynoparams');
        $gm->override_method('newMethod', function() use ($overwrittenValue) {
            return $overwrittenValue;
        });
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals($overwrittenValue,$m->newMethod(),'Did not call the overwritten method');
        $this->assertEquals($__CallValue,$m->newMethod2(),'did not call the __call defined in the mocked class');
    }
    // }}}
    // {{{ + testOverrideMethodWhenCallNotImplemented()
    /**
     * test overriding a method which does not exist without __call implemented
     *
     */
    public function testOverrideMethodWhenCallNotImplemented() {
        $overwrittenValue = 'overwritten';
        $gm = new globalmock('dummy2noparams');
        $gm->override_method('newMethod', function() use ($overwrittenValue) {
            return $overwrittenValue;
        });
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals($overwrittenValue,$m->newMethod(),'Did not call the overwritten method');
        $this->assertEquals(dummy2::MY_CONST,$m->getMyConst(),'did not call the __call defined in globalmock');
    }
    // }}}
    // {{{ + testMethodThatOverridesParentMethod()
    /**
     * test overriding a public parent method
     *
     */
    public function testMethodThatOverridesParentMethod() {
        $childValue = $_TAG->dummychild->publicFunction();
        $gm = new globalmock('dummychild');
        $m = $gm->mock();
        $this->assertEquals($childValue,$m->publicFunction(),'Did not call the overriding method in mock');
        $this->assertEquals($childValue,$_TAG->dummychild->publicFunction(),'Did not call the overriding method in mocking global');
        $m->unmock();
        $this->assertEquals($childValue,$_TAG->dummychild->publicFunction(),'Did not call the overriding method after unmocking');
    }
    // }}}
    // {{{ + testOverrideMethodThatOverridesParentMethod()
    /**
     * test overriding a method which already overrides a parent method
     *
     */
    public function testOverrideMethodThatOverridesParentMethod() {
        $oldvalue = $_TAG->dummychild->publicFunction();
        $overriddenValue = 'overridden';
        $gm = new globalmock('dummychild');
        $gm->override_method('publicFunction', function() use ($overriddenValue) {
            return $overriddenValue;
        });
        $m = $gm->mock();
        $this->assertEquals($overriddenValue,$m->publicFunction(),'Did not call the overriding method in mock');
        $this->assertEquals($overriddenValue,$_TAG->dummychild->publicFunction(),'Did not call the overriding method in mocking global');
        $m->unmock();
        $this->assertEquals($oldvalue, $_TAG->dummychild->publicFunction(), 'Did not get correct value after unmocking');
    }
    // }}}
    // {{{ + testOverrideMethodOnlyInParent()
    /**
     * test overriding a private parent method
     *
     */
    public function testOverrideMethodOnlyInParent() {
        $oldvalue = $_TAG->dummychild->publicFunctionCallPrivateFunction();
        $overriddenValue = 'overridden';
        $gm = new globalmock('dummychild');
        $gm->override_method('privateFunction', function() use ($overriddenValue) {
            return $overriddenValue;
        });
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals($oldvalue,$m->publicFunctionCallPrivateFunction(),'Did not ignore the overriding method in mock');
        $this->assertEquals($oldvalue,$_TAG->dummychild->publicFunctionCallPrivateFunction(),'Did not ignore the overriding method in mocking global');
    }
    // }}}
    // {{{ + testTypeHintingArguments()
    /**
     * test type hinting
     *
     */    
    public function testTypeHintingArguments() {
        $error_handler = new testErrorHandler();
        $error_handler->suppressError('test/class/dummy.php', E_RECOVERABLE_ERROR);
        
        $dummyA = $_TAG->dummyparams['a'];
        $oldvalue = $_TAG->dummyparams['b']->typeHintArg($dummyA);
        $gm = new globalmock('dummyparams', 'a');
        $this->_mocks[] = $m = $gm->mock();
        $this->assertEquals($oldvalue,$_TAG->dummyparams['b']->typeHintArg($m),'Type Hinting threw an exception on mock.');
        $this->assertEquals(0,$error_handler->numErrorsSuppressed(),'Type Hinting Error suppressed!');
    }
    // }}}
}
// }}}
