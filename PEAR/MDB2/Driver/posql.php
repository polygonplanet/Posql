<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * The PEAR MDB2 driver for interacting with Posql database
 */
/**
 * Obtain the MDB2 class so it can be extended from
 * ------------------------------------------------------------------
 * Note:
 * -----
 *   An "standard" driver does not need the Require code.
 *   Moreover, acknowledge Require code
 *    which is at the end of the script that not needs originally.
 * ------------------------------------------------------------------
 */
require_once 'MDB2.php';

/**
 * The methods PEAR MDB2 uses to interact with Posql database class
 *
 * These methods overload the ones declared in MDB2_common.
 *
 * NOTE:
 *   This driver is non-standard in PEAR.
 *   And, consider done only minimum debugging. 
 *   The test script is bundled in the same directory.
 *   See detail in it.
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 * @uses        Posql Version 2.11 or later
 * @link        http://sourceforge.jp/projects/posql/
 * @link        http://sourceforge.net/projects/posql/
 * @link        http://pear.php.net/package/MDB2
 * @license     Dual licensed under the MIT and GPL licenses
 * @copyright   Copyright (c) 2010 polygon planet
 * @version     $Rev: 0.04 2010/01/11 06:45:26 polygon $
 */

/**
 * MDB2 Posql driver
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 */
class MDB2_Driver_posql extends MDB2_Driver_Common
{
    // {{{ properties

    var $string_quoting = array(
        'start'          => "'",
        'end'            => "'",
        'escape'         => '\\',
        'escape_pattern' => false
    );

    var $identifier_quoting = array(
        'start'  => '',
        'end'    => '',
        'escape' => false
    );

    var $sql_comments = array(
        array('start' => '--',  'end' => "\n", 'escape' => false),
        array('start' => '#',   'end' => "\n", 'escape' => false),
        array('start' => '//',  'end' => "\n", 'escape' => false),
        array('start' => '/*',  'end' => '*/', 'escape' => false)
    );

    var $_lasterror = '';

    var $_affected_rows;

    var $_num_rows;

    var $_num_cols;

    var $_row_count;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();

        $this->phptype = 'posql';
        $this->dbsyntax = 'posql';

        $this->supported['sequences']            = 'emulated';
        $this->supported['indexes']              = false;
        $this->supported['affected_rows']        = true;
        $this->supported['summary_functions']    = true;
        $this->supported['order_by_text']        = true;
        $this->supported['current_id']           = 'emulated';
        $this->supported['limit_queries']        = true;
        $this->supported['LOBs']                 = true;
        $this->supported['replace']              = false;
        $this->supported['transactions']         = true;
        $this->supported['savepoints']           = false;
        $this->supported['sub_selects']          = true;
        $this->supported['triggers']             = false;
        $this->supported['auto_increment']       = true;
        $this->supported['primary_key']          = true;
        $this->supported['result_introspection'] = false;
        $this->supported['prepared_statements']  = 'emulated';
        $this->supported['identifier_quoting']   = false;
        $this->supported['pattern_escaping']     = false;
        $this->supported['new_link']             = false;

        $trans_name = '___php_MDB2_posql_auto_commit_off';
        $this->options['DBA_username']           = false;
        $this->options['DBA_password']           = false;
        $this->options['base_transaction_name']  = $trans_name;
        $this->options['fixed_float']            = 0;
        $this->options['database_path']          = '';
        $this->options['database_extension']     = '';
        $this->options['server_version']         = '';
        $this->options['max_identifiers_length'] = 128;

        // The "engine" supports only Posql. to use setOption(), getOption()
        $this->options['engine'] = 'sql';
    }

    // }}}
    // {{{ function errorInfo()

    /**
     * This method is used to collect information about an error
     *
     * @param integer $error
     * @return array
     * @access public
     */
    function errorInfo($error = null)
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        $native_msg = null;
        if ($this->connection->isError()) {
            $native_msg = $this->connection->lastError();
        }

        return array($error, null, $native_msg);
    }

    // }}}
    // {{{ function escape()

    /**
     * Quotes a string so it can be safely used in a query. It will quote
     * the text so it can safely be used within a query.
     *
     * @param   string  the input string to quote
     * @param   bool    escape wildcards
     *
     * @return  string  quoted string
     *
     * @access  public
     */
    function escape($text, $escape_wildcards = false)
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        $text = $this->connection->escapeString($text);
        if ($escape_wildcards) {
            $text = strtr($text,
                array(
                    '%' => '\%',
                    '_' => '\_'
                )
            );
        }
        return $text;
    }

    // }}}
    // {{{ function setOption($option, $value)

    /**
     * set the option for the db class
     *
     * @param   string  option name
     * @param   mixed   value for the option
     *
     * @return  mixed   MDB2_OK or MDB2 Error Object
     *
     * @access  public
     */
    function setOption($option, $value)
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
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
            if ($this->connection->isError()) {
                $errmsg = $this->connection->lastError();
                return $this->raiseError(null, null, null,
                                         $errmsg, __FUNCTION__);
            }
            return MDB2_OK;
        }

        return parent::setOption($option, $value);
    }

    // }}}
    // {{{ function getOption($option)

    /**
     * Returns the value of an option
     *
     * @param   string  option name
     *
     * @return  mixed   the option value or error object
     *
     * @access  public
     */
    function getOption($option)
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
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
    // {{{ function setDatabase($name)

    /**
     * Select a different database
     *
     * @param   string  name of the database that should be selected
     *
     * @return  string  name of the database previously connected to
     *
     * @access  public
     */
    function setDatabase($name)
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        if (isset($this->database_name)) {
            $previous_database_name = $this->database_name;
        } else {
            $previous_database_name = null;
        }
        $this->database_name = $name;
        $this->connection->setPath($name);

        return $previous_database_name;
    }

    // }}}
    // {{{ function getDatabase()

    /**
     * Get the current database
     *
     * @return  string  name of the database
     *
     * @access  public
     */
    function getDatabase()
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        $result = $this->connection->getPath();
        return $result;
    }

    // }}}
    // {{{ function getDatabaseFile()

    /**
     * Builds the string with path+dbname+extension
     *
     * @return string full database path+file
     * @access protected
     */
    function _getDatabaseFile($database_name)
    {
        if ($database_name === '') {
            return $database_name;
        }
        return $this->options['database_path']
             . $database_name 
             . $this->options['database_extension'];
    }

    // }}}
    // {{{ function _getPosqlObject()

    /**
     * Posql only usage:
     * Return the object instance of Posql.
     *
     * Note:
     *  PHP4 warns "Nesting level too deep - recursive dependency?"
     *   by comparing objects.
     *  Use the static variable to evade.
     *
     * @return object   Posql instance object on success, 
     *                  MDB2 Error Object on failure
     */
    function &_getPosqlObject()
    {
        static $object = null;

        if ($object === null) {

            $exists = false;
            if (substr(phpversion(), 0, 1) <= 4) {
                $exists = class_exists('Posql');
            } else {
                $exists = class_exists('Posql', false);
            }

            if (!$exists) {
                return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                         null, null,
                                         'Not exists the Posql class',
                                         __FUNCTION__);
            }
            $object = & new Posql;
        }
        return $object;
    }

    // }}}
    // {{{ function connect()

    /**
     * Connect to the database
     *
     * Don't call this method directly. Use MDB2::connect() instead.
     *
     * @example
     * <code>
     * require_once 'DB.php';
     * require_once 'Driver/posql.php';
     *
     * $dsn = 'posql:///path/to/database_name';
     * $mdb2 =& MDB2::connect($dsn);
     * if (PEAR::isError($mdb2)) {
     *     die($mdb2->getMessage());
     * }
     * </code>
     *
     * @return true on success, MDB2 Error Object on failure
     */
    function connect()
    {
        if (isset($this->connection) && @is_a($this->connection, 'Posql')) {
            return MDB2_OK;
        }

        $this->connection = & $this->_getPosqlObject();
        $database_file = $this->_getDatabaseFile($this->database_name);
        if (empty($this->database_name)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED, null, null,
                          'unable to establish a connection', __FUNCTION__);
        }

        if (substr(phpversion(), 0, 1) <= 4 
         && !$this->connection->_getRegistered()) {
            //
            // PHP4 will emit the warning with register_shutdown_function(),
            // this code will be  deal with warning.
            // Instead,the VACUUM command might not be automatically executed.
            //
            // Therefore, use PEAR's shutdown function caller.
            if (is_callable(array('PEAR', 'registerShutdownFunc'))) {
                PEAR::registerShutdownFunc(
                    array(
                        $this->connection,
                        'terminate'
                    ),
                    array(
                        $this->connection->fullPath($database_file), 
                        $this->connection->getId()
                    )
                );
            }
            $this->connection->_setRegistered(true);
        }

        $this->connection->open($database_file);
        if ($this->connection->isError()) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED, null, null,
                              $this->connection->lastError(), __FUNCTION__);
        }

        $this->connected_dsn = $this->dsn;
        $this->connected_database_name = $database_file;
        $this->opened_persistent = false;
        $this->dbsyntax = $this->phptype;
        if (!empty($this->dsn['dbsyntax'])) {
            $this->dbsyntax = $this->dsn['dbsyntax'];
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ function databaseExists()

    /**
     * check if given database name is exists?
     *
     * @param string $name    name of the database that should be checked
     *
     * @return mixed true/false on success, a MDB2 error on failure
     * @access public
     */
    function databaseExists($name)
    {
        $database_file = $this->_getDatabaseFile($name);
        $result = @file_exists($database_file);
        return $result;
    }

    // }}}
    // {{{ function disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @param  boolean $force if the disconnect should be forced even if the
     *                        connection is opened persistently
     * @return mixed true on success, false if not connected and error
     *                object on error
     * @access public
     */
    function disconnect($force = true)
    {
        if (isset($this->connection) && is_object($this->connection)) {
            if (method_exists($this->connection, 'terminate')) {
                $this->connection->terminate();
            }
        }
        $this->connection = null;
        return parent::disconnect($force);
    }

    // }}}
    // {{{ function setCharset($charset, $connection = null)

    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     * @param resource  connection handle
     *
     * @return true on success, MDB2 Error Object on failure
     */
    function setCharset($charset, $connection = null)
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        $result = $this->connection->setCharset($charset);
        return $result;
    }

    // }}}
    // {{{ function getCharset()

    /**
     * This extension function only of Posql.
     * Get the current charset of the database
     *
     * @return  string  current internal encoding
     *
     * @access  public
     */
    function getCharset()
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        $result = $this->connection->getCharset();
        return $result;
    }

    // }}}
    // {{{ function beginTransaction($savepoint = null)

    /**
     * Start a transaction or set a savepoint.
     *
     * ------------------------------------------
     * Note:
     *   Posql ignore the savepoint.
     *   Not supported the nested transaction.
     * ------------------------------------------
     *
     * @param   string  name of a savepoint to set
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function beginTransaction($savepoint = null)
    {
        $this->debug('Starting transaction/savepoint', 
                     __FUNCTION__, 
                     array('is_manip'  => true,
                           'savepoint' => $savepoint));

        if (!is_null($savepoint)) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                            'savepoints are not supported', __FUNCTION__);
        } else if ($this->in_transaction) {
            return MDB2_OK;
        }

        if (!$this->destructor_registered && $this->opened_persistent) {
            $this->destructor_registered = true;
            register_shutdown_function('MDB2_closeOpenTransactions');
        }

        $query = sprintf('BEGIN TRANSACTION %s',
                         $this->options['base_transaction_name']);
        $result = & $this->_doQuery($query, true);
        if (PEAR::isError($result)) {
            return $result;
        }

        $this->in_transaction = true;

        return MDB2_OK;
    }

    // }}}
    // {{{ commit($savepoint = null)

    /**
     * Commit the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after committing the pending changes.
     *
     * ------------------------------------------
     * Note:
     *   Posql ignore the savepoint.
     *   Not supported the nested transaction.
     * ------------------------------------------
     *
     * @param   string  name of a savepoint to release
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function commit($savepoint = null)
    {
        $this->debug('Committing transaction/savepoint', 
                     __FUNCTION__, 
                     array('is_manip'  => true, 
                           'savepoint' => $savepoint));

        if (!$this->in_transaction) {
            return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                   'commit/release savepoint cannot be done'
                .  ' changes are auto committed', __FUNCTION__);
        }

        if (!is_null($savepoint)) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                            'savepoints are not supported', __FUNCTION__);
        }

        $query = sprintf('COMMIT TRANSACTION %s',
                         $this->options['base_transaction_name']);
        $result =& $this->_doQuery($query, true);
        if (PEAR::isError($result)) {
            return $result;
        }

        $this->in_transaction = false;

        return MDB2_OK;
    }

    // }}}
    // {{{ function rollback($savepoint = null)

    /**
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * ------------------------------------------
     * Note:
     *   Posql ignore the savepoint.
     *   Not supported the nested transaction.
     * ------------------------------------------
     *
     * @param   string  name of a savepoint to rollback to
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     *
     * @access  public
     */
    function rollback($savepoint = null)
    {
        $this->debug('Rolling back transaction/savepoint', 
                     __FUNCTION__, 
                     array('is_manip'  => true, 
                           'savepoint' => $savepoint));

        if (!$this->in_transaction) {
            return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                'rollback cannot be done changes are auto committed',
                __FUNCTION__);
        }

        if (!is_null($savepoint)) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                            'savepoints are not supported', __FUNCTION__);
        }

        $query = sprintf('ROLLBACK TRANSACTION %s',
                         $this->options['base_transaction_name']);

        $result =& $this->_doQuery($query, true);
        if (PEAR::isError($result)) {
            return $result;
        }

        $this->in_transaction = false;

        return MDB2_OK;
    }

    // }}}
    // {{{ function _doQuery()

    /**
     * Execute a query
     * @param string $query  query
     * @param boolean $is_manip  if the query is a manipulation query
     * @param resource $connection
     * @param string $database_name
     * @return result or error object
     * @access protected
     */
    function &_doQuery($query, $is_manip = false,
                       $connection = null, $database_name = null)
    {
        $this->last_query = $query;
        $result = $this->debug($query, 'query',
                     array(
                         'is_manip' => $is_manip,
                         'when'     => 'pre'
                     )
        );

        if ($result) {
            if (PEAR::isError($result)) {
                return $result;
            }
            $query = $result;
        }

        if ($this->options['disable_query']) {
            if ($is_manip) {
                $result = 0;
            } else {
                $result = null;
            }
            return $result;
        }

        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        if ($this->connection->isManip($query)) {
            $result = $this->connection->exec($query);
        } else {
            $result = $this->connection->query($query);
        }

        if ($this->connection->isError()) {
            $errmsg = $this->connection->lastError();
            $err = & $this->raiseError(null, null, null,
                         'Could not execute statement: ' . $errmsg,
                          __FUNCTION__);
            return $err;
        }

        if (is_object($result)) {
            if (method_exists($result, 'fetchAll')) {
                $result = $result->fetchAll('assoc');
            } else {
                settype($result, 'array');
            }
        }

        if (!is_array($result) && is_numeric($result)) {
            $this->_affected_rows = $result;
        }

        if (is_array($result)) {
            $this->_affected_rows = 0;
            $this->_num_rows = count($result);
            if (is_array(reset($result))) {
                $this->_num_cols = count(reset($result));
            } else {
                $this->_num_cols = null;
            }
            $this->_row_count = 0;
        }

        $this->debug($query, 'query',
                      array(
                          'is_manip' => $is_manip,
                          'when'     => 'post',
                          'result'   => $result
                      )
        );

        return $result;
    }

    // }}}
    // {{{ function _affectedRows()

    /**
     * Returns the number of rows affected
     *
     * @param resource $result
     * @param resource $connection
     * @return mixed MDB2 Error Object or the number of rows affected
     * @access private
     */
    function _affectedRows($connection, $result = null)
    {
        return $this->_affected_rows;
    }

    // }}}
    // {{{ function affectedRows()

    /**
     * Returns the number of rows affected
     *
     * Note: This function is usage only of the Posql driver.
     *
     * @param void
     * @return mixed MDB2 Error Object or the number of rows affected
     * @access public
     */
    function affectedRows()
    {
        $affected = null;
        if (isset($this->_affected_rows)) {
            $affected = $this->_affected_rows;
        }
        return $affected;
    }

    // }}}
    // {{{ function _modifyQuery()

    /**
     * Changes a query string for various DBMS specific reasons
     *
     * @param string $query  query to modify
     * @param boolean $is_manip  if it is a DML query
     * @param integer $limit  limit the number of rows
     * @param integer $offset  start reading from given offset
     * @return string modified query
     * @access protected
     */
    function _modifyQuery($query, $is_manip, $limit, $offset)
    {
        return $query;
    }

    // }}}
    // {{{ function getServerVersion()

    /**
     * return version information about the server
     *
     * @param bool   $native  determines
     *                        if the raw version string should be returned
     * @return mixed array/string with version information
     *                            or MDB2 error object
     * @access public
     */
    function getServerVersion($native = false)
    {
        $server_info = false;

        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        if ($this->connected_server_info) {
            $server_info = $this->connected_server_info;
        } else if (!empty($this->options['server_version'])) {
            $server_info = $this->options['server_version'];
        } else {
            $server_info = $this->connection->getVersion();
        }

        if (!$server_info) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'Requires either the "server_version" option'
             .  ' or the Posql::getVersion() function', __FUNCTION__);
        }

        // cache server_info
        $this->connected_server_info = $server_info;
        if (!$native) {
            $tmp = explode('.', $server_info, 3);
            $server_info = array(
                'major'  => isset($tmp[0]) ? $tmp[0] : null,
                'minor'  => isset($tmp[1]) ? $tmp[1] : null,
                'patch'  => isset($tmp[2]) ? $tmp[2] : null,
                'extra'  => null,
                'native' => $server_info,
            );
        }
        return $server_info;
    }

    // }}}
    // {{{ function nextID($seq_name = null, $ondemand = true)

    /**
     * Returns the next free id of a sequence
     *
     * @param   string  name of the sequence
     * @param   bool    when true missing sequences are automatic created
     *
     * @return  mixed   MDB2 Error Object or id
     *
     * @access  public
     */
    function nextID($seq_name = null, $ondemand = true)
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        $result = null;
        $current_table = $this->connection->getTableName();
        if ($current_table != null) {
            $result = $this->connection->getNextId($current_table);
        }
        return $result;
    }

    // }}}
    // {{{ function lastInsertID()

    /**
     * Returns the autoincrement ID if supported or $id or fetches the current
     * ID in a sequence called: $table.(empty($field) ? '' : '_'.$field)
     *
     * @param string $table name of the table into which a new row was inserted
     * @param string $field name of the field into which a new row was inserted
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function lastInsertID($table = null, $field = null)
    {
        if (empty($this->connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                                    null, null,
                                    'Database is not connected', 
                                    __FUNCTION__);
        }

        $result = $this->connection->getLastInsertId();
        return $result;
    }
    // }}}
}

/**
 * MDB2 Posql result driver
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 */
class MDB2_Result_posql extends MDB2_Result_Common
{

    // }}}
    // {{{ function fetchRow()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param  int   $fetchmode  how the array data should be indexed
     * @param  int   $rownum     number of the row where the data can be found
     * @return int   data array on success, a MDB2 error on failure
     * @access public
     */
    function &fetchRow($fetchmode = MDB2_FETCHMODE_DEFAULT, $rownum = null)
    {
        if (!is_null($rownum)) {
            $seek = $this->seek($rownum);
            if (PEAR::isError($seek)) {
                return $seek;
            }
        }

        if (isset($this->db->_row_count)
         && isset($this->result[$this->db->_row_count])) {
            $row = $this->result[$this->db->_row_count];
        } else {
            $row = null;
        }

        $this->db->_row_count++;

        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            if (is_array($row)
             && ($this->db->options['portability'] & MDB2_PORTABILITY_FIX_CASE)
            ) {
                $row = array_change_key_case($row,
                           $this->db->options['field_case']);
            }
        }

        if (!$row) {
            if ($this->result === false) {
                $err = & $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA,
                         null, null,
                         'resultset has already been freed', __FUNCTION__);
                return $err;
            }
            $null = null;
            return $null;
        }

        $mode = $this->db->options['portability'] &
                           MDB2_PORTABILITY_EMPTY_TO_NULL;
        $rtrim = false;

        if ($this->db->options['portability'] & MDB2_PORTABILITY_RTRIM) {
            if (empty($this->types)) {
                $mode += MDB2_PORTABILITY_RTRIM;
            } else {
                $rtrim = true;
            }
        }
        if ($mode) {
            $this->db->_fixResultArrayValues($row, $mode);
        }
        if (!empty($this->types)) {
            $row = $this->db->datatype->convertResultRow($this->types,
                                                         $row, $rtrim);
        }
        if (!empty($this->values)) {
            $this->_assignBindColumns($row);
        }
        if ($fetchmode === MDB2_FETCHMODE_OBJECT) {
            $object_class = $this->db->options['fetch_class'];
            if (strtolower($object_class) === 'stdclass') {
                $row = (object) $row;
            } else {
                if (substr(phpversion(), 0, 1) >= 5) {
                    $row = new $object_class($row);
                } else {
                    $row = & new $object_class($row);
                }
            }
        }
        ++$this->rownum;
        return $row;
    }

    // }}}
    // {{{ function numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @access public
     * @return mixed integer value with the number of columns, a MDB2 error
     *                       on failure
     */
    function numCols()
    {
        $cols = null;
        if (isset($this->result)
         && is_array($this->result) && is_array(current($this->result))) {
            $cols = count(current($this->result));
        } else if (isset($this->db->_num_cols)) {
            $cols = $this->db->_num_cols;
        }

        if ($cols === null) {
            return $this->db->raiseError(null, null, null,
                   'Could not get columns count', __FUNCTION__);
        }

        return $cols;
    }
    // }}}
}

/**
 * MDB2 Posql buffered result driver
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 */
class MDB2_BufferedResult_posql extends MDB2_Result_posql
{

    // }}}
    // {{{ function valid()

    /**
     * Check if the end of the result set has been reached
     *
     * @return mixed true or false on sucess, a MDB2 error on failure
     * @access public
     */
    function valid()
    {
        $numrows = $this->numRows();
        if (PEAR::isError($numrows)) {
            return $numrows;
        }
        return $this->rownum < ($numrows - 1);
    }

    // }}}
    // {{{ function numRows()

    /**
     * Returns the number of rows in a result object
     *
     * @return mixed MDB2 Error Object or the number of rows
     * @access public
     */
    function numRows()
    {
        $rows = null;
        if (isset($this->result) && is_array($this->result)) {
            $rows = count($this->result);
        } else if (isset($this->db->_num_rows)) {
            $rows = $this->db->_num_rows;
        }

        if ($rows === null) {
            return $this->db->raiseError(null, null, null,
                   'Could not get row count', __FUNCTION__);
        }

        return $rows;
    }
    // }}}
}

/**
 * MDB2 Posql statement driver
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 */
class MDB2_Statement_posql extends MDB2_Statement_Common
{
}

// below, for the purpose is to emulate loadModule, __autoload().
// @see opening note of this script.
require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 Posql driver
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 */
class MDB2_Driver_Datatype_posql extends MDB2_Driver_Datatype_Common
{
}

require_once 'MDB2/Driver/Function/Common.php';

/**
 * MDB2 Posql driver for the function modules
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 */
class MDB2_Driver_Function_posql extends MDB2_Driver_Function_Common
{
}

// same up.
require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 Posql driver for the management modules
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 */
class MDB2_Driver_Manager_posql extends MDB2_Driver_Manager_Common
{
}

// same up.
require_once 'MDB2/Driver/Native/Common.php';

/**
 * MDB2 Posql driver for the native module
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 */
class MDB2_Driver_Native_posql extends MDB2_Driver_Native_Common
{
}

// same up.
require_once 'MDB2/Driver/Reverse/Common.php';

/**
 * MDB2 Posql driver for the schema reverse engineering module
 *
 * @package     Posql
 * @subpackage  PEAR/MDB2
 * @category    Database
 * @author      polygon planet <polygon_planet@yahoo.co.jp>
 */
class MDB2_Driver_Reverse_posql extends MDB2_Driver_Reverse_Common
{
}

// End of the modules of PEAR MDB2 driver for interacting with Posql database
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
