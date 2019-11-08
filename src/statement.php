<?php
require_once dirname(__FILE__) . '/method.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_Statement
 *
 * The result storage and statement handle class for Posql result objects
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet.aqua@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Statement {

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
 * @param  object           give of the Posql self
 * @param  array            the result rows as array
 * @param  string           the last SQL statement
 * @return Posql_Statement
 * @access public
 */
 function __construct(&$posql, $rows = array(), $query = null) {
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
   unset($this->posql);
   foreach (get_object_vars($this) as $prop => $val) {
     if ($prop != null) {
       unset($this->{$prop});
     }
   }
 }

/**
 * Reference to the instance of Posql
 *
 * @param  object  give of the Posql self
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
