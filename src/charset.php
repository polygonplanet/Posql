<?php
require_once dirname(__FILE__) . '/math.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_Charset
 *
 * This class is a library to handle the character-code
 * If the mbstring library is available, most functions will use it
 * Or, when the iconv library is available, it will be used
 * When it is both invalid, it distinguishes by PHP
 * (In this case, the execution speed might be slow)
 *
 * The index keys and the encoding-list which can be handled in this class
 * +----+---------------------
 * | No.| Encoding
 * +----+---------------------
 * |  0 | ASCII
 * |  1 | EUC-JP
 * |  2 | SJIS
 * |  3 | JIS (ISO-2022-JP)
 * |  4 | ISO-8859-1
 * |  5 | UTF-8
 * |  6 | UTF-16
 * |  7 | UTF-16BE
 * |  8 | UTF-16LE
 * |  9 | UTF-32
 * | 10 | BIG5
 * | 11 | EUC-CN (GB2312)
 * | 12 | EUC-KR (KS-X-1001)
 * | 13 | BINARY
 * +----+----------------------
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet.aqua@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Charset {

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
 * @var    boolean   temporary variable for the error
 * @access private
 */
 var $hasError;

/**
 * @var    boolean   whether PHP VERSION is over 5 or not
 * @access private
 */
 var $isPHP5;

/**
 * @var    string    maintains reference to the Posql's property charset
 * @access private
 */
 var $charset;

/**
 * Class constructor
 *
 * @param  void
 * @return Posql_Charset
 * @access public
 */
 function __construct() {
   $this->hasMBString = extension_loaded('mbstring')
                     && function_exists('mb_detect_encoding');
   $this->hasIConv = extension_loaded('iconv');
   $this->isPHP5 = version_compare(PHP_VERSION, 5, '>=');
   $this->hasError = false;
 }

/**
 * Destructor for PHP Version 5+
 * Release the reference.
 *
 * Note:
 *  There is a leakage in the memory management of PHP.
 *  Bug #33595 recursive references leak memory
 *  @see http://bugs.php.net/bug.php?id=33595
 *
 * Assumes access as "public" for all __destruct() functions,
 *  if you care about the zend memory table.
 *
 * @access public
 */
 function __destruct(){
   unset($this->charset);
   foreach (get_object_vars($this) as $prop => $val) {
     if ($prop != null) {
       unset($this->{$prop});
     }
   }
 }

/**
 * Reference to the Posql, and its properties
 *
 * @param  Posql   give of the Posql self
 * @return void
 * @access private
 */
 function _referProperty(&$posql){
   $this->charset = & $posql->charset;
 }

/**
 * Mapping from the character-code name to
 *  the key index number of the encoding-list which can be used
 * Given the argument as "AUTO", it will be detected automatically
 *
 * @param  string   the encoding name
 * @return number   the key index number of encoding-list
 * @access private
 */
 function getEncodingIndex($encoding = 'auto'){
   static $marks = array('-', '_', '.', ':');
   $result = -1;
   if (is_string($encoding)) {
     foreach ($marks as $mark) {
       if (strpos($encoding, $mark) !== false) {
         $split = explode($mark, $encoding);
         $encoding = implode('', $split);
       }
     }
     switch (strtoupper(trim($encoding))) {
       case 'ANSIX341968':
       case 'ANSIX341986':
       case 'ASCII':
       case 'CP367':
       case 'CSASCII':
       case 'IBM367':
       case 'ISO646IRV1991':
       case 'ISOIR6':
       case 'ISO646':
       case 'ISO646US':
       case 'US':
       case 'USASCII':
           $result = 0;
           break;
       case 'EUC':
       case 'EUCJP':
       case 'EUCJPWIN':
       case 'EUCJPMS':
       case 'EUCJPOPEN':
       case 'XEUCJP':
           $result = 1;
           break;
       case 'CP932':
       case 'MSKANJI':
       case 'SJIS':
       case 'SJISWIN':
       case 'SJISOPEN':
       case 'SHIFTJIS':
       case 'WINDOWS31J':
       case 'XSJIS':
           $result = 2;
           break;
       case 'ISO2022JP':
       case 'ISO2022JPMS':
       case 'JIS':
           $result = 3;
           break;
       case 'CP819':
       case 'CSISOLATIN1':
       case 'IBM819':
       case 'ISO885911987':
       case 'ISO88591':
       case 'ISOIR100':
       case 'L1':
       case 'LATIN1':
           $result = 4;
           break;
       case 'UTF8':
           $result = 5;
           break;
       case 'UTF16':
           $result = 6;
           break;
       case 'UTF16BE':
           $result = 7;
           break;
       case 'UTF16LE':
           $result = 8;
           break;
       case 'UTF32':
           $result = 9;
           break;
       case 'CNBIG5':
       case 'CP950':
       case 'BIG5':
       case 'BIGFIVE':
           $result = 10;
           break;
       case 'CNGB':
       case 'EUCCN':
       case 'GB':
       case 'GB2312':
       case 'XEUCCN':
           $result = 11;
           break;
       case 'EUCKR':
       case 'KSX1001':
       case 'KSC5601':
       case 'XEUCKR':
           $result = 12;
           break;
       case 'BIN':
       case 'BINARY':
           $result = 13;
           break;
       case 'AUTO':
       default:
           $result = -1;
           break;
     }
   }
   return $result;
 }

/**
 * Mapping from the key index number of the encoding-list
 *  to the character-code name which can be used
 * When the index key is over the range,
 *  and the second argument is given, it will be used
 *
 * @param  number   the key index number of encoding
 * @param  string   the type of results
 * @param  string   optionally, the supplement encoding name
 * @return string   the encoding name
 * @access private
 */
 function getEncodingName($index, $type = null, $sub_encoding = null){
   if ($type == null) {
     $type = 'encoding';
   }
   $default = (string)$index;
   $sub = (string)$sub_encoding;
   if ($sub != null) {
     $sub = trim($sub);
   }
   $result = array(
     'encoding' => $sub,
     'method'   => $sub,
     'iconv'    => $sub,
     'mbstring' => $sub
   );
   $encoding = & $result['encoding'];
   $method   = & $result['method'];
   $iconv    = & $result['iconv'];
   $mbstring = & $result['mbstring'];
   $enc_index = $index;
   if (is_string($index)) {
     $enc_index = $this->getEncodingIndex($index);
   }
   switch ((int)$enc_index) {
     case 0:
         $encoding = 'ASCII';
         $method   = 'ASCII';
         $iconv    = 'ASCII';
         $mbstring = 'ASCII';
         break;
     case 1:
         $encoding = 'EUC-JP';
         $method   = 'EUCJP';
         $iconv    = 'EUC-JP';
         $mbstring = 'eucJP-win';
         break;
     case 2:
         $encoding = 'SJIS';
         $method   = 'SJIS';
         $iconv    = 'CP932';
         $mbstring = 'SJIS-win';
         break;
     case 3:
         $encoding = 'JIS';
         $method   = 'JIS';
         $iconv    = 'ISO-2022-JP';
         $mbstring = 'JIS';
         break;
     case 4:
         $encoding = 'ISO-8859-1';
         $method   = 'LATIN1';
         $iconv    = 'ISO-8859-1';
         $mbstring = 'ISO-8859-1';
         break;
     case 5:
         $encoding = 'UTF-8';
         $method   = 'UTF8';
         $iconv    = 'UTF-8';
         $mbstring = 'UTF-8';
         break;
     case 6:
         $encoding = 'UTF-16';
         $method   = 'UTF16';
         $iconv    = 'UTF-16';
         $mbstring = 'UTF-16';
         break;
     case 7:
         $encoding = 'UTF-16BE';
         $method   = 'UTF16BE';
         $iconv    = 'UTF-16BE';
         $mbstring = 'UTF-16BE';
         break;
     case 8:
         $encoding = 'UTF-16LE';
         $method   = 'UTF16LE';
         $iconv    = 'UTF-16LE';
         $mbstring = 'UTF-16LE';
         break;
     case 9:
         $encoding = 'UTF-32';
         $method   = 'UTF32';
         $iconv    = 'UTF-32';
         $mbstring = 'UTF-32';
         break;
     case 10:
         $encoding = 'BIG5';
         $method   = 'BIG5';
         $iconv    = 'BIG5';
         $mbstring = 'BIG-5';
         break;
     case 11:
         $encoding = 'EUC-CN';
         $method   = 'GB';
         $iconv    = 'EUC-CN';
         $mbstring = 'EUC-CN';
         break;
     case 12:
         $encoding = 'EUC-KR';
         $method   = 'EUCKR';
         $iconv    = 'EUC-KR';
         $mbstring = 'EUC-KR';
         break;
     case 13:
         $encoding = 'Binary';
         $method   = 'Binary';
         $iconv    = 'Binary';
         $mbstring = 'Binary';
         break;
     default:
         $encoding = $default;
         $method   = $default;
         $iconv    = $default;
         $mbstring = $default;
         break;
   }
   unset($encoding, $method, $iconv, $mbstring);
   if (array_key_exists($type, $result)) {
     $result = $result[$type];
   }
   return $result;
 }

/**
 * Returns the order of encoding
 *  when the encoding detection was given as "AUTO"
 *
 * @param  void
 * @return array   the orders of encoding-list
 * @access private
 */
 function getAutoDetectOrder(){
   static $orders = array(
     'method' => array(
       'UTF32',
       'UTF16',
       'BINARY',
       'ASCII',
       'JIS',
       'UTF-8',
       'EUC-JP',
       'SJIS',
       'BIG5',
       'EUC-KR',
       'EUC-CN'
     ),
     'iconv' => array(
       'UTF-32',
       'UTF-16',
       'ASCII',
       'JIS',
       'UTF-8',
       'EUC-JP',
       'SJIS',
       'BIG5',
       'EUC-KR',
       'EUC-CN',
       'BINARY'
     ),
     'mbstring' => array(
       'UTF-32',
       'UTF-16',
       'ASCII',
       'JIS',
       'UTF-8',
       'eucJP-win',
       'SJIS-win',
       'BIG-5',
       'EUC-KR',
       'EUC-CN',
       'BINARY'
     )
   );
   if ($this->hasMBString) {
     $result = $orders['mbstring'];
   } else if ($this->hasIConv) {
     $result = $orders['iconv'];
   } else {
     $result = $orders['method'];
   }
   return $result;
 }

/**
 * Convert character encoding
 *
 * Given the third argument as "AUTO", it will be converted automatically
 * The encoding-list of third argument order will be specified by array
 *  or comma separated list string
 * If the mbstring library is available, it will be used for conversion
 * Or, if the iconv library is available, it will be used for conversion
 *
 * @param  string   the string being converted
 * @param  string   the type of encoding that to be converted to
 * @param  string   the character code names before conversion
 * @return string   the encoded string, or FALSE on error
 * @access public
 */
 function convert($string, $to = 'UTF-8', $from = 'auto'){
   $result = false;
   if ($string != null && $to != null && is_string($to)) {
     $string = (string) $string;
     if (strpos($to, '//') !== false) {
       $split = explode('//', $to);
       $to = array_shift($split);
     }
     if (is_array($from)
      || (is_string($from) && strcasecmp($from, 'auto') === 0)) {
       $detect = $this->detectEncoding($string, $from);
       if ($detect !== false) {
         $from = $detect;
       }
     }
     if ($this->hasMBString) {
       $to = $this->getEncodingName($to, 'mbstring', $to);
       $from = $this->getEncodingName($from, 'mbstring', $from);
       $result = mb_convert_encoding($string, $to, $from);
     } else if ($this->hasIConv) {
       $to  = $this->getEncodingName($to, 'iconv', $to);
       $to .= '//TRANSLIT//IGNORE';
       $from = $this->getEncodingName($from, 'iconv', $from);
       $result = @iconv($from, $to, $string);
     }
     if (!$result) {
       $to = $this->getEncodingName($to, null, $to);
       $from = $this->getEncodingName($from, null, $from);
       if (strcasecmp($to, $from) === 0) {
         $result = $string;
       } else {
         $result = false;
       }
     }
   }
   return $result;
 }

/**
 * Detects character encoding
 * Given the first argument as "AUTO", it will be detected automatically
 * the encoding-list of second argument order will be specified by array
 *  or comma separated list string
 * If the Iconv library is effective, it will be used for detection
 * If it is invalid, it will be detected by this class methods
 *
 * @param  string   the string being detected
 * @param  mixed    the encoding-list of character encoding
 * @return string   the detected character encoding
 * @access public
 */
 function detectEncoding($string, $encodings = 'auto'){
   $result = false;
   if (is_string($encodings)) {
     $encodings = strtoupper(trim($encodings));
     if ($encodings === 'AUTO') {
       $encodings = $this->getAutoDetectOrder();
     } else if (strpos($encodings, ',') !== false) {
       $encodings = preg_split('{\s*,\s*}', $encodings);
     } else {
       $encodings = trim($encodings);
     }
   }
   foreach ((array)$encodings as $encode) {
     if ($encode == null) {
       continue;
     }
     $valid = false;
     if ($this->hasMBString) {
       if ($this->detectByMBString($encode, $string)) {
         $valid = true;
       }
     } else if ($this->hasIConv) {
       if ($this->detectByIConv($encode, $string)) {
         $valid = true;
       }
     } else {
       if ($this->detectByMethod($encode, $string)) {
         $valid = true;
       }
     }
     if ($valid) {
       $result = $this->getEncodingName($encode);
       break;
     }
   }
   return $result;
 }

/**
 * Calls the encoding detection function in this class
 *
 * @param  string  the encoding name, or the method name
 * @param  string  the string of target
 * @return boolean the encoding was proper or not
 * @access private
 */
 function detectByMethod($encoding, $string){
   $result = false;
   $method = $this->getEncodingName($encoding, 'method', 'String');
   $method = sprintf('is%s', $method);
   $func = array(&$this, $method);
   if (is_callable($func)) {
     $result = call_user_func($func, $string);
   }
   unset($func);
   return $result;
 }

/**
 * Detects the encoding to using the mbstring library
 *
 * @param  string  the encoding name
 * @param  string  the string of target
 * @return boolean the encoding was proper or not
 * @access private
 */
 function detectByMBString($encoding, $string){
   $result = false;
   $charset = $this->getEncodingName($encoding, 'mbstring');
   if (0 !== strcasecmp($charset, 'BINARY')) {
     if ($charset == null && $encoding != null) {
       $detect = @mb_detect_encoding($string, $encoding, true);
     } else {
       $detect = mb_detect_encoding($string, $charset, true);
     }
     $result = is_string($detect) && strlen($detect);
   }
   if (!$result) {
     $result = $this->detectEncodingHelper($charset, $string, false);
   }
   return $result;
 }

/**
 * Detects the encoding to using the iconv library
 *
 * @param  string  the encoding name
 * @param  string  the string of target
 * @return boolean the encoding was proper or not
 * @access private
 */
 function detectByIConv($encoding, $string){
   $result = false;
   $charset = $this->getEncodingName($encoding, 'iconv');
   if ($charset == null) {
     $charset = $encoding;
   }
   if ($charset != null) {
     $this->hasError = false;
     if (0 !== strcasecmp($charset, 'BINARY')) {
       if ($this->isPHP5) {
         set_error_handler(array($this, 'iconvErrorHandler'), E_ALL);
       } else {
         set_error_handler(array(&$this, 'iconvErrorHandler'));
       }
       iconv($charset, 'UTF-16', $string); // maybe UTF-16 is proper
       restore_error_handler();
     }
     if (!$this->hasError) {
       $result = $this->detectEncodingHelper($charset, $string, true);
     }
   }
   return $result;
 }

/**
 * A helper function to detect encoding as strict
 *
 * @access private
 */
 function detectEncodingHelper($charset, $string, $default = false){
   $result = false;
   switch (strtoupper($charset)) {
     case 'ASCII':
         $result = $this->isASCII($string);
         break;
     case 'JIS':
     case 'ISO-2022-JP':
         $result = $this->isJIS($string);
         break;
     case 'UTF-16':
         $result = $this->isUTF16($string);
         break;
     case 'UTF-16BE':
         $result = $this->isUTF16BE($string);
         break;
     case 'UTF-16LE':
         $result = $this->isUTF16LE($string);
         break;
     case 'UTF-32':
         $result = $this->isUTF32($string);
         break;
     case 'BINARY':
         $result = $this->isBinary($string);
         break;
     default:
         $result = $default;
         break;
   }
   return $result;
 }

/**
 * A handle to detect character-code by using iconv
 *
 * @access private
 */
 function iconvErrorHandler($no, $msg, $file, $line){
   if ($no === E_NOTICE) {
     $this->hasError = true;
   }
   //return true;
 }

/**
 * An insignificantly function
 */
 function isString($string){
   $result = false;
   if (is_scalar($string) || $string === null) {
     $result = true;
   }
   return $result;
 }

/**
 * Binary (exe, images, so, etc.)
 *
 * Note:
 *   This function is not considered for Unicode
 */
 function isBinary($string){
   static $bin_chars = array(
     "\x00", "\x01", "\x02", "\x03", "\x04",
     "\x05", "\x06", "\x07", "\xFF"
   );
   $result = false;
   if ($string != null) {
     foreach ($bin_chars as $char) {
       if (strpos($string, $char) !== false) {
         $result = true;
         break;
       }
     }
   }
   return $result;
 }

/**
 * ASCII (ISO-646)
 */
 function isASCII($string){
   $result = false;
   if (!preg_match('{[\x80-\xFF]}', $string)) {
     $result = strpos($string, "\x1B") === false;
   }
   return $result;
 }

/**
 * Latin-1 (ISO/IEC 8859-1)
 *
 * @link http://www.rfc-editor.org/rfc/rfc1554.txt
 * @link http://www.alanwood.net/demos/charsetdiffs.html
 */
 function isLATIN1($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   for (; $i < $length; ++$i) {
     if (($bytes[$i] === 0x09 || $bytes[$i] === 0x0A
      ||  $bytes[$i] === 0x0D || $bytes[$i] === 0x20)
      || ($bytes[$i]  >  0x20 && $bytes[$i]  <  0x7F)
      || ($bytes[$i]  >  0xA0 && $bytes[$i]  <  0xFF)) {
       continue;
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * ISO-2022-JP (JIS)
 */
 function isJIS($string){
   $result = false;
   if (!preg_match('{[\x80-\xFF]}', $string)) {
     $pos = strpos($string, "\x1B");
     if ($pos !== false) {
       $esc = substr($string, $pos + 1, 2);
       if (strlen($esc) === 2) {
         $esc1 = ord($esc);
         $esc2 = ord(substr($esc, 1));
         if ($esc1 === 0x24) {
           if ($esc2 === 0x28    // JIS X 0208-1990
            || $esc2 === 0x40    // JIS X 0208-1978
            || $esc2 === 0x42) { // JIS X 0208-1983
             $result = true;
           }
         } else if ($esc1 === 0x26 // JIS X 0208-1990
                &&  $esc2 === 0x40) {
           $result = true;
         }
       }
     }
   }
   return $result;
 }

/**
 * EUC-JP
 */
 function isEUCJP($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if ($bytes[$i] < 0x80) {
       continue;
     }
     if ($bytes[$i] < 0x8E) {
       $result = false;
       break;
     }
     if ($bytes[$i] === 0x8E) {
       if (!isset($bytes[++$i])
        || ($bytes[$i] < 0xA1 || $bytes[$i] > 0xDF)) {
         $result = false;
         break;
       }
     } else if ($bytes[$i] >= 0xA1 && $bytes[$i] <= 0xFE) {
       if (!isset($bytes[++$i])
        || ($bytes[$i] < 0xA1 || $bytes[$i] > 0xFE)) {
         $result = false;
         break;
       }
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * Shift-JIS (SJIS)
 */
 function isSJIS($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if (($bytes[$i] <= 0x80)
      || ($bytes[$i] >= 0xA1 && $bytes[$i] <= 0xDF)) {
       continue;
     }
     if ($bytes[$i] === 0xA0 || $bytes[$i] > 0xEF) {
       $result = false;
       break;
     }
     if (!isset($bytes[++$i])
      || ($bytes[$i] < 0x40 || $bytes[$i] === 0x7F || $bytes[$i] > 0xFC)) {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * UTF-8
 */
 function isUTF8($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $bom = array_slice($bytes, 0, 3);
   if (isset($bytes[0], $bytes[1], $bytes[2])
    && $bytes[0] === 0xEF && $bytes[1] === 0xBB && $bytes[2] === 0xBF) {
     $result = true; // BOM
   } else {
     $i = 0;
     while ($i < $length && $bytes[$i++] > 0x80);
     for (; $i < $length; ++$i) {
       if ($bytes[$i] < 0x80) {
         continue;
       }
       if ($bytes[$i] >= 0xC0 && $bytes[$i] <= 0xDF) {
         if (!isset($bytes[++$i])
          || ($bytes[$i] < 0x80 || $bytes[$i] > 0xEF)) {
           $result = false;
           break;
         }
       } else if ($bytes[$i] >= 0xE0 && $bytes[$i] <= 0xEF) {
         if (!isset($bytes[++$i])
          || ($bytes[$i] < 0x80 || $bytes[$i] > 0xBF)
          || !isset($bytes[++$i])
          || ($bytes[$i] < 0x80 || $bytes[$i] > 0xBF)) {
           $result = false;
           break;
         }
       } else {
         $result = false;
         break;
       }
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * UTF-16 (LE or BE)
 *
 * RFC2781: UTF-16, an encoding of ISO 10646
 * Must be labelled BOM
 *
 * @link http://www.ietf.org/rfc/rfc2781.txt
 */
 function isUTF16($string){
   $result = false;
   $length = strlen($string);
   if ($length >= 2) {
     $byte1 = ord($string);
     $byte2 = ord(substr($string, 1, 1));
     if ($byte1 === 0xFF // BOM (little-endian)
      && $byte2 === 0xFE) {
       $result = true;
     } else if ($byte1 === 0xFE // BOM (big-endian)
            &&  $byte2 === 0xFF) {
       $result = true;
     } else {
       $null = chr(0x00);
       $pos = strpos($string, $null);
       if ($pos === false) {
         $result = false; // Non ASCII (omit)
       } else {
         $next = substr($string, $pos + 1, 1); // BE
         $prev = substr($string, $pos - 1, 1); // LE
         if ((strlen($next) > 0 && ord($next) > 0x00 && ord($next) < 0x80)
          || (strlen($prev) > 0 && ord($prev) > 0x00 && ord($prev) < 0x80)) {
           $pos = strlen($string) - 1;
           $next = substr($string, $pos + 1, 1); // BE
           $prev = substr($string, $pos - 1, 1); // LE
           if (strlen($next) > 0) {
             if (ord($next) > 0x00 && ord($next) < 0x80) {
               $result = true;
             } else {
               $result = false;
             }
           } else if (strlen($prev) > 0) {
             if (ord($prev) > 0x00 && ord($prev) < 0x80) {
               $result = true;
             } else {
               $result = false;
             }
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * UTF-16BE (big-endian)
 *
 * RFC 2781 4.3 Interpreting text labelled as UTF-16
 * Text labelled "UTF-16BE" can always be interpreted as being big-endian
 *  when BOM does not founds (SHOULD)
 *
 * @link http://www.ietf.org/rfc/rfc2781.txt
 */
 function isUTF16BE($string){
   $result = false;
   $length = strlen($string);
   if ($length >= 2) {
     $byte1 = ord($string);
     $byte2 = ord(substr($string, 1, 1));
     if ($byte1 === 0xFE // BOM
      && $byte2 === 0xFF) {
       $result = true;
     } else {
       $pos = strpos($string, chr(0x00));
       if ($pos === false) {
         $result = false; // Non ASCII..
       } else {
         $byte = substr($string, $pos + 1, 1);
         if (strlen($byte) > 0 && ord($byte) < 0x80) {
           $result = true;
         }
       }
     }
   }
   return $result;
 }

/**
 * UTF-16LE (little-endian)
 *
 * @see isUTF16BE
 */
 function isUTF16LE($string){
   $result = false;
   $length = strlen($string);
   if ($length >= 2) {
     $byte1 = ord($string);
     $byte2 = ord(substr($string, 1, 1));
     if ($byte1 === 0xFF // BOM
      && $byte2 === 0xFE) {
       $result = true;
     } else {
       $pos = strpos($string, chr(0x00));
       if ($pos === false) {
         $result = false; // There is no ASCII in string
       } else {
         $byte = substr($string, $pos - 1, 1);
         if (strlen($byte) > 0 && ord($byte) < 0x80) {
           $result = true;
         }
       }
     }
   }
   return $result;
 }

/**
 * UTF-32
 *
 * Unicode 3.2.0: Unicode Standard Annex #19
 *
 * @link http://www.iana.org/assignments/charset-reg/UTF-32
 * @link http://www.unicode.org/reports/tr19/tr19-9.html
 */
 function isUTF32($string){
   $result = false;
   $length = strlen($string);
   if ($length >= 4) {
     $byte1 = ord($string);
     $byte2 = ord(substr($string, 1, 1));
     $byte3 = ord(substr($string, 2, 1));
     $byte4 = ord(substr($string, 3, 1));
     if ($byte1 === 0x00 && $byte2 === 0x00 // BOM (big-endian)
      && $byte3 === 0xFE && $byte4 === 0xFF) {
       $result = true;
     } else if ($byte1 === 0xFF && $byte2 === 0xFE // BOM (little-endian)
            &&  $byte3 === 0x00 && $byte4 === 0x00) {
       $result = true;
     } else {
       $pos = strpos($string, chr(0x00));
       if ($pos === false) {
         $result = false; // Non ASCII (omit)
       } else {
         $next = substr($string, $pos, 4); // Must be (BE)
         if (strlen($next) === 4) {
           $bytes = array_values(unpack('C4', $next));
           if (isset($bytes[0], $bytes[1], $bytes[2], $bytes[3])) {
             if ($bytes[0] === 0x00 && $bytes[1] === 0x00) {
               if ($bytes[2] === 0x00) {
                 $result = $bytes[3] >= 0x09 && $bytes[3] <= 0x7F;
               } else if ($bytes[2] > 0x00 && $bytes[2] < 0xFF) {
                 $result = $bytes[3] > 0x00 && $bytes[3] <= 0xFF;
               }
             }
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Big5 (Traditional Chinese)
 */
 function isBIG5($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if ($bytes[$i] < 0x80) {
       continue;
     }
     if ($bytes[$i] >= 0x81 && $bytes[$i] <= 0xFE) {
       if (!isset($bytes[++$i])
        || (($bytes[$i] < 0x40 || $bytes[$i] > 0x7E)
        &&  ($bytes[$i] < 0xA1 || $bytes[$i] > 0xFE))) {
         $result = false;
         break;
       }
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * EUC-CN (GB2312) (Simplified Chinese)
 */
 function isGB($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if ($bytes[$i] < 0x80) {
       continue;
     }
     if ($bytes[$i] >= 0x81 && $bytes[$i] <= 0xFE) {
       if (!isset($bytes[++$i])
        || (($bytes[$i] < 0x40 || $bytes[$i] > 0x7E)
        &&  ($bytes[$i] < 0x80 || $bytes[$i] > 0xFE))) {
         $result = false;
         break;
       } else if ($bytes[$i] >= 0x30 && $bytes[$i] <= 0x39) {
         if (!isset($bytes[++$i])
          || ($bytes[$i] < 0x81 || $bytes[$i] > 0xFE)
          || !isset($bytes[++$i])
          || ($bytes[$i] < 0x30 || $bytes[$i] > 0x39)) {
           $result = false;
           break;
         }
       }
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }

/**
 * EUC-KR (KS X 1001) RFC1557
 */
 function isEUCKR($string){
   $result = true;
   $string = array_values((array)unpack('C*', $string));
   $bytes = & $string;
   $length = count($bytes);
   $i = 0;
   while ($i < $length && $bytes[$i++] > 0x80);
   for (; $i < $length; ++$i) {
     if ($bytes[$i] < 0x80) {
       continue;
     }
     if ($bytes[$i] >= 0xA1 && $bytes[$i] <= 0xFE) {
       if (!isset($bytes[++$i])
        || ($bytes[$i] < 0xA1 || $bytes[$i] > 0x7E)) {
         $result = false;
         break;
       }
     } else {
       $result = false;
       break;
     }
   }
   unset($bytes);
   return $result;
 }
}
