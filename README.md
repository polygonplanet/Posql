Posql
=====

Posql is a tiny text-base database engine (DBMS) written by pure PHP that
does not need any additional extension library,
it is designed compatible with SQL-92, and only uses all-in-one file as database.

Posql は ピュア PHP の クラス ライブラリ系 DBMS です。
SQLite モデルの 1 ファイル 1 データベース形式で SQL-92 に準拠した設計で作られています。
PEAR::DB, PEAR::MDB2, CakePHP 用ドライバがあり PDO と互換性がある API が使えます。

## Docs

https://polygonpla.net/app/posql/

## Note

This project is not active now, and this may not work in PHP7+, it works in PHP5.

## Example

```php
require_once 'posql.php';
$posql = new Posql;

// Using SQL-99 Syntax
$sql = <<<SQL
SELECT
    SUBSTRING(UPPER(g.a) FROM 1 FOR 1) A,
    TRIM(LEADING LOWER('hOG') FROM g.a) B,
    CASE g.b WHEN 'xox' THEN 'x'
             WHEN 'lol' THEN 'l'
             ELSE '?'
    END AS C,
    TRIM(TRAILING 'ol' FROM g.b) D,
    TRIM(BOTH 'l' FROM g.b) E,
    SUBSTRING(g.c FROM 4 FOR 1) F,
    UPPER(
        TRIM(
            BOTH '^'
            FROM SUBSTRING(g.d FROM 2 FOR 3)
        )
    ) G,
    SUBSTRING(
        g.a
        FROM ((BIT_LENGTH(g.d) - BIT_LENGTH(g.a)) DIV 4)
        FOR CHAR_LENGTH(g.a) - CHAR_LENGTH(g.e)
    ) H,
    SUBSTRING(g.e FROM -1) I,
    CASE WHEN g.a = 1            THEN 'a'
         WHEN g.b = 2            THEN 'b'
         WHEN g.c IS NULL        THEN 'c'
         WHEN g.d NOT LIKE '%x%' THEN 'l'
         WHEN g.e LIKE '.w.'     THEN 'm'
         ELSE '?'
    END J,
    LOWER(
        SUBSTRING(
            g.c
            FROM -1
        )
    ) K
FROM (
    SELECT a, b, c, d, e
    FROM (
        SELECT
            'hoge' AS a,
            'lol'  AS b,
            '1 + 0xF1AF0D' AS c,
            '(^w^)' AS d,
            'bar'  AS e
        UNION
        SELECT 1 a, 2 b, 3 c, 4 d, 5 e
    ) AS f
    WHERE a LIKE '%o%'
) AS g
LIMIT 1 OFFSET 0
SQL;

$stmt = $posql->query($sql);
print $stmt->fetchAll('htmltable');
```

## Results

```
1 Record.

        +---+---+---+---+---+---+---+---+---+---+---+
        | A | B | C | D | E | F | G | H | I | J | K |
        +---+---+---+---+---+---+---+---+---+---+---+
        | H | e | l | l | o |   | W | o | r | l | d |
        +---+---+---+---+---+---+---+---+---+---+---+
```

## License

This software is licensed under a dual license system ([MIT](LICENSE-MIT) or [GPL v2](LICENSE-GPLv2)).
This means you are free to choose with which of both licenses (MIT or GPL v2) you want to use this library.
