<?php
require_once dirname(__FILE__) . '/ctype.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_Math
 *
 * The math operator class that to using for big integer
 * If available it will use the BCMath library
 * Will default to the standard PHP integer representation otherwise
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet.aqua@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Math {

/**
 * @var    boolean   maintains BCMath library can be use
 * @access private
 */
 var $hasbc;

/**
 * @var    boolean   temporary variable whether the function called at inside
 * @access private
 */
 var $_innerCall;

/**
 * Class constructor
 *
 * @param  void
 * @return Posql_Math
 * @access public
 */
 function __construct() {
   $this->hasbc = extension_loaded('bcmath');
 }

/**
 * Format the value for plain numbers
 *
 * @param  number  the number of target
 * @param  number  optionally, the number of digits for floating-point numbers
 * @param  string  optionally, to use sprintf()'s format
 * @return number  formatted numeric value
 * @access public
 */
 function format($number, $float = null, $sprintf = null){
   static $dot = '.', $zero = '0', $sups = array('0', '0');
   $result = $zero;
   $length = strlen($number);
   if ($length > 0) {
     $number = trim($number);
     $sign = substr($number, 0, 1);
     if ($sign === '-' || $sign === '+') {
       $number = substr($number, 1);
       --$length;
       if ($sign === '+') {
         $sign = '';
       }
     } else {
       $sign = '';
     }
     if ($this->isDec($number)) {
       $number = (string)$number;
     } else if ($this->isHex($number)) {
       $this->_innerCall = true;
       $number = $this->toHex($number);
       $number = $this->convertToBase($number, 16, 10);
       $this->_innerCall = null;
     } else if (is_numeric($number)) {
       $number = (string)($number - 0);
     } else {
       $number = sprintf('%.0f', $number);
     }
     if ($float !== null && $float > 0) {
       $float = (int)$float;
       $format = '%0' . $float . $dot . $float . 's';
       $numbers = explode($dot, $number) + $sups;
       if (count($numbers) > 2) {
         $numbers = array_slice($numbers, 0, 2);
       }
       if (!isset($numbers[1])) {
         $numbers[1] = $zero;
       }
       $numbers[1] = sprintf($format, $numbers[1]);
       $number = implode($dot, $numbers);
     }
     $result = $sign . ltrim($number);
     if ($float !== null
      && (($float - 0) === 0) && strpos($result, $dot) !== false) {
       $result = rtrim($result, $zero);
     }
     if (substr($result, -1) === $dot) {
       $result = substr($result, 0, -1);
     }
   }
   if ($sprintf != null) {
     $result = sprintf($sprintf, $result);
   }
   return $result;
 }

/**
 * A handy function which was customized
 *  by base_convert() function for big scale integers.
 * The maximum base number is 62.
 * The base number '0' will be not converted.
 * It is same as base_convert() function usage.
 *
 * @example
 * <code>
 * $func = "convertToBase";
 * $value = "FFFFFFFF"; $from = 16; $to = 10;
 * printf("<p>%s(<em>%s</em>, %d, %d);<br> = %s</p>",
 *        $func, $value, $from, $to,
 *        Posql_Math::convertToBase($value, $from, $to));
 * $value = "9223372036854775807"; $from = 10; $to = 16;
 * printf("<p>%s(<em>%s</em>, %d, %d);<br> = %s</p>",
 *        $func, $value, $from, $to,
 *        Posql_Math::convertToBase($value, $from, $to));
 * $value = "1101010001001101101001110111110110011001101101100111001101";
 * $from = 2; $to = 62;
 * printf("<p>%s(<em>%s</em>, %d, %d);<br> = %s</p>",
 *        $func, $value, $from, $to,
 *        Posql_Math::convertToBase($value, $from, $to));
 * </code>
 *
 * @param  number  the numeric or alphameric value of target
 * @param  number  the base number is in
 * @param  number  the base to convert number to
 * @return string  the numbers of result which was converted to base to base
 * @access public
 * @static
 */
 function convertToBase($number, $frombase, $tobase){
   static $bases =
   '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
   $result = '';
   if (isset($this) && empty($this->_innerCall)) {
     $number = $this->format($number, 0);
   }
   $length = strlen($number);
   $frombase = (int)$frombase;
   $tobase = (int)$tobase;
   if ($frombase > 0 && $frombase < 63
    && $tobase   > 0 && $tobase   < 63) {
     $numbers = array();
     for ($i = 0; $i < $length; $i++) {
       $pos = strpos($bases, substr($number, $i, 1));
       if ($pos === false) {
         $result = false;
         break;
       }
       $numbers[$i] = $pos;
     }
     if ($result !== false) {
       do {
         $divide = 0;
         $index  = 0;
         for ($i = 0; $i < $length; ++$i) {
           $divide = $divide * $frombase + $numbers[$i];
           if ($divide >= $tobase) {
             $numbers[$index++] = (int)($divide / $tobase);
             $divide = $divide % $tobase;
           } else if ($index > 0) {
             $numbers[$index++] = 0;
           }
         }
         $length = $index;
         $result = substr($bases, $divide, 1) . $result;
       } while ($index !== 0);
     }
   }
   return $result;
 }

/**
 * Hexadecimal to decimal
 * A handle hex to decimal converter.
 * If bcmath library available, it will be use.
 * Otherwise, it will be to use the precision of PHP floats.
 *
 * @param  number  the number of target
 * @param  boolean whether it is checked strictly as hexadecimal number
 * @return string  the number of result as decimal numbers, or '0'
 * @access public
 */
 function hexdec($number, $strict = false){
   $result = '0';
   $length = strlen($number);
   if ($length > 1
    && (!$strict || $this->isHex($number))) {
     if ($length >= 8) {
       $dec = 0;
       $bit = 1;
       if ($this->hasbc) {
         for ($pos = 1; $pos <= $length; ++$pos) {
           $sub = substr($number, (-$pos), 1);
           $dec = bcadd($dec, bcmul(hexdec($sub), $bit));
           $bit = bcmul($bit, 16);
         }
         $result = sprintf('%s', $dec);
       } else {
         for ($pos = 1; $pos <= $length; ++$pos) {
           $sub = substr($number, (-$pos), 1);
           $dec += hexdec($sub) * $bit;
           $bit *= 16;
         }
         $result = sprintf('%.0f', $dec);
       }
     } else {
       $result = sprintf('%.0f', hexdec($number));
     }
     if (ord($result) === 0x20) {
       $result = ltrim($result);
     }
   }
   return $result;
 }

/**
 * Check whether the numbers of argument is consist of decimal number
 *
 * @param  number  the number of target
 * @return boolean whether the numbers had consisted of decimal number
 * @access public
 */
 function isDec($number){
   static $mask = '0123456789.';
   $result = false;
   $length = strlen($number);
   if ($length > 0) {
     $ord = ord($number);
     if ($ord === 0x2B    // +  plus  sign
      || $ord === 0x2D) { // -  minus sign
       $number = substr($number, 1);
       --$length;
     }
     if (strspn($number, $mask) === $length) {
       $result = true;
     }
   }
   return $result;
 }

/**
 * Check whether the numbers of argument is consist of hexadecimal number
 *
 * @param  number  the number of target
 * @return boolean whether the numbers had consisted of hexadecimal number
 * @access public
 */
 function isHex($number){
   static $mask = 'abcdef0123456789';
   $result = false;
   $length = strlen($number);
   if ($length > 0) {
     $number = strtolower($number);
     $pos = strpos($number, 'x');
     if ($pos !== false && $pos >= 0 && $pos <= 2) {
       $number = substr($number, $pos + 1);
       $length = strlen($number);
     }
     if (strspn($number, $mask) === $length) {
       $result = true;
     }
   }
   return $result;
 }

/**
 * Return it if the given numerical value is a hexadecimal number
 *
 * @param  number   the number of target
 * @param  boolean  whether the result of all lowercase or all uppercase
 * @return string   the hexadecimal numbers, or '0'
 * @access public
 */
 function toHex($number, $tolower = false){
   $result = '0';
   if ($this->isHex($number)) {
     $number = strtoupper($number);
     $pos = strpos($number, 'X');
     if ($pos !== false) {
       $number = substr($number, $pos + 1);
     }
     if ($tolower) {
       $result = strtolower($number);
     } else {
       $result = $number;
     }
   }
   return $result;
 }

/**
 * Returns the number of digits for floating-point numbers.
 * If not found, NULL will be returned.
 *
 * @param  number (...)  the one or some arguments of numbers
 * @return number        the number of floating-point, or NULL
 * @access public
 */
 function getScale($op){
   static $dot = '.';
   $result = null;
   $argn = func_num_args();
   if ($argn > 1) {
     $args = func_get_args();
     $max_scale = 0;
     foreach ($args as $op) {
       $scale = strstr($op, $dot);
       if ($scale && strlen($scale) > $max_scale) {
         $max_scale = strlen($scale) - 1;
       }
     }
     if ($max_scale > 0) {
       $result = $max_scale;
     }
   } else {
     $scale = strstr($op, $dot);
     if (strlen($scale) > 1) {
       $result = strlen($scale) - 1;
     }
   }
   return $result;
 }

/**
 * Add two numbers: $op1 + $op2
 *
 * @access public
 */
 function add($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcadd($op1, $op2, $scale);
   } else {
     $result = ($op1 - 0) + ($op2 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Substract two numbers: $op1 - $op2
 *
 * @access public
 */
 function sub($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcsub($op1, $op2, $scale);
   } else {
     $result = ($op1 - 0) - ($op2 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Multiply two numbers: $op1 * $op2
 *
 * @access public
 */
 function mul($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcmul($op1, $op2, $scale);
   } else {
     $result = ($op1 - 0) * ($op2 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Divide two numbers: $op1 / $op2
 *
 * @access public
 */
 function div($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     if ($op2 != 0) {
       $result = bcdiv($op1, $op2, $scale);
     }
   } else {
     if ($op2 != 0) {
       $result = ($op1 - 0) / ($op2 - 0);
     }
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Calculate the modulus of $op1 and $op2: $op1 % $op2
 *
 * @access public
 */
 function mod($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcmod($op1, $op2);
   } else {
     $result = ($op1 - 0) % ($op2 - 0);
   }
   $result = $this->format($result);
   return $result;
 }

/**
 * Raise $op1 to the $op2 exponent: $op1 ^ $op2
 *
 * @access public
 */
 function pow($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bcpow($op1, $op2, $scale);
   } else {
     $result = pow($op1 - 0, $op2 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Compare two numbers
 *
 * @return int
 * -------------------
 *  $op1 >  $op2 :  1
 *  $op1 == $op2 :  0
 *  $op1 <  $op2 : -1
 * -------------------
 * @access public
 */
 function comp($op1, $op2, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1, $op2);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $op2 = $this->format($op2, $scale);
     $result = bccomp($op1, $op2, $scale);
   } else {
     $op1 = $op1 - 0;
     $op2 = $op2 - 0;
     if ($op1 > $op2) {
       $result = 1;
     } else if ($op1 < $op2) {
       $result = -1;
     } else {
       $result = 0;
     }
   }
   $result = (int)$result;
   return $result;
 }

/**
 * Returns the sign of number
 *
 * @return int
 * ----------------
 *  $op1 >  0 :  1
 *  $op1 == 0 :  0
 *  $op1 <  0 : -1
 * ----------------
 * @access public
 */
 function sign($op1){
   $result = null;
   $op1 = $this->format($op1);
   if ($op1 > 0) {
     $result = 1;
   } else if ($op1 < 0) {
     $result = -1;
   } else {
     $result = 0;
   }
   $result = (int)$result;
   return $result;
 }

/**
 * Returns the square root of number
 *
 * @access public
 */
 function sqrt($op1, $scale = null) {
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $result = bcsqrt($op1, $scale);
   } else {
     $result = sqrt($op1 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Returns the absolute value of number
 *
 * @access public
 */
 function abs($op1, $scale = null){
   $result = null;
   if ($scale === null) {
     $scale = $this->getScale($op1);
   }
   if ($this->hasbc) {
     $op1 = $this->format($op1, $scale);
     $result = ltrim($op1, '+-');
   } else {
     $result = abs($op1 - 0);
   }
   $result = $this->format($result, $scale);
   return $result;
 }

/**
 * Rounds a float
 *
 * @access public
 */
 function round($op1, $precision = 0){
   $result = null;
   $precision = (int)$precision;
   if ($this->hasbc) {
     $scale = $this->getScale($op1);
     $op1 = $this->format($op1, $scale);
     $pad = '0';
     $abs = $this->abs($precision);
     if ($abs > 0) {
       $pad = str_repeat($pad, $abs);
     }
     if ($precision < 0) {
       $result = round($op1, $precision);
     } else {
       if ($precision > 0) {
         $round = sprintf('0.%s5', $pad);
       } else {
         $round = '0.5';
       }
       $number = bcadd($op1, $round, bcadd($precision, 1));
       $result = bcdiv($number, '1.0', $precision);
     }
   } else {
     $result = round($op1 - 0, $precision);
     $precision = $this->getScale($result);
   }
   $result = $this->format($result, $precision);
   return $result;
 }

/**
 * Generates random numbers
 *
 * @access public
 */
 function rand($min = null, $max = null, $scale = null){
   $result = 0;
   if ($scale === null) {
     $scale = $this->getScale($min, $max);
   }
   if ($min > $max) {
     $tmp = $max;
     $max = $min;
     $min = $tmp;
     unset($tmp);
   }
   if ($min == $max) {
     $result = $min;
   } else {
     $randmax = mt_getrandmax();
     if ($this->hasbc) {
       if (!$max) {
         $max = $randmax;
         $min = 0;
       }
       $result = bcadd(
                   bcmul(
                     bcdiv(
                       mt_rand(0, $randmax),
                       $randmax,
                       strlen($max)
                     ),
                     bcsub(
                       bcadd(
                         $max,
                         1
                       ),
                       $min
                     )
                   ),
                   $min
       );
       if ($scale > 0) {
         $result .= '.' . $this->rand(0, str_repeat('9', $scale));
       }
     } else {
       if (!$max) {
         $max = $randmax;
         $min = 0;
       }
       $result = mt_rand($min - 0, $max - 0);
     }
   }
   $result = $this->format($result, $scale);
   return $result;
 }
}

