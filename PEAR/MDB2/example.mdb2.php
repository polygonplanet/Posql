<?php
//
// Posql sample script using PEAR::MDB2 driver
//
//    {{{ internal settings

// filename of Posql class
define('POSQL_PATH', '../../src/posql.php');

// posql project page's url (Japanese)
define('POSQL_PROJECT_JP_URL', 'http://sourceforge.jp/projects/posql/');

// posql project page's url (English)
define('POSQL_PROJECT_EN_URL', 'http://sourceforge.net/projects/posql/');

// posql index catalog list url (Japanese)
define('POSQL_CATALOG_JP_URL', 'http://feel.happy.nu/doc/posql/');

// posql index catalog list url (English)
define('POSQL_CATALOG_EN_URL', 'http://feel.happy.nu/doc/posql/en/');

//    }}} 
//    {{{ configuration

// Settings the error report.
defined('E_STRICT')     or define('E_STRICT',     2048);
defined('E_DEPRECATED') or define('E_DEPRECATED', 8192);
error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
@ini_set('display_errors', 1);

// send Content-Type header
header('Content-Type: text/html; charset=UTF-8');
ob_implicit_flush(1);

//    }}} 
//    {{{ begin example script

// require PEAR::MDB2
require_once 'MDB2.php';

// require non standard package "Posql" driver
require_once 'Driver/posql.php';

// require the "Posql" class
require_once POSQL_PATH;

// sets DSN (Data Source Name), it is similar to the SQLite package
$dsn = 'posql:///__sample_mdb2__';
// or
//$dsn = array(
//  'database' => '/path/to/database_name',
//  'phptype'  => 'posql'
//);


// try connect
$mdb2 = & MDB2::connect($dsn);
// or 
//$mdb2 = & MDB2::factory($dsn);
// or 
//$mdb2 = & MDB2::singleton($dsn);

if (PEAR::isError($mdb2)) {
  abort($mdb2); // this script is using original method for debug.
  // use the following sample code if you want to use PEAR::MDB2 API methods.
  //die($mdb2->toString());
  // or
  //die($mdb2->getUserInfo());
  // or
  //die($mdb2->getMessage());
}

// set the fetches mode as associate array
$mdb2->setFetchMode(MDB2_FETCHMODE_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Posql sample script using PEAR::MDB2 driver</title>
<style type="text/css">
caption {font-weight     : bold}
a       {text-decoration : underline}
a:hover {text-decoration : none}
.sub    {font-size       : 80%}
</style>
</head>
<body>
<h1>Posql for PEAR::MDB2 - <span class="sub">sample script</span></h1>
<hr>
<dl>
<?php
$options = array(
  'version' => 'Posql Version',
  'charset' => 'Posql Internal Encoding',
  'path'    => 'Current Database Path',
  'engine'  => 'Expression Engine'
);
foreach ($options as $option => $desc) {
  printf('<dt>%s</dt><dd>%s</dd>', $desc, $mdb2->getOption($option));
}

?>
</dl>
<?php

// Drop sample table if existed
$sql = "DROP TABLE IF EXISTS bookmark";
$res = $mdb2->exec($sql);
if (PEAR::isError($res)) {
  abort($res);
}
debug($res, $sql);

// Creates sample table
$sql = <<<SQL
CREATE TABLE IF NOT EXISTS bookmark (
  id    INTEGER PRIMARY KEY,
  url   VARCHAR(255),
  title TEXT
);
SQL;
$res = $mdb2->exec($sql);
if (PEAR::isError($res)) {
  abort($res);
}
debug($res, $sql);

// Inserts some items
$items = array(
  POSQL_PROJECT_JP_URL      => 'Posql Project Page',
  POSQL_PROJECT_EN_URL      => 'Posql Project Page(en)',
  POSQL_CATALOG_JP_URL      => 'Posql Index Catalog',
  POSQL_CATALOG_EN_URL      => 'Posql Index Catalog(en)',
  'http://www.example.com/' => 'example.com',
  'http://www.example.com/' => 'quote(\')in a string'
);
$sql = "INSERT INTO bookmark (url, title) VALUES(?, ?)";
$stmt = $mdb2->prepare($sql);
foreach ($items as $url => $title) {
  $res = $stmt->execute(array($url, $title));
  if (PEAR::isError($res)) {
    abort($res);
  }
  debug($mdb2->affectedRows(),
        sprintf(str_replace('?', '%s', $sql), $url, $title));
}
$stmt->free();

// get next/last sequence id
debug($mdb2->lastInsertID(), '$mdb2->lastInsertID()');
debug($mdb2->nextID(), '$mdb2->nextID()');

// Simple SELECT with output
$sql = "SELECT * FROM bookmark WHERE 1 = 1";
$res = $mdb2->query($sql);
if (PEAR::isError($res)) {
  abort($res);
}

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$res->numRows()) {
    ?>
    <tr><td>No results</td></tr>
    <?php
  } else {
    $i = 0;
    $colspan = 1;
    while ($row = $res->fetchRow()) {
      echo '<tr>';
      if ($i === 0) {
        $colspan = count($row);
        foreach (array_keys($row) as $col) {
          printf('<th>%s</th>', htmlspecialchars($col));
        }
        echo '</tr><tr>';
      }
      foreach ($row as $col => $val) {
        printf('<td>%s</td>', htmlspecialchars($val));
      }
      echo "<tr>\n";
      $i++;
    }
  }
  ?>
  <tr>
    <td colspan="<?php echo $colspan; ?>">
      <?php
      debug($res->numRows(), '$res->numRows()');
      debug($res->numCols(), '$res->numCols()');
      ?>
    </td>
  </tr>
</table>
<?php

// Test for UPDATE and affectedRows()
$sql = "UPDATE bookmark SET title = '(modified)' WHERE url LIKE '%example%'";
$res = $mdb2->exec($sql);
debug($res, $sql);

// it will be same values
debug($mdb2->affectedRows(), '$mdb2->affectedRows()');

// Simple SELECT after UPDATE with output
$sql = "SELECT * FROM bookmark WHERE title LIKE '%modified%'";
$res = $mdb2->query($sql);
if (PEAR::isError($res)) {
  abort($res);
}

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$res->numRows()) {
    ?>
    <tr><td>No results</td></tr>
    <?php
  } else {
    $i = 0;
    while ($row = $res->fetchRow()) {
      echo '<tr>';
      if ($i === 0) {
        foreach (array_keys($row) as $col) {
          printf('<th>%s</th>', htmlspecialchars($col));
        }
        echo '</tr><tr>';
      }
      foreach ($row as $col => $val) {
        printf('<td>%s</td>', htmlspecialchars($val));
      }
      echo "<tr>\n";
      $i++;
    }
  }
  ?>
</table>
<?php

// Drop other table if existed
$sql = "DROP TABLE IF EXISTS bookmark_en";
$res = $mdb2->exec($sql);
if (PEAR::isError($res)) {
  abort($res);
}
debug($res, $sql);

// Creates other table
$sql = <<<SQL
CREATE TABLE IF NOT EXISTS bookmark_en (
  en_id    INTEGER,
  en_url   VARCHAR(255) DEFAULT '',
  en_title TEXT,
  PRIMARY KEY(en_id)
);
SQL;
$res = $mdb2->exec($sql);
if (PEAR::isError($res)) {
  abort($res);
}
debug($res, $sql);

// Try INSERT and SELECT sub-query
$sql = <<<SQL
INSERT INTO bookmark_en (en_url, en_title) 
    SELECT 
           CASE url
                WHEN 'http://sourceforge.jp/projects/posql/'
                THEN 'http://en.sourceforge.jp/projects/posql/'
                WHEN 'http://www.example.com/'
                THEN 'http://www.example.net/'
                ELSE url
           END AS a, 
           CASE WHEN title LIKE 'Posql Project%' 
                THEN 'Posql Project Page - English'
                WHEN title LIKE '%(en)%'
                THEN REPLACE(title, '(en)', ' - English')
                ELSE title
           END AS b
    FROM bookmark 
    WHERE 1 = 1;
SQL;
$res = $mdb2->query($sql);
if (PEAR::isError($res)) {
  abort($res);
}
?>
<div style="border:2px solid gray;margin:6px;padding:6px">
  <pre><?php echo htmlspecialchars($sql); ?></pre>
  <div>
    <?php
    debug($mdb2->affectedRows(), '$mdb2->affectedRows()');
    ?>
  </div>
</div>
<?php

// Simple SELECT after INSERT with output
$sql = "SELECT bookmark_en.* FROM bookmark_en WHERE 1 = 1";
$res = $mdb2->query($sql);
if (PEAR::isError($res)) {
  abort($res);
}

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$res->numRows()) {
    ?>
    <tr><td>No results</td></tr>
    <?php
  } else {
    $i = 0;
    while ($row = $res->fetchRow()) {
      echo '<tr>';
      if ($i === 0) {
        foreach (array_keys($row) as $col) {
          printf('<th>%s</th>', htmlspecialchars($col));
        }
        echo '</tr><tr>';
      }
      foreach ($row as $col => $val) {
        printf('<td>%s</td>', htmlspecialchars($val));
      }
      echo "<tr>\n";
      $i++;
    }
  }
  ?>
</table>
<?php

// try query which join on 2 tables
$sql = <<<SQL
SELECT a.url, b.en_url, a.title, b.en_title 
FROM bookmark AS a 
     LEFT JOIN bookmark_en AS b
     ON a.id = b.id;
SQL;
$res = $mdb2->query($sql);
if (PEAR::isError($res)) {
  abort($res);
}

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$res->numRows()) {
    ?>
    <tr><td>No results</td></tr>
    <?php
  } else {
    $i = 0;
    while ($row = $res->fetchRow()) {
      echo '<tr>';
      if ($i === 0) {
        foreach (array_keys($row) as $col) {
          printf('<th>%s</th>', htmlspecialchars($col));
        }
        echo '</tr><tr>';
      }
      foreach ($row as $col => $val) {
        printf('<td>%s</td>', htmlspecialchars($val));
      }
      echo "<tr>\n";
      $i++;
    }
  }
  ?>
</table>
<?php

// Try and begin transaction
$res = $mdb2->beginTransaction();
if (PEAR::isError($res)) {
  abort($res);
}
debug($res, '$mdb2->beginTransaction()');

// Delete all records
$sql = "DELETE FROM bookmark WHERE 1 = 1";
$res = $mdb2->exec($sql);
if (PEAR::isError($res)) {
  abort($res);
}
debug($res, $sql);

// check whether the table is empty
$sql = "SELECT * FROM bookmark WHERE 1 = 1";
$res = $mdb2->query($sql);
if (PEAR::isError($res)) {
  abort($res);
}

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$res->numRows()) {
    ?>
    <tr><td width="350">No results</td></tr>
    <?php
  } else {
    $i = 0;
    while ($row = $res->fetchRow()) {
      echo '<tr>';
      if ($i === 0) {
        foreach (array_keys($row) as $col) {
          printf('<th>%s</th>', htmlspecialchars($col));
        }
        echo '</tr><tr>';
      }
      foreach ($row as $col => $val) {
        printf('<td>%s</td>', htmlspecialchars($val));
      }
      echo "<tr>\n";
      $i++;
    }
  }
  ?>
</table>
<?php

// rollback
$res = $mdb2->rollback();
if (PEAR::isError($res)) {
  abort($res);
}
debug($res, '$mdb2->rollback()');

// check whether the table was rollbacked
$sql = "SELECT * FROM bookmark WHERE 1 = 1";
$res = $mdb2->query($sql);
if (PEAR::isError($res)) {
  abort($res);
}

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$res->numRows()) {
    ?>
    <tr><td>No results</td></tr>
    <?php
  } else {
    $i = 0;
    while ($row = $res->fetchRow()) {
      echo '<tr>';
      if ($i === 0) {
        foreach (array_keys($row) as $col) {
          printf('<th>%s</th>', htmlspecialchars($col));
        }
        echo '</tr><tr>';
      }
      foreach ($row as $col => $val) {
        printf('<td>%s</td>', htmlspecialchars($val));
      }
      echo "<tr>\n";
      $i++;
    }
  }
  ?>
</table>
<?php

// try UNION statement
$sql = <<<SQL
SELECT A.id ID, A.url URL,
    TRIM(TRAILING ' - English' FROM A.title) TITLE,
    CASE WHEN POSITION('English' IN A.title)
         THEN 'English'
         ELSE 'Japanese'
    END LANG
FROM (
    SELECT be.en_id id, be.en_url url, be.en_title title FROM bookmark_en be
    UNION
    SELECT bj.id, bj.url, bj.title FROM bookmark bj
    UNION
    SELECT 80 id, 'http://php.net/' url, 'php.net - English' title
    UNION
    SELECT 99 id, 'http://google.com/' url, 'google - English' title
    UNION
    SELECT 80 id, 'http://jp.php.net/' url, 'php.net' title
    UNION
    SELECT 99 id, 'http://google.co.jp/' url, 'google' title
) A
WHERE A.title NOT LIKE '%(%)%'
ORDER BY A.id DESC, A.url ASC
SQL;
$res = $mdb2->query($sql);
if (PEAR::isError($res)) {
  abort($res);
}
?>
<div style="border:2px solid gray;margin:6px;padding:6px">
  <pre><?php echo htmlspecialchars($sql); ?></pre>
  <div>
    <table border="1">
      <?php
      if (!$res->numRows()) {
        ?>
        <tr><td>No results</td></tr>
        <?php
      } else {
        $i = 0;
        while ($row = $res->fetchRow()) {
          echo '<tr>';
          if ($i === 0) {
            foreach (array_keys($row) as $col) {
              printf('<th>%s</th>', htmlspecialchars($col));
            }
            echo '</tr><tr>';
          }
          foreach ($row as $col => $val) {
            printf('<td>%s</td>', htmlspecialchars($val));
          }
          echo "<tr>\n";
          $i++;
        }
      }
      ?>
    </table>
  </div>
</div>
<?php

// change the expression engine to the mode of "PHP".
debug($mdb2->setOption("engine", "php"), '$mdb2->setOption("engine", "php")');

// test query for PHP expression engine
$sql = <<<SQL
SELECT id, url, title, date('Y-m-d H:i:s') AS now
  FROM bookmark
  WHERE preg_match('/^[0-9a-f]{8}/i', md5( (string)title ) ) 
    AND id != (SELECT COUNT(*) FROM bookmark)
  ORDER BY id DESC;
SQL;
$res = $mdb2->query($sql);
if (PEAR::isError($res)) {
  abort($res);
}

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$res->numRows()) {
    ?>
    <tr><td>No results</td></tr>
    <?php
  } else {
    $i = 0;
    while ($row = $res->fetchRow()) {
      echo '<tr>';
      if ($i === 0) {
        foreach (array_keys($row) as $col) {
          printf('<th>%s</th>', htmlspecialchars($col));
        }
        echo '</tr><tr>';
      }
      foreach ($row as $col => $val) {
        printf('<td>%s</td>', htmlspecialchars($val));
      }
      echo "<tr>\n";
      $i++;
    }
  }
  ?>
</table>
<?php

// disconnect
$res = $mdb2->disconnect();
debug($res, '$mdb2->disconnect()');

//    }}} 
//    {{{ helper functions

/**
 * debug function like var_dump()
 */
function debug($val, $text = 'debug'){
  $text = preg_replace('/\s+/', ' ', $text);
  echo '<fieldset><legend style="font:x-small verdana;">',
       htmlspecialchars($text),
       "</legend><div><pre>\n";
  var_dump($val);
  echo "</pre></div></fieldset>\n";
}

/**
 * make anchor tags from URL in string
 */
function linkage($text){
  $pattern = '{(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+)}u';
  $replace = '<a href="$1">$1</a>';
  $text = preg_replace($pattern, $replace, $text);
  return $text;
}

/**
 * Abort with the error message.
 */
function abort($arg = null, $html = false){
  $msg = 'unknown error';
  if (is_string($arg)) {
    $msg = $arg;
  } else if (PEAR::isError($arg)) {
    if (method_exists($arg, 'toString')) {
      $msg = $arg->toString();
    } else if (method_exists($arg, 'getUserInfo')) {
      $msg = $arg->getUserInfo();
    } else if (method_exists($arg, 'getMessage')) {
      $msg = $arg->getMessage();
    }
  }
  if (!$html) {
    $msg = htmlspecialchars($msg);
  }
  $style_error = implode(';', array(
      'margin: 2px',
      'padding: 4px',
      'border: 4px solid #993300',
      'background: #ffffcc', 
      'color: #cc3300'
    )
  );
  $style_report = implode(';', array(
      'margin: 2px',
      'padding: 4px',
      'border: 4px solid #003399',
      'background: #ccffff', 
      'color: #0033cc',
      'text-align: center'
    )
  );
  ?>
  <div style="<?php echo $style_error; ?>">
    <h2>Error!</h2>
    <div>
      <?php echo $msg; ?>
    </div>
  </div>
  <div style="<?php echo $style_report; ?>">
    <div>
      If you think this to be a bug, 
      the report can be contributed to 
      <?php
      printf('%s or %s.',
        linkage(POSQL_PROJECT_JP_URL),
        linkage(POSQL_PROJECT_EN_URL)
      );
      ?>
    </div>
  </div>
  <?php
  die;
}
//    }}} 
?>
<h2>Thanks for using Posql and enjoy it with Posql.</h2>
<hr>
<div>
  <small>
    <ul>
      <li>
        <a href="<?php echo POSQL_PROJECT_JP_URL; ?>">
          Posql Project Page (jp)
        </a>
      </li>
      <li>
        <a href="<?php echo POSQL_PROJECT_EN_URL; ?>">
          Posql Project Page (en)
        </a>
      </li>
      <li>
        <a href="<?php echo POSQL_CATALOG_JP_URL; ?>">
          Posql Index Catalog List (jp)
        </a>
      </li>
      <li>
        <a href="<?php echo POSQL_CATALOG_EN_URL; ?>">
          Posql Index Catalog List (en)
        </a>
      </li>
    </ul>
  </small>
</div>
</body>
</html>