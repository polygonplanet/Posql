<?php
require_once dirname(__FILE__) . '/utf8.php';
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
 * @return Posql_Unicode
 * @access public
 */
 function Posql_Unicode(){
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

