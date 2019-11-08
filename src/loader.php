<?php
require_once dirname(__FILE__) . '/posql.php';
//-----------------------------------------------------------------------------
if ((int)PHP_VERSION < 5) {
/**
 * @name Posql_Loader
 *
 * Posql:
 *   The tiny text-base database engine (DBMS) written by pure PHP
 *   that does not need any additional extension library,
 *   it is designed compatible with SQL-92,
 *   and only uses all-in-one file as database.
 *
 * Loader:
 *   Loader class to create the Posql class instance for singleton.
 *
 * PHP versions 4 and 5
 *
 * @package    Posql
 * @subpackage Loader
 * @author     polygon planet <polygon.planet.aqua@gmail.com>
 * @link       http://sourceforge.jp/projects/posql/
 * @link       http://sourceforge.net/projects/posql/
 * @license    Dual licensed under the MIT and GPL licenses
 * @copyright  Copyright (c) 2010 polygon planet
 * @version    $Id$
 *---------------------------------------------------------------------------*/
 class Posql_Loader {
/**
 * @name getInstance
 *
 * Returns the instance of Posql object
 *
 * @example
 * <code>
 * $posql = & Posql_Loader::getInstance();
 * var_dump($posql->getVersion());
 * </code>
 *
 * Note:
 *   This definition prevents
 *   "Strict standards: Non-static method" being warned.
 *
 * @param  void
 * @return Posql  instance of Posql database class
 * @access public
 * @static
 */
  function &getInstance(){
    static $posql = null;
    if ($posql === null) {
      $posql = new Posql;
    }
    return $posql;
  }
 }
 // @dummyPHPDocComment
 // Code for PHP versions 5 or later, below
} else {
  eval('
    class Posql_Loader {
      /**
       * @return Posql
       */
      static function &getInstance(){
        static $posql = null;
        if ($posql === null) {
          $posql = new Posql;
        }
        return $posql;
      }
    }
  ');
}
