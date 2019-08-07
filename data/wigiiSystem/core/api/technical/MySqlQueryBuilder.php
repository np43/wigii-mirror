<?php
/**
 *  This file is part of Wigii (R) software.
 *  Wigii is developed to inspire humanity. To Humankind we offer Gracefulness, Righteousness and Goodness.
 *  
 *  Wigii is free software: you can redistribute it and/or modify it 
 *  under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, 
 *  or (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 *  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *  See the GNU General Public License for more details.
 *
 *  A copy of the GNU General Public License is available in the Readme folder of the source code.  
 *  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2016  Wigii.org
 *  @author     <http://www.wigii.org/system>      Wigii.org 
 *  @link       <http://www.wigii-system.net>      <https://github.com/wigii/wigii>   Source Code
 *  @license    <http://www.gnu.org/licenses/>     GNU General Public License
 */

/**
 * MySql Query builder
 * Created by CWE on 9 sept. 09
 */
class MySqlQueryBuilder
{
	private $_debugLogger;

	const SQLTYPE_BIGINT = 1;
	const SQLTYPE_DOUBLE = 11;
	const SQLTYPE_DECIMAL = 12;
	const SQLTYPE_BOOLEAN = 2;
	const SQLTYPE_DATE = 3;
	const SQLTYPE_DATETIME = 4;
	const SQLTYPE_INT = 5;
	const SQLTYPE_LONGBLOB = 6;
	const SQLTYPE_BLOB = 10;
	const SQLTYPE_SQLEXP = 13;
	const SQLTYPE_TEXT = 7;
	const SQLTYPE_TIME = 8;
	const SQLTYPE_VARCHAR = 9;

	const SQLQUERYTYPE_SELECT = 1;
	const SQLQUERYTYPE_INSERT = 2;
	const SQLQUERYTYPE_UPDATE = 3;
	const SQLQUERYTYPE_DELETE = 4;
	const SQLQUERYTYPE_IF_EXIST_UPDATE_ELSE_INSERT = 5;

	private $sqlIntro;
	private $sqlWhere;
	private $sqlFields;
	private $sqlValues;
	private $sqlQueryType;
	private $sqlUpdateOnDuplicate;
	private $sqlOrderBy;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("MySqlQueryBuilder");
		}
		return $this->_debugLogger;
	}

	/**
	 * Resets sql builder from scratch
	 */
	public function reset()
	{
		unset($this->sqlIntro);
		unset($this->sqlWhere);
		unset($this->sqlFields);
		unset($this->sqlValues);
		unset($this->sqlUpdateOnDuplicate);
		unset($this->sqlOrderBy);
		$this->sqlQueryType = 0;
	}
	/**
	 * Gets the built sql query string
	 * returns null if no SQL query has been built
	 * or if no values have changed compared to original, then no update needed.
	 */
	public function getSql()
	{
		switch($this->sqlQueryType)
		{
			case MySqlQueryBuilder::SQLQUERYTYPE_INSERT:
				return $this->sqlIntro.$this->sqlFields.') VALUES ('.$this->sqlValues.')';
			case MySqlQueryBuilder::SQLQUERYTYPE_UPDATE:
				if(!isset($this->sqlValues)) return null;
				if(isset($this->sqlWhere)) return $this->sqlIntro.$this->sqlValues.' WHERE '.$this->sqlWhere;
				else return $this->sqlIntro.$this->sqlValues;
			case MySqlQueryBuilder::SQLQUERYTYPE_DELETE:
				if(isset($this->sqlWhere)) return $this->sqlIntro.' WHERE '.$this->sqlWhere;
				else return $this->sqlIntro;
			case MySqlQueryBuilder::SQLQUERYTYPE_IF_EXIST_UPDATE_ELSE_INSERT:
				if(!isset($this->sqlValues)) return null;
				return $this->sqlIntro.$this->sqlFields.') VALUES ('.$this->sqlValues.') ON DUPLICATE KEY UPDATE '.$this->sqlUpdateOnDuplicate;
			default: return null;
		}
	}

	/**
	 * Formats a given value in the appropriate SQL code chunk according to SQL type
	 * val : value to be formatted in SQL
	 * sqlType: one of SQLTYPE constants
	 * returns an SQL code chunk with the value
	 */
	public function formatValue($val, $sqlType)
	{
		if(!isset($val) || is_null($val) || $val==="NULL" || $val==="null") return 'NULL';
		if($sqlType == MySqlQueryBuilder::SQLTYPE_SQLEXP) return $val;
		
		$sval = addslashes(stripslashes($val));
		switch($sqlType)
		{
			case MySqlQueryBuilder::SQLTYPE_DECIMAL:
			case MySqlQueryBuilder::SQLTYPE_BIGINT:
			case MySqlQueryBuilder::SQLTYPE_INT:
			case MySqlQueryBuilder::SQLTYPE_DOUBLE:
			    $sval = trim($sval);
				if($sval === '') return 'NULL';
				elseif(is_numeric($sval)) return $sval;
				else throw new MySqlFacadeException('invalid SQL type', MySqlFacadeException::INVALID_ARGUMENT);
			case MySqlQueryBuilder::SQLTYPE_BOOLEAN:
				if($val == "0" ||
					$val == null ||
					$val === 0 ||
					$val === "NULL" ||
					$val === "null" ||
					$val === "FALSE" ||
					$val === "false" ||
					$val === false) return 0;
				else return 1;
			case MySqlQueryBuilder::SQLTYPE_TEXT:
			case MySqlQueryBuilder::SQLTYPE_VARCHAR:
				if('' == $val) return 'NULL';
				return "'$sval'";
			case MySqlQueryBuilder::SQLTYPE_DATE:
			case MySqlQueryBuilder::SQLTYPE_DATETIME:
				if(($sval === '') || ($sval === '0')) return 'NULL';
				else
				{
					$sval = $this->date2MySqlDate($sval);
					return "'$sval'";
				}
			case MySqlQueryBuilder::SQLTYPE_TIME:
				if(($sval === '') || ($sval === '0')) return 'NULL';
				else
				{
					$sval = $this->time2MySqlTime($sval);
					return "'$sval'";
				}
			case MySqlQueryBuilder::SQLTYPE_LONGBLOB:
			case MySqlQueryBuilder::SQLTYPE_BLOB:
				if('' == $val) return 'NULL';
				return "'$sval'";
			default:
				throw new MySqlFacadeException('invalid SQL type', MySqlFacadeException::INVALID_ARGUMENT);
		}
	}
	/**
	 * Transforms a date/time string to MYSQL format (yyyy-mm-dd hh:mm:ss)
	 */
	protected function date2MySqlDate($sDate)
	{
		if(is_null($sDate) || ($sDate === '')) throw new MySqlQueryBuilderException("date cannot be null", MySqlQueryBuilderException::INVALID_ARGUMENT);
		if(is_numeric($sDate))
		{
			$returnValue = date("Y-m-d H:i:s", $sDate);
			if(!$returnValue) throw new MySqlQueryBuilderException("invalid date/time: $sDate", MySqlQueryBuilderException::INVALID_ARGUMENT);
			return $returnValue;
		}
		elseif(is_string($sDate))
		{
			$hour=null;$minute=null;$second=null;
			$day=null;$month=null;$year=null;
			if(!Dates::fromString($sDate, $day, $month, $year, $hour, $minute, $second))
			{
				throw new MySqlQueryBuilderException("invalid date/time: $sDate", MySqlQueryBuilderException::INVALID_ARGUMENT);
			}
			$returnValue = "$year-$month-$day";
			if($hour !== null || $minute !== null || $second !== null)
			{
				$returnValue .= ' '.Times::toString($hour, $minute,$second, "hh:mm:ss");
			}
			return $returnValue;
		}
		else throw new MySqlQueryBuilderException("date should be a numeric timestamp or a valid date string", MySqlQueryBuilderException::INVALID_ARGUMENT);
	}
	/**
	 * Transforms a time string to MYSQL format (hh:mm:ss)
	 */
	protected function time2MySqlTime($sTime)
	{
		if(is_null($sTime) || ($sTime === '')) throw new MySqlQueryBuilderException("time cannot be null", MySqlQueryBuilderException::INVALID_ARGUMENT);
		if(is_string($sTime))
		{
			$hour=null;$minute=null;$second=null;
			if(!Times::fromString($sTime, $hour, $minute, $second))
			{
				throw new MySqlQueryBuilderException("invalid time", MySqlQueryBuilderException::INVALID_ARGUMENT);
			}
			return Times::toString($hour, $minute,$second, "hh:mm:ss");
		}
		else throw new MySqlQueryBuilderException("date should be a numeric timestamp or a valid date string", MySqlQueryBuilderException::INVALID_ARGUMENT);
	}

	/**
	 * Formats a logical binary expression
	 * fieldName: complete sql field name (with table prefix if needed)
	 * op: operator, one of <, >, <=, >=, =, !=, LIKE, NOTLIKE, IN, NOTIN
	 * val: value to compare with, it can be null
	 * if IN and NOTIN are used then val must be an array.
	 * sqlType: sql type name for formatting
	 */
	public function formatBinExp($fieldName, $op, $val, $sqlType)
	{
		if(!is_string($op)) throw new MySqlFacadeException('operator must be a valid string', MySqlFacadeException::INVALID_ARGUMENT);
		$op = strtoupper($op);
		if(is_array($val))
		{
			if($op !== 'IN' && $op !== 'NOTIN') throw new MySqlFacadeException('if val is an array then operator must be IN or NOTIN', MySqlFacadeException::INVALID_ARGUMENT);
		}
		else
		{
			$val = $this->formatValue($val, $sqlType);
		}

		// if NULL
		if($val === 'NULL' || ($sqlType==MySqlQueryBuilder::SQLTYPE_BOOLEAN && $val == 0)) //add by LWR, if $val = 1 then $val == 'NULL' and this is out of sense, add by LWR if boolean is 0 then add the OR on null or false
		{
			switch($op)
			{
				case '<':
				case '>':
				case '<=':
				case '>=':
					return $fieldName.' '.$op.' 0';
				case '=':
				case 'LIKE':
				case 'MATCHAGAINST':
					if($sqlType==MySqlQueryBuilder::SQLTYPE_BOOLEAN) return '('.$fieldName.' IS NULL OR '.$fieldName.' = 0)';
					return $fieldName.' IS NULL';
				case '!=':
				case 'NOTLIKE':
					if($sqlType==MySqlQueryBuilder::SQLTYPE_BOOLEAN) return '('.$fieldName.' IS NOT NULL AND '.$fieldName.' != 0)';
					return $fieldName.' IS NOT NULL';
				case 'IN':
					return ' FALSE ';
				case 'NOTIN':
					return ' TRUE ';
					//throw new MySqlQueryBuilderException("$op operator is not compatible with NULL", MySqlQueryBuilderException::INVALID_SYNTAX);
				default:
					throw new MySqlFacadeException("$op operator is not supported", MySqlFacadeException::INVALID_ARGUMENT);
			}
		}
		// else
		{
			switch($op)
			{
				case '<':
				case '>':
				case '<=':
				case '>=':
				case '=':
				case 'LIKE':
					return $fieldName.' '.$op.' '.$val;
				case 'MATCHAGAINST':
					//return 'MATCH('.$fieldName.') AGAINST('.$val.')';
					return 'MATCH('.$fieldName.') AGAINST('."'+(*".str_replace(" ", "*) +(*", str_replace(array("-","+","~"), "", preg_replace('/\s\s+/', ' ', substr($val,1,-1))))."*)'".' IN BOOLEAN MODE)';
				case '!=':
					return '('.$fieldName.' '.$op.' '.$val.' OR '.$fieldName.' IS NULL)';
				case 'NOTLIKE':
					return '('.$fieldName.' NOT LIKE '.$val.' OR '.$fieldName.' IS NULL)';
				case 'IN':
					return $fieldName.' IN '.$this->implodeValues($val, $sqlType);
				case 'NOTIN':
					return '('.$fieldName.' NOT IN '.$this->implodeValues($val, $sqlType).' OR '.$fieldName.' IS NULL)';
				default:
					throw new MySqlFacadeException("$op operator is not supported", MySqlFacadeException::INVALID_ARGUMENT);
			}
		}
	}
	private function implodeValues($values, $sqlType)
	{
		if(empty($values) || !(is_array($values) || is_string($values))) throw new MySqlFacadeException('values must be a no null array', MySqlFacadeException::INVALID_ARGUMENT);
		$i = 1;
		$returnValues = '(';
		if(is_string($values)){
			$returnValues .= $values;
		} else {
		    $n = count($values);
			foreach($values as $v)
			{
				$returnValues .= $this->formatValue($v, $sqlType);
				if($i < $n)
				{
					$returnValues .= ', ';
				}
				$i++;
			}
		}
		return $returnValues.')';
	}

	/**
	 * Sets table where to insert values
	 */
	public function setTableForInsert($tableName)
	{
		if(isset($this->sqlIntro)) $this->reset();
		$this->sqlIntro = "INSERT INTO $tableName (";
		$this->sqlQueryType = MySqlQueryBuilder::SQLQUERYTYPE_INSERT;
	}

	/**
	 * Adds a new value to insert
	 */
	public function insertValue($fieldName, $value, $sqlType)
	{
		if(strlen($this->sqlFields) > 0)
		{
			$this->sqlFields .= ', ';
			$this->sqlValues .= ', ';
		}
		$this->sqlFields .= '`'.$fieldName.'`';
		$this->sqlValues .= $this->formatValue($value, $sqlType);
	}

	/**
	 * Prepares sql to insert one more record
	 */
	public function insertMoreRecord()
	{
		$this->sqlValues .= '),(';
	}

	/**
	 * Adds a new value to insert (when inserting more than one record at a time)
	 */
	public function insertMoreValue($value, $sqlType)
	{
		if(substr($this->sqlValues, -1) != '(') $this->sqlValues .= ', ';
		$this->sqlValues .= $this->formatValue($value, $sqlType);
	}

	/**
	 * Sets table where to update values
	 * if insertIfNotExist is true then uses MYSQL INSERT ON DUPLICATE KEY UPDATE facility
	 */
	public function setTableForUpdate($tableName, $insertIfNotExist=false)
	{
		if(isset($this->sqlIntro)) $this->reset();
		if($insertIfNotExist)
		{
			$this->sqlIntro = "INSERT INTO $tableName (";
			$this->sqlQueryType = MySqlQueryBuilder::SQLQUERYTYPE_IF_EXIST_UPDATE_ELSE_INSERT;
		}
		else
		{
			$this->sqlIntro = "UPDATE $tableName SET ";
			$this->sqlQueryType = MySqlQueryBuilder::SQLQUERYTYPE_UPDATE;
		}
	}

	/**
	 * Adds a new value to update, only if changed compared to original
	 */
	public function updateValueIfChanged($fieldName, $value, $origValue, $sqlType)
	{
		if($value != $origValue)
		{
			if($this->sqlQueryType === MySqlQueryBuilder::SQLQUERYTYPE_IF_EXIST_UPDATE_ELSE_INSERT)
			{
				$this->insertValue($fieldName, $value, $sqlType);
				if(strlen($this->sqlUpdateOnDuplicate) > 0) $this->sqlUpdateOnDuplicate .= ', ';
				$this->sqlUpdateOnDuplicate .= '`'.$fieldName.'`'.' = VALUES('.'`'.$fieldName.'`'.')';
			}
			else
			{
				if(strlen($this->sqlValues) > 0) $this->sqlValues .= ', ';
				$this->sqlValues .= '`'.$fieldName.'`'.' = '.$this->formatValue($value, $sqlType);
			}
		}
	}

	/**
	 * Adds a new value to update
	 */
	public function updateValue($fieldName, $value, $sqlType)
	{
		if($this->sqlQueryType === MySqlQueryBuilder::SQLQUERYTYPE_IF_EXIST_UPDATE_ELSE_INSERT)
		{
			$this->insertValue($fieldName, $value, $sqlType);
			if(strlen($this->sqlUpdateOnDuplicate) > 0) $this->sqlUpdateOnDuplicate .= ', ';
			$this->sqlUpdateOnDuplicate .= '`'.$fieldName.'`'.' = VALUES('.'`'.$fieldName.'`'.')';
		}
		else
		{
			if(strlen($this->sqlValues) > 0) $this->sqlValues .= ', ';
			$this->sqlValues .= '`'.$fieldName.'`'.' = '.$this->formatValue($value, $sqlType);
		}
	}
	
	/**
	 * Adds a new value to insert if not exist
	 */
	public function insertValueIfNotExist($fieldName, $value, $sqlType)
	{
		if($this->sqlQueryType === MySqlQueryBuilder::SQLQUERYTYPE_IF_EXIST_UPDATE_ELSE_INSERT)
		{
			$this->insertValue($fieldName, $value, $sqlType);
		}
		else
		{
			throw new MySqlQueryBuilderException("insertValueIfNotExist works only with MYSQL INSERT ON DUPLICATE KEY UPDATE facility", MySqlQueryBuilderException::INVALID_STATE);
		}
	}

	/**
	 * Updates last user and last modification date
	 * if timestamp is null then invokes php time function
	 */
	public function updateSysUser($principal, $timestamp=null)
	{
		$userId = $principal->getRealUserId();
		$username = $principal->getRealUsername();
		if(is_null($timestamp)) $timestamp = time();
		if($this->sqlQueryType === MySqlQueryBuilder::SQLQUERYTYPE_INSERT)
		{
			$this->insertValue('sys_date', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
			$this->insertValue('sys_user', $userId, MySqlQueryBuilder::SQLTYPE_INT);
			$this->insertValue('sys_username', $username, MySqlQueryBuilder::SQLTYPE_VARCHAR);
			$this->insertValue('sys_creationDate', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
			$this->insertValue('sys_creationUser', $userId, MySqlQueryBuilder::SQLTYPE_INT);
			$this->insertValue('sys_creationUsername', $username, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		}
		else
		{
			$this->updateValue('sys_date', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
			$this->updateValue('sys_user', $userId, MySqlQueryBuilder::SQLTYPE_INT);
			$this->updateValue('sys_username', $username, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		}
	}

	/**
	 * Sets a lock for this principal
	 * if timestamp is null then invokes principal->getMicroTime function
	 */
	public function lock($principal, $timestamp=null)
	{
		$userId = $principal->getExecutionId();
		if(is_null($timestamp)) $timestamp = $principal->getMicroTime();
		if($this->sqlQueryType === MySqlQueryBuilder::SQLQUERYTYPE_INSERT)
		{
			$this->insertValue('sys_lockMicroTime', $timestamp, MySqlQueryBuilder::SQLTYPE_INT);
			$this->insertValue('sys_lockId', $userId, MySqlQueryBuilder::SQLTYPE_VARCHAR);
		}
		else throw new MySqlQueryBuilderException("lock can only be set during INSERT, else you should use DbAdminService::lock function", MySqlQueryBuilderException::INVALID_STATE);
	}

	/**
	 * Sets table where to delete values
	 */
	public function setTableForDelete($tableName)
	{
		if(isset($this->sqlIntro)) $this->reset();
		$this->sqlIntro = "DELETE FROM $tableName ";
		$this->sqlQueryType = MySqlQueryBuilder::SQLQUERYTYPE_DELETE;
	}

	/**
	 * Sets where clause
	 */
	public function setWhereClause($whereClause)
	{
		if(isset($this->sqlWhere)) throw new MySqlQueryBuilderException('where clause is already set for this query', MySqlQueryBuilderException::INVALID_SYNTAX);
		$this->sqlWhere = $whereClause;
	}

	/**
	 * Returns where clause logical string (without WHERE keyword)
	 */
	protected function getWhereClause()
	{
		return $this->sqlWhere;
	}

	/**
	 * Sets a where clause containing idFieldName = idValue
	 */
	public function setWhereClauseSingleId($idFieldName, $idValue, $sqlType=MySqlQueryBuilder::SQLTYPE_INT)
	{
		$this->setWhereClause($this->formatBinExp($idFieldName, '=', $idValue, $sqlType));
	}

	/**
	 * Sets a where clause containing idFieldName in (idValue1, idValue2, ...)
	 */
	public function setWhereClauseMultipleId($idFieldName, $idValues, $sqlType=MySqlQueryBuilder::SQLTYPE_INT)
	{
		$this->setWhereClause($this->formatBinExp($idFieldName, 'IN', $idValues, $sqlType));
	}

	/**
	 * Returns constructed order by clause
	 * return '' if no order by key has been added
	 */
	public function getOrderByClause()
	{
		if(strlen($this->sqlOrderBy) > 0)
		{
			return "ORDER BY ".$this->sqlOrderBy;
		}
		else return '';
	}

	/**
	 * Adds a new order by key
	 */
	public function orderByCol($fieldName, $ascending=true)
	{
		if(strlen($this->sqlOrderBy) > 0) $this->sqlOrderBy .= ', ';
		$this->sqlOrderBy .= $fieldName.($ascending ? " ASC" : " DESC");
	}
}