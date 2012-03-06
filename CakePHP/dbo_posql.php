<?php
/**
 * Posql layer for DBO
 *
 * Long description for file
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
 * @subpackage    cake.cake.libs.model.datasources.dbo
 * @since         CakePHP(tm) v 0.9.0
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * The CakePHP DBO driver for interacting with Posql database
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
 * The methods CakePHP DBO uses to interact with Posql database class.
 * This driver is based on SQLite, and other DBO,
 *  and all methods are inherited from CakePHP framework.
 *
 * NOTE:
 *   This driver is non-standard in CakePHP.
 *   And, consider done only minimum debugging.
 *
 * @package     Posql
 * @subpackage  CakePHP/DBO
 * @category    Database
 * @author      polygon planet <polygon.planet@gmail.com>
 * @uses        Posql Version 2.11 or later
 * @link        http://sourceforge.jp/projects/posql/
 * @link        http://sourceforge.net/projects/posql/
 * @link        http://www.cakephp.org
 * @license     Dual licensed under the MIT and GPL licenses
 * @copyright   Copyright (c) 2010 polygon planet
 * @version     $Id: dbo_posql.php,v 0.02 2010/01/11 06:51:57 polygon Exp $
 */

/**
 * Import the Posql class.
 * Put the file "posql.php" in "vendors" directory (e.g. "vendors/posql.php").
 * You can change this path arbitrarily.
 */
APP::import('Vendor', 'posql');

/**
 * DBO implementation for the Posql DBMS.
 *
 * Long description for class
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources.dbo
 */
class DboPosql extends DboSource {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $description = 'Posql DBO Driver';
/**
 * Opening quote for quoted identifiers
 *
 * @var string
 */
	var $startQuote = '';
/**
 * Closing quote for quoted identifiers
 *
 * @var string
 */
	var $endQuote = '';
/**
 * Keeps the transaction statistics of CREATE/UPDATE/DELETE queries
 *
 * @var array
 * @access protected
 */
	var $_queryStats = array();
/**
 * Base configuration settings for Posql driver
 *
 * "connect" is not used.
 * @see _getPosqlObject
 *
 * @var array
 */
	var $_baseConfig = array(
		'persistent' => false,
		'database'   => null,
		'connect'    => 'Posql'
	);
/**
 * Index of basic SQL commands
 *
 * @var array
 * @access protected
 */
	var $_commands = array(
		'begin'    => 'BEGIN TRANSACTION',
		'commit'   => 'COMMIT TRANSACTION',
		'rollback' => 'ROLLBACK TRANSACTION'
	);
/**
 * Posql column definition
 *
 * @var array
 */
	var $columns = array(
		'primary_key' => array(
			'name' => 'integer primary key'
		),
		'string' => array(
			'name'  => 'varchar',
			'limit' => '255'
		),
		'text' => array(
			'name' => 'text'
		),
		'integer' => array(
			'name'      => 'integer',
			'limit'     => 11,
			'formatter' => 'intval'
		),
		'float' => array(
			'name'      => 'float',
			'formatter' => 'floatval'
		),
		'datetime' => array(
			'name'      => 'datetime',
			'format'    => 'Y-m-d H:i:s',
			'formatter' => 'date'
		),
		'timestamp' => array(
			'name'      => 'timestamp',
			'format'    => 'Y-m-d H:i:s',
			'formatter' => 'date'
		),
		'time' => array(
			'name'      => 'time',
			'format'    => 'H:i:s',
			'formatter' => 'date'
		),
		'date' => array(
			'name'      => 'date',
			'format'    => 'Y-m-d',
			'formatter' => 'date'
		),
		'binary' => array(
			'name' => 'blob'
		),
		'boolean' => array(
			'name' => 'boolean'
		)
	);

/**
 * Posql only usage:
 * Return the object instance of Posql.
 *
 * Note:
 *  PHP4 warns "Nesting level too deep - recursive dependency?"
 *   by comparing objects.
 *  Use the static variable to evade.
 *
 * @param void
 * @return object
 */
	function &_getPosqlObject(){
		static $object = null;
		if ($object === null) {
			$object = new Posql;
			//
			// PHP4 will emit the warning with register_shutdown_function(),
			// this code will be  deal with warning.
			// Instead,the VACUUM command might not be automatically executed.
			//
			if (substr(phpversion(), 0, 1) <= 4) {
				// Therefore, the shutdown function will have been registered.
				$object->_setRegistered(true);
			}
		}
		return $object;
	}

/**
 * Posql only usage:
 * Apply the object instance of Posql_Statement.
 *
 * @param  object  the object instance of Posql_Statement
 * @param  boolean whether the variable is deleted
 * @return object
 */
	function &_applyPosqlStatementObject($statement = null, $exit = false){
		static $object = null;
		if (is_object($statement)) {
			$object = $statement;
		}
		if ($exit) {
			$object = null;
		}
		return $object;
	}

/**
 * Connects to the database using config['database'] as a filename.
 *
 * @return boolean
 */
	function connect() {
		$posql = & $this->_getPosqlObject();
		$posql->open($this->config['database']);
		$this->connection = $posql->getId();
		$this->connected = $posql->isEnableId($this->connection);
		unset($posql);
		return $this->connected;
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		$posql = & $this->_getPosqlObject();
		if (method_exists($posql, 'terminate')) {
			$posql->terminate();
			$posql->_terminated = true;
			$posql = null;
			$this->_applyPosqlStatementObject(null, true);
			$this->connection = null;
			$this->connected = false;
		}
		unset($posql);
		return $this->connected;
	}
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return boolean success or failure
 */
	function _execute($sql) {
		$result = false;
		$posql = & $this->_getPosqlObject();
		$statement = $posql->query($sql);
		$this->_applyPosqlStatementObject($statement);
		
		switch (strtolower($posql->getLastMethod())) {
			case 'insert':
			case 'update':
			case 'delete':
				$this->resultSet();
				list($this->_queryStats) = $this->fetchResult();
				break;
			default:
				break;
		}
		if (!$posql->isError()) {
			$result = true;
		}
		unset($posql, $statement);
		return $result;
	}
/**
 * Overrides DboSource::execute() to correctly handle query statistics
 *
 * @param string $sql the SQL statement
 * @return unknown
 */
	function execute($sql) {
		$result = parent::execute($sql);
		$this->_queryStats = array();
		return $result;
	}
/**
 * Returns an array of tables in the database.
 * If there are no tables, an error is raised and the application exits.
 *
 * @return array Array of tablenames in the database
 */
	function listSources() {
		$result = array();
		$posql = & $this->_getPosqlObject();
		$database = $this->config['database'];
		$this->config['database'] = $posql->getDatabaseName();
		unset($posql);
		
		$desc = $this->fetchAll('DESCRIBE', false);
		
		if (!empty($desc) && is_array($desc)) {
			$shift = array_shift($desc);
			if (!empty($shift['name'])) {
				$tables = array();
				foreach ($desc as $table) {
					$tables[] = $table['name'];
				}
				parent::listSources($tables);
				$result = $tables;
			}
		}
		$this->config['database'] = $database;
		return $result;
	}
/**
 * Returns an array of the fields in given table name.
 *
 * @param object $model
 * @return array fields in table. keys are name and type
 */
	function describe(&$model) {
		$result = array();
		$posql = & $this->_getPosqlObject();
		$database = $this->config['database'];
		$this->config['database'] = $posql->getDatabaseName();
		unset($posql);
		$sql = sprintf('DESCRIBE %s', $this->fullTableName($model));
		$desc = $this->fetchAll($sql);
		$this->config['database'] = $database;
		if (!empty($desc) && is_array($desc)) {
			$fields = array();
			$hiddens = array('rowid', 'ctime', 'utime');
			foreach ($desc as $column) {
				if (array_key_exists(0, $column)) {
					$column = $column[0];
				}
				$name = $column['name'];
				if (in_array($name, $hiddens)) {
					continue;
				}
				$fields[$name] = array(
					'type'    => $this->column($column['type']),
					'null'    => true,
					'default' => $column['default'],
					'length'  => $this->length($column['type'])
				);
				if (strpos($column['extra'], 'alias for') === 0) {
					$length = $this->length($column['type']);
					$fields[$name] = array(
						'type'    => $fields[$name]['type'],
						'null'    => false,
						'default' => $column['default'],
						'key'     => $this->index['PRI'],
						'length'  => $length == null ? 11 : $length
					);
				}
			}
			$result = $fields;
		}
		return $result;
	}
/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @return string Quoted and escaped
 */
	function value($data, $column = null, $safe = false) {
		$parent = parent::value($data, $column, $safe);
		if ($parent != null) {
			return $parent;
		}
		if ($data === null) {
			return 'NULL';
		}
		if ($data === '' && $column !== 'integer' &&
			$column !== 'float' && $column !== 'boolean') {
			return  "''";
		}
		switch ($column) {
			case 'boolean':
				$data = $this->boolean((bool)$data);
				break;
			case 'integer':
			case 'float':
				if ($data === '') {
					return 'NULL';
				}
			default:
				$posql = & $this->_getPosqlObject();
				$data = $posql->escapeString($data);
				unset($posql);
				break;
		}
		return "'" . $data . "'";
	}
/**
 * Generates and executes an SQL UPDATE statement
 *  for given model, fields, and values.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @param mixed $conditions
 * @return array
 */
	function update(&$model, $fields = array(), $values = null, $conditions = null) {
		$result = array();
		if (empty($values) && !empty($fields)) {
			foreach ($fields as $field => $value) {
				if (strpos($field, $model->alias . '.') !== false) {
					unset($fields[$field]);
					$field = str_replace($model->alias . '.', '', $field);
					$fields[$field] = $value;
				}
			}
		}
		$result = parent::update($model, $fields, $values, $conditions);
		return $result;
	}
/**
 * Deletes all the records in a table and resets the count of
 *  the auto-incrementing primary key, where applicable.
 *
 * @param mixed $table A string or model class representing the table to be truncated
 * @return boolean SQL TRUNCATE TABLE statement, false if not applicable.
 * @access public
 */
	function truncate($table) {
		$table = $this->fullTableName($table);
		$sql = sprintf('DELETE FROM %s WHERE 1 = 1', $table);
		$result = $this->execute($sql);
		$posql = & $this->_getPosqlObject();
		$posql->setNextId($table, 1);
		unset($posql);
		return $result;
	}
/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message
 */
	function lastError() {
		$error = null;
		$posql = & $this->_getPosqlObject();
		if ($posql->isError()) {
			$error = $posql->lastError();
		}
		unset($posql);
		return $error;
	}
/**
 * Returns number of affected rows in previous database operation.
 * If no previous operation exists, this returns false.
 *
 * @return integer Number of affected rows
 */
	function lastAffected() {
		if (!empty($this->_queryStats) && is_array($this->_queryStats)) {
			$ops = array('rows inserted', 'rows updated', 'rows deleted');
			foreach ($ops as $op) {
				if (array_key_exists($op, $this->_queryStats)) {
					return $this->_queryStats[$op];
				}
			}
		}
		return false;
	}
/**
 * Returns number of rows in previous resultset.
 * If no previous resultset exists,
 * this returns false.
 *
 * @return integer Number of rows in resultset
 */
	function lastNumRows() {
		$result = false;
		$statement = & $this->_applyPosqlStatementObject();
		if ($this->hasResult() && method_exists($statement, 'rowCount')) {
			$result = $statement->rowCount();
		}
		unset($statement);
		return $result;
	}
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @return int
 */
	function lastInsertId() {
		$posql = & $this->_getPosqlObject();
		$result = $posql->getLastInsertId();
		unset($posql);
		return $result;
	}
/**
 * Converts database-layer column types to basic types
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
	function column($real) {
		$open  = '(';
		$close = ')';
		if (is_array($real)) {
			$col = $real['name'];
			if (isset($real['limit'])) {
				$col .= $open . $real['limit'] . $close;
			}
			return $col;
		}
		$col = strtolower($real);
		if (strpos($col, $close) !== false) {
			str_replace($close, '', $col);
		}
		$limit = null;
		if (strpos($col, $open) !== false) {
			list($col, $limit) = explode($open, $col);
		}
		$types = array(
			'text',
			'integer',
			'float',
			'boolean',
			'timestamp',
			'date',
			'datetime',
			'time'
		);
		if (in_array($col, $types)) {
			return $col;
		}
		if (strpos($col, 'varchar') !== false) {
			return 'string';
		}
		if (in_array($col, array('blob', 'clob'))) {
			return 'binary';
		}
		if (strpos($col, 'numeric') !== false) {
			return 'float';
		}
		return 'text';
	}
/**
 * Enter description here...
 *
 * @param unknown_type $results
 */
	function resultSet($results = null) {
		$statement = & $this->_applyPosqlStatementObject();
		if (is_object($statement)) {
			$this->map = array();
			$cols = $statement->getColumnNames(true);
			$fields = $statement->getTableNames();
			if (!empty($fields)) {
				foreach ($fields as $i => $field) {
					if ($field == null && isset($cols[$i])) {
						$field = $cols[$i];
					}
					if (strpos($field, '.') === false) {
						$this->map[] = array(0, $field);
					} else {
						list($table, $column) = explode('.', $field);
						$this->map[] = array($table, $column);
					}
				}
			} else {
				foreach ($cols as $i => $name) {
					$this->map[] = array(0, $name);
				}
			}
		}
		unset($statement);
	}
/**
 * Fetches the next row from the current result set
 *
 * @return unknown
 */
	function fetchResult() {
		$result = false;
		$statement = & $this->_applyPosqlStatementObject();
		if (is_object($statement) && ($row = $statement->fetch('assoc'))) {
			$result = $row;
			if (!empty($this->map) && is_array($this->map)) {
				$posql = & $this->_getPosqlObject();
				$method = $posql->getLastMethod();
				if (strtolower($method) === 'select') {
					$result = array();
					$index = 0;
					foreach ($row as $i => $field) {
						list($table, $column) = $this->map[$index];
						$result[$table][$column] = $field;
						$index++;
					}
				}
				unset($posql);
			}
		}
		unset($statement);
		return $result;
	}
/**
 * Checks if the result is valid
 *
 * @return boolean True if the result is valid else false
 */
	function hasResult() {
		$result = false;
		$statement = & $this->_applyPosqlStatementObject();
		if (is_object($statement) && method_exists($statement, 'hasRows')) {
			$result = $statement->hasRows();
		}
		unset($statement);
		return $result;
	}
/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param integer $limit Limit of results returned
 * @param integer $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	function limit($limit, $offset = null) {
		$result = null;
		if ($limit) {
			$parts = array();
			$pos = strpos(strtolower($limit), 'limit');
			if ($pos === false) {
				$parts[] = 'LIMIT';
			}
			$parts[] = sprintf('%.0f', $limit);
			if ($offset !== null) {
				$parts[] = sprintf('OFFSET %.0f', $offset);
			}
			$result = sprintf(' %s ', implode(' ', $parts));
		}
		return $result;
	}
/**
 * Generate a database-native column schema string
 *
 * @param array $column An array structured like the following:
 *                      array('name'=>'value', 'type'=>'value'[, options]),
 *                      where options can be 'default', 'length', or 'key'.
 * @return string
 */
	function buildColumn($column) {
		$name = $type = null;
		$column = array_merge(array('null' => true), $column);
		extract($column);
		
		if (empty($name) || empty($type)) {
			trigger_error('Column name or type not defined in schema', E_USER_WARNING);
			return null;
		}

		if (!isset($this->columns[$type])) {
			trigger_error("Column type {$type} does not exist", E_USER_WARNING);
			return null;
		}

		$real = $this->columns[$type];
		$out = $this->name($name) . ' ' . $real['name'];
		if (isset($column['key']) && $column['key'] == 'primary' && $type == 'integer') {
			return $this->name($name) . ' ' . $this->columns['primary_key']['name'];
		}
		if (isset($real['limit']) || isset($real['length']) || isset($column['limit']) || isset($column['length'])) {
			if (isset($column['length'])) {
				$length = $column['length'];
			} else if (isset($column['limit'])) {
				$length = $column['limit'];
			} else if (isset($real['length'])) {
				$length = $real['length'];
			} else {
				$length = $real['limit'];
			}
			$out .= '(' . $length . ')';
		}
		if (isset($column['key']) && $column['key'] == 'primary' && $type == 'integer') {
			$out .= ' ' . $this->columns['primary_key']['name'];
		} else if (isset($column['key']) && $column['key'] == 'primary') {
			$out .= ' NOT NULL';
		} else if (isset($column['default']) && isset($column['null']) && $column['null'] == false) {
			$out .= ' DEFAULT ' . $this->value($column['default'], $type) . ' NOT NULL';
		} else if (isset($column['default'])) {
			$out .= ' DEFAULT ' . $this->value($column['default'], $type);
		} else if (isset($column['null']) && $column['null'] == true) {
			$out .= ' DEFAULT NULL';
		} else if (isset($column['null']) && $column['null'] == false) {
			$out .= ' NOT NULL';
		}
		return $out;
	}
/**
 * Sets the database encoding
 *
 * @param string $enc Database encoding
 */
	function setEncoding($enc) {
		$posql = & $this->_getPosqlObject();
		$posql->setCharset($enc);
		unset($posql);
		return true;
	}
/**
 * Gets the database encoding
 *
 * @return string The database encoding
 */
	function getEncoding() {
		$posql = & $this->_getPosqlObject();
		$result = $posql->getCharset();
		unset($posql);
		return $result;
	}
}
?>