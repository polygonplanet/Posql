<?php
require_once dirname(__FILE__) . '/utils.php';
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

