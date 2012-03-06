<?php
//
// Posql sample script
//
//    {{{ internal settings

// filename of Posql class
define('POSQL_PATH', '../src/posql.php');

// posql project page's url (Japanese)
//#####
//TODO: use https://github.com/polygonplanet/Posql
//#####
define('POSQL_PROJECT_JP_URL', 'http://sourceforge.jp/projects/posql/');

// posql project page's url (English)
define('POSQL_PROJECT_EN_URL', 'http://sourceforge.net/projects/posql/');

// posql index catalog list url (Japanese)
define('POSQL_CATALOG_JP_URL', 'http://feel.happy.nu/doc/posql/');

// posql index catalog list url (English)
define('POSQL_CATALOG_EN_URL', 'http://feel.happy.nu/doc/posql/en/');

//    }}} 
//    {{{ configuration

// Maximizes the error report.
defined('E_STRICT') or define('E_STRICT', 2048);
error_reporting(E_ALL | E_STRICT); // including E_STRICT
@ini_set('display_errors', 1);

// send Content-Type header
header('Content-Type: text/html; charset=UTF-8');
ob_implicit_flush(1);

//    }}} 
//    {{{ begin example script

// require the "Posql" class
require_once POSQL_PATH;

$database_name = '__sample_posql__';

// try connect
$posql = new Posql($database_name);

if ($posql->isError()) {
  abort($posql);
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Posql sample script</title>
<style type="text/css">
caption {font-weight     : bold}
a       {text-decoration : underline}
a:hover {text-decoration : none}
.sub    {font-size: 80%}
</style>
</head>
<body>
<h1>Posql - <span class="sub">sample script</span></h1>
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
  $value = null;
  switch ($option) {
    case 'version':
        $value = $posql->getVersion();
        break;
    case 'charset':
        $value = $posql->getCharset();
        break;
    case 'path':
        $value = $posql->getPath();
        break;
    case 'engine':
        $value = $posql->getEngine();
        break;
  }
  printf('<dt>%s</dt><dd>%s</dd>', $desc, $value);
}

?>
</dl>
<?php

// Drop sample table if existed
$sql = "DROP TABLE IF EXISTS bookmark";
$res = $posql->exec($sql);
if ($posql->isError()) {
  abort($posql);
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
$res = $posql->exec($sql);
if ($posql->isError()) {
  abort($posql);
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
$stmt = $posql->prepare($sql);
foreach ($items as $url => $title) {
  $res = $stmt->execute(array($url, $title));
  if ($posql->isError()) {
    abort($posql);
  }
  $real_sql = sprintf(str_replace('?', '"%s"', $sql), $url, $title);
  debug($stmt->affectedRows(), $real_sql);
}
$stmt->free(); //optionally

// get next/last sequence id
debug($posql->getLastInsertId(), '$posql->getLastInsertId()');
debug($posql->getNextId("bookmark"), '$posql->getNextId("bookmark")');

// Simple SELECT with output
$sql = "SELECT * FROM bookmark WHERE 1 = 1";
$stmt = $posql->query($sql);
if ($posql->isError()) {
  abort($posql);
}

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$stmt->rowCount()) {
    ?>
    <tr><td>No results</td></tr>
    <?php
  } else {
    $i = 0;
    $colspan = 1;
    // set the fetches mode as associate array
    $stmt->setFetchMode('assoc'); // or PDO::FETCH_ASSOC
    while ($row = $stmt->fetch()) {
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
      debug($stmt->numRows(), '$stmt->numRows()');
      debug($stmt->numCols(), '$stmt->numCols()');
      ?>
    </td>
  </tr>
</table>
<?php

// Test for UPDATE and SELECT COUNT(*) using bindParam(), bindValue().
$sql = "SELECT COUNT(*) FROM bookmark WHERE url LIKE :like";
$like = null;
$stmt = $posql->prepare($sql);
if ($posql->isError()) {
  abort($posql);
}
// Bind with the column name
$stmt->bindParam(':like', $like);
$like = '%example%';
$stmt->execute();
$affected_rows = (int)$stmt->fetchColumn();

$title = '(modified)';
$sql = "UPDATE bookmark SET title = :title WHERE url LIKE :like";
$stmt = $posql->prepare($sql);
if ($posql->isError()) {
  abort($posql);
}
$stmt->bindValue(':title', $title);
$stmt->bindValue(':like', $like);
$stmt->execute();
$sql = str_replace(':title', $posql->quote($title), $sql);
$sql = str_replace(':like', $posql->quote($like), $sql);
debug($affected_rows, $sql);

// Simple SELECT after UPDATE with output
$sql = "SELECT id, url, title FROM bookmark WHERE 1 = 1";
$id    = null;
$url   = null;
$title = null;
$stmt = $posql->prepare($sql);
$stmt->execute();
if ($posql->isError()) {
  abort($posql);
}
// Bind with the columns index
$stmt->bindColumn(1, $id);
$stmt->bindColumn(2, $url);

// Bind with the columns name
$stmt->bindColumn('title', $title);

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$stmt->rowCount()) {
    ?>
    <tr><td>No results</td></tr>
    <?php
  } else {
    ?>
    <tr>
      <th>id</th>
      <th>url</th>
      <th>title</th>
    </tr>
    <?php
    while ($stmt->fetch()) {
      echo '<tr>';
      printf('<td>%s</td>', htmlspecialchars($id));
      printf('<td>%s</td>', htmlspecialchars($url));
      printf('<td>%s</td>', htmlspecialchars($title));
      echo "<tr>\n";
    }
  }
  ?>
</table>
<?php

// Drop other table if existed
$sql = "DROP TABLE IF EXISTS bookmark_en";
$res = $posql->exec($sql);
if ($posql->isError()) {
  abort($posql);
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
$res = $posql->exec($sql);
if ($posql->isError()) {
  abort($posql);
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
$affected_rows = $posql->exec($sql);
if ($posql->isError()) {
  abort($posql);
}
?>
<div style="border:2px solid gray;margin:6px;padding:6px">
  <pre><?php echo htmlspecialchars($sql); ?></pre>
  <div>
    <?php
    debug($affected_rows, 'Affected rows');
    ?>
  </div>
</div>
<?php

// Simple SELECT after INSERT with output
$sql = "SELECT bookmark_en.* FROM bookmark_en WHERE 1 = 1";
$stmt = $posql->query($sql);
if ($posql->isError()) {
  abort($posql);
}
// output all rows as HTML table element
echo $stmt->fetchAllHTMLTable($sql);

// try query which join on 2 tables
$sql = <<<SQL
SELECT a.url, b.en_url, a.title, b.en_title 
FROM bookmark AS a 
     LEFT JOIN bookmark_en AS b
     ON a.id = b.id;
SQL;
$stmt = $posql->query($sql);
if ($posql->isError()) {
  abort($posql);
}
// below code equals $stmt->fetchAllHTMLTable($sql)
echo $stmt->fetchAll('HTMLTable', $sql);

// Try and begin transaction
$res = $posql->beginTransaction();
if ($posql->isError()) {
  abort($posql);
}
debug($res, '$posql->beginTransaction()');

// Delete all records
$sql = "DELETE FROM bookmark WHERE 1 = 1";
$affected_rows = $posql->exec($sql);
if ($posql->isError()) {
  abort($posql);
}
debug($affected_rows, $sql);

// check whether the table is empty
$sql = "SELECT * FROM bookmark WHERE 1 = 1";
$stmt = $posql->query($sql);
if ($posql->isError()) {
  abort($posql);
}

?>
<table border="1">
  <caption>
    <strong><?php echo htmlspecialchars($sql); ?></strong>
  </caption>
  <?php
  if (!$stmt->rowCount()) {
    ?>
    <tr><td width="350">No results</td></tr>
    <?php
  } else {
    $i = 0;
    while ($row = $res->fetch('ASSOC')) {
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
$res = $posql->rollBack();
if ($posql->isError()) {
  abort($posql);
}
debug($res, '$posql->rollBack()');

// check whether the table was rollbacked
$sql = "SELECT * FROM bookmark WHERE 1 = 1";
$stmt = $posql->query($sql);
if ($posql->isError()) {
  abort($posql);
}
echo $stmt->fetchAll('HTMLTable', $sql);

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
$stmt = $posql->query($sql);
if ($posql->isError()) {
  abort($posql);
}
?>
<div style="border:2px solid gray;margin:6px;padding:6px">
  <pre><?php echo htmlspecialchars($sql); ?></pre>
  <div>
    <?php
    echo $stmt->fetchAll('HTMLTable');
    ?>
  </div>
</div>
<?php

// change the expression engine to the mode of "PHP".
debug((bool)$posql->setEngine("php"), '$posql->setEngine("php")');

?>
<div style="margin: 4px">
  <fieldset>
    <dl>
      <dt>Expression Engine<dt>
      <dd><?php echo $posql->getEngine(); ?></dd>
    </dl>
  </fieldset>
</div>
<?php

// test query for PHP expression engine
$sql = <<<SQL
SELECT id, url, title, date('Y-m-d H:i:s') AS now
  FROM bookmark
  WHERE preg_match('/^[0-9a-f]{8}/i', md5( (string)title ) ) 
    AND id != (SELECT COUNT(*) FROM bookmark)
  ORDER BY id DESC;
SQL;
$stmt = $posql->query($sql);
if ($posql->isError()) {
  abort($posql);
}
echo $stmt->fetchAll('HTMLTable', $sql);

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
  } else if (is_object($arg) && 0 === strcasecmp(get_class($arg), 'Posql')) {
    $msg = $arg->lastError();
  } else if (is_callable(array('PEAR', 'isError')) && @PEAR::isError($arg)) {
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
<div style="font-size: small;">
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
</div>
</body>
</html>