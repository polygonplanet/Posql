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
 * @author    polygon planet <polygon.planet@gmail.com>
 * @link      http://sourceforge.jp/projects/posql/
 * @link      http://sourceforge.net/projects/posql/
 * @link      http://posql.org/
 * @license   Dual licensed under the MIT and GPL licenses
 * @copyright Copyright (c) 2011 polygon planet
 * @version   $Id: posql.php,v 2.18a 2011/12/12 01:26:52 polygon Exp $
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
 * @name Posql_Object
 *
 * The base object class for Posql
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Object {

/**
 * Deprecated constructor
 *
 * @param  void
 * @access public
 */
 function Posql_Object() {
   $args = func_get_args();
   call_user_func_array(array(&$this, '__construct'), $args);
 }

/**
 * Common constructor
 *
 * @param  void
 * @access public
 */
 function __construct() {
   if ((int)PHP_VERSION < 5 && method_exists($this, '__destruct')) {
     register_shutdown_function(array(&$this, '__destruct'));
   }
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Config
 *
 * The class to configure the variables for Posql
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Config extends Posql_Object {

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
 *  ------------------------------------------
 *  sql: standard SQL syntax support
 *  ------------------------------------------
 *  php: using PHP's syntax on all expression
 *  ------------------------------------------
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
 *  e.g. "SELECT ... WHERE foo = 1"
 *        will convert to
 *       "SELECT ... WHERE foo == 1"
 *  when operating it in the PHP mode{@see $engine}, this is important
 *  (default = true : auto convert it)
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
 *                  default = 0x7fffff00 (2147483392)
 * @access public
 */
 var $MAX = 0x7fffff00;

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

/**
 * @var    string   maintains the current schema (e.g. "index" or "table")
 * @access private
 */
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
 * @var    array    the invalid statements in expression
 * @access private
 */
 var $_invalidStatements = array(
   'goto'         => 1,
   'declare'      => 1,
   'eval'         => 1,
   'new'          => 1,
   'global'       => 1,
   'include'      => 1,
   'include_once' => 1,
   'require'      => 1,
   'require_once' => 1,
   'echo'         => 1,
   'print'        => 1,
   'exit'         => 1,
   'die'          => 1
 );

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
 * @var    Posql_Math   maintains instance of the Posql_Math class
 * @access public
 */
 var $math;

/**
 * @var    Posql_CType   maintains instance of the Posql_CType class
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
 * @var    Posql_ECMA   maintains instance of the Posql_ECMA class
 * @access public
 */
 var $ecma;

/**
 * @var    Posql_Archive   maintains instance of the Posql_Archive class
 * @access public
 */
 var $archive;

/**
 * @var    Posql_Method   maintains instance of the Posql_Method class
 * @access public
 */
 var $method;

/**
 * @var    string   versions of this class
 * @access public   **READ ONLY**
 */
 var $version = '2.18a';

//-----------------------------------------------------------------------------
/**
 * @access public
 */
 function getEncoder(){
   return $this->encoder;
 }

/**
 * @access public
 */
 function getDecoder(){
   return $this->decoder;
 }

/**
 * @access public
 */
 function getExt(){
   return $this->ext;
 }

/**
 * @access public
 */
 function getMax(){
   return $this->MAX;
 }

/**
 * @access public
 */
 function getDeadLockTimeout(){
   return $this->deadLockTimeout;
 }

/**
 * @access public
 */
 function setAutoVacuum($auto_vacuum){
   $this->autoVacuum = (bool) $auto_vacuum;
 }

/**
 * @access public
 */
 function getAutoVacuum(){
   return $this->autoVacuum;
 }

/**
 * @access public
 */
 function setAutoAssignEquals($auto_assign_equals){
   $this->autoAssignEquals = (bool) $auto_assign_equals;
 }

/**
 * @access public
 */
 function getAutoAssignEquals(){
   return $this->autoAssignEquals;
 }

/**
 * @access public
 */
 function getAutoIncrement(){
   return $this->autoIncrement;
 }

/**
 * @access public
 */
 function getAutoLock(){
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
 function setAutoLock($auto_lock){
   $this->autoLock = (bool)$auto_lock;
 }

/**
 * @access public
 */
 function getDefaultDataType(){
   return $this->defaultDataType;
 }

/**
 * @access public
 */
 function setCharset($charset){
   $this->charset = (string) $charset;
 }

/**
 * @access public
 */
 function getCharset(){
   return $this->charset;
 }

/**
 * @access public
 */
 function getLastMethod(){
   return $this->lastMethod;
 }

/**
 * @access public
 */
 function getLastQuery(){
   return $this->lastQuery;
 }

/**
 * @access public
 */
 function setUseQueryCache($use_query_cache){
   $this->useQueryCache = (bool)$use_query_cache;
 }

/**
 * @access public
 */
 function getUseQueryCache(){
   return $this->useQueryCache;
 }

/**
 * @access public
 */
 function setQueryCacheMaxRows($query_cache_max_rows){
   $this->queryCacheMaxRows = $query_cache_max_rows - 0;
 }

/**
 * @access public
 */
 function getQueryCacheMaxRows(){
   return $this->queryCacheMaxRows;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_CType
 *
 * Compat ctype_*
 *
 * POSIX CHARACTER CLASSES
 *
 * This class emulate the "character type" extension,
 *   which is present in PHP first since version 4.3 bundled
 * The default ctype extension that is behavior is different
 *   according to argument that are STRING and INTEGER
 * -----
 * Note:
 * -------------------------------------------------------------------
 *   When called with an empty string
 *   the result will always be TRUE in PHP < 5.1 and FALSE since 5.1.
 * -------------------------------------------------------------------
 * {@see http://www.php.net/manual/en/intro.ctype.php}
 *
 * To clarify this difference, this class handle only "STRING"
 *
 * @link     http://php.net/ctype
 * @link     http://www.pcre.org/pcre.txt
 * @package  Posql
 * @author   polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_CType extends Posql_Object {

/**
 * @var    boolean   maintains ctype library can be use
 * @access private
 */
 var $hasCType;

/**
 * Class constructor
 *
 * @param  void
 * @return object
 * @access public
 */
 function __construct(){
   parent::__construct();
   $this->hasCType = extension_loaded('ctype');
 }

/**
 * alnum    letters and digits
 */
 function isAlnum($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_alnum($text);
     } else {
       $result = !preg_match('{[[:^alnum:]]}', $text);
     }
   }
   return $result;
 }

/**
 * alpha    letters
 */
 function isAlpha($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_alpha($text);
     } else {
       $result = !preg_match('{[[:^alpha:]]}', $text);
     }
   }
   return $result;
 }

/**
 * cntrl    control characters
 */
 function isCntrl($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_cntrl($text);
     } else {
       $result = !preg_match('{[[:^cntrl:]]}', $text);
     }
   }
   return $result;
 }

/**
 * digit    decimal digits (same as \d)
 */
 function isDigit($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_digit($text);
     } else {
       $result = !preg_match('{[[:^digit:]]}', $text);
     }
   }
   return $result;
 }

/**
 * graph    printing characters, excluding space
 */
 function isGraph($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_graph($text);
     } else {
       $result = !preg_match('{[[:^graph:]]}', $text);
     }
   }
   return $result;
 }

/**
 * lower    lower case letters
 */
 function isLower($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_lower($text);
     } else {
       $result = !preg_match('{[[:^lower:]]}', $text);
     }
   }
   return $result;
 }

/**
 * print    printing characters, including space
 */
 function isPrint($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_print($text);
     } else {
       $result = !preg_match('{[[:^print:]]}', $text);
     }
   }
   return $result;
 }

/**
 * punct    printing characters, excluding letters and digits
 */
 function isPunct($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_punct($text);
     } else {
       $result = !preg_match('{[[:^punct:]]}', $text);
     }
   }
   return $result;
 }

/**
 * space    white space (not quite the same as \s)
 */
 function isSpace($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_space($text);
     } else {
       $result = !preg_match('{[[:^space:]]}', $text);
     }
   }
   return $result;
 }

/**
 * upper    upper case letters
 */
 function isUpper($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_upper($text);
     } else {
       $result = !preg_match('{[[:^upper:]]}', $text);
     }
   }
   return $result;
 }

/**
 * xdigit   hexadecimal digits
 */
 function isXdigit($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     if ($this->hasCType) {
       $result = ctype_xdigit($text);
     } else {
       $result = !preg_match('{[[:^xdigit:]]}', $text);
     }
   }
   return $result;
 }

// ---------------------------------------------
// the Perl, GNU, and PCRE extensions are below
// ---------------------------------------------
/**
 * ascii    character codes 0 - 127
 */
 function isAscii($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     $result = !preg_match('{[[:^ascii:]]}', $text);
   }
   return $result;
 }

/**
 * blank    space or tab only
 */
 function isBlank($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     $result = !preg_match('{[[:^blank:]]}', $text);
   }
   return $result;
 }

/**
 * word     "word" characters (same as \w)
 */
 function isWord($text){
   $result = false;
   if (is_string($text) && $text !== '') {
     $result = !preg_match('{[[:^word:]]}', $text);
   }
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Math
 *
 * The math operator class that to using for big integer
 * If available it will use the BCMath library
 * Will default to the standard PHP integer representation otherwise
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Math extends Posql_Object {

/**
 * @var    boolean   maintains BCMath library can be use
 * @access private
 */
 var $hasbc;

/**
 * @var    boolean   temporary variable whether the function called at inside
 * @access private
 */
 var $_innerCall;

/**
 * Class constructor
 *
 * @param  void
 * @return object
 * @access public
 */
 function __construct(){
   parent::__construct();
   $this->hasbc = extension_loaded('bcmath');
 }

/**
 * Format the value for plain numbers
 *
 * @param  number  the number of target
 * @param  number  optionally, the number of digits for floating-point numbers
 * @param  string  optionally, to use sprintf()'s format
 * @return number  formatted numeric value
 * @access public
 */
 function format($number, $float = null, $sprintf = null){
   static $dot = '.', $zero = '0', $sups = array('0', '0');
   $result = $zero;
   $length = strlen($number);
   if ($length > 0) {
     $number = trim($number);
     $sign = substr($number, 0, 1);
     if ($sign === '-' || $sign === '+') {
       $number = substr($number, 1);
       --$length;
       if ($sign === '+') {
         $sign = '';
       }
     } else {
       $sign = '';
     }
     if ($this->isDec($number)) {
       $number = (string)$number;
     } else if ($this->isHex($number)) {
       $this->_innerCall = true;
       $number = $this->toHex($number);
       $number = $this->convertToBase($number, 16, 10);
       $this->_innerCall = null;
     } else if (is_numeric($number)) {
       $number = (string)($number - 0);
     } else {
       $number = sprintf('%.0f', $number);
     }
     if ($float !== null && $float > 0) {
       $float = (int)$float;
       $format = '%0' . $float . $dot . $float . 's';
       $numbers = explode($dot, $number) + $sups;
       if (count($numbers) > 2) {
         $numbers = array_slice($numbers, 0, 2);
       }
       if (!isset($numbers[1])) {
         $numbers[1] = $zero;
       }
       $numbers[1] = sprintf($format, $numbers[1]);
       $number = implode($dot, $numbers);
     }
     $result = $sign . ltrim($number);
     if ($float !== null
      && (($float - 0) === 0) && strpos($result, $dot) !== false) {
       $result = rtrim($result, $zero);
     }
     if (substr($result, -1) === $dot) {
       $result = substr($result, 0, -1);
     }
   }
   if ($sprintf != null) {
     $result = sprintf($sprintf, $result);
   }
   return $result;
 }

/**
 * A handy function which was customized
 *  by base_convert() function for big scale integers.
 * The maximum base number is 62.
 * The base number '0' will be not converted.
 * It is same as base_convert() function usage.
 *
 * @example
 * <code>
 * $func = "convertToBase";
 * $value = "FFFFFFFF"; $from = 16; $to = 10;
 * printf("<p>%s(<em>%s</em>, %d, %d);<br> = %s</p>",
 *        $func, $value, $from, $to,
 *        Posql_Math::convertToBase($value, $from, $to));
 * $value = "9223372036854775807"; $from = 10; $to = 16;
 * printf("<p>%s(<em>%s</em>, %d, %d);<br> = %s</p>",
 *        $func, $value, $from, $to,
 *        Posql_Math::convertToBase($value, $from, $to));
 * $value = "1101010001001101101001110111110110011001101101100111001101";
 * $from = 2; $to = 62;
 * printf("<p>%s(<em>%s</em>, %d, %d);<br> = %s</p>",
 *        $func, $value, $from, $to,
 *        Posql_Math::convertToBase($value, $from, $to));
 * </code>
 *
 * @param  number  the numeric or alphameric value of target
 * @param  number  the base number is in
 * @param  number  the base to convert number to
 * @return string  the numbers of result which was converted to base to base
 * @access public
 * @static
 */
 function convertToBase($number, $frombase, $tobase){
   static $bases =
   '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
   $result = '';
   if (isset($this) && empty($this->_innerCall)) {
     $number = $this->format($number, 0);
   }
   $length = strlen($number);
   $frombase = (int)$frombase;
   $tobase = (int)$tobase;
   if ($frombase > 0 && $frombase < 63
    && $tobase   > 0 && $tobase   < 63) {
     $numbers = array();
     for ($i = 0; $i < $length; $i++) {
       $pos = strpos($bases, substr($number, $i, 1));
       if ($pos === false) {
         $result = false;
         break;
       }
       $numbers[$i] = $pos;
     }
     if ($result !== false) {
       do {
         $divide = 0;
         $index  = 0;
         for ($i = 0; $i < $length; ++$i) {
           $divide = $divide * $frombase + $numbers[$i];
           if ($divide >= $tobase) {
             $numbers[$index++] = (int)($divide / $tobase);
             $divide = $divide % $tobase;
           } else if ($index > 0) {
             $numbers[$index++] = 0;
           }
         }
         $length = $index;
         $result = substr($bases, $divide, 1) . $result;
       } while ($index !== 0);
     }
   }
   return $result;
 }

/**
 * Hexadecimal to decimal
 * A handle hex to decimal converter.
 * If bcmath library available, it will be use.
 * Otherwise, it will be to use the precision of PHP floats.
 *
 * @param  number  the number of target
 * @param  boolean whether it is checked strictly as hexadecimal number
 * @return string  the number of result as decimal numbers, or '0'
 * @access public
 */
 function hexdec($number, $strict = false){
   $result = '0';
   $length = strlen($number);
   if ($length > 1
    && (!$strict || $this->isHex($number))) {
     if ($length >= 8) {
       $dec = 0;
       $bit = 1;
       if ($this->hasbc) {
         for ($pos = 1; $pos <= $length; ++$pos) {
           $sub = substr($number, (-$pos), 1);
           $dec = bcadd($dec, bcmul(hexdec($sub), $bit));
           $bit = bcmul($bit, 16);
         }
         $result = sprintf('%s', $dec);
       } else {
         for ($pos = 1; $pos <= $length; ++$pos) {
           $sub = substr($number, (-$pos), 1);
           $dec += hexdec($sub) * $bit;
           $bit *= 16;
         }
         $result = sprintf('%.0f', $dec);
       }
     } else {
       $result = sprintf('%.0f', hexdec($number));
     }
     if (ord($result) === 0x20) {
       $result = ltrim($result);
     }
   }
   return $result;
 }

/**
 * Check whether the numbers of argument is consist of decimal number
 *
 * @param  number  the number of target
 * @return boolean whether the numbers had consisted of decimal number
 * @access public
 */
 function isDec($number){
   static $mask = '0123456789.';
   $result = false;
   $length = strlen($number);
   if ($length > 0) {
     $ord = ord($number);
     if ($ord === 0x2B    // +  plus  sign
      || $ord === 0x2D) { // -  minus sign
       $number = substr($number, 1);
       --$length;
     }
     if (strspn($number, $mask) === $length) {
       $result = true;
     }
   }
   return $result;
 }

/**
 * Check whether the numbers of argument is consist of hexadecimal number
 *
 * @param  number  the number of target
 * @return boolean whether the numbers had consisted of hexadecimal number
 * @access public
 */
 function isHex($number){
   static $mask = 'abcdef0123456789';
   $result = false;
   $length = strlen($number);
   if ($length > 0) {
     $number = strtolower($number);
     $pos = strpos($number, 'x');
     if ($pos !== false && $pos >= 0 && $pos <= 2) {
       $number = substr($number, $pos + 1);
       $length = strlen($number);
     }
     if (strspn($number, $mask) === $length) {
       $result = true;
     }
   }
   return $result;
 }

/**
 * Return it if the given numerical value is a hexadecimal number
 *
 * @param  number   the number of target
 * @param  boolean  whether the result of all lowercase or all uppercase
 * @return string   the hexadecimal numbers, or '0'
 * @access public
 */
 function toHex($number, $tolower = false){
   $result = '0';
   if ($this->isHex($number)) {
     $number = strtoupper($number);
     $pos = strpos($number, 'X');
     if ($pos !== false) {
       $number = substr($number, $pos + 1);
     }
     if ($tolower) {
       $result = strtolower($number);
     } else {
       $result = $number;
     }
   }
   return $result;
 }

/**
 * Returns the number of digits for floating-point numbers.
 * If not found, NULL will be returned.
 *
 * @param  number (...)  the one or some arguments of numbers
 * @return number        the number of floating-point, or NULL
 * @access public
 */
 function getScale($op){
   static $dot = '.';
   $result = null;
   $argn = func_num_args();
   if ($argn > 1) {
     $args = func_get_args();
     $max_scale = 0;
     foreach ($args as $op) {
       $scale = strstr($op, $dot);
       if ($scale && strlen($scale) > $max_scale) {
         $max_scale = strlen($scale) - 1;
       }
     }
     if ($max_scale > 0) {
       $result = $max_scale;
     }
   } else {
     $scale = strstr($op, $dot);
     if (strlen($scale) > 1) {
       $result = strlen($scale) - 1;
     }
   }
   return $result;
 }

/**
 * Add two numbers: $op1 + $op2
 *
 * @access public
 */
 function add($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcadd($op1, $op2, $scale);
   } else {
     $result = ($op1 - 0) + ($op2 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Substract two numbers: $op1 - $op2
 *
 * @access public
 */
 function sub($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcsub($op1, $op2, $scale);
   } else {
     $result = ($op1 - 0) - ($op2 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Multiply two numbers: $op1 * $op2
 *
 * @access public
 */
 function mul($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcmul($op1, $op2, $scale);
   } else {
     $result = ($op1 - 0) * ($op2 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Divide two numbers: $op1 / $op2
 *
 * @access public
 */
 function div($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     if ($op2 != 0) {
       $result = bcdiv($op1, $op2, $scale);
     }
   } else {
     if ($op2 != 0) {
       $result = ($op1 - 0) / ($op2 - 0);
     }
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Calculate the modulus of $op1 and $op2: $op1 % $op2
 *
 * @access public
 */
 function mod($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcmod($op1, $op2);
   } else {
     $result = ($op1 - 0) % ($op2 - 0);
   }
   $result = $this->format($result);
   return $result;
 }

/**
 * Raise $op1 to the $op2 exponent: $op1 ^ $op2
 *
 * @access public
 */
 function pow($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcpow($op1, $op2, $scale);
   } else {
     $result = pow($op1 - 0, $op2 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Compare two numbers
 *
 * @return int
 * -------------------
 *  $op1 >  $op2 :  1
 *  $op1 == $op2 :  0
 *  $op1 <  $op2 : -1
 * -------------------
 * @access public
 */
 function comp($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bccomp($op1, $op2, $scale);
   } else {
     $op1 = $op1 - 0;
     $op2 = $op2 - 0;
     if ($op1 > $op2) {
       $result = 1;
     } else if ($op1 < $op2) {
       $result = -1;
     } else {
       $result = 0;
     }
   }
   $result = (int)$result;
   return $result;
 }

/**
 * Returns the sign of number
 *
 * @return int
 * ----------------
 *  $op1 >  0 :  1
 *  $op1 == 0 :  0
 *  $op1 <  0 : -1
 * ----------------
 * @access public
 */
 function sign($op1){
   $result = null;
   $op1 = $this->format($op1);
   if ($op1 > 0) {
     $result = 1;
   } else if ($op1 < 0) {
     $result = -1;
   } else {
     $result = 0;
   }
   $result = (int)$result;
   return $result;
 }

/**
 * Returns the square root of number
 *
 * @access public
 */
 function sqrt($op1, $scale = null) {
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $result = bcsqrt($op1, $scale);
   } else {
     $result = sqrt($op1 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Returns the absolute value of number
 *
 * @access public
 */
 function abs($op1, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $result = ltrim($op1, '+-');
   } else {
     $result = abs($op1 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Rounds a float
 *
 * @access public
 */
 function round($op1, $precision = 0){
   $result = null;
   $precision = (int)$precision;
   if ($this->hasbc) {
     $scale = $this->getScale($op1);
     $op1 = $this->format($op1, $scale);
     $pad = '0';
     $abs = $this->abs($precision);
     if ($abs > 0) {
       $pad = str_repeat($pad, $abs);
     }
     if ($precision < 0) {
       $result = round($op1, $precision);
     } else {
       if ($precision > 0) {
         $round = sprintf('0.%s5', $pad);
       } else {
         $round = '0.5';
       }
       $number = bcadd($op1, $round, bcadd($precision, 1));
       $result = bcdiv($number, '1.0', $precision);
     }
   } else {
     $result = round($op1 - 0, $precision);
     $precision = $this->getScale($result);
   }
   $result = $this->format($result, $precision);
   return $result;
 }

/**
 * Generates random numbers
 *
 * @access public
 */
 function rand($min = null, $max = null, $scale = null){
   $result = 0;
   if ($scale === null) {
     $scale = $this->getScale($min, $max);
   }
   if ($min > $max) {
     $tmp = $max;
     $max = $min;
     $min = $tmp;
     unset($tmp);
   }
   if ($min == $max) {
     $result = $min;
   } else {
     $randmax = mt_getrandmax();
     if ($this->hasbc) {
       if (!$max) {
         $max = $randmax;
         $min = 0;
       }
       $result = bcadd(
                   bcmul(
                     bcdiv(
                       mt_rand(0, $randmax),
                       $randmax,
                       strlen($max)
                     ),
                     bcsub(
                       bcadd(
                         $max,
                         1
                       ),
                       $min
                     )
                   ),
                   $min
       );
       if ($scale > 0) {
         $result .= '.' . $this->rand(0, str_repeat('9', $scale));
       }
     } else {
       if (!$max) {
         $max = $randmax;
         $min = 0;
       }
       $result = mt_rand($min - 0, $max - 0);
     }
   }
   $result = $this->format($result, $scale);
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Charset
 *
 * This class is a library to handle the character-code
 * If the mbstring library is available, most functions will use it
 * Or, when the iconv library is available, it will be used
 * When it is both invalid, it distinguishes by PHP
 * (In this case, the execution speed might be slow)
 *
 * The index keys and the encoding-list which can be handled in this class
 * +----+---------------------
 * | No.| Encoding
 * +----+---------------------
 * |  0 | ASCII
 * |  1 | EUC-JP
 * |  2 | SJIS
 * |  3 | JIS (ISO-2022-JP)
 * |  4 | ISO-8859-1
 * |  5 | UTF-8
 * |  6 | UTF-16
 * |  7 | UTF-16BE
 * |  8 | UTF-16LE
 * |  9 | UTF-32
 * | 10 | BIG5
 * | 11 | EUC-CN (GB2312)
 * | 12 | EUC-KR (KS-X-1001)
 * | 13 | BINARY
 * +----+----------------------
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Charset extends Posql_Object {

/**
 * @var    boolean   maintains mbstring library can be use
 * @access private
 */
 var $hasMBString;

/**
 * @var    boolean   maintains iconv library can be use
 * @access private
 */
 var $hasIConv;

/**
 * @var    boolean   temporary variable for the error
 * @access private
 */
 var $hasError;

/**
 * @var    boolean   whether PHP VERSION is over 5 or not
 * @access private
 */
 var $isPHP5;

/**
 * @var    string    maintains reference to the Posql's property charset
 * @access private
 */
 var $charset;

/**
 * Class constructor
 *
 * @param  void
 * @return object
 * @access public
 */
 function __construct(){
   parent::__construct();
   $this->hasMBString = extension_loaded('mbstring')
                     && function_exists('mb_detect_encoding');
   $this->hasIConv    = extension_loaded('iconv');
   $this->isPHP5      = version_compare(PHP_VERSION, 5, '>=');
   $this->hasError    = false;
 }

/**
 * Destructor for PHP Version 5+
 * Release the reference.
 *
 * Note:
 *  There is a leakage in the memory management of PHP.
 *  Bug #33595 recursive references leak memory
 *  @see http://bugs.php.net/bug.php?id=33595
 *
 * Assumes access as "public" for all __destruct() functions,
 *  if you care about the zend memory table.
 *
 * @access public
 */
 function __destruct(){
   if (isset($this)) {
     unset($this->charset);
     foreach (get_object_vars($this) as $prop => $val) {
       if ($prop != null) {
         unset($this->{$prop});
       }
     }
   }
 }

/**
 * Reference to the Posql, and its properties
 *
 * @param  Posql    give of the Posql self
 * @return void
 * @access private
 */
 function _referProperty(&$posql){
   $this->charset = & $posql->charset;
 }

/**
 * Mapping from the character-code name to
 *  the key index number of the encoding-list which can be used
 * Given the argument as "AUTO", it will be detected automatically
 *
 * @param  string   the encoding name
 * @return number   the key index number of encoding-list
 * @access private
 */
 function getEncodingIndex($encoding = 'auto'){
   static $marks = array('-', '_', '.', ':');
   $result = -1;
   if (is_string($encoding)) {
     foreach ($marks as $mark) {
       if (strpos($encoding, $mark) !== false) {
         $split = explode($mark, $encoding);
         $encoding = implode('', $split);
       }
     }
     switch (strtoupper(trim($encoding))) {
       case 'ANSIX341968':
       case 'ANSIX341986':
       case 'ASCII':
       case 'CP367':
       case 'CSASCII':
       case 'IBM367':
       case 'ISO646IRV1991':
       case 'ISOIR6':
       case 'ISO646':
       case 'ISO646US':
       case 'US':
       case 'USASCII':
           $result = 0;
           break;
       case 'EUC':
       case 'EUCJP':
       case 'EUCJPWIN':
       case 'EUCJPMS':
       case 'EUCJPOPEN':
       case 'XEUCJP':
           $result = 1;
           break;
       case 'CP932':
       case 'MSKANJI':
       case 'SJIS':
       case 'SJISWIN':
       case 'SJISOPEN':
       case 'SHIFTJIS':
       case 'WINDOWS31J':
       case 'XSJIS':
           $result = 2;
           break;
       case 'ISO2022JP':
       case 'ISO2022JPMS':
       case 'JIS':
           $result = 3;
           break;
       case 'CP819':
       case 'CSISOLATIN1':
       case 'IBM819':
       case 'ISO885911987':
       case 'ISO88591':
       case 'ISOIR100':
       case 'L1':
       case 'LATIN1':
           $result = 4;
           break;
       case 'UTF8':
           $result = 5;
           break;
       case 'UTF16':
           $result = 6;
           break;
       case 'UTF16BE':
           $result = 7;
           break;
       case 'UTF16LE':
           $result = 8;
           break;
       case 'UTF32':
           $result = 9;
           break;
       case 'CNBIG5':
       case 'CP950':
       case 'BIG5':
       case 'BIGFIVE':
           $result = 10;
           break;
       case 'CNGB':
       case 'EUCCN':
       case 'GB':
       case 'GB2312':
       case 'XEUCCN':
           $result = 11;
           break;
       case 'EUCKR':
       case 'KSX1001':
       case 'KSC5601':
       case 'XEUCKR':
           $result = 12;
           break;
       case 'BIN':
       case 'BINARY':
           $result = 13;
           break;
       case 'AUTO':
       default:
           $result = -1;
           break;
     }
   }
   return $result;
 }

/**
 * Mapping from the key index number of the encoding-list
 *  to the character-code name which can be used
 * When the index key is over the range,
 *  and the second argument is given, it will be used
 *
 * @param  number   the key index number of encoding
 * @param  string   the type of results
 * @param  string   optionally, the supplement encoding name
 * @return string   the encoding name
 * @access private
 */
 function getEncodingName($index, $type = null, $sub_encoding = null){
   if ($type == null) {
     $type = 'encoding';
   }
   $default = (string)$index;
   $sub = (string)$sub_encoding;
   if ($sub != null) {
     $sub = trim($sub);
   }
   $result = array(
     'encoding' => $sub,
     'method'   => $sub,
     'iconv'    => $sub,
     'mbstring' => $sub
   );
   $encoding = & $result['encoding'];
   $method   = & $result['method'];
   $iconv    = & $result['iconv'];
   $mbstring = & $result['mbstring'];
   $enc_index = $index;
   if (is_string($index)) {
     $enc_index = $this->getEncodingIndex($index);
   }
   switch ((int)$enc_index) {
     case 0:
         $encoding = 'ASCII';
         $method   = 'ASCII';
         $iconv    = 'ASCII';
         $mbstring = 'ASCII';
         break;
     case 1:
         $encoding = 'EUC-JP';
         $method   = 'EUCJP';
         $iconv    = 'EUC-JP';
         $mbstring = 'eucJP-win';
         break;
     case 2:
         $encoding = 'SJIS';
         $method   = 'SJIS';
         $iconv    = 'CP932';
         $mbstring = 'SJIS-win';
         break;
     case 3:
         $encoding = 'JIS';
         $method   = 'JIS';
         $iconv    = 'ISO-2022-JP';
         $mbstring = 'JIS';
         break;
     case 4:
         $encoding = 'ISO-8859-1';
         $method   = 'LATIN1';
         $iconv    = 'ISO-8859-1';
         $mbstring = 'ISO-8859-1';
         break;
     case 5:
         $encoding = 'UTF-8';
         $method   = 'UTF8';
         $iconv    = 'UTF-8';
         $mbstring = 'UTF-8';
         break;
     case 6:
         $encoding = 'UTF-16';
         $method   = 'UTF16';
         $iconv    = 'UTF-16';
         $mbstring = 'UTF-16';
         break;
     case 7:
         $encoding = 'UTF-16BE';
         $method   = 'UTF16BE';
         $iconv    = 'UTF-16BE';
         $mbstring = 'UTF-16BE';
         break;
     case 8:
         $encoding = 'UTF-16LE';
         $method   = 'UTF16LE';
         $iconv    = 'UTF-16LE';
         $mbstring = 'UTF-16LE';
         break;
     case 9:
         $encoding = 'UTF-32';
         $method   = 'UTF32';
         $iconv    = 'UTF-32';
         $mbstring = 'UTF-32';
         break;
     case 10:
         $encoding = 'BIG5';
         $method   = 'BIG5';
         $iconv    = 'BIG5';
         $mbstring = 'BIG-5';
         break;
     case 11:
         $encoding = 'EUC-CN';
         $method   = 'GB';
         $iconv    = 'EUC-CN';
         $mbstring = 'EUC-CN';
         break;
     case 12:
         $encoding = 'EUC-KR';
         $method   = 'EUCKR';
         $iconv    = 'EUC-KR';
         $mbstring = 'EUC-KR';
         break;
     case 13:
         $encoding = 'Binary';
         $method   = 'Binary';
         $iconv    = 'Binary';
         $mbstring = 'Binary';
         break;
     default:
         $encoding = $default;
         $method   = $default;
         $iconv    = $default;
         $mbstring = $default;
         break;
   }
   unset($encoding, $method, $iconv, $mbstring);
   if (array_key_exists($type, $result)) {
     $result = $result[$type];
   }
   return $result;
 }

/**
 * Returns the order of encoding
 *  when the encoding detection was given as "AUTO"
 *
 * @param  void
 * @return array   the orders of encoding-list
 * @access private
 */
 function getAutoDetectOrder(){
   static $orders = array(
     'method' => array(
       'UTF32',
       'UTF16',
       'BINARY',
       'ASCII',
       'JIS',
       'UTF-8',
       'EUC-JP',
       'SJIS',
       'BIG5',
       'EUC-KR',
       'EUC-CN'
     ),
     'iconv' => array(
       'UTF-32',
       'UTF-16',
       'ASCII',
       'JIS',
       'UTF-8',
       'EUC-JP',
       'SJIS',
       'BIG5',
       'EUC-KR',
       'EUC-CN',
       'BINARY'
     ),
     'mbstring' => array(
       'UTF-32',
       'UTF-16',
       'ASCII',
       'JIS',
       'UTF-8',
       'eucJP-win',
       'SJIS-win',
       'BIG-5',
       'EUC-KR',
       'EUC-CN',
       'BINARY'
     )
   );
   if ($this->hasMBString) {
     $result = $orders['mbstring'];
   } else if ($this->hasIConv) {
     $result = $orders['iconv'];
   } else {
     $result = $orders['method'];
   }
   return $result;
 }

/**
 * Convert character encoding
 *
 * Given the third argument as "AUTO", it will be converted automatically
 * The encoding-list of third argument order will be specified by array
 *  or comma separated list string
 * If the mbstring library is available, it will be used for conversion
 * Or, if the iconv library is available, it will be used for conversion
 *
 * @param  string   the string being converted
 * @param  string   the type of encoding that to be converted to
 * @param  string   the character code names before conversion
 * @return string   the encoded string, or FALSE on error
 * @access public
 */
 function convert($string, $to = 'UTF-8', $from = 'auto'){
   $result = false;
   if ($string != null && $to != null && is_string($to)) {
     $string = (string) $string;
     if (strpos($to, '//') !== false) {
       $split = explode('//', $to);
       $to = array_shift($split);
     }
     if (is_array($from)
      || (is_string($from) && strcasecmp($from, 'auto') === 0)) {
       $detect = $this->detectEncoding($string, $from);
       if ($detect !== false) {
         $from = $detect;
       }
     }
     if ($this->hasMBString) {
       $to = $this->getEncodingName($to, 'mbstring', $to);
       $from = $this->getEncodingName($from, 'mbstring', $from);
       $result = mb_convert_encoding($string, $to, $from);
     } else if ($this->hasIConv) {
       $to  = $this->getEncodingName($to, 'iconv', $to);
       $to .= '//TRANSLIT//IGNORE';
       $from = $this->getEncodingName($from, 'iconv', $from);
       $result = @iconv($from, $to, $string);
     }
     if (!$result) {
       $to = $this->getEncodingName($to, null, $to);
       $from = $this->getEncodingName($from, null, $from);
       if (strcasecmp($to, $from) === 0) {
         $result = $string;
       } else {
         $result = false;
       }
     }
   }
   return $result;
 }

/**
 * Detects character encoding
 * Given the first argument as "AUTO", it will be detected automatically
 * the encoding-list of second argument order will be specified by array
 *  or comma separated list string
 * If the Iconv library is effective, it will be used for detection
 * If it is invalid, it will be detected by this class methods
 *
 * @param  string   the string being detected
 * @param  mixed    the encoding-list of character encoding
 * @return string   the detected character encoding
 * @access public
 */
 function detectEncoding($string, $encodings = 'auto'){
   $result = false;
   if (is_string($encodings)) {
     $encodings = strtoupper(trim($encodings));
     if ($encodings === 'AUTO') {
       $encodings = $this->getAutoDetectOrder();
     } else if (strpos($encodings, ',') !== false) {
       $encodings = preg_split('{\s*,\s*}', $encodings);
     } else {
       $encodings = trim($encodings);
     }
   }
   foreach ((array)$encodings as $encode) {
     if ($encode == null) {
       continue;
     }
     $valid = false;
     if ($this->hasMBString) {
       if ($this->detectByMBString($encode, $string)) {
         $valid = true;
       }
     } else if ($this->hasIConv) {
       if ($this->detectByIConv($encode, $string)) {
         $valid = true;
       }
     } else {
       if ($this->detectByMethod($encode, $string)) {
         $valid = true;
       }
     }
     if ($valid) {
       $result = $this->getEncodingName($encode);
       break;
     }
   }
   return $result;
 }

/**
 * Calls the encoding detection function in this class
 *
 * @param  string  the encoding name, or the method name
 * @param  string  the string of target
 * @return boolean the encoding was proper or not
 * @access private
 */
 function detectByMethod($encoding, $string){
   $result = false;
   $method = $this->getEncodingName($encoding, 'method', 'String');
   $method = sprintf('is%s', $method);
   $func = array(&$this, $method);
   if (is_callable($func)) {
     $result = call_user_func($func, $string);
   }
   unset($func);
   return $result;
 }

/**
 * Detects the encoding to using the mbstring library
 *
 * @param  string  the encoding name
 * @param  string  the string of target
 * @return boolean the encoding was proper or not
 * @access private
 */
 function detectByMBString($encoding, $string){
   $result = false;
   $charset = $this->getEncodingName($encoding, 'mbstring');
   if (0 !== strcasecmp($charset, 'BINARY')) {
     if ($charset == null && $encoding != null) {
       $detect = @mb_detect_encoding($string, $encoding, true);
     } else {
       $detect = mb_detect_encoding($string, $charset, true);
     }
     $result = is_string($detect) && strlen($detect);
   }
   if (!$result) {
     $result = $this->detectEncodingHelper($charset, $string, false);
   }
   return $result;
 }

/**
 * Detects the encoding to using the iconv library
 *
 * @param  string  the encoding name
 * @param  string  the string of target
 * @return boolean the encoding was proper or not
 * @access private
 */
 function detectByIConv($encoding, $string){
   $result = false;
   $charset = $this->getEncodingName($encoding, 'iconv');
   if ($charset == null) {
     $charset = $encoding;
   }
   if ($charset != null) {
     $this->hasError = false;
     if (0 !== strcasecmp($charset, 'BINARY')) {
       if ($this->isPHP5) {
         set_error_handler(array($this, 'iconvErrorHandler'), E_ALL);
       } else {
         set_error_handler(array(&$this, 'iconvErrorHandler'));
       }
       iconv($charset, 'UTF-16', $string); // maybe UTF-16 is proper
       restore_error_handler();
     }
     if (!$this->hasError) {
       $result = $this->detectEncodingHelper($charset, $string, true);
     }
   }
   return $result;
 }

/**
 * A helper function to detect encoding as strict
 *
 * @access private
 */
 function detectEncodingHelper($charset, $string, $default = false){
   $result = false;
   switch (strtoupper($charset)) {
     case 'ASCII':
         $result = $this->isASCII($string);
         break;
     case 'JIS':
     case 'ISO-2022-JP':
         $result = $this->isJIS($string);
         break;
     case 'UTF-16':
         $result = $this->isUTF16($string);
         break;
     case 'UTF-16BE':
         $result = $this->isUTF16BE($string);
         break;
     case 'UTF-16LE':
         $result = $this->isUTF16LE($string);
         break;
     case 'UTF-32':
         $result = $this->isUTF32($string);
         break;
     case 'BINARY':
         $result = $this->isBinary($string);
         break;
     default:
         $result = $default;
         break;
   }
   return $result;
 }

/**
 * A handle to detect character-code by using iconv
 *
 * @access private
 */
 function iconvErrorHandler($no, $msg, $file, $line){
   if ($no === E_NOTICE) {
     $this->hasError = true;
   }
   //return true;
 }

/**
 * An insignificantly function
 */
 function isString($string){
   $result = false;
   if (is_scalar($string) || $string === null) {
     $result = true;
   }
   return $result;
 }

/**
 * Binary (exe, images, so, etc.)
 *
 * Note:
 *   This function is not considered for Unicode
 */
 function isBinary($string){
   static $bin_chars = array(
     "\x00", "\x01", "\x02", "\x03", "\x04",
     "\x05", "\x06", "\x07", "\xFF"
   );
   $result = false;
   if ($string != null) {
     foreach ($bin_chars as $char) {
       if (strpos($string, $char) !== false) {
         $result = true;
         break;
       }
     }
   }
   return $result;
 }

/**
 * ASCII (ISO-646)
 */
 function isASCII($string){
   $result = false;
   if (!preg_match('{[\x80-\xFF]}', $string)) {
     $result = strpos($string, "\x1B") === false;
   }
   return $result;
 }

/**
 * Latin-1 (ISO/IEC 8859-1)
 *
 * @link http://www.rfc-editor.org/rfc/rfc1554.txt
 * @link http://www.alanwood.net/demos/charsetdiffs.html
 */
 function isLATIN1($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   for (; $i < $length; ++$i) {
     if (($bytes[$i] === 0x09 || $bytes[$i] === 0x0A
      ||  $bytes[$i] === 0x0D || $bytes[$i] === 0x20)
      || ($bytes[$i]  >  0x20 && $bytes[$i]  <  0x7F)
      || ($bytes[$i]  >  0xA0 && $bytes[$i]  <  0xFF)) {
       continue;
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * ISO-2022-JP (JIS)
 */
 function isJIS($string){
   $result = false;
   if (!preg_match('{[\x80-\xFF]}', $string)) {
     $pos = strpos($string, "\x1B");
     if ($pos !== false) {
       $esc = substr($string, $pos + 1, 2);
       if (strlen($esc) === 2) {
         $esc1 = ord($esc);
         $esc2 = ord(substr($esc, 1));
         if ($esc1 === 0x24) {
           if ($esc2 === 0x28    // JIS X 0208-1990
            || $esc2 === 0x40    // JIS X 0208-1978
            || $esc2 === 0x42) { // JIS X 0208-1983
             $result = true;
           }
         } else if ($esc1 === 0x26 // JIS X 0208-1990
                &&  $esc2 === 0x40) {
           $result = true;
         }
       }
     }
   }
   return $result;
 }

/**
 * EUC-JP
 */
 function isEUCJP($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if ($bytes[$i] < 0x80) {
       continue;
     }
     if ($bytes[$i] < 0x8E) {
       $result = false;
       break;
     }
     if ($bytes[$i] === 0x8E) {
       if (!isset($bytes[++$i])
        || ($bytes[$i] < 0xA1 || $bytes[$i] > 0xDF)) {
         $result = false;
         break;
       }
     } else if ($bytes[$i] >= 0xA1 && $bytes[$i] <= 0xFE) {
       if (!isset($bytes[++$i])
        || ($bytes[$i] < 0xA1 || $bytes[$i] > 0xFE)) {
         $result = false;
         break;
       }
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * Shift-JIS (SJIS)
 */
 function isSJIS($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if (($bytes[$i] <= 0x80)
      || ($bytes[$i] >= 0xA1 && $bytes[$i] <= 0xDF)) {
       continue;
     }
     if ($bytes[$i] === 0xA0 || $bytes[$i] > 0xEF) {
       $result = false;
       break;
     }
     if (!isset($bytes[++$i])
      || ($bytes[$i] < 0x40 || $bytes[$i] === 0x7F || $bytes[$i] > 0xFC)) {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * UTF-8
 */
 function isUTF8($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $bom = array_slice($bytes, 0, 3);
   if (isset($bytes[0], $bytes[1], $bytes[2])
    && $bytes[0] === 0xEF && $bytes[1] === 0xBB && $bytes[2] === 0xBF) {
     $result = true; // BOM
   } else {
     $i = 0;
     while ($i < $length && $bytes[$i++] > 0x80);
     for (; $i < $length; ++$i) {
       if ($bytes[$i] < 0x80) {
         continue;
       }
       if ($bytes[$i] >= 0xC0 && $bytes[$i] <= 0xDF) {
         if (!isset($bytes[++$i])
          || ($bytes[$i] < 0x80 || $bytes[$i] > 0xEF)) {
           $result = false;
           break;
         }
       } else if ($bytes[$i] >= 0xE0 && $bytes[$i] <= 0xEF) {
         if (!isset($bytes[++$i])
          || ($bytes[$i] < 0x80 || $bytes[$i] > 0xBF)
          || !isset($bytes[++$i])
          || ($bytes[$i] < 0x80 || $bytes[$i] > 0xBF)) {
           $result = false;
           break;
         }
       } else {
         $result = false;
         break;
       }
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * UTF-16 (LE or BE)
 *
 * RFC2781: UTF-16, an encoding of ISO 10646
 * Must be labelled BOM
 *
 * @link http://www.ietf.org/rfc/rfc2781.txt
 */
 function isUTF16($string){
   $result = false;
   $length = strlen($string);
   if ($length >= 2) {
     $byte1 = ord($string);
     $byte2 = ord(substr($string, 1, 1));
     if ($byte1 === 0xFF // BOM (little-endian)
      && $byte2 === 0xFE) {
       $result = true;
     } else if ($byte1 === 0xFE // BOM (big-endian)
            &&  $byte2 === 0xFF) {
       $result = true;
     } else {
       $null = chr(0x00);
       $pos = strpos($string, $null);
       if ($pos === false) {
         $result = false; // Non ASCII (omit)
       } else {
         $next = substr($string, $pos + 1, 1); // BE
         $prev = substr($string, $pos - 1, 1); // LE
         if ((strlen($next) > 0 && ord($next) > 0x00 && ord($next) < 0x80)
          || (strlen($prev) > 0 && ord($prev) > 0x00 && ord($prev) < 0x80)) {
           $pos = strlen($string) - 1;
           $next = substr($string, $pos + 1, 1); // BE
           $prev = substr($string, $pos - 1, 1); // LE
           if (strlen($next) > 0) {
             if (ord($next) > 0x00 && ord($next) < 0x80) {
               $result = true;
             } else {
               $result = false;
             }
           } else if (strlen($prev) > 0) {
             if (ord($prev) > 0x00 && ord($prev) < 0x80) {
               $result = true;
             } else {
               $result = false;
             }
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * UTF-16BE (big-endian)
 *
 * RFC 2781 4.3 Interpreting text labelled as UTF-16
 * Text labelled "UTF-16BE" can always be interpreted as being big-endian
 *  when BOM does not founds (SHOULD)
 *
 * @link http://www.ietf.org/rfc/rfc2781.txt
 */
 function isUTF16BE($string){
   $result = false;
   $length = strlen($string);
   if ($length >= 2) {
     $byte1 = ord($string);
     $byte2 = ord(substr($string, 1, 1));
     if ($byte1 === 0xFE // BOM
      && $byte2 === 0xFF) {
       $result = true;
     } else {
       $pos = strpos($string, chr(0x00));
       if ($pos === false) {
         $result = false; // Non ASCII..
       } else {
         $byte = substr($string, $pos + 1, 1);
         if (strlen($byte) > 0 && ord($byte) < 0x80) {
           $result = true;
         }
       }
     }
   }
   return $result;
 }

/**
 * UTF-16LE (little-endian)
 *
 * @see isUTF16BE
 */
 function isUTF16LE($string){
   $result = false;
   $length = strlen($string);
   if ($length >= 2) {
     $byte1 = ord($string);
     $byte2 = ord(substr($string, 1, 1));
     if ($byte1 === 0xFF // BOM
      && $byte2 === 0xFE) {
       $result = true;
     } else {
       $pos = strpos($string, chr(0x00));
       if ($pos === false) {
         $result = false; // There is no ASCII in string
       } else {
         $byte = substr($string, $pos - 1, 1);
         if (strlen($byte) > 0 && ord($byte) < 0x80) {
           $result = true;
         }
       }
     }
   }
   return $result;
 }

/**
 * UTF-32
 *
 * Unicode 3.2.0: Unicode Standard Annex #19
 *
 * @link http://www.iana.org/assignments/charset-reg/UTF-32
 * @link http://www.unicode.org/reports/tr19/tr19-9.html
 */
 function isUTF32($string){
   $result = false;
   $length = strlen($string);
   if ($length >= 4) {
     $byte1 = ord($string);
     $byte2 = ord(substr($string, 1, 1));
     $byte3 = ord(substr($string, 2, 1));
     $byte4 = ord(substr($string, 3, 1));
     if ($byte1 === 0x00 && $byte2 === 0x00 // BOM (big-endian)
      && $byte3 === 0xFE && $byte4 === 0xFF) {
       $result = true;
     } else if ($byte1 === 0xFF && $byte2 === 0xFE // BOM (little-endian)
            &&  $byte3 === 0x00 && $byte4 === 0x00) {
       $result = true;
     } else {
       $pos = strpos($string, chr(0x00));
       if ($pos === false) {
         $result = false; // Non ASCII (omit)
       } else {
         $next = substr($string, $pos, 4); // Must be (BE)
         if (strlen($next) === 4) {
           $bytes = array_values(unpack('C4', $next));
           if (isset($bytes[0], $bytes[1], $bytes[2], $bytes[3])) {
             if ($bytes[0] === 0x00 && $bytes[1] === 0x00) {
               if ($bytes[2] === 0x00) {
                 $result = $bytes[3] >= 0x09 && $bytes[3] <= 0x7F;
               } else if ($bytes[2] > 0x00 && $bytes[2] < 0xFF) {
                 $result = $bytes[3] > 0x00 && $bytes[3] <= 0xFF;
               }
             }
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Big5 (Traditional Chinese)
 */
 function isBIG5($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if ($bytes[$i] < 0x80) {
       continue;
     }
     if ($bytes[$i] >= 0x81 && $bytes[$i] <= 0xFE) {
       if (!isset($bytes[++$i])
        || (($bytes[$i] < 0x40 || $bytes[$i] > 0x7E)
        &&  ($bytes[$i] < 0xA1 || $bytes[$i] > 0xFE))) {
         $result = false;
         break;
       }
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * EUC-CN (GB2312) (Simplified Chinese)
 */
 function isGB($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if ($bytes[$i] < 0x80) {
       continue;
     }
     if ($bytes[$i] >= 0x81 && $bytes[$i] <= 0xFE) {
       if (!isset($bytes[++$i])
        || (($bytes[$i] < 0x40 || $bytes[$i] > 0x7E)
        &&  ($bytes[$i] < 0x80 || $bytes[$i] > 0xFE))) {
         $result = false;
         break;
       } else if ($bytes[$i] >= 0x30 && $bytes[$i] <= 0x39) {
         if (!isset($bytes[++$i])
          || ($bytes[$i] < 0x81 || $bytes[$i] > 0xFE)
          || !isset($bytes[++$i])
          || ($bytes[$i] < 0x30 || $bytes[$i] > 0x39)) {
           $result = false;
           break;
         }
       }
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * EUC-KR (KS X 1001) RFC1557
 */
 function isEUCKR($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if ($bytes[$i] < 0x80) {
       continue;
     }
     if ($bytes[$i] >= 0xA1 && $bytes[$i] <= 0xFE) {
       if (!isset($bytes[++$i])
        || ($bytes[$i] < 0xA1 || $bytes[$i] > 0x7E)) {
         $result = false;
         break;
       }
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_UTF8
 *
 * This class is a library to handle the basic string functions as UTF-8
 * If the mbstring library is available, most functions will use it
 * Or, when the iconv library is available, it will be used
 * When it is both invalid, it distinguishes by PHP,
 *  which uses PCRE RegExp's with 'u' flag
 *  (In this case, the execution speed might be slow)
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_UTF8 extends Posql_Object {

/**
 * @var    boolean   maintains mbstring library can be use
 * @access private
 */
 var $hasMBString;

/**
 * @var    boolean   maintains iconv library can be use
 * @access private
 */
 var $hasIConv;

/**
 * @var    boolean   whether PHP VERSION is over 5 or not
 * @access private
 */
 var $isPHP5;

/**
 * @var    boolean   whether PHP VERSION is over 5.2.0 or not
 * @access private
 */
 var $isPHP520;

/**
 * @var    Posql_Charset    maintains instance of the Posql_Charset class
 * @access private
 */
 var $pcharset;

/**
 * @var    boolean   whether initialized or not
 * @access private
 */
 var $_inited;

/**
 * @var    mixed     maintains the previous mbstring settings
 * @access private
 */
 var $prevMBStringEncoding;

/**
 * @var    mixed     maintains the previous iconv settings
 * @access private
 */
 var $prevIConvEncoding;

/**
 * @var    mixed     maintains the previous encoding
 * @access private
 */
 var $_orgCharset;

/**
 * @var    string    maintains reference to the Posql's property charset
 * @access private
 */
 var $charset;

/**
 * Class constructor
 *
 * @param  void
 * @return object
 * @access public
 */
 function __construct(){
   parent::__construct();
   $this->init();
 }

/**
 * Destructor for PHP Version 5+
 * Release all referenced properties
 *
 * @access public
 */
 function __destruct(){
   if (isset($this)) {
     unset($this->pcharset, $this->charset);
     foreach (get_object_vars($this) as $prop => $val) {
       if ($prop != null) {
         unset($this->{$prop});
       }
     }
   }
 }

/**
 * Reference to the Posql, and its properties
 *
 * @param  Posql    give of the Posql self
 * @return void
 * @access private
 */
 function _referProperty(&$posql){
   $this->charset  = & $posql->charset;
   $this->pcharset = & $posql->pcharset;
 }

/**
 * Initializes class variables
 *
 * @param  void
 * @return void
 * @access private
 */
 function init(){
   if (empty($this->_inited)) {
     $this->hasMBString = false;
     $this->hasIConv    = false;
     $this->isPHP5      = version_compare(PHP_VERSION, 5, '>=');
     $this->isPHP520    = version_compare(PHP_VERSION, '5.2.0', '>=');
     if (extension_loaded('mbstring') && function_exists('mb_strlen')
      && function_exists('mb_strpos') && function_exists('mb_strrpos')
      && function_exists('mb_substr') && function_exists('mb_strtolower')
      && function_exists('mb_strtoupper')
      && function_exists('mb_internal_encoding')) {
       $this->hasMBString = true;
     }
     if (extension_loaded('iconv') && function_exists('iconv_strlen')
      && function_exists('iconv_strpos') && function_exists('iconv_strrpos')
      && function_exists('iconv_get_encoding')
      && function_exists('iconv_set_encoding') && $this->isPHP5) {
       // Not uses iconv_substr() for Bug {@see substr}
       $this->hasIConv = true;
     }
     $this->_inited = true;
   }
 }

/**
 * Convert the given string to valid as UTF-8
 *
 * @param  string   the string of target
 * @param  boolean  whether the original encoding to preserved or not
 * @return string   the validated string as UTF-8
 * @access private
 */
 function toUTF8($string, $save_org = false){
   $string = (string)$string;
   if ($string != null && !is_numeric($string)) {
     $to = 'UTF-8';
     $from = $this->pcharset->detectEncoding($string);
     $string = $this->pcharset->convert($string, $to, $from);
     if ($save_org) {
       $this->_orgCharset = $from;
     }
   }
   return $string;
 }

/**
 * Restore the given string to original encoding from UTF-8
 *
 * @param  string   the string of target as UTF-8
 * @param  string   optionally, the encoding before the string to converted
 * @return string   the encoded string as original encoding
 * @access private
 */
 function fromUTF8($string, $before = null){
   $string = (string)$string;
   if ($string != null && !is_numeric($string)) {
     if (!empty($this->_orgCharset)
      || !empty($this->charset) || $before != null) {
       if ($before == null) {
         if (!empty($this->_orgCharset)) {
           $to = $this->_orgCharset;
         } else {
           $to = $this->charset;
         }
       } else {
         $to = $before;
       }
       $from = 'UTF-8';
       if ($to !== $from) {
         $string = $this->pcharset->convert($string, $to, $from);
       }
       //XXX: Fixed(?) Bug: Cannot restore encoding in array
       /*
       if ($before == null) {
         $this->_orgCharset = null;
       }
       */
     }
   }
   return $string;
 }

/**
 * Sets the libraries internal encoding to UTF-8
 *
 * @param  void
 * @return void
 * @access private
 */
 function setEncoding(){
   if ($this->hasMBString) {
     $this->prevMBStringEncoding = mb_internal_encoding();
     mb_internal_encoding('UTF-8');
   }
   if ($this->hasIConv) {
     $this->prevIConvEncoding = iconv_get_encoding('internal_encoding');
     iconv_set_encoding('internal_encoding', 'UTF-8');
   }
 }

/**
 * Restores the libraries internal encoding to previous settings
 *
 * @param  void
 * @return void
 * @access private
 */
 function restoreEncoding(){
   if ($this->hasMBString && $this->prevMBStringEncoding != null) {
     mb_internal_encoding($this->prevMBStringEncoding);
     $this->prevMBStringEncoding = null;
   }
   if ($this->hasIConv && $this->prevIConvEncoding != null) {
     iconv_set_encoding('internal_encoding', $this->prevIConvEncoding);
     $this->prevIConvEncoding = null;
   }
 }

/**
 * The handle of strlen() as UTF-8 in Unicode
 * Returns the length of the string as UTF-8
 *
 * @see    strlen
 * @param  string  the string of target
 * @return number  the length of the string
 * @access public
 */
 function strlen($string){
   $result = 0;
   $this->setEncoding();
   $string = $this->toUTF8($string);
   $result = $this->_strlen($string);
   $this->restoreEncoding();
   return $result;
 }

/**
 * The handle of strpos() as UTF-8
 * Find position of first occurrence of a string
 * If the searched substring is not found then return as FALSE
 *
 * @see    strpos
 * @param  string  the entire string
 * @param  string  the searched substring
 * @param  number  optionally, the offset of the search position
 * @return number  the numeric position of the first occurrence, or FALSE
 * @access public
 */
 function strpos($string, $search, $offset = 0){
   $result = false;
   $this->setEncoding();
   if ($string !== '' && $search !== '' && $offset >= 0) {
     $string = $this->toUTF8($string);
     $search = $this->toUTF8($search);
     $result = $this->_strpos($string, $search, $offset);
   }
   $this->restoreEncoding();
   return $result;
 }

/**
 * The handle of strrpos() as UTF-8
 * Find position of last occurrence of a substring in a string
 * If the searched substring is not found then return as FALSE
 *
 * @see    strrpos
 * @param  string   the entire string.
 * @param  string   the searched substring.
 * @param  number   optionally, the offset of the search position from the end
 * @return number   the numeric position of the first occurrence, or FALSE
 * @access public
 */
 function strrpos($string, $search, $offset = null){
   $result = false;
   $this->setEncoding();
   if ($string !== '' && $search !== '' && $offset >= 0) {
     $string = $this->toUTF8($string);
     $search = $this->toUTF8($search);
     $result = $this->_strrpos($string, $search, $offset);
   }
   $this->restoreEncoding();
   return $result;
 }

/**
 * The handle of substr() as UTF-8
 * Cut out part of a string
 *
 * Notes:
 *   uses PCRE RegExp's with 'u' flag
 *   PCRE only supports repetitions of less than 65536
 *   It will return an empty string('') instead
 *
 * Notes:
 *   Should not use it because there are some bugs in the iconv_substr()
 *   Bug #37773 iconv_substr() gives "Unknown error"
 *                             when string length = 1
 *   @see http://bugs.php.net/bug.php?id=37773
 *   Bug #34757 iconv_substr() gives "Unknown error"
 *                             when offset > string length = 1
 *   @see http://bugs.php.net/bug.php?id=34757
 *
 * @see    substr
 * @param  string  the original string
 * @param  number  the numeric position of offset
 * @param  number  optionally, the numeric length from offset
 * @return string  a cut part of the substring, or the empty string
 * @access public
 */
 function substr($string, $offset, $length = null){
   $result = '';
   $this->setEncoding();
   if ($string !== '' && $length !== 0) {
     $string = $this->toUTF8((string)$string, true);
     $result = $this->_substr($string, $offset, $length);
     $result = $this->fromUTF8($result);
   }
   $this->restoreEncoding();
   return $result;
 }

/**
 * this function usage internal only
 *
 * @access private
 */
 function _strlen($string){
   $result = 0;
   if ($this->hasMBString) {
     $result = mb_strlen($string);
   } else if ($this->hasIConv) {
     $result = iconv_strlen($string);
   } else {
     $result = strlen(utf8_decode($string));
   }
   return $result;
 }

/**
 * this function usage internal only
 *
 * @access private
 */
 function _strpos($string, $search, $offset = 0){
   $result = false;
   if ($string !== '' && $search !== '' && $offset >= 0) {
     if ($this->hasMBString) {
       if ($offset - 0 === 0) {
         $result = mb_strpos($string, $search);
       } else {
         $result = mb_strpos($string, $search, $offset);
       }
     } else if ($this->hasIConv) {
       if ($offset - 0 === 0) {
         $result = iconv_strpos($string, $search);
       } else {
         $result = iconv_strpos($string, $search, $offset);
       }
     } else {
       if ($offset - 0 === 0) {
         $string = explode($search, $string, 2);
         if (count($string) > 1 && isset($string[0])) {
           $result = $this->_strlen($string[0]);
         }
       } else {
         if (is_numeric($offset) && $offset >= 0) {
           $string = $this->_substr($string, $offset);
           $pos = $this->_strpos($string, $search);
           if ($pos !== false) {
             $result = $pos + $offset;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * this function usage internal only
 *
 * @access private
 */
 function _strrpos($string, $search, $offset = null){
   $result = false;
   if ($string !== '' && $search !== '' && $offset >= 0) {
     if ($offset - 0 !== 0) {
       if ($this->hasMBString && $this->isPHP520) {
         $result = mb_strrpos($string, $search, $offset);
       } else {
         if (is_numeric($offset) && $offset >= 0) {
           $string = $this->_substr($string, $offset);
           $pos = $this->_strrpos($string, $search);
           if ($pos !== false) {
             $result = $pos + $offset;
           }
         }
       }
     } else {
       if ($this->hasMBString) {
         $result = mb_strrpos($string, $search);
       } else if ($this->hasIConv) {
         $result = iconv_strrpos($string, $search);
       } else {
         $string = explode($search, $string);
         if (count($string) > 1) {
           array_pop($string);
           $string = implode($search, $string);
           $result = $this->_strlen($string);
         }
       }
     }
   }
   return $result;
 }

/**
 * this function usage internal only
 *
 * @access private
 */
 function _substr($string, $offset, $length = null){
   $result = '';
   if ($string !== '' && $length !== 0) {
     if ($this->hasMBString) {
       if ($length === null) {
         $result = mb_substr($string, $offset);
       } else {
         $result = mb_substr($string, $offset, $length);
       }
     } else if ($length === null && (($offset - 0) === 0)) {
       $result = $string;
     } else {
       $offset = (int)$offset;
       if ($length !== null) {
         $length = (int)$length;
       }
       if ($offset < 0 && $length < 0 && $length < $offset) {
         $result = '';
       } else {
         $offset_pattern = '';
         $length_pattern = '';
         if ($offset < 0) {
           $strlen = $this->_strlen($string);
           $offset = $strlen + $offset;
           if ($offset < 0) {
             $offset = 0;
           }
         }
         if ($offset > 0) {
           $offset_x = (int)($offset / 65535);
           $offset_y = $offset % 65535;
           if ($offset_x) {
             $offset_pattern = '(?:.{65535}){' . $offset_x . '}';
           }
           $offset_pattern = '^(?:' . $offset_pattern
                           . '.{' . $offset_y . '})';
         } else {
           $offset_pattern = '^';
         }
         if ($length === null) {
           $length_pattern = '(.*)$';
         } else {
           if (!isset($strlen)) {
             $strlen = $this->_strlen($string);
           }
           if ($offset > $strlen) {
             $result = '';
           } else {
             if ($length > 0) {
               $minlen = $strlen - $offset;
               if ($minlen > $length) {
                 $minlen = $length;
               }
               $length = $minlen;
               $len_x = (int)($length / 65535);
               $len_y = $length % 65535;
               if ($len_x) {
                 $length_pattern = '(?:.{65535}){' . $len_x . '}';
               }
               $length_pattern = '(' . $length_pattern
                               . '.{' . $len_y . '})';
             } else if ($length < 0) {
               if ($length < ($offset - $strlen)) {
                 $result = '';
               } else {
                 $len_x = (int)((-$length) / 65535);
                 $len_y = (-$length) % 65535;
                 if ($len_x) {
                   $length_pattern = '(?:.{65535}){' . $len_x . '}';
                 }
                 $length_pattern = '(.*)(?:' . $length_pattern
                                 . '.{' . $len_y . '})$';
               }
             }
           }
         }
         $pattern = '<' . $offset_pattern . $length_pattern . '>us';
         if (preg_match($pattern, $string, $match)) {
           $result = $match[1];
         }
       }
     }
   }
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Unicode
 *
 * This class is a library to handle the basic string functions as Unicode
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Unicode extends Posql_UTF8 {

/**
 * Class constructor
 *
 * @param  void
 * @return object
 * @access public
 */
 function __construct(){
   parent::__construct();
   $this->init();
 }

/**
 * The handle of strcasecmp() as Unicode
 * Binary safe case-insensitive string comparison
 *
 * @see    strcasecmp
 * @param  string  the first string
 * @param  string  the second string
 * @return number  0 or 1 or -1, if they are equal, it will be returned as 0
 * @access public
 */
 function strcasecmp($string1, $string2){
   if ($string1 != null && $string2 != null && $string1 != $string2) {
     $string1 = $this->strtolower($string1);
     $string2 = $this->strtolower($string2);
   }
   return strcmp($string1, $string2);
 }

/**
 * The handle of chr() as Unicode
 * This function works like so-called String.fromCharCode()
 * Returns one-character string as Unicode from the numbers
 *
 * @see    chr
 * @param  number  the numbers of Unicode to be contained
 * @return string  the one-character string as Unicode
 * @access public
 */
 function chr($number = null){
   $result = '';
   $argn = func_num_args();
   $args = func_get_args();
   if ($argn > 0) {
     $i = 0;
     while (--$argn >= 0) {
       $arg = (int)$args[$i++];
       if ($arg < 0) {
         $chr = '';
       } else {
         if ($arg <= 0xFF) {
           $chr = chr($arg);
         } else {
           $chr = $this->fromUnicode(array($arg));
         }
         if ($chr == null) {
           $chr = '';
         } else {
           $chr = (string)$chr;
         }
       }
       $result .= $chr;
     }
   }
   return $result;
 }

/**
 * The handle of ord() as Unicode
 * Returns the number from the head of string as Unicode
 *
 * @see    ord
 * @param  string  the string of target as Unicode
 * @return number  the numbers of Unicode to be contained
 * @access public
 */
 function ord($string){
   $result = false;
   $string = $this->substr($string, 0, 1);
   $string = $this->toUnicode($string);
   if (!empty($string) && is_array($string)) {
     $result = array_shift($string);
   }
   return $result;
 }

/**
 * The handle of substr_replace() as Unicode
 * Replace text within a portion of a string
 *
 * @see    substr_replace
 * @param  string  the input string
 * @param  string  the replacement string
 * @param  number  the numeric position of offset
 * @param  number  optionally, the numeric length from offset
 * @return string  the replaced substring, or the empty string
 * @access public
 */
 function substr_replace($string, $replace, $start, $length = 0){
   $result = '';
   if ((is_scalar($string)  || $string  === null)
    && (is_scalar($replace) || $replace === null)) {
     if ($start > 0) {
       $result .= $this->substr($string, 0, $start);
     }
     $result .= $this->substr($replace, 0);
     $result .= $this->substr($string, $start + $length);
   }
   return $result;
 }

/**
 * The handle of strrev() as Unicode
 * Reverse a string
 *
 * @see    strrev
 * @param  string  the string to be reversed
 * @return string  returns the reversed string
 * @access public
 */
 function strrev($string){
   $this->setEncoding();
   if ($string != null && strlen($string) > 1) {
     $string = $this->toUTF8($string, true);
     $string = $this->_toUnicode($string);
     if (!empty($string) && is_array($string)) {
       $string = array_reverse($string);
       $string = $this->fromUnicode($string);
     }
     $string = $this->fromUTF8($string);
   }
   $this->restoreEncoding();
   return $string;
 }

/**
 * Translate certain characters as Unicode
 *
 * @param  string  the string being translated
 * @param  string  the string being translated to
 * @param  string  the string replacing from
 * @return string  the copy of string,
 *                 translating all occurrences of each character in
 *                 from to the corresponding character
 * @access public
 */
 function strtr($string, $from, $to = null){
   $this->setEncoding();
   if ($string != null) {
     if ($to === null && is_array($from)
      && is_string(reset($from)) && is_string(key($from))) {
       $string = strtr($string, $from);
     } else {
       $from = $this->toUnicode($from);
       $to = $this->toUnicode($to);
       if (!empty($from) && !empty($to)
        && is_array($from) && is_array($to)
        && count($from) === count($to)) {
         $string = $this->toUTF8($string, true);
         $parts = array();
         $count = count($to);
         while (--$count >= 0) {
           $key = array_shift($from);
           $val = array_shift($to);
           if (is_int($key) && is_int($val)) {
             $parts[ $this->chr($key) ] = $this->chr($val);
           }
         }
         $string = strtr($string, $parts);
         $string = $this->fromUTF8($string);
       }
     }
   }
   $this->restoreEncoding();
   return $string;
 }

/**
 * The handle of ucfirst() as Unicode
 * Make a string's first character uppercase
 *
 * @see    ucfirst
 * @param  string  the input string
 * @return string  returns the resulting string
 * @access public
 */
 function ucfirst($string){
   $string = (string)$string;
   if ($string != null) {
     switch ($this->strlen($string)) {
       case 0:
           $string = '';
           break;
       case 1:
           $string = $this->strtoupper($string);
           break;
       default:
           $first = $this->substr($string, 0, 1);
           $first = $this->strtoupper($string);
           $string = $first . $this->substr($string, 1);
           break;
     }
   }
   return $string;
 }

/**
 * The handle of strtolower() as Unicode
 * Returns string with all alphabetic characters converted to lowercase
 *
 * Note:
 * Unicode Standard Annex #21: Case Mappings
 * The concept of a characters "case" only exists is some alphabets
 *  such as Latin, Greek, Cyrillic, Armenian and archaic Georgian,
 *  and Japanese alphabets
 * It does not exist in the Chinese alphabet
 *
 * @see    http://www.unicode.org/reports/tr21/tr21-5.html
 * @see    strtolower
 * @param  string  the input string
 * @return string  returns the lowercased string
 * @access public
 */
 function strtolower($string){
   $this->setEncoding();
   if ($string != null) {
     $string = $this->toUTF8($string, true);
     $string = $this->_strtolower($string);
     $string = $this->fromUTF8($string);
   }
   $this->restoreEncoding();
   return $string;
 }

/**
 * The handle of strtoupper() as Unicode
 * Returns string with all alphabetic characters converted to uppercase
 *
 * Note:
 * Unicode Standard Annex #21: Case Mappings
 * The concept of a characters "case" only exists is some alphabets
 *  such as Latin, Greek, Cyrillic, Armenian and archaic Georgian,
 *  and Japanese alphabets
 * It does not exist in the Chinese alphabet
 *
 * @see    http://www.unicode.org/reports/tr21/tr21-5.html
 * @see    strtoupper
 * @param  string  the input string
 * @return string  returns the uppercased string
 * @access public
 */
 function strtoupper($string){
   $this->setEncoding();
   if ($string != null) {
     $string = $this->toUTF8($string, true);
     $string = $this->_strtoupper($string);
     $string = $this->fromUTF8($string);
   }
   $this->restoreEncoding();
   return $string;
 }

/**
 * this function usage internal only
 *
 * @access private
 */
 function _strtolower($string){
   $string = (string)$string;
   if ($string != null) {
     if ($this->hasMBString) {
       $string = mb_strtolower($string);
     } else {
       $string = $this->toUnicode($string);
       $maps = $this->getCaseMaps('L');
       if ($string != null && is_array($maps)) {
         $length = count($string);
         for ($i = 0; $i < $length; $i++) {
           if (isset($maps[ $string[$i] ])) {
             $string[$i] = $maps[ $string[$i] ];
           }
         }
         unset($maps);
       }
       $string = $this->fromUnicode($string);
     }
   }
   return $string;
 }

/**
 * this function usage internal only
 *
 * @access private
 */
 function _strtoupper($string){
   $string = (string)$string;
   if ($string != null) {
     if ($this->hasMBString) {
       $string = mb_strtoupper($string);
     } else {
       $string = $this->toUnicode($string);
       $maps = $this->getCaseMaps('U');
       if ($string != null && is_array($maps)) {
         $length = count($string);
         for ($i = 0; $i < $length; $i++) {
           if (isset($maps[ $string[$i] ])) {
             $string[$i] = $maps[ $string[$i] ];
           }
         }
         unset($maps);
       }
       $string = $this->fromUnicode($string);
     }
   }
   return $string;
 }

/**
 * Unicode Standard Annex #21: Case Mappings
 *
 * Note:
 * The concept of a characters "case" only exists is some alphabets
 *  such as Latin, Greek, Cyrillic, Armenian and archaic Georgian,
 *  and Japanese alphabets
 * It does not exist in the Chinese alphabet
 *
 * @see http://www.unicode.org/reports/tr21/tr21-5.html
 *
 * @param  mixed   either of LOWER or UPPER (e.g. 'lower')
 * @return array   the case mappings as an associate array
 * @access private
 */
 function getCaseMaps($lower = false){
   static $inited = false, $casemaps = array(
   0x0041=>0x0061,0x03A6=>0x03C6,0x0162=>0x0163,0x00C5=>0x00E5,0x0042=>0x0062,
   0x0139=>0x013A,0x00C1=>0x00E1,0x0141=>0x0142,0x038E=>0x03CD,0x0100=>0x0101,
   0x0490=>0x0491,0x0394=>0x03B4,0x015A=>0x015B,0x0044=>0x0064,0x0393=>0x03B3,
   0x00D4=>0x00F4,0x042A=>0x044A,0x0419=>0x0439,0x0112=>0x0113,0x041C=>0x043C,
   0x015E=>0x015F,0x0143=>0x0144,0x00CE=>0x00EE,0x040E=>0x045E,0x042F=>0x044F,
   0x039A=>0x03BA,0x0154=>0x0155,0x0049=>0x0069,0x0053=>0x0073,0x1E1E=>0x1E1F,
   0x0134=>0x0135,0x0427=>0x0447,0x03A0=>0x03C0,0x0418=>0x0438,0x00D3=>0x00F3,
   0x0420=>0x0440,0x0404=>0x0454,0x0415=>0x0435,0x0429=>0x0449,0x014A=>0x014B,
   0x0411=>0x0431,0x0409=>0x0459,0x1E02=>0x1E03,0x00D6=>0x00F6,0x00D9=>0x00F9,
   0x004E=>0x006E,0x0401=>0x0451,0x03A4=>0x03C4,0x0423=>0x0443,0x015C=>0x015D,
   0x0403=>0x0453,0x03A8=>0x03C8,0x0158=>0x0159,0x0047=>0x0067,0x00C4=>0x00E4,
   0x0386=>0x03AC,0x0389=>0x03AE,0x0166=>0x0167,0x039E=>0x03BE,0x0164=>0x0165,
   0x0116=>0x0117,0x0108=>0x0109,0x0056=>0x0076,0x00DE=>0x00FE,0x0156=>0x0157,
   0x00DA=>0x00FA,0x1E60=>0x1E61,0x1E82=>0x1E83,0x00C2=>0x00E2,0x0118=>0x0119,
   0x0145=>0x0146,0x0050=>0x0070,0x0150=>0x0151,0x042E=>0x044E,0x0128=>0x0129,
   0x03A7=>0x03C7,0x013D=>0x013E,0x0422=>0x0442,0x005A=>0x007A,0x0428=>0x0448,
   0x03A1=>0x03C1,0x1E80=>0x1E81,0x016C=>0x016D,0x00D5=>0x00F5,0x0055=>0x0075,
   0x0176=>0x0177,0x00DC=>0x00FC,0x1E56=>0x1E57,0x03A3=>0x03C3,0x041A=>0x043A,
   0x004D=>0x006D,0x016A=>0x016B,0x0170=>0x0171,0x0424=>0x0444,0x00CC=>0x00EC,
   0x0168=>0x0169,0x039F=>0x03BF,0x004B=>0x006B,0x00D2=>0x00F2,0x00C0=>0x00E0,
   0x0414=>0x0434,0x03A9=>0x03C9,0x1E6A=>0x1E6B,0x00C3=>0x00E3,0x042D=>0x044D,
   0x0416=>0x0436,0x01A0=>0x01A1,0x010C=>0x010D,0x011C=>0x011D,0x00D0=>0x00F0,
   0x013B=>0x013C,0x040F=>0x045F,0x040A=>0x045A,0x00C8=>0x00E8,0x03A5=>0x03C5,
   0x0046=>0x0066,0x00DD=>0x00FD,0x0043=>0x0063,0x021A=>0x021B,0x00CA=>0x00EA,
   0x0399=>0x03B9,0x0179=>0x017A,0x00CF=>0x00EF,0x01AF=>0x01B0,0x0045=>0x0065,
   0x039B=>0x03BB,0x0398=>0x03B8,0x039C=>0x03BC,0x040C=>0x045C,0x041F=>0x043F,
   0x042C=>0x044C,0x00DE=>0x00FE,0x00D0=>0x00F0,0x1EF2=>0x1EF3,0x0048=>0x0068,
   0x00CB=>0x00EB,0x0110=>0x0111,0x0413=>0x0433,0x012E=>0x012F,0x00C6=>0x00E6,
   0x0058=>0x0078,0x0160=>0x0161,0x016E=>0x016F,0x0391=>0x03B1,0x0407=>0x0457,
   0x0172=>0x0173,0x0178=>0x00FF,0x004F=>0x006F,0x041B=>0x043B,0x0395=>0x03B5,
   0x0425=>0x0445,0x0120=>0x0121,0x017D=>0x017E,0x017B=>0x017C,0x0396=>0x03B6,
   0x0392=>0x03B2,0x0388=>0x03AD,0x1E84=>0x1E85,0x0174=>0x0175,0x0051=>0x0071,
   0x0417=>0x0437,0x1E0A=>0x1E0B,0x0147=>0x0148,0x0104=>0x0105,0x0408=>0x0458,
   0x014C=>0x014D,0x00CD=>0x00ED,0x0059=>0x0079,0x010A=>0x010B,0x038F=>0x03CE,
   0x0052=>0x0072,0x0410=>0x0430,0x0405=>0x0455,0x0402=>0x0452,0x0126=>0x0127,
   0x0136=>0x0137,0x012A=>0x012B,0x038A=>0x03AF,0x042B=>0x044B,0x004C=>0x006C,
   0x0397=>0x03B7,0x0124=>0x0125,0x0218=>0x0219,0x00DB=>0x00FB,0x011E=>0x011F,
   0x041E=>0x043E,0x1E40=>0x1E41,0x039D=>0x03BD,0x0106=>0x0107,0x03AB=>0x03CB,
   0x0426=>0x0446,0x00DE=>0x00FE,0x00C7=>0x00E7,0x03AA=>0x03CA,0x0421=>0x0441,
   0x0412=>0x0432,0x010E=>0x010F,0x00D8=>0x00F8,0x0057=>0x0077,0x011A=>0x011B,
   0x0054=>0x0074,0x004A=>0x006A,0x040B=>0x045B,0x0406=>0x0456,0x0102=>0x0103,
   0x039B=>0x03BB,0x00D1=>0x00F1,0x041D=>0x043D,0x038C=>0x03CC,0x00C9=>0x00E9,
   0x00D0=>0x00F0,0x0407=>0x0457,0x0122=>0x0123,0xFF21=>0xFF41,0xFF22=>0xFF42,
   0xFF23=>0xFF43,0xFF24=>0xFF44,0xFF25=>0xFF45,0xFF26=>0xFF46,0xFF27=>0xFF47,
   0xFF28=>0xFF48,0xFF29=>0xFF49,0xFF2A=>0xFF4A,0xFF2B=>0xFF4B,0xFF2C=>0xFF4C,
   0xFF2D=>0xFF4D,0xFF2E=>0xFF4E,0xFF2F=>0xFF4F,0xFF30=>0xFF50,0xFF31=>0xFF51,
   0xFF32=>0xFF52,0xFF33=>0xFF53,0xFF34=>0xFF54,0xFF35=>0xFF55,0xFF36=>0xFF56,
   0xFF37=>0xFF57,0xFF38=>0xFF58,0xFF39=>0xFF59,0xFF3A=>0xFF5A);
   if (!$inited) {
     $casemaps = array(
       'lower' => $casemaps,
       'upper' => array_flip($casemaps)
     );
     $inited = true;
   }
   switch (ord((string)$lower) | 0x20) {
     case 0x21:
     case 0x75:
         $type = 'upper';
         break;
     case 0x20:
     case 0x6C:
     default:
         $type = 'lower';
         break;
   }
   return $casemaps[$type];
 }

/**
 * Takes an UTF-8 string
 *  and returns an array of integers representing the Unicode characters.
 * Astral planes are supported
 *  (i.e. the integers in the output can be > 0xFFFF).
 * Occurrences of the BOM are ignored.
 * Surrogates are not allowed.
 * Returns FALSE if the input string isn't a valid UTF-8 octet sequence.
 *
 * the original C++ source code
 * http://lxr.mozilla.org/seamonkey/source/intl/uconv/src/nsUTF8ToUnicode.cpp
 * http://lxr.mozilla.org/seamonkey/source/intl/uconv/src/nsUnicodeToUTF8.cpp
 *
 * @param  string  the string of target, which should be UTF-8
 * @return array   an array which has the values of integers
 *                  that structured the Unicode characters.
 *                 Or FALSE by invalid character.
 * @access public
 */
 function toUnicode($string){
   $result = array();
   $this->setEncoding();
   $string = $this->toUTF8((string)$string);
   $result = $this->_toUnicode($string);
   unset($string);
   $this->restoreEncoding();
   return $result;
 }

/**
 * this function usage internal only
 *
 * @access private
 */
 function _toUnicode($string){
   $result = array();
   if ($string != null && is_scalar($string)) {
     $state = 0;
     $ucs4  = 0;
     $bytes = 1;
     $string = array_values((array)unpack('C*', (string)$string));
     $length = count($string);
     for ($i = 0; $i < $length; ++$i) {
       $byte = $string[$i];
       if ($state === 0) {
         if (($byte & 0x80) === 0) {
           $result[] = $byte;
           $bytes = 1;
         } else if (($byte & 0xE0) === 0xC0) {
           $ucs4 = $byte;
           $ucs4 = ($ucs4 & 0x1F) << 6;
           $state = 1;
           $bytes = 2;
         } else if (($byte & 0xF0) === 0xE0) {
           $ucs4 = $byte;
           $ucs4 = ($ucs4 & 0x0F) << 12;
           $state = 2;
           $bytes = 3;
         } else if (($byte & 0xF8) === 0xF0) {
           $ucs4 = $byte;
           $ucs4 = ($ucs4 & 0x07) << 18;
           $state = 3;
           $bytes = 4;
         } else if (($byte & 0xFC) === 0xF8) {
           $ucs4 = $byte;
           $ucs4 = ($ucs4 & 0x03) << 24;
           $state = 4;
           $bytes = 5;
         } else if (($byte & 0xFE) === 0xFC) {
           $ucs4 = $byte;
           $ucs4 = ($ucs4 & 1) << 30;
           $state = 5;
           $bytes = 6;
         } else {
           $result = false;
           break;
         }
       } else {
         if (($byte & 0xC0) === 0x80) {
           $shift = ($state - 1) * 6;
           $tmp = $byte;
           $tmp = ($tmp & 0x0000003F) << $shift;
           $ucs4 |= $tmp;
           if (--$state === 0) {
             if (($bytes === 2 && $ucs4 < 0x0080)
              || ($bytes === 3 && $ucs4 < 0x0800)
              || ($bytes === 4 && $ucs4 < 0x10000)
              || ($bytes > 4)
              || (($ucs4 & 0xFFFFF800) === 0xD800)
              || ($ucs4 > 0x10FFFF)) {
               $result = false; // illegal sequence or codepoint
               break;
             }
             if ($ucs4 !== 0xFEFF) {
               $result[] = $ucs4;
             }
             $state = 0;
             $ucs4  = 0;
             $bytes = 1;
           }
         } else {
           $result = false; // incomplete multi-octet sequence
           break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Takes an array of integers representing the Unicode characters
 *  and returns a UTF-8 string.
 * Astral planes are supported
 *  (i.e. the integers in the input can be > 0xFFFF).
 * Occurrences of the BOM are ignored.
 * Surrogates are not allowed.
 *
 * Returns FALSE if the input array contains integers
 *  that represent surrogates or are outside the Unicode range.
 *
 * @param  array   an array of target, which should be Unicode characters
 * @return string  a string which contained as valid UTF-8, or FALSE on error
 * @access public
 */
 function fromUnicode($uni){
   $result = '';
   if (is_array($uni)) {
     $uni = array_values($uni);
     $length = count($uni);
     for ($i = 0; $i < $length; $i++) {
       if ($uni[$i] >= 0x00 && $uni[$i] <= 0x7F) {
         $result .= chr($uni[$i]);
       } else if ($uni[$i] <= 0x7FF) {
         $result .= chr(0xC0 | ($uni[$i] >> 6));
         $result .= chr(0x80 | ($uni[$i] & 0x3F));
       } else if ($uni[$i] === 0xFEFF) {
         continue;
       } else if ($uni[$i] >= 0xD800 && $uni[$i] <= 0xDFFF) {
         $result = false;
         break;
       } else if ($uni[$i] <= 0xFFFF) {
         $result .= chr(0xE0 | ($uni[$i]  >> 12));
         $result .= chr(0x80 | (($uni[$i] >>  6) & 0x3F));
         $result .= chr(0x80 | ($uni[$i] & 0x3F));
       } else if ($uni[$i] <= 0x10FFFF) {
         $result .= chr(0xF0 | ($uni[$i]  >> 18));
         $result .= chr(0x80 | (($uni[$i] >> 12) & 0x3F));
         $result .= chr(0x80 | (($uni[$i] >>  6) & 0x3F));
         $result .= chr(0x80 | ($uni[$i] & 0x3F));
       } else {
         $result = false;
         break;
       }
     }
   }
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_ECMA
 *
 * This class emulates ECMAScript/JavaScript functions as ECMA-262 3rd edition
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_ECMA extends Posql_Object {

/**
 * @var    float     constant value as NaN
 * @access private
 */
 var $NaN = null;

/**
 * @var    Posql_Unicode    maintains instance of the Posql_Unicode class
 * @access private
 */
 var $unicode = null;

/**
 * @var    boolean   whether initialized or not
 * @access private
 */
 var $_inited = false;

/**
 * Class constructor
 *
 * @param  void
 * @return object
 * @access public
 */
 function __construct(){
   parent::__construct();
   $this->_init();
 }

/**
 * Destructor for PHP Version 5+
 * Release all referenced properties
 *
 * @access public
 */
 function __destruct(){
   if (isset($this)) {
     unset($this->NaN, $this->unicode, $this->_inited);
   }
 }

/**
 * Reference to the Posql, and its properties
 *
 * @param  Posql    give of the Posql self
 * @return void
 * @access private
 */
 function _referProperty(&$posql){
   $this->unicode = & $posql->unicode;
 }

/**
 * Initializes class variables
 *
 * @param  void
 * @return void
 * @access private
 */
 function _init(){
   if (empty($this->_inited)) {
     $this->NaN = acos(1.01);
     $this->_inited = true;
   }
 }

/**
 * Returns the character at the specified index
 *
 * @param  string  subject string
 * @param  number  the specified index
 * @return string  the character at the specified index
 * @access public
 */
 function charAt($string, $index = 0){
   $result = '';
   if (is_scalar($string)) {
     $string = (string)$string;
     $index  = (int)$index;
     if ($string != null && $index >= 0) {
       $string = $this->unicode->toUnicode($string);
       if (!empty($string) && is_array($string)
        && array_key_exists($index, $string)) {
         $result = $this->unicode->fromUnicode(array($string[$index]));
         if (!is_string($result)) {
           $result = '';
         }
       }
     }
   }
   return $result;
 }

/**
 * Returns a number indicating the Unicode value
 *  of the character at the given index.
 *
 * @param  string  subject string
 * @param  number  an integer between 0 and string length (default = 0)
 * @return number  a number indicating the Unicode value
 * @access public
 */
 function charCodeAt($string, $index = 0){
   $result = $this->NaN;
   if (is_scalar($string)) {
     $string = (string)$string;
     $index  = (int)$index;
     if ($string != null && $index >= 0) {
       $string = $this->unicode->toUnicode($string);
       if (!empty($string) && is_array($string)
        && array_key_exists($index, $string)) {
         $result = $string[$index];
       }
     }
   }
   return $result;
 }

/**
 * Returns a string created by using the specified sequence of Unicode values
 *
 * @param  number (...)  a sequence of numbers that are Unicode values
 * @return string        created string
 * @access public
 */
 function fromCharCode(){
   $args = func_get_args();
   $result = call_user_func_array(array(&$this->unicode, 'chr'), $args);
   return $result;
 }

/**
 * Returns the index within the string of
 *  the first occurrence of the specified value, or -1 if not found.
 *
 * @see  http://mxr.mozilla.org/mozilla/source/js/src/jsstr.c#970
 *
 * @param  string  subject string
 * @param  string  a string representing the value to search for
 * @param  number  the location within the string to
 *                 start the search from (default = 0)
 * @return number  the index within string, or -1 if not found
 * @access public
 */
 function indexOf($string, $search, $offset = null){
   $result = -1;
   if (is_scalar($string)) {
     $string = (string)$string;
     $offset = (int)$offset;
     if ($string === $search) {
       $result = 0;
     } else if ($string != null) {
       if ($offset === null) {
         $result = $this->unicode->strpos($string, $search);
         if ($result === false) {
           $result = -1;
         }
       } else {
         $offset = (int)$offset;
         $string = $this->split($string);
         $search = $this->split($search);
         $length = count($string);
         $sublen = count($search);
         if ($offset < 0) {
           $i = 0;
         } else if ($offset > $length) {
           $i = $length;
         } else {
           $i = $offset;
         }
         if ($sublen === 0) {
           $result = $i;
         } else {
           $index = -1;
           $j = 0;
           while ($i + $j < $length) {
             if ($string[$i + $j] === $search[$j]) {
               if (++$j === $sublen) {
                 $index = $i;
                 break;
               }
             } else {
               $i++;
               $j = 0;
             }
           }
           $result = $index;
         }
       }
     }
   }
   return $result;
 }

/**
 * Returns the index within the string of
 *  the last occurrence of the specified value, or -1 if not found.
 *
 * @see  http://mxr.mozilla.org/mozilla/source/js/src/jsstr.c#1045
 *
 * @param  string  subject string
 * @param  string  a string representing the value to search for
 * @param  number  the location within the string to
 *                 start the search from (default = 0)
 * @return number  the index within string, or -1 if not found
 * @access public
 */
 function lastIndexOf($string, $search, $offset = null){
   $result = -1;
   if (is_scalar($string)) {
     $string = (string)$string;
     $search = (string)$search;
     if ($string === $search) {
       $result = 0;
     } else if ($string != null) {
       if ($offset === null) {
         $result = $this->unicode->strrpos($string, $search);
         if ($result === false) {
           $result = -1;
         }
       } else {
         $offset = (int)$offset;
         $string = $this->split($string);
         $search = $this->split($search);
         $length = count($string);
         $sublen = count($search);
         if ($offset < 0) {
           $i = 0;
         } else if ($offset > $length) {
           $i = $length;
         } else {
           $i = $offset;
         }
         if ($sublen === 0) {
           $result = $i;
         } else {
           $j = 0;
           while ($i >= 0) {
             if ($i + $j < $length
              && $string[$i + $j] === $search[$j]) {
               if (++$j === $sublen) {
                 break;
               }
             } else {
               $i--;
               $j = 0;
             }
           }
           $result = $i;
         }
       }
     }
   }
   return $result;
 }

/**
 * Reflects the length of the string as Unicode
 *
 * @param  string  subject string
 * @return number  the length of the string as Unicode
 * @access public
 */
 function length($string){
   $result = 0;
   if (is_scalar($string)) {
     $string = (string)$string;
     if ($string != null) {
       $result = $this->unicode->strlen($string);
     }
   }
   return $result;
 }

/**
 * Extracts a section of a string and returns a new string
 *
 * @see  http://mxr.mozilla.org/mozilla/source/js/src/jsstr.c#1971
 *
 * @param  string  subject string
 * @param  number  the zero-based index at which to begin extraction
 * @param  number  the zero-based index at which to end extraction.
 *                 if omitted, extracts to the end of the string.
 * @return string  extracted string
 * @access public
 */
 function slice($string, $begin, $end = null){
   $result = '';
   if (is_scalar($string)) {
     $string = (string)$string;
     if ($string !== '' && $end !== 0) {
       $begin = (int)$begin;
       if ($end === null) {
         $result = $this->unicode->substr($string, $begin);
       } else {
         $end = (int)$end;
         $length = $this->unicode->strlen($string);
         if ($begin < 0) {
           $begin += $length;
           if ($begin < 0) {
             $begin = 0;
           }
         } else if ($begin > $length) {
           $begin = $length;
         }
         if ($end < 0) {
           $end += $length;
           if ($end < 0) {
             $end = 0;
           }
         } else if ($end > $length) {
           $end = $length;
         }
         if ($end < $begin) {
           $end = $begin;
         }
         $result = $this->unicode->substr($string, $begin, $end - $begin);
       }
     }
   }
   return $result;
 }

/**
 * Splits a string into an array of strings by
 *  separating the string into substrings.
 *
 * @param  string  subject string
 * @param  string  specifies the character to use for separating the string
 * @param  number  integer specifying a limit on the number of
 *                 splits to be found
 * @return array   split array
 * @access public
 */
 function split($string, $separator = '', $limit = -1){
   $result = array();
   if (is_scalar($string) && is_scalar($separator)) {
     $string = (string)$string;
     $separator = (string)$separator;
     if ($separator == null) {
       $this->unicode->setEncoding();
       $string = $this->unicode->toUTF8($string, true);
       $result = preg_split('|(.{1})|su', $string, -1,
                   PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
       $result = array_map(array(&$this->unicode, 'fromUTF8'), $result);
       $this->unicode->restoreEncoding();
     } else {
       $result = explode($separator, $string);
     }
     $limit = (int)$limit;
     if ($limit === 0) {
       $result = array();
     } else if ($limit > 0) {
       $result = array_slice($result, 0, $limit);
     }
   }
   return $result;
 }

/**
 * Return the characters in string beginning at the specified
 *  location through the specified number of characters.
 *
 * @see  http://mxr.mozilla.org/mozilla/source/js/src/jsstr.c#1894
 *
 * @param  string  subject string
 * @param  number  location at which to begin extracting characters
 * @param  number  The number of characters to extract
 * @return string  extracted string
 * @access public
 */
 function substr($string, $start, $length = null){
   $result = '';
   if (is_scalar($string)) {
     $string = (string)$string;
     if ($string !== '' && $length !== 0) {
       $start = (int)$start;
       if ($length === null) {
         $result = $this->unicode->substr($string, $start);
       } else {
         $strlen = $this->unicode->strlen($string);
         if ($start < 0) {
           $start += $strlen;
           if ($start < 0) {
             $start = 0;
           }
         } else if ($start > $strlen) {
           $start = $strlen;
         }
         $length = (int)$length;
         if ($length < 0) {
            $length = 0;
         }
         $length += $start;
         if ($length > $strlen) {
           $length = $strlen;
         }
         $result = $this->unicode->substr($string, $start, $length - $start);
       }
     }
   }
   return $result;
 }

/**
 * Returns the characters in a string between two indexes into the string
 *
 * @see  http://mxr.mozilla.org/mozilla/source/js/src/jsstr.c#703
 *
 * @param  string  subject string
 * @param  number  first index between 0 and string length
 * @param  number  (optional) next index between 0 and string length
 * @return string  extracted string
 * @access public
 */
 function substring($string, $begin, $end = null){
   $result = '';
   if (is_scalar($string)) {
     $string = (string)$string;
     if ($string !== '') {
       $length = $this->unicode->strlen($string);
       $begin = (int)$begin;
       if ($begin < 0) {
         $begin = 0;
       } else if ($begin > $length) {
         $begin = $length;
       }
       if ($end === null) {
         $end = $length;
       } else {
         $end = (int)$end;
         if ($end < 0) {
           $end = 0;
         } else if ($end > $length) {
           $end = $length;
         }
         if ($end < $begin) {
           $tmp = $begin;
           $begin = $end;
           $end = $tmp;
         }
       }
       $result = $this->unicode->substr($string, $begin, $end - $begin);
     }
   }
   return $result;
 }

/**
 * Return the string that converted to lowercase
 *
 * @param  string  subject string
 * @return string  converted string
 * @access public
 */
 function toLowerCase($string){
   $result = '';
   if (is_scalar($string)) {
     $string = (string)$string;
     if ($string !== '') {
       $result = $this->unicode->strtolower($string);
     }
   }
   return $result;
 }

/**
 * Return the string that converted to uppercase
 *
 * @param  string  subject string
 * @return string  converted string
 * @access public
 */
 function toUpperCase($string){
   $result = '';
   if (is_scalar($string)) {
     $string = (string)$string;
     if ($string !== '') {
       $result = $this->unicode->strtoupper($string);
     }
   }
   return $result;
 }

/**
 * Return a string representing the specified object
 *
 * @param  mixed   subject object
 * @return string  a string representing the specified object
 * @access public
 */
 function toString($object){
   $result = null;
   switch (true) {
     case is_int($object):
     case is_float($object):
     case is_string($object):
     case is_resource($object):
         $result = (string)$object;
         break;
     case is_bool($object):
         $result = $object ? 'true' : 'false';
         break;
     case is_null($object):
         $result = 'null';
         break;
     case is_object($object):
         foreach (array('toString', '__toString') as $method) {
           $func = array($object, $method);
           if (is_callable($func)) {
             $result = @call_user_func($func);
             break 2;
           }
         }
     case is_array($object):
         ob_start();
         var_export($object);
         $result = ob_get_contents();
         ob_end_clean();
         break;
     default:
         $result = '';
         break;
   }
   if (!is_string($result)) {
     $result = '';
   }
   return $result;
 }

/**
 * Encodes a string,
 * replacing certain characters with a hexadecimal escape sequence.
 *
 * Note:
 *  Not part of any standard.
 *  Mentioned in a non-normative section of ECMA-262.
 *
 * See ECMA-262 Edition 3 B.2.1
 *
 * This method handles any encoding with Unicode,
 * and it is different on JavaScript.
 *
 * @see http://mxr.mozilla.org/mozilla/ident?i=js_str_escape
 *
 * @param  string  subject string
 * @return string  escaped string
 * @access public
 */
 function escape($string){
   static $digits = array('0', '1', '2', '3', '4', '5', '6', '7',
                          '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'),
   $url_char_types = array(
   //      Bit 0         xalpha          -- the alphas
   //      Bit 1         xpalpha         -- as xalpha but
   //                           converts spaces to plus and plus to %20
   //      Bit 2 ...     path            -- as xalphas but doesn't escape '/'
   //
   //   0 1 2 3 4 5 6 7 8 9 A B C D E F
        0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,       // 0x
        0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,       // 1x
        0,0,0,0,0,0,0,0,0,0,7,4,0,7,7,4,       // 2x   !"#$%&'()*+,-./
        7,7,7,7,7,7,7,7,7,7,0,0,0,0,0,0,       // 3x  0123456789:;<=>?
        7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,       // 4x  @ABCDEFGHIJKLMNO
        7,7,7,7,7,7,7,7,7,7,7,0,0,0,0,7,       // 5X  PQRSTUVWXYZ[\]^_
        0,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,       // 6x  `abcdefghijklmno
        7,7,7,7,7,7,7,7,7,7,7,0,0,0,0,0,       // 7X  pqrstuvwxyz{\}~  DEL
        0
   );
   $result = '';
   $url_xalphas  = 1;
   $url_xpalphas = 2;
   $url_path     = 4;
   $mask = $url_xalphas | $url_xpalphas | $url_path;
   if ($string != null) {
     $result = '';
     $string = $this->unicode->toUnicode($string);
     if (!empty($string) && is_array($string)) {
       $length = count($string);
       for ($i = 0; $i < $length; $i++) {
         $ch = $string[$i];
         if ($ch < 128 && ($url_char_types[$ch] & $mask)) {
           $result .= chr($ch);
         } else if ($ch < 256) {
           if ($mask === $url_xpalphas && $ch === ' ') {
             $result .= '+';
           } else {
             $result .= '%' . $digits[$ch >> 4] . $digits[$ch & 0xF];
           }
         } else {
           $result .= '%u' . $digits[$ch >> 12]
                           . $digits[($ch & 0xF00) >> 8]
                           . $digits[($ch & 0xF0) >> 4]
                           . $digits[$ch & 0xF];
         }
       }
     }
   }
   return $result;
 }

/**
 * Decodes a value that has been encoded in hexadecimal (e.g., a cookie).
 *
 * Note:
 *  Not part of any standard.
 *  Mentioned in a non-normative section of ECMA-262.
 *
 * See ECMA-262 Edition 3 B.2.2
 *
 * This method handles any encoding with Unicode,
 * and it is different on JavaScript.
 *
 * @see http://mxr.mozilla.org/mozilla/ident?i=str_unescape
 *
 * @param  string  subject string
 * @return string  decoded string
 * @access public
 */
 function unescape($string){
   $result = '';
   $string = (string)$string;
   if ($string != null) {
     $i = 0;
     $chars = array();
     $length = strlen($string);
     while ($i < $length) {
       $ch = $string[$i++];
       if ($ch === '%') {
         if ($i + 1 < $length
          && $this->_isHex($string[$i])
          && $this->_isHex($string[$i + 1])) {
           $ch = (hexdec($string[$i]) << 4) + hexdec($string[$i + 1]);
           $i += 2;
         } else if ($i + 4 < $length && $string[$i] === 'u'
                 && $this->_isHex($string[$i + 1])
                 && $this->_isHex($string[$i + 2])
                 && $this->_isHex($string[$i + 3])
                 && $this->_isHex($string[$i + 4])) {
           $ch = (((((hexdec($string[$i + 1])  << 4)
                    + hexdec($string[$i + 2])) << 4)
                    + hexdec($string[$i + 3])) << 4)
                    + hexdec($string[$i + 4]);
           $i += 5;
         }
       }
       if (is_int($ch)) {
         $ch = $this->unicode->chr($ch);
       }
       $result .= $ch;
     }
   }
   return $result;
 }

/**
 * ECMA 3, 15.1.3 URI Handling Function Properties
 *
 * The following are implementations of the algorithms
 * given in the ECMA specification for the hidden functions
 * 'Encode' and 'Decode'.
 *
 * @access private
 */
 function _encode($string, $unescaped_set = null, $unescaped_set2 = null){
   $result = '';
   $string = (string)$string;
   if ($string != null) {
     $chars = $this->unicode->toUnicode($string);
     unset($string);
     if (!empty($chars) && is_array($chars)) {
       $length = count($chars);
       $hex_buf = array('%');
       for ($k = 0; $k < $length; $k++) {
         $c = $chars[$k];
         if (isset($unescaped_set[$c])
          || isset($unescaped_set2, $unescaped_set2[$c])) {
           $result .= chr($c);
         } else {
           if ($c >= 0xDC00 && $c <= 0xDFFF) {
             $result = '';
             break;
           }
           if ($c < 0xD800 || $c > 0xDBFF) {
             $v = $c;
           } else {
             $k++;
             if ($k === $length) {
               $result = '';
               break;
             }
             $c2 = $chars[$k];
             if ($c2 < 0xDC00 || $c2 > 0xDFFF) {
               $result = '';
               break;
             }
             $v = (($c - 0xD800) << 10) + ($c2 - 0xDC00) + 0x10000;
           }
           // convert code into a UTF-8 buffer
           // which must be at least 6 bytes long.
           // rawurlencode() is a shortcut for process.
           $result .= rawurlencode($this->unicode->chr($v));
         }
       }
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function _decode($string, $reserved_set = null){
   $result = '';
   $string = (string)$string;
   if ($string != null) {
     $chars = $this->unicode->toUnicode($string);
     unset($string);
     if (!empty($chars) && is_array($chars)) {
       $length = count($chars);
       $percent = ord('%');
       for ($k = 0; $k < $length; $k++) {
         $c = $chars[$k];
         if ($c === $percent) {
           $start = $k;
           if ($k + 2 >= $length) {
             $result = '';
             break;
           }
           if (!$this->_isHex($this->unicode->chr($chars[$k + 1]))
            || !$this->_isHex($this->unicode->chr($chars[$k + 2]))) {
             $result = '';
             break;
           }
           $b = (hexdec($this->unicode->chr($chars[$k + 1])) << 4)
              +  hexdec($this->unicode->chr($chars[$k + 2]));
           $k += 2;
           if (!($b & 0x80)) {
             $c = $b;
           } else {
             $n = 1;
             while ($b & (0x80 >> $n)) {
               $n++;
             }
             if ($n === 1 || $n > 6) {
               $result = '';
               break;
             }
             $octets = array($b);
             if ($k + 3 * ($n - 1) >= $length) {
               $result = '';
               break;
             }
             for ($j = 1; $j < $n; $j++) {
               $k++;
               if ($chars[$k] !== $percent) {
                 $result = '';
                 break 2;
               }
               if (!$this->_isHex($this->unicode->chr($chars[$k + 1]))
                || !$this->_isHex($this->unicode->chr($chars[$k + 2]))) {
                 $result = '';
                 break 2;
               }
               $b = (hexdec($this->unicode->chr($chars[$k + 1])) << 4)
                  +  hexdec($this->unicode->chr($chars[$k + 2]));
               if (($b & 0xC0) !== 0x80) {
                 $result = '';
                 break 2;
               }
               $k += 2;
               $octets[$j] = $b;
             }
             $v = $this->utf8ToOneUcs4Char($octets, $n);
             if ($v >= 0x10000) {
               $v -= 0x10000;
               if ($v > 0xFFFFF) {
                 $result = '';
                 break;
               }
               $c = (($v & 0x3FF) + 0xDC00);
               $h = (($v >> 10) + 0xD800);
               $result .= $this->unicode->chr($h);
             } else {
               $c = $v;
             }
           }
           if (isset($reserved_set, $reserved_set[$c])) {
             $len = $k - $start + 1;
             $idx = $start;
             while (--$len >= 0) {
               $result .= $this->unicode->chr($chars[$idx++]);
             }
           } else {
             $result .= $this->unicode->chr($c);
           }
         } else {
           $result .= $this->unicode->chr($c);
         }
       }
     }
   }
   if ($result != null) {
     //
     // Note: Using $posql->charset for not UTF-8 encoding
     //
     $result = $this->unicode->fromUTF8($result);
   }
   return $result;
 }

/**
 * @access private
 */
 function _isHex($x){
   $result = false;
   if (strlen($x) === 1) {
     if (($x >= '0' && $x <= '9')
      || ($x >= 'A' && $x <= 'F')
      || ($x >= 'a' && $x <= 'f')) {
       $result = true;
     }
   }
   return $result;
 }

/**
 * Convert a utf8 character sequence into a UCS-4 character and return that
 * character.  It is assumed that the caller already checked that the sequence
 * is valid.
 *
 * @access private
 */
 function utf8ToOneUcs4Char($utf8_buffer, $utf8_length){
   // from Unicode 3.1, non-shortest form is illegal
   static $minucs4_tables = array(
     0x00000080, 0x00000800, 0x0001000, 0x0020000, 0x0400000
   );
   $i = 0;
   if ($utf8_length === 1) {
     $ucs4_char = $utf8_buffer[$i];
   } else {
     $ucs4_char = $utf8_buffer[$i++] & ((1 << (7 - $utf8_length)) - 1);
     $minucs4_char = $minucs4_tables[$utf8_length - 2];
     while (--$utf8_length) {
       $ucs4_char = $ucs4_char << 6 | ($utf8_buffer[$i++] & 0x3F);
     }
     if ($ucs4_char < $minucs4_char
      || $ucs4_char === 0xFFFE || $ucs4_char === 0xFFFF) {
       $ucs4_char = 0xFFFD;
     }
   }
   return $ucs4_char;
 }

/**
 * URI reserved plus pound
 *
 * @access private
 */
 function _getURIReservedPlusPound(){
   static $inited = false, $uri_reserved_plus_pounds = array(
     ';', '/', '?', ':', '@', '&', '=', '+', '$', ',', '#'
   );
   if (!$inited) {
     $uri_reserved_plus_pounds = array_flip(
       array_map('ord', $uri_reserved_plus_pounds)
     );
     $inited = true;
   }
   return $uri_reserved_plus_pounds;
 }

/**
 * URI unescaped
 *
 * @access private
 */
 function _getURIUnescaped(){
   static $inited = false, $uri_unescaped = array(
     '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
     'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
     'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
     'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
     'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
     '-', '_', '.', '!', '~', '*', '\'', '(', ')'
   );
   if (!$inited) {
     $uri_unescaped = array_flip(array_map('ord', $uri_unescaped));
     $inited = true;
   }
   return $uri_unescaped;
 }

/**
 * Encodes a Uniform Resource Identifier (URI) by replacing each
 * instance of certain characters by one, two, three, or
 * four escape sequences representing the UTF-8 encoding of the character
 * (will only be four escape sequences for characters composed of two
 *  "surrogate" characters).
 *
 * Assumes that the URI is a complete URI, so does not encode reserved
 * characters that have special meaning in the URI.
 *
 * Compatibility with UTF-8.
 *
 * @see http://mxr.mozilla.org/mozilla/ident?i=str_encodeURI
 *
 * @param  string  subject string
 * @return string  encoded string
 * @access public
 */
 function encodeURI($string){
   $result = $this->_encode($string, $this->_getURIReservedPlusPound(),
                                     $this->_getURIUnescaped());
   return $result;
 }

/**
 * Encodes a Uniform Resource Identifier (URI) component by replacing each
 * instance of certain characters by one, two, three, or four escape
 * sequences representing the UTF-8 encoding of the character
 * (will only be four escape sequences for characters composed of two
 *  "surrogate" characters).
 *
 * encodeURIComponent escapes all characters except the following:
 * alphabetic, decimal digits, - _ . ! ~ * ' ( )
 *
 * Compatibility with UTF-8.
 *
 * @see http://mxr.mozilla.org/mozilla/ident?i=str_encodeURI_Component
 *
 * @param  string  subject string
 * @return string  encoded string
 * @access public
 */
 function encodeURIComponent($string){
   $result = $this->_encode($string, $this->_getURIUnescaped());
   return $result;
 }

/**
 * Decodes a Uniform Resource Identifier (URI) previously
 * created by encodeURI or by a similar routine.
 *
 * Replaces each escape sequence in the encoded URI
 * with the character that it represents.
 * Does not decode escape sequences that could not have
 * been introduced by encodeURI.
 *
 * Compatibility with UTF-8.
 *
 * @see http://mxr.mozilla.org/mozilla/ident?i=str_decodeURI
 *
 * @param  string  subject string
 * @return string  decoded string
 * @access public
 */
 function decodeURI($string){
   $result = $this->_decode($string, $this->_getURIReservedPlusPound());
   return $result;
 }

/**
 * Decodes a Uniform Resource Identifier (URI) component previously
 * created by encodeURIComponent or by a similar routine.
 *
 * Replaces each escape sequence in the encoded URI component with
 * the character that it represents.
 *
 * Compatibility with UTF-8.
 *
 * @see http://mxr.mozilla.org/mozilla/ident?i=str_decodeURI_Component
 *
 * @param  string  subject string
 * @return string  decoded string
 * @access public
 */
 function decodeURIComponent($string){
   $result = $this->_decode($string, null);
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Archive
 *
 * This class implements compression and the archive methods
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Archive extends Posql_Object {

/**
 * @var    Posql    maintains reference of the Posql class instance
 * @access private
 */
 var $posql = null;

/**
 * @var    boolean   whether initialized or not
 * @access private
 */
 var $_inited = false;

/**
 * Class constructor
 *
 * @param  void
 * @return object
 * @access public
 */
 function __construct(){
   parent::__construct();
   $this->_init();
 }

/**
 * Destructor for PHP Version 5+
 * Release all referenced properties
 *
 * @access public
 */
 function __destruct(){
   if (isset($this)) {
     unset($this->posql);
     foreach (get_object_vars($this) as $prop => $val) {
       if ($prop != null) {
         unset($this->{$prop});
       }
     }
   }
 }

/**
 * Reference to the Posql, and its properties
 *
 * @param  Posql    give of the Posql self
 * @return void
 * @access private
 */
 function _referProperty(&$posql){
   $this->posql = & $posql;
 }

/**
 * Initializes class variables
 *
 * @param  void
 * @return void
 * @access private
 */
 function _init(){
   if (empty($this->_inited)) {
     $this->_inited = true;
   }
 }

/**
 * Get the constant character string as alphameric base 63
 *
 * Alphameric base 63 equels RegExp: [0-9A-Za-z_]
 *
 * @param  void
 * @return string  constant character string as alphameric base 63
 * @access public
 */
 function getAlphamericBase63(){
   static $base63 =
   '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_';
   return $base63;
 }

/**
 * Encode the character string into the composition only of
 * the alphanumeric 63 based characters, and it compressed by
 * the Lz77 algorithm.
 *
 * This function transplanted for PHP which is implemented on JavaScript.
 *
 * @see http://nurucom-archives.hp.infoseek.co.jp/digital/Alphameric-HTML.html
 *
 * @param  string  subject string to compress
 * @return string  encoded and compressed string
 * @access public
 */
 function encodeAlphamericString($s){
   static $b = null;
   $js = & $this->posql->ecma;
   if ($b === null) {
     $b = $js->split($this->getAlphamericBase63(), '');
   }
   $a = '';
   $c = null;
   $j = null;
   $o = null;
   $k = null;
   $h = null;
   $p = null;
   $x = null;
   $i = 1014;
   $l = -1;
   $t = ' ';
   for (; $i < 1024; $i++) {
     $t .= $t;
   }
   $t .= $s;
   while (true) {
     $p = $js->substr($t, $i, 64);
     if ($p == null) {
       break;
     }
     $n = $js->length($p);
     for ($j = 2; $j <= $n; $j++) {
       $k = $js->lastIndexOf(
         $js->substring($t, $i - 819, $i + $j - 1),
         $js->substring($p, 0, $j)
       );
       if (-1 === $k) {
         break;
       }
       $o = $k;
     }
     if (2 === $j || 3 === $j && $h === $l) {
       $h = $l;
       $c = $js->charCodeAt($t, $i);
       $i++;
       if ($c < 128) {
         $x = $c;
         $l = ($x - ($c %= 32)) / 32 + 64;
         if ($h !== $l) {
           $a .= $b[$l - 32];
         }
         $a .= $b[$c];
       } else if (12288 <= $c && $c < 12544) {
         $l = (($c -= 12288) - ($c %= 32)) / 32 + 68;
         if ($h !== $l) {
           $a .= $b[$l - 32];
         }
         $a .= $b[$c];
       } else if (65280 <= $c && $c < 65440) {
         $l = (($c -= 65280) - ($c %= 32)) / 32 + 76;
         if ($h !== $l) {
           $a .= $b[$l - 32];
         }
         $a .= $b[$c];
       } else {
         $x = $c;
         $l = ($x - ($c %= 1984)) / 1984;
         if ($h !== $l) {
           $a .= 'n' . $b[$l];
         }
         $x = $c;
         $a .= $b[($x - ($c %= 62)) / 62] . $b[$c];
       }
     } else {
       $x = $o;
       $a .= $b[($x - ($o %= 63)) / 63 + 50] . $b[$o] . $b[$j - 3];
       $i += $j - 1;
     }
   }
   unset($js);
   return $a;
 }

/**
 * Decode the character string converted by
 * the encodeAlphamericString() method.
 *
 * This function transplanted for PHP which is implemented on JavaScript.
 *
 * @see http://nurucom-archives.hp.infoseek.co.jp/digital/Alphameric-HTML.html
 *
 * @param  string  subject string to decode
 * @return string  decoded and uncompressed string
 * @access public
 */
 function decodeAlphamericString($a){
   static $o = null;
   $js = & $this->posql->ecma;
   if ($o === null) {
     $o = array();
     $b = $this->getAlphamericBase63();
     for ($i = 0; $i < 63; $i++) {
       $o[$js->charAt($b, $i)] = $i;
     }
   }
   $c = null;
   $j = null;
   $k = null;
   $l = null;
   $m = null;
   $p = null;
   $w = null;
   $t = null;
   $s = '    ';
   $i = 63;
   while ($i -= 7) {
     $s .= $s;
   }
   while (true) {
     $t = $js->charAt($a, $i);
     $i++;
     if (!isset($o[$t])) {
       break;
     }
     $c = $o[$t];
     if ($c >= 63) {
       break;
     }
     if ($c < 32) {
       $s .= $js->fromCharCode(
         $m ?  $l * 32 + $c
            : ($l * 32 + $c) * 62 + $o[$js->charAt($a, $i++)]
       );
     } else if ($c < 49) {
       $l = ($c < 36 ? $c - 32  :
            ($c < 44 ? $c + 348 : $c + 1996));
       $m = 1;
     } else if ($c < 50) {
       $l = $o[$js->charAt($a, $i++)];
       $m = 0;
     } else {
       $w = $js->slice($s, -819);
       $k = ($c - 50) * 63 + $o[$js->charAt($a, $i++)];
       $j = $k + $o[$js->charAt($a, $i++)] + 2;
       $p = $js->substring($w, $k, $j);
       if ($p != null) {
         while ($js->length($w) < $j) {
           $w .= $p;
         }
       }
       $s .= $js->substring($w, $k, $j);
     }
   }
   $s = $js->slice($s, 1024);
   unset($js);
   return $s;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Method
 *
 * This class provides standard SQL92-99 functions on SQL mode
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Method extends Posql_Object {

/**
 * @var    boolean  maintains mbstring library that can be use
 * @access private
 */
 var $hasMBString;

/**
 * @var    boolean  maintains iconv library that can be use
 * @access private
 */
 var $hasIConv;

/**
 * @var    string   maintains reference to the Posql's property charset
 * @access private
 */
 var $charset;

/**
 * @var    Posql   maintains reference of the Posql class
 * @access private
 */
 var $posql;

/**
 * @var    Posql_CType   maintains reference of the Posql_CType class
 * @access private
 */
 var $ctype;

/**
 * @var    Posql_Math   maintains reference of the Posql_Math class
 * @access private
 */
 var $math;

/**
 * @var    Posql_Unicode   maintains reference of the Posql_Unicode class
 * @access private
 */
 var $unicode;

/**
 * Class constructor
 *
 * @param  void
 * @return object
 * @access public
 */
 function __construct(){
   parent::__construct();
 }

/**
 * Destructor for PHP Version 5+
 * Release all referenced properties
 *
 * @access public
 */
 function __destruct(){
   if (isset($this)) {
     unset($this->charset, $this->hasIConv, $this->hasMBString);
     unset($this->unicode, $this->math, $this->ctype);
     unset($this->posql);
     foreach (get_object_vars($this) as $prop => $val) {
       if ($prop != null) {
         unset($this->{$prop});
       }
     }
   }
 }

/*
 {{{   Internal functions
*/

/**
 * Reference to the Posql, and its properties
 *
 * @param  Posql    give of the Posql self
 * @return void
 * @access private
 */
 function _referObject(&$posql){
   $this->posql       = & $posql;
   $this->ctype       = & $posql->ctype;
   $this->math        = & $posql->math;
   $this->unicode     = & $posql->unicode;
   $this->hasMBString = & $posql->unicode->hasMBString;
   $this->hasIConv    = & $posql->unicode->hasIConv;
   $this->charset     = & $posql->charset;
 }

/**
 * A handle for encoding as base64
 */
 function base64_encode($value){
   $result = base64_encode($value);
   return $result;
 }

/**
 * A handle for decoding as base64
 */
 function base64_decode($value){
   $result = base64_decode($value);
   return $result;
 }

/**
 * A handle for encoding as URL
 */
 function urlencode($value){
   $result = urlencode($value);
   return $result;
 }

/**
 * A handle for decoding as URL
 */
 function urldecode($value){
   $result = urldecode($value);
   return $result;
 }

/*
 }}}   Internal functions
*/
/*
 {{{   Interchangeability with built-in function of SQLite
*/

/**
 * Return the absolute value of the numeric argument.
 * Return NULL if argument is NULL.
 * Return 0 if argument is not a numeric value.
 */
 function abs($value = null){
   $result = null;
   if ($value === null) {
     $result = null;
   } else if (is_numeric($value)) {
     $result = $this->math->abs($value);
   } else {
     $result = 0;
   }
   return $result;
 }

/**
 * NULL is returned when the first non-NULL value of
 *  the list is returned or there is no non-NULL value.
 */
 function coalesce(){
   $result = null;
   $argn = func_num_args();
   if ($argn) {
     $args = func_get_args();
     while (--$argn >= 0) {
       $arg = array_shift($args);
       if ($arg !== null) {
         $result = $arg;
         break;
       }
     }
   }
   return $result;
 }

/**
 * Return NULL when the value is NULL.
 * Return TRUE when the expression is exist.
 * Return FALSE when the expression is not exist.
 */
 function exists($expr = null){
   $result = null;
   if ($expr !== null) {
     $result = (bool)$expr;
   }
   return $result;
 }

/**
 * Return a copy of the first non-NULL argument.
 * If both arguments are NULL then NULL is returned.
 * The ifnull() functions and coalesce()
 *  with two arguments are interchangeable.
 */
 function ifnull($expr1 = null, $expr2 = null){
   $args = func_get_args();
   $result = call_user_func_array(array($this, 'coalesce'), $args);
   return $result;
 }

/**
 * The argument is interpreted as a BLOB.
 * The result is a hexadecimal rendering of the content of that blob.
 */
 function hex($value = null){
   $result = null;
   if ($value !== null) {
     if ($value === '') {
       $result = '';
     } else if ($value == null) {
       $result = '0';
     } else if ($this->math->isDec($value)) {
       $result = $this->math->convertToBase($value, 10, 16);
     } else if ($this->math->isHex($value)) {
       $result = $this->math->toHex($value);
     } else {
       $result = strtoupper(bin2hex($value));
     }
   }
   unset($value);
   return $result;
 }

/**
 * Return the "rowid" of the last row insert
 *  from this connection to the database.
 */
 function last_insert_rowid(){
   $result = null;
   if (isset($this->posql->next)) {
     $result = $this->math->sub($this->posql->next, 1);
     if ($result < 0) {
       $result = '0';
     }
   }
   return $result;
 }

/**
 * Return the string length in characters.
 * This function corresponds to Unicode
 *  (i.e. multibytes characters are counted as one character).
 */
 function length($value = null){
   $result = null;
   if ($value !== null) {
     if ($value == null) {
       $result = 0;
     } else {
       if ($this->posql->pcharset->isBinary($value)) {
         $result = strlen($value);
       } else {
         $result = $this->unicode->strlen($value);
       }
     }
   }
   return $result;
 }

/**
 * This function is used to implement
 *  the "subject LIKE pattern [ESCAPE escape]" syntax of SQL.
 * If the optional ESCAPE clause is present,
 *  then the user-function is invoked with three arguments.
 * Otherwise, it is invoked with two arguments only.
 * The result will be a logical value
 *  whether the subject was matched in the pattern.
 */
 function like($pattern = null, $subject = null, $escape = '\\'){
   $result = null;
   $argn = func_num_args();
   if ($argn && $pattern != null && $subject != null) {
     $pattern = $this->posql->toLikePattern($pattern, $escape);
     if (preg_match($pattern . 'A', $subject)) {
       $result = true;
     } else {
       $result = false;
     }
   }
   return $result;
 }

/**
 * Return a copy of input string that converted to all lower-case letters.
 * This function corresponds to Unicode.
 */
 function lower($string = null){
   $result = null;
   if ($string !== null) {
     $result = $this->unicode->strtolower($string);
   }
   return $result;
 }

/**
 * Return a string formed by removing any and all characters that appear
 *  in second argument from the left side of first argument.
 * If the second argument is omitted, spaces are removed.
 */
 function ltrim($string = null, $chars = null){
   if ($string != null) {
     if ($chars === null) {
       $string = ltrim($string);
     } else {
       $string = ltrim($string, $chars);
     }
   }
   return $string;
 }

/**
 * Return the argument with the maximum value.
 */
 function max($expr1 = null, $expr2 = null){
   $result = null;
   $argn = func_num_args();
   switch ($argn) {
     case 0:
         $result = null;
         break;
     case 1:
         $result = $expr1;
         break;
     case 2:
         $result = $expr1 > $expr2 ? $expr1 : $expr2;
         break;
     default:
         $args = func_get_args();
         $result = call_user_func_array('max', $args);
         break;
   }
   return $result;
 }

/**
 * Return the argument with the minimum value.
 */
 function min($expr1 = null, $expr2 = null){
   $result = null;
   $argn = func_num_args();
   switch ($argn) {
     case 0:
         $result = null;
         break;
     case 1:
         $result = $expr1;
         break;
     case 2:
         $result = $expr1 > $expr2 ? $expr2 : $expr1;
         break;
     default:
         $args = func_get_args();
         $result = call_user_func_array('min', $args);
         break;
   }
   return $result;
 }

/**
 * Return the first argument if the arguments are different,
 *  otherwise return NULL.
 */
 function nullif($expr1 = null, $expr2 = null){
   $result = null;
   if ($expr1 != $expr2) {
     $result = $expr1;
   }
   return $result;
 }

/**
 * Return a pseudo-random integer.
 * Give the minimum value as the first argument,
 *  and give the maximum value as the 2nd argument.
 * If the argument is omitted,
 *  the number between -9999999999 and +9999999999 will be returned.
 */
 function random($min = '-9999999999', $max = '9999999999'){
   $result = $this->math->rand($min, $max);
   return $result;
 }

/**
 * Return a string formed by substituting string third argument
 *  for every occurrence of string second argument in string first argument.
 * The BINARY collating sequence is used for comparisons.
 * If second argument is an empty string then return first argument unchanged.
 */
 function replace($string = null, $search = null, $replace = null){
   if ($string != null && $search != null) {
     $string = str_replace($search, $replace, $string);
   }
   return $string;
 }

/**
 * Round off the number of first argument
 *  to the second argument digits to the right of the decimal point.
 * If the first argument is omitted, 0 is assumed.
 */
 function round($value = null, $scale = 0){
   $result = null;
   if ($value !== null) {
     $result = $this->math->round($value, $scale);
   }
   return $result;
 }

/**
 * Return a string formed by removing any and all characters
 *  that appear in second argument from the right side of forst argument.
 * If the second argument is omitted, spaces are removed.
 */
 function rtrim($string = null, $chars = null){
   if ($string != null) {
     if ($chars === null) {
       $string = rtrim($string);
     } else {
       $string = rtrim($string, $chars);
     }
   }
   return $string;
 }

/**
 * Compute the soundex encoding of the string.
 */
 function soundex($string = null){
   $result = '?000';
   if ($string !== null) {
     $soundex = @soundex($string);
     if ($soundex != null) {
       $result = $soundex;
     }
   }
   return $result;
 }

/**
 * Return the version string for the Posql library that is running.
 */
 function posql_version(){
   $result = $this->posql->getVersion();
   return $result;
 }

/**
 * Return format part from subject timestamp string
 *
 * @link http://php.net/manual/function.strftime
 */
 function strftime($format = null, $time = null){
   $result = null;
   if ($format !== null) {
     if ($time === null) {
       $result = @strftime($format);
     } else {
       $result = @(strftime($format, strtotime($time)));
     }
   }
   return $result;
 }

/**
 * Return a substring of input string.
 * If length is omitted then all character through
 *  the end of the string are returned.
 * The left-most substring is number 1.
 * If length is negative the the first character of the substring is found
 *  by counting from the right rather than the left.
 * If substring is string then characters indices refer
 *  to actual UTF-8 characters.
 * If the input string is a BLOB then the indices refer to bytes.
 */
 function substr($string = null, $offset = 0, $length = null){
   $result = '';
   if ($string !== null) {
     $offset = (int)$offset;
     if ($offset > 0) {
       $offset--;
     }
     if ($this->posql->pcharset->isBinary($string)) {
       if ($length - 0 === 0) {
         $result = substr($string, $offset);
       } else {
         $result = substr($string, $offset, $length);
       }
     } else {
       if ($length - 0 === 0) {
         $result = $this->unicode->substr($string, $offset);
       } else {
         $result = $this->unicode->substr($string, $offset, $length);
       }
     }
     unset($string);
   }
   return $result;
 }

/**
 * The TRIM function removes leading spaces, trailing characters,
 *  or both from a specified character string.
 * This function also removes other types of characters
 *  from a specified character string.
 * The default function is to trim the specified character
 *  from both sides of the character string.
 * If no removal string is specified, TRIM removes spaces by default.
 *
 * This function is used to implement the
 *  "TRIM ([[{LEADING | TRAILING | BOTH}]
 *         [removal_string] FROM]
 *         target_string)"
 *  syntax of SQL.
 */
 function trim($direction = 'both', $chars = ' ',
               $string = null, $collation = null){
   $argn = func_num_args();
   if ($argn <= 2) {
     $string = $direction;
     if ($argn === 1) {
       $string = trim($string);
     } else {
       $string = trim($string, $chars);
     }
   } else {
     if ($chars == null) {
       $chars = '\s';
     } else {
       $chars = preg_quote($chars, '~');
     }
     $pattern = sprintf('(?:%s){1,}', $chars);
     switch (strtolower($direction)) {
       case 'leading':
           $pattern = sprintf('~^%s~i', $pattern);
           break;
       case 'trailing':
           $pattern = sprintf('~%s$~i', $pattern);
           break;
       case 'both':
       default:
           $pattern = sprintf('~^%1$s|%1$s$~i', $pattern);
           break;
     }
     $string = preg_replace($pattern, '', $string);
   }
   return $string;
 }

/**
 * Return the datatype of the expression.
 * The only return values are below:
 * -----------------------------------------------
 *  null    : the value is NULL
 *  boolean : the value is TRUE, or FALSE
 *  number  : the numeric value
 *  text    : the textable (i.e. readable) value
 *  blob    : the binary data
 * -----------------------------------------------
 */
 function typeof($value = null){
   $result = 'null';
   if ($value !== null) {
     if (is_bool($value)) {
       $result = 'boolean';
     } else if (is_numeric($value)) {
       $result = 'number';
     } else {
       if ($value != null
        && $this->posql->pcharset->isBinary($value)) {
         $result = 'blob';
       } else {
         $result = 'text';
       }
     }
   }
   return $result;
 }

/**
 * Return a copy of input string that converted to all upper-case letters.
 * This function corresponds to Unicode.
 */
 function upper($string = null){
   $result = null;
   if ($string !== null) {
     $result = $this->unicode->strtoupper($string);
   }
   return $result;
 }

/*
 }}} Interchangeability with built-in function of SQLite
*/
/*
 {{{  general functions and SQL92-99 syntaxes
*/

/**
 *
 * The available types are below:
 * -----------------------------------------------
 *  null    : the value is NULL
 *  boolean : the value is TRUE, or FALSE
 *  number  : the numeric value
 *  text    : the textable (i.e. readable) value
 *  blob    : the binary data
 * -----------------------------------------------
 */
 function cast($value = null, $type = null){
   $result = null;
   switch (strtolower($type)) {
     case 'boolean':
         $result = (bool)$value;
         break;
     case 'number':
         $result = $this->math->format($value);
         break;
     case 'blob':
     case 'text':
         $result = (string)$value;
         break;
     case 'null':
         $result = null;
         break;
     default:
         $affinity = $this->posql->getColumnAffinity($type);
         switch (strtolower($affinity)) {
           case 'string':
               $result = (string)$value;
               break;
           case 'number':
               $result = $this->math->format($value);
               break;
           case 'null':
           default:
               $result = null;
               break;
         }
         break;
   }
   return $result;
 }

/**
 * Appends two or more literal expressions, column values,
 *  or variables together into one string
 * If any of the concatenation values are null,
 *  the entire returned string is null
 * Also, if a numeric value is concatenated,
 *  it is implicitly converted to a character string
 *
 * @param  mixed  (...) the values
 * @return string       the concatenated value as string
 * @access public
 */
 function concat(){
   $result = null;
   $argn = func_num_args();
   if ($argn) {
     $args = func_get_args();
     $result = '';
     while (--$argn >= 0) {
       $val = array_shift($args);
       if ($val === null) {
         $result = null;
         break;
       }
       $result .= (string) $val;
     }
   }
   return $result;
 }

/**
 * SQL99 Syntax
 * CONCATENATE ( 'string1' || 'string2' )
 * Appends two or more literal expressions, column values,
 *  or variables together into one string.
 */
 function concatenate($string = null){
   $args = func_get_args();
   $result = call_user_func_array(array($this, 'concat'), $args);
   return $result;
 }

/**
 * If expression expr1 is NULL, that will returns the value of expr2
 * Abbreviation of "Null Value Logic"
 *
 * @access public
 */
 function nvl($expr1 = null, $expr2 = null){
   $result = null;
   if ($expr1 === null) {
     $result = $expr2;
   } else {
     $result = $expr1;
   }
   return $result;
 }

/**
 * The CONVERT function alters the representation of a character string
 *  within its character set and collation.
 * Give the string of the first argument which should be to convert.
 * Specify the output character-code
 *  which will be to convert as the second argument.
 * Optionally, give the input character-code  as the third argument.
 * If the third argument is omitted,
 *  it will be detect the encoding automatically.
 * Return the converted string specifically.
 */
 function convert($string = null, $to_charset = null, $from_charset = null){
   if ($string !== null) {
     $string = (string)$string;
     if ($string != null && $to_charset != null) {
       $pchar = & $this->posql->pcharset;
       if ($from_charset == null) {
         $string = $pchar->convert($string, $to_charset);
       } else {
         $string = $pchar->convert($string, $to_charset, $from_charset);
       }
       unset($pchar);
     }
   }
   return $string;
 }

/**
 * Alias of SUBSTR
 * @see substr
 */
 function substring(){
   $args = func_get_args();
   $result = call_user_func_array(array(&$this, 'substr'), $args);
   return $result;
 }

/**
 * SQL99 Syntax
 * TRANSLATE(char_value target_char_set USING translation_name)
 *
 * alters the character set of a string value
 *  from one base-character set to another.
 */
 function translate($string = null, $from = null, $to = null){
   if ($string !== null) {
     $string = (string)$string;
     if ($string != null && $from != null && $to != null) {
       if (is_string($from) && is_string($to)) {
         $string = $this->unicode->strtr($string, $from, $to);
       } else if (is_array($from) && is_array($to)) {
         $from = $this->posql->toOneArray($from);
         $to = $this->posql->toOneArray($to);
         if (count($from) === count($to)) {
           $pairs = array();
           do {
             $pairs[ array_shift($from) ] = array_shift($to);
           } while (!empty($from));
           $string = $this->unicode->strtr($string, $pairs);
         }
       }
     }
   }
   return $string;
 }

/**
 * Returns an integer value representing the number of bits in an expression
 */
 function bit_length($value = null){
   $result = null;
   if ($value !== null) {
     $result = strlen($value) << 3;
   }
   return $result;
 }

/**
 * Returns an integer value representing the number
 *  of characters in an expression
 */
 function char_length($value = null){
   $result = null;
   $args = func_get_args();
   $result = call_user_func_array(array(&$this, 'length'), $args);
   return $result;
 }

/**
 * Returns an integer value representing the number
 *  of octets in an expression.
 * This value is the same as BIT_LENGTH/8.
 */
 function octet_length($value = null){
   $result = null;
   if ($value !== null) {
     $result = strlen($value);
   }
   return $result;
 }

/**
 * The function NOW returns the current date and time.
 *
 * @access public
 */
 function now($time = null){
   static $format = 'Y-m-d H:i:s';
   $result = null;
   if ($time != null && is_numeric($time)) {
     $result = @date($format, $time);
   } else {
     $result = date($format);
   }
   return $result;
 }

/**
 * Allows the datepart to be extracted (as follows) from an expression.
 * ---------------------------------------------------------------------------
 * Type value     Meaning                         Expected format
 * ---------------------------------------------------------------------------
 * SECOND         Seconds                         SECONDS
 * MINUTE         Minutes                         MINUTES
 * HOUR           Hours                           HOURS
 * DAY            Days                            DAYS
 * MONTH          Months                          MONTHS
 * YEAR           Years                           YEARS
 * MINUTE_SECOND  Minutes and seconds             "MINUTES:SECONDS"
 * HOUR_MINUTE    Hours and minutes               "HOURS:MINUTES"
 * DAY_HOUR       Days and hours                  "DAYS HOURS"
 * YEAR_MONTH     Years and months                "YEARS-MONTHS"
 * HOUR_SECOND    Hours, minutes, seconds         "HOURS:MINUTES:SECONDS"
 * DAY_MINUTE     Days, hours, minutes            "DAYS HOURS:MINUTES"
 * DAY_SECOND     Days, hours, minutes, seconds   "DAYS HOURS:MINUTES:SECONDS"
 * ---------------------------------------------------------------------------
 */
 function extract($datetime = null, $value = null){
   $format = '%Y-%m-%d %H:%M:%S';
   switch (strtoupper($datetime)) {
     case 'SECOND':
         $format = '%S';
         break;
     case 'MINUTE':
         $format = '%M';
         break;
     case 'HOUR':
         $format = '%H';
         break;
     case 'DAY':
         $format = '%d';
         break;
     case 'MONTH':
         $format = '%m';
         break;
     case 'YEAR':
         $format = '%Y';
         break;
     case 'MINUTE_SECOND':
         $format = '%M:%S';
         break;
     case 'HOUR_MINUTE':
         $format = '%H:%M';
         break;
     case 'DAY_HOUR':
         $format = '%d %H';
         break;
     case 'YEAR_MONTH':
         $format = '%Y-%m';
         break;
     case 'HOUR_SECOND':
         $format = '%H:%M:%S';
         break;
     case 'DAY_MINUTE':
         $format = '%d %H:%M';
         break;
     case 'DAY_SECOND':
         $format = '%d %H:%M:%S';
         break;
     default:
         break;
   }
   if ($value !== null) {
     $value = $this->strftime($format, $value);
   }
   return $value;
 }

/**
 * SQL99 Syntax
 *
 * POSITION ( substring IN target_string )
 *
 * Returns an integer value representing the starting position
 *  of a string within the search string.
 */
 function position($search = null, $string = null, $index = null){
   $result = 0;
   $argn = func_num_args();
   if ($argn && $string != null && $search != null) {
     $search = (string)$search;
     $string = (string)$string;
     if ($this->posql->pcharset->isBinary($search)
      || $this->posql->pcharset->isBinary($string)) {
       if ($argn <= 2) {
         $pos = strpos($string, $search);
       } else {
         $pos = strpos($string, $search, $index);
       }
     } else {
       if ($argn <= 2) {
         $pos = $this->unicode->strpos($string, $search);
       } else {
         $pos = $this->unicode->strpos($string, $search, $index);
       }
     }
     if ($pos === false) {
       $result = 0;
     } else {
       $result = $pos + 1;
     }
   }
   return $result;
 }
/*
 }}}  general functions and SQL92-99 syntaxes
*/
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Statement
 *
 * The result storage and statement handle class for Posql result objects
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Statement extends Posql_Object {

/**
 * @var    string   maintains the string of the last SQL statement
 * @access public
 */
 var $queryString;

/**
 * @var    string   the default fetch mode as string
 * @access public
 */
 var $defaultFetchMode = 'both';

/**
 * @var    string   maintains fetch mode as string
 * @access public
 */
 var $fetchMode;

/**
 * @var    array    maintains options of fetch mode as array
 * @access public
 */
 var $fetchInfo = array(
   'class_name' => 'stdClass',
   'class_args' => array(),
   'column_key' => 0
 );

/**
 * @var    array    maintains result rows as array
 * @access private
 */
 var $rows = array();

/**
 * @var    number   maintains count of result rows
 * @access private
 */
 var $rowCount = 0;

/**
 * @var    number   maintains count of results columns
 * @access private
 */
 var $columnCount = 0;

/**
 * @var    number   maintains the number of rows ware affected
 * @access private
 */
 var $affectedRows = 0;

/**
 * @var    array    maintains column names as associate array
 * @access private
 */
 var $columnNames = array();

/**
 * @var    array    maintains parameters to bind placeholder
 * @access private
 */
 var $bindParams = array();

/**
 * @var    array    maintains columns to bind on rows
 * @access private
 */
 var $bindColumns = array();

/**
 * @var    Posql    maintains reference of the Posql instance
 * @access private
 */
 var $posql;

/**
 * Class constructor
 *
 * @param  Posql            give of the Posql self
 * @param  array            the result rows as array
 * @param  string           the last SQL statement
 * @return Posql_Statement
 * @access public
 */
 function __construct(&$posql, $rows = array(), $query = null){
   parent::__construct();
   $this->rows = array();
   $this->fetchMode = $this->defaultFetchMode;
   $this->_referObject($posql);
   if (is_string($query)) {
     $this->queryString = $query;
   }
   $this->_setResultRows($rows);
 }

/**
 * Destructor for PHP Version 5+
 * Release all referenced properties
 *
 * @access public
 */
 function __destruct(){
   if (isset($this)) {
     unset($this->posql);
     foreach (get_object_vars($this) as $prop => $val) {
       if ($prop != null) {
         unset($this->{$prop});
       }
     }
   }
 }

/**
 * Reference to the instance of Posql
 *
 * @param  Posql   give of the Posql self
 * @return void
 * @access private
 */
 function _referObject(&$posql){
   $this->posql = & $posql;
 }

/**
 * Set the result rows and the informations to internal properties
 *
 * @param  array    the result rows
 * @return void
 * @access private
 */
 function _setResultRows($rows){
   if (is_array($rows)) {
     $this->rows = $rows;
     $this->rowCount = count($this->rows);
     if (is_array(reset($this->rows))) {
       $this->columnCount = count(current($this->rows));
       $this->columnNames = array_flip(array_keys(current($this->rows)));
     }
   } else {
     if (is_numeric($rows)) {
       $this->affectedRows = (int)$rows;
       $this->rowCount = $this->affectedRows;
     }
   }
 }

/**
 * Executes a prepared statement
 *
 * @param   array    an array of values with as many elements
 *                   as there are bound parameters
 *                   in the SQL statement being executed.
 * @return  boolean  success or failure
 * @access  public
 */
 function execute($params = array()){
   $result = false;
   if (!$this->posql->hasError()) {
     if (!empty($params) && is_array($params)) {
       $increment = 0;
       reset($params);
       if (key($params) === 0) {
         $increment++;
       }
       foreach ($params as $param => $value) {
         if (is_int($param)) {
           $param += $increment;
         }
         $this->bindValue($param, $value);
       }
     }
     if (!empty($this->queryString) && is_string($this->queryString)) {
       $args = array();
       $query = $this->queryString;
       $is_manip = $this->posql->isManip($query);
       if (!empty($this->bindParams) && is_array($this->bindParams)) {
         $identifier = ' %a ';
         $index = 1;
         $tokens = $this->posql->splitSyntax($query);
         foreach ($tokens as $i => $token) {
           $j = $i + 1;
           $next = $this->posql->getValue($tokens, $j);
           switch ($token) {
             case '?':
                 if (array_key_exists($index, $this->bindParams)) {
                   $args[] = $this->bindParams[$index++];
                   $tokens[$i] = $identifier;
                 }
                 break;
             case ':':
                 if ($next != null
                  && array_key_exists($next, $this->bindParams)) {
                   $args[] = $this->bindParams[$next];
                   $tokens[$i] = '';
                   $tokens[$j] = $identifier;
                 }
                 break;
             case '%':
                 $tokens[$i] = '%%';
                 break;
             default:
                 break;
           }
         }
         $query = $this->posql->joinWords($tokens);
       }
       $rows = array();
       $this->posql->_fromStatement = true;
       if (empty($args)) {
         if ($is_manip) {
           $rows = $this->posql->exec($query);
         } else {
           $rows = $this->posql->query($query);
         }
       } else {
         array_unshift($args, $query);
         $rows = call_user_func_array(array(&$this->posql,
                                            'queryf'), $args);
       }
       $this->posql->_fromStatement = null;
       $this->_setResultRows($rows);
       if (!$this->posql->hasError()) {
         $result = true;
       }
     }
   }
   return $result;
 }

/**
 *  Binds a value to a parameter
 *
 * For a prepared statement using named placeholders,
 *  this will be a parameter name of the form :name.
 * For a prepared statement using question mark placeholders,
 *  this will be the 1-indexed  position of the parameter.
 *
 * @see    bindParam, bindColumn
 *
 * @param  mixed    parameter identifier (e.g. '?' or ':xxx')
 * @param  mixed    variable reference
 * @param  mixed    specifies the type of the field
 * @param  number   reflected the length of the data type
 * @return boolean  success or failure
 * @access public
 */
 function bindValue($param, $value, $type = null, $length = null){
   $result = false;
   if (is_scalar($param)) {
     if (is_string($param)) {
       $param = ltrim($param, ':');
     }
     $this->bindParams[$param] = $value;
     $result = true;
   }
   return $result;
 }

/**
 * Binds a parameter to the specified variable name
 *
 * @see    bindValue, bindColumn
 *
 * @param  mixed    parameter identifier (e.g. '?' or ':xxx')
 * @param  mixed    variable reference
 * @param  mixed    specifies the type of the field
 * @param  number   reflected the length of the data type
 * @return boolean  success or failure
 * @access public
 */
 function bindParam($param, &$var, $type = null, $length = null){
   $result = false;
   if (is_scalar($param)) {
     if (is_string($param)) {
       $param = ltrim($param, ':');
     }
     $this->bindParams[$param] = & $var;
     $result = true;
   }
   return $result;
 }

/**
 * Bind a column to a PHP variable
 *
 * About the first argument(column):
 *   Number of the column (1-indexed)
 *    or name of the column in the result set.
 *   If using the column name,
 *    be aware that the name should match the case of the column.
 *
 * @see    bindValue, bindParam
 *
 * @param  mixed    column number or name
 * @param  mixed    variable reference
 * @param  mixed    specifies the type of the field
 * @param  number   reflected the maximum value
 * @return boolean  success or failure
 * @access public
 */
 function bindColumn($column, &$var, $type = null, $maxlen = null){
   $result = false;
   if (is_scalar($column)) {
     if (is_string($column)) {
       $column = ltrim($column, ':');
     } else if (is_int($column)) {
       $column--;
       if ($column < 0) {
         $column++;
       }
     }
     $this->bindColumns[$column] = & $var;
     $result = true;
   }
   return $result;
 }

/**
 * Bind and assign to PHP variable to a value in the result row
 *
 * @param  array    optionally, the row to fetched
 * @return void
 * @access private
 */
 function _assignBindColumns($row = array()){
   if (!empty($this->bindColumns) && is_array($this->bindColumns)) {
     if (empty($row)) {
       $row = end($this->rows);
       reset($this->rows);
     }
     if (is_array($row)) {
       $index = 0;
       foreach ($row as $column => $value) {
         if (array_key_exists($column, $this->bindColumns)) {
           $this->bindColumns[$column] = $value;
         } else if (array_key_exists($index, $this->bindColumns)) {
           $this->bindColumns[$index] = $value;
         }
         $index++;
       }
     }
   }
 }

/**
 * Return the names of columns by the query result or from the cache.
 * The result names are associate array(column => index).
 *
 * @param  boolean  whether flip the key and the index
 * @return array    the names of columns as associate array
 * @access public
 */
 function getColumnNames($flip = false){
   $result = array();
   if (empty($this->columnNames)) {
     if (!empty($this->rows)
      && is_array($this->rows) && is_array(current($this->rows))) {
       $this->columnNames = array_flip(array_keys(current($this->rows)));
     } else {
       $this->columnNames = array();
     }
   }
   if (!empty($this->columnNames) && is_array($this->columnNames)) {
     if ($flip) {
       $result = array_flip($this->columnNames);
     } else {
       $result = $this->columnNames;
     }
   }
   return $result;
 }

/**
 * Sets the fetch mode
 * This mode should be used by default on queries on this connection.
 *
 * @param  mixed   the fetch mode of numerical value or string
 * @param  string  the class name of the object to be returned
 * @param  array   the arguments which will be passed class constructor
 * @return boolean success or failure
 * @access public
 */
 function setFetchMode($fetch_mode = null, $args_1 = null, $args_2 = null){
   $result = false;
   $mode = $this->mapFetchMode($fetch_mode, true);
   switch ($mode) {
     case 'class':
         $class_name = $args_1;
         $class_args = $args_2;
         if ($class_name === null) {
           $class_name = 'stdClass';
         }
         if ($class_args === null) {
           $class_args = array();
         }
         if (!is_string($class_name)
          || !$this->posql->existsClass($class_name)) {
           $error = (string)$class_name;
           $this->posql->pushError('Not exists class(%s)', $error);
         } else if (!is_array($class_args)) {
           $this->posql->pushError('Only array type is enabled as arguments');
         } else {
           $this->fetchInfo['class_name'] = $class_name;
           $this->fetchInfo['class_args'] = $class_args;
           $result = true;
         }
         break;
     case 'column':
         $column_key = $args_1;
         if ($column_key === null) {
           $column_key = 0;
         }
         if (!is_scalar($column_key)) {
           $this->posql->pushError('Should be given'
                                .  ' the scalar type as arguments');
         } else {
           $this->fetchInfo['column_key'] = $column_key;
           $result = true;
         }
         break;
     case 'lazy':
     case 'bound':
     case 'into':
     case 'single':
     case 'string':
     case 'integer':
     case 'int':
     case 'htmlentity':
     //case 'htmltable':
     case 'join':
     case 'export':
     case 'serialize':
     case 'httpquery':
         $this->posql->pushError('Not supported the fetch mode(%s)', $mode);
         break;
     default:
         $result = true;
         break;
   }
   if ($result) {
     $this->fetchMode = $mode;
   }
   return $result;
 }

/**
 * Mapping the fetch mode by specified type
 *
 * @param  mixed   the fetch mode of numerical value or string
 * @param  boolean whether get the fetch mode as string
 * @return mixed   the value to mapped of fetch mode
 * @access private
 */
 function mapFetchMode($fetch_mode, $as_string = false){
   static $marks, $flip_maps, $maps = array(
     'lazy'   => 1,
     'assoc'  => 2,
     'number' => 3,
     'num'    => 3,
     'both'   => 4,
     'object' => 5,
     'obj'    => 5,
     'bound'  => 6,
     'column' => 7,
     'class'  => 8,
     'into'   => 9,

     //'tablenames' => 14,

     'single'   => 100,
     'string'   => 101,
     'integer'  => 102,
     'int'      => 102,
     'htmlentity' => 110,
     'htmltable'  => 111,
     'join'       => 112,
     'export'     => 113,
     'serialize'  => 114,
     'httpquery'  => 115
   );
   if (!$marks) {
     $marks = array('_', '-', ':', '.');
     $flip_maps = array_flip($maps);
   }
   $result = null;
   $mode = $fetch_mode;
   if (!is_scalar($mode)) {
     $mode = null;
   } else if (is_numeric($mode)) {
     $mode = (int)$mode;
   } else if (is_string($mode)) {
     $mode = strtolower($mode);
     foreach ($marks as $mark) {
       if (strpos($mode, $mark) !== false) {
         $split = explode($mark, $mode);
         $mode = implode('', $split);
       }
     }
   }
   if ($mode !== null
    && (is_string($mode) || is_int($mode))) {
     if (array_key_exists($mode, $maps)) {
       $result = $maps[$mode];
     } else if (array_key_exists($mode, $flip_maps)) {
       $result = $mode;
     } else {
       if ($mode != null && is_string($mode)) {
         foreach ($maps as $map => $index) {
           if (strncasecmp($map, $mode, 4) === 0) {
             $result = $index;
             break;
           }
         }
       } else if (is_int($mode) && !array_key_exists($mode, $flip_maps)) {
         for ($i = 0; $i <= 10; $i++) {
           if (array_key_exists($i, $flip_maps)) {
             $mode_name = $flip_maps[$i];
             $pdo_const = sprintf('PDO::FETCH_%s', $mode_name);
             if (isset($maps[$flip_maps[$i]])
              && $this->posql->compareDefinedValue($pdo_const, $mode, true)) {
               $result = $maps[$flip_maps[$i]];
               break;
             }
           }
         }
       }
     }
   }
   if (!is_int($result)) {
     $result = $maps[$this->defaultFetchMode];
   }
   if ($as_string) {
     if (array_key_exists($result, $flip_maps)) {
       $result = $flip_maps[$result];
     } else {
       $result = $this->defaultFetchMode;
     }
   }
   return $result;
 }

/**
 * Fetches next row by specified fetch mode
 *
 * This method is able to use the constant PDO::*
 *
 * @param  mixed   the fetch mode of numerical value or string
 * @param  mixed   the value of the first argument
 * @param  array   the value of the second argument
 * @return mixed   the value that was fetched
 * @access public
 */
 function fetch($fetch_mode = null, $args_1 = null, $args_2 = null){
   $result = false;
   $row = array_shift($this->rows);
   if (!empty($row) && is_array($row)) {
     $argn = func_num_args();
     $mode = null;
     if ($argn === 0
      || $this->setFetchMode($fetch_mode, $args_1, $args_2)) {
       $mode = $this->fetchMode;
     }
     if (!$this->posql->hasError()) {
       $this->_assignBindColumns($row);
       switch ($mode) {
         case 'assoc':
             $result = $row;
             break;
         case 'number':
         case 'num':
             $result = array_values($row);
             break;
         case 'both':
             $result = $row + array_values($row);
             break;
         case 'object':
         case 'obj':
             $result = (object)$row;
             break;
         case 'column':
             $column_key = $this->fetchInfo['column_key'];
             if (array_key_exists($column_key, $row)) {
               $result = $row[$column_key];
             } else {
               $keys = array_keys($row);
               if (array_key_exists($column_key, $keys)) {
                 $result = $row[$keys[$column_key]];
               } else {
                 $error = (string)$column_key;
                 $this->posql->pushError('Invalid column index(%s)',
                                         $error);
                 $result = false;
               }
             }
             break;
         case 'class':
             $class = $this->fetchInfo['class_name'];
             $args  = $this->fetchInfo['class_args'];
             if (!is_array($args)) {
               $args = array();
             }
             array_unshift($args, $class);
             $result = call_user_func_array(array(&$this->posql,
                                                  'createInstance'), $args);
             if (!is_object($result)) {
               $class = (string)$class;
               $this->posql->pushError('Unable to create'
                                    .  ' instance of class(%s)', $class);
               $result = false;
             } else {
               foreach ($row as $key => $val) {
                 $result->{$key} = $val;
               }
             }
             break;
         case 'lazy':
         case 'bound':
         case 'into':
         case 'single':
         case 'string':
         case 'integer':
         case 'int':
         case 'htmlentity':
         case 'htmltable':
         case 'join':
         case 'export':
         case 'serialize':
         case 'httpquery':
         default:
             $this->posql->pushError('Not supported the fetch mode(%s)',
                                     $mode);
             break;
       }
     }
   }
   return $result;
 }

/**
 * Alias of fetch()
 *
 * @see    fetch
 * @access public
 */
 function fetchRow(){
   $args = func_get_args();
   $result = call_user_func_array(array(&$this, 'fetch'), $args);
   return $result;
 }

/**
 * Return and fetch the all result rows by specified fetch mode as array
 *
 * @param  mixed   the fetch mode of numerical value or string
 * @param  mixed   the value of the first argument
 * @param  array   the value of the second argument
 * @return array   the all rows which to fetched
 * @access public
 */
 function fetchAll($fetch_mode = null, $args_1 = null, $args_2 = null) {
   $result = array();
   $argn = func_num_args();
   $args = func_get_args();
   $mode = null;
   if ($argn === 0
    || $this->setFetchMode($fetch_mode, $args_1, $args_2)) {
     $mode = $this->fetchMode;
   }
   if (empty($this->rows) || !is_array($this->rows)) {
     switch ($mode) {
       case 'column':
           $result = null;
           break;
       case 'htmltable':
           array_shift($args);
           array_unshift($args, $this->rows);
           $result = call_user_func_array(array(&$this->posql,
                                                'toHTMLTable'), $args);
           break;
       default:
           $result = array();
           break;
     }
   } else {
     if (!$this->posql->hasError()) {
       $this->_assignBindColumns();
       switch ($mode) {
         case 'assoc':
             $result = array_splice($this->rows, 0);
             break;
         case 'number':
         case 'num':
             $result = array_splice($this->rows, 0);
             $result = array_map('array_values', $result);
             break;
         case 'both':
             $this->rows = array_values($this->rows);
             $row_count = count($this->rows);
             $i = 0;
             while (--$row_count >= 0) {
               $row = $this->rows[$i];
               $this->rows[$i] = null;
               $result[] = $row + array_values($row);
               $i++;
             }
             $this->rows = array();
             break;
         case 'object':
         case 'obj':
             $this->rows = array_values($this->rows);
             $row_count = count($this->rows);
             $i = 0;
             while (--$row_count >= 0) {
               $row = $this->rows[$i];
               $this->rows[$i] = null;
               $result[] = (object)$row;
               $i++;
             }
             $this->rows = array();
             break;
         case 'column':
             $column_key = $this->fetchInfo['column_key'];
             $this->rows = array_values($this->rows);
             $row_count = count($this->rows);
             $i = 0;
             while (--$row_count >= 0) {
               $row = $this->rows[$i];
               $this->rows[$i] = null;
               if (array_key_exists($column_key, $row)) {
                 $result[] = $row[$column_key];
               } else {
                 $keys = array_keys($row);
                 if (array_key_exists($column_key, $keys)) {
                   $result[] = $row[$keys[$column_key]];
                 } else {
                   $error = (string)$column_key;
                   $this->posql->pushError('Invalid column index(%s)',
                                           $error);
                   $result = array();
                   break;
                 }
               }
               $i++;
             }
             $this->rows = array();
             break;
         case 'class':
             while ($row = $this->fetch($fetch_mode, $args_1, $args_2)) {
               if ($this->posql->hasError()) {
                 break;
               }
               $result[] = $row;
             }
             break;
         case 'htmltable':
             array_shift($args);
             array_unshift($args, $this->rows);
             $result = call_user_func_array(array(&$this->posql,
                                                  'toHTMLTable'), $args);
             break;
         case 'lazy':
         case 'bound':
         case 'into':
         case 'single':
         case 'string':
         case 'integer':
         case 'int':
         case 'htmlentity':
         case 'join':
         case 'export':
         case 'serialize':
         case 'httpquery':
         default:
             $this->posql->pushError('Not supported the fetch mode(%s)',
                                     $mode);
             break;
       }
     }
   }
   $this->rows = array();
   if ($this->posql->hasError()) {
     switch ($mode) {
       case 'column':
           $result = null;
           break;
       case 'htmltable':
           $result = (string)null;
           break;
       default:
           $result = array();
           break;
     }
   }
   return $result;
 }

/**
 * Fetches all rows and returns it as a HTML table element
 *
 * @param  mixed   optionally, the caption, or the table attributes
 * @param  mixed   optionally, the table attributes, or the caption
 * @return string  created HTML TABLE element
 * @access public
 */
 function fetchAllHTMLTable($caption = null, $attr = array('border' => 1)){
   $args = func_get_args();
   array_unshift($args, 'htmltable');
   $result = call_user_func_array(array(&$this, 'fetchAll'), $args);
   return $result;
 }

/**
 * Returns a single column from the next row of a result set
 *  or FALSE if there are no more rows.
 *
 * @param  mixed  target column index or column name
 * @return mixed  value of corresponding column
 * @access public
 */
 function fetchColumn($column_key = 0){
   $result = $this->fetch('column', $column_key);
   return $result;
 }

/**
 * Fetches the next row and returns it as an object
 *
 * @param  string  name of the created class, defaults to "stdClass"
 * @param  array   elements of this array are passed to the constructor
 * @return object  instance of the required class, or FALSE on error
 * @access public
 */
 function fetchObject($class_name = null, $class_args = null){
   $result = false;
   if (func_num_args()) {
     $result = $this->fetch('class', $class_name, $class_args);
   } else {
     $result = $this->fetch('object');
   }
   return $result;
 }

/**
 * Return the field name which correlated the table with the column by the dot
 *
 * @param  number  the number of target column (e.g. 0, 1, ..)
 * @return array   the field name which correlated by dot(.), or FALSE
 * @access public
 */
 function getTableNames($key = null){
   $result = false;
   $column_names = $this->getColumnNames();
   if (!empty($column_names) && is_array($column_names)) {
     $tables = null;
     $last_method = $this->posql->getLastMethod();
     if (strtolower($last_method) === 'select') {
       $tables = $this->posql->getTableNames();
       if (is_string($tables)) {
         $tables = array($tables => null);
       }
       if (!empty($tables) && is_array($tables)) {
         $meta = $this->posql->getMetaData();
         if (!is_array($meta)) {
           $meta = array(array());
         }
         $as_columns = $this->posql->getColumnAliasNames();
         $as_columns = $this->posql->flipArray($as_columns);
         $result = array();
         foreach ($column_names as $name => $index) {
           $table_name = null;
           $org_name = $name;
           $use_func = false;
           if (isset($as_columns[$name])) {
             $org_name = $as_columns[$name];
             if (strpos($org_name, '.') > 0) {
               list($left_name) = explode('.', $org_name);
               if ($this->posql->isEnableName($left_name)) {
                 $table_name = $left_name;
               } else {
                 $use_func = true;
               }
             }
           }
           if (!$use_func && $table_name == null) {
             foreach ($tables as $org_table => $as_name) {
               $metadata = $this->posql->getMetaData($org_table);
               if (is_array($metadata)
                && array_key_exists($org_name, $metadata)) {
                 $table_name = $as_name;
                 break;
               }
             }
             if ($table_name == null) {
               foreach ($meta as $meta_table => $meta_info) {
                 if (is_array($meta_info)
                  && array_key_exists($org_name, $meta_info)) {
                   $table_name = $meta_table;
                   break;
                 }
               }
             }
           }
           if ($table_name != null && $name != null) {
             $result[] = $table_name . '.' . $name;
           } else {
             $result[] = null;
           }
         }
         if (is_int($key) && !empty($result[$key])) {
           $result = $result[$key];
         }
       }
     }
   }
   return $result;
 }

/**
 * Returns the actual row number that was last fetched
 *
 * @param  void
 * @return number  the actual row count
 * @access public
 */
 function rowCount(){
   $result = $this->rowCount;
   return $result;
 }

/**
 * Returns the number of columns in the result set
 *
 * @param  void
 * @return number  the number of columns
 * @access public
 */
 function columnCount(){
   $result = $this->columnCount;
   return $result;
 }

/**
 * Returns the number of rows in a result object
 *
 * @param  void
 * @return number  the number of rows
 * @access public
 */
 function numRows(){
   $result = $this->rowCount();
   return $result;
 }

/**
 * Count the number of columns
 *
 * @param  void
 * @return number  the number of columns
 * @access public
 */
 function numCols(){
   $result = $this->columnCount();
   return $result;
 }

/**
 * Check whether the rows of the result are empty
 *
 * @param  void
 * @return boolean  whether the rows of the result are empty, or not
 * @access public
 */
 function hasRows(){
   $result = !empty($this->rows);
   return $result;
 }

/**
 * Returns the number of rows affected
 *
 * @param  void
 * @return number   the number of rows affected
 * @access public
 */
 function affectedRows(){
   $result = $this->affectedRows;
   return $result;
 }

/**
 * Free the internal resources associated with result
 *
 * @param  void
 * @return void
 * @access public
 */
 function free(){
   $this->__destruct();
 }

/**
 * Closes the cursor, enabling the statement to be executed again
 *
 * @param  void
 * @return void
 * @access public
 */
 function closeCursor(){
   $rows = array();
   $this->_setResultRows($rows);
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Pager
 *
 * A simple Pager class for client view
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Pager extends Posql_Object {

/**
 * @var    number    number of all items
 * @access public
 */
 var $totalCount = 0;

/**
 * @var    number    current page number
 * @access public
 */
 var $currentPage = 1;

/**
 * @var    number    number of items per page
 * @access public
 */
 var $perPage = 10;

/**
 * @var    number    number of page links for each window
 * @access public
 */
 var $range = 10;

/**
 * @var    number    number of total pages
 * @access public
 */
 var $totalPages = null;

/**
 * @var    array     array with number of pages
 * @access public
 */
 var $pages = array();

/**
 * @var    number    number of start page
 * @access public
 */
 var $startPage = null;

/**
 * @var    number    number of end page
 * @access public
 */
 var $endPage = null;

/**
 * @var    number    number of previous page
 * @access public
 */
 var $prev = null;

/**
 * @var    number    number of next page
 * @access public
 */
 var $next = null;

/**
 * @var    number    number offset of SELECT statement
 * @access public
 */
 var $offset = null;

/**
 * @var    number    number limit of SELECT statement
 * @access public
 */
 var $limit = null;

/**
 * Class constructor
 *
 * @param  void
 * @return object
 * @access public
 */
 function __construct(){
   parent::__construct();
 }

/**
 * Set each pages information for the Pager object
 *
 * @param  number  number of total items
 * @param  number  current page number
 * @param  number  number of items per page
 * @param  number  number of page links for each window
 * @return void
 * @access public
 */
 function setPager($total_count = null, $curpage = null,
                   $perpage     = null, $range = null){
   if (is_numeric($total_count)) {
     $this->totalCount = $total_count;
   }
   if (is_numeric($curpage)) {
     $this->currentPage = $curpage;
   }
   if (is_numeric($perpage)) {
     $this->perPage = $perpage;
   }
   if (is_numeric($range)) {
     $this->range = $range;
   }
   $this->totalCount  = $this->totalCount  - 0;
   $this->currentPage = $this->currentPage - 0;
   $this->perPage     = $this->perPage     - 0;
   $this->range       = $this->range       - 0;
   $this->totalPages = ceil($this->totalCount / $this->perPage);
   if ($this->totalPages < $this->range) {
     $this->range = $this->totalPages;
   }
   $this->startPage = 1;
   if ($this->currentPage >= ceil($this->range / 2)) {
     $this->startPage = $this->currentPage - floor($this->range / 2);
   }
   if ($this->startPage < 1) {
     $this->startPage = 1;
   }
   $this->endPage = $this->startPage + $this->range - 1;
   if ($this->currentPage > $this->totalPages - ceil($this->range / 2)) {
     $this->endPage = $this->totalPages;
     $this->startPage = $this->endPage - $this->range + 1;
   }
   $this->prev = null;
   if ($this->currentPage > $this->startPage) {
     $this->prev = $this->currentPage - 1;
   }
   $this->next = null;
   if ($this->currentPage < $this->endPage) {
     $this->next = $this->currentPage + 1;
   }
   $range_end = 1;
   if ($this->endPage) {
     $range_end = $this->endPage;
   }
   $this->pages = range($this->startPage, $range_end);
   $this->offset = ceil($this->currentPage - 1) * $this->perPage;
   $this->limit = $this->perPage;
   if ($this->totalCount < $this->perPage) {
     $this->limit = $this->totalCount;
   }
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Utils
 *
 * This class is a group of static methods for Posql
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Utils extends Posql_Config {

/**
 * Escape a string to validate expression in a query.
 * Optionally, and escape characters to validate string as a LIKE pattern.
 *
 * @param  string   the subject string to escape
 * @param  boolean  whether or not to escape the wildcards
 * @param  mixed    whether or not enclose string by quotes
 * @return string   escaped string
 * @access public
 */
 function escape($string, $escape_wildcards = false, $enclose = false){
   if (!is_scalar($string)) {
     $string = '';
   } else {
     $string = $this->escapeString($string, $enclose);
     if ($escape_wildcards) {
       $string = $this->escapePattern($string);
     }
   }
   return $string;
 }

/**
 * Quotes the string so it can be safely used
 *  within string delimiters in a query.
 *
 * Note:
 *   Version 2.06 older:
 *     This function does not escapes more than once.
 *      (the backslash does not become two or more)
 *   Version 2.07 later:
 *     The backslashes will be increased when escaping more than once.
 *
 * @param  string  the string to be quoted
 * @param  mixed   the optionals to enclose marker of string (e.g. "'")
 *                 Or, TRUE will be use single quotation mark(')
 * @return string  the quoted string
 * @access public
 * @static
 */
 function escapeString($string, $enclose = false){
   static $bs = '\\', $sq = '\'', $dq = '"';
   $quote = $sq;
   if ($enclose === $dq) {
     $quote = $dq;
   }
   if (!is_scalar($string)) {
     $string = '';
   } else if ($string == null) {
     $string = (string)$string;
   } else {
     if (strpos($string, $bs) !== false) {
       $split = explode($bs, $string);
       $string = implode($bs . $bs, $split);
     }
     $split = explode($quote, $string);
     $string = array();
     $end = count($split);
     while (--$end >= 0) {
       $shift = array_shift($split);
       $glue = '';
       if (substr($shift, -1) === $bs) {
         $rtrim = rtrim($shift, $bs);
         $diff = strlen($shift) - strlen($rtrim);
         if ($end) {
           if ($diff % 2 === 0) {
             $glue = $bs;
           }
         } else {
           if ($diff % 2 === 1) {
             $glue = $bs;
           }
         }
       } else {
         if ($end) {
           $glue = $bs;
         } else {
           $glue = '';
         }
       }
       $shift .= $glue;
       $string[] = $shift;
     }
     $string = implode($quote, $string);
   }
   if ($enclose) {
     $string = $quote . $string . $quote;
   }
   return $string;
 }

/**
 * Escape characters that work as wildcard string in a LIKE pattern.
 * The wildcard characters "%" and "_" should be escaped.
 *
 * @param  string  the string pattern to escape
 * @return string  the escaped string pattern
 * @access public
 * @static
 */
 function escapePattern($pattern){
   static $translates = array(
     '%' => '\%',
     '_' => '\_'
   );
   if (!is_scalar($pattern)) {
     $pattern = '';
   } else {
     $bs = '\\';
     $pattern = str_replace($bs, $bs . $bs, $pattern);
     $pattern = strtr($pattern, $translates);
   }
   return $pattern;
 }

/**
 * The string in SQL query where
 *  it is escaped is returned to former character
 * When giving the relation character to unite,
 *  and it become it side by side by quotation mark (''),
 *  they are united.
 *
 * @see    concatStringTokens()
 * @param  string  string of target
 * @param  string  relation character row to unite
 * @return string  unescaped string
 * @access public
 * @static
 */
 function unescapeSQLString($string, $concat = null){
   static $tr = array(
     '\0'   => "\x00",
     "\\'"  => "'",
     '\\"'  => '"',
     '\b'   => "\x08",
     '\n'   => "\x0A",
     '\r'   => "\x0D",
     '\t'   => "\t",
     '\Z'   => "\x1A",// (Ctrl-Z)
     '\\\\' => '\\',
     '\%'   => '%',
     '\_'   => '_'
   );
   if ($concat !== null && ord($string) === ord($concat)) {
     $string = $this->concatStringTokens($string, $concat);
   }
   $string = strtr($string, $tr);
   return $string;
 }

/**
 * A handy function that will escape the contents of a variable,
 *   recursing into arrays for HTML/XML text
 *
 * @param  mixed   the variable of target
 * @param  string  optional, the character set for encode
 * @return mixed   the encoded valiable
 * @access public
 * @static
 */
 function escapeHTML($html, $charset = null){
   $result = null;
   if (is_array($html)) {
     $args = func_get_args();
     if (isset($this)) {
       $result = array_map(array(&$this, 'escapeHTML'), $args);
     } else {
       $result = array_map(array('Posql_Utils', 'escapeHTML'), $args);
     }
   } else if (is_string($html)) {
     if ($charset != null && is_string($charset)) {
       $result = htmlspecialchars($html, ENT_QUOTES, $charset);
     } else if (!empty($this->charset)) {
       $result = htmlspecialchars($html, ENT_QUOTES, $this->charset);
     } else {
       $result = htmlspecialchars($html, ENT_QUOTES);
     }
   } else {
     $result = $html;
   }
   return $result;
 }

/**
 * Truncates the string by limited length
 * If the mbstring extension loaded, it will use on cutting to string
 *  or, it will be truncated by the UTF-8 option of preg_*
 * When the padding character is a dot(.),
 *  it is likely to become three dots(...)
 *
 * @param  mixed   the value of target
 * @param  number  the miximun length
 * @param  string  the padding character
 * @param  string  options as quotes enclosed
 * @param  boolean whether the string already escaped or not
 * @return mixed   truncated value
 * @access public
 */
 function truncatePad($value, $length, $marker = '.',
                      $quotes = '', $escaped = false){
   static $mb = null;
   if ($mb === null) {
     $mb = extension_loaded('mbstring')
        && function_exists('mb_strcut')
        && function_exists('mb_detect_encoding');
   }
   if ($quotes != null && ord($value) === ord($quotes)
    && substr($value, -1) === $quotes) {
     $value = substr($value, 1, -1);
   }
   $bytes = strlen($value);
   if ($marker === '.') {
     $marker = '...';
   }
   $length = (int)($length - strlen($marker));
   if ($length <= 0) {
     $value = '';
   } else {
     if ($bytes && $length > 0 && $bytes > $length) {
       if (is_numeric($value)) {
         $value = substr($value, 0, $length);
       } else if ($mb) {
         if (empty($this->charset)) {
           if (isset($this->pcharset)) {
             $charset = $this->pcharset->detectEncoding($value);
           } else {
             $charset = mb_detect_encoding($value, 'auto', true);
           }
         } else {
           $charset = $this->charset;
         }
         $value = mb_strcut($value, 0, $length, $charset);
       }
       else if ($length < 0x10000 && $length > 1
             && preg_match('<^.{1,' . $length . '}>s', $value, $match))
       {
         $value = $match[0];
         unset($match);
       } else {
         $value = substr($value, 0, $length);
       }
       if ($marker != null) {
         $value .= $marker;
       }
       if ($escaped) {
         if (ord($value) !== ord($quotes)) {
           $value = $quotes . $value . $quotes;
         }
       } else {
         if (isset($this)) {
           if ($quotes == null) {
             $value = $this->escapeString($value);
           } else {
             $value = $this->escapeString($value, $quotes);
           }
         } else {
           while (substr($value, -1) === '\\') {
             $value = substr($value, 0, -1);
           }
           if ($quotes != null) {
             $value = $quotes . $value . $quotes;
           }
         }
       }
     }
   }
   return $value;
 }

/**
 * A handy function the strip_tags() of inherited call
 *
 * @param  string  the HTML string of target
 * @return boolean the string that stripped tags
 * @access public
 * @static
 */
 function stripTags($html){
   if (strpos($html, '<') !== false
    && strpos($html, '>') !== false) {
     $html = preg_replace('{</?\w+[^>]*>}isU', '', $html);
   }
   return $html;
 }

/**
 * Encodes the given data with base16 as soon as hexadecimal numbers
 *
 * @example
 * <code>
 *  foreach(array("\n", "\r\n", "foo", "&&") as $string) {
 *    echo "<ul>\n";
 *    $hex_normal = Posql_Utils::base16Encode($string);
 *    printf("<li>normal: %s</li>\n", htmlspecialchars($hex_normal));
 *    $hex_regexp = Posql_Utils::base16Encode($string, '\x');
 *    printf("<li>regexp: %s</li>\n", htmlspecialchars($hex_regexp));
 *    $hex_entity = Posql_Utils::base16Encode($string, '&#x', ';');
 *    printf("<li>entity: %s</li>\n", htmlspecialchars($hex_entity));
 *    echo "</ul>\n";
 *  }
 * </code>
 *
 * @param  string  the data to encode
 * @param  string  optionally, the prefix string
 * @param  string  optionally, the suffix string
 * @return string  the hexadecimal numbers as string
 * @access public
 * @static
 */
 function base16Encode($string, $prefix = '', $suffix = ''){
   $result = '';
   if (is_string($string) && $string != null) {
     if ($prefix == null && $suffix == null) {
       $result = strtoupper(bin2hex($string));
       $string = null;
     } else {
       $string = unpack('C*', $string);
       $length = count($string);
       while (--$length >= 0) {
         $byte = array_shift($string);
         $result .= sprintf('%s%02X%s', $prefix, $byte, $suffix);
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the given token is a string
 * (it was enclosed with the quotation mark)
 *
 * @param  string  string of token
 * @return boolean whether the token is string or not
 * @access public
 * @static
 */
 function isStringToken($token){
   static $sq = "'", $dq = '"';
   $result = false;
   if ($token != null && is_string($token)) {
     $pre = substr($token, 0, 1);
     if ($pre === $sq || $pre === $dq) {
       $result = substr($token, -1) === $pre;
     }
   }
   return $result;
 }

/**
 * Convert the value of the given argument that will be the token as string
 * The token will be enclosed with the single quotation mark(')
 *
 * @param  string  the token of target
 * @return string  the converted string token
 * @access public
 */
 function toStringToken($token){
   $result = null;
   if (!$this->isStringToken($token)) {
     $result = $this->escapeString($token, true);
   } else {
     if (substr($token, 0, 1) === '"') {
       $token = substr($token, 1, -1);
       $result = $this->escapeString($token, true);
     } else {
       $result = $token;
     }
   }
   return $result;
 }

/**
 * Concatenate the operands pairs.
 *  (e.g. [ 'foo''bar' ] => [ 'foo\'bar' ])
 *
 * @param  string  the left string
 * @param  string  the right string
 * @return string  concatenated string
 * @access public
 */
 function concatStringTokens($left_string, $right_string){
   $result = null;
   if ($left_string != null && $right_string != null
    && $this->isStringToken($left_string)
    && $this->isStringToken($right_string)) {
     $left_string  = $this->toStringToken($left_string);
     $right_string = $this->toStringToken($right_string);
     $glue = '\\\'';
     $result = substr($left_string, 0, -1) . $glue . substr($right_string, 1);
   }
   return $result;
 }

/**
 * It further as for the trim() function
 *
 * @param  string  string of target
 * @param  string  optionally, the stripped characters
 * @return string  the trimmed string
 * @access public
 * @static
 */
 function trimExtra($string, $extras = '$'){
   $string = trim(trim($string), $extras);
   return $string;
 }

/**
 * Checks whether the character string is a word
 *  equal to RegExp: [a-zA-Z_\x7F-\xFF]
 *
 * @param  string  a character string of target
 * @return boolean whether string was word or not
 * @access public
 * @static
 */
 function isWord($char){
   $ord = ord($char);
   return $ord === 0x5F || $ord > 0x7E
      || ($ord  >  0x40 && $ord < 0x5B)
      || ($ord  >  0x60 && $ord < 0x7B);
 }

/**
 * Checks whether the character string is alphanumeric
 *  equal to RegExp: [0-9a-zA-Z_\x7F-\xFF]
 *
 * @param  string  a character string of target
 * @return string  whether the string was alphanumeric or not
 * @access public
 */
 function isAlphaNum($char){
   $ord = ord($char);
   return $ord === 0x5F || $ord > 0x7E
      || ($ord  >  0x2F && $ord < 0x3A)
      || ($ord  >  0x40 && $ord < 0x5B)
      || ($ord  >  0x60 && $ord < 0x7B);
 }

/**
 * Check whether it is a format to which the name is effective
 * This format is the same as the rule of the variable identifier of PHP
 *
 * @param  string  string of target
 * @return boolean whether the name is effective or not
 * @access public
 */
 function isEnableName($name){
   static $pattern = '{^[a-zA-Z_\x7F-\xFF][a-zA-Z0-9_\x7F-\xFF]*$}';
   $result = false;
   if (is_string($name)) {
     switch (strlen($name)) {
       case 0:
           break;
       case 1:
           $result = $this->isWord($name);
           break;
       case 2:
           if ($this->isWord(substr($name, 0, 1))
            && $this->isAlphaNum(substr($name, -1))) {
             $result = true;
           }
           break;
       default:
           if (preg_match($pattern, $name)) {
             $result = true;
           }
           break;
     }
   }
   return $result;
 }

/**
 * Check whether the token is evaluatable or static value
 *
 * @param  mixed    the target token
 * @return boolean  whether the argument was evaluatable token
 * @access public
 */
 function isExprToken($token) {
   $result = false;
   if (is_array($token)) {
     foreach ($token as $item) {
       $lc_item = strtolower($item);
       if ($lc_item === 'false'  || $lc_item === 'true'
        || !$this->isWord($item) || !$this->isEnableName($item)) {
         $result = true;
         break;
       }
     }
   } else if (is_scalar($token)) {
     $lc_token = strtolower($token);
     if ($lc_token === 'false'  || $lc_token === 'true'
      || !$this->isWord($token) || !$this->isEnableName($token)) {
       $result = true;
     }
   }
   return $result;
 }

/**
 * This logic refers to the column affinity algorithm of SQLite.
 *
 * @link http://www.sqlite.org/datatype3.html#affinity
 *
 * @param  string  type of subject as string
 * @return string  type of string which passed affinity.
 *                 return as following types.
 *                 ------------------------------
 *                 - string : type of string
 *                 - number : type of number
 *                 - null   : null
 *                 ------------------------------
 * @access public
 */
 function getColumnAffinity($column){
   static $maps = array(
     'string' => array(
       'char', 'text', 'lob', 'date', 'time', 'year'
     ),
     'number' => array(
       'int',  'dec',  'num', 'real', 'doub',
       'floa', 'seri', 'bit', 'bool'
     )
   );
   $result = 'null';
   if (is_string($column) && $column != null) {
     $column = strtolower($column);
     foreach ($maps as $type => $types) {
       foreach ($types as $part) {
         if (strpos($column, $part) !== false) {
           $result = $type;
           break 2;
         }
       }
     }
   }
   return $result;
 }

/**
 * Returns the microtime in seconds as a float.
 *
 * @param  void
 * @return float  microtime
 */
 function getMicrotime(){
   $result = array_sum(explode(' ', microtime()));
   return $result;
 }

/**
 * Gets the value of the key from array or object
 * If it not exists the key, it return $default = null
 *
 * @param  mixed   target variable which is type of array or object
 * @param  mixed   the key which should be type of scalar
 * @param  mixed   default value to return if result is null
 * @param  boolean whether the result evaluated strictly or not
 * @return mixed   the value of the key on variable, or default
 * @access public
 * @static
 */
 function getValue($var, $key, $default = null, $strict = false){
   $result = null;
   if (is_scalar($key)) {
     if (is_array($var) && isset($var[$key])) {
       $result = $var[$key];
     } else if (is_object($var) && isset($var->{$key})) {
       $result = $var->{$key};
     }
   }
   if ($strict) {
     if ($result === null) {
       $result = $default;
     }
   } else {
     if ($result == null) {
       $result = $default;
     }
   }
   return $result;
 }

/**
 * Calculate the hash value that using by the crc32 checksum as 8 digits
 *
 * @param  mixed   the value of target
 * @param  boolean whether it returns as bare integer,or string of hexadecimal
 * @return mixed   the hashed value as number, or as string of hex
 * @access public
 * @static
 */
 function hashValue($value, $as_int = false){
   $value = crc32(serialize($value));
   if (!$as_int) {
     $value = sprintf('%08x', $value);
   }
   return $value;
 }

/**
 * Compare the defined value and given argument value
 *
 * This method is useful for PHP versions interchangeable.
 *
 * @example
 * <code>
 *  // Assume some error occurred:
 *  if (!Posql_Utils::compareDefinedValue("PDO::ERR_NONE", $db_error)) {
 *    print "Error!";
 *  }
 * </code>
 *
 * @param  string   the constant string to compared
 * @param  mixed    the value to be compared
 * @param  boolean  whether compare as strict
 * @return boolean  whether the values were same or not
 * @access public
 * @static
 */
 function compareDefinedValue($defined, $value, $strict = false){
   $result = false;
   if (!is_string($defined) && is_string($value)) {
     $tmp = $defined;
     $defined = $value;
     $value = $tmp;
   }
   if (is_string($defined) && defined($defined)) {
     if ($strict) {
       $result = $value === constant($defined);
     } else {
       $result = $value == constant($defined);
     }
   }
   return $result;
 }

/**
 * Sorts the array according to the template of the given arguments
 *
 * @example
 * <code>
 *   $array = array(
 *     'id'   => 1,
 *     'name' => 'foo',
 *     'text' => 'hello!'
 *   );
 *   print "<br/><b>original:</b><br/><pre>\n";
 *   print_r($array);
 *   $template = array('name', 'id', 'text');
 *   Posql_Utils::sortByTemplate($array, $template);
 *   print "\n<b>after:</b><br/>\n";
 *   print_r($array);
 *   print "</pre>";
 *   // output:
 *   //  array(
 *   //     [name] => foo
 *   //     [id]   => 1
 *   //     [text] => hello!
 *   //  );
 * </code>
 *
 * @example
 * <code>
 *   $array = array();
 *   for ($i = 0; $i < 5; $i++) {
 *     $array[] = array(
 *       'id'   => $i,
 *       'name' => 'foo@' . $i,
 *       'text' => 'hello!' . $i
 *     );
 *   }
 *   echo "<b>Original:</b><br/><pre>\n";
 *   var_dump($array);
 *   echo "<b>After:</b><br/>\n";
 *   Posql_Utils::sortByTemplate($array, 'text', 'name', 'id');
 *   var_dump($array);
 *   echo "</pre>";
 * </code>
 *
 * @param  array   the target array as reference variable
 * @param  mixed   template which becomes row of key to sort it
 * @return boolean success or failure
 * @access public
 * @static
 */
 function sortByTemplate(&$array, $templates = array()){
   $result = false;
   if (is_array($array)) {
     $dim = is_array(reset($array));
     if (!$dim) {
       $array = array($array);
     }
     if (func_num_args() > 2) {
       $args = func_get_args();
       $tpls = array_slice($args, 1);
     } else {
       $tpls = (array) $templates;
       $flip = array_keys($tpls);
       $check = array_filter($flip, 'is_string');
       if ($check != null) {
         $tpls = $flip;
       }
     }
     $array = array_filter($array, 'is_array');
     $elems = array();
     $count = count($array);
     $i = 0;
     do {
       $shift = array_shift($array);
       $elems[$i] = array();
       foreach ($tpls as $key) {
         if (array_key_exists($key, $shift)) {
           $elems[$i][$key] = $shift[$key];
         }
       }
     } while ($count > ++$i);
     unset($shift);
     if ($dim) {
       $array = array_splice($elems, 0);
     } else {
       $array = reset($elems);
     }
     unset($elems);
     $result = true;
   }
   return $result;
 }

/**
 * A shortcut of preg_split() as PREG_SPLIT_NO_EMPTY
 *
 * @param  string  pattern of the RegExp
 * @param  string  input values
 * @param  number  divided the maximum value
 * @param  number  flags of split mode
 * @return array   divided array
 * @access public
 * @static
 */
 function split($pattern, $subject, $limit = -1, $flags = 0){
   $result = null;
   $flags |= PREG_SPLIT_NO_EMPTY;
   if ($limit === -1) {
     $result = preg_split($pattern, $subject, $limit, $flags);
   } else {
     $result = @preg_split($pattern, $subject, $limit, $flags);
     if (!is_array($result)) {
       $result = array();
     }
   }
   return $result;
 }

/**
 * Splits to string as format to which the name is effective
 *
 * @param  string  input values
 * @param  number  divided the maximum value
 * @param  number  flags of split mode
 * @return array   divided array
 * @access public
 * @static
 */
 function splitEnableName($subject, $limit = -1, $flags = 0){
   static $pattern = '{[^a-zA-Z0-9_\x7F-\xFF]+}';
   $result = array();
   if (isset($this)) {
     $result = $this->split($pattern, $subject, $limit, $flags);
   } else {
     $result = Posql_Utils::split($pattern, $subject, $limit, $flags);
   }
   return $result;
 }

/**
 * Examine whether it is an associate array
 *
 * @example
 * <code>
 * $array = array(1, 2, 'foo');
 * var_dump(Posql_Utils::isAssoc($array));
 * // result: false
 * </code>
 *
 * @example
 * <code>
 * $array = array('key1' => 'value1', 'key2' => 'value2');
 * var_dump(Posql_Utils::isAssoc($array));
 * // result: true
 * </code>
 *
 * @example
 * <code>
 * $array = array(1, 2, 'key' => 'foo');
 * var_dump(Posql_Utils::isAssoc($array));
 * // result: true
 * </code>
 *
 * @param  array   the target array
 * @return boolean the result whether the array was associated
 * @access public
 * @static
 */
 function isAssoc($array){
   $result = false;
   if (is_array($array)) {
     $keys = array_keys($array);
     $array = array_filter($keys, 'is_string');
     $result = $array != null;
   }
   return $result;
 }

/**
 * Cleans the array which removed empty value
 *
 * @example
 * <code>
 * $array = array('', 'foo', null, 'bar');
 * var_dump(Posql_Utils::cleanArray($array));
 * // result: array('foo', 'bar');
 * </code>
 *
 * @param  array   the target array
 * @param  boolean whether key to array is preserve or reset it
 * @return array   an array which removed empty value
 * @access public
 * @static
 */
 function cleanArray($array, $preserve_keys = false){
   $result = array();
   if (is_array($array)) {
     $result = array_filter(array_map('trim', $array), 'strlen');
     if (!$preserve_keys) {
       $result = array_values($result);
     }
   }
   return $result;
 }

/**
 * Exchanges all keys valid from values in an array
 *
 * @example
 * <code>
 *  $array = array('foo', null, '', array(2), 'bar');
 *  var_dump(Posql_Utils::flipArray($array));
 *  // result: array('foo' => 0, 'bar' => 1);
 * </code>
 *
 * @param  array   an array of target
 * @return array   the flipped array
 * @access public
 * @static
 */
 function flipArray($array){
   $result = array();
   if (is_array($array)) {
     $array = array_filter(array_filter($array, 'is_string'), 'strlen');
     $result = array_flip(array_splice($array, 0));
   }
   return $result;
 }

/**
 * Bundles the multi-dimensional array to an array which has one dimension
 *
 * @example
 * <code>
 * $array = array(1, 2, array(3, 4, 5), 6);
 * var_dump(Posql_Utils::toOneArray($array));
 * // result: array(1, 2, 3, 4, 5, 6);
 * </code>
 *
 * @example
 * <code>
 * $array = array(1, 2, array(3, 4, array(4.1, 4.2), 5), 6);
 * var_dump(Posql_Utils::toOneArray($array));
 * // result: array(1, 2, 3, 4, 4.1, 4.2, 5, 6);
 * </code>
 *
 * @param  array  the target array
 * @return array  an array which has only one dimension
 * @access public
 * @static
 */
 function toOneArray($value){
   $result = array();
   if (is_array($value)) {
     $value = array_values($value);
     foreach ($value as $index => $val) {
       if (is_array($val)) {
         while (($valid = array_filter($val, 'is_array')) != null) {
           foreach ($valid as $i => $v) {
             array_splice($val, $i, 1, $v);
           }
         }
         foreach ($val as $v) {
           $result[] = $v;
         }
       } else {
         $result[] = $val;
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the class exists without triggering __autoload().
 *
 * @param   string   the class name of target
 * @return  boolean  TRUE success and FALSE on error
 * @access  public
 */
 function existsClass($class){
   $result = false;
   if ($class != null && is_string($class)) {
     if ($this->isPHP5) {
       $result = class_exists($class, false);
     } else {
       $result = class_exists($class);
     }
   }
   return $result;
 }

/**
 * Translates a string with underscores into camel case
 *
 * @example
 * <code>
 * echo Posql_Utils::camelize('font-color');   // output: 'fontColor'
 * echo Posql_Utils::camelize('-moz-opacity'); // output: 'MozOpacity'
 * </code>
 *
 * @param  string  subject string
 * @param  string  optionally, a separator character (default = '-')
 * @return string  string which translated into camel caps
 * @access public
 * @static
 */
 function camelize($string, $separator = '-'){
   $org_head = substr($string, 0, 1);
   $string = str_replace(' ', '', ucwords(strtr($string, $separator, ' ')));
   if ($org_head !== $separator) {
     $head = substr($string, 0, 1);
     if ($org_head !== $head) {
       $string = $org_head . substr($string, 1);
     }
   }
   return $string;
 }

/**
 * Translates a camel case string into a string with underscores
 *
 * @example
 * <code>
 * echo Posql_Utils::underscore('underScore');    // output: 'under_score'
 * echo Posql_Utils::underscore('PrivateMethod'); // output: '_private_method'
 * </code>
 *
 * @param  string  subject string
 * @return string  string which translated into underscore format
 */
 function underscore($string){
   static $maps = null;
   if ($maps === null) {
     $maps = array();
     for ($c = 0x41; $c <= 0x5A; ++$c) {
       $maps[chr($c)] = '_' . strtolower(chr($c));
     }
   }
   $string = strtolower(strtr($string, $maps));
   return $string;
 }

/**
 * It is examined whether Mime-Type is text/plain.
 * It is searched from the Content-Type and the header information
 *
 * @param  void
 * @return boolean whether Mime-Type is text/plain, or not
 * @access public
 * @static
 */
 function isContentPlainText(){
   $result = false;
   if (strncasecmp(php_sapi_name(), 'cli', 3) === 0) {
     $result = true;
   } else {
     $headers = array();
     if (function_exists('headers_list')) {
       $headers = @headers_list();
     } else if (function_exists('apache_response_headers')) {
       $headers = @apache_response_headers();
     }
     if (!empty($headers)) {
       $content_type = 'content-type';
       $text_plain   = 'text/plain';
       foreach ((array)$headers as $key => $val) {
         if (is_string($key)) {
           $val = $key . $val;
         }
         $val = strtolower($val);
         if (strpos($val, $content_type) !== false
          && strpos($val, $text_plain)   !== false) {
           $result = true;
           break;
         }
       }
     }
   }
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Error
 *
 * The class raise and generate the errors as stackable exception
 *
 * @package Posql
 * @author  polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Error extends Posql_Utils {
/**
 * The error handler for expression
 *
 * @access public
 */
 function errorHandler($no, $msg, $file, $line){
   $php_errormsg = sprintf('%s on line %d', $msg, $line);
   $this->pushError($msg);
 }

/**
 * Generates the error message
 * It is stored in an internal variable, and it handle stackable
 *
 * @param  string   the error message
 * @return void
 * @access private
 */
 function pushError($msg = 'unknown error'){
   $result = 0;
   if (func_num_args() > 1) {
     $args = func_get_args();
     $format = array_shift($args);
     if (empty($args)) {
       $msg = $format;
     } else {
       foreach ($args as $i => $arg) {
         if (!is_scalar($arg)) {
           $args[$i] = gettype($arg);
         }
       }
       $msg = vsprintf($format, $args);
     }
   }
   $trace = null;
   if (function_exists('debug_backtrace')) {
     $trace = @debug_backtrace();
   }
   if (!is_array($trace)) {
     $trace = array();
   }
   $bt = array_pop($trace) + $this->getDefaultBackTrace();
   $code = $this->mapErrorObject($trace);
   $this->errors[] = array(
     'msg'  => $msg,
     'line' => $bt['line'],
     'file' => $bt['file'],
     'code' => $code
   );
 }

/**
 * A helper function for the return value of debug_backtrace()
 *
 * @param  void
 * @return array  an array that has the all backtrace keys
 * @access private
 */
 function getDefaultBackTrace(){
   $default = array(
     'function' => null,
     'line'     => __LINE__,
     'file'     => __FILE__,
     'class'    => __CLASS__,
     'object'   => null,
     'type'     => null,
     'args'     => array()
   );
   return $default;
 }

/**
 * Converts it to the error message from the class name
 *   which generated the error
 *
 * @param  mixed    the class name
 * @return string   the error code as string
 * @access private
 */
 function mapErrorObject($class){
   $result = '';
   if (is_array($class)) {
     $reset = reset($class);
     if (is_array($reset) && count($class) >= 1 && isset($reset['class'])
      && strtolower(substr($reset['class'], -5)) === 'error') {
       array_shift($class);
       if (is_array(reset($class))) {
         $class = reset($class);
       }
     }
     foreach (array('class', 3) as $key) {
       if (array_key_exists($key, $class)) {
         $class = $class[$key];
         break;
       }
     }
   }
   if (is_string($class)) {
     if (strpos($class, '_') === false) {
       $class .= '_';
     }
     $class = explode('_', trim($class, '_'));
     $class = array_filter($class, 'strlen');
     reset($class);
     if (count($class) > 1) {
       $class = next($class);
     } else {
       $class = current($class);
     }
   }
   if (!is_string($class)) {
     $class = '';
   }
   switch (strtolower($class)) {
     case 'builder':
         $result = 'Compile';
         break;
     case 'config':
         $result = 'Setting';
         break;
     case 'expr':
     case 'method':
         $result = 'Expression';
         break;
     case 'file':
         $result = 'IO';
         break;
     case 'parser':
         $result = 'Parse';
         break;
     case 'core':
     case 'ctype':
     case 'error':
     case 'math':
     case 'utils':
     case 'utf8':
     case 'unicode':
     case 'charset':
         $result = 'Internal';
         break;
     case 'statement':
         $result = 'Statement';
         break;
     case 'posql':
     default:
         $result = '';
         break;
   }
   return $result;
 }

/**
 * Pops an error off of the error stack
 * If the stack is empty, NULL will be returned
 *
 * @param  void
 * @return mixed
 * @access public
 */
 function popError(){
   $result = array_pop($this->errors);
   return $result;
 }

/**
 * Formats the error message from the error stack values
 *
 * @param  array   the error stack
 * @return string  the error message that was formatted
 * @access private
 */
 function formatError($error){
   $result = 'error';
   $map = array(
     '-c-'  => '%s',
     'code' => '%s',
     '-e-'  => 'Error:',
     'msg'  => '%s',
     'file' => 'in %s',
     'line' => 'on line %d'
   );
   if (is_array($error)) {
     $format = array();
     $i = 0;
     foreach ($map as $key => $val) {
       if (!$i--) {
         $arg = $this->getClass();
       } else if (array_key_exists($key, $error)) {
         $arg = $error[$key];
       } else {
         $arg = $val;
       }
       $format[] = sprintf($val, $arg);
     }
     $result = implode(' ', $format);
   }
   return $result;
 }

/**
 * Returns the last error message
 * If no errors, it return empty string ('')
 *
 * @param  boolean whether details besides the message are contained or not
 * @return string  the last error message
 * @access public
 */
 function lastError($detail = false){
   $result = '';
   $e = $this->popError();
   if (!empty($e) && is_array($e)) {
     if ($detail) {
       $result = $this->formatError($e);
     } else {
       if (array_key_exists('msg', $e)) {
         $result = $e['msg'];
       } else {
         $result = 'unknown error';
       }
     }
   }
   return $result;
 }

/**
 * Returns the all error messages as an array
 *
 * @param  boolean whether details besides the message are contained or not
 * @return array   the all errors as an array
 * @access public
 */
 function getErrors($detail = false){
   $result = array();
   if ($this->hasError()) {
     while ($this->hasError()) {
       array_unshift($result, $this->lastError($detail));
     }
   }
   return $result;
 }

/**
 * Checks whether it had the error
 *
 * @param  void
 * @return boolean whether it had the error or not
 * @access public
 */
 function isError(){
   return !empty($this->errors);
 }

/**
 * Checks whether it had the error
 *
 * @param  void
 * @return boolean whether it had the error or not
 * @access public
 */
 function hasError(){
   return !empty($this->errors);
 }

/**
 * How many numbers of errors are there
 *
 * @param  void
 * @return number  the numbers of errors count
 * @access public
 */
 function numError(){
   return count($this->errors);
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_File
 *
 * The class which collected the file utility for Posql
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_File extends Posql_Error {
/**
 * A shortcut of fopen() and fgets() function call
 *
 * Note:
 *   Version 2.10 latest is:
 *     The second argument was removed.
 *     Instead, fseekLine() is used.
 *
 * @see fseekLine
 *
 * @param  string  operation mode
 * @return mixed   stream resource or false
 * @access private
 */
 function fopen($mode){
   $mode = strtolower($mode);
   if (strpos($mode, 'b') === false) {
     $mode .= 'b';
   }
   if ($fp = @fopen($this->path, $mode)) {
     if ($this->isWriteMode($mode)) {
       set_file_buffer($fp, 0);
     }
   } else {
     if (isset($this->path)) {
       $path = $this->path;
     } else {
       $path = null;
     }
     if (!empty($this->_useQuery)
      && !empty($this->lastMethod)
      && (($this->lastMethod === 'drop'
      &&   $this->_ifExistsClause !== 'if_exists')
      ||  ($this->lastMethod === 'create'
      &&   $this->_ifExistsClause !== 'if_not_exists'))) {
       $this->pushError('Cannot open file(%s)', $path);
     }
     $fp = false;
   }
   return $fp;
 }

/**
 * Checks whether fopen()'s mode is writing or not
 *
 * @param  string   fopen()'s mode
 * @param  boolean  whether mode is writing or not
 * @access public
 * @static
 */
 function isWriteMode($mode){
   return strpos($mode, '+') !== false
       || strpos($mode, 'a') !== false
       || strpos($mode, 'w') !== false
       || strpos($mode, 'x') !== false;
 }

/**
 * Optimized method for fgets() function
 *
 * @param  resource stream handle
 * @param  boolean  whether to trim or not
 * @return string   next one line on stream
 * @access private
 */
 function fgets(&$fp, $trim = false){
   /*
   static $has = null;
   if ($has === null) {
     $has = function_exists('stream_get_line')
         && !empty($this->isPHP5);
   }
   if ($has) {
     $line = trim(stream_get_line($fp, $this->MAX, $this->NL)) . $this->NL;
   } else {
     $line = fgets($fp);
   }
   return $trim ? trim($line) : $line;
   */
   //
   //Fixed bug on PHP function stream_get_line().
   //
   // Bug #44607 stream_get_line unable to correctly identify
   //            the "ending" in the stream content.
   // @see  http://bugs.php.net/bug.php?id=44607
   //
   // Bug #43522 stream_get_line eats additional characters.
   // @see  http://bugs.php.net/bug.php?id=43522
   //
   // stream_get_line() fixed since PHP 5.2.7
   // But it is assumed that it does not use it now for stability.
   //
   $line = fgets($fp);
   if ($trim) {
     $line = trim($line);
   }
   return $line;
 }

/**
 * Optimized method for fwrite() function
 *
 * @param  resource stream handle
 * @param  string   the string that is to be written
 * @return number   the number of bytes written, or FALSE on error
 * @access public
 * @static
 */
 function fputs(&$fp, $string){
   $result = fwrite($fp, $string, strlen($string));
   return $result;
 }

/**
 * Seek lines on a file pointer
 *
 * @param  resource  stream handle
 * @param  number    number of lines for moving cursor
 * @return void
 * @access private
 */
 function fseekLine(&$fp, $lines = 0){
   if (is_int($lines)) {
     while (--$lines >= 0) {
       $this->fgets($fp);
     }
   }
 }

/**
 * Appends given string into the file with the specified line number
 *
 * @param  string   filename
 * @param  mixed    string or array to append
 * @param  number   line number to insert which starts from 1
 * @param  string   the string glue to join array
 * @return number   the number of bytes written
 * @access public
 */
 function appendFileLine($filename, $append_data, $line_number, $glue = ''){
   $result = 0;
   if (is_numeric($line_number)) {
     $line_number = $line_number - 0;
     $appends = array();
     if ($append_data === null || is_scalar($append_data)) {
       $appends = array((string)$append_data);
     } else {
       if (!is_array($append_data)) {
         $append_data = (array)$append_data;
       }
       if (empty($append_data)) {
         $append_data = array(null);
       }
       $appends = array_map('strval', $append_data);
     }
     $org_ext = $this->setExt(null);
     $org_path = $this->getPath();
     $this->setPath($filename);
     if ($fp = $this->fopen('r')) {
       $i = 1;
       $nl = '';
       while (!feof($fp)) {
         if ($i == $line_number) {
           $buffer = $this->fgets($fp);
           if ($nl == null) {
             $nl = substr($buffer, -2);
             if (trim($nl) != null) {
               $nl = substr($nl, -1);
               if (trim($nl) != null) {
                 $nl = '';
               }
             }
           }
           $buffer = substr($buffer, 0, -strlen($nl));
           if (count($appends) > 1) {
             $nl = '';
           }
           reset($appends);
           $key = key($appends);
           $appends[$key] = $buffer . $appends[$key] . $nl;
           break;
         } else {
           if ($i === 1) {
             $buffer = $this->fgets($fp);
             $nl = substr($buffer, -2);
             if (trim($nl) != null) {
               $nl = substr($nl, -1);
               if (trim($nl) != null) {
                 $nl = '';
               }
             }
             unset($buffer);
           } else {
             $this->fgets($fp);
           }
         }
         $i++;
       }
       fclose($fp);
       $result = $this->_replaceFileLine($appends, $line_number, $glue);
     }
     $this->setExt($org_ext);
     $this->setPath($org_path);
   }
   return $result;
 }

/**
 * Insert given string into the file with the specified line number
 *
 * @param  string   filename
 * @param  mixed    string or array to insert
 * @param  number   line number to insert which starts from 1
 * @param  string   the string glue to join array
 * @return number   the number of bytes written
 * @access public
 */
 function insertFileLine($filename, $insert_data, $line_number, $glue = ''){
   $result = 0;
   if (is_numeric($line_number)) {
     $line_number = $line_number - 0;
     $inserts = array();
     if ($insert_data === null || is_scalar($insert_data)) {
       $inserts = array((string)$insert_data);
     } else {
       if (!is_array($insert_data)) {
         $insert_data = (array)$insert_data;
       }
       if (empty($insert_data)) {
         $insert_data = array(null);
       }
       $inserts = array_map('strval', $insert_data);
     }
     $org_ext = $this->setExt(null);
     $org_path = $this->getPath();
     $this->setPath($filename);
     if ($fp = $this->fopen('r')) {
       $i = 1;
       $nl = '';
       while (!feof($fp)) {
         if ($i == $line_number) {
           $buffer = $this->fgets($fp);
           if ($nl == null) {
             $nl = substr($buffer, -2);
             if (trim($nl) != null) {
               $nl = substr($nl, -1);
               if (trim($nl) != null) {
                 $nl = '';
               }
             }
           }
           if (count($inserts) > 1) {
             $buffer = substr($buffer, 0, -strlen($nl));
           }
           end($inserts);
           $key = key($inserts);
           $inserts[$key] .= $nl . $buffer;
           break;
         } else {
           if ($i === 1) {
             $buffer = $this->fgets($fp);
             $nl = substr($buffer, -2);
             if (trim($nl) != null) {
               $nl = substr($nl, -1);
               if (trim($nl) != null) {
                 $nl = '';
               }
             }
             unset($buffer);
           } else {
             $this->fgets($fp);
           }
         }
         $i++;
       }
       fclose($fp);
       $result = $this->_replaceFileLine($inserts, $line_number, $glue);
     }
     $this->setExt($org_ext);
     $this->setPath($org_path);
   }
   return $result;
 }

/**
 * Replaces to given string into the file with the specified line number
 *
 * @param  string   filename
 * @param  mixed    replacement as string or array
 * @param  number   line number to replace which starts from 1
 * @param  string   the string glue to join array
 * @return number   the number of bytes written
 * @access public
 */
 function replaceFileLine($filename, $replacement, $line_number, $glue = ''){
   $result = 0;
   if (is_numeric($line_number)) {
     $line_number = $line_number - 0;
     $replacements = array();
     if ($replacement === null || is_scalar($replacement)) {
       $replacements = array((string)$replacement);
     } else {
       if (!is_array($replacement)) {
         $replacement = (array)$replacement;
       }
       if (empty($replacement)) {
         $replacement = array(null);
       }
       $replacements = array_map('strval', $replacement);
     }
     unset($replacement);
     $org_ext = $this->setExt(null);
     $org_path = $this->getPath();
     $this->setPath($filename);
     $nl = '';
     if ($fp = $this->fopen('r')) {
       $buffer = $this->fgets($fp);
       fclose($fp);
       $nl = substr($buffer, -2);
       unset($buffer);
       if (trim($nl) != null) {
         $nl = substr($nl, -1);
         if (trim($nl) != null) {
           $nl = '';
         }
       }
       if (count($replacements) > 1) {
         $nl = '';
       }
       end($replacements);
       $key = key($replacements);
       $replacements[$key] .= $nl;
       $result = $this->_replaceFileLine($replacements, $line_number, $glue);
     }
     $this->setExt($org_ext);
     $this->setPath($org_path);
   }
   return $result;
 }

/**
 * Replaces to given string into the file with the specified line number
 *
 * @param  mixed    replacement as string or array
 * @param  number   line number to replace which starts from 1
 * @param  string   the string glue to join array
 * @return number   the number of bytes written
 * @access private
 */
 function _replaceFileLine($replacement, $line_number, $glue = ''){
   $result = 0;
   if (is_numeric($line_number)) {
     $line_number = $line_number - 0;
     $replacements = array();
     if ($replacement === null || is_scalar($replacement)) {
       $replacements = array((string)$replacement);
     } else {
       if (!is_array($replacement)) {
         $replacement = (array)$replacement;
       }
       if (empty($replacement)) {
         $replacement = array(null);
       }
       $replacements = array_map('strval', $replacement);
     }
     $replacement = '';
     if (is_array($replacements)) {
       $count = count($replacements);
       if ($count === 1) {
         $replacement = array_shift($replacements);
       } else {
         while (--$count >= 0) {
           $replacement .= array_shift($replacements) . $glue;
         }
       }
       $written_bytes = 0;
       $length = strlen($replacement);
       $buffer_size = 0x2000;
       if ($rp = $this->fopen('r')) {
         if ($wp = $this->fopen('r+')) {
           $i = 1;
           while (!feof($rp)) {
             if ($i == $line_number) {
               $this->fgets($rp);
               $buffers = array();
               $buffers[] = fread($rp, $length);
               $written_bytes = $this->fputs($wp, $replacement);
               $buffer_size += $length;
               if (!feof($rp)) {
                 do {
                   $buffers[] = fread($rp, $buffer_size);
                   $this->fputs($wp, array_shift($buffers));
                 } while (!feof($rp));
               }
               $this->fputs($wp, array_shift($buffers));
               $org_user_abort = ignore_user_abort(1);
               ftruncate($wp, ftell($wp));
               ignore_user_abort($org_user_abort);
               $result = $written_bytes;
               break;
             } else {
               $this->fputs($wp, $this->fgets($rp));
             }
             $i++;
           }
           fclose($wp);
         }
         fclose($rp);
       }
     }
   }
   return $result;
 }

/**
 * Parse CSV (Comma-Separated Values) fields as string.
 * Almost compatibly PHP's fgetcsv() function.
 *
 * @link http://www.ietf.org/rfc/rfc4180.txt
 *
 * @param  resource  a valid file pointer
 * @param  string    the field delimiter
 * @param  string    the field enclosure character
 * @return array     an indexed array containing the fields or FALSE
 * @access public
 */
 function fgetcsv(&$fp, $delimiter = ',', $enclosure = '"'){
   $result = false;
   if (!feof($fp)) {
     $line = '';
     while (!feof($fp)) {
       $line .= $this->fgets($fp);
       $quote_count = substr_count($line, $enclosure);
       if ($quote_count % 2 === 0) {
         break;
       }
     }
     $line = rtrim($line);
     if ($line != null) {
       $quoted_delimiter = preg_quote($delimiter, '/');
       $quoted_enclosure = preg_quote($enclosure, '/');
       $pattern = sprintf('/(?:(?<=%s)(?=(?:(?:[^%s]*%s){2})*[^%s]*$)|^)'
                        .  '(?:[^%s,]+|%s(?:[^%s]|%s%s)+%s|)/',
         $quoted_delimiter, $quoted_enclosure, $quoted_enclosure,
         $quoted_enclosure, $quoted_enclosure, $quoted_enclosure,
         $quoted_enclosure, $quoted_enclosure, $quoted_enclosure,
         $quoted_enclosure
       );
       preg_match_all($pattern, $line, $matches);
       if (!empty($matches[0])) {
         $fields = array_shift($matches);
         if (is_array($fields)) {
           $result = array();
           foreach ($fields as $i => $field) {
             if (substr($field, 0, 1) === $enclosure
              && substr($field, -1)  === $enclosure) {
               $field = substr($field, 1, -1);
             }
             $enclosure2 = $enclosure . $enclosure;
             if (strpos($field, $enclosure2) !== false) {
               $field = str_replace($enclosure2, $enclosure, $field);
             }
             $result[] = $field;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Returns full path as realpath() function behaves
 *
 * @param  string  file path
 * @param  boolean checks then return valid path or filename
 * @return string  file path of full
 * @access public
 */
 function fullPath($path, $check_exists = false){
   static $backslash = '\\', $slash = '/', $colon = ':';
   $result = '';
   $fullpath = $path;
   $pre0 = substr($path, 0, 1);
   $pre1 = substr($path, 1, 1);
   if ((!$this->isWin && $pre0 !== $slash)
     || ($this->isWin && $pre1 !== $colon)) {
     $fullpath = getcwd() . $slash . $path;
   }
   $fullpath = strtr($fullpath, $backslash, $slash);
   $items = explode($slash, $fullpath);
   $new_items = array();
   foreach ($items as $item) {
     if ($item == null || strpos($item, '.') === 0) {
       if ($item === '..') {
         array_pop($new_items);
       }
       continue;
     }
     $new_items[] = $item;
   }
   $fullpath = implode($slash, $new_items);
   if (!$this->isWin) {
     $fullpath = $slash . $fullpath;
   }
   $result = $fullpath;
   if ($check_exists) {
     clearstatcache();
     if (!@file_exists($result)) {
       $result = false;
     }
   }
   return $result;
 }

/**
 * A handy function like the dirname() of inherited call
 *
 * Note:
 *  Windows: unable to use "*" for filename
 *  Unix:    unable to use "\0" for filename
 *  MacOS:   unable to use ":" for filename
 *
 * @example
 * <code>
 * $posql = new Posql;
 * echo $posql->dirname('C:/tmp');       // output: 'C:/tmp'
 * echo $posql->dirname('C:/tmp/');      // output: 'C:/tmp'
 * echo $posql->dirname('C:/tmp//');     // output: 'C:/tmp'
 * echo $posql->dirname('/tmp/hoge');    // output: '/tmp/hoge'
 * echo $posql->dirname('/tmp/hoge/');   // output: '/tmp/hoge'
 * echo $posql->dirname('/tmp/hoge///'); // output: '/tmp/hoge'
 * </code>
 *
 * @example
 * <code>
 * $posql = new Posql;
 * echo $posql->dirname('C:/path/to/file.ext', 1); // output: 'C:/path/to'
 * echo $posql->dirname('C:/path/to/file.ext', 2); // output: 'C:/path'
 * echo $posql->dirname('C:/path/to/file.ext', 3); // output: 'C:/'
 * echo $posql->dirname('C:/path/to/file.ext', 4); // output: 'C:/'
 * echo $posql->dirname('/path/to/file.ext', 2);   // output: '/path'
 * echo $posql->dirname('/path/to/file.ext', 3);   // output: '/'
 * echo $posql->dirname('/path/to/file.ext', 4);   // output: '/'
 * </code>
 *
 * @param  string  file path to target
 * @param  int     how many hierarchies to up for path
 * @return string  the name of the directory, or FALSE on failure
 * @access public
 * @static
 */
 function dirname($path, $hierarchy = 0){
   static $dummy = "/:*\0";
   $result = false;
   if (is_scalar($path)) {
     if ($path === '/') {
       $result = $path;
     } else {
       $result = dirname($path . $dummy);
       if (is_numeric($hierarchy)) {
         $base = null;
         $prev = null;
         while (--$hierarchy >= 0) {
           $prev = $result;
           $base = basename($result);
           $result = dirname($result);
           if (dirname($result) === $result) {
             break;
           }
         }
         if ($prev != null && $base != null
          && dirname($result) === $result) {
           $result = substr($prev, 0, -strlen($base));
         }
         if (strlen($result) > strlen($path)) {
           $result = $path;
         }
       }
     }
   }
   return $result;
 }

/**
 * Fixes with the suffix the paths
 *
 * @example
 * <code>
 * $posql = new Posql;
 * echo $posql->dirnameWith('/path/to/file');   // output: '/path/to/file/'
 * echo $posql->dirnameWith('path/file.ext');   // output: 'path/file.ext/'
 * echo $posql->dirnameWith('/path/');          // output: '/path/'
 * echo $posql->dirnameWith('/path');           // output: '/path/'
 * echo $posql->dirnameWith('/');               // output: '/'
 * echo $posql->dirnameWith('C:/tmp/a/file');   // output: 'C:/tmp/a/file/'
 * echo $posql->dirnameWith('C:/tmp/file.ext'); // output: 'C:/tmp/file.ext/'
 * echo $posql->dirnameWith('C:/tmp/');         // output: 'C:/tmp/'
 * echo $posql->dirnameWith('C:/tmp');          // output: 'C:/tmp/'
 * echo $posql->dirnameWith('C:');              // output: 'C:/'
 * </code>
 *
 * @param  string  file path to target
 * @param  string  optionally, the suffix (default = '/')
 * @return string  the name of the directory, or NULL on failure
 * @access public
 */
 function dirnameWith($path, $with = '/'){
   $result = null;
   if (is_scalar($path)) {
     if (!is_scalar($with)) {
       $result = $this->dirname($path);
     } else {
       $result = rtrim($this->dirname($path), $with) . $with;
     }
   }
   return $result;
 }

/**
 * Returns the extension that added the dot(.)
 *
 * @param  string  extension
 * @param  boolean whether the extension of all lowercase or not
 * @return string  extension that added the period
 * @access public
 * @static
 */
 function toExt($ext = null, $tolower = false){
   if ($ext == null && isset($this->ext)) {
     $ext = $this->ext;
   }
   if ($ext != null) {
     $ext = '.' . ltrim($ext, '.');
   }
   if ($tolower) {
     $ext = strtolower($ext);
   }
   return $ext;
 }

/**
 * Get the extension name from filename without dot
 *
 * @example
 * <code>
 * $posql = new Posql;
 * echo $posql->getExtensionName("/path/to/file.ext"); // output: "ext"
 * echo $posql->getExtensionName(".ext");              // output: "ext"
 * echo $posql->getExtensionName("..ext");             // output: "ext"
 * echo $posql->getExtensionName("ext2.ext1.ext");     // output: "ext"
 * echo $posql->getExtensionName("ext.");              // output: ""
 * echo $posql->getExtensionName("ext");               // output: ""
 * echo $posql->getExtensionName("file.ext");          // output: "ext"
 * </code>
 *
 * @param  string   filename
 * @param  boolean  whether result returned lower-case
 * @return string   the extension name
 * @access public
 * @static
 */
 function getExtensionName($filename, $tolower = false){
   $ext = null;
   $base = trim(basename($filename));
   $tail = strrchr($base, '.');
   if ($tail == null) {
     $ext = '';
   } else {
     if ($tail === '.') {
       $ext = '';
     } else {
       $ext = substr($tail, 1);
     }
   }
   if ($tolower) {
     $ext = strtolower($ext);
   }
   return $ext;
 }

/**
 * Assigns the mode of locking for constant LOCK_*
 *
 * @param  number  the mode of locking
 * @return number  assigned locking mode
 * @access private
 */
 function assignLockMode($mode){
   switch ((int)$mode) {
     case 1:
     case LOCK_SH:
     case $this->LOCK_SH:
         $mode = $this->LOCK_SH;
         break;
     case 2:
     case LOCK_EX:
     case $this->LOCK_EX:
         $mode = $this->LOCK_EX;
         break;
     case 3:
     case LOCK_UN:
     case 4:
     case LOCK_NB:
     case $this->LOCK_NONE:
     default:
         $mode = $this->LOCK_NONE;
         break;
   }
   $mode = (int)$mode;
   return $mode;
 }

/**
 * Not implemented function
 */
 function setResultStorage($mode, $storage = null){
   //TODO: The storage of the result row should be able to be set
   //      in the future.
   switch ($mode) {
     case 'auto':    //use auto
     case 'tmpfile': //use tmpfile();
     case 'memory':  //use memory
     case 'file':    //use file
   }
 }

/**
 * Get the unique filename which is able to create actually
 *
 * @param  string  target directory name
 * @param  string  string which is preppended to the filename
 * @return string  the unique filename, or FALSE on failure
 * @access public
 */
 function getUniqueFilename($dir, $prefix = null){
   $result = false;
   if ($dir != null && is_string($dir)) {
     $dir = $this->fullPath($dir, true);
     if ($dir) {
       $dir = $this->dirname($dir);
       if ($prefix === null || !is_scalar($prefix)) {
         $prefix = sprintf('%s_dummy_%s', $this->getClassName(), $this->id);
         $prefix = strtolower($prefix);
       }
       do {
         clearstatcache();
         $dirname = sprintf('%s_%.08f', $prefix, $this->getMicrotime());
         $dirname = rawurlencode($dirname);
         $path = sprintf('%s/%s', $dir, $dirname);
       } while (@file_exists($path));
       $result = $dirname;
     }
   }
   return $result;
 }

/**
 * Return the temporary directory name which  is used on locking the database
 *
 * @param  void
 * @return string  the temporary directory name
 * @access private
 */
 function getLockDirName(){
   $result = sprintf('%s/.%s.lock',
     dirname($this->path),
     $this->getDatabaseName()
   );
   return $result;
 }

/**
 * Check whether the current directory is able to lock
 *
 * @param  void
 * @return boolean  whether the current directory is able to lock or not
 * @access private
 */
 function canLockDatabase(){
   $result = false;
   if ($this->path != null) {
     $perms = 0777;
     $dir = dirname($this->path);
     $uniqid = $this->getUniqueFilename($dir);
     if ($uniqid) {
       $path = sprintf('%s/%s', $dir, $uniqid);
       if (@mkdir($path, $perms) && @rmdir($path)) {
         $result = true;
       }
     }
   }
   return $result;
 }

/**
 * Tries to lock a database.
 * It waits maximal timeout seconds for the lock.
 * Clears the dead lock when the timeout passes.
 *
 * @param  void
 * @return void
 * @access private
 */
 function lockDatabase(){
   if ($this->autoLock) {
     $perms = 0777;
     $lock_dir = $this->getLockDirName();
     $timeout = $this->getDeadLockTimeout();
     while (true) {
       $locked = @mkdir($lock_dir, $perms);
       if ($locked) {
         $mask = @umask(0);
         @chmod($lock_dir, $perms);
         @umask($mask);
         break;
       }
       clearstatcache();
       if (@is_dir($lock_dir)) {
         if (!empty($this->_inTransaction)) {
           break;
         } else {
           $mtime = @filemtime($lock_dir);
           if (is_numeric($mtime) && (time() - 60 * $timeout) > $mtime) {
             $this->unlockDatabase();
             continue;
           }
         }
       }
       $this->sleep();
     }
   }
 }

/**
 * Unlocks a file
 *
 * @param  void
 * @return void
 * @access private
 */
 function unlockDatabase(){
   if ($this->autoLock) {
     $lock_dir = $this->getLockDirName();
     if (empty($this->_inTransaction)) {
       @rmdir($lock_dir);
     }
   }
 }

/**
 * Checks whether the table is locking
 * If the argument is not given it will interpreted
 *   as locked by either LOCK_EX or LOCK_SH
 *
 * @param  string  table name
 * @param  number  the mode of locking
 * @return boolean locking or not
 * @access private
 */
 function isLock($table, $mode = null){
   $result = false;
   if ($this->isLockAll()) {
     $result = true;
   } else {
     $mode_org = $mode;
     $mode = $this->assignLockMode($mode);
     $tname = $this->encodeKey($table);
     $tname_symbol = $tname . ':';
     if ($fp = $this->fopen('r')) {
       $this->lockDatabase();
       $this->fseekLine($fp, 1);
       $line = $this->fgets($fp);
       fclose($fp);
       $this->unlockDatabase();
       $symbol_pos = strpos($line, $tname_symbol);
       if ($symbol_pos === false
        || strpos($line, '@') === false) {
         if (empty($this->meta[$tname])) {
           $this->pushError('Not exists the table(%s)', $table);
         } else {
           $this->pushError('Cannot check by isLock(%s)', $table);
         }
       } else {
         $lock_pos = $symbol_pos + strlen($tname_symbol);
         $lock_buffer = substr($line, $lock_pos, 10);
         list($lock_mode, $lock_id) = explode('@', $lock_buffer);
         $lock_mode = $this->assignLockMode($lock_mode);
         if ($lock_id === $this->id) {
           $result = false;
         } else {
           if ($lock_id === $this->toUniqId(0)) {
             $result = false;
           } else {
             if ($mode_org === null) {
               if ($lock_mode !== $this->LOCK_NONE) {
                 $result = true;
               }
             } else {
               if ($lock_mode === $mode) {
                 $result = true;
               }
             }
           }
         }
       }
     }
   }
   if ($result) {
     if (empty($this->autoLock)) {
       $this->autoLock = true;
     }
   }
   return $result;
 }

/**
 * It is checked whether the table is locked as LOCK_EX
 *
 * @param  string   table name
 * @return boolean  locked or not locked
 * @access private
 */
 function isLockEx($table){
   $result = $this->isLock($table, $this->LOCK_EX);
   return $result;
 }

/**
 * It is checked whether the table is locked as LOCK_SH
 *
 * @param  string   table name
 * @return boolean  locked or not locked
 * @access private
 */
 function isLockSh($table){
   $result = $this->isLock($table, $this->LOCK_SH);
   return $result;
 }

/**
 * It is checked whether all tables are locked
 * If the argument is not given it will interpreted
 *   as locked by either LOCK_EX or LOCK_SH
 *
 * @param  number   the mode of locking
 * @return boolean  all locked or not locked
 * @access private
 */
 function isLockAll($mode = null){
   $result = false;
   $mode_org = $mode;
   $mode = $this->assignLockMode($mode);
   if ($fp = $this->fopen('r')) {
     $this->lockDatabase();
     $header = $this->fgets($fp, true);
     fclose($fp);
     $this->unlockDatabase();
     $buffer = substr($header, -10);
     $at_pos = strpos($buffer, '@');
     if ($at_pos === false) {
       $this->pushError('Database might be broken');
     } else {
       list($lock_mode, $lock_id) = explode('@', $buffer);
       $lock_mode = $this->assignLockMode($lock_mode);
       if ($lock_id === $this->id) {
         $result = false;
       } else {
         if ($lock_id === $this->toUniqId(0)) {
           $result = false;
         } else {
           if ($mode_org === null) {
             if ($lock_mode !== $this->LOCK_NONE) {
               $result = true;
             }
           } else {
             if ($lock_mode === $mode) {
               $result = true;
             }
           }
         }
       }
     }
   }
   if ($result) {
     if (empty($this->autoLock)) {
       $this->autoLock = true;
     }
   }
   return $result;
 }

/**
 * It is checked whether all tables are locked as LOCK_EX
 *
 * @param  void
 * @return boolean  all locked or not locked
 * @access private
 */
 function isLockExAll(){
   $result = $this->isLockAll($this->LOCK_EX);
   return $result;
 }

/**
 * It is checked whether all tables are locked as LOCK_SH
 *
 * @param  void
 * @return boolean  all locked or not locked
 * @access private
 */
 function isLockShAll(){
   $result = $this->isLockAll($this->LOCK_SH);
   return $result;
 }

/**
 * Locks to all tables
 * Other processes stand by while locking
 *
 * @param  number   the mode of locking
 * @return boolean  success or not
 * @access private
 */
 function lockAll($mode){
   $result = false;
   $this->userAbort = @ignore_user_abort(1);
   $timeout = $this->getDeadLockTimeout();
   $start_time = time();
   while (!$this->hasError()) {
     $sleep = false;
     if (!$this->_lockAll($mode)) {
       $sleep = true;
     } else {
       $current_id = $this->getLockIdAll();
       if ($current_id === $this->id) {
         $result = true;
         break;
       } else {
         $sleep = true;
       }
     }
     if ($sleep) {
       // Release the dead-lock
       if (time() - $start_time >= $timeout) {
         $this->unlockAll(true);
       }
       $this->sleep();
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function _lockAll($mode){
   $result = false;
   if (!empty($this->_inTransaction)) {
     $result = true;
   } else {
     $mode     = $this->assignLockMode($mode);
     $uniqid   = $this->id;
     $id_len   = strlen($uniqid);
     $lock_pos = strlen($this->getHeader());
     $id_pos   = $lock_pos + 2;
     $puts     = sprintf('%d@%s', $mode, $uniqid);
     if ($this->isLockAll()) {
       $result = false;
     } else {
       if ($fp = $this->fopen('r+')) {
         $this->lockDatabase();
         $do_write = true;
         fseek($fp, $id_pos);
         $lock_id = fread($fp, $id_len);
         if ($lock_id === $this->toUniqId(0)) {
           $do_write = true;
         } else {
           if ($lock_id === $this->id) {
             $do_write = true;
           } else {
             $do_write = false;
           }
         }
         if ($do_write) {
           fseek($fp, $lock_pos);
           $this->fputs($fp, $puts);
           $result = true;
         }
         fclose($fp);
         $this->unlockDatabase();
       }
       if ($result) {
         $current_id = $this->getLockIdAll();
         if ($current_id !== $this->id) {
           $result = false;
         }
       }
     }
   }
   return $result;
 }

/**
 * Locks to all tables as LOCK_EX
 * Other processes stand by while locking
 *
 * @param  void
 * @return boolean  success or not
 * @access private
 */
 function lockExAll(){
   $result = $this->lockAll($this->LOCK_EX);
   return $result;
 }

/**
 * Locks to all tables as LOCK_SH
 * Other processes stand by while locking
 *
 * @param  void
 * @return boolean  success or not
 * @access private
 */
 function lockShAll(){
   $result = $this->lockAll($this->LOCK_SH);
   return $result;
 }

/**
 * Unlocks to all tables
 *
 * @param  boolean  whether it compulsorily unlocks
 *                  it regardless of the state of the lock
 * @return boolean  success or not
 * @access private
 */
 function unlockAll($force = false){
   $result = false;
   while (!$this->hasError()) {
     if (!$this->_unlockAll($force)) {
       $this->sleep();
     } else {
       $result = true;
       break;
     }
   }
   if (isset($this->userAbort)) {
     @ignore_user_abort($this->userAbort);
   }
   return $result;
 }

/**
 * @access private
 */
 function _unlockAll($force = false){
   $result = false;
   if (!empty($this->_inTransaction)) {
     $result = true;
   } else {
     $uniqid   = $this->toUniqId(0);
     $id_len   = strlen($uniqid);
     $lock_pos = strlen($this->getHeader());
     $id_pos   = $lock_pos + 2;
     $puts     = sprintf('%d@%s', $this->LOCK_NONE, $uniqid);
     if (!$force && $this->isLockAll()) {
       $result = false;
     } else {
       if ($fp = $this->fopen('r+')) {
         $this->lockDatabase();
         $do_write = true;
         fseek($fp, $id_pos);
         $lock_id = fread($fp, $id_len);
         if ($lock_id === $this->toUniqId(0)) {
           $do_write = true;
         } else {
           if ($lock_id === $this->id) {
             $do_write = true;
           } else {
             $do_write = false;
           }
         }
         if ($force) {
           $do_write = true;
         }
         if ($do_write) {
           fseek($fp, $lock_pos);
           $this->fputs($fp, $puts);
           $result = true;
         }
         fclose($fp);
         $this->unlockDatabase();
       }
     }
   }
   return $result;
 }

/**
 * Locks to one table
 * Other processes stand by while locking
 *
 * @param  string  table name
 * @param  number  the mode of locking
 * @return boolean success or not
 * @access private
 */
 function lock($table, $mode){
   $result = false;
   $this->userAbort = @ignore_user_abort(1);
   while (!$this->hasError()) {
     $sleep = false;
     if (!$this->_lock($table, $mode)) {
       $sleep = true;
     } else {
       $current_id = $this->getLockId($table);
       if ($current_id === $this->id) {
         $result = true;
         break;
       } else {
         $sleep = true;
       }
     }
     if ($sleep) {
       $this->sleep();
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function _lock($table, $mode){
   $result = false;
   if (!empty($this->_inTransaction)) {
     $result = true;
   } else {
     $tname  = $this->encodeKey($table);
     $symbol = $tname . ':';
     $mode   = $this->assignLockMode($mode);
     $uniqid = $this->toUniqId($this->id);
     $id_len = strlen($uniqid);
     $puts   = sprintf('%d@%s', $mode, $uniqid);
     $sleep  = false;
     switch ($mode) {
       case $this->LOCK_SH:
           if ($this->isLockAll() || $this->isLockEx($table)) {
             $sleep = true;
           }
           break;
       case $this->LOCK_EX:
           if ($this->isLockAll() || $this->isLock($table)) {
             $sleep = true;
           }
           break;
       default:
           break;
     }
     if ($sleep) {
       $result = false;
     } else {
       if ($fp = $this->fopen('r+')) {
         $this->lockDatabase();
         $do_write = true;
         $head = $this->fgets($fp);
         $line = $this->fgets($fp);
         $symbol_pos = strpos($line, $symbol);
         if ($symbol_pos === false
          || strpos($line, '@') === false) {
           if (isset($this->meta[$tname])) {
             $this->pushError('Cannot lock table(%s)', $table);
           } else {
             $this->pushError('Not exists the table(%s)', $table);
           }
           $result = false;
         } else {
           $lock_pos = strlen($head) + strlen($symbol) + $symbol_pos;
           $id_pos   = $lock_pos + 2;
           fseek($fp, $id_pos);
           $lock_id = fread($fp, $id_len);
           if ($lock_id === $this->toUniqId(0)) {
             $do_write = true;
           } else {
             if ($lock_id === $this->id) {
               $do_write = true;
             } else {
               $do_write = false;
             }
           }
           if ($do_write) {
             fseek($fp, $lock_pos);
             $this->fputs($fp, $puts);
             $result = true;
           }
         }
         fclose($fp);
         $this->unlockDatabase();
       }
       if ($result) {
         $current_id = $this->getLockId($table);
         if ($current_id !== $this->id) {
           $result = false;
         }
       }
     }
   }
   return $result;
 }

/**
 * Locks to one table as LOCK_EX
 * Other processes stand by while locking
 *
 * @param  string  table name
 * @return boolean success or not
 * @access private
 */
 function lockEx($table){
   $result = $this->lock($table, $this->LOCK_EX);
   return $result;
 }

/**
 * Locks to one table as LOCK_SH
 * Other processes stand by while locking
 *
 * @param  string   table name
 * @return boolean  success or not
 * @access private
 */
 function lockSh($table){
   $result = $this->lock($table, $this->LOCK_SH);
   return $result;
 }

/**
 * Unlocks to the one table
 *
 * @param  string   table name
 * @param  boolean  whether it compulsorily unlocks
 *                  it regardless of the state of the lock
 * @return boolean  success or not
 * @access private
 */
 function unlock($table, $force = false){
   $result = false;
   while (!$this->hasError()) {
     if (!$this->_unlock($table, $force)) {
       $this->sleep();
     } else {
       $result = true;
       break;
     }
   }
   if (isset($this->userAbort)) {
     @ignore_user_abort($this->userAbort);
   }
   return $result;
 }

/**
 * @access private
 */
 function _unlock($table, $force = false){
   $result = false;
   if (!empty($this->_inTransaction)) {
     $result = true;
   } else {
     $tname  = $this->encodeKey($table);
     $symbol = $tname . ':';
     $uniqid = $this->toUniqId(0);
     $id_len = strlen($uniqid);
     $puts   = sprintf('%d@%s', $this->LOCK_NONE, $uniqid);
     $sleep  = false;
     if (!$force && $this->isLockAll()) {
       $sleep = true;
     }
     if (!$force && $this->isLock($table)) {
       $sleep = true;
     }
     if ($sleep) {
       $result = false;
     } else {
       if ($fp = $this->fopen('r+')) {
         $this->lockDatabase();
         $do_write = true;
         $head = $this->fgets($fp);
         $line = $this->fgets($fp);
         $symbol_pos = strpos($line, $symbol);
         if ($symbol_pos === false
          || strpos($line, '@') === false) {
           $this->pushError('Cannot unlock the table(%s)', $table);
           $result = false;
         } else {
           $lock_pos = strlen($head) + strlen($symbol) + $symbol_pos;
           $id_pos   = $lock_pos + 2;
           fseek($fp, $id_pos);
           $lock_id = fread($fp, $id_len);
           if ($lock_id === $this->toUniqId(0)) {
             $do_write = true;
           } else {
             if ($lock_id === $this->id) {
               $do_write = true;
             } else {
               $do_write = false;
             }
           }
           if ($force) {
             $do_write = true;
           }
           if ($do_write) {
             fseek($fp, $lock_pos);
             $this->fputs($fp, $puts);
             $result = true;
           }
         }
         fclose($fp);
         $this->unlockDatabase();
       }
     }
   }
   return $result;
 }

/**
 * Lock all tables by using lock() and lockAll() methods.
 * Other processes stand by while locking
 *
 * @param  number   the mode of locking
 * @return boolean  success or not
 * @access private
 */
 function lockAllTables($mode){
   $result = false;
   $meta = $this->getMeta();
   if ($meta != null && is_array($meta)) {
     $this->_lockedTables = array();
     foreach (array_keys($meta) as $tname) {
       $this->_lockedTables[] = $this->decodeKey($tname);
     }
     foreach ($this->_lockedTables as $table) {
       if (!$this->lock($table, $mode)) {
         $this->pushError('Failed to lock table(%s)', $table);
         break;
       }
     }
     if ($this->hasError()) {
       foreach ($this->_lockedTables as $table) {
         $this->unlock($table);
       }
       $this->_lockedTables = array();
     } else {
       if ($this->lockAll($mode)) {
         $result = true;
       }
     }
   }
   return $result;
 }

/**
 * Lock all tables as LOCK_EX by using lock() and lockAll() methods
 * Other processes stand by while locking
 *
 * @param  void
 * @return boolean  success or not
 * @access private
 */
 function lockExAllTables(){
   $result = $this->lockAllTables($this->LOCK_EX);
   return $result;
 }

/**
 * Lock all tables as LOCK_SH by using lock() and lockAll() methods
 * Other processes stand by while locking
 *
 * @param  void
 * @return boolean  success or not
 * @access private
 */
 function lockShAllTables(){
   $result = $this->lockAllTables($this->LOCK_SH);
   return $result;
 }

/**
 * Unlock all tables by using unlock() and unlockAll() methods
 *
 * @param  boolean  whether it compulsorily unlocks
 *                  it regardless of the state of the lock
 * @return boolean  success or not
 * @access private
 */
 function unlockAllTables($force = false){
   $result = false;
   if (!empty($this->_lockedTables) && is_array($this->_lockedTables)) {
     while ($table = array_pop($this->_lockedTables)) {
       $this->unlock($table, $force);
     }
     $this->unlockAll($force);
     if (!$this->hasError()) {
       $result = true;
     }
   }
   return $result;
 }

/**
 * Return the locking instance object id
 *
 * @param  string   target table name
 * @return string   the locking object id
 * @access private
 */
 function getLockId($table){
   $result = false;
   while ($this->isLockAll()) {
     $this->sleep();
   }
   if (!$this->hasError()) {
     $tname = $this->encodeKey($table);
     $symbol = $tname . ':';
     if ($fp = $this->fopen('r')) {
       $this->lockDatabase();
       $this->fseekLine($fp, 1);
       $line = $this->fgets($fp);
       fclose($fp);
       $this->unlockDatabase();
       $symbol_pos = strpos($line, $symbol);
       if ($symbol_pos === false
        || strpos($line, '@') === false) {
         if (empty($this->meta[$tname])) {
           $this->pushError('Not exists the table(%s)', $table);
         } else {
           $this->pushError('Cannot check id by getLockId(%s)', $table);
         }
       } else {
         $lock_pos = strlen($symbol) + $symbol_pos;
         $lock_buffer = substr($line, $lock_pos, 10);
         list(, $lock_id) = explode('@', $lock_buffer);
         $result = $lock_id;
       }
     }
   }
   return $result;
 }

/**
 * Return the locking instance object id as locking all tables
 *
 * @param  void
 * @return string   the locking object id
 * @access private
 */
 function getLockIdAll(){
   $result = false;
   while ($this->isLockAll()) {
     $this->sleep();
   }
   $lock_pos = strlen($this->getHeader());
   $id_pos   = $lock_pos + 2;
   $id_len   = strlen($this->id);
   if (!$this->hasError()) {
     if ($fp = $this->fopen('r')) {
       $this->lockDatabase();
       fseek($fp, $id_pos);
       $result = fread($fp, $id_len);
       fclose($fp);
       $this->unlockDatabase();
     }
   }
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Parser
 *
 * This class token parses SQL syntax, expression and PHP code
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Parser extends Posql_File {
/**
 * Checks whether the expression has already parsed or it has not yet
 *
 * @param  mixed   the target expression
 * @return boolean whether it already parsed or it has not yet
 * @access private
 */
 function isParsedExpr($expr){
   $result = false;
   $mark = $this->getParsedMark();
   if ($mark && $expr != null) {
     if (is_array($expr)) {
       $result = reset($expr) === $mark;
     } else {
       $result = substr($expr, 0, strlen($mark)) === $mark;
     }
   }
   return $result;
 }

/**
 * Returns the mark to compare that parsed the expression
 *
 * @param  void
 * @return string   the Mark that use to parsed
 * @access private
 */
 function getParsedMark(){
   static $mark = '#';
   return $mark;
 }

/**
 * Adds the mark to compare for the expression
 *
 * @param  mixed    the expression
 * @return boolean  success or failure
 * @access private
 */
 function addParsedMark(&$expr){
   $result = false;
   $mark = $this->getParsedMark();
   if ($mark && !$this->isParsedExpr($expr)) {
     if (is_array($expr)) {
       array_unshift($expr, $mark);
     } else {
       $expr = $mark . $expr;
     }
     $result = true;
   }
   return $result;
 }

/**
 * Removes the mark to compare for the expression
 *
 * @param  mixed    the expression
 * @return boolean  success or failure
 * @access private
 */
 function removeParsedMark(&$expr){
   $result = false;
   $mark = $this->getParsedMark();
   if ($mark && $this->isParsedExpr($expr)) {
     if (is_array($expr)) {
       array_shift($expr);
     } else {
       $expr = substr($expr, strlen($mark));
     }
     $result = true;
   }
   return $result;
 }

/**
 * Splits the syntax to tokens
 *
 * @param  string  expression/syntax
 * @return array   parsed tokens as an array
 * @access public
 */
 function splitSyntax($syntax){
   static $cache = array(), $size = 0, $opted = false, $regexp = '
   {(?:
       (
        "[^"\\\\]*(?:\\\\.[^"\\\\]*)*"
     |  \'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'
       )
   |   (?:
          (?:
              //
            | [#]
            | --
          )[^\x0A\x0D]*
       )
   |   (?:/[*][\s\S]*?[*]/)
   |   [\x0A\x0D]+
   |   (
         (?: <=>
           | \(\+\)
           | !!=
           | !~[~*]
           | [!=]==
           | <<[<=]
           | >>[>=]
         )
    |    (?: [!=<>.^&|/%*+-]=
           | [&][&]
           | [|][|]
           | <<
           | [=<>-]>
           | ::
           | \+\+
           | --
           | [!~]~
           | ~[~*]
           | <[%?]
           | [%?]>
         )
       )
   | (
       (?: [+-]?
            (?:
               [0](?i:x[0-9a-f]+|[0-7]+)
          |    (?:\d+(?:[.]\d+)?(?i:e[+-]?\d+)?)
          |    [1-9]\d*
            )
        )
     |  (?i:
            [\w\x7F-\xFF]+
        )
     )
   | ([$:@?,;`~\\\\()\{\}\[\]!=<>.^&|/%*+-])
   | \b
   | ([\s])[\s]+
   )}x';
   if (!$opted) {
     $regexp = preg_replace('{\s+|x$}', '', $regexp);
     $opted = true;
   }
   $hash = $this->hashValue($syntax);
   if (isset($cache[$hash])) {
     $result = $cache[$hash];
   } else {
     $result = array_values(
                   array_filter(
                       array_map(
                         'trim',
                         preg_split(
                           $regexp,
                           trim($syntax),
                           -1,
                           PREG_SPLIT_NO_EMPTY |
                           PREG_SPLIT_DELIM_CAPTURE
                         )
                       ),
                       'strlen'
                   )
     );
     if ($size < 0x1000 && strlen(serialize($result)) < 0x100) {
       $cache[$hash] = $result;
       $size++;
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function getSupportedSyntax($flip = false, $method = null, $subset = null){
   static $syntax = array(
     'select' => array(
       'select', 'from', 'where', 'group', 'having', 'order', 'limit'
     ),
     'update' => array(
       'update', 'set', 'where'
     ),
     'delete' => array(
       'delete', 'from', 'where'
     ),
     'insert' => array(
       'insert', 'into', 'values', 'select'
     ),
     'replace' => array(
       'replace', 'into', 'values', 'select'
     ),
     'create' => array(
       'create', 'table', 'database'
     ),
     'drop'   => array(
       'drop', 'table', 'database'
     ),
     'vacuum' => array(
       'vacuum'
     ),
     'describe' => array(
       'describe'
     ),
     'desc' => array(
       'desc'
     ),
     'begin' => array(
       'begin'
     ),
     'start' => array(
       'start'
     ),
     'commit' => array(
       'commit'
     ),
     'end' => array(
       'end'
     ),
     'rollback' => array(
       'rollback'
     )
   );
   $result = array();
   if ($method == null) {
     if ($flip) {
       $result = array_flip(array_keys($syntax));
     } else {
       $result = $syntax;
     }
   } else {
     $method = strtolower($method);
     if (array_key_exists($method, $syntax)) {
       if ($flip) {
         $result = array_flip($syntax[$method]);
       } else {
         $result = $syntax[$method];
       }
     } else {
       $this->pushError('Not supported SQL syntax(%s)', $method);
     }
   }
   return $result;
 }

/**
 * Gets the command name from SQL query
 *
 * @param  mixed   the SQL query, or the parsed tokens
 * @param  boolean whether get the result as is or lowercase
 * @return string  the command name
 * @access private
 */
 function getQueryCommand($query, $as_is = false){
   $result = false;
   if (is_array($query)) {
     $result = reset($query);
   } else if (is_string($query)) {
     $tokens = $this->splitSyntax($query);
     if (!empty($tokens) && is_array($tokens)) {
       $result = reset($tokens);
     }
     unset($tokens);
   }
   if (is_string($result) && !$as_is) {
     $result = strtolower($result);
   }
   return $result;
 }

/**
 * Parses tokens of the SQL for implement each methods
 *
 * @param  string  the SQL query
 * @return array   parsed tokens
 * @access private
 */
 function parseQueryImplements($query){
   $result = false;
   $query = trim($query);
   if ($query != null) {
     $methods = $this->getSupportedSyntax(true);
     $tokens  = $this->splitSyntax($query);
     if (!empty($tokens) && is_array($tokens)) {
       $method = strtolower(reset($tokens));
       if ($method == null
        || !array_key_exists($method, $methods)) {
         $this->pushError('Not supported SQL syntax(%s)', $method);
       } else {
         $this->lastMethod = $method;
         $this->lastQuery  = $query;
         $tokens = $this->removeBacktickToken($tokens);
         //
         //XXX: Should replace the alias in this point?
         // i.e.  $tokens = $this->replaceAliasTokens($tokens);
         //
         switch ($method) {
           case 'select':
               $result = $this->parseSelectQuery($tokens);
               break;
           case 'update':
           case 'delete':
           case 'create':
           case 'drop':
           case 'describe':
           case 'desc':
               $result = $this->parseBasicQuery($tokens, $method);
               break;
           case 'insert':
           case 'replace':
               $result = $this->parseInsertQuery($tokens);
               break;
           case 'vacuum':
           case 'begin':
           case 'start':
           case 'commit':
           case 'end':
           case 'rollback':
               $result = $this->getSupportedSyntax(false, $method);
               break;
           case 'alter':
           default:
               $this->pushError('Not supported SQL syntax(%s)', $method);
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses basic SQL query from the tokens
 *
 * @param  array   parsed tokens
 * @param  string  the method name
 * @return array   the tokens that parsed as clauses
 * @access private
 */
 function parseBasicQuery($tokens, $method){
   $result = false;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s) on %s', $tokens, $method);
   } else {
     $methods = $this->getSupportedSyntax(true, $method);
     if (!is_array($methods)) {
       $this->pushError('illegal syntax, or token(%s)', $methods);
     } else {
       $result = array();
       $clause = null;
       $level  = 0;
       $tokens = array_values($tokens);
       foreach ($tokens as $token) {
         $ltoken = strtolower($token);
         switch ($ltoken) {
           case '(':
               $level++;
               break;
           case ')':
               $level--;
               break;
           default:
               break;
         }
         if ($level === 0 && !empty($methods)
          && array_key_exists($ltoken, $methods)) {
           $clause = $ltoken;
           foreach ($methods as $method => $k) {
             if ($method === $clause) {
               unset($methods[$method]);
               break;
             }
             unset($methods[$method]);
           }
         } else if ($clause != null) {
           $result[$clause][] = $token;
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses SELECT SQL query from the tokens
 *
 * @param  array   parsed tokens
 * @return array   the tokens that parsed as clauses
 * @access private
 */
 function parseSelectQuery($tokens){
   $result = false;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s) on SELECT', $tokens);
   } else {
     $methods = $this->getSupportedSyntax(true, 'select');
     if (!is_array($methods)) {
       $this->pushError('illegal syntax, or token(%s)', $methods);
     } else {
       $result = array();
       $clause = null;
       $level  = 0;
       $tokens = array_values($tokens);
       $length = count($tokens);
       for ($i = 0; $i < $length; $i++) {
         $token = $tokens[$i];
         $ltoken = strtolower($token);
         switch ($ltoken) {
           case '(':
               $level++;
               break;
           case ')':
               $level--;
               break;
           default:
               break;
         }
         if ($level === 0 && !empty($methods)
          && array_key_exists($ltoken, $methods)) {
           $clause = $ltoken;
           foreach ($methods as $method => $k) {
             if ($method === $clause) {
               unset($methods[$method]);
               break;
             }
             unset($methods[$method]);
           }
         } else if ($clause != null) {
           $push = true;
           switch ($ltoken) {
             case 'by':
                 if ($level === 0) {
                   if ($clause === 'group' || $clause === 'order') {
                     $push = false;
                   }
                 }
                 break;
             case 'union':
             case 'intersect':
             case 'except':
                 if ($level === 0) {
                   if (isset($tokens[$i + 1])
                    && 0 === strcasecmp($tokens[$i + 1], 'all')) {
                     $ltoken = sprintf('%s_all', $ltoken);
                     $i++;
                   }
                   $slice = array_slice($tokens, $i + 1);
                   $subset = $this->parseSelectUnion($slice);
                   if (!is_array($subset)) {
                     break 2;
                   }
                   $subset_length = count($this->toOneArray($subset));
                   $subset_length += count($subset);
                   if (array_key_exists('group', $subset)) {
                     $subset_length += 1;
                   }
                   $i += $subset_length;
                   $result[$ltoken][] = $subset;
                   $push = false;
                 }
                 break;
             default:
                 break;
           }
           if ($push) {
             $result[$clause][] = $token;
           }
         }
       }
     }
   }
   //debug($result,'color=purple:***parseSelectQuery***;');
   return $result;
 }

/**
 * Parse compound-operator for SELECT query
 *
 * compound-operator:
 *  - UNION [ALL] : combine two query expressions into a single result set.
 *                  if ALL is specified, duplicate rows returned by
 *                  union expression are retained.
 *  - EXCEPT      : evaluates the output of two query expressions and
 *                  returns the difference between the results.
 *  - INTERSECT   : evaluates the output of two query expressions and
 *                  returns only the rows common to each.
 *
 * @param  array   parsed tokens
 * @return array   the tokens that parsed as clauses
 * @access private
 */
 function parseSelectUnion($tokens){
   $result = false;
   if (!$this->hasError()) {
     if (!is_array($tokens)) {
       $this->pushError('Cannot parse tokens(%s) on SELECT', $tokens);
     } else {
       $methods = $this->getSupportedSyntax(true, 'select');
       if (!is_array($methods)) {
         $this->pushError('illegal syntax, or token(%s)', $methods);
       } else {
         $result = array();
         $clause = null;
         $level  = 0;
         $tokens = array_values($tokens);
         $length = count($tokens);
         for ($i = 0; $i < $length; $i++) {
           $token = $tokens[$i];
           $ltoken = strtolower($token);
           switch ($ltoken) {
             case '(':
                 $level++;
                 break;
             case ')':
                 $level--;
                 break;
             case 'order':
                 if ($level === 0 && isset($tokens[$i + 1])
                  && 0 === strcasecmp($tokens[$i + 1], 'by')) {
                   break 2;
                 }
                 break;
             case 'limit':
             case 'union':
             case 'intersect':
             case 'except':
                 if ($level === 0) {
                   break 2;
                 }
                 break;
             default:
                 break;
           }
           if ($level === 0 && !empty($methods)
            && array_key_exists($ltoken, $methods)) {
             $clause = $ltoken;
             foreach ($methods as $method => $k) {
               if ($method === $clause) {
                 unset($methods[$method]);
                 break;
               }
               unset($methods[$method]);
             }
           } else if ($clause != null) {
             if ($level === 0 && $ltoken === 'by'
              && ($clause === 'group' || $clause === 'order')) {
               continue;
             }
             $result[$clause][] = $token;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses the INSERT query from the tokens
 *
 * @param  array   parsed tokens
 * @return array   the tokens that parsed as clauses
 * @access private
 */
 function parseInsertQuery($tokens){
   $result = false;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s)', $tokens);
   } else {
     $methods = $this->getSupportedSyntax(true, 'insert');
     $result = array();
     $clause = null;
     $level  = 0;
     $select = null;
     $tokens = array_values($tokens);
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       $j = $i + 1;
       $skip = false;
       $token = $this->getValue($tokens, $i);
       $next = $this->getValue($tokens, $j);
       $ltoken = strtolower($token);
       switch ($ltoken) {
         case '(':
             $level++;
             if ($next != null && 0 === strcasecmp($next, 'select')) {
               $level--;
               $select = $level - 1;
               $skip = true;
             }
             break;
         case ')':
             $level--;
             if ($select !== null && $select === $level) {
               $level++;
               $select = null;
               $skip = true;
             }
             break;
         case 'insert':
             if ($level === 0 && 0 === strcasecmp($next, 'or')
              && isset($tokens[$i + 2])
              && 0 === strcasecmp($tokens[$i + 2], 'replace')) {
               $this->lastMethod = strtolower($tokens[$i + 2]);
               $i += 2;
               $skip = true;
             }
             break;
         default:
             break;
       }
       if ($skip) {
         continue;
       }
       if ($level === 0 && !empty($methods)
        && array_key_exists($ltoken, $methods)) {
         $clause = $ltoken;
         foreach ($methods as $method => $k) {
           if ($method === $clause) {
             unset($methods[$method]);
             break;
           }
           unset($methods[$method]);
         }
         if (($clause === 'values' || $clause === 'select')
          && empty($result['cols']) && isset($result['into'])) {
           $into = & $result['into'];
           $pre = array_search('(', $into);
           $suf = array_search(')', $into);
           if (is_int($pre) && is_int($suf) && $pre < $suf) {
             $result['cols'] = array_splice($into, $pre, $pre + $suf);
           }
         }
       } else if ($clause != null) {
         $result[$clause][] = $token;
       }
     }
     if ($level !== 0) {
       $this->pushError('Syntax error, expect () parenthesis');
       $result = false;
     } else {
       $cols_name = 'cols';
       if (isset($into)
        && !array_key_exists($cols_name, $result)) {
         $cols = $this->getMeta(implode('', $into));
         if (is_array($cols)) {
           $result[$cols_name] = array();
           $columns = & $result[$cols_name];
           foreach (array_keys($cols) as $col) {
             $columns[] = $col;
             $columns[] = ',';
           }
           array_pop($columns);
         }
       }
     }
     unset($into, $columns);
   }
   return $result;
 }

/**
 * Parses the multielement queries to the individual query
 *
 * @param  string  the multielement queies
 * @return array   results of to disjointed query
 * @access private
 */
 function parseMultiQuery($query){
   $result = array();
   if ($query != null && is_string($query)) {
     $query = $this->splitSyntax($query);
     $query = array_values($query);
     $length = count($query);
     $stack  = array();
     $prev_token = null;
     $prev_index = 0;
     while (--$length >= 0) {
       $token = array_shift($query);
       if ($this->isStringToken($token)) {
         $token = $this->toStringToken($token);
         if ($prev_index === 0) {
           $prev_token = $token;
         } else if ($prev_index - 1 === $length) {
           $token = $this->concatStringTokens($prev_token, $token);
           array_pop($stack);
         }
         $prev_token = $token;
         $prev_index = $length;
       }
       $stack[] = $token;
       if ($token === ';'
        && (!$length || $this->isWord(reset($query)))) {
         $result[] = $this->joinWords($stack);
         $stack = array();
       }
     }
   }
   return $result;
 }

/**
 * Parses the tokens for naming the table, or database
 *
 * @param  array    parsed tokens
 * @param  boolean  whether it exists is checked
 * @return mixed    results of parsed tokens
 * @access private
 */
 function assignNames($tokens, $check_exists = false){
   $result = false;
   $this->_ifExistsClause = null;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s)', $tokens);
   } else {
     $name = '';
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       $j = $i + 1;
       $k = $j + 1;
       switch (strtolower($tokens[$i])) {
         case '`':
         //case ';':
         //case ':':
         //case '(':
         //case ')':
             break;
         case 'if':
             if ($check_exists) {
               if (isset($tokens[$j])) {
                 if (strtolower($tokens[$j]) === 'exists') {
                   $this->_ifExistsClause = 'if_exists';
                   $i = $j;
                 } else if (isset($tokens[$k])
                         && strtolower($tokens[$j]) === 'not'
                         && strtolower($tokens[$k]) === 'exists') {
                   $this->_ifExistsClause = 'if_not_exists';
                   $i = $k;
                 }
               }
             }
             break;
         default:
             $name .= $tokens[$i];
             break;
       }
     }
     if ($this->isStringToken($name)) {
       $name = substr($name, 1, -1);
     }
     $result = $name;
   }
   return $result;
 }

/**
 * Parses the tokens for CREATE DATABASE
 *
 * @param  array   parsed tokens
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseCreateDatabaseQuery($tokens){
   $result = $this->assignNames($tokens, true);
   return $result;
 }

/**
 * Parses the tokens for DROP TABLE/DATABASE
 *
 * @param  array   parsed tokens
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseDropQuery($tokens){
   $result = $this->assignNames($tokens, true);
   return $result;
 }

/**
 * Parses the tokens for CREATE TABLE
 *
 * @param  array    parsed tokens
 * @param  boolean  whether return the result as simple array
 * @return mixed    results of parsed tokens
 * @access private
 */
 function parseCreateTableQuery($tokens, $simple = false){
   $result = false;
   if (!is_array($tokens) || empty($this->lastQuery)) {
     $this->pushError('Cannot parse tokens on CREATE TABLE');
   } else {
     $sql = $this->lastQuery;
     $open  = '(';
     $close = ')';
     $bpos = strpos($sql, $open);
     $epos = strrpos($sql, $close);
     if ($bpos === false || $epos === false || $bpos > $epos) {
       $this->pushError('illegal query on CREATE(%s)', $sql);
     } else {
       $tokens = $this->removeBacktickToken($tokens);
       $table = array();
       while (!empty($tokens) && reset($tokens) !== $open) {
         $table[] = array_shift($tokens);
       }
       array_shift($tokens);
       $table = $this->assignNames($table, true);
       while (!empty($tokens) && end($tokens) !== $close) {
         array_pop($tokens);
       }
       array_pop($tokens);
       $fields = $this->parseColumnDefinition($tokens);
       if (empty($fields) || $this->hasError()) {
         $result = false;
       } else {
         $primary = null;
         $this->_onCreate = true;
         $this->_execExpr = true;
         $simple_fields = array();
         foreach ($fields as $name => $field) {
           if (empty($field) || !is_array($field)
            || $name == null || !is_string($name)) {
             $this->pushError('Failed to parse CREATE_DEFINITION');
             break;
           } else {
             foreach ($field as $desc => $value) {
               switch (strtolower($desc)) {
                 case 'key':
                     if ($primary === null
                      && strcasecmp($value, 'primary') === 0) {
                       $primary = $name;
                     }
                     break;
                 case 'default':
                     $value = $this->validExpr($value);
                     if (!$this->hasError()) {
                       $value = $this->safeExpr($value);
                       $fields[$name][$desc] = $value;
                       $simple_fields[$name] = $value;
                     }
                     break;
                 case 'type':
                 case 'null':
                 default:
                     break;
               }
               if ($this->hasError()) {
                 $result = false;
                 break;
               }
             }
           }
         }
         if (!$this->hasError()) {
           $result = array(
             'table'   => $table,
             'fields'  => $fields,
             'primary' => $primary
           );
           if ($simple) {
             $result['fields'] = $simple_fields;
           }
         }
         $this->_execExpr = null;
         $this->_onCreate = null;
       }
     }
   }
   return $result;
 }

/**
 * Parses the tokens that part of column_definition as simple
 *
 * @param  mixed   parsed tokens, or CREATE TABLE SQL as string
 * @return mixed   create_definition as associate array
 * @access private
 */
 function parseCreateTableQuerySimple($query){
   $result = false;
   if (is_array($query)) {
     $query = $this->joinWords($query);
   }
   $open  = '(';
   $close = ')';
   $bpos = strpos($query, $open);
   $epos = strrpos($query, $close);
   if ($bpos === false || $epos === false || $bpos > $epos) {
     $this->pushError('Connot parse tokens (CREATE_DEFINITION)');
   } else {
     $tokens = $this->splitSyntax($query);
     $tokens = $this->removeBacktickToken($tokens);
     while (!empty($tokens) && reset($tokens) !== $open) {
       array_shift($tokens);
     }
     array_shift($tokens);
     while (!empty($tokens) && end($tokens) !== $close) {
       array_pop($tokens);
     }
     array_pop($tokens);
     $result = $this->parseColumnDefinition($tokens);
     if (empty($result) || $this->hasError()) {
       $result = false;
     }
   }
   return $result;
 }

/**
 * Parses the tokens that part of column_definition
 * The result will be classified as follows.
 *
 * - type
 *           Data type of column  i.e. INTEGER, TEXT or VARCHAR(255) etc.
 * - null
 *           Boolean flag that indicates whether this field is constrained
 *            to be set to NULL.
 * - key
 *           Field of key types, others are NULL  e.g. "primary"
 * - default
 *           Text value to be used as default for this field.
 *
 * @param  array   target definition
 * @return array   results of parsed tokens, or FALSE on error
 * @access private
 */
 function parseColumnDefinition($definition){
   static $reserve_types = array(
     'default',
     'primary',
     'key',
     'check',
     'unique',
     'not',
     'null',
     'auto_increment',
     'collate',
     'foreign'
   );
   $result = false;
   if (!is_array($definition)) {
     $definition = $this->splitSyntax($definition);
   }
   $tokens = $this->removeBacktickToken($definition);
   unset($definition);
   $open     = '(';
   $close    = ')';
   $comma    = ',';
   $level    = 0;
   $names    = array();
   $types    = array();
   $defaults = array();
   $nulls    = array();
   $primary  = null;
   if (end($tokens) !== $comma) {
     array_push($tokens, $comma);
   }
   if (reset($tokens) !== $comma) {
     array_unshift($tokens, $comma);
   }
   $length = count($tokens);
   for ($i = 0; $i < $length; $i++) {
     $j = $i + 1;
     $k = $j + 1;
     switch (strtolower($tokens[$i])) {
       case '(':
           $level++;
           break;
       case ')':
           $level--;
           break;
       case ',':
           if ($level === 0) {
             if (isset($tokens[$j])
              && in_array(strtolower($tokens[$j]), $reserve_types)) {
               break;
             }
             if (!empty($names)) {
               if (count($names) > count($defaults)) {
                 $defaults[] = 'null';
               }
               if (count($names) > count($nulls)) {
                 $nulls[] = true;
               }
             }
             while (++$i < $length) {
               if ($this->isEnableName($tokens[$i])) {
                 $names[] = $tokens[$i];
                 $n = $i + 1;
                 if (isset($tokens[$n])) {
                   $type = strtolower($tokens[$n]);
                   if ($this->isEnableName($type)
                    && !in_array($type, $reserve_types)) {
                     $type_length = '';
                     if (isset($tokens[++$n]) && $tokens[$n] === $open) {
                       while (isset($tokens[++$n])) {
                         if ($tokens[$n] === $close) {
                           break;
                         }
                         $type_length .= $tokens[$n];
                       }
                       if ($type_length != null) {
                         $type .= sprintf('(%s)', $type_length);
                       }
                       $i = $n;
                     }
                     $types[] = $type;
                   }
                 }
                 if (count($names) > count($types)) {
                   $types[] = $this->defaultDataType;
                 }
                 break;
               }
             }
           }
           break;
       case 'default':
           if ($level === 0) {
             if (isset($tokens[$j])) {
               if (strtolower($tokens[$j]) !== 'character') {
                 $defaults[] = $tokens[$j];
               }
               $i = $j;
             }
           }
           break;
       case 'null':
           if ($level === 0) {
             $n = $i - 1;
             if (isset($tokens[$n])
              && strtolower($tokens[$n]) === 'not') {
               $nulls[] = false;
             } else {
               $nulls[] = true;
             }
           }
           break;
       case 'primary':
           if ($level === 0 && isset($tokens[$j])
            && strtolower($tokens[$j]) === 'key') {
             if (empty($names)) {
               $this->pushError('Failed to parse tokens, expect PRIMARY');
               break 2;
             }
             if (isset($tokens[$k])
              && !empty($names) && $tokens[$k] === $open) {
               $n = $k;
               $primary_tokens = array();
               while (isset($tokens[++$n])) {
                 switch ($tokens[$n]) {
                   case '(':
                   case ')':
                       break 2;
                   case ',':
                       $this->pushError('Not supported'
                                     .  ' the multiple PRIMARY keys');
                       break 4;
                   default:
                       $primary_tokens[] = $tokens[$n];
                       break;
                 }
               }
               $primary_token = array_shift($primary_tokens);
               if ($this->isStringToken($primary_token)) {
                 $primary_token = substr($primary_token, 1, -1);
               }
               if (!empty($primary_tokens) || $primary_token == null
                || !in_array($primary_token, $names)) {
                 $this->pushError('Failed to parse tokens, expect PRIMARY');
                 break 2;
               }
               $primary = $primary_token;
               $i = $n;
             } else {
               if (!empty($names)
                && count($names) === 1 && isset($names[0])) {
                 $primary = $names[0];
                 $i = $j;
               }
             }
           }
           break;
       case 'character':
           if ($level === 0) {
             if (isset($tokens[$j], $tokens[$k])
              && strtolower($tokens[$j]) === 'set'
              && ($this->isStringToken($tokens[$k])
              ||  $this->isEnableName($tokens[$k]))) {
               $i = $k;
             }
           }
           break;
       // ignore for futures
       case 'check':
       case 'unique':
       case 'auto_increment':
       case 'collate':
       case 'foreign':
       default:
           break;
     }
   }
   if (!$this->hasError()) {
     if ($level !== 0) {
       $this->pushError('Failed to parse tokens, expect %s bracket', $close);
     } else {
       $names    = array_values($names);
       $types    = array_values($types);
       $defaults = array_values($defaults);
       $nulls    = array_values($nulls);
       $length   = count($names);
       if ($length !== count($types)
        || $length !== count($defaults)
        || $length !== count($nulls)) {
         $this->pushError('Failed to parse tokens, expect CREATE_DEFINITION');
       } else {
         $fields = array();
         for ($i = 0; $i < $length; $i++) {
           if (!array_key_exists($i, $names)
            || !array_key_exists($i, $types)
            || !array_key_exists($i, $defaults)
            || !array_key_exists($i, $nulls)) {
             $this->pushError('Unable to parse tokens (CREATE_DEFINITION)');
             break;
           }
           $key = null;
           if ($primary === $names[$i]) {
             $key = 'primary';
           }
           $fields[$names[$i]] = array(
             'type'    => $types[$i],
             'null'    => $nulls[$i],
             'key'     => $key,
             'default' => $defaults[$i]
           );
         }
         if (!$this->hasError()) {
           $result = $fields;
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses the tokens for "INSERT (...) VALUES (...)"
 *
 * @param  array   parsed tokens
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseInsertValues($values){
   $result = false;
   if (!is_array($values)
    || !isset($values['cols']) || !isset($values['values'])) {
     $this->pushError('Cannot parse tokens on INSERT');
   } else {
     $rows = array();
     $cols = $values['cols'];
     $vals = $values['values'];
     while (end($cols) === ';') {
       array_pop($cols);
     }
     if ( end($cols)   === ')'
      &&  reset($cols) === '(') {
       array_pop($cols);
       array_shift($cols);
     }
     while (end($vals) === ';') {
       array_pop($vals);
     }
     if ( end($vals)   === ')'
      &&  reset($vals) === '(') {
       array_pop($vals);
       array_shift($vals);
     }
     if ($cols != null && $vals != null) {
       $tmp = array();
       $level = 0;
       $stack = '';
       foreach ($vals as $val) {
         switch ($val) {
           case '`':
               break;
           case '(':
               $level++;
               $stack .= $val;
               break;
           case ')':
               $level--;
               $stack .= $val;
               break;
           case ',':
               if ($level) {
                 $stack .= $val;
               } else {
                 $tmp[] = $stack;
                 $stack = '';
               }
               break;
           default:
               $stack .= $val;
               break;
         }
       }
       if ($stack != null) {
         $tmp[] = $stack;
       }
       $vals = $tmp;
       $tmp = array();
       $stack = '';
       foreach ($cols as $col) {
         switch ($col) {
           case '`':
               break;
           case ',':
               $tmp[] = $stack;
               $stack = '';
               break;
           default:
               $stack .= $col;
               break;
         }
       }
       if ($stack != null) {
         $tmp[] = $stack;
       }
       $cols = $tmp;
       $vlen = count($vals);
       if ($vlen < count($cols)) {
         while ($vlen < count($cols)) {
           array_pop($cols);
         }
       }
       if ($cols == null || $vals == null
        || count($cols) !== count($vals)) {
         $this->pushError('illegal format of columns, or values');
       } else {
         $cols = array_values($cols);
         $vals = array_values($vals);
         $vlen = count($vals);
         $rows = array();
         for ($i = 0; $i < $vlen; $i++) {
           $rows[$cols[$i]] = $vals[$i];
         }
         $result = $rows;
       }
     }
   }
   return $result;
 }

/**
 * Parses the tokens for UPDATE
 *
 * @param  array   parsed tokens
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseUpdateSet($tokens){
   $result = false;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens in UPDATE SET(%s)', $tokens);
   } else {
     $row   = array();
     $stack = array();
     $level = 0;
     $col   = '';
     foreach ($tokens as $token) {
       switch ($token) {
         case '`':
             break;
         case '=':
             if ($level === 0) {
               $col = $this->joinWords($stack);
               $row[$col] = '';
               $stack = array();
             } else {
               $stack[] = $token;
             }
             break;
         case '(':
             $level++;
             $stack[] = $token;
             break;
         case ')':
             $level--;
             $stack[] = $token;
             break;
         case ',':
             if ($level === 0) {
               $row[$col] = $stack;
               $stack = array();
             } else {
               $stack[] = $token;
             }
             break;
         default:
             $stack[] = $token;
             break;
       }
     }
     if (isset($row[$col])) {
       $row[$col] .= $this->joinWords($stack);
     }
     $result = $row;
   }
   return $result;
 }

/**
 * Parses the arguments for SELECT method
 *
 * @param  mixed    target arguments
 * @param  boolean  whether return the result as associate array
 * @return mixed    parsed arguments, or empty array on error
 * @access private
 */
 function parseSelectArguments($args, $as_assoc = false){
   static $indexes = array(
     'from'   => 0,
     'select' => 1, 'where'  => 2,
     'group'  => 3, 'having' => 4,
     'order'  => 5, 'limit'  => 6
   );
   $result = array();
   if (!is_array($args)) {
     $args = (string)$args;
     $result = array($args);
   } else {
     if (!$this->isAssoc($args)) {
       $result = $args;
     } else {
       $marks = array('_', ' ');
       foreach ($args as $clause => $val) {
         if (!is_string($clause)) {
           $result[] = $clause;
         } else {
           $clause = strtolower($clause);
           if (substr($clause, -1) === 's') {
             $clause = rtrim($clause, 's');
           }
           foreach ($marks as $mark) {
             if (strpos($clause, $mark) !== false) {
               $split = explode($mark, $clause);
               $clause = implode('', $split);
             }
           }
           switch ($clause) {
             case 'from':
             case 'table':
                 $result[$indexes['from']] = $val;
                 break;
             case 'select':
             case 'col':
             case 'column':
             case 'field':
                 $result[$indexes['select']] = $val;
                 break;
             case 'where':
             case 'expr':
             case 'expression':
             case 'cond':
             case 'condition':
                 $result[$indexes['where']] = $val;
                 break;
             case 'group':
             case 'groupby':
                 $result[$indexes['group']] = $val;
                 break;
             case 'having':
                 $result[$indexes['having']] = $val;
                 break;
             case 'order':
             case 'orderby':
             case 'sort':
             case 'sortby':
                 $result[$indexes['order']] = $val;
                 break;
             case 'limit':
                 $limit = $val;
                 $index = & $indexes['limit'];
                 if (isset($offset)) {
                   $result[$index] = sprintf('%.0f,%.0f', $offset, $limit);
                 } else {
                   if (is_numeric($limit)) {
                     $limit = sprintf('%.0f', $limit);
                   }
                   $result[$index] = $limit;
                 }
                 unset($index);
                 break;
             case 'offset':
                 $offset = $val;
                 $index = & $indexes['limit'];
                 if (array_key_exists($index, $result)) {
                   if (!isset($limit)) {
                     $limit = $result[$index];
                   }
                   $result[$index] = sprintf('%.0f,%.0f', $offset, $limit);
                 } else {
                   $result[$index] = sprintf('%.0f,ALL', $offset);
                 }
                 unset($index);
                 break;
             default:
                 $this->pushError('Undefined name(%s) on argument', $clause);
                 $result = array();
                 break 2;
           }
         }
       }
     }
   }
   if (!is_array($result)
    || count($result) > count($indexes)) {
     $error = (string)$result;
     if (is_array($result)) {
       $error .= sprintf('#%d', count($result));
     }
     $this->pushError('illegal arguments(%s) on SELECT', $error);
     $result = array();
   } else {
     foreach ($indexes as $clause => $i) {
       if (!array_key_exists($i, $result)) {
         $result[$i] = $this->getSelectDefaultArguments($clause);
       }
     }
     ksort($result);
     if ($as_assoc) {
       $assoc = array();
       foreach ($indexes as $clause => $i) {
         $assoc[$clause] = $result[$i];
       }
       $result = $assoc;
     }
   }
   return $result;
 }

/**
 * Parses the sub-query
 *
 * @param  mixed   the SQL query, or the parsed tokens
 * @param  boolean whether include parentheses
 * @param  boolean whether include alias length
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseSubQuery($tokens, $include_parentheses = false,
                                 $include_alias_length = false){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (is_array($tokens)) {
     $level     = 0;
     $sub_level = null;
     $offset    = null;
     $operator  = null;
     $alias     = null;
     $stack     = array();
     $tokens    = $this->cleanArray($tokens);
     $count     = count($tokens);
     for ($i = 0; $i < $count; $i++) {
       $token = $tokens[$i];
       $next  = $this->getValue($tokens, $i + 1);
       $prev  = $this->getValue($tokens, $i - 1);
       switch (strtolower($token)) {
         case '`':
             break;
         case '(':
             $level++;
             if ($sub_level !== null) {
               $stack[] = $token;
             }
             if ($next != null && 0 === strcasecmp($next, 'select')) {
               $sub_level = $level - 1;
               $offset = $i + 1;
               if ($prev != null) {
                 $operator = strtolower($prev);
               }
               if (!empty($stack)) {
                 $stack = array();
               }
               if ($include_parentheses) {
                 $offset--;
                 $stack[] = $token;
               }
             }
             break;
         case ')':
             $level--;
             if ($sub_level === $level) {
               $sub_level = null;
               if ($include_parentheses) {
                 $stack[] = $token;
               }
             }
             if ($sub_level !== null) {
               $stack[] = $token;
             }
             break;
         case 'as':
             if ($level === 0 && $next != null) {
               $alias = $next;
               $i++;
               break;
             }
             // FALLTHROUGH
         default:
             if ($sub_level !== null) {
               $stack[] = $token;
             }
             break;
       }
     }
     if (!empty($stack)) {
       $length = count($stack);
       if ($include_alias_length && $alias != null) {
         $length++;
       }
       $result = array(
         'tokens'   => $stack,
         'offset'   => $offset,
         'length'   => $length,
         'operator' => $operator,
         'alias'    => $alias
       );
     }
   }
   //debug($result,"color=#6600ff:**sub-query**;");
   return $result;
 }

/**
 * Get the table name from parsed tokens
 *
 * @param  array    parsed tokens
 * @param  string   type of statement
 * @return string   table name
 * @access private
 */
 function getTableNameFromTokens($tokens, $type){
   $result = null;
   if (is_array($tokens)) {
     switch ($type) {
       case 'select':
           if (array_key_exists('from', $tokens)) {
             $result = $tokens['from'];
             if (is_array($result)) {
               $result = implode('', $result);
             }
           }
           break;
       default:
           $result = null;
           break;
     }
   }
   return $result;
 }

/**
 * Check whether the query is manipulation or definition query
 *
 * @param  mixed   the query, or the parsed tokens
 * @return boolean whether the query is manipulation query
 * @access public
 */
 function isManip($query){
   static $spaces = array(' ', "\t", "\r", "\n");
   $result = false;
   if (is_string($query)) {
     $query = $this->splitSyntax($query);
   }
   if (is_array($query)) {
     $query = reset($query);
   }
   if (is_string($query) && $query != null) {
     $query = substr(ltrim($query), 0, 10);
     foreach ($spaces as $space) {
       if (strpos($query, $space) !== false) {
         $split = explode($space, $query);
         $query = array_shift($split);
       }
     }
     switch (strtolower($query)) {
       case 'alter':
       case 'begin':
       case 'commit':
       case 'copy':
       case 'create':
       case 'delete':
       case 'drop':
       case 'end':
       case 'grant':
       case 'insert':
       case 'load':
       case 'lock':
       case 'replace':
       case 'revoke':
       case 'rollback':
       case 'start':
       case 'unlock':
       case 'update':
       case 'vacuum':
           $result = true;
           break;
       default:
           $result = false;
           break;
     }
   }
   return $result;
 }

/**
 * Check whether queries are multiple or one query
 *
 * @param  mixed   the target query
 * @return boolean whether the argument was multiple queries
 * @access public
 */
 function isMultiQuery($query){
   $result = false;
   if (is_string($query)) {
     $query = $this->splitSyntax($query);
   }
   if (!empty($query) && is_array($query)) {
     $tokens = $this->cleanArray($query);
     unset($query);
     $length = count($tokens);
     while (--$length >= 0) {
       $token = array_shift($tokens);
       if ($token === ';') {
         $next = array_shift($tokens);
         if ($next != null && $this->isWord($next)) {
           $result = true;
           break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the SELECT Query has multiples or single
 *
 * @param  mixed    the value of FROM clause
 * @return boolean  it has multiples or not
 * @access private
 */
 function isMultipleSelect($from){
   $result = false;
   if ($from == null) {
     $this->pushError('The query of FROM clause is empty');
   } else {
     if (is_string($from)) {
       $from = $this->splitSyntax($from);
     }
     if (is_array($from)) {
       $level = 0;
       $from = $this->cleanArray($from);
       $length = count($from);
       for ($i = 0; $i < $length; $i++) {
         $token = $from[$i];
         switch (strtolower($token)) {
           case '(':
               $level++;
               break;
           case ')':
               $level--;
               break;
           case ',':
           case 'join':
               if ($level === 0) {
                 $j = $i + 1;
                 if (isset($from[$j]) && $from[$j] != null) {
                   $result = true;
                   break 2;
                 }
               }
               break;
           default:
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the FROM clause has sub-query
 *
 * @param  mixed    the value of FROM clause
 * @return boolean  whether the FROM clause has sub-query or not
 * @access private
 */
 function isSubSelectFrom($from){
   $result = false;
   if ($from == null) {
     $this->pushError('The query of FROM clause is empty');
   } else {
     if (is_string($from)) {
       $from = $this->splitSyntax($from);
     }
     if (is_array($from)) {
       $level = 0;
       $from = $this->cleanArray($from);
       $count = count($from);
       for ($i = 0; $i < $count; $i++) {
         $token = $from[$i];
         $next = $this->getValue($from, $i + 1);
         switch ($token) {
           case '(':
               $level++;
               if ($next != null && 0 === strcasecmp($next, 'select')
                && isset($from[$i + 2]) && $from[$i + 2] != null) {
                 $result = true;
               }
               break;
           case ')':
               $level--;
               break;
           default:
               break;
         }
       }
       if ($level !== 0) {
         $this->pushError('Syntax error on FROM clause');
         $result = false;
       }
     }
   }
   return $result;
 }

/**
 * Check whether the statement is "SELECT COUNT(*)" by the arguments.
 * Only asterisk(*) is accepted to the expression.
 *
 * @param  array    the parsed arguments
 * @param  boolean  whether the query has multi-tables
 * @return boolean  whether the statement is simple COUNT
 * @access private
 */
 function isSimpleCount($args, $multi_select = false){
   $result = false;
   if (!is_array($args)) {
     $this->pushError('Failed to parse arguments on SELECT(%s)', $args);
   } else {
     if ($this->isAssoc($args)) {
       $defaults = $this->getSelectDefaultArguments();
       $result = false;
       foreach ($args as $clause => $expr) {
         $clause = strtolower($clause);
         switch ($clause) {
           case 'from':
               if (!$multi_select && !is_string($expr)) {
                 $result = false;
                 break 2;
               }
               break;
           case 'select':
               if (!is_array($expr)) {
                 $expr = $this->parseColumns($expr);
               }
               if (!$this->hasError()) {
                 if (is_array($expr)) {
                   $keys = array('cols', 'func');
                   foreach ($keys as $key) {
                     if (!array_key_exists($key, $expr)
                      || count($expr[$key]) !== 1) {
                       $result = false;
                       break 3;
                     }
                   }
                   $func_key = end($keys);
                   if (reset($expr[$func_key]) === '*'
                    && strtolower(key($expr[$func_key])) === 'count') {
                     $result = true;
                   }
                 }
               }
               break;
           case 'where':
               break;
           default:
               if (!array_key_exists($clause, $defaults)
                || $defaults[$clause] !== $expr) {
                 $result = false;
                 break 2;
               }
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Check whether or not the statement is simple "LIMIT 1" by the arguments.
 *
 * @param  array    the parsed arguments
 * @param  boolean  whether the query has multi-tables
 * @return boolean  whether the statement is simple "LIMIT 1"
 * @access private
 */
 function isSimpleLimitOnce($args, $multi_select = false){
   $result = false;
   if (!is_array($args)) {
     $this->pushError('Failed to parse arguments on SELECT(%s)', $args);
   } else {
     if ($this->isAssoc($args)) {
       $defaults = $this->getSelectDefaultArguments();
       $result = false;
       foreach ($args as $clause => $expr) {
         $clause = strtolower($clause);
         switch ($clause) {
           case 'from':
               if (!$multi_select && !is_string($expr)) {
                 $result = false;
                 break 2;
               }
               break;
           case 'select':
               break;
           case 'where':
               break;
           case 'limit':
               $limit = $expr;
               if ($limit != null) {
                 $parsed = $this->parseLimitTokens($limit);
                 if (!$this->hasError()) {
                   if (is_array($parsed)
                    && array_key_exists('length', $parsed)
                    && array_key_exists('offset', $parsed)) {
                     if ($parsed['offset'] == null
                      && $parsed['length'] == 1) {
                       $result = true;
                     }
                   }
                 }
               }
               break;
           default:
               if (!array_key_exists($clause, $defaults)
                || $defaults[$clause] !== $expr) {
                 $result = false;
                 break 2;
               }
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Check whether or not the statement is simple primary-key specify
 * expression by the arguments.
 *
 * @param  array    the parsed arguments
 * @param  boolean  whether the query has multi-tables
 * @return boolean  whether the statement is simple primary-key specify
 * @access private
 */
 function isSimplePrimaryKeySpecify($args, $multi_select = false){
   $result = false;
   if (!is_array($args)) {
     $this->pushError('Failed to parse arguments on SELECT(%s)', $args);
   } else {
     if ($this->isAssoc($args)) {
       $defaults = $this->getSelectDefaultArguments();
       $result = false;
       foreach ($args as $clause => $expr) {
         $clause = strtolower($clause);
         switch ($clause) {
           case 'from':
               if (!$multi_select && !is_string($expr)) {
                 $result = false;
                 break 2;
               }
               break;
           case 'select':
               break;
           case 'where':
               if ($this->isSimplePrimaryKeyCompareExpression($expr)) {
                 $result = true;
               }
               break;
           case 'limit':
               break;
           case 'order':
               break;
           default:
               if (!array_key_exists($clause, $defaults)
                || $defaults[$clause] !== $expr) {
                 $result = false;
                 break 2;
               }
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether or not the expression is simple primary-key comparing
 *
 * @param  mixed    the expression as WHERE clause
 * @return boolean  whether the expression is simple primary-key comparing
 * @access private
 */
 function isSimplePrimaryKeyCompareExpression($expr){
   $result = false;
   if ($expr != null
    && isset($this->_primaryKey) && $this->_primaryKey != null) {
     if (is_string($expr)) {
       $expr = $this->splitSyntax($expr);
     }
     $this->removeValidMarks($expr);
     if (is_array($expr)) {
       $expr   = $this->cleanArray($expr);
       $tokens = array();
       foreach ($expr as $token) {
         switch ($token) {
           case '#':
           case $this->NL:
           case '$':
               break;
           default:
               $tokens[] = $token;
               break;
         }
       }
       $level  = 0;
       $lefts  = array();
       $rights = array();
       $stack  = array();
       foreach ($tokens as $i => $token) {
         $prev = $this->getValue($tokens, $i - 1);
         $next = $this->getValue($tokens, $i + 1);
         switch (strtolower($token)) {
           case '(':
               $level++;
               break;
           case ')':
               $level--;
               break;
           case '=':
           case '==':
           case '===':
               if (count($stack) === 1) {
                 if ($prev != null && $next != null) {
                   $lefts[]  = $prev;
                   $rights[] = $next;
                 }
               }
               break;
           default:
               $stack[] = $token;
               break;
         }
       }
       if (count($stack) === 2
        && count($lefts) === 1 && count($rights) === 1) {
         $left = reset($lefts);
         $right = reset($rights);
         if ($right === $this->_primaryKey) {
           $tmp = $left;
           $left = $right;
           $right = $tmp;
         }
         if ($left === $this->_primaryKey) {
           if ($this->isStringToken($right)) {
             $right = substr($right, 1, -1);
           }
           if (is_numeric($right)) {
             $result = true;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the SELECT query has DISTINCT clause
 *
 * @param  mixed    the value of SELECT clause
 * @return boolean  it has DISTINCT or not
 * @access private
 */
 function isDistinct($columns){
   $result = false;
   if (!$this->hasError()) {
     if ($columns == null) {
       $this->pushError('Empty SELECT clause (select-list)');
     } else {
       if (is_string($columns)) {
         $columns = $this->splitSyntax($columns);
       }
       if (is_array($columns)) {
         $reset = reset($columns);
         if (0 === strcasecmp($reset, 'distinct')) {
           $result = true;
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the SELECT query has ALL clause
 *
 * @param  mixed    the value of SELECT clause
 * @return boolean  it has ALL clause or not
 * @access private
 */
 function isSelectListAllClause($columns){
   $result = false;
   if (!$this->hasError()) {
     if ($columns == null) {
       $this->pushError('Empty SELECT clause (select-list)');
     } else {
       if (is_string($columns)) {
         $columns = $this->splitSyntax($columns);
       }
       if (is_array($columns)) {
         $reset = reset($columns);
         $next = next($columns);
         if (0 === strcasecmp($reset, 'all')) {
           if ($next != null) {
             if ($next !== ',') {
               $result = true;
             }
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether or not the statement has the function
 *
 * @param  mixed    the statement
 * @return boolean  whether or not is the function in statement
 * @access public
 */
 function isFunctionInStatement($expr){
   $result = false;
   if ($expr == null) {
     $result = false;
   } else {
     if (is_string($expr)) {
       $expr = $this->splitSyntax($expr);
     }
     if (is_array($expr)) {
       $level = 0;
       $func = null;
       $tokens = $this->cleanArray($expr);
       foreach ($tokens as $i => $token) {
         $prev = $this->getValue($tokens, $i - 1);
         switch ($token) {
           case '(':
               $level++;
               if ($prev != null) {
                 $func = $prev;
               }
               break;
           case ')':
               if ($level > 0 && $this->isEnableName($func)) {
                 $is_func = true;
                 switch (strtolower($func)) {
                   case 'and':
                   case 'or':
                   case 'xor':
                   case 'is':
                   case 'not':
                   case 'in':
                   case 'where':
                   case 'having':
                   case 'on':
                   case 'avg':
                   case 'count':
                   case 'max':
                   case 'min':
                   case 'sum':
                       $is_func = false;
                       break;
                   default:
                       $is_func = true;
                       break;
                 }
                 if ($is_func) {
                   $result = true;
                   break 2;
                 }
               }
               $level--;
               break;
           default:
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Check whether compound-operator token is in statement
 *
 * compound-operator:
 *  - UNION [ALL] : combine two query expressions into a single result set.
 *                  if ALL is specified, duplicate rows returned by
 *                  union expression are retained.
 *  - EXCEPT      : evaluates the output of two query expressions and
 *                  returns the difference between the results.
 *  - INTERSECT   : evaluates the output of two query expressions and
 *                  returns only the rows common to each.
 *
 * @param  mixed    SELECT tokens, or statement
 * @param  boolean  whether return as compound type or boolean
 * @return boolean  tokens has compound-operator or not
 * @access private
 */
 function hasCompoundOperator($tokens, $return_string = false){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (is_array($tokens)) {
     $level = 0;
     $tokens = array_values($tokens);
     foreach ($tokens as $token) {
       $ltoken = strtolower($token);
       switch ($ltoken) {
         case '(':
             $level++;
             break;
         case ')':
             $level--;
             break;
         case 'union':
         case 'except':
         case 'intersect':
             if ($level === 0) {
               $result = $ltoken;
               break 2;
             }
             break;
         default:
             break;
       }
     }
   }
   if (!$return_string) {
     $result = (bool)$result;
   }
   return $result;
 }

/**
 * parse and split to the tokens of columns
 *
 * @param  array  columns of target
 * @return array  results of columns, or false on error
 * @access private
 */
 function splitColumns($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if ($tokens == null || !is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s) of COLUMNS on SELECT',
                      $tokens);
   } else {
     $colms = array();
     $stack = array();
     $level = 0;
     $tokens[] = ',';
     $tokens = array_values($tokens);
     foreach ($tokens as $token) {
       switch ($token) {
         case '`':
         case ';':
             break;
         case '(':
             $level++;
             $stack[] = $token;
             break;
         case ')':
             $level--;
             $stack[] = $token;
             break;
         case ',':
             if ($level) {
               $stack[] = $token;
             } else {
               $colms[] = $stack;
               $stack = array();
             }
             break;
         default:
             $stack[] = $token;
             break;
       }
     }
     $result = $colms;
   }
   return $result;
 }

/**
 * Parses the columns name for SELECT method
 *
 * @param  mixed   columns of target
 * @param  string  if given as string, will returns only it
 * @return array   results of columns, or false on error
 * @access private
 */
 function parseColumns($tokens, $colname = null){
   $result = false;
   $tokens = $this->splitColumns($tokens);
   if (!empty($tokens) && is_array($tokens)) {
     $alias = array();
     $funcs = array();
     $colms = array();
     $stack = array();
     $exprs = array();
     $level = 0;
     $func_name  = null;
     $alias_name = null;
     $aggregates = $this->getAggregateDefaults();
     foreach ($tokens as $columns) {
       $columns = array_values($columns);
       $this->appendAliasToken($columns);
       $length = count($columns);
       for ($i = 0; $i < $length; $i++) {
         $j = $i + 1;
         $token = $columns[$i];
         switch (strtolower($token)) {
           case '`':
           case ';':
               break;
           case '(':
               $tmp_func = strtolower($this->joinWords($stack));
               if ($level === 0
                && array_key_exists($tmp_func, $aggregates)) {
                 $func_name = $this->joinWords($stack);
               }
               unset($tmp_func);
               $level++;
               $stack[] = $token;
               break;
           case ')':
               $level--;
               if ($func_name != null) {
                 $funcs[$func_name] = $this->joinWords($exprs);
                 $func_name = null;
                 $exprs = array();
               }
               $stack[] = $token;
               break;
           case 'as':
               if ($level === 0) {
                 if ($stack == null || !isset($columns[$j])) {
                   $this->pushError('Failed to parse tokens'
                                 .  ' for columns(AS)');
                   break 3;
                 } else {
                   $alias[ $this->joinWords($stack) ] = $columns[$j];
                   $i += 2;
                 }
                 break;
               }
           default:
               if ($level) {
                 $exprs[] = $token;
               }
               $stack[] = $token;
               break;
         }
       }
       if ($stack == null) {
         $this->pushError('Failed to parse tokens for columns');
         break;
       }
       $colms[] = $this->joinWords($stack);
       $stack = array();
       $exprs = array();
     }
     switch (strtolower(substr($colname, 0, 1))) {
       case 'a':
           $result = $alias;
           break;
       case 'c':
           $result = $colms;
           break;
       case 'f':
           $result = $funcs;
           break;
       default:
           $result = array(
             'cols' => $colms,
             'as'   => $alias,
             'func' => $funcs
           );
           break;
     }
   }
   return $result;
 }

/**
 * Append the alias token "AS" into parsed tokens if proper
 *
 * @param  array   parsed tokens
 * @return void
 * @access private
 */
 function appendAliasToken(&$tokens){
   if (is_array($tokens)) {
     $length = count($tokens);
     if ($length > 1) {
       $end = end($tokens);
       $prev = prev($tokens);
       if ($prev != null && 0 !== strcasecmp($prev, 'as')
        && $end  != null && $this->isEnableName($end)
        && substr($prev, -1) !== '.') {
         array_splice($tokens, $length - 1, 0, array('as'));
       }
       reset($tokens);
     }
   }
 }

/**
 * Applies the alias of SELECT-List and FROM clauses
 *
 * @param  mixed   the tokens of SELECT-List
 * @param  mixed   the tokens of FROM clause
 * @return void
 * @access private
 */
 function applyAliasNames($tables, $columns){
   $this->_onParseAliases = true;
   if (empty($this->_onUnionSelect)) {
     $this->_selectTableAliases  = array();
     $this->_selectColumnAliases = array();
   }
   if ($tables != null) {
     $do_parse = false;
     if (!is_array($tables)) {
       $do_parse = true;
     } else {
       if (!is_array(reset($tables))) {
         $tables = array($tables);
       }
       $reset = reset($tables);
       if (!array_key_exists('table_as', $reset)
        && !array_key_exists('table_name', $reset)) {
         $do_parse = true;
       } else {
         $aliases = array();
         foreach ($tables as $table) {
           $name  = $this->getValue($table, 'table_name');
           $alias = $this->getValue($table, 'table_as');
           if ($name != null && $alias != null) {
             $aliases[$name] = $alias;
           }
         }
       }
     }
     if ($do_parse) {
       $aliases = $this->parseFromClause($tables);
     }
     if (!empty($aliases) && is_array($aliases)) {
       if (empty($this->_onUnionSelect)) {
         $this->_selectTableAliases = $aliases;
       } else {
         if (!is_array($this->_selectTableAliases)) {
           $this->_selectTableAliases = array();
         }
         foreach ($aliases as $org_name => $as_name) {
           $this->_selectTableAliases[$org_name] = $as_name;
         }
       }
     }
   }
   if ($columns != null) {
     $aliases = array();
     if (is_array($columns)) {
       if (array_key_exists('as', $columns)) {
         $columns = $columns['as'];
       }
       if (is_array($columns)) {
         $aliases = $columns;
       }
     } else if ($columns !== '*') {
       $aliases = $this->parseColumns($columns, 'as');
     }
     if (!empty($aliases) && $this->isAssoc($aliases)) {
       if (empty($this->_onUnionSelect)) {
         $this->_selectColumnAliases = $aliases;
       } else {
         if (!is_array($this->_selectColumnAliases)) {
           $this->_selectColumnAliases = array();
         }
         foreach ($aliases as $org_name => $as_name) {
           $this->_selectColumnAliases[$org_name] = $as_name;
         }
       }
     }
   }
   $this->_onParseAliases = null;
 }

/**
 * Replace the alias to enable name on "SELECT COUNT(*) FROM xxx AS x"
 *
 * @param  string  the name that include "AS xxx" syntax
 * @return string  the replaced name, or FALSE on error
 * @access private
 */
 function replaceTableAlias($name){
   $result = false;
   if (!is_string($name)) {
     $this->pushError('illegal type of alias(%s)', gettype($name));
   } else {
     if ($name == null) {
       $this->pushError('Empty table name on FROM clause');
     } else {
       $result = $name;
       $as_exists = false;
       $tokens = $this->splitSyntax($name);
       if (is_array($tokens)) {
         $tokens = $this->cleanArray($tokens);
         foreach ($tokens as $i => $token) {
           switch (strtolower($token)) {
             case 'as':
                 $as_exists = true;
                 break 2;
             default:
                 break;
           }
         }
       }
       if ($as_exists) {
         $this->_onParseAliases = true;
         $aliases = $this->parseFromClause($name);
         if (!empty($aliases) && is_array($aliases)) {
           $as_name  = reset($aliases);
           $org_name = key($aliases);
           if ($org_name != null && is_string($org_name)) {
             $result = $org_name;
           }
         }
         $this->_onParseAliases = null;
       }
     }
   }
   return $result;
 }

/**
 * Restore the tokens to original tokens
 *
 * @param  array   the target tokens (i.e. WHERE, HAVING clauses)
 * @return string  the restored tokens
 * @access private
 */
 function restoreAliasTokens($tokens){
   $result = false;
   if (!$this->hasError()) {
     if (is_string($tokens)) {
       $tokens = $this->splitSyntax($tokens);
     }
     if ($tokens != null) {
       if (!empty($this->_selectColumnAliases)
        && $this->isAssoc($this->_selectColumnAliases)) {
         $aliases = array();
         foreach ($this->_selectColumnAliases as $org_name => $as_name) {
           if (is_string($org_name) && is_string($as_name)) {
             $aliases[$as_name] = $this->splitSyntax($org_name);
           }
         }
         $tables = array();
         if (!empty($this->_selectTableAliases)
          && $this->isAssoc($this->_selectTableAliases)) {
           foreach ($this->_selectTableAliases as $org_name => $as_name) {
             if (is_string($org_name) && is_string($as_name)) {
               $tables[$as_name] = $org_name;
             }
           }
         }
         $tokens = array_values($tokens);
         for ($i = 0; $i < count($tokens); $i++) {
           $j = $i + 1;
           if (array_key_exists($tokens[$i], $aliases)) {
             $restore = $aliases[$tokens[$i]];
             array_splice($tokens, $i, 1, $restore);
           }
           if ($tables != null
            && array_key_exists($tokens[$i], $tables)
            && isset($tokens[$j]) && $tokens[$j] === '.') {
             $tokens[$i] = '';
             $tokens[$j] = '';
             $i = $j;
           }
         }
         $result = $this->cleanArray($tokens);
       }
     }
   }
   return $result;
 }

/**
 * Replace the alias to enable name
 *
 * @param  string  the alias name
 * @return string  the replaced name
 * @access private
 */
 function replaceAliasName($name){
   $result = false;
   if (!is_string($name)) {
     $this->pushError('illegal type of alias(%s)', gettype($name));
   } else {
     if (!empty($this->_selectColumnAliases)) {
       foreach ($this->_selectColumnAliases as $org_name => $as_name) {
         if ($name === $as_name) {
           $name = $org_name;
           break;
         }
       }
     }
     if (strpos($name, '.') !== false) {
       list($table, $column) = explode('.', $name);
       if ($this->isEnableName($table)
        && ($this->isEnableName($column) || $column === '*')) {
         $name = $column;
       }
     }
     $result = $name;
   }
   return $result;
 }

/**
 * Replace the alias to enable names
 *
 * @param  array   the alias names
 * @return array   the replaced names
 * @access private
 */
 function replaceAliasNames($names){
   $result = array();
   if (!$this->hasError()) {
     if (!is_array($names)) {
       $this->pushError('illegal type of aliases(%s)', gettype($names));
     } else {
       $result = array();
       foreach ($names as $org_name => $as_name) {
         $org_name = (string)$org_name;
         $new_name = $this->replaceAliasName($org_name);
         if ($this->hasError()) {
           break;
         }
         $result[$new_name] = $as_name;
       }
     }
   }
   return $result;
 }

/**
 * Restore the table name from alias name
 *
 * @param  string   the alias table name
 * @return string   the original table name
 * @access private
 */
 function restoreTableAliasName($name){
   $result = $name;
   if (strpos($name, '.') !== false) {
     list($correlation, $field_name) = explode('.', $name);
     $aliases = $this->getTableAliasNames();
     if (!empty($aliases) && is_array($aliases)) {
       foreach ($aliases as $table_name => $alias_name) {
         if ($alias_name === $correlation) {
           $result = sprintf('%s.%s', $table_name, $field_name);
           break;
         }
       }
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function findIdentifier($field_name, $keys){
   $result = $field_name;
   if (is_string($field_name) && $field_name != null
    && is_array($keys) && !empty($keys) && is_string(key($keys))) {
     if (array_key_exists($field_name, $keys)) {
       $result = $field_name;
     } else {
       if (strpos($field_name, '.') === false) {
         $simple_col = $this->replaceAliasName($field_name);
         if (array_key_exists($simple_col, $keys)) {
           $result = $simple_col;
         }
       } else {
         $column_aliases = $this->getColumnAliasNames();
         if (is_array($column_aliases) && !empty($column_aliases)
          && array_key_exists($field_name, $column_aliases)
          && array_key_exists($column_aliases[$field_name], $keys)) {
           $result = $column_aliases[$field_name];
         } else {
           list($table_name, $column_name) = explode('.', $field_name);
           $table_aliases = $this->flipArray($this->getTableAliasNames());
           if (is_array($table_aliases) && !empty($table_aliases)) {
             $correlation = $table_name;
             if (array_key_exists($table_name, $table_aliases)) {
               $correlation = $table_aliases[$table_name];
             }
             $identifier = sprintf('%s.%s', $correlation, $column_name);
             if (array_key_exists($identifier, $keys)) {
               $result = $identifier;
             } else {
               // considered simple SELECT syntax.
               if (array_key_exists($column_name, $keys)) {
                 $result = $column_name;
               }
             }
           }
           // considered simple SELECT syntax.
           if (!array_key_exists($result, $keys)
            && array_key_exists($column_name, $keys)) {
             $result = $column_name;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses the HAVING clause tokens from the SQL syntax
 *
 * @param  array    the parsed tokens which is a part of HAVING clause
 * @return array    the information of aggregate functions as array
 * @access private
 */
 function parseHaving($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s) on HAVING', $tokens);
   } else {
     $open  = '(';
     $close = ')';
     $level = 0;
     $token = null;
     $next  = null;
     $in_func    = false;
     $func_reset = false;
     $func_name  = null;
     $func_index = null;
     $func_level = null;
     $func_args  = array();
     $func_info  = array();
     $tokens = array_values($tokens);
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       $j = $i + 1;
       $token = $tokens[$i];
       if (isset($tokens[$j])) {
         $next = $tokens[$j];
       } else {
         $next = null;
       }
       switch (strtolower($token)) {
         case '(':
             $level++;
             break;
         case ')':
             $level--;
             if ($in_func && $func_level === $level) {
               $in_func = false;
               $cols = array(
                 strtoupper($func_name),
                 $open,
                 $func_args,
                 $close
               );
               $cols = $this->toOneArray($cols);
               $cols = $this->joinWords($cols);
               $func_info[] = array(
                 'name'  => $func_name,
                 'args'  => $func_args,
                 'cols'  => $cols,
                 'index' => $func_index,
                 'size'  => $i - $func_index + 1
               );
               unset($cols);
               $func_reset = true;
             }
             break;
         case 'avg':
         case 'count':
         case 'max':
         case 'min':
         case 'sum':
             if ($next === $open) {
               $func_name = $token;
               $func_args = array();
               $func_level = $level;
               $func_index = $i;
               $in_func = true;
             } else if ($in_func) {
               $func_args[] = $token;
             }
             break;
         default:
             if ($in_func) {
               $func_args[] = $token;
             }
             break;
       }
       if ($func_reset) {
         $func_reset = false;
         $in_func    = false;
         $func_name  = null;
         $func_index = null;
         $func_level = null;
         $func_args  = array();
       }
     }
     $result = $func_info;
   }
   return $result;
 }

/**
 * Check whether the token is enabled for HAVING clause
 *
 * @param  array    the parsed tokens by parseHaving() method
 * @return boolean  whether the tokens were enabled or not
 * @access private
 */
 function isEnableHavingInfo($having_infos){
   $result = true;
   if (!is_array($having_infos) || empty($having_infos)) {
     if (!$this->hasError()) {
       $error_token = $having_infos;
       if (is_array($error_token)) {
         $error_token = $this->joinWords($error_token);
       }
       $this->pushError('Failed to parse HAVING (%s)', $error_token);
     }
     $result = false;
   } else {
     $name  = 'name';
     $args  = 'args';
     $cols  = 'cols';
     $index = 'index';
     $size  = 'size';
     $props = array($name, $args, $cols, $index, $size);
     if (!is_array(reset($having_infos))) {
       $having_infos = array($having_infos);
     }
     foreach ($having_infos as $info) {
       foreach ($props as $prop) {
         if (!isset($info[$prop])) {
           $this->pushError('Undefined property(%s) in parsed token',
                            $prop);
           $result = false;
           break 2;
         }
       }
       if (!is_string($info[$name]) || $info[$name] == null
        || !is_string($info[$cols]) || $info[$cols] == null) {
         $error_token  = (string)$info[$name];
         if ($error_token == null) {
           $error_token = (string)$info[$cols];
         }
         $this->pushError('illegal token(%s) on HAVING', $error_token);
         $result = false;
         break;
       }
     }
   }
   return $result;
 }

/**
 * Replace the HAVING tokens to use on expression
 *
 * @param  array   the parsed tokens from HAVING clause
 * @param  array   the HAVING information as array
 * @param  mixed   the tokens to replace
 * @param  boolean wether clean the tokens or not
 * @return array   the tokens which ware replaced according to replacement
 * @access private
 */
 function replaceHavingTokens($having, $info,
                              $replace = null, $clean = false){
   $result = false;
   if (!$this->hasError()) {
     if (is_array($having) && $this->isEnableHavingInfo($info)
      && isset($info['size'])) {
       if (is_array($replace)) {
         $replace = $this->toOneArray($replace);
         $replace = $this->joinWords($replace);
       } else {
         $replace = (string)$replace;
       }
       $size = null;
       foreach ($having as $i => $token) {
         if ($i === $info['index']) {
           $having[$i] = $replace;
           $size = $info['size'];
           continue;
         }
         if (is_int($size)) {
           $having[$i] = '';
           if (--$size <= 1) {
             $size = null;
           }
         }
       }
       if ($clean) {
         $result = $this->cleanArray($having);
       } else {
         $result = $having;
       }
     }
   }
   return $result;
 }

/**
 * Parses the items of "GROUP BY" clause from the SQL syntax
 *
 * @param  array    the tokens which is a part of GROUP BY clause
 * @return array    the parsed items as array
 * @access private
 */
 function parseGroupBy($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s) on GROUP BY', $tokens);
   } else {
     $items  = array();
     $index  = 0;
     $level  = 0;
     $tokens = array_values($tokens);
     foreach ($tokens as $i => $token) {
       $skip = false;
       switch (strtolower($token)) {
         case '(':
             $level++;
             break;
         case ')':
             $level--;
             break;
         case ',':
             if ($level === 0) {
               $skip = true;
               $index++;
             }
             break;
         default:
             break;
       }
       if (!$skip) {
         $items[$index][] = $token;
       }
     }
     $fields = array();
     $is_expr = array();
     foreach ($items as $item) {
       if (!empty($item) && is_array($item)) {
         $field = $this->joinWords($item);
         $is_expr[] = $this->isExprToken($field);
         $fields[] = $field;
       }
     }
     $result = array(
       'fields'  => $fields,
       'is_expr' => $is_expr
     );
   }
   return $result;
 }

/**
 * Parses the "ORDER BY" clause from the SQL syntax
 *
 * @param  array    the parsed tokens which is a part of ORDER BY clause
 * @return array    the parsed clause as associate array
 * @access private
 */
 function parseOrderByTokens($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s) on ORDER BY', $tokens);
   } else {
     $index  = 0;
     $orders = array();
     $colums = array();
     $stack  = array();
     if (end($tokens) !== ' ') {
       $tokens[] = ' '; // the end point
     }
     $tokens = array_values($tokens);
     foreach ($tokens as $i => $token) {
       switch (strtolower($token)) {
         case ';':
         case '`':
             break;
         case ' ':
             $colums[$index] = $stack;
             $stack = array();
             break;
         case ',':
             if (!empty($stack)) {
               $colums[$index++] = $stack;
               $stack = array();
             }
             break;
         case 'asc':
             $orders[$index] = SORT_ASC;
             break;
         case 'desc':
             $orders[$index] = SORT_DESC;
             break;
         default:
             $stack[] = $token;
             break;
       }
     }
     $result = array();
     foreach ($colums as $i => $cols) {
       if (!array_key_exists($i, $orders)) {
         $orders[$i] = SORT_ASC;
       }
       $cols = implode('', $cols);
       $result[$cols] = $orders[$i];
     }
   }
   return $result;
 }

/**
 * Parses the LIMIT and OFFSET tokens from the SQL syntax
 *
 * @param  array    the parsed tokens which is a part of LIMIT clause
 * @return array    the parsed clause as array
 * @access private
 */
 function parseLimitTokens($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s) on LIMIT', $tokens);
   } else {
     $offset = array();
     $length = array();
     $stack  = array();
     $is_off = false;
     $is_all = false;
     if (end($tokens) !== ' ') {
       $tokens[] = ' '; // the end point
     }
     $tokens = array_values($tokens);
     foreach ($tokens as $i => $token) {
       switch (strtolower($token)) {
         case ';':
         case '`':
             break;
         case ' ':
             $length = $stack;
             $stack = array();
             if ($is_off) {
               $tmp = $length;
               $length = $offset;
               $offset = $tmp;
             }
             if ($is_all) {
               $length = array();
             }
             break;
         case 'offset':
             $is_off = true;
         case ',':
             if (!empty($stack)) {
               $offset = $stack;
               $stack = array();
             }
             break;
         case 'all':
             $is_all = true;
             $stack = array();
             break;
         default:
             $stack[] = $token;
             break;
       }
     }
     if ($offset != null) {
       $offset = (int) implode('', $offset);
     } else {
       $offset = 0;
     }
     if ($length != null) {
       $length = (int) implode('', $length);
     } else {
       $length = null;
     }
     $result = array(
       'offset' => $offset,
       'length' => $length
     );
   }
   return $result;
 }

/**
 * Parses the table name which is used on FROM clause
 *
 * @param  mixed   the token of FROM clause
 * @return array   parsed associate array, or FALSE on error
 * @access private
 */
 function parseFromClause($tokens){
   $result = false;
   if (!$this->hasError()) {
     if (is_string($tokens)) {
       $tokens = $this->splitSyntax($tokens);
     }
     if (!is_array($tokens) || $tokens == null) {
       $this->pushError('Failed to parse tokens(%s) on FROM clause', $tokens);
     } else {
       $tokens = $this->cleanArray($tokens);
       if ($this->isMultipleSelect($tokens)) {
         $result = $this->parseJoinTables($tokens);
       } else {
         $stack  = array();
         $table  = null;
         $alias  = null;
         $level  = 0;
         $this->appendAliasToken($tokens);
         $length = count($tokens);
         for ($i = 0; $i < $length; $i++) {
           $j = $i + 1;
           $token = $tokens[$i];
           switch (strtolower($token)) {
             case '`':
                 break;
             case '(':
                 $level++;
                 $stack[] = $token;
                 break;
             case ')':
                 $level--;
                 $stack[] = $token;
                 break;
             case 'as':
                 if ($level === 0) {
                   if (isset($tokens[$j])) {
                     $alias = $tokens[$j];
                     $i = $j;
                   }
                 } else {
                   $stack[] = $token;
                 }
                 break;
             default:
                 if ($level === 0) {
                   if ($table == null) {
                     $table = $token;
                   }
                 }
                 $stack[] = $token;
                 break;
           }
         }
         $sub_select = false;
         if ($this->isSubSelectFrom($tokens)) {
           $sub_select = true;
           $table = $this->joinWords($stack);
         }
         $is_error = false;
         if ($table == null) {
           $is_error = true;
         } else if (!$sub_select && !$this->isEnableName($table)) {
           $is_error = true;
         }
         if ($is_error) {
           $this->pushError('illegal table name(%s) on FROM clause', $table);
         } else {
           $this->_selectTableNames = array($table => $alias);
           if (empty($this->_onParseAliases)) {
             $result = array(
               'table_name' => $table,
               'table_as'   => $alias
             );
           } else {
             if ($alias != null) {
               $result = array($table => $alias);
             }
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses the tables name for SELECT method
 *
 * @param  mixed  tables of target
 * @return array  results of tables, or false on error
 * @access private
 */
 function parseJoinTables($tokens){
   $result = false;
   if (!is_array($tokens) && $tokens != null) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens on SELECT using JOIN(%s)',
                      $tokens);
   } else {
     $tokens = $this->removeBacktickToken($tokens);
     $open   = '(';
     $close  = ')';
     while (reset($tokens) === $open
          && end($tokens) === $close) {
       array_shift($tokens);
       array_pop($tokens);
     }
     reset($tokens);
     array_push($tokens, ','); // dummy to parse
     $tokens = array_values($tokens);
     $length = count($tokens);
     $in_func = false;
     $in_expr = false;
     $usings = array();
     $tables = array();
     $stack  = array();
     $index      = 0;
     $on_index   = 0;
     $level      = 0;
     $base_table = '';
     $table_name = '';
     $table_as   = '';
     $join_type  = '';
     $prev_type  = '';
     $join_expr  = array();
     for ($i = 0; $i < $length; $i++) {
       $h = $i - 1;
       $j = $i + 1;
       $k = $j + 1;
       $token = $tokens[$i];
       $prev  = $this->getValue($tokens, $h);
       $next  = $this->getValue($tokens, $j);
       switch (strtolower($token)) {
         case ';':
         case '$':
         case '`':
             break;
         case '(':
             $level++;
             break;
         case ')':
             $level--;
             break;
         case 'as':
             if ($level === 0 && $next != null) {
               $table_as = $next;
               $i = $j;
               $token = null;
             }
             break;
         case ',':
             if ($level === 0 && $j !== $length) {
               $join_type = 'cross';
             }
             // FALLTHROUGH
         case 'join':
             if ($level === 0) {
               $in_expr = false;
               $this->_setJoinTableTokens($table_name, $table_as, $stack);
               if ($join_type == null || !isset($tokens[$h])) {
                 $join_type = 'cross';
               } else {
                 $ltoken_h = strtolower($tokens[$h]);
                 if ($ltoken_h === 'inner'
                  || $ltoken_h === 'cross'
                  || $table_name === $tokens[$h]) {
                   $join_type = 'cross';
                 }
                 unset($ltoken_h);
               }
               if ($next != null) {
                 $tables[$index] = array(
                   'table_name' => $table_name,
                   'table_as'   => $table_as,
                   'join_type'  => $join_type,
                   'join_expr'  => $join_expr,
                   'using'      => $usings
                 );
                 $tables[$index]['join_type'] = $prev_type;
                 $prev_type = $join_type;
                 if ($index === 0) {
                   $base_table = $table_name;
                 }
                 $index++;
                 $in_expr = false;
                 $table_name = '';
                 $table_as = '';
                 $join_expr = array();
                 $usings = array();
                 $token = null;
               }
             }
             break;
         case 'inner':
         case 'cross':
             if ($level === 0) {
               if (0 === strcasecmp($next, 'join')) {
                 $join_type = 'cross';
                 $this->_setJoinTableTokens($table_name, $table_as, $stack);
               }
               $in_expr = false;
               $token = null;
             }
             break;
         case 'outer':
             if ($level === 0) {
               if (0 === strcasecmp($next, 'join')) {
                 $this->_setJoinTableTokens($table_name, $table_as, $stack);
               }
               $in_expr = false;
               $token = null;
             }
             break;
         case 'natural':
             if ($level === 0) {
               $in_expr = false;
               if (!empty($this->_onParseAliases)) {
                 break;
               }
               if ($next != null) {
                 $this->_setJoinTableTokens($table_name, $table_as, $stack);
                 $is_natural = false;
                 if (0 === strcasecmp($next, 'join')) {
                   $is_natural = true;
                 } else if (isset($tokens[$k])
                         && 0 === strcasecmp($tokens[$k], 'join')) {
                   $is_natural = true;
                   $i++;
                 } else if (isset($tokens[$k + 1])
                         && 0 === strcasecmp($tokens[$k + 1], 'join')) {
                   $is_natural = true;
                   $i += 2;
                 }
                 if ($is_natural) {
                   $join_type = 'natural';
                   if ($table_name != null) {
                     if ($base_table == null) {
                       if (isset($tokens[$i + 2])
                        && $tokens[$i + 2] != null) {
                         $base_table = $tokens[$i + 2];
                       }
                     }
                     $meta = $this->getMeta($table_name);
                     if (!is_array($meta)) {
                       $this->pushError('Not exists the table(%s) on JOIN',
                                        $table_name);
                       break 2;
                     } else if (empty($meta) || $base_table == null) {
                       $this->pushError('Failed to parse the tokens on JOIN');
                       break 2;
                     } else {
                       $nm_expr = array();
                       foreach (array_keys($meta) as $nm_key) {
                         if ($nm_key === 'rowid'
                          || $nm_key === 'ctime' || $nm_key === 'utime') {
                           continue;
                         }
                         $usings[] = $nm_key;
                         foreach (array($base_table, '.', $nm_key,
                                   '=', $table_name, '.', $nm_key) as $nm_c) {
                           $nm_expr[] = $nm_c;
                         }
                         $nm_expr[] = '&&';
                       }
                       array_pop($nm_expr);
                       foreach ($nm_expr as $nm_c) {
                         $join_expr[] = $nm_c;
                       }
                     }
                     unset($meta, $nm_expr, $nm_key, $nm_c);
                   }
                   $token = null;
                 }
                 unset($is_natural);
               }
             }
             break;
         case 'left':
         case 'right':
         case 'full':
             if ($level === 0) {
               $in_expr = false;
               if ($next != null) {
                 if ((0 === strcasecmp($next, 'join'))
                  || (isset($tokens[$k])
                  && 0 === strcasecmp($next, 'outer')
                  && 0 === strcasecmp($tokens[$k], 'join'))) {
                   $this->_setJoinTableTokens($table_name, $table_as, $stack);
                   $join_type = strtolower($token);
                   $token = null;
                 }
               }
             }
             break;
         case 'using':
             if ($level === 0) {
               if ($next === $open && !empty($stack)) {
                 $slice = array_slice($tokens, $i);
                 $close_pos = array_search($close, $slice);
                 if (!is_int($close_pos)) {
                   $this->pushError('Parsed error on JOIN(%s)', $token);
                   break 2;
                 }
                 $close_pos += $i;
                 $on_index = $k;
                 $i++;
                 $in_expr = true;
                 if ($base_table == null) {
                   if (isset($tables[$index - 1]['table_name'])) {
                     $base_table = $tables[$index - 1]['table_name'];
                   } else {
                     $this->pushError('Parsed error on JOIN(USING)');
                     break 2;
                   }
                 }
                 $this->_setJoinTableTokens($table_name, $table_as, $stack);
                 while (++$i < $length) {
                   $using_token = $tokens[$i];
                   if ($i == $close_pos || $token === $close) {
                     $in_expr = false;
                     break;
                   } else if ($using_token === ',') {
                     $join_expr[] = '&&';
                   } else {
                     $join_expr[] = $base_table;
                     $join_expr[] = '.';
                     $join_expr[] = $using_token;
                     $join_expr[] = '=';
                     $join_expr[] = $table_name;
                     $join_expr[] = '.';
                     $join_expr[] = $using_token;
                     $usings[] = $using_token;
                   }
                 }
                 $in_expr = false;
                 $token = null;
               }
             }
             break;
         case 'on':
             if ($level === 0) {
               $on_index = $i;
               $this->_setJoinTableTokens($table_name, $table_as, $stack);
               $usings = array();
               $in_expr = true;
               $token = null;
             }
             break;
         default:
             break;
       }
       if ($this->hasError()) {
         break;
       }
       if ($token != null) {
         if ($in_expr) {
           $join_expr[] = $token;
         } else {
           $stack[] = $token;
         }
       }
     }
     if ($this->hasError()) {
       $result = false;
     } else {
       if ($prev_type != null) {
         $join_type = $prev_type;
       }
       // Fixes for NATURAL JOIN
       $key = 'join_expr';
       if (!empty($tables[0][$key]) && empty($join_expr)) {
         $join_expr = $tables[0][$key];
         $tables[0][$key] = array();
       }
       $key = 'using';
       if (!empty($tables[0][$key]) && empty($usings)) {
         $usings = $tables[0][$key];
         $tables[0][$key] = array();
       }
       $tables[++$index] = array(
         'table_name' => $table_name,
         'table_as'   => $table_as,
         'join_type'  => $join_type,
         'join_expr'  => $join_expr,
         'using'      => $usings
       );
       $tables = array_values($tables);
       $fields = array();
       foreach ($tables as $table) {
         $name  = $this->getValue($table, 'table_name');
         $alias = $this->getValue($table, 'table_as');
         if ($name != null) {
           $fields[$name] = $alias;
         }
       }
       $this->_selectTableNames = $fields;
       if (empty($this->_onParseAliases)) {
         $result = $this->assignJoinTables($tables);
       } else {
         $result = array();
         foreach ($tables as $table) {
           $name  = $this->getValue($table, 'table_name');
           $alias = $this->getValue($table, 'table_as');
           if ($name != null && $alias != null) {
             $result[$name] = $alias;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function _setJoinTableTokens(&$table_name, &$table_as, &$stack){
   if (!empty($stack)) {
     $is_subquery = $this->isSubSelectFrom($stack);
     if (!$this->hasError()) {
       if ($is_subquery) {
         $this->appendAliasToken($stack);
         $items = $this->parseSubQuery($stack, true, true);
         if (is_array($items) && array_key_exists('tokens', $items)) {
           $tokens = $items['tokens'];
           if (is_array($tokens)) {
             $table_name = $this->joinWords($tokens);
             if ($table_as == null) {
               if (array_key_exists('alias', $items)
                && $items['alias'] != null) {
                 $table_as = $items['alias'];
               } else if (array_key_exists('offset', $items)
                       && array_key_exists('length', $items)) {
                 array_splice($stack, $items['offset'], $items['length']);
                 $stub = array_splice($stack, 0);
                 if (is_array($stub) && 1 === count($stub)) {
                   $table_as = array_shift($stub);
                 }
               }
             }
           }
         }
       } else {
         $table_name = array_shift($stack);
         if (1 === count($stack) && $table_as == null) {
           $table_as = array_shift($stack);
         }
       }
     }
     $stack = array();
   }
 }

/**
 * @access private
 */
 function assignJoinTables($parsed){
   $result = false;
   if (empty($parsed) || !is_array($parsed) || !isset($parsed[0])) {
     $this->pushError('Cannot parse tokens on JOIN');
   } else {
     $reset = reset($parsed);
     $check = true;
     $tname = 'table_name';
     $alias = 'table_as';
     $jtype = 'join_type';
     $jexpr = 'join_expr';
     foreach (array($tname, $alias, $jtype, $jexpr) as $key) {
       if (!array_key_exists($key, $reset)) {
         $this->pushError('Failed parsing tokens on JOIN');
         $check = false;
         break;
       }
     }
     if ($check) {
       $tnames = array();
       foreach ($parsed as $i => $items) {
         if (isset($items[$tname]) && $items[$tname] != null) {
           $tnames[$items[$tname]] = $this->getValue($items, $alias, 1);
         }
       }
       $aliases = array();
       foreach ($tnames as $key => $val) {
         if (is_int($val)) {
           $tnames[$key] = $key;
         }
         $aliases[$tnames[$key]] = $key;
       }
       $convert_tnames = array();
       $do_convert = false;
       foreach ($parsed as $pkey => $items) {
         $convert_tnames[$pkey] = false;
         if ($this->isSubSelectFrom($items[$tname])) {
           $convert_tnames[$pkey] = true;
           $do_convert = true;
         }
       }
       $this->_subSelectJoinUniqueNames = array();
       if ($do_convert) {
         $unique_seed = 0;
         $new_parsed = array();
         foreach ($parsed as $pkey => $items) {
           if (!empty($convert_tnames[$pkey])) {
             do {
               $unique_tname = sprintf('_posql_subquery_%d', $unique_seed++);
             } while (array_key_exists($unique_tname, $tnames)
                   || array_key_exists($unique_tname, $aliases));
             $this->_subSelectJoinUniqueNames[$unique_tname] = $items[$tname];
             $items[$tname] = $unique_tname;
           } else {
             $this->_subSelectJoinUniqueNames[$items[$tname]] = '<=>';
           }
           $new_parsed[$pkey] = $items;
         }
         $parsed = $new_parsed;
         unset($new_parsed);
       }
       if (!empty($this->_subSelectJoinUniqueNames)) {
         $flip_uniqs = $this->flipArray($this->_subSelectJoinUniqueNames);
         $new_tnames = array();
         foreach ($tnames as $key => $val) {
           if (array_key_exists($key, $flip_uniqs)) {
             $key = $flip_uniqs[$key];
           }
           $new_tnames[$key] = $val;
         }
         $tnames = $new_tnames;
         unset($new_tnames);
         $new_aliases = array();
         foreach ($aliases as $key => $val) {
           if (array_key_exists($val, $flip_uniqs)) {
             $val = $flip_uniqs[$val];
           }
           $new_aliases[$key] = $val;
         }
         $aliases = $new_aliases;
         unset($new_aliases);
       }
       $tables = array_map(array($this, 'decodeKey'),
                   array_filter(array_keys($this->meta)));
       foreach ($parsed as $pkey => $items) {
         if (isset($items[$jexpr], $items[$tname])
          && is_array($items[$jexpr])) {
           $expr_vars = array();
           $level = 0;
           $tokens = array_values($items[$jexpr]);
           foreach ($tokens as $i => $token) {
             $h = $i - 1;
             $j = $i + 1;
             switch ($token) {
               case '$':
               case ';':
               case '`':
                   break;
               case '(':
                   $level++;
                   break;
               case ')':
                   $level--;
                   break;
               case '.':
                   if (isset($tokens[$h], $tokens[$j])) {
                     if (isset($aliases[ $tokens[$h] ])) {
                       $tokens[$h] = $aliases[ $tokens[$h] ];
                     }
                     if (isset($tnames[ $tokens[$h] ])
                      || isset($tables[ $tokens[$h] ])) {
                       $tokens[$i] = $tokens[$h] . "['" . $tokens[$j] . "']";
                       $expr_vars[] = array(
                         'name' => $tokens[$h],
                         'key'  => $tokens[$j]
                       );
                       $tokens[$h] = $tokens[$j] = '';
                       if (substr(trim($tokens[$i]), 0, 1) !== '$') {
                         $tokens[$i] = '$' . $tokens[$i];
                       }
                     }
                   }
                   break;
               case '=':
                   if (isset($tokens[$h], $tokens[$j])
                    && !empty($this->autoAssignEquals)) {
                     $tokens[$i] = '==';
                   }
                   break;
               default:
                   if (isset($aliases[$token])) {
                     $token = $tokens[$i] = $aliases[$token];
                   }
                   if (isset($tokens[$h])) {
                     if (isset($aliases[ $tokens[$h] ])) {
                       $tokens[$h] = $aliases[ $tokens[$h] ];
                     }
                     if (isset($tnames[ $tokens[$h] ])
                      || isset($tables[ $tokens[$h] ])) {
                       $tokens[$i] = $tokens[$h] . "['" . $tokens[$j] . "']";
                       $expr_vars[] = array(
                         'name' => $tokens[$h],
                         'key'  => $tokens[$j]
                       );
                       $tokens[$h] = $tokens[$j] = '';
                       if (substr(trim($tokens[$i]), 0, 1) !== '$') {
                         $tokens[$i] = '$' . $tokens[$i];
                       }
                     }
                   }
                   break;
             }
           }
           if (!empty($expr_vars)) {
             $exprs = array();
             foreach ($expr_vars as $expr_var) {
               $varname = '$' . $this->trimExtra($expr_var['name']);
               $varkey  = "'" . $this->trimExtra($expr_var['key']) . "'";
               $exprs[] = sprintf('isset(%s[%s])', $varname, $varkey);
             }
             $exprs = array_unique($exprs);
             $exprs[] = '';
             $expr = implode('&&', $exprs);
             array_unshift($tokens, $expr);
           }
           $tokens = array_values(array_filter($tokens, 'strlen'));
           $expr = trim($this->joinWords($tokens), '&|;');
           if (strlen($expr) === 0) {
             $expr = true;
           } else {
             $expr = $this->optimizeExpr($expr);
           }
           switch ($this->getEngine()) {
             case 'SQL':
                 $this->_onJoinParseExpr = true;
                 if (!is_bool($expr)) {
                   if ($expr != null && is_array($expr)) {
                     $expr = $this->joinWords($expr);
                   }
                   $expr = $this->validExpr($expr);
                 }
                 $this->_onJoinParseExpr = null;
                 break;
             case 'PHP':
             default:
                 break;
           }
           $parsed[$pkey][$jexpr] = $expr;
         } else {
           $parsed[$pkey][$jexpr] = true;
         }
       }
       $result = $parsed;
     }
   }
   return $result;
 }

/**
 * Backtick operator will be ignored for compatibly with MySQL
 *
 * @param  array   the parsed tokens
 * @return array   the tokens which has no backticks
 * @access private
 */
 function removeBacktickToken($tokens){
   $result = array();
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s)', $tokens);
   } else {
     $tokens = $this->cleanArray($tokens);
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       if ($tokens[$i] === '`') {
         unset($tokens[$i]);
       }
     }
     $result = array_values($tokens);
     unset($tokens);
   }
   return $result;
 }

/**
 * @access private
 */
 function removeCorrelations(&$rows){
   if (!empty($rows) && is_array($rows)) {
     $row_count = count($rows);
     for ($i = 0; $i < $row_count; $i++) {
       foreach ($rows[$i] as $key => $val) {
         if (strpos($key, '.') !== false) {
           unset($rows[$i][$key]);
         }
       }
     }
   }
 }

/**
 * @access private
 */
 function removeCorrelationName(&$rows){
   if (!empty($rows) && is_array($rows)) {
     $row_count = count($rows);
     for ($i = 0; $i < $row_count; $i++) {
       $correlations = array();
       foreach (array_keys($rows[$i]) as $key) {
         if (strpos($key, '.') !== false) {
           list($table, $column) = explode('.', $key);
           $correlations[$key] = $column;
         }
       }
       if (!empty($correlations)) {
         $new_row = array();
         foreach ($rows[$i] as $key => $val) {
           if (array_key_exists($key, $correlations)) {
             $new_row[$correlations[$key]] = $val;
           } else {
             $new_row[$key] = $val;
           }
         }
         $rows[$i] = $new_row;
       }
     }
   }
 }

/**
 * To maintain the form of "correlation.columnname"
 *  from the token as the array, it unites by dot (.)
 *
 * @param  array   the parsed tokens
 * @return array   the tokens which was concatenated by dot
 * @access private
 */
 function concatCorrelationToken($tokens){
   $result = array();
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the correlative tokens(%s)', $tokens);
   } else {
     $tokens = $this->cleanArray($tokens);
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       $h = $i - 1;
       $j = $i + 1;
       if (isset($tokens[$h])) {
         $prev = & $tokens[$h];
       } else {
         unset($prev);
         $prev = null;
       }
       if (isset($tokens[$j])) {
         $next = & $tokens[$j];
       } else {
         unset($next);
         $next = null;
       }
       $token = & $tokens[$i];
       switch ($token) {
         case '.':
             if ($prev != null && $next != null) {
               if ($this->isWord($prev)
                && ($this->isWord($next) || $next === '*')) {
                 $token = $prev . $token . $next;
                 $prev = null;
                 $next = null;
               } else if (!is_numeric($prev) && !is_numeric($next)) {
                 $this->pushError('illegal syntax,'
                               .  ' expect (correlation.column)');
                 $tokens = array();
                 break 2;
               }
             }
             break;
         default:
             break;
       }
     }
     if (empty($tokens)) {
       $result = array();
     } else {
       $result = $this->cleanArray($tokens);
     }
   }
   unset($token, $prev, $next);
   return $result;
 }

/**
 * To maintain the form of "correlation.columnname"
 *  from the token as the array, it divide by dot (.)
 *
 * @param  array   the parsed tokens
 * @return array   the tokens which was split by dot
 * @access private
 */
 function splitCorrelationToken($tokens){
   $result = array();
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the correlative tokens(%s)', $tokens);
   } else {
     $tokens = $this->cleanArray($tokens);
     $length = count($tokens);
     $dot = '.';
     for ($i = 0; $i < $length; $i++) {
       $token = & $tokens[$i];
       if (strpos($token, $dot) !== false) {
         $parts = explode($dot, $token);
         if (count($parts) === 2) {
           list($correlation, $column) = $parts;
           if ($correlation != null && $column != null
            && $this->isWord($correlation)
            && ($this->isWord($column) || $column === '*')) {
             array_splice($tokens, $i, 1,
               array(
                 $correlation,
                 $dot,
                 $column
               )
             );
             $length = count($tokens);
           }
         }
       }
       unset($token);
     }
     if (empty($tokens)) {
       $result = array();
     } else {
       $result = $this->cleanArray($tokens);
     }
   }
   return $result;
 }

/**
 * Gets the operand from right directive tokens
 *
 * @param  array    the parsed tokens as an array
 * @param  number   the start number of token's index
 * @param  string   the token of current operator
 * @return string   the operand, or FALSE on error
 * @access private
 */
 function getRightOperandBySQL(&$tokens, $start_idx, $token){
   //TODO: unidentified
   $result = false;
   $i = $start_idx - 1;
   $j = $i + 1;
   $k = $j + 1;
   $l = $k + 1;
   $open  = '(';
   $close = ')';
   if (is_array($tokens)
    && isset($tokens[$i], $tokens[$j])) {
     if ($tokens[$j] === $open) {
       $nest  = 0;
       $idx   = $i;
       $stack = array();
       while ($nest >= 0 && isset($tokens[++$idx])) {
         $op = $tokens[$idx];
         $tokens[$idx] = '';
         if ($op === $open) {
           $nest++;
         } else if ($op === $close) {
           $nest--;
         }
         $stack[] = $op;
         if ($nest === 0) {
           if (isset($tokens[++$idx]) && $tokens[$idx] != null) {
             $stack[] = $tokens[$idx];
             $tokens[$idx] = '';
           }
           break;
         }
       }
       if (empty($stack) || !is_array($stack)) {
         $result = false;
       } else {
         $result = $stack;
       }
     } else {
       if (isset($tokens[$k], $tokens[$l])
        && ($tokens[$k] === '||' || $tokens[$k] === '.')
        && !is_numeric($tokens[$l])) {
         $result = array();
         $idx = $i;
         while (isset($tokens[++$idx])) {
           $op = $tokens[$idx];
           if ($op === '' || $op === null) {
             continue;
           }
           if (($op === '||' || $op === '.')
            || $this->isStringToken($op)) {
             $result[] = $op;
             $tokens[$idx] = '';
           } else {
             break;
           }
         }
       } else {
         $result = array($tokens[$j]);
         $tokens[$j] = '';
       }
     }
   }
   if ($result === false) {
     $token = strtoupper($token);
     $this->pushError('illegal syntax, expect(%s) operand', $token);
   }
   return $result;
 }

/**
 * Gets the operand from left directive tokens
 *
 * @param  array    the parsed tokens as an array
 * @param  number   the start number of token's index
 * @param  string   the token of current operator
 * @return string   the operand, or FALSE on error
 * @access private
 */
 function getLeftOperandBySQL(&$tokens, $start_idx, $token){
   $result = false;
   $i = $start_idx + 1;
   $h = $i - 1;
   $g = $h - 1;
   $f = $g - 1;
   $open  = '(';
   $close = ')';
   if (is_array($tokens)
    && isset($tokens[$i], $tokens[$h])) {
     if ($tokens[$h] === $close) {
       $nest  = 0;
       $idx   = $i;
       $stack = array();
       while ($nest >= 0 && isset($tokens[--$idx])) {
         $op = $tokens[$idx];
         $tokens[$idx] = '';
         if ($op === $open) {
           $nest--;
         } else if ($op === $close) {
           $nest++;
         }
         array_unshift($stack, $op);
         if ($nest === 0) {
           if (isset($tokens[--$idx]) && $tokens[$idx] != null) {
             array_unshift($stack, $tokens[$idx]);
             $tokens[$idx] = '';
           }
           break;
         }
       }
       if (empty($stack) || !is_array($stack)) {
         $result = false;
       } else {
         $result = $stack;
       }
     } else {
       if (isset($tokens[$g], $tokens[$f])
        && $tokens[$g] === '.' && !is_numeric($tokens[$f])) {
         $result = array();
         $idx = $i;
         while (isset($tokens[--$idx])) {
           $op = $tokens[$idx];
           if ($op === '' || $op === null) {
             continue;
           }
           if ($op === '.' || $this->isStringToken($op)) {
             array_unshift($result, $op);
             $tokens[$idx] = '';
           } else {
             break;
           }
         }
       } else {
         $result = array($tokens[$h]);
         $tokens[$h] = '';
       }
     }
   }
   if ($result === false) {
     $token = strtoupper($token);
     $this->pushError('illegal syntax, expect(%s) operand', $token);
   }
   return $result;
 }

/**
 * Replaces from the standard SQL syntax to the Expression
 *   which be able to handled on Posql
 *
 * @param  mixed   the expression, or the SQL syntax
 * @return array   the parsed tokens which were replaced available of Posql
 *                 (or FALSE on error)
 * @access private
 */
 function replaceExprBySQL($expr){
   $result = false;
   $is_error = false;
   $this->_caseCount = null;
   if ($this->isParsedExpr($expr)) {
     $result = $expr;
   } else {
     if (is_string($expr)) {
       $expr = $this->splitSyntax($expr);
     }
     if (!is_array($expr)) {
       $this->pushError('Cannot parse the tokens(%s) as SQL mode', $expr);
     } else {
       $tokens = $this->cleanArray($expr);
       unset($expr);
       $tokens = $this->removeBacktickToken($tokens);
       $tokens = $this->concatCorrelationToken($tokens);
       $result = array();
       for ($i = 0; $i < count($tokens); $i++) {
         $is_error = $this->replaceTokenBySQL($tokens, $i);
         if ($is_error) {
           $result = false;
           break;
         }
       }
       if (!$is_error) {
         $result = $this->splitCorrelationToken($tokens);
         unset($tokens);
         $this->addParsedMark($result);
       }
     }
   }
   $this->_caseCount = null;
   return $result;
 }

/**
 * Replaces the tokens from the standard SQL syntax
 *  which be able to available on Posql
 *
 * @param  array  the tokens which is parsing
 * @param  number the number of current index in parsing
 * @return array  the parsed tokens which were replaced available of Posql
 * @access private
 */
 function replaceTokenBySQL(&$tokens, &$index){
   $is_error = false;
   $i = & $index;
   if (!is_array($tokens) || !array_key_exists($i, $tokens)) {
     $error_token = $tokens;
     if (is_array($error_token)) {
       $error_token = implode(', ', $error_token);
     }
     $this->pushError('Cannot parse the tokens(%s) as SQL mode',
                      $error_token);
     $is_error = true;
     unset($error_token);
   } else {
     $g = $i - 2;
     $h = $i - 1;
     $j = $i + 1;
     $k = $j + 1;
     $l = $k + 1;
     $prev = '';
     $next = '';
     $level = 0;
     $open  = '(';
     $close = ')';
     $token = & $tokens[$i];
     if (array_key_exists($h, $tokens)) {
       $prev = & $tokens[$h];
     }
     if (array_key_exists($j, $tokens)) {
       $next = & $tokens[$j];
     }
     switch (strtolower($token)) {
       case 'not':
       case '!':
           if ($prev === '==' || strtolower($prev) === 'is') {
             $token = '!=';
             $prev = '';
           } else if ($prev === '===') {
             $token = '!==';
             $prev = '';
           } else {
             $token = '!';
           }
           break;
       case 'is':
       case '=':
           if ($next === '!' || strtolower($next) === 'not') {
             $next  = '';
             $token = '!=';
           } else {
             $token = '==';
           }
           break;
       case 'isnull':
           // expression ISNULL
           if ($next !== $open && $prev != null) {
             $isnull = array('===', 'null');
             array_splice($tokens, $i, 1, $isnull);
             unset($isnull);
           }
           break;
       case 'notnull':
           // expression NOTNULL
           if ($next !== $open && $prev != null) {
             $notnull = array('!==', 'null');
             array_splice($tokens, $i, 1, $notnull);
             unset($notnull);
           }
           break;
       case 'coalesce':
       case 'ifnull':
       case 'nvl':
            // ---------------------------
            // COALESCE ( value [, ...] )
            // ---------------------------
            // IFNULL ( expr1, expr2 )
            // ---------------------------
            // NVL ( expr1 , expr2 )
            // ---------------------------
            if ($next === $open) {
              //TODO: parse right operand
              break;
              $idx = $i + 1;
              $nest = 0;
              $exprs = array();
              $stack = array();
              $prev_opd = null;
              $open_count = 0;
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch ($opd) {
                  case '(':
                      $nest++;
                      $stack[] = $opd;
                      break;
                  case ')':
                      $nest--;
                      $stack[] = $opd;
                      break;
                  case ',':
                      $prev_opd = $stack;
                      $stack = array();
                      if ($open_count === 0) {
                        if (reset($prev_opd) === $open) {
                          array_shift($prev_opd);
                        }
                        $exprs[] = $open;
                        $open_count++;
                      }
                      $exprs[] = array(
                        $prev_opd, '!==', 'null', '?',
                        $prev_opd, ':',
                        $open
                      );
                      $open_count++;
                      break;
                  default:
                      $stack[] = $opd;
                      break;
                }
                if ($nest === 0) {
                  if (end($stack) === $close) {
                    array_pop($stack);
                  }
                  $prev_opd = $stack;
                  $stack = array();
                  $exprs[] = array(
                    $prev_opd, '!==', 'null', '?',
                    $prev_opd, ':',   'null'
                  );
                  break;
                }
                ++$idx;
              }
              while (--$open_count >= 0) {
                $exprs[] = $close;
              }
              $exprs = $this->toOneArray($exprs);
              $check = array_count_values($exprs);
              if (isset($check[$open], $check[$close])
               && $check[$open] === $check[$close]) {
                array_splice($tokens, $i, $idx - $i + 1, $exprs);
              } else {
                $this->pushError('illegal syntax, expect (%s) at end',
                                 strtoupper($token));
                $is_error = true;
              }
              unset($exprs, $check, $stack, $prev_opd, $opd);
            } else {
              $this->pushError('illegal syntax, expect (%s) at end',
                               strtoupper($token));
              $is_error = true;
            }
            break;
       case 'nullif':
            // -------------------------------------------------
            // NULLIF(expr1, expr2)
            // -------------------------------------------------
            //   eq
            // -------------------------------------------------
            // CASE WHEN expr1 = expr2 THEN NULL ELSE expr1 END
            // -------------------------------------------------
            if ($next === $open) {
              //TODO: parse right operand
              break;
              $idx = $i + 1;
              $nest = 0;
              $nf_index = 0;
              $nf_exprs = array(0 => array(),
                                1 => array());
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch ($opd) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case ',':
                      $nf_index = 1;
                  default:
                      break;
                }
                if ($opd !== ',') {
                  $nf_exprs[$nf_index][] = $opd;
                }
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              foreach (array(0, 1) as $nf_index) {
                if (count($nf_exprs[$nf_index]) === 0) {
                  $nf_exprs[$nf_index] = array('null');
                }
              }
              $nf_expr = array(
                '(',
                $nf_exprs[0], '=', $nf_exprs[1],
                              '?', 'null',
                              ':', $nf_exprs[0],
                ')'
              );
              $nf_expr = $this->toOneArray($nf_expr);
              array_splice($tokens, $i, $idx - $i, $nf_expr);
              $stack = array();
              unset($nf_expr, $nf_exprs, $nf_index);
            } else {
              $this->pushError('illegal syntax, expect NULLIF(%s)', $next);
              $is_error = true;
            }
            break;
       case 'cast':
       case 'convert':
       case 'translate':
            // --------------------------------------------------
            // CAST (expr AS type)
            // --------------------------------------------------
            // CONVERT (expr, type)
            // --------------------------------------------------
            // CONVERT (string, dest_charset [, source_charset] )
            // --------------------------------------------------
            // SQL99 Syntax
            // --------------------------------------------
            // CONVERT (char_value target_char_set
            //          USING form_of_use source_char_name)
            // --------------------------------------------------
            // TRANSLATE (char_value target_char_set
            //            USING translation_name)
            // --------------------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              //$args_count = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case ',':
                      /*
                      // It will not convert it yet now though
                      // it might be appropriate to handle as CAST
                      //  when the argument is delimited by the comma
                      //  because it is not so general.
                      if (strtolower($token) === 'convert') {
                        $token = 'CAST';
                        array_shift($stack);
                        array_unshift($stack, $token);
                      }
                      */
                  case 'as':
                  case 'using':
                      $next_idx = $idx + 1;
                      if (isset($tokens[$next_idx])) {
                        $next_opd = & $tokens[$next_idx];
                        if ($next_opd != null && $this->isWord($next_opd)) {
                          $next_opd = $this->toStringToken($next_opd);
                        }
                        unset($next_opd);
                      }
                      unset($next_idx);
                      $opd = ',';
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect %s(%s)',
                               strtoupper($token), $next);
              $is_error = true;
            }
            break;
       case 'substring':
            // --------------------------------------
            // SQL99 Syntax
            // --------------------------------------
            // SUBSTRING (  extraction_string
            //      FROM    starting_position
            //    [ FOR     length ]
            //    [ COLLATE collation_name ]
            // )
            // --------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case 'from':
                  case 'for':
                  case 'collate':
                      $opd = ',';
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect SUBSTRING(%s)',
                               $next);
              $is_error = true;
            }
            break;
       case 'trim':
            // SQL99 Syntax
            // ------------------------------------------------------------
            // TRIM (
            //    [
            //      [ {LEADING | TRAILING | BOTH} ]
            //      [ removal_string ]
            //      FROM
            //    ]
            //    target_string
            //    [ COLLATE collation_name ]
            // )
            // ------------------------------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case 'leading':
                  case 'trailing':
                  case 'both':
                      $stack[] = $this->toStringToken($opd);
                      $opd = ',';
                      break;
                  case 'from':
                  case 'collate':
                      $opd = ',';
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect %s(%s)',
                               strtoupper($token), $next);
              $is_error = true;
            }
            break;
       case 'position':
            // SQL99 Syntax
            // -----------------------------------------------
            // POSITION ( starting_string IN search_string )
            // -----------------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case 'in':
                      $opd = ',';
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect POSITION(%s)',
                               $next);
              $is_error = true;
            }
            break;
       case 'extract':
            // SQL99 Syntax
            // --------------------------------------------------------
            // EXTRACT(datepart FROM expression)
            // --------------------------------------------------------
            // the datepart to be extracted
            // (
            //  YEAR, MONTH, DAY, HOUR, MINUTE, SECOND,
            //  TIMEZONE_HOUR, or TIMEZONE_MINUTE
            // )
            // from an expression
            // --------------------------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case 'from':
                      $opd = ',';
                      $prev_opd = array_pop($stack);
                      if (!$this->isStringToken($prev_opd)) {
                        $stack[] = $this->toStringToken($prev_opd);
                      } else {
                        $stack[] = $prev_opd;
                      }
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect EXTRACT(%s)',
                               $next);
              $is_error = true;
            }
            break;
       case 'case':
           if ($next == null
            || !isset($tokens[$k]) || !isset($tokens[$l])) {
             $this->pushError('illegal syntax, expect (CASE, WHEN, ...)');
             $is_error = true;
           } else {
             if (strtolower($next) !== 'when') {
               $idx = $i + 1;
               $case_opd = array();
               while (isset($tokens[$idx])) {
                 if (strtolower($tokens[$idx]) === 'when') {
                   break;
                 }
                 $case_opd[] = $tokens[$idx];
                 $idx++;
               }
               if (empty($case_opd)) {
                 $this->pushError(
                     'illegal syntax, expect the empty expression'
                   . ' on CASE expression'
                 );
                 $is_error = true;
               } else {
                 // ----------------------------------------------
                 // CASE expr
                 //      WHEN comparison_expr THEN result_expr
                 //     [WHEN ...]
                 //      ELSE default_expr
                 // END
                 // ----------------------------------------------
                 // ( expr = comparison_expr
                 //        ? result_expr
                 //        : (...) default_expr )
                 // ----------------------------------------------
                 $stack = array($open, $case_opd, '=');
                 $stack = $this->toOneArray($stack);
                 $case_opd_len = count($case_opd) + 1;
                 array_splice($tokens, $i, $case_opd_len, $stack);
                 $idx += $case_opd_len;
                 $case_else = false;
                 $case_nest = 1;
                 while (isset($tokens[$idx])) {
                   $case_expr = $tokens[$idx];
                   switch (strtolower($case_expr)) {
                     case 'then':
                         $stack[] = '?';
                         break;
                     case 'when':
                         $stack[] = ':';
                         $stack[] = $open;
                         $stack[] = $case_opd;
                         $stack[] = '=';
                         $case_nest++;
                         break;
                     case 'else':
                         $stack[] = ':';
                         $case_else = true;
                         break;
                     case 'end':
                         if (!$case_else) {
                           $stack[] = ':';
                           $stack[] = 'null';
                         }
                         while (--$case_nest >= 0) {
                           $stack[] = $close;
                         }
                         $idx++;
                         break 2;
                     default:
                         $stack[] = $case_expr;
                         break;
                   }
                   $idx++;
                 }
               }
             } else {
               // -------------------------------------
               // CASE WHEN condition THEN value
               //     [WHEN ...]
               //     [ELSE result]
               // END
               // -------------------------------------
               // ( condition ? value : (...) result )
               // -------------------------------------
               $stack = array($open);
               array_splice($tokens, $i, 2, $stack);
               $idx = $i + 1;
               $case_else = false;
               $case_nest = 1;
               while (isset($tokens[$idx])) {
                 $case_expr = $tokens[$idx];
                 switch (strtolower($case_expr)) {
                   case 'then':
                       $stack[] = '?';
                       break;
                   case 'when':
                       $stack[] = ':';
                       $stack[] = $open;
                       $case_nest++;
                       break;
                   case 'else':
                       $stack[] = ':';
                       $case_else = true;
                       break;
                   case 'end':
                       if (!$case_else) {
                         $stack[] = ':';
                         $stack[] = 'null';
                       }
                       while (--$case_nest >= 0) {
                         $stack[] = $close;
                       }
                       $idx++;
                       break 2;
                   default:
                       $stack[] = $case_expr;
                       break;
                 }
                 $idx++;
               }
             }
             $stack = $this->toOneArray($stack);
             $case_check = array_count_values($stack);
             if (isset($case_check[$open], $case_check[$close])
              && $case_check[$open] === $case_check[$close]) {
               $this->_caseCount  = $this->getValue($case_check, '?', 0);
               $this->_caseCount += $this->getValue($case_check, ':', 0);
               array_splice($tokens, $i, $idx - $i, $stack);
             } else {
               $this->pushError('illegal syntax, expect (CASE, WHEN, END)');
               $is_error = true;
             }
           }
           break;
       case 'in':
           // ----------------------------------------------------
           // expression IN (value [, ...])
           // ----------------------------------------------------
           //        eq
           // ----------------------------------------------------
           // expression = value1 OR expression = value2 OR ...
           // ----------------------------------------------------
           if ($prev == null || $next == null || $next !== $open) {
             $this->pushError('illegal syntax, expect(IN)');
             $is_error = true;
             break;
           }
           // --------------------------------------------------------
           // expression NOT IN (value [, ...])
           // --------------------------------------------------------
           //        eq
           // --------------------------------------------------------
           // (expression <> value1 AND expression <> value2 AND ...)
           // --------------------------------------------------------
           if ($prev === '!' || strtolower($prev) === 'not') {
             $this->_notInOp = true;
             array_splice($tokens, $h, 1);
             $i = $h - 1;
             break;
           }
           if (empty($this->_notInOp)) {
             $in_opr = '=';
             $in_cnd = 'or';
           } else {
             $in_opr = '<>';
             $in_cnd = 'and';
           }
           $this->_notInOp = null;
           $in_expr = $this->getLeftOperandBySQL($tokens, $h, $token);
           if ($in_expr === false) {
             $is_error = true;
             break;
           }
           array_splice($tokens, $i, 2);
           $idx = $i;
           $nest = 0;
           $in_count = 0;
           $stack = array($open, $in_expr, $in_opr);
           while (isset($tokens[$idx])) {
             $in_op = $tokens[$idx];
             switch ($in_op) {
               case '(':
                   $nest++;
                   $stack[] = $in_op;
                   break;
               case ')':
                   $nest--;
                   $stack[] = $in_op;
                   if ($nest === -1) {
                     $idx++;
                     $in_count++;
                     break 2;
                   }
                   break;
               case ',':
                   if ($nest === 0) {
                     $stack[] = $in_cnd;
                     $stack[] = $in_expr;
                     $stack[] = $in_opr;
                     $in_count += 3;
                     break;
                   }
               default:
                   $stack[] = $in_op;
                   break;
             }
             $in_count++;
             $idx++;
           }
           end($stack);
           if (prev($stack) === $in_opr) {
             $this->pushError('illegal syntax, expect IN at end(%s)',
                              $in_opr);
             $is_error = true;
             break;
           }
           reset($stack);
           $stack = $this->toOneArray($stack);
           array_splice($tokens, $i, $idx - $i, $stack);
           unset($stack);
           break;
       case 'exists':
           // ----------------------------------------------------
           // EXISTS (sub-query)
           // ----------------------------------------------------
           // NOT EXISTS (sub-query)
           // ----------------------------------------------------
           if ($next !== $open) {
             $this->pushError('illegal syntax, expect(EXISTS)');
             $is_error = true;
             break;
           }
           break;
       case 'any':
       case 'some':
       case 'all':
           // ----------------------------------------------------
           // SQL-92 Syntax
           // ----------------------------------------------------
           // expression operator { ANY | SOME } (sub-query)
           // ----------------------------------------------------
           // expr operator ANY (value1, value2, ...)
           // ----------------------------------------------------
           //        eq
           // ----------------------------------------------------
           // expr operator value1 OR expr operator value2 OR ...
           // ----------------------------------------------------
           if ($prev == null || $next == null || $next !== $open) {
             $this->pushError('illegal syntax, expect(%s)', $token);
             $is_error = true;
             break;
           }
           // ----------------------------------------------------
           // expression operator ALL (sub-query)
           // ----------------------------------------------------
           // expr operator ALL (value1, value2, ...)
           // ----------------------------------------------------
           //        eq
           // ----------------------------------------------------
           // expr operator value1 AND expr operator value2 AND...
           // ----------------------------------------------------
           $sub_opr = $prev;
           if (0 === strcasecmp($token, 'all')) {
             $sub_cnd = 'and';
           } else {
             $sub_cnd = 'or';
           }
           $sub_expr = $this->getLeftOperandBySQL($tokens, $g, $token);
           if ($sub_expr === false) {
             $is_error = true;
             break;
           }
           array_splice($tokens, $h, 2);
           $idx = $i;
           $nest = 0;
           $sub_count = 0;
           $stack = array($open, $sub_expr, $sub_opr);
           while (isset($tokens[$idx])) {
             $sub_op = $tokens[$idx];
             switch ($sub_op) {
               case '(':
                   $nest++;
                   $stack[] = $sub_op;
                   break;
               case ')':
                   $nest--;
                   $stack[] = $sub_op;
                   if ($nest === -1) {
                     $idx++;
                     $sub_count++;
                     break 2;
                   }
                   break;
               case ',':
                   if ($nest === 0) {
                     $stack[] = $sub_cnd;
                     $stack[] = $sub_expr;
                     $stack[] = $sub_opr;
                     $sub_count += 3;
                     break;
                   }
               default:
                   $stack[] = $sub_op;
                   break;
             }
             $sub_count++;
             $idx++;
           }
           end($stack);
           if (prev($stack) === $sub_opr) {
             $this->pushError('illegal syntax, expect (%s) at end(%s)',
                              $token, $sub_opr);
             $is_error = true;
             break;
           }
           reset($stack);
           $stack = $this->toOneArray($stack);
           array_splice($tokens, $i - 1, $idx - $i + 1, $stack);
           unset($stack);
           break;
       case 'like':
           // ----------------------------------------------------
           // string LIKE pattern [ESCAPE escape-character]
           // ----------------------------------------------------
           // string NOT LIKE pattern [ESCAPE escape-character]
           // ----------------------------------------------------
           if ($prev == null || $next == null
            || !$this->isStringToken($next)) {
             $this->pushError('illegal syntax, expect LIKE(%s)', $next);
             $is_error = true;
             break;
           } else {
             $pattern = $next;
             $next = '';
             $org_pattern = $pattern;
             if (isset($tokens[$g]) && $tokens[$g] != null
              && $prev === '!' || strtolower($prev) === 'not') {
               $subject = $this->getLeftOperandBySQL($tokens, $g, $token);
               $prev = '!';
             } else {
               $subject = $this->getLeftOperandBySQL($tokens, $h, $token);
             }
             if ($subject === false) {
               $is_error = true;
               break;
             }
             if (isset($tokens[$k], $tokens[$l])
              && strtolower($tokens[$k]) === 'escape') {
               if (!$this->isStringToken($tokens[$l])) {
                 $this->pushError('illegal syntax, expect LIKE, ESCAPE(%s)',
                                  $tokens[$l]);
                 $is_error = true;
                 break;
               }
               $escape = $tokens[$l];
               $tokens[$k] = '';
               $tokens[$l] = '';
             } else {
               $escape = null;
             }
             $test = $this->toLikePattern($pattern, $escape) . 'A';
             if (@preg_match($test, '') === false) {
               $this->pushError('illegal pattern on LIKE(%s)',
                                $org_pattern);
               $is_error = true;
               break;
             }
             $pattern = $this->toStringToken($pattern);
             unset($test, $org_pattern);
             $like_func = strtoupper($token);
             if ($escape === null) {
               $like_tokens = array(
                 $like_func, '(',
                   $pattern, ',',
                   $subject,
                 ')'
               );
             } else {
               $like_tokens = array(
                 $like_func, '(',
                   $pattern, ',',
                   $subject, ',',
                   $escape,
                 ')'
               );
             }
             $like_tokens = $this->toOneArray($like_tokens);
             array_splice($tokens, $i, 1, $like_tokens);
             unset($like_tokens, $escape, $pattern, $subject, $like_func);
           }
           break;
       case 'between':
           // ========================
           // a BETWEEN x AND y
           // ------------------
           //       eq
           // ------------------
           // a >= x AND a <= y
           // ========================
           // a NOT BETWEEN x AND y
           // ------------------
           //       eq
           // ------------------
           // a < x OR a > y
           // ------------------------
           if ($prev != null && $next != null
            && isset($tokens[$k], $tokens[$l])
            && ($tokens[$k] === '&&'
            || strtolower($tokens[$k]) === 'and')) {
             if ($prev === '!' || strtolower($prev) === 'not') {
               if (!isset($tokens[$g])) {
                 $this->pushError('illegal syntax, expect(BETWEEN)');
                 $is_error = true;
                 break;
               }
               $opd = $this->getLeftOperandBySQL($tokens, $g, $token);
               if (is_array($opd)) {
                 $betweens = array(
                   '(',
                   $opd, '<', $next,
                   'or',
                   $opd, '>', $tokens[$l],
                   ')'
                 );
                 $betweens = $this->toOneArray($betweens);
                 $org = array_splice($tokens, $g, 6, $betweens);
               }
             } else {
               $opd = $this->getLeftOperandBySQL($tokens, $h, $token);
               if (is_array($opd)) {
                 $betweens = array(
                   '(',
                   $opd, '>=', $next,
                   'and',
                   $opd, '<=', $tokens[$l],
                   ')'
                 );
                 $betweens = $this->toOneArray($betweens);
                 $org = array_splice($tokens, $h, 5, $betweens);
               }
             }
             if (isset($opd) && $opd === false) {
               $this->pushError('illegal syntax,'
                             .  ' expect the operand on BETWEEN');
               $is_error = true;
             }
             //$i += count($betweens) - count($org) + 3;
             unset($betweens, $org, $opd);
           } else {
             $this->pushError('illegal syntax, expect(BETWEEN)');
             $is_error = true;
           }
           break;
       case '<<=':
       case '>>=':
       case '.=':
       case '^=':
       case '&=':
       case '|=':
       case '/=':
       case '%=':
       case '*=':
       case '+=':
       case '-=':
       case '++':
       case '--':
           $this->pushError('The assignment operator(%s)'
                         .  ' is not supported', $token);
           $is_error = true;
           break;
       case '!!=':
       case '!~~':
       case '!~*':
       case '!~':
       case '~~':
       case '~*':
       case '<<<':
       case '>>>':
       case '=>':
       case '->':
       case '::':
       case '@':
       case '\\':
       case '{':
       case '}':
           $this->pushError('Not supported SQL syntax,'
                         .  ' expect (%s)', $token);
           $is_error = true;
           break;
       case '[':
       case ']':
           if (empty($this->_onJoinParseExpr)) {
             $this->pushError('Not supported SQL syntax,'
                         .  ' expect (%s)', $token);
             $is_error = true;
           }
           break;
       case '?':
       case ':':
           if (isset($this->_caseCount) && is_int($this->_caseCount)) {
             if (--$this->_caseCount < 0) {
               $this->pushError('illegal syntax, expect (CASE, WHEN, END)');
               $is_error = true;
             }
           }
           break;
       case '`':
           $token = '';
           break;
       case '$':
           if ($next == null || substr($next, 0, 1) === '$'
            || !$this->isWord($next) || substr($prev, -1) === '$') {
             $token = '';
           } else if ($next === '{'
                   || $next === '}') {
             $this->pushError('Invalid SQL syntax, expect(%s%s)',
                              $token, $next);
             $is_error = true;
           }
           break;
       case '(':
           $level++;
           if ($next != null && $this->isWord($next)
            && isset($tokens[$k]) && $tokens[$k] === $close) {
             $cast_token = strtolower($next);
             switch ($cast_token) {
               case 'int':
               case 'integer':
               case 'bool':
               case 'boolean':
               case 'float':
               case 'double':
               case 'real':
               case 'string':
                   $next = $cast_token;
                   break;
               case 'binary':
               case 'unset':
               case 'array':
               case 'object':
               default:
                   if ($this->isWord($cast_token)
                    && strpos($cast_token, '.') === false) {
                     $meta = $this->getMetaAssoc();
                     if (is_array($meta)) {
                       if (!array_key_exists($cast_token, $meta)) {
                         $this->pushError('Not supported SQL syntax,'
                                       .  ' expect(%s)', $cast_token);
                         $is_error = true;
                       }
                     }
                   }
                   break;
             }
             unset($cast_token);
           }
           break;
       case ')':
           $level--;
           break;
       case '=':
           $token = '==';
           break;
       case '||':
           $token = '.';
           break;
       case 'and':
           $token = '&&';
           break;
       case 'or':
           $token = '||';
           break;
       case 'xor':
           break;
       case 'div':
           if ($next !== $open) {
             $token = '/';
           }
           break;
       case 'mod':
           if ($next !== $open) {
             $token = '%';
           }
           break;
       case '.':
           if ($prev != null && $next != null
            && ($this->isStringToken($prev) || $this->isWord($prev))
            && ($this->isStringToken($next) || $this->isWord($next))) {
             $token = $token;
           } else {
             $this->pushError('illegal syntax, expect (%s)', $token);
             $is_error = true;
           }
           break;
       case '<=>':
           $token = '===';
           break;
       case '<?':
       case '<%':
           $error_token = 'T_OPEN_TAG';
       case '?>':
       case '%>':
           if (empty($error_token)) {
             $error_token = 'T_CLOSE_TAG';
           }
           $this->pushError('Invalid SQL syntax, expect %s', $error_token);
           $is_error = true;
           break;
       case '!==':
       case '===':
       case '!=':
       case '==':
       case '&&':
       case '<=':
       case '>=':
       case '<>':
       case '>>':
       case '<<':
       case ',':
       case ';':
       case '<':
       case '>':
       case '^':
       case '~':
       case '&':
       case '|':
       case '/':
       case '%':
       case '*':
       case '+':
       case '-':
           break;
       default:
           if ($token != null) {
             if ($token === 0 || is_numeric($token)) {
               if ($token == 0) {
                 $token = '0';
               } else {
                 $token_chr = substr($token, 0, 1);
                 $token_num = substr($token, 1);
                 if (strlen($token_num) && is_numeric($token_num)
                  && ($token_chr === '+' || $token_chr === '-')) {
                   if ($prev != null && $this->isStringToken($prev)) {
                     $prev_org = substr($prev, 1, -1);
                     if (is_numeric($prev_org)) {
                       array_splice($tokens, $i, 0, $token_chr);
                       $token = $token_num;
                     }
                   } else if ($prev != null) {
                     $prev_last = substr($prev, -1);
                     if ($prev_last !== $token_chr) {
                       array_splice($tokens, $i, 0, $token_chr);
                       $token = $token_num;
                     }
                   }
                 }
                 $token = $this->math->format($token);
               }
               $token = $this->toStringToken($token);
               break;
             } else if ($this->isStringToken($token)) {
               $token = $this->toStringToken($token);
             }
             if ($this->isStringToken($prev)
              && $this->isStringToken($token)) {
               $token = $this->concatStringTokens($prev, $token);
               $prev = '';
             }
           }
           break;
     }
     unset($token, $prev, $next);
   }
   unset($i);
   return $is_error;
 }

/**
 * Parses the attributes of the given HTML element.
 *
 * @param  mixed    the attributes of target element
 * @return array    the parsed attributes as array
 * @access private
 */
 function parseHTMLAttributes($attrs = array()){
   $result = array();
   if (func_num_args() > 1) {
     $args = func_get_args();
     $args = array($args);
     $result = call_user_func(array(&$this, 'parseHTMLAttributes'), $args);
   } else {
     if (is_string($attrs)) {
       $attrs = array($attrs);
     }
     if (is_array($attrs)) {
       foreach ($attrs as $key => $val) {
         if (is_array($val)) {
           if (strtolower($key) === 'style') {
             $css = array();
             foreach ($val as $css_key => $css_val) {
               if (!is_array($css_val)) {
                 $css[] = sprintf('%s:%s', $css_key, $css_val);
               }
             }
             $val = implode(';', $css);
           } else {
             $val = $this->parseHTMLAttributes($val);
           }
         }
         if (is_scalar($val)) {
           if (is_string($key)) {
             $char = substr($val, 0, 1);
             if (($char === '"' || $char === '\'')
              && substr($val, -1) === $char) {
               $format = '%s=%s';
             } else {
               $format = '%s="%s"';
             }
             $val = sprintf($format, $key, $val);
           }
         }
         foreach ((array)$val as $v) {
           $result[] = $v;
         }
       }
       $result = array_filter($result);
       $result = array_unique($result);
       $result = array_values($result);
     }
   }
   return $result;
 }

/**
 * Parse CSV (Comma-Separated Values) from file
 *
 * @link http://www.ietf.org/rfc/rfc4180.txt
 *
 * @param  string   optionally, CSV filename
 * @return array    a parsed multiple dimension array
 * @access public
 */
 function parseCSV($filename = null){
   $result = array();
   if ($filename != null) {
     $org_path = $this->path;
     $this->setPath($filename);
   }
   $fp = $this->fopen('r');
   if ($fp) {
     while (!feof($fp)) {
       $fields = $this->fgetcsv($fp);
       if (is_array($fields)) {
         foreach ($fields as $i => $field) {
           $fields[$i] = $this->pcharset->convert($field, $this->charset);
         }
         $result[] = $fields;
       }
     }
     fclose($fp);
   }
   if (isset($org_path) && $org_path !== $this->path) {
     $this->setPath($org_path);
   }
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Builder
 *
 * This builder class that assign from tokens for Posql
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Builder extends Posql_Parser {

/**
 * Generates the pattern of the LIKE operator
 *
 * @param  string  the pattern which is given from the tokens
 * @param  string  optionally, escape character (default=backslash: \ )
 * @return string  the pattern which was generated as string
 * @access private
 */
 function toLikePattern($pattern, $escape = '\\'){
   static $patterns = array();
   $result = null;
   $hash = $this->hashValue($pattern . $escape);
   if (isset($patterns[$hash])) {
     $result = $patterns[$hash];
   } else {
     $mod = 'isu';
     $dlm = '~';
     $pre = '^';
     $suf = '$';
     $q   = '\'';
     if ($this->isStringToken($pattern)) {
       $pattern = substr($pattern, 1, -1);
     }
     if ($this->isStringToken($escape)) {
       $escape = substr($escape, 1, -1);
     }
     if ($pattern == null) {
       $pattern = '(?:)';
     } else {
       $default_escape = '\\';
       if ($escape === null) {
         $escape = $default_escape;
       }
       if ($escape !== $default_escape) {
         $pattern = str_replace($escape, $default_escape, $pattern);
       }
       $pattern = preg_quote($pattern, $dlm);
       $translates = array(
         '\\\\\\\\' => '\\\\',
         '\"'       => '"',
         '\''       => '\\\'',
         '\%'       => '%',
         '\_'       => '_',
         '%'        => '.*',
         '_'        => '.'
       );
       $pattern = strtr($pattern, $translates);
       /*
       $pattern = strtr($pattern,
         array(
           '\"' => '"',
           '\'' => '\\\'',
           '\%' => '%',
           '\_' => '_',
           '%'  => '.*',
           '_'  => '.'
         )
       );
       */
     }
     $result = $dlm . $pre . $pattern . $suf . $dlm . $mod;
     $patterns[$hash] = $result;
   }
   return $result;
 }

/**
 * Convert the statement object into array
 *
 * @param  mixed   the statement object, or result-set as array
 * @return string  result-set which converted into array forcibly
 * @access public
 */
 function toArrayResult($stmt){
   $result = array();
   if (is_array($stmt)) {
     $result = $stmt;
   } else {
     if (is_object($stmt)) {
       if (method_exists($stmt, 'fetchAll')) {
         $result = $stmt->fetchAll('assoc');
       } else if (isset($stmt->rows) && is_array($stmt->rows)) {
         $result = $stmt->rows;
       } else {
         $result = (array)$stmt;
       }
     }
   }
   $stmt = null;
   unset($stmt);
   return $result;
 }

/**
 * Correlate the string which delimited by the dot(.)
 *
 * @param  string  the left correlation name
 * @param  string  optionally, the right correlation name
 * @return string  the unique internal correlation name
 * @access private
 */
 function toCorrelationName($left, $right = null){
   $result = null;
   $checked = false;
   $enable_left = false;
   $enable_right = false;
   if (is_string($left)) {
     if ($right === null) {
       if (strpos($left, '.') !== false) {
         if (empty($this->_onHaving)) {
           list($left, $right) = explode('.', $left);
         } else {
           list($left_side, $right_side) = explode('.', $left);
           $enable_left = $this->isEnableName($left_side);
           $enable_right = $this->isEnableName($right_side);
           $checked = true;
           if ($enable_left && $enable_right) {
             $left = $left_side;
             $right = $right_side;
           }
         }
       }
     }
     if ($left != null) {
       if ($right == null) {
         $result = $left;
       } else {
         if (!$checked) {
           $enable_left = $this->isEnableName($left);
           $enable_right = $this->isEnableName($right);
         }
         if (!$enable_right) {
           $result = $left;
         } else if (!$enable_left) {
           $result = $right;
         } else {
           if (empty($this->_correlationPrefix)) {
             $this->initCorrelationPrefix();
           }
           $result = sprintf('_%s_%s_%s',
             $this->_correlationPrefix,
             $left,
             $right
           );
         }
       }
     }
   }
   return $result;
 }

/**
 * Concatenate tokens of array with a string
 *
 * @param  array   the target tokens as array
 * @return string  concatenated string
 * @access public
 */
 function joinWords($tokens){
   static $cache = array(), $size = 0;
   $result = '';
   $hash = $this->hashValue($tokens);
   if (isset($cache[$hash])) {
     $result = $cache[$hash];
   } else {
     $tokens = array_values($tokens);
     foreach ($tokens as $i => $token) {
       $h = $i - 1;
       $j = $i + 1;
       $pre = substr($token, 0, 1);
       if ($this->isAlphaNum($pre) || $pre === '$') {
         if (isset($tokens[$h])) {
           $lastchar = substr($tokens[$h], -1);
           if ($this->isAlphaNum($lastchar)) {
             $tokens[$h] .= ' ';
           }
         }
       } else if ($token === '+' || $token === '-') {
         $tokens[$i] = ' ' . $token . ' ';
       }
     }
     $result = trim(implode('', $tokens));
     if ($size < 0x1000 && strlen($result) < 0x100) {
       $cache[$hash] = $result;
       $size++;
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function joinCrossTable(&$row1, &$row2, $row1name, $row2name, $expr = true){
   $result = array();
   $row1_count = count($row1);
   $row2_count = count($row2);
   if ($expr === true) {
     for ($i = 0; $i < $row2_count; $i++) {
       for ($j = 0; $j < $row1_count; $j++) {
         $result[] = $row2[$i] + $row1[$j];
       }
     }
   } else {
     $this->fixJoinCrossExpr($expr, $row1name, $row2name);
     $arg = array();
     $arg[$row1name] = array();
     $arg[$row2name] = array();
     $arg_row1name = & $arg[$row1name];
     $arg_row2name = & $arg[$row2name];
     $first = true;
     for ($i = 0; $i < $row2_count; $i++) {
       $arg_row2name = $row2[$i];
       for ($j = 0; $j < $row1_count; $j++) {
         $arg_row1name = $row1[$j];
         if ($first) {
           if ($this->safeExpr($arg, $expr)) {
             $result[] = $row2[$i] + $row1[$j];
           }
           if ($this->hasError()) {
             $result = array();
             $row1 = array();
             $row2 = array();
             break 2;
           }
           $first = false;
         } else {
           if ($this->expr($arg, $expr)) {
             $result[] = $row2[$i] + $row1[$j];
           }
         }
       }
     }
     unset($arg_row1name, $arg_row2name);
   }
   return $result;
 }

/**
 * @access private
 */
 function fixJoinCrossExpr(&$expr, $row1name, $row2name){
   if ($expr != null && strpos($expr, $row1name) === false) {
     $row_keys = array(
       $row1name => 1,
       $row2name => 2
     );
     $tokens = $this->splitSyntax($expr);
     $tokens = array_values($tokens);
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       $token = $this->getValue($tokens, $i);
       $next = $this->getValue($tokens, $i + 1);
       switch ($token) {
         case '$':
             if ($next != null && !array_key_exists($next, $row_keys)) {
               $tokens[$i + 1] = $row1name;
               $i++;
             }
             break;
         default:
             break;
       }
     }
     $expr = $this->joinWords($tokens);
   }
 }

/**
 * @access private
 */
 function joinLeftTable(&$row1, &$row2, $row1name, $row2name,
                                $using, $isleft, $expr = true){
   $result = array();
   $expr = $this->optimizeExpr($expr);
   if ($expr === false) {
     if (!empty($row1) && is_array($row1) && is_array(reset($row1))) {
       $keys = array_keys(reset($row1));
       for ($i = 0, $c = count($row1); $i < $c; $i++) {
         foreach ($keys as $key) {
           $result[$i][$key] = null;
         }
       }
     }
   } else {
     if ($isleft) {
       $cross = $this->joinCrossTable($row1, $row2,
                              $row1name, $row2name, $expr);
     } else {
       $cross = $this->joinCrossTable($row2, $row1,
                              $row2name, $row1name, $expr);
     }
     if ($expr === true) {
       $result = array_splice($cross, 0);
     } else {
       if (!empty($row1) && is_array($row1)
        && !empty($row2) && is_array($row2)) {
         $row1_count  = count($row1);
         $row2_count  = count($row2);
         $cross_count = count($cross);
         $max_count   = $cross_count;
         if ($max_count < $row1_count) {
           $max_count = $row1_count;
         }
         $cross_keys = array();
         if (isset($cross[0]) && is_array($cross[0])) {
           foreach ($cross[0] as $key => $val) {
             $cross_keys[$key] = null;
           }
         }
         $reset = reset($row1);
         if (!is_array($reset)) {
           $this->pushError('illegal record(%s) on JOIN', $reset);
         } else {
           $row1_keys = array_keys($reset);
           $reset = reset($row2);
           if (!is_array($reset)) {
             $this->pushError('illegal record(%s) on JOIN', $reset);
           } else {
             $row2_keys = array_keys($reset);
             $arg = array();
             if (!empty($using)) {
               if (!is_array($using)) {
                 $using = array();
               } else {
                 $using = array_flip(array_filter($using, 'is_string'));
               }
             }
             if (empty($cross_keys)) {
               $join_keys = array_merge($row1_keys, $row2_keys);
               $join_keys = array_unique($join_keys);
             } else {
               $join_keys = array_keys($cross_keys);
             }
             for ($i = 0, $j = 0; $i < $max_count; $i++) {
               $new_row = array();
               if ($i < $cross_count) {
                 $new_row = $cross[$j];
                 $cross[$j] = null;
                 $j++;
               } else {
                 $row = $row1[$i];
                 $diff = $cross_keys;
                 if (isset($row2[$i])) {
                   $diff = $row2[$i];
                 }
                 if (empty($using)) {
                   $pad = $row;
                   foreach ($row2_keys as $key) {
                     if (array_key_exists($key, $pad)) {
                       $pad[$key] = null;
                     }
                   }
                   //TODO(?): Contradiction of the order of column
                   $new_row = $pad + $diff;
                 } else {
                   $new_row = $row + $diff;
                 }
               }
               $join_row = array();
               foreach ($join_keys as $key) {
                 if (array_key_exists($key, $new_row)) {
                   $join_row[$key] = $new_row[$key];
                 } else {
                   $join_row[$key] = null;
                 }
               }
               $result[] = $join_row;
             }
           }
         }
       }
     }
     unset($cross);
   }
   return $result;
 }

/**
 * @access private
 */
 function appendCorrelationPairs(&$rows, $table_name){
   if (is_array($rows)) {
     $row_count = count($rows);
     for ($i = 0; $i < $row_count; $i++) {
       foreach ($rows[$i] as $col => $val) {
         $column_name = sprintf('%s.%s', $table_name, $col);
         $rows[$i][$column_name] = $val;
       }
     }
   }
 }

/**
 * @return void
 * @access private
 */
 function joinTables(&$rows, $info = array(), $expr = true){
   $length = count($rows);
   if ($length < 1 || $length !== count($info)) {
     /*
     $this->pushError('The numbers of tables'
                    . ' and rows are discrepancy on JOIN');
     */
     $rows = array();
   } else {
     $info = array_values($info);
     for ($i = 0; $i < $length; $i++) {
       $table_name = $this->getValue($info[$i], 'table_name');
       $table_as   = $this->getValue($info[$i], 'table_as');
       $join_type  = $this->getValue($info[$i], 'join_type', 'undefined');
       $join_expr  = $this->getValue($info[$i], 'join_expr', false);
       $using      = $this->getValue($info[$i], 'using', array());
       $tname = $this->encodeKey($table_name);
       if (!isset($rows[$tname])) {
         $this->pushError('Table is not exists(%s)', $table_name);
         $rows = array();
         break;
       }
       if ($this->hasError()) {
         $rows = array();
         break;
       }
       if ($i === 0) {
         $base_row        = $rows[$tname];
         $base_row_name   = $table_name;
         $base_count      = count($base_row);
         $this->tableName = $tname;
         $this->appendCorrelationPairs($base_row, $base_row_name);
         continue;
       } else {
         $row = $rows[$tname];
         $this->appendCorrelationPairs($row, $table_name);
       }
       $join_type = strtolower($join_type);
       switch ($join_type) {
         case 'cross':
         case 'inner':
         case 'join':
             $base_row = $this->joinCrossTable($base_row, $row,
                           $base_row_name, $table_name, $join_expr);
             break;
         case 'full':
         case 'left':
         case 'natural':
             $base_row = $this->joinLeftTable($base_row, $row,
                   $base_row_name, $table_name, $using, true, $join_expr);
             if ($join_type !== 'full') {
               break;
             }
         case 'right':
             $base_row = $this->joinLeftTable($row, $base_row,
                   $table_name, $base_row_name, $using, false, $join_expr);
             break;
         default:
             $this->pushError('Not supported JOIN type(%s)', $join_type);
             $rows = array();
             break 2;
       }
     }
     if (!empty($base_row)) {
       $tmp = $base_row;
       unset($base_row);
       $rows = $tmp;
       unset($tmp);
     }
     unset($base_row, $tmp);
     $this->applyJoinExpr($rows, $expr);
   }
 }

/**
 * @access private
 */
 function applyJoinExpr(&$rows, $expr = true){
   if ($this->hasError()) {
     $rows = array();
   } else if ($expr !== true) {
     if (!empty($rows) && is_array($rows)) {
       if (!empty($this->_extraJoinExpr)
        && is_string($expr) && $expr != null) {
         $extra_expr = $this->_extraJoinExpr;
         if (is_array($extra_expr)) {
           $extra_expr = $this->toOneArray($extra_expr);
           $extra_expr = $this->joinWords($extra_expr);
           $this->removeValidMarks($expr);
           if (strpos($expr, $extra_expr) === 0) {
             $expr = substr($expr, strlen($extra_expr));
             $expr = trim($expr, '&|');
           }
           $this->addParsedMark($expr);
           $this->addValidMarks($expr);
         }
       }
       $this->_extraJoinExpr = array();
       $rows = array_values($rows);
       $length = count($rows);
       $first = true;
       for ($i = 0; $i < $length; $i++) {
         $valid_row = array();
         foreach ($rows[$i] as $key => $val) {
           $valid_row[$key] = $val;
           $left_key = $this->toCorrelationName($key);
           $valid_row[$left_key] = $val;
         }
         if ($first) {
           $first = false;
           if (!$this->safeExpr($valid_row, $expr)) {
             unset($rows[$i]);
           }
           if ($this->hasError()) {
             $rows = array();
             break;
           }
         } else {
           if (!$this->expr($valid_row, $expr)) {
             unset($rows[$i]);
           }
         }
       }
       $rows = array_values($rows);
     }
   }
 }

/**
 * Builds the parsed tokens, and passes each methods
 *
 * @param  array   parsed tokens
 * @param  string  the type of query e.g. "select"
 * @return mixed   results of executed, or false on error
 * @access private
 */
 function buildQuery($parsed, $type){
   $result = false;
   $this->_useQuery = true;
   switch (strtolower($type)) {
     case 'select':
         $result = $this->buildSelectQuery($parsed);
         break;
     case 'update':
         $result = $this->buildUpdateQuery($parsed);
         break;
     case 'delete':
         $result = $this->buildDeleteQuery($parsed);
         break;
     case 'insert':
     case 'replace':
         $result = $this->buildInsertQuery($parsed);
         break;
     case 'create':
         $result = $this->buildCreateQuery($parsed);
         break;
     case 'drop':
         $result = $this->buildDropQuery($parsed);
         break;
     case 'vacuum':
         $result = $this->vacuum();
         break;
     case 'describe':
     case 'desc':
         $result = $this->buildDescribeQuery($parsed);
         break;
     case 'begin':
     case 'start':
     case 'commit':
     case 'end':
     case 'rollback':
         $result = $this->transaction($type);
         break;
     case 'alter':
     default:
         $this->pushError('Not supported SQL syntax(%s)', $type);
         break;
   }
   $this->_useQuery = null;
   return $result;
 }

/**
 * Builds the parsed tokens for CREATE TABLE/DATABASE
 *
 * @param  array   parsed tokens
 * @return mixed   results of executed, or false on error
 * @access private
 */
 function buildCreateQuery($parsed){
   $result = false;
   $args = array('table'    => array('table'    => null, 'fields' => array()),
                 'database' => array('database' => null));
   $type = null;
   if (isset($parsed['table'])) {
     $type = 'table';
   } else if (isset($parsed['database'])) {
     $type = 'database';
   }
   switch ($type) {
     case 'table':
         $args = $this->parseCreateTableQuery($parsed[$type], true);
         if (!$this->hasError()) {
           if (is_array($args)
            && isset($args['table'], $args['fields'])) {
             $primary = $this->getValue($args, 'primary');
             $result = $this->createTable($args['table'],
                                          $args['fields'], $primary);
           }
         }
         break;
     case 'database':
         $path = $this->parseCreateDatabaseQuery($parsed[$type]);
         if (!$this->hasError() && $path != null) {
           $result = $this->createDatabase($path);
         }
         break;
     default:
         $this->pushError('Not supported SQL syntax(%s)', $type);
         break;
   }
   return $result;
 }

/**
 * Builds the parsed tokens for DROP TABLE/DATABASE
 *
 * @param  array   parsed tokens
 * @return mixed   results of executed, or false on error
 * @access private
 */
 function buildDropQuery($parsed){
   $result = false;
   $args = array('table'    => array('table'    => null),
                 'database' => array('database' => null));
   $type = null;
   if (isset($parsed['table'])) {
     $type = 'table';
   } else if (isset($parsed['database'])) {
     $type = 'database';
   }
   switch ($type) {
     case 'table':
         $table = $this->parseDropQuery($parsed[$type]);
         if (!$this->hasError()) {
           if (is_string($table) && $table != null) {
             $result = $this->dropTable($table);
           }
         }
         break;
     case 'database':
         $dbname = $this->parseDropQuery($parsed[$type]);
         if (!$this->hasError()) {
           if (is_string($dbname) && $dbname != null) {
             $result = $this->dropDatabase($dbname);
           }
         }
         break;
     default:
         $this->pushError('Not supported SQL syntax(%s)', $type);
         break;
   }
   return $result;
 }

/**
 * Builds the parsed tokens for INSERT
 *
 * @param  array   parsed tokens
 * @return mixed   results of executed, or false on error
 * @access private
 */
 function buildInsertQuery($parsed){
   $result = false;
   $args = array('table' => null, 'rows' => array());
   $cols = array();
   ksort($parsed);
   foreach ($parsed as $key => $val) {
     switch ($key) {
       case 'into':
           $args['table'] = $this->assignNames($val);
           break;
       case 'cols':
           $cols = $val;
           break;
       case 'values':
           $values = array('cols'   => $cols,
                           'values' => $val);
           $args['rows'] = $this->parseInsertValues($values);
           $this->_execExpr = true;
           break;
       case 'select':
           if (is_array($val)) {
             array_unshift($val, $key);
           }
           $args['rows'] = $this->buildInsertSelectSubQuery($val, $cols);
           break;
       case 'set':
       default:
           $this->pushError('Not supported SQL syntax(%s)', $key);
           break;
     }
   }
   //debug($args,'color=blue:***insert***;');
   if (!$this->hasError()) {
     switch ($this->lastMethod) {
       case 'insert':
           $result = $this->insert($args['table'], $args['rows']);
           break;
       case 'replace':
           $result = $this->replace($args['table'], $args['rows']);
           break;
       default:
           $this->pushError('Not supported SQL syntax(%s)', $this->lastMethod);
           break;
     }
   }
   $this->_execExpr = false;
   return $result;
 }

/**
 * Builds the parsed tokens for INSERT-SELECT sub-query
 *
 * @param  array   parsed tokens as SELECT clause
 * @param  array   parsed tokens as columns-list
 * @return array   array which was executed as SELECT sub-query
 * @access private
 */
 function buildInsertSelectSubQuery($select, $cols){
   $result = array();
   if (!$this->hasError()) {
     if (empty($cols) || !is_array($cols)) {
       $this->pushError('Cannot execute sub-query. expect columns-list');
     } else if (empty($select) || !is_array($select)) {
       $this->pushError('Cannot execute sub-query. expect SELECT clause');
     } else {
       $values = array('cols'   => $cols,
                       'values' => $cols);
       $columns = $this->parseInsertValues($values);
       if (!$this->hasError()) {
         if (is_array($columns) && reset($columns) === key($columns)) {
           $columns = array_values($columns);
           $parsed_select = $this->parseSelectQuery($select);
           if (!$this->hasError() && is_array($parsed_select)) {
             $stmt = $this->buildSelectQuery($parsed_select);
             if (!$this->hasError()) {
               $rows = null;
               if ($this->isStatementObject($stmt)) {
                 $rows = $stmt->fetchAll('assoc');
               } else if (is_array($stmt) && $this->isAssoc(reset($stmt))) {
                 $rows = $stmt;
               } else {
                 $this->pushError('Failed to get records on INSERT-SELECT');
               }
               if (!$this->hasError() && is_array($rows)) {
                 $rows_count = count($rows);
                 for ($i = 0; $i < $rows_count; $i++) {
                   $new_row = array();
                   $values = array_values($rows[$i]);
                   foreach ($columns as $index => $column) {
                     if (array_key_exists($index, $values)) {
                       $new_row[$column] = $values[$index];
                     }
                   }
                   if (empty($new_row)) {
                     $this->pushError('Column count does not match'
                                   .  ' value count on INSERT-SELECT');
                     $rows = array();
                     break;
                   }
                   $rows[$i] = $new_row;
                 }
                 $result = array_splice($rows, 0);
                 unset($rows);
               }
             }
             $stmt = null;
             unset($stmt);
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Builds the parsed tokens for DELETE
 *
 * @param  array   parsed tokens
 * @return mixed   results of executed, or false on error
 * @access private
 */
 function buildDeleteQuery($parsed){
   $result = false;
   $args = array('table' => null, 'expr' => false);
   foreach ($parsed as $key => $val) {
     switch ($key) {
       case 'from':
           $tablename = $this->assignNames($val);
           if ($this->getMeta($tablename)) {
             $this->tableName = $this->encodeKey($tablename);
           }
           $args['table'] = $tablename;
           break;
       case 'where':
           $args['expr'] = $this->validExpr($val);
           break;
       default:
           $this->pushError('Not supported SQL syntax(%s)', $key);
           break;
     }
   }
   if (!$this->hasError()) {
     $result = $this->delete($args['table'], $args['expr']);
   }
   return $result;
 }

/**
 * Builds the parsed tokens for UPDATE
 *
 * @param  array   parsed tokens
 * @return mixed   results of executed, or false on error
 * @access private
 */
 function buildUpdateQuery($parsed){
   $result = false;
   $args = array('table' => null, 'row' => array(), 'expr' => true);
   foreach ($parsed as $key => $val) {
     switch ($key) {
       case 'update':
           $tablename = $this->assignNames($val);
           if ($this->getMeta($tablename)) {
             $this->tableName = $this->encodeKey($tablename);
           }
           $args['table'] = $tablename;
           break;
       case 'where':
           $args['expr'] = $this->validExpr($val);
           break;
       case 'set':
           $args['row'] = $this->parseUpdateSet($val);
           $this->_execExpr = true;
           break;
       default:
           $this->pushError('Not supported SQL syntax(%s)', $key);
           break;
     }
   }
   if (!$this->hasError()) {
     $result = $this->update($args['table'], $args['row'], $args['expr']);
   }
   $this->_execExpr = false;
   return $result;
 }

/**
 * Builds the parsed tokens for SELECT
 *
 * @param  array   parsed tokens
 * @return mixed   results of executed, or false on error
 * @access private
 */
 function buildSelectQuery($parsed){
   $result = false;
   $this->_subSelectJoinInfo = array();
   $this->_subSelectJoinUniqueNames = array();
   $args = $this->getSelectDefaultArguments();
   $multi = false;
   $sub_select = false;
   $subsets = array();
   foreach ($parsed as $key => $val) {
     switch ($key) {
       case 'select':
           $args[$key] = $this->joinWords($val);
           break;
       case 'from':
           $tables = array();
           $from = $this->joinWords($val);
           $table_name = 'table_name';
           $sub_select = $this->isSubSelectFrom($from);
           if ($sub_select) {
             $this->tableName = '';
             $this->applyAliasNames($from, $args['select']);
             $aliases = $this->getTableAliasNames();
             if (is_array($aliases)) {
               $this->tableName = $this->encodeKey(reset($aliases));
             }
             if ($this->isMultipleSelect($from)) {
               $this->_subSelectJoinInfo = $this->parseJoinTables($val);
               if ($this->hasError()) {
                 break 2;
               } else if (empty($this->_subSelectJoinUniqueNames)) {
                 $this->pushError('Failed to compile on sub-query using JOIN');
                 break 2;
               }
               $args[$key] = $this->execMultiSubQueryFrom($from);
               $multi = true;
             } else {
               $args[$key] = $this->execSubQueryFrom($from);
             }
           } else {
             $this->tableName = $this->encodeKey($from);
             if ($this->isMultipleSelect($from)) {
               $tables = $this->parseJoinTables($val);
               if (isset($tables[0][$table_name])) {
                 $this->tableName = $this->encodeKey($tables[0][$table_name]);
               }
               $args[$key] = $tables;
               $multi = true;
             } else {
               $tables = $this->parseFromClause($val);
               if (isset($tables[$table_name])) {
                 $this->tableName = $this->encodeKey($tables[$table_name]);
                 $this->appendAliasToken($val);
                 $from = $this->joinWords($val);
               }
               $args[$key] = $from;
             }
           }
           unset($tables, $from);
           break;
       case 'where':
           if (!empty($this->tableName)
             && empty($this->meta[$this->tableName])) {
             $this->getMeta();
           }
           if ($multi) {
             $this->_onMultiSelect = true;
           }
           if (!$sub_select) {
             $this->applyAliasNames($args['from'], $args['select']);
           }
           $args[$key] = $this->validExpr($val);
           $this->_onMultiSelect = null;
           break;
       case 'group':
           $args[$key] = $this->joinWords($val);
           break;
       case 'having':
           $args[$key] = $this->joinWords($val);
           break;
       case 'order':
           $args[$key] = $this->parseOrderByTokens($val);
           break;
       case 'limit':
           $args[$key] = $this->parseLimitTokens($val);
           break;
       case 'union':
       case 'union_all':
       case 'intersect':
       case 'intersect_all':
       case 'except':
       case 'except_all':
           if (empty($val) || $val === array(array())) {
             $error = strtoupper(strtr($key, '_', ' '));
             $this->pushError('Syntax error, expect(%s)', $error);
           }
           $subsets[$key] = $val;
           break;
       default:
           $this->pushError('Not supported SQL syntax(%s)', $key);
           break 2;
     }
   }
   //debug($args,'color=blue:***buildSelectQuery***;');
   if ($this->hasError()) {
     $result = array();
   } else {
     if ($this->_fromSubSelect) {
       if ($multi) {
         $result = $this->multiSubSelect($args);
       } else {
         $result = $this->subSelect($args);
       }
     } else if ($args['from'] === null) {
       $result = $this->selectDual($args['select']);
     } else if ($multi) {
       $result = $this->multiSelect($args);
     } else {
       $result = $this->select($args);
     }
     if (!empty($subsets)) {
       $this->_unionColNames = array();
       $result = $this->buildSelectUnionQuery($result, $args, $subsets);
       $this->_unionColNames = array();
     }
   }
   $this->_subSelectJoinInfo = array();
   $this->_subSelectJoinUniqueNames = array();
   return $result;
 }

/**
 * Builds the parsed tokens for SELECT (using UNION: compound-operator)
 *
 * @param  array   parsed tokens as UNION clause same as SELECT tokens
 * @return mixed   executed result-set, or FALSE on error
 * @access private
 */
 function buildSelectUnionQuery($base_stmt, $args, $subsets){
   $result = array();
   if (!empty($subsets) && is_array($subsets)) {
     $rows = array();
     if (!$this->isStatementObject($base_stmt)) {
       if (!is_array($base_stmt)) {
         $this->pushError('Invalid result-set on SELECT (using UNION)');
       } else {
         $rows = $base_stmt;
       }
     } else {
       $rows = $base_stmt->fetchAll('assoc');
     }
     $base_stmt = null;
     unset($base_stmt);
     if (!$this->hasError()) {
       foreach ($subsets as $type => $subset) {
         foreach ($subset as $parsed_tokens) {
           $col_names = $this->getValue($args, 'select');
           if ($col_names != null) {
             $col_names = $this->parseColumns($col_names, 'columns');
           }
           if (is_array($col_names)) {
             foreach ($col_names as $col_name) {
               $this->_unionColNames[$col_name] = 1;
             }
           } else if (is_string($col_names)) {
             $this->_unionColNames[$col_names] = 1;
           }
           $this->_onUnionSelect = true;
           $stmt = $this->buildSelectQuery($parsed_tokens);
           $this->_onUnionSelect = null;
           if ($this->hasError()) {
             break;
           } else {
             if (!$this->isStatementObject($stmt)) {
               if (!is_array($stmt)) {
                 $this->pushError('Failed to compound rows on UNION');
                 break;
               } else {
                 $union_rows = $stmt;
               }
             } else {
               $union_rows = $stmt->fetchAll('assoc');
             }
             switch ($type) {
               case 'union':
                   $rows = $this->unionAll($rows, $union_rows, $col_names);
                   $this->applyDistinct($rows);
                   break;
               case 'union_all':
                   $rows = $this->unionAll($rows, $union_rows, $col_names);
                   break;
               case 'intersect':
                   $rows = $this->intersectAll($rows, $union_rows, $col_names);
                   $this->applyDistinct($rows);
                   break;
               case 'intersect_all':
                   $rows = $this->intersectAll($rows, $union_rows, $col_names);
                   break;
               case 'except':
                   $rows = $this->exceptAll($rows, $union_rows, $col_names);
                   $this->applyDistinct($rows);
                   break;
               case 'except_all':
                   $rows = $this->exceptAll($rows, $union_rows, $col_names);
                   break;
               default:
                   $this->pushError('Not supported syntax(%s)', $type);
                   break 2;
             }
           }
           $stmt = null;
           unset($stmt);
         }
         if ($this->hasError()) {
           break;
         }
       }
       if ($this->hasError()) {
         $rows = array();
       }
       $this->removeCorrelationName($rows);
       $args['from'] = $rows;
       $args['where'] = true;
       $this->_onUnionSelect = true;
       $result = $this->subSelect($args);
       $this->_onUnionSelect = null;
     }
   }
   if (is_array($result) && empty($this->_fromStatement)) {
     $result = new Posql_Statement($this, $result, $this->getLastQuery());
   }
   return $result;
 }

/**
 * Builds the parsed tokens for DESCRIBE command
 *
 * @param  array   parsed tokens
 * @return mixed   results of executed, or FALSE on error
 * @access private
 */
 function buildDescribeQuery($parsed){
   $result = false;
   $args = array('table' => null);
   foreach ($parsed as $key => $val) {
     switch ($key) {
       case 'desc':
       case 'describe':
           $table_name = $this->assignNames($val);
           $args['table'] = $table_name;
           break;
       default:
           $this->pushError('Not supported SQL syntax(%s)', $key);
           break;
     }
   }
   if ($this->hasError()) {
     $result = array();
   } else {
     $result = $this->describe($args['table']);
   }
   return $result;
 }

/**
 * Combine two query expressions into a single result set.
 * If ALL is specified, duplicate rows returned by
 * union expression are retained.
 *
 * @param  array   based rows
 * @param  array   subject rows to combine
 * @param  array   column names which is used for result-set
 * @access public
 */
 function unionAll($base_rows, $union_rows, $col_names = array()){
   $result = array();
   if (!is_array($base_rows) || !is_array($union_rows)) {
     $this->pushError('Invalid arguments on UNION');
   } else {
     if (empty($col_names) && is_array(reset($base_rows))) {
       $col_names = array_keys(reset($base_rows));
     }
     if (empty($col_names)) {
       $this->pushError('Failed to compound rows on UNION');
     } else {
       if (is_string($col_names)) {
         $col_names = array($col_names);
       }
       $col_length = count($col_names);
       $union_rows = array_values($union_rows);
       $union_row_count = count($union_rows);
       $key = 0;
       while (--$union_row_count >= 0) {
         $row = $union_rows[$key];
         $union_rows[$key] = null;
         $base_rows[] = $row;
         $key++;
       }
       unset($union_rows);
       $base_rows = array_values($base_rows);
       $base_row_count = count($base_rows);
       $key = 0;
       while (--$base_row_count >= 0) {
         $row = $base_rows[$key];
         $base_rows[$key] = null;
         $subset_row = array();
         if (is_array($row) && count($row) === $col_length) {
           foreach (array_values($row) as $i => $val) {
             $subset_row[ $col_names[$i] ] = $val;
           }
           $result[] = $subset_row;
         } else {
           $this->pushError('In UNION, two rows should be the same numbers');
           $result = array();
           break;
         }
         $key++;
       }
     }
   }
   return $result;
 }

/**
 * Evaluates the output of two query expressions and
 * returns only the rows common to each.
 *
 * @param  array   based rows
 * @param  array   subject rows to intersected
 * @param  array   column names which is used for result-set
 * @access public
 */
 function intersectAll($base_rows, $intersect_rows, $col_names = array()){
   $result = array();
   if (!is_array($base_rows) || !is_array($intersect_rows)) {
     $this->pushError('Invalid arguments on INTERSECT');
   } else {
     if (empty($col_names) && is_array(reset($base_rows))) {
       $col_names = array_keys(reset($base_rows));
     }
     if (empty($col_names)) {
       $this->pushError('Failed to compound rows on INTERSECT');
     } else {
       if (is_string($col_names)) {
         $col_names = array($col_names);
       }
       $col_length = count($col_names);
       $base_row_count   = count($base_rows);
       $intersect_row_count = count($intersect_rows);
       for ($i = 0; $i < $base_row_count; $i++) {
         $subset_row = array();
         if (isset($base_rows[$i]) && is_array($base_rows[$i])
          && count($base_rows[$i]) === $col_length) {
           $index = -1;
           $base_values = array_values($base_rows[$i]);
           for ($j = 0; $j < $intersect_row_count; $j++) {
             if ($base_values === array_values($intersect_rows[$j])) {
               $index = $i;
               break;
             }
           }
           if (array_key_exists($index, $base_rows)) {
             foreach ($base_values as $idx => $val) {
               $subset_row[ $col_names[$idx] ] = $val;
             }
             $result[] = $subset_row;
           }
         } else {
           $this->pushError('In INTERSECT,'
                         .  ' two rows should be the same numbers');
           $result = array();
           break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Evaluates the output of two query expressions and
 * returns the difference between the results.
 *
 * @param  array   based rows
 * @param  array   subject rows to combine
 * @param  array   column names which is used for result-set
 * @access public
 */
 function exceptAll($base_rows, $except_rows, $col_names = array()){
   $result = array();
   if (!is_array($base_rows) || !is_array($except_rows)) {
     $this->pushError('Invalid arguments on EXCEPT');
   } else {
     if (empty($col_names) && is_array(reset($base_rows))) {
       $col_names = array_keys(reset($base_rows));
     }
     if (empty($col_names)) {
       $this->pushError('Failed to compound rows on EXCEPT');
     } else {
       if (is_string($col_names)) {
         $col_names = array($col_names);
       }
       $col_length = count($col_names);
       $base_row_count   = count($base_rows);
       $except_row_count = count($except_rows);
       for ($i = 0; $i < $base_row_count; $i++) {
         $subset_row = array();
         if (isset($base_rows[$i]) && is_array($base_rows[$i])
          && count($base_rows[$i]) === $col_length) {
           $index = -1;
           $base_values = array_values($base_rows[$i]);
           for ($j = 0; $j < $except_row_count; $j++) {
             if ($base_values !== array_values($except_rows[$j])) {
               $index = $i;
               break;
             }
           }
           if (array_key_exists($index, $base_rows)) {
             foreach ($base_values as $idx => $val) {
               $subset_row[ $col_names[$idx] ] = $val;
             }
             $result[] = $subset_row;
           }
         } else {
           $this->pushError('In EXCEPT, two rows should be the same numbers');
           $result = array();
           break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Executes all the aggregate functions,
 *  and append the aggregate results to the rows of the result
 *
 * @param  array   result records
 * @param  array   results of all aggregate functions
 * @return array   array which appended the aggregate results to rows
 * @access private
 */
 function appendAggregateResult(&$rows, $aggregates = array()){
   $result = array();
   if (!$this->hasError()) {
     if (is_array($rows) && is_array(reset($rows))
      && is_array($aggregates) && is_array(reset($aggregates))) {
       $rows_reset = reset($rows);
       $reset = reset($aggregates);
       $check_col = key($reset);
       $agg_count = count($aggregates);
       if (count($rows) !== $agg_count
        || !array_key_exists($check_col, $rows_reset)) {
         $this->pushError('Failed to execute the expression on HAVING');
       } else {
         $agg_appends = array();
         $i = 0;
         while (--$agg_count >= 0) {
           $agg_shift = array_shift($aggregates);
           if (is_array($agg_shift)) {
             foreach ($agg_shift as $agg_col => $agg_pairs) {
               if (is_string($agg_col) && is_array($agg_pairs)) {
                 foreach ($agg_pairs as $func => $val) {
                   if (is_string($func)) {
                     $func = strtoupper($func);
                     $key = null;
                     if ($func === 'COUNT_ALL') {
                       $key = sprintf('COUNT(*)#%08u', $i);
                     } else {
                       $key = sprintf('%s(%s)#%08u', $func, $agg_col, $i);
                     }
                     $rows[$i][$key] = $val;
                     $agg_appends[$key] = $val;
                   }
                 }
               }
             }
           }
           $i++;
         }
         $result = $agg_appends;
       }
     }
   }
   return $result;
 }

/**
 * A handle using the select syntax for to select the columns
 *
 * @param  array   a record of target
 * @param  mixed   selected keys as columns
 * @param  array   results of aggregate functions
 * @param  string  the name of column which will be a group
 * @return void
 * @access private
 */
 function assignCols(&$rows, $cols, $aggregates = null,
                             $group = null, $order = null){
   $is_distinct = false;
   if ($this->isDistinct($cols)) {
     $is_distinct = true;
     $this->removeDistinctToken($cols);
   } else if ($this->isSelectListAllClause($cols)) {
     $this->removeSelectListAllClauseToken($cols);
   }
   $parsed = $this->parseColumns($cols);
   if (!empty($parsed) && is_array($parsed) && isset($parsed['cols'])) {
     $cols = $parsed['cols'];
     $cols_order = $cols;
     if (!empty($parsed['func']) && is_array($parsed['func'])) {
       $fn = $parsed['func'];
       if ($aggregates == null) {
         $this->execAllAggregateFunction($rows, $fn);
       } else {
         foreach ($fn as $name => $col) {
           $this->execAggregateFunction($rows, $name, $col, $aggregates);
         }
       }
       $funcs = $fn;
     } else {
       $funcs = array();
     }
     $cols_order = $this->assignColumnsOrder($cols_order, $funcs);
     $this->orderColumns($rows, $cols_order);
     if (!empty($parsed['as']) && is_array($parsed['as'])) {
       $as = $parsed['as'];
       $as_mod = $this->replaceAliasNames($as);
       if (!empty($as_mod) && is_array($as_mod) && $as !== $as_mod) {
         $as = $as + $as_mod;
       }
       foreach ($as as $field_name => $alias_name) {
         $restored = $this->restoreTableAliasName($field_name);
         $as[$restored] = $alias_name;
       }
       $row_count = count($rows);
       for ($i = 0; $i < $row_count; $i++) {
         foreach ($as as $org => $key) {
           if (array_key_exists($org, $rows[$i])) {
             $tmp = $rows[$i][$org];
             unset($rows[$i][$org]);
             $rows[$i][$key] = $tmp;
           }
         }
       }
       if (!$this->hasError()) {
         $cols = $cols_order;
         $cols_order = array();
         foreach ($cols as $key) {
           if (array_key_exists($key, $as)) {
             $cols_order[] = $as[$key];
           } else {
             $cols_order[] = $key;
           }
         }
         $this->orderColumns($rows, $cols_order);
       }
     }
     if ($this->hasError()) {
       $rows = array();
     } else {
       if (count($rows) > 1 && $order != null) {
         $this->orderBy($rows, $order);
       }
       $this->diffColumns($rows, $cols_order);
       if ($is_distinct) {
         $this->applyDistinct($rows);
       }
       if ($this->_onMultiSelect) {
         $this->removeCorrelationName($rows);
       }
       $this->removeDuplicateRows($rows, $cols_order, $parsed, $group);
     }
   }
 }

/**
 * A handle using the select syntax as "SELECT COUNT(*)"
 *
 * @param  array   a record of target
 * @param  number  the result value of COUNT function
 * @param  mixed   selected keys as columns
 * @return void
 * @access private
 */
 function assignColsBySimpleCount(&$rows, $count, $cols){
   $rows = array();
   $parsed = $this->parseColumns($cols);
   if (!empty($parsed) && is_array($parsed) && isset($parsed['cols'])) {
     $cols = $parsed['cols'];
     $func = 'func';
     if (!empty($parsed[$func])
      && is_array($parsed[$func]) && is_string(reset($parsed[$func]))) {
       $func_col  = reset($parsed[$func]);
       $func_name = key($parsed[$func]);
       $rows = $this->execSimpleCountFunction($count, $func_name, $func_col);
       if (!empty($rows) && isset($rows[0]) && is_array($rows)) {
         $as = 'as';
         if (!empty($parsed[$as]) && is_array($parsed[$as])) {
           $aliases = $parsed[$as];
           $as_mod = $this->replaceAliasNames($aliases);
           if (!empty($as_mod) && is_array($as_mod) && $aliases !== $as_mod) {
             $aliases = $aliases + $as_mod;
           }
           $row_count = count($rows);
           for ($i = 0; $i < $row_count; $i++) {
             foreach ($aliases as $org => $key) {
               if (isset($rows[$i][$org])) {
                 $tmp = $rows[$i][$org];
                 unset($rows[$i][$org]);
                 $rows[$i][$key] = $tmp;
               }
             }
             if ($i > 1) {
               $this->pushError('Failed to assign the alias name');
               $rows = array();
               break;
             }
           }
         }
       }
     } else {
       $this->pushError('Failed to assign the column');
     }
   }
   if ($this->hasError()) {
     $rows = array();
   }
 }

/**
 * A handle using the select syntax for to select-list expression
 *
 * @param  array   a record of target
 * @param  mixed   selected keys as columns
 * @param  array   results of aggregate functions
 * @param  string  the name of column which will be a group
 * @return void
 * @access private
 */
 function assignDualCols(&$rows, $cols, $aggregates = null, $group = null){
   $is_distinct = false;
   if ($this->isDistinct($cols)) {
     $is_distinct = true;
     $this->removeDistinctToken($cols);
   } else if ($this->isSelectListAllClause($cols)) {
     $this->removeSelectListAllClauseToken($cols);
   }
   $parsed = $this->parseColumns($cols);
   if (!empty($parsed) && is_array($parsed) && isset($parsed['cols'])) {
     $cols = $parsed['cols'];
     $cols_order = $this->assignColumnsOrder($cols);
     $this->orderColumns($rows, $cols_order);
     if (!empty($parsed['as']) && is_array($parsed['as'])) {
       $as = $parsed['as'];
       $as_mod = $this->replaceAliasNames($as);
       if (!empty($as_mod) && is_array($as_mod) && $as !== $as_mod) {
         $as = $as + $as_mod;
       }
       $count = count($rows);
       for ($i = 0; $i < $count; $i++) {
         foreach ($as as $org => $key) {
           if (isset($rows[$i][$org])) {
             $tmp = $rows[$i][$org];
             unset($rows[$i][$org]);
             $rows[$i][$key] = $tmp;
           }
         }
       }
       if (!$this->hasError()) {
         $cols = $cols_order;
         $cols_order = array();
         foreach ($cols as $key) {
           if (array_key_exists($key, $as)) {
             $cols_order[] = $as[$key];
           } else {
             $cols_order[] = $key;
           }
         }
         $this->orderColumns($rows, $cols_order);
       }
     }
     if ($this->hasError()) {
       $rows = array();
     } else {
       $this->diffColumns($rows, $cols_order);
       if ($is_distinct) {
         $this->applyDistinct($rows);
       }
       $this->removeDuplicateRows($rows, $cols_order, $parsed, $group);
     }
   }
 }

/**
 * Remove duplicate rows
 *
 * Quote:
 *  If you use a group function in a statement containing no GROUP BY clause,
 *  it is equivalent to grouping on all rows.
 *
 * @access private
 */
 function removeDuplicateRows(&$rows, $cols_order, $parsed, $group = null){
   $f = 'func';
   if (!empty($rows) && is_array($rows)
    && empty($group) && is_array($cols_order)) {
     if (count($cols_order) === 1 && !empty($parsed[$f])
      && count($parsed[$f]) === 1 && is_array(reset($rows))) {
       $length = count($rows);
       $values = array();
       $remove = true;
       for ($i = 0; $i < $length; $i++) {
         if (!empty($values)) {
           if ($rows[$i] === $values) {
             continue;
           } else {
             $remove = false;
             break;
           }
         }
         $values = $rows[$i];
       }
       if ($remove) {
         $rows = reset($rows);
         if (!array_key_exists(0, $rows)) {
           $rows = array($rows);
         }
       }
     }
   }
 }

/**
 * @access private
 */
 function diffColumns(&$rows, $cols, $each = false){
   if (!empty($rows) && is_array($rows) && is_array($cols)) {
     $reset = reset($rows);
     if (!empty($reset)
      && is_array($reset) && count($cols) < count($reset)) {
       $diff_keys = array_diff(array_keys($reset), $cols);
       $length = count($rows);
       for ($i = 0; $i < $length; $i++) {
         if ($each) {
           $diff_keys = array_diff(array_keys($rows[$i]), $cols);
         }
         foreach ($diff_keys as $key) {
           unset($rows[$i][$key]);
         }
       }
     }
   }
 }

/**
 * @access private
 */
 function orderColumns(&$rows, $cols_order){
   $result = false;
   if (!$this->hasError()) {
     if (!is_array($cols_order)) {
       $this->pushError('Cannot assign the columns on orderColumns(%s)',
                         $cols_order);
     } else {
       if (!empty($this->_selectExprCols)) {
         $selcols = & $this->_selectExprCols;
       } else {
         $selcols = array();
       }
       $new_rows = array();
       $rows = array_values($rows);
       $count = count($rows);
       $i = 0;
       while (--$count >= 0) {
         $row = $rows[$i];
         $rows[$i] = null;
         foreach ($cols_order as $col) {
           if (array_key_exists($col, $selcols)) {
             $val = $this->execSelectExpr($row, $col);
             if ($this->hasError()) {
               $rows = array();
               break 2;
             }
             $new_rows[$i][$col] = $val;
           } else {
             if (array_key_exists($col, $row)) {
               $new_rows[$i][$col] = $row[$col];
             } else {
               $new_rows[$i][$col] = null;
             }
           }
         }
         $new_rows[$i] = $new_rows[$i] + $row;
         $i++;
       }
       if ($this->hasError()) {
         $rows = array();
       } else {
         $rows = $new_rows;
       }
       unset($selcols, $new_rows);
     }
   }
 }

/**
 * @access private
 */
 function assignColumnsOrder($cols_order, $funcs = array()){
   $result = false;
   $this->_selectExprCols = array();
   if ($this->_fromSubSelect) {
     $curmeta = $this->_subSelectMeta;
     if (!isset($this->tableName)) {
       $this->tableName = '';
     }
     $this->meta = array($this->tableName => $curmeta);
     $meta = $this->meta;
   } else if (empty($this->_isDualSelect)
           && empty($this->_onUnionSelect)) {
     if (empty($this->tableName)
      || empty($this->meta[$this->tableName])) {
       $this->pushError('Cannot load the meta data'
                     .  ' on assignColumnsOrder()');
     } else if (!is_array($cols_order)) {
       $this->pushError('Cannot assign the columns'
                     .  ' on assignColumnsOrder(%s)', $cols_order);
     }
     if ($this->hasError()) {
       $meta = array();
       $curmeta = array();
     } else {
       $meta = $this->meta;
       unset($meta[0]);
       $curmeta = $meta[$this->tableName];
     }
   } else if (!empty($this->_onUnionSelect)) {
     $meta = $this->meta;
     $curmeta = array();
     if (!empty($this->_unionColNames) && is_array($this->_unionColNames)) {
       $curmeta = $this->_unionColNames;
     }
   } else {
     $meta = array();
     $curmeta = array();
   }
   if (!$this->hasError()) {
     $cols_keys = array_keys($cols_order);
     $cols_order = array_values($cols_order);
     foreach ($cols_order as $i => $col) {
       if ($col === '*') {
         $mod_cols = array();
         foreach (array_keys($curmeta) as $mcol) {
           if (!array_key_exists($mcol, $cols_keys)) {
             $mod_cols[] = $mcol;
           }
         }
         if (!empty($mod_cols)) {
           array_splice($cols_order, $i, 0, $mod_cols);
           $index = array_search($col, $cols_order);
           unset($cols_order[$index]);
         }
       } else {
         $colms = explode('.', $col);
         if (count($colms) === 2 && isset($colms[0], $colms[1])
          && $this->isEnableName($colms[0])
          && ($this->isEnableName($colms[1]) || $colms[1] === '*')) {
           list($tablename, $column) = $colms;
           $tname = $this->encodeKey($tablename);
           if (!array_key_exists($tname, $meta)) {
             $replaced = false;
             if (!empty($this->_selectTableAliases)
              && is_array($this->_selectTableAliases)) {
               foreach ($this->_selectTableAliases as $org_name => $as_name) {
                 if ($org_name != null && $as_name != null
                  && $tablename === $as_name) {
                   if ($this->_fromSubSelect) {
                     $replaced = true;
                     if (empty($this->_subSelectJoinUniqueNames)) {
                       break;
                     }
                   }
                   $tablename = $org_name;
                   $tname = $this->encodeKey($tablename);
                   $replaced = true;
                   break;
                 }
               }
             }
             if (!$replaced) {
               $this->pushError('Not exists the table(%s)', $tablename);
               $cols_order = array();
               break;
             }
           }
           if ($column === '*') {
             $mod_cols = array();
             foreach (array_keys($meta[$tname]) as $tcol) {
               if (!array_key_exists($tcol, $cols_keys)) {
                 if ($this->_onMultiSelect) {
                   $mod_cols[] = sprintf('%s.%s', $tablename, $tcol);
                   //XXX: necessary?
                   //$mod_cols[] = $tcol;
                 } else {
                   $mod_cols[] = $tcol;
                 }
               }
             }
             if (!empty($mod_cols)) {
               array_splice($cols_order, $i, 0, $mod_cols);
               $index = array_search($col, $cols_order);
               unset($cols_order[$index]);
             }
           } else {
             if ($this->_onMultiSelect) {
               $correlation = sprintf('%s.%s', $tablename, $column);
               array_splice($cols_order, $i, 0, $correlation);
               $index = array_search($col, $cols_order);
               unset($cols_order[$index]);
             } else {
               array_splice($cols_order, $i, 0, $column);
               $index = array_search($col, $cols_order);
               unset($cols_order[$index]);
             }
           }
         } else if (!array_key_exists($col, $curmeta)
                 && $this->isExprToken($col)) {
           $do_expr = true;
           if (!empty($funcs) && is_array($funcs)) {
             foreach ($funcs as $func_name => $func_args) {
               $func_name = sprintf('%s(%s)', $func_name, $func_args);
               if (strcasecmp($col, $func_name) === 0) {
                 $do_expr = false;
                 break;
               }
             }
           }
           if ($do_expr) {
             $this->_selectExprCols[$col] = 1;
           }
         }
       }
     }
     $result = array_values(array_unique($cols_order));
   }
   return $result;
 }

/**
 * Apply DISTINCT for result-set
 *
 * @param  array    the result-set
 * @return void
 * @access private
 */
 function applyDistinct(&$rows){
   if (!$this->hasError()) {
     if (is_array($rows)) {
       $row_count = count($rows);
       if ($row_count > 1) {
         $removes = array();
         for ($i = 0; $i < $row_count; ++$i){
           for ($j = 0; $j < $i; $j++) {
             if (empty($removes[$i]) && $rows[$i] === $rows[$j]) {
               $removes[$i] = 1;
               break;
             }
           }
         }
         foreach (array_keys($removes) as $index) {
           unset($rows[$index]);
         }
         $rows = array_values($rows);
       }
     }
   }
 }

/**
 * Remove DISTINCT token for SELECT-LIST clause
 *
 * @param  mixed    the value of SELECT clause
 * @return void
 * @access private
 */
 function removeDistinctToken(&$columns){
   if (!$this->hasError()) {
     if (is_string($columns)) {
       $columns = $this->splitSyntax($columns);
     }
     if (is_array($columns)) {
       $shift = array_shift($columns);
       if (0 !== strcasecmp($shift, 'distinct')) {
         array_unshift($shift);
       }
       $columns = $this->joinWords($columns);
     }
   }
 }

/**
 * Remove "ALL" token for SELECT-LIST clause
 *
 * @param  mixed    the value of SELECT clause
 * @return void
 * @access private
 */
 function removeSelectListAllClauseToken(&$columns){
   if (!$this->hasError()) {
     if (is_string($columns)) {
       $columns = $this->splitSyntax($columns);
     }
     if (is_array($columns)) {
       $shift = array_shift($columns);
       if (0 !== strcasecmp($shift, 'all')) {
         array_unshift($shift);
       }
       $columns = $this->joinWords($columns);
     }
   }
 }

/**
 * Creates the HTML TABLE element from rows.
 *
 * @example
 * <code>
 * $fields = array('col1' => null, 'col2' => null);
 * $posql = new Posql('table_example_db1', 'table1', $fields);
 * for ($i = 0; $i < 10; $i++) {
 *   $data = array(
 *     'col1' => "col1_value{$i}",
 *     'col2' => "col2_value{$i}"
 *   );
 *   $posql->insert('table1', $data);
 *   if ($posql->isError()) {
 *     die($posql->lastError());
 *   }
 * }
 * $sql = 'SELECT * FROM table1';
 * $result = $posql->query($sql);
 * if ($posql->isError()) {
 *   die($posql->lastError());
 * }
 * $result = $result->fetchAll('assoc');
 * $attrs = array(
 *   'border' => 1,
 *   'style'  => array(
 *     'color'      => '#333',
 *     'background' => '#FFF',
 *     'margin'     => '4px'
 *   )
 * );
 * print $posql->toHTMLTable($result, $sql, $attrs);
 * </code>
 *
 * @param  array   the rows of result sets
 * @param  mixed   optionally, the caption, or the table attributes
 * @param  mixed   optionally, the table attributes, or the caption
 * @return string  Created HTML TABLE element
 * @access public
 */
 function toHTMLTable($rows, $caption = null, $attr = 'border="1"'){
   $result = '';
   if (!empty($rows) && is_array($rows)) {
     if (!is_array(reset($rows))) {
       $rows = array($rows);
     }
     switch (func_num_args()) {
       case 0:
       case 1:
           break;
       case 2:
           if (is_array($caption)) {
             $attr = $caption;
             $caption = null;
           }
           break;
       case 3:
           if (is_array($caption)) {
             $tmp = $attr;
             $attr = $caption;
             $caption = $tmp;
             unset($tmp);
           }
           break;
       default:
           $args = func_get_args();
           array_shift($args);
           $caption = array_shift($args);
           $attr = array_splice($args, 0);
           break;
     }
     if (!is_array($attr)) {
       $attr = array($attr);
     }
     $is_caption = true;
     if (is_string($caption)) {
       //TODO: check more
       $eq_count = substr_count($caption, '=');
       $sp_count = substr_count($caption, ' ');
       $eq_split = explode('=', $caption);
       if ($eq_count === 1) {
         $eq_left = reset($eq_split);
         if ($this->isEnableName($eq_left)) {
           $is_caption = false;
         }
       } else if ($sp_count > 1
               && $eq_count > 1) {
         if ($sp_count !== $eq_count) {
           $is_caption = false;
         }
       }
     } else if (is_array($caption)) {
       $is_caption = false;
     }
     if (!$is_caption) {
       array_unshift($attr, $caption);
       $caption = null;
     }
     $caption = (string)$caption;
     $attr = $this->parseHTMLAttributes($attr);
     if (!empty($attr) && is_array($attr)) {
       $attr = ' ' . implode(' ', $attr);
     } else {
       $attr = '';
     }
     $tables = array();
     $tables[] = sprintf('<table%s>', $attr);
     if ($caption != null) {
       $tables[] = sprintf('<caption>%s</caption>',
                           $this->escapeHTML($caption));
     }
     $tables[] = '<tr>';
     $row_cols = array_keys(reset($rows));
     foreach ($row_cols as $col) {
       $tables[] = sprintf('<th>%s</th>', $this->escapeHTML($col));
     }
     $tables[] = '</tr>';
     $br = trim(nl2br($this->NL));
     $rows = array_values($rows);
     $row_count = count($rows);
     $i = 0;
     while (--$row_count >= 0) {
       $row = $rows[$i];
       $rows[$i] = null;
       $tables[] = '<tr>';
       foreach ($row as $col => $val) {
         if ($val === '') {
           $val = $br;
         } else if ($val === true) {
           $val = '<var>TRUE</var>';
         } else if ($val === false) {
           $val = '<var>FALSE</var>';
         } else if ($val === null) {
           $val = '<var>NULL</var>';
         } else {
           $val = $this->escapeHTML($val);
         }
         $tables[] = sprintf('<td>%s</td>', $val);
       }
       $tables[] = '</tr>';
       $i++;
     }
     $tables[] = '</table>';
     $result = implode($this->NL, array_splice($tables, 0));
     unset($tables);
   }
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Expr
 *
 * This class processes the expression on WHERE, HAVING, ON, etc. mode
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Expr extends Posql_Builder {
/**
 * Checks whether the expression has already validated or it has not yet
 *
 * @param  mixed   the target expression
 * @return boolean whether it already validated or it has not yet
 * @access private
 */
 function isValidExpr($expr){
   $result = false;
   $marks = $this->getValidMarks();
   if (is_array($expr)) {
     $result = reset($expr) === reset($marks)
             && next($expr) === next($marks);
   } else {
     $mark = implode('', $marks);
     $result = substr($expr, 0, strlen($mark)) === $mark;
   }
   return $result;
 }

/**
 * Returns the mark to compare that validated the expression
 *
 * @param  void
 * @return array    the Mark that use to valid
 * @access private
 */
 function getValidMarks(){
   static $marks = array();
   if (!$marks) {
     $marks = array(
       $this->getParsedMark(),
       $this->NL
     );
   }
   return $marks;
 }

/**
 * Adds the mark to compare for valid to the expression
 *
 * @param  mixed    the expression
 * @return boolean  success or failure
 * @access private
 */
 function addValidMarks(&$expr){
   $result = false;
   $marks = $this->getValidMarks();
   if ($this->isParsedExpr($expr) && !$this->isValidExpr($expr)) {
     if ($this->removeParsedMark($expr)) {
       if (is_array($expr)) {
         array_splice($expr, 0, 0, $marks);
       } else {
         $expr = implode('', $marks) . $expr;
       }
       $result = true;
     }
   }
   return $result;
 }

/**
 * Removes the mark to compare for valid the expression
 *
 * @param  mixed    the expression
 * @return boolean  success or failure
 * @access private
 */
 function removeValidMarks(&$expr){
   $result = false;
   $marks = $this->getValidMarks();
   if ($this->isParsedExpr($expr) && $this->isValidExpr($expr)) {
     if ($this->removeParsedMark($expr)) {
       array_shift($marks);
       if (is_array($expr)) {
         if (reset($expr) === reset($marks)) {
           array_shift($expr);
           array_shift($marks);
           $result = empty($marks);
         }
       } else {
         $mark = implode('', $marks);
         $len = strlen($mark);
         if ($len && substr($expr, 0, $len) === $mark) {
           $expr = substr($expr, $len);
           $result = true;
         }
       }
     }
   }
   return $result;
 }

/**
 * Validate the internal correlation prefix for unique name
 *
 * @param  array   associated array which includes dot yet
 * @return void
 * @access private
 */
 function validCorrelationPrefix($target){
   $this->initCorrelationPrefix();
   $prefix = & $this->_correlationPrefix;
   $seed = 0;
   foreach ($target as $key => $val) {
     if (!is_string($key) && is_string($val)) {
       $key = $val;
     }
     if (is_string($key)) {
       if (strpos($key, $prefix) !== false) {
         do {
           $new_prefix = sprintf('%s_%s_%s',
             $prefix,
             $this->hashValue((string)$seed),
             $seed
           );
           $seed++;
         } while (strpos($key, $new_prefix) !== false);
         $prefix = $new_prefix;
       }
     }
   }
   unset($prefix);
 }

/**
 * @access private
 */
 function callMethod($method, $args = array()){
   $result = null;
   $argn = func_num_args();
   if ($argn > 1) {
     $args = func_get_args();
     $method = array_shift($args);
   } else if ($argn === 1 && !is_array($args)) {
     $args = array($args);
   }
   $callable = $this->isCallableMethod($method, true);
   if ($callable) {
     $result = call_user_func_array($callable, $args);
   }
   return $result;
 }

/**
 * @access private
 */
 function isCallableMethod($method, $return_callable = false){
   $result = false;
   if ($method != null && is_string($method)) {
     $method = strtolower($method);
     if (isset($this->UDF[$method])) {
       if ($return_callable) {
         if (is_callable($this->UDF[$method])) {
           $result = $this->UDF[$method];
         } else {
           $this->pushError('Cannot call the function(%s)',
                            strtoupper($method));
           $result = false;
         }
       } else {
         $result = true;
       }
     } else if (is_object($this->method)) {
       if (substr($method, 0, 1) !== '_'
        && strcasecmp(get_class($this->method), $method) !== 0) {
         if (!method_exists($this->method, $method)) {
           $this->pushError('Undefined function (%s)', strtoupper($method));
           $result = false;
         } else {
           if ($return_callable) {
             $result = array($this->method, $method);
             if (!is_callable($result)) {
               $this->pushError('Cannot call the function(%s)',
                                strtoupper($method));
               $result = false;
             }
           } else {
             $result = true;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Create new class instance as variable arguments
 *
 * @param   string        the class name of target
 * @param   mixed   (...) zero or more parameters to be passed in
 * @return  object        the new instance of object
 * @access  public
 */
 function createInstance($class){
   $result = null;
   if ($class != null
    && is_string($class) && $this->existsClass($class)) {
     $argn = func_num_args();
     if ($argn === 1) {
       $result = new $class;
     } else {
       $args = func_get_args();
       array_shift($args);
       $argn--;
       $params = array();
       for ($i = 0; $i < $argn; $i++) {
         $params[] = sprintf('$args[%d]', $i);
       }
       $expr = sprintf('$result=new $class(%s);', implode(',', $params));
       eval($expr);
       unset($expr);
     }
   }
   return $result;
 }

/**
 * Expression for WHERE syntax as PHP language
 *
 * @param  array   an array variable of target
 * @param  string  expression of PHP syntax
 * @return boolean results of evaluation code
 * @access private
 */
 function expr(){
   extract(func_get_arg(0));
   return eval('return ' . func_get_arg(1) . ';');
 }

/**
 * Does valid expression safely for WHERE syntax as PHP language
 * The error message will be captured by this class
 * This method works only checking
 *
 * @param  mixed   an array variable for extract(), or expression as string
 * @param  mixed   expression of PHP syntax, or array variable for extract()
 * @return boolean whether it is possible expression
 * @access private
 */
 function checkExpr($expr, $meta = array()){
   $result = false;
   if (is_array($expr)) {
     $tmp  = $expr;
     $expr = $meta;
     $meta = $tmp;
     unset($tmp);
   }
   if (!is_array($meta)) {
     $meta = array();
   }
   if (!$this->hasError()) {
     if (is_string($expr)) {
       $tokens = $this->splitSyntax($expr);
       $level = 0;
       foreach ($tokens as $token) {
         switch ($token) {
           case '(':
               $level++;
               break;
           case ')':
               $level--;
               break;
           default:
               break;
         }
       }
       if ($level !== 0) {
         $this->pushError('Syntax error, expect () parenthesis');
       }
     }
     if (!$this->hasError()) {
       $this->_checkOnlyExpr = true;
       $result = $this->safeExpr($meta, $expr);
       $this->_checkOnlyExpr = null;
     }
   }
   return $result;
 }

/**
 * Does expression safely for WHERE syntax as PHP language
 * The error message will be captured by this class
 * But the execution speed will be slow
 *
 * @param  mixed   an array variable for extract(), or expression as string
 * @param  mixed   expression of PHP syntax, or array variable for extract()
 * @return mixed   results of evaluation code
 * @access private
 */
 function safeExpr(){
   if ($this->isPHP5) {
     set_error_handler(array($this, 'errorHandler'), E_ALL);
   } else {
     set_error_handler(array(&$this, 'errorHandler'));
   }
   $args = func_get_args();
   switch (count($args)) {
     case 1:
         $result = $this->_execSafeExpr(array(),  $args[0]);
         break;
     case 2:
         $result = $this->_execSafeExpr($args[0], $args[1]);
         break;
     default:
         $this->pushError('illegal arguments on expression');
         $result = false;
         break;
   }
   restore_error_handler();
   return $result;
 }

/**
 * private method for safeExpr()
 *
 * @param  array   an array variable for extract()
 * @param  string  expression of PHP syntax
 * @return mixed   results of evaluation code
 * @access private
 */
 function _execSafeExpr(){
   if (func_get_arg(0) != null) {
     extract(func_get_arg(0));
   }
   ob_start();
   eval('if(0):return ' . func_get_arg(1) . ';endif;');
   if (ob_get_length()) {
     $msg = ob_get_contents();
     ob_end_clean();
     $msg = trim($this->stripTags($msg));
     if (preg_match('{\w+\s+error:\s+(.+)\sin[\s\S]*$}isU', $msg, $match)) {
       $msg = $match[1];
     }
     if ($msg == null) {
       if (empty($php_errormsg)) {
         $msg = 'Fatal error on expression';
       } else {
         $msg = $php_errormsg;
       }
     }
     $this->pushError($msg);
   } else {
     ob_end_clean();
     if (empty($this->_checkOnlyExpr)) {
       return eval('return ' . func_get_arg(1) . ';');
     }
     return true;
   }
   return false;
 }

/**
 * optimizes the expression which worked PHP syntax
 * When the engine is executed in the PHP mode,
 *  and the command was SELECT, this expression is useful
 *
 * @param  mixed   the expression as string,
 *                  or an array which  parsed tokens
 * @return mixed   the value of expression which was optimized
 * @access private
 */
 function optimizeExpr($expr){
   if (empty($expr)) {
     $expr = false;
   } else {
     if (is_array($expr)) {
       $expr = $this->joinWords($expr);
     }
     $expr = $this->trimExtra($expr, '%=`*?:/<>^\\,.|&;');
     if (is_numeric($expr) || strlen($expr) === 1) {
       $expr = (bool)$expr;
     } else {
       switch (strtolower($expr)) {
         case '':
         case 'null':
         case 'false':
             $expr = false;
             break;
         case '*':
         case 'true':
         case '0=0':
         case '0==0':
         case '1=1':
         case '1==1':
             $expr = true;
             break;
         default:
             break;
       }
     }
   }
   return $expr;
 }

/**
 * Validates the expression for safely
 *
 * @param  string   expression
 * @return string   validated string of expression or false on error
 * @access private
 */
 function validExpr($expr){
   $result = false;
   switch ($this->getEngine()) {
     case 'PHP':
         $result = $this->validExprByPHP($expr);
         break;
     case 'SQL':
     //case 'ECMA':
     //
     // ECMA/JavaScript engine is likely to be able to implements by
     //  using the Posql_ECMA class in future.
     //
     default:
         $result = $this->validExprBySQL($expr);
         break;
   }
   //debug(array('expr'=>$expr,'ret'=>$result),'color=navy:***validated***;');
   return $result;
 }

/**
 * Validates the expression which handled as PHP syntax for safely
 *
 * @param  string   expression
 * @param  boolean  internal only
 * @return string   validated string of expression or false on error
 * @access private
 */
 function validExprBySQL($expr, $recursive = false){
   $result = false;
   if ($this->isValidExpr($expr)) {
     $result = $expr;
   } else {
     if (!$recursive) {
       $expr = $this->assignSubQuery($expr);
       $this->_extraJoinExpr = array();
       $expr = $this->replaceExprBySQL($expr);
       if (!$this->hasError()) {
         if (empty($this->_isDualSelect)
          && empty($this->_onJoinParseExpr)
          && empty($this->_fromSubSelect)
          && empty($this->_onUnionSelect)) {
           if (!empty($this->tableName)
            && empty($this->meta[$this->tableName])) {
             $this->getMeta();
           }
           if (empty($this->_onCreate)) {
             if (empty($this->tableName)
              || empty($this->meta[$this->tableName])) {
               $table_name = $this->getTableName();
               $this->pushError('No such table(%s)'
                             .  ' on validating expression(%s)'
                             .  ' using SQL engine',
                 $table_name,
                 $expr
               );
               $result = false;
             }
           }
         }
       }
     }
     if ($this->hasError()) {
       $this->_extraJoinExpr = array();
     } else {
       if (!empty($this->_subSelectMeta)) {
         $meta = $this->_subSelectMeta;
         if (!empty($this->_subSelectJoinUniqueNames)) {
           $unique_names = & $this->_subSelectJoinUniqueNames;
           if (is_array($unique_names) && is_array($meta)) {
             foreach (array_keys($meta) as $meta_identifier) {
               foreach ($unique_names as $subquery => $sub_identifier) {
                 $identifier = $this->toCorrelationName($sub_identifier,
                                                        $meta_identifier);
                 if ($identifier != null) {
                   $meta[$identifier] = 1;
                 }
               }
             }
           }
           unset($unique_names);
         }
       } else if (!empty($this->meta) && is_array($this->meta)) {
         $org_meta = $this->meta;
         $meta = array();
         foreach ($org_meta as $meta_key => $meta_div) {
           if (!empty($meta_div) && is_array($meta_div)) {
             foreach ($meta_div as $key => $val) {
               $meta[$key] = 1;
             }
           }
         }
         if (!empty($this->_onMultiSelect)) {
           $this->validCorrelationPrefix($meta);
           foreach ($org_meta as $meta_key => $meta_div) {
             if (!empty($meta_div) && is_array($meta_div)) {
               foreach ($meta_div as $key => $val) {
                 $meta[$key] = 1;
                 $meta_key_org = $this->decodeKey($meta_key);
                 $extra_key = $this->toCorrelationName($meta_key_org, $key);
                 if ($extra_key != null) {
                   $meta[$extra_key] = 1;
                 }
               }
             }
           }
         }
       } else if (!empty($this->meta[$this->tableName])
               && is_array($this->meta[$this->tableName])) {
         $meta = & $this->meta[$this->tableName];
       } else {
         $meta = array();
       }
       $callable = 'callmethod';
       $is_recursive = false;
       $expr  = $this->cleanArray($expr);
       $open  = '(';
       $close = ')';
       $level = 0;
       $length = count($expr);
       for ($i = 0; $i < $length; $i++) {
         $token = $this->getValue($expr, $i);
         $prev  = $this->getValue($expr, $i - 1);
         $next  = $this->getValue($expr, $i + 1);
         switch (strtolower($token)) {
           case '(':
               $level++;
               if ($prev != null) {
                 if (substr($prev, 0, 1) === '$') {
                   $prev = substr($prev, 1);
                   if (isset($expr[$i - 1])) {
                     $expr[$i - 1] = $prev;
                   }
                 }
                 if (!$this->isWord($prev)) {
                   break;
                 }
                 $func = strtolower($prev);
                 if ($func === $callable
                  && isset($expr[$i - 2], $expr[$i - 3], $expr[$i - 4])
                  && $expr[$i - 2] === '->'
                  && $expr[$i - 3] === 'this'
                  && $expr[$i - 4] === '$') {
                   break;
                 } else if ((!empty($this->_onJoinParseExpr)
                         ||  !empty($this->_onMultiSelect))
                         && ($func === 'isset' || $func === 'empty')) {
                   break;
                 } else {
                   if ($func !== 'and' && $func !== 'or'
                    && $func !== 'xor' && $func !== 'is'
                    && $func !== 'not' && $func !== 'in') {
                     $nest = 0;
                     $idx = $i;
                     $args = array();
                     while (array_key_exists($idx, $expr)) {
                       $arg = $expr[$idx];
                       switch ($arg) {
                         case '(':
                             $nest++;
                             $prev_idx = $idx - 1;
                             if ($nest > 1 && isset($expr[$prev_idx])
                              && $expr[$prev_idx] != null
                              && $this->isWord($expr[$prev_idx])
                              && strtolower($expr[$prev_idx]) !== $callable) {
                               $is_recursive = true;
                             }
                             break;
                         case ')':
                             $nest--;
                             break;
                         case ',':
                         default:
                             break;
                       }
                       $args[] = $arg;
                       if ($nest === 0) {
                         array_pop($args);
                         array_shift($args);
                         break;
                       }
                       $idx++;
                     }
                     if (!empty($args)) {
                       array_unshift($args, ',');
                     }
                     $func = $this->toStringToken(strtoupper($func));
                     $method = array(
                       '$', 'this', '->', $callable, '(',
                            $func, $args,
                       ')'
                     );
                     $method = $this->toOneArray($method);
                     array_splice($expr, $i - 1, $idx - $i + 2, $method);
                     $length = count($expr);
                   }
                 }
               }
               break;
           case ')':
               $level--;
               break;
           case 'new':
               if (!$recursive) {
                 if ($next != null && $this->isWord($next)) {
                   if ($this->existsClass($next)
                    || !array_key_exists($token, $meta)) {
                     $this->pushError('Not supported SQL syntax,'
                                   .  ' expect(%s)', $token);
                     $result = false;
                     break 2;
                   }
                 }
               }
               break;
           case '<?':
           case '<%':
               $error_token = 'T_OPEN_TAG';
           case '?>':
           case '%>':
               if (empty($error_token)) {
                 $error_token = 'T_CLOSE_TAG';
               }
               if (!$recursive) {
                 $this->pushError('Invalid SQL syntax,'
                               .  ' expect %s', $error_token);
                 $result = false;
                 break 2;
               }
               break;
           case '$':
               if (empty($this->_onJoinParseExpr)) {
                 if (!$recursive) {
                   if ($next == null || !array_key_exists($next, $meta)) {
                     $this->pushError('Unknown variable name(%s)'
                                   .  ' on expression', $next);
                     $result = false;
                     break 2;
                   }
                 }
               }
               if (!$recursive) {
                 if ($next === '{' || $next === '}') {
                   $this->pushError('Invalid SQL syntax, expect(%s%s)',
                                    $token, $next);
                   $result = false;
                 }
               }
               break;
           default:
               if (!$recursive) {
                 if (
                   isset($this->_invalidStatements[trim(strtolower($token))])
                 ) {
                   $this->pushError('Invalid SQL syntax,'
                                 .  ' expect %s', $token);
                   $result = false;
                   break 2;
                 }
                 $lastchar = substr($prev, -1);
                 if (!empty($this->_uniqueColsByHaving)) {
                   $unique_cols = $this->_uniqueColsByHaving;
                   if ($lastchar !== '$' && is_array($unique_cols)
                    && array_key_exists($token, $unique_cols)) {
                     $token = '$' . $token;
                     $expr[$i] = $token;
                   }
                   unset($unique_cols);
                 }
                 if (array_key_exists($token, $meta)) {
                   if ($lastchar !== '$' && $this->isWord($token)) {
                     $varname = $token;
                     $token = '$' . $token;
                     $expr[$i] = $token;
                     $prev_idx = $i - 1;
                     $corr_idx = $i - 2;
                     $corr_name = $this->getValue($expr, $corr_idx);
                     if ($lastchar === '.' && isset($expr[$corr_idx])
                      && $expr[$corr_idx] != null
                      && $this->isWord($expr[$corr_idx])) {
                       array_splice($expr, $corr_idx, 2);
                       $i = $corr_idx;
                     }
                     if (!empty($this->_onMultiSelect)) {
                       if ($corr_name != null) {
                         $corr_dot = sprintf('%s.%s', $corr_name, $varname);
                         $corr_org = $this->restoreTableAliasName($corr_dot);
                         if ($corr_dot !== $corr_org
                          && strpos($corr_org, '.') !== false) {
                           list($corr_name, $varname) = explode('.',
                                                                $corr_org);
                         }
                         if (!empty($this->_subSelectJoinUniqueNames)) {
                           $unique_names = & $this->_subSelectJoinUniqueNames;
                           if ($corr_dot !== $corr_org
                            && strpos($corr_org, '.') !== false) {
                             $corr_split = explode('.', $corr_org);
                             if (count($corr_split) > 2) {
                               $corr_split_tail = array_pop($corr_split);
                               $corr_split = array(
                                 implode('.', $corr_split),
                                 $corr_split_tail
                               );
                             }
                             if (count($corr_split) >= 2) {
                               list($corr_name, $varname) = $corr_split;
                             }
                           }
                           if (is_array($unique_names)
                            && array_key_exists($corr_name, $unique_names)) {
                             $corr_name = $unique_names[$corr_name];
                           }
                           unset($unique_names);
                         }
                         $varname = $this->toCorrelationName($corr_name,
                                                             $varname);
                         $token = '$' . $varname;
                         $expr[$i] = $token;
                       }
                       // Check whether the variable exists for NOTICE
                       $isset_expr = array(
                         '!', 'isset', '(', '$', $varname, ')',
                         '||'
                       );
                       $this->removeParsedMark($expr);
                       array_splice($expr, 0, 0, $isset_expr);
                       $this->addParsedMark($expr);
                       $length = count($expr);
                       array_unshift($this->_extraJoinExpr, $isset_expr);
                     }
                   }
                 }
               }
               break;
         }
       }
       if (end($expr) === '||') {
         array_pop($expr);
       }
       unset($meta);
       if ($this->hasError()) {
         $this->_extraJoinExpr = array();
       } else {
         if ($is_recursive) {
           $result = $this->validExprBySQL($expr, true);
         } else {
           //TODO: optimize the expression
           if ($this->addValidMarks($expr)) {
             $result = $this->joinWords($expr);
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Validates the expression which handled as PHP syntax for safely
 *
 * @param  string   expression
 * @return string   validated string of expression or false on error
 * @access private
 */
 function validExprByPHP($expr){
   $result = false;
   if (!empty($this->tableName)
    && empty($this->meta[$this->tableName])) {
     $this->getMeta();
   }
   $this->_extraJoinExpr = array();
   if (!$this->hasError()) {
     if (empty($this->_isDualSelect)
      && empty($this->_fromSubSelect)
      && empty($this->_onUnionSelect)) {
       if (empty($this->_onCreate)) {
         if (empty($this->tableName)
          || empty($this->meta[$this->tableName])) {
           $table_name = $this->getTableName();
           $this->pushError('No such table(%s)'
                             .  ' on validating expression(%s)'
                             .  ' using PHP engine',
             $table_name,
             $expr
           );
           $result = false;
         }
       }
     }
     if (!$this->hasError()) {
       $this->checkDisableFunctions();
       if (!empty($this->_subSelectMeta)) {
         $meta = $this->_subSelectMeta;
         if (!empty($this->_subSelectJoinUniqueNames)) {
           $unique_names = & $this->_subSelectJoinUniqueNames;
           if (is_array($unique_names) && is_array($meta)) {
             foreach (array_keys($meta) as $meta_identifier) {
               foreach ($unique_names as $subquery => $sub_identifier) {
                 $identifier = $this->toCorrelationName($sub_identifier,
                                                        $meta_identifier);
                 if ($identifier != null) {
                   $meta[$identifier] = 1;
                 }
               }
             }
           }
           unset($unique_names);
         }
       } else if (!empty($this->meta) && is_array($this->meta)) {
         $org_meta = $this->meta;
         $meta = array();
         foreach ($org_meta as $meta_key => $meta_div) {
           if (!empty($meta_div) && is_array($meta_div)) {
             foreach ($meta_div as $key => $val) {
               $meta[$key] = 1;
             }
           }
         }
         if (!empty($this->_onMultiSelect)) {
           $this->validCorrelationPrefix($meta);
           foreach ($org_meta as $meta_key => $meta_div) {
             if (!empty($meta_div) && is_array($meta_div)) {
               foreach ($meta_div as $key => $val) {
                 $meta[$key] = 1;
                 $meta_key_org = $this->decodeKey($meta_key);
                 $extra_key = $this->toCorrelationName($meta_key_org, $key);
                 if ($extra_key != null) {
                   $meta[$extra_key] = 1;
                 }
               }
             }
           }
         }
       } else if (!empty($this->meta[$this->tableName])
               && is_array($this->meta[$this->tableName])) {
         $meta = & $this->meta[$this->tableName];
       } else {
         $meta = array();
       }
       $expr = $this->assignSubQuery($expr);
       if (is_array($expr)) {
         $tokens = $expr;
       } else {
         $tokens = $this->splitSyntax($expr);
       }
       if (!is_array($tokens)) {
         $this->pushError('Failed to parsing the syntax(%s)', $expr);
         $result = false;
       } else if (empty($tokens)) {
         $result = false;
       } else {
         array_unshift($tokens, ' ');
         foreach ($tokens as $i => $token) {
           $h = $i - 1;
           $pre = substr($token, 0, 1);
           switch ($token) {
             case '`':
                 $this->pushError('The backtick(``) operator is disabled');
                 $tokens = array('0');
                 break;
             case '$':
                 if (isset($tokens[$i + 1])) {
                   $next = $tokens[$i + 1];
                   if ($next === '{' || $next === '}') {
                     $this->pushError('Invalid SQL syntax, expect(%s%s)',
                                      $token, $next);
                     $tokens = array('0');
                     break;
                   }
                 }
                 break;
             case '<?':
             case '<%':
                 $error_token = 'T_OPEN_TAG';
             case '?>':
             case '%>':
                 if (empty($error_token)) {
                   $error_token = 'T_CLOSE_TAG';
                 }
                 $this->pushError('Invalid SQL syntax, expect %s',
                                  $error_token);
                 $tokens = array('0');
                 break;
             default:
                 if (
                   isset($this->_invalidStatements[trim(strtolower($token))])
                 ) {
                   $this->pushError('Invalid SQL syntax,'
                                 . ' expect %s', $token);
                   $tokens = array('0');
                   break;
                 }
                 break;
           }
           if ($this->hasError()) {
             $tokens = array('0');
             break;
           }
           if ($token === '=' && !empty($this->autoAssignEquals)) {
             $token = $tokens[$i] = '==';
           } else if ($this->isWord($pre)) {
             if (isset($tokens[$h])) {
               $lastchar = substr($tokens[$h], -1);
               if ($lastchar === '$' && $token === 'this') {
                 $this->pushError('Disable variable($this) in expression');
                 $tokens = array('0');
                 break;
               }
               if ($lastchar !== '$') {
                 if (!empty($this->_uniqueColsByHaving)) {
                   $unique_cols = $this->_uniqueColsByHaving;
                   if (is_array($unique_cols)
                    && array_key_exists($token, $unique_cols)) {
                     $token = '$' . $token;
                     $tokens[$i] = $token;
                   }
                   unset($unique_cols);
                 }
                 if (array_key_exists($token, $meta)) {
                   $varname = $token;
                   $token = '$' . $token;
                   $tokens[$i] = $token;
                   $prev_idx = $i - 1;
                   $corr_idx = $i - 2;
                   $corr_name = $this->getValue($tokens, $corr_idx);
                   if ($lastchar === '.' && isset($tokens[$corr_idx])
                    && $tokens[$corr_idx] != null
                    && $this->isWord($tokens[$corr_idx])) {
                     $tokens[$prev_idx] = '';
                     $tokens[$corr_idx] = '';
                   }
                   if (!empty($this->_onMultiSelect)) {
                     if ($corr_name != null) {
                       $corr_dot = sprintf('%s.%s', $corr_name, $varname);
                       $corr_org = $this->restoreTableAliasName($corr_dot);
                       if ($corr_dot !== $corr_org
                        && strpos($corr_org, '.') !== false) {
                         list($corr_name, $varname) = explode('.', $corr_org);
                       }
                       if (!empty($this->_subSelectJoinUniqueNames)) {
                         $unique_names = & $this->_subSelectJoinUniqueNames;
                         if ($corr_dot !== $corr_org
                          && strpos($corr_org, '.') !== false) {
                           $corr_split = explode('.', $corr_org);
                           if (count($corr_split) > 2) {
                             $corr_split_tail = array_pop($corr_split);
                             $corr_split = array(
                               implode('.', $corr_split),
                               $corr_split_tail
                             );
                           }
                           if (count($corr_split) >= 2) {
                             list($corr_name, $varname) = $corr_split;
                           }
                         }
                         if (is_array($unique_names)
                          && array_key_exists($corr_name, $unique_names)) {
                           $corr_name = $unique_names[$corr_name];
                         }
                         unset($unique_names);
                       }
                       $varname = $this->toCorrelationName($corr_name,
                                                            $varname);
                       $token = '$' . $varname;
                       $tokens[$i] = $token;
                     }
                     // Check whether the variable exists for NOTICE
                     $isset_expr = array(
                       '!', 'isset', '(', '$', $varname, ')',
                       '||'
                     );
                     array_unshift($this->_extraJoinExpr, $isset_expr);
                   }
                 }
               }
               if ($this->isAlphaNum($lastchar)) {
                 $tokens[$h] .= ' ';
               }
             }
             if ($this->isDisableFunction($token)) {
               $this->pushError('Disable function(%s) in expression', $token);
               $tokens = array('0');
               break;
             }
           }
         }
         if ($this->hasError()) {
           $this->_extraJoinExpr = array();
           $result = false;
         } else {
           if (!empty($this->_extraJoinExpr)) {
             foreach ($this->_extraJoinExpr as $isset_expr) {
               array_splice($tokens, 0, 0, $isset_expr);
             }
           }
           if (end($tokens) === '||') {
             array_pop($tokens);
           }
           $tokens = $this->cleanArray($tokens);
           if (empty($this->_execExpr)) {
             $result = $this->optimizeExpr($tokens);
           } else {
             $result = $this->trimExtra($this->joinWords($tokens), '&|;');
           }
           if (!$this->checkExpr($result)) {
             $result = false;
           }
         }
       }
       unset($meta);
     }
   }
   return $result;
 }

/**
 * Executes the sub-select expression
 *
 * @param  string  the sub-query expression
 * @param  string  the left operator
 * @return mixed   result of sub-select query
 * @access private
 */
 function execSubSelect($query, $operator = null){
   $result = null;
   if (!$this->hasError()) {
     $command = $this->getQueryCommand($query);
     if (0 !== strcasecmp($command, 'select')) {
       $this->pushError('The sub-query(%s) should be SELECT', $command);
     } else {
       $rows = array();
       $clone = new Posql;
       foreach (get_object_vars($this) as $prop => $val) {
         if ($prop != null && is_string($prop)) {
           $clone->{$prop} = $val;
         }
       }
       $stmt = $clone->query($query);
       if ($clone->hasError()) {
         $this->pushError($clone->lastError());
       }
       if ($this->isStatementObject($stmt)) {
         $fetch_mode = 'assoc';
         switch (strtolower($operator)) {
           case 'from':
               $this->_fromSubSelect = true;
               $result = $stmt->fetchAll($fetch_mode);
               if (!is_array($result) || empty($result)) {
                 $result = array();
               } else {
                 $reset = reset($result);
                 if (!is_array($reset)) {
                   $this->pushError('Invalid result-set on sub-query');
                 } else {
                   $this->_subSelectMeta = array();
                   foreach (array_keys($reset) as $column) {
                     $this->_subSelectMeta[$column] = 1;
                   }
                 }
               }
               break;
           case 'exists':
               $result = '0';
               if ($stmt->rowCount()) {
                 $result = '1';
               }
               break;
           default:
               if (!$stmt->rowCount()) {
                 $result = null;
               } else {
                 $row = $stmt->fetch($fetch_mode);
                 if (empty($row)) {
                   $result = null;
                 } else if (is_array($row)) {
                   if (count($row) !== 1) {
                     $this->pushError('Operand should contain 1 column');
                     $result = null;
                   } else {
                     $rows = array();
                     do {
                       $rows[] = $this->quote(reset($row));
                     } while (is_array($row = $stmt->fetch($fetch_mode)));
                     if (0 === strcasecmp($operator, 'in')
                      || 0 === strcasecmp($operator, 'any')
                      || 0 === strcasecmp($operator, 'some')
                      || 0 === strcasecmp($operator, 'all')) {
                       $rows = array_unique($rows);
                     }
                     $result = implode(',', $rows);
                   }
                 }
               }
               break;
         }
       }
       $stmt = null;
       unset($stmt, $rows);
       $clone->_setTerminated(true);
       $clone = null;
       unset($clone);
     }
   }
   if ($result === null) {
     $result = 'NULL';
   }
   return $result;
 }

/**
 * Assign the sub-query to the result
 *
 * @param  array   the SQL query, or the parsed tokens
 * @return mixed   executed result, or FALSE on error
 * @access private
 */
 function assignSubQuery($query){
   if (is_string($query)) {
     $query = $this->splitSyntax($query);
   }
   if (is_array($query)) {
     while (true) {
       $sub_parsed = $this->parseSubQuery($query);
       if (is_array($sub_parsed)
        && array_key_exists('tokens', $sub_parsed)
        && array_key_exists('offset', $sub_parsed)
        && array_key_exists('length', $sub_parsed)
        && array_key_exists('operator', $sub_parsed)) {
         $sub_tokens   = $sub_parsed['tokens'];
         $sub_offset   = $sub_parsed['offset'];
         $sub_length   = $sub_parsed['length'];
         $sub_operator = $sub_parsed['operator'];
         $sub_query    = $this->joinWords($sub_tokens);
         $sub_result   = $this->execSubSelect($sub_query, $sub_operator);
         if ($this->hasError()) {
           $query = array('NULL');
           break;
         } else {
           $prev_query = $query;
           array_splice($query, $sub_offset, $sub_length, $sub_result);
           $query = $this->cleanArray($query);
           if ($query === $prev_query) {
             break;
           }
         }
       } else {
         break;
       }
     }
   }
   if (is_array($query)) {
     $query = $this->joinWords($query);
   }
   return $query;
 }

/**
 * Execute the sub-query for FROM clause
 *
 * @param  array   the SQL query, or the parsed tokens
 * @return mixed   executed result, or FALSE on error
 * @access private
 */
 function execSubQueryFrom($query){
   $result = array();
   if (is_string($query)) {
     $query = $this->splitSyntax($query);
   }
   if (is_array($query)) {
     $sub_parsed = $this->parseSubQuery($query);
     if (is_array($sub_parsed)
      && array_key_exists('tokens', $sub_parsed)) {
       $tokens = $sub_parsed['tokens'];
       if (is_array($tokens) && !empty($tokens)) {
         $sub_query = $this->joinWords($tokens);
         $result = $this->execSubSelect($sub_query, 'from');
       }
     }
   }
   if ($this->hasError()) {
     $result = array();
   } else if (is_string($result) && 0 === strcasecmp($result, 'null')) {
     $result = array();
   }
   return $result;
 }

/**
 * Execute any sub-queries for FROM clause
 *
 * @param  array   the SQL query, or the parsed tokens
 * @return mixed   executed result, or FALSE on error
 * @access private
 */
 function execMultiSubQueryFrom($query){
   $result = array();
   if (!$this->hasError()) {
     if (empty($this->_subSelectJoinInfo)
      || empty($this->_subSelectJoinUniqueNames)) {
       $this->pushError('Failed to compile on sub-query using JOIN');
     } else {
       $unique_names = & $this->_subSelectJoinUniqueNames;
       $unique_names = $this->flipArray($unique_names);
       if (is_string($query)) {
         $query = $this->splitSyntax($query);
       }
       if (is_array($query)
        && !empty($unique_names) && is_array($unique_names)) {
         while (true) {
           $sub_parsed = $this->parseSubQuery($query, true);
           if (is_array($sub_parsed)
            && array_key_exists('tokens', $sub_parsed)
            && array_key_exists('offset', $sub_parsed)
            && array_key_exists('length', $sub_parsed)) {
             $sub_tokens = $sub_parsed['tokens'];
             $sub_offset = $sub_parsed['offset'];
             $sub_length = $sub_parsed['length'];
             $sub_query  = $this->joinWords($sub_tokens);
             if (!array_key_exists($sub_query, $unique_names)) {
               $this->pushError('Failed to compile on sub-query using JOIN');
               break;
             }
             $exec_query = $sub_query;
             while (substr($exec_query, 0, 1) === '('
                 && substr($exec_query,  -1)  === ')') {
               $exec_query = substr($exec_query, 1, -1);
             }
             $this->_fromSubSelect = null;
             $sub_result = $this->execSubSelect($exec_query, 'from');
             if ($this->hasError()) {
               break;
             } else {
               $unique_name = $unique_names[$sub_query];
               $result[$unique_name] = $sub_result;
               $sub_result = null;
               $prev_query = $query;
               array_splice($query, $sub_offset, $sub_length, $unique_name);
               $query = $this->cleanArray($query);
               if ($query === $prev_query) {
                 break;
               }
             }
             unset($sub_result);
           } else {
             break;
           }
         }
       }
       if (!$this->hasError()) {
         foreach ($unique_names as $symbol => $identifier) {
           if (!array_key_exists($identifier, $result)) {
             $result[$identifier] = $symbol;
           }
         }
       }
       unset($unique_names);
     }
   }
   if ($this->hasError()) {
     $result = array();
   } else if (!is_array($result)) {
     $result = array();
   }
   return $result;
 }


/**
 * Executes the expression on HAVING clause
 *
 * @param  array   result records as array
 * @param  mixed   expression as HAVING clause (default = true)
 * @param  mixed   the name of column which was used GROUP BY clause
 * @param  array   results of all aggregate functions
 * @return array   results of aggregate which were varied by HAVING with rows
 * @access private
 */
 function having(&$rows, $having = true, $groups = null, $aggregates = null){
   $this->_onHaving = true;
   $row_count = count($rows);
   if ($having === false || $having === null) {
     $rows = array();
   } else if (!is_scalar($having)) {
     $type = gettype($having);
     $this->pushError('illegal type(%s) of expression on HAVING', $type);
     $rows = array();
   } else if (!is_array($rows) || !is_array(reset($rows))) {
     $rows = array();
   } else {
     if (!is_array($having)) {
       $having = $this->splitSyntax($having);
     }
     $restored = $this->restoreAliasTokens($having);
     if ($restored != null) {
       $having = $restored;
       unset($restored);
     }
     $func_info = $this->parseHaving($having);
     if ($this->hasError()) {
       $rows = array();
     } else {
       $rows_cols_org = array_keys(reset($rows));
       if ($groups == null && $func_info != null) {
         if (!empty($rows) && is_array($rows)) {
           array_splice($rows, 1);
           if (!empty($aggregates) && is_array($aggregates)) {
             array_splice($aggregates, 1);
           } else if ($aggregates == null) {
             $group_single = array(array(0));
             $aggregates = $this->aggregateByGroups($rows, $group_single);
             $this->aggregateAssignByGroups($rows, $aggregates);
           }
         }
       }
       $appended_cols = $this->appendAggregateResult($rows, $aggregates);
       $rows_cols = array_keys(reset($rows));
       if (!empty($func_info) && $this->isEnableHavingInfo($func_info)) {
         $unique_id = 0;
         $unique_vars = array();
         if (!empty($appended_cols) && is_array($appended_cols)) {
           $check = '0000';
           $cols  = 'cols';
           foreach ($func_info as $info) {
             $unique_format = '_posql_having_';
             $unique_more = false;
             foreach ($rows_cols as $key) {
               if (strpos($key, $check) !== false
                || strpos($key, $unique_format) !== false) {
                 $unique_more = true;
                 break;
               }
             }
             $unique_format .= '%04d';
             do {
               $unique_var = sprintf($unique_format, $unique_id++);
               if ($unique_more) {
                 $unique_var .= dechex($unique_id);
               }
             } while (array_key_exists($unique_var, $rows_cols));
             $having = $this->replaceHavingTokens($having, $info,
                                                  $unique_var);
             $unique_vars[$unique_var] = $info[$cols];
           }
           $having = $this->cleanArray($having);
         }
         if (!empty($rows)) {
           if (!empty($unique_vars) && is_array($unique_vars)) {
             $this->_uniqueColsByHaving = array();
             foreach ($unique_vars as $unique_var => $column_name) {
               $this->_uniqueColsByHaving[$unique_var] = $column_name;
               $row_count = count($rows);
               for ($i = 0; $i < $row_count; $i++) {
                 $agg_column = sprintf('%s#%08u', $column_name, $i);
                 if (!is_array($rows[$i])
                  || !array_key_exists($agg_column, $rows[$i])) {
                   $this->pushError('Failed to compile on HAVING');
                   $rows = array();
                   break 2;
                 }
                 $rows[$i][$unique_var] = & $rows[$i][$agg_column];
               }
             }
           }
         }
       }
       if (!empty($rows)) {
         $expr = $this->validExpr($having);
         $this->_uniqueColsByHaving = null;
         $this->execHaving($rows, $aggregates, $expr);
         //debug($rows,'color=orange:***HAVING***;');
         if ($this->hasError()) {
           $rows = array();
         } else {
           $this->diffColumns($rows, $rows_cols_org, true);
         }
       }
     }
   }
   $this->_uniqueColsByHaving = null;
   $this->_onHaving = null;
   return $aggregates;
 }

/**
 * Executes the expression for HAVING clause
 *
 * @param  array   the rows of result sets
 * @param  array   results of all aggregate functions
 * @param  mixed   the expression on HAVING clause
 * @return void
 * @access private
 */
 function execHaving(&$rows, &$aggregates, $expr = true){
   if ($this->hasError()) {
     $rows = array();
   } else if (is_array($expr)) {
     $this->pushError('Failed to parse HAVING clause');
     $rows = array();
   } else if (is_array($rows) && is_array(reset($rows))) {
     if ($expr === false || $expr === null) {
       $rows = array();
     } else if ($expr !== true && is_scalar($expr)) {
       $first = true;
       $row_count = count($rows);
       for ($i = 0; $i < $row_count; $i++) {
         $row = $rows[$i];
         if (empty($this->_onMultiSelect)) {
           $row = $rows[$i];
         } else {
           $row = array();
           foreach ($rows[$i] as $key => $val) {
             $key = $this->toCorrelationName($key);
             $row[$key] = $val;
           }
         }
         if ($first) {
           $first = false;
           if (!$this->safeExpr($row, $expr)) {
             unset($rows[$i], $aggregates[$i]);
           }
           if ($this->hasError()) {
             $rows = array();
             break;
           }
         } else {
           if (!$this->expr($row, $expr)) {
             unset($rows[$i], $aggregates[$i]);
           }
         }
       }
       if (!empty($rows) && is_array($rows)
        && !empty($aggregates) && is_array($aggregates)) {
         $rows = array_values($rows);
         $aggregates = array_values($aggregates);
       }
     }
   }
 }

/**
 * Executes the expression for INSERT query
 *
 * @param  array   A row of result sets
 * @param  mixed   the column, or expression
 * @param  mixed   the value of expression
 * @return mixed   results of expression
 * @access private
 */
 function execInsertExpr($row, $key, $val){
   $result = false;
   if (array_key_exists($key, $row)) {
     $expr = $this->validExpr($row[$key]);
     if (!$this->hasError()) {
       $result = $this->safeExpr($this->_curMeta, $expr);
     }
   } else {
     $result = $val;
   }
   return $result;
 }

/**
 * Executes the expression for UPDATE query
 *  e.g. "UPDATE counter SET count = count + 1"
 *
 * @param  array   A row of result sets
 * @param  mixed   the column, or expression
 * @param  mixed   the value of expression
 * @return mixed   results of expression
 * @access private
 */
 function execUpdateExpr($row, $key, $val){
   $result = false;
   if (array_key_exists($key, $this->_curMeta)) {
     $expr = $this->validExpr($this->_curMeta[$key]);
     if (!$this->hasError()) {
       $result = $this->safeExpr($row, $expr);
     }
   }
   return $result;
 }

/**
 * Executes the expression for SELECT-List clause
 *
 * @param  array   A row of result sets
 * @param  mixed   the column, or expression
 * @return mixed   results of expression
 * @access private
 */
 function execSelectExpr($row, $col){
   $result = false;
   if (is_array($row)) {
     $expr = $this->validExpr($col);
     if (!$this->hasError()) {
       $result = $this->safeExpr($row, $expr);
     }
   }
   return $result;
 }

/**
 * Executes the aggregate function to the rows of the result
 *
 * @param  array   result records
 * @param  string  the aggregate function name
 * @param  string  the name of column which executed aggregate function
 * @param  array   array which has the value of aggregate functions result
 * @return boolean success or failure
 * @access private
 */
 function execAggregateFunction(&$rows, $func, $col, $aggregates){
   $result = false;
   if (!$this->hasError()) {
     if (!empty($rows) && is_array($rows) && is_array(reset($rows))
      && !empty($aggregates)
      && is_array($aggregates) && is_array(reset($aggregates))) {
       $org_col = $col;
       $col = $this->replaceAliasName($col);
       if (!$this->hasError()) {
         $row_count = count($rows);
         $agg_count = count($aggregates);
         $func_name = $this->formatAggregateFunction($func, $org_col);
         $lfunc_name = strtolower($func);
         $reset = reset($aggregates);
         $agg_defaults = $this->getAggregateDefaults();
         if (!array_key_exists($lfunc_name, $agg_defaults)) {
           $this->pushError('Not supported function(%s)', $func);
         } else {
           if ($agg_count < $row_count) {
             $this->pushError('illegal offset(%d) on aggregate function',
                              $agg_count);
           } else {
             if ($col === '*' && $lfunc_name === 'count') {
               $agg_func = 'count_all';
               $col = key($reset);
             } else {
               $agg_func = $lfunc_name;
             }
             for ($i = 0; $i < $row_count; $i++) {
               $val = null;
               if (isset($aggregates[$i][$col][$agg_func])) {
                 $val = $aggregates[$i][$col][$agg_func];
               }
               $rows[$i][$func_name] = $val;
             }
             $result = true;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Executes all the aggregate functions to the rows of the result
 *
 * @param  array   result records
 * @param  array   array which has the value of aggregate functions result
 * @access private
 */
 function execAllAggregateFunction(&$rows, $funcs){
   $length = count($rows);
   if ($length) {
     $aggregates = $this->aggregateAll($rows);
     foreach ($funcs as $funcname => $colname) {
       $lcf = strtolower($funcname);
       $org_col = $colname;
       $colname = $this->replaceAliasName($colname);
       if (!$this->hasError()) {
         if ($colname === '*') {
           if ($lcf !== 'count') {
             $this->pushError('Supplied argument is not valid(%s)',
                              $funcname);
           } else {
             $col = $this->formatAggregateFunction($funcname, $org_col);
             $val = $this->math->add($length, 0);
             for ($i = 0; $i < $length; $i++) {
               $rows[$i][$col] = $val;
             }
           }
         } else {
           $agg = & $aggregates['std'];
           $col = $this->formatAggregateFunction($funcname, $org_col);
           if (!array_key_exists($lcf, $agg)) {
             $this->pushError('Not supported function(%s)', $funcname);
           } else {
             if (array_key_exists($colname, $agg[$lcf])) {
               $val = $agg[$lcf][$colname];
               for ($i = 0; $i < $length; $i++) {
                 $rows[$i][$col] = $val;
               }
             }
           }
           unset($agg);
         }
       }
     }
   }
 }

/**
 * Returns the assigned result row which executed aggregate function
 *
 * @param  number  the result value of COUNT function
 * @param  string  the aggregate function name
 * @param  string  the name of column
 *                  which will be assigned aggregate function
 * @return array   the result row which assigned aggregate function
 * @access private
 */
 function execSimpleCountFunction($count, $func, $col){
   $result = array();
   if (!$this->hasError()) {
     if (is_numeric($count)
      && $func != null && $col != null
      && is_string($func) && is_string($col)) {
       $func_name = $this->formatAggregateFunction($func, $col);
       $result[] = array($func_name => $count);
     }
   }
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Core
 *
 * The base class to dissociate the methods from PUBLIC and PRIVATE
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Core extends Posql_Expr {
/**
 * Check whether the database format is available
 *
 * @param  string   optionally, the file path to check
 * @return boolean  whether it was database or not
 * @access public
 */
 function isDatabase($path = null){
   $result = false;
   if ($path != null) {
     $org_path = $this->path;
     $this->setPath($path);
   }
   $class = $this->getClass() . $this->getVersion(1);
   $length = strlen($class);
   if ($fp = $this->fopen('r')) {
     $heads = $this->fgets($fp);
     fclose($fp);
     if (0 === strncasecmp($heads, $class, $length)) {
       if (substr($heads, -1) === $this->NL
        && rtrim($heads) === rtrim($heads, $this->NL)) {
         $result = true;
       } else {
         $this->pushError('illegal the end of line character.'
                       .  ' Must be LF (0x0A)');
       }
     } else {
       $this->pushError('illegal format or version');
       $result = false;
     }
   }
   if (isset($org_path) && $org_path !== $this->path) {
     $this->setPath($org_path);
   }
   return $result;
 }

/**
 * It is checked whether the table exists
 *
 * @param  string   table name
 * @return boolean  exists or not
 * @access public
 */
 function existsTable($tablename){
   $result = false;
   if (is_string($tablename)) {
     $tname = $this->encodeKey($tablename);
     $meta = $this->getMeta($tablename);
     $result = $meta !== false && is_array($meta);
   }
   return $result;
 }

/**
 * Gets the meta data from database
 *
 * @param  string  table name
 * @return array   meta data of table or false on error
 * @access public
 */
 function getMeta($table = null){
   $result = false;
   if (!$this->hasError()) {
     $tname = $this->encodeKey($table);
     while (($table != null && isset($this->meta[$tname])
         && $this->isLockEx($table)) || $this->isLockAll()) {
       $this->sleep();
     }
     $line = null;
     if ($this->lockShAll()) {
       if ($fp = $this->fopen('r')) {
         $this->fseekLine($fp, 2);
         $line = $this->fgets($fp, true);
         fclose($fp);
       }
       $this->unlockAll();
     }
     if ($line != null) {
       $meta = @($this->decode($line));
       if (!is_array($meta)) {
         $meta = array(array());
       }
       $this->meta = $meta;
     }
     if (!$this->hasError()) {
       if (!is_array($this->meta)) {
         $this->meta = array(array());
       }
       if (!array_key_exists(0, $this->meta)) {
         $this->meta[0] = array();
       }
       $posql_key = $this->getClassKey();
       if (!array_key_exists($posql_key, $this->meta)
        || !is_array($this->meta[$posql_key])) {
         $this->meta[$posql_key] = array();
       }
       $this->_primaryKey  = null;
       $this->_createQuery = null;
       if ($tname != null && isset($this->meta[$posql_key][$tname])
        && is_array($this->meta[$posql_key][$tname])) {
         $def = & $this->meta[$posql_key][$tname];
         $this->_primaryKey  = $this->getValue($def, 'primary');
         $this->_createQuery = $this->getValue($def, 'sql');
         unset($def);
       }
       if (empty($this->_getMetaAll)) {
         unset($this->meta[$posql_key]);
       }
       if ($table == null) {
         $result = $this->meta;
       } else {
         $result = $this->getValue($this->meta, $tname, false);
       }
     }
     if ($result === false) {
       if (!empty($this->_onCreate)) {
         $this->pushError('Cannot load the meta data(%s)', $table);
       }
     }
   }
   return $result;
 }

/**
 * Gets the meta data from the object memory
 *
 * @param  string  optionally, table name
 * @return array   meta data as associate array, or FALSE on error
 * @access public
 */
 function getMetaData($table = null){
   $result = null;
   if (!empty($this->meta) && is_array($this->meta)) {
     if ($table === null) {
       $result = array();
       foreach ($this->meta as $tname => $meta) {
         if ($tname == null) {
           continue;
         }
         $table_name = $this->decodeKey($tname);
         if ($this->isEnableName($table_name)) {
           $result[$table_name] = $meta;
         }
       }
     } else {
       if (is_string($table) && $this->isEnableName($table)) {
         $tname = $this->encodeKey($table);
         if (array_key_exists($tname, $this->meta)) {
           $result = $this->meta[$tname];
         }
       }
     }
   }
   return $result;
 }

/**
 * Gets the meta data from database as associate array
 *
 * @param  string  optionally, table name
 * @return array   meta data as associate array, or empty array
 * @access public
 */
 function getMetaAssoc($table = null){
   $result = array();
   if (!$this->hasError()) {
     if (isset($this->meta)) {
       $meta = $this->meta;
       if (empty($meta)
        || (isset($meta[0]) && $meta[0] == null)
        || ($meta === array(array()))) {
         $meta = $this->getMeta($table);
       }
       if (!empty($meta) && is_array($meta)) {
         if (!is_array(reset($meta))) {
           $meta = array($meta);
         }
         $meta_org = $meta;
         $count = count($meta_org);
         $meta = array();
         while (--$count >= 0) {
           $meta_div = array_shift($meta_org);
           if (!empty($meta_div) && is_array($meta_div)) {
             foreach ($meta_div as $key => $val) {
               $meta[$key] = 1;
             }
           }
         }
       }
       if (!is_array($meta)) {
         $meta = array();
       }
       $result = $meta;
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function getCreateDefinitionMeta($table = null, $field = null){
   $result = false;
   if (!$this->hasError()) {
     $posql_key = $this->getClassKey();
     $this->_getMetaAll = true;
     $this->getMeta();
     if (isset($this->meta[$posql_key])) {
       $metas = & $this->meta[$posql_key];
       if (!empty($metas) && is_array($metas)) {
         $table = (string)$table;
         $field = (string)$field;
         if ($table == null) {
           if ($field == null) {
             $result = $metas;
           } else {
             $result = array();
             foreach ($metas as $tname => $meta) {
               if (is_string($tname)
                && is_array($meta) && array_key_exists($field, $meta)) {
                 $result[$tname] = $meta[$field];
               }
             }
           }
         } else {
           $tname = $this->encodeKey($table);
           $result = $this->getValue($metas, $tname);
           if ($field != null) {
             $result = $this->getValue($result, $tname);
           }
         }
       }
       unset($metas);
       unset($this->meta[$posql_key]);
     }
     $this->_getMetaAll = null;
   }
   return $result;
 }

/**
 * Initializes class variables
 * If file path exists, will registered vacuum() as  the shutdown function
 *
 * @param  string  file path of database
 * @return void
 * @access private
 */
 function init($path = null){
   if (empty($this->_inited)) {
     if (function_exists('date_default_timezone_set')
      && function_exists('date_default_timezone_get')) {
       // PHP VERSION 5+ will still emit a warning in E_STRICT,
       //  it handy keep problems
       @date_default_timezone_set(@date_default_timezone_get());
     }
     // Alias for compatibility
     $this->autoIncrement = & $this->auto_increment;
     $this->isPHP5 = version_compare(PHP_VERSION, 5, '>=');
     $this->isWin  = 0 === strncasecmp(PHP_OS, 'win', 3);
     $this->id     = $this->getUniqId();
     $this->math     = new Posql_Math();
     $this->ctype    = new Posql_CType();
     $this->pcharset = new Posql_Charset();
     $this->unicode  = new Posql_Unicode();
     $this->ecma     = new Posql_ECMA();
     $this->archive  = new Posql_Archive();
     $this->method   = new Posql_Method();
     $this->pcharset->_referProperty($this);
     $this->unicode->_referProperty($this);
     $this->ecma->_referProperty($this);
     $this->archive->_referProperty($this);
     $this->method->_referObject($this);
     if (!$this->isWin) {
       // Note: autoLock should be enabled excluding Windows.
       //       Because, maybe very slows
       //       when locking database by using mkdir() on Windows.
       // @see $autoLock, setAutoLock, getAutoLock
       $this->autoLock = true;
     }
     $this->_inited = true;
   }
   $this->initCorrelationPrefix();
   $this->checkDisableFunctions();
   if ($path != null) {
     $this->setPath($path);
   }
   if (empty($this->_registered)
    && !empty($this->path) && @is_file($this->path)) {
     register_shutdown_function(array(&$this, 'terminate'));
     $this->_registered = true;
   }
   if (!is_array($this->meta) || !isset($this->meta[0])) {
     $this->meta = array(array());
   }
   if (!$this->isEnableId($this->id)) {
     $this->pushError('illegal unique id(%s)', $this->id);
   }
 }

/**
 * The termination process for the exceptive aborted on script
 * Unlocks all that by the instance of this class
 * This method will called automatically by register_shutdown_function()
 *
 * @see    init
 * @param  string optionally, the database filename
 * @param  string optionally, the ID of the Posql's instance (i.e. Posql->id)
 * @return void
 * @access public
 */
 function terminate($path = null, $id = null){
   if ($path != null && $id != null) {
     // below code is urgent calls which needs arguments
     if (is_string($id) && strlen($id) === 8
      && ((empty($this->path)
      ||  (empty($this->path) || strlen($this->id) !== 8)))) {
       $this->setPath($path);
       $this->id = $id;
     }
   }
   unset($path, $id);
   if (empty($this->_terminated)) {
     if (!empty($this->path) && @is_file($this->path)) {
       if (!empty($this->_inTransaction)
        && !empty($this->_transactionName)) {
         // Rollback maybe considered error.
         $this->rollBack();
       }
       if (empty($this->meta)) {
         $this->getMeta();
       }
       $posql_key = $this->getClassKey();
       if (is_array($this->meta)
        && array_key_exists($posql_key, $this->meta)) {
         unset($this->meta[$posql_key]);
       }
       if (!empty($this->autoVacuum)) {
         $this->vacuum();
         $this->autoVacuum = null;
       }
       $tables = array_keys($this->meta);
       if (!in_array(0, $tables)) {
         $tables[] = 0;
       }
       $didall = false;
       foreach ($tables as $table) {
         $this->userAbort = @ignore_user_abort(1);
         if (!$didall && $table == 0) {
           $this->unlockAll();
           $didall = true;
         } else if ($table != null) {
           $org = $this->decodeKey($table);
           if ($this->isEnableName($org)) {
             $this->unlock($org);
           }
         }
       }
       if (!$didall) {
         $this->userAbort = @ignore_user_abort(1);
         $this->unlockAll();
       }
       $this->meta[0] = array();
     }
     // Release all referenced properties, and call destructor
     unset($this->method->posql);
     $props = array(
       'method', 'archive', 'ecma', 'unicode', 'pcharset', 'ctype', 'math'
     );
     foreach ($props as $prop) {
       if ($prop != null && method_exists($this->{$prop}, '__destruct')) {
         $this->{$prop}->__destruct();
       }
     }
     foreach ($props as $prop) {
       if ($prop != null) {
         $this->{$prop} = null;
         unset($this->{$prop});
       }
     }
     unset($this->autoIncrement, $this->id, $props);
     $this->_terminated = true;
   }
 }

/**
 * Delays the program execution for database locking
 *
 * @param  number  halt time in micro seconds. (default = 100000 = 0.1 sec.)
 * @return void
 * @access private
 */
 function sleep($msec = 100000){
   if ($this->isWin && !$this->isPHP5) {
     sleep(1);
   } else {
     usleep($msec);
   }
 }

/**
 * initialize the internal correlation prefix
 *
 * @param  void
 * @return void
 * @access private
 */
 function initCorrelationPrefix(){
   if (empty($this->_correlationPrefix)) {
     $this->_correlationPrefix = null;
   }
   $prefix = & $this->_correlationPrefix;
   if ($prefix == null || !is_string($prefix)) {
     $prefix = $this->getClassName(true);
   }
   unset($prefix);
 }

/**
 * Returns the current file path of the database
 *
 * @param  void
 * @return string  file path of the database
 * @access public
 */
 function getPath(){
   return $this->path;
 }

/**
 * Sets the file path of the database
 *
 * @param  string  file path of the database
 * @return void
 * @access public
 */
 function setPath($path){
   $ext = $this->toExt();
   $dir = dirname($path);
   if ($dir != null) {
     $dir .= '/';
   }
   $path = $dir . basename($path, $ext) . $ext;
   $this->path = $this->fullPath($path);
 }

/**
 * Returns the current database name
 *
 * @param  void
 * @return string  the database name
 * @access public
 */
 function getDatabaseName(){
   $result = false;
   $path = $this->getPath();
   if ($path != null) {
     $ext = $this->toExt();
     $result = basename($path, $ext);
   }
   return $result;
 }

/**
 * Returns the tablename of the most recent result
 *
 * @param  void
 * @return mixed   the tablename of the most recent result
 * @access public
 */
 function getTableNames(){
   $result = null;
   if (!empty($this->_selectTableNames)) {
     $result = $this->_selectTableNames;
   } else if (!empty($this->tableName)) {
     $result = $this->decodeKey($this->tableName);
   }
   return $result;
 }

/**
 * Returns the table alias of the most recent result
 *
 * @param  void
 * @return array  the table alias of the most recent result
 * @access public
 */
 function getTableAliasNames(){
   $result = array();
   if (isset($this->_selectTableAliases)) {
     $result = $this->_selectTableAliases;
   }
   return $result;
 }

/**
 * Returns the column alias of the most recent result
 *
 * @param  void
 * @return array  the column alias of the most recent result
 * @access public
 */
 function getColumnAliasNames(){
   $result = array();
   if (isset($this->_selectColumnAliases)) {
     $result = $this->_selectColumnAliases;
   }
   return $result;
 }

/**
 * Returns the tablename of the most recent result
 *
 * @param  void
 * @return string  the tablename of the most recent result
 * @access public
 */
 function getTableName(){
   $result = null;
   if (isset($this->tableName)
    && is_string($this->tableName) && $this->tableName != null) {
     $result = $this->decodeKey($this->tableName);
   }
   return $result;
 }

/**
 * Return the "rowid" of the last row insert
 *  from this connection to the database.
 *
 * @param  void
 * @return number  the value to inserted "rowid" or NULL
 * @access public
 */
 function getLastInsertId(){
   $result = null;
   if (isset($this->next)) {
     $result = $this->math->sub($this->next, 1);
     if ($result < 0) {
       $result = '0';
     }
   }
   return $result;
 }

/**
 * Get the table definition and information
 *
 * @param  string  table name, (or null = all tables)
 * @return array   the information as array, or FALSE on error
 * @access public
 */
 function getTableInfo($table = null){
   $result = false;
   $infos = $this->_getTableInfo($table);
   if (!empty($infos) && is_array($infos)) {
     $encodekey = $this->encodeKey($table);
     if (!is_array(reset($infos))) {
       $infos = array(
         $encodekey => $infos
       );
     }
     $posql_key = $this->getClassKey();
     $sql_infos = $this->getCreateDefinitionMeta(null, 'sql');
     $defaults = array(
       'lockid'   => null,
       'lockmode' => null,
       'nextid'   => null,
       'lastmod'  => null,
       'sql'      => null
     );
     $i = 0;
     $result = array();
     foreach ($infos as $tname => $info) {
       if (!is_array($info)) {
         break;
       }
       if (isset($sql_infos[$tname])) {
         $info['sql'] = $sql_infos[$tname];
       }
       $info = $info + $defaults;
       if (strcasecmp($tname, $posql_key) === 0) {
         $name = $this->getDatabaseName();
         if ($name == null) {
           $name = $tname;
         }
       } else {
         $name = $this->decodeKey($tname);
       }
       $result[$i] = array(
         'name' => $name
       );
       foreach ($info as $key => $val) {
         switch (strtolower($key)) {
           case 'lockid':
               $key = 'lock_id';
               break;
           case 'lockmode':
               $key = 'lock_mode';
               break;
           case 'nextid':
               $key = 'next_id';
               break;
           case 'lastmod':
               $key = 'last_modified';
               $val = $this->method->now($val);
               break;
           case 'sql':
               $key = 'sql';
               break;
           default:
               $key = null;
               break;
         }
         if ($key != null) {
           $result[$i][$key] = $val;
         }
       }
       ++$i;
     }
     if ($result != null && is_array($result)) {
       $template = array(
         'name',    'next_id',   'last_modified',
         'lock_id', 'lock_mode', 'sql'
       );
       $this->sortByTemplate($result, $template);
     }
   }
   return $result;
 }

/**
 * Returns the information of current tables
 *
 * @param  string  table name, (or null = all tables)
 * @return array   the information as array, or FALSE on error
 * @access private
 */
 function _getTableInfo($table = null){
   $result = false;
   $error  = false;
   while ($this->isLockExAll()
       || ($table != null && $this->isLockEx($table))) {
     $this->sleep();
   }
   $heads = null;
   $infos = null;
   if ($this->lockShAll()) {
     if ($fp = $this->fopen('r')) {
       $heads = $this->fgets($fp, true);
       $infos = $this->fgets($fp, true);
       fclose($fp);
     }
     $this->unlockAll();
   }
   if ($heads !== null && $infos !== null) {
     $modes = array();
     $delm = '@';
     $lock = substr($heads, -10);
     if (strpos($lock, $delm) === false) {
       $error = $delm;
     } else {
       list($mode, $id) = explode($delm, $lock);
       $modes[$this->getClassKey()] = array(
         'lockid'   => $id,
         'lockmode' => $this->assignLockMode($mode)
       );
       if ($infos != null) {
         $delm = ';';
         if (strpos($infos, $delm) === false) {
           $error = $delm;
         } else {
           $split = explode($delm, $infos);
           foreach ($split as $tableinfo) {
             $tableinfo = trim($tableinfo);
             if ($tableinfo == null) {
               continue;
             }
             $delm = ':';
             if (strpos($tableinfo, $delm) === false) {
               $error = $delm;
               break;
             }
             list($tablename, $info) = explode($delm, $tableinfo);
             $delm = '#';
             if (strpos($info, $delm) === false) {
               $error = $delm;
               break;
             }
             list($lockinfo, $inctimes) = explode($delm, $info);
             $delm = '@';
             if (strpos($lockinfo, $delm) === false) {
               $error = $delm;
               break;
             }
             list($mode, $id) = explode($delm, $lockinfo);
             if (strpos($inctimes, $delm) === false) {
               $error = $delm;
               break;
             }
             list($inc, $mtime) = explode($delm, $inctimes);
             $modes[$tablename] = array(
               'lockid'   => $id,
               'lockmode' => $this->assignLockMode($mode),
               'nextid'   => $this->math->add($inc, 0),
               'lastmod'  => $mtime - 0
             );
           }
         }
       }
     }
     if ($error) {
       $this->pushError('Database might be broken: char(%s)', $error);
       $result = false;
     } else if (!empty($modes)) {
       if ($table == null) {
         $result = $modes;
       } else {
         if (is_string($table)) {
           $tname = $this->encodeKey($table);
           if (!array_key_exists($tname, $modes)) {
             $this->pushError('Not exists the table(%s)', $table);
             $result = false;
           } else {
             $result = $modes[$tname];
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Gets the last modified time as unix timestamp from the headers of table
 *
 * @param  string  table name
 * @return int     last modified time, or FALSE on error
 * @access public
 */
 function getLastMod($table){
   $result = false;
   $info = $this->_getTableInfo($table);
   if (isset($info['lastmod'])) {
     $result = $info['lastmod'];
   }
   return $result;
 }

/**
 * Sets the last modified time as unix timestamp into the table
 *
 * @param  string  table name
 * @return boolean success or not
 * @access public
 */
 function setLastMod($table){
   static $length = null;
   if ($length === null) {
     $length = strlen(sprintf('0@%08x#%010u@', 0, 0));
   }
   $result = false;
   while ($this->isLockAll()
       || ($table != null && $this->isLockEx($table))) {
     $this->sleep();
   }
   $tname = $this->encodeKey($table);
   if ($this->lockExAll()) {
     if ($fp = $this->fopen('r+')) {
       $head = $this->fgets($fp);
       $line = $this->fgets($fp);
       $ltnm = $tname . ':';
       $pos = strpos($line, $ltnm);
       $plus = $length;
       if ($pos === false) {
         $this->pushError('Failed in setLastMod(%s)', $table);
         $result = false;
       } else {
         $seekpos = strlen($head) + $pos + strlen($ltnm) + $plus;
         fseek($fp, $seekpos);
         $this->fputs($fp, sprintf('%010u', time()));
         $result = true;
       }
       fclose($fp);
     }
     $this->unlockAll();
   }
   return $result;
 }

/**
 * Gets the next sequence id from the table
 *
 * @param  string  table name
 * @return number  next sequence id, or false
 * @access public
 */
 function getNextId($table){
   $result = false;
   $info = $this->_getTableInfo($table);
   if (isset($info['nextid'])) {
     $result = $info['nextid'];
   }
   return $result;
 }

/**
 * Sets the next sequence id into the table
 *
 * @param  string  table name
 * @param  number  next sequence id
 * @return boolean success or not
 * @access public
 */
 function setNextId($table, $next, $unlock = false){
   $result = false;
   while ($this->isLockAll() || $this->isLockEx($table)) {
     $this->sleep();
   }
   $tname = $this->encodeKey($table);
   if ($this->lockExAll()) {
     if ($fp = $this->fopen('r+')) {
       $head = $this->fgets($fp);
       $line = $this->fgets($fp);
       $ltnm = $tname . ':';
       $pos = strpos($line, $ltnm);
       if ($pos === false) {
         $this->pushError('Failed in setNexId(%s)', $table);
         $result = false;
       } else {
         $seekpos = strlen($head) + $pos + strlen($ltnm) + 10;
         fseek($fp, $seekpos);
         $puts = sprintf('#%010.0f@%010u', $next, time());
         $this->fputs($fp, $puts);
         $result = true;
       }
       fclose($fp);
     }
     $this->unlockAll();
   }
   return $result;
 }

/**
 * Returns string as the hexadecimal number of eight digit for lock key
 *
 * @param  string  input value of number
 * @return string  hex number of 8 digit
 * @access public
 */
 function toUniqId($id){
   if ($id == null || !is_string($id)
    || strlen($id) !== 8  || !$this->ctype->isXdigit($id)) {
     $id = sprintf('%08x', $id);
   }
   if (strlen($id) !== 8) {
     $id = str_repeat('0', 8);
   }
   return $id;
 }

/**
 * Returns unique id as the hexadecimal number of eight digit
 *
 * @param  void
 * @return string  hex number of 8 digit
 * @access public
 */
 function getUniqId(){
   $id = $this->toUniqId(crc32(uniqid(mt_rand(), true)));
   return $id;
 }

/**
 * Returns the unique id of instance object
 *
 * @param  void
 * @return string
 * @access public
 */
 function getId(){
   $result = $this->id;
   return $result;
 }

/**
 * Check whether the id is enabled
 *
 * @see toUniqId, getUniqId
 *
 * @param  string  the target id as string
 * @return boolean whether the id was enabled
 * @access public
 */
 function isEnableId($id){
   $result = false;
   if (is_string($id) && strlen($id) === 8 && $this->ctype->isXdigit($id)) {
     $result = true;
   }
   return $result;
 }

/**
 * Check whether the object is instance of Posql_Statement
 *
 * @param  object  the target object to check
 * @return boolean whether the object is instance of statement class
 * @access public
 */
 function isStatementObject($object){
   $result = false;
   if (is_object($object)
    && strcasecmp(get_class($object), 'Posql_Statement') === 0) {
     $result = true;
   }
   return $result;
 }

/**
 * Returns this class name
 *
 * @param  boolean whether the name of all lowercase or not
 * @return string  class name
 * @access public
 * @static
 */
 function getClass($tolower = false){
   static $class;
   if (!$class) {
     $class = strtolower(__CLASS__);
     if (strpos($class, '_')) {
       $class = explode('_', $class);
       $class = reset($class);
     }
     $class = array(
       0 => @ucfirst($class),
       1 => $class
     );
   }
   $key = 0;
   if ($tolower) {
     $key = 1;
   }
   return $class[$key];
 }

/**
 * Alias of getClass()
 *
 * @param  boolean whether the name of all lowercase or not
 * @return string  class name
 * @access public
 */
 function getClassName($tolower = false){
   $result = $this->getClass($tolower);
   return $result;
 }

/**
 * Returns the name of this class as private fields key
 * This is used for an internal process of Posql
 *
 * @param  void
 * @return string  the key name of this class
 * @access public
 */
 function getClassKey(){
   static $classkey;
   if (!$classkey) {
     $classkey = sprintf('.%s', $this->getClass());
   }
   return $classkey;
 }

/**
 * Generate the key to use on transaction
 *
 * @param  void
 * @return string  the key to use on transaction
 * @access public
 */
 function getTransactionKey(){
   $key = sprintf('.trans-%s.', $this->id);
   $result = $this->encodeKey($key);
   return $result;
 }

/**
 * Returns this class version
 *
 * @param  boolean whether to get only the major version or not
 * @return string  class version
 * @access public
 * @static
 */
 function getVersion($major = false){
   static $versions;
   if (!$versions) {
     $version = '2.18a';
     $pos = strpos($version, '.');
     if ($pos === false) {
       $versions = array(
         0 => $version,
         1 => $version
       );
     } else {
       $versions = array(
         0 => $version,
         1 => substr($version, 0, $pos)
       );
     }
   }
   $key = 0;
   if ($major) {
     $key = 1;
   }
   return $versions[$key];
 }

/**
 * Returns the file headers of database.
 * Includes the string valid for access directly to it
 *
 * @param  void
 * @return string  headers as string
 * @access private
 */
 function getHeader(){
   static $header;
   if (!$header) {
     $header = sprintf('%s%0.2f format<'
                    .  '?php exit;__halt_compiler();?'
                    .  '>#LOCK=',
       $this->getClass(),
       $this->getVersion()
     );
   }
   return $header;
 }

/**
 * Returns the table delimiters on format of Posql database
 *
 * @param  boolean whether or not to get as list of array
 * @return array   the table delimiters
 * @access public
 */
 function getTableDelimiters($list = false){
   $result = array(
     $this->DELIM_CACHE => 0,
     $this->DELIM_INDEX => 1,
     $this->DELIM_TABLE => 2,
     $this->DELIM_VIEW  => 3
   );
   if ($list) {
     $result = array_flip($result);
   }
   return $result;
 }

/**
 * Return the extra alias message for DESCRIBE command
 *
 * @param  void
 * @return string  the extra alias message
 * @access public
 */
 function getExtraAliasMessage(){
   return 'alias for rowid';
 }

/**
 * Gets the operation mode of SQL Expression as the engine
 *
 * @see $engine
 * @param  boolean  whether the name of all lowercase or all uppercase
 * @return string   the operation mode of SQL Expression
 * @access public
 */
 function getEngine($tolower = false){
   $engine = $this->engine;
   if ($tolower) {
     $engine = strtolower($engine);
   } else {
     $engine = strtoupper($engine);
   }
   return $engine;
 }

/**
 * Sets the operation mode of SQL Expression as the engine
 *
 * @see $engine
 * @param  string   the operation mode of SQL Expression as string
 *                  supported modes are "SQL" or "PHP" now
 * @return string   the previous operation mode, or FALSE on error
 * @access public
 */
 function setEngine($engine){
   $result = false;
   if (!is_string($engine)) {
     $this->pushError('An illegal argument,'
                   .  ' and only the string is available');
   } else {
     $engine = strtolower($engine);
     switch ($engine) {
       case 'sql':
            $result = $this->getEngine();
            $this->engine = $engine;
            break;
       case 'php':
            $result = $this->getEngine();
            $this->engine = $engine;
            break;
       case 'js': // so enjoyable if implements in the future.
       case 'ecma':
       default:
            $this->pushError('Not supported the expression engine(%s)',
                             $engine);
            $result = false;
            break;
     }
   }
   return $result;
 }

/**
 * Set the value of maximal minutes as timeout for the dead lock
 *
 * @see getDeadLockTimeout, $deadLockTimeout
 * @param  number  the value of maximal minutes
 * @return boolean success or failure
 * @access public
 */
 function setDeadLockTimeout($timeout = 10){
   $result = false;
   if ($timeout != null && is_numeric($timeout)
    && $timeout >= 1 && $timeout <= $this->MAX) {
     $this->deadLockTimeout = (int) $timeout;
     $result = true;
   } else {
     $this->pushError('The deadLockTimeout should be larger than 1'
                   .  ' in the numerical value(%s)', $timeout);
     $result = false;
   }
   return $result;
 }

/**
 * Set the value of auto_increment that will be used as "rowid".
 * This value should be larger than 1 in the numerical value.
 *
 * @see getAutoIncrement, $autoIncrement, $auto_increment
 * @param  number  the value of auto_increment
 * @return boolean success or failure
 * @access public
 */
 function setAutoIncrement($auto_increment = 1){
   $result = false;
   if ($auto_increment != null && is_numeric($auto_increment)
    && $auto_increment >= 1 && $auto_increment <= $this->MAX) {
     $this->autoIncrement = (int) $auto_increment;
     $result = true;
   } else {
     $this->pushError('The auto_increment should be larger than 1'
                   .  ' in the numerical value(%s)', $auto_increment);
     $result = false;
   }
   return $result;
 }

/**
 * Set the type for supplements which omitted data type.
 * This type adjust by the "CREATE TABLE" and "DESCRIBE" commands.
 *
 * @see getDefaultDataType, $defaultDataType
 *
 * @param  string  the type for supplements which omitted data type
 * @return mixed   it will return the old data type on success,
 *                   or FALSE that settings had an error
 * @access public
 */
 function setDefaultDataType($type){
   $result = false;
   if (is_string($type) && strlen($type) < $this->MAX) {
     $type = trim($type);
     $result = $this->defaultDataType;
     $this->defaultDataType = $type;
   }
   return $result;
 }

/**
 * Sets the value of the database extension
 *
 * @see $ext, Posql_Config
 * @param  string  the value of the database extension
 * @return mixed   it will return the old value of extension on success,
 *                   or FALSE that settings had an error
 * @access public
 */
 function setExt($ext){
   $result = false;
   if ($ext == null) {
     $result = $this->ext;
     $this->ext = null;
   } else if (is_string($ext)) {
     $result = $this->ext;
     $this->ext = $this->toExt($ext);
   } else {
     $ext = is_array($ext) ? implode(',', $ext) : strval($ext);
     $this->pushError('illegal the value of the argument as extension(%s)',
                      $ext);
   }
   return $result;
 }

/**
 * Sets the value of maximum bytes which handle the size of associable all
 *
 * @see $MAX, Posql_Config
 * @param  number  the value of miximum bytes
 * @return mixed   it will return the old value of maximum on success,
 *                    or FALSE that settings had an error
 * @access public
 */
 function setMax($max){
   $result = false;
   if (is_numeric($max) && $max > 0) {
     $result = $this->MAX;
     $this->MAX = $max;
   } else {
     $this->pushError('illegal the value of the argument as MAX(%s)', $max);
   }
   return $result;
 }

/**
 * Applies the serializing function of the given argument
 * The function is must able to serializing all PHP's values
 *
 * @see encode(), $encoder, Posql_Config
 * @param  mixed   the serializing function
 * @return mixed   it will return the old value of serializer on success,
 *                   or FALSE on error
 * @access public
 */
 function setEncoder($encoder){
   $result = false;
   if (is_callable($encoder)) {
     $result = $this->encoder;
     $this->encoder = $encoder;
   } else {
     $e = implode('::', (array) $encoder);
     $this->pushError('Cannot call the encoder function(%s)', $e);
   }
   return $result;
 }

/**
 * Applies the unserializing function of the given argument
 * The function is must able to unserializing to PHP's value
 *
 * @see decode(), $decoder, Posql_Config
 * @param  mixed   the unserializing function
 * @return mixed   it will return the old value of unserializer on success,
 *                   or FALSE on error
 * @access public
 */
 function setDecoder($decoder){
   $result = false;
   if (is_callable($decoder)) {
     $result = $this->decoder;
     $this->decoder = $decoder;
   } else {
     $e = implode('::', (array) $decoder);
     $this->pushError('Cannot call the decoder function(%s)', $e);
   }
   return $result;
 }

/**
 * @access private
 */
 function _setRegistered($registered){
   $this->_registered = (bool)$registered;
 }

/**
 * @access private
 */
 function _getRegistered(){
   return $this->_registered;
 }

/**
 * @access private
 */
 function _setTerminated($terminated){
   $this->_terminated = (bool)$terminated;
 }

/**
 * @access private
 */
 function _getTerminated(){
   return $this->_terminated;
 }

/**
 * @access private
 */
 function _setLockedTables($tables){
   if (is_array($tables)) {
     $this->_lockedTables = $tables;
   }
 }

/**
 * @access private
 */
 function _getLockedTables(){
   return $this->_lockedTables;
 }

/**
 * Using the query of INSERT that for array_map() function
 *
 * @param  array   a record of target
 * @return array   array of adjusted callback
 * @access private
 */
 function _addMap($put){
   $add = array();
   $increment = false;
   foreach ($this->_curMeta as $key => $val) {
     switch ($key) {
       case 'rowid':
       case $this->_primaryKey:
           $add[$key] = $this->next;
           $increment = true;
           break;
       case 'ctime':
       case 'utime':
           $add[$key] = time();
           break;
       default:
           if (empty($this->_execExpr)) {
             $add[$key] = $this->getValue($put, $key, $val);
           } else {
             if (array_key_exists($key, $put)) {
               $add[$key] = $this->execInsertExpr($put, $key, $val);
             } else {
               $add[$key] = $val;
             }
           }
           break;
     }
   }
   if ($increment) {
     if ($this->autoIncrement) {
       $this->next = $this->math->add(
                       (int)$this->autoIncrement,
                       $this->next
       );
       //XXX: warn the message
       // The maximum value of "rowid" is 9,999,999,999 now.
       // It is necessary to warn because this value exceeds
       //  integer of PHP.
       if (strlen($this->next) > 10) {
         $this->pushError('Over the auto increment value(%s)',
                           $this->next);
         $this->next = 1;
       }
     }
   }
   $result = $this->tableName . $this->DELIM_TABLE . $this->encode($add)
                                                   . $this->NL;
   if (strlen($result) > $this->MAX) {
     $this->pushError('Over the maximum length');
     $result = null;
   }
   return $result;
 }

/**
 * Using the query of UPDATE that for array_map() function
 *
 * @param  array   a record of target
 * @return array   array of adjusted callback
 * @access private
 */
 function _upMap($row){
   foreach ($row as $key => $val) {
     switch ($key) {
       case 'utime':
           $row[$key] = time();
           break;
       default:
           if (empty($this->_execExpr)) {
             $row[$key] = $this->getValue($this->_curMeta, $key, $val);
           } else {
             if (array_key_exists($key, $this->_curMeta)) {
               $row[$key] = $this->execUpdateExpr($row, $key, $val);
             } else {
               $row[$key] = $val;
             }
           }
           break;
     }
   }
   $result = $this->tableName . $this->DELIM_TABLE . $this->encode($row)
                                                   . $this->NL;
   if (strlen($result) > $this->MAX) {
     $this->pushError('Over the maximum length(%.0f)', strlen($result));
     $result = null;
   }
   return $result;
 }

/**
 * Restores the updated records on error
 * This method to used by array_map() function
 *
 * @param  array   a record of target
 * @return array   array of adjusted callback
 * @access private
 */
 function _upRestoreMap($row){
   $result = $this->tableName . $this->DELIM_TABLE . $this->encode($row)
                                                   . $this->NL;
   return $result;
 }

/**
 * Callback function for queryf()
 *
 * @access private
 */
 function _queryFormatCallback($match, $set_args = false){
   static $args = array(), $q = "'";
   if ($set_args) {
     if (is_array($match) && is_array(reset($match))) {
       $args = reset($match);
     } else {
       $args = (array) $match;
     }
     return;
   }
   $result = null;
   $length = null;
   $quotes = null;
   $escaped = false;
   $recursive = false;
   $format = end($match);
   $format_index = key($match);
   $quotes_index = 1;
   $length_index = 3;
   reset($match);
   if (isset($match[$length_index])
    && is_numeric($match[$length_index])) {
     $length = (int) $match[$length_index];
   }
   switch ($format) {
     case '%':
     case '%%':
         $result = '%';
         break;
     case 'a':
         $arg = array_shift($args);
         switch (true) {
           case is_int($arg):
           case is_float($arg):
               $match[$format_index] = 'n';
               array_unshift($args, $arg);
               $result = $this->_queryFormatCallback($match);
               $recursive = true;
               break;
           case is_bool($arg):
               $result = $arg ? 'TRUE' : 'FALSE';
               break;
           case is_string($arg):
               $quotes = $this->getValue($match, $quotes_index);
               if ($quotes == null) {
                 $match[$format_index] = 'q';
               } else {
                 $match[$format_index] = 's';
               }
               array_unshift($args, $arg);
               $result = $this->_queryFormatCallback($match);
               $recursive = true;
               break;
           case is_null($arg):
               $result = 'NULL';
               break;
           case is_array($arg):
           case is_object($arg):
           default:
               $error = gettype($arg);
               $this->pushError('Must be scaler type'
                             .  ' on variable(%s)', $error);
               $result = 'NULL';
               break;
         }
         break;
     case 'B':
         $result = (string)array_shift($args);
         $result = $q . base64_encode($result) . $q;
         $result = sprintf('base64_decode(%s)', $result);
         $escaped = true;
         break;
     case 'n':
         $result = array_shift($args);
         if (!is_numeric($result)) {
           $result = '0';
         } else {
           $result = $this->math->format($result);
           if ($result >= $this->MAX) {
             if (ord($result) !== ord($q)) {
               $result = $q . $result . $q;
             }
             $quotes = $q;
           }
         }
         $escaped = true;
         break;
     case 'q':
         if (array_key_exists($quotes_index, $match)) {
           if ($match[$quotes_index] == null) {
             $match[$quotes_index] = $q;
           }
         }
     case 's':
         $quotes = $this->getValue($match, $quotes_index);
         $result = array_shift($args);
         if ($quotes == null) {
           $result = $this->escapeString($result);
         } else {
           $result = $this->escapeString($result, $quotes);
         }
         $escaped = true;
         break;
     default:
         $result = sprintf($format, array_shift($args));
         break;
   }
   if (!$recursive) {
     if ($length !== null && strlen($result) > $length) {
       $marker = $this->getValue($match, 2);
       $result = $this->truncatePad($result, $length,
                                    $marker, $quotes, $escaped);
     }
   }
   return $result;
 }

/**
 * Format the SQL statement based on format of sprintf().
 *
 * @see    queryf
 * @param  string       the SQL statement as formatted as sprintf()
 * @param  mixed  (...) zero or more parameters to be passed to the function
 * @return string       formatted statement as string
 * @access public
 */
 function formatQueryString($query){
   static $opted = false, $pattern = '
   {(?:
       ([\'"])?
       %
       \'?(\.+|\s|(?!-)[^.\'\s%0-9aBnqs]*)
       ((?!0)[0-9]*)
       (?(1)([as])\1|([%aBnqs]))
     |
       %
       (?:[0-9]+[$]|)[+-]?
       (?:[0\s]|\'.|)-?
       (?:[0-9]*|)
       (?:\.[0-9]+|)
       [%bcdeufFosxX]
    )
   }uUx';
   if (!$opted) {
     $pattern = preg_replace('{\s+|x$}', '', $pattern);
     $opted = true;
   }
   $result = '';
   $args = func_get_args();
   $argn = func_num_args();
   if ($argn > 1) {
     array_shift($args);
     if (is_array(reset($args))) {
       $args = array_shift($args);
     }
     $this->_queryFormatCallback($args, true);
     $result = preg_replace_callback($pattern,
                array(&$this, '_queryFormatCallback'), $query);
   } else if (is_array($query) && is_string(reset($query))) {
     $result = call_user_func_array(array(&$this,
                                          'formatQueryString'), $query);
   } else if (is_string($query)) {
     $result = $query;
   } else {
     $error_type = gettype($query);
     $this->pushError('illegal types in arguments on formatQueryString(%s)',
                      $error_type);
     $result = false;
   }
   //debug($result, '//___formatted___//');
   return $result;
 }

/**
 * Merges an array these always will be appended as extra columns
 *
 * @note
 *  below are the extra columns
 * ----------------------------------------------------------------------
 *  rowid - works like SQLite's "ROWID" as unique key and auto increment
 *  ctime - created time as unix time
 *  utime - updated time as unix time
 * ----------------------------------------------------------------------
 * @param  array   an array of target
 * @return array   an array which merged with extra columns
 * @access private
 * @static
 */
 function mergeDefaults($array){
   $result = array();
   $time = time();
   $defaults = array(
     'rowid' => 0,
     'ctime' => $time,
     'utime' => $time
   );
   $result = array_merge((array)$array, $defaults);
   return $result;
 }

/**
 * applies serializing to the given function
 * Substitute the function name for $encoder if you modify it
 *
 * @param  mixed  input values
 * @return string
 * @access public
 */
 function encode($value){
   return base64_encode(call_user_func($this->encoder, $value));
 }

/**
 * applies unserializing to the given function
 *
 * @param  string  input values
 * @return array
 * @access public
 */
 function decode($value){
   return call_user_func($this->decoder, base64_decode($value));
 }

/**
 * Encodes for table name as base 64
 *
 * @param  string  input values
 * @return string
 * @access public
 * @static
 */
 function encodeKey($table){
   return base64_encode($table);
 }

/**
 * Decodes for table name as base 64
 *
 * @param  string  input values
 * @return string
 * @access public
 * @static
 */
 function decodeKey($table){
   return base64_decode($table);
 }

/**
 * Returns the default value of arguments on SELECT method
 *
 * @see    Posql_Parser::parseSelectArguments, Posql::select
 *
 * @param  mixed    index of target arguments
 * @return mixed    default value to associate
 * @access private
 */
 function getSelectDefaultArguments($index = null){
   static $args = array(
     'from'   => null,
     'select' => '*',  'where'  => true,
     'group'  => null, 'having' => true,
     'order'  => null, 'limit'  => null
   );
   $result = null;
   if ($index === null) {
     $result = $args;
   } else {
     if (is_int($index)) {
       $result = $this->getValue(array_values($args), $index);
     } else {
       $result = $this->getValue($args, $index);
     }
   }
   return $result;
 }

/**
 * Returns the array which has default values for aggregate functions
 *
 * @param  boolean  whether "count_all" is included or not
 * @return array    the array which has default values
 * @access private
 * @static
 */
 function getAggregateDefaults($with_count_all = false){
   static $defaults = array(
     'avg'   => null,
     'count' => 0,
     'max'   => null,
     'min'   => null,
     'sum'   => null
   );
   $result = $defaults;
   if ($with_count_all) {
     $result['count_all'] = 0;
   }
   return $result;
 }

/**
 * Returns the array to generate variable with extract() function
 *
 * @param  boolean  whether "count_all" is included or not
 * @return array    array to generate variable with extract() function
 * @access private
 */
 function getAggregateExtract($with_count_all = false){
   $result = array();
   $aggregates = $this->getAggregateDefaults($with_count_all);
   foreach ($aggregates as $name => $val) {
     $result[$name] = $name;
   }
   return $result;
 }

/**
 * Executes the all aggregate functions to result rows
 *
 * @param  array   result records
 * @return array   array of result of all aggregate functions
 * @access private
 */
 function aggregateAll(&$rows){
   $std = array();
   $all = array();
   if (is_array($rows) && is_array(reset($rows))) {
     $row_count = count($rows);
     $cols = array_keys(reset($rows));
     $funcs = array_keys($this->getAggregateDefaults());
     foreach ($funcs as $func) {
       foreach ($cols as $col) {
         $std[$func][$col] = null;
         $all[$func][$col] = null;
       }
     }
     extract($this->getAggregateExtract());
     for ($i = 0; $i < $row_count; $i++) {
       foreach ($rows[$i] as $key => $val) {
         if ($val !== null) {
           $std[$count][$key] = $this->math->add($std[$count][$key], 1);
           if ($val > $std[$max][$key]) {
             $std[$max][$key] = $val;
           }
           if (!$i || $val < $std[$min][$key]) {
             $std[$min][$key] = $val;
           }
           $std[$sum][$key] = $this->math->add($std[$sum][$key], $val);
         }
         $all[$count][$key] = $this->math->add($all[$count][$key], 1);
         if ($val > $all[$max][$key]) {
           $all[$max][$key] = $val;
         }
         if (!$i || $val < $all[$min][$key]) {
           $all[$min][$key] = $val;
         }
         $all[$sum][$key] = $this->math->add($all[$sum][$key], $val);
       }
     }
     foreach ($cols as $col) {
       $std[$avg][$col] = $this->math->div($std[$sum][$col],
                                           $std[$count][$col],
                                           4);
       $all[$avg][$col] = $this->math->div($all[$sum][$col],
                                           $all[$count][$col],
                                           4);
     }
   }
   $result = array(
     'std' => $std,
     'all' => $all
   );
   return $result;
 }

/**
 * Executes all aggregate functions for the rows which has groups
 *
 * @param  array   result records
 * @param  array   array with index of row which has a group
 * @return array   array of result of all aggregate functions
 * @access private
 */
 function aggregateByGroups(&$rows, $groups){
   $result = array();
   if (!$this->hasError()) {
     if (!empty($rows) && is_array($rows) && is_array(reset($rows))) {
       $agg = array();
       $agg_defaults = $this->getAggregateDefaults(true);
       extract($this->getAggregateExtract(true));
       $rows_cols = array_keys(reset($rows));
       foreach ($groups as $i => $array) {
         $agg[$i] = array();
         foreach ($rows_cols as $col) {
           $agg[$i][$col] = $agg_defaults;
           foreach ($array as $index) {
             $val = & $rows[$index][$col];
             $agi = & $agg[$i][$col];
             if ($val !== null) {
               $agi[$count] = $this->math->add($agi[$count], 1);
               if ($val > $agi[$max]) {
                 $agi[$max] = $val;
               }
               if ($agi[$min] === null
                || $agi[$min]  >  $val) {
                 $agi[$min] = $val;
               }
               $agi[$sum] = $this->math->add($agi[$sum], $val);
             }
             $agi[$count_all] = $this->math->add($agi[$count_all], 1);
           }
           $agi[$avg] = $this->math->div($agi[$sum], $agi[$count], 4);
         }
         $result[] = $agg[$i];
       }
       unset($val, $agi);
     }
   }
   //debug($result,"color=green:Groups;");
   return $result;
 }

/**
 * Executes all aggregate functions for the rows which has not groups
 *
 * @param  array   result records
 * @param  array   array which was grouped by aggregate functions
 * @return void
 * @access private
 */
 function aggregateAssignByGroups(&$rows, &$groups){
   if (!$this->hasError()) {
     if (!empty($rows)   && is_array($rows)   && is_array(reset($rows))
      && !empty($groups) && is_array($groups) && is_array(reset($groups))) {
       $agg_defaults = $this->getAggregateDefaults(true);
       extract($this->getAggregateExtract(true));
       $row_count = count($rows);
       $rows_cols = array_keys(reset($rows));
       for ($i = 0; $i < $row_count; $i++) {
         if (!array_key_exists($i, $groups)) {
           $groups[$i] = array();
           foreach ($rows_cols as $col) {
             $groups[$i][$col] = $agg_defaults;
             $agi = & $groups[$i][$col];
             $val = & $rows[$i][$col];
             if ($val !== null) {
               $agi[$count] = $this->math->add($agi[$count], 1);
               if ($val > $agi[$max]) {
                 $agi[$max] = $val;
               }
               if ($agi[$min] === null
                || $agi[$min]  >  $val) {
                 $agi[$min] = $val;
               }
             }
             $agi[$count_all] = $this->math->add($agi[$count_all], 1);
             $agi[$sum] = $this->math->add($agi[$sum], $val);
             $agi[$avg] = $agi[$sum];
           }
         }
       }
       unset($val, $agi);
     }
   }
 }

/**
 * Format the aggregate function to correspond with result rows
 *
 * @param  string  the function name which should be parsed in
 * @param  string  the name of column as the argument of function
 * @return string  the formatted result as string
 * @access private
 */
 function formatAggregateFunction($funcname, $column) {
   $result = sprintf('%s(%s)', $funcname, $column);
   return $result;
 }

/**
 * Grouping function to use it by select method for each items
 *
 * @param  array   result records as array
 * @param  string  the name of column which will be a group, or expression
 * @return array   array of result of all aggregate functions
 * @access private
 */
 function groupBy(&$rows, $groups){
   $result = array();
   if (!$this->hasError()) {
     $parsed = $this->parseGroupBy($groups);
     if (array_key_exists('fields', $parsed)
      && array_key_exists('is_expr', $parsed)) {
       $group_count = count($parsed['fields']);
       if (count($parsed['is_expr']) === $group_count) {
         if ($group_count === 1) {
           $fields = array_shift($parsed['fields']);
           $is_expr = array_shift($parsed['is_expr']);
           $result = $this->_groupBy($rows, $fields, $is_expr);
         } else {
           $result = $this->_multiGroupBy($rows, $parsed['fields'],
                                                 $parsed['is_expr']);
         }
       }
     }
   }
   return $result;
 }

/**
 * Grouping function to use it by select method for one item
 *
 * @param  array   result records as array
 * @param  string  the name of column which will be a group, or expression
 * @param  boolean whether the grouping value is the expression
 * @return array   array of result of all aggregate functions
 * @access private
 */
 function _groupBy(&$rows, $group_col, $is_expr = false){
   $result = array();
   if (!$this->hasError()) {
     $uniqs = array();
     $row_count = count($rows);
     if ($is_expr) {
       $group_expr = $this->validExpr($group_col);
       $first = true;
       for ($i = $row_count - 1; $i >= 0; --$i) {
         $val = null;
         if ($first) {
           $val = $this->safeExpr($rows[$i], $group_expr);
           if ($this->hasError()) {
             break;
           }
           $first = false;
         } else {
           $val = $this->expr($rows[$i], $group_expr);
         }
         $uniqs[ $this->hashValue($val) ][] = $i;
       }
     } else {
       $group_col = $this->replaceAliasName($group_col);
       for ($i = $row_count - 1; $i >= 0; --$i) {
         foreach ($rows[$i] as $key => $val) {
           if ($key === $group_col) {
             $uniqs[ $this->hashValue($val) ][] = $i;
           }
         }
         if (empty($uniqs)) {
           $this->pushError('Not exists the column(%s) on GROUP BY clause',
                            $group_col);
           $rows = array();
           break;
         }
       }
     }
     if (!$this->hasError()) {
       $result = $this->_groupByResolve($rows, $uniqs);
     }
   }
   return $result;
 }

/**
 * Grouping function to use by SELECT for two or more items
 *
 * @param  array   result records as array
 * @param  array   column names, or expression as array
 * @param  array   whether the grouping value is the expression
 * @return array   array of result of all aggregate functions
 * @access private
 */
 function _multiGroupBy(&$rows, $groups, $is_exprs){
   $result = array();
   if (!$this->hasError()) {
     if (count($groups) === count($is_exprs)) {
       $firsts = array();
       $exprs = array();
       foreach ($is_exprs as $i => $is_expr) {
         $firsts[$i] = true;
         if ($is_expr) {
           $exprs[$i] = $this->validExpr($groups[$i]);
         } else {
           $exprs[$i] = null;
         }
         if ($this->hasError()) {
           break;
         }
       }
       if ($this->hasError()) {
         $rows = array();
       } else {
         $uniqs = array();
         $row_count = count($rows);
         for ($i = $row_count - 1; $i >= 0; --$i) {
           $hash = array();
           foreach ($groups as $j => $group) {
             if ($is_exprs[$j]) {
               if ($firsts[$j]) {
                 $hash[] = $this->safeExpr($rows[$i], $exprs[$j]);
                 if ($this->hasError()) {
                   break;
                 }
                 $firsts[$j] = false;
               } else {
                 $hash[] = $this->expr($rows[$i], $exprs[$j]);
               }
             } else {
               $key = $this->findIdentifier($group, $rows[$i]);
               if (!array_key_exists($key, $rows[$i])) {
                 $this->pushError('No such column(%s)'
                               .  ' on GROUP BY clause', $group);
                 break;
               }
               $hash[] = $rows[$i][$key];
             }
           }
           if ($this->hasError()) {
             $rows = array();
             break;
           }
           $uniqs[ $this->hashValue($hash) ][] = $i;
         }
       }
     }
     if (!$this->hasError()) {
       $result = $this->_groupByResolve($rows, $uniqs);
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function _groupByResolve(&$rows, $uniqs){
   $result = null;
   if (!$this->hasError()) {
     $diffs  = array();
     $groups = array();
     foreach ($uniqs as $hashed => $array) {
       $diffs[ end($array) ] = 1;
       if (count($array) > 1) {
         array_unshift($groups, $array);
         unset($uniqs[$hashed]);
       }
     }
     if (!empty($uniqs) && empty($groups)) {
       foreach ($uniqs as $hashed => $array) {
         array_unshift($groups, $array);
         unset($uniqs[$hashed]);
       }
     }
     unset($uniqs);
     $groups_idx = $groups;
     $groups = $this->aggregateByGroups($rows, $groups_idx);
     $row_count = count($rows);
     for ($i = 0; $i < $row_count; $i++) {
       if (!array_key_exists($i, $diffs)) {
         unset($rows[$i]);
       }
     }
     $push = array();
     $groups_count = count($groups_idx);
     for ($i = 0; $i < $groups_count; $i++) {
       $pop = array_pop($groups_idx[$i]);
       if (array_key_exists($pop, $rows)) {
         $row = $rows[$pop];
         unset($rows[$pop]);
         $push[] = $row;
       }
     }
     while ($row = array_pop($push)) {
       array_unshift($rows, $row);
     }
     $rows = array_values($rows);
     $this->aggregateAssignByGroups($rows, $groups);
     $result = $groups;
   }
   return $result;
 }

/**
 * The sorting function to used by SELECT command
 *
 * @param  array   the result records
 * @param  mixed   the value of column name,
 *                   ascending order of object or descending order
 * @return void
 * @access private
 */
 function orderBy(&$rows, $orders){
   if (!$this->hasError() && $orders != null) {
     if (!is_array($orders) || !is_string(key($orders))) {
       $orders = $this->parseOrderByTokens($orders);
     }
     if (!empty($orders) && is_array($orders) && is_string(key($orders))
      && !empty($rows)   && is_array($rows)   && is_array(reset($rows))) {
       $params = array();
       $args   = array();
       $index  = 0;
       $reset  = reset($rows);
       $row_count = count($rows);
       foreach ($orders as $col => $sort) {
         $identifier = $this->findIdentifier($col, $reset);
         if (is_string($identifier) && $identifier != null
          && array_key_exists($identifier, $reset)) {
           $col = $identifier;
         }
         if (!is_string($col)) {
           $col = '';
         }
         if (!array_key_exists($col, $reset)) {
           $this->pushError('Not exists the column(%s)'
                          . ' on ORDER BY clause', $col);
           break;
         }
         for ($i = 0; $i < $row_count; $i++) {
           $params[$col][$i] = & $rows[$i][$col];
         }
         $args[$index++] = & $params[$col];
         $args[$index++] = $sort;
       }
       if (!$this->hasError()) {
         $args[$index] = & $rows;
         call_user_func_array('array_multisort', $args);
       }
       unset($args, $params);
     }
   }
 }

/**
 * Assign the LIMIT and OFFSET phrases for the SELECT command
 *
 * @param  array    the result records
 * @param  mixed    the argument given as LIMIT phrase of SELECT command
 * @return void
 * @access private
 */
 function assignLimitOffset(&$rows, $limit){
   $offset = 'offset';
   $length = 'length';
   if (!is_array($limit) || !array_key_exists($offset, $limit)) {
     $limit = $this->parseLimitTokens($limit);
   }
   if (array_key_exists($offset, $limit)
    && array_key_exists($length, $limit)) {
     if ($limit[$length] === 0) {
       $rows = array();
     } else if ($limit[$length] != null) {
       $rows = array_slice($rows, $limit[$offset], $limit[$length]);
     } else {
       $rows = array_slice($rows, $limit[$offset]);
     }
   } else {
     $this->pushError('illegal the value of arguments on LIMIT clause');
   }
 }

/**
 * @access private
 */
 function &_emitDisableFunctions(){
   return $this->disableFuncs;
 }

/**
 * Returns the list of to disable functions as array
 *
 * @param  void
 * @return array  the list of to disable functions
 * @access public
 */
 function getDisableFunctions(){
   $result = array();
   $this->checkDisableFunctions();
   $df = $this->_emitDisableFunctions();
   $result = array_keys((array)$df);
   return $result;
 }

/**
 * Sets the name of function to disable for using in expression
 * To reset the setting, it calls by an empty argument
 *
 * @param  mixed  name of function to disable as array, or string
 * @return number number of affected functions
 * @access public
 */
 function setDisableFunctions($func = array()){
   $result = 0;
   $df = & $this->_emitDisableFunctions();
   $argn = func_num_args();
   if ($argn === 0) {
     $result = count($df);
     $df = array();
   } else {
     if ($argn > 1) {
       $func = func_get_args();
       $func = $this->toOneArray($func);
     }
     if (!is_array($func)) {
       $func = (array) $this->splitEnableName($func);
     }
     if ($this->isAssoc($func)) {
       $func = array_keys($func);
     }
     foreach ((array)$func as $fn) {
       $fn = strtolower($fn);
       if ($fn != null && !array_key_exists($fn, $df)) {
         $df[$fn] = ++$result;
       }
     }
   }
   $this->checkDisableFunctions();
   unset($df);
   return $result;
 }

/**
 * Checks whether the function is disabled
 *
 * @param  string  the name of function to check
 * @return boolean whether the function is disabled
 * @access public
 */
 function isDisableFunction($func_name){
   $result = false;
   if (is_string($func_name)) {
     $df = $this->_emitDisableFunctions();
     if (!empty($df) && is_array($df)
      && (isset($df[$func_name]) || array_key_exists($func_name, $df))) {
       $result = true;
     }
     unset($df);
   }
   return $result;
 }

/**
 * Normalizes to the format to which the disabled functions are effective
 *
 * @param  void
 * @return void
 * @access private
 */
 function checkDisableFunctions(){
   $df = & $this->_emitDisableFunctions();
   if (!empty($df)) {
     if (!is_array($df)) {
       $df = (array) $this->splitEnableName($df);
     }
     if (!$this->isAssoc($df)) {
       $df = $this->flipArray($df);
     }
   }
   unset($df);
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Cache
 *
 * This class handles the query cache
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Cache extends Posql_Core {

/**
 * Gets table name which used for queries cache
 *
 * @param  void
 * @return string  the table name of query cache
 * @access public
 */
 function getQueryCacheTableName(){
   static $table_name = '_posql_query_cache';
   return $table_name;
 }

/**
 * Applies the query cache with specified statement
 *
 * @param  string  table name
 * @param  string  query
 * @return mixed   the result-rows from the cache table, or FALSE on failure
 * @access public
 */
 function applyQueryCache($table, $query){
   $result = array();
   if ($this->useQueryCache) {
     $is_manip = $this->isManip($query);
     if ($is_manip) {
       $this->useQueryCache = false;
       $result = $this->query($query);
       $this->useQueryCache = true;
     } else {
       $result = $this->loadQueryCache($table, $query, null, true);
       if (empty($result)) {
         $this->useQueryCache = false;
         $stmt = $this->query($query);
         $this->useQueryCache = true;
         $result = $this->toArrayResult($stmt);
         $stmt = null;
         unset($stmt);
         if (!$this->isFunctionInStatement($query)) {
           $count = $this->countQueryCache($table, $query, true);
           if (is_numeric($count) && $count >= $this->queryCacheMaxRows) {
             $caches = $this->loadQueryCache($table, $query, true);
             $this->orderBy($caches, 'time, rows');
             $i = 0;
             while ($count >= $this->queryCacheMaxRows) {
               if (!isset($caches[$i], $caches[$i]['query'])) {
                 $this->removeQueryCache($caches[$i]['query'], null, 1);
               } else {
                 $this->removeQueryCache(null, true, 1);
               }
               $i++;
               $count--;
             }
           }
           if ($count >= 1) {
             $this->removeQueryCache($query, null, 1);
           }
           $this->saveQueryCache($table, $query, $result);
         }
       }
     }
   }
   return $result;
 }

/**
 * Clears all query caches from database
 *
 * @param  void
 * @return number  number of affected rows
 * @access public
 */
 function clearQueryCache(){
   $result = $this->removeQueryCache(null, true, -1);
   return $result;
 }

/**
 * Validates the specified expression for the query cache
 *
 * @param  string   query
 * @param  string   table name
 * @param  boolean  whether or not the expression is simple comparing
 * @return string   valid expression
 * @access private
 */
 function _validQueryCacheExpr($query, $table, $compare_only = false){
   $result = '';
   if ($this->useQueryCache) {
     $query = $this->joinWords($this->splitSyntax($query));
     if ($compare_only) {
       $result = $this->formatQueryString('$%s===%a',
         'query', $query
       );
     } else {
       $result = $this->formatQueryString('$%s===%a&&$%s>%a',
         'query', $query,
         'time', $this->getLastMod($table)
       );
     }
   }
   return $result;
 }

/**
 * Removes the records for the query cache by specified expression
 *
 * @param  string  query
 * @param  string  expression
 * @param  number  deleted limit value
 * @return number  number of affected rows
 * @access public
 */
 function removeQueryCache($query, $expr = null, $limit = -1){
   $result = 0;
   if (!$this->hasError()) {
     $table = $this->getQueryCacheTableName();
     $tname = $this->encodeKey($table);
     if ($expr === null) {
       $expr = $this->_validQueryCacheExpr($query, null, true);
     }
     if ($this->lockExAll()) {
       if ($fp = $this->fopen('r')) {
         $this->fseekLine($fp, 2);
         if ($up = $this->fopen('r+')) {
           $this->fseekLine($up, 2);
           $delim = $this->DELIM_CACHE;
           $first = true;
           while (!feof($fp)) {
             $remove = false;
             $line = $this->fgets($fp);
             if (strpos($line, $delim)) {
               list($key, $buf) = explode($delim, $line);
               if ($key === $tname) {
                 $rec = $this->decode($buf);
                 if (is_array($rec)) {
                   if ($first) {
                     if ($expr === true || $this->safeExpr($rec, $expr)) {
                       $remove = true;
                     }
                     $first = false;
                   } else {
                     if ($expr === true || $this->expr($rec, $expr)) {
                       $remove = true;
                     }
                   }
                 }
               }
             }
             if ($remove) {
               $line = str_repeat(' ', strlen(trim($line))) . $this->NL;
               if ($this->fputs($up, $line)) {
                 $result++;
                 if ($limit >= 1 && $result >= $limit) {
                   break;
                 }
               }
             } else {
               $this->fgets($up);
             }
           }
           fclose($up);
         }
         fclose($fp);
       }
       $this->unlockAll();
     }
   }
   return $result;
 }

/**
 * Insert a new result-set for the query cache
 *
 * @param  string  table name
 * @param  string  query
 * @param  array   a new result-set
 * @return number  number of affected rows
 * @access public
 */
 function saveQueryCache($table, $query, $rows = array()){
   $result = 0;
   if ($this->useQueryCache) {
     if (!$this->hasError()) {
       $cache_table_name = $this->getQueryCacheTableName();
       $tname = $this->encodeKey($cache_table_name);
       if ($this->lockExAll()) {
         if ($fp = $this->fopen('a')) {
           $row = array(
             'time'  => time(),
             'query' => $this->joinWords($this->splitSyntax($query)),
             'rows'  => $this->encode($rows)
           );
           $puts = $tname . $this->DELIM_CACHE . $this->encode($row)
                                               . $this->NL;
           if ($this->fputs($fp, $puts)) {
             $result++;
           }
           fclose($fp);
           unset($row, $puts);
         }
         unset($rows);
         $this->unlockAll();
       }
     }
   }
   return $result;
 }

/**
 * Count the row numbers for the query cache tables
 *
 * @param  string   table name
 * @param  string   query
 * @return number   row count of specified expression
 * @access public
 */
 function countQueryCache($table, $query, $expr = null){
   $result = 0;
   if ($this->useQueryCache) {
     $cache_table_name = $this->getQueryCacheTableName();
     $tname = $this->encodeKey($cache_table_name);
     if (!$this->hasError()) {
       if ($expr === null) {
         $expr = $this->_validQueryCacheExpr($query, $table);
       }
       if ($this->lockShAll()) {
         if ($fp = $this->fopen('r')) {
           $this->fseekLine($fp, 3);
           $delim = $this->DELIM_CACHE;
           $first = true;
           while (!feof($fp)) {
             $line = $this->fgets($fp);
             if (strpos($line, $delim)) {
               list($key, $buf) = explode($delim, $line);
               if ($key === $tname) {
                 $rec = $this->decode($buf);
                 if (is_array($rec)) {
                   if ($first) {
                     if ($expr === true || $this->safeExpr($rec, $expr)) {
                       $result++;
                     }
                     if ($this->hasError()) {
                       $result = 0;
                       break;
                     }
                     $first = false;
                   } else {
                     if ($expr === true || $this->expr($rec, $expr)) {
                       $result++;
                     }
                   }
                 }
               }
             }
           }
           fclose($fp);
         }
         $this->unlockAll();
       }
     }
   }
   return $result;
 }

/**
 * Load the row from the query cache tables by the condition specification
 *
 * @param  string   table name
 * @param  string   query
 * @param  string   expression
 * @param  boolean  whether or not to get only rows
 * @return array    result-set which passed condition
 * @access public
 */
 function loadQueryCache($table, $query, $expr = null, $rows_only = false){
   $result = array();
   if ($this->useQueryCache) {
     $cache_table_name = $this->getQueryCacheTableName();
     $tname = $this->encodeKey($cache_table_name);
     if (!$this->hasError()) {
       if ($expr === null) {
         $expr = $this->_validQueryCacheExpr($query, $table);
       }
       if ($this->lockShAll()) {
         if ($fp = $this->fopen('r')) {
           $this->fseekLine($fp, 3);
           $delim = $this->DELIM_CACHE;
           $rows_key = 'rows';
           $first = true;
           while (!feof($fp)) {
             $line = $this->fgets($fp);
             if (strpos($line, $delim)) {
               list($key, $buf) = explode($delim, $line);
               if ($key === $tname) {
                 $rec = $this->decode($buf);
                 if (is_array($rec)) {
                   if ($first) {
                     if ($expr === true || $this->safeExpr($rec, $expr)) {
                       if (array_key_exists($rows_key, $rec)) {
                         if ($rows_only) {
                           $result = $this->decode($rec[$rows_key]);
                           break;
                         } else {
                           $rec[$rows_key] = strlen($rec[$rows_key]);
                           $result[] = $rec;
                         }
                       }
                     }
                     if ($this->hasError()) {
                       $result = array();
                       break;
                     }
                     $first = false;
                   } else {
                     if ($expr === true || $this->expr($rec, $expr)) {
                       if (array_key_exists($rows_key, $rec)) {
                         if ($rows_only) {
                           $result = $this->decode($rec[$rows_key]);
                           break;
                         } else {
                           $rec[$rows_key] = strlen($rec[$rows_key]);
                           $result[] = $rec;
                         }
                       }
                     }
                   }
                 }
               }
             }
           }
           fclose($fp);
         }
         $this->unlockAll();
       }
     }
   }
   return $result;
 }
}
//-----------------------------------------------------------------------------
/**
 * @name Posql_Scan
 *
 * The base class to scan the tables and optimize statement
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Scan extends Posql_Cache {

/**
 * Applies the optimized explan for table scanning
 *
 * @param  array   parsed select arguments
 * @return array   result-set
 * @access private
 */
 function applyOptimizeExplan($args){
   $result = array();
   if (!$this->hasError()) {
     if (!is_array($args)) {
       $this->pushError('Invalid arguments on SELECT(%s)', $args);
     } else {
       $defaults = $this->getSelectDefaultArguments();
       foreach ($defaults as $clause => $val) {
         if (!array_key_exists($clause, $args)) {
           $this->pushError('Invalid arguments on SELECT(%s)', $clause);
         }
       }
       if (!$this->hasError()) {
         $table_name = reset($args);
         $columns    = next($args);
         $expr       = next($args);
         $group      = next($args);
         $having     = next($args);
         $order      = next($args);
         $limit      = end($args);
         $this->_getMetaAll = true;
         $this->getMeta($table_name);
         $this->_getMetaAll = null;
         $posql_key = $this->getClassKey();
         if (is_array($this->meta)
          && array_key_exists($posql_key, $this->meta)) {
           unset($this->meta[$posql_key]);
         }
         if (!$this->hasError()) {
           if ($this->isSimpleLimitOnce($args)) {
             $result = $this->scanFullTablesOnce($table_name, $expr);
           } else if ($this->isSimplePrimaryKeySpecify($args)) {
             $result = $this->scanFullTablesOnce($table_name, $expr);
           } else {
             $result = $this->scanFullTables($table_name, $expr);
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Scan all tables (TABLE ACCESS FULL)
 *
 * @param  string  table name
 * @param  mixed   expression as WHERE clause
 * @return array   result-set
 * @access private
 */
 function scanFullTables($table_name, $expr = true){
   $result = array();
   if (is_bool($expr)) {
     $valid_expr = $expr;
   } else {
     $valid_expr = $this->validExpr($expr);
   }
   if (!$this->hasError()) {
     $table_key = $this->encodeKey($table_name);
     if ($fp = $this->fopen('r')) {
       $this->fseekLine($fp, 3);
       $delim = $this->DELIM_TABLE;
       $first = true;
       while (!feof($fp)) {
         $line = $this->fgets($fp);
         if (strpos($line, $delim)) {
           list($key, $buf) = explode($delim, $line);
           if ($key === $table_key) {
             $rec = $this->decode($buf);
             if (is_array($rec)) {
               if ($valid_expr === true) {
                 $result[] = $rec;
               } else {
                 if ($first) {
                   if ($this->safeExpr($rec, $valid_expr)) {
                     $result[] = $rec;
                   }
                   if ($this->hasError()) {
                     $result = array();
                     break;
                   }
                   $first = false;
                 } else {
                   if ($this->expr($rec, $valid_expr)) {
                     $result[] = $rec;
                   }
                 }
               }
             }
           }
         }
       }
       fclose($fp);
     }
   }
   return $result;
 }

/**
 * Scan all tables (TABLE ACCESS FULL) once
 *
 * @param  string  table name
 * @param  mixed   expression as WHERE clause
 * @return array   result-set
 * @access private
 */
 function scanFullTablesOnce($table_name, $expr = true){
   $result = array();
   if (is_bool($expr)) {
     $valid_expr = $expr;
   } else {
     $valid_expr = $this->validExpr($expr);
   }
   if (!$this->hasError()) {
     $table_key = $this->encodeKey($table_name);
     if ($fp = $this->fopen('r')) {
       $this->fseekLine($fp, 3);
       $delim = $this->DELIM_TABLE;
       $first = true;
       while (!feof($fp)) {
         $line = $this->fgets($fp);
         if (strpos($line, $delim)) {
           list($key, $buf) = explode($delim, $line);
           if ($key === $table_key) {
             $rec = $this->decode($buf);
             if (is_array($rec)) {
               if ($valid_expr === true) {
                 $result[] = $rec;
                 break;
               } else {
                 if ($first) {
                   if ($this->safeExpr($rec, $valid_expr)) {
                     $result[] = $rec;
                     break;
                   }
                   if ($this->hasError()) {
                     $result = array();
                     break;
                   }
                   $first = false;
                 } else {
                   if ($this->expr($rec, $valid_expr)) {
                     $result[] = $rec;
                     break;
                   }
                 }
               }
             }
           }
         }
       }
       fclose($fp);
     }
   }
   return $result;
 }
}
//----------------------------------------------------------------------------
/**
 * @name Posql
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
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 * @link      http://sourceforge.jp/projects/posql/
 * @link      http://sourceforge.net/projects/posql/
 * @license   Dual licensed under the MIT and GPL licenses
 * @copyright Copyright (c) 2010 polygon planet
 * @version   $Id: posql.php,v 2.18a 2011/12/12 01:26:52 polygon Exp $
 *---------------------------------------------------------------------------*/
class Posql extends Posql_Scan {

/**
 * constructor
 *
 * @param  string  filename as database
 * @param  string  table name
 * @param  array   regulated value of field as an array
 * @return void
 * @access public
 */
 function Posql($path = null, $table = null, $fields = array()){
   $this->init($path);
   if (func_num_args()) {
     $this->open($path, $table, $fields);
   }
 }

/**
 * Destructor for PHP Version 5+
 *
 * @access public
 */
 function __destruct(){
   $this->terminate();
 }

/**
 * Opens the database as a file
 * To switched for calling createTable() and createDatabase() by the arguments
 *
 * @param  string  file path of database
 * @param  string  table name
 * @param  array   regulated value of field as an array
 * @return boolean success or not
 * @access public
 */
 function open($path, $table = null, $fields = array()){
   $result = false;
   $this->setPath($path);
   if (!$this->canLockDatabase()) {
     $this->pushError('Cannot open and lock database(%s)', $path);
   } else {
     if (!@is_file($this->path)) {
       $result = $this->createDatabase($this->path);
     } else {
       $result = $this->isDatabase();
     }
     if ($result && $table && $fields) {
       $result = $this->createTable($table, $fields);
       if (!$result && !$this->hasError()) {
         $result = true;
       }
     }
   }
   if ($result) {
     $this->init();
   }
   return $result;
 }

/**
 * Creates database as one file
 *
 * @param  string  filename of database
 * @param  number  file permission (default = 0666)
 * @return boolean success or not
 * @access public
 */
 function createDatabase($path, $perms = 0666){
   $result = false;
   $warn_error = false;
   if ($this->_ifExistsClause !== 'if_not_exists') {
     $warn_error = true;
   }
   $this->setPath($path);
   clearstatcache();
   if (@is_file($this->path)) {
     if ($warn_error) {
       $this->pushError('Already exists the database(%s)', $this->getPath());
     }
   } else {
     $fp = $this->fopen('a');
     if (!$fp) {
       if (!$this->hasError() && $warn_error) {
         $this->pushError('Failed to create database(%s),'
                       .  ' confirm directory permission', $this->getPath());
       }
     } else {
       $meta = array(array());
       $head = implode($this->NL,
         array(
           $this->getHeader() . $this->LOCK_NONE . '@' . $this->toUniqId(0),
           '',
           $this->encode($meta),
           ''
         )
       );
       if ($this->fputs($fp, $head)) {
         $this->meta = $meta;
         $result = true;
       }
       fclose($fp);
       $mask = @umask(0);
       @chmod($this->path, $perms);
       @umask($mask);
     }
   }
   return $result;
 }

/**
 * Creates definition of table in a file
 *
 * @param  string  table name
 * @param  array   regulated value of field as an array
 * @param  string  optionally, specify the name of primary key
 * @return boolean success or not
 * @access public
 */
 function createTable($table, $fields = array(), $primary_key = null){
   $result = false;
   $this->_onCreate   = true;
   $this->_getMetaAll = true;
   $this->getMeta();
   if (!is_string($table)) {
     $this->pushError('Only string type is enabled as arguments(%s)', $table);
     $table = null;
   }
   $tname = $this->encodeKey($table);
   if (isset($this->meta[$tname])) {
     if (!empty($this->_useQuery)
      && $this->_ifExistsClause !== 'if_not_exists') {
       $this->pushError('Already exists the table(%s)', $table);
     }
   } else {
     if (!$this->isEnableName($table)) {
       $error_token = (string)$table;
       $this->pushError('Invalid table name(%s)', $error_token);
     } else if (!is_array($fields)
             || !$this->isAssoc($fields)) {
       $this->pushError('Only associatable array is enabled');
     } else {
       foreach (array_keys($fields) as $field) {
         if (!$this->isEnableName($field)) {
           $this->pushError('Invalid field name(%s)', $field);
           break;
         }
       }
       if (!$this->hasError()) {
         if (!is_string($primary_key)
          || !array_key_exists($primary_key, $fields)) {
           $primary_key = null;
         }
         $fields = $this->mergeDefaults($fields);
         $meta = $this->encode($fields);
         if (strlen($meta) > $this->MAX) {
           $this->pushError('Over the maximum length on fields');
         } else {
           $this->meta[$tname] = $fields;
           $posql_key = $this->getClassKey();
           if (empty($this->meta[$posql_key])) {
             $this->meta[$posql_key] = array();
           }
           $create_query = null;
           if (!empty($this->_useQuery) && !empty($this->lastQuery)
            && !empty($this->lastMethod) && $this->lastMethod === 'create') {
             $create_query = $this->lastQuery;
           }
           $this->meta[$posql_key][$tname] = array(
             'sql'     => $create_query,
             'primary' => $primary_key
           );
           $this->tableName = $tname;
           $meta = $this->encode($this->meta);
           if ($this->lockExAll()) {
             $lock = sprintf('%s:%d@%s#%010.0f@%010u;',
               $tname, 0, $this->toUniqId(0), 1, time());
             if ($this->appendFileLine($this->getPath(), $lock, 2)
              && $this->replaceFileLine($this->getPath(), $meta, 3)) {
               $result = true;
             }
             $this->unlockAll();
           }
         }
       }
     }
   }
   $posql_key = $this->getClassKey();
   if (isset($this->meta[$posql_key])) {
     unset($this->meta[$posql_key]);
   }
   $this->_onCreate   = null;
   $this->_getMetaAll = null;
   return $result;
 }

/**
 * Registers a "regular" User Defined Function(UDF) for use in SQL statements
 *
 * @param  string    the name of the function used in SQL statements
 * @param  callback  callback function to handle the defined SQL function
 * @return boolean   success or failure
 * @access public
 */
 function createFunction($funcname, $callback){
   $result = false;
   if ($funcname != null && is_string($funcname)
    && $callback != null && isset($this->UDF) && is_array($this->UDF)) {
     if (!is_callable($callback)) {
       $msg = (string)$callback;
       if (is_array($callback)) {
         if (isset($callback[0]) && is_object($callback[0])) {
           $callback[0] = get_class($callback[0]);
         }
         $msg = implode('::', $callback);
       }
       $this->pushError('Cannot call the function(%s)', $msg);
     } else {
       $this->UDF[$funcname] = $callback;
       $result = true;
     }
   } else {
     $funcname = (string)$funcname;
     $this->pushError('Failed to create function(%s)', $funcname);
   }
   return $result;
 }

/**
 * Drops the database as one file
 * When deleted it is not able to restore by physical deletion
 *
 * @param  string  filename as the database
 * @param  boolean whether do force drops or waits for locking
 * @return boolean success or not
 * @access public
 */
 function dropDatabase($path = null, $force = false){
   $result = false;
   if ($path) {
     $this->setPath($path);
   }
   if ($this->isDatabase()) {
     while (!$force && $this->isLockAll()) {
       $this->sleep();
     }
     $result = @unlink($this->path);
     if (!$result) {
       if ($this->_ifExistsClause !== 'if_exists') {
         $this->pushError('Cannot dropDatabase(%s)', $this->path);
       }
     } else {
       $this->autoVacuum = false;
     }
   }
   return $result;
 }

/**
 * Drops the table and all it records
 *
 * @param  string  table name
 * @return boolean success or not
 * @access public
 */
 function dropTable($table){
   $result = false;
   $warn_error = false;
   if (!empty($this->_useQuery)
    && !empty($this->lastMethod)
    && $this->lastMethod === 'drop'
    && $this->_ifExistsClause !== 'if_exists') {
     $warn_error = true;
   }
   $this->_getMetaAll = true;
   $this->getMeta();
   $tname = $this->encodeKey($table);
   if (empty($this->meta[$tname])) {
     if ($warn_error) {
       $this->pushError('Not exists the table(%s)', $table);
     }
     $result = false;
   } else {
     if ($this->lockExAll()) {
       if (($rp = $this->fopen('r'))
        && ($wp = $this->fopen('r+'))) {
         $head = $this->fgets($rp);
         $lock = $this->fgets($rp);
         $pos = ftell($rp);
         $re = sprintf('|%s:\d@\w{8}#\d+@\d+;|', preg_quote($tname));
         if (!preg_match($re, $lock, $match)) {
           if ($warn_error) {
             $this->pushError('Already dropped the table(%s)', $table);
           }
           fclose($rp);
           fclose($wp);
           $result = false;
         } else {
           $meta = $this->meta;
           unset($meta[$tname]);
           $posql_key = $this->getClassKey();
           if (isset($meta[$posql_key][$tname])) {
             unset($meta[$posql_key][$tname]);
           }
           fseek($wp, strlen($head) + strpos($lock, $match[0]));
           $this->fputs($wp, str_repeat(' ', strlen($match[0])));
           fseek($wp, $pos);
           $this->fputs($wp, $this->encode($meta));
           fseek($rp, ftell($wp));
           while (!feof($rp)) {
             $c = fgetc($rp);
             if ($c === $this->NL || $c === false) {
               break;
             } else {
               $this->fputs($wp, ' ');
             }
           }
           fseek($wp, ftell($rp));
           $tnamep = $tname . $this->DELIM_TABLE;
           $tlen = strlen($tnamep);
           while (!feof($rp)) {
             $line = $this->fgets($rp);
             $trim = trim($line);
             $sub = substr($trim, 0, $tlen);
             if ($sub === $tnamep) {
               $put = str_repeat(' ', strlen($trim)) . $this->NL;
               $this->fputs($wp, $put);
             } else {
               $this->fgets($wp);
             }
           }
           fclose($rp);
           fclose($wp);
           unset($this->meta[$tname]);
           if (isset($this->meta[$posql_key][$tname])) {
             unset($this->meta[$posql_key][$tname]);
           }
           $result = true;
         }
       }
       $this->vacuum();
       $this->unlockAll();
     }
   }
   $this->_getMetaAll = null;
   return $result;
 }

/**
 * @access public
 */
 function alterTable($table, $action){
   //TODO: Implements or emulate
   $this->pushError('Not implemented command (ALTER TABLE)');
 }

/**
 * Inserts the records to table of the database
 *
 * @param  string  table name
 * @param  array   add data as the array
 * @return number  number of affected rows
 * @access public
 */
 function insert($table, $rows = array()){
   $result = 0;
   if ($table != null && !empty($rows) && is_array($rows)) {
     if (!array_key_exists(0, $rows)) {
       $rows = array($rows);
     }
     $rows = array_map(array($this, 'mergeDefaults'), $rows);
     $this->_curMeta = $this->getMeta($table);
     $this->next = $this->getNextId($table);
     if (!$this->hasError()
      && $this->_curMeta !== false && $this->next !== false) {
       $tname = $this->tableName = $this->encodeKey($table);
       if (empty($this->meta[$tname])) {
         $this->pushError('Cannot insert to the table(%s)', $table);
       } else {
         if ($this->lockEx($table)) {
           if ($fp = $this->fopen('a')) {
             do {
               $repeat = false;
               $nextid = $this->getNextId($table);
               $new_rows = array_map(array(&$this, '_addMap'), $rows);
               if (1 !== $this->math->comp($this->next, $nextid)) {
                 // 1 ::= $this->next > $nextid
                 $repeat = true;
               }
               if ($this->hasError()) {
                 $repeat = false;
               }
             } while ($repeat);
             unset($rows);
             $this->_execExpr = false;
             if (!$this->hasError()) {
               if ($this->fputs($fp, implode('', $new_rows))) {
                 $result = count($new_rows);
               }
             }
             fclose($fp);
             unset($new_rows);
             if (!$this->hasError()) {
               $this->setNextId($table, $this->next);
             }
           }
           $this->unlock($table);
         }
       }
     }
   }
   return $result;
 }

/**
 * Updates the database to the given arguments condition and expression
 * At that time, "utime" will always be updated
 *
 * @param  string  table name
 * @param  array   update data as an array
 * @param  mixed   expression of PHP syntax, default = true (update all)
 * @return number  number of affected rows
 * @access public
 */
 function update($table, $row = array(), $expr = true){
   $result = 0;
   if (!is_array($row)) {
     $this->pushError('Invalid type of the records(%s). Must an array.', $row);
   }
   if (!$this->hasError()) {
     $this->getMeta($table);
     $tname = $this->tableName = $this->encodeKey($table);
     if (!is_bool($expr)) {
       $expr = $this->validExpr($expr);
     }
     if (empty($this->meta[$tname])) {
       $this->pushError('Not exists the table(%s)', $table);
     }
   }
   if (!$this->hasError()) {
     if ($this->lockExAll()) {
       if ($fp = $this->fopen('r')) {
         $this->fseekLine($fp, 2);
         if ($up = $this->fopen('r+')) {
           $this->fseekLine($up, 2);
           $delim = $this->DELIM_TABLE;
           $ups = array();
           $first = true;
           while (!feof($fp)) {
             $isup = false;
             $line = $this->fgets($fp);
             $trim = trim($line);
             if ($trim == null) {
               $this->fputs($up, $line);
               continue;
             } else if (strpos($line, $delim)) {
               list($tkey, $buf) = explode($delim, $line);
               if ($tkey === $tname) {
                 $rec = $this->decode($buf);
                 if (is_array($rec)) {
                   if ($first) {
                     if ($this->safeExpr($rec, $expr)) {
                       $isup = true;
                     }
                     if ($this->hasError()) {
                       break;
                     }
                     $first = false;
                   } else {
                     if ($this->expr($rec, $expr)) {
                       $isup = true;
                     }
                   }
                 }
               }
             }
             if ($isup) {
               $put = str_repeat(' ', strlen($trim)) . $this->NL;
               if ($this->fputs($up, $put)) {
                 $result++;
               }
               $ups[] = $rec;
             } else {
               $this->fgets($up);
             }
           }
           if (!empty($ups) && empty($this->_inDelete)) {
             $this->_curMeta = $row;
             $this->tableName = $tname;
             $maps = array_map(array(&$this, '_upMap'), $ups);
             $this->_execExpr = false;
             if ($this->hasError()) {
               $maps = array_map(array(&$this, '_upRestoreMap'), $ups);
               $result = 0;
             }
             $this->fputs($up, implode('', $maps));
           }
           unset($maps, $ups);
           fclose($up);
           if ($result) {
             $this->setLastMod($table);
           }
         }
         fclose($fp);
       }
       $this->unlockAll();
     }
   }
   return $result;
 }

/**
 * Deletes the row of the database in the condition specification
 *
 * @param  string  table name
 * @param  mixed   expression of WHERE clause, default = false (not delete)
 * @return number  number of affected rows
 * @access public
 */
 function delete($table, $expr = false){
   $this->_inDelete = true;
   $result = $this->update($table, array(), $expr);
   $this->_inDelete = null;
   return $result;
 }

/**
 * Replace the records to table of the database
 * if conflicts to the primary key.
 *
 * This method supports "REPLACE INTO ..." syntax.
 *
 * @param  string  table name
 * @param  array   a replacement associative array
 * @return number  number of affected rows
 * @access public
 */
 function replace($table, $row = array()){
   $result = 0;
   if ($table != null && is_array($row)) {
     if (array_key_exists(0, $row)) {
       $this->pushError('Array of one dimension must be given REPLACE.');
     }
     $expr = null;
     $meta = null;
     $insert_only = false;
     if (!$this->hasError()) {
       $meta = $this->getMeta($table);
       if (!isset($this->_primaryKey) || $this->_primaryKey == null) {
         $insert_only = true;
       } else {
         if (!array_key_exists($this->_primaryKey, $row)) {
           $insert_only = true;
         } else {
           $expr = sprintf('%s = %s',
             $this->_primaryKey,
             $this->quote($row[$this->_primaryKey])
           );
           $row_exists = $this->count($table, $expr);
           if (!$row_exists) {
             $insert_only = true;
           }
         }
       }
     }
     if (!$this->hasError()) {
       if ($insert_only) {
         $result = $this->insert($table, $row);
       } else {
         if ($expr == null || $meta == null
          || !is_array($meta) || !isset($this->_primaryKey)
          || !array_key_exists($this->_primaryKey, $row)) {
           $this->pushError('Failed to REPLACE INTO the table.');
         } else {
           $row = $row + $meta;
           if ($this->_execExpr) {
             foreach ($row as $key => $val) {
               if (!$this->isValidExpr($val)) {
                 $val = $this->quote($val);
                 $row[$key] = $this->validExpr($val);
               }
             }
           }
           if (array_key_exists('rowid', $row)) {
             $row['rowid'] = $row[$this->_primaryKey];
           } else {
             $this->pushError('Invalid the records, expect (rowid).');
           }
           if (!$this->hasError()) {
             $result = $this->update($table, $row, $expr) * 2;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Select the row from the database in the condition specification
 *
 * @param  string   table name
 * @param  string   the list of columns (e.g. "foo,bar")
 * @param  mixed    expression as WHERE clause, default=true (select all)
 * @param  mixed    value to decide the group
 * @param  mixed    expression as HAVING clause, default=true (select all)
 * @param  mixed    value to decide the order
 * @param  mixed    value to decide limit value
 * @return Posql_Statement  Posql_Statement object which has the result-set
 * @access public
 */
 function select($table, $columns = '*', $expr = true,
                         $group = null,  $having = true,
                         $order = null,  $limit  = null){
   $result = array();
   if (is_array($table) && func_num_args() === 1) {
     $args = $table;
   } else {
     $args = func_get_args();
   }
   $args = $this->parseSelectArguments($args, true);
   if (!empty($args) && is_array($args)) {
     $table   = reset($args);
     $tbl_key = key($args);
     $columns = next($args);
     $expr    = next($args);
     $group   = next($args);
     $having  = next($args);
     $order   = next($args);
     $limit   = end($args);
   }
   $this->applyAliasNames($table, $columns);
   if ($this->isSimpleCount($args)) {
     $count = $this->count($table, $expr);
     $this->assignColsBySimpleCount($result, $count, $columns);
   } else {
     //Fix Bug: Cannot get the result on SELECT statement.
     //         When getMeta() not called.
     // Thanks 5974
     $this->getMeta();
     $table = $this->replaceTableAlias($table);
     if (isset($tbl_key)) {
       $args[$tbl_key] = $table;
     }
     $tname = $this->tableName = $this->encodeKey($table);
     /*
     if (!is_bool($expr)) {
       $expr = $this->validExpr($expr);
     }
     */
     if (empty($this->meta[$tname])) {
       $this->pushError('Not exists the table(%s)', $table);
     }
     if (!$this->hasError()) {
       if ($this->lockSh($table)) {
         $result = $this->applyOptimizeExplan($args);
         $this->unlock($table);
         if (!$this->hasError() && !empty($result)) {
           if ($group != null) {
             $aggregates = $this->groupBy($result, $group);
           } else {
             $aggregates = null;
           }
           if ($having !== true) {
             $aggregates = $this->having($result, $having,
                                         $group, $aggregates);
           }
           if ($columns == null) {
             $result = array();
           } else {
             $this->assignCols($result, $columns,
                               $aggregates, $group, $order);
           }
           if (count($result) > 1 && $limit != null) {
             $this->assignLimitOffset($result, $limit);
           }
         }
       }
     }
   }
   if ($this->hasError()) {
     $result = array();
   }
   if (is_array($result) && empty($this->_fromStatement)) {
     $result = new Posql_Statement($this, $result);
   }
   return $result;
 }

/**
 * Selects each rows by using JOIN from the database
 *  in the condition specification
 *
 * @param  mixed    tables (e.g. "table1 JOIN table2")
 * @param  string   for choice columns
 * @param  mixed    expression as WHERE clause, default = true (select all)
 * @param  mixed    value to decide the group
 * @param  mixed    expression as HAVING clause, default = true (select all)
 * @param  string   value to decide the order
 * @param  mixed    value to decide limit value
 * @return Posql_Statement   Posql_Statement object which has the result-set
 * @access public
 */
 function multiSelect($tables,
                      $columns = '*', $expr = true,
                      $group = null,  $having = true,
                      $order = null,  $limit  = null){
   $result = array();
   if (is_array($tables) && func_num_args() === 1) {
     $args = $tables;
   } else {
     $args = func_get_args();
   }
   $args = $this->parseSelectArguments($args, true);
   if (!empty($args) && is_array($args)) {
     $tables  = reset($args);
     $columns = next($args);
     $expr    = next($args);
     $group   = next($args);
     $having  = next($args);
     $order   = next($args);
     $limit   = end($args);
   }
   $this->applyAliasNames($tables, $columns);
   if ($this->isSimpleCount($args, true)) {
     $count = $this->multiCount($tables, $expr);
     $this->assignColsBySimpleCount($result, $count, $columns);
   } else {
     $this->getMeta();
     if (!$this->hasError()) {
       if (!is_array($tables)) {
         $tables = $this->parseJoinTables($tables);
       }
       $tnames = array();
       $tables = array_values($tables);
       foreach ($tables as $i => $table) {
         $oname = $this->getValue($table, 'table_name');
         $tname = $this->encodeKey($oname);
         if ($i === 0) {
           $this->tableName = $tname;
         }
         $tnames[$tname] = 1;
       }
       if (!is_bool($expr)) {
         $this->_onMultiSelect = true;
         $expr = $this->validExpr($expr);
         $this->_onMultiSelect = null;
       }
       if (empty($this->meta[$this->tableName])) {
         $this->pushError('Not exists the table(%s)', $this->tableName);
       }
       if (!$this->hasError()) {
         if ($this->lockShAll()) {
           if ($fp = $this->fopen('r')) {
             $this->fseekLine($fp, 3);
             $delim = $this->DELIM_TABLE;
             while (!feof($fp)) {
               $line = $this->fgets($fp);
               if (strpos($line, $delim)) {
                 list($key, $buf) = explode($delim, $line);
                 if (isset($tnames[$key])) {
                   $rec = $this->decode($buf);
                   if (is_array($rec)) {
                     $result[$key][] = $rec;
                   }
                 }
               }
             }
             fclose($fp);
           }
           $this->unlockAll();
           if (!$this->hasError() && !empty($result)) {
             $this->joinTables($result, $tables, $expr);
             if ($group != null) {
               $aggregates = $this->groupBy($result, $group);
             } else {
               $aggregates = null;
             }
             if ($having !== true) {
               $this->_onMultiSelect = true;
               $aggregates = $this->having($result, $having,
                                           $group, $aggregates);
               $this->_onMultiSelect = null;
             }
             if ($columns === '*') {
               $this->removeCorrelations($result);
             } else {
               if ($columns == null) {
                 $result = array();
               } else {
                 $this->_onMultiSelect = true;
                 $this->assignCols($result, $columns,
                                   $aggregates, $group, $order);
                 $this->_onMultiSelect = null;
               }
             }
             if (count($result) > 1 && $limit != null) {
               $this->assignLimitOffset($result, $limit);
             }
           }
         }
       }
     }
   }
   if ($this->hasError()) {
     $result = array();
   }
   if (is_array($result) && empty($this->_fromStatement)) {
     $result = new Posql_Statement($this, $result);
   }
   return $result;
 }

/**
 * Execute simple sub-select query for FROM clause
 *
 * @see select
 *
 * @param  array    result set which was executed by sub-query
 * @param  string   the list of columns (e.g. "foo,bar")
 * @param  mixed    expression as WHERE clause, default=true (select all)
 * @param  mixed    value to decide the group
 * @param  mixed    expression as HAVING clause, default=true (select all)
 * @param  mixed    value to decide the order
 * @param  mixed    value to decide limit value
 * @return Posql_Statement   Posql_Statement object which has the result-set
 * @access public
 */
 function subSelect($rows,  $columns = '*', $expr = true,
                            $group = null,  $having = true,
                            $order = null,  $limit  = null){
   $result = array();
   if (is_array($rows) && func_num_args() === 1) {
     $args = $rows;
   } else {
     $args = func_get_args();
   }
   $args = $this->parseSelectArguments($args, true);
   if (!empty($args) && is_array($args)) {
     $rows    = reset($args);
     $columns = next($args);
     $expr    = next($args);
     $group   = next($args);
     $having  = next($args);
     $order   = next($args);
     $limit   = end($args);
   }
   if (!$this->hasError()) {
     if (!is_bool($expr)) {
       $expr = $this->validExpr($expr);
     }
     if (!$this->hasError()) {
       if (is_array($rows) && !empty($rows)) {
         $result = array();
         $first = true;
         $row_count = count($rows);
         while (--$row_count >= 0) {
           $row = array_shift($rows);
           if (!is_array($row)) {
             $this->pushError('Invalid result-set using subquery');
             break;
           }
           if ($first) {
             if ($this->safeExpr($row, $expr)) {
               $result[] = $row;
             }
             if ($this->hasError()) {
               break;
             }
             $first = false;
           } else {
             if ($this->expr($row, $expr)) {
               $result[] = $row;
             }
           }
         }
         if ($this->hasError()) {
           $result = array();
         } else {
           if ($group != null) {
             $aggregates = $this->groupBy($result, $group);
           } else {
             $aggregates = null;
           }
           if ($having !== true) {
             $aggregates = $this->having($result, $having,
                                         $group, $aggregates);
           }
           if ($columns == null) {
             $result = array();
           } else {
             $this->assignCols($result, $columns,
                               $aggregates, $group, $order);
           }
           if (count($result) > 1 && $limit != null) {
             $this->assignLimitOffset($result, $limit);
           }
         }
       }
     }
   }
   unset($rows);
   if ($this->hasError()) {
     $result = array();
   }
   if (is_array($result) && empty($this->_fromStatement)) {
     $result = new Posql_Statement($this, $result);
   }
   $this->_subSelectMeta = array();
   $this->_fromSubSelect = null;
   return $result;
 }

/**
 * Execute sub-select query which has multiple rows for FROM clause
 *
 * @see multiSelect, subSelect
 *
 * @param  array    result set which was executed by sub-query
 * @param  string   for choice columns
 * @param  mixed    expression as WHERE clause, default = true (select all)
 * @param  mixed    value to decide the group
 * @param  mixed    expression as HAVING clause, default = true (select all)
 * @param  string   value to decide the order
 * @param  mixed    value to decide limit value
 * @return Posql_Statement   Posql_Statement object which has the result-set
 * @access public
 */
 function multiSubSelect($tables,
                         $columns = '*', $expr = true,
                         $group = null,  $having = true,
                         $order = null,  $limit  = null){
   $result = array();
   if (is_array($tables) && func_num_args() === 1) {
     $args = $tables;
   } else {
     $args = func_get_args();
   }
   $args = $this->parseSelectArguments($args, true);
   if (!empty($args) && is_array($args)) {
     $tables  = reset($args);
     $columns = next($args);
     $expr    = next($args);
     $group   = next($args);
     $having  = next($args);
     $order   = next($args);
     $limit   = end($args);
   }
   if (!is_array($tables)) {
     $this->pushError('Invalid result-set using subquery');
   }
   if (!$this->hasError()) {
     $tnames = array();
     foreach (array_keys($tables) as $i => $identifier) {
       $tname = $this->encodeKey($identifier);
       if ($i === 0) {
         $this->tableName = $tname;
       }
       $tnames[$tname] = 1;
     }
     $this->applyAliasNames($this->_subSelectJoinInfo, $columns);
     if (!is_bool($expr)) {
       $this->_onMultiSelect = true;
       $expr = $this->validExpr($expr);
       $this->_onMultiSelect = null;
     }
     if (!$this->hasError()) {
       if (!empty($tables)) {
         $result = array();
         if ($this->lockShAll()) {
           $i = 0;
           $delim = $this->DELIM_TABLE;
           $table_count = count($tables);
           while (--$table_count >= 0) {
             reset($tables);
             $key = $this->encodeKey(key($tables));
             $rows = array_shift($tables);
             if (is_string($rows) && $rows === '<=>') {
               if ($fp = $this->fopen('r')) {
                 $this->fseekLine($fp, 3);
                 while (!feof($fp)) {
                   $line = $this->fgets($fp);
                   if (strpos($line, $delim)) {
                     list($key, $buf) = explode($delim, $line);
                     if (isset($tnames[$key])) {
                       $row = $this->decode($buf);
                       if (is_array($row)) {
                         $result[$key][] = $row;
                       }
                     }
                   }
                 }
                 fclose($fp);
               }
             } else if (is_array($rows)) {
               $row_count = count($rows);
               while (--$row_count >= 0) {
                 $row = array_shift($rows);
                 if (!is_array($row)) {
                   $this->pushError('Invalid result-set using subquery');
                   break 2;
                 }
                 $result[$key][] = $row;
               }
             }
             if ($this->hasError()) {
               break;
             }
             $i++;
           }
           $this->unlockAll();
         }
         unset($rows);
         if ($this->hasError()) {
           $result = array();
         } else {
           $this->joinTables($result, $this->_subSelectJoinInfo, $expr);
           if ($group != null) {
             $aggregates = $this->groupBy($result, $group);
           } else {
             $aggregates = null;
           }
           if ($having !== true) {
             $this->_onMultiSelect = true;
             $aggregates = $this->having($result, $having,
                                         $group, $aggregates);
             $this->_onMultiSelect = null;
           }
           if ($columns === '*') {
             $this->removeCorrelations($result);
           } else {
             if ($columns == null) {
               $result = array();
             } else {
               $this->_onMultiSelect = true;
               $this->assignCols($result, $columns,
                                 $aggregates, $group, $order);
               $this->_onMultiSelect = null;
             }
             if (count($result) > 1 && $limit != null) {
               $this->assignLimitOffset($result, $limit);
             }
           }
         }
       }
     }
   }
   if ($this->hasError()) {
     $result = array();
   }
   if (is_array($result) && empty($this->_fromStatement)) {
     $result = new Posql_Statement($this, $result);
   }
   $this->_subSelectMeta = array();
   $this->_fromSubSelect = null;
   $this->_subSelectJoinUniqueNames = array();
   $this->_subSelectJoinInfo = array();
   return $result;
 }

/**
 * A simple SELECT statement
 * There is no FROM clause with a table name in there
 *
 * @param  string   the list of select statement as expression
 * @param  mixed    the WHERE clause as the expression
 * @param  mixed    value to decide the group
 * @param  mixed    expression as HAVING clause
 * @param  mixed    value to decide the order
 * @param  mixed    value to decide limit value
 * @return Posql_Statement   Posql_Statement object which has the result-set
 * @access public
 */
 function selectDual($select = null, $expr  = true,
                     $group  = null, $having = true,
                     $order  = null, $limit = null){
   $result = array();
   $this->_isDualSelect = true;
   if ($select !== null) {
     $result = array(array());
     $this->assignDualCols($result, $select);
   }
   $this->_isDualSelect = null;
   if ($this->hasError()) {
     $result = array();
   }
   if (is_array($result) && empty($this->_fromStatement)) {
     $result = new Posql_Statement($this, $result);
   }
   return $result;
 }

/**
 * Count all of the records, it returns the number of the counted rows
 *
 * @param  string  table name
 * @param  mixed   expression as WHERE clause, default = true (count all)
 * @return number  number of the counted rows
 * @access public
 */
 function count($table, $expr = true){
   $result = 0;
   $table = $this->replaceTableAlias($table);
   $tname = $this->tableName = $this->encodeKey($table);
   if ($table != null) {
     $this->getMeta($table);
     if (!is_bool($expr)) {
       $expr = $this->validExpr($expr);
     }
     if (!$this->hasError()) {
       if ($this->lockSh($table)) {
         if ($fp = $this->fopen('r')) {
           $this->fseekLine($fp, 3);
           if ($expr === true) {
             $top = $tname . $this->DELIM_TABLE;
             while (!feof($fp)) {
               $line = $this->fgets($fp, true);
               if ($line != null && strpos($line, $top) === 0) {
                 ++$result;
               }
             }
           } else {
             $delim = $this->DELIM_TABLE;
             $first = true;
             while (!feof($fp)) {
               $line = $this->fgets($fp);
               if (strpos($line, $delim)) {
                 list($key, $buf) = explode($delim, $line);
                 if ($key === $tname) {
                   $rec = $this->decode($buf);
                   if (is_array($rec)) {
                     if ($first) {
                       if ($this->safeExpr($rec, $expr)) {
                         ++$result;
                       }
                       if ($this->hasError()) {
                         $result = 0;
                         break;
                       }
                       $first = false;
                     } else {
                       if ($this->expr($rec, $expr)) {
                         ++$result;
                       }
                     }
                   }
                 }
               }
             }
           }
           fclose($fp);
         }
         $this->unlock($table);
       }
     }
   }
   return $result;
 }

/**
 * Count all of the records from multi-tables,
 *  it returns the number of the counted rows
 *
 * @param  mixed   table names
 * @param  mixed   expression as WHERE clause, default = true (count all)
 * @return number  number of the counted rows
 * @access public
 */
 function multiCount($tables, $expr = true){
   $result = 0;
   $this->getMeta();
   if (!$this->hasError() && $tables != null) {
     if (!is_array($tables)) {
       $tables = $this->parseJoinTables($tables);
     }
     $tnames = array();
     $tables = array_values($tables);
     foreach ($tables as $i => $table) {
       $oname = $this->getValue($table, 'table_name');
       $tname = $this->encodeKey($oname);
       if ($i === 0) {
         $this->tableName = $tname;
         $tablename = $oname;
       }
       $tnames[$tname] = 1;
     }
     if (!is_bool($expr)) {
       $this->_onMultiSelect = true;
       $expr = $this->validExpr($expr);
       $this->_onMultiSelect = null;
     }
     if (empty($this->meta[$this->tableName])) {
       $this->pushError('Not exists the table(%s)', $this->tableName);
     }
     if (!$this->hasError()) {
       if ($this->lockShAll()) {
         $rows = array();
         if ($fp = $this->fopen('r')) {
           $this->fseekLine($fp, 3);
           $delim = $this->DELIM_TABLE;
           $first = true;
           while (!feof($fp)) {
             $line = $this->fgets($fp);
             if (strpos($line, $delim)) {
               list($key, $buf) = explode($delim, $line);
               if (isset($tnames[$key])) {
                 $rec = $this->decode($buf);
                 if (is_array($rec)) {
                   if ($first) {
                     if ($this->safeExpr($rec, $expr)) {
                       $rows[$key][] = $rec;
                     }
                     if ($this->hasError()) {
                       $result = 0;
                       break;
                     }
                     $first = false;
                   } else {
                     if ($this->expr($rec, $expr)) {
                       $rows[$key][] = $rec;
                     }
                   }
                 }
               }
             }
           }
           fclose($fp);
         }
         $this->unlockAll();
         if ($this->hasError() || empty($rows)) {
           $result = 0;
         } else {
           $this->joinTables($rows, $tables, $expr);
           if (!$this->hasError()) {
             $result = count($rows);
           }
         }
         unset($rows);
       }
     }
   }
   return $result;
 }

/**
 * Optimizes the database
 * Extra spaces and null string(\0) in file are removed
 *
 * @param  void
 * @return boolean success or failure
 * @access public
 */
 function vacuum(){
   $result = false;
   if ($this->lockExAll()) {
     if ($rp = $this->fopen('r')) {
       $this->fseekLine($rp, 1);
       if ($wp = $this->fopen('r+')) {
         $this->fseekLine($wp, 1);
         $pos = ftell($rp);
         $line = $this->fgets($rp);
         $move = true;
         if (trim($line) == null) {
           $move = false;
           if ($this->_inTransaction) {
             $move = true;
           }
         }
         fseek($rp, $pos);
         if (!empty($this->meta)
          && $this->meta === array(array())) {
           $result = true;
         } else {
           if ($move) {
             fseek($rp, -1, SEEK_CUR);
             fseek($wp, -1, SEEK_CUR);
           }
           $nl  = $this->base16Encode($this->NL, '\x');
           $re  = sprintf('<[%s]{2,}>', $nl);
           $rm  = array(' ', "\t", "\x00");
           $buf = null;
           while (!feof($rp)) {
             $buf = fread($rp, 0x2000);
             $buf = str_replace($rm, '', $buf);
             $buf = preg_replace($re, $this->NL, $buf);
             $this->fputs($wp, $buf);
           }
           if (isset($buf) && substr($buf, -1) !== $this->NL) {
             $this->fputs($wp, $this->NL);
           }
           ftruncate($wp, ftell($wp));
           $result = true;
         }
         fclose($wp);
       }
       fclose($rp);
     }
     $this->unlockAll();
   }
   //debug($result,'color=navy:***vacuum()ed***;');
   return $result;
 }

/**
 * Get the table definition and information
 *
 * Note:
 *   This function is experimental SQL command.
 *   Note that there is a possibility
 *    that the specification changes in the future.
 *
 * @param  string  table name, (or null = all tables)
 * @return array   the information as array
 * @access public
 */
 function describe($table = null){
   $result = array();
   if ($table == null) {
     $result = $this->getTableInfo();
   } else if (!is_string($table)) {
     $this->pushError('Only string can be given as argument');
   } else {
     $meta = $this->getMeta();
     if (!$this->hasError() && is_array($meta)) {
       $meta = array_filter($meta);
       if (!empty($meta) && is_array($meta)) {
         $database = $this->getDatabaseName();
         $table_key = $this->encodeKey($table);
         if (strtolower($table) === 'database'
          || strcasecmp($table, $database) === 0) {
           $result = $this->getTableInfo();
         } else {
           if (array_key_exists($table_key, $meta)) {
             $meta = $meta[$table_key];
             $defs = $this->getCreateDefinitionMeta($table);
             $create_sql  = $this->getValue($defs, 'sql');
             $primary_key = $this->getValue($defs, 'primary');
             $definition  = array();
             if ($create_sql != null) {
               $definition = $this->parseCreateTableQuerySimple($create_sql);
             }
             if (!$this->hasError() && is_array($definition)) {
               $result = array();
               foreach ($meta as $name => $default) {
                 $type  = '';
                 $key   = '';
                 $extra = '';
                 if (array_key_exists($name, $definition)
                  && array_key_exists('type', $definition[$name])) {
                   $type = $definition[$name]['type'];
                 } else {
                   $type = $this->defaultDataType;
                 }
                 if ($name === 'rowid') {
                   $key   = 'primary';
                   $extra = 'auto_increment';
                 } else if ($name === $primary_key) {
                   $extra = $this->getExtraAliasMessage();
                 }
                 $result[] = array(
                   'name'    => $name,
                   'type'    => $type,
                   'key'     => $key,
                   'default' => $default,
                   'extra'   => $extra
                 );
               }
             }
           }
         }
       }
     }
   }
   if (is_array($result) && empty($this->_fromStatement)) {
     $result = new Posql_Statement($this, $result);
   }
   return $result;
 }

/**
 * Executes the SQL query which is data manipulation
 *
 * @see    query
 *
 * @param  string  the manipulative SQL query
 * @return number  the number of affected rows
 * @access public
 */
 function exec($query){
   $result = 0;
   if ($this->isManip($query)) {
     if ($this->isMultiQuery($query)) {
       $result = $this->multiQuery($query);
     } else {
       $parsed = $this->parseQueryImplements($query);
       if (is_array($parsed)) {
         reset($parsed);
         $type = key($parsed);
         if (!empty($this->lastMethod)) {
           $type = $this->lastMethod;
         }
         $result = $this->buildQuery($parsed, $type);
       }
     }
     $result = (int)$result;
   }
   return $result;
 }

/**
 * Executes the SQL query
 *
 * Uses PHP's expression on "PHP" Engine
 *  e.g. "SELECT strlen('foo')"
 *
 * Uses SQL standard expression on "SQL" Engine
 *  e.g. "SELECT LENGTH('foo')"
 *
 * @see    setEngine, getEngine
 *
 * @param  string           SQL query
 * @return Posql_Statement  results of the query
 * @access public
 */
 function query($query){
   $result = false;
   if ($this->isMultiQuery($query)) {
     $result = $this->multiQuery($query);
   } else {
     $parsed = $this->parseQueryImplements($query);
     if (is_array($parsed)) {
       reset($parsed);
       $type = key($parsed);
       if (!empty($this->lastMethod)) {
         $type = $this->lastMethod;
       }
       if ($this->useQueryCache) {
         $table = $this->getTableNameFromTokens($parsed, $type);
         $result = $this->applyQueryCache($table, $query);
       } else {
         $result = $this->buildQuery($parsed, $type);
       }
       if (is_array($result) && empty($this->_fromStatement)) {
         $result = new Posql_Statement($this, $result, $query);
       }
     }
   }
   return $result;
 }

/**
 * Executes the SQL query based on format of sprintf()
 * The format was enhanced somewhat like below.
 * ------------------------------------------------------------------
 * 1. An optional precision specifier that acts as a cutoff point,
 *    setting a maximum character limit to the string
 *
 * 2. A type specifier that says
 *    what type the argument data should be treated as
 * ---------------
 * Extended types:
 * ---------------
 *  a - Distinguished by native PHP type automatically.
 *      It will process as the suitable type of the argument.
 *  B - the argument is handled as binary string.
 *      It will convert as base64, and the data will be put on SQL
 *      so that the decipherment is done when the query is executed.
 *  n - the argument is treated as number(int or float).
 *      It is only cast to the type of number.
 *  s - it is the same as the type of sprintf()
 *      when the quotation mark is included in the string,
 *      it will escaped for SQL injection.
 *  q - the argument is treated as and presented as a string
 *      that enclosed by the single quotes('').
 * ------------------------------------------------------------------
 * @example
 * <code>
 * $posql = new Posql;
 * $sql = "SELECT * FROM %s WHERE strlen(%s) == %5n OR %s = %q";
 * $ret = $posql->queryf($sql, "foo", "bar", 123456789, "baz", "value");
 * // to  "SELECT * FROM foo WHERE strlen(bar) == 12345 OR baz = 'value'"
 * </code>
 *
 * @example
 * <code>
 * $posql = new Posql;
 * $sql = "INSERT INTO foo (cols1, cols2) VALUES (%10n, %.10q)";
 * $ret = $posql->queryf($sql, "123456789123456789", str_repeat("abc", 100));
 * // to  "INSERT INTO foo (cols1, cols2) VALUES ('1234567891', 'abcabca...')"
 * </code>
 *
 * @example
 * <code>
 * // example: handle binary data
 * $posql = new Posql;
 * $path = "http://php.net/images/php.gif";
 * $type = "image/gif";
 * $name = "hoge.gif";
 * $sql = "INSERT INTO image (name, type, data) VALUES(%q, %q, %B)";
 * $ret = $posql->queryf($sql, $name, $type, file_get_contents($path));
 * // it will be like convert below
 * // "INSERT ... VALUES('hoge.gif', 'image/gif', base64_decode('R0lGO...'))"
 * </code>
 *
 * @example
 * <code>
 * $posql = new Posql;
 * $sql = "INSERT INTO foo (cols2) VALUES (%q)";
 * $ret = $posql->queryf($sql, "I'm feeling happy!");
 * // to  "INSERT INTO foo (cols2) VALUES ('I\'m feeling happy!')"
 * </code>
 *
 * @example
 * <code>
 * // using %a (auto) type
 * $posql = new Posql;
 * $sql = "SELECT %a, %a, %a %% 2, '%a' LIKE %a ESCAPE %a";
 * $ret = $posql->queryf($sql, null, true, -1100.19, "hoge", "%o%", "|");
 * // to "SELECT NULL, TRUE, -1100.19 % 2, 'hoge' LIKE '%o%' ESCAPE '|'"
 * </code>
 *
 * @param  string       the SQL query as formatted as sprintf()
 * @param  mixed  (...) zero or more parameters to be passed to the function
 * @return Posql_Statement        results of the query
 * @access public
 */
 function queryf($query){
   $result = false;
   $args = func_get_args();
   $query = call_user_func_array(array(&$this, 'formatQueryString'), $args);
   if (!$this->hasError()) {
     $result = $this->query($query);
   }
   return $result;
 }

/**
 * Executes the SQL query that has multielement
 * If the argument was given as string,
 * it have use for the semicolon (;) in the end of line
 *
 * @example
 * <code>
 * $posql = new Posql("foo.db");
 * $sql  = "CREATE TABLE foo (cols1 DEFAULT 0);\n";// adds ";\n"
 * $sql .= "INSERT INTO foo (cols1) VALUES(1);\n";
 * $sql .= "INSERT INTO foo (cols1) VALUES(2);\n";
 * $ret = $posql->multiQuery($sql);
 * var_dump($ret);
 * </code>
 *
 * @example
 * <code>
 * $posql = new Posql("foo.db");
 * $sql = array();
 * $sql[] = "CREATE TABLE bar (cols1 DEFAULT 0)";
 * $sql[] = "INSERT INTO bar (cols1) VALUES(1)";
 * $sql[] = "INSERT INTO bar (cols1) VALUES(2)";
 * $ret = $posql->multiQuery($sql);
 * var_dump($ret);
 * </code>
 *
 * @param  mixed   the SQL query as array or string
 * @return number  number of affected rows
 * @access public
 */
 function multiQuery($query){
   $result = 0;
   if (!is_array($query)) {
     $query = $this->parseMultiQuery($query);
   }
   if (is_array($query)) {
     $len = count($query);
     while (--$len >= 0) {
       $sql = array_shift($query);
       if (!is_string($sql)) {
         $this->pushError('Must be given only one dimensional array');
         break;
       }
       /*
       debug($sql, 'SQL@multiQuery');
       */
       $result += (int)$this->exec($sql);
       if ($this->hasError()) {
         //XXX: rollBack(); Should do?
         //$this->pushError('Cannot executes query(%s)', $sql);
         break;
       }
     }
   }
   return $result;
 }

/**
 * Quotes a string for use in a query.
 *
 * The type argument is not implemented.
 * but it will be automatically distinguished.
 *
 * @param  string  the string to be quoted
 * @param  mixed   the type (not implemented)
 * @return string  quoted string that is safe to pass into an SQL statement
 * @access public
 */
 function quote($string, $type = null){
   $string = $this->formatQueryString('%a', $string);
   return $string;
 }

/**
 * Prepares a statement for execution and returns a statement object
 *
 * @param  string           the SQL statement
 * @return Posql_Statement  the instance of Posql_Statement object
 * @access public
 */
 function prepare($query){
   $result = new Posql_Statement($this, null, $query);
   return $result;
 }

/**
 * Operates a transaction
 *
 * Note:
 *   Posql does not support nested transaction.
 *
 * Mode:
 *  - BEGIN    : Initialize and starts the transaction block.
 *  - START    : Alias for BEGIN.
 *
 *  - COMMIT   : A present transaction is committed.
 *  - END      : Alias for COMMIT.
 *
 *  - ROLLBACK : A present transaction is rolls backed.
 *
 * @param  string   the transaction command
 * @return boolean  success or failure
 * @access public
 */
 function transaction($mode){
   $result = false;
   if (!$this->hasError()) {
     if (!is_string($mode)) {
       $mode = gettype($mode);
     } else {
       $mode = trim($mode);
     }
     switch (strtolower($mode)) {
       case 'begin':
       case 'start':
           if (!empty($this->_inTransaction)) {
             $this->pushError('Not supported the nested transaction');
           } else {
             if (!$this->lockExAllTables()) {
               $this->pushError('Failed to begin the transaction');
             }
             if ($this->hasError()) {
               $this->unlockAllTables();
             } else {
               $this->lockDatabase();
               $rp = $this->fopen('r');
               if ($rp) {
                 $this->fseekLine($rp, 1);
                 $wp = $this->fopen('r+');
                 if ($wp) {
                   $this->fseekLine($wp, 1);
                   while (!feof($wp)) {
                     $this->fgets($wp);
                   }
                   $trans_name = $this->getTransactionKey();
                   $delim = $this->DELIM_TABLE;
                   while (!feof($rp)) {
                     $line = $this->fgets($rp, true);
                     if ($line != null) {
                       if (strpos($line, $trans_name) === 0) {
                         break;
                       } else {
                         $line = $trans_name . $delim . $this->encode($line);
                         $this->fputs($wp, $line . $this->NL);
                       }
                     }
                   }
                   fclose($wp);
                   $this->_inTransaction = true;
                   $this->_transactionName = $trans_name;
                   $result = true;
                 }
                 fclose($rp);
               }
             }
           }
           break;
       case 'commit':
       case 'end':
           if (empty($this->_inTransaction)
            || empty($this->_transactionName)) {
             $this->pushError('Unable to commit the transaction');
           } else {
             $rp = $this->fopen('r');
             if ($rp) {
               $this->fseekLine($rp, 3);
               $wp = $this->fopen('r+');
               if ($wp) {
                 $this->fseekLine($wp, 3);
                 $trans_name = $this->_transactionName;
                 $delim = $this->DELIM_TABLE;
                 while (!feof($rp)) {
                   $wrote = false;
                   $line = $this->fgets($rp, true);
                   if ($line != null && strpos($line, $delim) !== false) {
                     list($key) = explode($delim, $line);
                     if (strpos($key, $trans_name) === 0) {
                       $line = str_repeat(' ', strlen($line)) . $this->NL;
                       $this->fputs($wp, $line);
                       $wrote = true;
                     }
                   }
                   if (!$wrote) {
                     $this->fgets($wp);
                   }
                 }
                 fclose($wp);
                 $result = true;
               }
               fclose($rp);
             }
             for ($i = 0; $i < 2; $i++) {
               $this->vacuum();
             }
             $this->_inTransaction = null;
             $this->_transactionName = null;
             $this->unlockDatabase();
             $this->unlockAllTables();
           }
           break;
       case 'rollback':
           if (empty($this->_inTransaction)
            || empty($this->_transactionName)) {
             $this->pushError('Unable to rollback the transaction');
           } else {
             $rp = $this->fopen('r');
             if ($rp) {
               $this->fseekLine($rp, 1);
               $wp = $this->fopen('r+');
               if ($wp) {
                 $this->fseekLine($wp, 1);
                 $ap = $this->fopen('a');
                 if ($ap) {
                   $tp = $this->fopen('r');
                   if ($tp) {
                     $this->fseekLine($tp, 3);
                     $trans_name = $this->_transactionName;
                     $delim = $this->DELIM_TABLE;
                     $end_symbol = 'EOT';
                     $end_point = sprintf('%s:%s;%s',
                       $trans_name,
                       $end_symbol,
                       $this->NL
                     );
                     $end_point_trim = trim($end_point);
                     $this->fputs($ap, $end_point);
                     $transaction_exists = false;
                     $nl_length = (-strlen($this->NL));
                     while (!feof($tp)) {
                       $line = $this->fgets($tp, true);
                       if ($line != null) {
                         if ($line === $end_point_trim) {
                           break;
                         }
                         if (strpos($line, $delim) !== false) {
                           list($key, $data) = explode($delim, $line);
                           if (strpos($key, $trans_name) === 0) {
                             $transaction_exists = true;
                             $data = $this->decode($data);
                             if (substr($data, $nl_length) !== $this->NL) {
                               $data .= $this->NL;
                             }
                             $this->fputs($ap, $data);
                           }
                         }
                       }
                     }
                     if (!$transaction_exists) {
                       $this->pushError('Not exists the transaction data');
                     }
                     while (!feof($rp)) {
                       $line = $this->fgets($rp, true);
                       if ($line != null) {
                         if ($line === $end_point_trim) {
                           $size = strlen($line);
                           $line = str_repeat(' ', $size) . $this->NL;
                           $this->fputs($wp, $line);
                           break;
                         }
                         $size = strlen($line);
                         $line = str_repeat(' ', $size) . $this->NL;
                         $this->fputs($wp, $line);
                       } else {
                         $this->fgets($wp);
                       }
                     }
                     fclose($tp);
                     $result = true;
                   }
                   fclose($ap);
                 }
                 fclose($wp);
               }
               fclose($rp);
             }
             for ($i = 0; $i < 2; $i++) {
               $this->vacuum();
             }
             $this->_inTransaction = null;
             $this->_transactionName = null;
             $this->unlockDatabase();
             $this->unlockAllTables();
           }
           break;
       default:
           $this->pushError('Not supported transaction command(%s)', $mode);
           break;
     }
   }
   return $result;
 }

/**
 * Initialize and starts the transaction block
 *
 * @see  transaction
 *
 * @param  void
 * @return boolean  success or failure
 * @access public
 */
 function beginTransaction(){
   $result = $this->transaction('begin');
   return $result;
 }

/**
 * Commits a transaction
 *
 * @see  transaction
 *
 * @param  void
 * @return boolean  success or failure
 * @access public
 */
 function commit(){
   $result = $this->transaction('commit');
   return $result;
 }

/**
 * Rolls back a transaction
 *
 * @see  transaction
 *
 * @param  void
 * @return boolean  success or failure
 * @access public
 */
 function rollBack(){
   $result = $this->transaction('rollback');
   return $result;
 }

/**
 * Return the "rowid" of the last row insert
 *  from this connection to the database.
 *
 * This method implemented for PDO with compatibility
 *
 * @see  getLastInsertId
 *
 * @param  void
 * @return number  the value to inserted "rowid" or NULL
 * @access public
 */
 function lastInsertId(){
   $result = $this->getLastInsertId();
   return $result;
 }

/**
 * Return the Pager object which has each pages information
 *
 * @see Posql_Pager
 *
 * @param  number  number of total items
 * @param  number  current page number
 * @param  number  number of items per page
 * @param  number  number of page links for each window
 * @return Posql_Pager  the pager object
 *                 - totalPages  : number of total pages
 *                 - currentPage : number of current page
 *                 - range       : number of page links for each window
 *                 - pages       : array with number of pages
 *                 - startPage   : number of start page
 *                 - endPage     : number of end page
 *                 - prev        : number of previous page
 *                 - next        : number of next page
 *                 - offset      : number offset of SELECT statement
 *                 - limit       : number limit of SELECT statement
 * @access public
 */
 function getPager($total_count = null, $curpage = null,
                   $perpage     = null, $range   = null){
   $result = new Posql_Pager();
   $result->setPager($total_count, $curpage, $perpage, $range);
   return $result;
 }
}
