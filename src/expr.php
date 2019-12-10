<?php
require_once dirname(__FILE__) . '/builder.php';

/**
 * @name Posql_Expr
 *
 * This class processes the expression on WHERE, HAVING, ON, etc. mode
 *
 * @package   Posql
 * @author    Polygon Planet <polygon.planet.aqua@gmail.com>
 */
class Posql_Expr extends Posql_Builder {

  /**
   * Checks whether the expression has already validated or it has not yet
   *
   * @param  mixed   the target expression
   * @return boolean whether it already validated or it has not yet
   * @access private
   */
  function isValidExpr($expr) {
    $result = false;
    $marks = $this->getValidMarks();
    if (is_array($expr)) {
      $result = reset($expr) === reset($marks)
        && next($expr) === next($marks);
    } else {
      $mark = implode('', $marks);
      $result = substr($expr, 0, strlen($mark)) === $mark;
    }
    return $result;
  }

  /**
   * Returns the mark to compare that validated the expression
   *
   * @param  void
   * @return array    the Mark that use to valid
   * @access private
   */
  function getValidMarks() {
    static $marks = array();
    if (!$marks) {
      $marks = array(
        $this->getParsedMark(),
        $this->NL
      );
    }
    return $marks;
  }

  /**
   * Adds the mark to compare for valid to the expression
   *
   * @param  mixed    the expression
   * @return boolean  success or failure
   * @access private
   */
  function addValidMarks(&$expr) {
    $result = false;
    $marks = $this->getValidMarks();
    if ($this->isParsedExpr($expr) && !$this->isValidExpr($expr)) {
      if ($this->removeParsedMark($expr)) {
        if (is_array($expr)) {
          array_splice($expr, 0, 0, $marks);
        } else {
          $expr = implode('', $marks) . $expr;
        }
        $result = true;
      }
    }
    return $result;
  }

  /**
   * Removes the mark to compare for valid the expression
   *
   * @param  mixed    the expression
   * @return boolean  success or failure
   * @access private
   */
  function removeValidMarks(&$expr) {
    $result = false;
    $marks = $this->getValidMarks();
    if ($this->isParsedExpr($expr) && $this->isValidExpr($expr)) {
      if ($this->removeParsedMark($expr)) {
        array_shift($marks);
        if (is_array($expr)) {
          if (reset($expr) === reset($marks)) {
            array_shift($expr);
            array_shift($marks);
            $result = empty($marks);
          }
        } else {
          $mark = implode('', $marks);
          $len = strlen($mark);
          if ($len && substr($expr, 0, $len) === $mark) {
            $expr = substr($expr, $len);
            $result = true;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Validate the internal correlation prefix for unique name
   *
   * @param  array   associated array which includes dot yet
   * @return void
   * @access private
   */
  function validCorrelationPrefix($target) {
    $this->initCorrelationPrefix();
    $prefix = & $this->_correlationPrefix;
    $seed = 0;
    foreach ($target as $key => $val) {
      if (!is_string($key) && is_string($val)) {
        $key = $val;
      }

      if (is_string($key)) {
        if (strpos($key, $prefix) !== false) {
          do {
            $new_prefix = sprintf('%s_%s_%s',
              $prefix,
              $this->hashValue((string)$seed),
              $seed
            );
            $seed++;
          } while (strpos($key, $new_prefix) !== false);
          $prefix = $new_prefix;
        }
      }
    }
    unset($prefix);
  }

  /**
   * @access private
   */
  function callMethod($method, $args = array()) {
    $result = null;
    $argn = func_num_args();
    if ($argn > 1) {
      $args = func_get_args();
      $method = array_shift($args);
    } else if ($argn === 1 && !is_array($args)) {
      $args =  array($args);
    }
    $callable = $this->isCallableMethod($method, true);
    if ($callable) {
      $result = call_user_func_array($callable, $args);
    }
    return $result;
  }

  /**
   * @access private
   */
  function isCallableMethod($method, $return_callable = false) {
    $result = false;
    if ($method != null && is_string($method)) {
      $method = strtolower($method);
      if (isset($this->UDF[$method])) {
        if ($return_callable) {
          if (is_callable($this->UDF[$method])) {
            $result = $this->UDF[$method];
          } else {
            $this->pushError('Cannot call the function(%s)',
              strtoupper($method));
            $result = false;
          }
        } else {
          $result = true;
        }
      } else if (is_object($this->method)) {
        if (substr($method, 0, 1) !== '_'
          && strcasecmp(get_class($this->method), $method) !== 0) {
          if (!method_exists($this->method, $method)) {
            $this->pushError('Undefined function (%s)', strtoupper($method));
            $result = false;
          } else {
            if ($return_callable) {
              $result = array($this->method, $method);
              if (!is_callable($result)) {
                $this->pushError('Cannot call the function(%s)',
                  strtoupper($method));
                $result = false;
              }
            } else {
              $result = true;
            }
          }
        }
      }
    }
    return $result;
  }

  /**
   * Create new class instance as variable arguments
   *
   * @param   string        the class name of target
   * @param   mixed   (...) zero or more parameters to be passed in
   * @return  object        the new instance of object
   * @access  public
   */
  function createInstance($class) {
    $result = null;
    if ($class != null
      && is_string($class) && $this->existsClass($class)) {
      $argn = func_num_args();
      if ($argn === 1) {
        $result = new $class;
      } else {
        $args = func_get_args();
        array_shift($args);
        $argn--;
        $params = array();
        for ($i = 0; $i < $argn; $i++) {
          $params[] = sprintf('$args[%d]', $i);
        }
        $expr = sprintf('$result=new $class(%s);', implode(',', $params));
        eval($expr);
        unset($expr);
      }
    }
    return $result;
  }

  /**
   * Expression for WHERE syntax as PHP language
   *
   * @param  array   an array variable of target
   * @param  string  expression of PHP syntax
   * @return boolean results of evaluation code
   * @access private
   */
  function expr() {
    extract(func_get_arg(0));
    return eval('return ' . func_get_arg(1) . ';');
  }

  /**
   * Does valid expression safely for WHERE syntax as PHP language
   * The error message will be captured by this class
   * This method works only checking
   *
   * @param  mixed   an array variable for extract(), or expression as string
   * @param  mixed   expression of PHP syntax, or array variable for extract()
   * @return boolean whether it is possible expression
   * @access private
   */
  function checkExpr($expr, $meta = array()) {
    $result = false;
    if (is_array($expr)) {
      $tmp = $expr;
      $expr = $meta;
      $meta = $tmp;
      unset($tmp);
    }
    if (!is_array($meta)) {
      $meta = array();
    }
    if (!$this->hasError()) {
      if (is_string($expr)) {
        $tokens = $this->splitSyntax($expr);
        $level = 0;
        foreach ($tokens as $token) {
          switch ($token) {
            case '(':
              $level++;
              break;
            case ')':
              $level--;
              break;
            default:
              break;
          }
        }
        if ($level !== 0) {
          $this->pushError('Syntax error, expect () parenthesis');
        }
      }
      if (!$this->hasError()) {
        $this->_checkOnlyExpr = true;
        $result = $this->safeExpr($meta, $expr);
        $this->_checkOnlyExpr = null;
      }
    }
    return $result;
  }

  /**
   * Does expression safely for WHERE syntax as PHP language
   * The error message will be captured by this class
   * But the execution speed will be slow
   *
   * @param  mixed   an array variable for extract(), or expression as string
   * @param  mixed   expression of PHP syntax, or array variable for extract()
   * @return mixed   results of evaluation code
   * @access private
   */
  function safeExpr() {
    if ($this->isPHP5) {
      set_error_handler(array($this, 'errorHandler'), E_ALL);
    } else {
      set_error_handler(array(&$this, 'errorHandler'));
    }
    $args = func_get_args();
    switch (count($args)) {
      case 1:
        $result = $this->_execSafeExpr(array(), $args[0]);
        break;
      case 2:
        $result = $this->_execSafeExpr($args[0], $args[1]);
        break;
      default:
        $this->pushError('illegal arguments on expression');
        $result = false;
        break;
    }
    restore_error_handler();
    return $result;
  }

  /**
   * private method for safeExpr()
   *
   * @param  array   an array variable for extract()
   * @param  string  expression of PHP syntax
   * @return mixed   results of evaluation code
   * @access private
   */
  function _execSafeExpr() {
    if (func_get_arg(0) != null) {
      extract(func_get_arg(0));
    }
    ob_start();
    eval('if(0):return ' . func_get_arg(1) . ';endif;');
    if (ob_get_length()) {
      $msg = ob_get_contents();
      ob_end_clean();
      $msg = trim($this->stripTags($msg));
      if (preg_match('{\w+\s+error:\s+(.+)\sin[\s\S]*$}isU', $msg, $match)) {
        $msg = $match[1];
      }
      if ($msg == null) {
        if (empty($php_errormsg)) {
          $msg = 'Fatal error on expression';
        } else {
          $msg = $php_errormsg;
        }
      }
      $this->pushError($msg);
    } else {
      ob_end_clean();
      if (empty($this->_checkOnlyExpr)) {
        return eval('return ' . func_get_arg(1) . ';');
      }
      return true;
    }
    return false;
  }

  /**
   * optimizes the expression which worked PHP syntax
   * When the engine is executed in the PHP mode,
   *  and the command was SELECT, this expression is useful
   *
   * @param  mixed   the expression as string,
   *                  or an array which  parsed tokens
   * @return mixed   the value of expression which was optimized
   * @access private
   */
  function optimizeExpr($expr) {
    if (empty($expr)) {
      $expr = false;
    } else {
      if (is_array($expr)) {
        $expr = $this->joinWords($expr);
      }
      $expr = $this->trimExtra($expr, '%=`*?:/<>^\\,.|&;');
      if (is_numeric($expr) || strlen($expr) === 1) {
        $expr = (bool)$expr;
      } else {
        switch (strtolower($expr)) {
          case '':
          case 'null':
          case 'false':
            $expr = false;
            break;
          case '*':
          case 'true':
          case '0=0':
          case '0==0':
          case '1=1':
          case '1==1':
            $expr = true;
            break;
          default:
            break;
        }
      }
    }
    return $expr;
  }

  /**
   * Validates the expression for safely
   *
   * @param  string   expression
   * @return string   validated string of expression or false on error
   * @access private
   */
  function validExpr($expr) {
    $result = false;
    switch ($this->getEngine()) {
      case 'PHP':
        $result = $this->validExprByPHP($expr);
        break;
      case 'SQL':
        //case 'ECMA':
        //
        // ECMA/JavaScript engine is likely to be able to implements by
        //  using the Posql_ECMA class in future.
        //
      default:
        $result = $this->validExprBySQL($expr);
        break;
    }
    //debug(array('expr'=>$expr,'ret'=>$result),'color=navy:***validated***;');
    return $result;
  }

  /**
   * Validates the expression which handled as PHP syntax for safely
   *
   * @param  string   expression
   * @param  boolean  internal only
   * @return string   validated string of expression or false on error
   * @access private
   */
  function validExprBySQL($expr, $recursive = false) {
    $result = false;
    if ($this->isValidExpr($expr)) {
      $result = $expr;
    } else {
      if (!$recursive) {
        $expr = $this->assignSubQuery($expr);
        $this->_extraJoinExpr = array();
        $expr = $this->replaceExprBySQL($expr);
        if (!$this->hasError()) {
          if (empty($this->_isDualSelect)
            && empty($this->_onJoinParseExpr)
            && empty($this->_fromSubSelect)
            && empty($this->_onUnionSelect)) {
            if (!empty($this->tableName)
              && empty($this->meta[$this->tableName])) {
              $this->getMeta();
            }
            if (empty($this->_onCreate)) {
              if (empty($this->tableName)
                || empty($this->meta[$this->tableName])) {
                $table_name = $this->getTableName();
                $this->pushError('No such table(%s)'
                  .  ' on validating expression(%s)'
                  .  ' using SQL engine',
                  $table_name,
                  $expr
                );
                $result = false;
              }
            }
          }
        }
      }
      if ($this->hasError()) {
        $this->_extraJoinExpr = array();
      } else {
        if (!empty($this->_subSelectMeta)) {
          $meta = $this->_subSelectMeta;
          if (!empty($this->_subSelectJoinUniqueNames)) {
            $unique_names = & $this->_subSelectJoinUniqueNames;
            if (is_array($unique_names) && is_array($meta)) {
              foreach (array_keys($meta) as $meta_identifier) {
                foreach ($unique_names as $subquery => $sub_identifier) {
                  $identifier = $this->toCorrelationName($sub_identifier,
                    $meta_identifier);
                  if ($identifier != null) {
                    $meta[$identifier] = 1;
                  }
                }
              }
            }
            unset($unique_names);
          }
        } else if (!empty($this->meta) && is_array($this->meta)) {
          $org_meta = $this->meta;
          $meta = array();
          foreach ($org_meta as $meta_key => $meta_div) {
            if (!empty($meta_div) && is_array($meta_div)) {
              foreach ($meta_div as $key => $val) {
                $meta[$key] = 1;
              }
            }
          }
          if (!empty($this->_onMultiSelect)) {
            $this->validCorrelationPrefix($meta);
            foreach ($org_meta as $meta_key => $meta_div) {
              if (!empty($meta_div) && is_array($meta_div)) {
                foreach ($meta_div as $key => $val) {
                  $meta[$key] = 1;
                  $meta_key_org = $this->decodeKey($meta_key);
                  $extra_key = $this->toCorrelationName($meta_key_org, $key);
                  if ($extra_key != null) {
                    $meta[$extra_key] = 1;
                  }
                }
              }
            }
          }
        } else if (!empty($this->meta[$this->tableName])
          && is_array($this->meta[$this->tableName])) {
          $meta = & $this->meta[$this->tableName];
        } else {
          $meta = array();
        }
        $callable = 'callmethod';
        $is_recursive = false;
        $expr = $this->cleanArray($expr);
        $open = '(';
        $close = ')';
        $level = 0;
        $length = count($expr);
        for ($i = 0; $i < $length; $i++) {
          $token = $this->getValue($expr, $i);
          $prev = $this->getValue($expr, $i - 1);
          $next = $this->getValue($expr, $i + 1);

          switch (strtolower($token)) {
            case '(':
              $level++;
              if ($prev != null) {
                if (substr($prev, 0, 1) === '$') {
                  $prev = substr($prev, 1);
                  if (isset($expr[$i - 1])) {
                    $expr[$i - 1] = $prev;
                  }
                }
                if (!$this->isWord($prev)) {
                  break;
                }
                $func = strtolower($prev);
                if ($func === $callable
                  && isset($expr[$i - 2], $expr[$i - 3], $expr[$i - 4])
                  && $expr[$i - 2] === '->'
                  && $expr[$i - 3] === 'this'
                  && $expr[$i - 4] === '$') {
                  break;
                } else if ((!empty($this->_onJoinParseExpr)
                    || !empty($this->_onMultiSelect))
                  && ($func === 'isset' || $func === 'empty')) {
                  break;
                } else {
                  if ($func !== 'and' && $func !== 'or'
                    && $func !== 'xor' && $func !== 'is'
                    && $func !== 'not' && $func !== 'in') {
                    $nest = 0;
                    $idx = $i;
                    $args = array();
                    while (array_key_exists($idx, $expr)) {
                      $arg = $expr[$idx];
                      switch ($arg) {
                        case '(':
                          $nest++;
                          $prev_idx = $idx - 1;
                          if ($nest > 1 && isset($expr[$prev_idx])
                            && $expr[$prev_idx] != null
                            && $this->isWord($expr[$prev_idx])
                            && strtolower($expr[$prev_idx]) !== $callable) {
                            $is_recursive = true;
                          }
                          break;
                        case ')':
                          $nest--;
                          break;
                        case ',':
                        default:
                          break;
                      }

                      $args[] = $arg;
                      if ($nest === 0) {
                        array_pop($args);
                        array_shift($args);
                        break;
                      }
                      $idx++;
                    }

                    if (!empty($args)) {
                      array_unshift($args, ',');
                    }

                    $func = $this->toStringToken(strtoupper($func));
                    $method = array(
                      '$', 'this', '->', $callable, '(',
                      $func, $args,
                      ')'
                    );
                    $method = $this->toOneArray($method);
                    array_splice($expr, $i - 1, $idx - $i + 2, $method);
                    $length = count($expr);
                  }
                }
              }
              break;
            case ')':
              $level--;
              break;
            case 'new':
              if (!$recursive) {
                if ($next != null && $this->isWord($next)) {
                  if ($this->existsClass($next)
                    || !array_key_exists($token, $meta)) {
                    $this->pushError('Not supported SQL syntax,'
                      .  ' expect(%s)', $token);
                    $result = false;
                    break 2;
                  }
                }
              }
              break;
            case '<?':
            case '<%':
              $error_token = 'T_OPEN_TAG';
            case '?>':
            case '%>':
              if (empty($error_token)) {
                $error_token = 'T_CLOSE_TAG';
              }
              if (!$recursive) {
                $this->pushError('Invalid SQL syntax,'
                  .  ' expect %s', $error_token);
                $result = false;
                break 2;
              }
              break;
            case '$':
              if (empty($this->_onJoinParseExpr)) {
                if (!$recursive) {
                  if ($next == null || !array_key_exists($next, $meta)) {
                    $this->pushError('Unknown variable name(%s)'
                      .  ' on expression', $next);
                    $result = false;
                    break 2;
                  }
                }
              }
              if (!$recursive) {
                if ($next === '{' || $next === '}') {
                  $this->pushError('Invalid SQL syntax, expect(%s%s)',
                    $token, $next);
                  $result = false;
                }
              }
              break;
            default:
              if (!$recursive) {
                $lastchar = substr($prev, -1);
                if (!empty($this->_uniqueColsByHaving)) {
                  $unique_cols = $this->_uniqueColsByHaving;
                  if ($lastchar !== '$' && is_array($unique_cols)
                    && array_key_exists($token, $unique_cols)) {
                    $token = '$' . $token;
                    $expr[$i] = $token;
                  }
                  unset($unique_cols);
                }
                if (array_key_exists($token, $meta)) {
                  if ($lastchar !== '$' && $this->isWord($token)) {
                    $varname = $token;
                    $token = '$' . $token;
                    $expr[$i] = $token;
                    $prev_idx = $i - 1;
                    $corr_idx = $i - 2;
                    $corr_name = $this->getValue($expr, $corr_idx);
                    if ($lastchar === '.' && isset($expr[$corr_idx])
                      && $expr[$corr_idx] != null
                      && $this->isWord($expr[$corr_idx])) {
                      array_splice($expr, $corr_idx, 2);
                      $i = $corr_idx;
                    }
                    if (!empty($this->_onMultiSelect)) {
                      if ($corr_name != null) {
                        $corr_dot = sprintf('%s.%s', $corr_name, $varname);
                        $corr_org = $this->restoreTableAliasName($corr_dot);
                        if ($corr_dot !== $corr_org
                          && strpos($corr_org, '.') !== false) {
                          list($corr_name, $varname) = explode('.',
                            $corr_org);
                        }
                        if (!empty($this->_subSelectJoinUniqueNames)) {
                          $unique_names = & $this->_subSelectJoinUniqueNames;
                          if ($corr_dot !== $corr_org
                            && strpos($corr_org, '.') !== false) {
                            $corr_split = explode('.', $corr_org);
                            if (count($corr_split) > 2) {
                              $corr_split_tail = array_pop($corr_split);
                              $corr_split = array(
                                implode('.', $corr_split),
                                $corr_split_tail
                              );
                            }
                            if (count($corr_split) >= 2) {
                              list($corr_name, $varname) = $corr_split;
                            }
                          }
                          if (is_array($unique_names)
                            && array_key_exists($corr_name, $unique_names)) {
                            $corr_name = $unique_names[$corr_name];
                          }
                          unset($unique_names);
                        }
                        $varname = $this->toCorrelationName($corr_name,
                          $varname);
                        $token = '$' . $varname;
                        $expr[$i] = $token;
                      }
                      // Check whether the variable exists for NOTICE
                      $isset_expr = array(
                        '!', 'isset', '(', '$', $varname, ')',
                        '||'
                      );
                      $this->removeParsedMark($expr);
                      array_splice($expr, 0, 0, $isset_expr);
                      $this->addParsedMark($expr);
                      $length = count($expr);
                      array_unshift($this->_extraJoinExpr, $isset_expr);
                    }
                  }
                }
              }
              break;
          }
        }
        if (end($expr) === '||') {
          array_pop($expr);
        }
        unset($meta);
        if ($this->hasError()) {
          $this->_extraJoinExpr = array();
        } else {
          if ($is_recursive) {
            $result = $this->validExprBySQL($expr, true);
          } else {
            //TODO: optimize the expression
            if ($this->addValidMarks($expr)) {
              $result = $this->joinWords($expr);
            }
          }
        }
      }
    }
    return $result;
  }

  /**
   * Validates the expression which handled as PHP syntax for safely
   *
   * @param  string   expression
   * @return string   validated string of expression or false on error
   * @access private
   */
  function validExprByPHP($expr) {
    $result = false;
    if (!empty($this->tableName)
      && empty($this->meta[$this->tableName])) {
      $this->getMeta();
    }
    $this->_extraJoinExpr = array();
    if (!$this->hasError()) {
      if (empty($this->_isDualSelect)
        && empty($this->_fromSubSelect)
        && empty($this->_onUnionSelect)) {
        if (empty($this->_onCreate)) {
          if (empty($this->tableName)
            || empty($this->meta[$this->tableName])) {
            $table_name = $this->getTableName();
            $this->pushError('No such table(%s)'
              .  ' on validating expression(%s)'
              .  ' using PHP engine',
              $table_name,
              $expr
            );
            $result = false;
          }
        }
      }
      if (!$this->hasError()) {
        $this->checkDisableFunctions();
        if (!empty($this->_subSelectMeta)) {
          $meta = $this->_subSelectMeta;
          if (!empty($this->_subSelectJoinUniqueNames)) {
            $unique_names = & $this->_subSelectJoinUniqueNames;
            if (is_array($unique_names) && is_array($meta)) {
              foreach (array_keys($meta) as $meta_identifier) {
                foreach ($unique_names as $subquery => $sub_identifier) {
                  $identifier = $this->toCorrelationName($sub_identifier,
                    $meta_identifier);
                  if ($identifier != null) {
                    $meta[$identifier] = 1;
                  }
                }
              }
            }
            unset($unique_names);
          }
        } else if (!empty($this->meta) && is_array($this->meta)) {
          $org_meta = $this->meta;
          $meta = array();
          foreach ($org_meta as $meta_key => $meta_div) {
            if (!empty($meta_div) && is_array($meta_div)) {
              foreach ($meta_div as $key => $val) {
                $meta[$key] = 1;
              }
            }
          }
          if (!empty($this->_onMultiSelect)) {
            $this->validCorrelationPrefix($meta);
            foreach ($org_meta as $meta_key => $meta_div) {
              if (!empty($meta_div) && is_array($meta_div)) {
                foreach ($meta_div as $key => $val) {
                  $meta[$key] = 1;
                  $meta_key_org = $this->decodeKey($meta_key);
                  $extra_key = $this->toCorrelationName($meta_key_org, $key);
                  if ($extra_key != null) {
                    $meta[$extra_key] = 1;
                  }
                }
              }
            }
          }
        } else if (!empty($this->meta[$this->tableName])
          && is_array($this->meta[$this->tableName])) {
          $meta = & $this->meta[$this->tableName];
        } else {
          $meta = array();
        }
        $expr = $this->assignSubQuery($expr);
        if (is_array($expr)) {
          $tokens = $expr;
        } else {
          $tokens = $this->splitSyntax($expr);
        }
        if (!is_array($tokens)) {
          $this->pushError('Failed to parsing the syntax(%s)', $expr);
          $result = false;
        } else if (empty($tokens)) {
          $result = false;
        } else {
          array_unshift($tokens, ' ');
          foreach ($tokens as $i => $token) {
            $h = $i - 1;
            $pre = substr($token, 0, 1);
            switch ($token) {
              case '`':
                $this->pushError('The backtick(``) operator is disabled');
                $tokens = array('0');
                break;
              case '$':
                if (isset($tokens[$i + 1])) {
                  $next = $tokens[$i + 1];
                  if ($next === '{' || $next === '}') {
                    $this->pushError('Invalid SQL syntax, expect(%s%s)',
                      $token, $next);
                    $tokens = array('0');
                    break;
                  }
                }
                break;
              case '<?':
              case '<%':
                $error_token = 'T_OPEN_TAG';
              case '?>':
              case '%>':
                if (empty($error_token)) {
                  $error_token = 'T_CLOSE_TAG';
                }
                $this->pushError('Invalid SQL syntax, expect %s',
                  $error_token);
                $tokens = array('0');
                break;
              default:
                break;
            }
            if ($this->hasError()) {
              $tokens = array('0');
              break;
            }
            if ($token === '=' && !empty($this->autoAssignEquals)) {
              $token = $tokens[$i] = '==';
            } else if ($this->isWord($pre)) {
              if (isset($tokens[$h])) {
                $lastchar = substr($tokens[$h], -1);
                if ($lastchar === '$' && $token === 'this') {
                  $this->pushError('Disable variable($this) in expression');
                  $tokens = array('0');
                  break;
                }
                if ($lastchar !== '$') {
                  if (!empty($this->_uniqueColsByHaving)) {
                    $unique_cols = $this->_uniqueColsByHaving;
                    if (is_array($unique_cols)
                      && array_key_exists($token, $unique_cols)) {
                      $token = '$' . $token;
                      $tokens[$i] = $token;
                    }
                    unset($unique_cols);
                  }
                  if (array_key_exists($token, $meta)) {
                    $varname = $token;
                    $token = '$' . $token;
                    $tokens[$i] = $token;
                    $prev_idx = $i - 1;
                    $corr_idx = $i - 2;
                    $corr_name = $this->getValue($tokens, $corr_idx);
                    if ($lastchar === '.' && isset($tokens[$corr_idx])
                      && $tokens[$corr_idx] != null
                      && $this->isWord($tokens[$corr_idx])) {
                      $tokens[$prev_idx] = '';
                      $tokens[$corr_idx] = '';
                    }
                    if (!empty($this->_onMultiSelect)) {
                      if ($corr_name != null) {
                        $corr_dot = sprintf('%s.%s', $corr_name, $varname);
                        $corr_org = $this->restoreTableAliasName($corr_dot);
                        if ($corr_dot !== $corr_org
                          && strpos($corr_org, '.') !== false) {
                          list($corr_name, $varname) = explode('.', $corr_org);
                        }
                        if (!empty($this->_subSelectJoinUniqueNames)) {
                          $unique_names = & $this->_subSelectJoinUniqueNames;
                          if ($corr_dot !== $corr_org
                            && strpos($corr_org, '.') !== false) {
                            $corr_split = explode('.', $corr_org);
                            if (count($corr_split) > 2) {
                              $corr_split_tail = array_pop($corr_split);
                              $corr_split = array(
                                implode('.', $corr_split),
                                $corr_split_tail
                              );
                            }
                            if (count($corr_split) >= 2) {
                              list($corr_name, $varname) = $corr_split;
                            }
                          }
                          if (is_array($unique_names)
                            && array_key_exists($corr_name, $unique_names)) {
                            $corr_name = $unique_names[$corr_name];
                          }
                          unset($unique_names);
                        }
                        $varname = $this->toCorrelationName($corr_name,
                          $varname);
                        $token = '$' . $varname;
                        $tokens[$i] = $token;
                      }
                      // Check whether the variable exists for NOTICE
                      $isset_expr = array(
                        '!', 'isset', '(', '$', $varname, ')',
                        '||'
                      );
                      array_unshift($this->_extraJoinExpr, $isset_expr);
                    }
                  }
                }
                if ($this->isAlphaNum($lastchar)) {
                  $tokens[$h] .= ' ';
                }
              }
              if ($this->isDisableFunction($token)) {
                $this->pushError('Disable function(%s) in expression', $token);
                $tokens = array('0');
                break;
              }
            }
          }
          if ($this->hasError()) {
            $this->_extraJoinExpr = array();
            $result = false;
          } else {
            if (!empty($this->_extraJoinExpr)) {
              foreach ($this->_extraJoinExpr as $isset_expr) {
                array_splice($tokens, 0, 0, $isset_expr);
              }
            }
            if (end($tokens) === '||') {
              array_pop($tokens);
            }
            $tokens = $this->cleanArray($tokens);
            if (empty($this->_execExpr)) {
              $result = $this->optimizeExpr($tokens);
            } else {
              $result = $this->trimExtra($this->joinWords($tokens), '&|;');
            }
            if (!$this->checkExpr($result)) {
              $result = false;
            }
          }
        }
        unset($meta);
      }
    }
    return $result;
  }

  /**
   * Executes the sub-select expression
   *
   * @param  string  the sub-query expression
   * @param  string  the left operator
   * @return mixed   result of sub-select query
   * @access private
   */
  function execSubSelect($query, $operator = null) {
    $result = null;
    if (!$this->hasError()) {
      $command = $this->getQueryCommand($query);
      if (0 !== strcasecmp($command, 'select')) {
        $this->pushError('The sub-query(%s) should be SELECT', $command);
      } else {
        $rows = array();
        $clone = new Posql;
        foreach (get_object_vars($this) as $prop => $val) {
          if ($prop != null && is_string($prop)) {
            $clone->{$prop} = $val;
          }
        }
        $stmt = $clone->query($query);
        if ($clone->hasError()) {
          $this->pushError($clone->lastError());
        }
        if ($this->isStatementObject($stmt)) {
          $fetch_mode = 'assoc';
          switch (strtolower($operator)) {
            case 'from':
              $this->_fromSubSelect = true;
              $result = $stmt->fetchAll($fetch_mode);
              if (!is_array($result) || empty($result)) {
                $result = array();
              } else {
                $reset = reset($result);
                if (!is_array($reset)) {
                  $this->pushError('Invalid result-set on sub-query');
                } else {
                  $this->_subSelectMeta = array();
                  foreach (array_keys($reset) as $column) {
                    $this->_subSelectMeta[$column] = 1;
                  }
                }
              }
              break;
            case 'exists':
              $result = '0';
              if ($stmt->rowCount()) {
                $result = '1';
              }
              break;
            default:
              if (!$stmt->rowCount()) {
                $result = null;
              } else {
                $row = $stmt->fetch($fetch_mode);
                if (empty($row)) {
                  $result = null;
                } else if (is_array($row)) {
                  if (count($row) !== 1) {
                    $this->pushError('Operand should contain 1 column');
                    $result = null;
                  } else {
                    $rows = array();
                    do {
                      $rows[] = $this->quote(reset($row));
                    } while (is_array($row = $stmt->fetch($fetch_mode)));
                    if (0 === strcasecmp($operator, 'in')
                      || 0 === strcasecmp($operator, 'any')
                      || 0 === strcasecmp($operator, 'some')
                      || 0 === strcasecmp($operator, 'all')) {
                      $rows = array_unique($rows);
                    }
                    $result = implode(',', $rows);
                  }
                }
              }
              break;
          }
        }
        $stmt = null;
        unset($stmt, $rows);
        $clone->_setTerminated(true);
        $clone = null;
        unset($clone);
      }
    }
    if ($result === null) {
      $result = 'NULL';
    }
    return $result;
  }

  /**
   * Assign the sub-query to the result
   *
   * @param  array   the SQL query, or the parsed tokens
   * @return mixed   executed result, or FALSE on error
   * @access private
   */
  function assignSubQuery($query) {
    if (is_string($query)) {
      $query = $this->splitSyntax($query);
    }
    if (is_array($query)) {
      while (true) {
        $sub_parsed = $this->parseSubQuery($query);
        if (is_array($sub_parsed)
          && array_key_exists('tokens', $sub_parsed)
          && array_key_exists('offset', $sub_parsed)
          && array_key_exists('length', $sub_parsed)
          && array_key_exists('operator', $sub_parsed)) {
          $sub_tokens   = $sub_parsed['tokens'];
          $sub_offset   = $sub_parsed['offset'];
          $sub_length   = $sub_parsed['length'];
          $sub_operator = $sub_parsed['operator'];
          $sub_query    = $this->joinWords($sub_tokens);
          $sub_result   = $this->execSubSelect($sub_query, $sub_operator);
          if ($this->hasError()) {
            $query = array('NULL');
            break;
          } else {
            $prev_query = $query;
            array_splice($query, $sub_offset, $sub_length, $sub_result);
            $query = $this->cleanArray($query);
            if ($query === $prev_query) {
              break;
            }
          }
        } else {
          break;
        }
      }
    }
    if (is_array($query)) {
      $query = $this->joinWords($query);
    }
    return $query;
  }

  /**
   * Execute the sub-query for FROM clause
   *
   * @param  array   the SQL query, or the parsed tokens
   * @return mixed   executed result, or FALSE on error
   * @access private
   */
  function execSubQueryFrom($query) {
    $result = array();
    if (is_string($query)) {
      $query = $this->splitSyntax($query);
    }
    if (is_array($query)) {
      $sub_parsed = $this->parseSubQuery($query);
      if (is_array($sub_parsed)
        && array_key_exists('tokens', $sub_parsed)) {
        $tokens = $sub_parsed['tokens'];
        if (is_array($tokens) && !empty($tokens)) {
          $sub_query = $this->joinWords($tokens);
          $result = $this->execSubSelect($sub_query, 'from');
        }
      }
    }
    if ($this->hasError()) {
      $result = array();
    } else if (is_string($result) && 0 === strcasecmp($result, 'null')) {
      $result = array();
    }
    return $result;
  }

  /**
   * Execute any sub-queries for FROM clause
   *
   * @param  array   the SQL query, or the parsed tokens
   * @return mixed   executed result, or FALSE on error
   * @access private
   */
  function execMultiSubQueryFrom($query) {
    $result = array();
    if (!$this->hasError()) {
      if (empty($this->_subSelectJoinInfo)
        || empty($this->_subSelectJoinUniqueNames)) {
        $this->pushError('Failed to compile on sub-query using JOIN');
      } else {
        $unique_names = & $this->_subSelectJoinUniqueNames;
        $unique_names = $this->flipArray($unique_names);
        if (is_string($query)) {
          $query = $this->splitSyntax($query);
        }
        if (is_array($query)
          && !empty($unique_names) && is_array($unique_names)) {
          while (true) {
            $sub_parsed = $this->parseSubQuery($query, true);
            if (is_array($sub_parsed)
              && array_key_exists('tokens', $sub_parsed)
              && array_key_exists('offset', $sub_parsed)
              && array_key_exists('length', $sub_parsed)) {
              $sub_tokens = $sub_parsed['tokens'];
              $sub_offset = $sub_parsed['offset'];
              $sub_length = $sub_parsed['length'];
              $sub_query  = $this->joinWords($sub_tokens);
              if (!array_key_exists($sub_query, $unique_names)) {
                $this->pushError('Failed to compile on sub-query using JOIN');
                break;
              }
              $exec_query = $sub_query;
              while (substr($exec_query, 0, 1) === '('
                && substr($exec_query,  -1)  === ')') {
                $exec_query = substr($exec_query, 1, -1);
              }
              $this->_fromSubSelect = null;
              $sub_result = $this->execSubSelect($exec_query, 'from');
              if ($this->hasError()) {
                break;
              } else {
                $unique_name = $unique_names[$sub_query];
                $result[$unique_name] = $sub_result;
                $sub_result = null;
                $prev_query = $query;
                array_splice($query, $sub_offset, $sub_length, $unique_name);
                $query = $this->cleanArray($query);
                if ($query === $prev_query) {
                  break;
                }
              }
              unset($sub_result);
            } else {
              break;
            }
          }
        }
        if (!$this->hasError()) {
          foreach ($unique_names as $symbol => $identifier) {
            if (!array_key_exists($identifier, $result)) {
              $result[$identifier] = $symbol;
            }
          }
        }
        unset($unique_names);
      }
    }
    if ($this->hasError()) {
      $result = array();
    } else if (!is_array($result)) {
      $result = array();
    }
    return $result;
  }


  /**
   * Executes the expression on HAVING clause
   *
   * @param  array   result records as array
   * @param  mixed   expression as HAVING clause (default = true)
   * @param  mixed   the name of column which was used GROUP BY clause
   * @param  array   results of all aggregate functions
   * @return array   results of aggregate which were varied by HAVING with rows
   * @access private
   */
  function having(&$rows, $having = true, $groups = null, $aggregates = null) {
    $this->_onHaving = true;
    $row_count = count($rows);
    if ($having === false || $having === null) {
      $rows = array();
    } else if (!is_scalar($having)) {
      $type = gettype($having);
      $this->pushError('illegal type(%s) of expression on HAVING', $type);
      $rows = array();
    } else if (!is_array($rows) || !is_array(reset($rows))) {
      $rows = array();
    } else {
      if (!is_array($having)) {
        $having = $this->splitSyntax($having);
      }
      $restored = $this->restoreAliasTokens($having);
      if ($restored != null) {
        $having = $restored;
        unset($restored);
      }
      $func_info = $this->parseHaving($having);
      if ($this->hasError()) {
        $rows = array();
      } else {
        $rows_cols_org = array_keys(reset($rows));
        if ($groups == null && $func_info != null) {
          if (!empty($rows) && is_array($rows)) {
            array_splice($rows, 1);
            if (!empty($aggregates) && is_array($aggregates)) {
              array_splice($aggregates, 1);
            } else if ($aggregates == null) {
              $group_single = array(array(0));
              $aggregates = $this->aggregateByGroups($rows, $group_single);
              $this->aggregateAssignByGroups($rows, $aggregates);
            }
          }
        }
        $appended_cols = $this->appendAggregateResult($rows, $aggregates);
        $rows_cols = array_keys(reset($rows));
        if (!empty($func_info) && $this->isEnableHavingInfo($func_info)) {
          $unique_id = 0;
          $unique_vars = array();
          if (!empty($appended_cols) && is_array($appended_cols)) {
            $check = '0000';
            $cols  = 'cols';
            foreach ($func_info as $info) {
              $unique_format = '_posql_having_';
              $unique_more = false;
              foreach ($rows_cols as $key) {
                if (strpos($key, $check) !== false
                  || strpos($key, $unique_format) !== false) {
                  $unique_more = true;
                  break;
                }
              }
              $unique_format .= '%04d';
              do {
                $unique_var = sprintf($unique_format, $unique_id++);
                if ($unique_more) {
                  $unique_var .= dechex($unique_id);
                }
              } while (array_key_exists($unique_var, $rows_cols));
              $having = $this->replaceHavingTokens($having, $info,
                $unique_var);
              $unique_vars[$unique_var] = $info[$cols];
            }
            $having = $this->cleanArray($having);
          }
          if (!empty($rows)) {
            if (!empty($unique_vars) && is_array($unique_vars)) {
              $this->_uniqueColsByHaving = array();
              foreach ($unique_vars as $unique_var => $column_name) {
                $this->_uniqueColsByHaving[$unique_var] = $column_name;
                $row_count = count($rows);
                for ($i = 0; $i < $row_count; $i++) {
                  $agg_column = sprintf('%s#%08u', $column_name, $i);
                  if (!is_array($rows[$i])
                    || !array_key_exists($agg_column, $rows[$i])) {
                    $this->pushError('Failed to compile on HAVING');
                    $rows = array();
                    break 2;
                  }
                  $rows[$i][$unique_var] = & $rows[$i][$agg_column];
                }
              }
            }
          }
        }
        if (!empty($rows)) {
          $expr = $this->validExpr($having);
          $this->_uniqueColsByHaving = null;
          $this->execHaving($rows, $aggregates, $expr);
          //debug($rows,'color=orange:***HAVING***;');
          if ($this->hasError()) {
            $rows = array();
          } else {
            $this->diffColumns($rows, $rows_cols_org, true);
          }
        }
      }
    }
    $this->_uniqueColsByHaving = null;
    $this->_onHaving = null;
    return $aggregates;
  }

  /**
   * Executes the expression for HAVING clause
   *
   * @param  array   the rows of result sets
   * @param  array   results of all aggregate functions
   * @param  mixed   the expression on HAVING clause
   * @return void
   * @access private
   */
  function execHaving(&$rows, &$aggregates, $expr = true) {
    if ($this->hasError()) {
      $rows = array();
    } else if (is_array($expr)) {
      $this->pushError('Failed to parse HAVING clause');
      $rows = array();
    } else if (is_array($rows) && is_array(reset($rows))) {
      if ($expr === false || $expr === null) {
        $rows = array();
      } else if ($expr !== true && is_scalar($expr)) {
        $first = true;
        $row_count = count($rows);
        for ($i = 0; $i < $row_count; $i++) {
          $row = $rows[$i];
          if (empty($this->_onMultiSelect)) {
            $row = $rows[$i];
          } else {
            $row = array();
            foreach ($rows[$i] as $key => $val) {
              $key = $this->toCorrelationName($key);
              $row[$key] = $val;
            }
          }
          if ($first) {
            $first = false;
            if (!$this->safeExpr($row, $expr)) {
              unset($rows[$i], $aggregates[$i]);
            }
            if ($this->hasError()) {
              $rows = array();
              break;
            }
          } else {
            if (!$this->expr($row, $expr)) {
              unset($rows[$i], $aggregates[$i]);
            }
          }
        }
        if (!empty($rows) && is_array($rows)
          && !empty($aggregates) && is_array($aggregates)) {
          $rows = array_values($rows);
          $aggregates = array_values($aggregates);
        }
      }
    }
  }

  /**
   * Executes the expression for INSERT query
   *
   * @param  array   A row of result sets
   * @param  mixed   the column, or expression
   * @param  mixed   the value of expression
   * @return mixed   results of expression
   * @access private
   */
  function execInsertExpr($row, $key, $val) {
    $result = false;
    if (array_key_exists($key, $row)) {
      $expr = $this->validExpr($row[$key]);
      if (!$this->hasError()) {
        $result = $this->safeExpr($this->_curMeta, $expr);
      }
    } else {
      $result = $val;
    }
    return $result;
  }

  /**
   * Executes the expression for UPDATE query
   *  e.g. "UPDATE counter SET count = count + 1"
   *
   * @param  array   A row of result sets
   * @param  mixed   the column, or expression
   * @param  mixed   the value of expression
   * @return mixed   results of expression
   * @access private
   */
  function execUpdateExpr($row, $key, $val) {
    $result = false;
    if (array_key_exists($key, $this->_curMeta)) {
      $expr = $this->validExpr($this->_curMeta[$key]);
      if (!$this->hasError()) {
        $result = $this->safeExpr($row, $expr);
      }
    }
    return $result;
  }

  /**
   * Executes the expression for SELECT-List clause
   *
   * @param  array   A row of result sets
   * @param  mixed   the column, or expression
   * @return mixed   results of expression
   * @access private
   */
  function execSelectExpr($row, $col) {
    $result = false;
    if (is_array($row)) {
      $expr = $this->validExpr($col);
      if (!$this->hasError()) {
        $result = $this->safeExpr($row, $expr);
      }
    }
    return $result;
  }

  /**
   * Executes the aggregate function to the rows of the result
   *
   * @param  array   result records
   * @param  string  the aggregate function name
   * @param  string  the name of column which executed aggregate function
   * @param  array   array which has the value of aggregate functions result
   * @return boolean success or failure
   * @access private
   */
  function execAggregateFunction(&$rows, $func, $col, $aggregates) {
    $result = false;
    if (!$this->hasError()) {
      if (!empty($rows) && is_array($rows) && is_array(reset($rows))
        && !empty($aggregates)
        && is_array($aggregates) && is_array(reset($aggregates))) {
        $org_col = $col;
        $col = $this->replaceAliasName($col);
        if (!$this->hasError()) {
          $row_count = count($rows);
          $agg_count = count($aggregates);
          $func_name = $this->formatAggregateFunction($func, $org_col);
          $lfunc_name = strtolower($func);
          $reset = reset($aggregates);
          $agg_defaults = $this->getAggregateDefaults();
          if (!array_key_exists($lfunc_name, $agg_defaults)) {
            $this->pushError('Not supported function(%s)', $func);
          } else {
            if ($agg_count < $row_count) {
              $this->pushError('illegal offset(%d) on aggregate function',
                $agg_count);
            } else {
              if ($col === '*' && $lfunc_name === 'count') {
                $agg_func = 'count_all';
                $col = key($reset);
              } else {
                $agg_func = $lfunc_name;
              }
              for ($i = 0; $i < $row_count; $i++) {
                $val = null;
                if (isset($aggregates[$i][$col][$agg_func])) {
                  $val = $aggregates[$i][$col][$agg_func];
                }
                $rows[$i][$func_name] = $val;
              }
              $result = true;
            }
          }
        }
      }
    }
    return $result;
  }

  /**
   * Executes all the aggregate functions to the rows of the result
   *
   * @param  array   result records
   * @param  array   array which has the value of aggregate functions result
   * @access private
   */
  function execAllAggregateFunction(&$rows, $funcs) {
    $length = count($rows);
    if ($length) {
      $aggregates = $this->aggregateAll($rows);
      foreach ($funcs as $funcname => $colname) {
        $lcf = strtolower($funcname);
        $org_col = $colname;
        $colname = $this->replaceAliasName($colname);
        if (!$this->hasError()) {
          if ($colname === '*') {
            if ($lcf !== 'count') {
              $this->pushError('Supplied argument is not valid(%s)',
                $funcname);
            } else {
              $col = $this->formatAggregateFunction($funcname, $org_col);
              $val = $this->math->add($length, 0);
              for ($i = 0; $i < $length; $i++) {
                $rows[$i][$col] = $val;
              }
            }
          } else {
            $agg = & $aggregates['std'];
            $col = $this->formatAggregateFunction($funcname, $org_col);
            if (!array_key_exists($lcf, $agg)) {
              $this->pushError('Not supported function(%s)', $funcname);
            } else {
              if (array_key_exists($colname, $agg[$lcf])) {
                $val = $agg[$lcf][$colname];
                for ($i = 0; $i < $length; $i++) {
                  $rows[$i][$col] = $val;
                }
              }
            }
            unset($agg);
          }
        }
      }
    }
  }

  /**
   * Returns the assigned result row which executed aggregate function
   *
   * @param  number  the result value of COUNT function
   * @param  string  the aggregate function name
   * @param  string  the name of column
   *                  which will be assigned aggregate function
   * @return array   the result row which assigned aggregate function
   * @access private
   */
  function execSimpleCountFunction($count, $func, $col) {
    $result = array();
    if (!$this->hasError()) {
      if (is_numeric($count)
        && $func != null && $col != null
        && is_string($func) && is_string($col)) {
        $func_name = $this->formatAggregateFunction($func, $col);
        $result[] = array($func_name => $count);
      }
    }
    return $result;
  }

}
