<?php
require_once dirname(__FILE__) . '/file.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_Parser
 *
 * This class token parses SQL syntax, expression and PHP code
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet.aqua@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Parser extends Posql_File {
/**
 * Checks whether the expression has already parsed or it has not yet
 *
 * @param  mixed   the target expression
 * @return boolean whether it already parsed or it has not yet
 * @access private
 */
 function isParsedExpr($expr){
   $result = false;
   $mark = $this->getParsedMark();
   if ($mark && $expr != null) {
     if (is_array($expr)) {
       $result = reset($expr) === $mark;
     } else {
       $result = substr($expr, 0, strlen($mark)) === $mark;
     }
   }
   return $result;
 }

/**
 * Returns the mark to compare that parsed the expression
 *
 * @param  void
 * @return string   the Mark that use to parsed
 * @access private
 */
 function getParsedMark(){
   static $mark = '#';
   return $mark;
 }

/**
 * Adds the mark to compare for the expression
 *
 * @param  mixed    the expression
 * @return boolean  success or failure
 * @access private
 */
 function addParsedMark(&$expr){
   $result = false;
   $mark = $this->getParsedMark();
   if ($mark && !$this->isParsedExpr($expr)) {
     if (is_array($expr)) {
       array_unshift($expr, $mark);
     } else {
       $expr = $mark . $expr;
     }
     $result = true;
   }
   return $result;
 }

/**
 * Removes the mark to compare for the expression
 *
 * @param  mixed    the expression
 * @return boolean  success or failure
 * @access private
 */
 function removeParsedMark(&$expr){
   $result = false;
   $mark = $this->getParsedMark();
   if ($mark && $this->isParsedExpr($expr)) {
     if (is_array($expr)) {
       array_shift($expr);
     } else {
       $expr = substr($expr, strlen($mark));
     }
     $result = true;
   }
   return $result;
 }

/**
 * Splits the syntax to tokens
 *
 * @param  string  expression/syntax
 * @return array   parsed tokens as an array
 * @access public
 */
 function splitSyntax($syntax){
   static $cache = array(), $size = 0, $opted = false, $regexp = '
   {(?:
       (
        "[^"\\\\]*(?:\\\\.[^"\\\\]*)*"
     |  \'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'
       )
   |   (?:
          (?:
              //
            | [#]
            | --
          )[^\x0A\x0D]*
       )
   |   (?:/[*][\s\S]*?[*]/)
   |   [\x0A\x0D]+
   |   (
         (?: <=>
           | \(\+\)
           | !!=
           | !~[~*]
           | [!=]==
           | <<[<=]
           | >>[>=]
         )
    |    (?: [!=<>.^&|/%*+-]=
           | [&][&]
           | [|][|]
           | <<
           | [=<>-]>
           | ::
           | \+\+
           | --
           | [!~]~
           | ~[~*]
           | <[%?]
           | [%?]>
         )
       )
   | (
       (?: [+-]?
            (?:
               [0](?i:x[0-9a-f]+|[0-7]+)
          |    (?:\d+(?:[.]\d+)?(?i:e[+-]?\d+)?)
          |    [1-9]\d*
            )
        )
     |  (?i:
            [\w\x7F-\xFF]+
        )
     )
   | ([$:@?,;`~\\\\()\{\}\[\]!=<>.^&|/%*+-])
   | \b
   | ([\s])[\s]+
   )}x';
   if (!$opted) {
     $regexp = preg_replace('{\s+|x$}', '', $regexp);
     $opted = true;
   }
   $hash = $this->hashValue($syntax);
   if (isset($cache[$hash])) {
     $result = $cache[$hash];
   } else {
     $result = array_values(
                   array_filter(
                       array_map(
                         'trim',
                         preg_split(
                           $regexp,
                           trim($syntax),
                           -1,
                           PREG_SPLIT_NO_EMPTY |
                           PREG_SPLIT_DELIM_CAPTURE
                         )
                       ),
                       'strlen'
                   )
     );
     if ($size < 0x1000 && strlen(serialize($result)) < 0x100) {
       $cache[$hash] = $result;
       $size++;
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function getSupportedSyntax($flip = false, $method = null, $subset = null){
   static $syntax = array(
     'select' => array(
       'select', 'from', 'where', 'group', 'having', 'order', 'limit'
     ),
     'update' => array(
       'update', 'set', 'where'
     ),
     'delete' => array(
       'delete', 'from', 'where'
     ),
     'insert' => array(
       'insert', 'into', 'values', 'select'
     ),
     'replace' => array(
       'replace', 'into', 'values', 'select'
     ),
     'create' => array(
       'create', 'table', 'database'
     ),
     'drop'   => array(
       'drop', 'table', 'database'
     ),
     'vacuum' => array(
       'vacuum'
     ),
     'describe' => array(
       'describe'
     ),
     'desc' => array(
       'desc'
     ),
     'begin' => array(
       'begin'
     ),
     'start' => array(
       'start'
     ),
     'commit' => array(
       'commit'
     ),
     'end' => array(
       'end'
     ),
     'rollback' => array(
       'rollback'
     )
   );
   $result = array();
   if ($method == null) {
     if ($flip) {
       $result = array_flip(array_keys($syntax));
     } else {
       $result = $syntax;
     }
   } else {
     $method = strtolower($method);
     if (array_key_exists($method, $syntax)) {
       if ($flip) {
         $result = array_flip($syntax[$method]);
       } else {
         $result = $syntax[$method];
       }
     } else {
       $this->pushError('Not supported SQL syntax(%s)', $method);
     }
   }
   return $result;
 }

/**
 * Gets the command name from SQL query
 *
 * @param  mixed   the SQL query, or the parsed tokens
 * @param  boolean whether get the result as is or lowercase
 * @return string  the command name
 * @access private
 */
 function getQueryCommand($query, $as_is = false){
   $result = false;
   if (is_array($query)) {
     $result = reset($query);
   } else if (is_string($query)) {
     $tokens = $this->splitSyntax($query);
     if (!empty($tokens) && is_array($tokens)) {
       $result = reset($tokens);
     }
     unset($tokens);
   }
   if (is_string($result) && !$as_is) {
     $result = strtolower($result);
   }
   return $result;
 }

/**
 * Parses tokens of the SQL for implement each methods
 *
 * @param  string  the SQL query
 * @return array   parsed tokens
 * @access private
 */
 function parseQueryImplements($query){
   $result = false;
   $query = trim($query);
   if ($query != null) {
     $methods = $this->getSupportedSyntax(true);
     $tokens  = $this->splitSyntax($query);
     if (!empty($tokens) && is_array($tokens)) {
       $method = strtolower(reset($tokens));
       if ($method == null
        || !array_key_exists($method, $methods)) {
         $this->pushError('Not supported SQL syntax(%s)', $method);
       } else {
         $this->lastMethod = $method;
         $this->lastQuery  = $query;
         $tokens = $this->removeBacktickToken($tokens);
         //
         //XXX: Should replace the alias in this point?
         // i.e.  $tokens = $this->replaceAliasTokens($tokens);
         //
         switch ($method) {
           case 'select':
               $result = $this->parseSelectQuery($tokens);
               break;
           case 'update':
           case 'delete':
           case 'create':
           case 'drop':
           case 'describe':
           case 'desc':
               $result = $this->parseBasicQuery($tokens, $method);
               break;
           case 'insert':
           case 'replace':
               $result = $this->parseInsertQuery($tokens);
               break;
           case 'vacuum':
           case 'begin':
           case 'start':
           case 'commit':
           case 'end':
           case 'rollback':
               $result = $this->getSupportedSyntax(false, $method);
               break;
           case 'alter':
           default:
               $this->pushError('Not supported SQL syntax(%s)', $method);
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses basic SQL query from the tokens
 *
 * @param  array   parsed tokens
 * @param  string  the method name
 * @return array   the tokens that parsed as clauses
 * @access private
 */
 function parseBasicQuery($tokens, $method){
   $result = false;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s) on %s', $tokens, $method);
   } else {
     $methods = $this->getSupportedSyntax(true, $method);
     if (!is_array($methods)) {
       $this->pushError('illegal syntax, or token(%s)', $methods);
     } else {
       $result = array();
       $clause = null;
       $level  = 0;
       $tokens = array_values($tokens);
       foreach ($tokens as $token) {
         $ltoken = strtolower($token);
         switch ($ltoken) {
           case '(':
               $level++;
               break;
           case ')':
               $level--;
               break;
           default:
               break;
         }
         if ($level === 0 && !empty($methods)
          && array_key_exists($ltoken, $methods)) {
           $clause = $ltoken;
           foreach ($methods as $method => $k) {
             if ($method === $clause) {
               unset($methods[$method]);
               break;
             }
             unset($methods[$method]);
           }
         } else if ($clause != null) {
           $result[$clause][] = $token;
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses SELECT SQL query from the tokens
 *
 * @param  array   parsed tokens
 * @return array   the tokens that parsed as clauses
 * @access private
 */
 function parseSelectQuery($tokens){
   $result = false;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s) on SELECT', $tokens);
   } else {
     $methods = $this->getSupportedSyntax(true, 'select');
     if (!is_array($methods)) {
       $this->pushError('illegal syntax, or token(%s)', $methods);
     } else {
       $result = array();
       $clause = null;
       $level  = 0;
       $tokens = array_values($tokens);
       $length = count($tokens);
       for ($i = 0; $i < $length; $i++) {
         $token = $tokens[$i];
         $ltoken = strtolower($token);
         switch ($ltoken) {
           case '(':
               $level++;
               break;
           case ')':
               $level--;
               break;
           default:
               break;
         }
         if ($level === 0 && !empty($methods)
          && array_key_exists($ltoken, $methods)) {
           $clause = $ltoken;
           foreach ($methods as $method => $k) {
             if ($method === $clause) {
               unset($methods[$method]);
               break;
             }
             unset($methods[$method]);
           }
         } else if ($clause != null) {
           $push = true;
           switch ($ltoken) {
             case 'by':
                 if ($level === 0) {
                   if ($clause === 'group' || $clause === 'order') {
                     $push = false;
                   }
                 }
                 break;
             case 'union':
             case 'intersect':
             case 'except':
                 if ($level === 0) {
                   if (isset($tokens[$i + 1])
                    && 0 === strcasecmp($tokens[$i + 1], 'all')) {
                     $ltoken = sprintf('%s_all', $ltoken);
                     $i++;
                   }
                   $slice = array_slice($tokens, $i + 1);
                   $subset = $this->parseSelectUnion($slice);
                   if (!is_array($subset)) {
                     break 2;
                   }
                   $subset_length = count($this->toOneArray($subset));
                   $subset_length += count($subset);
                   if (array_key_exists('group', $subset)) {
                     $subset_length += 1;
                   }
                   $i += $subset_length;
                   $result[$ltoken][] = $subset;
                   $push = false;
                 }
                 break;
             default:
                 break;
           }
           if ($push) {
             $result[$clause][] = $token;
           }
         }
       }
     }
   }
   //debug($result,'color=purple:***parseSelectQuery***;');
   return $result;
 }

/**
 * Parse compound-operator for SELECT query
 *
 * compound-operator:
 *  - UNION [ALL] : combine two query expressions into a single result set.
 *                  if ALL is specified, duplicate rows returned by
 *                  union expression are retained.
 *  - EXCEPT      : evaluates the output of two query expressions and
 *                  returns the difference between the results.
 *  - INTERSECT   : evaluates the output of two query expressions and
 *                  returns only the rows common to each.
 *
 * @param  array   parsed tokens
 * @return array   the tokens that parsed as clauses
 * @access private
 */
 function parseSelectUnion($tokens){
   $result = false;
   if (!$this->hasError()) {
     if (!is_array($tokens)) {
       $this->pushError('Cannot parse tokens(%s) on SELECT', $tokens);
     } else {
       $methods = $this->getSupportedSyntax(true, 'select');
       if (!is_array($methods)) {
         $this->pushError('illegal syntax, or token(%s)', $methods);
       } else {
         $result = array();
         $clause = null;
         $level  = 0;
         $tokens = array_values($tokens);
         $length = count($tokens);
         for ($i = 0; $i < $length; $i++) {
           $token = $tokens[$i];
           $ltoken = strtolower($token);
           switch ($ltoken) {
             case '(':
                 $level++;
                 break;
             case ')':
                 $level--;
                 break;
             case 'order':
                 if ($level === 0 && isset($tokens[$i + 1])
                  && 0 === strcasecmp($tokens[$i + 1], 'by')) {
                   break 2;
                 }
                 break;
             case 'limit':
             case 'union':
             case 'intersect':
             case 'except':
                 if ($level === 0) {
                   break 2;
                 }
                 break;
             default:
                 break;
           }
           if ($level === 0 && !empty($methods)
            && array_key_exists($ltoken, $methods)) {
             $clause = $ltoken;
             foreach ($methods as $method => $k) {
               if ($method === $clause) {
                 unset($methods[$method]);
                 break;
               }
               unset($methods[$method]);
             }
           } else if ($clause != null) {
             if ($level === 0 && $ltoken === 'by'
              && ($clause === 'group' || $clause === 'order')) {
               continue;
             }
             $result[$clause][] = $token;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses the INSERT query from the tokens
 *
 * @param  array   parsed tokens
 * @return array   the tokens that parsed as clauses
 * @access private
 */
 function parseInsertQuery($tokens){
   $result = false;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s)', $tokens);
   } else {
     $methods = $this->getSupportedSyntax(true, 'insert');
     $result = array();
     $clause = null;
     $level  = 0;
     $select = null;
     $tokens = array_values($tokens);
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       $j = $i + 1;
       $skip = false;
       $token = $this->getValue($tokens, $i);
       $next = $this->getValue($tokens, $j);
       $ltoken = strtolower($token);
       switch ($ltoken) {
         case '(':
             $level++;
             if ($next != null && 0 === strcasecmp($next, 'select')) {
               $level--;
               $select = $level - 1;
               $skip = true;
             }
             break;
         case ')':
             $level--;
             if ($select !== null && $select === $level) {
               $level++;
               $select = null;
               $skip = true;
             }
             break;
         case 'insert':
             if ($level === 0 && 0 === strcasecmp($next, 'or')
              && isset($tokens[$i + 2])
              && 0 === strcasecmp($tokens[$i + 2], 'replace')) {
               $this->lastMethod = strtolower($tokens[$i + 2]);
               $i += 2;
               $skip = true;
             }
             break;
         default:
             break;
       }
       if ($skip) {
         continue;
       }
       if ($level === 0 && !empty($methods)
        && array_key_exists($ltoken, $methods)) {
         $clause = $ltoken;
         foreach ($methods as $method => $k) {
           if ($method === $clause) {
             unset($methods[$method]);
             break;
           }
           unset($methods[$method]);
         }
         if (($clause === 'values' || $clause === 'select')
          && empty($result['cols']) && isset($result['into'])) {
           $into = & $result['into'];
           $pre = array_search('(', $into);
           $suf = array_search(')', $into);
           if (is_int($pre) && is_int($suf) && $pre < $suf) {
             $result['cols'] = array_splice($into, $pre, $pre + $suf);
           }
         }
       } else if ($clause != null) {
         $result[$clause][] = $token;
       }
     }
     if ($level !== 0) {
       $this->pushError('Syntax error, expect () parenthesis');
       $result = false;
     } else {
       $cols_name = 'cols';
       if (isset($into)
        && !array_key_exists($cols_name, $result)) {
         $cols = $this->getMeta(implode('', $into));
         if (is_array($cols)) {
           $result[$cols_name] = array();
           $columns = & $result[$cols_name];
           foreach (array_keys($cols) as $col) {
             $columns[] = $col;
             $columns[] = ',';
           }
           array_pop($columns);
         }
       }
     }
     unset($into, $columns);
   }
   return $result;
 }

/**
 * Parses the multielement queries to the individual query
 *
 * @param  string  the multielement queies
 * @return array   results of to disjointed query
 * @access private
 */
 function parseMultiQuery($query){
   $result = array();
   if ($query != null && is_string($query)) {
     $query = $this->splitSyntax($query);
     $query = array_values($query);
     $length = count($query);
     $stack  = array();
     $prev_token = null;
     $prev_index = 0;
     while (--$length >= 0) {
       $token = array_shift($query);
       if ($this->isStringToken($token)) {
         $token = $this->toStringToken($token);
         if ($prev_index === 0) {
           $prev_token = $token;
         } else if ($prev_index - 1 === $length) {
           $token = $this->concatStringTokens($prev_token, $token);
           array_pop($stack);
         }
         $prev_token = $token;
         $prev_index = $length;
       }
       $stack[] = $token;
       if ($token === ';'
        && (!$length || $this->isWord(reset($query)))) {
         $result[] = $this->joinWords($stack);
         $stack = array();
       }
     }
   }
   return $result;
 }

/**
 * Parses the tokens for naming the table, or database
 *
 * @param  array    parsed tokens
 * @param  boolean  whether it exists is checked
 * @return mixed    results of parsed tokens
 * @access private
 */
 function assignNames($tokens, $check_exists = false){
   $result = false;
   $this->_ifExistsClause = null;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s)', $tokens);
   } else {
     $name = '';
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       $j = $i + 1;
       $k = $j + 1;
       switch (strtolower($tokens[$i])) {
         case '`':
         //case ';':
         //case ':':
         //case '(':
         //case ')':
             break;
         case 'if':
             if ($check_exists) {
               if (isset($tokens[$j])) {
                 if (strtolower($tokens[$j]) === 'exists') {
                   $this->_ifExistsClause = 'if_exists';
                   $i = $j;
                 } else if (isset($tokens[$k])
                         && strtolower($tokens[$j]) === 'not'
                         && strtolower($tokens[$k]) === 'exists') {
                   $this->_ifExistsClause = 'if_not_exists';
                   $i = $k;
                 }
               }
             }
             break;
         default:
             $name .= $tokens[$i];
             break;
       }
     }
     if ($this->isStringToken($name)) {
       $name = substr($name, 1, -1);
     }
     $result = $name;
   }
   return $result;
 }

/**
 * Parses the tokens for CREATE DATABASE
 *
 * @param  array   parsed tokens
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseCreateDatabaseQuery($tokens){
   $result = $this->assignNames($tokens, true);
   return $result;
 }

/**
 * Parses the tokens for DROP TABLE/DATABASE
 *
 * @param  array   parsed tokens
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseDropQuery($tokens){
   $result = $this->assignNames($tokens, true);
   return $result;
 }

/**
 * Parses the tokens for CREATE TABLE
 *
 * @param  array    parsed tokens
 * @param  boolean  whether return the result as simple array
 * @return mixed    results of parsed tokens
 * @access private
 */
 function parseCreateTableQuery($tokens, $simple = false){
   $result = false;
   if (!is_array($tokens) || empty($this->lastQuery)) {
     $this->pushError('Cannot parse tokens on CREATE TABLE');
   } else {
     $sql = $this->lastQuery;
     $open  = '(';
     $close = ')';
     $bpos = strpos($sql, $open);
     $epos = strrpos($sql, $close);
     if ($bpos === false || $epos === false || $bpos > $epos) {
       $this->pushError('illegal query on CREATE(%s)', $sql);
     } else {
       $tokens = $this->removeBacktickToken($tokens);
       $table = array();
       while (!empty($tokens) && reset($tokens) !== $open) {
         $table[] = array_shift($tokens);
       }
       array_shift($tokens);
       $table = $this->assignNames($table, true);
       while (!empty($tokens) && end($tokens) !== $close) {
         array_pop($tokens);
       }
       array_pop($tokens);
       $fields = $this->parseColumnDefinition($tokens);
       if (empty($fields) || $this->hasError()) {
         $result = false;
       } else {
         $primary = null;
         $this->_onCreate = true;
         $this->_execExpr = true;
         $simple_fields = array();
         foreach ($fields as $name => $field) {
           if (empty($field) || !is_array($field)
            || $name == null || !is_string($name)) {
             $this->pushError('Failed to parse CREATE_DEFINITION');
             break;
           } else {
             foreach ($field as $desc => $value) {
               switch (strtolower($desc)) {
                 case 'key':
                     if ($primary === null
                      && strcasecmp($value, 'primary') === 0) {
                       $primary = $name;
                     }
                     break;
                 case 'default':
                     $value = $this->validExpr($value);
                     if (!$this->hasError()) {
                       $value = $this->safeExpr($value);
                       $fields[$name][$desc] = $value;
                       $simple_fields[$name] = $value;
                     }
                     break;
                 case 'type':
                 case 'null':
                 default:
                     break;
               }
               if ($this->hasError()) {
                 $result = false;
                 break;
               }
             }
           }
         }
         if (!$this->hasError()) {
           $result = array(
             'table'   => $table,
             'fields'  => $fields,
             'primary' => $primary
           );
           if ($simple) {
             $result['fields'] = $simple_fields;
           }
         }
         $this->_execExpr = null;
         $this->_onCreate = null;
       }
     }
   }
   return $result;
 }

/**
 * Parses the tokens that part of column_definition as simple
 *
 * @param  mixed   parsed tokens, or CREATE TABLE SQL as string
 * @return mixed   create_definition as associate array
 * @access private
 */
 function parseCreateTableQuerySimple($query){
   $result = false;
   if (is_array($query)) {
     $query = $this->joinWords($query);
   }
   $open  = '(';
   $close = ')';
   $bpos = strpos($query, $open);
   $epos = strrpos($query, $close);
   if ($bpos === false || $epos === false || $bpos > $epos) {
     $this->pushError('Connot parse tokens (CREATE_DEFINITION)');
   } else {
     $tokens = $this->splitSyntax($query);
     $tokens = $this->removeBacktickToken($tokens);
     while (!empty($tokens) && reset($tokens) !== $open) {
       array_shift($tokens);
     }
     array_shift($tokens);
     while (!empty($tokens) && end($tokens) !== $close) {
       array_pop($tokens);
     }
     array_pop($tokens);
     $result = $this->parseColumnDefinition($tokens);
     if (empty($result) || $this->hasError()) {
       $result = false;
     }
   }
   return $result;
 }

/**
 * Parses the tokens that part of column_definition
 * The result will be classified as follows.
 *
 * - type
 *           Data type of column  i.e. INTEGER, TEXT or VARCHAR(255) etc.
 * - null
 *           Boolean flag that indicates whether this field is constrained
 *            to be set to NULL.
 * - key
 *           Field of key types, others are NULL  e.g. "primary"
 * - default
 *           Text value to be used as default for this field.
 *
 * @param  array   target definition
 * @return array   results of parsed tokens, or FALSE on error
 * @access private
 */
 function parseColumnDefinition($definition){
   static $reserve_types = array(
     'default',
     'primary',
     'key',
     'check',
     'unique',
     'not',
     'null',
     'auto_increment',
     'collate',
     'foreign'
   );
   $result = false;
   if (!is_array($definition)) {
     $definition = $this->splitSyntax($definition);
   }
   $tokens = $this->removeBacktickToken($definition);
   unset($definition);
   $open     = '(';
   $close    = ')';
   $comma    = ',';
   $level    = 0;
   $names    = array();
   $types    = array();
   $defaults = array();
   $nulls    = array();
   $primary  = null;
   if (end($tokens) !== $comma) {
     array_push($tokens, $comma);
   }
   if (reset($tokens) !== $comma) {
     array_unshift($tokens, $comma);
   }
   $length = count($tokens);
   for ($i = 0; $i < $length; $i++) {
     $j = $i + 1;
     $k = $j + 1;
     switch (strtolower($tokens[$i])) {
       case '(':
           $level++;
           break;
       case ')':
           $level--;
           break;
       case ',':
           if ($level === 0) {
             if (isset($tokens[$j])
              && in_array(strtolower($tokens[$j]), $reserve_types)) {
               break;
             }
             if (!empty($names)) {
               if (count($names) > count($defaults)) {
                 $defaults[] = 'null';
               }
               if (count($names) > count($nulls)) {
                 $nulls[] = true;
               }
             }
             while (++$i < $length) {
               if ($this->isEnableName($tokens[$i])) {
                 $names[] = $tokens[$i];
                 $n = $i + 1;
                 if (isset($tokens[$n])) {
                   $type = strtolower($tokens[$n]);
                   if ($this->isEnableName($type)
                    && !in_array($type, $reserve_types)) {
                     $type_length = '';
                     if (isset($tokens[++$n]) && $tokens[$n] === $open) {
                       while (isset($tokens[++$n])) {
                         if ($tokens[$n] === $close) {
                           break;
                         }
                         $type_length .= $tokens[$n];
                       }
                       if ($type_length != null) {
                         $type .= sprintf('(%s)', $type_length);
                       }
                       $i = $n;
                     }
                     $types[] = $type;
                   }
                 }
                 if (count($names) > count($types)) {
                   $types[] = $this->defaultDataType;
                 }
                 break;
               }
             }
           }
           break;
       case 'default':
           if ($level === 0) {
             if (isset($tokens[$j])) {
               if (strtolower($tokens[$j]) !== 'character') {
                 $defaults[] = $tokens[$j];
               }
               $i = $j;
             }
           }
           break;
       case 'null':
           if ($level === 0) {
             $n = $i - 1;
             if (isset($tokens[$n])
              && strtolower($tokens[$n]) === 'not') {
               $nulls[] = false;
             } else {
               $nulls[] = true;
             }
           }
           break;
       case 'primary':
           if ($level === 0 && isset($tokens[$j])
            && strtolower($tokens[$j]) === 'key') {
             if (empty($names)) {
               $this->pushError('Failed to parse tokens, expect PRIMARY');
               break 2;
             }
             if (isset($tokens[$k])
              && !empty($names) && $tokens[$k] === $open) {
               $n = $k;
               $primary_tokens = array();
               while (isset($tokens[++$n])) {
                 switch ($tokens[$n]) {
                   case '(':
                   case ')':
                       break 2;
                   case ',':
                       $this->pushError('Not supported'
                                     .  ' the multiple PRIMARY keys');
                       break 4;
                   default:
                       $primary_tokens[] = $tokens[$n];
                       break;
                 }
               }
               $primary_token = array_shift($primary_tokens);
               if ($this->isStringToken($primary_token)) {
                 $primary_token = substr($primary_token, 1, -1);
               }
               if (!empty($primary_tokens) || $primary_token == null
                || !in_array($primary_token, $names)) {
                 $this->pushError('Failed to parse tokens, expect PRIMARY');
                 break 2;
               }
               $primary = $primary_token;
               $i = $n;
             } else {
               if (!empty($names)
                && count($names) === 1 && isset($names[0])) {
                 $primary = $names[0];
                 $i = $j;
               }
             }
           }
           break;
       case 'character':
           if ($level === 0) {
             if (isset($tokens[$j], $tokens[$k])
              && strtolower($tokens[$j]) === 'set'
              && ($this->isStringToken($tokens[$k])
              ||  $this->isEnableName($tokens[$k]))) {
               $i = $k;
             }
           }
           break;
       // ignore for futures
       case 'check':
       case 'unique':
       case 'auto_increment':
       case 'collate':
       case 'foreign':
       default:
           break;
     }
   }
   if (!$this->hasError()) {
     if ($level !== 0) {
       $this->pushError('Failed to parse tokens, expect %s bracket', $close);
     } else {
       $names    = array_values($names);
       $types    = array_values($types);
       $defaults = array_values($defaults);
       $nulls    = array_values($nulls);
       $length   = count($names);
       if ($length !== count($types)
        || $length !== count($defaults)
        || $length !== count($nulls)) {
         $this->pushError('Failed to parse tokens, expect CREATE_DEFINITION');
       } else {
         $fields = array();
         for ($i = 0; $i < $length; $i++) {
           if (!array_key_exists($i, $names)
            || !array_key_exists($i, $types)
            || !array_key_exists($i, $defaults)
            || !array_key_exists($i, $nulls)) {
             $this->pushError('Unable to parse tokens (CREATE_DEFINITION)');
             break;
           }
           $key = null;
           if ($primary === $names[$i]) {
             $key = 'primary';
           }
           $fields[$names[$i]] = array(
             'type'    => $types[$i],
             'null'    => $nulls[$i],
             'key'     => $key,
             'default' => $defaults[$i]
           );
         }
         if (!$this->hasError()) {
           $result = $fields;
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses the tokens for "INSERT (...) VALUES (...)"
 *
 * @param  array   parsed tokens
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseInsertValues($values){
   $result = false;
   if (!is_array($values)
    || !isset($values['cols']) || !isset($values['values'])) {
     $this->pushError('Cannot parse tokens on INSERT');
   } else {
     $rows = array();
     $cols = $values['cols'];
     $vals = $values['values'];
     while (end($cols) === ';') {
       array_pop($cols);
     }
     if ( end($cols)   === ')'
      &&  reset($cols) === '(') {
       array_pop($cols);
       array_shift($cols);
     }
     while (end($vals) === ';') {
       array_pop($vals);
     }
     if ( end($vals)   === ')'
      &&  reset($vals) === '(') {
       array_pop($vals);
       array_shift($vals);
     }
     if ($cols != null && $vals != null) {
       $tmp = array();
       $level = 0;
       $stack = '';
       foreach ($vals as $val) {
         switch ($val) {
           case '`':
               break;
           case '(':
               $level++;
               $stack .= $val;
               break;
           case ')':
               $level--;
               $stack .= $val;
               break;
           case ',':
               if ($level) {
                 $stack .= $val;
               } else {
                 $tmp[] = $stack;
                 $stack = '';
               }
               break;
           default:
               $stack .= $val;
               break;
         }
       }
       if ($stack != null) {
         $tmp[] = $stack;
       }
       $vals = $tmp;
       $tmp = array();
       $stack = '';
       foreach ($cols as $col) {
         switch ($col) {
           case '`':
               break;
           case ',':
               $tmp[] = $stack;
               $stack = '';
               break;
           default:
               $stack .= $col;
               break;
         }
       }
       if ($stack != null) {
         $tmp[] = $stack;
       }
       $cols = $tmp;
       $vlen = count($vals);
       if ($vlen < count($cols)) {
         while ($vlen < count($cols)) {
           array_pop($cols);
         }
       }
       if ($cols == null || $vals == null
        || count($cols) !== count($vals)) {
         $this->pushError('illegal format of columns, or values');
       } else {
         $cols = array_values($cols);
         $vals = array_values($vals);
         $vlen = count($vals);
         $rows = array();
         for ($i = 0; $i < $vlen; $i++) {
           $rows[$cols[$i]] = $vals[$i];
         }
         $result = $rows;
       }
     }
   }
   return $result;
 }

/**
 * Parses the tokens for UPDATE
 *
 * @param  array   parsed tokens
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseUpdateSet($tokens){
   $result = false;
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens in UPDATE SET(%s)', $tokens);
   } else {
     $row   = array();
     $stack = array();
     $level = 0;
     $col   = '';
     foreach ($tokens as $token) {
       switch ($token) {
         case '`':
             break;
         case '=':
             if ($level === 0) {
               $col = $this->joinWords($stack);
               $row[$col] = '';
               $stack = array();
             } else {
               $stack[] = $token;
             }
             break;
         case '(':
             $level++;
             $stack[] = $token;
             break;
         case ')':
             $level--;
             $stack[] = $token;
             break;
         case ',':
             if ($level === 0) {
               $row[$col] = $stack;
               $stack = array();
             } else {
               $stack[] = $token;
             }
             break;
         default:
             $stack[] = $token;
             break;
       }
     }
     if (isset($row[$col])) {
       $row[$col] .= $this->joinWords($stack);
     }
     $result = $row;
   }
   return $result;
 }

/**
 * Parses the arguments for SELECT method
 *
 * @param  mixed    target arguments
 * @param  boolean  whether return the result as associate array
 * @return mixed    parsed arguments, or empty array on error
 * @access private
 */
 function parseSelectArguments($args, $as_assoc = false){
   static $indexes = array(
     'from'   => 0,
     'select' => 1, 'where'  => 2,
     'group'  => 3, 'having' => 4,
     'order'  => 5, 'limit'  => 6
   );
   $result = array();
   if (!is_array($args)) {
     $args = (string)$args;
     $result = array($args);
   } else {
     if (!$this->isAssoc($args)) {
       $result = $args;
     } else {
       $marks = array('_', ' ');
       foreach ($args as $clause => $val) {
         if (!is_string($clause)) {
           $result[] = $clause;
         } else {
           $clause = strtolower($clause);
           if (substr($clause, -1) === 's') {
             $clause = rtrim($clause, 's');
           }
           foreach ($marks as $mark) {
             if (strpos($clause, $mark) !== false) {
               $split = explode($mark, $clause);
               $clause = implode('', $split);
             }
           }
           switch ($clause) {
             case 'from':
             case 'table':
                 $result[$indexes['from']] = $val;
                 break;
             case 'select':
             case 'col':
             case 'column':
             case 'field':
                 $result[$indexes['select']] = $val;
                 break;
             case 'where':
             case 'expr':
             case 'expression':
             case 'cond':
             case 'condition':
                 $result[$indexes['where']] = $val;
                 break;
             case 'group':
             case 'groupby':
                 $result[$indexes['group']] = $val;
                 break;
             case 'having':
                 $result[$indexes['having']] = $val;
                 break;
             case 'order':
             case 'orderby':
             case 'sort':
             case 'sortby':
                 $result[$indexes['order']] = $val;
                 break;
             case 'limit':
                 $limit = $val;
                 $index = & $indexes['limit'];
                 if (isset($offset)) {
                   $result[$index] = sprintf('%.0f,%.0f', $offset, $limit);
                 } else {
                   if (is_numeric($limit)) {
                     $limit = sprintf('%.0f', $limit);
                   }
                   $result[$index] = $limit;
                 }
                 unset($index);
                 break;
             case 'offset':
                 $offset = $val;
                 $index = & $indexes['limit'];
                 if (array_key_exists($index, $result)) {
                   if (!isset($limit)) {
                     $limit = $result[$index];
                   }
                   $result[$index] = sprintf('%.0f,%.0f', $offset, $limit);
                 } else {
                   $result[$index] = sprintf('%.0f,ALL', $offset);
                 }
                 unset($index);
                 break;
             default:
                 $this->pushError('Undefined name(%s) on argument', $clause);
                 $result = array();
                 break 2;
           }
         }
       }
     }
   }
   if (!is_array($result)
    || count($result) > count($indexes)) {
     $error = (string)$result;
     if (is_array($result)) {
       $error .= sprintf('#%d', count($result));
     }
     $this->pushError('illegal arguments(%s) on SELECT', $error);
     $result = array();
   } else {
     foreach ($indexes as $clause => $i) {
       if (!array_key_exists($i, $result)) {
         $result[$i] = $this->getSelectDefaultArguments($clause);
       }
     }
     ksort($result);
     if ($as_assoc) {
       $assoc = array();
       foreach ($indexes as $clause => $i) {
         $assoc[$clause] = $result[$i];
       }
       $result = $assoc;
     }
   }
   return $result;
 }

/**
 * Parses the sub-query
 *
 * @param  mixed   the SQL query, or the parsed tokens
 * @param  boolean whether include parentheses
 * @param  boolean whether include alias length
 * @return mixed   results of parsed tokens
 * @access private
 */
 function parseSubQuery($tokens, $include_parentheses = false,
                                 $include_alias_length = false){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (is_array($tokens)) {
     $level     = 0;
     $sub_level = null;
     $offset    = null;
     $operator  = null;
     $alias     = null;
     $stack     = array();
     $tokens    = $this->cleanArray($tokens);
     $count     = count($tokens);
     for ($i = 0; $i < $count; $i++) {
       $token = $tokens[$i];
       $next  = $this->getValue($tokens, $i + 1);
       $prev  = $this->getValue($tokens, $i - 1);
       switch (strtolower($token)) {
         case '`':
             break;
         case '(':
             $level++;
             if ($sub_level !== null) {
               $stack[] = $token;
             }
             if ($next != null && 0 === strcasecmp($next, 'select')) {
               $sub_level = $level - 1;
               $offset = $i + 1;
               if ($prev != null) {
                 $operator = strtolower($prev);
               }
               if (!empty($stack)) {
                 $stack = array();
               }
               if ($include_parentheses) {
                 $offset--;
                 $stack[] = $token;
               }
             }
             break;
         case ')':
             $level--;
             if ($sub_level === $level) {
               $sub_level = null;
               if ($include_parentheses) {
                 $stack[] = $token;
               }
             }
             if ($sub_level !== null) {
               $stack[] = $token;
             }
             break;
         case 'as':
             if ($level === 0 && $next != null) {
               $alias = $next;
               $i++;
               break;
             }
             // FALLTHROUGH
         default:
             if ($sub_level !== null) {
               $stack[] = $token;
             }
             break;
       }
     }
     if (!empty($stack)) {
       $length = count($stack);
       if ($include_alias_length && $alias != null) {
         $length++;
       }
       $result = array(
         'tokens'   => $stack,
         'offset'   => $offset,
         'length'   => $length,
         'operator' => $operator,
         'alias'    => $alias
       );
     }
   }
   //debug($result,"color=#6600ff:**sub-query**;");
   return $result;
 }

/**
 * Get the table name from parsed tokens
 *
 * @param  array    parsed tokens
 * @param  string   type of statement
 * @return string   table name
 * @access private
 */
 function getTableNameFromTokens($tokens, $type){
   $result = null;
   if (is_array($tokens)) {
     switch ($type) {
       case 'select':
           if (array_key_exists('from', $tokens)) {
             $result = $tokens['from'];
             if (is_array($result)) {
               $result = implode('', $result);
             }
           }
           break;
       default:
           $result = null;
           break;
     }
   }
   return $result;
 }

/**
 * Check whether the query is manipulation or definition query
 *
 * @param  mixed   the query, or the parsed tokens
 * @return boolean whether the query is manipulation query
 * @access public
 */
 function isManip($query){
   static $spaces = array(' ', "\t", "\r", "\n");
   $result = false;
   if (is_string($query)) {
     $query = $this->splitSyntax($query);
   }
   if (is_array($query)) {
     $query = reset($query);
   }
   if (is_string($query) && $query != null) {
     $query = substr(ltrim($query), 0, 10);
     foreach ($spaces as $space) {
       if (strpos($query, $space) !== false) {
         $split = explode($space, $query);
         $query = array_shift($split);
       }
     }
     switch (strtolower($query)) {
       case 'alter':
       case 'begin':
       case 'commit':
       case 'copy':
       case 'create':
       case 'delete':
       case 'drop':
       case 'end':
       case 'grant':
       case 'insert':
       case 'load':
       case 'lock':
       case 'replace':
       case 'revoke':
       case 'rollback':
       case 'start':
       case 'unlock':
       case 'update':
       case 'vacuum':
           $result = true;
           break;
       default:
           $result = false;
           break;
     }
   }
   return $result;
 }

/**
 * Check whether queries are multiple or one query
 *
 * @param  mixed   the target query
 * @return boolean whether the argument was multiple queries
 * @access public
 */
 function isMultiQuery($query){
   $result = false;
   if (is_string($query)) {
     $query = $this->splitSyntax($query);
   }
   if (!empty($query) && is_array($query)) {
     $tokens = $this->cleanArray($query);
     unset($query);
     $length = count($tokens);
     while (--$length >= 0) {
       $token = array_shift($tokens);
       if ($token === ';') {
         $next = array_shift($tokens);
         if ($next != null && $this->isWord($next)) {
           $result = true;
           break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the SELECT Query has multiples or single
 *
 * @param  mixed    the value of FROM clause
 * @return boolean  it has multiples or not
 * @access private
 */
 function isMultipleSelect($from){
   $result = false;
   if ($from == null) {
     $this->pushError('The query of FROM clause is empty');
   } else {
     if (is_string($from)) {
       $from = $this->splitSyntax($from);
     }
     if (is_array($from)) {
       $level = 0;
       $from = $this->cleanArray($from);
       $length = count($from);
       for ($i = 0; $i < $length; $i++) {
         $token = $from[$i];
         switch (strtolower($token)) {
           case '(':
               $level++;
               break;
           case ')':
               $level--;
               break;
           case ',':
           case 'join':
               if ($level === 0) {
                 $j = $i + 1;
                 if (isset($from[$j]) && $from[$j] != null) {
                   $result = true;
                   break 2;
                 }
               }
               break;
           default:
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the FROM clause has sub-query
 *
 * @param  mixed    the value of FROM clause
 * @return boolean  whether the FROM clause has sub-query or not
 * @access private
 */
 function isSubSelectFrom($from){
   $result = false;
   if ($from == null) {
     $this->pushError('The query of FROM clause is empty');
   } else {
     if (is_string($from)) {
       $from = $this->splitSyntax($from);
     }
     if (is_array($from)) {
       $level = 0;
       $from = $this->cleanArray($from);
       $count = count($from);
       for ($i = 0; $i < $count; $i++) {
         $token = $from[$i];
         $next = $this->getValue($from, $i + 1);
         switch ($token) {
           case '(':
               $level++;
               if ($next != null && 0 === strcasecmp($next, 'select')
                && isset($from[$i + 2]) && $from[$i + 2] != null) {
                 $result = true;
               }
               break;
           case ')':
               $level--;
               break;
           default:
               break;
         }
       }
       if ($level !== 0) {
         $this->pushError('Syntax error on FROM clause');
         $result = false;
       }
     }
   }
   return $result;
 }

/**
 * Check whether the statement is "SELECT COUNT(*)" by the arguments.
 * Only asterisk(*) is accepted to the expression.
 *
 * @param  array    the parsed arguments
 * @param  boolean  whether the query has multi-tables
 * @return boolean  whether the statement is simple COUNT
 * @access private
 */
 function isSimpleCount($args, $multi_select = false){
   $result = false;
   if (!is_array($args)) {
     $this->pushError('Failed to parse arguments on SELECT(%s)', $args);
   } else {
     if ($this->isAssoc($args)) {
       $defaults = $this->getSelectDefaultArguments();
       $result = false;
       foreach ($args as $clause => $expr) {
         $clause = strtolower($clause);
         switch ($clause) {
           case 'from':
               if (!$multi_select && !is_string($expr)) {
                 $result = false;
                 break 2;
               }
               break;
           case 'select':
               if (!is_array($expr)) {
                 $expr = $this->parseColumns($expr);
               }
               if (!$this->hasError()) {
                 if (is_array($expr)) {
                   $keys = array('cols', 'func');
                   foreach ($keys as $key) {
                     if (!array_key_exists($key, $expr)
                      || count($expr[$key]) !== 1) {
                       $result = false;
                       break 3;
                     }
                   }
                   $func_key = end($keys);
                   if (reset($expr[$func_key]) === '*'
                    && strtolower(key($expr[$func_key])) === 'count') {
                     $result = true;
                   }
                 }
               }
               break;
           case 'where':
               break;
           default:
               if (!array_key_exists($clause, $defaults)
                || $defaults[$clause] !== $expr) {
                 $result = false;
                 break 2;
               }
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Check whether or not the statement is simple "LIMIT 1" by the arguments.
 *
 * @param  array    the parsed arguments
 * @param  boolean  whether the query has multi-tables
 * @return boolean  whether the statement is simple "LIMIT 1"
 * @access private
 */
 function isSimpleLimitOnce($args, $multi_select = false){
   $result = false;
   if (!is_array($args)) {
     $this->pushError('Failed to parse arguments on SELECT(%s)', $args);
   } else {
     if ($this->isAssoc($args)) {
       $defaults = $this->getSelectDefaultArguments();
       $result = false;
       foreach ($args as $clause => $expr) {
         $clause = strtolower($clause);
         switch ($clause) {
           case 'from':
               if (!$multi_select && !is_string($expr)) {
                 $result = false;
                 break 2;
               }
               break;
           case 'select':
               break;
           case 'where':
               break;
           case 'limit':
               $limit = $expr;
               if ($limit != null) {
                 $parsed = $this->parseLimitTokens($limit);
                 if (!$this->hasError()) {
                   if (is_array($parsed)
                    && array_key_exists('length', $parsed)
                    && array_key_exists('offset', $parsed)) {
                     if ($parsed['offset'] == null
                      && $parsed['length'] == 1) {
                       $result = true;
                     }
                   }
                 }
               }
               break;
           default:
               if (!array_key_exists($clause, $defaults)
                || $defaults[$clause] !== $expr) {
                 $result = false;
                 break 2;
               }
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Check whether or not the statement is simple primary-key specify
 * expression by the arguments.
 *
 * @param  array    the parsed arguments
 * @param  boolean  whether the query has multi-tables
 * @return boolean  whether the statement is simple primary-key specify
 * @access private
 */
 function isSimplePrimaryKeySpecify($args, $multi_select = false){
   $result = false;
   if (!is_array($args)) {
     $this->pushError('Failed to parse arguments on SELECT(%s)', $args);
   } else {
     if ($this->isAssoc($args)) {
       $defaults = $this->getSelectDefaultArguments();
       $result = false;
       foreach ($args as $clause => $expr) {
         $clause = strtolower($clause);
         switch ($clause) {
           case 'from':
               if (!$multi_select && !is_string($expr)) {
                 $result = false;
                 break 2;
               }
               break;
           case 'select':
               break;
           case 'where':
               if ($this->isSimplePrimaryKeyCompareExpression($expr)) {
                 $result = true;
               }
               break;
           case 'limit':
               break;
           case 'order':
               break;
           default:
               if (!array_key_exists($clause, $defaults)
                || $defaults[$clause] !== $expr) {
                 $result = false;
                 break 2;
               }
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether or not the expression is simple primary-key comparing
 *
 * @param  mixed    the expression as WHERE clause
 * @return boolean  whether the expression is simple primary-key comparing
 * @access private
 */
 function isSimplePrimaryKeyCompareExpression($expr){
   $result = false;
   if ($expr != null
    && isset($this->_primaryKey) && $this->_primaryKey != null) {
     if (is_string($expr)) {
       $expr = $this->splitSyntax($expr);
     }
     $this->removeValidMarks($expr);
     if (is_array($expr)) {
       $expr   = $this->cleanArray($expr);
       $tokens = array();
       foreach ($expr as $token) {
         switch ($token) {
           case '#':
           case $this->NL:
           case '$':
               break;
           default:
               $tokens[] = $token;
               break;
         }
       }
       $level  = 0;
       $lefts  = array();
       $rights = array();
       $stack  = array();
       foreach ($tokens as $i => $token) {
         $prev = $this->getValue($tokens, $i - 1);
         $next = $this->getValue($tokens, $i + 1);
         switch (strtolower($token)) {
           case '(':
               $level++;
               break;
           case ')':
               $level--;
               break;
           case '=':
           case '==':
           case '===':
               if (count($stack) === 1) {
                 if ($prev != null && $next != null) {
                   $lefts[]  = $prev;
                   $rights[] = $next;
                 }
               }
               break;
           default:
               $stack[] = $token;
               break;
         }
       }
       if (count($stack) === 2
        && count($lefts) === 1 && count($rights) === 1) {
         $left = reset($lefts);
         $right = reset($rights);
         if ($right === $this->_primaryKey) {
           $tmp = $left;
           $left = $right;
           $right = $tmp;
         }
         if ($left === $this->_primaryKey) {
           if ($this->isStringToken($right)) {
             $right = substr($right, 1, -1);
           }
           if (is_numeric($right)) {
             $result = true;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the SELECT query has DISTINCT clause
 *
 * @param  mixed    the value of SELECT clause
 * @return boolean  it has DISTINCT or not
 * @access private
 */
 function isDistinct($columns){
   $result = false;
   if (!$this->hasError()) {
     if ($columns == null) {
       $this->pushError('Empty SELECT clause (select-list)');
     } else {
       if (is_string($columns)) {
         $columns = $this->splitSyntax($columns);
       }
       if (is_array($columns)) {
         $reset = reset($columns);
         if (0 === strcasecmp($reset, 'distinct')) {
           $result = true;
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether the SELECT query has ALL clause
 *
 * @param  mixed    the value of SELECT clause
 * @return boolean  it has ALL clause or not
 * @access private
 */
 function isSelectListAllClause($columns){
   $result = false;
   if (!$this->hasError()) {
     if ($columns == null) {
       $this->pushError('Empty SELECT clause (select-list)');
     } else {
       if (is_string($columns)) {
         $columns = $this->splitSyntax($columns);
       }
       if (is_array($columns)) {
         $reset = reset($columns);
         $next = next($columns);
         if (0 === strcasecmp($reset, 'all')) {
           if ($next != null) {
             if ($next !== ',') {
               $result = true;
             }
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Checks whether or not the statement has the function
 *
 * @param  mixed    the statement
 * @return boolean  whether or not is the function in statement
 * @access public
 */
 function isFunctionInStatement($expr){
   $result = false;
   if ($expr == null) {
     $result = false;
   } else {
     if (is_string($expr)) {
       $expr = $this->splitSyntax($expr);
     }
     if (is_array($expr)) {
       $level = 0;
       $func = null;
       $tokens = $this->cleanArray($expr);
       foreach ($tokens as $i => $token) {
         $prev = $this->getValue($tokens, $i - 1);
         switch ($token) {
           case '(':
               $level++;
               if ($prev != null) {
                 $func = $prev;
               }
               break;
           case ')':
               if ($level > 0 && $this->isEnableName($func)) {
                 $is_func = true;
                 switch (strtolower($func)) {
                   case 'and':
                   case 'or':
                   case 'xor':
                   case 'is':
                   case 'not':
                   case 'in':
                   case 'where':
                   case 'having':
                   case 'on':
                   case 'avg':
                   case 'count':
                   case 'max':
                   case 'min':
                   case 'sum':
                       $is_func = false;
                       break;
                   default:
                       $is_func = true;
                       break;
                 }
                 if ($is_func) {
                   $result = true;
                   break 2;
                 }
               }
               $level--;
               break;
           default:
               break;
         }
       }
     }
   }
   return $result;
 }

/**
 * Check whether compound-operator token is in statement
 *
 * compound-operator:
 *  - UNION [ALL] : combine two query expressions into a single result set.
 *                  if ALL is specified, duplicate rows returned by
 *                  union expression are retained.
 *  - EXCEPT      : evaluates the output of two query expressions and
 *                  returns the difference between the results.
 *  - INTERSECT   : evaluates the output of two query expressions and
 *                  returns only the rows common to each.
 *
 * @param  mixed    SELECT tokens, or statement
 * @param  boolean  whether return as compound type or boolean
 * @return boolean  tokens has compound-operator or not
 * @access private
 */
 function hasCompoundOperator($tokens, $return_string = false){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (is_array($tokens)) {
     $level = 0;
     $tokens = array_values($tokens);
     foreach ($tokens as $token) {
       $ltoken = strtolower($token);
       switch ($ltoken) {
         case '(':
             $level++;
             break;
         case ')':
             $level--;
             break;
         case 'union':
         case 'except':
         case 'intersect':
             if ($level === 0) {
               $result = $ltoken;
               break 2;
             }
             break;
         default:
             break;
       }
     }
   }
   if (!$return_string) {
     $result = (bool)$result;
   }
   return $result;
 }

/**
 * parse and split to the tokens of columns
 *
 * @param  array  columns of target
 * @return array  results of columns, or false on error
 * @access private
 */
 function splitColumns($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if ($tokens == null || !is_array($tokens)) {
     $this->pushError('Cannot parse tokens(%s) of COLUMNS on SELECT',
                      $tokens);
   } else {
     $colms = array();
     $stack = array();
     $level = 0;
     $tokens[] = ',';
     $tokens = array_values($tokens);
     foreach ($tokens as $token) {
       switch ($token) {
         case '`':
         case ';':
             break;
         case '(':
             $level++;
             $stack[] = $token;
             break;
         case ')':
             $level--;
             $stack[] = $token;
             break;
         case ',':
             if ($level) {
               $stack[] = $token;
             } else {
               $colms[] = $stack;
               $stack = array();
             }
             break;
         default:
             $stack[] = $token;
             break;
       }
     }
     $result = $colms;
   }
   return $result;
 }

/**
 * Parses the columns name for SELECT method
 *
 * @param  mixed   columns of target
 * @param  string  if given as string, will returns only it
 * @return array   results of columns, or false on error
 * @access private
 */
 function parseColumns($tokens, $colname = null){
   $result = false;
   $tokens = $this->splitColumns($tokens);
   if (!empty($tokens) && is_array($tokens)) {
     $alias = array();
     $funcs = array();
     $colms = array();
     $stack = array();
     $exprs = array();
     $level = 0;
     $func_name  = null;
     $alias_name = null;
     $aggregates = $this->getAggregateDefaults();
     foreach ($tokens as $columns) {
       $columns = array_values($columns);
       $this->appendAliasToken($columns);
       $length = count($columns);
       for ($i = 0; $i < $length; $i++) {
         $j = $i + 1;
         $token = $columns[$i];
         switch (strtolower($token)) {
           case '`':
           case ';':
               break;
           case '(':
               $tmp_func = strtolower($this->joinWords($stack));
               if ($level === 0
                && array_key_exists($tmp_func, $aggregates)) {
                 $func_name = $this->joinWords($stack);
               }
               unset($tmp_func);
               $level++;
               $stack[] = $token;
               break;
           case ')':
               $level--;
               if ($func_name != null) {
                 $funcs[$func_name] = $this->joinWords($exprs);
                 $func_name = null;
                 $exprs = array();
               }
               $stack[] = $token;
               break;
           case 'as':
               if ($level === 0) {
                 if ($stack == null || !isset($columns[$j])) {
                   $this->pushError('Failed to parse tokens'
                                 .  ' for columns(AS)');
                   break 3;
                 } else {
                   $alias[ $this->joinWords($stack) ] = $columns[$j];
                   $i += 2;
                 }
                 break;
               }
           default:
               if ($level) {
                 $exprs[] = $token;
               }
               $stack[] = $token;
               break;
         }
       }
       if ($stack == null) {
         $this->pushError('Failed to parse tokens for columns');
         break;
       }
       $colms[] = $this->joinWords($stack);
       $stack = array();
       $exprs = array();
     }
     switch (strtolower(substr($colname, 0, 1))) {
       case 'a':
           $result = $alias;
           break;
       case 'c':
           $result = $colms;
           break;
       case 'f':
           $result = $funcs;
           break;
       default:
           $result = array(
             'cols' => $colms,
             'as'   => $alias,
             'func' => $funcs
           );
           break;
     }
   }
   return $result;
 }

/**
 * Append the alias token "AS" into parsed tokens if proper
 *
 * @param  array   parsed tokens
 * @return void
 * @access private
 */
 function appendAliasToken(&$tokens){
   if (is_array($tokens)) {
     $length = count($tokens);
     if ($length > 1) {
       $end = end($tokens);
       $prev = prev($tokens);
       if ($prev != null && 0 !== strcasecmp($prev, 'as')
        && $end  != null && $this->isEnableName($end)
        && substr($prev, -1) !== '.') {
         array_splice($tokens, $length - 1, 0, array('as'));
       }
       reset($tokens);
     }
   }
 }

/**
 * Applies the alias of SELECT-List and FROM clauses
 *
 * @param  mixed   the tokens of SELECT-List
 * @param  mixed   the tokens of FROM clause
 * @return void
 * @access private
 */
 function applyAliasNames($tables, $columns){
   $this->_onParseAliases = true;
   if (empty($this->_onUnionSelect)) {
     $this->_selectTableAliases  = array();
     $this->_selectColumnAliases = array();
   }
   if ($tables != null) {
     $do_parse = false;
     if (!is_array($tables)) {
       $do_parse = true;
     } else {
       if (!is_array(reset($tables))) {
         $tables = array($tables);
       }
       $reset = reset($tables);
       if (!array_key_exists('table_as', $reset)
        && !array_key_exists('table_name', $reset)) {
         $do_parse = true;
       } else {
         $aliases = array();
         foreach ($tables as $table) {
           $name  = $this->getValue($table, 'table_name');
           $alias = $this->getValue($table, 'table_as');
           if ($name != null && $alias != null) {
             $aliases[$name] = $alias;
           }
         }
       }
     }
     if ($do_parse) {
       $aliases = $this->parseFromClause($tables);
     }
     if (!empty($aliases) && is_array($aliases)) {
       if (empty($this->_onUnionSelect)) {
         $this->_selectTableAliases = $aliases;
       } else {
         if (!is_array($this->_selectTableAliases)) {
           $this->_selectTableAliases = array();
         }
         foreach ($aliases as $org_name => $as_name) {
           $this->_selectTableAliases[$org_name] = $as_name;
         }
       }
     }
   }
   if ($columns != null) {
     $aliases = array();
     if (is_array($columns)) {
       if (array_key_exists('as', $columns)) {
         $columns = $columns['as'];
       }
       if (is_array($columns)) {
         $aliases = $columns;
       }
     } else if ($columns !== '*') {
       $aliases = $this->parseColumns($columns, 'as');
     }
     if (!empty($aliases) && $this->isAssoc($aliases)) {
       if (empty($this->_onUnionSelect)) {
         $this->_selectColumnAliases = $aliases;
       } else {
         if (!is_array($this->_selectColumnAliases)) {
           $this->_selectColumnAliases = array();
         }
         foreach ($aliases as $org_name => $as_name) {
           $this->_selectColumnAliases[$org_name] = $as_name;
         }
       }
     }
   }
   $this->_onParseAliases = null;
 }

/**
 * Replace the alias to enable name on "SELECT COUNT(*) FROM xxx AS x"
 *
 * @param  string  the name that include "AS xxx" syntax
 * @return string  the replaced name, or FALSE on error
 * @access private
 */
 function replaceTableAlias($name){
   $result = false;
   if (!is_string($name)) {
     $this->pushError('illegal type of alias(%s)', gettype($name));
   } else {
     if ($name == null) {
       $this->pushError('Empty table name on FROM clause');
     } else {
       $result = $name;
       $as_exists = false;
       $tokens = $this->splitSyntax($name);
       if (is_array($tokens)) {
         $tokens = $this->cleanArray($tokens);
         foreach ($tokens as $i => $token) {
           switch (strtolower($token)) {
             case 'as':
                 $as_exists = true;
                 break 2;
             default:
                 break;
           }
         }
       }
       if ($as_exists) {
         $this->_onParseAliases = true;
         $aliases = $this->parseFromClause($name);
         if (!empty($aliases) && is_array($aliases)) {
           $as_name  = reset($aliases);
           $org_name = key($aliases);
           if ($org_name != null && is_string($org_name)) {
             $result = $org_name;
           }
         }
         $this->_onParseAliases = null;
       }
     }
   }
   return $result;
 }

/**
 * Restore the tokens to original tokens
 *
 * @param  array   the target tokens (i.e. WHERE, HAVING clauses)
 * @return string  the restored tokens
 * @access private
 */
 function restoreAliasTokens($tokens){
   $result = false;
   if (!$this->hasError()) {
     if (is_string($tokens)) {
       $tokens = $this->splitSyntax($tokens);
     }
     if ($tokens != null) {
       if (!empty($this->_selectColumnAliases)
        && $this->isAssoc($this->_selectColumnAliases)) {
         $aliases = array();
         foreach ($this->_selectColumnAliases as $org_name => $as_name) {
           if (is_string($org_name) && is_string($as_name)) {
             $aliases[$as_name] = $this->splitSyntax($org_name);
           }
         }
         $tables = array();
         if (!empty($this->_selectTableAliases)
          && $this->isAssoc($this->_selectTableAliases)) {
           foreach ($this->_selectTableAliases as $org_name => $as_name) {
             if (is_string($org_name) && is_string($as_name)) {
               $tables[$as_name] = $org_name;
             }
           }
         }
         $tokens = array_values($tokens);
         for ($i = 0; $i < count($tokens); $i++) {
           $j = $i + 1;
           if (array_key_exists($tokens[$i], $aliases)) {
             $restore = $aliases[$tokens[$i]];
             array_splice($tokens, $i, 1, $restore);
           }
           if ($tables != null
            && array_key_exists($tokens[$i], $tables)
            && isset($tokens[$j]) && $tokens[$j] === '.') {
             $tokens[$i] = '';
             $tokens[$j] = '';
             $i = $j;
           }
         }
         $result = $this->cleanArray($tokens);
       }
     }
   }
   return $result;
 }

/**
 * Replace the alias to enable name
 *
 * @param  string  the alias name
 * @return string  the replaced name
 * @access private
 */
 function replaceAliasName($name){
   $result = false;
   if (!is_string($name)) {
     $this->pushError('illegal type of alias(%s)', gettype($name));
   } else {
     if (!empty($this->_selectColumnAliases)) {
       foreach ($this->_selectColumnAliases as $org_name => $as_name) {
         if ($name === $as_name) {
           $name = $org_name;
           break;
         }
       }
     }
     if (strpos($name, '.') !== false) {
       list($table, $column) = explode('.', $name);
       if ($this->isEnableName($table)
        && ($this->isEnableName($column) || $column === '*')) {
         $name = $column;
       }
     }
     $result = $name;
   }
   return $result;
 }

/**
 * Replace the alias to enable names
 *
 * @param  array   the alias names
 * @return array   the replaced names
 * @access private
 */
 function replaceAliasNames($names){
   $result = array();
   if (!$this->hasError()) {
     if (!is_array($names)) {
       $this->pushError('illegal type of aliases(%s)', gettype($names));
     } else {
       $result = array();
       foreach ($names as $org_name => $as_name) {
         $org_name = (string)$org_name;
         $new_name = $this->replaceAliasName($org_name);
         if ($this->hasError()) {
           break;
         }
         $result[$new_name] = $as_name;
       }
     }
   }
   return $result;
 }

/**
 * Restore the table name from alias name
 *
 * @param  string   the alias table name
 * @return string   the original table name
 * @access private
 */
 function restoreTableAliasName($name){
   $result = $name;
   if (strpos($name, '.') !== false) {
     list($correlation, $field_name) = explode('.', $name);
     $aliases = $this->getTableAliasNames();
     if (!empty($aliases) && is_array($aliases)) {
       foreach ($aliases as $table_name => $alias_name) {
         if ($alias_name === $correlation) {
           $result = sprintf('%s.%s', $table_name, $field_name);
           break;
         }
       }
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function findIdentifier($field_name, $keys){
   $result = $field_name;
   if (is_string($field_name) && $field_name != null
    && is_array($keys) && !empty($keys) && is_string(key($keys))) {
     if (array_key_exists($field_name, $keys)) {
       $result = $field_name;
     } else {
       if (strpos($field_name, '.') === false) {
         $simple_col = $this->replaceAliasName($field_name);
         if (array_key_exists($simple_col, $keys)) {
           $result = $simple_col;
         }
       } else {
         $column_aliases = $this->getColumnAliasNames();
         if (is_array($column_aliases) && !empty($column_aliases)
          && array_key_exists($field_name, $column_aliases)
          && array_key_exists($column_aliases[$field_name], $keys)) {
           $result = $column_aliases[$field_name];
         } else {
           list($table_name, $column_name) = explode('.', $field_name);
           $table_aliases = $this->flipArray($this->getTableAliasNames());
           if (is_array($table_aliases) && !empty($table_aliases)) {
             $correlation = $table_name;
             if (array_key_exists($table_name, $table_aliases)) {
               $correlation = $table_aliases[$table_name];
             }
             $identifier = sprintf('%s.%s', $correlation, $column_name);
             if (array_key_exists($identifier, $keys)) {
               $result = $identifier;
             } else {
               // considered simple SELECT syntax.
               if (array_key_exists($column_name, $keys)) {
                 $result = $column_name;
               }
             }
           }
           // considered simple SELECT syntax.
           if (!array_key_exists($result, $keys)
            && array_key_exists($column_name, $keys)) {
             $result = $column_name;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses the HAVING clause tokens from the SQL syntax
 *
 * @param  array    the parsed tokens which is a part of HAVING clause
 * @return array    the information of aggregate functions as array
 * @access private
 */
 function parseHaving($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s) on HAVING', $tokens);
   } else {
     $open  = '(';
     $close = ')';
     $level = 0;
     $token = null;
     $next  = null;
     $in_func    = false;
     $func_reset = false;
     $func_name  = null;
     $func_index = null;
     $func_level = null;
     $func_args  = array();
     $func_info  = array();
     $tokens = array_values($tokens);
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       $j = $i + 1;
       $token = $tokens[$i];
       if (isset($tokens[$j])) {
         $next = $tokens[$j];
       } else {
         $next = null;
       }
       switch (strtolower($token)) {
         case '(':
             $level++;
             break;
         case ')':
             $level--;
             if ($in_func && $func_level === $level) {
               $in_func = false;
               $cols = array(
                 strtoupper($func_name),
                 $open,
                 $func_args,
                 $close
               );
               $cols = $this->toOneArray($cols);
               $cols = $this->joinWords($cols);
               $func_info[] = array(
                 'name'  => $func_name,
                 'args'  => $func_args,
                 'cols'  => $cols,
                 'index' => $func_index,
                 'size'  => $i - $func_index + 1
               );
               unset($cols);
               $func_reset = true;
             }
             break;
         case 'avg':
         case 'count':
         case 'max':
         case 'min':
         case 'sum':
             if ($next === $open) {
               $func_name = $token;
               $func_args = array();
               $func_level = $level;
               $func_index = $i;
               $in_func = true;
             } else if ($in_func) {
               $func_args[] = $token;
             }
             break;
         default:
             if ($in_func) {
               $func_args[] = $token;
             }
             break;
       }
       if ($func_reset) {
         $func_reset = false;
         $in_func    = false;
         $func_name  = null;
         $func_index = null;
         $func_level = null;
         $func_args  = array();
       }
     }
     $result = $func_info;
   }
   return $result;
 }

/**
 * Check whether the token is enabled for HAVING clause
 *
 * @param  array    the parsed tokens by parseHaving() method
 * @return boolean  whether the tokens were enabled or not
 * @access private
 */
 function isEnableHavingInfo($having_infos){
   $result = true;
   if (!is_array($having_infos) || empty($having_infos)) {
     if (!$this->hasError()) {
       $error_token = $having_infos;
       if (is_array($error_token)) {
         $error_token = $this->joinWords($error_token);
       }
       $this->pushError('Failed to parse HAVING (%s)', $error_token);
     }
     $result = false;
   } else {
     $name  = 'name';
     $args  = 'args';
     $cols  = 'cols';
     $index = 'index';
     $size  = 'size';
     $props = array($name, $args, $cols, $index, $size);
     if (!is_array(reset($having_infos))) {
       $having_infos = array($having_infos);
     }
     foreach ($having_infos as $info) {
       foreach ($props as $prop) {
         if (!isset($info[$prop])) {
           $this->pushError('Undefined property(%s) in parsed token',
                            $prop);
           $result = false;
           break 2;
         }
       }
       if (!is_string($info[$name]) || $info[$name] == null
        || !is_string($info[$cols]) || $info[$cols] == null) {
         $error_token  = (string)$info[$name];
         if ($error_token == null) {
           $error_token = (string)$info[$cols];
         }
         $this->pushError('illegal token(%s) on HAVING', $error_token);
         $result = false;
         break;
       }
     }
   }
   return $result;
 }

/**
 * Replace the HAVING tokens to use on expression
 *
 * @param  array   the parsed tokens from HAVING clause
 * @param  array   the HAVING information as array
 * @param  mixed   the tokens to replace
 * @param  boolean wether clean the tokens or not
 * @return array   the tokens which ware replaced according to replacement
 * @access private
 */
 function replaceHavingTokens($having, $info,
                              $replace = null, $clean = false){
   $result = false;
   if (!$this->hasError()) {
     if (is_array($having) && $this->isEnableHavingInfo($info)
      && isset($info['size'])) {
       if (is_array($replace)) {
         $replace = $this->toOneArray($replace);
         $replace = $this->joinWords($replace);
       } else {
         $replace = (string)$replace;
       }
       $size = null;
       foreach ($having as $i => $token) {
         if ($i === $info['index']) {
           $having[$i] = $replace;
           $size = $info['size'];
           continue;
         }
         if (is_int($size)) {
           $having[$i] = '';
           if (--$size <= 1) {
             $size = null;
           }
         }
       }
       if ($clean) {
         $result = $this->cleanArray($having);
       } else {
         $result = $having;
       }
     }
   }
   return $result;
 }

/**
 * Parses the items of "GROUP BY" clause from the SQL syntax
 *
 * @param  array    the tokens which is a part of GROUP BY clause
 * @return array    the parsed items as array
 * @access private
 */
 function parseGroupBy($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s) on GROUP BY', $tokens);
   } else {
     $items  = array();
     $index  = 0;
     $level  = 0;
     $tokens = array_values($tokens);
     foreach ($tokens as $i => $token) {
       $skip = false;
       switch (strtolower($token)) {
         case '(':
             $level++;
             break;
         case ')':
             $level--;
             break;
         case ',':
             if ($level === 0) {
               $skip = true;
               $index++;
             }
             break;
         default:
             break;
       }
       if (!$skip) {
         $items[$index][] = $token;
       }
     }
     $fields = array();
     $is_expr = array();
     foreach ($items as $item) {
       if (!empty($item) && is_array($item)) {
         $field = $this->joinWords($item);
         $is_expr[] = $this->isExprToken($field);
         $fields[] = $field;
       }
     }
     $result = array(
       'fields'  => $fields,
       'is_expr' => $is_expr
     );
   }
   return $result;
 }

/**
 * Parses the "ORDER BY" clause from the SQL syntax
 *
 * @param  array    the parsed tokens which is a part of ORDER BY clause
 * @return array    the parsed clause as associate array
 * @access private
 */
 function parseOrderByTokens($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s) on ORDER BY', $tokens);
   } else {
     $index  = 0;
     $orders = array();
     $colums = array();
     $stack  = array();
     if (end($tokens) !== ' ') {
       $tokens[] = ' '; // the end point
     }
     $tokens = array_values($tokens);
     foreach ($tokens as $i => $token) {
       switch (strtolower($token)) {
         case ';':
         case '`':
             break;
         case ' ':
             $colums[$index] = $stack;
             $stack = array();
             break;
         case ',':
             if (!empty($stack)) {
               $colums[$index++] = $stack;
               $stack = array();
             }
             break;
         case 'asc':
             $orders[$index] = SORT_ASC;
             break;
         case 'desc':
             $orders[$index] = SORT_DESC;
             break;
         default:
             $stack[] = $token;
             break;
       }
     }
     $result = array();
     foreach ($colums as $i => $cols) {
       if (!array_key_exists($i, $orders)) {
         $orders[$i] = SORT_ASC;
       }
       $cols = implode('', $cols);
       $result[$cols] = $orders[$i];
     }
   }
   return $result;
 }

/**
 * Parses the LIMIT and OFFSET tokens from the SQL syntax
 *
 * @param  array    the parsed tokens which is a part of LIMIT clause
 * @return array    the parsed clause as array
 * @access private
 */
 function parseLimitTokens($tokens){
   $result = false;
   if (is_string($tokens)) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s) on LIMIT', $tokens);
   } else {
     $offset = array();
     $length = array();
     $stack  = array();
     $is_off = false;
     $is_all = false;
     if (end($tokens) !== ' ') {
       $tokens[] = ' '; // the end point
     }
     $tokens = array_values($tokens);
     foreach ($tokens as $i => $token) {
       switch (strtolower($token)) {
         case ';':
         case '`':
             break;
         case ' ':
             $length = $stack;
             $stack = array();
             if ($is_off) {
               $tmp = $length;
               $length = $offset;
               $offset = $tmp;
             }
             if ($is_all) {
               $length = array();
             }
             break;
         case 'offset':
             $is_off = true;
         case ',':
             if (!empty($stack)) {
               $offset = $stack;
               $stack = array();
             }
             break;
         case 'all':
             $is_all = true;
             $stack = array();
             break;
         default:
             $stack[] = $token;
             break;
       }
     }
     if ($offset != null) {
       $offset = (int) implode('', $offset);
     } else {
       $offset = 0;
     }
     if ($length != null) {
       $length = (int) implode('', $length);
     } else {
       $length = null;
     }
     $result = array(
       'offset' => $offset,
       'length' => $length
     );
   }
   return $result;
 }

/**
 * Parses the table name which is used on FROM clause
 *
 * @param  mixed   the token of FROM clause
 * @return array   parsed associate array, or FALSE on error
 * @access private
 */
 function parseFromClause($tokens){
   $result = false;
   if (!$this->hasError()) {
     if (is_string($tokens)) {
       $tokens = $this->splitSyntax($tokens);
     }
     if (!is_array($tokens) || $tokens == null) {
       $this->pushError('Failed to parse tokens(%s) on FROM clause', $tokens);
     } else {
       $tokens = $this->cleanArray($tokens);
       if ($this->isMultipleSelect($tokens)) {
         $result = $this->parseJoinTables($tokens);
       } else {
         $stack  = array();
         $table  = null;
         $alias  = null;
         $level  = 0;
         $this->appendAliasToken($tokens);
         $length = count($tokens);
         for ($i = 0; $i < $length; $i++) {
           $j = $i + 1;
           $token = $tokens[$i];
           switch (strtolower($token)) {
             case '`':
                 break;
             case '(':
                 $level++;
                 $stack[] = $token;
                 break;
             case ')':
                 $level--;
                 $stack[] = $token;
                 break;
             case 'as':
                 if ($level === 0) {
                   if (isset($tokens[$j])) {
                     $alias = $tokens[$j];
                     $i = $j;
                   }
                 } else {
                   $stack[] = $token;
                 }
                 break;
             default:
                 if ($level === 0) {
                   if ($table == null) {
                     $table = $token;
                   }
                 }
                 $stack[] = $token;
                 break;
           }
         }
         $sub_select = false;
         if ($this->isSubSelectFrom($tokens)) {
           $sub_select = true;
           $table = $this->joinWords($stack);
         }
         $is_error = false;
         if ($table == null) {
           $is_error = true;
         } else if (!$sub_select && !$this->isEnableName($table)) {
           $is_error = true;
         }
         if ($is_error) {
           $this->pushError('illegal table name(%s) on FROM clause', $table);
         } else {
           $this->_selectTableNames = array($table => $alias);
           if (empty($this->_onParseAliases)) {
             $result = array(
               'table_name' => $table,
               'table_as'   => $alias
             );
           } else {
             if ($alias != null) {
               $result = array($table => $alias);
             }
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Parses the tables name for SELECT method
 *
 * @param  mixed  tables of target
 * @return array  results of tables, or false on error
 * @access private
 */
 function parseJoinTables($tokens){
   $result = false;
   if (!is_array($tokens) && $tokens != null) {
     $tokens = $this->splitSyntax($tokens);
   }
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse tokens on SELECT using JOIN(%s)',
                      $tokens);
   } else {
     $tokens = $this->removeBacktickToken($tokens);
     $open   = '(';
     $close  = ')';
     while (reset($tokens) === $open
          && end($tokens) === $close) {
       array_shift($tokens);
       array_pop($tokens);
     }
     reset($tokens);
     array_push($tokens, ','); // dummy to parse
     $tokens = array_values($tokens);
     $length = count($tokens);
     $in_func = false;
     $in_expr = false;
     $usings = array();
     $tables = array();
     $stack  = array();
     $index      = 0;
     $on_index   = 0;
     $level      = 0;
     $base_table = '';
     $table_name = '';
     $table_as   = '';
     $join_type  = '';
     $prev_type  = '';
     $join_expr  = array();
     for ($i = 0; $i < $length; $i++) {
       $h = $i - 1;
       $j = $i + 1;
       $k = $j + 1;
       $token = $tokens[$i];
       $prev  = $this->getValue($tokens, $h);
       $next  = $this->getValue($tokens, $j);
       switch (strtolower($token)) {
         case ';':
         case '$':
         case '`':
             break;
         case '(':
             $level++;
             break;
         case ')':
             $level--;
             break;
         case 'as':
             if ($level === 0 && $next != null) {
               $table_as = $next;
               $i = $j;
               $token = null;
             }
             break;
         case ',':
             if ($level === 0 && $j !== $length) {
               $join_type = 'cross';
             }
             // FALLTHROUGH
         case 'join':
             if ($level === 0) {
               $in_expr = false;
               $this->_setJoinTableTokens($table_name, $table_as, $stack);
               if ($join_type == null || !isset($tokens[$h])) {
                 $join_type = 'cross';
               } else {
                 $ltoken_h = strtolower($tokens[$h]);
                 if ($ltoken_h === 'inner'
                  || $ltoken_h === 'cross'
                  || $table_name === $tokens[$h]) {
                   $join_type = 'cross';
                 }
                 unset($ltoken_h);
               }
               if ($next != null) {
                 $tables[$index] = array(
                   'table_name' => $table_name,
                   'table_as'   => $table_as,
                   'join_type'  => $join_type,
                   'join_expr'  => $join_expr,
                   'using'      => $usings
                 );
                 $tables[$index]['join_type'] = $prev_type;
                 $prev_type = $join_type;
                 if ($index === 0) {
                   $base_table = $table_name;
                 }
                 $index++;
                 $in_expr = false;
                 $table_name = '';
                 $table_as = '';
                 $join_expr = array();
                 $usings = array();
                 $token = null;
               }
             }
             break;
         case 'inner':
         case 'cross':
             if ($level === 0) {
               if (0 === strcasecmp($next, 'join')) {
                 $join_type = 'cross';
                 $this->_setJoinTableTokens($table_name, $table_as, $stack);
               }
               $in_expr = false;
               $token = null;
             }
             break;
         case 'outer':
             if ($level === 0) {
               if (0 === strcasecmp($next, 'join')) {
                 $this->_setJoinTableTokens($table_name, $table_as, $stack);
               }
               $in_expr = false;
               $token = null;
             }
             break;
         case 'natural':
             if ($level === 0) {
               $in_expr = false;
               if (!empty($this->_onParseAliases)) {
                 break;
               }
               if ($next != null) {
                 $this->_setJoinTableTokens($table_name, $table_as, $stack);
                 $is_natural = false;
                 if (0 === strcasecmp($next, 'join')) {
                   $is_natural = true;
                 } else if (isset($tokens[$k])
                         && 0 === strcasecmp($tokens[$k], 'join')) {
                   $is_natural = true;
                   $i++;
                 } else if (isset($tokens[$k + 1])
                         && 0 === strcasecmp($tokens[$k + 1], 'join')) {
                   $is_natural = true;
                   $i += 2;
                 }
                 if ($is_natural) {
                   $join_type = 'natural';
                   if ($table_name != null) {
                     if ($base_table == null) {
                       if (isset($tokens[$i + 2])
                        && $tokens[$i + 2] != null) {
                         $base_table = $tokens[$i + 2];
                       }
                     }
                     $meta = $this->getMeta($table_name);
                     if (!is_array($meta)) {
                       $this->pushError('Not exists the table(%s) on JOIN',
                                        $table_name);
                       break 2;
                     } else if (empty($meta) || $base_table == null) {
                       $this->pushError('Failed to parse the tokens on JOIN');
                       break 2;
                     } else {
                       $nm_expr = array();
                       foreach (array_keys($meta) as $nm_key) {
                         if ($nm_key === 'rowid'
                          || $nm_key === 'ctime' || $nm_key === 'utime') {
                           continue;
                         }
                         $usings[] = $nm_key;
                         foreach (array($base_table, '.', $nm_key,
                                   '=', $table_name, '.', $nm_key) as $nm_c) {
                           $nm_expr[] = $nm_c;
                         }
                         $nm_expr[] = '&&';
                       }
                       array_pop($nm_expr);
                       foreach ($nm_expr as $nm_c) {
                         $join_expr[] = $nm_c;
                       }
                     }
                     unset($meta, $nm_expr, $nm_key, $nm_c);
                   }
                   $token = null;
                 }
                 unset($is_natural);
               }
             }
             break;
         case 'left':
         case 'right':
         case 'full':
             if ($level === 0) {
               $in_expr = false;
               if ($next != null) {
                 if ((0 === strcasecmp($next, 'join'))
                  || (isset($tokens[$k])
                  && 0 === strcasecmp($next, 'outer')
                  && 0 === strcasecmp($tokens[$k], 'join'))) {
                   $this->_setJoinTableTokens($table_name, $table_as, $stack);
                   $join_type = strtolower($token);
                   $token = null;
                 }
               }
             }
             break;
         case 'using':
             if ($level === 0) {
               if ($next === $open && !empty($stack)) {
                 $slice = array_slice($tokens, $i);
                 $close_pos = array_search($close, $slice);
                 if (!is_int($close_pos)) {
                   $this->pushError('Parsed error on JOIN(%s)', $token);
                   break 2;
                 }
                 $close_pos += $i;
                 $on_index = $k;
                 $i++;
                 $in_expr = true;
                 if ($base_table == null) {
                   if (isset($tables[$index - 1]['table_name'])) {
                     $base_table = $tables[$index - 1]['table_name'];
                   } else {
                     $this->pushError('Parsed error on JOIN(USING)');
                     break 2;
                   }
                 }
                 $this->_setJoinTableTokens($table_name, $table_as, $stack);
                 while (++$i < $length) {
                   $using_token = $tokens[$i];
                   if ($i == $close_pos || $token === $close) {
                     $in_expr = false;
                     break;
                   } else if ($using_token === ',') {
                     $join_expr[] = '&&';
                   } else {
                     $join_expr[] = $base_table;
                     $join_expr[] = '.';
                     $join_expr[] = $using_token;
                     $join_expr[] = '=';
                     $join_expr[] = $table_name;
                     $join_expr[] = '.';
                     $join_expr[] = $using_token;
                     $usings[] = $using_token;
                   }
                 }
                 $in_expr = false;
                 $token = null;
               }
             }
             break;
         case 'on':
             if ($level === 0) {
               $on_index = $i;
               $this->_setJoinTableTokens($table_name, $table_as, $stack);
               $usings = array();
               $in_expr = true;
               $token = null;
             }
             break;
         default:
             break;
       }
       if ($this->hasError()) {
         break;
       }
       if ($token != null) {
         if ($in_expr) {
           $join_expr[] = $token;
         } else {
           $stack[] = $token;
         }
       }
     }
     if ($this->hasError()) {
       $result = false;
     } else {
       if ($prev_type != null) {
         $join_type = $prev_type;
       }
       // Fixes for NATURAL JOIN
       $key = 'join_expr';
       if (!empty($tables[0][$key]) && empty($join_expr)) {
         $join_expr = $tables[0][$key];
         $tables[0][$key] = array();
       }
       $key = 'using';
       if (!empty($tables[0][$key]) && empty($usings)) {
         $usings = $tables[0][$key];
         $tables[0][$key] = array();
       }
       $tables[++$index] = array(
         'table_name' => $table_name,
         'table_as'   => $table_as,
         'join_type'  => $join_type,
         'join_expr'  => $join_expr,
         'using'      => $usings
       );
       $tables = array_values($tables);
       $fields = array();
       foreach ($tables as $table) {
         $name  = $this->getValue($table, 'table_name');
         $alias = $this->getValue($table, 'table_as');
         if ($name != null) {
           $fields[$name] = $alias;
         }
       }
       $this->_selectTableNames = $fields;
       if (empty($this->_onParseAliases)) {
         $result = $this->assignJoinTables($tables);
       } else {
         $result = array();
         foreach ($tables as $table) {
           $name  = $this->getValue($table, 'table_name');
           $alias = $this->getValue($table, 'table_as');
           if ($name != null && $alias != null) {
             $result[$name] = $alias;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function _setJoinTableTokens(&$table_name, &$table_as, &$stack){
   if (!empty($stack)) {
     $is_subquery = $this->isSubSelectFrom($stack);
     if (!$this->hasError()) {
       if ($is_subquery) {
         $this->appendAliasToken($stack);
         $items = $this->parseSubQuery($stack, true, true);
         if (is_array($items) && array_key_exists('tokens', $items)) {
           $tokens = $items['tokens'];
           if (is_array($tokens)) {
             $table_name = $this->joinWords($tokens);
             if ($table_as == null) {
               if (array_key_exists('alias', $items)
                && $items['alias'] != null) {
                 $table_as = $items['alias'];
               } else if (array_key_exists('offset', $items)
                       && array_key_exists('length', $items)) {
                 array_splice($stack, $items['offset'], $items['length']);
                 $stub = array_splice($stack, 0);
                 if (is_array($stub) && 1 === count($stub)) {
                   $table_as = array_shift($stub);
                 }
               }
             }
           }
         }
       } else {
         $table_name = array_shift($stack);
         if (1 === count($stack) && $table_as == null) {
           $table_as = array_shift($stack);
         }
       }
     }
     $stack = array();
   }
 }

/**
 * @access private
 */
 function assignJoinTables($parsed){
   $result = false;
   if (empty($parsed) || !is_array($parsed) || !isset($parsed[0])) {
     $this->pushError('Cannot parse tokens on JOIN');
   } else {
     $reset = reset($parsed);
     $check = true;
     $tname = 'table_name';
     $alias = 'table_as';
     $jtype = 'join_type';
     $jexpr = 'join_expr';
     foreach (array($tname, $alias, $jtype, $jexpr) as $key) {
       if (!array_key_exists($key, $reset)) {
         $this->pushError('Failed parsing tokens on JOIN');
         $check = false;
         break;
       }
     }
     if ($check) {
       $tnames = array();
       foreach ($parsed as $i => $items) {
         if (isset($items[$tname]) && $items[$tname] != null) {
           $tnames[$items[$tname]] = $this->getValue($items, $alias, 1);
         }
       }
       $aliases = array();
       foreach ($tnames as $key => $val) {
         if (is_int($val)) {
           $tnames[$key] = $key;
         }
         $aliases[$tnames[$key]] = $key;
       }
       $convert_tnames = array();
       $do_convert = false;
       foreach ($parsed as $pkey => $items) {
         $convert_tnames[$pkey] = false;
         if ($this->isSubSelectFrom($items[$tname])) {
           $convert_tnames[$pkey] = true;
           $do_convert = true;
         }
       }
       $this->_subSelectJoinUniqueNames = array();
       if ($do_convert) {
         $unique_seed = 0;
         $new_parsed = array();
         foreach ($parsed as $pkey => $items) {
           if (!empty($convert_tnames[$pkey])) {
             do {
               $unique_tname = sprintf('_posql_subquery_%d', $unique_seed++);
             } while (array_key_exists($unique_tname, $tnames)
                   || array_key_exists($unique_tname, $aliases));
             $this->_subSelectJoinUniqueNames[$unique_tname] = $items[$tname];
             $items[$tname] = $unique_tname;
           } else {
             $this->_subSelectJoinUniqueNames[$items[$tname]] = '<=>';
           }
           $new_parsed[$pkey] = $items;
         }
         $parsed = $new_parsed;
         unset($new_parsed);
       }
       if (!empty($this->_subSelectJoinUniqueNames)) {
         $flip_uniqs = $this->flipArray($this->_subSelectJoinUniqueNames);
         $new_tnames = array();
         foreach ($tnames as $key => $val) {
           if (array_key_exists($key, $flip_uniqs)) {
             $key = $flip_uniqs[$key];
           }
           $new_tnames[$key] = $val;
         }
         $tnames = $new_tnames;
         unset($new_tnames);
         $new_aliases = array();
         foreach ($aliases as $key => $val) {
           if (array_key_exists($val, $flip_uniqs)) {
             $val = $flip_uniqs[$val];
           }
           $new_aliases[$key] = $val;
         }
         $aliases = $new_aliases;
         unset($new_aliases);
       }
       $tables = array_map(array($this, 'decodeKey'),
                   array_filter(array_keys($this->meta)));
       foreach ($parsed as $pkey => $items) {
         if (isset($items[$jexpr], $items[$tname])
          && is_array($items[$jexpr])) {
           $expr_vars = array();
           $level = 0;
           $tokens = array_values($items[$jexpr]);
           foreach ($tokens as $i => $token) {
             $h = $i - 1;
             $j = $i + 1;
             switch ($token) {
               case '$':
               case ';':
               case '`':
                   break;
               case '(':
                   $level++;
                   break;
               case ')':
                   $level--;
                   break;
               case '.':
                   if (isset($tokens[$h], $tokens[$j])) {
                     if (isset($aliases[ $tokens[$h] ])) {
                       $tokens[$h] = $aliases[ $tokens[$h] ];
                     }
                     if (isset($tnames[ $tokens[$h] ])
                      || isset($tables[ $tokens[$h] ])) {
                       $tokens[$i] = $tokens[$h] . "['" . $tokens[$j] . "']";
                       $expr_vars[] = array(
                         'name' => $tokens[$h],
                         'key'  => $tokens[$j]
                       );
                       $tokens[$h] = $tokens[$j] = '';
                       if (substr(trim($tokens[$i]), 0, 1) !== '$') {
                         $tokens[$i] = '$' . $tokens[$i];
                       }
                     }
                   }
                   break;
               case '=':
                   if (isset($tokens[$h], $tokens[$j])
                    && !empty($this->autoAssignEquals)) {
                     $tokens[$i] = '==';
                   }
                   break;
               default:
                   if (isset($aliases[$token])) {
                     $token = $tokens[$i] = $aliases[$token];
                   }
                   if (isset($tokens[$h])) {
                     if (isset($aliases[ $tokens[$h] ])) {
                       $tokens[$h] = $aliases[ $tokens[$h] ];
                     }
                     if (isset($tnames[ $tokens[$h] ])
                      || isset($tables[ $tokens[$h] ])) {
                       $tokens[$i] = $tokens[$h] . "['" . $tokens[$j] . "']";
                       $expr_vars[] = array(
                         'name' => $tokens[$h],
                         'key'  => $tokens[$j]
                       );
                       $tokens[$h] = $tokens[$j] = '';
                       if (substr(trim($tokens[$i]), 0, 1) !== '$') {
                         $tokens[$i] = '$' . $tokens[$i];
                       }
                     }
                   }
                   break;
             }
           }
           if (!empty($expr_vars)) {
             $exprs = array();
             foreach ($expr_vars as $expr_var) {
               $varname = '$' . $this->trimExtra($expr_var['name']);
               $varkey  = "'" . $this->trimExtra($expr_var['key']) . "'";
               $exprs[] = sprintf('isset(%s[%s])', $varname, $varkey);
             }
             $exprs = array_unique($exprs);
             $exprs[] = '';
             $expr = implode('&&', $exprs);
             array_unshift($tokens, $expr);
           }
           $tokens = array_values(array_filter($tokens, 'strlen'));
           $expr = trim($this->joinWords($tokens), '&|;');
           if (strlen($expr) === 0) {
             $expr = true;
           } else {
             $expr = $this->optimizeExpr($expr);
           }
           switch ($this->getEngine()) {
             case 'SQL':
                 $this->_onJoinParseExpr = true;
                 if (!is_bool($expr)) {
                   if ($expr != null && is_array($expr)) {
                     $expr = $this->joinWords($expr);
                   }
                   $expr = $this->validExpr($expr);
                 }
                 $this->_onJoinParseExpr = null;
                 break;
             case 'PHP':
             default:
                 break;
           }
           $parsed[$pkey][$jexpr] = $expr;
         } else {
           $parsed[$pkey][$jexpr] = true;
         }
       }
       $result = $parsed;
     }
   }
   return $result;
 }

/**
 * Backtick operator will be ignored for compatibly with MySQL
 *
 * @param  array   the parsed tokens
 * @return array   the tokens which has no backticks
 * @access private
 */
 function removeBacktickToken($tokens){
   $result = array();
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the tokens(%s)', $tokens);
   } else {
     $tokens = $this->cleanArray($tokens);
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       if ($tokens[$i] === '`') {
         unset($tokens[$i]);
       }
     }
     $result = array_values($tokens);
     unset($tokens);
   }
   return $result;
 }

/**
 * @access private
 */
 function removeCorrelations(&$rows){
   if (!empty($rows) && is_array($rows)) {
     $row_count = count($rows);
     for ($i = 0; $i < $row_count; $i++) {
       foreach ($rows[$i] as $key => $val) {
         if (strpos($key, '.') !== false) {
           unset($rows[$i][$key]);
         }
       }
     }
   }
 }

/**
 * @access private
 */
 function removeCorrelationName(&$rows){
   if (!empty($rows) && is_array($rows)) {
     $row_count = count($rows);
     for ($i = 0; $i < $row_count; $i++) {
       $correlations = array();
       foreach (array_keys($rows[$i]) as $key) {
         if (strpos($key, '.') !== false) {
           list($table, $column) = explode('.', $key);
           $correlations[$key] = $column;
         }
       }
       if (!empty($correlations)) {
         $new_row = array();
         foreach ($rows[$i] as $key => $val) {
           if (array_key_exists($key, $correlations)) {
             $new_row[$correlations[$key]] = $val;
           } else {
             $new_row[$key] = $val;
           }
         }
         $rows[$i] = $new_row;
       }
     }
   }
 }

/**
 * To maintain the form of "correlation.columnname"
 *  from the token as the array, it unites by dot (.)
 *
 * @param  array   the parsed tokens
 * @return array   the tokens which was concatenated by dot
 * @access private
 */
 function concatCorrelationToken($tokens){
   $result = array();
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the correlative tokens(%s)', $tokens);
   } else {
     $tokens = $this->cleanArray($tokens);
     $length = count($tokens);
     for ($i = 0; $i < $length; $i++) {
       $h = $i - 1;
       $j = $i + 1;
       if (isset($tokens[$h])) {
         $prev = & $tokens[$h];
       } else {
         unset($prev);
         $prev = null;
       }
       if (isset($tokens[$j])) {
         $next = & $tokens[$j];
       } else {
         unset($next);
         $next = null;
       }
       $token = & $tokens[$i];
       switch ($token) {
         case '.':
             if ($prev != null && $next != null) {
               if ($this->isWord($prev)
                && ($this->isWord($next) || $next === '*')) {
                 $token = $prev . $token . $next;
                 $prev = null;
                 $next = null;
               } else if (!is_numeric($prev) && !is_numeric($next)) {
                 $this->pushError('illegal syntax,'
                               .  ' expect (correlation.column)');
                 $tokens = array();
                 break 2;
               }
             }
             break;
         default:
             break;
       }
     }
     if (empty($tokens)) {
       $result = array();
     } else {
       $result = $this->cleanArray($tokens);
     }
   }
   unset($token, $prev, $next);
   return $result;
 }

/**
 * To maintain the form of "correlation.columnname"
 *  from the token as the array, it divide by dot (.)
 *
 * @param  array   the parsed tokens
 * @return array   the tokens which was split by dot
 * @access private
 */
 function splitCorrelationToken($tokens){
   $result = array();
   if (!is_array($tokens)) {
     $this->pushError('Cannot parse the correlative tokens(%s)', $tokens);
   } else {
     $tokens = $this->cleanArray($tokens);
     $length = count($tokens);
     $dot = '.';
     for ($i = 0; $i < $length; $i++) {
       $token = & $tokens[$i];
       if (strpos($token, $dot) !== false) {
         $parts = explode($dot, $token);
         if (count($parts) === 2) {
           list($correlation, $column) = $parts;
           if ($correlation != null && $column != null
            && $this->isWord($correlation)
            && ($this->isWord($column) || $column === '*')) {
             array_splice($tokens, $i, 1,
               array(
                 $correlation,
                 $dot,
                 $column
               )
             );
             $length = count($tokens);
           }
         }
       }
       unset($token);
     }
     if (empty($tokens)) {
       $result = array();
     } else {
       $result = $this->cleanArray($tokens);
     }
   }
   return $result;
 }

/**
 * Gets the operand from right directive tokens
 *
 * @param  array    the parsed tokens as an array
 * @param  number   the start number of token's index
 * @param  string   the token of current operator
 * @return string   the operand, or FALSE on error
 * @access private
 */
 function getRightOperandBySQL(&$tokens, $start_idx, $token){
   //TODO: unidentified
   $result = false;
   $i = $start_idx - 1;
   $j = $i + 1;
   $k = $j + 1;
   $l = $k + 1;
   $open  = '(';
   $close = ')';
   if (is_array($tokens)
    && isset($tokens[$i], $tokens[$j])) {
     if ($tokens[$j] === $open) {
       $nest  = 0;
       $idx   = $i;
       $stack = array();
       while ($nest >= 0 && isset($tokens[++$idx])) {
         $op = $tokens[$idx];
         $tokens[$idx] = '';
         if ($op === $open) {
           $nest++;
         } else if ($op === $close) {
           $nest--;
         }
         $stack[] = $op;
         if ($nest === 0) {
           if (isset($tokens[++$idx]) && $tokens[$idx] != null) {
             $stack[] = $tokens[$idx];
             $tokens[$idx] = '';
           }
           break;
         }
       }
       if (empty($stack) || !is_array($stack)) {
         $result = false;
       } else {
         $result = $stack;
       }
     } else {
       if (isset($tokens[$k], $tokens[$l])
        && ($tokens[$k] === '||' || $tokens[$k] === '.')
        && !is_numeric($tokens[$l])) {
         $result = array();
         $idx = $i;
         while (isset($tokens[++$idx])) {
           $op = $tokens[$idx];
           if ($op === '' || $op === null) {
             continue;
           }
           if (($op === '||' || $op === '.')
            || $this->isStringToken($op)) {
             $result[] = $op;
             $tokens[$idx] = '';
           } else {
             break;
           }
         }
       } else {
         $result = array($tokens[$j]);
         $tokens[$j] = '';
       }
     }
   }
   if ($result === false) {
     $token = strtoupper($token);
     $this->pushError('illegal syntax, expect(%s) operand', $token);
   }
   return $result;
 }

/**
 * Gets the operand from left directive tokens
 *
 * @param  array    the parsed tokens as an array
 * @param  number   the start number of token's index
 * @param  string   the token of current operator
 * @return string   the operand, or FALSE on error
 * @access private
 */
 function getLeftOperandBySQL(&$tokens, $start_idx, $token){
   $result = false;
   $i = $start_idx + 1;
   $h = $i - 1;
   $g = $h - 1;
   $f = $g - 1;
   $open  = '(';
   $close = ')';
   if (is_array($tokens)
    && isset($tokens[$i], $tokens[$h])) {
     if ($tokens[$h] === $close) {
       $nest  = 0;
       $idx   = $i;
       $stack = array();
       while ($nest >= 0 && isset($tokens[--$idx])) {
         $op = $tokens[$idx];
         $tokens[$idx] = '';
         if ($op === $open) {
           $nest--;
         } else if ($op === $close) {
           $nest++;
         }
         array_unshift($stack, $op);
         if ($nest === 0) {
           if (isset($tokens[--$idx]) && $tokens[$idx] != null) {
             array_unshift($stack, $tokens[$idx]);
             $tokens[$idx] = '';
           }
           break;
         }
       }
       if (empty($stack) || !is_array($stack)) {
         $result = false;
       } else {
         $result = $stack;
       }
     } else {
       if (isset($tokens[$g], $tokens[$f])
        && $tokens[$g] === '.' && !is_numeric($tokens[$f])) {
         $result = array();
         $idx = $i;
         while (isset($tokens[--$idx])) {
           $op = $tokens[$idx];
           if ($op === '' || $op === null) {
             continue;
           }
           if ($op === '.' || $this->isStringToken($op)) {
             array_unshift($result, $op);
             $tokens[$idx] = '';
           } else {
             break;
           }
         }
       } else {
         $result = array($tokens[$h]);
         $tokens[$h] = '';
       }
     }
   }
   if ($result === false) {
     $token = strtoupper($token);
     $this->pushError('illegal syntax, expect(%s) operand', $token);
   }
   return $result;
 }

/**
 * Replaces from the standard SQL syntax to the Expression
 *   which be able to handled on Posql
 *
 * @param  mixed   the expression, or the SQL syntax
 * @return array   the parsed tokens which were replaced available of Posql
 *                 (or FALSE on error)
 * @access private
 */
 function replaceExprBySQL($expr){
   $result = false;
   $is_error = false;
   $this->_caseCount = null;
   if ($this->isParsedExpr($expr)) {
     $result = $expr;
   } else {
     if (is_string($expr)) {
       $expr = $this->splitSyntax($expr);
     }
     if (!is_array($expr)) {
       $this->pushError('Cannot parse the tokens(%s) as SQL mode', $expr);
     } else {
       $tokens = $this->cleanArray($expr);
       unset($expr);
       $tokens = $this->removeBacktickToken($tokens);
       $tokens = $this->concatCorrelationToken($tokens);
       $result = array();
       for ($i = 0; $i < count($tokens); $i++) {
         $is_error = $this->replaceTokenBySQL($tokens, $i);
         if ($is_error) {
           $result = false;
           break;
         }
       }
       if (!$is_error) {
         $result = $this->splitCorrelationToken($tokens);
         unset($tokens);
         $this->addParsedMark($result);
       }
     }
   }
   $this->_caseCount = null;
   return $result;
 }

/**
 * Replaces the tokens from the standard SQL syntax
 *  which be able to available on Posql
 *
 * @param  array  the tokens which is parsing
 * @param  number the number of current index in parsing
 * @return array  the parsed tokens which were replaced available of Posql
 * @access private
 */
 function replaceTokenBySQL(&$tokens, &$index){
   $is_error = false;
   $i = & $index;
   if (!is_array($tokens) || !array_key_exists($i, $tokens)) {
     $error_token = $tokens;
     if (is_array($error_token)) {
       $error_token = implode(', ', $error_token);
     }
     $this->pushError('Cannot parse the tokens(%s) as SQL mode',
                      $error_token);
     $is_error = true;
     unset($error_token);
   } else {
     $g = $i - 2;
     $h = $i - 1;
     $j = $i + 1;
     $k = $j + 1;
     $l = $k + 1;
     $prev = '';
     $next = '';
     $level = 0;
     $open  = '(';
     $close = ')';
     $token = & $tokens[$i];
     if (array_key_exists($h, $tokens)) {
       $prev = & $tokens[$h];
     }
     if (array_key_exists($j, $tokens)) {
       $next = & $tokens[$j];
     }
     switch (strtolower($token)) {
       case 'not':
       case '!':
           if ($prev === '==' || strtolower($prev) === 'is') {
             $token = '!=';
             $prev = '';
           } else if ($prev === '===') {
             $token = '!==';
             $prev = '';
           } else {
             $token = '!';
           }
           break;
       case 'is':
       case '=':
           if ($next === '!' || strtolower($next) === 'not') {
             $next  = '';
             $token = '!=';
           } else {
             $token = '==';
           }
           break;
       case 'isnull':
           // expression ISNULL
           if ($next !== $open && $prev != null) {
             $isnull = array('===', 'null');
             array_splice($tokens, $i, 1, $isnull);
             unset($isnull);
           }
           break;
       case 'notnull':
           // expression NOTNULL
           if ($next !== $open && $prev != null) {
             $notnull = array('!==', 'null');
             array_splice($tokens, $i, 1, $notnull);
             unset($notnull);
           }
           break;
       case 'coalesce':
       case 'ifnull':
       case 'nvl':
            // ---------------------------
            // COALESCE ( value [, ...] )
            // ---------------------------
            // IFNULL ( expr1, expr2 )
            // ---------------------------
            // NVL ( expr1 , expr2 )
            // ---------------------------
            if ($next === $open) {
              //TODO: parse right operand
              break;

              $idx = $i + 1;
              $nest = 0;
              $exprs = array();
              $stack = array();
              $prev_opd = null;
              $open_count = 0;
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch ($opd) {
                  case '(':
                      $nest++;
                      $stack[] = $opd;
                      break;
                  case ')':
                      $nest--;
                      $stack[] = $opd;
                      break;
                  case ',':
                      $prev_opd = $stack;
                      $stack = array();
                      if ($open_count === 0) {
                        if (reset($prev_opd) === $open) {
                          array_shift($prev_opd);
                        }
                        $exprs[] = $open;
                        $open_count++;
                      }
                      $exprs[] = array(
                        $prev_opd, '!==', 'null', '?',
                        $prev_opd, ':',
                        $open
                      );
                      $open_count++;
                      break;
                  default:
                      $stack[] = $opd;
                      break;
                }
                if ($nest === 0) {
                  if (end($stack) === $close) {
                    array_pop($stack);
                  }
                  $prev_opd = $stack;
                  $stack = array();
                  $exprs[] = array(
                    $prev_opd, '!==', 'null', '?',
                    $prev_opd, ':',   'null'
                  );
                  break;
                }
                ++$idx;
              }
              while (--$open_count >= 0) {
                $exprs[] = $close;
              }
              $exprs = $this->toOneArray($exprs);
              $check = array_count_values($exprs);
              if (isset($check[$open], $check[$close])
               && $check[$open] === $check[$close]) {
                array_splice($tokens, $i, $idx - $i + 1, $exprs);
              } else {
                $this->pushError('illegal syntax, expect (%s) at end',
                                 strtoupper($token));
                $is_error = true;
              }
              unset($exprs, $check, $stack, $prev_opd, $opd);
            } else {
              $this->pushError('illegal syntax, expect (%s) at end',
                               strtoupper($token));
              $is_error = true;
            }
            break;
       case 'nullif':
            // -------------------------------------------------
            // NULLIF(expr1, expr2)
            // -------------------------------------------------
            //   eq
            // -------------------------------------------------
            // CASE WHEN expr1 = expr2 THEN NULL ELSE expr1 END
            // -------------------------------------------------
            if ($next === $open) {
              //TODO: parse right operand
              break;

              $idx = $i + 1;
              $nest = 0;
              $nf_index = 0;
              $nf_exprs = array(0 => array(),
                                1 => array());
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch ($opd) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case ',':
                      $nf_index = 1;
                  default:
                      break;
                }
                if ($opd !== ',') {
                  $nf_exprs[$nf_index][] = $opd;
                }
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              foreach (array(0, 1) as $nf_index) {
                if (count($nf_exprs[$nf_index]) === 0) {
                  $nf_exprs[$nf_index] = array('null');
                }
              }
              $nf_expr = array(
                '(',
                $nf_exprs[0], '=', $nf_exprs[1],
                              '?', 'null',
                              ':', $nf_exprs[0],
                ')'
              );
              $nf_expr = $this->toOneArray($nf_expr);
              array_splice($tokens, $i, $idx - $i, $nf_expr);
              $stack = array();
              unset($nf_expr, $nf_exprs, $nf_index);
            } else {
              $this->pushError('illegal syntax, expect NULLIF(%s)', $next);
              $is_error = true;
            }
            break;
       case 'cast':
       case 'convert':
       case 'translate':
            // --------------------------------------------------
            // CAST (expr AS type)
            // --------------------------------------------------
            // CONVERT (expr, type)
            // --------------------------------------------------
            // CONVERT (string, dest_charset [, source_charset] )
            // --------------------------------------------------
            // SQL99 Syntax
            // --------------------------------------------
            // CONVERT (char_value target_char_set
            //          USING form_of_use source_char_name)
            // --------------------------------------------------
            // TRANSLATE (char_value target_char_set
            //            USING translation_name)
            // --------------------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              //$args_count = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case ',':
                      /*
                      // It will not convert it yet now though
                      // it might be appropriate to handle as CAST
                      //  when the argument is delimited by the comma
                      //  because it is not so general.
                      if (strtolower($token) === 'convert') {
                        $token = 'CAST';
                        array_shift($stack);
                        array_unshift($stack, $token);
                      }
                      */
                  case 'as':
                  case 'using':
                      $next_idx = $idx + 1;
                      if (isset($tokens[$next_idx])) {
                        $next_opd = & $tokens[$next_idx];
                        if ($next_opd != null && $this->isWord($next_opd)) {
                          $next_opd = $this->toStringToken($next_opd);
                        }
                        unset($next_opd);
                      }
                      unset($next_idx);
                      $opd = ',';
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect %s(%s)',
                               strtoupper($token), $next);
              $is_error = true;
            }
            break;
       case 'substring':
            // --------------------------------------
            // SQL99 Syntax
            // --------------------------------------
            // SUBSTRING (  extraction_string
            //      FROM    starting_position
            //    [ FOR     length ]
            //    [ COLLATE collation_name ]
            // )
            // --------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case 'from':
                  case 'for':
                  case 'collate':
                      $opd = ',';
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect SUBSTRING(%s)',
                               $next);
              $is_error = true;
            }
            break;
       case 'trim':
            // SQL99 Syntax
            // ------------------------------------------------------------
            // TRIM (
            //    [
            //      [ {LEADING | TRAILING | BOTH} ]
            //      [ removal_string ]
            //      FROM
            //    ]
            //    target_string
            //    [ COLLATE collation_name ]
            // )
            // ------------------------------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case 'leading':
                  case 'trailing':
                  case 'both':
                      $stack[] = $this->toStringToken($opd);
                      $opd = ',';
                      break;
                  case 'from':
                  case 'collate':
                      $opd = ',';
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect %s(%s)',
                               strtoupper($token), $next);
              $is_error = true;
            }
            break;
       case 'position':
            // SQL99 Syntax
            // -----------------------------------------------
            // POSITION ( starting_string IN search_string )
            // -----------------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case 'in':
                      $opd = ',';
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect POSITION(%s)',
                               $next);
              $is_error = true;
            }
            break;
       case 'extract':
            // SQL99 Syntax
            // --------------------------------------------------------
            // EXTRACT(datepart FROM expression)
            // --------------------------------------------------------
            // the datepart to be extracted
            // (
            //  YEAR, MONTH, DAY, HOUR, MINUTE, SECOND,
            //  TIMEZONE_HOUR, or TIMEZONE_MINUTE
            // )
            // from an expression
            // --------------------------------------------------------
            if ($next === $open) {
              $idx = $i + 1;
              $nest = 0;
              $stack = array($token);
              while (isset($tokens[$idx])) {
                $opd = $tokens[$idx];
                switch (strtolower($opd)) {
                  case '(':
                      $nest++;
                      break;
                  case ')':
                      $nest--;
                      break;
                  case 'from':
                      $opd = ',';
                      $prev_opd = array_pop($stack);
                      if (!$this->isStringToken($prev_opd)) {
                        $stack[] = $this->toStringToken($prev_opd);
                      } else {
                        $stack[] = $prev_opd;
                      }
                      break;
                  default:
                      break;
                }
                $stack[] = $opd;
                if ($nest === 0) {
                  break;
                }
                ++$idx;
              }
              array_splice($tokens, $i, $idx - $i + 1, $stack);
              $stack = array();
            } else {
              $this->pushError('illegal syntax, expect EXTRACT(%s)',
                               $next);
              $is_error = true;
            }
            break;
       case 'case':
           if ($next == null
            || !isset($tokens[$k]) || !isset($tokens[$l])) {
             $this->pushError('illegal syntax, expect (CASE, WHEN, ...)');
             $is_error = true;
           } else {
             if (strtolower($next) !== 'when') {
               $idx = $i + 1;
               $case_opd = array();
               while (isset($tokens[$idx])) {
                 if (strtolower($tokens[$idx]) === 'when') {
                   break;
                 }
                 $case_opd[] = $tokens[$idx];
                 $idx++;
               }
               if (empty($case_opd)) {
                 $this->pushError(
                     'illegal syntax, expect the empty expression'
                   . ' on CASE expression'
                 );
                 $is_error = true;
               } else {
                 // ----------------------------------------------
                 // CASE expr
                 //      WHEN comparison_expr THEN result_expr
                 //     [WHEN ...]
                 //      ELSE default_expr
                 // END
                 // ----------------------------------------------
                 // ( expr = comparison_expr
                 //        ? result_expr
                 //        : (...) default_expr )
                 // ----------------------------------------------
                 $stack = array($open, $case_opd, '=');
                 $stack = $this->toOneArray($stack);
                 $case_opd_len = count($case_opd) + 1;
                 array_splice($tokens, $i, $case_opd_len, $stack);
                 $idx += $case_opd_len;
                 $case_else = false;
                 $case_nest = 1;
                 while (isset($tokens[$idx])) {
                   $case_expr = $tokens[$idx];
                   switch (strtolower($case_expr)) {
                     case 'then':
                         $stack[] = '?';
                         break;
                     case 'when':
                         $stack[] = ':';
                         $stack[] = $open;
                         $stack[] = $case_opd;
                         $stack[] = '=';
                         $case_nest++;
                         break;
                     case 'else':
                         $stack[] = ':';
                         $case_else = true;
                         break;
                     case 'end':
                         if (!$case_else) {
                           $stack[] = ':';
                           $stack[] = 'null';
                         }
                         while (--$case_nest >= 0) {
                           $stack[] = $close;
                         }
                         $idx++;
                         break 2;
                     default:
                         $stack[] = $case_expr;
                         break;
                   }
                   $idx++;
                 }
               }
             } else {
               // -------------------------------------
               // CASE WHEN condition THEN value
               //     [WHEN ...]
               //     [ELSE result]
               // END
               // -------------------------------------
               // ( condition ? value : (...) result )
               // -------------------------------------
               $stack = array($open);
               array_splice($tokens, $i, 2, $stack);
               $idx = $i + 1;
               $case_else = false;
               $case_nest = 1;
               while (isset($tokens[$idx])) {
                 $case_expr = $tokens[$idx];
                 switch (strtolower($case_expr)) {
                   case 'then':
                       $stack[] = '?';
                       break;
                   case 'when':
                       $stack[] = ':';
                       $stack[] = $open;
                       $case_nest++;
                       break;
                   case 'else':
                       $stack[] = ':';
                       $case_else = true;
                       break;
                   case 'end':
                       if (!$case_else) {
                         $stack[] = ':';
                         $stack[] = 'null';
                       }
                       while (--$case_nest >= 0) {
                         $stack[] = $close;
                       }
                       $idx++;
                       break 2;
                   default:
                       $stack[] = $case_expr;
                       break;
                 }
                 $idx++;
               }
             }
             $stack = $this->toOneArray($stack);
             $case_check = array_count_values($stack);
             if (isset($case_check[$open], $case_check[$close])
              && $case_check[$open] === $case_check[$close]) {
               $this->_caseCount  = $this->getValue($case_check, '?', 0);
               $this->_caseCount += $this->getValue($case_check, ':', 0);
               array_splice($tokens, $i, $idx - $i, $stack);
             } else {
               $this->pushError('illegal syntax, expect (CASE, WHEN, END)');
               $is_error = true;
             }
           }
           break;
       case 'in':
           // ----------------------------------------------------
           // expression IN (value [, ...])
           // ----------------------------------------------------
           //        eq
           // ----------------------------------------------------
           // expression = value1 OR expression = value2 OR ...
           // ----------------------------------------------------
           if ($prev == null || $next == null || $next !== $open) {
             $this->pushError('illegal syntax, expect(IN)');
             $is_error = true;
             break;
           }
           // --------------------------------------------------------
           // expression NOT IN (value [, ...])
           // --------------------------------------------------------
           //        eq
           // --------------------------------------------------------
           // (expression <> value1 AND expression <> value2 AND ...)
           // --------------------------------------------------------
           if ($prev === '!' || strtolower($prev) === 'not') {
             $this->_notInOp = true;
             array_splice($tokens, $h, 1);
             $i = $h - 1;
             break;
           }
           if (empty($this->_notInOp)) {
             $in_opr = '=';
             $in_cnd = 'or';
           } else {
             $in_opr = '<>';
             $in_cnd = 'and';
           }
           $this->_notInOp = null;
           $in_expr = $this->getLeftOperandBySQL($tokens, $h, $token);
           if ($in_expr === false) {
             $is_error = true;
             break;
           }
           array_splice($tokens, $i, 2);
           $idx = $i;
           $nest = 0;
           $in_count = 0;
           $stack = array($open, $in_expr, $in_opr);
           while (isset($tokens[$idx])) {
             $in_op = $tokens[$idx];
             switch ($in_op) {
               case '(':
                   $nest++;
                   $stack[] = $in_op;
                   break;
               case ')':
                   $nest--;
                   $stack[] = $in_op;
                   if ($nest === -1) {
                     $idx++;
                     $in_count++;
                     break 2;
                   }
                   break;
               case ',':
                   if ($nest === 0) {
                     $stack[] = $in_cnd;
                     $stack[] = $in_expr;
                     $stack[] = $in_opr;
                     $in_count += 3;
                     break;
                   }
               default:
                   $stack[] = $in_op;
                   break;
             }
             $in_count++;
             $idx++;
           }
           end($stack);
           if (prev($stack) === $in_opr) {
             $this->pushError('illegal syntax, expect IN at end(%s)',
                              $in_opr);
             $is_error = true;
             break;
           }
           reset($stack);
           $stack = $this->toOneArray($stack);
           array_splice($tokens, $i, $idx - $i, $stack);
           unset($stack);
           break;
       case 'exists':
           // ----------------------------------------------------
           // EXISTS (sub-query)
           // ----------------------------------------------------
           // NOT EXISTS (sub-query)
           // ----------------------------------------------------
           if ($next !== $open) {
             $this->pushError('illegal syntax, expect(EXISTS)');
             $is_error = true;
             break;
           }
           break;
       case 'any':
       case 'some':
       case 'all':
           // ----------------------------------------------------
           // SQL-92 Syntax
           // ----------------------------------------------------
           // expression operator { ANY | SOME } (sub-query)
           // ----------------------------------------------------
           // expr operator ANY (value1, value2, ...)
           // ----------------------------------------------------
           //        eq
           // ----------------------------------------------------
           // expr operator value1 OR expr operator value2 OR ...
           // ----------------------------------------------------
           if ($prev == null || $next == null || $next !== $open) {
             $this->pushError('illegal syntax, expect(%s)', $token);
             $is_error = true;
             break;
           }
           // ----------------------------------------------------
           // expression operator ALL (sub-query)
           // ----------------------------------------------------
           // expr operator ALL (value1, value2, ...)
           // ----------------------------------------------------
           //        eq
           // ----------------------------------------------------
           // expr operator value1 AND expr operator value2 AND...
           // ----------------------------------------------------
           $sub_opr = $prev;
           if (0 === strcasecmp($token, 'all')) {
             $sub_cnd = 'and';
           } else {
             $sub_cnd = 'or';
           }
           $sub_expr = $this->getLeftOperandBySQL($tokens, $g, $token);
           if ($sub_expr === false) {
             $is_error = true;
             break;
           }
           array_splice($tokens, $h, 2);
           $idx = $i;
           $nest = 0;
           $sub_count = 0;
           $stack = array($open, $sub_expr, $sub_opr);
           while (isset($tokens[$idx])) {
             $sub_op = $tokens[$idx];
             switch ($sub_op) {
               case '(':
                   $nest++;
                   $stack[] = $sub_op;
                   break;
               case ')':
                   $nest--;
                   $stack[] = $sub_op;
                   if ($nest === -1) {
                     $idx++;
                     $sub_count++;
                     break 2;
                   }
                   break;
               case ',':
                   if ($nest === 0) {
                     $stack[] = $sub_cnd;
                     $stack[] = $sub_expr;
                     $stack[] = $sub_opr;
                     $sub_count += 3;
                     break;
                   }
               default:
                   $stack[] = $sub_op;
                   break;
             }
             $sub_count++;
             $idx++;
           }
           end($stack);
           if (prev($stack) === $sub_opr) {
             $this->pushError('illegal syntax, expect (%s) at end(%s)',
                              $token, $sub_opr);
             $is_error = true;
             break;
           }
           reset($stack);
           $stack = $this->toOneArray($stack);
           array_splice($tokens, $i - 1, $idx - $i + 1, $stack);
           unset($stack);
           break;
       case 'like':
           // ----------------------------------------------------
           // string LIKE pattern [ESCAPE escape-character]
           // ----------------------------------------------------
           // string NOT LIKE pattern [ESCAPE escape-character]
           // ----------------------------------------------------
           if ($prev == null || $next == null
            || !$this->isStringToken($next)) {
             $this->pushError('illegal syntax, expect LIKE(%s)', $next);
             $is_error = true;
             break;
           } else {
             $pattern = $next;
             $next = '';
             $org_pattern = $pattern;
             if (isset($tokens[$g]) && $tokens[$g] != null
              && $prev === '!' || strtolower($prev) === 'not') {
               $subject = $this->getLeftOperandBySQL($tokens, $g, $token);
               $prev = '!';
             } else {
               $subject = $this->getLeftOperandBySQL($tokens, $h, $token);
             }
             if ($subject === false) {
               $is_error = true;
               break;
             }
             if (isset($tokens[$k], $tokens[$l])
              && strtolower($tokens[$k]) === 'escape') {
               if (!$this->isStringToken($tokens[$l])) {
                 $this->pushError('illegal syntax, expect LIKE, ESCAPE(%s)',
                                  $tokens[$l]);
                 $is_error = true;
                 break;
               }
               $escape = $tokens[$l];
               $tokens[$k] = '';
               $tokens[$l] = '';
             } else {
               $escape = null;
             }
             $test = $this->toLikePattern($pattern, $escape) . 'A';
             if (@preg_match($test, '') === false) {
               $this->pushError('illegal pattern on LIKE(%s)',
                                $org_pattern);
               $is_error = true;
               break;
             }
             $pattern = $this->toStringToken($pattern);
             unset($test, $org_pattern);
             $like_func = strtoupper($token);
             if ($escape === null) {
               $like_tokens = array(
                 $like_func, '(',
                   $pattern, ',',
                   $subject,
                 ')'
               );
             } else {
               $like_tokens = array(
                 $like_func, '(',
                   $pattern, ',',
                   $subject, ',',
                   $escape,
                 ')'
               );
             }
             $like_tokens = $this->toOneArray($like_tokens);
             array_splice($tokens, $i, 1, $like_tokens);
             unset($like_tokens, $escape, $pattern, $subject, $like_func);
           }
           break;
       case 'between':
           // ========================
           // a BETWEEN x AND y
           // ------------------
           //       eq
           // ------------------
           // a >= x AND a <= y
           // ========================
           // a NOT BETWEEN x AND y
           // ------------------
           //       eq
           // ------------------
           // a < x OR a > y
           // ------------------------
           if ($prev != null && $next != null
            && isset($tokens[$k], $tokens[$l])
            && ($tokens[$k] === '&&'
            || strtolower($tokens[$k]) === 'and')) {
             if ($prev === '!' || strtolower($prev) === 'not') {
               if (!isset($tokens[$g])) {
                 $this->pushError('illegal syntax, expect(BETWEEN)');
                 $is_error = true;
                 break;
               }
               $opd = $this->getLeftOperandBySQL($tokens, $g, $token);
               if (is_array($opd)) {
                 $betweens = array(
                   '(',
                   $opd, '<', $next,
                   'or',
                   $opd, '>', $tokens[$l],
                   ')'
                 );
                 $betweens = $this->toOneArray($betweens);
                 $org = array_splice($tokens, $g, 6, $betweens);
               }
             } else {
               $opd = $this->getLeftOperandBySQL($tokens, $h, $token);
               if (is_array($opd)) {
                 $betweens = array(
                   '(',
                   $opd, '>=', $next,
                   'and',
                   $opd, '<=', $tokens[$l],
                   ')'
                 );
                 $betweens = $this->toOneArray($betweens);
                 $org = array_splice($tokens, $h, 5, $betweens);
               }
             }
             if (isset($opd) && $opd === false) {
               $this->pushError('illegal syntax,'
                             .  ' expect the operand on BETWEEN');
               $is_error = true;
             }
             //$i += count($betweens) - count($org) + 3;
             unset($betweens, $org, $opd);
           } else {
             $this->pushError('illegal syntax, expect(BETWEEN)');
             $is_error = true;
           }
           break;
       case '<<=':
       case '>>=':
       case '.=':
       case '^=':
       case '&=':
       case '|=':
       case '/=':
       case '%=':
       case '*=':
       case '+=':
       case '-=':
       case '++':
       case '--':
           $this->pushError('The assignment operator(%s)'
                         .  ' is not supported', $token);
           $is_error = true;
           break;
       case '!!=':
       case '!~~':
       case '!~*':
       case '!~':
       case '~~':
       case '~*':
       case '<<<':
       case '>>>':
       case '=>':
       case '->':
       case '::':
       case '@':
       case '\\':
       case '{':
       case '}':
           $this->pushError('Not supported SQL syntax,'
                         .  ' expect (%s)', $token);
           $is_error = true;
           break;
       case '[':
       case ']':
           if (empty($this->_onJoinParseExpr)) {
             $this->pushError('Not supported SQL syntax,'
                         .  ' expect (%s)', $token);
             $is_error = true;
           }
           break;
       case '?':
       case ':':
           if (isset($this->_caseCount) && is_int($this->_caseCount)) {
             if (--$this->_caseCount < 0) {
               $this->pushError('illegal syntax, expect (CASE, WHEN, END)');
               $is_error = true;
             }
           }
           break;
       case '`':
           $token = '';
           break;
       case '$':
           if ($next == null || substr($next, 0, 1) === '$'
            || !$this->isWord($next) || substr($prev, -1) === '$') {
             $token = '';
           } else if ($next === '{'
                   || $next === '}') {
             $this->pushError('Invalid SQL syntax, expect(%s%s)',
                              $token, $next);
             $is_error = true;
           }
           break;
       case '(':
           $level++;
           if ($next != null && $this->isWord($next)
            && isset($tokens[$k]) && $tokens[$k] === $close) {
             $cast_token = strtolower($next);
             switch ($cast_token) {
               case 'int':
               case 'integer':
               case 'bool':
               case 'boolean':
               case 'float':
               case 'double':
               case 'real':
               case 'string':
                   $next = $cast_token;
                   break;
               case 'binary':
               case 'unset':
               case 'array':
               case 'object':
               default:
                   if ($this->isWord($cast_token)
                    && strpos($cast_token, '.') === false) {
                     $meta = $this->getMetaAssoc();
                     if (is_array($meta)) {
                       if (!array_key_exists($cast_token, $meta)) {
                         $this->pushError('Not supported SQL syntax,'
                                       .  ' expect(%s)', $cast_token);
                         $is_error = true;
                       }
                     }
                   }
                   break;
             }
             unset($cast_token);
           }
           break;
       case ')':
           $level--;
           break;
       case '=':
           $token = '==';
           break;
       case '||':
           $token = '.';
           break;
       case 'and':
           $token = '&&';
           break;
       case 'or':
           $token = '||';
           break;
       case 'xor':
           break;
       case 'div':
           if ($next !== $open) {
             $token = '/';
           }
           break;
       case 'mod':
           if ($next !== $open) {
             $token = '%';
           }
           break;
       case '.':
           if ($prev != null && $next != null
            && ($this->isStringToken($prev) || $this->isWord($prev))
            && ($this->isStringToken($next) || $this->isWord($next))) {
             $token = $token;
           } else {
             $this->pushError('illegal syntax, expect (%s)', $token);
             $is_error = true;
           }
           break;
       case '<=>':
           $token = '===';
           break;
       case '<?':
       case '<%':
           $error_token = 'T_OPEN_TAG';
       case '?>':
       case '%>':
           if (empty($error_token)) {
             $error_token = 'T_CLOSE_TAG';
           }
           $this->pushError('Invalid SQL syntax, expect %s', $error_token);
           $is_error = true;
           break;
       case '!==':
       case '===':
       case '!=':
       case '==':
       case '&&':
       case '<=':
       case '>=':
       case '<>':
       case '>>':
       case '<<':
       case ',':
       case ';':
       case '<':
       case '>':
       case '^':
       case '~':
       case '&':
       case '|':
       case '/':
       case '%':
       case '*':
       case '+':
       case '-':
           break;
       default:
           if ($token != null) {
             if ($token === 0 || is_numeric($token)) {
               if ($token == 0) {
                 $token = '0';
               } else {
                 $token_chr = substr($token, 0, 1);
                 $token_num = substr($token, 1);
                 if (strlen($token_num) && is_numeric($token_num)
                  && ($token_chr === '+' || $token_chr === '-')) {
                   if ($prev != null && $this->isStringToken($prev)) {
                     $prev_org = substr($prev, 1, -1);
                     if (is_numeric($prev_org)) {
                       array_splice($tokens, $i, 0, $token_chr);
                       $token = $token_num;
                     }
                   } else if ($prev != null) {
                     $prev_last = substr($prev, -1);
                     if ($prev_last !== $token_chr) {
                       array_splice($tokens, $i, 0, $token_chr);
                       $token = $token_num;
                     }
                   }
                 }
                 $token = $this->math->format($token);
               }
               $token = $this->toStringToken($token);
               break;
             } else if ($this->isStringToken($token)) {
               $token = $this->toStringToken($token);
             }
             if ($this->isStringToken($prev)
              && $this->isStringToken($token)) {
               $token = $this->concatStringTokens($prev, $token);
               $prev = '';
             }
           }
           break;
     }
     unset($token, $prev, $next);
   }
   unset($i);
   return $is_error;
 }

/**
 * Parses the attributes of the given HTML element.
 *
 * @param  mixed    the attributes of target element
 * @return array    the parsed attributes as array
 * @access private
 */
 function parseHTMLAttributes($attrs = array()){
   $result = array();
   if (func_num_args() > 1) {
     $args = func_get_args();
     $args = array($args);
     $result = call_user_func(array(&$this, 'parseHTMLAttributes'), $args);
   } else {
     if (is_string($attrs)) {
       $attrs = array($attrs);
     }
     if (is_array($attrs)) {
       foreach ($attrs as $key => $val) {
         if (is_array($val)) {
           if (strtolower($key) === 'style') {
             $css = array();
             foreach ($val as $css_key => $css_val) {
               if (!is_array($css_val)) {
                 $css[] = sprintf('%s:%s', $css_key, $css_val);
               }
             }
             $val = implode(';', $css);
           } else {
             $val = $this->parseHTMLAttributes($val);
           }
         }
         if (is_scalar($val)) {
           if (is_string($key)) {
             $char = substr($val, 0, 1);
             if (($char === '"' || $char === '\'')
              && substr($val, -1) === $char) {
               $format = '%s=%s';
             } else {
               $format = '%s="%s"';
             }
             $val = sprintf($format, $key, $val);
           }
         }
         foreach ((array)$val as $v) {
           $result[] = $v;
         }
       }
       $result = array_filter($result);
       $result = array_unique($result);
       $result = array_values($result);
     }
   }
   return $result;
 }

/**
 * Parse CSV (Comma-Separated Values) from file
 *
 * @link http://www.ietf.org/rfc/rfc4180.txt
 *
 * @param  string   optionally, CSV filename
 * @return array    a parsed multiple dimension array
 * @access public
 */
 function parseCSV($filename = null){
   $result = array();
   if ($filename != null) {
     $org_path = $this->path;
     $this->setPath($filename);
   }
   $fp = $this->fopen('r');
   if ($fp) {
     while (!feof($fp)) {
       $fields = $this->fgetcsv($fp);
       if (is_array($fields)) {
         foreach ($fields as $i => $field) {
           $fields[$i] = $this->pcharset->convert($field, $this->charset);
         }
         $result[] = $fields;
       }
     }
     fclose($fp);
   }
   if (isset($org_path) && $org_path !== $this->path) {
     $this->setPath($org_path);
   }
   return $result;
 }

}
