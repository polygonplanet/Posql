<?php
require_once dirname(__FILE__) . '/parser.php';

/**
 * @name Posql_Builder
 *
 * This builder class that assign from tokens for Posql
 *
 * @package   Posql
 * @author    Polygon Planet <polygon.planet.aqua@gmail.com>
 */
class Posql_Builder extends Posql_Parser {

  /**
   * Generates the pattern of the LIKE operator
   *
   * @param  string  the pattern which is given from the tokens
   * @param  string  optionally, escape character (default=backslash: \ )
   * @return string  the pattern which was generated as string
   * @access private
   */
  function toLikePattern($pattern, $escape = '\\') {
    static $patterns = array();

    $result = null;
    $hash = $this->hashValue($pattern . $escape);

    if (isset($patterns[$hash])) {
      $result = $patterns[$hash];
    } else {
      $mod = 'isu';
      $dlm = '~';
      $pre = '^';
      $suf = '$';
      $q = '\'';

      if ($this->isStringToken($pattern)) {
        $pattern = substr($pattern, 1, -1);
      }

      if ($this->isStringToken($escape)) {
        $escape = substr($escape, 1, -1);
      }

      if ($pattern == null) {
        $pattern = '(?:)';
      } else {
        $default_escape = '\\';
        if ($escape === null) {
          $escape = $default_escape;
        }

        if ($escape !== $default_escape) {
          $pattern = str_replace($escape, $default_escape, $pattern);
        }
        $pattern = preg_quote($pattern, $dlm);
        $translates = array(
          '\\\\\\\\' => '\\\\',
          '\"' => '"',
          '\'' => '\\\'',
          '\%' => '%',
          '\_' => '_',
          '%'  => '.*',
          '_'  => '.'
        );
        $pattern = strtr($pattern, $translates);
      }
      $result = $dlm . $pre . $pattern . $suf . $dlm . $mod;
      $patterns[$hash] = $result;
    }
    return $result;
  }

  /**
   * Convert the statement object into array
   *
   * @param  mixed   the statement object, or result-set as array
   * @return string  result-set which converted into array forcibly
   * @access public
   */
  function toArrayResult($stmt) {
    $result = array();

    if (is_array($stmt)) {
      $result = $stmt;
    } else {
      if (is_object($stmt)) {
        if (method_exists($stmt, 'fetchAll')) {
          $result = $stmt->fetchAll('assoc');
        } else if (isset($stmt->rows) && is_array($stmt->rows)) {
          $result = $stmt->rows;
        } else {
          $result = (array)$stmt;
        }
      }
    }

    $stmt = null;
    unset($stmt);
    return $result;
  }

  /**
   * Correlate the string which delimited by the dot(.)
   *
   * @param  string  the left correlation name
   * @param  string  optionally, the right correlation name
   * @return string  the unique internal correlation name
   * @access private
   */
  function toCorrelationName($left, $right = null) {
    $result = null;
    $checked = false;
    $enable_left = false;
    $enable_right = false;

    if (is_string($left)) {
      if ($right === null) {
        if (strpos($left, '.') !== false) {
          if (empty($this->_onHaving)) {
            list($left, $right) = explode('.', $left);
          } else {
            list($left_side, $right_side) = explode('.', $left);
            $enable_left = $this->isEnableName($left_side);
            $enable_right = $this->isEnableName($right_side);
            $checked = true;
            if ($enable_left && $enable_right) {
              $left = $left_side;
              $right = $right_side;
            }
          }
        }
      }

      if ($left != null) {
        if ($right == null) {
          $result = $left;
        } else {
          if (!$checked) {
            $enable_left = $this->isEnableName($left);
            $enable_right = $this->isEnableName($right);
          }

          if (!$enable_right) {
            $result = $left;
          } else if (!$enable_left) {
            $result = $right;
          } else {
            if (empty($this->_correlationPrefix)) {
              $this->initCorrelationPrefix();
            }

            $result = sprintf('_%s_%s_%s', $this->_correlationPrefix, $left, $right);
          }
        }
      }
    }
    return $result;
  }

  /**
   * Concatenate tokens of array with a string
   *
   * @param  array   the target tokens as array
   * @return string  concatenated string
   * @access public
   */
  function joinWords($tokens) {
    static $cache = array(), $size = 0;

    $result = '';
    $hash = $this->hashValue($tokens);

    if (isset($cache[$hash])) {
      $result = $cache[$hash];
    } else {
      $tokens = array_values($tokens);
      foreach ($tokens as $i => $token) {
        $h = $i - 1;
        $j = $i + 1;
        $pre = substr($token, 0, 1);

        if ($this->isAlphaNum($pre) || $pre === '$') {
          if (isset($tokens[$h])) {
            $lastchar = substr($tokens[$h], -1);
            if ($this->isAlphaNum($lastchar)) {
              $tokens[$h] .= ' ';
            }
          }
        } else if ($token === '+' || $token === '-') {
          $tokens[$i] = ' ' . $token . ' ';
        }
      }

      $result = trim(implode('', $tokens));
      if ($size < 0x1000 && strlen($result) < 0x100) {
        $cache[$hash] = $result;
        $size++;
      }
    }

    return $result;
  }

  /**
   * @access private
   */
  function joinCrossTable(&$row1, &$row2, $row1name, $row2name, $expr = true) {
    $result = array();

    $row1_count = count($row1);
    $row2_count = count($row2);

    if ($expr === true) {
      for ($i = 0; $i < $row2_count; $i++) {
        for ($j = 0; $j < $row1_count; $j++) {
          $result[] = $row2[$i] + $row1[$j];
        }
      }
    } else {
      $this->fixJoinCrossExpr($expr, $row1name, $row2name);

      $arg = array();
      $arg[$row1name] = array();
      $arg[$row2name] = array();
      $arg_row1name = & $arg[$row1name];
      $arg_row2name = & $arg[$row2name];
      $first = true;

      for ($i = 0; $i < $row2_count; $i++) {
        $arg_row2name = $row2[$i];

        for ($j = 0; $j < $row1_count; $j++) {
          $arg_row1name = $row1[$j];

          if ($first) {
            if ($this->safeExpr($arg, $expr)) {
              $result[] = $row2[$i] + $row1[$j];
            }

            if ($this->hasError()) {
              $result = array();
              $row1 = array();
              $row2 = array();
              break 2;
            }
            $first = false;
          } else {
            if ($this->expr($arg, $expr)) {
              $result[] = $row2[$i] + $row1[$j];
            }
          }
        }
      }
      unset($arg_row1name, $arg_row2name);
    }

    return $result;
  }

  /**
   * @access private
   */
  function fixJoinCrossExpr(&$expr, $row1name, $row2name) {
    if ($expr != null && strpos($expr, $row1name) === false) {
      $row_keys = array(
        $row1name => 1,
        $row2name => 2
      );
      $tokens = $this->splitSyntax($expr);
      $tokens = array_values($tokens);
      $length = count($tokens);

      for ($i = 0; $i < $length; $i++) {
        $token = $this->getValue($tokens, $i);
        $next = $this->getValue($tokens, $i + 1);
        switch ($token) {
          case '$':
            if ($next != null && !array_key_exists($next, $row_keys)) {
              $tokens[$i + 1] = $row1name;
              $i++;
            }
            break;
          default:
            break;
        }
      }
      $expr = $this->joinWords($tokens);
    }
  }

  /**
   * @access private
   */
  function joinLeftTable(&$row1, &$row2, $row1name, $row2name, $using, $isleft, $expr = true) {
    $result = array();
    $expr = $this->optimizeExpr($expr);

    if ($expr === false) {
      if (!empty($row1) && is_array($row1) && is_array(reset($row1))) {
        $keys = array_keys(reset($row1));
        for ($i = 0, $c = count($row1); $i < $c; $i++) {
          foreach ($keys as $key) {
            $result[$i][$key] = null;
          }
        }
      }
    } else {
      if ($isleft) {
        $cross = $this->joinCrossTable($row1, $row2, $row1name, $row2name, $expr);
      } else {
        $cross = $this->joinCrossTable($row2, $row1, $row2name, $row1name, $expr);
      }

      if ($expr === true) {
        $result = array_splice($cross, 0);
      } else {
        if (!empty($row1) && is_array($row1) && !empty($row2) && is_array($row2)) {
          $row1_count = count($row1);
          $row2_count = count($row2);
          $cross_count = count($cross);
          $max_count = $cross_count;
          if ($max_count < $row1_count) {
            $max_count = $row1_count;
          }

          $cross_keys = array();
          if (isset($cross[0]) && is_array($cross[0])) {
            foreach ($cross[0] as $key => $val) {
              $cross_keys[$key] = null;
            }
          }

          $reset = reset($row1);
          if (!is_array($reset)) {
            $this->pushError('illegal record(%s) on JOIN', $reset);
          } else {
            $row1_keys = array_keys($reset);
            $reset = reset($row2);

            if (!is_array($reset)) {
              $this->pushError('illegal record(%s) on JOIN', $reset);
            } else {
              $row2_keys = array_keys($reset);
              $arg = array();

              if (!empty($using)) {
                if (!is_array($using)) {
                  $using = array();
                } else {
                  $using = array_flip(array_filter($using, 'is_string'));
                }
              }

              if (empty($cross_keys)) {
                $join_keys = array_merge($row1_keys, $row2_keys);
                $join_keys = array_unique($join_keys);
              } else {
                $join_keys = array_keys($cross_keys);
              }

              for ($i = 0, $j = 0; $i < $max_count; $i++) {
                $new_row = array();

                if ($i < $cross_count) {
                  $new_row = $cross[$j];
                  $cross[$j] = null;
                  $j++;
                } else {
                  $row = $row1[$i];
                  $diff = $cross_keys;
                  if (isset($row2[$i])) {
                    $diff = $row2[$i];
                  }

                  if (empty($using)) {
                    $pad = $row;
                    foreach ($row2_keys as $key) {
                      if (array_key_exists($key, $pad)) {
                        $pad[$key] = null;
                      }
                    }
                    //TODO(?): Contradiction of the order of column
                    $new_row = $pad + $diff;
                  } else {
                    $new_row = $row + $diff;
                  }
                }

                $join_row = array();
                foreach ($join_keys as $key) {
                  if (array_key_exists($key, $new_row)) {
                    $join_row[$key] = $new_row[$key];
                  } else {
                    $join_row[$key] = null;
                  }
                }
                $result[] = $join_row;
              }
            }
          }
        }
      }
      unset($cross);
    }

    return $result;
  }

  /**
   * @access private
   */
  function appendCorrelationPairs(&$rows, $table_name) {
    if (is_array($rows)) {
      $row_count = count($rows);

      for ($i = 0; $i < $row_count; $i++) {
        foreach ($rows[$i] as $col => $val) {
          $column_name = sprintf('%s.%s', $table_name, $col);
          $rows[$i][$column_name] = $val;
        }
      }
    }
  }

  /**
   * @return void
   * @access private
   */
  function joinTables(&$rows, $info = array(), $expr = true) {
    $length = count($rows);
    if ($length < 1 || $length !== count($info)) {
      //$this->pushError('The numbers of tables and rows are discrepancy on JOIN');
      $rows = array();
    } else {
      $info = array_values($info);
      for ($i = 0; $i < $length; $i++) {
        $table_name = $this->getValue($info[$i], 'table_name');
        $table_as = $this->getValue($info[$i], 'table_as');
        $join_type = $this->getValue($info[$i], 'join_type', 'undefined');
        $join_expr = $this->getValue($info[$i], 'join_expr', false);
        $using = $this->getValue($info[$i], 'using', array());
        $tname = $this->encodeKey($table_name);

        if (!isset($rows[$tname])) {
          $this->pushError('Table is not exists(%s)', $table_name);
          $rows = array();
          break;
        }

        if ($this->hasError()) {
          $rows = array();
          break;
        }

        if ($i === 0) {
          $base_row = $rows[$tname];
          $base_row_name = $table_name;
          $base_count = count($base_row);
          $this->tableName = $tname;
          $this->appendCorrelationPairs($base_row, $base_row_name);
          continue;
        } else {
          $row = $rows[$tname];
          $this->appendCorrelationPairs($row, $table_name);
        }

        $join_type = strtolower($join_type);
        switch ($join_type) {
          case 'cross':
          case 'inner':
          case 'join':
            $base_row = $this->joinCrossTable($base_row, $row,
              $base_row_name, $table_name, $join_expr);
            break;
          case 'full':
          case 'left':
          case 'natural':
            $base_row = $this->joinLeftTable($base_row, $row,
              $base_row_name, $table_name, $using, true, $join_expr);
            if ($join_type !== 'full') {
              break;
            }
          case 'right':
            $base_row = $this->joinLeftTable($row, $base_row,
              $table_name, $base_row_name, $using, false, $join_expr);
            break;
          default:
            $this->pushError('Not supported JOIN type(%s)', $join_type);
            $rows = array();
            break 2;
        }
      }

      if (!empty($base_row)) {
        $tmp = $base_row;
        unset($base_row);
        $rows = $tmp;
        unset($tmp);
      }

      unset($base_row, $tmp);
      $this->applyJoinExpr($rows, $expr);
    }
  }

  /**
   * @access private
   */
  function applyJoinExpr(&$rows, $expr = true) {
    if ($this->hasError()) {
      $rows = array();
    } else if ($expr !== true) {
      if (!empty($rows) && is_array($rows)) {
        if (!empty($this->_extraJoinExpr) && is_string($expr) && $expr != null) {
          $extra_expr = $this->_extraJoinExpr;
          if (is_array($extra_expr)) {
            $extra_expr = $this->toOneArray($extra_expr);
            $extra_expr = $this->joinWords($extra_expr);
            $this->removeValidMarks($expr);
            if (strpos($expr, $extra_expr) === 0) {
              $expr = substr($expr, strlen($extra_expr));
              $expr = trim($expr, '&|');
            }
            $this->addParsedMark($expr);
            $this->addValidMarks($expr);
          }
        }

        $this->_extraJoinExpr = array();
        $rows = array_values($rows);
        $length = count($rows);
        $first = true;

        for ($i = 0; $i < $length; $i++) {
          $valid_row = array();
          foreach ($rows[$i] as $key => $val) {
            $valid_row[$key] = $val;
            $left_key = $this->toCorrelationName($key);
            $valid_row[$left_key] = $val;
          }

          if ($first) {
            $first = false;
            if (!$this->safeExpr($valid_row, $expr)) {
              unset($rows[$i]);
            }

            if ($this->hasError()) {
              $rows = array();
              break;
            }
          } else {
            if (!$this->expr($valid_row, $expr)) {
              unset($rows[$i]);
            }
          }
        }
        $rows = array_values($rows);
      }
    }
  }

  /**
   * Builds the parsed tokens, and passes each methods
   *
   * @param  array   parsed tokens
   * @param  string  the type of query e.g. "select"
   * @return mixed   results of executed, or false on error
   * @access private
   */
  function buildQuery($parsed, $type) {
    $result = false;
    $this->_useQuery = true;

    switch (strtolower($type)) {
      case 'select':
        $result = $this->buildSelectQuery($parsed);
        break;
      case 'update':
        $result = $this->buildUpdateQuery($parsed);
        break;
      case 'delete':
        $result = $this->buildDeleteQuery($parsed);
        break;
      case 'insert':
      case 'replace':
        $result = $this->buildInsertQuery($parsed);
        break;
      case 'create':
        $result = $this->buildCreateQuery($parsed);
        break;
      case 'drop':
        $result = $this->buildDropQuery($parsed);
        break;
      case 'vacuum':
        $result = $this->vacuum();
        break;
      case 'describe':
      case 'desc':
        $result = $this->buildDescribeQuery($parsed);
        break;
      case 'begin':
      case 'start':
      case 'commit':
      case 'end':
      case 'rollback':
        $result = $this->transaction($type);
        break;
      case 'alter':
      default:
        $this->pushError('Not supported SQL syntax(%s)', $type);
        break;
    }

    $this->_useQuery = null;
    return $result;
  }

  /**
   * Builds the parsed tokens for CREATE TABLE/DATABASE
   *
   * @param  array   parsed tokens
   * @return mixed   results of executed, or false on error
   * @access private
   */
  function buildCreateQuery($parsed) {
    $result = false;
    $args = array(
      'table' => array(
        'table' => null,
        'fields' => array()
      ),
      'database' => array(
        'database' => null
      )
    );

    $type = null;
    if (isset($parsed['table'])) {
      $type = 'table';
    } else if (isset($parsed['database'])) {
      $type = 'database';
    }

    switch ($type) {
      case 'table':
        $args = $this->parseCreateTableQuery($parsed[$type], true);
        if (!$this->hasError()) {
          if (is_array($args) && isset($args['table'], $args['fields'])) {
            $primary = $this->getValue($args, 'primary');
            $result = $this->createTable($args['table'], $args['fields'], $primary);
          }
        }
        break;
      case 'database':
        $path = $this->parseCreateDatabaseQuery($parsed[$type]);
        if (!$this->hasError() && $path != null) {
          $result = $this->createDatabase($path);
        }
        break;
      default:
        $this->pushError('Not supported SQL syntax(%s)', $type);
        break;
    }

    return $result;
  }

  /**
   * Builds the parsed tokens for DROP TABLE/DATABASE
   *
   * @param  array   parsed tokens
   * @return mixed   results of executed, or false on error
   * @access private
   */
  function buildDropQuery($parsed) {
    $result = false;
    $args = array(
      'table' => array(
        'table' => null
      ),
      'database' => array(
        'database' => null
      )
    );

    $type = null;
    if (isset($parsed['table'])) {
      $type = 'table';
    } else if (isset($parsed['database'])) {
      $type = 'database';
    }

    switch ($type) {
      case 'table':
        $table = $this->parseDropQuery($parsed[$type]);
        if (!$this->hasError()) {
          if (is_string($table) && $table != null) {
            $result = $this->dropTable($table);
          }
        }
        break;
      case 'database':
        $dbname = $this->parseDropQuery($parsed[$type]);
        if (!$this->hasError()) {
          if (is_string($dbname) && $dbname != null) {
            $result = $this->dropDatabase($dbname);
          }
        }
        break;
      default:
        $this->pushError('Not supported SQL syntax(%s)', $type);
        break;
    }

    return $result;
  }

  /**
   * Builds the parsed tokens for INSERT
   *
   * @param  array   parsed tokens
   * @return mixed   results of executed, or false on error
   * @access private
   */
  function buildInsertQuery($parsed) {
    $result = false;
    $args = array(
      'table' => null,
      'rows' => array()
    );
    $cols = array();
    ksort($parsed);

    foreach ($parsed as $key => $val) {
      switch ($key) {
        case 'into':
          $args['table'] = $this->assignNames($val);
          break;
        case 'cols':
          $cols = $val;
          break;
        case 'values':
          $values = array(
            'cols' => $cols,
            'values' => $val
          );
          $args['rows'] = $this->parseInsertValues($values);
          $this->_execExpr = true;
          break;
        case 'select':
          if (is_array($val)) {
            array_unshift($val, $key);
          }
          $args['rows'] = $this->buildInsertSelectSubQuery($val, $cols);
          break;
        case 'set':
        default:
          $this->pushError('Not supported SQL syntax(%s)', $key);
          break;
      }
    }

    if (!$this->hasError()) {
      switch ($this->lastMethod) {
        case 'insert':
          $result = $this->insert($args['table'], $args['rows']);
          break;
        case 'replace':
          $result = $this->replace($args['table'], $args['rows']);
          break;
        default:
          $this->pushError('Not supported SQL syntax(%s)', $this->lastMethod);
          break;
      }
    }

    $this->_execExpr = false;
    return $result;
  }

  /**
   * Builds the parsed tokens for INSERT-SELECT sub-query
   *
   * @param  array   parsed tokens as SELECT clause
   * @param  array   parsed tokens as columns-list
   * @return array   array which was executed as SELECT sub-query
   * @access private
   */
  function buildInsertSelectSubQuery($select, $cols) {
    $result = array();

    if (!$this->hasError()) {
      if (empty($cols) || !is_array($cols)) {
        $this->pushError('Cannot execute sub-query. expect columns-list');
      } else if (empty($select) || !is_array($select)) {
        $this->pushError('Cannot execute sub-query. expect SELECT clause');
      } else {
        $values = array(
          'cols' => $cols,
          'values' => $cols
        );
        $columns = $this->parseInsertValues($values);

        if (!$this->hasError()) {
          if (is_array($columns) && reset($columns) === key($columns)) {
            $columns = array_values($columns);
            $parsed_select = $this->parseSelectQuery($select);

            if (!$this->hasError() && is_array($parsed_select)) {
              $stmt = $this->buildSelectQuery($parsed_select);
              if (!$this->hasError()) {
                $rows = null;

                if ($this->isStatementObject($stmt)) {
                  $rows = $stmt->fetchAll('assoc');
                } else if (is_array($stmt) && $this->isAssoc(reset($stmt))) {
                  $rows = $stmt;
                } else {
                  $this->pushError('Failed to get records on INSERT-SELECT');
                }

                if (!$this->hasError() && is_array($rows)) {
                  $rows_count = count($rows);

                  for ($i = 0; $i < $rows_count; $i++) {
                    $new_row = array();
                    $values = array_values($rows[$i]);

                    foreach ($columns as $index => $column) {
                      if (array_key_exists($index, $values)) {
                        $new_row[$column] = $values[$index];
                      }
                    }

                    if (empty($new_row)) {
                      $this->pushError('Column count does not match value count on INSERT-SELECT');
                      $rows = array();
                      break;
                    }
                    $rows[$i] = $new_row;
                  }
                  $result = array_splice($rows, 0);
                  unset($rows);
                }
              }
              $stmt = null;
              unset($stmt);
            }
          }
        }
      }
    }

    return $result;
  }

  /**
   * Builds the parsed tokens for DELETE
   *
   * @param  array   parsed tokens
   * @return mixed   results of executed, or false on error
   * @access private
   */
  function buildDeleteQuery($parsed) {
    $result = false;
    $args = array(
      'table' => null,
      'expr' => false
    );

    foreach ($parsed as $key => $val) {
      switch ($key) {
        case 'from':
          $tablename = $this->assignNames($val);
          if ($this->getMeta($tablename)) {
            $this->tableName = $this->encodeKey($tablename);
          }
          $args['table'] = $tablename;
          break;
        case 'where':
          $args['expr'] = $this->validExpr($val);
          break;
        default:
          $this->pushError('Not supported SQL syntax(%s)', $key);
          break;
      }
    }

    if (!$this->hasError()) {
      $result = $this->delete($args['table'], $args['expr']);
    }

    return $result;
  }

  /**
   * Builds the parsed tokens for UPDATE
   *
   * @param  array   parsed tokens
   * @return mixed   results of executed, or false on error
   * @access private
   */
  function buildUpdateQuery($parsed) {
    $result = false;
    $args = array(
      'table' => null,
      'row' => array(),
      'expr' => true
    );

    foreach ($parsed as $key => $val) {
      switch ($key) {
        case 'update':
          $tablename = $this->assignNames($val);
          if ($this->getMeta($tablename)) {
            $this->tableName = $this->encodeKey($tablename);
          }
          $args['table'] = $tablename;
          break;
        case 'where':
          $args['expr'] = $this->validExpr($val);
          break;
        case 'set':
          $args['row'] = $this->parseUpdateSet($val);
          $this->_execExpr = true;
          break;
        default:
          $this->pushError('Not supported SQL syntax(%s)', $key);
          break;
      }
    }

    if (!$this->hasError()) {
      $result = $this->update($args['table'], $args['row'], $args['expr']);
    }
    $this->_execExpr = false;
    return $result;
  }

  /**
   * Builds the parsed tokens for SELECT
   *
   * @param  array   parsed tokens
   * @return mixed   results of executed, or false on error
   * @access private
   */
  function buildSelectQuery($parsed) {
    $result = false;
    $this->_subSelectJoinInfo = array();
    $this->_subSelectJoinUniqueNames = array();
    $args = $this->getSelectDefaultArguments();
    $multi = false;
    $sub_select = false;
    $subsets = array();

    foreach ($parsed as $key => $val) {
      switch ($key) {
        case 'select':
          $args[$key] = $this->joinWords($val);
          break;
        case 'from':
          $tables = array();
          $from = $this->joinWords($val);
          $table_name = 'table_name';
          $sub_select = $this->isSubSelectFrom($from);

          if ($sub_select) {
            $this->tableName = '';
            $this->applyAliasNames($from, $args['select']);
            $aliases = $this->getTableAliasNames();

            if (is_array($aliases)) {
              $this->tableName = $this->encodeKey(reset($aliases));
            }

            if ($this->isMultipleSelect($from)) {
              $this->_subSelectJoinInfo = $this->parseJoinTables($val);
              if ($this->hasError()) {
                break 2;
              } else if (empty($this->_subSelectJoinUniqueNames)) {
                $this->pushError('Failed to compile on sub-query using JOIN');
                break 2;
              }
              $args[$key] = $this->execMultiSubQueryFrom($from);
              $multi = true;
            } else {
              $args[$key] = $this->execSubQueryFrom($from);
            }
          } else {
            $this->tableName = $this->encodeKey($from);
            if ($this->isMultipleSelect($from)) {
              $tables = $this->parseJoinTables($val);
              if (isset($tables[0][$table_name])) {
                $this->tableName = $this->encodeKey($tables[0][$table_name]);
              }
              $args[$key] = $tables;
              $multi = true;
            } else {
              $tables = $this->parseFromClause($val);
              if (isset($tables[$table_name])) {
                $this->tableName = $this->encodeKey($tables[$table_name]);
                $this->appendAliasToken($val);
                $from = $this->joinWords($val);
              }
              $args[$key] = $from;
            }
          }
          unset($tables, $from);
          break;
        case 'where':
          if (!empty($this->tableName) && empty($this->meta[$this->tableName])) {
            $this->getMeta();
          }

          if ($multi) {
            $this->_onMultiSelect = true;
          }

          if (!$sub_select) {
            $this->applyAliasNames($args['from'], $args['select']);
          }
          $args[$key] = $this->validExpr($val);
          $this->_onMultiSelect = null;
          break;
        case 'group':
          $args[$key] = $this->joinWords($val);
          break;
        case 'having':
          $args[$key] = $this->joinWords($val);
          break;
        case 'order':
          $args[$key] = $this->parseOrderByTokens($val);
          break;
        case 'limit':
          $args[$key] = $this->parseLimitTokens($val);
          break;
        case 'union':
        case 'union_all':
        case 'intersect':
        case 'intersect_all':
        case 'except':
        case 'except_all':
          if (empty($val) || $val === array(array())) {
            $error = strtoupper(strtr($key, '_', ' '));
            $this->pushError('Syntax error, expect(%s)', $error);
          }
          $subsets[$key] = $val;
          break;
        default:
          $this->pushError('Not supported SQL syntax(%s)', $key);
          break 2;
      }
    }

    if ($this->hasError()) {
      $result = array();
    } else {
      if ($this->_fromSubSelect) {
        if ($multi) {
          $result = $this->multiSubSelect($args);
        } else {
          $result = $this->subSelect($args);
        }
      } else if ($args['from'] === null) {
        $result = $this->selectDual($args['select']);
      } else if ($multi) {
        $result = $this->multiSelect($args);
      } else {
        $result = $this->select($args);
      }

      if (!empty($subsets)) {
        $this->_unionColNames = array();
        $result = $this->buildSelectUnionQuery($result, $args, $subsets);
        $this->_unionColNames = array();
      }
    }

    $this->_subSelectJoinInfo = array();
    $this->_subSelectJoinUniqueNames = array();
    return $result;
  }

  /**
   * Builds the parsed tokens for SELECT (using UNION: compound-operator)
   *
   * @param  array   parsed tokens as UNION clause same as SELECT tokens
   * @return mixed   executed result-set, or FALSE on error
   * @access private
   */
  function buildSelectUnionQuery($base_stmt, $args, $subsets) {
    $result = array();

    if (!empty($subsets) && is_array($subsets)) {
      $rows = array();
      if (!$this->isStatementObject($base_stmt)) {
        if (!is_array($base_stmt)) {
          $this->pushError('Invalid result-set on SELECT (using UNION)');
        } else {
          $rows = $base_stmt;
        }
      } else {
        $rows = $base_stmt->fetchAll('assoc');
      }

      $base_stmt = null;
      unset($base_stmt);

      if (!$this->hasError()) {
        foreach ($subsets as $type => $subset) {
          foreach ($subset as $parsed_tokens) {
            $col_names = $this->getValue($args, 'select');
            if ($col_names != null) {
              $col_names = $this->parseColumns($col_names, 'columns');
            }

            if (is_array($col_names)) {
              foreach ($col_names as $col_name) {
                $this->_unionColNames[$col_name] = 1;
              }
            } else if (is_string($col_names)) {
              $this->_unionColNames[$col_names] = 1;
            }

            $this->_onUnionSelect = true;
            $stmt = $this->buildSelectQuery($parsed_tokens);
            $this->_onUnionSelect = null;

            if ($this->hasError()) {
              break;
            } else {
              if (!$this->isStatementObject($stmt)) {
                if (!is_array($stmt)) {
                  $this->pushError('Failed to compound rows on UNION');
                  break;
                } else {
                  $union_rows = $stmt;
                }
              } else {
                $union_rows = $stmt->fetchAll('assoc');
              }

              switch ($type) {
                case 'union':
                  $rows = $this->unionAll($rows, $union_rows, $col_names);
                  $this->applyDistinct($rows);
                  break;
                case 'union_all':
                  $rows = $this->unionAll($rows, $union_rows, $col_names);
                  break;
                case 'intersect':
                  $rows = $this->intersectAll($rows, $union_rows, $col_names);
                  $this->applyDistinct($rows);
                  break;
                case 'intersect_all':
                  $rows = $this->intersectAll($rows, $union_rows, $col_names);
                  break;
                case 'except':
                  $rows = $this->exceptAll($rows, $union_rows, $col_names);
                  $this->applyDistinct($rows);
                  break;
                case 'except_all':
                  $rows = $this->exceptAll($rows, $union_rows, $col_names);
                  break;
                default:
                  $this->pushError('Not supported syntax(%s)', $type);
                  break 2;
              }
            }
            $stmt = null;
            unset($stmt);
          }

          if ($this->hasError()) {
            break;
          }
        }

        if ($this->hasError()) {
          $rows = array();
        }

        $this->removeCorrelationName($rows);
        $args['from'] = $rows;
        $args['where'] = true;
        $this->_onUnionSelect = true;
        $result = $this->subSelect($args);
        $this->_onUnionSelect = null;
      }
    }

    if (is_array($result) && empty($this->_fromStatement)) {
      $result = new Posql_Statement($this, $result, $this->getLastQuery());
    }
    return $result;
  }

  /**
   * Builds the parsed tokens for DESCRIBE command
   *
   * @param  array   parsed tokens
   * @return mixed   results of executed, or FALSE on error
   * @access private
   */
  function buildDescribeQuery($parsed) {
    $result = false;
    $args = array('table' => null);

    foreach ($parsed as $key => $val) {
      switch ($key) {
        case 'desc':
        case 'describe':
          $table_name = $this->assignNames($val);
          $args['table'] = $table_name;
          break;
        default:
          $this->pushError('Not supported SQL syntax(%s)', $key);
          break;
      }
    }

    if ($this->hasError()) {
      $result = array();
    } else {
      $result = $this->describe($args['table']);
    }
    return $result;
  }

  /**
   * Combine two query expressions into a single result set.
   * If ALL is specified, duplicate rows returned by
   * union expression are retained.
   *
   * @param  array   based rows
   * @param  array   subject rows to combine
   * @param  array   column names which is used for result-set
   * @access public
   */
  function unionAll($base_rows, $union_rows, $col_names = array()) {
    $result = array();

    if (!is_array($base_rows) || !is_array($union_rows)) {
      $this->pushError('Invalid arguments on UNION');
    } else {
      if (empty($col_names) && is_array(reset($base_rows))) {
        $col_names = array_keys(reset($base_rows));
      }

      if (empty($col_names)) {
        $this->pushError('Failed to compound rows on UNION');
      } else {
        if (is_string($col_names)) {
          $col_names = array($col_names);
        }
        $col_length = count($col_names);
        $union_rows = array_values($union_rows);
        $union_row_count = count($union_rows);
        $key = 0;

        while (--$union_row_count >= 0) {
          $row = $union_rows[$key];
          $union_rows[$key] = null;
          $base_rows[] = $row;
          $key++;
        }

        unset($union_rows);
        $base_rows = array_values($base_rows);
        $base_row_count = count($base_rows);
        $key = 0;

        while (--$base_row_count >= 0) {
          $row = $base_rows[$key];
          $base_rows[$key] = null;
          $subset_row = array();

          if (is_array($row) && count($row) === $col_length) {
            foreach (array_values($row) as $i => $val) {
              $subset_row[ $col_names[$i] ] = $val;
            }
            $result[] = $subset_row;
          } else {
            $this->pushError('In UNION, two rows should be the same numbers');
            $result = array();
            break;
          }
          $key++;
        }
      }
    }
    return $result;
  }

  /**
   * Evaluates the output of two query expressions and
   * returns only the rows common to each.
   *
   * @param  array   based rows
   * @param  array   subject rows to intersected
   * @param  array   column names which is used for result-set
   * @access public
   */
  function intersectAll($base_rows, $intersect_rows, $col_names = array()) {
    $result = array();

    if (!is_array($base_rows) || !is_array($intersect_rows)) {
      $this->pushError('Invalid arguments on INTERSECT');
    } else {
      if (empty($col_names) && is_array(reset($base_rows))) {
        $col_names = array_keys(reset($base_rows));
      }

      if (empty($col_names)) {
        $this->pushError('Failed to compound rows on INTERSECT');
      } else {
        if (is_string($col_names)) {
          $col_names = array($col_names);
        }
        $col_length = count($col_names);
        $base_row_count   = count($base_rows);
        $intersect_row_count = count($intersect_rows);
        for ($i = 0; $i < $base_row_count; $i++) {
          $subset_row = array();

          if (isset($base_rows[$i]) && is_array($base_rows[$i])
            && count($base_rows[$i]) === $col_length) {
            $index = -1;
            $base_values = array_values($base_rows[$i]);
            for ($j = 0; $j < $intersect_row_count; $j++) {
              if ($base_values === array_values($intersect_rows[$j])) {
                $index = $i;
                break;
              }
            }

            if (array_key_exists($index, $base_rows)) {
              foreach ($base_values as $idx => $val) {
                $subset_row[ $col_names[$idx] ] = $val;
              }
              $result[] = $subset_row;
            }
          } else {
            $this->pushError('In INTERSECT, two rows should be the same numbers');
            $result = array();
            break;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Evaluates the output of two query expressions and
   * returns the difference between the results.
   *
   * @param  array   based rows
   * @param  array   subject rows to combine
   * @param  array   column names which is used for result-set
   * @access public
   */
  function exceptAll($base_rows, $except_rows, $col_names = array()) {
    $result = array();
    if (!is_array($base_rows) || !is_array($except_rows)) {
      $this->pushError('Invalid arguments on EXCEPT');
    } else {
      if (empty($col_names) && is_array(reset($base_rows))) {
        $col_names = array_keys(reset($base_rows));
      }

      if (empty($col_names)) {
        $this->pushError('Failed to compound rows on EXCEPT');
      } else {
        if (is_string($col_names)) {
          $col_names = array($col_names);
        }

        $col_length = count($col_names);
        $base_row_count = count($base_rows);
        $except_row_count = count($except_rows);
        for ($i = 0; $i < $base_row_count; $i++) {
          $subset_row = array();
          if (isset($base_rows[$i]) && is_array($base_rows[$i])
            && count($base_rows[$i]) === $col_length) {
            $index = -1;
            $base_values = array_values($base_rows[$i]);
            for ($j = 0; $j < $except_row_count; $j++) {
              if ($base_values !== array_values($except_rows[$j])) {
                $index = $i;
                break;
              }
            }

            if (array_key_exists($index, $base_rows)) {
              foreach ($base_values as $idx => $val) {
                $subset_row[ $col_names[$idx] ] = $val;
              }
              $result[] = $subset_row;
            }
          } else {
            $this->pushError('In EXCEPT, two rows should be the same numbers');
            $result = array();
            break;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Executes all the aggregate functions,
   *  and append the aggregate results to the rows of the result
   *
   * @param  array   result records
   * @param  array   results of all aggregate functions
   * @return array   array which appended the aggregate results to rows
   * @access private
   */
  function appendAggregateResult(&$rows, $aggregates = array()) {
    $result = array();

    if (!$this->hasError()) {
      if (is_array($rows) && is_array(reset($rows))
        && is_array($aggregates) && is_array(reset($aggregates))) {
        $rows_reset = reset($rows);
        $reset = reset($aggregates);
        $check_col = key($reset);
        $agg_count = count($aggregates);
        if (count($rows) !== $agg_count || !array_key_exists($check_col, $rows_reset)) {
          $this->pushError('Failed to execute the expression on HAVING');
        } else {
          $agg_appends = array();
          $i = 0;

          while (--$agg_count >= 0) {
            $agg_shift = array_shift($aggregates);
            if (is_array($agg_shift)) {
              foreach ($agg_shift as $agg_col => $agg_pairs) {
                if (is_string($agg_col) && is_array($agg_pairs)) {
                  foreach ($agg_pairs as $func => $val) {
                    if (is_string($func)) {
                      $func = strtoupper($func);
                      $key = null;

                      if ($func === 'COUNT_ALL') {
                        $key = sprintf('COUNT(*)#%08u', $i);
                      } else {
                        $key = sprintf('%s(%s)#%08u', $func, $agg_col, $i);
                      }

                      $rows[$i][$key] = $val;
                      $agg_appends[$key] = $val;
                    }
                  }
                }
              }
            }
            $i++;
          }
          $result = $agg_appends;
        }
      }
    }
    return $result;
  }

  /**
   * A handle using the select syntax for to select the columns
   *
   * @param  array   a record of target
   * @param  mixed   selected keys as columns
   * @param  array   results of aggregate functions
   * @param  string  the name of column which will be a group
   * @return void
   * @access private
   */
  function assignCols(&$rows, $cols, $aggregates = null, $group = null, $order = null) {
    $is_distinct = false;

    if ($this->isDistinct($cols)) {
      $is_distinct = true;
      $this->removeDistinctToken($cols);
    } else if ($this->isSelectListAllClause($cols)) {
      $this->removeSelectListAllClauseToken($cols);
    }

    $parsed = $this->parseColumns($cols);
    if (!empty($parsed) && is_array($parsed) && isset($parsed['cols'])) {
      $cols = $parsed['cols'];
      $cols_order = $cols;

      if (!empty($parsed['func']) && is_array($parsed['func'])) {
        $fn = $parsed['func'];
        if ($aggregates == null) {
          $this->execAllAggregateFunction($rows, $fn);
        } else {
          foreach ($fn as $name => $col) {
            $this->execAggregateFunction($rows, $name, $col, $aggregates);
          }
        }
        $funcs = $fn;
      } else {
        $funcs = array();
      }

      $cols_order = $this->assignColumnsOrder($cols_order, $funcs);
      $this->orderColumns($rows, $cols_order);

      if (!empty($parsed['as']) && is_array($parsed['as'])) {
        $as = $parsed['as'];
        $as_mod = $this->replaceAliasNames($as);

        if (!empty($as_mod) && is_array($as_mod) && $as !== $as_mod) {
          $as = $as + $as_mod;
        }

        foreach ($as as $field_name => $alias_name) {
          $restored = $this->restoreTableAliasName($field_name);
          $as[$restored] = $alias_name;
        }

        $row_count = count($rows);
        for ($i = 0; $i < $row_count; $i++) {
          foreach ($as as $org => $key) {
            if (array_key_exists($org, $rows[$i])) {
              $tmp = $rows[$i][$org];
              unset($rows[$i][$org]);
              $rows[$i][$key] = $tmp;
            }
          }
        }

        if (!$this->hasError()) {
          $cols = $cols_order;
          $cols_order = array();
          foreach ($cols as $key) {
            if (array_key_exists($key, $as)) {
              $cols_order[] = $as[$key];
            } else {
              $cols_order[] = $key;
            }
          }
          $this->orderColumns($rows, $cols_order);
        }
      }

      if ($this->hasError()) {
        $rows = array();
      } else {
        if (count($rows) > 1 && $order != null) {
          $this->orderBy($rows, $order);
        }
        $this->diffColumns($rows, $cols_order);
        if ($is_distinct) {
          $this->applyDistinct($rows);
        }

        if ($this->_onMultiSelect) {
          $this->removeCorrelationName($rows);
        }
        $this->removeDuplicateRows($rows, $cols_order, $parsed, $group);
      }
    }
  }

  /**
   * A handle using the select syntax as "SELECT COUNT(*)"
   *
   * @param  array   a record of target
   * @param  number  the result value of COUNT function
   * @param  mixed   selected keys as columns
   * @return void
   * @access private
   */
  function assignColsBySimpleCount(&$rows, $count, $cols) {
    $rows = array();
    $parsed = $this->parseColumns($cols);

    if (!empty($parsed) && is_array($parsed) && isset($parsed['cols'])) {
      $cols = $parsed['cols'];
      $func = 'func';

      if (!empty($parsed[$func]) && is_array($parsed[$func]) && is_string(reset($parsed[$func]))) {
        $func_col = reset($parsed[$func]);
        $func_name = key($parsed[$func]);
        $rows = $this->execSimpleCountFunction($count, $func_name, $func_col);

        if (!empty($rows) && isset($rows[0]) && is_array($rows)) {
          $as = 'as';
          if (!empty($parsed[$as]) && is_array($parsed[$as])) {
            $aliases = $parsed[$as];
            $as_mod = $this->replaceAliasNames($aliases);
            if (!empty($as_mod) && is_array($as_mod) && $aliases !== $as_mod) {
              $aliases = $aliases + $as_mod;
            }

            $row_count = count($rows);
            for ($i = 0; $i < $row_count; $i++) {
              foreach ($aliases as $org => $key) {
                if (isset($rows[$i][$org])) {
                  $tmp = $rows[$i][$org];
                  unset($rows[$i][$org]);
                  $rows[$i][$key] = $tmp;
                }
              }

              if ($i > 1) {
                $this->pushError('Failed to assign the alias name');
                $rows = array();
                break;
              }
            }
          }
        }
      } else {
        $this->pushError('Failed to assign the column');
      }
    }

    if ($this->hasError()) {
      $rows = array();
    }
  }

  /**
   * A handle using the select syntax for to select-list expression
   *
   * @param  array   a record of target
   * @param  mixed   selected keys as columns
   * @param  array   results of aggregate functions
   * @param  string  the name of column which will be a group
   * @return void
   * @access private
   */
  function assignDualCols(&$rows, $cols, $aggregates = null, $group = null) {
    $is_distinct = false;

    if ($this->isDistinct($cols)) {
      $is_distinct = true;
      $this->removeDistinctToken($cols);
    } else if ($this->isSelectListAllClause($cols)) {
      $this->removeSelectListAllClauseToken($cols);
    }

    $parsed = $this->parseColumns($cols);
    if (!empty($parsed) && is_array($parsed) && isset($parsed['cols'])) {
      $cols = $parsed['cols'];
      $cols_order = $this->assignColumnsOrder($cols);
      $this->orderColumns($rows, $cols_order);
      if (!empty($parsed['as']) && is_array($parsed['as'])) {
        $as = $parsed['as'];
        $as_mod = $this->replaceAliasNames($as);
        if (!empty($as_mod) && is_array($as_mod) && $as !== $as_mod) {
          $as = $as + $as_mod;
        }

        $count = count($rows);
        for ($i = 0; $i < $count; $i++) {
          foreach ($as as $org => $key) {
            if (isset($rows[$i][$org])) {
              $tmp = $rows[$i][$org];
              unset($rows[$i][$org]);
              $rows[$i][$key] = $tmp;
            }
          }
        }

        if (!$this->hasError()) {
          $cols = $cols_order;
          $cols_order = array();
          foreach ($cols as $key) {
            if (array_key_exists($key, $as)) {
              $cols_order[] = $as[$key];
            } else {
              $cols_order[] = $key;
            }
          }
          $this->orderColumns($rows, $cols_order);
        }
      }

      if ($this->hasError()) {
        $rows = array();
      } else {
        $this->diffColumns($rows, $cols_order);
        if ($is_distinct) {
          $this->applyDistinct($rows);
        }
        $this->removeDuplicateRows($rows, $cols_order, $parsed, $group);
      }
    }
  }

  /**
   * Remove duplicate rows
   *
   * Quote:
   *  If you use a group function in a statement containing no GROUP BY clause,
   *  it is equivalent to grouping on all rows.
   *
   * @access private
   */
  function removeDuplicateRows(&$rows, $cols_order, $parsed, $group = null) {
    $f = 'func';

    if (!empty($rows) && is_array($rows) && empty($group) && is_array($cols_order)) {
      if (count($cols_order) === 1 && !empty($parsed[$f])
        && count($parsed[$f]) === 1 && is_array(reset($rows))) {
        $length = count($rows);
        $values = array();
        $remove = true;

        for ($i = 0; $i < $length; $i++) {
          if (!empty($values)) {
            if ($rows[$i] === $values) {
              continue;
            } else {
              $remove = false;
              break;
            }
          }
          $values = $rows[$i];
        }

        if ($remove) {
          $rows = reset($rows);
          if (!array_key_exists(0, $rows)) {
            $rows = array($rows);
          }
        }
      }
    }
  }

  /**
   * @access private
   */
  function diffColumns(&$rows, $cols, $each = false) {
    if (!empty($rows) && is_array($rows) && is_array($cols)) {
      $reset = reset($rows);

      if (!empty($reset) && is_array($reset) && count($cols) < count($reset)) {
        $diff_keys = array_diff(array_keys($reset), $cols);
        $length = count($rows);
        for ($i = 0; $i < $length; $i++) {
          if ($each) {
            $diff_keys = array_diff(array_keys($rows[$i]), $cols);
          }
          foreach ($diff_keys as $key) {
            unset($rows[$i][$key]);
          }
        }
      }
    }
  }

  /**
   * @access private
   */
  function orderColumns(&$rows, $cols_order) {
    $result = false;
    if (!$this->hasError()) {
      if (!is_array($cols_order)) {
        $this->pushError('Cannot assign the columns on orderColumns(%s)',
          $cols_order);
      } else {
        if (!empty($this->_selectExprCols)) {
          $selcols = & $this->_selectExprCols;
        } else {
          $selcols = array();
        }

        $new_rows = array();
        $rows = array_values($rows);
        $count = count($rows);
        $i = 0;

        while (--$count >= 0) {
          $row = $rows[$i];
          $rows[$i] = null;
          foreach ($cols_order as $col) {
            if (array_key_exists($col, $selcols)) {
              $val = $this->execSelectExpr($row, $col);
              if ($this->hasError()) {
                $rows = array();
                break 2;
              }
              $new_rows[$i][$col] = $val;
            } else {
              if (array_key_exists($col, $row)) {
                $new_rows[$i][$col] = $row[$col];
              } else {
                $new_rows[$i][$col] = null;
              }
            }
          }
          $new_rows[$i] = $new_rows[$i] + $row;
          $i++;
        }

        if ($this->hasError()) {
          $rows = array();
        } else {
          $rows = $new_rows;
        }
        unset($selcols, $new_rows);
      }
    }
  }

  /**
   * @access private
   */
  function assignColumnsOrder($cols_order, $funcs = array()) {
    $result = false;

    $this->_selectExprCols = array();
    if ($this->_fromSubSelect) {
      $curmeta = $this->_subSelectMeta;
      if (!isset($this->tableName)) {
        $this->tableName = '';
      }
      $this->meta = array($this->tableName => $curmeta);
      $meta = $this->meta;
    } else if (empty($this->_isDualSelect)
      && empty($this->_onUnionSelect)) {
      if (empty($this->tableName)
        || empty($this->meta[$this->tableName])) {
        $this->pushError('Cannot load the meta data on assignColumnsOrder()');
      } else if (!is_array($cols_order)) {
        $this->pushError('Cannot assign the columns on assignColumnsOrder(%s)', $cols_order);
      }

      if ($this->hasError()) {
        $meta = array();
        $curmeta = array();
      } else {
        $meta = $this->meta;
        unset($meta[0]);
        $curmeta = $meta[$this->tableName];
      }
    } else if (!empty($this->_onUnionSelect)) {
      $meta = $this->meta;
      $curmeta = array();
      if (!empty($this->_unionColNames) && is_array($this->_unionColNames)) {
        $curmeta = $this->_unionColNames;
      }
    } else {
      $meta = array();
      $curmeta = array();
    }

    if (!$this->hasError()) {
      $cols_keys = array_keys($cols_order);
      $cols_order = array_values($cols_order);
      foreach ($cols_order as $i => $col) {
        if ($col === '*') {
          $mod_cols = array();
          foreach (array_keys($curmeta) as $mcol) {
            if (!array_key_exists($mcol, $cols_keys)) {
              $mod_cols[] = $mcol;
            }
          }

          if (!empty($mod_cols)) {
            array_splice($cols_order, $i, 0, $mod_cols);
            $index = array_search($col, $cols_order);
            unset($cols_order[$index]);
          }
        } else {
          $colms = explode('.', $col);
          if (count($colms) === 2 && isset($colms[0], $colms[1])
            && $this->isEnableName($colms[0])
            && ($this->isEnableName($colms[1]) || $colms[1] === '*')) {
            list($tablename, $column) = $colms;
            $tname = $this->encodeKey($tablename);
            if (!array_key_exists($tname, $meta)) {
              $replaced = false;
              if (!empty($this->_selectTableAliases) && is_array($this->_selectTableAliases)) {
                foreach ($this->_selectTableAliases as $org_name => $as_name) {
                  if ($org_name != null && $as_name != null && $tablename === $as_name) {
                    if ($this->_fromSubSelect) {
                      $replaced = true;
                      if (empty($this->_subSelectJoinUniqueNames)) {
                        break;
                      }
                    }

                    $tablename = $org_name;
                    $tname = $this->encodeKey($tablename);
                    $replaced = true;
                    break;
                  }
                }
              }

              if (!$replaced) {
                $this->pushError('Not exists the table(%s)', $tablename);
                $cols_order = array();
                break;
              }
            }

            if ($column === '*') {
              $mod_cols = array();
              foreach (array_keys($meta[$tname]) as $tcol) {
                if (!array_key_exists($tcol, $cols_keys)) {
                  if ($this->_onMultiSelect) {
                    $mod_cols[] = sprintf('%s.%s', $tablename, $tcol);
                    //XXX: necessary?
                    //$mod_cols[] = $tcol;
                  } else {
                    $mod_cols[] = $tcol;
                  }
                }
              }

              if (!empty($mod_cols)) {
                array_splice($cols_order, $i, 0, $mod_cols);
                $index = array_search($col, $cols_order);
                unset($cols_order[$index]);
              }
            } else {
              if ($this->_onMultiSelect) {
                $correlation = sprintf('%s.%s', $tablename, $column);
                array_splice($cols_order, $i, 0, $correlation);
                $index = array_search($col, $cols_order);
                unset($cols_order[$index]);
              } else {
                array_splice($cols_order, $i, 0, $column);
                $index = array_search($col, $cols_order);
                unset($cols_order[$index]);
              }
            }
          } else if (!array_key_exists($col, $curmeta) && $this->isExprToken($col)) {
            $do_expr = true;
            if (!empty($funcs) && is_array($funcs)) {
              foreach ($funcs as $func_name => $func_args) {
                $func_name = sprintf('%s(%s)', $func_name, $func_args);
                if (strcasecmp($col, $func_name) === 0) {
                  $do_expr = false;
                  break;
                }
              }
            }

            if ($do_expr) {
              $this->_selectExprCols[$col] = 1;
            }
          }
        }
      }
      $result = array_values(array_unique($cols_order));
    }
    return $result;
  }

  /**
   * Apply DISTINCT for result-set
   *
   * @param  array    the result-set
   * @return void
   * @access private
   */
  function applyDistinct(&$rows) {
    if (!$this->hasError()) {
      if (is_array($rows)) {
        $row_count = count($rows);
        if ($row_count > 1) {
          $removes = array();
          for ($i = 0; $i < $row_count; ++$i) {
            for ($j = 0; $j < $i; $j++) {
              if (empty($removes[$i]) && $rows[$i] === $rows[$j]) {
                $removes[$i] = 1;
                break;
              }
            }
          }
          foreach (array_keys($removes) as $index) {
            unset($rows[$index]);
          }
          $rows = array_values($rows);
        }
      }
    }
  }

  /**
   * Remove DISTINCT token for SELECT-LIST clause
   *
   * @param  mixed    the value of SELECT clause
   * @return void
   * @access private
   */
  function removeDistinctToken(&$columns) {
    if (!$this->hasError()) {
      if (is_string($columns)) {
        $columns = $this->splitSyntax($columns);
      }

      if (is_array($columns)) {
        $shift = array_shift($columns);
        if (0 !== strcasecmp($shift, 'distinct')) {
          array_unshift($shift);
        }
        $columns = $this->joinWords($columns);
      }
    }
  }

  /**
   * Remove "ALL" token for SELECT-LIST clause
   *
   * @param  mixed    the value of SELECT clause
   * @return void
   * @access private
   */
  function removeSelectListAllClauseToken(&$columns) {
    if (!$this->hasError()) {
      if (is_string($columns)) {
        $columns = $this->splitSyntax($columns);
      }

      if (is_array($columns)) {
        $shift = array_shift($columns);
        if (0 !== strcasecmp($shift, 'all')) {
          array_unshift($shift);
        }
        $columns = $this->joinWords($columns);
      }
    }
  }

  /**
   * Creates the HTML TABLE element from rows.
   *
   * @example
   * <code>
   * $fields = array('col1' => null, 'col2' => null);
   * $posql = new Posql('table_example_db1', 'table1', $fields);
   * for ($i = 0; $i < 10; $i++) {
   *   $data = array(
   *     'col1' => "col1_value{$i}",
   *     'col2' => "col2_value{$i}"
   *   );
   *   $posql->insert('table1', $data);
   *   if ($posql->isError()) {
   *     die($posql->lastError());
   *   }
   * }
   * $sql = 'SELECT * FROM table1';
   * $result = $posql->query($sql);
   * if ($posql->isError()) {
   *   die($posql->lastError());
   * }
   * $result = $result->fetchAll('assoc');
   * $attrs = array(
   *   'border' => 1,
   *   'style'  => array(
   *     'color'      => '#333',
   *     'background' => '#FFF',
   *     'margin'     => '4px'
   *   )
   * );
   * print $posql->toHTMLTable($result, $sql, $attrs);
   * </code>
   *
   * @param  array   the rows of result sets
   * @param  mixed   optionally, the caption, or the table attributes
   * @param  mixed   optionally, the table attributes, or the caption
   * @return string  Created HTML TABLE element
   * @access public
   */
  function toHTMLTable($rows, $caption = null, $attr = 'border="1"') {
    $result = '';

    if (!empty($rows) && is_array($rows)) {
      if (!is_array(reset($rows))) {
        $rows = array($rows);
      }

      switch (func_num_args()) {
        case 0:
        case 1:
          break;
        case 2:
          if (is_array($caption)) {
            $attr = $caption;
            $caption = null;
          }
          break;
        case 3:
          if (is_array($caption)) {
            $tmp = $attr;
            $attr = $caption;
            $caption = $tmp;
            unset($tmp);
          }
          break;
        default:
          $args = func_get_args();
          array_shift($args);
          $caption = array_shift($args);
          $attr = array_splice($args, 0);
          break;
      }

      if (!is_array($attr)) {
        $attr = array($attr);
      }

      $is_caption = true;
      if (is_string($caption)) {
        //TODO: check more
        $eq_count = substr_count($caption, '=');
        $sp_count = substr_count($caption, ' ');
        $eq_split = explode('=', $caption);
        if ($eq_count === 1) {
          $eq_left = reset($eq_split);
          if ($this->isEnableName($eq_left)) {
            $is_caption = false;
          }
        } else if ($sp_count > 1 && $eq_count > 1) {
          if ($sp_count !== $eq_count) {
            $is_caption = false;
          }
        }
      } else if (is_array($caption)) {
        $is_caption = false;
      }

      if (!$is_caption) {
        array_unshift($attr, $caption);
        $caption = null;
      }

      $caption = (string)$caption;
      $attr = $this->parseHTMLAttributes($attr);
      if (!empty($attr) && is_array($attr)) {
        $attr = ' ' . implode(' ', $attr);
      } else {
        $attr = '';
      }

      $tables = array();
      $tables[] = sprintf('<table%s>', $attr);
      if ($caption != null) {
        $tables[] = sprintf('<caption>%s</caption>',
          $this->escapeHTML($caption));
      }
      $tables[] = '<tr>';
      $row_cols = array_keys(reset($rows));
      foreach ($row_cols as $col) {
        $tables[] = sprintf('<th>%s</th>', $this->escapeHTML($col));
      }
      $tables[] = '</tr>';
      $br = trim(nl2br($this->NL));
      $rows = array_values($rows);
      $row_count = count($rows);
      $i = 0;

      while (--$row_count >= 0) {
        $row = $rows[$i];
        $rows[$i] = null;
        $tables[] = '<tr>';
        foreach ($row as $col => $val) {
          if ($val === '') {
            $val = $br;
          } else if ($val === true) {
            $val = '<var>TRUE</var>';
          } else if ($val === false) {
            $val = '<var>FALSE</var>';
          } else if ($val === null) {
            $val = '<var>NULL</var>';
          } else {
            $val = $this->escapeHTML($val);
          }
          $tables[] = sprintf('<td>%s</td>', $val);
        }
        $tables[] = '</tr>';
        $i++;
      }
      $tables[] = '</table>';
      $result = implode($this->NL, array_splice($tables, 0));
      unset($tables);
    }
    return $result;
  }
}
