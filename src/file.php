<?php
require_once dirname(__FILE__) . '/error.php';
//-----------------------------------------------------------------------------
/**
 * @name Posql_File
 *
 * The class which collected the file utility for Posql
 *
 * @package   Posql
 * @author    polygon planet <polygon.planet.aqua@gmail.com>
 *---------------------------------------------------------------------------*/
class Posql_File extends Posql_Error {
/**
 * A shortcut of fopen() and fgets() function call
 *
 * Note:
 *   Version 2.10 latest is:
 *     The second argument was removed.
 *     Instead, fseekLine() is used.
 *
 * @see fseekLine
 *
 * @param  string  operation mode
 * @return mixed   stream resource or false
 * @access private
 */
 function fopen($mode){
   $mode = strtolower($mode);
   if (strpos($mode, 'b') === false) {
     $mode .= 'b';
   }
   if ($fp = @fopen($this->path, $mode)) {
     if ($this->isWriteMode($mode)) {
       set_file_buffer($fp, 0);
     }
   } else {
     if (isset($this->path)) {
       $path = $this->path;
     } else {
       $path = null;
     }
     if (!empty($this->_useQuery)
      && !empty($this->lastMethod)
      && (($this->lastMethod === 'drop'
      &&   $this->_ifExistsClause !== 'if_exists')
      ||  ($this->lastMethod === 'create'
      &&   $this->_ifExistsClause !== 'if_not_exists'))) {
       $this->pushError('Cannot open file(%s)', $path);
     }
     $fp = false;
   }
   return $fp;
 }

/**
 * Checks whether fopen()'s mode is writing or not
 *
 * @param  string   fopen()'s mode
 * @param  boolean  whether mode is writing or not
 * @access public
 * @static
 */
 function isWriteMode($mode){
   return strpos($mode, '+') !== false
       || strpos($mode, 'a') !== false
       || strpos($mode, 'w') !== false
       || strpos($mode, 'x') !== false;
 }

/**
 * Optimized method for fgets() function
 *
 * @param  resource stream handle
 * @param  boolean  whether to trim or not
 * @return string   next one line on stream
 * @access private
 */
 function fgets(&$fp, $trim = false){
   /*
   static $has = null;
   if ($has === null) {
     $has = function_exists('stream_get_line')
         && !empty($this->isPHP5);
   }
   if ($has) {
     $line = trim(stream_get_line($fp, $this->MAX, $this->NL)) . $this->NL;
   } else {
     $line = fgets($fp);
   }
   return $trim ? trim($line) : $line;
   */
   //
   //Fixed bug on PHP function stream_get_line().
   //
   // Bug #44607 stream_get_line unable to correctly identify
   //            the "ending" in the stream content.
   // @see  http://bugs.php.net/bug.php?id=44607
   //
   // Bug #43522 stream_get_line eats additional characters.
   // @see  http://bugs.php.net/bug.php?id=43522
   //
   // stream_get_line() fixed since PHP 5.2.7
   // But it is assumed that it does not use it now for stability.
   //
   $line = fgets($fp);
   if ($trim) {
     $line = trim($line);
   }
   return $line;
 }

/**
 * Optimized method for fwrite() function
 *
 * @param  resource stream handle
 * @param  string   the string that is to be written
 * @return number   the number of bytes written, or FALSE on error
 * @access public
 * @static
 */
 function fputs(&$fp, $string){
   $result = fwrite($fp, $string, strlen($string));
   return $result;
 }

/**
 * Seek lines on a file pointer
 *
 * @param  resource  stream handle
 * @param  number    number of lines for moving cursor
 * @return void
 * @access private
 */
 function fseekLine(&$fp, $lines = 0){
   if (is_int($lines)) {
     while (--$lines >= 0) {
       $this->fgets($fp);
     }
   }
 }

/**
 * Appends given string into the file with the specified line number
 *
 * @param  string   filename
 * @param  mixed    string or array to append
 * @param  number   line number to insert which starts from 1
 * @param  string   the string glue to join array
 * @return number   the number of bytes written
 * @access public
 */
 function appendFileLine($filename, $append_data, $line_number, $glue = ''){
   $result = 0;
   if (is_numeric($line_number)) {
     $line_number = $line_number - 0;
     $appends = array();
     if ($append_data === null || is_scalar($append_data)) {
       $appends = array((string)$append_data);
     } else {
       if (!is_array($append_data)) {
         $append_data = (array)$append_data;
       }
       if (empty($append_data)) {
         $append_data = array(null);
       }
       $appends = array_map('strval', $append_data);
     }
     $org_ext = $this->setExt(null);
     $org_path = $this->getPath();
     $this->setPath($filename);
     if ($fp = $this->fopen('r')) {
       $i = 1;
       $nl = '';
       while (!feof($fp)) {
         if ($i == $line_number) {
           $buffer = $this->fgets($fp);
           if ($nl == null) {
             $nl = substr($buffer, -2);
             if (trim($nl) != null) {
               $nl = substr($nl, -1);
               if (trim($nl) != null) {
                 $nl = '';
               }
             }
           }
           $buffer = substr($buffer, 0, -strlen($nl));
           if (count($appends) > 1) {
             $nl = '';
           }
           reset($appends);
           $key = key($appends);
           $appends[$key] = $buffer . $appends[$key] . $nl;
           break;
         } else {
           if ($i === 1) {
             $buffer = $this->fgets($fp);
             $nl = substr($buffer, -2);
             if (trim($nl) != null) {
               $nl = substr($nl, -1);
               if (trim($nl) != null) {
                 $nl = '';
               }
             }
             unset($buffer);
           } else {
             $this->fgets($fp);
           }
         }
         $i++;
       }
       fclose($fp);
       $result = $this->_replaceFileLine($appends, $line_number, $glue);
     }
     $this->setExt($org_ext);
     $this->setPath($org_path);
   }
   return $result;
 }

/**
 * Insert given string into the file with the specified line number
 *
 * @param  string   filename
 * @param  mixed    string or array to insert
 * @param  number   line number to insert which starts from 1
 * @param  string   the string glue to join array
 * @return number   the number of bytes written
 * @access public
 */
 function insertFileLine($filename, $insert_data, $line_number, $glue = ''){
   $result = 0;
   if (is_numeric($line_number)) {
     $line_number = $line_number - 0;
     $inserts = array();
     if ($insert_data === null || is_scalar($insert_data)) {
       $inserts = array((string)$insert_data);
     } else {
       if (!is_array($insert_data)) {
         $insert_data = (array)$insert_data;
       }
       if (empty($insert_data)) {
         $insert_data = array(null);
       }
       $inserts = array_map('strval', $insert_data);
     }
     $org_ext = $this->setExt(null);
     $org_path = $this->getPath();
     $this->setPath($filename);
     if ($fp = $this->fopen('r')) {
       $i = 1;
       $nl = '';
       while (!feof($fp)) {
         if ($i == $line_number) {
           $buffer = $this->fgets($fp);
           if ($nl == null) {
             $nl = substr($buffer, -2);
             if (trim($nl) != null) {
               $nl = substr($nl, -1);
               if (trim($nl) != null) {
                 $nl = '';
               }
             }
           }
           if (count($inserts) > 1) {
             $buffer = substr($buffer, 0, -strlen($nl));
           }
           end($inserts);
           $key = key($inserts);
           $inserts[$key] .= $nl . $buffer;
           break;
         } else {
           if ($i === 1) {
             $buffer = $this->fgets($fp);
             $nl = substr($buffer, -2);
             if (trim($nl) != null) {
               $nl = substr($nl, -1);
               if (trim($nl) != null) {
                 $nl = '';
               }
             }
             unset($buffer);
           } else {
             $this->fgets($fp);
           }
         }
         $i++;
       }
       fclose($fp);
       $result = $this->_replaceFileLine($inserts, $line_number, $glue);
     }
     $this->setExt($org_ext);
     $this->setPath($org_path);
   }
   return $result;
 }

/**
 * Replaces to given string into the file with the specified line number
 *
 * @param  string   filename
 * @param  mixed    replacement as string or array
 * @param  number   line number to replace which starts from 1
 * @param  string   the string glue to join array
 * @return number   the number of bytes written
 * @access public
 */
 function replaceFileLine($filename, $replacement, $line_number, $glue = ''){
   $result = 0;
   if (is_numeric($line_number)) {
     $line_number = $line_number - 0;
     $replacements = array();
     if ($replacement === null || is_scalar($replacement)) {
       $replacements = array((string)$replacement);
     } else {
       if (!is_array($replacement)) {
         $replacement = (array)$replacement;
       }
       if (empty($replacement)) {
         $replacement = array(null);
       }
       $replacements = array_map('strval', $replacement);
     }
     unset($replacement);
     $org_ext = $this->setExt(null);
     $org_path = $this->getPath();
     $this->setPath($filename);
     $nl = '';
     if ($fp = $this->fopen('r')) {
       $buffer = $this->fgets($fp);
       fclose($fp);
       $nl = substr($buffer, -2);
       unset($buffer);
       if (trim($nl) != null) {
         $nl = substr($nl, -1);
         if (trim($nl) != null) {
           $nl = '';
         }
       }
       if (count($replacements) > 1) {
         $nl = '';
       }
       end($replacements);
       $key = key($replacements);
       $replacements[$key] .= $nl;
       $result = $this->_replaceFileLine($replacements, $line_number, $glue);
     }
     $this->setExt($org_ext);
     $this->setPath($org_path);
   }
   return $result;
 }

/**
 * Replaces to given string into the file with the specified line number
 *
 * @param  mixed    replacement as string or array
 * @param  number   line number to replace which starts from 1
 * @param  string   the string glue to join array
 * @return number   the number of bytes written
 * @access private
 */
 function _replaceFileLine($replacement, $line_number, $glue = ''){
   $result = 0;
   if (is_numeric($line_number)) {
     $line_number = $line_number - 0;
     $replacements = array();
     if ($replacement === null || is_scalar($replacement)) {
       $replacements = array((string)$replacement);
     } else {
       if (!is_array($replacement)) {
         $replacement = (array)$replacement;
       }
       if (empty($replacement)) {
         $replacement = array(null);
       }
       $replacements = array_map('strval', $replacement);
     }
     $replacement = '';
     if (is_array($replacements)) {
       $count = count($replacements);
       if ($count === 1) {
         $replacement = array_shift($replacements);
       } else {
         while (--$count >= 0) {
           $replacement .= array_shift($replacements) . $glue;
         }
       }
       $written_bytes = 0;
       $length = strlen($replacement);
       $buffer_size = 0x2000;
       if ($rp = $this->fopen('r')) {
         if ($wp = $this->fopen('r+')) {
           $i = 1;
           while (!feof($rp)) {
             if ($i == $line_number) {
               $this->fgets($rp);
               $buffers = array();
               $buffers[] = fread($rp, $length);
               $written_bytes = $this->fputs($wp, $replacement);
               $buffer_size += $length;
               if (!feof($rp)) {
                 do {
                   $buffers[] = fread($rp, $buffer_size);
                   $this->fputs($wp, array_shift($buffers));
                 } while (!feof($rp));
               }
               $this->fputs($wp, array_shift($buffers));
               $org_user_abort = ignore_user_abort(1);
               ftruncate($wp, ftell($wp));
               ignore_user_abort($org_user_abort);
               $result = $written_bytes;
               break;
             } else {
               $this->fputs($wp, $this->fgets($rp));
             }
             $i++;
           }
           fclose($wp);
         }
         fclose($rp);
       }
     }
   }
   return $result;
 }

/**
 * Parse CSV (Comma-Separated Values) fields as string.
 * Almost compatibly PHP's fgetcsv() function.
 *
 * @link http://www.ietf.org/rfc/rfc4180.txt
 *
 * @param  resource  a valid file pointer
 * @param  string    the field delimiter
 * @param  string    the field enclosure character
 * @return array     an indexed array containing the fields or FALSE
 * @access public
 */
 function fgetcsv(&$fp, $delimiter = ',', $enclosure = '"'){
   $result = false;
   if (!feof($fp)) {
     $line = '';
     while (!feof($fp)) {
       $line .= $this->fgets($fp);
       $quote_count = substr_count($line, $enclosure);
       if ($quote_count % 2 === 0) {
         break;
       }
     }
     $line = rtrim($line);
     if ($line != null) {
       $quoted_delimiter = preg_quote($delimiter, '/');
       $quoted_enclosure = preg_quote($enclosure, '/');
       $pattern = sprintf('/(?:(?<=%s)(?=(?:(?:[^%s]*%s){2})*[^%s]*$)|^)'
                        .  '(?:[^%s,]+|%s(?:[^%s]|%s%s)+%s|)/',
         $quoted_delimiter, $quoted_enclosure, $quoted_enclosure,
         $quoted_enclosure, $quoted_enclosure, $quoted_enclosure,
         $quoted_enclosure, $quoted_enclosure, $quoted_enclosure,
         $quoted_enclosure
       );
       preg_match_all($pattern, $line, $matches);
       if (!empty($matches[0])) {
         $fields = array_shift($matches);
         if (is_array($fields)) {
           $result = array();
           foreach ($fields as $i => $field) {
             if (substr($field, 0, 1) === $enclosure
              && substr($field, -1)  === $enclosure) {
               $field = substr($field, 1, -1);
             }
             $enclosure2 = $enclosure . $enclosure;
             if (strpos($field, $enclosure2) !== false) {
               $field = str_replace($enclosure2, $enclosure, $field);
             }
             $result[] = $field;
           }
         }
       }
     }
   }
   return $result;
 }

/**
 * Returns full path as realpath() function behaves
 *
 * @param  string  file path
 * @param  boolean checks then return valid path or filename
 * @return string  file path of full
 * @access public
 */
 function fullPath($path, $check_exists = false){
   static $backslash = '\\', $slash = '/', $colon = ':';
   $result = '';
   $fullpath = $path;
   $pre0 = substr($path, 0, 1);
   $pre1 = substr($path, 1, 1);
   if ((!$this->isWin && $pre0 !== $slash)
     || ($this->isWin && $pre1 !== $colon)) {
     $fullpath = getcwd() . $slash . $path;
   }
   $fullpath = strtr($fullpath, $backslash, $slash);
   $items = explode($slash, $fullpath);
   $new_items = array();
   foreach ($items as $item) {
     if ($item == null || strpos($item, '.') === 0) {
       if ($item === '..') {
         array_pop($new_items);
       }
       continue;
     }
     $new_items[] = $item;
   }
   $fullpath = implode($slash, $new_items);
   if (!$this->isWin) {
     $fullpath = $slash . $fullpath;
   }
   $result = $fullpath;
   if ($check_exists) {
     clearstatcache();
     if (!@file_exists($result)) {
       $result = false;
     }
   }
   return $result;
 }

/**
 * A handy function like the dirname() of inherited call
 *
 * Note:
 *  Windows: unable to use "*" for filename
 *  Unix:    unable to use "\0" for filename
 *  MacOS:   unable to use ":" for filename
 *
 * @example
 * <code>
 * $posql = new Posql;
 * echo $posql->dirname('C:/tmp');       // output: 'C:/tmp'
 * echo $posql->dirname('C:/tmp/');      // output: 'C:/tmp'
 * echo $posql->dirname('C:/tmp//');     // output: 'C:/tmp'
 * echo $posql->dirname('/tmp/hoge');    // output: '/tmp/hoge'
 * echo $posql->dirname('/tmp/hoge/');   // output: '/tmp/hoge'
 * echo $posql->dirname('/tmp/hoge///'); // output: '/tmp/hoge'
 * </code>
 *
 * @example
 * <code>
 * $posql = new Posql;
 * echo $posql->dirname('C:/path/to/file.ext', 1); // output: 'C:/path/to'
 * echo $posql->dirname('C:/path/to/file.ext', 2); // output: 'C:/path'
 * echo $posql->dirname('C:/path/to/file.ext', 3); // output: 'C:/'
 * echo $posql->dirname('C:/path/to/file.ext', 4); // output: 'C:/'
 * echo $posql->dirname('/path/to/file.ext', 2);   // output: '/path'
 * echo $posql->dirname('/path/to/file.ext', 3);   // output: '/'
 * echo $posql->dirname('/path/to/file.ext', 4);   // output: '/'
 * </code>
 *
 * @param  string  file path to target
 * @param  int     how many hierarchies to up for path
 * @return string  the name of the directory, or FALSE on failure
 * @access public
 * @static
 */
 function dirname($path, $hierarchy = 0){
   static $dummy = "/:*\0";
   $result = false;
   if (is_scalar($path)) {
     if ($path === '/') {
       $result = $path;
     } else {
       $result = dirname($path . $dummy);
       if (is_numeric($hierarchy)) {
         $base = null;
         $prev = null;
         while (--$hierarchy >= 0) {
           $prev = $result;
           $base = basename($result);
           $result = dirname($result);
           if (dirname($result) === $result) {
             break;
           }
         }
         if ($prev != null && $base != null
          && dirname($result) === $result) {
           $result = substr($prev, 0, -strlen($base));
         }
         if (strlen($result) > strlen($path)) {
           $result = $path;
         }
       }
     }
   }
   return $result;
 }

/**
 * Fixes with the suffix the paths
 *
 * @example
 * <code>
 * $posql = new Posql;
 * echo $posql->dirnameWith('/path/to/file');   // output: '/path/to/file/'
 * echo $posql->dirnameWith('path/file.ext');   // output: 'path/file.ext/'
 * echo $posql->dirnameWith('/path/');          // output: '/path/'
 * echo $posql->dirnameWith('/path');           // output: '/path/'
 * echo $posql->dirnameWith('/');               // output: '/'
 * echo $posql->dirnameWith('C:/tmp/a/file');   // output: 'C:/tmp/a/file/'
 * echo $posql->dirnameWith('C:/tmp/file.ext'); // output: 'C:/tmp/file.ext/'
 * echo $posql->dirnameWith('C:/tmp/');         // output: 'C:/tmp/'
 * echo $posql->dirnameWith('C:/tmp');          // output: 'C:/tmp/'
 * echo $posql->dirnameWith('C:');              // output: 'C:/'
 * </code>
 *
 * @param  string  file path to target
 * @param  string  optionally, the suffix (default = '/')
 * @return string  the name of the directory, or NULL on failure
 * @access public
 */
 function dirnameWith($path, $with = '/'){
   $result = null;
   if (is_scalar($path)) {
     if (!is_scalar($with)) {
       $result = $this->dirname($path);
     } else {
       $result = rtrim($this->dirname($path), $with) . $with;
     }
   }
   return $result;
 }

/**
 * Returns the extension that added the dot(.)
 *
 * @param  string  extension
 * @param  boolean whether the extension of all lowercase or not
 * @return string  extension that added the period
 * @access public
 * @static
 */
 function toExt($ext = null, $tolower = false){
   if ($ext == null && isset($this->ext)) {
     $ext = $this->ext;
   }
   if ($ext != null) {
     $ext = '.' . ltrim($ext, '.');
   }
   if ($tolower) {
     $ext = strtolower($ext);
   }
   return $ext;
 }

/**
 * Get the extension name from filename without dot
 *
 * @example
 * <code>
 * $posql = new Posql;
 * echo $posql->getExtensionName("/path/to/file.ext"); // output: "ext"
 * echo $posql->getExtensionName(".ext");              // output: "ext"
 * echo $posql->getExtensionName("..ext");             // output: "ext"
 * echo $posql->getExtensionName("ext2.ext1.ext");     // output: "ext"
 * echo $posql->getExtensionName("ext.");              // output: ""
 * echo $posql->getExtensionName("ext");               // output: ""
 * echo $posql->getExtensionName("file.ext");          // output: "ext"
 * </code>
 *
 * @param  string   filename
 * @param  boolean  whether result returned lower-case
 * @return string   the extension name
 * @access public
 * @static
 */
 function getExtensionName($filename, $tolower = false){
   $ext = null;
   $base = trim(basename($filename));
   $tail = strrchr($base, '.');
   if ($tail == null) {
     $ext = '';
   } else {
     if ($tail === '.') {
       $ext = '';
     } else {
       $ext = substr($tail, 1);
     }
   }
   if ($tolower) {
     $ext = strtolower($ext);
   }
   return $ext;
 }

/**
 * Assigns the mode of locking for constant LOCK_*
 *
 * @param  number  the mode of locking
 * @return number  assigned locking mode
 * @access private
 */
 function assignLockMode($mode){
   switch ((int)$mode) {
     case 1:
     case LOCK_SH:
     case $this->LOCK_SH:
         $mode = $this->LOCK_SH;
         break;
     case 2:
     case LOCK_EX:
     case $this->LOCK_EX:
         $mode = $this->LOCK_EX;
         break;
     case 3:
     case LOCK_UN:
     case 4:
     case LOCK_NB:
     case $this->LOCK_NONE:
     default:
         $mode = $this->LOCK_NONE;
         break;
   }
   $mode = (int)$mode;
   return $mode;
 }

/**
 * Not implemented function
 */
 function setResultStorage($mode, $storage = null){
   //TODO: The storage of the result row should be able to be set
   //      in the future.
   switch ($mode) {
     case 'auto':    //use auto
     case 'tmpfile': //use tmpfile();
     case 'memory':  //use memory
     case 'file':    //use file
   }
 }

/**
 * Get the unique filename which is able to create actually
 *
 * @param  string  target directory name
 * @param  string  string which is preppended to the filename
 * @return string  the unique filename, or FALSE on failure
 * @access public
 */
 function getUniqueFilename($dir, $prefix = null){
   $result = false;
   if ($dir != null && is_string($dir)) {
     $dir = $this->fullPath($dir, true);
     if ($dir) {
       $dir = $this->dirname($dir);
       if ($prefix === null || !is_scalar($prefix)) {
         $prefix = sprintf('%s_dummy_%s', $this->getClassName(), $this->id);
         $prefix = strtolower($prefix);
       }
       do {
         clearstatcache();
         $dirname = sprintf('%s_%.08f', $prefix, $this->getMicrotime());
         $dirname = rawurlencode($dirname);
         $path = sprintf('%s/%s', $dir, $dirname);
       } while (@file_exists($path));
       $result = $dirname;
     }
   }
   return $result;
 }

/**
 * Return the temporary directory name which  is used on locking the database
 *
 * @param  void
 * @return string  the temporary directory name
 * @access private
 */
 function getLockDirName(){
   $result = sprintf('%s/.%s.lock',
     dirname($this->path),
     $this->getDatabaseName()
   );
   return $result;
 }

/**
 * Check whether the current directory is able to lock
 *
 * @param  void
 * @return boolean  whether the current directory is able to lock or not
 * @access private
 */
 function canLockDatabase(){
   $result = false;
   if ($this->path != null) {
     $perms = 0777;
     $dir = dirname($this->path);
     $uniqid = $this->getUniqueFilename($dir);
     if ($uniqid) {
       $path = sprintf('%s/%s', $dir, $uniqid);
       if (@mkdir($path, $perms) && @rmdir($path)) {
         $result = true;
       }
     }
   }
   return $result;
 }

/**
 * Tries to lock a database.
 * It waits maximal timeout seconds for the lock.
 * Clears the dead lock when the timeout passes.
 *
 * @param  void
 * @return void
 * @access private
 */
 function lockDatabase(){
   if ($this->autoLock) {
     $perms = 0777;
     $lock_dir = $this->getLockDirName();
     $timeout = $this->getDeadLockTimeout();
     while (true) {
       $locked = @mkdir($lock_dir, $perms);
       if ($locked) {
         $mask = @umask(0);
         @chmod($lock_dir, $perms);
         @umask($mask);
         break;
       }
       clearstatcache();
       if (@is_dir($lock_dir)) {
         if (!empty($this->_inTransaction)) {
           break;
         } else {
           $mtime = @filemtime($lock_dir);
           if (is_numeric($mtime) && (time() - 60 * $timeout) > $mtime) {
             $this->unlockDatabase();
             continue;
           }
         }
       }
       $this->sleep();
     }
   }
 }

/**
 * Unlocks a file
 *
 * @param  void
 * @return void
 * @access private
 */
 function unlockDatabase(){
   if ($this->autoLock) {
     $lock_dir = $this->getLockDirName();
     if (empty($this->_inTransaction)) {
       @rmdir($lock_dir);
     }
   }
 }

/**
 * Checks whether the table is locking
 * If the argument is not given it will interpreted
 *   as locked by either LOCK_EX or LOCK_SH
 *
 * @param  string  table name
 * @param  number  the mode of locking
 * @return boolean locking or not
 * @access private
 */
 function isLock($table, $mode = null){
   $result = false;
   if ($this->isLockAll()) {
     $result = true;
   } else {
     $mode_org = $mode;
     $mode = $this->assignLockMode($mode);
     $tname = $this->encodeKey($table);
     $tname_symbol = $tname . ':';
     if ($fp = $this->fopen('r')) {
       $this->lockDatabase();
       $this->fseekLine($fp, 1);
       $line = $this->fgets($fp);
       fclose($fp);
       $this->unlockDatabase();
       $symbol_pos = strpos($line, $tname_symbol);
       if ($symbol_pos === false
        || strpos($line, '@') === false) {
         if (empty($this->meta[$tname])) {
           $this->pushError('Not exists the table(%s)', $table);
         } else {
           $this->pushError('Cannot check by isLock(%s)', $table);
         }
       } else {
         $lock_pos = $symbol_pos + strlen($tname_symbol);
         $lock_buffer = substr($line, $lock_pos, 10);
         list($lock_mode, $lock_id) = explode('@', $lock_buffer);
         $lock_mode = $this->assignLockMode($lock_mode);
         if ($lock_id === $this->id) {
           $result = false;
         } else {
           if ($lock_id === $this->toUniqId(0)) {
             $result = false;
           } else {
             if ($mode_org === null) {
               if ($lock_mode !== $this->LOCK_NONE) {
                 $result = true;
               }
             } else {
               if ($lock_mode === $mode) {
                 $result = true;
               }
             }
           }
         }
       }
     }
   }
   if ($result) {
     if (empty($this->autoLock)) {
       $this->autoLock = true;
     }
   }
   return $result;
 }

/**
 * It is checked whether the table is locked as LOCK_EX
 *
 * @param  string   table name
 * @return boolean  locked or not locked
 * @access private
 */
 function isLockEx($table){
   $result = $this->isLock($table, $this->LOCK_EX);
   return $result;
 }

/**
 * It is checked whether the table is locked as LOCK_SH
 *
 * @param  string   table name
 * @return boolean  locked or not locked
 * @access private
 */
 function isLockSh($table){
   $result = $this->isLock($table, $this->LOCK_SH);
   return $result;
 }

/**
 * It is checked whether all tables are locked
 * If the argument is not given it will interpreted
 *   as locked by either LOCK_EX or LOCK_SH
 *
 * @param  number   the mode of locking
 * @return boolean  all locked or not locked
 * @access private
 */
 function isLockAll($mode = null){
   $result = false;
   $mode_org = $mode;
   $mode = $this->assignLockMode($mode);
   if ($fp = $this->fopen('r')) {
     $this->lockDatabase();
     $header = $this->fgets($fp, true);
     fclose($fp);
     $this->unlockDatabase();
     $buffer = substr($header, -10);
     $at_pos = strpos($buffer, '@');
     if ($at_pos === false) {
       $this->pushError('Database might be broken');
     } else {
       list($lock_mode, $lock_id) = explode('@', $buffer);
       $lock_mode = $this->assignLockMode($lock_mode);
       if ($lock_id === $this->id) {
         $result = false;
       } else {
         if ($lock_id === $this->toUniqId(0)) {
           $result = false;
         } else {
           if ($mode_org === null) {
             if ($lock_mode !== $this->LOCK_NONE) {
               $result = true;
             }
           } else {
             if ($lock_mode === $mode) {
               $result = true;
             }
           }
         }
       }
     }
   }
   if ($result) {
     if (empty($this->autoLock)) {
       $this->autoLock = true;
     }
   }
   return $result;
 }

/**
 * It is checked whether all tables are locked as LOCK_EX
 *
 * @param  void
 * @return boolean  all locked or not locked
 * @access private
 */
 function isLockExAll(){
   $result = $this->isLockAll($this->LOCK_EX);
   return $result;
 }

/**
 * It is checked whether all tables are locked as LOCK_SH
 *
 * @param  void
 * @return boolean  all locked or not locked
 * @access private
 */
 function isLockShAll(){
   $result = $this->isLockAll($this->LOCK_SH);
   return $result;
 }

/**
 * Locks to all tables
 * Other processes stand by while locking
 *
 * @param  number   the mode of locking
 * @return boolean  success or not
 * @access private
 */
 function lockAll($mode){
   $result = false;
   $this->userAbort = @ignore_user_abort(1);
   while (!$this->hasError()) {
     $sleep = false;
     if (!$this->_lockAll($mode)) {
       $sleep = true;
     } else {
       $current_id = $this->getLockIdAll();
       if ($current_id === $this->id) {
         $result = true;
         break;
       } else {
         $sleep = true;
       }
     }
     if ($sleep) {
       $this->sleep();
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function _lockAll($mode){
   $result = false;
   if (!empty($this->_inTransaction)) {
     $result = true;
   } else {
     $mode     = $this->assignLockMode($mode);
     $uniqid   = $this->id;
     $id_len   = strlen($uniqid);
     $lock_pos = strlen($this->getHeader());
     $id_pos   = $lock_pos + 2;
     $puts     = sprintf('%d@%s', $mode, $uniqid);
     if ($this->isLockAll()) {
       $result = false;
     } else {
       if ($fp = $this->fopen('r+')) {
         $this->lockDatabase();
         $do_write = true;
         fseek($fp, $id_pos);
         $lock_id = fread($fp, $id_len);
         if ($lock_id === $this->toUniqId(0)) {
           $do_write = true;
         } else {
           if ($lock_id === $this->id) {
             $do_write = true;
           } else {
             $do_write = false;
           }
         }
         if ($do_write) {
           fseek($fp, $lock_pos);
           $this->fputs($fp, $puts);
           $result = true;
         }
         fclose($fp);
         $this->unlockDatabase();
       }
       if ($result) {
         $current_id = $this->getLockIdAll();
         if ($current_id !== $this->id) {
           $result = false;
         }
       }
     }
   }
   return $result;
 }

/**
 * Locks to all tables as LOCK_EX
 * Other processes stand by while locking
 *
 * @param  void
 * @return boolean  success or not
 * @access private
 */
 function lockExAll(){
   $result = $this->lockAll($this->LOCK_EX);
   return $result;
 }

/**
 * Locks to all tables as LOCK_SH
 * Other processes stand by while locking
 *
 * @param  void
 * @return boolean  success or not
 * @access private
 */
 function lockShAll(){
   $result = $this->lockAll($this->LOCK_SH);
   return $result;
 }

/**
 * Unlocks to all tables
 *
 * @param  boolean  whether it compulsorily unlocks
 *                  it regardless of the state of the lock
 * @return boolean  success or not
 * @access private
 */
 function unlockAll($force = false){
   $result = false;
   while (!$this->hasError()) {
     if (!$this->_unlockAll($force)) {
       $this->sleep();
     } else {
       $result = true;
       break;
     }
   }
   if (isset($this->userAbort)) {
     @ignore_user_abort($this->userAbort);
   }
   return $result;
 }

/**
 * @access private
 */
 function _unlockAll($force = false){
   $result = false;
   if (!empty($this->_inTransaction)) {
     $result = true;
   } else {
     $uniqid   = $this->toUniqId(0);
     $id_len   = strlen($uniqid);
     $lock_pos = strlen($this->getHeader());
     $id_pos   = $lock_pos + 2;
     $puts     = sprintf('%d@%s', $this->LOCK_NONE, $uniqid);
     if (!$force && $this->isLockAll()) {
       $result = false;
     } else {
       if ($fp = $this->fopen('r+')) {
         $this->lockDatabase();
         $do_write = true;
         fseek($fp, $id_pos);
         $lock_id = fread($fp, $id_len);
         if ($lock_id === $this->toUniqId(0)) {
           $do_write = true;
         } else {
           if ($lock_id === $this->id) {
             $do_write = true;
           } else {
             $do_write = false;
           }
         }
         if ($force) {
           $do_write = true;
         }
         if ($do_write) {
           fseek($fp, $lock_pos);
           $this->fputs($fp, $puts);
           $result = true;
         }
         fclose($fp);
         $this->unlockDatabase();
       }
     }
   }
   return $result;
 }

/**
 * Locks to one table
 * Other processes stand by while locking
 *
 * @param  string  table name
 * @param  number  the mode of locking
 * @return boolean success or not
 * @access private
 */
 function lock($table, $mode){
   $result = false;
   $this->userAbort = @ignore_user_abort(1);
   while (!$this->hasError()) {
     $sleep = false;
     if (!$this->_lock($table, $mode)) {
       $sleep = true;
     } else {
       $current_id = $this->getLockId($table);
       if ($current_id === $this->id) {
         $result = true;
         break;
       } else {
         $sleep = true;
       }
     }
     if ($sleep) {
       $this->sleep();
     }
   }
   return $result;
 }

/**
 * @access private
 */
 function _lock($table, $mode){
   $result = false;
   if (!empty($this->_inTransaction)) {
     $result = true;
   } else {
     $tname  = $this->encodeKey($table);
     $symbol = $tname . ':';
     $mode   = $this->assignLockMode($mode);
     $uniqid = $this->toUniqId($this->id);
     $id_len = strlen($uniqid);
     $puts   = sprintf('%d@%s', $mode, $uniqid);
     $sleep  = false;
     switch ($mode) {
       case $this->LOCK_SH:
           if ($this->isLockAll() || $this->isLockEx($table)) {
             $sleep = true;
           }
           break;
       case $this->LOCK_EX:
           if ($this->isLockAll() || $this->isLock($table)) {
             $sleep = true;
           }
           break;
       default:
           break;
     }
     if ($sleep) {
       $result = false;
     } else {
       if ($fp = $this->fopen('r+')) {
         $this->lockDatabase();
         $do_write = true;
         $head = $this->fgets($fp);
         $line = $this->fgets($fp);
         $symbol_pos = strpos($line, $symbol);
         if ($symbol_pos === false
          || strpos($line, '@') === false) {
           if (isset($this->meta[$tname])) {
             $this->pushError('Cannot lock table(%s)', $table);
           } else {
             $this->pushError('Not exists the table(%s)', $table);
           }
           $result = false;
         } else {
           $lock_pos = strlen($head) + strlen($symbol) + $symbol_pos;
           $id_pos   = $lock_pos + 2;
           fseek($fp, $id_pos);
           $lock_id = fread($fp, $id_len);
           if ($lock_id === $this->toUniqId(0)) {
             $do_write = true;
           } else {
             if ($lock_id === $this->id) {
               $do_write = true;
             } else {
               $do_write = false;
             }
           }
           if ($do_write) {
             fseek($fp, $lock_pos);
             $this->fputs($fp, $puts);
             $result = true;
           }
         }
         fclose($fp);
         $this->unlockDatabase();
       }
       if ($result) {
         $current_id = $this->getLockId($table);
         if ($current_id !== $this->id) {
           $result = false;
         }
       }
     }
   }
   return $result;
 }

/**
 * Locks to one table as LOCK_EX
 * Other processes stand by while locking
 *
 * @param  string  table name
 * @return boolean success or not
 * @access private
 */
 function lockEx($table){
   $result = $this->lock($table, $this->LOCK_EX);
   return $result;
 }

/**
 * Locks to one table as LOCK_SH
 * Other processes stand by while locking
 *
 * @param  string   table name
 * @return boolean  success or not
 * @access private
 */
 function lockSh($table){
   $result = $this->lock($table, $this->LOCK_SH);
   return $result;
 }

/**
 * Unlocks to the one table
 *
 * @param  string   table name
 * @param  boolean  whether it compulsorily unlocks
 *                  it regardless of the state of the lock
 * @return boolean  success or not
 * @access private
 */
 function unlock($table, $force = false){
   $result = false;
   while (!$this->hasError()) {
     if (!$this->_unlock($table, $force)) {
       $this->sleep();
     } else {
       $result = true;
       break;
     }
   }
   if (isset($this->userAbort)) {
     @ignore_user_abort($this->userAbort);
   }
   return $result;
 }

/**
 * @access private
 */
 function _unlock($table, $force = false){
   $result = false;
   if (!empty($this->_inTransaction)) {
     $result = true;
   } else {
     $tname  = $this->encodeKey($table);
     $symbol = $tname . ':';
     $uniqid = $this->toUniqId(0);
     $id_len = strlen($uniqid);
     $puts   = sprintf('%d@%s', $this->LOCK_NONE, $uniqid);
     $sleep  = false;
     if (!$force && $this->isLockAll()) {
       $sleep = true;
     }
     if (!$force && $this->isLock($table)) {
       $sleep = true;
     }
     if ($sleep) {
       $result = false;
     } else {
       if ($fp = $this->fopen('r+')) {
         $this->lockDatabase();
         $do_write = true;
         $head = $this->fgets($fp);
         $line = $this->fgets($fp);
         $symbol_pos = strpos($line, $symbol);
         if ($symbol_pos === false
          || strpos($line, '@') === false) {
           $this->pushError('Cannot unlock the table(%s)', $table);
           $result = false;
         } else {
           $lock_pos = strlen($head) + strlen($symbol) + $symbol_pos;
           $id_pos   = $lock_pos + 2;
           fseek($fp, $id_pos);
           $lock_id = fread($fp, $id_len);
           if ($lock_id === $this->toUniqId(0)) {
             $do_write = true;
           } else {
             if ($lock_id === $this->id) {
               $do_write = true;
             } else {
               $do_write = false;
             }
           }
           if ($force) {
             $do_write = true;
           }
           if ($do_write) {
             fseek($fp, $lock_pos);
             $this->fputs($fp, $puts);
             $result = true;
           }
         }
         fclose($fp);
         $this->unlockDatabase();
       }
     }
   }
   return $result;
 }

/**
 * Lock all tables by using lock() and lockAll() methods.
 * Other processes stand by while locking
 *
 * @param  number   the mode of locking
 * @return boolean  success or not
 * @access private
 */
 function lockAllTables($mode){
   $result = false;
   $meta = $this->getMeta();
   if ($meta != null && is_array($meta)) {
     $this->_lockedTables = array();
     foreach (array_keys($meta) as $tname) {
       $this->_lockedTables[] = $this->decodeKey($tname);
     }
     foreach ($this->_lockedTables as $table) {
       if (!$this->lock($table, $mode)) {
         $this->pushError('Failed to lock table(%s)', $table);
         break;
       }
     }
     if ($this->hasError()) {
       foreach ($this->_lockedTables as $table) {
         $this->unlock($table);
       }
       $this->_lockedTables = array();
     } else {
       if ($this->lockAll($mode)) {
         $result = true;
       }
     }
   }
   return $result;
 }

/**
 * Lock all tables as LOCK_EX by using lock() and lockAll() methods
 * Other processes stand by while locking
 *
 * @param  void
 * @return boolean  success or not
 * @access private
 */
 function lockExAllTables(){
   $result = $this->lockAllTables($this->LOCK_EX);
   return $result;
 }

/**
 * Lock all tables as LOCK_SH by using lock() and lockAll() methods
 * Other processes stand by while locking
 *
 * @param  void
 * @return boolean  success or not
 * @access private
 */
 function lockShAllTables(){
   $result = $this->lockAllTables($this->LOCK_SH);
   return $result;
 }

/**
 * Unlock all tables by using unlock() and unlockAll() methods
 *
 * @param  boolean  whether it compulsorily unlocks
 *                  it regardless of the state of the lock
 * @return boolean  success or not
 * @access private
 */
 function unlockAllTables($force = false){
   $result = false;
   if (!empty($this->_lockedTables) && is_array($this->_lockedTables)) {
     while ($table = array_pop($this->_lockedTables)) {
       $this->unlock($table, $force);
     }
     $this->unlockAll($force);
     if (!$this->hasError()) {
       $result = true;
     }
   }
   return $result;
 }

/**
 * Return the locking instance object id
 *
 * @param  string   target table name
 * @return string   the locking object id
 * @access private
 */
 function getLockId($table){
   $result = false;
   while ($this->isLockAll()) {
     $this->sleep();
   }
   if (!$this->hasError()) {
     $tname = $this->encodeKey($table);
     $symbol = $tname . ':';
     if ($fp = $this->fopen('r')) {
       $this->lockDatabase();
       $this->fseekLine($fp, 1);
       $line = $this->fgets($fp);
       fclose($fp);
       $this->unlockDatabase();
       $symbol_pos = strpos($line, $symbol);
       if ($symbol_pos === false
        || strpos($line, '@') === false) {
         if (empty($this->meta[$tname])) {
           $this->pushError('Not exists the table(%s)', $table);
         } else {
           $this->pushError('Cannot check id by getLockId(%s)', $table);
         }
       } else {
         $lock_pos = strlen($symbol) + $symbol_pos;
         $lock_buffer = substr($line, $lock_pos, 10);
         list(, $lock_id) = explode('@', $lock_buffer);
         $result = $lock_id;
       }
     }
   }
   return $result;
 }

/**
 * Return the locking instance object id as locking all tables
 *
 * @param  void
 * @return string   the locking object id
 * @access private
 */
 function getLockIdAll(){
   $result = false;
   while ($this->isLockAll()) {
     $this->sleep();
   }
   $lock_pos = strlen($this->getHeader());
   $id_pos   = $lock_pos + 2;
   $id_len   = strlen($this->id);
   if (!$this->hasError()) {
     if ($fp = $this->fopen('r')) {
       $this->lockDatabase();
       fseek($fp, $id_pos);
       $result = fread($fp, $id_len);
       fclose($fp);
       $this->unlockDatabase();
     }
   }
   return $result;
 }
}
