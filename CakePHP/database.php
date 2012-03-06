<?php
/* SVN FILE: $Id$ */
/**
 * This is core configuration file.
 *
 * Use it to configure core behaviour ofCake.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Original configuration file of Posql attachment.
 *
 * Revision of CakePHP: 1.2.5
 * ---------------------------------------------------------------------------
 * Posql:
 *   the database class by pure PHP language,
 *   and a design which conforms to SQL-92,
 *   the target database only using all-in-one file.
 *
 * Posql Project:
 *   http://sourceforge.jp/projects/posql/
 *   http://sourceforge.net/projects/posql/
 * ---------------------------------------------------------------------------
 * @package     Posql
 * @subpackage  CakePHP/DBO/Config
 * @category    Database
 * @author      polygon planet <polygon.planet@gmail.com>
 * @link        http://sourceforge.jp/projects/posql/
 * @link        http://sourceforge.net/projects/posql/
 * @link        http://www.cakephp.org
 * @license     Dual licensed under the MIT and GPL licenses
 * @copyright   Copyright (c) 2010 polygon planet
 * @version     $Id: database.php.posql,v 0.02 2010/01/11 06:58:15 polygon Exp $
 */
/**
 * In this file you set up your database connection details.
 *
 * @package       cake
 * @subpackage    cake.config
 */
/**
 * Database configuration class.
 * You can specify multiple configurations for production, development and testing.
 *
 * driver => The name of a supported driver; valid options are as follows:
 *		mysql 		- MySQL 4 & 5,
 *		mysqli 		- MySQL 4 & 5 Improved Interface (PHP5 only),
 *		sqlite		- SQLite (PHP5 only),
 *		postgres	- PostgreSQL 7 and higher,
 *		mssql		- Microsoft SQL Server 2000 and higher,
 *		db2			- IBM DB2, Cloudscape, and Apache Derby (http://php.net/ibm-db2)
 *		oracle		- Oracle 8 and higher
 *		firebird	- Firebird/Interbase
 *		sybase		- Sybase ASE
 *		adodb-[drivername]	- ADOdb interface wrapper (see below),
 *		odbc		- ODBC DBO driver
 *		posql		- Posql 2.xx (http://sourceforge.jp/projects/posql/)
 *
 * You can add custom database drivers (or override existing drivers) by adding the
 * appropriate file to app/models/datasources/dbo.  Drivers should be named 'dbo_x.php',
 * where 'x' is the name of the database.
 *
 * persistent => true / false
 * Determines whether or not the database should use a persistent connection
 *
 * connect =>
 * ADOdb set the connect to one of these
 *	(http://phplens.com/adodb/supported.databases.html) and
 *	append it '|p' for persistent connection. (mssql|p for example, or just mssql for not persistent)
 * For all other databases, this setting is deprecated.
 *
 * host =>
 * the host you connect to the database.  To add a socket or port number, use 'port' => #
 *
 * prefix =>
 * Uses the given prefix for all the tables in this database.  This setting can be overridden
 * on a per-table basis with the Model::$tablePrefix property.
 *
 * schema =>
 * For Postgres and DB2, specifies which schema you would like to use the tables in. Postgres defaults to
 * 'public', DB2 defaults to empty.
 *
 * encoding =>
 * For MySQL, MySQLi, Postgres and DB2, specifies the character encoding to use when connecting to the
 * database.  Uses database default.
 *
 */
class DATABASE_CONFIG {

	var $default = array(
		'driver'     => 'posql',
		'persistent' => false,
		'host'       => '',
		'login'      => '',
		'password'   => '',
		//
		// Set your Posql database path.
		// The extension "php" is important to apply.
		// The database will be created in "app/vendors/posql/" directory.
		//
		'database'   => '../vendors/posql/database_name.php',
		'prefix'     => '',
	);

	var $test = array(
		'driver'     => 'posql',
		'persistent' => false,
		'host'       => '',
		'login'      => '',
		'password'   => '',
		//
		// Set your Posql database path.
		//
		'database'   => '/path/to/[your-database-path]/database.php',
		'prefix'     => '',
	);
}
?>