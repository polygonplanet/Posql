<?php
// PosqlAdmin
// ぽすきゅーえるあどみん
posql_admin_run();

/**
 * @name PosqlAdmin
 *
 * The database manager of class Posql
 *
 * WARNING:
 *   Must be given the permission "enables writing" (e.g. 666 (rw-rw-rw))
 *   to the script after uploaded to the server.
 *   Because, this script saves data in oneself.
 *
 * PHP version 4.3+ or 5+
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet@gmail.com>
 * @link      http://sourceforge.jp/projects/posql/
 * @license   Dual licensed under the MIT and GPL licenses
 * @copyright Copyright (c) 2010 polygon planet
 * @version   $Id: posqladmin.php,v 0.19a 2011/12/13 20:26:17 polygon Exp $
 *---------------------------------------------------------------------------*/
class PosqlAdmin {

  var $debug            = false;
  var $charset          = 'UTF-8';
  var $timeLimit        = 1800;   // max_execution_time (php.ini)
  var $memoryLimit      = '128M'; // memory_limit (php.ini)
  var $maxEditRowsCount = 30;   // maximum editable row-count
  var $maxLoginHistory  = 20;   // maximum number of the login histories
  var $script;
  var $version;
  var $className;
  var $title;
  var $mem;
  var $db;
  var $isLogin = false;
  var $status = array();
  var $errors = array();
  var $lastLogin;
  var $loginErrorCount;
  var $selectDBAction;
  var $posqlPath;
  var $hideWarnMsg;
  var $config_posql;
  var $config_id;
  var $config_ps;
  var $config_css;
  var $config_js;
  var $config_help;
  var $config_dl;
  var $config_mode;
  var $optimizeLines = array();
  var $OPTIMIZE_SKIP    = 0;
  var $OPTIMIZE_DELETE  = 1;
  var $OPTIMIZE_REPAIR  = 2;
  var $helpDocFrameId   = 'posql_help_frame';
  var $projectURL       = 'http://sourceforge.jp/projects/posql/';


  function init(){
    defined('E_STRICT') or define('E_STRICT', 2048);
    if ($this->debug) {
      error_reporting(E_ALL | E_STRICT);
    } else {
      //error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
      error_reporting(1023);
    }
    if (!@ini_get('safe_mode')) {
      if ($this->timeLimit > ((int)(@ini_get('max_execution_time')))) {
        @set_time_limit($this->timeLimit);
      }
    }
    if (!defined('PHP_EOL')) {
      switch (ord(PHP_OS) | 0x20) {
        case 0x77: define('PHP_EOL', "\r\n"); break; // win
        case 0x64: define('PHP_EOL', "\r");   break; // mac
        default:   define('PHP_EOL', "\n");   break; // unix etc.
      }
    }
    if (function_exists('date_default_timezone_set')
     && function_exists('date_default_timezone_get')) {
      @date_default_timezone_set(@date_default_timezone_get());
    }
    $this->setConfigAsBigInt('memory_limit', $this->memoryLimit, 'string');
    $this->version   = $this->getVersion();
    $this->charset   = $this->getCharset();
    $this->script    = $this->getScriptName();
    $this->className = $this->getClass();
    $this->title     = $this->className;
    $this->status = array();
    $this->errors = array();
    $this->mem = & Memo();
    $this->posqlPath = null;
    $this->db = null;
  }


  function run(){
    $action = $this->getPost('a');
    if ($action == null || $action === 'login' || $this->checkAuth()) {
      $this->action();
    } else {
      $this->displayAuthError();
    }
  }


  function action(){
    $this->includePosql();
    $m = strtolower($this->getPost('m'));
    switch (strtolower($this->getPost('a'))) {
      case 'menu':
          switch ($m) {
            case 'top':
                $this->write('head', 'title', 'menu', 'top', 'foot');
                break;
            case 'sql':
                $this->selectDBAction = 'sql';
                $this->write('head', 'title', 'menu',
                             'selectdb', 'sql', 'foot');
                break;
            case 'manage':
                $this->write('head', 'title', 'menu',
                             'managelist', 'managesearch', 'foot');
                break;
            case 'import':
                $this->selectDBAction = 'import';
                $this->write('head', 'title', 'menu',
                             'selectdb', 'import', 'foot');
                break;
            case 'export':
                $this->selectDBAction = 'export';
                $this->write('head', 'title', 'menu',
                             'selectdb', 'export', 'foot');
                break;
            case 'config':
                $this->write('head', 'title', 'menu', 'config', 'foot');
                break;
            case 'help':
                $this->write('head', 'title', 'menu', 'help', 'foot');
                break;
            case 'logout':
                header('Location: ' . $this->script);
                exit;
          }
          break;
      case 'select_db_sql':
      case 'sql':
      case 'sql_result':
          $this->selectDBAction = 'sql';
          $this->write('head', 'title', 'menu', 'selectdb', 'sql');
          if ($m === 'execute') {
            $this->write('sqlresult');
          }
          $this->write('foot');
          break;
      case 'manage':
      case 'managelist':
      case 'managesearch':
          switch ($m) {
            case 'result':
                $result = $this->getPost('managesearch_result');
                if ($result == null || !(@is_file($result))) {
                  $this->setStatus('無効なファイルです('
                                         . $result . ')', true);
                } else {
                  $tmp = $this->db->getPath();
                  $this->db->setPath($result);
                  if (!$this->db->isDatabase()) {
                    $this->setStatus('有効なデータベースではありません('
                                  . $result . ')', true);
                  } else {
                    $list = $this->mem->loadAsPrivate('manage.list');
                    if (!is_array($list)) {
                      $list = array();
                    }
                    if (in_array($result, $list)) {
                      $this->setStatus('すでに登録されています', true);
                    } else {
                      $list[] = $result;
                      if ($this->mem->saveAsPrivate('manage.list', $list)) {
                        $this->setStatus('管理リストに追加しました('
                                                     . $result . ')');
                      } else {
                        $this->setStatus('管理リスト追加に失敗! ('
                                                   . $result . ')', true);
                      }
                    }
                  }
                  $this->db->setPath($tmp);
                }
                unset($result, $tmp, $list);
            case 'search':
            default:
                $this->write('head', 'title', 'menu',
                             'managelist', 'managesearch', 'foot');
                break;
          }
          break;
      case 'update_posqladmin':
          $this->write('head', 'title', 'menu');
          if ($m === 'upload_script') {
            $this->write('updatePosqlAdminResult');
          } else {
            $this->write('updatePosqlAdmin');
          }
          $this->write('foot');
          break;
      case 'import':
      case 'select_db_import':
          $this->selectDBAction = 'import';
          $this->write('head', 'title', 'menu');
          if ($m === 'execute') {
            $this->write('importResult');
          } else {
            $this->write('selectdb', 'import');
          }
          $this->write('foot');
          break;
      case 'export':
      case 'select_db_export':
          $this->selectDBAction = 'export';
          $this->write('head', 'title', 'menu', 'selectdb', 'export');
          if ($m === 'execute') {
            $this->write('exportResult');
          }
          $this->write('foot');
          break;
      case 'create_db':
          $this->write('head', 'title', 'menu', 'createdb', 'foot');
          break;
      case 'create_db_execute':
          $this->write('head', 'title', 'menu', 'createdbResult', 'foot');
          break;
      case 'config':
          foreach (array('posql', 'id', 'ps',  'css',
                         'js',  'help', 'dl', 'mode') as $var) {
            $param = sprintf('config_%s', $var);
            $this->{$param} = $this->getPost($param);
            ${$var} = & $this->{$param};
          }
          $dl = empty($dl) ? '' : '1';
          unset($var, $param);
          switch ($m) {
            case 'save':
                $e = false;
                if ($posql != null 
                 && !$this->mem->saveAsPrivate('posql', $posql)) 
                {
                  $e = $this->setStatus('"Posql ファイルパス"'
                                      . ' の保存に失敗!', true);
                }
                if (!$this->isEnablePosqlFile($posql)) 
                {
                  $e = $this->setStatus('"Posql ファイルパス"'
                                      . ' 無効なファイルです!', true);
                }
                if (!$e) {
                  $this->hideWarnMsg = true;
                }
                if ($mode != null 
                 && !$this->mem->saveAsPrivate('mode', $mode)) 
                {
                  $e = $this->setStatus('"SQL Engine" の保存に失敗!', true);
                }
                if ($help != null) {
                  if (!$this->isReadable($help)) {
                    $e = $this->setStatus('"Help"'
                                      .  ' ファイルが読み込めません', true);
                  }
                  else if (!$this->mem->saveAsPrivate('help', $help))
                  {
                    $e = $this->setStatus('"Help" の保存に失敗!', true);
                  }
                }
                else if ($help == null
                      && $this->mem->countAsPrivate('help')
                      && !$this->mem->deleteAsPrivate('help')) 
                {
                  $e = $this->setStatus('"Help" の削除に失敗!', true);
                }
                if (!$this->mem->saveAsPrivate('dl', $dl)) 
                {
                  $e = $this->setStatus('"Download" の保存に失敗!', true);
                }
                if ($css != null 
                 && !$this->mem->saveAsPrivate('css', $css)) 
                {
                  $e = $this->setStatus('"CSS" の保存に失敗!', true);
                }
                else if ($css == null
                      && $this->mem->countAsPrivate('css')
                      && !$this->mem->deleteAsPrivate('css')) 
                {
                  $e = $this->setStatus('"CSS" の削除に失敗!', true);
                }
                if ($js != null 
                 && !$this->mem->saveAsPrivate('js', $js)) 
                {
                  $e = $this->setStatus('"JavaScript" の保存に失敗!', true);
                }
                else if ($js == null
                      && $this->mem->countAsPrivate('js')
                      && !$this->mem->deleteAsPrivate('js')) 
                {
                  $e = $this->setStatus('"JavaScript" の削除に失敗!', true);
                }
                $new_id = $this->encrypt($id);
                $new_ps = $this->encrypt($ps);
                if ($this->mem->saveAsPrivate('account',
                    array(
                      'id' => $new_id,
                      'ps' => $new_ps
                    )
                  )
                ) 
                {
                  $this->isLogin = $this->generateKey($new_id, $new_ps);
                }
                else {
                  $e = $this->setStatus('アカウント情報の保存に失敗!', true);
                }
                unset($new_id, $new_ps);
                if (!$e) {
                  $this->setStatus('"設定" を保存しました ('
                                        . $this->now() . ')');
                }
                break;
          }
          unset($posql);
          unset($id, $ps, $css, $js, $help, $dl, $mode, $e, $new_id, $new_ps);
          $this->write('head', 'title', 'menu', 'config', 'foot');
          break;
      case 'download':
          $target   = $this->getPost('target');
          $is_file  = !!$this->getPost('is_file');
          $filename = $this->getPost('filename');
          $this->download($target, $is_file, $filename);
          break;
      case 'maintenance':
          $this->write('head', 'title', 'menu', 'maintenance', 'foot');
          break;
      case 'login_history':
          $this->write('head', 'title', 'menu', 'top', 'loginHistory', 'foot');
          break;
      case 'login':
      default:
          if ($this->isLogin || $this->checkAuth()) {
            $this->includePosql();
            $this->write('head', 'title', 'menu', 'top', 'foot');
          } else if ($this->isOverErrorCount()) {
            $this->write('AuthError');
          } else {
            $this->write('head', 'title', 'login', 'foot');
          }
          break;
    }
  }


  function includePosql(){
    static $inited = false, $warned = false;
    $warn_msg = '"posql.php" が不正なファイルです!';
    $conf_msg = '「設定」から "posql.php" のファイルパスを設定してください';
    $class = 'posql_open_failed';
    if (!$inited) {
      $inited = true;
      if (defined('POSQL_PATH') && @is_file(POSQL_PATH)) {
        $this->posqlPath = POSQL_PATH;
      } else {
        $this->posqlPath = $this->mem->loadAsPrivate('posql');
      }
      if (!$this->isEnablePosqlFile($this->posqlPath)) {
        if ($this->isLogin) {
          $warned = true;
          $this->setStatus($warn_msg, $class);
          $this->setStatus($conf_msg, $class);
        }
      } else {
        require_once $this->posqlPath;
        $this->db = & new Posql();
      }
    }
    if (!is_object($this->db)) {
      $this->db = & new Posql_Dummy_Object;
    }
    if ($this->isLogin && !$warned
     && 0 !== strcasecmp(@get_class($this->db), 'Posql')) {
      $warned = true;
      $this->setStatus($warn_msg, $class);
      $this->setStatus($conf_msg, $class);
    }
  }


  function getClass(){
    return 'PosqlAdmin';
  }

  function getClassName(){
    return $this->getClass();
  }

  function getVersion(){
    return '0.19a';
  }

  function getCharset(){
    return 'UTF-8';
  }

  function getScriptName($all = false){
    static $script = null;
    if ($script === null) {
      $script = $this->getEnv('SCRIPT_NAME');
      $script = array(
        0 => basename($script),
        1 => $script
      );
    }
    $key = 0;
    if ($all) {
      $key = 1;
    }
    return $script[$key];
  }


  function getPost($key = null, $default = null){
    static $vars = null;
    if ($vars === null) {
      $vars = (array)$this->filter(array_merge($_GET, $_POST));
    }
    $result = null;
    if ($key === null) {
      $result = $vars;
    } else if (isset($vars[$key]) || array_key_exists($key, $vars)) {
      $result = $vars[$key];
    } else {
      $result = $default;
    }
    return $result;
  }


  function getEnv($key = null){
    $result = null;
    if ($key === null) {
      if (isset($_SERVER)) {
        $result = $_SERVER;
      } else if (isset($_ENV)) {
        $result = $_ENV;
      }
    } else if (is_scalar($key)) {
      if (isset($_SERVER) && isset($_SERVER[$key])) {
        $result = $_SERVER[$key];
      } else if (isset($_ENV) && isset($_ENV[$key])) {
        $result = $_ENV[$key];
      } else if (function_exists('getenv')) {
        $result = @getenv($key);
        if ($result === false) {
          $result = null;
        }
      }
      if ($result === null && function_exists('apache_getenv')) {
        $result = @apache_getenv($key);
        if ($result === false) {
          $result = null;
        }
      }
    }
    return $result;
  }


  function getValue($var, $key, $default = null, $strict = false){
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


  function filter($var){
    if (is_array($var)) {
      $var = array_map(array($this, 'filter'), $var);
    } else if (is_string($var)) {
      $var = str_replace("\0", '', $var);
      if (get_magic_quotes_gpc()) {
        $var = stripslashes($var);
      }
    }
    return $var;
  }


  function split($pattern, $subject, $limit = -1, $flags = 0){
    $flags |= PREG_SPLIT_NO_EMPTY;
    $split = preg_split($pattern, $subject, $limit, $flags);
    return $split;
  }


  function escape($string){
    $string = htmlspecialchars($string, ENT_QUOTES, $this->charset);
    return $string;
  }


  function escapeSQLString($string, $dbtype = null){
    static $disables = array(), $trancate = array(
      "\x00" => '\0',
      "'"    => "''",
      "\x08" => '\b',
      "\x0A" => '\n',
      "\x0D" => '\r',
      "\x09" => '\t',
      "\x1A" => '\Z',
      '\\'   => '\\\\'
    );
    switch (strtolower($dbtype)) {
      case 'none':
          $string = $this->db->escapeString($string);
          break;
      case 'mysql':
          $func = 'mysql_real_escape_string';
          if (!array_key_exists($func, $disables)) {
            $disables[$func] = false;
            if (!function_exists($func) || @($func('dummy')) == null) {
              $disables[$func] = true;
            }
          }
          if (empty($disables[$func])) {
            $string = @mysql_real_escape_string($string);
          } else if (function_exists('mysql_escape_string')) {
            $string = @mysql_escape_string($string);
          } else {
            $string = strtr($string, $trancate);
          }
          break;
      case 'sqlite':
          if (function_exists('sqlite_escape_string')) {
            $string = sqlite_escape_string($string);
          } else {
            $string = str_replace("'", "''", $string);
          }
          break;
      case 'db2':
          $func = 'db2_escape_string';
          if (!array_key_exists($func, $disables)) {
            $disables[$func] = false;
            if (!function_exists($func) || @($func('dummy')) == null) {
              $disables[$func] = true;
            }
          }
          if (empty($disables[$func])) {
            $string = @db2_escape_string($string);
          } else {
            $string = addcslashes($string, "\000\n\r\\'\"\032");
          }
          break;
      case 'oracle':
          $string = str_replace("'", "''", $string);
          $string = addcslashes($string, "\000\n\r\\\032");
          break;
      case 'postgres':
          $func = 'pg_escape_string';
          if (!array_key_exists($func, $disables)) {
            $disables[$func] = false;
            if (version_compare(phpversion(), '5.2.0RC5', '<')
             && !function_exists($func) || @($func('dummy')) == null) {
              $disables[$func] = true;
            }
          }
          if (empty($disables[$func])) {
            $string = @pg_escape_string($string);
          } else {
            $string = strtr($string, $trancate);
          }
          break;
      default:
          $string = strtr($string, $trancate);
          break;
    }
    return $string;
  }


  function toSQLString($value, $type = null, $dbtype = null){
    static $sq = "'";
    switch (true) {
      case is_bool($value):
          $value = (int)$value;
          break;
      case is_string($value):
          $value = $this->escapeSQLString($value, $dbtype);
          break;
      case is_numeric($value):
          $value = $this->db->math->format($value);
          break;
      case is_null($value):
      default:
          $value = 'NULL';
          break;
    }
    if ($value !== 'NULL') {
      if (!is_numeric($value)
       || strlen($value) === 0
       || $this->getColumnAffinity($type) === 'string') {
        $value = $sq . $value . $sq;
      }
    }
    return $value;
  }

/**
 * This logic refers to the column affinity algorithm of SQLite.
 * @see http://www.sqlite.org/datatype3.html#affinity
 */
  function getColumnAffinity($column){
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


  function getDefaultDataType(){
    $result = 'TEXT';
    if (isset($this->db->defaultDataType)) {
      $result = $this->db->defaultDataType;
    }
    return $result;
  }


  function checkAuth(){
    $this->isLogin = false;
    $aid = $aps = $key = -1;
    $auth = $this->getPost('auth', false);
    $id = $this->getPost('login_id', false);
    $ps = $this->getPost('login_ps', false);
    if (($auth !== false)
     ||   ($id !== false 
        && $ps !== false)) 
    {
      $act = (array) $this->mem->loadAsPrivate('account');
      $aid = (string) $this->getValue($act, 'id');
      $aps = (string) $this->getValue($act, 'ps');
      $key = $this->generateKey($aid, $aps);
    }
    if (($id !== false
      && $ps !== false
      && $this->encrypt($id) === $aid
      && $this->encrypt($ps) === $aps)
      || ($auth !== false
       && $auth === $key)) 
    {
      $this->setLastLogin(!$auth);
      if (!$auth) {
        $this->setLoginHistory(true);
      }
      $this->loginErrorCount = null;
      $this->isLogin = $key;
    } else {
      $count = $this->getPost('login_error_count', null);
      if ($count === null) {
        $count = $this->encodeErrorCount(0);
      }
      $count = $this->decodeErrorCount($count);
      if (!$this->isEnableErrorCount($count)) {
        $count = 808;
      } else {
        $count = (int)$count;
      }
      if ($count) {
        $this->setStatus('認証に失敗!', true);
        $this->setLoginHistory(false);
      }
      $this->loginErrorCount = $this->encodeErrorCount($count + 1);
      unset($count);
    }
    unset($key, $auth);
    return (bool)$this->isLogin;
  }


  function generateKey($id, $ps, $key = 'W1Y75'){
    return strrev(md5($id . date($key) . $ps));
  }


  function setLastLogin($save = false){
    if (empty($this->lastLogin)) {
      $now = $this->now();
      $times = $this->mem->loadAsPrivate('lastLogin');
      if (empty($times)) {
        $times = array('now' => $now, 'old' => $now);
      }
      if ($save) {
        $times['old'] = $times['now'];
        $times['now'] = $now;
        $this->mem->saveAsPrivate('lastLogin', $times);
      }
      $this->lastLogin = $times['old'];
    }
  }


  function setLoginHistory($login_result){
    $defaults = array(
      'id'      => null, // id
      'date'    => null, // YYYY-MM-DD HH:MM:SS
      'service' => null, // PosqlAdmin
      'type'    => null, // Login
      'result'  => null, // success/failure
      'ip'      => null, // 127.0.0.1
      'carrier' => null  // Mozilla/5.0 ...
    );
    $histories = $this->mem->loadAsPrivate('login.history');
    if (empty($histories)) {
      $histories = array();
    }
    if ($login_result) {
      $account = $this->mem->loadAsPrivate('account');
    } else {
      $account = array('id' => $this->encrypt($this->getPost('login_id')));
    }
    $id      = isset($account['id']) ? $this->decrypt($account['id']) : null;
    $date    = time();
    $service = null;
    $type    = null;
    $result  = (bool)$login_result;
    $ip      = $this->getEnv('REMOTE_ADDR');
    $carrier = $this->getEnv('HTTP_USER_AGENT');
    $history = array(
      'id'      => $id,
      'date'    => $date,
      'service' => $service,
      'type'    => $type,
      'result'  => $result,
      'ip'      => $ip,
      'carrier' => $carrier
    );
    $prev = end($histories);
    $do_save = false;
    if (empty($prev)) {
      $do_save = true;
    } else {
      $last_date = $this->getValue($prev, 'date');
      if ($last_date < $date) {
        $do_save = true;
      }
    }
    if ($do_save) {
      if (count($histories) >= $this->maxLoginHistory) {
        array_shift($histories);
      }
      $histories[] = $history;
      $this->mem->saveAsPrivate('login.history', $histories);
    }
  }


  function ac5($b){
    $l=strlen($a=dechex($n=strlen($b)));
    for($s=range($i=$j=0,255);$i<256;$i++){
      $j=($j+$s[$i]+ord($a[$i%$l]))%256;$x=$s[$i];$s[$i]=$s[$j];$s[$j]=$x;
    }
    for($y=$i=$j=0,$c='';$y<$n;$y++){
      $i=($i+1)%256;$j=($j+$s[$i])%256;$x=$s[$i];$s[$i]=$s[$j];$s[$j]=$x;
      $c.=$b[$y]^chr($s[($s[$i]+$s[$j])%256]);
    }
    return $c;
  }

  function encrypt($a){
    return base64_encode(call_user_func(array($this, 'ac5'), (string)$a));
  }

  function decrypt($a){
    return call_user_func(array($this, 'ac5'), base64_decode((string)$a));
  }


  function isEnableErrorCount($count){
    $result = false;
    if (is_scalar($count) && strspn($count, '0123') === 1) {
      $result = true;
    }
    return $result;
  }


  function isOverErrorCount(){
    $result = true;
    $count = $this->loginErrorCount;
    if (strlen($count) > 32) {
      $count = (int)$this->decodeErrorCount($count);
      if ($this->isEnableErrorCount($count)) {
        if ($count <= 3 && $count >= 0) {
          $result = false;
        }
      }
    }
    return $result;
  }


  function generateErrorCountKey($left = 'Error', $right = 'Count'){
    $result = strtoupper($this->generateKey($left, $right));
    $result = strtr($result, '349CF', 'POSQL');
    return $result;
  }


  function encodeErrorCount($count){
    $result = null;
    if (!$this->isEnableErrorCount($count)) {
      $count = rand(5, 810);
    }
    $count = (string)((int)$count + (int)date('W'));
    $result = sprintf('%s%s',
      $this->generateErrorCountKey(),
      $this->encrypt((string)$count)
    );
    return $result;
  }


  function decodeErrorCount($count){
    $result = null;
    if (!is_string($count) || $count == null) {
      $count = $this->encodeErrorCount(7);
    }
    $key = $this->generateErrorCountKey();
    if (strpos($count, $key) === 0) {
      $count = substr($count, strlen($key));
      $count = (int)$this->decrypt($count);
      $key = (int)date('W');
      $max = max($count, $key);
      $result = $max - min($count, $key);
    } else {
      $result = 909;
    }
    return $result;
  }


  function getExt($filename, $tolower = false){
    $ext = (string)substr(strrchr(trim(basename($filename)), '.'), 1);
    return $tolower ? strtolower($ext) : $ext;
  }

  function cleanFilePath($path){
    $path = strtr($path, '\\', '/');
    if (strpos($path, '//') !== false) {
      $path = preg_replace('{/+}', '/', $path);
    }
    return $path;
  }

  function now($times = null){
    static $format = 'Y-m-d H:i:s';
    if (is_numeric($times)) {
      $result = @date($format, $times);
      if (!$result) {
        $result = $times;
      }
    } else {
      $result = date($format);
    }
    return $result;
  }


  function addslashes($string){
    if (!is_string($string)) {
      $string = '';
    } else {
      if (@ini_get('magic_quotes_sybase')) {
        $string = strtr($string,
          array(
            '\\' => '\\\\',
            '"'  => '\"',
            "'"  => "\'",
            "\0" => '\0'
          )
        );
      } else {
        $string = addslashes($string);
      }
    }
    return $string;
  }


  function stripslashes($string){
    if (!is_string($string)) {
      $string = '';
    } else {
      if (@ini_get('magic_quotes_sybase')) {
        $string = strtr($string,
          array(
            '\0' => "\0",
            "\'" => "'",
            '\"' => '"',
            '\\' => '\\\\'
          )
        );
      } else {
        $string = stripslashes($string);
      }
    }
    return $string;
  }


 /**
  * Checks whether the class exists without triggering __autoload().
  *
  * @param   string   the class name of target
  * @return  boolean  TRUE success and FALSE on error
  */
  function existsClass($class) {
    $result = false;
    if ($class != null && is_string($class)) {
      if (version_compare(phpversion(), 5, '>=')) {
        $result = class_exists($class, false);
      } else {
        $result = class_exists($class);
      }
    }
    return $result;
  }


 /**
  * JSON encode a string value by escaping characters as necessary.
  * This function usage is string only.
  *
  * @param  string   subject string
  * @param  boolean  whether quote string
  * @return string   encoded string
  * @access public
  */
  function toJSONString($string, $enclose = false) {
    $result = '';
    if (is_scalar($string)) {
      $result = $this->encodeJSON($string);
      if ($result == null || !is_string($result)) {
        $result = '';
      }
    }
    $quote = substr($result, 0, 1);
    if ($quote === '"' && substr($result, -1) === $quote) {
      if (!$enclose) {
        $result = substr($result, 1, -1);
      }
    } else {
      if ($enclose) {
        $result = '"' . $result . '"';
      }
    }
    return $result;
  }


 /**
  * Encodes an arbitrary variable into JSON format.
  * This function usage like json_encode PHP extension.
  *
  * @link  http://php.net/function.json-encode
  *
  * @param  mixed  any number, boolean, string, array, or object to be encoded
  * @return string JSON string representation of input variable
  */
  function encodeJSON($var) {
    static $translates = array(
      '\\' => '\\\\',
      "\n" => '\n',
      "\t" => '\t',
      "\r" => '\r',
      "\b" => '\b',
      "\f" => '\f',
      '"'  => '\"'
    );
    $result = null;
    if (function_exists('json_encode')) {
      if (is_float($var) && is_nan($var)) {
        $result = 'NaN';
      } else {
        $result = @json_encode($var);
      }
    } else {
      @(include_once 'Services/JSON.php');
      $exists = $this->existsClass('Services_JSON');
      if ($exists) {
        $js = new Services_JSON();
        $result = $js->encode($var);
        if (!is_string($result)) {
          $result = null;
        }
        $js = null;
        unset($js);
      } else {
        switch (true) {
          case is_null($var):
          case is_resource($var):
              $result = 'null';
              break;
          case is_bool($var):
              $result = $var ? 'true' : 'false';
              break;
          case is_scalar($var):
              switch (true) {
                case is_float($var):
                    if (is_nan($var)) {
                      $result = 'NaN';
                    } else {
                      $result = floatval(strtr(strval($var), ',', '.'));
                    }
                    break;
                case is_string($var):
                    $result = '"' . strtr($var, $translates) . '"';
                    break;
                default:
                    $result = $var;
                    break;
              }
              break;
          default:
              $is_list = true;
              $count = count($var);
              for ($i = 0, reset($var); $i < $count; $i++, next($var)) {
                if (key($var) !== $i) {
                  $is_list = false;
                  break;
                }
              }
              $items = array();
              if ($is_list) {
                foreach ($var as $val) {
                  $items[] = $this->encodeJSON($val);
                }
                $result = '[' . implode(',', $items) . ']';
              } else {
                foreach ($var as $key => $val) {
                  $items[] = $this->encodeJSON($key) 
                           . ':' 
                           . $this->encodeJSON($val);
                }
                $result = '{' . implode(',', $items) . '}';
              }
              break;
        }
      }
    }
    $result = (string)$result;
    return $result;
  }


 /**
  * Decodes a JSON string into appropriate variable.
  * This function usage like json_decode PHP extension.
  *
  * @link  http://php.net/function.json-decode
  *
  * @param  string  JSON-formatted string
  * @return mixed   any object types corresponding to given JSON input string
  */
  function decodeJSON($json) {
    $result = null;
    $json = (string)$json;
    if (function_exists('json_decode')) {
      $result = @json_decode($json, true);
    } else {
      @(include_once 'Services/JSON.php');
      $exists = $this->existsClass('Services_JSON');
      if ($exists) {
        $js = new Services_JSON();
        $result = $js->decode($json);
        if (is_object($result)) {
          $class = strtolower(get_class($result));
          if (strpos($class, 'error') !== false
           || strpos($class, 'exception') !== false) {
            $result = null;
          }
        }
        $js = null;
        unset($js);
      } else {
        $code = '';
        $quote = false;
        $length = strlen($json);
        for ($i = 0; $i < $length; $i++) {
          if ($quote) {
            $code .= $json[$i];
            if ($json[$i] === '"') {
              $quote = !$quote;
            }
          } else {
            switch ($json[$i]) {
              case '(':
              case ')':
                  $code = 'null';
                  break 2;
              case '{':
              case '[':
                  $code .= ' array(';
                  break;
              case '}':
              case ']':
                  $code .= ')';
                  break;
              case ':':
                  $code .= '=>';
                  break;
              case '"':
                  $quote = true;
              default:
                  $code .= $json[$i];
                  break;
            }
          }
        }
        $result = @eval('return ' . $code . ';');
      }
    }
    return $result;
  }


  function setStatus($status, $error = false){
    $this->status[] = $status;
    $this->errors[] = $error;
    return $error;
  }


  function setConfigAsBigInt($varname, $value,
                             $type = 'number',
                             $overwrite = false){
    $ini_value = trim(@ini_get($varname));
    if ($ini_value == null) {
      $ini_value = 0;
    } else if (!is_numeric($ini_value)) {
      $ini_value = $this->toByte($ini_value);
    }
    $ini_value = sprintf('%.0f', $ini_value);
    switch (strtolower($type)) {
      case 'string':
          $value = (string)$value;
          break;
      case 'number':
          if ($value == null) {
            $value = 0;
          }
          if (!is_numeric($value)) {
            $value = $this->toByte($value);
          }
          if (!is_string($value)) {
            $value = sprintf('%.0f', $value);
          }
          break;
      case 'boolean':
          $value = (bool)$value;
          break;
      case 'null':
      default:
          $value = null;
          break;
    }
    if ($overwrite || $ini_value < $value) {
      @ini_set($varname, $value);
    }
  }


  function plainValue($var, $coltype = null){
    $result = null;
    $value = '(UNKNOWN)';
    $class = strtolower(gettype($var));
    switch (true) {
      case is_int($var):
          $class = 'int';
          $value = intval($var);
          $lctype = strtolower($coltype);
          if (strpos($lctype, 'time') !== false
           || strpos($lctype, 'date') !== false) {
            $value .= sprintf(' <br><em>(%s)</em>', $this->now($var));
          }
          unset($lctype);
          break;
      case is_bool($var):
          $class = 'bool';
          $value = $var ? '(TRUE)' : '(FALSE)';
          break;
      case is_string($var):
          $class = 'string';
          $length = sprintf('%.0f', strlen($var));
          $value  = sprintf('%s <em>(length=%s)</em>',
                            $this->escape($var), $length);
          if ($var != null && is_numeric($var)
           && $var > 1234567890 && $var < 0x7FFFFFFF) {
            $var = intval($var);
            $lctype = strtolower($coltype);
            if (strpos($lctype, 'time') !== false
             || strpos($lctype, 'date') !== false) {
              $value .= sprintf(' <br><em>(%s)</em>', $this->now($var));
            }
          }
          unset($length);
          break;
      case is_null($var):
          $class = 'null';
          $value = '(NULL)';
          break;
      case is_float($var):
          $class = 'float';
          $value  = trim(sprintf('%.0f', $var));
          $value .= sprintf(' <em>(%s)</em>', floatval($var));
          break;
      case is_array($var):
      case is_object($var):
          $value = $this->escape((string)var_export($var, true));
          break;
      default:
          $class = 'unknown';
          $value = '(UNKNOWN)';
          break;
    }
    $result = sprintf('<var class="%s">%s</var>', $class, $value);
    return $result;
  }


  function formatByte($size){
    $format = '0 Byte';
    if ($size) {
      $suf = array('Bytes', 'KB', 'MB', 'GB');
      $cnt = count($suf);
      for ($i = 0, $j = $cnt - 1; $size >= 1024 && $i < $j; $i++) {
        $size /= 1024;
      }
      $format = round($size, $i ? 2 : 0) . ' ' . $suf[$i];
    }
    return $format;
  }

  function toByte($value){
    $bytes = floatval($value);
    if (!is_numeric($value)) {
      switch (strtoupper(substr($value, -1))) {
        case 'P': $bytes *= 1024;
        case 'T': $bytes *= 1024;
        case 'G': $bytes *= 1024;
        case 'M': $bytes *= 1024;
        case 'K': $bytes *= 1024;
      }
    }
    return $bytes;
  }


  function toArrayResult($stmt){
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
          settype($stmt, 'array');
          $result = $stmt;
        }
      }
    }
    return $result;
  }


  function optimizeDatabase(){
    static $mask = 
    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
    $result = false;
    $this->optimizeLines = array();
    if ($this->db->lockExAll()) {
      $this->db->getMeta();
      for ($i = 0; $i < 3; $i++) {
        $this->db->vacuum();
        if ($this->db->isError()) {
          break;
        }
      }
      if (!$this->db->isError()) {
        if ($rp = $this->db->fopen('r')) {
          $this->db->fseekLine($rp, 3);
          if ($wp = $this->db->fopen('r+')) {
            $this->db->fseekLine($wp, 3);
            $i = 1;
            while (!feof($rp)) {
              $delete = false;
              $repair = false;
              $line = $this->db->fgets($rp);
              $trim = trim($line);
              if ($trim != null) {
                $delims = array(
                  '#' => 0,
                  '|' => 1,
                  ':' => 2
                );
                if (method_exists($this->db, 'getTableDelimiters')) {
                  $delims = $this->db->getTableDelimiters();
                }
                $using_delim = null;
                foreach ($delims as $delim => $index) {
                  if (strpos($line, $delim) !== false) {
                    $using_delim = $delim;
                    break;
                  }
                }
                if ($using_delim === null
                 || substr_count($line, $using_delim) !== 1) {
                  $delete = true;
                } else {
                  list($key, $val) = explode($using_delim, $line);
                  $table_name = @($this->db->decodeKey($key));
                  if (!$this->db->isEnableName($table_name)) {
                    $delete = true;
                  } else {
                    $data = trim($val);
                    if ($data == null) {
                      $delete = true;
                    } else if (strspn($data, $mask) !== strlen($data)) {
                      $delete = true;
                    } else {
                      $decode = @($this->db->decode($data));
                      if ($decode == null) {
                        $delete = true;
                      } else {
                        if (substr($val, -1) !== $this->db->NL) {
                          $repair = true;
                        } else if ($this->db->NL === chr(0x0A)) {
                          if (strpos(substr($val, -2), chr(0x0D)) !== false) {
                            $repair = true;
                          }
                        }
                      }
                    }
                  }
                }
              }
              if ($delete) {
                $line_len = strlen($line);
                $tail_len = strlen($this->db->NL);
                if ($line_len <= $tail_len) {
                  $puts = $this->db->NL;
                } else {
                  $puts = sprintf('%s%s',
                    str_repeat(' ', $line_len - $tail_len),
                    $this->db->NL
                  );
                }
                if ($this->db->fputs($wp, $puts)) {
                  $this->optimizeLines[$i] = $this->OPTIMIZE_DELETE;
                }
              } else if ($repair) {
                $space_len = strlen($line) - strlen(rtrim($line));
                $space = '';
                if ($space_len > 1) {
                  $space = str_repeat(' ', $space_len - 1);
                }
                $puts = rtrim($line) . $space . $this->db->NL;
                if ($this->db->fputs($wp, $puts)) {
                  $this->optimizeLines[$i] = $this->OPTIMIZE_REPAIR;
                }
              } else {
                $this->db->fgets($wp);
                $this->optimizeLines[$i] = $this->OPTIMIZE_SKIP;
              }
              $i++;
            }
            fclose($wp);
            if (!$this->db->isError()) {
              $result = true;
            }
          }
          fclose($rp);
        }
        if ($result) {
          for ($i = 0; $i < 3; $i++) {
            $this->db->vacuum();
            if ($this->db->isError()) {
              $result = false;
              break;
            }
          }
        }
      }
      $this->db->unlockAll();
    }
    return $result;
  }


  /**
   * Check whether the file is readable.
   * This function validates 'allow_url_fopen' flag.
   *
   * @param  string   target filename
   * @return boolean  whether the file is readable
   */
  function isReadable($file){
    $result = false;
    if (is_scalar($file)) {
      $fp = @fopen($file, 'r', true);
      if ($fp) {
        fclose($fp);
        $result = true;
      }
    }
    return $result;
  }


  function isEnablePosqlFile($posql_path){
    /*
    // Not using (posql.php: md5 => array(version, size))
    $hashes = array(
      '7ba286d538b86af31d902d05f6f4c297' => array('2.00',  '69156'),
      '9bc69cd1db7f7ffe1d970a7a1c814cc8' => array('2.01', '115553'),
      '537dcda83150ce6880f00333fb4caeb4' => array('2.02', '119938'),
      'e4999897e6b8095b5239af1c9f839898' => array('2.03', '146951'),
      'f38a331b8fa7ff239b21201a3d0ab709' => array('2.04', '166193'),
      '82483c01417d60d83e9372bf0381aaec' => array('2.05', '176292'),
      'a7817e8268a7cb62fb00cdb06e0852d1' => array('2.06', '358431'),
      '57af589754c0ef55dc26f17251873805' => array('2.07', '434864'),
      'd583f10027eb8cd3cead9d8ac52b41a4' => array('2.08', '442183'),
      'c5b5dea4816ac801ebccfcfb11c8b14d' => array('2.09', '487937'),
      '65057bf82650bde7799ca2a5f4955741' => array('2.10', '514117'),
      'e15c21b7fcd733aa93829588c31fb64c' => array('2.11', '532552'),
      '619317bbea95dc7c591378dc4fbc4546' => array('2.12', '541183'),
      'a3d8e0421a26d7e77ff1db1a3d3bd3d5' => array('2.13', '543162'),
      'de61a738da273fbbdd198ade0f9b745f' => array('2.14', '551303'),
      '2fa981e9ef123c1b9da22135b21e6efd' => array('2.15', '611970'),
      'c8ea8e8da87cb356ed9141e91df49250' => array('2.16', '632419'),
      '32de3945300d5cc0ae08a4f3010d4921' => array('2.17', '685672')
    );
    */
    $result = false;
    if ($posql_path != null && is_string($posql_path)) {
      $fp = @fopen($posql_path, 'rb');
      if ($fp) {
        $buffer = '';
        while (!feof($fp)) {
          $buffer .= fread($fp, 0x2000);
        }
        fclose($fp);
        $points = array(
          '<?php',
          '@name',
          '@package',
          'class',
          'Posql',
          'function',
          'query'
        );
        $result = true;
        foreach ($points as $point) {
          if (strpos($buffer, $point) === false) {
            $result = false;
            break;
          }
        }
        $buffer = null;
        unset($buffer);
      }
    }
    return $result;
  }


  function isSimpleSelectSQL($sql){
    $result = false;
    if (method_exists($this->db, 'getPath')
     && $this->db->getPath() != null) {
      if ($this->db->getLastMethod() === 'select') {
        $tokens = $this->db->splitSyntax($sql);
        $tokens = $this->db->parseSelectQuery($tokens);
        if (!$this->db->isError() && is_array($tokens)) {
          $select = $this->getValue($tokens, 'select');
          $is_expr = true;
          if (!empty($select)) {
            if (count($select) === 1 && reset($select) === '*') {
              $is_expr = false;
            } else {
              $is_expr = false;
              foreach ($select as $token) {
                if ($token === ',') {
                  continue;
                }
                if (strtolower($token) === 'null'
                 || $this->db->isExprToken($token)) {
                  $is_expr = true;
                  break;
                }
              }
            }
          }
          if (!$is_expr) {
            $from = $this->getValue($tokens, 'from');
            if ($from != null
             && !$this->db->isMultipleSelect($from)
             && !$this->db->isSubSelectFrom($from)
             && !$this->db->hasCompoundOperator($sql)) {
              if (count($from) === 1) {
                $table = $this->getCurrentTablename();
                if ($table != null && reset($from) === $table) {
                  $group = $this->getValue($tokens, 'group');
                  if ($group == null) {
                    $result = true;
                  }
                }
              }
            }
          }
        }
      }
    }
    return $result;
  }


  function getCurrentTablename(){
    $result = false;
    if (method_exists($this->db, 'getTableName')) {
      $result = $this->db->getTableName();
    }
    if ($result == null) {
      if (method_exists($this->db, 'getPath')
       && $this->db->getPath() != null) {
        if (!empty($this->db->tableName)) {
          $table = @($this->db->decodeKey($this->db->tableName));
          $result = (string)$table;
        }
      }
    }
    return $result;
  }


  function getMicrotime(){
    return array_sum(explode(' ', microtime()));
  }


  function _trimCallback($string){
    static $inline = array(
      'style'    => false,
      'script'   => false,
      'textarea' => false,
      'pre'      => false
    );
    $lcs = strtolower($string);
    foreach (array_keys($inline) as $element) {
      $tag = sprintf('<%s', $element);
      if (strpos($lcs, $tag) !== false) {
        $inline[$element] = true;
      }
    }
    $trim = trim($string);
    if ($trim == null || substr($trim, 0, 1) === '<') {
      $string = $this->_trimCallbackInline($trim);
    } else {
      $is_inline = array_filter($inline);
      if ($is_inline) {
        $string = rtrim($string);
      } else {
        $string = ' ' . $this->_trimCallbackInline($trim);
      }
    }
    foreach (array_keys($inline) as $element) {
      $tag = sprintf('</%s', $element);
      if (strpos($lcs, $tag) !== false) {
        $inline[$element] = false;
      }
    }
    return $string;
  }

  function _trimCallbackInline($string){
    $trim = trim($string);
    if ($trim != null) {
      $pre = null;
      $suf = null;
      if (strpos($trim, '> ') !== false) {
        $pre = '(?<=[\w\s\'"]>)';
      }
      if (strpos($trim, ' <') !== false) {
        $suf = '(?=</?\w)';
      }
      if ($pre || $suf) {
        $pattern = sprintf('|%s(\s+)%s|', $pre, $suf);
        $trim = preg_replace($pattern, ' ', $trim);
      }
      $string = $trim;
    }
    return $string;
  }

  function obTrim($buffer){
    $buffer = $this->split('{[\x0A\x0D]+}', $buffer);
    $buffer = array_map(array($this, '_trimCallback'), $buffer);
    $buffer = implode(PHP_EOL, $buffer);
    return $buffer;
  }

  function write(){
    $args = func_get_args();
    ob_implicit_flush(1);
    foreach ($args as $func) {
      $func = 'display' . $func;
      ob_start();
      call_user_func(array(&$this, $func));
      $contents = ob_get_contents();
      ob_end_clean();
      echo $this->obTrim($contents);
      flush();
    }
  }


  function download($target, $is_file = false, $filename = null){
    $data = '';
    if (headers_sent()) {
      ?>
      <h1>Unknown Error</h1>
      <p>
        Already headers sent.<br>
        The download of file cannot begin.
      </p>
      <?php
      exit;
    }
    if ($is_file) {
      $fp = @fopen($target, 'rb');
      if (!$fp) {
        $this->sendContentType();
        ?>
        <div>
          <h1>Error</h1>
          <p>
            <strong>対象のファイルが読み込めません!</strong>
          </p>
          <p>
            <small>ファイル: <?php echo $this->escape($target); ?></small>
          </p>
        </div>
        <?php
        exit;
      }
      while (!feof($fp)) {
        $data .= fread($fp, 0x2000);
      }
      fclose($fp);
      if ($filename == null) {
        $filename = $target;
      }
    } else {
      $data = $target;
      if (is_string($is_file) && $filename == null) {
        $filename = $is_file;
      }
    }
    if ($filename == null) {
      $filename = sprintf('%08x_%s.posql',
        crc32(uniqid(rand(), true)),
        date('Ymd')
      );
    }
    $filename = urlencode($filename);
    ob_implicit_flush(1);
    header('Cache-Control: public');
    header('Expires: 0');
    header('Pragma: public');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-Description: File Transfer');
    header('Content-Length: ' . strlen($data));
    echo $data;
    exit(0);
  }


  function sendContentType(){
    if (!headers_sent()) {
      header('Content-Type: text/html; charset=' . $this->charset);
    }
  }

  function displayHead(){
    $this->sendContentType();
    $css = $this->mem->loadAsPrivate('css');
    ?>
    <html>
    <head>
    <meta http-equiv="content-type" content="text/html; charset=<?php
     echo $this->charset; ?>">
    <meta http-equiv="Pragma" content="no-cache">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo $this->title; ?></title><?php 
     echo $css;
    ?></head>
    <body id="doc">
    <?php
    $this->displayInitJS();
  }

  function displayInitJS(){
    ?>
    <script type="text/javascript">
    <!--
    /*
     * Posql utilities class on JavaScript, version 1.13
     */
    function Posql(e, s, a){
      var d = document, t = typeof e;
      return s ? Posql.tag(e, s, a) :
         t == "string" ? d.getElementById(e) ||
                 (e = d.getElementsByName(e)) && e[0] :
         t == "undefined" ? d : e;
    };
    Posql.extend = function(o, c){
      if (!c) {
        c = o;
        o = this;
      }
      if (o && c)
        for (var p in c) 
          o[p] = c[p];
      return o;
    };
    (function(){
      Posql.extend({
        each: function(o, f, a){
          var i, l = o.length;
          if (!!o == !l)
            for (i in o)
              f.apply(o[i], a || [i, o[i]]);
          else
            for (i = 0; l > i; i++)
              if (f.apply(o[i], a || [i, o[i]]) === false)
                break;
          return o;
        },
        addEvent: function(e, t, f, c){
          if (e.addEventListener)
            e.addEventListener(t, f, c);
          else if (e.attachEvent)
            e.attachEvent("on" + t, f);
          else
            e["on" + t] = f;
        },
        removeEvent: function(e, t, f, c){
          if (e.removeEventListener)
            e.removeEventListener(t, f, c);
          else if (e.detachEvent)
            e.detachEvent("on" + t, f);
          else
            e["on" + t] = null;
        },
        toggle: function(o){
          var e = Posql(o), n = "none";
          with (e.style)
            display = display == n ? "" : n;
        },
        show: function(o){
          var e = Posql(o);
          e.style.display = "";
        },
        hide: function(o){
          var e = Posql(o);
          e.style.display = "none";
        },
        getElementsByClass: function(c, n, t){
          c = c ? "(?:^|\\s)" + c + "(?:\\s|$)" : "(?:)";
          n = n || Posql();
          t = t || "*";
          var i, j, r = [], e = n.getElementsByTagName(t), p = new RegExp(c);
          for (i = j = 0; i < e.length; i++)
            if (p.test(e[i].className))
              r[j++] = e[i];
          return r;
        },
        trim: function(s){
          return (s || "").replace(/^\s+|\s+$/g, "");
        },
        nl: function(s, n){
          return (s || "").replace(/\x0D\x0A|\x0D|\x0A/g, n || "\n");
        },
        stripTags: function(s, t){
          var re = {
            simple: /<\/?[^>]+>/gi,
            assoc: /<(\w+)[^>]*>[\s\S]*?<\/\1>|<\w+[^>]*>/gi
          };
          return (s || "").replace(t ? re.assoc : re.simple, "");
        },
        escapeHTML: function(s){
          var d = Posql(), v = d.createElement("div");
          v.appendChild(d.createTextNode(s));
          return v.innerHTML;
        },
        unescapeHTML: function(s){
          var v = Posql().createElement("div");
          v.innerHTML = Posql.stripTags(s);
          return v.childNodes[0] ? v.childNodes[0].nodeValue : "";
        },
        escapeString: function(s){
          return (typeof s == "undefined" ? "" : s + "").
            replace(/([\\\"\'])/g, "\\$1").replace(/\u0000/g, "\\0");
        },
        isNumeric: function(n){
          return n === "" ? false : !isNaN(n - 0);
        },
        val: function(e, s){
          var a = arguments, l = a.length, v, r = "";
          if (e) {
            v = typeof e.value == "undefined" ? "innerHTML" : "value";
            if (l == 2)
              e[v] = Posql.nl(s);
            r = Posql.nl(e[v]);
          }
          return r;
        },
        attr: function(e, a){
          var r = [], s = " ";
          if (!a) {
            a = e;
            e = {};
          }
          Posql.each(a, function(k, v){
            try {
              if (k == "className" || k == "class")
                e.className = v;
              else
                e.setAttribute(k, v);
            } catch (x) {
              e[k] = v;
            }
            r.push(k + '="' + v + '"');
          });
          return s + r.join(s);
        },
        css: function(e, s){
          var result;
          var setOpacity = function(a, b){
            a.style.filter = "alpha(opacity=" + (b * 100) + ")";
            a.style.MozOpacity = b;
            a.style.opacity = b;
          };
          if (typeof s == "object") {
            Posql.each(s || {}, function(k, v){
              if (/(?:opacity|alpha)$/i.test(k)) {
                setOpacity(e, v);
              } else {
                try {
                  e.style[ Posql.camelize(k) ] = v;
                } catch (er) {}
              }
            });
            result = e;
          } else if (typeof e == "string") {
            result = e.style[ Posql.camelize(e) ];
          }
          return result;
        },
        camelize: function(s){
          return (s || "").replace(/\-(\w)/g, function(m0, m1){
            return m1.toUpperCase();
          });
        },
        underscore: function(s){
          return (s || "").replace(/([A-Z])/g, "-$1").toLowerCase();
        },
        tag: function(a, b, c){
          var p, s = "", t = "<", d = ">";
          if (typeof c == "string") {
            p = c;
            c = b;
            b = p;
          }
          a = a || s;
          c = c ? Posql.attr(c) : s;
          b = b || s;
          return t + a + c + d + b + t + "/" + a + d;
        },
        getCenterPos: function(){
          var d = Posql(), de = d.documentElement, db = d.body, mx, my, x, y;
          if (Posql.browser.msie) {
            mx = de.clientWidth || db.clientWidth || db.scrollWidth;
            my = de.clientHeight|| db.clientHeight|| db.scrollHeight;
          } else {
            mx = innerWidth;
            my = innerHeight;
          }
          mx = mx || 800;
          my = my || 600;
          x = parseInt(mx / 2 * 0.86);
          y = parseInt(my / 2 * 0.82);
          return {
            mx: mx,
            my: my,
            x: x, 
            y: y
          };
        }
      });
      (function(){
        var u = navigator.userAgent.toLowerCase();
        Posql.browser = {
          safari: /webkit/.test(u),
          opera: /opera/.test(u),
          msie: /msie/.test(u) && !/opera/.test(u),
          mozilla: /mozilla/.test(u) && !/(compatible|webkit)/.test(u)
        };
      })();
    })();
    // Show the loading message
    (function($){
      $.loading = true;
      var d = $(), pos = $.getCenterPos();
      var x = pos.x, y = pos.y, mx = pos.mx, my = pos.my;
      var panel = d.createElement("div");
      with (panel.style) {
        position = "absolute";
        left = 0;
        top  = 0;
        width  = mx + "px";
        height = my + "px";
        zIndex = 998;
        backgroundColor = "#fff";
      }
      var text = d.createElement("div");
      with (text.style) {
        position = "absolute";
        left = x;
        top  = y;
        zIndex = 999;
        backgroundColor = "#fff";
        color = "#666";
        fontSize = "20px";
      }
      var msg = "Loading..";
      var dot = d.createElement("strong");
      dot.appendChild(d.createTextNode("."));
      var interval = 500;
      var blink = function(){
        if ($.loading) {
          $.toggle(dot);
          setTimeout(blink, interval);
        }
      };
      panel.id = "loading";
      text.innerHTML = $("strong", msg);
      text.appendChild(dot);
      panel.appendChild(text);
      $("doc").appendChild(panel);
      blink();
      $.addEvent(window, "load", function(){
        $("doc").removeChild( $("loading") );
        $.loading = false;
      });
    })(Posql);
    // Only PosqlAdmin use functions below
    (function($){
      $.extend({
        filterSpecialCols: function(c, a){
          var r = [];
          $.each(c, function(i, v){
            if (v != "rowid" && v != "ctime" && v != "utime") {
              if (a && typeof a[i] != "undefined")
                r.push(a[i]);
              else
                r.push(v);
            }
          });
          return r;
        },
        decodePlainValue: function(v, t){
          var r = "";
          t = v.className || t;
          v = $.trim( $.stripTags(v.innerHTML || v || "", true) );
          switch (t) {
            case "int":
            case "float":
                r = $.isNumeric(v) ? v : "0";
                break;
            case "string":
                r = "'" + $.escapeString(v) + "'";
                break;
            case "bool":
                r = /true/i.test(v) ? "TRUE" : "FALSE";
                break;
            case "null":
            case "unknown":
            default:
                r = "NULL";
                break;
          }
          return r;
        }
      });
    })(Posql);
    // Ajax simple class
    (function($){
      $.ajax = function(){
        return window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") :
                                      new XMLHttpRequest();
      };
      $.extend($.ajax, {
        $: function(e){
          return typeof e == "string" ? $(e) : e;
        },
        _: function(a, f){
          for (var o, r = [], i = 0; i < a.length; i++)
            if (o = f(a[i]))
              r.push(o)
          return r;
        },
        serialize: function(f){
          var c = encodeURIComponent,
              g = function(n){
                return f.getElementsByTagName(n)
              },
              h = function(e){
                return e.name && (c(e.name) + "=" + c(e.value)) || "";
              },
              i = $.ajax._(g("input"), function(i){
                return ((i.type != "radio" && i.type != "checkbox") ||
                        i.checked) ? h(i) : "";
              }),
              s = $.ajax._(g("select"), h),
              t = $.ajax._(g("textarea"), h);
          return i.concat(s).concat(t).join("&");
        },
        send: function(u, f, m, a){
          var x = $.ajax();
          x.open(m, u, true);
          x.onreadystatechange = function(){
            if (x.readyState == 4)
              f(x.responseText);
          };
          if (m == "POST")
            x.setRequestHeader(
              "Content-Type", 
              "application/x-www-form-urlencoded"
            );
          x.send(a);
        },
        get: function(u, f){
          $.ajax.send(u, f, "GET");
        },
        gets: function(u, xml){
          var x = $.ajax();
          x.open("GET", u, false);
          x.send("");
          return xml ? x.responseXML : x.responseText;
        },
        post: function(u, f, a){
          $.ajax.send(u, f, "POST", a);
        },
        update: function(u, e){
          var a = $.ajax.$(e), f = function(r){ a.innerHTML = r };
          $.ajax.get(u, f);
        },
        submit: function(u, e, m){
          var a = $.ajax.$(e), f = function(r){ a.innerHTML = r };
          $.ajax.post(u, f, $.ajax.serialize(m));
        }
      });
    })(Posql);
    //-->
    </script>
    <?php
  }

  function displayTitle(){
    ?>
    <h1><?php echo $this->title; ?></h1>
    <?php
    if (!empty($this->status)):
      $n = count($this->status);
      for($i = 0; $i < $n; $i++):
        if(isset($this->errors[$i])):
          if($this->errors[$i]):
            $class = 'error';
            if(is_string($this->errors[$i])):
              $class .= ' ' . $this->errors[$i];
            endif;
          else:
            $class = 'success';
          endif;
        else:
          $class = 'status';
        endif;
        ?>
        <p class="<?php echo $class; ?>">
          <strong><?php echo $this->escape($this->status[$i]); ?></strong>
        </p>
        <?php
      endfor;
    endif;
  }

  function displayMenu(){
    $menu = array(
      'top'    => 'トップ',
      'sql'    => 'SQL',
      'manage' => '管理',
      'import' => 'インポート',
      'export' => 'エクスポート',
      'config' => '設定',
      'help'   => 'ヘルプ',
      'logout' => 'ログアウト'
    );
    $strong = 'top';
    foreach (array('a', 'm') as $action) {
      $am = strtolower($this->getPost($action));
      foreach ($menu as $key => $val) {
        if ($am != null && strpos($am, $key) !== false) {
          $strong = $key;
          break 2;
        }
      }
    }
    if (isset($menu[$strong])) {
      $menu[$strong] = sprintf('<strong>%s</strong>', $menu[$strong]);
    }
    ?>
    <div id="menu">
      <form action="<?php echo $this->script ?>" method="post">
        <input type="hidden" name="auth" value="<?php echo $this->isLogin; ?>">
        <fieldset>
          <legend>メニュー</legend>
          <div><?php
          foreach($menu as $m => $s):
            printf('<a href="%s?a=menu'
                       .  '&amp;m=%s'
                       .  '&amp;auth=%s">%s</a>' . PHP_EOL,
              $this->script, $m, $this->isLogin, $s);
          endforeach;
          ?></div>
        </fieldset>
      </form>
    </div>
    <?php
  }


  function displayTop(){
    $u = $this->getPost('login_id');
    if (!$u) {
      $a = $this->mem->loadAsPrivate('account');
      $u = isset($a['id']) ? $this->decrypt($a['id']) : false;
    }
    ?>
    <div id="top">
      <fieldset>
        <legend>トップ</legend>
        <div>
          <span>ようこそ！ <?php echo $this->className; ?> 管理ページへ</span>
          <br>
          <span>id: <?php
              echo $u ? $this->escape($u) 
                      : '<span class="error">(id が未設定です)</span>';
          ?></span><br>
          <p>
            <small>
              <span>最終ログイン: <?php echo $this->lastLogin; ?></span>
              <br>
              <span><?php echo $this->className; ?> Version: <?php
                          echo $this->getVersion(); ?></span>
              <br>
              <span><?php echo $this->db->getClass(); ?> Version: <?php
                          echo $this->db->getVersion(); ?></span>
              <br>
              <span>PHP Version: <?php echo phpversion(); ?></span>
            </small>
          </p>
          <p>
            <small>
              <?php
              $update_text = '最新にアップデート';
              $update_title = '設定を引き継いだまま、&#x0A;'
                       .  '新しいバージョンのPosqlAdminにアップデートします';
              printf('<a href="%s?a=update_posqladmin'
                          . '&amp;auth=%s"'
                          . ' title="%s">'
                          . '%s'
                  .  '</a>',
                $this->script,
                $this->isLogin,
                $update_title,
                $update_text
              );
              ?>
            </small>
          </p>
          <p>
            <small>
              <?php
              $login_history = 'ログイン履歴';
              printf('<a href="%s?a=login_history'
                          . '&amp;auth=%s"'
                          . ' title="%s">'
                          . '%s'
                  .  '</a>',
                $this->script,
                $this->isLogin,
                $login_history,
                $login_history
              );
              ?>
            </small>
          </p>
        </div>
      </fieldset>
    </div>
    <?php
  }


  function displayLoginHistory(){
    $histories = $this->mem->loadAsPrivate('login.history');
    ?>
    <div id="login_history">
      <fieldset>
        <legend>ログイン履歴</legend>
        <div>
          <p>
            <small>
              最大 <?php echo $this->escape($this->maxLoginHistory); ?>
              件まで保存されます。
            </small>
          </p>
          <?php
          if(empty($histories)):
            ?>
            <p>履歴がありません</p>
            <?php
          else:
            ?>
            <table class="result">
              <tr>
                <?php
                $captions = array(
                  0 => 'no',
                  1 => 'id', 
                  2 => 'date', 
                  3 => 'service', 
                  4 => 'type', 
                  5 => 'result',
                  6 => 'ip',
                  7 => 'carrier'
                );
                foreach($captions as $caption):
                  ?>
                  <td class="cols"><?php echo $this->escape($caption); ?></td>
                  <?php
                endforeach;
                ?>
              </tr>
              <?php
              $no = count($histories);
              while($history = array_pop($histories)):
                echo '<tr>';
                $item = array('no' => $no--);
                $history = array_merge($item, $history);
                foreach($history as $caption => $val):
                  $escaped = false;
                  switch($caption):
                    case 'id':
                        if ($val == null) {
                          $val = '<span class="error">'
                               . '(id が未設定です)'
                               . '</span>';
                          $escaped = true;
                        }
                        break;
                    case 'date':
                        $val = $this->now($val);
                        break;
                    case 'service':
                        $val = $this->getClass();
                        break;
                    case 'type':
                        $val = 'ログイン';
                        break;
                    case 'result':
                        $val = $val ? '成功' : '失敗';
                        break;
                    case 'ip':
                    case 'carrier':
                    default:
                        break;
                  endswitch;
                  if(!$escaped):
                    $val = $this->escape($val);
                  endif;
                  printf('<td class="row">%s</td>', $val);
                endforeach;
                echo '</tr>', PHP_EOL;
              endwhile;
              ?>
            </table>
            <?php
          endif;
          ?>
        </div>
      </fieldset>
    </div>
    <?php
  }


  function displayUpdatePosqlAdmin(){
    $maxsize = $this->toByte('32K');
    $keys = array('post_max_size', 'upload_max_filesize', 'memory_limit');
    foreach ($keys as $key) {
      if ($size = @ini_get($key)) {
        $maxsize = max($this->toByte($size), $maxsize);
      }
    }
    ?>
    <div id="import">
      <form action="<?php
        echo $this->script;
        ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="auth" value="<?php
          echo $this->isLogin ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php
          echo $maxsize ?>">
        <input type="hidden" name="a" value="update_posqladmin">
        <input type="hidden" name="m" value="upload_script">
        <fieldset>
          <legend>最新にアップデート</legend>
          <div>
            <p>
              <small>
                設定を引き継いだまま、
                <br>
                新しいバージョンの PosqlAdmin にアップデートします。
              </small>
            </p>
            <p>
              <small>
                アップデートする <strong>posqladmin.php</strong>
                を指定してください。
              </small>
            </p>
            <p>
              <input type="file" name="update_posqladmin_upfile" size="50">
            </p>
            <p>
              <input type="submit" value="OK" title="最新にアップデート">
            </p>
          </div>
        </fieldset>
      </form>
    </div>
    <?php
  }


  function displayUpdatePosqlAdminResult(){
    $error = '';
    $buffer = '';
    $var = 'update_posqladmin_upfile';
    if (empty($_FILES[$var]['name'])
     || empty($_FILES[$var]['tmp_name'])) {
      $error = 'アップロード失敗';
    } else {
      $name     = $_FILES[$var]['name'];
      $tmp_name = $_FILES[$var]['tmp_name'];
      if (!is_uploaded_file($tmp_name)) {
        $error = 'アップロード失敗';
      } else {
        $fp = @fopen($tmp_name, 'rb');
        if (!$fp) {
          $error = 'アップロードしたファイルが開けません';
        } else {
          $buffer = '';
          while (!feof($fp)) {
            $buffer .= fread($fp, 0x2000);
          }
          fclose($fp);
          if ($buffer == null) {
            $error = 'ファイルが空です';
          } else {
            $points = array(
              '<?php',
              '@name',
              $this->getClassName(),
              '$this->db',
              '$this->mem',
              'displayUpdatePosqlAdminResult'
            );
            foreach ($points as $point) {
              if (strpos($buffer, $point) === false) {
                $error = '不正なファイルです';
                break;
              }
            }
          }
        }
        @unlink($tmp_name);
        if ($error == null) {
          $old_data = $this->mem->loadAsPrivate();
          if (!empty($old_data) && is_array($old_data)) {
            $fp = @fopen($this->db->fullPath($this->getScriptName()), 'r+b');
            if (!$fp) {
              $error = 'ファイルが開けません';
            } else {
              fwrite($fp, $buffer, strlen($buffer));
              fclose($fp);
              $this->mem->B = trim($this->mem->B);
              $this->mem->E = trim($this->mem->E);
              $this->mem->init();
              if ($this->mem->o == 0) {
                $error = '不正なファイルのようです';
              } else {
                foreach ($old_data as $key => $val) {
                  $this->mem->saveAsPrivate($key, $val);
                }
              }
            }
          }
          unset($old_data);
        }
        unset($buffer);
      }
    }
    if ($error):
      ?>
      <div>
        <p class="error">
          <big><strong>Error</strong></big>
          <br>
          <?php echo $this->escape($error); ?>
        </p>
      </div>
      <?php
    else:
      ?>
      <div id="update_posqladmin_result">
        <form action="<?php echo $this->script ?>" method="post">
          <input type="hidden" name="auth" value="<?php
            echo $this->isLogin ?>">
          <fieldset>
            <legend>最新にアップデート結果</legend>
            <div>
              <p class="success">
                <strong>正常に処理が完了しました。</strong>
              </p>
              <p>
                <small>
                  PosqlAdmin の設定を引き継ぎ アップデートできました。
                </small>
              </p>
              <p>
                <input type="submit" value="OK">
              </p>
            </div>
          </fieldset>
        </form>
      </div>
      <?php
    endif;
  }


  function displayImport(){
    $target_file = $this->getPost('select_db_path');
    if ($target_file == null) {
      return;
    }
    $maxsize = $this->toByte('32K');
    $keys = array('post_max_size', 'upload_max_filesize', 'memory_limit');
    foreach ($keys as $key) {
      if ($size = @ini_get($key)) {
        $maxsize = max($this->toByte($size), $maxsize);
      }
    }
    if (!@is_file($target_file)):
      ?>
      <div>
        <p class="error">
          データベース <?php echo $this->escape($target_file); ?>
          が存在しません。
        </p>
      </div>
      <?php
    else:
      ?>
      <div id="import">
        <form action="<?php
          echo $this->script;
          ?>" method="post" enctype="multipart/form-data">
          <input type="hidden" name="auth" value="<?php
            echo $this->isLogin ?>">
          <input type="hidden" name="MAX_FILE_SIZE" value="<?php
            echo $maxsize ?>">
          <input type="hidden" name="select_db_path" value="<?php
            echo $this->escape($target_file) ?>">
          <input type="hidden" name="a" value="import">
          <input type="hidden" name="m" value="execute">
          <fieldset>
            <legend>ローカルファイルからインポート</legend>
            <div>
              <p>
                <small>
                  ローカルの SQL ファイルから
                  ダンプデータをインポートします。
                </small>
              </p>
              <p>
                <small>アップロードサイズ上限: <?php
                  echo $this->formatByte($maxsize);
                ?></small>
              </p>
              <p>
                <small>
                  対象のデータベース: <?php
                    echo $this->escape($target_file);
                  ?>
                </small>
              </p>
              <p>
                <input type="file" name="import_upfile" size="50">
              </p>
              <p>
                <input type="submit" value="UPLOAD" title="アップロード">
              </p>
            </div>
          </fieldset>
        </form>
      </div>
      <?php
    endif;
  }


  function displayImportResult(){
    $target_file = $this->getPost('select_db_path');
    $query = '';
    $error = '';
    $var = 'import_upfile';
    if (empty($_FILES[$var]['name'])
     || empty($_FILES[$var]['tmp_name'])) {
      $error = 'アップロード失敗';
    } else {
      $name     = $_FILES[$var]['name'];
      $tmp_name = $_FILES[$var]['tmp_name'];
      if (!is_uploaded_file($tmp_name)) {
        $error = 'アップロード失敗';
      } else {
        $fp = @fopen($tmp_name, 'rb');
        if (!$fp) {
          $error = 'アップロードしたファイルが開けません';
        } else {
          $query = '';
          while (!feof($fp)) {
            $query .= fread($fp, 0x2000);
          }
          fclose($fp);
        }
        @unlink($tmp_name);
      }
    }
    if ($query == null) {
      $error = 'データが空のようです';
    }
    if ($error):
      ?>
      <div>
        <p class="error">
          <big><strong>Error</strong></big>
          <br>
          <?php echo $this->escape($error); ?>
        </p>
      </div>
      <?php
    else:
      ?>
      <div id="import_result">
        <form action="<?php
            echo $this->script ?>" method="post">
          <input type="hidden" name="from_import" value="1">
          <input type="hidden" name="auth" value="<?php
            echo $this->isLogin ?>">
          <input type="hidden" name="sql_select_db" value="<?php
            echo $this->escape($target_file); ?>">
          <input type="hidden" name="select_db_path" value="<?php
            echo $this->escape($target_file); ?>">
          <input type="hidden" name="a" value="sql">
          <input type="hidden" name="m" value="execute">
          <fieldset>
            <legend>SQL を実行</legend>
            <div>
              <p>
                <small>
                  データが正常に受け取れました。
                  <br>
                  実行するにはボタンを押してください。
                </small>
              </p>
              <p>
                <small>
                  対象のデータベース: <?php
                    echo $this->escape($target_file);
                  ?>
                </small>
              </p>
              <div>
                <div>
                  <small>実行する SQL</small>
                </div>
                <textarea name="sql_query" rows="10" cols="80"><?php
                  echo $this->escape($query);
                ?></textarea>
              </div>
              <div>
                <input type="submit" value="OK" title="実行">
                <span id="import_cancel_area" style="margin-left:2px"></span>
              </div>
            </div>
          </fieldset>
        </form>
      </div>
      <script type="text/javascript">
      <!--
      (function($){
        var url = "<?php
          printf('%s?a=import&auth=%s',
            $this->script,
            $this->isLogin
          );
        ?>";
        var button = $().createElement("button");
        $.attr(button, {
          type: "button",
          title: "キャンセル"
        });
        $.val(button, "CANCEL");
        button.innerHTML = "CANCEL";
        $.addEvent(button, "click", function(){
          location.href = url;
        });
        $("import_cancel_area").appendChild(button);
      })(Posql);
      //-->
      </script>
      <?php
    endif;
  }


  function displayExport(){
    $error = null;
    $include_specials = $this->getPost('export_include_specials');
    $use_sql          = $this->getPost('export_use_sql');
    $dbtype           = $this->getPost('export_dbtype');
    $default_type     = $this->getPost('export_default_type');
    $db               = $this->getPost('select_db_path');
    if ($db == null) {
      return;
    }
    foreach (array('include_specials', 'use_sql') as $var) {
      ${$var} = empty(${$var}) ? '' : ' checked="checked"';
    }
    if ($default_type == null) {
      $default_type = $this->getDefaultDataType();
    }
    $tmp = $this->db->path;
    $this->db->setPath($db);
    if (!$this->db->isDatabase()) {
      $error = $this->db->lastError();
      $error.= sprintf('<div class="error">%s</div>',
                        'データベースが変なようです。<br>'
                      . '再度、登録しなおしてください。');
    }
    $this->db->setPath($tmp);
    if (!$this->db->open($db)) {
      $error .= '<p class="error">データベースが開けません!</p>';
    } else {
      $meta = $this->db->getMeta();
      if (!$meta) {
        $error .= '<p class="error">テーブル情報が取得できません</p>';
      }
    }
    if($error):
      printf('<div class="error">%s</div>', $error);
    else:
      ?>
      <div id="export">
        <form action="<?php echo $this->script; ?>#export_result" method="post">
          <input type="hidden" name="auth" value="<?php echo $this->isLogin;?>">
          <input type="hidden" name="a" value="export">
          <input type="hidden" name="m" value="execute">
          <input type="hidden" name="select_db_path" value="<?php
            echo $this->escape($db);
          ?>">
          <fieldset>
            <legend>データベースのダンプをエクスポート</legend>
            <div>
              <p>
                <small>
                  <span>対象のデータベース:</span>
                  <span><?php
                    echo $this->escape(basename($db));
                    ?><small> (<?php
                    echo $this->formatByte(@filesize($db));
                    ?>)</small>
                  </span>
                </small>
              </p>
              <p>
                <small>
                  <label>
                    <input type="checkbox" name="export_include_specials"<?php
                      echo $include_specials;
                    ?>>
                    特殊カラム (rowid, ctime, utime) を含める
                  </label>
                </small>
              </p>
              <p>
                <small>
                  <label>
                    <input type="checkbox" name="export_use_sql"<?php
                      echo $use_sql;
                    ?>>
                    &quot;CREATE TABLE&quot; で定義したSQLを使う
                  </label>
                </small>
              </p>
              <p>
                <small>
                  <span>文字列のエスケープ:</span>
                  <select name="export_dbtype">
                    <?php
                    foreach (array(
                        'none'     => 'NONE',
                        'mysql'    => 'MySQL',
                        'sqlite'   => 'SQLite',
                        'db2'      => 'DB2',
                        'oracle'   => 'Oracle',
                        'postgres' => 'Postgres'
                      ) as $op_key => $op_val) {
                      $selected = '';
                      if (strcasecmp($dbtype, $op_key) === 0) {
                        $selected = ' selected="selected"';
                      }
                      printf('<option value="%s"%s>%s</option>%s',
                        $op_key, $selected, $op_val, PHP_EOL
                      );
                    }
                    ?>
                  </select>
                </small>
              </p>
              <p>
                <small>
                  <span>データ型の補完:</span>
                  <input type="text" name="export_default_type" value="<?php
                    echo $this->escape($default_type);
                  ?>">
                </small>
              </p>
              <div>
                <input type="submit" value="OK" title="OKですか?">
              </div>
            </div>
          </fieldset>
        </form>
      </div>
      <script type="text/javascript">
      <!--
      (function($){
        var spe = $("export_include_specials"), sql = $("export_use_sql");
        if (spe && sql && sql.nodeType == 1) {
          var f = function(){
            spe.disabled = !!sql.checked;
          };
          $.addEvent(sql, "click", f);
          f();
        }
      })(Posql);
      -->
      </script>
      <?php
    endif;
  }


  function displayExportResult(){
    $error = null;
    $include_specials = $this->getPost('export_include_specials');
    $use_sql          = $this->getPost('export_use_sql');
    $dbtype           = $this->getPost('export_dbtype');
    $default_type     = $this->getPost('export_default_type');
    $db               = $this->getPost('select_db_path');
    if (!isset($this->config_dl)) {
      $this->config_dl = $this->mem->loadAsPrivate('dl');
    }
    $tmp = $this->db->path;
    $this->db->setPath($db);
    if (!$this->db->isDatabase()) {
      $error = $this->db->lastError();
      $error.= sprintf('<div class="error">%s</div>',
                        'データベースが変なようです。<br>'
                      . '再度、登録しなおしてください。');
    }
    $this->db->setPath($tmp);
    if (!$this->db->open($db)) {
      $error .= '<p class="error">データベースが開けません!</p>';
    } else {
      $meta = $this->db->getMeta();
      if (!$meta) {
        $error .= '<p class="error">テーブル情報が取得できません</p>';
      }
    }
    $hr = '--------------------------------------------------------';
    $stmt = $this->db->describe();
    $rows = $this->toArrayResult($stmt);
    $dump = array();
    $header = array(
      sprintf('%s Version %s - SQL Dump',$this->getClass(),$this->getVersion()),
      sprintf('Export date: %s', $this->now()),
      sprintf('%s Version: %s', $this->db->getClass(), $this->db->getVersion()),
      sprintf('%s Project: %s', $this->db->getClass(), $this->projectURL),
      sprintf($hr),
      sprintf('Database: %s', $this->db->getDatabaseName()),
      sprintf($hr)
    );
    foreach ($header as $head) {
      $dump[] = '-- ' . $head;
    }
    $commented_default = false;
    $row_count = count($rows);
    for ($i = 1; $i < $row_count; $i++) {
      if (!empty($use_sql)
       && isset($rows[$i]['sql']) && $rows[$i]['sql'] != null) {
        $sql = rtrim($rows[$i]['sql'], ';') . ';';
      } else {
        $stmt = $this->db->describe( $rows[$i]['name'] );
        $cols = $this->toArrayResult($stmt);
        $fields = array();
        foreach ($cols as $col) {
          if (!$include_specials
           && ($col['name'] === 'rowid'
           ||  $col['name'] === 'ctime' || $col['name'] === 'utime')) {
            continue;
          }
          $datatype = $default_type;
          if ($col['type'] !== $default_type
           && $col['type'] !== $this->getDefaultDataType()) {
            $datatype = $col['type'];
          }
          $field_definition = array(
            '', '', $col['name'], $datatype
          );
          if (strcasecmp($dbtype, 'mysql') === 0
           && (strcasecmp($datatype, 'text') === 0
           ||  strcasecmp($datatype, 'blob') === 0)) {
            if (!$commented_default) {
              $dump[] = '-- DEFAULT does not apply to the BLOB or TEXT types.';
              $dump[] = '-- Therefore, the part comments.';
              $dump[] = '-- ' . $hr;
              $commented_default = true;
            }
            $field_definition[] = '--';
          }
          $field_definition[] = 'DEFAULT';
          $field_definition[] = $this->toSQLString(
            $col['default'], $datatype, $dbtype
          );
          if ($commented_default) {
            $field_definition[] = PHP_EOL;
          }
          $field = array();
          $field[] = implode(' ', $field_definition);
          if (strpos(strtolower($col['extra']), 'alias') !== false) {
            $field[] = 'PRIMARY KEY';
          }
          $fields[] = implode(' ', $field);
        }
        $sql = implode(PHP_EOL,
          array(
            'CREATE TABLE ' . $rows[$i]['name'] . ' (',
            implode(',' . PHP_EOL, $fields),
            ');'
          )
        );
      }
      $stmt = $this->db->describe($rows[$i]['name']);
      $infos = $this->toArrayResult($stmt);
      $types = array();
      if (!empty($infos) && is_array($infos)) {
        foreach ($infos as $info) {
          $types[ $info['name'] ] = $info['type'];
        }
      }
      if ($this->db->isError()) {
        break;
      }
      $dump[] = $sql;
      $stmt = $this->db->queryf('SELECT * FROM %s', $rows[$i]['name']);
      $tables = $this->toArrayResult($stmt);
      if ($this->db->isError()) {
        break;
      }
      $table_count = count($tables);
      while (--$table_count >= 0) {
        $table_row = array_shift($tables);
        $values = array();
        foreach ($table_row as $table_col => $table_val) {
          if (!$include_specials
           && ($table_col === 'rowid'
           ||  $table_col === 'ctime' || $table_col === 'utime')) {
            continue;
          }
          $table_type = null;
          if (array_key_exists($table_col, $types)) {
            $table_type = $types[$table_col];
          }
          $values[] = $this->toSQLString($table_val, $table_type, $dbtype);
        }
        $dump[] = sprintf('INSERT INTO %s VALUES(%s);%s',
          $rows[$i]['name'],
          implode(', ', $values),
          PHP_EOL
        );
      }
      $dump[] = sprintf('-- ' . $hr);
    }
    $errors = array();
    if ($this->db->isError()) {
      foreach ($this->db->getErrors() as $errmsg) {
        $errors[] = sprintf('<p class="error">%s</p>', $this->escape($errmsg));
      }
    }
    if (!empty($errors)):
      printf('<div>%s</div>', implode(PHP_EOL, $errors));
      unset($dump, $errors);
    else:
      $result = implode(PHP_EOL, $dump) . PHP_EOL;
      unset($dump);
      ?>
      <div id="export_result">
        <form action="<?php
            echo $this->script;
          ?>" <?php
            echo empty($this->config_dl) ? '' : 'target="_blank" ';
          ?>method="post">
          <input type="hidden" name="auth" value="<?php
            echo $this->isLogin;
          ?>">
          <input type="hidden" name="a" value="download">
          <input type="hidden" name="is_file" value="">
          <input type="hidden" name="filename" value="<?php
            echo $this->escape($this->db->getDatabaseName() . '.sql');
          ?>">
          <fieldset>
            <legend>データベースのダンプ結果</legend>
            <div>
              <textarea name="target" class="dump-text"><?php
                echo $this->escape($result);
              ?></textarea>
            </div>
            <div>
              <input type="submit" value="Download" 
                     title="ダンプ結果のSQLテキストをダウンロード">
            </div>
          </fieldset>
        </form>
      </div>
      <script type="text/javascript">
      <!--
      (function($){
        $.addEvent(window, "load", function(){
          try {
            location.hash = "#export_result";
          } catch (e) {}
        });
      })(Posql);
      //-->
      </script>
      <?php
    endif;
  }


  function displayManageList(){
    $list = $this->mem->loadAsPrivate('manage.list');
    if (empty($list)) {
      $list = array();
    }
    if (!isset($this->config_dl)) {
      $this->config_dl = $this->mem->loadAsPrivate('dl');
    }
    ?>
    <div id="managelist">
      <fieldset>
        <legend>データベース管理下一覧</legend>
          <div><?php
           if(empty($list)):?>
            <p>登録されていません。</p><?php
           else:?>
            <small>
            <ul><?php
            foreach($list as $db):
              if(!@file_exists($db)):
                continue;
              endif;
              printf('<li>%s <small>(%s) %s</small>'
                        .  ' <span>%s</span>'
                  .  '</li>' . PHP_EOL,
                $this->escape($db),
                $this->formatByte(@filesize($db)),
                sprintf('<a href="%s?a=download'
                             . '&amp;auth=%s'
                             . '&amp;target=%s'
                             . '&amp;is_file=%d'
                             . '&amp;filename=%s" '
                         . 'title="ダウンロード"%s>Download</a>',
                  $this->script,
                  $this->isLogin,
                  urlencode($db),
                  1,
                  urlencode(basename($db)),
                  empty($this->config_dl) ? '' : ' target="_blank"'
                ),
                sprintf('<a href="%s?a=maintenance'
                             . '&amp;auth=%s'
                             . '&amp;target=%s" '
                         . 'title="メンテナンス">メンテナンス</a>',
                  $this->script,
                  $this->isLogin,
                  urlencode($db)
                )
              );
            endforeach;
           endif;
          ?></ul>
          </small>
         </div>
      </fieldset>
    </div>
    <?php
  }

  function displayManageSearch(){
    if (0 === strcasecmp(@get_class($this->db), 'Posql_Dummy_Object')) {
      printf('<p>設定を先に済ませてください。</p>');
      return;
    }
    $dir = $this->getPost('managesearch_dir');
    if ($dir == null) {
      $dir = getcwd();
    }
    $dir = rtrim($this->cleanFilePath($dir), '/*');
    $result = null;
    clearstatcache();
    if (@is_file($dir)) {
      $result  = $dir;
      $curpath = dirname($dir);
    } else {
      $curpath = $dir;
    }
    $pattern = rtrim(dirname($curpath . '/*'), '/') . '/*';
    $glob = array(
      'dir'  => array('..' => $dir),
      'file' => array()
    );
    foreach ((array)@glob($pattern) as $file) {
      $file = $this->cleanFilePath($file);
      $base = basename($file);
      if ($file == null || $file === '..' || $base === $this->script) {
        continue;
      }
      if (@is_dir($file)) {
        $glob['dir'][] = $file;
      } else {
        $glob['file'][] = $file;
      }
    }
    ?>
    <div id="managesearch">
      <form action="<?php echo $this->script; ?>" method="post">
        <input type="hidden" name="auth" value="<?php echo $this->isLogin;?>">
        <input type="hidden" name="a" value="managesearch">
        <fieldset>
          <legend>データベース管理</legend>
          <div><?php
            if($result):
              ?><p>
                 <small>選択したファイル: <strong><?php
                      echo $this->escape($result);
                    ?></strong>
                    <br>
                    <div class="note"><?php
                      printf('<a href="%s?a=managesearch'
                                 .  '&amp;m=result'
                                 .  '&amp;auth=%s'
                                 .  '&amp;managesearch_result=%s">',
                        $this->script,
                        $this->isLogin,
                        urlencode($result)
                      );?><big>このファイルを管理リストに追加する</big>
                        </a>
                    </div>
                 </small>
              </p><?php
            endif;
            ?><p>
              <small>※ファイル形式は <strong><?php
                printf('%s (Version %s)',
                  $this->db->getClass(),
                  $this->db->getVersion()
                );
              ?></strong> のデータ形式のみ扱えます。
              </small>
            </p>
            <div>
              <fieldset>
                <legend><small>いまここ</small></legend>
                <div>
                  <p>
                    <strong><?php echo $this->escape($curpath); ?></strong>
                  </p>
                  <div>
                    <small>
                      <a href="<?php
                        printf('%s?a=create_db'
                          .  '&amp;auth=%s'
                          .  '&amp;create_db_dir=%s',
                          $this->script,
                          $this->isLogin,
                          urlencode($curpath)
                        );
                      ?>">このディレクトリにデータベースを作成する</a>
                    </small>
                  </div>
                </div>
              </fieldset>
            </div>
            <ul><?php
            foreach ($glob['dir'] as $key => $dir):
              $dirname = $this->escape($dir);
              if($key === '..'):
                $dir = dirname($dir);
                $dirname = '..';
              endif;
              $perms = substr(sprintf('%o', @fileperms($dir)), -3);
              if (3 !== strlen($perms)) {
                $perms = '';
              } else {
                $perms = sprintf('<small>[%s]</small>', $perms);
              }
              printf('<li><a href="%s?a=managesearch'
                             .  '&amp;m=search'
                             .  '&amp;auth=%s'
                             .  '&amp;managesearch_dir=%s">'
                         .  '<small>'
                           .  '<span class="tt">[+]</span>'
                           .  ' %s '
                           .  ' <span>%s</span> '
                         .  '</small>'
                       .  '</a>'
                  .  '</li>' . PHP_EOL,
                $this->script,
                $this->isLogin,
                urlencode($dir),
                $perms,
                $this->escape(basename($dirname))
              );
            endforeach;
            foreach ($glob['file'] as $file):
              $perms = substr(sprintf('%o', @fileperms($file)), -3);
              if (3 !== strlen($perms)) {
                $perms = '';
              } else {
                $perms = sprintf('<small>[%s]</small>', $perms);
              }
              printf('<li><a href="%s?a=managesearch'
                             .  '&amp;m=search'
                             .  '&amp;auth=%s'
                             .  '&amp;managesearch_dir=%s">'
                         .  '<small>'
                           .  '<span class="tt">[-]</span>'
                           .  ' %s '
                           .  ' <span>%s</span> '
                           .  '<small>(%s)</small>'
                         .  '</small>'
                       .  '</a>'
                  .  '</li>' . PHP_EOL,
                $this->script,
                $this->isLogin,
                urlencode($file),
                $perms,
                $this->escape(basename($file)),
                $this->formatByte(@filesize($file))
              );
            endforeach;
            ?></ul>
          </div>
        </fieldset>
      </form>
    </div>
    <?php
  }


  function displaySelectDB(){
    $path = $this->getPost('select_db_path');
    $dbs = $this->mem->loadAsPrivate('manage.list');
    if (empty($dbs)) {
      $dbs = array();
    }
    $selected = null;
    $options = array();
    $is_deleted = false;
    foreach ($dbs as $i => $db) {
      if ($db == null || !@file_exists($db)) {
        unset($dbs[$i]);
        $is_deleted = true;
        continue;
      }
      $options[$i]['selected'] = null;
      if ($db === $path) {
        $options[$i]['selected'] = ' selected="selected"';
      }
      $options[$i]['value'] = $this->escape($db);
    }
    if ($is_deleted) {
      $this->mem->saveAsPrivate('manage.list', $dbs);
    }
    ?>
    <div id="select_db">
      <form action="<?php echo $this->script ?>" method="post">
        <input type="hidden" name="auth" value="<?php echo $this->isLogin;?>">
        <input type="hidden" name="a" value="<?php
          printf('select_db_%s', $this->selectDBAction);
        ?>">
        <fieldset>
          <legend>操作するデータベース選択</legend>
          <div><?php
            if(empty($dbs)):?>
              <p>データベースが登録されていません。</p><?php
            else:?>
              <select name="select_db_path"><?php
              foreach ($options as $op):
                printf('<option value="%s"%s>%s</option>'.PHP_EOL,
                  $op['value'],
                  $op['selected'],
                  $op['value']
                );
              endforeach;
              ?></select>
              <input type="submit" name="m" value="OK" title="選択"><?php
            endif;
          ?></div>
        </fieldset>
      </form>
    </div>
    <?php
  }


  function displayCreateDB(){
    $dir  = $this->getPost('create_db_dir');
    $file = $this->getPost('create_db_filename');
    $errors = array();
    if ($dir == null) {
      $errors[] = '選択したパスが不正(empty)です';
    } else if (!@is_dir($dir)) {
      $errors[] = sprintf('選択したパス(%s)はディレクトリではありません',
                          $this->escape($dir));
    } else {
      if (!(@fileperms($dir) & 2)) {
        $errors[] = sprintf('選択したパス(%s)に書き込み属性'
                         .  '(パーミッション)がありません',
                            $this->escape($dir));
      }
      if (!(@fileperms($dir) & 4)) {
        $errors[] = sprintf('選択したパス(%s)に読み込み属性'
                         .  '(パーミッション)がありません',
                            $this->escape($dir));
      }
    }
    if ($file == null) {
      $file = '';
    }
    $ext = $this->db->getExt();
    $dot_ext = '.' . ltrim($ext, '.');
    ?>
    <div id="create_db">
      <form action="<?php echo $this->script ?>" method="post">
        <input type="hidden" name="auth" value="<?php echo $this->isLogin;?>">
        <input type="hidden" name="a" value="create_db_execute">
        <input type="hidden" name="create_db_dir" value="<?php
          echo $this->escape($dir);
        ?>">
        <fieldset>
          <legend>データベースを作成</legend>
          <div><?php
            if(!empty($errors)):
              foreach($errors as $error):
                printf('<p class="error">%s</p>%s', $error, PHP_EOL);
              endforeach;
            else:
              if($ext != null):?>
                <p>
                  <small>
                    ※ファイル名の後に拡張子「<strong><?php
                      echo $this->escape($ext); ?></strong>」が付加されます。
                  </small>
                </p>
                <?php
              endif;
              ?>
              <p>
                <small>作成するディレクトリ:</small>
                <br>
                <strong><?php echo $this->escape($dir); ?>/</strong>
              </p>
              <p>
                <small>作成するデータベース(ファイル)名:</small>
                <br>
                <span>
                  <input type="text" name="create_db_filename" value="<?php
                    echo $this->escape($file);
                  ?>" size="20">
                  <strong><?php echo $this->escape($dot_ext); ?></strong>
                </span>
              </p>
              <p>
                <input type="submit" value="OK" title="実行">
              </p><?php
            endif;?>
          </div>
          <div><?php
            printf(implode('',
                array(
                  '<p>',
                    '<a href="%s?auth=%s',
                           '&amp;a=managesearch',
                           '&amp;m=search',
                           '&amp;managesearch_dir=%s">',
                      '<small>戻る</small>',
                    '</a>',
                  '</p>'
                )
              ),
              $this->script,
              $this->isLogin,
              urlencode($dir)
            );
          ?>
          </div>
        </fieldset>
      </form>
    </div>
    <?php
  }


  function displayCreateDBResult(){
    $dir  = $this->getPost('create_db_dir');
    $file = $this->getPost('create_db_filename');
    $path = sprintf('%s/%s.%s',
      rtrim($dir, '/'),
      ltrim(trim($file), '/'),
      ltrim($this->db->getExt(), '.')
    );
    $path = $this->cleanFilePath($path);
    $errors = array();
    if ($path == null || $file == null) {
      $errors[] = 'ファイル名が不正(empty)です';
    } else if (@file_exists($path)) {
      $errors[] = sprintf('ファイル(%s)はすでに存在しています',
                          $this->escape($path));
    } else {
      $this->db->open($path);
      if ($this->db->isError()) {
        foreach ($this->db->getErrors() as $error) {
          $errors[] = $this->escape($error);
        }
      } else {
        $result = $this->db->isDatabase($path);
        if ($this->db->isError()) {
          foreach ($this->db->getErrors() as $error) {
            $errors[] = $this->escape($error);
          }
        }
      }
    }
    $success = '';
    $error = '';
    if (!empty($errors)) {
      $error_msgs = array();
      foreach ($errors as $err) {
        $error_msgs[] = sprintf('<p class="error">%s</p>', $err);
      }
      $error = implode(PHP_EOL, $error_msgs);
    } else {
      $dbs = $this->mem->loadAsPrivate('manage.list');
      if (empty($dbs) || !is_array($dbs)) {
        $dbs = array();
      }
      $dbs[] = $path;
      $this->mem->saveAsPrivate('manage.list', $dbs);
      $success = sprintf('<p class="success">'
                      .  'データベース「%s」を作成しました'
                      .  '</p>', $this->escape($path));
    }
    $back_link = null;
    if ($success) {
      $back_link = sprintf(implode('',
          array(
            '<p>',
              '<a href="%s?auth=%s',
                     '&amp;a=managesearch',
                     '&amp;m=search',
                     '&amp;managesearch_dir=%s">',
                '<small>戻る</small>',
              '</a>',
            '</p>'
          )
        ),
        $this->script,
        $this->isLogin,
        urlencode($dir)
      );
    } else {
      $back_link = sprintf(implode('',
          array(
            '<p>',
              '<a href="%s?auth=%s',
                     '&amp;a=create_db',
                     '&amp;create_db_filename=%s',
                     '&amp;create_db_dir=%s">',
                '<small>戻る</small>',
              '</a>',
            '</p>'
          )
        ),
        $this->script,
        $this->isLogin,
        urlencode($file),
        urlencode($dir)
      );
    }
    
    ?>
    <div id="create_db_result">
      <fieldset>
        <legend>データベース作成の結果</legend>
        <div><?php
          if($error):
            echo $error;
          else:
            echo $success;
          endif;
          echo $back_link;?>
        </div>
      </fieldset>
    </div>
    <?php
  }


  function displaySQL(){
    $error = null;
    $sql = $this->getPost('sql_query');
    $db  = $this->getPost('select_db_path');
    if ($db == null) {
      return;
    }
    $mode = (string) $this->mem->loadAsPrivate('mode');
    if (!$mode) {
      $mode = 'SQL';
    }
    $tmp = $this->db->getPath();
    $this->db->setPath($db);
    if (!$this->db->isDatabase()) {
      $error = $this->db->lastError();
      $error.= sprintf('<div class="error">%s</div>',
                        'データベースが変なようです。<br>'
                      . '再度、登録しなおしてください。');
    }
    $this->db->setPath($tmp);
    if (!$this->db->open($db)) {
      $error .= '<p class="error">データベースが開けません!</p>';
    } else {
      $meta = $this->db->getMeta();
      if (!$meta) {
        $error .= '<p class="error">テーブル情報が取得できません</p>';
      } else {
        $meta = array_filter($meta);
        $this->db->setEngine($mode);
        if ($this->db->isError()) {
          foreach ($this->db->getErrors() as $errmsg) {
            $error .= sprintf('<p class="error">%s</p>',
                              $this->escape($errmsg));
          }
        }
        $mode = $this->db->getEngine();
      }
    }
    $sql_histories = $this->mem->loadAsPrivate('sql_history');
    if (!is_array($sql_histories)) {
      $sql_histories = array();
    }
    if($error):
      printf('<div class="error">%s</div>', $error);
    else:
     ?><div id="tableinfo">
        <fieldset>
          <legend>データベース情報</legend>
          <div><?
          foreach($meta as $table_key => $cols):
            $table_name = $this->db->decodeKey($table_key);
            ?><div class="tableinfo-panel">
                 <fieldset>
                   <legend class="tableinfo-list"><?php
                     echo $this->escape($table_name);
                   ?></legend><div><ol start="0"><?php
                   foreach($cols as $col_name => $default):
                     printf('<li><span class="columns">%s</span>'
                            .  ' <small>%s</small>'
                          . '</li>' . PHP_EOL,
                       $this->escape($col_name),
                       $this->plainValue($default, $col_name)
                     );
                   endforeach;
                   ?></ol></div>
                 </fieldset>
            </div><?php
          endforeach;
          ?></div>
        </fieldset>
     </div>
     <div id="sql">
      <form action="<?php echo $this->script; ?>#sql_result" method="post">
        <input type="hidden" name="auth" value="<?php echo $this->isLogin;?>">
        <input type="hidden" name="sql_select_db" value="<?php
          echo $this->escape($db); ?>">
        <input type="hidden" name="select_db_path" value="<?php
          echo $this->escape($db); ?>">
        <input type="hidden" name="a" value="sql">
        <input type="hidden" name="m" value="execute">
        <fieldset>
          <legend>SQL</legend>
          <div>
            <div>
              <div>
               <small>現在の評価モード:</small>
               <small>
                 <strong><?php echo $this->escape($mode); ?></strong>
               </small>
              </div>
            </div>
            <div>
              <textarea name="sql_query" id="sql_query" rows="7" cols="80"><?php
               echo $this->escape( $this->getPost('from_import') ? '' : $sql);
               ?></textarea>
            </div>
            <div>
              <input type="submit" value="OK" title="実行">
              <span id="sql_history_panel"></span>
            </div>
          </div>
        </fieldset>
      </form>
     </div>
     <script type="text/javascript">
     <!--
     (function($){
       var currentIndex = null;
       var currentSQL = null;
       var histories = <?php echo $this->encodeJSON($sql_histories);?>;
       var panel = $("sql_history_panel");
       if (panel && histories && histories.length) {
         $.css(panel, {
           marginLeft: "80px"
         });
         var space1 = $().createElement("span");
         $.val(space1, "&nbsp;");
         var space2 = space1.cloneNode(true);
         var label = $().createElement("small");
         $.val(label, "履歴:");
         var prev = $().createElement("button");
         $.attr(prev, {
           type: "button"
         });
         $.css(prev, {
           fontSize: "xx-small"
         });
         var next = prev.cloneNode(true);
         prev.innerHTML = "&lt; PREV";
         next.innerHTML = "NEXT &gt;";
         $.attr(prev, {
           title: "前のSQL履歴を表示"
         });
         $.attr(next, {
           title: "次のSQL履歴を表示"
         });
         $.addEvent(prev, "click", function(ev) {
           var editor = $("sql_query");
           if (editor) {
             if (currentIndex === null) {
               currentSQL = $.val(editor);
               currentIndex = histories.length;
             }
             currentIndex = Math.max(0, currentIndex - 1);
             $.val(editor, histories[currentIndex]);
             if (currentIndex <= 0) {
               prev.disabled = true;
             } else {
               prev.disabled = false;
             }
             next.disabled = false;
           }
           try {
             if (ev.preventDefault) {
               ev.preventDefault();
             } else {
               ev.returnValue = false;
             }
           } catch (e) {}
         });
         $.addEvent(next, "click", function(ev) {
           var editor = $("sql_query");
           if (editor) {
             if (currentIndex === null) {
               currentSQL = $.val(editor);
               currentIndex = histories.length;
             }
             currentIndex = Math.max(
               0,
               Math.min(histories.length, currentIndex + 1)
             );
             if (histories.length <= currentIndex) {
               $.val(editor, currentSQL);
               currentIndex = histories.length;
               next.disabled = true;
             } else {
               $.val(editor, histories[currentIndex]);
               next.disabled = false;
             }
             if (0 < currentIndex) {
               prev.disabled = false;
             }
           }
           try {
             if (ev.preventDefault) {
               ev.preventDefault();
             } else {
               ev.returnValue = false;
             }
           } catch (e) {}
         });
         $.each([label, space1, prev, space2, next], function(i, node){
           panel.appendChild(node);
         });
       }
     })(Posql);
     (function(){
       Posql.extend({
         joinNL: function(){
           var args = [].slice.call(arguments, 0);
           return args.join("\n") + "\n";
         }
       });
     })();
     (function($){
      var t = $.getElementsByClass("tableinfo-list") || [];
      for (var i = 0; i < t.length; i++) {
        var e = t[i];
        while (e = e.nextSibling) {
          if (e.tagName == "DIV") {
            (function(){
              var d = e, self = t[i];
              $.addEvent(self, "mouseover", function(){
                self.style.cursor = "pointer";
              });
              $.addEvent(self, "mouseout", function(){
                self.style.cursor = "default";
              });
              $.addEvent(self, "click", function(){ $.toggle(d); });
              var tbl = $.trim(self.innerHTML);
              var cols = [], defaults = [];
              $.each(d.firstChild.childNodes, function(key, val){
                var df = null, fc = val.firstChild;
                if (fc && fc.className == "columns") {
                  cols.push($.trim(fc.innerHTML));
                  while (fc = fc.nextSibling) {
                    if (fc.tagName == "SMALL" && fc.hasChildNodes()) {
                      $.each(fc.childNodes, function(key, val){
                        if (df === null && val.tagName == "VAR")
                          df = $.decodePlainValue(val);
                      });
                    }
                  }
                  defaults.push(df === null ? "NULL" : df);
                }
              });
              if (tbl && cols && cols.length) {
                var dbPath = $.val( $("select_db_path") );
                var orgCols = $.filterSpecialCols(cols);
                var joinNL = $.joinNL;
                if (!defaults.length || defaults.length != cols.length) {
                  defaults = [];
                  $.each(orgCols, function(key, val){
                    defaults.push("NULL");
                  });
                } else {
                  defaults = $.filterSpecialCols(cols, defaults);
                }
                $.each({
                  SELECT: joinNL(
                    "SELECT " + cols.join(", "),
                    "FROM " + tbl,
                    "WHERE 1 = 1",
                    "-- GROUP BY ctime",
                    "-- HAVING 1 = 1",
                    "ORDER BY rowid ASC",
                    "LIMIT 30 OFFSET 0"
                  ),
                  "COUNT(*)": joinNL(
                    "SELECT COUNT(*) FROM " + tbl
                  ),
                  "SELECT * ": joinNL(
                    "SELECT * FROM " + tbl,
                    "ORDER BY rowid ASC"
                  ),
                  INSERT: joinNL(
                    "INSERT INTO " + tbl,
                    "(" + orgCols.join(", ") + ")",
                    "VALUES",
                    "(" + defaults.join(", ") + ")"
                  ),
                  UPDATE: joinNL(
                    "UPDATE " + tbl,
                    "SET",
                    "  " + orgCols.join(" = {value},\n  ") + " = {value}",
                    "WHERE rowid = -1"
                  ),
                  DELETE: joinNL(
                    "DELETE FROM " + tbl,
                    "WHERE rowid = -1"
                  ),
                  DESCRIBE: joinNL(
                    "DESCRIBE " + tbl
                  ),
                  DROP: joinNL(
                    "-- *** Drop at your own risk! ***",
                    "DROP TABLE " + tbl
                  )
                }, function(key, val){
                  var btn = $().createElement("button");
                  btn.setAttribute("type", "button");
                  btn.appendChild( $().createTextNode(key) );
                  $.css(btn, {
                    fontSize: "x-small"
                  });
                  $.addEvent(btn, "click", function(){
                    var sql = val, text = $("sql_query");
                    if (text && typeof text.value != "undefined") {
                      if ($.val(text).indexOf(sql) == -1)
                        $.val(text, sql);
                      try {
                        text.focus();
                      } catch (e) {
                        location.hash = "#sql";
                      }
                    }
                  });
                  d.appendChild(btn);
                });
              }
            })();
          }
        }
      }
     })(Posql);
     (function($){
      var e, t = $("tableinfo");
      if (t.hasChildNodes()) {
        t = t.firstChild;
        do 
          if (t.tagName == "FIELDSET")
            break;
        while (t = t.nextSibling);
        (function(){
          var dbPath = $.val( $("select_db_path") );
          $.each({
            DESCRIBE: $.joinNL(
              "DESCRIBE DATABASE"
            ),
            CREATE: $.joinNL(
              "-- example of creation table query",
              "CREATE TABLE {table_name} (",
              "  {column_name_1} INTEGER PRIMARY KEY,",
              "  {column_name_2} VARCHAR(255) DEFAULT '',",
              "  {column_name_3} TEXT",
              ");"
            ),
            DROP: $.joinNL(
              "-- *** Drop at your own risk! ***",
              "DROP DATABASE " + dbPath
            )
          }, function(key, val){
            var btn = $().createElement("button");
            btn.setAttribute("type", "button");
            btn.appendChild( $().createTextNode(key) );
            $.css(btn, {
              fontSize: "x-small"
            });
            $.addEvent(btn, "click", function(){
              var sql = val, text = $("sql_query");
              if (text && typeof text.value != "undefined") {
                if ($.val(text).indexOf(sql) == -1)
                  $.val(text, sql);
                try {
                  text.focus();
                } catch (e) {
                  location.hash = "#sql";
                }
              }
            });
            t.appendChild(btn);
          });
        })();
      }
     })(Posql);
     //-->
     </script>
     <?php
    endif;
  }


  function displaySQLResult(){
    $result = null;
    $error  = null;
    $db  = $this->getPost('sql_select_db');
    $sql = $this->getPost('sql_query');
    if ($db == null || $sql == null) {
      return;
    }
    $sql_histories = $this->mem->loadAsPrivate('sql_history');
    if (!is_array($sql_histories)) {
      $sql_histories = array();
    }
    if (strlen($sql) < 1024) {
      $sql_histories[] = $sql;
      if (count($sql_histories) > 16) {
        array_shift($sql_histories);
      }
      $this->mem->saveAsPrivate('sql_history', $sql_histories);
    }
    $org_path = $this->db->path;
    $this->db->setPath($db);
    if (!$this->db->isDatabase()) {
      $error = $this->db->lastError();
      $error = $this->escape($error);
      $error.= sprintf('<div class="error">%s<br>%s</div>',
                 'データベースが変なようです。',
                 '再度、登録しなおしてください。'
      );
    } else {
      $start_time = $this->getMicrotime();
      $result = $this->db->query($sql);
      $end_time = $this->getMicrotime();
      $benchmark = sprintf('%.4f Sec.', $end_time - $start_time);
      if ($this->db->isError()) {
        $error = '';
        foreach ($this->db->getErrors() as $error_stack) {
          $error .= sprintf('<div class="error">%s</div>' . PHP_EOL,
                            $this->escape($error_stack));
        }
        $result = array();
      }
    }
    if (is_array($result) || is_object($result)) {
      $result = $this->toArrayResult($result);
    }
    if (!@file_exists($db) && $this->db->getLastMethod() === 'drop'
     && strpos(strtolower($this->db->getLastQuery()), 'database') !== false) {
      $db = null;
    }
    $this->db->setPath($org_path);
    $reload_link = sprintf(implode('',
        array(
          '<p>',
            '<a href="%s?auth=%s',
                   '&amp;sql_select_db=%s',
                   '&amp;select_db_path=%s',
                   '&amp;a=sql',
                   '&amp;sql_query=%s#sql">更新',
            '</a>',
          '</p>'
        )
      ),
      $this->script,
      $this->isLogin,
      urlencode($db),
      urlencode($db),
      urlencode( $this->getPost('from_import') ? '' : $sql )
    );
    $enable_editrows = false;
    $current_table = null;
    if ($this->isSimpleSelectSQL($sql)) {
      $current_table = $this->getCurrentTablename();
      if ($current_table != null) {
        $enable_editrows = true;
      }
    }
    if (!is_numeric($this->maxEditRowsCount)) {
      $this->maxEditRowsCount = 30;
    }
    ?>
    <div id="sql_result">
      <?php
      if($error):
        ?>
        <fieldset>
          <legend>Error</legend>
          <div>
            <div class="error">
              <?php echo $error; ?>
            </div>
          </div>
        </fieldset>
        <?php
      else:
        ?>
        <form action="<?php echo $this->script; ?>" method="post">
          <input type="hidden" name="auth" value="<?php
            echo $this->isLogin; ?>">
          <input type="hidden" name="a" value="sql_result">
          <fieldset>
            <legend>SQL Result</legend>
            <div>
              <fieldset>
                <legend><small>実行した SQL</small></legend>
                <div>
                  <div>
                    <textarea rows="7" cols="80" class="note"
                              readonly="readonly"><?php
                      echo $this->escape($sql);
                    ?></textarea>
                    <textarea id="sql_editrows_table"
                              style="display:none"><?php
                      if($enable_editrows):
                        echo $this->escape($current_table);
                      else:
                        echo "";
                      endif;
                    ?></textarea>
                  </div>
                  <div>
                    <small><?php echo $benchmark; ?></small>
                  </div>
                </div>
              </fieldset>
              <?php
              if(!is_array($result)):
                if(!is_bool($result) && is_numeric($result)):
                  ?>
                  <p class="success">
                    <?php
                    echo $result;
                    ?> の列に影響がありました。
                  </p>
                  <?php
                else:
                  ?>
                  <p>
                    <strong>結果:</strong>
                    <span>
                      <?php
                      echo $this->plainValue($result);
                      ?>
                    </span>
                  </p>
                  <?php
                endif;
                echo $reload_link;
              elseif(empty($result)):
                ?>
                <p>結果セットが空です。<p>
                <?php
                printf('<small>%s</small>', $this->db->lastError());
                echo $reload_link;
              else:
                ?>
                <p>
                  <strong class="row-num">
                    <?php
                    echo count($result);
                    ?>
                  </strong>
                  <span>rows.</span>
                </p>
                <p id="sql_editrows_note" style="display:none">
                  <small>
                    列をダブルクリックすると編集できます。
                    <small>(beta)</small>
                    <br>
                    編集後 OK ボタンを押すと
                    テキストエリアに SQL が挿入されます。
                  </small>
                </p>
                <p id="sql_editrows_failed" style="display:none">
                  <small>
                    結果の列が多いため直接編集できません。
                    <br>
                    列が <?php
                      echo $this->maxEditRowsCount;
                    ?> 以下の場合、直接編集できます。
                  </small>
                </p>
                <table class="result">
                  <tr>
                    <?php
                    foreach(array_keys(reset($result)) as $cols):
                      printf('<td class="cols">%s </td>',
                             $this->escape($cols));
                    endforeach;
                    ?>
                  </tr>
                  <?php
                  $id_seed = 1;
                  while($row = array_shift($result)):
                    $rowid = $this->getValue($row, 'rowid', $id_seed++);
                    printf('<tr id="rowid_%010.0f">', $rowid);
                      foreach($row as $col => $rs):
                        switch(true):
                          case is_array($rs):
                          case is_object($rs):
                              $rs_string = var_export($rs, true);
                              break;
                          default:
                              $rs_string = $rs;
                              break;
                        endswitch;
                        echo '<td class="row">',
                               '<textarea class="row-col"'
                                     .  ' style="display:none">',
                                 $this->escape($col),
                               '</textarea>', PHP_EOL,
                               '<textarea class="row-data"'
                                     .  ' style="display:none">',
                                 $this->escape($rs_string),
                               '</textarea>', PHP_EOL,
                               '<div class="row-html">',
                                 $this->plainValue($rs, $col),
                               '</div>',
                             '</td>';
                      endforeach;
                    echo '</tr>', PHP_EOL;
                  endwhile;
                  ?>
                </table>
                <?php
              endif;
              ?>
            </div>
          </fieldset>
        </form>
        <?php
      endif;
      ?>
    </div>
    <script type="text/javascript">
    <!--
    (function($){
      <?php
      if($this->getPost('from_import')):
        ?>
        $.addEvent(window, "load", function(){
          location.hash = "#sql_result";
        });
        <?php
      endif;
      ?>
    })(Posql);
    (function($){
      var getTable = function(){
        var ret = "";
        var elem = $("sql_editrows_table");
        if (elem) {
          var table = $.trim( $.val(elem) );
          if (table && table.length) {
            ret = table;
          }
        }
        return ret;
      };
      var table = getTable();
      if (!table) {
        return;
      }
      var editor = $("sql_query");
      var rows = $.getElementsByClass("row") || [];
      var getRowId = function(id){
        return (id || "").replace(/^rowid_0*/gi, "");
      };
      var getChild = function(node, type){
        return $.getElementsByClass(type, node)[0];
      };
      var rowCount = Number( $.trim( $.val( getChild(null, "row-num") ) ) );
      var maxEditRowsCount = Number("<?php echo $this->maxEditRowsCount; ?>");
      if (maxEditRowsCount < rowCount) {
        $.show( $("sql_editrows_failed") );
        return;
      }
      if (editor && rows && rows.length) {
        $.show( $("sql_editrows_note") );
        $.each(rows, function(i, v){
          var td = v;
          var rowid = getRowId(td.parentNode.id);
          var text = $().createElement("textarea");
          $.attr(text, {
            className: "row-update",
            cols: "60",
            rows: "5"
          });
          $.css(text, {
            width: "99%",
            fontSize: "small"
          });
          var ok = $().createElement("button");
          $.attr(ok, {
            type: "button",
            title: "SQLを生成"
          });
          $.css(ok, {
            fontSize: "xx-small",
            margin: "4px"
          });
          ok.appendChild( $().createTextNode("OK") );
          var foot = $().createElement("div");
          $.css(foot, {
            width: "98%",
            textAlign: "center"
          });
          var cancel = $().createElement("button");
          $.attr(cancel, {
            type: "button",
            title: "キャンセル"
          });
          $.css(cancel, {
            fontSize: "xx-small",
            margin: "4px"
          });
          cancel.appendChild( $().createTextNode("CANCEL") );
          foot.appendChild( ok );
          foot.appendChild( cancel );
          var wrap = $().createElement("div");
          $.attr(wrap, {
            className: "row-edit"
          });
          $.css(wrap, {
            display: "none"
          });
          wrap.appendChild(text);
          wrap.appendChild(foot);
          td.appendChild( wrap );
          $.addEvent(ok, "click", function(){
            var update = getChild(td, "row-update");
            var data = getChild(td, "row-data");
            var html = getChild(td, "row-html");
            var edit = getChild(td, "row-edit");
            var col = getChild(td, "row-col");
            var colName = $.trim( $.val(col) );
            var updateText = $.val( update );
            var quote = function(s){
              return "'" + $.escapeString(s).replace(/\\\"/g, "\"") + "'";
            };
            var sql = [
              "UPDATE " + table,
              "SET " + colName + " = " + quote(updateText),
              "WHERE rowid = " + rowid,
              ""
            ].join("\n");
            $.val( data, $.val( update ) );
            $.val( html, $.escapeHTML( $.val( update ) ) );
            $.toggle(edit);
            $.toggle(html);
            if (editor && typeof editor.value != "undefined") {
              if ($.val(editor).indexOf(sql) == -1) {
                $.val(editor, sql);
              }
              try {
                editor.focus();
                editor.scrollIntoView(true);
              } catch (er) {
                location.hash = "#sql";
              }
            }
          });
          $.addEvent(cancel, "click", function(){
            var html = getChild(td, "row-html");
            var edit = getChild(td, "row-edit");
            $.toggle(edit);
            $.toggle(html);
          });
          $.addEvent(td, "dblclick", function(){
            var update = getChild(td, "row-update");
            var data = getChild(td, "row-data");
            var html = getChild(td, "row-html");
            var edit = getChild(td, "row-edit");
            var col = getChild(td, "row-col");
            if (col) {
              var colName = $.trim( $.val(col) );
              if (colName != "rowid" &&
                  colName != "ctime" && 
                  colName != "utime") {
                if (edit.style.display == "none") {
                  $.val( update, $.val( data ) );
                  $.toggle(html);
                  $.toggle(edit);
                }
              }
            }
          });
        });
      }
    })(Posql);
    //-->
    </script>
    <?php
  }


  function displayMaintenance(){
    $result = null;
    $file = $this->getPost('target');
    $m = $this->getPost('m');
    $title = '';
    switch ($m) {
      case 'vacuum':
          $title = '最適化';
          $result = false;
          if ($this->db->isDatabase($file) && $this->db->open($file)) {
            $database_before_size = @(filesize($this->db->getPath()));
            $reports = array();
            $opts = & $this->optimizeLines;
            $opts = array();
            $result = (bool)$this->optimizeDatabase();
            clearstatcache();
            $database_after_size = @(filesize($this->db->getPath()));
            if ($result) {
              if (!empty($opts)) {
                foreach ($opts as $i => $ret) {
                  switch ($ret) {
                    case $this->OPTIMIZE_DELETE:
                        $reports[] = sprintf('Line %.0f: 削除しました', $i);
                        break;
                    case $this->OPTIMIZE_REPAIR:
                        $reports[] = sprintf('Line %.0f: 修復しました', $i);
                        break;
                    case $this->OPTIMIZE_SKIP:
                    default:
                        //$reports[] = sprintf('line %010u: スキップ', $i);
                        break;
                  }
                }
              }
            }
            if (!empty($reports)) {
              $htmls = array('<div><small><ul>');
              foreach ($reports as $report) {
                $htmls[] = sprintf('<li>%s</li>', $this->escape($report));
              }
              $htmls[] = '</ul></small></div>';
              $reports = implode(PHP_EOL, $htmls);
              unset($htmls);
            }
            if (is_array($reports)) {
              $reports = implode('', $reports);
            }
            $reports .= sprintf('<div>データベースのサイズ:</div>'
                             .  '<div>'
                              .  '<ul>'
                               .  '<li>処理前: %u Bytes</li>'
                               .  '<li>処理後: %u Bytes</li>'
                               .  '<li> 差分 : %d Bytes</li>'
                              .  '</ul>'
                             .  '</div>',
              $database_before_size,
              $database_after_size,
              $database_before_size - $database_after_size
            );
            $opts = array();
            unset($opts);
          }
          break;
      case 'unlock':
          $title = '強制ロック解除';
          $result = false;
          if ($this->db->isDatabase($file)) {
            $this->db->_setTerminated(true);
            if ($this->db->open($file)) {
              $this->db->unlockDatabase();
              if ($this->db->unlockAll(true)) {
                $meta = $this->db->getMeta();
                if ($meta != null && is_array($meta)) {
                  $tables = array();
                  foreach (array_keys($meta) as $encoded_name) {
                    $tables[] = $this->db->decodeKey($encoded_name);
                  }
                  $this->db->_setLockedTables($tables);
                  if ($this->db->unlockAllTables(true)) {
                    $result = true;
                  }
                }
              }
            }
          }
          break;
      case 'clear_query_cache':
          $title = 'クエリーキャッシュをクリア';
          $result = false;
          if ($this->db->isDatabase($file) && $this->db->open($file)) {
            if (method_exists($this->db, 'clearQueryCache')) {
              $clear_count = $this->db->clearQueryCache();
              if (!$this->db->isError()) {
                $result = true;
              }
              $reports = sprintf(
                '<p><small><strong><big>%d</big></strong> '
                 .  'のキャッシュを削除しました</small></p>',
                $clear_count
              );
            }
          }
          break;
      default:
          $result = null;
          break;
    }
    ?>
    <div id="maintenance">
      <fieldset>
        <legend>データベースのメンテナンス</legend>
          <div>
          <?php
          if(!@is_file($file)):
            ?>
            <p>
              <small>対象のデータベースがありません</small>
            </p>
            <p>
              <small>ファイル: <?php echo $this->escape($file); ?></small>
            </p>
            <?php
          else:
            if(is_bool($result)):
              printf('<p><strong>%s</strong></p>', $this->escape($title));
              if($result):
                ?>
                <p class="success">
                  正常に処理が完了しました。
                </p>
                <?php
              else:
                ?>
                <p class="error">
                  エラーが発生しました。
                </p>
                <?php
                if($this->db->isError()):
                  echo '<p>';
                  foreach($this->db->getErrors() as $error):
                    printf('<div class="error"><small>%s</small></div>%s',
                      $this->escape($error),
                      PHP_EOL
                    );
                  endforeach;
                  echo '</p>';
                endif;
              endif;
              if(isset($reports) && is_string($reports)):
                echo $reports;
              endif;
            endif;
            ?>
            <p>
              <small>データベース:</small>
              <br>
              <strong><?php echo $this->escape($file); ?></strong>
            </p>
            <p>
              <small>
               <div>データベースの最適化や壊れた列の除去を行います。</div>
              </small>
            </p>
            <p>
              <?php
              printf(implode('',
                  array('<a href="%s?auth=%s',
                               '&amp;a=maintenance',
                               '&amp;m=vacuum',
                               '&amp;target=%s">',
                          '<small>最適化を実行</small>',
                        '</a>'
                  )
                ),
                $this->script,
                $this->isLogin,
                urlencode($file)
              );
              ?>
            </p>
            <p>
              <small>
               <div>デッドロックを解消します。<div>
               <div>※解除されていないままのロックを強制的に解除します</div>
               <div>※アクセス中のプロセスがある場合は注意してください</div>
              </small>
            </p>
            <p>
              <?php
              printf(implode('',
                  array('<a href="%s?auth=%s',
                               '&amp;a=maintenance',
                               '&amp;m=unlock',
                               '&amp;target=%s">',
                          '<small>強制ロック解除を実行</small>',
                        '</a>'
                  )
                ),
                $this->script,
                $this->isLogin,
                urlencode($file)
              );
              ?>
            </p>
            <p>
              <small>
               <div>クエリーキャッシュをクリアします。<div>
               <div>※すべてのクエリーキャッシュが削除されます。</div>
              </small>
            </p>
            <p>
              <?php
              printf(implode('',
                  array('<a href="%s?auth=%s',
                               '&amp;a=maintenance',
                               '&amp;m=clear_query_cache',
                               '&amp;target=%s">',
                          '<small>クエリーキャッシュのクリアを実行</small>',
                        '</a>'
                  )
                ),
                $this->script,
                $this->isLogin,
                urlencode($file)
              );
              ?>
            </p>
            <?php
          endif;
          ?>
        </div>
      </fieldset>
    </div>
    <?php
  }


  function displayConfig(){
    foreach (array('posql', 'id',   'ps', 'css', 'js',
                                  'help',  'dl', 'mode') as $var) {
      ${$var} = & $this->{sprintf('config_%s', $var)};
    }
    unset($var);
    if ($id == null || $ps == null) {
      $act = (array) $this->mem->loadAsPrivate('account');
      $id  = isset($act['id']) ? $this->decrypt($act['id']) : '';
      $ps  = isset($act['ps']) ? $this->decrypt($act['ps']) : '';
      unset($act);
    }
    foreach (array('posql', 'css', 'js', 'help', 'dl', 'mode') as $var) {
      if (${$var} == null) {
        ${$var} = $this->mem->loadAsPrivate($var);
      }
    }
    unset($var);
    $engines = array('SQL', 'PHP');
    ?>
    <div id="config">
      <form action="<?php echo $this->script ?>" method="post">
        <input type="hidden" name="auth" value="<?php echo $this->isLogin; ?>">
        <input type="hidden" name="a" value="config">
        <fieldset>
          <legend>設定</legend>
          <div class="config">
            <small>
              <fieldset>
                <legend>Posql クラス ライブラリ パス</legend>
                <table>
                  <tr>
                    <td colspan="2" align="left">
                      <small>
                        <strong>posql.php</strong> のファイルパスを設定
                      </small>
                      <br>
                      <small>
                        ※最新でない場合エラーが発生するかもしれません。
                      </small>
                    </td>
                  </tr>
                  <tr>
                    <td align="left">
                      <input type="text" size="40" name="config_posql"
                             value="<?php echo $this->escape($posql); ?>">
                    </td>
                  </tr>
                </table>
              </fieldset>
              <fieldset>
                <legend>アカウント</legend>
                <table>
                  <tr>
                    <td colspan="2" align="left">
                      <small>ログイン時のアカウント情報を設定</small>
                    </td>
                  </tr>
                  <tr>
                    <td align="left">
                      <span>id:</span>
                    </td>
                    <td align="right">
                      <input type="text" size="40" name="config_id" value="<?php
                        echo $this->escape($id); ?>">
                    </td>
                  </tr>
                  <tr>
                    <td align="left">
                      <span>pass:</span>
                    </td>
                    <td align="right">
                      <input type="password" size="40" name="config_ps" value="<?php
                        echo $this->escape($ps); ?>">
                    </td>
                  </tr>
                </table>
              </fieldset>
              <fieldset>
                <legend>SQL Engine</legend>
                <table>
                  <tr>
                    <td colspan="2" align="left">
                      <small>SQL の評価エンジンを設定</small><br>
                      <small>
                       ※評価エンジンの詳細は
                         マニュアルを参照してください。
                      </small>
                    </td>
                  </tr>
                  <tr>
                    <td align="left">
                      <select name="config_mode"><?php
                        foreach($engines as $engine):
                          $selected = '';
                          if(strcasecmp($mode, $engine) === 0):
                            $selected = ' selected="selected"';
                          endif;
                          printf('<option value="%s"%s>%s</option>',
                                $this->escape($engine),
                                $selected,
                                $this->escape($engine)
                          );
                        endforeach;?>
                      </select>
                    </td>
                  </tr>
                </table>
              </fieldset>
              <fieldset>
                <legend>Help</legend>
                <table>
                  <tr>
                    <td colspan="2" align="left">
                      <small>マニュアルのファイルパスを設定</small><br>
                      <small>※マニュアルは html 形式で 同梱されています。</small>
                    </td>
                  </tr>
                  <tr>
                    <td align="left">
                      <input type="text" size="40" name="config_help" value="<?php
                        echo $this->escape($help); ?>">
                    </td>
                  </tr>
                </table>
              </fieldset>
              <fieldset>
                <legend>Download</legend>
                <table>
                  <tr>
                    <td colspan="2" align="left">
                      <small>ダウンロードが失敗する場合の設定</small><br>
                      <small>※別窓で開くと成功するかもしれません。</small>
                    </td>
                  </tr>
                  <tr>
                    <td align="left">
                      <label>
                        <?php
                          printf('<input type="checkbox" name="config_dl"%s>',
                            empty($dl) ? '' : ' checked="checked"'
                          );
                        ?>
                        ダウンロードリンクを別窓で開く
                      </label>
                    </td>
                  </tr>
                </table>
              </fieldset>
              <fieldset>
                <legend>CSS</legend>
                <table>
                  <tr>
                    <td colspan="2" align="left">
                      <small>管理ページで使用するスタイルシートを設定</small>
                    </td>
                  </tr>
                  <tr>
                    <td align="left">
                      <textarea cols="80" rows="7" name="config_css"><?php
                        echo $this->escape($css); ?></textarea>
                    </td>
                  </tr>
                </table>
              </fieldset>
              <fieldset>
                <legend>JavaScript</legend>
                <table>
                  <tr>
                    <td colspan="2" align="left">
                      <small>
                        <span>管理ページで使用する JavaScript を設定</span><br>
                        <span>JavaScript は &lt;body&gt; の末尾に記述されます</span>
                      </small>
                    </td>
                  </tr>
                  <tr>
                    <td align="left">
                      <textarea cols="80" rows="7" name="config_js"><?php
                        echo $this->escape($js); ?></textarea>
                    </td>
                  </tr>
                </table>
              </fieldset>
            </small>
            <div>
              <input type="submit" name="m" value="SAVE" title="保存">
            </div>
          </div>
        </fieldset>
      </form>
    </div>
    <?php
    if (!empty($this->hideWarnMsg)):
      ?>
      <script type="text/javascript">
      <!--
      (function($){
        $.each( $.getElementsByClass("posql_open_failed"), function(i, v){
          $.hide(v);
        });
      })(Posql);
      //-->
      </script>
      <?php
    endif;
  }

  function displayHelp(){
    $help_path = & $this->config_help;
    if ($help_path == null) {
      $help_path = $this->mem->loadAsPrivate('help');
    }
    $links = array();
    $exists = $this->isReadable($help_path);
    if ($exists) {
      $fp = @fopen($help_path, 'rb');
      if (is_resource($fp)) {
        $links = array();
        $index_point_start = '<!--Begin#IndexPoint-->';
        $index_point_end   = '<!--End#IndexPoint-->';
        $link_pattern = '{<a\s+href="#(\w+)">([^<]+)</a>}i';
        $in_index = false;
        while (!feof($fp)) {
          $line = rtrim(fgets($fp));
          if ($line === $index_point_start) {
            $in_index = true;
          }
          if ($in_index) {
            if (preg_match($link_pattern, $line, $match)) {
              $links[ $match[1] ] = $match[2];
            }
          }
          if ($line === $index_point_end) {
            break;
          }
        }
        fclose($fp);
      }
      if (!empty($links)) {
        $top = array('top' => 'マニュアルトップ');
        $links = array_merge($top, $links);
      }
      unset($file);
    }
    ?>
    <div id="help">
      <fieldset>
        <legend>ヘルプ</legend>
        <div><?php
         if($exists):?>
           <small><?php
           foreach($links as $id => $text):
             printf('<a href="%s#%s" target="%s">%s</a>' . PHP_EOL,
               $this->escape($help_path),
               $this->escape($id),
               $this->escape($this->helpDocFrameId),
               $this->escape($text)
             );
           endforeach;?>
           </small>
           <iframe name="<?php
             echo $this->escape($this->helpDocFrameId);
              ?>" id="<?php
             echo $this->escape($this->helpDocFrameId);
              ?>" src="<?php
             echo $this->escape($help_path);
              ?>" width="99%" height="400" class="help-frame">
              <a href="<?php
                echo $this->escape($help_path);
                ?>">マニュアル</a>
            </iframe><?php
         else:?>
            ドキュメントがありません
            <?php
         endif;?>
        </div>
      </fieldset>
    </div>
    <?php
    unset($help_path);
  }

  function displayLogin(){
    ?>
    <div id="login">
      <form action="<?php echo $this->script ?>" method="post">
        <input type="hidden" name="a" value="login">
        <input type="hidden" name="login_error_count" value="<?php
          echo $this->loginErrorCount; ?>">
        <fieldset>
          <legend>ログイン</legend>
          <div>
            <table>
              <tr>
                <td align="left">
                  <span>id:</span>
                </td>
                <td align="right">
                  <input type="text" size="40" name="login_id" value="">
                </td>
              </tr>
              <tr>
                <td align="left">
                  <span>pass:</span>
                </td>
                <td align="right">
                  <input type="password" size="40" name="login_ps" value="">
                </td>
              </tr>
              <tr>
                <td colspan="2" align="center">
                  <input type="submit" name="m" value="LOGIN" title="ログイン">
                </td>
              </tr>
            </table>
          </div>
        </fieldset>
      </form>
    </div>
    <?php
  }

  function displayFoot(){
      echo $this->mem->loadAsPrivate('js');
    ?></body>
    </html><?php
  }

  function displayAuthError(){
    $this->sendContentType();
    if (!is_object($this->db)) {
      $this->db = & new Posql_Dummy_Object;
    }
    ?>
    <html>
    <head>
    <meta http-equiv="content-type" content="text/html; charset=<?php
      echo $this->charset ?>">
    <title><?php echo $this->title; ?> - Authentication Failure</title>
    <style type="text/css">
    <!--
    *    { font-family  : verdana;
           font-size    : x-small;}
    body { text-align   : left;
           background   : #fff;
           margin       : 20px auto auto 20px;}
    h1   { color        : #ff6633;
           font-size    : x-large;
           font-weight  : bold;}
    h3   { color        : #666;
           font-size    : small;
           font-weight  : normal;}
    p    { color        : #999;
           font-size    : x-small;}
    hr   { border-color : #999;
           border-style : dashed;
           border-width : 1px 0 0;
           height       : 1px;
           width        : 70%;
           margin-left  : 0;}
    cite { font-weight  : bold;
           font-style   : normal;}
    .pwd { color        : #666;
           font-size    : xx-small;
           margin       : 2px 6px auto auto;}
    -->
    </style>
    </head>
    <body>
    <h1><?php echo $this->className; ?> - Authentication Failure</h1>
    <h3>Confirm ID and PASS again.</h3>
    <p><em>Hint: default values are the empty ID and PASS</em></p>
    <hr>
    <div class="pwd">
      <span>Powered by </span>
      <a href="<?php echo $this->projectURL; ?>"><?php
        echo $this->db->getClass(); ?> <small>(Version <?php
        echo $this->db->getVersion(); ?>)</small></a>
      <span><small>.. by polygon planet</small></span>
    </div>
    </body>
    </html><?php
  }

}
//-----------------------------------------------------------------------------
/**
 * Run the PosqlAdmin
 *
 * @param void
 * @return void
 */
function posql_admin_run(){
  $p = & new PosqlAdmin();
  $p->init();
  $p->run();
  $p = null;
  unset($p);
  exit(0);
}
//-----------------------------------------------------------------------------
/**
 * Posql dummy class for PosqlAdmin
 */
class Posql_Dummy_Object {
  function getClass(){
    return 'Posql';
  }
  function getVersion(){
    return 'undefined';
  }
  function fullPath(){
    return 'undefined';
  }
}
//-----------------------------------------------------------------------------
// $Id: memo.php,v 0.15 2011/12/13 02:26:00 polygon Exp $
class Memo{
	var $m,$o,$B,$E,$N;

	function init(){
		$p='/';
		$this->m=__FILE__;
		$this->B=$p.'*@BEGIN';
		$this->E='END@*'.$p;
		if(!(fileperms($this->m)&2)){
			$a=@fopen($this->m,'a');
			@fclose($a);
			if(!$a)
				die('There is no writing attribute in the permission of the file.
						Must to enables the writing attribute (e.g. 666 (rw-rw-rw)).');
		}
		$f=$this->_fopen($this->o=0);
		$i=1;
		while(!feof($f)){
			$s=fgets($f);
			if(!--$i){
				$this->N=substr($s,strlen(rtrim($s)));
				$this->B.=$this->N;
				$this->E.=$this->N;
			}
			if($s==$this->B)
				$this->o=ftell($f);
		}
		fclose($f);
	}

	function encode($a){
		return base64_encode(serialize($a));
	}

	function decode($a){
		return unserialize(base64_decode($a));
	}

	function _glue($a){
		return$a?':':'|';
	}

	function &_fopen($a=0){
		$f=fopen($this->m,$a?'r+b':'rb');
		flock($f,$a?LOCK_EX:LOCK_SH);
		fseek($f,$this->o);
		return$f;
	}

	function _load($a=0,$b=1){
		$r=array();
		$b=$this->_glue($b);
		$f=$this->_fopen();
		while(!feof($f)&&($e=fgets($f))!=$this->E){
			if(count($g=explode($b,$e))==2){
				$k=urldecode(reset($g));
				if(!$a||$a==$k){
					$r[$k]=$this->decode(end($g));
					if($a)
						break;
				}
			}
		}
		fclose($f);
		return$a&&isset($r[$a])?($r[$a]==null?'':$r[$a]):($r==null?'':$r);
	}

	function _save($a,$b,$c=1,$d=1){
		$r=0;
		$c=$this->_glue($c);
		$b=$this->encode($b);
		$a=urlencode($a);
		$f=$this->_fopen(1);
		$m=$w=array();
		while(!feof($f)&&($e=fgets($f))!=$this->E)
			foreach(array(':','|')as$l)
				if(count($g=explode($l,$e))==2)
					$m[reset($g)]=array($l,end($g));
		if(empty($m[$a])||$d){
			$m[$a]=array($c,$b);
			$r=1;
		}
		fseek($f,$this->o);
		$t=ignore_user_abort(1);
		foreach($m as$k=>$v)
			fputs($f,$k.$v[0].rtrim($v[1]).$this->N);
		fputs($f,$this->E);
		ftruncate($f,ftell($f));
		ignore_user_abort($t);
		fclose($f);
		return!!$r;
	}

	function _delete($a,$b=1){
		$r=0;
		$b=$this->_glue($b);
		$a=urlencode($a);
		$f=$this->_fopen(1);
		$m='';
		while(!feof($f)&&($e=fgets($f))!=$this->E)
			if((count($g=explode($p=':',$e))==2
				||count($g=explode($p='|',$e))==2)
				&&($p!=$b||($p==$b&&$a&&$a!=reset($g))))
				$m.=join($p,$g);
			else
				$r=1;
		fseek($f,$this->o);
		$t=ignore_user_abort(1);
		fputs($f,$m.$this->E);
		ftruncate($f,ftell($f));
		ignore_user_abort($t);
		fclose($f);
		return!!$r;
	}

	function _count($a=0,$b=1){
		$b=$this->_glue($b);
		$f=$this->_fopen($c=0);
		while(!feof($f)&&($e=fgets($f))!=$this->E)
			if(count($g=explode($b,$e))==2)
				$c+=(!$a||$a==urldecode(reset($g)))?1:0;
		fclose($f);
		return$c;
	}

	function _enum($a=1){
		$r=array();
		$a=$this->_glue($a);
		$f=$this->_fopen(0);
		while(!feof($f)&&($e=fgets($f))!=$this->E)
			if(count($g=explode($a,$e))==2)
				$r[]=urldecode(reset($g));
		fclose($f);
		return$r;
	}

	function load($a=0){return$this->_load($a,1);}
	function save($a,$b,$c=1){return$this->_save($a,$b,1,$c);}
	function delete($a){return$this->_delete($a,1);}
	function count($a=0){return$this->_count($a,1);}
	function enum(){return$this->_enum(1);}

	function loadAsPrivate($a=0){return$this->_load($a,0);}
	function saveAsPrivate($a,$b,$c=1){return$this->_save($a,$b,0,$c);}
	function deleteAsPrivate($a){return$this->_delete($a,0);}
	function countAsPrivate($a=0){return$this->_count($a,0);}
	function enumAsPrivate(){return$this->_enum(0);}
}
function &Memo(){
	static $a;
	if(!$a){
		$a=&new Memo;
		$a->init();
	}
	return$a;
}
//-----------------------------------------------------------------------------
//the point for storage are below. *MUST NOT DELETE*
exit;__halt_compiler();
/*@BEGIN
help|czoyMToicG9zcWwuc3FsLm1hbnVhbC5odG1sIjs=
css|czoyODIyOiI8c3R5bGUgdHlwZT0idGV4dC9jc3MiPg0KPCEtLQ0KLyogYmFzaWNzICovDQoqICAgICAgICAgICAgICAgIHtmb250LWZhbWlseSAgICAgOiB2ZXJkYW5hO30NCmJvZHksIHRkICAgICAgICAge2ZvbnQtc2l6ZSAgICAgICA6IHNtYWxsO30NCmZpZWxkc2V0ICAgICAgICAge3BhZGRpbmcgICAgICAgICA6IDJweDt9DQp0ZXh0YXJlYSAgICAgICAgIHtwYWRkaW5nICAgICAgICAgOiAycHg7DQogICAgICAgICAgICAgICAgICBmb250LWZhbWlseSAgICAgOiBtb25vc3BhY2U7fQ0KLyogY2xhc3NlcyAqLw0KLmVycm9yICAgICAgICAgICB7Y29sb3IgICAgICAgICAgIDogcmVkO30NCi5zdWNjZXNzICAgICAgICAge2NvbG9yICAgICAgICAgICA6IGJsdWU7fQ0KLnN0YXR1cyAgICAgICAgICB7fQ0KLnJlc3VsdCAgICAgICAgICB7Ym9yZGVyICAgICAgICAgIDogMnB4IHNvbGlkICM5OTk7DQogICAgICAgICAgICAgICAgICBiYWNrZ3JvdW5kICAgICAgOiAjZmZmOw0KICAgICAgICAgICAgICAgICAgZW1wdHktY2VsbHMgICAgIDogc2hvdzsNCiAgICAgICAgICAgICAgICAgIGJvcmRlci1jb2xsYXBzZSA6IGNvbGxhcHNlOw0KICAgICAgICAgICAgICAgICAgY29sb3IgICAgICAgICAgIDogIzMzMzt9DQoucmVzdWx0LA0KLnJlc3VsdCB0ZCAgICAgICB7Zm9udC1zaXplICAgICAgIDogeC1zbWFsbDsNCiAgICAgICAgICAgICAgICAgIHRleHQtYWxpZ24gICAgICA6IGxlZnQ7DQogICAgICAgICAgICAgICAgICBib3JkZXIgICAgICAgICAgOiAycHggc29saWQgIzk5OTsNCiAgICAgICAgICAgICAgICAgIHBhZGRpbmcgICAgICAgICA6IDJweDsNCiAgICAgICAgICAgICAgICAgIG1hcmdpbiAgICAgICAgICA6IDFweDt9DQoucmVzdWx0IC5jb2xzICAgIHtiYWNrZ3JvdW5kICAgICAgOiAjZWVlOw0KICAgICAgICAgICAgICAgICAgY29sb3IgICAgICAgICAgIDogIzMzMzsNCiAgICAgICAgICAgICAgICAgIGZvbnQtd2VpZ2h0ICAgICA6IGJvbGQ7fQ0KLnJlc3VsdCAucm93ICAgICB7YmFja2dyb3VuZCAgICAgIDogI2ZmZjsNCiAgICAgICAgICAgICAgICAgIGNvbG9yICAgICAgICAgICA6ICMzMzM7DQogICAgICAgICAgICAgICAgICBmb250LXdlaWdodCAgICAgOiBub3JtYWw7fQ0KLnRhYmxlaW5mby1wYW5lbCB7Zm9udC1zaXplICAgICAgIDogeC1zbWFsbDsNCiAgICAgICAgICAgICAgICAgIG1hcmdpbiAgICAgICAgICA6IDJweDsNCiAgICAgICAgICAgICAgICAgIHBhZGRpbmcgICAgICAgICA6IDJweDt9DQoubm90ZSAgICAgICAgICAgIHtib3JkZXIgICAgICAgICAgOiAxcHggc29saWQgIzY2NjZjYzsNCiAgICAgICAgICAgICAgICAgIGJhY2tncm91bmQgICAgICA6ICNlZWVlZmY7DQogICAgICAgICAgICAgICAgICBjb2xvciAgICAgICAgICAgOiAjMzMzMzY2Ow0KICAgICAgICAgICAgICAgICAgcGFkZGluZyAgICAgICAgIDogNnB4Ow0KICAgICAgICAgICAgICAgICAgbWFyZ2luICAgICAgICAgIDogNHB4Ow0KICAgICAgICAgICAgICAgICAgZm9udC1zaXplICAgICAgIDogeC1zbWFsbDsNCiAgICAgICAgICAgICAgICAgIGZvbnQtd2VpZ2h0ICAgICA6IGJvbGQ7DQogICAgICAgICAgICAgICAgICBmb250LXN0eWxlICAgICAgOiBub3JtYWw7fQ0KLmhlbHAtZnJhbWUgICAgICB7Ym9yZGVyICAgICAgICAgIDogMnB4IHNvbGlkICM5OTk7DQogICAgICAgICAgICAgICAgICBtYXJnaW4gICAgICAgICAgOiAycHg7DQogICAgICAgICAgICAgICAgICBwYWRkaW5nICAgICAgICAgOiAwO30NCi5kdW1wLXRleHQgICAgICAge3dpZHRoICAgICAgICAgICA6IDk5JTsNCiAgICAgICAgICAgICAgICAgIGhlaWdodCAgICAgICAgICA6IDQwMHB4O30NCi50dCAgICAgICAgICAgICAge2ZvbnQtZmFtaWx5ICAgICA6ICdjb3VyaWVyIG5ldycsbW9ub3NwYWNlO30NCi8qIGFuY2hvcnMgKi8NCmE6bGluayAgICAgICAgICAge3RleHQtZGVjb3JhdGlvbiA6IHVuZGVybGluZTt9DQphOnZpc2l0ZWQsDQphOmFjdGl2ZSAgICAgICAgIHt0ZXh0LWRlY29yYXRpb24gOiB1bmRlcmxpbmU7fQ0KYTpob3ZlciAgICAgICAgICB7dGV4dC1kZWNvcmF0aW9uIDogbm9uZTt9DQovKiB2YXJzICovDQp2YXIgICAgICAgICAgICAgIHtmb250LXNpemUgICAgICAgOiB4LXNtYWxsOw0KICAgICAgICAgICAgICAgICAgZm9udC13ZWlnaHQgICAgIDogbm9ybWFsOw0KICAgICAgICAgICAgICAgICAgZm9udC1zdHlsZSAgICAgIDogbm9ybWFsOw0KICAgICAgICAgICAgICAgICAgY29sb3IgICAgICAgICAgIDogIzY2NjsNCiAgICAgICAgICAgICAgICAgIGJhY2tncm91bmQgICAgICA6ICNmZmY7fQ0KdmFyLnVua25vd24gICAgICB7Y29sb3IgICAgICAgICAgIDogIzk5OTk5OTt9DQp2YXIuaW50ICAgICAgICAgIHtjb2xvciAgICAgICAgICAgOiAjMzMwMDY2O30NCnZhci5ib29sICAgICAgICAge2NvbG9yICAgICAgICAgICA6ICMwMDAwOTk7fQ0KdmFyLnN0cmluZyAgICAgICB7Y29sb3IgICAgICAgICAgIDogI2NjMzMwMDt9DQp2YXIubnVsbCAgICAgICAgIHtjb2xvciAgICAgICAgICAgOiAjNjY2NjY2O30NCnZhci5mbG9hdCAgICAgICAge2NvbG9yICAgICAgICAgICA6ICMwMDY2MDA7fQ0KdmFyLmFycmF5ICAgICAgICB7Y29sb3IgICAgICAgICAgIDogIzAwMDBjYzt9DQp2YXIub2JqZWN0ICAgICAgIHtjb2xvciAgICAgICAgICAgOiAjOTk2NjAwO30NCnZhci5zdHJpbmcgZW0sDQp2YXIuZmxvYXQgZW0sDQp2YXIuaW50IGVtICAgICAgIHtmb250LXNpemUgICAgICAgOiB4eC1zbWFsbDsNCiAgICAgICAgICAgICAgICAgIGNvbG9yICAgICAgICAgICA6ICM5OTk7fQ0KLS0+DQo8L3N0eWxlPiI7
js|czoxNjQ6IjxzY3JpcHQgdHlwZT0idGV4dC9qYXZhc2NyaXB0Ij4NCjwhLS0NCihmdW5jdGlvbigpew0KIHRyeSB7DQogICBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgibG9hZGluZyIpLnN0eWxlLmRpc3BsYXkgPSAibm9uZSI7DQogfSBjYXRjaCAoZSkge30NCn0pKCk7DQovLy0tPg0KPC9zY3JpcHQ+Ijs=
dl|czowOiIiOw==
posql|czoxMToiLi9wb3NxbC5waHAiOw==
END@*/
