<?php
require_once dirname(__FILE__) . '/scan.php';
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
 * @version   $Id$
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
 * @param  string          table name
 * @param  string          the list of columns (e.g. "foo,bar")
 * @param  mixed           expression as WHERE clause,default=true (select all)
 * @param  mixed           value to decide the group
 * @param  mixed           expression as HAVING clause,default=true(select all)
 * @param  mixed           value to decide the order
 * @param  mixed           value to decide limit value
 * @return Posql_Statement Posql_Statement object which has the result-set
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
         $result = $this->applyOptimizeExplain($args);
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
 * @param  mixed           tables (e.g. "table1 JOIN table2")
 * @param  string          for choice columns
 * @param  mixed           expression as WHERE clause,default=true (select all)
 * @param  mixed           value to decide the group
 * @param  mixed           expression as HAVING clause,default=true(select all)
 * @param  string          value to decide the order
 * @param  mixed           value to decide limit value
 * @return Posql_Statement Posql_Statement object which has the result-set
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
 * @param  array           result set which was executed by sub-query
 * @param  string          the list of columns (e.g. "foo,bar")
 * @param  mixed           expression as WHERE clause,default=true (select all)
 * @param  mixed           value to decide the group
 * @param  mixed           expression as HAVING clause,default=true(select all)
 * @param  mixed           value to decide the order
 * @param  mixed           value to decide limit value
 * @return Posql_Statement Posql_Statement object which has the result-set
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
 * @param  array           result set which was executed by sub-query
 * @param  string          for choice columns
 * @param  mixed           expression as WHERE clause,default=true (select all)
 * @param  mixed           value to decide the group
 * @param  mixed           expression as HAVING clause,default=true(select all)
 * @param  string          value to decide the order
 * @param  mixed           value to decide limit value
 * @return Posql_Statement Posql_Statement object which has the result-set
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
 * @param  string          the list of select statement as expression
 * @param  mixed           the WHERE clause as the expression
 * @param  mixed           value to decide the group
 * @param  mixed           expression as HAVING clause
 * @param  mixed           value to decide the order
 * @param  mixed           value to decide limit value
 * @return Posql_Statement Posql_Statement object which has the result-set
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
 * @param  string SQL query
 * @return mixed  results of the query
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
 * @return mixed        results of the query
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
 * @param  number       number of total items
 * @param  number       current page number
 * @param  number       number of items per page
 * @param  number       number of page links for each window
 * @return Posql_Pager  the pager object
 *                      - totalPages  : number of total pages
 *                      - currentPage : number of current page
 *                      - range       : number of page links for each window
 *                      - pages       : array with number of pages
 *                      - startPage   : number of start page
 *                      - endPage     : number of end page
 *                      - prev        : number of previous page
 *                      - next        : number of next page
 *                      - offset      : number offset of SELECT statement
 *                      - limit       : number limit of SELECT statement
 * @access public
 */
 function getPager($total_count = null, $curpage = null,
                   $perpage     = null, $range   = null){
   $result = new Posql_Pager();
   $result->setPager($total_count, $curpage, $perpage, $range);
   return $result;
 }
}
