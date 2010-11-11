<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_db}
 *
 * @package tgiframework
 * @subpackage utilities
 * @copyright 2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_db_pdo
// docs {{{
/**
 * Class adds some useful functions (a la {@link http://codex.wordpress.org/Function_Reference/wpdb_Class WordPress wpdb}) to PDO
 *
 * @package tgiframework
 * @subpackage database
 * @author terry chay <tychay@php.net>
 */
// }}}
class tgif_db_pdo extends pdo
{
    // {{{ __construct(…)
    /**
     * Calls PDO constructor using a single paramter array
     *
     * @param array $pdo_args The arguments for the {@link PDO} constructor
     */
    function __construct($pdo_args)
    {
        switch( count($pdo_args) ) {
        case 1:
            parent::__construct($pdo_args[0]);
            break;
        case 2:
            parent::__construct($pdo_args[0],$pdo_args[1]);
            break;
        case 3:
            parent::__construct($pdo_args[0],$pdo_args[1],$pdo_args[2]);
            break;
        case 4:
            parent::__construct($pdo_args[0],$pdo_args[1],$pdo_args[2],$pdo_args[3]);
            break;
        }
    }
    // }}}
    // PUBLIC METHODS
    // {{{ - getRow($query[,$bindings,$output_type,$row_offset])
    /**
     * Select an entire row from a database.
     *
     * @param string $query SQL query to execute
     * @param array $binding bind variables
     * @param string $output_type OBJECT, ARRAY_A, or ARRAY_N. Unlike the
     * WordPress db, this will default to ARRAY_A
     * @param integer $row_offset The desired row
     * @return mixed the row from the database. If no result found then null
     * @todo support CLASS, BOUND, INTO, LAZY fetch types?
     */
    function getRow($query, $bindings=array(), $output_type='ARRAY_A', $row_offset=0)
    {
        $fetch_style = PDO::FETCH_BOTH;
        switch ($output_type) {
            case 'ARRAY_A': $fetch_style = PDO::FETCH_ASSOC; break;
            case 'ARRAY_N': $fetch_style = PDO::FETCH_NUM; break;
            case 'OBJECT' : $fetch_style = PDO::FETCH_OBJ; break;
        }
        $sth = $this->_prepareQuery($query,$bindings);

        $sth->execute();

        // scan to right row
        for ($i=1; $i<$row_offset; ++$i) {
            $success = $sth->nextRowset();
            if (!$success) { return null; }
        }

        return $sth->fetch($fetch_style);
    }
    // }}}
    // {{{ - getVar($query[,$bindings,$column_offset,$row_offset])
    /**
     * Select a single variable from a database.
     *
     * @param string $query SQL query to execute
     * @param array $binding bind variables
     * @param integer $column_offset The desired column
     * @param integer $row_offset The desired row
     * @return mixed the varible from the database. If no result found then null
     */
    function getVar($query, $bindings=array(), $column_offset=0, $row_offset=0)
    {
        $sth = $this->_prepareQuery($query,$bindings);

        $sth->execute();

        // scan to right row
        for ($i=1; $i<$row_offset; ++$i) {
            $success = $sth->nextRowset();
            if (!$success) { return null; }
        }

        return $sth->fetchColumn($column_offset);
    }
    // }}}
    // PRIVATE METHODS
    // {{{ - _prepareQuery($query[,$bindings])
    /**
     * Prepares a query
     *
     * @param string $query SQL query to execute
     * @param array $binding bind variables
     * @return PDOStatement a statement handle of the prepared query
     */
    private function _prepareQuery($query, $bindings=array())
    {
        $return_obj = $this->prepare($query);
        foreach ($bindings as $key=>$value) {
            // make sure there is a : at the beginning of the bindparam
            $key = ( substr($key,0,1) == ':' ) ? $key : ':'.$key;
            if (is_int($value)) {
		        $return_obj->bindParam($key, $value, PDO::PARAM_INT);
            } else {
		        $return_obj->bindParam($key, $value, PDO::PARAM_INT);
            }
        }
        return $return_obj;
    }
    // }}}
}
// }}}
?>
