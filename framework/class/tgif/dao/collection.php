<?php
/**
 * Container for {@link tgif_dao_collection}
 *
 * @package tgiframework
 * @subpackage database
 * @author terry chay <tychay@php.net>
 * @copyright c.2015 Terry Chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 *
 */
/**
 * @author terry chay <tychay@php.net>
 * @package tgifraemwork
 * @subpackage database
 */
class tgif_dao_collection extends tgif_dao implements Iterator
{
    // - $_keyData;
    /**
     * The actual primary key values for this instance (because collection may
     * be empty)
     * @var array
     */
    protected $_keyData = array();

    /**
     * Modify read from database to grab rows instead of single row
     *
     * @param array $whereKeys a hash of keys to do the where lookup on (AND)
     * If the array is empty, it will try to generate the wheres from internal
     * list of primary keys.
     * @return array|false $this->_data (which is an array of hashes)
     */
    protected function _read($whereKeys=array())
    {
        global $_TAG;
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
        $this->_keyData = $whereKeys; // cache the values
        $this->_data = $_TAG->dbh->getResults( sprintf(self::_SQL_READ, $this->_table_name, implode(' AND ',$wheres)), $whereKeys );
        return $this->_data;
    }
	function __sleep() {
		return array_merge( parent::__sleep(), array('_keyData') );
	}
    // {{{ __get($name)
    /**
     * Read data from primaryu keys
     *
     * @param string $name property to get
     * @return mixed
     */
    function __get($name)
    {
        if ( array_key_exists($name,$this->_keyData) ) {
            return $this->_keyData[$name];
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
        trigger_error(sprintf('Notice: May not set collection properties: %s::$%s', get_class($this), $name), E_USER_NOTICE);
    }
    // }}}
    //
    // ITERATOR
    //
    public function rewind()
    {
    	reset($this->_data);
    }
    public function current()
    {
    	return current($this->_data);
    }
    public function key()
    {
    	return key($this->_data);
    }
    public function next()
    {
    	return next($this->_data);
    }
    public function valid()
    {
    	$key = key($this->_data);
    	return ( ( $key !== null ) && ( $key !== false ) );
    }

    //
    // OVERRIDES
    //
    // TODO: need to re-implement save()
}
