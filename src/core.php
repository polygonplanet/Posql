<?php
require_once dirname(__FILE__) . '/expr.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_Core
 *
 * The base class to dissociate the methods from PUBLIC and PRIVATE
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet.aqua@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_Core extends Posql_Expr {
  /**
   * Check whether the database format is available
   *
   * @param  string   optionally, the file path to check
   * @return boolean  whether it was database or not
   * @access public
   */
  function isDatabase($path = null) {
    $result = false;
    if ($path != null) {
      $org_path = $this->path;
      $this->setPath($path);
    }
    $class = $this->getClass() . $this->getVersion(1);
    $length = strlen($class);
    if ($fp = $this->fopen('r')) {
      $heads = $this->fgets($fp);
      fclose($fp);
      if (0 === strncasecmp($heads, $class, $length)) {
        if (substr($heads, -1) === $this->NL
          && rtrim($heads) === rtrim($heads, $this->NL)) {
          $result = true;
        } else {
          $this->pushError('illegal the end of line character.'
            .  ' Must be LF (0x0A)');
        }
      } else {
        $this->pushError('illegal format or version');
        $result = false;
      }
    }

    if (isset($org_path) && $org_path !== $this->path) {
      $this->setPath($org_path);
    }
    return $result;
  }

  /**
   * It is checked whether the table exists
   *
   * @param  string   table name
   * @return boolean  exists or not
   * @access public
   */
  function existsTable($tablename) {
    $result = false;
    if (is_string($tablename)) {
      $tname = $this->encodeKey($tablename);
      $meta = $this->getMeta($tablename);
      $result = $meta !== false && is_array($meta);
    }
    return $result;
  }

  /**
   * Gets the meta data from database
   *
   * @param  string  table name
   * @return array   meta data of table or false on error
   * @access public
   */
  function getMeta($table = null) {
    $result = false;
    if (!$this->hasError()) {
      $tname = $this->encodeKey($table);
      while (($table != null && isset($this->meta[$tname])
          && $this->isLockEx($table)) || $this->isLockAll()) {
        $this->sleep();
      }
      $line = null;
      if ($this->lockShAll()) {
        if ($fp = $this->fopen('r')) {
          $this->fseekLine($fp, 2);
          $line = $this->fgets($fp, true);
          fclose($fp);
        }
        $this->unlockAll();
      }

      if ($line != null) {
        $meta = @($this->decode($line));
        if (!is_array($meta)) {
          $meta = array(array());
        }
        $this->meta = $meta;
      }

      if (!$this->hasError()) {
        if (!is_array($this->meta)) {
          $this->meta = array(array());
        }

        if (!array_key_exists(0, $this->meta)) {
          $this->meta[0] = array();
        }
        $posql_key = $this->getClassKey();
        if (!array_key_exists($posql_key, $this->meta)
          || !is_array($this->meta[$posql_key])) {
          $this->meta[$posql_key] = array();
        }
        $this->_primaryKey  = null;
        $this->_createQuery = null;
        if ($tname != null && isset($this->meta[$posql_key][$tname])
          && is_array($this->meta[$posql_key][$tname])) {
          $def = & $this->meta[$posql_key][$tname];
          $this->_primaryKey  = $this->getValue($def, 'primary');
          $this->_createQuery = $this->getValue($def, 'sql');
          unset($def);
        }

        if (empty($this->_getMetaAll)) {
          unset($this->meta[$posql_key]);
        }

        if ($table == null) {
          $result = $this->meta;
        } else {
          $result = $this->getValue($this->meta, $tname, false);
        }
      }

      if ($result === false) {
        if (!empty($this->_onCreate)) {
          $this->pushError('Cannot load the meta data(%s)', $table);
        }
      }
    }
    return $result;
  }

  /**
   * Gets the meta data from the object memory
   *
   * @param  string  optionally, table name
   * @return array   meta data as associate array, or FALSE on error
   * @access public
   */
  function getMetaData($table = null) {
    $result = null;
    if (!empty($this->meta) && is_array($this->meta)) {
      if ($table === null) {
        $result = array();
        foreach ($this->meta as $tname => $meta) {
          if ($tname == null) {
            continue;
          }
          $table_name = $this->decodeKey($tname);
          if ($this->isEnableName($table_name)) {
            $result[$table_name] = $meta;
          }
        }
      } else {
        if (is_string($table) && $this->isEnableName($table)) {
          $tname = $this->encodeKey($table);
          if (array_key_exists($tname, $this->meta)) {
            $result = $this->meta[$tname];
          }
        }
      }
    }
    return $result;
  }

  /**
   * Gets the meta data from database as associate array
   *
   * @param  string  optionally, table name
   * @return array   meta data as associate array, or empty array
   * @access public
   */
  function getMetaAssoc($table = null) {
    $result = array();
    if (!$this->hasError()) {
      if (isset($this->meta)) {
        $meta = $this->meta;
        if (empty($meta)
          || (isset($meta[0]) && $meta[0] == null)
          || ($meta === array(array()))) {
          $meta = $this->getMeta($table);
        }

        if (!empty($meta) && is_array($meta)) {
          if (!is_array(reset($meta))) {
            $meta = array($meta);
          }
          $meta_org = $meta;
          $count = count($meta_org);
          $meta = array();
          while (--$count >= 0) {
            $meta_div = array_shift($meta_org);
            if (!empty($meta_div) && is_array($meta_div)) {
              foreach ($meta_div as $key => $val) {
                $meta[$key] = 1;
              }
            }
          }
        }

        if (!is_array($meta)) {
          $meta = array();
        }
        $result = $meta;
      }
    }
    return $result;
  }

  /**
   * @access private
   */
  function getCreateDefinitionMeta($table = null, $field = null) {
    $result = false;
    if (!$this->hasError()) {
      $posql_key = $this->getClassKey();
      $this->_getMetaAll = true;
      $this->getMeta();
      if (isset($this->meta[$posql_key])) {
        $metas = & $this->meta[$posql_key];
        if (!empty($metas) && is_array($metas)) {
          $table = (string)$table;
          $field = (string)$field;
          if ($table == null) {
            if ($field == null) {
              $result = $metas;
            } else {
              $result = array();
              foreach ($metas as $tname => $meta) {
                if (is_string($tname)
                  && is_array($meta) && array_key_exists($field, $meta)) {
                  $result[$tname] = $meta[$field];
                }
              }
            }
          } else {
            $tname = $this->encodeKey($table);
            $result = $this->getValue($metas, $tname);
            if ($field != null) {
              $result = $this->getValue($result, $tname);
            }
          }
        }
        unset($metas);
        unset($this->meta[$posql_key]);
      }
      $this->_getMetaAll = null;
    }
    return $result;
  }

  /**
   * Initializes class variables
   * If file path exists, will registered vacuum() as  the shutdown function
   *
   * @param  string  file path of database
   * @return void
   * @access private
   */
  function init($path = null) {
    if (empty($this->_inited)) {
      if (function_exists('date_default_timezone_set')
        && function_exists('date_default_timezone_get')) {
        // PHP VERSION 5+ will still emit a warning in E_STRICT,
        //  it handy keep problems
        @date_default_timezone_set(@date_default_timezone_get());
      }
      // Alias for compatibility
      $this->autoIncrement = & $this->auto_increment;
      $this->supportsUTF8PCRE = (bool)@preg_match('/\pL/u', 'a');
      $this->isPHP5 = version_compare(PHP_VERSION, 5, '>=');
      $this->isWin  = 0 === strncasecmp(PHP_OS, 'win', 3);
      $this->id     = $this->getUniqId();
      $this->math     = new Posql_Math();
      $this->ctype    = new Posql_CType();
      $this->pcharset = new Posql_Charset();
      $this->unicode  = new Posql_Unicode();
      $this->ecma     = new Posql_ECMA();
      $this->archive  = new Posql_Archive();
      $this->method   = new Posql_Method();
      $this->pcharset->_referProperty($this);
      $this->unicode->_referProperty($this);
      $this->ecma->_referProperty($this);
      $this->archive->_referProperty($this);
      $this->method->_referObject($this);
      if (!$this->isWin) {
        // Note: autoLock should be enabled excluding Windows.
        //       Because, maybe very slows
        //       when locking database by using mkdir() on Windows.
        // @see $autoLock, setAutoLock, getAutoLock
        $this->autoLock = true;
      }
      $this->_inited = true;
    }
    $this->initCorrelationPrefix();
    $this->checkDisableFunctions();
    if ($path != null) {
      $this->setPath($path);
    }

    if (empty($this->_registered)
      && !empty($this->path) && @is_file($this->path)) {
      register_shutdown_function(array(&$this, 'terminate'));
      $this->_registered = true;
    }

    if (!is_array($this->meta) || !isset($this->meta[0])) {
      $this->meta = array(array());
    }

    if (!$this->isEnableId($this->id)) {
      $this->pushError('illegal unique id(%s)', $this->id);
    }
  }

  /**
   * The termination process for the exceptive aborted on script
   * Unlocks all that by the instance of this class
   * This method will called automatically by register_shutdown_function()
   *
   * @see    init
   * @param  string optionally, the database filename
   * @param  string optionally, the ID of the Posql's instance (i.e. Posql->id)
   * @return void
   * @access public
   */
  function terminate($path = null, $id = null) {
    if ($path != null && $id != null) {
      // below code is urgent calls which needs arguments
      if (is_string($id) && strlen($id) === 8
        && ((empty($this->path)
            ||  (empty($this->path) || strlen($this->id) !== 8)))) {
        $this->setPath($path);
        $this->id = $id;
      }
    }
    unset($path, $id);
    if (empty($this->_terminated)) {
      if (!empty($this->path) && @is_file($this->path)) {
        if (!empty($this->_inTransaction)
          && !empty($this->_transactionName)) {
          // Rollback maybe considered error.
          $this->rollBack();
        }

        if (empty($this->meta)) {
          $this->getMeta();
        }
        $posql_key = $this->getClassKey();
        if (is_array($this->meta)
          && array_key_exists($posql_key, $this->meta)) {
          unset($this->meta[$posql_key]);
        }

        if (!empty($this->autoVacuum)) {
          $this->vacuum();
          $this->autoVacuum = null;
        }
        $tables = array_keys($this->meta);
        if (!in_array(0, $tables)) {
          $tables[] = 0;
        }
        $didall = false;
        foreach ($tables as $table) {
          $this->userAbort = @ignore_user_abort(1);
          if (!$didall && $table == 0) {
            $this->unlockAll();
            $didall = true;
          } else if ($table != null) {
            $org = $this->decodeKey($table);
            if ($this->isEnableName($org)) {
              $this->unlock($org);
            }
          }
        }

        if (!$didall) {
          $this->userAbort = @ignore_user_abort(1);
          $this->unlockAll();
        }
        $this->meta[0] = array();
      }
      // Release all referenced properties, and call destructor
      unset($this->method->posql);
      $props = array(
        'method', 'archive', 'ecma', 'unicode', 'pcharset', 'ctype', 'math'
      );
      foreach ($props as $prop) {
        if ($prop != null && method_exists($this->{$prop}, '__destruct')) {
          $this->{$prop}->__destruct();
        }
      }
      foreach ($props as $prop) {
        if ($prop != null) {
          $this->{$prop} = null;
          unset($this->{$prop});
        }
      }
      unset($this->autoIncrement, $this->id, $props);
      $this->_terminated = true;
    }
  }

  /**
   * Delays the program execution for database locking
   *
   * @param  number  halt time in micro seconds. (default = 100000 = 0.1 sec.)
   * @return void
   * @access private
   */
  function sleep($msec = 100000) {
    if ($this->isWin && !$this->isPHP5) {
      sleep(1);
    } else {
      usleep($msec);
    }
  }

  /**
   * initialize the internal correlation prefix
   *
   * @param  void
   * @return void
   * @access private
   */
  function initCorrelationPrefix() {
    if (empty($this->_correlationPrefix)) {
      $this->_correlationPrefix = null;
    }
    $prefix = & $this->_correlationPrefix;
    if ($prefix == null || !is_string($prefix)) {
      $prefix = $this->getClassName(true);
    }
    unset($prefix);
  }

  /**
   * Returns the current file path of the database
   *
   * @param  void
   * @return string  file path of the database
   * @access public
   */
  function getPath() {
    return $this->path;
  }

  /**
   * Sets the file path of the database
   *
   * @param  string  file path of the database
   * @return void
   * @access public
   */
  function setPath($path) {
    $ext = $this->toExt();
    $dir = dirname($path);
    if ($dir != null) {
      $dir .= '/';
    }
    $path = $dir . basename($path, $ext) . $ext;
    $this->path = $this->fullPath($path);
  }

  /**
   * Returns the current database name
   *
   * @param  void
   * @return string  the database name
   * @access public
   */
  function getDatabaseName() {
    $result = false;
    $path = $this->getPath();
    if ($path != null) {
      $ext = $this->toExt();
      $result = basename($path, $ext);
    }
    return $result;
  }

  /**
   * Returns the tablename of the most recent result
   *
   * @param  void
   * @return mixed   the tablename of the most recent result
   * @access public
   */
  function getTableNames() {
    $result = null;
    if (!empty($this->_selectTableNames)) {
      $result = $this->_selectTableNames;
    } else if (!empty($this->tableName)) {
      $result = $this->decodeKey($this->tableName);
    }
    return $result;
  }

  /**
   * Returns the table alias of the most recent result
   *
   * @param  void
   * @return array  the table alias of the most recent result
   * @access public
   */
  function getTableAliasNames() {
    $result = array();
    if (isset($this->_selectTableAliases)) {
      $result = $this->_selectTableAliases;
    }
    return $result;
  }

  /**
   * Returns the column alias of the most recent result
   *
   * @param  void
   * @return array  the column alias of the most recent result
   * @access public
   */
  function getColumnAliasNames() {
    $result = array();
    if (isset($this->_selectColumnAliases)) {
      $result = $this->_selectColumnAliases;
    }
    return $result;
  }

  /**
   * Returns the tablename of the most recent result
   *
   * @param  void
   * @return string  the tablename of the most recent result
   * @access public
   */
  function getTableName() {
    $result = null;
    if (isset($this->tableName)
      && is_string($this->tableName) && $this->tableName != null) {
      $result = $this->decodeKey($this->tableName);
    }
    return $result;
  }

  /**
   * Return the "rowid" of the last row insert
   *  from this connection to the database.
   *
   * @param  void
   * @return number  the value to inserted "rowid" or NULL
   * @access public
   */
  function getLastInsertId() {
    $result = null;
    if (isset($this->next)) {
      $result = $this->math->sub($this->next, 1);
      if ($result < 0) {
        $result = '0';
      }
    }
    return $result;
  }

  /**
   * Get the table definition and information
   *
   * @param  string  table name, (or null = all tables)
   * @return array   the information as array, or FALSE on error
   * @access public
   */
  function getTableInfo($table = null) {
    $result = false;
    $infos = $this->_getTableInfo($table);
    if (!empty($infos) && is_array($infos)) {
      $encodekey = $this->encodeKey($table);
      if (!is_array(reset($infos))) {
        $infos = array(
          $encodekey => $infos
        );
      }
      $posql_key = $this->getClassKey();
      $sql_infos = $this->getCreateDefinitionMeta(null, 'sql');
      $defaults = array(
        'lockid'   => null,
        'lockmode' => null,
        'nextid'   => null,
        'lastmod'  => null,
        'sql'      => null
      );
      $i = 0;
      $result = array();
      foreach ($infos as $tname => $info) {
        if (!is_array($info)) {
          break;
        }
        if (isset($sql_infos[$tname])) {
          $info['sql'] = $sql_infos[$tname];
        }
        $info = $info + $defaults;
        if (strcasecmp($tname, $posql_key) === 0) {
          $name = $this->getDatabaseName();
          if ($name == null) {
            $name = $tname;
          }
        } else {
          $name = $this->decodeKey($tname);
        }
        $result[$i] = array(
          'name' => $name
        );
        foreach ($info as $key => $val) {
          switch (strtolower($key)) {
          case 'lockid':
            $key = 'lock_id';
            break;
          case 'lockmode':
            $key = 'lock_mode';
            break;
          case 'nextid':
            $key = 'next_id';
            break;
          case 'lastmod':
            $key = 'last_modified';
            $val = $this->method->now($val);
            break;
          case 'sql':
            $key = 'sql';
            break;
          default:
            $key = null;
            break;
          }
          if ($key != null) {
            $result[$i][$key] = $val;
          }
        }
        ++$i;
      }
      if ($result != null && is_array($result)) {
        $template = array(
          'name',    'next_id',   'last_modified',
          'lock_id', 'lock_mode', 'sql'
        );
        $this->sortByTemplate($result, $template);
      }
    }
    return $result;
  }

  /**
   * Returns the information of current tables
   *
   * @param  string  table name, (or null = all tables)
   * @return array   the information as array, or FALSE on error
   * @access private
   */
  function _getTableInfo($table = null) {
    $result = false;
    $error  = false;
    while ($this->isLockExAll()
      || ($table != null && $this->isLockEx($table))) {
      $this->sleep();
    }
    $heads = null;
    $infos = null;
    if ($this->lockShAll()) {
      if ($fp = $this->fopen('r')) {
        $heads = $this->fgets($fp, true);
        $infos = $this->fgets($fp, true);
        fclose($fp);
      }
      $this->unlockAll();
    }
    if ($heads !== null && $infos !== null) {
      $modes = array();
      $delm = '@';
      $lock = substr($heads, -10);
      if (strpos($lock, $delm) === false) {
        $error = $delm;
      } else {
        list($mode, $id) = explode($delm, $lock);
        $modes[$this->getClassKey()] = array(
          'lockid'   => $id,
          'lockmode' => $this->assignLockMode($mode)
        );
        if ($infos != null) {
          $delm = ';';
          if (strpos($infos, $delm) === false) {
            $error = $delm;
          } else {
            $split = explode($delm, $infos);
            foreach ($split as $tableinfo) {
              $tableinfo = trim($tableinfo);
              if ($tableinfo == null) {
                continue;
              }
              $delm = ':';
              if (strpos($tableinfo, $delm) === false) {
                $error = $delm;
                break;
              }
              list($tablename, $info) = explode($delm, $tableinfo);
              $delm = '#';
              if (strpos($info, $delm) === false) {
                $error = $delm;
                break;
              }
              list($lockinfo, $inctimes) = explode($delm, $info);
              $delm = '@';
              if (strpos($lockinfo, $delm) === false) {
                $error = $delm;
                break;
              }
              list($mode, $id) = explode($delm, $lockinfo);
              if (strpos($inctimes, $delm) === false) {
                $error = $delm;
                break;
              }
              list($inc, $mtime) = explode($delm, $inctimes);
              $modes[$tablename] = array(
                'lockid'   => $id,
                'lockmode' => $this->assignLockMode($mode),
                'nextid'   => $this->math->add($inc, 0),
                'lastmod'  => $mtime - 0
              );
            }
          }
        }
      }
      if ($error) {
        $this->pushError('Database might be broken: char(%s)', $error);
        $result = false;
      } else if (!empty($modes)) {
        if ($table == null) {
          $result = $modes;
        } else {
          if (is_string($table)) {
            $tname = $this->encodeKey($table);
            if (!array_key_exists($tname, $modes)) {
              $this->pushError('Not exists the table(%s)', $table);
              $result = false;
            } else {
              $result = $modes[$tname];
            }
          }
        }
      }
    }
    return $result;
  }

  /**
   * Gets the last modified time as unix timestamp from the headers of table
   *
   * @param  string  table name
   * @return int     last modified time, or FALSE on error
   * @access public
   */
  function getlastmod($table) {
    $result = false;
    $info = $this->_getTableInfo($table);
    if (isset($info['lastmod'])) {
      $result = $info['lastmod'];
    }
    return $result;
  }

  /**
   * Sets the last modified time as unix timestamp into the table
   *
   * @param  string  table name
   * @return boolean success or not
   * @access public
   */
  function setLastMod($table) {
    static $length = null;
    if ($length === null) {
      $length = strlen(sprintf('0@%08x#%010u@', 0, 0));
    }
    $result = false;
    while ($this->isLockAll()
      || ($table != null && $this->isLockEx($table))) {
      $this->sleep();
    }
    $tname = $this->encodeKey($table);
    if ($this->lockExAll()) {
      if ($fp = $this->fopen('r+')) {
        $head = $this->fgets($fp);
        $line = $this->fgets($fp);
        $ltnm = $tname . ':';
        $pos = strpos($line, $ltnm);
        $plus = $length;
        if ($pos === false) {
          $this->pushError('Failed in setLastMod(%s)', $table);
          $result = false;
        } else {
          $seekpos = strlen($head) + $pos + strlen($ltnm) + $plus;
          fseek($fp, $seekpos);
          $this->fputs($fp, sprintf('%010u', time()));
          $result = true;
        }
        fclose($fp);
      }
      $this->unlockAll();
    }
    return $result;
  }

  /**
   * Gets the next sequence id from the table
   *
   * @param  string  table name
   * @return number  next sequence id, or false
   * @access public
   */
  function getNextId($table) {
    $result = false;
    $info = $this->_getTableInfo($table);
    if (isset($info['nextid'])) {
      $result = $info['nextid'];
    }
    return $result;
  }

  /**
   * Sets the next sequence id into the table
   *
   * @param  string  table name
   * @param  number  next sequence id
   * @return boolean success or not
   * @access public
   */
  function setNextId($table, $next, $unlock = false) {
    $result = false;
    while ($this->isLockAll() || $this->isLockEx($table)) {
      $this->sleep();
    }
    $tname = $this->encodeKey($table);
    if ($this->lockExAll()) {
      if ($fp = $this->fopen('r+')) {
        $head = $this->fgets($fp);
        $line = $this->fgets($fp);
        $ltnm = $tname . ':';
        $pos = strpos($line, $ltnm);
        if ($pos === false) {
          $this->pushError('Failed in setNexId(%s)', $table);
          $result = false;
        } else {
          $seekpos = strlen($head) + $pos + strlen($ltnm) + 10;
          fseek($fp, $seekpos);
          $puts = sprintf('#%010.0f@%010u', $next, time());
          $this->fputs($fp, $puts);
          $result = true;
        }
        fclose($fp);
      }
      $this->unlockAll();
    }
    return $result;
  }

  /**
   * Returns string as the hexadecimal number of eight digit for lock key
   *
   * @param  string  input value of number
   * @return string  hex number of 8 digit
   * @access public
   */
  function toUniqId($id) {
    if ($id == null || !is_string($id)
      || strlen($id) !== 8  || !$this->ctype->isXdigit($id)) {
      $id = sprintf('%08x', $id);
    }
    if (strlen($id) !== 8) {
      $id = str_repeat('0', 8);
    }
    return $id;
  }

  /**
   * Returns unique id as the hexadecimal number of eight digit
   *
   * @param  void
   * @return string  hex number of 8 digit
   * @access public
   */
  function getUniqId() {
    $id = $this->toUniqId(crc32(uniqid(mt_rand(), true)));
    return $id;
  }

  /**
   * Returns the unique id of instance object
   *
   * @param  void
   * @return string
   * @access public
   */
  function getId() {
    $result = $this->id;
    return $result;
  }

  /**
   * Check whether the id is enabled
   *
   * @see toUniqId, getUniqId
   *
   * @param  string  the target id as string
   * @return boolean whether the id was enabled
   * @access public
   */
  function isEnableId($id) {
    $result = false;
    if (is_string($id) && strlen($id) === 8 && $this->ctype->isXdigit($id)) {
      $result = true;
    }
    return $result;
  }

  /**
   * Check whether the object is instance of Posql_Statement
   *
   * @param  object  the target object to check
   * @return boolean whether the object is instance of statement class
   * @access public
   */
  function isStatementObject($object) {
    $result = false;
    if (is_object($object)
      && strcasecmp(get_class($object), 'Posql_Statement') === 0) {
      $result = true;
    }
    return $result;
  }

  /**
   * Returns this class name
   *
   * @param  boolean whether the name of all lowercase or not
   * @return string  class name
   * @access public
   * @static
   */
  function getClass($tolower = false) {
    static $class;
    if (!$class) {
      $class = strtolower(__CLASS__);
      if (strpos($class, '_')) {
        $class = explode('_', $class);
        $class = reset($class);
      }
      $class = array(
        0 => @ucfirst($class),
        1 => $class
      );
    }
    $key = 0;
    if ($tolower) {
      $key = 1;
    }
    return $class[$key];
  }

  /**
   * Alias of getClass()
   *
   * @param  boolean whether the name of all lowercase or not
   * @return string  class name
   * @access public
   */
  function getClassName($tolower = false) {
    $result = $this->getClass($tolower);
    return $result;
  }

  /**
   * Returns the name of this class as private fields key
   * This is used for an internal process of Posql
   *
   * @param  void
   * @return string  the key name of this class
   * @access public
   */
  function getClassKey() {
    static $classkey;
    if (!$classkey) {
      $classkey = sprintf('.%s', $this->getClass());
    }
    return $classkey;
  }

  /**
   * Generate the key to use on transaction
   *
   * @param  void
   * @return string  the key to use on transaction
   * @access public
   */
  function getTransactionKey() {
    $key = sprintf('.trans-%s.', $this->id);
    $result = $this->encodeKey($key);
    return $result;
  }

  /**
   * Returns this class version
   *
   * @param  boolean whether to get only the major version or not
   * @return string  class version
   * @access public
   * @static
   */
  function getVersion($major = false) {
    static $versions;
    if (!$versions) {
      //$version = '$Version$';
      $version = '2.17';
      $pos = strpos($version, '.');
      if ($pos === false) {
        $versions = array(
          0 => $version,
          1 => $version
        );
      } else {
        $versions = array(
          0 => $version,
          1 => substr($version, 0, $pos)
        );
      }
    }
    $key = 0;
    if ($major) {
      $key = 1;
    }
    return $versions[$key];
  }

  /**
   * Returns the file headers of database.
   * Includes the string valid for access directly to it
   *
   * @param  void
   * @return string  headers as string
   * @access private
   */
  function getHeader() {
    static $header;
    if (!$header) {
      $header = sprintf('%s%0.2f format<'
        .  '?php exit;__halt_compiler();?'
        .  '>#LOCK=',
        $this->getClass(),
        $this->getVersion()
      );
    }
    return $header;
  }

  /**
   * Returns the table delimiters on format of Posql database
   *
   * @param  boolean whether or not to get as list of array
   * @return array   the table delimiters
   * @access public
   */
  function getTableDelimiters($list = false) {
    $result = array(
      $this->DELIM_CACHE => 0,
      $this->DELIM_INDEX => 1,
      $this->DELIM_TABLE => 2,
      $this->DELIM_VIEW  => 3
    );
    if ($list) {
      $result = array_flip($result);
    }
    return $result;
  }

  /**
   * Return the extra alias message for DESCRIBE command
   *
   * @param  void
   * @return string  the extra alias message
   * @access public
   */
  function getExtraAliasMessage() {
    return 'alias for rowid';
  }

  /**
   * Gets the operation mode of SQL Expression as the engine
   *
   * @see $engine
   * @param  boolean  whether the name of all lowercase or all uppercase
   * @return string   the operation mode of SQL Expression
   * @access public
   */
  function getEngine($tolower = false) {
    $engine = $this->engine;
    if ($tolower) {
      $engine = strtolower($engine);
    } else {
      $engine = strtoupper($engine);
    }
    return $engine;
  }

  /**
   * Sets the operation mode of SQL Expression as the engine
   *
   * @see $engine
   * @param  string   the operation mode of SQL Expression as string
   *                  supported modes are "SQL" or "PHP" now
   * @return string   the previous operation mode, or FALSE on error
   * @access public
   */
  function setEngine($engine) {
    $result = false;
    if (!is_string($engine)) {
      $this->pushError('An illegal argument,'
        .  ' and only the string is available');
    } else {
      $engine = strtolower($engine);
      switch ($engine) {
      case 'sql':
        $result = $this->getEngine();
        $this->engine = $engine;
        break;
      case 'php':
        $result = $this->getEngine();
        $this->engine = $engine;
        break;
      case 'js': // so enjoyable if implements in the future.
      case 'ecma':
      default:
        $this->pushError('Not supported the expression engine(%s)',
          $engine);
        $result = false;
        break;
      }
    }
    return $result;
  }

  /**
   * Set the value of maximal minutes as timeout for the dead lock
   *
   * @see getDeadLockTimeout, $deadLockTimeout
   * @param  number  the value of maximal minutes
   * @return boolean success or failure
   * @access public
   */
  function setDeadLockTimeout($timeout = 10) {
    $result = false;
    if ($timeout != null && is_numeric($timeout)
      && $timeout >= 1 && $timeout <= $this->MAX) {
      $this->deadLockTimeout = (int) $timeout;
      $result = true;
    } else {
      $this->pushError('The deadLockTimeout should be larger than 1'
        .  ' in the numerical value(%s)', $timeout);
      $result = false;
    }
    return $result;
  }

  /**
   * Set the value of auto_increment that will be used as "rowid".
   * This value should be larger than 1 in the numerical value.
   *
   * @see getAutoIncrement, $autoIncrement, $auto_increment
   * @param  number  the value of auto_increment
   * @return boolean success or failure
   * @access public
   */
  function setAutoIncrement($auto_increment = 1) {
    $result = false;
    if ($auto_increment != null && is_numeric($auto_increment)
      && $auto_increment >= 1 && $auto_increment <= $this->MAX) {
      $this->autoIncrement = (int) $auto_increment;
      $result = true;
    } else {
      $this->pushError('The auto_increment should be larger than 1'
        .  ' in the numerical value(%s)', $auto_increment);
      $result = false;
    }
    return $result;
  }

  /**
   * Set the type for supplements which omitted data type.
   * This type adjust by the "CREATE TABLE" and "DESCRIBE" commands.
   *
   * @see getDefaultDataType, $defaultDataType
   *
   * @param  string  the type for supplements which omitted data type
   * @return mixed   it will return the old data type on success,
   *                   or FALSE that settings had an error
   * @access public
   */
  function setDefaultDataType($type) {
    $result = false;
    if (is_string($type) && strlen($type) < $this->MAX) {
      $type = trim($type);
      $result = $this->defaultDataType;
      $this->defaultDataType = $type;
    }
    return $result;
  }

  /**
   * Sets the value of the database extension
   *
   * @see $ext, Posql_Config
   * @param  string  the value of the database extension
   * @return mixed   it will return the old value of extension on success,
   *                   or FALSE that settings had an error
   * @access public
   */
  function setExt($ext) {
    $result = false;
    if ($ext == null) {
      $result = $this->ext;
      $this->ext = null;
    } else if (is_string($ext)) {
      $result = $this->ext;
      $this->ext = $this->toExt($ext);
    } else {
      $ext = is_array($ext) ? implode(',', $ext) : strval($ext);
      $this->pushError('illegal the value of the argument as extension(%s)',
        $ext);
    }
    return $result;
  }

  /**
   * Sets the value of maximum bytes which handle the size of associable all
   *
   * @see $MAX, Posql_Config
   * @param  number  the value of miximum bytes
   * @return mixed   it will return the old value of maximum on success,
   *                    or FALSE that settings had an error
   * @access public
   */
  function setMax($max) {
    $result = false;
    if (is_numeric($max) && $max > 0) {
      $result = $this->MAX;
      $this->MAX = $max;
    } else {
      $this->pushError('illegal the value of the argument as MAX(%s)', $max);
    }
    return $result;
  }

  /**
   * Applies the serializing function of the given argument
   * The function is must able to serializing all PHP's values
   *
   * @see encode(), $encoder, Posql_Config
   * @param  mixed   the serializing function
   * @return mixed   it will return the old value of serializer on success,
   *                   or FALSE on error
   * @access public
   */
  function setEncoder($encoder) {
    $result = false;
    if (is_callable($encoder)) {
      $result = $this->encoder;
      $this->encoder = $encoder;
    } else {
      $e = implode('::', (array) $encoder);
      $this->pushError('Cannot call the encoder function(%s)', $e);
    }
    return $result;
  }

  /**
   * Applies the unserializing function of the given argument
   * The function is must able to unserializing to PHP's value
   *
   * @see decode(), $decoder, Posql_Config
   * @param  mixed   the unserializing function
   * @return mixed   it will return the old value of unserializer on success,
   *                   or FALSE on error
   * @access public
   */
  function setDecoder($decoder) {
    $result = false;
    if (is_callable($decoder)) {
      $result = $this->decoder;
      $this->decoder = $decoder;
    } else {
      $e = implode('::', (array) $decoder);
      $this->pushError('Cannot call the decoder function(%s)', $e);
    }
    return $result;
  }

  /**
   * @access private
   */
  function _setRegistered($registered) {
    $this->_registered = (bool)$registered;
  }

  /**
   * @access private
   */
  function _getRegistered() {
    return $this->_registered;
  }

  /**
   * @access private
   */
  function _setTerminated($terminated) {
    $this->_terminated = (bool)$terminated;
  }

  /**
   * @access private
   */
  function _getTerminated() {
    return $this->_terminated;
  }

  /**
   * @access private
   */
  function _setLockedTables($tables) {
    if (is_array($tables)) {
      $this->_lockedTables = $tables;
    }
  }

  /**
   * @access private
   */
  function _getLockedTables() {
    return $this->_lockedTables;
  }

  /**
   * Using the query of INSERT that for array_map() function
   *
   * @param  array   a record of target
   * @return array   array of adjusted callback
   * @access private
   */
  function _addMap($put) {
    $add = array();
    $increment = false;
    foreach ($this->_curMeta as $key => $val) {
      switch ($key) {
      case 'rowid':
      case $this->_primaryKey:
        $add[$key] = $this->next;
        $increment = true;
        break;
      case 'ctime':
      case 'utime':
        $add[$key] = time();
        break;
      default:
        if (empty($this->_execExpr)) {
          $add[$key] = $this->getValue($put, $key, $val);
        } else {
          if (array_key_exists($key, $put)) {
            $add[$key] = $this->execInsertExpr($put, $key, $val);
          } else {
            $add[$key] = $val;
          }
        }
        break;
      }
    }
    if ($increment) {
      if ($this->autoIncrement) {
        $this->next = $this->math->add(
          (int)$this->autoIncrement,
          $this->next
        );
        //XXX: warn the message
        // The maximum value of "rowid" is 9,999,999,999 now.
        // It is necessary to warn because this value exceeds
        //  integer of PHP.
        if (strlen($this->next) > 10) {
          $this->pushError('Over the auto increment value(%s)',
            $this->next);
          $this->next = 1;
        }
      }
    }
    $result = $this->tableName . $this->DELIM_TABLE . $this->encode($add)
      . $this->NL;
    if (strlen($result) > $this->MAX) {
      $this->pushError('Over the maximum length');
      $result = null;
    }
    return $result;
  }

  /**
   * Using the query of UPDATE that for array_map() function
   *
   * @param  array   a record of target
   * @return array   array of adjusted callback
   * @access private
   */
  function _upMap($row) {
    foreach ($row as $key => $val) {
      switch ($key) {
      case 'utime':
        $row[$key] = time();
        break;
      default:
        if (empty($this->_execExpr)) {
          $row[$key] = $this->getValue($this->_curMeta, $key, $val);
        } else {
          if (array_key_exists($key, $this->_curMeta)) {
            $row[$key] = $this->execUpdateExpr($row, $key, $val);
          } else {
            $row[$key] = $val;
          }
        }
        break;
      }
    }
    $result = $this->tableName . $this->DELIM_TABLE . $this->encode($row)
      . $this->NL;
    if (strlen($result) > $this->MAX) {
      $this->pushError('Over the maximum length(%.0f)', strlen($result));
      $result = null;
    }
    return $result;
  }

  /**
   * Restores the updated records on error
   * This method to used by array_map() function
   *
   * @param  array   a record of target
   * @return array   array of adjusted callback
   * @access private
   */
  function _upRestoreMap($row) {
    $result = $this->tableName . $this->DELIM_TABLE . $this->encode($row)
      . $this->NL;
    return $result;
  }

  /**
   * Callback function for queryf()
   *
   * @access private
   */
  function _queryFormatCallback($match, $set_args = false) {
    static $args = array(), $q = "'";
    if ($set_args) {
      if (is_array($match) && is_array(reset($match))) {
        $args = reset($match);
      } else {
        $args = (array) $match;
      }
      return;
    }
    $result = null;
    $length = null;
    $quotes = null;
    $escaped = false;
    $recursive = false;
    $format = end($match);
    $format_index = key($match);
    $quotes_index = 1;
    $length_index = 3;
    reset($match);
    if (isset($match[$length_index])
      && is_numeric($match[$length_index])) {
      $length = (int) $match[$length_index];
    }
    switch ($format) {
    case '%':
    case '%%':
      $result = '%';
      break;
    case 'a':
      $arg = array_shift($args);
      switch (true) {
      case is_int($arg):
      case is_float($arg):
        $match[$format_index] = 'n';
        array_unshift($args, $arg);
        $result = $this->_queryFormatCallback($match);
        $recursive = true;
        break;
      case is_bool($arg):
        $result = $arg ? 'TRUE' : 'FALSE';
        break;
      case is_string($arg):
        $quotes = $this->getValue($match, $quotes_index);
        if ($quotes == null) {
          $match[$format_index] = 'q';
        } else {
          $match[$format_index] = 's';
        }
        array_unshift($args, $arg);
        $result = $this->_queryFormatCallback($match);
        $recursive = true;
        break;
      case is_null($arg):
        $result = 'NULL';
        break;
      case is_array($arg):
      case is_object($arg):
      default:
        $error = gettype($arg);
        $this->pushError('Must be scaler type'
          .  ' on variable(%s)', $error);
        $result = 'NULL';
        break;
      }
      break;
    case 'B':
      $result = (string)array_shift($args);
      $result = $q . base64_encode($result) . $q;
      $result = sprintf('base64_decode(%s)', $result);
      $escaped = true;
      break;
    case 'n':
      $result = array_shift($args);
      if (!is_numeric($result)) {
        $result = '0';
      } else {
        $result = $this->math->format($result);
        if ($result >= $this->MAX) {
          if (ord($result) !== ord($q)) {
            $result = $q . $result . $q;
          }
          $quotes = $q;
        }
      }
      $escaped = true;
      break;
    case 'q':
      if (array_key_exists($quotes_index, $match)) {
        if ($match[$quotes_index] == null) {
          $match[$quotes_index] = $q;
        }
      }
    case 's':
      $quotes = $this->getValue($match, $quotes_index);
      $result = array_shift($args);
      if ($quotes == null) {
        $result = $this->escapeString($result);
      } else {
        $result = $this->escapeString($result, $quotes);
      }
      $escaped = true;
      break;
    default:
      $result = sprintf($format, array_shift($args));
      break;
    }
    if (!$recursive) {
      if ($length !== null && strlen($result) > $length) {
        $marker = $this->getValue($match, 2);
        $result = $this->truncatePad($result, $length,
          $marker, $quotes, $escaped);
      }
    }
    return $result;
  }

  /**
   * Format the SQL statement based on format of sprintf().
   *
   * @see    queryf
   * @param  string       the SQL statement as formatted as sprintf()
   * @param  mixed  (...) zero or more parameters to be passed to the function
   * @return string       formatted statement as string
   * @access public
   */
  function formatQueryString($query) {
    static $opted = false, $pattern = '
   {(?:
       ([\'"])?
       %
       \'?(\.+|\s|(?!-)[^.\'\s%0-9aBnqs]*)
       ((?!0)[0-9]*)
       (?(1)([as])\1|([%aBnqs]))
     |
       %
       (?:[0-9]+[$]|)[+-]?
       (?:[0\s]|\'.|)-?
       (?:[0-9]*|)
       (?:\.[0-9]+|)
       [%bcdeufFosxX]
    )
   }uUx';
    if (!$opted) {
      $pattern = preg_replace('{\s+|x$}', '', $pattern);
      $opted = true;
    }
    $result = '';
    $args = func_get_args();
    $argn = func_num_args();
    if ($argn > 1) {
      array_shift($args);
      if (is_array(reset($args))) {
        $args = array_shift($args);
      }
      $this->_queryFormatCallback($args, true);
      $result = preg_replace_callback($pattern,
        array(&$this, '_queryFormatCallback'), $query);
    } else if (is_array($query) && is_string(reset($query))) {
      $result = call_user_func_array(array(&$this,
          'formatQueryString'), $query);
    } else if (is_string($query)) {
      $result = $query;
    } else {
      $error_type = gettype($query);
      $this->pushError('illegal types in arguments on formatQueryString(%s)',
        $error_type);
      $result = false;
    }
    //debug($result, '//___formatted___//');
    return $result;
  }

  /**
   * Merges an array these always will be appended as extra columns
   *
   * @note
   *  below are the extra columns
   * ----------------------------------------------------------------------
   *  rowid - works like SQLite's "ROWID" as unique key and auto increment
   *  ctime - created time as unix time
   *  utime - updated time as unix time
   * ----------------------------------------------------------------------
   * @param  array   an array of target
   * @return array   an array which merged with extra columns
   * @access private
   * @static
   */
  function mergeDefaults($array) {
    $result = array();
    $time = time();
    $defaults = array(
      'rowid' => 0,
      'ctime' => $time,
      'utime' => $time
    );
    $result = array_merge((array)$array, $defaults);
    return $result;
  }

  /**
   * applies serializing to the given function
   * Substitute the function name for $encoder if you modify it
   *
   * @param  mixed  input values
   * @return string
   * @access public
   */
  function encode($value) {
    return base64_encode(call_user_func($this->encoder, $value));
  }

  /**
   * applies unserializing to the given function
   *
   * @param  string  input values
   * @return array
   * @access public
   */
  function decode($value) {
    return call_user_func($this->decoder, base64_decode($value));
  }

  /**
   * Encodes for table name as base 64
   *
   * @param  string  input values
   * @return string
   * @access public
   * @static
   */
  function encodeKey($table) {
    return base64_encode($table);
  }

  /**
   * Decodes for table name as base 64
   *
   * @param  string  input values
   * @return string
   * @access public
   * @static
   */
  function decodeKey($table) {
    return base64_decode($table);
  }

  /**
   * Returns the default value of arguments on SELECT method
   *
   * @see    Posql_Parser::parseSelectArguments, Posql::select
   *
   * @param  mixed    index of target arguments
   * @return mixed    default value to associate
   * @access private
   */
  function getSelectDefaultArguments($index = null) {
    static $args = array(
      'from'   => null,
      'select' => '*',  'where'  => true,
      'group'  => null, 'having' => true,
      'order'  => null, 'limit'  => null
    );
    $result = null;
    if ($index === null) {
      $result = $args;
    } else {
      if (is_int($index)) {
        $result = $this->getValue(array_values($args), $index);
      } else {
        $result = $this->getValue($args, $index);
      }
    }
    return $result;
  }

  /**
   * Returns the array which has default values for aggregate functions
   *
   * @param  boolean  whether "count_all" is included or not
   * @return array    the array which has default values
   * @access private
   * @static
   */
  function getAggregateDefaults($with_count_all = false) {
    static $defaults = array(
      'avg'   => null,
      'count' => 0,
      'max'   => null,
      'min'   => null,
      'sum'   => null
    );
    $result = $defaults;
    if ($with_count_all) {
      $result['count_all'] = 0;
    }
    return $result;
  }

  /**
   * Returns the array to generate variable with extract() function
   *
   * @param  boolean  whether "count_all" is included or not
   * @return array    array to generate variable with extract() function
   * @access private
   */
  function getAggregateExtract($with_count_all = false) {
    $result = array();
    $aggregates = $this->getAggregateDefaults($with_count_all);
    foreach ($aggregates as $name => $val) {
      $result[$name] = $name;
    }
    return $result;
  }

  /**
   * Executes the all aggregate functions to result rows
   *
   * @param  array   result records
   * @return array   array of result of all aggregate functions
   * @access private
   */
  function aggregateAll(&$rows) {
    $std = array();
    $all = array();
    if (is_array($rows) && is_array(reset($rows))) {
      $row_count = count($rows);
      $cols = array_keys(reset($rows));
      $funcs = array_keys($this->getAggregateDefaults());
      foreach ($funcs as $func) {
        foreach ($cols as $col) {
          $std[$func][$col] = null;
          $all[$func][$col] = null;
        }
      }
      extract($this->getAggregateExtract());
      for ($i = 0; $i < $row_count; $i++) {
        foreach ($rows[$i] as $key => $val) {
          if ($val !== null) {
            $std[$count][$key] = $this->math->add($std[$count][$key], 1);
            if ($val > $std[$max][$key]) {
              $std[$max][$key] = $val;
            }
            if (!$i || $val < $std[$min][$key]) {
              $std[$min][$key] = $val;
            }
            $std[$sum][$key] = $this->math->add($std[$sum][$key], $val);
          }
          $all[$count][$key] = $this->math->add($all[$count][$key], 1);
          if ($val > $all[$max][$key]) {
            $all[$max][$key] = $val;
          }
          if (!$i || $val < $all[$min][$key]) {
            $all[$min][$key] = $val;
          }
          $all[$sum][$key] = $this->math->add($all[$sum][$key], $val);
        }
      }
      foreach ($cols as $col) {
        $std[$avg][$col] = $this->math->div($std[$sum][$col],
          $std[$count][$col],
          4);
        $all[$avg][$col] = $this->math->div($all[$sum][$col],
          $all[$count][$col],
          4);
      }
    }
    $result = array(
      'std' => $std,
      'all' => $all
    );
    return $result;
  }

  /**
   * Executes all aggregate functions for the rows which has groups
   *
   * @param  array   result records
   * @param  array   array with index of row which has a group
   * @return array   array of result of all aggregate functions
   * @access private
   */
  function aggregateByGroups(&$rows, $groups) {
    $result = array();
    if (!$this->hasError()) {
      if (!empty($rows) && is_array($rows) && is_array(reset($rows))) {
        $agg = array();
        $agg_defaults = $this->getAggregateDefaults(true);
        extract($this->getAggregateExtract(true));
        $rows_cols = array_keys(reset($rows));
        foreach ($groups as $i => $array) {
          $agg[$i] = array();
          foreach ($rows_cols as $col) {
            $agg[$i][$col] = $agg_defaults;
            foreach ($array as $index) {
              $val = & $rows[$index][$col];
              $agi = & $agg[$i][$col];
              if ($val !== null) {
                $agi[$count] = $this->math->add($agi[$count], 1);
                if ($val > $agi[$max]) {
                  $agi[$max] = $val;
                }
                if ($agi[$min] === null
                  || $agi[$min]  >  $val) {
                  $agi[$min] = $val;
                }
                $agi[$sum] = $this->math->add($agi[$sum], $val);
              }
              $agi[$count_all] = $this->math->add($agi[$count_all], 1);
            }
            $agi[$avg] = $this->math->div($agi[$sum], $agi[$count], 4);
          }
          $result[] = $agg[$i];
        }
        unset($val, $agi);
      }
    }
    //debug($result,"color=green:Groups;");
    return $result;
  }

  /**
   * Executes all aggregate functions for the rows which has not groups
   *
   * @param  array   result records
   * @param  array   array which was grouped by aggregate functions
   * @return void
   * @access private
   */
  function aggregateAssignByGroups(&$rows, &$groups) {
    if (!$this->hasError()) {
      if (!empty($rows)   && is_array($rows)   && is_array(reset($rows))
        && !empty($groups) && is_array($groups) && is_array(reset($groups))) {
        $agg_defaults = $this->getAggregateDefaults(true);
        extract($this->getAggregateExtract(true));
        $row_count = count($rows);
        $rows_cols = array_keys(reset($rows));
        for ($i = 0; $i < $row_count; $i++) {
          if (!array_key_exists($i, $groups)) {
            $groups[$i] = array();
            foreach ($rows_cols as $col) {
              $groups[$i][$col] = $agg_defaults;
              $agi = & $groups[$i][$col];
              $val = & $rows[$i][$col];
              if ($val !== null) {
                $agi[$count] = $this->math->add($agi[$count], 1);
                if ($val > $agi[$max]) {
                  $agi[$max] = $val;
                }
                if ($agi[$min] === null
                  || $agi[$min]  >  $val) {
                  $agi[$min] = $val;
                }
              }
              $agi[$count_all] = $this->math->add($agi[$count_all], 1);
              $agi[$sum] = $this->math->add($agi[$sum], $val);
              $agi[$avg] = $agi[$sum];
            }
          }
        }
        unset($val, $agi);
      }
    }
  }

  /**
   * Format the aggregate function to correspond with result rows
   *
   * @param  string  the function name which should be parsed in
   * @param  string  the name of column as the argument of function
   * @return string  the formatted result as string
   * @access private
   */
  function formatAggregateFunction($funcname, $column) {
    $result = sprintf('%s(%s)', $funcname, $column);
    return $result;
  }

  /**
   * Grouping function to use it by select method for each items
   *
   * @param  array   result records as array
   * @param  string  the name of column which will be a group, or expression
   * @return array   array of result of all aggregate functions
   * @access private
   */
  function groupBy(&$rows, $groups) {
    $result = array();
    if (!$this->hasError()) {
      $parsed = $this->parseGroupBy($groups);
      if (array_key_exists('fields', $parsed)
        && array_key_exists('is_expr', $parsed)) {
        $group_count = count($parsed['fields']);
        if (count($parsed['is_expr']) === $group_count) {
          if ($group_count === 1) {
            $fields = array_shift($parsed['fields']);
            $is_expr = array_shift($parsed['is_expr']);
            $result = $this->_groupBy($rows, $fields, $is_expr);
          } else {
            $result = $this->_multiGroupBy($rows, $parsed['fields'],
              $parsed['is_expr']);
          }
        }
      }
    }
    return $result;
  }

  /**
   * Grouping function to use it by select method for one item
   *
   * @param  array   result records as array
   * @param  string  the name of column which will be a group, or expression
   * @param  boolean whether the grouping value is the expression
   * @return array   array of result of all aggregate functions
   * @access private
   */
  function _groupBy(&$rows, $group_col, $is_expr = false) {
    $result = array();
    if (!$this->hasError()) {
      $uniqs = array();
      $row_count = count($rows);
      if ($is_expr) {
        $group_expr = $this->validExpr($group_col);
        $first = true;
        for ($i = $row_count - 1; $i >= 0; --$i) {
          $val = null;
          if ($first) {
            $val = $this->safeExpr($rows[$i], $group_expr);
            if ($this->hasError()) {
              break;
            }
            $first = false;
          } else {
            $val = $this->expr($rows[$i], $group_expr);
          }
          $uniqs[ $this->hashValue($val) ][] = $i;
        }
      } else {
        $group_col = $this->replaceAliasName($group_col);
        for ($i = $row_count - 1; $i >= 0; --$i) {
          foreach ($rows[$i] as $key => $val) {
            if ($key === $group_col) {
              $uniqs[ $this->hashValue($val) ][] = $i;
            }
          }
          if (empty($uniqs)) {
            $this->pushError('Not exists the column(%s) on GROUP BY clause',
              $group_col);
            $rows = array();
            break;
          }
        }
      }
      if (!$this->hasError()) {
        $result = $this->_groupByResolve($rows, $uniqs);
      }
    }
    return $result;
  }

  /**
   * Grouping function to use by SELECT for two or more items
   *
   * @param  array   result records as array
   * @param  array   column names, or expression as array
   * @param  array   whether the grouping value is the expression
   * @return array   array of result of all aggregate functions
   * @access private
   */
  function _multiGroupBy(&$rows, $groups, $is_exprs) {
    $result = array();
    if (!$this->hasError()) {
      if (count($groups) === count($is_exprs)) {
        $firsts = array();
        $exprs = array();
        foreach ($is_exprs as $i => $is_expr) {
          $firsts[$i] = true;
          if ($is_expr) {
            $exprs[$i] = $this->validExpr($groups[$i]);
          } else {
            $exprs[$i] = null;
          }
          if ($this->hasError()) {
            break;
          }
        }
        if ($this->hasError()) {
          $rows = array();
        } else {
          $uniqs = array();
          $row_count = count($rows);
          for ($i = $row_count - 1; $i >= 0; --$i) {
            $hash = array();
            foreach ($groups as $j => $group) {
              if ($is_exprs[$j]) {
                if ($firsts[$j]) {
                  $hash[] = $this->safeExpr($rows[$i], $exprs[$j]);
                  if ($this->hasError()) {
                    break;
                  }
                  $firsts[$j] = false;
                } else {
                  $hash[] = $this->expr($rows[$i], $exprs[$j]);
                }
              } else {
                $key = $this->findIdentifier($group, $rows[$i]);
                if (!array_key_exists($key, $rows[$i])) {
                  $this->pushError('No such column(%s)'
                    .  ' on GROUP BY clause', $group);
                  break;
                }
                $hash[] = $rows[$i][$key];
              }
            }
            if ($this->hasError()) {
              $rows = array();
              break;
            }
            $uniqs[ $this->hashValue($hash) ][] = $i;
          }
        }
      }
      if (!$this->hasError()) {
        $result = $this->_groupByResolve($rows, $uniqs);
      }
    }
    return $result;
  }

  /**
   * @access private
   */
  function _groupByResolve(&$rows, $uniqs) {
    $result = null;
    if (!$this->hasError()) {
      $diffs  = array();
      $groups = array();
      foreach ($uniqs as $hashed => $array) {
        $diffs[ end($array) ] = 1;
        if (count($array) > 1) {
          array_unshift($groups, $array);
          unset($uniqs[$hashed]);
        }
      }
      if (!empty($uniqs) && empty($groups)) {
        foreach ($uniqs as $hashed => $array) {
          array_unshift($groups, $array);
          unset($uniqs[$hashed]);
        }
      }
      unset($uniqs);
      $groups_idx = $groups;
      $groups = $this->aggregateByGroups($rows, $groups_idx);
      $row_count = count($rows);
      for ($i = 0; $i < $row_count; $i++) {
        if (!array_key_exists($i, $diffs)) {
          unset($rows[$i]);
        }
      }
      $push = array();
      $groups_count = count($groups_idx);
      for ($i = 0; $i < $groups_count; $i++) {
        $pop = array_pop($groups_idx[$i]);
        if (array_key_exists($pop, $rows)) {
          $row = $rows[$pop];
          unset($rows[$pop]);
          $push[] = $row;
        }
      }
      while ($row = array_pop($push)) {
        array_unshift($rows, $row);
      }
      $rows = array_values($rows);
      $this->aggregateAssignByGroups($rows, $groups);
      $result = $groups;
    }
    return $result;
  }

  /**
   * The sorting function to used by SELECT command
   *
   * @param  array   the result records
   * @param  mixed   the value of column name,
   *                   ascending order of object or descending order
   * @return void
   * @access private
   */
  function orderBy(&$rows, $orders) {
    if (!$this->hasError() && $orders != null) {
      if (!is_array($orders) || !is_string(key($orders))) {
        $orders = $this->parseOrderByTokens($orders);
      }
      if (!empty($orders) && is_array($orders) && is_string(key($orders))
        && !empty($rows)   && is_array($rows)   && is_array(reset($rows))) {
        $params = array();
        $args   = array();
        $index  = 0;
        $reset  = reset($rows);
        $row_count = count($rows);
        foreach ($orders as $col => $sort) {
          $identifier = $this->findIdentifier($col, $reset);
          if (is_string($identifier) && $identifier != null
            && array_key_exists($identifier, $reset)) {
            $col = $identifier;
          }
          if (!is_string($col)) {
            $col = '';
          }
          if (!array_key_exists($col, $reset)) {
            $this->pushError('Not exists the column(%s)'
              . ' on ORDER BY clause', $col);
            break;
          }
          for ($i = 0; $i < $row_count; $i++) {
            $params[$col][$i] = & $rows[$i][$col];
          }
          $args[$index++] = & $params[$col];
          $args[$index++] = $sort;
        }
        if (!$this->hasError()) {
          $args[$index] = & $rows;
          call_user_func_array('array_multisort', $args);
        }
        unset($args, $params);
      }
    }
  }

  /**
   * Assign the LIMIT and OFFSET phrases for the SELECT command
   *
   * @param  array    the result records
   * @param  mixed    the argument given as LIMIT phrase of SELECT command
   * @return void
   * @access private
   */
  function assignLimitOffset(&$rows, $limit) {
    $offset = 'offset';
    $length = 'length';
    if (!is_array($limit) || !array_key_exists($offset, $limit)) {
      $limit = $this->parseLimitTokens($limit);
    }
    if (array_key_exists($offset, $limit)
      && array_key_exists($length, $limit)) {
      if ($limit[$length] === 0) {
        $rows = array();
      } else if ($limit[$length] != null) {
        $rows = array_slice($rows, $limit[$offset], $limit[$length]);
      } else {
        $rows = array_slice($rows, $limit[$offset]);
      }
    } else {
      $this->pushError('illegal the value of arguments on LIMIT clause');
    }
  }

  /**
   * @access private
   */
  function &_emitDisableFunctions() {
    return $this->disableFuncs;
  }

  /**
   * Returns the list of to disable functions as array
   *
   * @param  void
   * @return array  the list of to disable functions
   * @access public
   */
  function getDisableFunctions() {
    $result = array();
    $this->checkDisableFunctions();
    $df = $this->_emitDisableFunctions();
    $result = array_keys((array)$df);
    return $result;
  }

  /**
   * Sets the name of function to disable for using in expression
   * To reset the setting, it calls by an empty argument
   *
   * @param  mixed  name of function to disable as array, or string
   * @return number number of affected functions
   * @access public
   */
  function setDisableFunctions($func = array()) {
    $result = 0;
    $df = & $this->_emitDisableFunctions();
    $argn = func_num_args();
    if ($argn === 0) {
      $result = count($df);
      $df = array();
    } else {
      if ($argn > 1) {
        $func = func_get_args();
        $func = $this->toOneArray($func);
      }
      if (!is_array($func)) {
        $func = (array) $this->splitEnableName($func);
      }
      if ($this->isAssoc($func)) {
        $func = array_keys($func);
      }
      foreach ((array)$func as $fn) {
        $fn = strtolower($fn);
        if ($fn != null && !array_key_exists($fn, $df)) {
          $df[$fn] = ++$result;
        }
      }
    }
    $this->checkDisableFunctions();
    unset($df);
    return $result;
  }

  /**
   * Checks whether the function is disabled
   *
   * @param  string  the name of function to check
   * @return boolean whether the function is disabled
   * @access public
   */
  function isDisableFunction($func_name) {
    $result = false;
    if (is_string($func_name)) {
      $df = $this->_emitDisableFunctions();
      if (!empty($df) && is_array($df)
        && (isset($df[$func_name]) || array_key_exists($func_name, $df))) {
        $result = true;
      }
      unset($df);
    }
    return $result;
  }

  /**
   * Normalizes to the format to which the disabled functions are effective
   *
   * @param  void
   * @return void
   * @access private
   */
  function checkDisableFunctions() {
    $df = & $this->_emitDisableFunctions();
    if (!empty($df)) {
      if (!is_array($df)) {
        $df = (array) $this->splitEnableName($df);
      }
      if (!$this->isAssoc($df)) {
        $df = $this->flipArray($df);
      }
    }
    unset($df);
  }
}
