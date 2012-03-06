<?php
require_once dirname(__FILE__) . '/unicode.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_ECMA
 *
 * This class emulates ECMAScript/JavaScript functions as ECMA-262 3rd edition
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_ECMA {

/**
 * @var    float     constant value as NaN
 * @access private
 */
 var $NaN = null;

/**
 * @var    Posql_Unicode   maintains instance of the Posql_Unicode class
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
 * @return Posql_ECMA
 * @access public
 */
 function Posql_ECMA(){
   $this->_init();
 }

/**
 * Destructor for PHP Version 5+
 * Release all referenced properties
 *
 * @access public
 */
 function __destruct(){
   unset($this->NaN, $this->unicode, $this->_inited);
 }

/**
 * Reference to the Posql, and its properties
 *
 * @param  object  give of the Posql self
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
       if ($this->unicode->supportsUTF8PCRE) {
         $result = preg_split('|(.{1})|su', $string, -1,
                     PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
       } else {
         $result = $this->unicode->_toUnicode($string);
         if (!is_array($result)) {
           $result = array();
         } else {
           $result = array_map(array(&$this->unicode, 'chr'), $result);
         }
       }
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
