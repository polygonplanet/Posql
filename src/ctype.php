<?php
require_once dirname(__FILE__) . '/config.php';
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
 * @author   polygon planet <polygon.planet.aqua@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_CType {

/**
 * @var    boolean   maintains ctype library can be use
 * @access private
 */
 var $hasCType;

/**
 * Class constructor
 *
 * @param  void
 * @return Posql_CType
 * @access public
 */
 function __construct() {
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

