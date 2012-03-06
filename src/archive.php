<?php
require_once dirname(__FILE__) . '/ecma.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_Archive
 *
 * This class implements compression and the archive methods
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Archive {

/**
 * @var    Posql     maintains reference of the Posql class instance
 * @access private
 */
 var $posql = null;

/**
 * @var    boolean   whether initialized or not
 * @access private
 */
 var $_inited = false;

/**
 * Class constructor
 *
 * @param  void
 * @return Posql_Archive
 * @access public
 */
 function Posql_Archive(){
   $this->_init();
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
 * Reference to the Posql, and its properties
 *
 * @param  Posql   give of the Posql self
 * @return void
 * @access private
 */
 function _referProperty(&$posql){
   $this->posql = & $posql;
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
     $this->_inited = true;
   }
 }

/**
 * Get the constant character string as alphameric base 63
 *
 * Alphameric base 63 equels RegExp: [0-9A-Za-z_]
 *
 * @param  void
 * @return string  constant character string as alphameric base 63
 * @access public
 */
 function getAlphamericBase63(){
   static $base63 = 
   '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_';
   return $base63;
 }

/**
 * Encode the character string into the composition only of
 * the alphanumeric 63 based characters, and it compressed by
 * the Lz77 algorithm.
 *
 * This function transplanted for PHP which is implemented on JavaScript.
 *
 * @see http://nurucom-archives.hp.infoseek.co.jp/digital/Alphameric-HTML.html
 *
 * @param  string  subject string to compress
 * @return string  encoded and compressed string
 * @access public
 */
 function encodeAlphamericString($s){
   static $b = null;
   $js = & $this->posql->ecma;
   if ($b === null) {
     $b = $js->split($this->getAlphamericBase63(), '');
   }
   $a = '';
   $c = null;
   $j = null;
   $o = null;
   $k = null;
   $h = null;
   $p = null;
   $x = null;
   $i = 1014;
   $l = -1;
   $t = ' ';
   for (; $i < 1024; $i++) {
     $t .= $t;
   }
   $t .= $s;
   while (true) {
     $p = $js->substr($t, $i, 64);
     if ($p == null) {
       break;
     }
     $n = $js->length($p);
     for ($j = 2; $j <= $n; $j++) {
       $k = $js->lastIndexOf(
         $js->substring($t, $i - 819, $i + $j - 1),
         $js->substring($p, 0, $j)
       );
       if (-1 === $k) {
         break;
       }
       $o = $k;
     }
     if (2 === $j || 3 === $j && $h === $l) {
       $h = $l;
       $c = $js->charCodeAt($t, $i);
       $i++;
       if ($c < 128) {
         $x = $c;
         $l = ($x - ($c %= 32)) / 32 + 64;
         if ($h !== $l) {
           $a .= $b[$l - 32];
         }
         $a .= $b[$c];
       } else if (12288 <= $c && $c < 12544) {
         $l = (($c -= 12288) - ($c %= 32)) / 32 + 68;
         if ($h !== $l) {
           $a .= $b[$l - 32];
         }
         $a .= $b[$c];
       } else if (65280 <= $c && $c < 65440) {
         $l = (($c -= 65280) - ($c %= 32)) / 32 + 76;
         if ($h !== $l) {
           $a .= $b[$l - 32];
         }
         $a .= $b[$c];
       } else {
         $x = $c;
         $l = ($x - ($c %= 1984)) / 1984;
         if ($h !== $l) {
           $a .= 'n' . $b[$l];
         }
         $x = $c;
         $a .= $b[($x - ($c %= 62)) / 62] . $b[$c];
       }
     } else {
       $x = $o;
       $a .= $b[($x - ($o %= 63)) / 63 + 50] . $b[$o] . $b[$j - 3];
       $i += $j - 1;
     }
   }
   unset($js);
   return $a;
 }

/**
 * Decode the character string converted by
 * the encodeAlphamericString() method.
 *
 * This function transplanted for PHP which is implemented on JavaScript.
 *
 * @see http://nurucom-archives.hp.infoseek.co.jp/digital/Alphameric-HTML.html
 *
 * @param  string  subject string to decode
 * @return string  decoded and uncompressed string
 * @access public
 */
 function decodeAlphamericString($a){
   static $o = null;
   $js = & $this->posql->ecma;
   if ($o === null) {
     $o = array();
     $b = $this->getAlphamericBase63();
     for ($i = 0; $i < 63; $i++) {
       $o[$js->charAt($b, $i)] = $i;
     }
   }
   $c = null;
   $j = null;
   $k = null;
   $l = null;
   $m = null;
   $p = null;
   $w = null;
   $t = null;
   $s = '    ';
   $i = 63;
   while ($i -= 7) {
     $s .= $s;
   }
   while (true) {
     $t = $js->charAt($a, $i);
     $i++;
     if (!isset($o[$t])) {
       break;
     }
     $c = $o[$t];
     if ($c >= 63) {
       break;
     }
     if ($c < 32) {
       $s .= $js->fromCharCode(
         $m ?  $l * 32 + $c 
            : ($l * 32 + $c) * 62 + $o[$js->charAt($a, $i++)]
       );
     } else if ($c < 49) {
       $l = ($c < 36 ? $c - 32  : 
            ($c < 44 ? $c + 348 : $c + 1996));
       $m = 1;
     } else if ($c < 50) {
       $l = $o[$js->charAt($a, $i++)];
       $m = 0;
     } else {
       $w = $js->slice($s, -819);
       $k = ($c - 50) * 63 + $o[$js->charAt($a, $i++)];
       $j = $k + $o[$js->charAt($a, $i++)] + 2;
       $p = $js->substring($w, $k, $j);
       if ($p != null) {
         while ($js->length($w) < $j) {
           $w .= $p;
         }
       }
       $s .= $js->substring($w, $k, $j);
     }
   }
   $s = $js->slice($s, 1024);
   unset($js);
   return $s;
 }

}

