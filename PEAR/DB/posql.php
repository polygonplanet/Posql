<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * The PEAR DB driver for interacting with Posql database
 */
/**
 * Obtain the DB_common class so it can be extended from
 */
require_once 'DB/common.php';

/**
 * The methods PEAR DB uses to interact with Posql database class
 *
 * These methods overload the ones declared in DB_common.
 *
 * NOTE:
 *   This driver is non-standard in PEAR. 
 *   And, consider done only minimum debugging. 
 *   The test script is bundled in the same directory.
 *   See detail in it.
 *
 * @package     Posql
 * @subpackage  PEAR/DB
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 * @uses        Posql Version 2.11 or later
 * @link        http://sourceforge.jp/projects/posql/
 * @link        http://sourceforge.net/projects/posql/
 * @link        http://pear.php.net/package/DB
 * @license     Dual licensed under the MIT and GPL licenses
 * @copyright   Copyright (c) 2010 polygon planet
 * @version     $Rev: 0.04 2010/01/11 06:24:17 polygon $
 */
class DB_posql extends DB_common
{
    // {{{ properties

    /**
     * The DB driver type (mysql, oci8, odbc, etc.)
     * @var string
     */
    var $phptype = 'posql';

    /**
     * The database syntax variant to be used (db2, access, etc.), if any
     * @var string
     */
    var $dbsyntax = 'posql';

    /**
     * The capabilities of this DB implementation
     *
     * The 'new_link' element contains the PHP version that first provided
     * new_link support for this DBMS.  Contains false if it's unsupported.
     *
     * Meaning of the 'limit' element:
     *   + 'emulate' = emulate with fetch row by number
     *   + 'alter'   = alter the query
     *   + false     = skip rows
     *
     * @var array
     */
    var $features = array(
        'limit'         => false,
        'new_link'      => false,
        'pconnect'      => false,
        'prepare'       => false,
        'ssl'           => false,
        'transactions'  => false,
        'numrows'       => 'emulate'
    );

    /**
     * A mapping of native error codes to DB error codes
     *
     * @var array
     */
    var $errorcode_map = array();

    /**
     * The raw database connection created by PHP
     * @var resource|object
     */
    var $connection;

    /**
     * The DSN information for connecting to a database
     * @var array
     */
    var $dsn = array();

    /**
     * The number of last affected rows
     * @var int
     */
    var $_affected_rows;

    /**
     * The size of result rows
     * @var int
     */
    var $_num_rows;

    /**
     * The column size of result rows
     * @var int
     */
    var $_num_cols;

    /**
     * The index number of result rows
     * @var int
     */
    var $_row_count = 0;

    // }}}
    // {{{ constructor

    /**
     * This constructor calls <kbd>$this->DB_common()</kbd>
     *
     * @return void
     */
    function DB_posql()
    {
        $this->DB_common();

        if (isset($this->options['portability'])) {
            $this->options['portability'] &= DB_PORTABILITY_NUMROWS;
        } else {
            $this->options['portability'] = 0;
        }

        // The "engine" supports only Posql. to use setOption(), getOption()
        $this->options['engine'] = 'sql';
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database server, log in and open the database
     *
     * Don't call this method directly.  Use DB::connect() instead.
     *
     * @example
     * <code>
     * require_once 'DB.php';
     * require_once 'posql.php';
     *
     * $dsn = 'posql:///path/to/database_name';
     * $db =& DB::connect($dsn);
     * if (PEAR::isError($db)) {
     *     die($db->getMessage());
     * }
     * </code>
     *
     * @param array $dsn         the data source name
     * @param bool  $persistent  should the connection be persistent?
     *
     * @return int  DB_OK on success. A DB_Error object on failure.
     */
    function connect($dsn, $persistent = false)
    {
        if (isset($this->connection) && @is_a($this->connection, 'Posql')) {
            return DB_OK;
        }

        $exists = false;
        if (substr(phpversion(), 0, 1) <= 4) {
            $exists = class_exists('Posql');
        } else {
            $exists = class_exists('Posql', false);
        }

        if (!$exists) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        if (empty($dsn['database'])) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        if (substr(phpversion(), 0, 1) <= 4) {
            //
            // PHP4 will emit the warning with register_shutdown_function(),
            // this code will be  deal with warning.
            // Instead,the VACUUM command might not be automatically executed.
            //
            $db = & new Posql;

            // Therefore, use PEAR's shutdown function caller.
            if (is_callable(array('PEAR', 'registerShutdownFunc'))) {
                PEAR::registerShutdownFunc(
                    array($db, 'terminate'),
                    array($db->fullPath($dsn['database']), $db->getId())
                );
            }
            $db->_setRegistered(true);
            $db->open($dsn['database']);
        } else {
            $db = new Posql($dsn['database']);
        }

        $this->connection = & $db;

        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Disconnects from the database server
     *
     * @return bool  TRUE on success, FALSE on failure
     */
    function disconnect()
    {
        if (isset($this->connection) && is_object($this->connection)) {
            if (method_exists($this->connection, 'terminate')) {
                $this->connection->terminate();
            }
        }
        $this->connection = null;
        return true;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Sends a query to the database server
     *
     * NOTICE:  This method needs PHP's track_errors ini setting to be on.
     * It is automatically turned on when connecting to the database.
     * Make sure your scripts don't turn it off.
     *
     * @param string  the SQL query string
     *
     * @return mixed  + a PHP result resrouce for successful SELECT queries
     *                + the DB_OK constant for other successful queries
     *                + a DB_Error object on failure
     */
    function simpleQuery($query)
    {
        if (!@is_a($this->connection, 'Posql')) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        $this->last_query = $query;
        $query = $this->modifyQuery($query);

        if ($this->connection->isManip($query)) {
            $result = $this->connection->exec($query);
        } else {
            $result = $this->connection->query($query);
        }

        if (is_object($result)) {
            if (method_exists($result, 'fetchAll')) {
                $result = $result->fetchAll('assoc');
            } else {
                settype($result, 'array');
            }
        }

        if ($this->connection->isError()) {
            $this->_lasterror = $this->connection->lastError();
            return $this->posqlRaiseError($this->_lasterror);
        }

        $this->result = & $result;

        if (!is_array($result) && is_numeric($result)) {
            $this->_affected_rows = $result;
        }

        if (is_array($result)) {
            $this->_affected_rows = 0;
            $this->_num_rows = count($this->result);
            if (is_array(reset($this->result))) {
                $this->_num_cols = count(reset($this->result));
            } else {
                $this->_num_cols = null;
            }
            $this->_row_count = 0;
            return $this->result;
        }

        return DB_OK;
    }

    // }}}
    // {{{ setOption()

    /**
     * Sets run-time configuration options for PEAR DB
     *
     * @param string $option option name
     * @param mixed  $value value for the option
     *
     * @return int  DB_OK on success.  A DB_Error object on failure.
     *
     * @see DB_common::$options
     */
    function setOption($option, $value)
    {
        if (!@is_a($this->connection, 'Posql')) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        $ok = false;
        switch (strtolower($option)) {
            case 'engine':
                //
                // The "engine" supports only Posql.
                // {@see Posql->setEngine(), Posql->getEngine()}
                //
                $this->connection->setEngine($value);
                $ok = true;
                break;
            case 'charset':
                $this->connection->setCharset($value);
                $ok = true;
                break;
            case 'path':
            case 'database_path':
                $this->connection->setPath($value);
                $ok = true;
                break;
            case 'ext':
            case 'database_extension':
                $this->connection->setExt($value);
                $ok = true;
                break;
            default:
                break;
        }
        if ($ok) {
            return DB_OK;
        }
        return parent::setOption($option, $value);
    }

    // }}}
    // {{{ getOption()

    /**
     * Returns the value of an option
     *
     * @param string $option  the option name you're curious about
     *
     * @return mixed  the option's value
     */
    function getOption($option)
    {
        if (!@is_a($this->connection, 'Posql')) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        switch (strtolower($option)) {
            case 'engine':
                return $this->connection->getEngine();
            case 'charset':
                return $this->connection->getCharset();
            case 'path':
            case 'database_path':
                return $this->connection->getPath();
            case 'ext':
            case 'database_extension':
                return $this->connection->getExt();
            case 'version':
            case 'server_version':
                return $this->connection->getVersion();
            default:
                break;
        }
        return parent::getOption($option);
    }

    // }}}
    // {{{ setDatabase($name)

    /**
     * Select a different database
     *
     * @param   string  name of the database that should be selected
     * @return  string  name of the database previously connected to
     * @access  public
     */
    function setDatabase($database)
    {
        if (!@is_a($this->connection, 'Posql')) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        $prev_database = $this->connection->getPath();
        $this->connection->setPath($database);
        return $prev_database;
    }

    // }}}
    // {{{ getDatabase()

    /**
     * Get the current database
     *
     * @return  string  name of the database
     * @access  public
     */
    function getDatabase()
    {
        if (!@is_a($this->connection, 'Posql')) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        $database = $this->connection->getPath();
        return $database;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal posql result pointer to the next available result
     *
     * @param resource $result  the valid posql result resource
     *
     * @return bool  true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return false;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Deletes the result set and frees the memory occupied by the result set
     *
     * @param resource $result  PHP's query result resource
     *
     * @return bool  TRUE on success, FALSE if $result is invalid
     *
     * @see DB_result::free()
     */
    function freeResult(&$result)
    {
        $result = null;
        $this->result = null;
        return true;
    }

    // }}}
    // {{{ fetchInfo()

    /**
     * Places a row from the result set into the given array
     *
     * Formating of the array and the data therein are configurable.
     * See DB_result::fetchInto() for more information.
     *
     * This method is not meant to be called directly.  Use
     * DB_result::fetchInto() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result    the query result resource
     * @param array    $arr       the referenced array to put the data in
     * @param int      $fetchmode how the resulting array should be indexed
     * @param int      $rownum    the row number to fetch (0 = first row)
     *
     * @return mixed  DB_OK on success, NULL when the end of a result set is
     *                 reached or on failure
     *
     * @see DB_result::fetchInto()
     */
    function fetchInto($result, &$arr, $fetchmode, $rownum = null)
    {
        if ($rownum !== null) {
            if (isset($this->_num_rows) && $this->_num_rows <= $rownum) {
                return null;
            }
        }

        if (isset($this->_row_count)
         && isset($result[$this->_row_count]))
        {
            $arr = $result[$this->_row_count];
        } else {
            return null;
        }

        $this->_row_count++;

        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            if (($this->options['portability'] & DB_PORTABILITY_LOWERCASE)
             && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        }

        if (!$arr) {
            return null;
        }

        if ($this->options['portability'] & DB_PORTABILITY_NULL_TO_EMPTY) {
            $this->_convertNullArrayValuesToEmpty($arr);
        }

        return DB_OK;
    }

    // }}}
    // {{{ numCols()

    /**
     * Gets the number of columns in a result set
     *
     * This method is not meant to be called directly.
     * Use DB_result::numCols() instead.
     * It can't be declared "protected"
     *  because DB_result is a separate object.
     *
     * @param resource $result  PHP's query result resource
     *
     * @return int  the number of columns.  A DB_Error object on failure.
     *
     * @see DB_result::numCols()
     */
    function numCols($result)
    {
        $cols = null;
        if (is_array($result) && is_array(current($result))) {
            $cols = count(current($result));
        } else if (isset($this->_num_cols)) {
            $cols = $this->_num_cols;
        }
        return $cols;
    }

    // }}}
    // {{{ numRows()

    /**
     * Gets the number of rows in a result set
     *
     * This method is not meant to be called directly.  Use
     * DB_result::numRows() instead.  It can't be declared "protected"
     * because DB_result is a separate object.
     *
     * @param resource $result  PHP's query result resource
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     *
     * @see DB_result::numRows()
     */
    function numRows($result)
    {
        $rows = null;
        if (is_array($result)) {
            $rows = count($result);
        } else if (isset($this->_num_rows)) {
            $rows = $this->_num_rows;
        }
        return $rows;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Determines the number of rows affected by a data maniuplation query
     *
     * 0 is returned for queries that don't manipulate data.
     *
     * @return int  the number of rows.  A DB_Error object on failure.
     */
    function affectedRows()
    {
        $affected_rows = null;
        if (isset($this->_affected_rows)) {
            $affected_rows = $this->_affected_rows;
        }
        return $affected_rows;
    }

    // }}}
    // {{{ nextId()

    /**
     * Returns the next free id in a sequence
     *
     * @param string  $seq_name  name of the sequence
     * @param boolean $ondemand  when true, the seqence is automatically
     *                            created if it does not exist
     *
     * @return int  the next id number in the sequence.
     *               A DB_Error object on failure.
     *
     * @see DB_common::nextID(), DB_common::getSequenceName()
     */
    function nextId()
    {
        if (!@is_a($this->connection, 'posql')) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        $result = null;
        $current_table = $this->connection->getTableName();
        if ($current_table != null) {
            $result = $this->connection->getNextId($current_table);
        }
        return $result;
    }

    // }}}
    // {{{ lastInsertId()

    /**
     * Returns the last inserted auto-increment ID
     *
     * This extension function only of Posql.
     *
     * @param string $table name of the table into which a new row was inserted
     * @param string $field name of the field into which a new row was inserted
     * @return mixed DB_Error object or auto-inclement ID
     * @access public
     */
    function lastInsertId($table = null, $field = null)
    {
        if (!@is_a($this->connection, 'Posql')) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        $result = $this->connection->getLastInsertId();
        return $result;
    }

    // }}}
    // {{{ getDbFileStats()

    /**
     * Get the file stats for the current database
     *
     * Possible arguments are
     *   dev, ino, mode, nlink, uid, gid, rdev, size,
     *   atime, mtime, ctime, blksize, blocks
     *  or a numeric key between 0 and 12.
     *
     * @param string $arg  the array key for stats()
     *
     * @return mixed  an array on an unspecified key, integer on a passed
     *                arg and false at a stats error
     */
    function getDbFileStats($arg = '')
    {
        clearstatcache();
        $stats = @stat($this->dsn['database']);
        if (!$stats) {
            return false;
        }
        if (is_array($stats)) {
            if (is_numeric($arg)) {
                if (((int)$arg <= 12) & ((int)$arg >= 0)) {
                    return false;
                }
                return $stats[$arg];
            }
            if (array_key_exists(trim($arg), $stats)) {
                return $stats[$arg];
            }
        }
        return $stats;
    }

    // }}}
    // {{{ escapeSimple()

    /**
     * Escapes a string according to the current DBMS's standards
     *
     * @param string $string  the string to be escaped
     *
     * @return string  the escaped string
     *
     * @see DB_common::escapeSimple()
     */
    function escapeSimple($string)
    {
        if (!@is_a($this->connection, 'Posql')) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED);
        }

        $string = $this->connection->escapeString($string);
        return $string;
    }

    // }}}
    // {{{ modifyLimitQuery()

    /**
     * Adds LIMIT clauses to a query string according
     *  to current DBMS standards
     *
     * @param string $query   the query to modify
     * @param int    $from    the row to start to fetching (0 = the first row)
     * @param int    $count   the numbers of rows to fetch
     *
     * @return string  the query string with LIMIT clauses added
     *
     * @access protected
     */
    function modifyLimitQuery($query, $from, $count)
    {
        $result = sprintf('%s LIMIT %.0f OFFSET %.0f', $query, $count, $from);
        return $result;
    }

    // }}}
    // {{{ posqlRaiseError()

    /**
     * Produces a DB_Error object regarding the current problem
     *
     * @param string $errmsg  the error message
     *
     * @return object  the DB_Error object
     *
     * @see DB_common::raiseError(),
     *      DB_posql::errorNative()
     */
    function posqlRaiseError($errmsg = null)
    {
        if ($errmsg === null) {
            $errmsg = $this->errorNative();
        }
        return $this->raiseError(DB_ERROR, null, null, null, $errmsg);
    }

    // }}}
    // {{{ errorNative()

    /**
     * Gets the DBMS' native error message produced by the last query
     *
     * {@internal uses Posql->lastError() to get native error message.}
     *
     * @return string  the DBMS' error message
     */
    function errorNative()
    {
        $error = null;
        if (isset($this->connection) && is_object($this->connection)
         && method_exists($this->connection, 'lastError')) 
        {
            $error = $this->connection->lastError();
        }

        if ($error == null) {
            $error = 'Unknown error';
        }
        return $error;
    }

    // }}}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
