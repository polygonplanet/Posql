<?php
require_once dirname(__FILE__) . '/charset.php';
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
class Posql_UTF8 {

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
 * @var    Posql_Charset   maintains instance of the Posql_Charset class
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
 * @var    boolean   maintains reference to the
 *                   Posql's property supportsUTF8PCRE (PCRE_UTF8)
 * @access private
 */
 var $supportsUTF8PCRE;

/**
 * Class constructor
 *
 * @param  void
 * @return Posql_UTF8
 * @access public
 */
 function Posql_UTF8(){
   $this->init();
 }

/**
 * Destructor for PHP Version 5+
 * Release all referenced properties
 *
 * @access public
 */
 function __destruct(){
   unset($this->pcharset, $this->charset);
   foreach (get_object_vars($this) as $prop => $val) {
     if ($prop != null) {
       unset($this->{$prop});
     }
   }
 }

/**
 * Reference to the Posql, and its properties
 *
 * @param  object  give of the Posql self
 * @return void
 * @access private
 */
 function _referProperty(&$posql){
   $this->charset = & $posql->charset;
   $this->pcharset = & $posql->pcharset;
   $this->supportsUTF8PCRE = & $posql->supportsUTF8PCRE;
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
     $this->hasIConv = false;
     $this->isPHP5 = version_compare(PHP_VERSION, 5, '>=');
     $this->isPHP520 = version_compare(PHP_VERSION, '5.2.0', '>=');
     $this->isPHP560 = version_compare(PHP_VERSION, '5.6.0', '>=');

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

   if ($this->hasIConv && !$this->isPHP560) {
     // iconv.internal_encoding is deprecated in PHP 5.6.0
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

   if ($this->hasIConv && $this->prevIConvEncoding != null && !$this->isPHP560) {
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
         $pattern = '<' . $offset_pattern . $length_pattern . '>s';
         if ($this->supportsUTF8PCRE) {
           $pattern .= 'u';
         }
         if (preg_match($pattern, $string, $match)) {
           $result = $match[1];
         }
       }
     }
   }
   return $result;
 }
}
