<?php
require_once dirname(__FILE__) . '/core.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_Cache
 *
 * This class handles the query cache
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet.aqua@gmail.com>
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
