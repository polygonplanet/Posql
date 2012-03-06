<?php
require_once dirname(__FILE__) . '/archive.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_Method
 *
 * This class provides standard SQL92-99 functions on SQL mode
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Method {

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
 * @var    Posql           maintains reference of the Posql class
 * @access private
 */
 var $posql;

/**
 * @var    Posql_CType     maintains reference of the Posql_CType class
 * @access private
 */
 var $ctype;

/**
 * @var    Posql_Math      maintains reference of the Posql_Math class
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
 * @return Posql_Method
 * @access public
 */
 function Posql_Method(){}

/**
 * Destructor for PHP Version 5+
 * Release all referenced properties
 *
 * @access public
 */
 function __destruct(){
   unset($this->charset, $this->hasIConv, $this->hasMBString);
   unset($this->unicode, $this->math, $this->ctype);
   unset($this->posql);
   foreach (get_object_vars($this) as $prop => $val) {
     if ($prop != null) {
       unset($this->{$prop});
     }
   }
 }

/*
 {{{   Internal functions
*/

/**
 * Reference to the Posql, and its properties
 *
 * @param  object  give of the Posql self
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
 * Return the formatted date from the unix time
 */
 function from_unixtime($unixtime = null, $format = null){
   $result = null;
   if (!$this->posql->isUnixTime($unixtime)) {
     $unixtime = 0;
   }
   $result = $this->now($unixtime);
   if ($format !== null) {
     $result = $this->strftime($format, $result);
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
   if ($time !== null && is_numeric($time)) {
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
