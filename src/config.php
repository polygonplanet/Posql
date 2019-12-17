<?php
/**
 * @package Posql
 *
 * Posql:
 *   The tiny text-base database engine (DBMS) written by pure PHP
 *   that does not need any additional extension library,
 *   it is designed compatible with SQL-92,
 *   and only uses all-in-one file as database.
 *
 * Supports the basic SQL-92 and SQL-99 syntax.
 * It is concluded by all one file as one database.
 * The database logic design does not use the file quite temporarily.
 * This database is inherited class from the MiniPod.
 *
 * PHP versions 4 and 5
 *
 * @author    Polygon Planet <polygon.planet.aqua@gmail.com>
 * @link      https://github.com/polygonplanet/Posql
 * @license   Dual licensed under the MIT and GPL v2 licenses
 * @copyright Copyright (c) 2010-2019 Polygon Planet
 *---------------------------------------------------------------------------*/
/**
 * A part of the Posql database format
 *
 * Line 1:
 * +------+------------------+
 * | line | 0123456789ABCDEF |
 * +------+------------------+
 * |    1 | Posql2.xx format | File header of Posql database (with Version)
 * +------+------------------+
 * |    1 | <?php exit;__hal | the dummy code as PHP --------
 * +------+------------------+
 * |    1 | t_compiler();?># | --- for guard on direct access
 * +------+------------------+
 * |    1 | LOCK=0@00000000  | the lock statement of database (as all tables)
 * +------+------------------+
 *
 * @note
 *       LOCK ::= flock_flags "@" instance_id_of_locked_object [LF]
 *               e.g. LOCK=2@0b276c0f
 * ---------------------------------------------------------------------
 *  LOCK MODE:
 *
 *   0 : Not locked
 *   1 : Locked for reading (enables reading, disables writing)
 *   2 : Locked for writing (disables reading, and writing)
 * ---------------------------------------------------------------------
 * Line 2:
 *  Each information on the table is stored in the second line
 *
 * Example:
 * +--------+----------------------------------------+
 * | line 2 | Zm9v:1@a5c4fe49#0000000004@1235752053; | + [LF]
 * +--------+----------------------------------------+
 *
 *  This example is composed like below.
 * +------------+-----------+----------+------------------+---------------+
 * | table name | lock mode | lock id  | next sequence id | modified time |
 * +------------+-----------+----------+------------------+---------------+
 * |  (base64)  |   0 - 2   |  crc32   |  1 - 9999999999  |  unix time()  |
 * +------------+-----------+----------+------------------+---------------+
 * |    Zm9v    :     0     @ a5c4fe49 #    0000000004    @   1235752053  ;
 * +------------+-----------+----------+------------------+---------------+
 *
 * ---------------------------------------------------------------------
 * Line 3:
 *   Meta data field by srialized base64 format.
 * ---------------------------------------------------------------------
 * Line 4..EOF
 *   Data rows actually handled.
 * ---------------------------------------------------------------------
 */
/**
 * @name Posql_Config
 *
 * The class to configure the variables for Posql
 *
 * @package   Posql
 * @author    Polygon Planet <polygon.planet.aqua@gmail.com>
 */
class Posql_Config {

  /**
   * @var    string   maintains file path of database
   * @access public
   */
  var $path = '';

  /**
   * @var    mixed    function name of serializer
   * @access public
   */
  var $encoder = 'serialize';

  /**
   * @var    mixed    function name of unserializer
   * @access public
   */
  var $decoder = 'unserialize';

  /**
   * @var    array    the default functions list of calls disable in expression
   * @link   http://www.phpbuilder.com/manual/features.safe-mode.functions.php
   * @access public
   */
  var $disableFuncs = array(
    'goto',
    'declare',
    'eval',
    'new',
    'global',
    'include',
    'include_once',
    'require',
    'require_once',
    'echo',
    'print',
    'printf',
    'vprintf',
    'flush',
    'var_export',
    'print_r',
    'var_dump',
    'exit',
    'die',
    'phpinfo',
    'header',
    'show_source',
    'highlight_file',
    'highlight_string',
    'php_check_syntax',
    'ini_get_all',
    'php_ini_scanned_files',
    'ini_set',
    'ini_alter',
    'ini_get',
    'get_cfg_var',
    'get_defined_functions',
    'get_defined_vars',
    'dl',
    'shell_exec',
    'exec',
    'suexec',
    'system',
    'passthru',
    'popen',
    'proc_open',
    'fopen',
    'curl_exec',
    'pcntl_exec',
    'm_connect',
    'm_initconn',
    'fsockopen',
    'pfsockopen',
    'mkdir',
    'rmdir',
    'rename',
    'unlink',
    'copy',
    'chgrp',
    'chown',
    'chmod',
    'touch',
    'symlink',
    'link',
    'move_uploaded_file',
    'chdir',
    'chroot',
    'scandir',
    'glob',
    'readfile',
    'setcookie',
    'setrawcookie',
    'set_include_path',
    'set_time_limit',
    'putenv',
    'dbmopen',
    'dbase_open',
    'filepro',
    'filepro_rowcount',
    'filepro_retrieve',
    'pg_lo_import',
    'posix_mkfifo',
    'posix_kill',
    'posix_setuid',
    'ftp_connect',
    'mail',
    'mb_send_mail',
    'call_user_func',
    'call_user_func_array',
    'call_user_method',
    'call_user_method_array',
    'create_function',
    'register_shutdown_function',
    'register_tick_function',
    'preg_replace',
    'preg_replace_callback',
    'debug_backtrace',
    'debug_print_backtrace',
    'virtual',
    'apache_request_headers',
    'apache_child_terminate',
    'apache_lookup_uri',
    'array_push',
    'array_unshift',
    'array_splice'
  );

  /**
   * @var    string   Operation mode of SQL Expression
   *                  +--------------------------------------------
   *                  | sql: standard SQL syntax support
   *                  +--------------------------------------------
   *                  | php: using PHP's syntax on all expression
   *                  +--------------------------------------------
   * @access public
   */
  var $engine = 'sql';

  /**
   * @var    boolean  enables executes vacuum() calls automatic when shutdown
   * @access public
   */
  var $autoVacuum = true;

  /**
   * @var    boolean  whether "=" to convert into "==" automatically or not
   *                  e.g. "SELECT ... WHERE foo = 1"
   *                       will convert to
   *                       "SELECT ... WHERE foo == 1"
   *                  when operating it in the PHP mode{@see $engine},
   *                  this is important
   *                  (default = true : auto convert it)
   *
   * @access public
   */
  var $autoAssignEquals = true;

  /**
   * @var    number   enables auto increment "rowid" or number of that value
   * @access public
   */
  var $auto_increment = 1;

  /**
   * @var    number   alias of auto_increment
   * @access public
   */
  var $autoIncrement;

  /**
   * @var    boolean  enables exclusive lock to database.
   *                  This value will dynamically become TRUE
   *                  when colliding with other processes.
   * @access public
   */
  var $autoLock = false;

  /**
   * @var    string   the type for supplements which omitted data type
   *                  on CREATE TABLE command (e.g. "CREATE TABLE t (a, b, c)")
   * @access public
   */
  var $defaultDataType = 'text';

  /**
   * @var    string   internal character-code (default = UTF-8)
   * @access public
   */
  var $charset = 'UTF-8';

  /**
   * @var    string   file extension as database
   * @access public
   */
  var $ext = 'php';

  /**
   * @var    number   the maximal minutes as timeout for the dead lock
   *                  default = 10 minutes.
   * @access public
   */
  var $deadLockTimeout = 10;

  /**
   * @var    number   max size (bytes) of associable all
   *                  default = 0x7FFFFF00 (2147483392)
   * @access public
   */
  var $MAX = 0x7FFFFF00;

  /**
   * @var    string   separator of row and row (LF: 0x0A)
   * @access private  **MUST NOT MODIFY**
   */
  var $NL = "\x0A";

  /**
   * @var    number   the state of lock (not locked)
   * @access private  **MUST NOT MODIFY**
   */
  var $LOCK_NONE = 0;

  /**
   * @var    number   the state of lock (locked for reading)
   * @access private  **MUST NOT MODIFY**
   */
  var $LOCK_SH = 1;

  /**
   * @var    number   the state of lock (locked for writing)
   * @access private  **MUST NOT MODIFY**
   */
  var $LOCK_EX = 2;

  /**
   * @var    string   the delimiter for table
   * @access private  **MUST NOT MODIFY**
   */
  var $DELIM_TABLE = ':';

  /**
   * @var    string   the delimiter for query cache
   * @access private  **MUST NOT MODIFY**
   */
  var $DELIM_CACHE = '#';

  /**
   * @var    string   the delimiter for index
   * @access private  **MUST NOT MODIFY**
   */
  var $DELIM_INDEX = '|';

  /**
   * @var    string   the delimiter for view
   * @access private  **MUST NOT MODIFY**
   */
  var $DELIM_VIEW = '*';

  /**
   * @var    array    maintains the User Defined Function(UDF)s
   * @access private
   */
  var $UDF = array();

  /**
   * @var    array    cache of the memory
   * @access private
   */
  var $meta = array(array());

  /**
   * @var    string   maintains current table name
   * @access private
   */
  var $tableName = '';

  /**
   * @var    array    storage of the error messages
   * @access private
   */
  var $errors = array();

  /**
   * @var    number   maintains next row count
   * @access private
   */
  var $next = 0;

  /**
   * @var    string   maintains the unique id of this object
   * @access private
   */
  var $id;

  //
  // @var    string   maintains the current schema (e.g. "index" or "table")
  // @access private
  //
  // var $currentSchema;

  /**
   * @var    boolean  whether to use the query cache
   * @access private
   */
  var $useQueryCache = false;

  /**
   * @var    number   maximum row number of the query cache.
   *                  (default = 1024).
   *                  (i.e. 1024 rows or less are used for cache.)
   * @access private
   */
  var $queryCacheMaxRows = 1024;

  /**
   * @var    boolean  flag of delete() or update()
   * @access private
   */
  var $_inDelete;

  /**
   * @var    mixed    temporary variable for some methods
   * @access private
   */
  var $_curMeta;

  /**
   * @var    string   temporary variable for specifying the primary key
   * @access private
   */
  var $_primaryKey;

  /**
   * @var    string   temporary variable for getting created definition
   * @access private
   */
  var $_createQuery;

  /**
   * @var    mixed    temporary variable for expression
   * @access private
   */
  var $_execExpr;

  /**
   * @var    boolean  temporary variable for checking expression
   * @access private
   */
  var $_checkOnlyExpr;

  /**
   * @var    array    temporary variable for JOIN expression
   * @access private
   */
  var $_extraJoinExpr = array();

  /**
   * @var    boolean  temporary variable for IN operator
   * @access private
   */
  var $_notInOp;

  /**
   * @var    string   temporary variable for "IF [NOT] EXISTS" clause
   *                  the maintained value is
   *                    "if_not_exists", "if_exists" or NULL
   * @access private
   */
  var $_ifExistsClause;

  /**
   * @var    number   temporary variable for CASE, WHEN, THEN, END expression
   * @access private
   */
  var $_caseCount;

  /**
   * @var    array    temporary variable for SELECT-List expression
   * @access private
   */
  var $_selectExprCols = array();

  /**
   * @var    mixed    storage for the tablename of the most recent result
   * @access private
   */
  var $_selectTableNames;

  /**
   * @var    array    temporary variable for alias names of FROM clause
   * @access private
   */
  var $_selectTableAliases = array();

  /**
   * @var    array    temporary variable for alias names of SELECT-List
   * @access private
   */
  var $_selectColumnAliases = array();

  /**
   * @var    array    temporary variable for column names on SELECT using UNION
   * @access private
   */
  var $_unionColNames = array();

  /**
   * @var    boolean  temporary variable to parse the alias names
   * @access private
   */
  var $_onParseAliases;

  /**
   * @var    boolean  temporary variable for SELECT command as Dual
   * @access private
   */
  var $_isDualSelect;

  /**
   * @var    boolean  temporary variable for SELECT query using UNION
   * @access private
   */
  var $_onUnionSelect;

  /**
   * @var    boolean  temporary variable for CREATE command
   * @access private
   */
  var $_onCreate;

  /**
   * @var    boolean  temporary variable for JOIN clause
   * @access private
   */
  var $_onJoinParseExpr;

  /**
   * @var    boolean  temporary variable for multipartite SELECT command
   * @access private
   */
  var $_onMultiSelect;

  /**
   * @var    boolean  temporary variable for HAVING clause
   * @access private
   */
  var $_onHaving;

  /**
   * @var    string   maintains the internal correlation prefix for SELECT
   * @access private
   */
  var $_correlationPrefix;

  /**
   * @var    array    temporary variable for HAVING clause
   * @access private
   */
  var $_uniqueColsByHaving;

  /**
   * @var    boolean  temporary variable for prepared-statements
   * @access private
   */
  var $_fromStatement;

  /**
   * @var    boolean  temporary variable for sub-query
   * @access private
   */
  var $_fromSubSelect;

  /**
   * @var    boolean  temporary variable for the query cache
   * @access private
   */
  var $_fromQueryCache;

  /**
   * @var    array    temporary variable for the list of subqueries table
   * @access private
   */
  var $_subSelectMeta = array();

  /**
   * @var    array    temporary variable for subquery using JOIN
   * @access private
   */
  var $_subSelectJoinInfo = array();

  /**
   * @var    array    temporary variable for unique name of subquery using JOIN
   * @access private
   */
  var $_subSelectJoinUniqueNames = array();

  /**
   * @var    boolean  temporary variable for distinction of the SQL statement
   * @access private
   */
  var $_useQuery;

  /**
   * @var    boolean  temporary variable for getting the meta data
   * @access private
   */
  var $_getMetaAll;

  /**
   * @var    boolean  temporary variable for transaction status
   * @access private
   */
  var $_inTransaction;

  /**
   * @var    string   temporary variable for transaction name
   * @access private
   */
  var $_transactionName;

  /**
   * @var    array    temporary variable to lock the tables
   * @access private
   */
  var $_lockedTables = array();

  /**
   * @var    boolean  whether initialized or not
   * @access private
   */
  var $_inited;

  /**
   * @var    boolean  whether the termination method was registered or not
   * @access private
   */
  var $_registered;

  /**
   * @var    boolean  whether the termination method was called or not
   * @access private
   */
  var $_terminated;

  /**
   * @var    boolean  whether OS is WINDOWS or not
   * @access private
   */
  var $isWin;

  /**
   * @var    boolean  whether PHP VERSION is over 5 or not
   * @access private
   */
  var $isPHP5;

  /**
   * @var    boolean  whether or not PCRE supports UTF-8
   * @access private
   */
  var $supportsUTF8PCRE;

  /**
   * @var    string   maintains the method of last query
   * @access private
   */
  var $lastMethod;

  /**
   * @var    string   maintains last SQL query
   * @access private
   */
  var $lastQuery;

  /**
   * @var    boolean  maintains old value of ignore_user_abort()
   * @access private
   */
  var $userAbort = true;

  /**
   * @var    Posql_Math      maintains instance of the Posql_Math class
   * @access public
   */
  var $math;

  /**
   * @var    Posql_CType     maintains instance of the Posql_CType class
   * @access public
   */
  var $ctype;

  /**
   * @var    Posql_Charset   maintains instance of the Posql_Charset class
   * @access public
   */
  var $pcharset;

  /**
   * @var    Posql_Unicode   maintains instance of the Posql_Unicode class
   * @access public
   */
  var $unicode;

  /**
   * @var    Posql_ECMA      maintains instance of the Posql_ECMA class
   * @access public
   */
  var $ecma;

  /**
   * @var    Posql_Archive   maintains instance of the Posql_Archive class
   * @access public
   */
  var $archive;

  /**
   * @var    Posql_Method    maintains instance of the Posql_Method class
   * @access public
   */
  var $method;

  /**
   * @var    string   versions of this class
   * @access public   **READ ONLY**
   */
  var $version = '2.17';

  /**
   * @access public
   */
  function getEncoder() {
    return $this->encoder;
  }

  /**
   * @access public
   */
  function getDecoder() {
    return $this->decoder;
  }

  /**
   * @access public
   */
  function getExt() {
    return $this->ext;
  }

  /**
   * @access public
   */
  function getMax() {
    return $this->MAX;
  }

  /**
   * @access public
   */
  function getDeadLockTimeout() {
    return $this->deadLockTimeout;
  }

  /**
   * @access public
   */
  function setAutoVacuum($auto_vacuum) {
    $this->autoVacuum = (bool)$auto_vacuum;
  }

  /**
   * @access public
   */
  function getAutoVacuum() {
    return $this->autoVacuum;
  }

  /**
   * @access public
   */
  function setAutoAssignEquals($auto_assign_equals) {
    $this->autoAssignEquals = (bool)$auto_assign_equals;
  }

  /**
   * @access public
   */
  function getAutoAssignEquals() {
    return $this->autoAssignEquals;
  }

  /**
   * @access public
   */
  function getAutoIncrement() {
    return $this->autoIncrement;
  }

  /**
   * @access public
   */
  function getAutoLock() {
    return $this->autoLock;
  }

  /**
   * Set value whether to enable exclusive lock.
   * This value will dynamically become TRUE
   *  when colliding with other processes.
   *
   * Note:
   *   autoLock should be enabled excluding Windows.
   *   Maybe, very slows when locking database by using mkdir() on Windows.
   *
   * @param  boolean   value whether to enable exclusive lock
   * @return void
   * @access public
   */
  function setAutoLock($auto_lock) {
    $this->autoLock = (bool)$auto_lock;
  }

  /**
   * @access public
   */
  function getDefaultDataType() {
    return $this->defaultDataType;
  }

  /**
   * @access public
   */
  function setCharset($charset) {
    $this->charset = (string)$charset;
  }

  /**
   * @access public
   */
  function getCharset() {
    return $this->charset;
  }

  /**
   * @access public
   */
  function getLastMethod() {
    return $this->lastMethod;
  }

  /**
   * @access public
   */
  function getLastQuery() {
    return $this->lastQuery;
  }

  /**
   * @access public
   */
  function setUseQueryCache($use_query_cache) {
    $this->useQueryCache = (bool)$use_query_cache;
  }

  /**
   * @access public
   */
  function getUseQueryCache() {
    return $this->useQueryCache;
  }

  /**
   * @access public
   */
  function setQueryCacheMaxRows($query_cache_max_rows) {
    $this->queryCacheMaxRows = $query_cache_max_rows - 0;
  }

  /**
   * @access public
   */
  function getQueryCacheMaxRows() {
    return $this->queryCacheMaxRows;
  }
}
