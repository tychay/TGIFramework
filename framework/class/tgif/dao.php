<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_dao}
 *
 * @package tgiframework
 * @subpackage database
 * @author terry chay <tychay@php.net>
 * @copyright c. 2010 5, Inc. and c. 2010 Terry Chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 *
 */
// {{{ tgif_dao
// docs {{{
/**
 * This is an abstract base class for data access objects (really a data object).
 *
 * This was created for BuyWith5 to ease programming of access. Note that this
 * does a lot more than a data access object (most data access is built right
 * in to PDO) to the point where this is closer to being a data object. In
 * fact it has the CRU of CRUD in it (D will come later).
 *
 * Base data access object (really a data object) to make reading bw5 data
 * easier.
 *
 * Subclass to use it. Example: <code>
 * class app_dao_tablename extends tgif_dao
 * {
 *      protected $_table_name =  'table_name';
 *      protected $_primaryKeys = array('primary_key');
 *      protected $_autoIncrement = 'primary_key';
 * }
 *
 * $dao_obj = new app_dao_tablename(array('primary_key'=>$primary_key));
 * $dao_obj->some_col = $new_value;
 * $dao_obj->save();
 * </code>
 *
 * Of course, you can make it a global by adding it to the config:<code>
 * return array(
 *      'gld_tablename' => array(
 *          'params'        => 1, //primary_key
 *          'construct'     => array('app_dao_tablename'),
 *          'loaderLoader'  => 'setLoader',
 *          'useMemcache'   => true,
 *      ),
 * );
 * </code>
 *
 * This the last lines become: <code>
 * $_TAG->tablename[$primary_key]->some_col = $new_value;
 * // Destructor will call save() which will also update memcache
 * </code>
 *
 * This depends on the database handle $_TAG->dbh.
 * @author terry chay <tychay@buywith5.com>
 * @package tgiframework
 * @subpackage database
 */
// }}}
class tgif_dao
{
    // {{{ - _SQL_READ
    /**
     * This is the base select for reading data from database
     */
    const _SQL_READ = 'SELECT * from %s WHERE %s';
    // }}}
    // {{{ - $_table_name
    /**
     * This is the name of the table in the database.
     * @var string
     */
    protected $_table_name = 'XXOVERRIDEXX';
    // }}}
    // {{{ - $_data
    /**
     * This is where the row is stored (indexed by key)
     * @var array
     */
    protected $_data = array();
    // }}}
    // {{{ - $_primaryKeys
    /**
     * Set this in order to separate WHERE clause for UPDATE
     * @var array
     */
    protected $_primaryKeys = array();
    // }}}
    // {{{ - $_autoIncrement
    /**
     * If set, then this is the autoincrement key in order to update {@link $_data}
     * on an insert.
     * @var string
     */
    protected $_autoIncrement = '';
    // }}}
    // {{{ - $_isChanged
    /**
     * @var boolean Has the row been changed?
     */
    private $_isChanged = false;
    // }}}
    // {{{ - $_loader
    /**
     * @var tgif_global_loader
     */
    private $_loader;
    // }}}
    // CREATION/DESTRUCTION
    // {{{ __construct($primary_keys[,$bypassDb])
    /**
     * Constructor
     *
     * This is designed to be used/cached as a global. Therefore the
     * default behavior is such that when initialized from a {@link tgif_global_collection collection}
     * The parameters are passed in are {@link $_primaryKeys primary keys} and are mapped to a WHERE clause.
     *
     * @param mixed $primary_keys Three types of arrays:
     * - array hash: where clause in lookup, indexed by column name
     * - array: where clause in lookup, variable order matches {@link $_primaryKeys}
     * - array ($bypassDb=true): hash of row data looked up 
     * @param boolean $bypassDb Skip reading the database, just insert
     */
    function __construct($primary_keys, $bypassDb = false)
    {
        if ($bypassDb) {
            // primary keys actually data
            $this->_data = $primary_keys;
        } else {
            if ( !is_array($primary_keys) ) {
                // Single value passed in from global system
                $key_name = $this->_primaryKeys[0];
                $select_keys = array($key_name => $primary_keys);
                $wheres = array($key_name.'=:'.$key_name);
            } else {
                $select_keys = array();
                foreach ($primary_keys as $key=>$value) {
                    // unnammed array passed in from global system
                    if (is_int($key)) {
                        $key = $this->_primaryKeys[$key];
                    }
                    $select_keys[$key] = $value;
                }
            }
            if ( !$this->_read($select_keys) ) {
                // no row? create object
                foreach ($select_keys as $key=>$value) {
                    $this->_data[$key] = $value;
                }
                // update data (unknown missing fields)
                $this->insert(true);
            }
        }
    }
    // }}}
    // {{{ - _read([$whereKeys])
    /**
     * Read from database where key
     *
     * @param array $whereKeys a hash of keys to do the where lookup on (AND)
     * If the array is empty, it will try to generate the wheres from internal
     * list of primary keys.
     * @return array|false $this->_data
     */
    function _read($whereKeys=array())
    {
        //global $_TAG;
        $wheres = array();
        if ( empty($whereKeys) ) {
            foreach ($this->_primaryKeys as $key) {
                $whereKeys[$key] = $this->_data[$key];
                $wheres[] = $key.'=:'.$key;
            }
        } else {
            foreach ($whereKeys as $key=>$value) {
                // unnammed array passed in from global system
                $wheres[] = $key.'=:'.$key;
            }
        }
        $this->_data = $_TAG->dbh->getRow( sprintf(self::_SQL_READ, $this->_table_name, implode(' AND ',$wheres)), $whereKeys );
        return $this->_data;
    }
    // }}}
    // {{{ __destruct()
    /**
     * Save the object if it's being destroyed
     */
    function __destruct()
    {
        $this->save();
    }
    // }}}
    // {{{ __sleep()
    /**
     * Save the object if it's being destroyed (don't save _loader).
     */
    function __sleep()
    {
        return array('_table_name','_data','_primaryKeys','_autoIncrement');
    }
    // }}}
    // {{{ __wakeup()
    /**
     * Save the object if it's being destroyed (don't save _loader).
     */
    function __wakeup()
    {
        $this->_isChanged = false;
    }
    // }}}
    // CREATE
    // {{{ - insert([$forceUpdate])
    /**
     * Create a new row (but not a creation operator).
     *
     * Example:<code>
     * $obj = new tgif_dao_tablename($data,true); // create new object but not database lookup
     * $obj->create(); //now object is in the database and autoincrement key bound
     * $_TAG->dao_tablename[$obj->autoIncremeent()] = $obj; //bind to global
     * </code>
     *
     * Note that this does not bind to any global becuase it is in the base class of something else (which knows what global to bind to).
     *
     * @param array $data The row data to insert
     * @return boolean success or failure
     * @todo failure should trigger exception
     */
    function insert($forceUpdate=false)
    {
        //global $_TAG;
        $dbh = $_TAG->dbh;
        $success = $dbh->insert( $this->_table_name, $this->_data );
        if (!$success) {
            // probably should trigger execption
            trigger_error('Insert failed!');
            return false;
        }
        if ($this->_autoIncrement) {
            $this->_data[$this->_autoIncrement] = $dbh->insertId;
        }
        if ( $forceUpdate ) {
            $this->_read();
        }
        $this->_saveToCache();
        return true;
    }
    // }}}
    // {{{ - insertOrUpdate($whereKeys[,$forceUpdate])
    /**
     * Do an insertOrUpdate()
     *
     * @param array $whereKeys a list of keys to use in the where cause.
     * @return boolean success or failure
     * @todo failure should trigger exception
     */
    function insertOrUpdate($whereKeys, $forceUpdate=false)
    {
        //global $_TAG;
        $dbh    = $_TAG->dbh;
        $data   = $this->_data;
        $wheres = array();
        foreach ($whereKeys as $key) {
            $wheres[$key] = $data[$key];
            unset($data[$key]);
        }
        $success = $dbh->insertOrUpdate( $this->_table_name, $data, $wheres, $this->_autoIncrement );
        if ( !$success ) {
            //trigger exception
            return false;
        }
        // there ar missing files :-(
        if ($this->_autoIncrement) {
            $this->_data[$this->_autoIncrement] = $dbh->insertId;
        }
        if ( $forceUpdate ) {
            $this->_read($wheres);
        }
        $this->_saveToCache();
        return true;
    }
    // }}}
    // MAGIC METHODS: READ UPDATE
    // {{{ __get($name)
    /**
     * Read data from database properties
     *
     * @param string $name property to get
     * @return mixed
     */
    function __get($name)
    {
        if ( array_key_exists($name,$this->_data) ) {
            return $this->_data[$name];
        }
        trigger_error(sprintf('Notice: Undefined property: %s::$%s', get_class($this), $name), E_USER_NOTICE);
        return null;
    }
    // }}}
    // {{{ __set($name,$value)
    /**
     * @param string $name property to set
     * @param mixed $value
     */
    function __set($name, $value)
    {
        //if (!isset ($this->_user)) { … }
        if ( array_key_exists($name,$this->_data) ) {
            $old_value = $this->_data[$name];
            $this->_data[$name] = $value;
            if ($old_value != $value) {
                $this->_isChanged = true;
            }
        }
    }
    // }}}
    // {{{ __issset($name)
    /**
     * isset() on empty properies
     *
     * @param string $name property to get
     * @return mixed
     */
    function __isset($name)
    {
        if ( array_key_exists($name,$this->_data) ) {
            return true;
        }
        return false;
    }
    // }}}
    // ACCESSORS
    // {{{ getData()
    /**
     * Get all the data necessary to be able to reconstruct.
     *
     * To reconstruct:<code>
     * $data = $obj->getData();
     * $obj = new tgif_dao_tablename($data,true); // create new object but not database lookup
     * </code>
     *
     * @return array The data used to reconstruct
     */
    function getData()
    {
        return $this->_data;
    }
    // }}}
    // PUBLIC METHODS
    // {{{ - save()
    /**
     * Save the object if it's being destroyed
     */
    public function save()
    {
        //global $_TAG;
        // don't db call if nothing changed
        if (!$this->_isChanged) { return; }
        $this->_isChanged = false;
        $data = $this->_data;
        $where = array();
        foreach ($this->_primaryKeys as $key) {
            $where[$key] = $data[$key];
            unset($data[$key]);
        }
        // save to database
        $success = $_TAG->dbh->update( $this->_table_name, $data, $where );
        if ( $success) {
            $this->_saveToCache();
        }
    }
    // }}}
    // CACHING METHODS
    // {{{ - setLoader($loader)
    /**
     * Bind to loader
     */
    public function setLoader($loader)
    {
        $this->_loader = $loader;
    }
    // }}}
    // {{{ - _saveToCache()
    /**
     * Update cache
     */
    private function _saveToCache()
    {
        if ( $this->_loader ) {
            $this->_loader->setToCache( $this );
        }
    }
    // }}}
}
// }}}
?>
