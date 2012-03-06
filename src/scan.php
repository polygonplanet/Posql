<?php
require_once dirname(__FILE__) . '/cache.php';
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
 * Applies the optimized explain for table scanning
 *
 * @param  array   parsed select arguments
 * @return array   result-set
 * @access private
 */
 function applyOptimizeExplain($args){
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
