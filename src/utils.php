<?php
require_once dirname(__FILE__) . '/pager.php';

/**
 * @name Posql_Utils
 *
 * This class is a group of static methods for Posql
 *
 * @package   Posql
 * @author    Polygon Planet <polygon.planet.aqua@gmail.com>
 */
class Posql_Utils extends Posql_Config {

  /**
   * Escape a string to validate expression in a query.
   * Optionally, and escape characters to validate string as a LIKE pattern.
   *
   * @param  string   the subject string to escape
   * @param  boolean  whether or not to escape the wildcards
   * @param  mixed    whether or not enclose string by quotes
   * @return string   escaped string
   * @access public
   */
  function escape($string, $escape_wildcards = false, $enclose = false) {
    if (!is_scalar($string)) {
      $string = '';
    } else {
      $string = $this->escapeString($string, $enclose);
      if ($escape_wildcards) {
        $string = $this->escapePattern($string);
      }
    }
    return $string;
  }

  /**
   * Quotes the string so it can be safely used
   *  within string delimiters in a query.
   *
   * Note:
   *   Version 2.06 older:
   *     This function does not escapes more than once.
   *      (the backslash does not become two or more)
   *   Version 2.07 later:
   *     The backslashes will be increased when escaping more than once.
   *
   * @param  string  the string to be quoted
   * @param  mixed   the optionals to enclose marker of string (e.g. "'")
   *                 Or, TRUE will be use single quotation mark(')
   * @return string  the quoted string
   * @access public
   * @static
   */
  function escapeString($string, $enclose = false) {
    static $bs = '\\', $sq = '\'', $dq = '"';
    $quote = $sq;
    if ($enclose === $dq) {
      $quote = $dq;
    }
    if (!is_scalar($string)) {
      $string = '';
    } else if ($string == null) {
      $string = (string)$string;
    } else {
      if (strpos($string, $bs) !== false) {
        $split = explode($bs, $string);
        $string = implode($bs . $bs, $split);
      }
      $split = explode($quote, $string);
      $string = array();
      $end = count($split);
      while (--$end >= 0) {
        $shift = array_shift($split);
        $glue = '';
        if (substr($shift, -1) === $bs) {
          $rtrim = rtrim($shift, $bs);
          $diff = strlen($shift) - strlen($rtrim);
          if ($end) {
            if ($diff % 2 === 0) {
              $glue = $bs;
            }
          } else {
            if ($diff % 2 === 1) {
              $glue = $bs;
            }
          }
        } else {
          if ($end) {
            $glue = $bs;
          } else {
            $glue = '';
          }
        }
        $shift .= $glue;
        $string[] = $shift;
      }
      $string = implode($quote, $string);
    }

    if ($enclose) {
      $string = $quote . $string . $quote;
    }
    return $string;
  }

  /**
   * Escape characters that work as wildcard string in a LIKE pattern.
   * The wildcard characters "%" and "_" should be escaped.
   *
   * @param  string  the string pattern to escape
   * @return string  the escaped string pattern
   * @access public
   * @static
   */
  function escapePattern($pattern) {
    static $translates = array(
      '%' => '\%',
      '_' => '\_'
    );
    if (!is_scalar($pattern)) {
      $pattern = '';
    } else {
      $bs = '\\';
      $pattern = str_replace($bs, $bs . $bs, $pattern);
      $pattern = strtr($pattern, $translates);
    }
    return $pattern;
  }

  /**
   * The string in SQL query where
   *  it is escaped is returned to former character
   * When giving the relation character to unite,
   *  and it become it side by side by quotation mark (''),
   *  they are united.
   *
   * @see    concatStringTokens()
   * @param  string  string of target
   * @param  string  relation character row to unite
   * @return string  unescaped string
   * @access public
   * @static
   */
  function unescapeSQLString($string, $concat = null) {
    static $tr = array(
      '\0'   => "\x00",
      "\\'"  => "'",
      '\\"'  => '"',
      '\b'   => "\x08",
      '\n'   => "\x0A",
      '\r'   => "\x0D",
      '\t'   => "\t",
      '\Z'   => "\x1A", // (Ctrl-Z)
      '\\\\' => '\\',
      '\%'   => '%',
      '\_'   => '_'
    );
    if ($concat !== null && ord($string) === ord($concat)) {
      $string = $this->concatStringTokens($string, $concat);
    }
    $string = strtr($string, $tr);
    return $string;
  }

  /**
   * A handy function that will escape the contents of a variable,
   *   recursing into arrays for HTML/XML text
   *
   * @param  mixed   the variable of target
   * @param  string  optional, the character set for encode
   * @return mixed   the encoded valiable
   * @access public
   * @static
   */
  function escapeHTML($html, $charset = null) {
    $result = null;
    if (is_array($html)) {
      $result = array();
      foreach ($html as $key => $val) {
        if (isset($this)) {
          $key = $this->escapeHTML($key, $charset);
          $val = $this->escapeHTML($val, $charset);
        } else {
          $key = Posql_Utils::escapeHTML($key, $charset);
          $val = Posql_Utils::escapeHTML($val, $charset);
        }
        $result[$key] = $val;
      }
    } else if (is_string($html)) {
      if ($charset != null && is_string($charset)) {
        $result = htmlspecialchars($html, ENT_QUOTES, $charset);
      } else if (!empty($this->charset)) {
        $result = htmlspecialchars($html, ENT_QUOTES, $this->charset);
      } else {
        $result = htmlspecialchars($html, ENT_QUOTES);
      }
    } else {
      $result = $html;
    }
    return $result;
  }

  /**
   * Truncates the string by limited length
   * If the mbstring extension loaded, it will use on cutting to string
   *  or, it will be truncated by the UTF-8 option of preg_*
   * When the padding character is a dot(.),
   *  it is likely to become three dots(...)
   *
   * @param  mixed   the value of target
   * @param  number  the miximun length
   * @param  string  the padding character
   * @param  string  options as quotes enclosed
   * @param  boolean whether the string already escaped or not
   * @return mixed   truncated value
   * @access public
   */
  function truncatePad($value, $length, $marker = '.',
    $quotes = '', $escaped = false) {
    static $mb = null;
    if ($mb === null) {
      $mb = extension_loaded('mbstring')
        && function_exists('mb_strcut')
        && function_exists('mb_detect_encoding');
    }

    if ($quotes != null && ord($value) === ord($quotes)
      && substr($value, -1) === $quotes) {
      $value = substr($value, 1, -1);
    }
    $bytes = strlen($value);
    if ($marker === '.') {
      $marker = '...';
    }
    $length = (int)($length - strlen($marker));
    if ($length <= 0) {
      $value = '';
    } else {
      if ($bytes && $length > 0 && $bytes > $length) {
        if (is_numeric($value)) {
          $value = substr($value, 0, $length);
        } else if ($mb) {
          if (empty($this->charset)) {
            if (isset($this->pcharset)) {
              $charset = $this->pcharset->detectEncoding($value);
            } else {
              $charset = mb_detect_encoding($value, 'auto', true);
            }
          } else {
            $charset = $this->charset;
          }
          $value = mb_strcut($value, 0, $length, $charset);
        } else if ($length < 0x10000 && $length > 1
          && preg_match('<^.{1,' . $length . '}>s', $value, $match)) {
          $value = $match[0];
          unset($match);
        } else {
          $value = substr($value, 0, $length);
        }

        if ($marker != null) {
          $value .= $marker;
        }

        if ($escaped) {
          if (ord($value) !== ord($quotes)) {
            $value = $quotes . $value . $quotes;
          }
        } else {
          if (isset($this)) {
            if ($quotes == null) {
              $value = $this->escapeString($value);
            } else {
              $value = $this->escapeString($value, $quotes);
            }
          } else {
            while (substr($value, -1) === '\\') {
              $value = substr($value, 0, -1);
            }

            if ($quotes != null) {
              $value = $quotes . $value . $quotes;
            }
          }
        }
      }
    }
    return $value;
  }

  /**
   * A handy function the strip_tags() of inherited call
   *
   * @param  string  the HTML string of target
   * @return boolean the string that stripped tags
   * @access public
   * @static
   */
  function stripTags($html) {
    if (strpos($html, '<') !== false
      && strpos($html, '>') !== false) {
      $html = preg_replace('{</?\w+[^>]*>}isU', '', $html);
    }
    return $html;
  }

  /**
   * Encodes the given data with base16 as soon as hexadecimal numbers
   *
   * @example
   * <code>
   *  foreach(array("\n", "\r\n", "foo", "&&") as $string) {
   *    echo "<ul>\n";
   *    $hex_normal = Posql_Utils::base16Encode($string);
   *    printf("<li>normal: %s</li>\n", htmlspecialchars($hex_normal));
   *    $hex_regexp = Posql_Utils::base16Encode($string, '\x');
   *    printf("<li>regexp: %s</li>\n", htmlspecialchars($hex_regexp));
   *    $hex_entity = Posql_Utils::base16Encode($string, '&#x', ';');
   *    printf("<li>entity: %s</li>\n", htmlspecialchars($hex_entity));
   *    echo "</ul>\n";
   *  }
   * </code>
   *
   * @param  string  the data to encode
   * @param  string  optionally, the prefix string
   * @param  string  optionally, the suffix string
   * @return string  the hexadecimal numbers as string
   * @access public
   * @static
   */
  function base16Encode($string, $prefix = '', $suffix = '') {
    $result = '';
    if (is_string($string) && $string != null) {
      if ($prefix == null && $suffix == null) {
        $result = strtoupper(bin2hex($string));
        $string = null;
      } else {
        $string = unpack('C*', $string);
        $length = count($string);
        while (--$length >= 0) {
          $byte = array_shift($string);
          $result .= sprintf('%s%02X%s', $prefix, $byte, $suffix);
        }
      }
    }
    return $result;
  }

  /**
   * Checks whether the given token is a string
   * (it was enclosed with the quotation mark)
   *
   * @param  string  string of token
   * @return boolean whether the token is string or not
   * @access public
   * @static
   */
  function isStringToken($token) {
    static $sq = "'", $dq = '"';
    $result = false;
    if ($token != null && is_string($token)) {
      $pre = substr($token, 0, 1);
      if ($pre === $sq || $pre === $dq) {
        $result = substr($token, -1) === $pre;
      }
    }
    return $result;
  }

  /**
   * Convert the value of the given argument that will be the token as string
   * The token will be enclosed with the single quotation mark(')
   *
   * @param  string  the token of target
   * @return string  the converted string token
   * @access public
   */
  function toStringToken($token) {
    $result = null;
    if (!$this->isStringToken($token)) {
      $result = $this->escapeString($token, true);
    } else {
      if (substr($token, 0, 1) === '"') {
        $token = substr($token, 1, -1);
        $result = $this->escapeString($token, true);
      } else {
        $result = $token;
      }
    }
    return $result;
  }

  /**
   * Concatenate the operands pairs.
   *  (e.g. [ 'foo''bar' ] => [ 'foo\'bar' ])
   *
   * @param  string  the left string
   * @param  string  the right string
   * @return string  concatenated string
   * @access public
   */
  function concatStringTokens($left_string, $right_string) {
    $result = null;
    if ($left_string != null && $right_string != null
      && $this->isStringToken($left_string)
      && $this->isStringToken($right_string)) {
      $left_string  = $this->toStringToken($left_string);
      $right_string = $this->toStringToken($right_string);
      $glue = '\\\'';
      $result = substr($left_string, 0, -1) . $glue . substr($right_string, 1);
    }
    return $result;
  }

  /**
   * It further as for the trim() function
   *
   * @param  string  string of target
   * @param  string  optionally, the stripped characters
   * @return string  the trimmed string
   * @access public
   * @static
   */
  function trimExtra($string, $extras = '$') {
    $string = trim(trim($string), $extras);
    return $string;
  }

  /**
   * Checks whether the character string is a word
   *  equal to RegExp: [a-zA-Z_\x7F-\xFF]
   *
   * @param  string  a character string of target
   * @return boolean whether string was word or not
   * @access public
   * @static
   */
  function isWord($char) {
    $ord = ord($char);
    return $ord === 0x5F || $ord > 0x7E
      || ($ord  >  0x40 && $ord < 0x5B)
      || ($ord  >  0x60 && $ord < 0x7B);
  }

  /**
   * Checks whether the character string is alphanumeric
   *  equal to RegExp: [0-9a-zA-Z_\x7F-\xFF]
   *
   * @param  string  a character string of target
   * @return string  whether the string was alphanumeric or not
   * @access public
   */
  function isAlphaNum($char) {
    $ord = ord($char);
    return $ord === 0x5F || $ord > 0x7E
      || ($ord  >  0x2F && $ord < 0x3A)
      || ($ord  >  0x40 && $ord < 0x5B)
      || ($ord  >  0x60 && $ord < 0x7B);
  }

  /**
   * Check whether it is a format to which the name is effective
   * This format is the same as the rule of the variable identifier of PHP
   *
   * @param  string  string of target
   * @return boolean whether the name is effective or not
   * @access public
   */
  function isEnableName($name) {
    static $pattern = '{^[a-zA-Z_\x7F-\xFF][a-zA-Z0-9_\x7F-\xFF]*$}';
    $result = false;
    if (is_string($name)) {
      switch (strlen($name)) {
      case 0:
        break;
      case 1:
        $result = $this->isWord($name);
        break;
      case 2:
        if ($this->isWord(substr($name, 0, 1))
          && $this->isAlphaNum(substr($name, -1))) {
          $result = true;
        }
        break;
      default:
        if (preg_match($pattern, $name)) {
          $result = true;
        }
        break;
      }
    }
    return $result;
  }

  /**
   * Check whether the token is evaluatable or static value
   *
   * @param  mixed    the target token
   * @return boolean  whether the argument was evaluatable token
   * @access public
   */
  function isExprToken($token) {
    $result = false;
    if (is_array($token)) {
      foreach ($token as $item) {
        $lc_item = strtolower($item);
        if ($lc_item === 'false'  || $lc_item === 'true'
          || !$this->isWord($item) || !$this->isEnableName($item)) {
          $result = true;
          break;
        }
      }
    } else if (is_scalar($token)) {
      $lc_token = strtolower($token);
      if ($lc_token === 'false'  || $lc_token === 'true'
        || !$this->isWord($token) || !$this->isEnableName($token)) {
        $result = true;
      }
    }
    return $result;
  }

  /**
   * Check whether the argument is effective as Unix time
   *
   * @param  mixed    subject value
   * @return boolean  whether the argument was effective as Unix time
   * @access public
   */
  function isUnixTime($value) {
    $result = false;
    if (is_scalar($value) && is_numeric($value)) {
      if ($value >= 0 && $value <= 2147483647
        && preg_match('{^(?:0|[123456789]\d*)$}', $value)) {
        $result = true;
      }
    }
    return $result;
  }

  /**
   * This logic refers to the column affinity algorithm of SQLite.
   *
   * @link http://www.sqlite.org/datatype3.html#affinity
   *
   * @param  string  type of subject as string
   * @return string  type of string which passed affinity.
   *                 return as following types.
   *                 ------------------------------
   *                 - string : type of string
   *                 - number : type of number
   *                 - null   : null
   *                 ------------------------------
   * @access public
   */
  function getColumnAffinity($column) {
    static $maps = array(
      'string' => array(
        'char', 'text', 'lob', 'date', 'time', 'year'
      ),
      'number' => array(
        'int',  'dec',  'num', 'real', 'doub',
        'floa', 'seri', 'bit', 'bool'
      )
    );
    $result = 'null';
    if (is_string($column) && $column != null) {
      $column = strtolower($column);
      foreach ($maps as $type => $types) {
        foreach ($types as $part) {
          if (strpos($column, $part) !== false) {
            $result = $type;
            break 2;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Returns the microtime in seconds as a float.
   *
   * @param  void
   * @return float  microtime
   */
  function getMicrotime() {
    $result = array_sum(explode(' ', microtime()));
    return $result;
  }

  /**
   * Gets the value of the key from array or object
   * If it not exists the key, it return $default = null
   *
   * @param  mixed   target variable which is type of array or object
   * @param  mixed   the key which should be type of scalar
   * @param  mixed   default value to return if result is null
   * @param  boolean whether the result evaluated strictly or not
   * @return mixed   the value of the key on variable, or default
   * @access public
   * @static
   */
  function getValue($var, $key, $default = null, $strict = false) {
    $result = null;
    if (is_scalar($key)) {
      if (is_array($var) && isset($var[$key])) {
        $result = $var[$key];
      } else if (is_object($var) && isset($var->{$key})) {
        $result = $var->{$key};
      }
    }

    if ($strict) {
      if ($result === null) {
        $result = $default;
      }
    } else {
      if ($result == null) {
        $result = $default;
      }
    }
    return $result;
  }

  /**
   * Calculate the hash value that using by the crc32 checksum as 8 digits
   *
   * @param  mixed   the value of target
   * @param  boolean whether it returns as bare integer,or string of hexadecimal
   * @return mixed   the hashed value as number, or as string of hex
   * @access public
   * @static
   */
  function hashValue($value, $as_int = false) {
    $value = crc32(serialize($value));
    if (!$as_int) {
      $value = sprintf('%08x', $value);
    }
    return $value;
  }

  /**
   * Compare the defined value and given argument value
   *
   * This method is useful for PHP versions interchangeable.
   *
   * @example
   * <code>
   *  // Assume some error occurred:
   *  if (!Posql_Utils::compareDefinedValue("PDO::ERR_NONE", $db_error)) {
   *    print "Error!";
   *  }
   * </code>
   *
   * @param  string   the constant string to compared
   * @param  mixed    the value to be compared
   * @param  boolean  whether compare as strict
   * @return boolean  whether the values were same or not
   * @access public
   * @static
   */
  function compareDefinedValue($defined, $value, $strict = false) {
    $result = false;
    if (!is_string($defined) && is_string($value)) {
      $tmp = $defined;
      $defined = $value;
      $value = $tmp;
    }

    if (is_string($defined) && defined($defined)) {
      if ($strict) {
        $result = $value === constant($defined);
      } else {
        $result = $value == constant($defined);
      }
    }
    return $result;
  }

  /**
   * Sorts the array according to the template of the given arguments
   *
   * @example
   * <code>
   *   $array = array(
   *     'id'   => 1,
   *     'name' => 'foo',
   *     'text' => 'hello!'
   *   );
   *   print "<br/><b>original:</b><br/><pre>\n";
   *   print_r($array);
   *   $template = array('name', 'id', 'text');
   *   Posql_Utils::sortByTemplate($array, $template);
   *   print "\n<b>after:</b><br/>\n";
   *   print_r($array);
   *   print "</pre>";
   *   // output:
   *   //  array(
   *   //     [name] => foo
   *   //     [id]   => 1
   *   //     [text] => hello!
   *   //  );
   * </code>
   *
   * @example
   * <code>
   *   $array = array();
   *   for ($i = 0; $i < 5; $i++) {
   *     $array[] = array(
   *       'id'   => $i,
   *       'name' => 'foo@' . $i,
   *       'text' => 'hello!' . $i
   *     );
   *   }
   *   echo "<b>Original:</b><br/><pre>\n";
   *   var_dump($array);
   *   echo "<b>After:</b><br/>\n";
   *   Posql_Utils::sortByTemplate($array, 'text', 'name', 'id');
   *   var_dump($array);
   *   echo "</pre>";
   * </code>
   *
   * @param  array   the target array as reference variable
   * @param  mixed   template which becomes row of key to sort it
   * @return boolean success or failure
   * @access public
   * @static
   */
  function sortByTemplate(&$array, $templates = array()) {
    $result = false;
    if (is_array($array)) {
      $dim = is_array(reset($array));
      if (!$dim) {
        $array = array($array);
      }

      if (func_num_args() > 2) {
        $args = func_get_args();
        $tpls = array_slice($args, 1);
      } else {
        $tpls = (array) $templates;
        $flip = array_keys($tpls);
        $check = array_filter($flip, 'is_string');
        if ($check != null) {
          $tpls = $flip;
        }
      }
      $array = array_filter($array, 'is_array');
      $elems = array();
      $count = count($array);
      $i = 0;
      do {
        $shift = array_shift($array);
        $elems[$i] = array();
        foreach ($tpls as $key) {
          if (array_key_exists($key, $shift)) {
            $elems[$i][$key] = $shift[$key];
          }
        }
      } while ($count > ++$i);
      unset($shift);
      if ($dim) {
        $array = array_splice($elems, 0);
      } else {
        $array = reset($elems);
      }
      unset($elems);
      $result = true;
    }
    return $result;
  }

  /**
   * A shortcut of preg_split() as PREG_SPLIT_NO_EMPTY
   *
   * @param  string  pattern of the RegExp
   * @param  string  input values
   * @param  number  divided the maximum value
   * @param  number  flags of split mode
   * @return array   divided array
   * @access public
   * @static
   */
  function split($pattern, $subject, $limit = -1, $flags = 0) {
    $result = null;
    $flags |= PREG_SPLIT_NO_EMPTY;
    if ($limit === -1) {
      $result = preg_split($pattern, $subject, $limit, $flags);
    } else {
      $result = @preg_split($pattern, $subject, $limit, $flags);
      if (!is_array($result)) {
        $result = array();
      }
    }
    return $result;
  }

  /**
   * Splits to string as format to which the name is effective
   *
   * @param  string  input values
   * @param  number  divided the maximum value
   * @param  number  flags of split mode
   * @return array   divided array
   * @access public
   * @static
   */
  function splitEnableName($subject, $limit = -1, $flags = 0) {
    static $pattern = '{[^a-zA-Z0-9_\x7F-\xFF]+}';
    $result = array();
    if (isset($this)) {
      $result = $this->split($pattern, $subject, $limit, $flags);
    } else {
      $result = Posql_Utils::split($pattern, $subject, $limit, $flags);
    }
    return $result;
  }

  /**
   * Examine whether it is an associate array
   *
   * @example
   * <code>
   * $array = array(1, 2, 'foo');
   * var_dump(Posql_Utils::isAssoc($array));
   * // result: false
   * </code>
   *
   * @example
   * <code>
   * $array = array('key1' => 'value1', 'key2' => 'value2');
   * var_dump(Posql_Utils::isAssoc($array));
   * // result: true
   * </code>
   *
   * @example
   * <code>
   * $array = array(1, 2, 'key' => 'foo');
   * var_dump(Posql_Utils::isAssoc($array));
   * // result: true
   * </code>
   *
   * @param  array   the target array
   * @return boolean the result whether the array was associated
   * @access public
   * @static
   */
  function isAssoc($array) {
    $result = false;
    if (is_array($array)) {
      $keys = array_keys($array);
      $array = array_filter($keys, 'is_string');
      $result = $array != null;
    }
    return $result;
  }

  /**
   * Cleans the array which removed empty value
   *
   * @example
   * <code>
   * $array = array('', 'foo', null, 'bar');
   * var_dump(Posql_Utils::cleanArray($array));
   * // result: array('foo', 'bar');
   * </code>
   *
   * @param  array   the target array
   * @param  boolean whether key to array is preserve or reset it
   * @return array   an array which removed empty value
   * @access public
   * @static
   */
  function cleanArray($array, $preserve_keys = false) {
    $result = array();
    if (is_array($array)) {
      $result = array_filter(array_map('trim', $array), 'strlen');
      if (!$preserve_keys) {
        $result = array_values($result);
      }
    }
    return $result;
  }

  /**
   * Exchanges all keys valid from values in an array
   *
   * @example
   * <code>
   *  $array = array('foo', null, '', array(2), 'bar');
   *  var_dump(Posql_Utils::flipArray($array));
   *  // result: array('foo' => 0, 'bar' => 1);
   * </code>
   *
   * @param  array   an array of target
   * @return array   the flipped array
   * @access public
   * @static
   */
  function flipArray($array) {
    $result = array();
    if (is_array($array)) {
      $array = array_filter(array_filter($array, 'is_string'), 'strlen');
      $result = array_flip(array_splice($array, 0));
    }
    return $result;
  }

  /**
   * Bundles the multi-dimensional array to an array which has one dimension
   *
   * @example
   * <code>
   * $array = array(1, 2, array(3, 4, 5), 6);
   * var_dump(Posql_Utils::toOneArray($array));
   * // result: array(1, 2, 3, 4, 5, 6);
   * </code>
   *
   * @example
   * <code>
   * $array = array(1, 2, array(3, 4, array(4.1, 4.2), 5), 6);
   * var_dump(Posql_Utils::toOneArray($array));
   * // result: array(1, 2, 3, 4, 4.1, 4.2, 5, 6);
   * </code>
   *
   * @param  array  the target array
   * @return array  an array which has only one dimension
   * @access public
   * @static
   */
  function toOneArray($value) {
    $result = array();
    if (is_array($value)) {
      $value = array_values($value);
      foreach ($value as $index => $val) {
        if (is_array($val)) {
          while (($valid = array_filter($val, 'is_array')) != null) {
            foreach ($valid as $i => $v) {
              array_splice($val, $i, 1, $v);
            }
          }
          foreach ($val as $v) {
            $result[] = $v;
          }
        } else {
          $result[] = $val;
        }
      }
    }
    return $result;
  }

  /**
   * Checks whether the class exists without triggering __autoload().
   *
   * @param   string   the class name of target
   * @return  boolean  TRUE success and FALSE on error
   * @access  public
   */
  function existsClass($class) {
    $result = false;
    if ($class != null && is_string($class)) {
      if ($this->isPHP5) {
        $result = class_exists($class, false);
      } else {
        $result = class_exists($class);
      }
    }
    return $result;
  }

  /**
   * Translates a string with underscores into camel case
   *
   * @example
   * <code>
   * echo Posql_Utils::camelize('font-color');   // output: 'fontColor'
   * echo Posql_Utils::camelize('-moz-opacity'); // output: 'MozOpacity'
   * </code>
   *
   * @param  string  subject string
   * @param  string  optionally, a separator character (default = '-')
   * @return string  string which translated into camel caps
   * @access public
   * @static
   */
  function camelize($string, $separator = '-') {
    $org_head = substr($string, 0, 1);
    $string = str_replace(' ', '', ucwords(strtr($string, $separator, ' ')));
    if ($org_head !== $separator) {
      $head = substr($string, 0, 1);
      if ($org_head !== $head) {
        $string = $org_head . substr($string, 1);
      }
    }
    return $string;
  }

  /**
   * Translates a camel case string into a string with underscores
   *
   * @example
   * <code>
   * echo Posql_Utils::underscore('underScore');    // output: 'under_score'
   * echo Posql_Utils::underscore('PrivateMethod'); // output: '_private_method'
   * </code>
   *
   * @param  string  subject string
   * @return string  string which translated into underscore format
   * @access public
   */
  function underscore($string) {
    static $maps = null;
    if ($maps === null) {
      $maps = array();
      for ($c = 0x41; $c <= 0x5A; ++$c) {
        $maps[chr($c)] = '_' . strtolower(chr($c));
      }
    }
    $string = strtolower(strtr($string, $maps));
    return $string;
  }

  /**
   * It is examined whether Mime-Type is text/plain.
   * It is searched from the Content-Type and the header information
   *
   * @param  void
   * @return boolean whether Mime-Type is text/plain, or not
   * @access public
   * @static
   */
  function isContentPlainText() {
    $result = false;
    if (strncasecmp(php_sapi_name(), 'cli', 3) === 0) {
      $result = true;
    } else {
      $headers = array();
      if (function_exists('headers_list')) {
        $headers = @headers_list();
      } else if (function_exists('apache_response_headers')) {
        $headers = @apache_response_headers();
      }

      if (!empty($headers)) {
        $content_type = 'content-type';
        $text_plain   = 'text/plain';
        foreach ((array)$headers as $key => $val) {
          if (is_string($key)) {
            $val = $key . $val;
          }
          $val = strtolower($val);
          if (strpos($val, $content_type) !== false
            && strpos($val, $text_plain)   !== false) {
            $result = true;
            break;
          }
        }
      }
    }
    return $result;
  }
}
