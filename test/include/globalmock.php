<?php
/**
 * This contains a way to mock objects using global system (i.e. $_TAG->dummy['a']).
 *
 * This class is a framework for dynamically creating a new class, on the fly, that mimics the class
 * being mocked, with a few modifications and whatever overrides the user desires.  Once the class is
 * created, a new instance of this class is created that mirrors the object being mocked, and the mock
 * object is then placed in the appropriate place in the global loader system, where calls to the global
 * loader for the object being mocked instead return the mock object replacement.
 *
 * Sample usage:
 *
 * print $_TAG->dummy['a']->publicGetPublicParam() . "\n"; // calls publicGetPublicParam from dummy
 * $globalmock = new globalmock( "dummy", "a",false, array ( "publicGetPublicParam" => function () { return 6; } ) ); // override publicGetPublicParam()
 * $mock_dummy = $globalmock->mock(); // mock $_TAG->dummy['a']
 * print $_TAG->dummy['a']->publicGetPublicParam() . "\n"; // calls new mocked publicGetPublicParam function
 * $globalmock->unmock(); // unmock $_TAG->dummy['a']
 * print $_TAG->dummy['a']->count() . "\n"; // calls original publicGetPublicParam from dummy
 * 
 * @package test
 * @subpackage include
 * @author diego matute <dmatute@tagged.com>
 * @author erik johannessen <ejohannessen@tagged.com>
 */
class globalmock {

    // {{{ - $_className
    /**
     * Name of class being mocked.
     */
    protected $_className = null;
    // }}}

    // {{{ - $_configName
    /**
     * Name of global i.e. what comes after gld_ in config or $_TAG->...[].
     */
    protected $_configName = null;
    // }}}

    // {{{ - $_params
    /**
     * Array of index access on global, i.e. $_TAG->something[first_index][second_index], $_params = array( first_index, second_index )
     */
    protected $_params = array();
    // }}}

    // {{{ - $_mockLoader
    /**
     * Whether or not we mock this class' loader.
     */
    protected $_mockLoader = true;
    // }}}

    // {{{ - $_lastParam
    /**
     * Last param in _params
     */
    protected $_lastParam = null;
    // }}}

    // {{{ - $_realObj
    /**
     * Penultimate object in index access on global
     */
    protected $_realObj = null;
    // }}}

    // {{{ - $_lastObj
    /**
     * Last object in index access on global (this is the actual object that would normally be accessed)
     */
    protected $_lastObj = null;
    // }}}

    // {{{ - $_constants
    /**
     * Constants to add to the new mock class.
     */

    protected $_constants = array();
    // }}}

    // {{{ - $_properties
    /**
     * Properties to add to the new mock class.
     */

    protected $_properties = array();
    // }}}

    // {{{ - $_methods
    /**
     * Methods to add to the new mock class.
     */

    protected $_methods = array();
    // }}}

    // {{{ - $_constantOverrides
    /**
     * All constant override definitions.
     */
    protected $_constantOverrides = array();
    // }}}

    // {{{ - $_propertyOverrides
    /**
     * All property override definitions.
     */
    protected $_propertyOverrides = array();
    // }}}

    // {{{ - $_methodOverrides
    /**
     * All methods override definitions.
     */
    protected $_methodOverrides = array();
    // }}}

    // CONSTRUCTOR
    // {{{ + __construct($configNames, $params, $mockLoader = true)
    /**
     * @param $configName
     * @param $params
     * @param $mockLoader
     * 
     * Save the arguments, put off the rest of the work until later
     * (if we have already mocked this class before with the exact
     * same overrides, we can avoid doing all that work).
     * 
     * Do make sure that a config array exists for $configName, however.
     */
    public function __construct($configName, $params = array(), $mockLoader = true) {
        $this->_configName = $configName;

        $config = $this->_getConfig();
        if (!$config) {
            trigger_error("No global config found for $configName.", E_USER_ERROR);
        }
        $construct = $config['construct'];
        $this->_className = $construct[0];

        $this->_params = is_array($params) ? $params : array($params);

        $this->_mockLoader = $mockLoader;
    }
    // }}}

    // {{{ + override_constant($constantName, $value)
    /*
     * @param $constantName
     * @param $value
     * 
     * Override a class constant.
     * 
     * var_export() with the second argument = true will return a string
     * that, when eval'd, will evaluate to $value.
     */
    public function override_constant($constantName, $value) {
        $this->_constantOverrides[$constantName] = var_export($value, true);
        return $this;
    }
    // }}}

    // {{{ + override_property($propertyName, $value)
    /*
     * @param $propertyName
     * @param $value
     * 
     * Override a property.
     * 
     * Save the value of $value, add it to the mock object AFTER that object
     * is instantiated (the mock object's constructor might set the value of
     * $propertyName, which we want to override).
     */
    public function override_property($propertyName, $value) {
        if (property_exists('mocktemplate', $propertyName)) {
            trigger_error("Name collision overriding property $propertyName in class $this->_className and mocktemplate.",  E_USER_ERROR);
        }

        $this->_propertyOverrides[$propertyName] = $value;
        return $this;
    }
    // }}}

    // {{{ + override_method($methodName, $value)
    /*
     * @param $methodName
     * @param $value
     * 
     * Override a method.
     * 
     * Save strings that will evaluate to the parameters and body of $closure.
     * Add a call to $this->_addCount() to the beginning of the method body.
     * Will turn $value into a closure if it is not already callable.
     */
    public function override_method($methodName, $value = null) {
        if (method_exists('mocktemplate', $methodName)) {
            trigger_error("Name collision overriding method $methodName in class $this->_className and mocktemplate.",  E_USER_ERROR);
        }

        if (is_callable($value)) {
            $closure = $value;
        } else {
            $closure = function() use ($value) { return $value; };
        }
        $function = new ReflectionFunction($closure);

        $body = $this->_getBody($function);
        $body = "\n\$this->_addCount('$methodName', func_get_args());\n$body"; 
            
        $methodParts = array(
            'parameters'    => $this->_getParameters($function->getParameters()),
            'body'          => $body
        );
        $this->_methodOverrides[$methodName] = $methodParts;

        return $this;
    }
    // }}}

    // {{{ + mock($copyPropertiesFromCache)
    /*
     * @param $copyPropertiesFromCache - Boolean.  Do we copy properties from the object previously in cache?
     * 
     * mock() does essentially four sequential tasks, each handled by a helper method.
     * 1. Get the name of our new mock class.
     * 2. Construct an instance of our mock class
     * 3. Add the mock object into the global loader.
     * 4. Load that instance up with property overrides and a reference to $this (so it can
     * call back to this object during unmocking), as well as (optionally) copying
     * properties from the real object (previously found in cache) that this object is mocking.
     */
    public function mock($copyPropertiesFromCache = false) {
        $mockClassName = $this->_getMockClassName();

        $mockObject = $this->_constructMockObject($mockClassName);

        $this->_addMockToGlobalLoader($mockObject);

        // set all property overrides (must do this AFTER $mockObject has been constructed)
        $this->_setPropertiesAndOverrides($mockObject, $copyPropertiesFromCache);

        return $mockObject;
    }
    // }}}

    // {{{ + unmock()
    /*
     * Restores the original object back to its place in the global loader.
     */
    public function unmock() {
        $this->_restoreOriginalToGlobalLoader();
    }
    // }}}

    // {{{ - _getMockClassName()
    /*
     * Creates a new mock class definition as a string, eval's that string, and returns the
     * new mock class name.
     * 
     * For a given class name $className, our 'mock' class name will be created by appending
     * 'Mock' and a hash of the $className and serialized overrides.
     * Example:  tgif_user_invites -> tgif_user_invitesMock347293801
     * 
     * As an optimization, we save the mock class names in the hash $mock_classes, a static
     * variable attached to this method.  The hash value is created by taking an md5 of the
     * name of the class being mocked, together with the serialization of any overrides for
     * this class.  If the same class has already been mocked with the same overrides, there's
     * no need to define a new class, so we just reuse the old class name, which sticks around
     * in the PHP environment because classes/variables defined by an eval() have a scope
     * lasting the entire length of the process.
     */
    protected function _getMockClassName() {
        static $mock_classes;
        $hash = md5($this->_className . serialize($this->_constantOverrides) . serialize($this->_methodOverrides));
        if (!isset($mock_classes)) {
            $mock_classes = array();
        }
        if (array_key_exists($hash, $mock_classes)) {
            $mockClassName = $mock_classes[$hash];
        } else {
            $mockClassName = $this->_className . 'Mock' . $hash;
            $mock_classes[$hash] = $mockClassName;
            $this->_prepareMock();
            $code = $this->_assembleCode($mockClassName);
            // replace all 'new' references to $this->_className with our new $mockClassName
            $code = str_replace("new $this->_className(", "new $mockClassName(", $code);
            $code = str_replace("new $this->_className;", "new $mockClassName;", $code);
            // replace all static calls to $this->_className:: with our new $mockClassName
            $code = str_replace("$this->_className::", "$mockClassName::", $code);
            eval($code);
        }
        return $mockClassName;
    }
    // }}}

    // {{{ - _prepareMock()
    /*
     * Uses the reflection objects for both the object being mocked and mocktemplate, as well
     * as the constant and method overrides to collect all of the constant, property and
     * method definitions that will make up this mock class definition.
     * 
     * Note that we don't bother trying to get constants from mocktemplate (because it doesn't
     * have any), and we don't add in property overrides just yet (we wait to add them to an
     * instantiated object - if we add property overrides to the class defintion, those
     * overridden values might themselves be overwritten by the constructor).
     */
    protected function _prepareMock() {
        // get reflections for this class and for mocktemplate
        $reflect = new ReflectionClass($this->_className);
        $reflectTemplate = new ReflectionClass('mocktemplate');

        // collect all constants        
        foreach ($reflect->getConstants() as $name => $value) {
            $this->_constants[$name] = var_export($value, true);
        }

        // now add in overrides
        foreach ($this->_constantOverrides as $name => $value) {
            $this->_constants[$name] = $value;
        }

        // add properties from mocktemplate
        $defaultTemplateProperties = $reflectTemplate->getDefaultProperties();
        foreach ($reflectTemplate->getProperties() as $prop) {
            $propParts = array(
                'signature' => $this->_getPropertySignature($prop),
                'value'     => array_key_exists($prop->name, $defaultTemplateProperties) ? var_export($defaultTemplateProperties[$prop->name], true) : null
            );

            $this->_properties[$prop->name] = $propParts;
        }

        // collect all properties
        $defaultProperties = $reflect->getDefaultProperties();
        foreach ($reflect->getProperties() as $prop) {
            if ($prop->getDeclaringClass()->name != $this->_className) {
                // ignore properties not defined in the class being mocked (inherited from parent classes)
                continue;
            }
            if (property_exists('mocktemplate', $prop->name)) {
                trigger_error("Name collision on property $prop->name in class $this->_className and mocktemplate.",  E_USER_ERROR);
            }
            
            $propParts = array(
                'signature' => $this->_getPropertySignature($prop),
                'value'     => array_key_exists($prop->name, $defaultProperties) ? var_export($defaultProperties[$prop->name], true) : null
            );

            $this->_properties[$prop->name] = $propParts;
        }

        // add methods from mocktemplate
        foreach ($reflectTemplate->getMethods() as $method) {
            $methodParts = array(
                'signature'     => $this->_getMethodSignature($method),
                'parameters'    => $this->_getParameters($method->getParameters()),
                'body'          => $this->_getBody($method)
            );
            $this->_methods[$method->name] = $methodParts;
        }

        // collect all methods
        foreach ($reflect->getMethods() as $method) {
            if ($method->getDeclaringClass()->name != $this->_className) {
                // ignore methods not defined in the class being mocked (inherited from parent classes)
                continue;
            }
            if (method_exists('mocktemplate', $method->name)) {
                trigger_error("Name collision on method $method->name in class $this->_className and mocktemplate.",  E_USER_ERROR);
            }

            $body = $this->_getBody($method);
            if ($method->isDestructor()) {
                $body = "\n\$this->_globalMock->unmock();\n$body";
            } elseif (!$method->isStatic()) {
                $body = "\n\$this->_addCount('$method->name', func_get_args());\n$body";
            }

            $methodParts = array(
                'signature'     => $this->_getMethodSignature($method),
                'parameters'    => $this->_getParameters($method->getParameters()),
                'body'          => $body
            );
            $this->_methods[$method->name] = $methodParts;
        }

        // now add in overrides
        foreach ($this->_methodOverrides as $methodName => $methodParts) {
            if (array_key_exists($methodName, $this->_methods)) {
                $this->_methods[$methodName]['parameters'] = $methodParts['parameters'];
                $this->_methods[$methodName]['body'] = $methodParts['body'];
            } else {
                // must be a new method, assume it is public and non-static
                $methodParts['signature'] = "public function $methodName";
                $this->_methods[$methodName] = $methodParts;
            }
        }
    }
    // }}}

    // {{{ - _getPropertySignature($prop)
    /*
     * @param $prop - a ReflectionProperty object
     * 
     * Returns a string that will evaluate to the name/properties of this property, i.e.
     * "public $myProperty" or "private static $_privateProp"
     */
    protected function _getPropertySignature($prop) {
        $sig = $this->_getSignature($prop);
        return $sig . '$' . $prop->name;
    }
    // }}}

    // {{{ - _getMethodSignature($method)
    /*
     * @param $method - a ReflectionMethod object
     * 
     * Returns a string that will evaluate to the name/properties of this method, i.e.
     * "public static function my_static_method" or
     * "protected function &_myMethodReturnsReference"
     */
    protected function _getMethodSignature($method) {
        $sig = $this->_getSignature($method);
        if ($method->isFinal()) {
            $sig .= 'final ';
        }
        $sig .= 'function ';
        if ($method->returnsReference()) {
            $sig .= '&';
        }
        return $sig . $method->name;
    }
    // }}}

    // {{{ - _getSignature($reflect)
    /*
     * @param $reflect - either a ReflectionProperty or a ReflectionMethod object
     * 
     * Returns a string representing whether this reflection object is private,
     * protected or public, and if it is static or not.
     */
    protected function _getSignature($reflect) {
        $sig = '';
        if ($reflect->isPrivate()) {
            $sig .= 'private ';
        } elseif ($reflect->isProtected()) {
            $sig .= 'protected ';
        } elseif ($reflect->isPublic()) {
            $sig .= 'public ';
        }
        if ($reflect->isStatic()) {
            $sig .= 'static ';
        }
        return $sig;
    }
    // }}}

    // {{{ - _getParameters($params)
    /*
     * @param $params - an array of ReflectionParameter objects
     * 
     * Returns a string representing a list of parameters (as for a method or a closure).
     * Adds '&' for parameters passed by reference, and " = {defaultValue}" for properties
     * that have default values.  var_export() with the second argument as 'true' will
     * return a string that evaluates to that value - essentially, a reverse-evaluator.
     * 
     * An example return value:
     * "$arg1, &$arg2, $arg3 = array(true, false)"
     * 3 parameters, the second passed by reference, the third with a default value
     */
    protected function _getParameters($params) {
        $strings = array();
        foreach($params as $param) {
            $string = '';
            if ($param->isPassedByReference()) {
                $string .= '&';
            }
            $string .= '$' . $param->name;
            if ($param->isDefaultValueAvailable()) {
                $defaultValue = $param->getDefaultValue();
                $string .= ' = ' . var_export($defaultValue, true);
            }
            $strings[] = $string;
        }
        return implode(', ', $strings);
    }
    // }}}

    // {{{ - _getBody($function)
    /*
     * @param $function - a ReflectionMethod or a ReflectionFunction object.
     * 
     * Returns a string representing the body of this method or function.  Goes back
     * to the original source file to grab the original source code.  Returns everything
     * between the opening and closing braces.
     */
    protected function _getBody($function) {
        $filename = $function->getFileName();
        if (file_exists($filename)) {
            $file   = file($filename);
            $body   = '';
            $start  = $function->getStartLine() - 1;
            $end    = $function->getEndLine() - 1;

            for ($line = $start; $line <= $end; $line++) {
                $body .= $file[$line];
            }

            // Only keep the code defining that function
            $begin = strpos($body, '{');
            $end = strrpos($body, '}');
            $head = substr($body, 0, $begin);
            $body = substr($body, $begin + 1, $end - $begin - 1);
            
            // get used variables, if there are any
            $usedVars = $this->_getUsedVariables($function, $head);
            $body = $usedVars . $body;
        } else {
            trigger_error("Could not find file named $filename to get body of function $function->name.",  E_USER_WARNING);
            $body = '';
        }
        return $body;
    }
    // }}}

    // {{{ - _getUsedVariables($function, $code)
    /*
     * @param $function - a ReflectionFunction object
     * @param $code - a string representing the code for this function, up until the opening brace
     * 
     * Get the variables used by the closure that this ReflectionFunction represents.
     * Returns a string that represents declaring these variables, with their imported values, at
     * the beginning of this function.  For example, if this was the creation of the original closure:
     * 
     *  $fakeLevels = array(
     *      1 => array('value' => 0),
     *      2 => array('value' => 2000, 'items' => array( array('id' => 1, 'count' => 1) )),
     *      3 => array('value' => 4000),
     *  );
     *  $this->_levelsStub = new globalmock('tgif_apps_farm_levels', 'farmLevels');
     *  $this->_levelsStub->override_method('getRequirements', function($level) use ($fakeLevels) { return $fakeLevels[$level]; } );
     *  $this->_levelsStub = $this->_levelsStub->mock();
     *  
     * the return value of this function would be:
     * 
     *  $fakeLevels = array(
     *      1 => array('value' => 0),
     *      2 => array('value' => 2000, 'items' => array( array('id' => 1, 'count' => 1) )),
     *      3 => array('value' => 4000),
     *  );
     *  
     * and the new class tgif_apps_farm_levelsMockXXXXXXXXX (the X's represent some sequence of digits)
     * would contain the following function:
     *  
     *  public function getRequirements($level) {
     *      $this->_addCount('getRequirements', func_get_args());
     *      $fakeLevels = array(
     *          1 => array('value' => 0),
     *          2 => array('value' => 2000, 'items' => array( array('id' => 1, 'count' => 1) )),
     *          3 => array('value' => 4000),
     *      );
     *      return $fakeLevels[$level];
     *  }
     * 
     * Unfortunately, one feature of PHP that is not supported is importing used variables by
     * reference. Because of how mock classes are eval'd, there's no way to import a variable's
     * original reference scope. However, in a testing framework, I don't think that this is too
     * great a loss.
     */
    protected function _getUsedVariables($function, $code) {
        // find the 'use' construct
        $functionIndex = stripos($code, 'function');
        $parenIndex = strpos($code, ')', $functionIndex);
        $useIndex = stripos($code, 'use', $parenIndex);
        if ($useIndex === false) {
            return "";
        }

        // get the static variables for this function
        $staticVars = $function->getStaticVariables();

        // get the variable names used
        $begin = strpos($code, '(', $useIndex) + 1;
        $end = strpos($code, ')', $begin);
        $vars = explode(',', substr($code, $begin, $end - $begin));

        // create the strings for each used variable
        $usedVars = "";
        foreach ($vars as $var) {
            $ampIndex = strpos($var, '&');
            if ($ampIndex !== false) {
                $var = substr($var, $ampIndex + 1);
                trigger_error("Importing variable $var into overriding function by reference is not supported; $var will be imported by value instead.",  E_USER_WARNING);
            }
            $name = trim($var, ' $');
            $value = var_export($staticVars[$name], true);
            $usedVars .= "$var = $value;\n";
        }
        return $usedVars;
    }
    // }}}

    // {{{ - _assembleCode($mockClassName)
    /*
     * @param $mockClassName - a string representing the name for this new mock class
     * 
     * Returns a string that we can eval() to create a definition of our new mock class.
     * Collects an arrays for each of constants, properties and methods, puts them
     * together with "class $className extends $this->_className { // stuff }
     */
    protected function _assembleCode($mockClassName) {
        $constants = "";
        foreach ($this->_constants as $name => $value) {
            $constants .= "    const $name = $value;\n";
        }
        $properties = "";
        foreach ($this->_properties as $prop) {
            $sig = $prop['signature'];
            $value = $prop['value'];
            $properties .= "    $sig = $value;\n";
        }
        $methods = "";
        foreach ($this->_methods as $method) {
            $sig = $method['signature'];
            $params = $method['parameters'];
            $body = $method['body'];
            $methods .= "    $sig($params) { $body }\n";
        }
        return "class $mockClassName extends $this->_className { $constants $properties $methods }";
    }
    // }}}

    // {{{ - _constructMockObject($mockClassName)
    /*
     * @param $mockClassName - a string representing our new mock class name
     * (which has already been eval'd)
     * 
     * Simulates what the global loader will normally do to create a new object.
     */
    protected function _constructMockObject($mockClassName) {
        $config = $this->_getConfig();
        $construct = $config['construct'];

        $params = $this->_params;
        if (isset($config['ids']) && is_array($config['ids'])) {
            $params = array_merge($params, $config['ids']);
        }
        $numParams = isset($config['params']) ? $config['params'] : count($params);
        if (count($params) < $numParams) {
            trigger_error("Not enough arguments supplied to instantiate $mockClassName.",  E_USER_ERROR);
        }

        if (count($construct) > 1) {
            $constructMethod = $construct[1];
            $mockObject = call_user_func_array(array($mockClassName, $constructMethod), $params);
        } elseif ($numParams == 0) {
            $mockObject = new $mockClassName();
        } elseif ($numParams == 1) {
            $mockObject = new $mockClassName($params[0]);
        } else {
            $mockObject = new $mockClassName($params);
        }
        if ($this->_mockLoader && isset($config['loaderLoader'])) {
            $loaderMethod = $config['loaderLoader'];
            $mockObject->$loaderMethod(new loadermock());
        }

        return $mockObject;
    }
    // }}}

    // {{{ - _setPropertiesAndOverrides($mockObject, $copyPropertiesFromCache)
    /*
     * @param $mockObject - An instance of our new mock class.
     * @param $copyPropertiesFromCache - Boolean.  Do we copy properties from the object previously in cache?
     * 
     * Sets the properties for our new mock object, based on the object previously in cache,
     * as well as any property overrides that have been set.
     */
    protected function _setPropertiesAndOverrides($mockObject, $copyPropertiesFromCache) {
        if ($copyPropertiesFromCache) {
            // in order to get access to the private variables inside the mocked object,
            // we temporarily create a ____get() method to get access
            if ( method_exists($this->_className, '____get') ) {
                trigger_error("Method ____get exists in class $this->_className.",  E_USER_ERROR);
                return false;
            }
            runkit_method_add($this->_className, '____get', '$key', 'return $this->$key;');

            // add all properties to mocking class, trapping all references to params and storing results only in mock
            $reflect = new ReflectionObject($this->_lastObj);
            foreach ($reflect->getProperties() as $prop) {
                if (!$prop->isStatic()) $mockObject->____set($prop->name, $this->_lastObj->____get($prop->name));
            }

            runkit_method_remove($this->_className, '____get');
        }

        // set all property overrides (must do this AFTER $mockObject has been constructed)
        foreach ($this->_propertyOverrides as $propName => $value) {
            $mockObject->____set($propName, $value);
        }
        // set a reference to $this for the unmock() callback
        $mockObject->____set('_globalMock', $this);
        // reset _counts() array, just in case
        $mockObject->____set('_counts', array());
    }
    // }}}

    // {{{ - _addMockToGlobalLoader($mockObject)
    /**
     * @param $mockObject - A instance of our new mock class.
     * 
     * This will make all references to $_TAG->$configName{[$params]} reference the given $mockObject.
     */
    protected function _addMockToGlobalLoader($mockObject) {
        // get last param in param array
        $this->_lastParam = array_pop( $this->_params );

        // save reference to $_TAG->class
        // this will be the final global object in the case of 0 params
        $globalObj = $this->_getGlobalObj();
        $this->_realObj = $globalObj->{$this->_configName};
        if( !is_null($this->_lastParam) ) {
            // since there are params traverse until penultimate reference (type tgif_global_collection)
            foreach( $this->_params as $param ) {
                $this->_realObj = $this->_realObj->offsetGet($param);
            }

            // get last object in param chain, this is actual loaded object that will be mocked
            $this->_lastObj = $this->_realObj->offsetGet($this->_lastParam);

            // check that this object isn't already mocked!
            // only mock objects will have an unmock() method
            if( method_exists($this->_lastObj, 'unmock') ) {
                trigger_error("Already mocked! in class $this->_className.",  E_USER_ERROR);
                return false;
            }

            // set reference in tgif_global_collection to this to trap all calls and mock
            $this->_realObj->offsetSet($this->_lastParam, $mockObject);
        } else {
            // no traversing needed since there are no params

            // save actualy real object
            $this->_lastObj = $this->_realObj;

            // check that this object isn't already mocked!
            // only mock objects will have an unmock() method
            if( method_exists($this->_lastObj, 'unmock') ) {
                trigger_error("Already mocked! in class $this->_className.",  E_USER_ERROR);
                return false;
            }

            // since there are no params set reference to this
            $globalObj->{$this->_configName} = $mockObject;
        }
    }
    // }}}

    // {{{ - _restoreOriginalToGlobalLoader()
    /**
     * This will turn $_TAG->$className{[$params]} back to orignal form without any changes to properties, methods etc..
     */
    protected function _restoreOriginalToGlobalLoader() {
        if( !is_null($this->_lastParam) ) {
            // unset so the cache is clear at the beginning of every test
            $this->_realObj->offsetUnset($this->_lastParam);

            $conf = $this->_getConfig();

            // TODO: standardize on_loader name?
            if ( array_key_exists('loaderLoader', $conf) && property_exists($this->_className, '_loader') ) {
                // kinda ugly hack to get the original loader, cannot rely on the one
                // copied to $this since it may have been overridden with loadermock object
                runkit_method_add($this->_className, '____get', '$key', 'return $this->$key;');
                $loader = $this->_lastObj->____get('_loader');
                runkit_method_remove($this->_className, '____get');
                $loader->setToCache($this->_lastObj);
            }
            array_push( $this->_params, $this->_lastParam );
            $this->_lastParam = null;
        } else {
            $globalObj = $this->_getGlobalObj();
            $globalObj->{$this->_configName} = $this->_lastObj;
        }
    }
    // }}}

    // {{{ - _getConfig()
    /**
     * Gets the config array for this mock class.
     */
    protected function _getConfig() {
        return $_TAG->config('gld_' . $this->_configName);
    }
    // }}}

    // {{{ - _getGlobalObj()
    /**
     * Gets the global object where the mock object will be stored.
     * Except in exceptional cases, this will be an instance of tgif_global.
     */
    protected function _getGlobalObj() {
        return $_TAG;
    }
    // }}}
}
// }}}

/**
 * This class should never be directly instantiated.  Instead, this class exists merely as a template
 * from which to copy constants, properties and methods to new mock object classes.
 *
 * All comments below are written as though those properties/methods belonged to a mock object, and
 * not to a mocktemplate object.
 */
// }}}
class mocktemplate {

    // {{{ - $_counts
    /**
     * Counts for method calls to this object.
     */
    protected $_counts = array();
    // }}}

    // {{{ - $_globalMock
    /**
     * A reference to the globalmock object that created this mock object.
     * Used to call back to that object during unmock().
     */
    protected $_globalMock = null;
    // }}}

    // {{{ + ____set($key, $value)
    /**
     * @param $key
     * @param $value
     * 
     * Used by globalmock to override protected and private properties.
     * Should not be used directly by tests.
     */
    public function ____set($key, $value) {
        return $this->{$key} = $value;
    }
    // }}}

    // {{{ + unmock()
    /**
     * Call back to the unmock() method for the globalmock object that created this mock object.
     * That unmock() method will remove this object from the global loader and replace it
     * with the original object.
     */
    public function unmock() {
        $this->_globalMock->unmock();
    }
    // }}}

    // {{{ + numCalls($method, $args)
    /**
     * @param $method
     * @param $args [as many as you want]
     * 
     * Returns the number of calls to this method on this object.  If $args is given, it will
     * only return the number of calls to this method with the specified $args.
     */
    public function numCalls() {
        $args      = func_get_args();

        // get method and args from args
        $method    = $args[0];
        $real_args = array_slice($args, 1, (func_num_args() - 1));

        // method should never be null
        if( is_null($method) ) {
            return 0;
        }

        // if numCalls('method') was requested without args, get the total calls to method regardless of args
        if ( empty($real_args) ) {
            if( !array_key_exists($method, $this->_counts )) {
                return 0;
            }

            $total = 0;
            foreach( $this->_counts[$method] as $key => $value ) {
                $total += $value;
            }

            return $total;
        }

        $sargs = $this->_serialize($real_args);

         if( !array_key_exists($method, $this->_counts)) {
            return 0;
        } else if( !array_key_exists($sargs, $this->_counts[$method])) {
            return 0;
        } else {
            return $this->_counts[$method][$sargs];
        }
    }
    // }}}

    // {{{ - _addCount($method, $args = null)
    /**
     * @param $method
     * @param $args
     * 
     * Adds 1 to the count of the number of times $method was called with $args.
     * All non-static methods besides __destruct() will have a call to
     * $this->_addCount($method_name, func_get_args());
     * added to the beginning of the method in the mock object.
     */
    private function _addCount($method, $args = null) {
        $sargs = $this->_serialize($args);
        if( !array_key_exists($method, $this->_counts) ) {
            $this->_counts[$method] = array();
        }
        if( !array_key_exists($sargs, $this->_counts[$method])) {
            $this->_counts[$method][$sargs] = 0;
        }
        $this->_counts[$method][$sargs]++;
    }
    // }}}

    // {{{ - _serialize($args)
    /**
     * @param $args
     */
    private function _serialize($args) {
        if( $args == null ) {
            return null;
        }
        if( is_array($args) && count($args) == 1 && ( is_string($args[0]) || is_int($args[0]) ) ) {
            return $args[0];
        }
        return serialize($args);
    }
    // }}}
}
// }}}

// mocking loader class
class loadermock extends tgif_global_loader {

    public function __construct($params = array()) {
        // don't do anything here
    }

    // php magic methods to trap errors
    public function __call($method, $args) {
        trigger_error("Method $method does not exist in loadermock.",  E_USER_ERROR);
    }

    public function __get($key) {
        trigger_error("Property $key does not exist in loadermock.",  E_USER_ERROR);
    }

    public function __set($key, $value) {
        trigger_error("Property $key does not exist in loadermock.",  E_USER_ERROR);
    }

    // setToCache does nothing
    public function setToCache() {
        return true;
    }

}

?>
