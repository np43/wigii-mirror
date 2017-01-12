<?php
/**
 *  This file is part of Wigii.
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
 * MySql database technical facade
 * Created by CWE on 23 juin 09
 * Modified by Medair (ACA) on August 10th 2016 to use mysqli driver instead of deprecated mysql. 
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class MySqlFacade
{
	private $_debugLogger;
	private $_executionSink;
	private $sqlBuilder;

	const Q_SELECTONE = 1;
	const Q_SELECTFIRST = 2;
	const Q_SELECTALL = 3;
	const Q_UPDATE = 4;
	const Q_INSERTONE = 5;
	const Q_INSERTMULTIPLE = 6;
	const Q_DELETE = 7;
	const Q_CREATETABLE_FROMSELECT = 8;
	const Q_INSERT_FROMSELECT = 9;
	const Q_DDLCMD = 10;

	const RESULT_MODE_ASSOC = MYSQLI_ASSOC;
	const RESULT_MODE_NUM = MYSQLI_NUM;
	
	//cache for all active connection
	private $dbConnectionCache = array();
	//cache for the name of the current selected database on an open connection
	private $currentDbCache = array();

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("MySqlFacade");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("MySqlFacade");
		}
		return $this->_executionSink;
	}

	/**
	 * Returns maximum number of tables which can be joined in a query.
	 * cf. http://dev.mysql.com/doc/refman/5.0/en/joins-limits.html
	 */
	public function getMaxJoinsInQuery()
	{
		return 61;
	}

	/**
	 * returns the speed ratio between (query with multiple join)/(multiple query with one join)
	 */
	public function getMultiQuerySpeedRatio()
	{
		return 0.8;
	}

	/**
	 * Executes an sql select query and returns the first record as an array or null if no records
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * throws MySqlFacadeException in case of error
	 */
	public function selectFirst($principal, $sql, $cnxSettings, $resultType=MySqlFacade::RESULT_MODE_ASSOC)
	{
		$this->executionSink()->publishStartOperation("selectFirst", $principal);
		try
		{
			$idQuery = $this->query($sql, $cnxSettings, MySqlFacade::Q_SELECTFIRST);
			$returnValue = mysqli_fetch_array($idQuery, $resultType);
			if($returnValue === false) return null;
			$this->freeMemoryResult($idQuery);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("selectFirst", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("selectFirst", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("selectFirst", $principal);
		return $returnValue;
	}

	/**
	 * Executes an sql select query and returns the only available record as an array or null if no records
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * throws MySqlFacadeException in case of error
	 * throws MySqlFacadeException(INVALID_ARGUMENT) if number of records selected in the database is greater than one.
	 */
	public function selectOne($principal, $sql, $cnxSettings, $resultType=MySqlFacade::RESULT_MODE_ASSOC)
	{
		$this->executionSink()->publishStartOperation("selectOne", $principal);
		try
		{
			$idQuery = $this->query($sql, $cnxSettings, MySqlFacade::Q_SELECTONE);
			//controls that the result is one line or no lines but cannot be multiple:
			if(mysqli_num_rows($idQuery)>1){
				throw new MySqlFacadeException("Invalid SQL, return more than one row for selectOne:\n".$sql,MySqlFacadeException::INVALID_ARGUMENT);
			}
			$returnValue = mysqli_fetch_array($idQuery, $resultType);
			if($returnValue === false) return null;
			$this->freeMemoryResult($idQuery);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("selectOne", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("selectOne", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("selectOne", $principal);
		return $returnValue;
	}

	/**
	 * Selects first column of one record and returns cell value
	 * Useful for selects which uses aggregators like COUNT, MAX, etc.
	 */
	public function selectOneValue($principal, $sql, $cnxSettings)
	{
		$this->executionSink()->publishStartOperation("selectOneValue", $principal);
		try
		{
			$row = $this->selectOne($principal, $sql, $cnxSettings, MySqlFacade::RESULT_MODE_NUM);
			if(is_null($row)) throw new MySqlFacadeException("Invalid SQL, return no row for selectOneValue:\n".$sql,MySqlFacadeException::INVALID_ARGUMENT);
			else $returnValue = $row[0];
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("selectOneValue", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("selectOneValue", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("selectOneValue", $principal);
		return $returnValue;
	}

	/**
	 * Executes an sql select query and fills a list of records
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * rowList: a list of rows to be filled
	 * resultType: MYSQL result type for each record, column name association or column number association. Default to column name association.
	 * returns the number of records selected.
	 * throws MySqlFacadeException in case of error
	 */
	public function selectAll($principal, $sql, $cnxSettings, $rowList, $resultType=MySqlFacade::RESULT_MODE_ASSOC)
	{
		$this->executionSink()->publishStartOperation("selectAll", $principal);
		$n = 0;
		try
		{
			if(!isset($rowList)) throw new MySqlFacadeException("rowList cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start selectAll query"] = microtime(true);
			$idQuery = $this->query($sql, $cnxSettings, MySqlFacade::Q_SELECTALL);
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."selectAll query is done"] = microtime(true);
//			fput($sql);
//			fput($idQuery);
//			fput(mysqli_num_rows($idQuery));
			while($row = mysqli_fetch_array($idQuery, $resultType)){
//				$this->debugLogger()->write("row ".$n." is fetched");
				//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."row $n is fetched"] = microtime(true);
				$rowList->addRow($row);
//				$this->debugLogger()->write("row ".$n." is added");
				//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."row $n is added"] = microtime(true);
				$n++;
			}
			$this->freeMemoryResult($idQuery);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("selectAll", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("selectAll", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("selectAll", $principal);
		return $n;
	}

	/**
	 * Executes a paged sql select query and fills a list of records
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * rowList: a list of rows to be filled
	 * offset: offset of page start, starting with 0, see MYSQL LIMIT reference
	 * pageSize: number maximum of records per page to return, see MYSQL LIMIT reference
	 * resultType: MYSQL result type for each record, column name association or column number association. Default to column name association.
	 *
	 * returns the total number of records that would have been selected if no page was used.
	 * throws MySqlFacadeException in case of error
	 *
	 * postcondition: automatically adds to sql query the SQL_CALC_FOUND_ROWS and LIMIT clauses
	 */
	public function selectPage($principal, $sql, $cnxSettings, $offset, $pageSize, $rowList, $resultType=MySqlFacade::RESULT_MODE_ASSOC)
	{
		$this->executionSink()->publishStartOperation("selectPage", $principal);
		$n = 0;
		try
		{
			if(!isset($rowList)) throw new MySqlFacadeException("rowList cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
			// checks integer validity of offset and pageSize to prevent sql injection
			if(0 > $offset) throw new MySqlFacadeException("offset must be a number greater or equal to 0", MySqlFacadeException::INVALID_ARGUMENT);
			if(0 >= $pageSize) throw new MySqlFacadeException("pageSize must be a number greater or equal to 0", MySqlFacadeException::INVALID_ARGUMENT);

			// adds SQL_CALC_FOUND_ROWS clause
			$sql = substr_replace($sql, 'SELECT SQL_CALC_FOUND_ROWS ', 0, 6);
			// adds LIMIT clause
			$sql .= " LIMIT $offset, $pageSize";

			$idQuery = $this->query($sql, $cnxSettings, MySqlFacade::Q_SELECTALL);

			// gets row count
			$idRowCountQuery = $this->query('SELECT FOUND_ROWS() AS NROWS;', $cnxSettings, MySqlFacade::Q_SELECTONE);
			if($row = mysqli_fetch_array($idRowCountQuery, MySqlFacade::RESULT_MODE_ASSOC))
			{
				$n = $row['NROWS'];
				$this->freeMemoryResult($idRowCountQuery);
			}
			else {throw new MySqlFacadeException('expected to select SQL FOUND_ROWS(), but nothing.', MySqlFacadeException::UNEXPECTED_ERROR);}

			// gets records
			while($row = mysqli_fetch_array($idQuery, $resultType)){
				$rowList->addRow($row);
			}

			$this->freeMemoryResult($idQuery);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("selectPage", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("selectPage", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("selectPage", $principal);
		return $n;
	}

	/**
	 * Inserts multiple records in one query.
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns the last insert id
	 * throws MySqlFacadeException in case of error
	 */
	public function insertMultiple($principal, $sql, $cnxSettings){
		$this->executionSink()->publishStartOperation("insertMultiple", $principal);
		try
		{
			$returnValue = $this->query($sql, $cnxSettings, MySqlFacade::Q_INSERTMULTIPLE);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("insertMultiple", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("insertMultiple", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("insertMultiple", $principal);
		return $returnValue;
	}

	/**
	 * Inserts one record.
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns the insert id
	 * throws MySqlFacadeException in case of error
	 */
	public function insertOne($principal, $sql, $cnxSettings){
		$this->executionSink()->publishStartOperation("insertOne", $principal);
		try
		{
			$returnValue = $this->query($sql, $cnxSettings, MySqlFacade::Q_INSERTONE);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("insertOne", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("insertOne", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("insertOne", $principal);
		return $returnValue;
	}

	/**
	 * Inserts one record if it does not exist yet in the database
	 * insertSql: sql query used to insert the record
	 * uniqueConstraintSql: sql query used to check unicity,
	 * constraint is respected if this query does not return any record
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns the insert id
	 * throws MySqlFacadeException in case of error
	 * 		with code MYSQL_NOTUNIQUE if unique constraint is violated
	 * poscondition: if uniqueConstraintSql does not return any record then 1 record is inserted, else 0
	 */
	public function insertIfUnique($principal, $insertSql, $uniqueConstraintSql, $cnxSettings)
	{
		$this->executionSink()->publishStartOperation("insertIfUnique", $principal);
		try
		{
			if(!is_null($this->selectOne($principal, $uniqueConstraintSql, $cnxSettings)))
			{
				throw new MySqlFacadeException('a same record already exists in database, can not insert duplicate', MySQLFacadeException::MYSQL_NOTUNIQUE);
			}
			$returnValue = $this->insertOne($principal, $insertSql, $cnxSettings);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("insertIfUnique", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("insertIfUnique", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("insertIfUnique", $principal);
		return $returnValue;
	}

	/**
	 * If a record exists in the database then updates it else inserts it
	 * existanceSql: sql query used to check record existance
	 * updateSql: sql query used to insert the record
	 * insertSql: sql query used to insert the record
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns effective number of records inserted or updated (0 or 1)
	 * throws MySqlFacadeException in case of error
	 */
	public function ifExistUpdateElseInsert($principal, $existanceSql, $updateSql, $insertSql, $cnxSettings)
	{
		$this->executionSink()->publishStartOperation("ifExistUpdateElseInsert", $principal);
		try
		{
			if(!is_null($this->selectOne($principal, $existanceSql, $cnxSettings)))
			{
				$returnValue = $this->update($principal, $updateSql, $cnxSettings);
			}
			else
			{
				$this->insertOne($principal, $insertSql, $cnxSettings);
				$returnValue = 1;
			}
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("ifExistUpdateElseInsert", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("ifExistUpdateElseInsert", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("ifExistUpdateElseInsert", $principal);
		return $returnValue;
	}

	/**
	 * Deletes one or several records in one query.
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns the number of affected rows
	 * throws MySqlFacadeException in case of error
	 */
	public function delete($principal, $sql, $cnxSettings){
		$this->executionSink()->publishStartOperation("delete", $principal);
		try
		{
			$returnValue = $this->query($sql, $cnxSettings, MySqlFacade::Q_DELETE);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("delete", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("delete", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("delete", $principal);
		return $returnValue;
	}

	/**
	 * Updates one or several records in one query.
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns the number of affected rows
	 * throws MySqlFacadeException in case of error
	 */
	public function update($principal, $sql, $cnxSettings){
		$this->executionSink()->publishStartOperation("update", $principal);
		try
		{
			$returnValue = $this->query($sql, $cnxSettings, MySqlFacade::Q_UPDATE);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("update", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("update", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("update", $principal);
		return $returnValue;
	}

	/**
	 * Updates a record if it is unique in the database
	 * updateSql: sql query used to update the record
	 * uniqueConstraintSql: sql query used to check unicity,
	 * constraint is respected if this query does not return any record
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns the number of records updated
	 * throws MySqlFacadeException in case of error
	 * 		with code MYSQL_NOTUNIQUE if unique constraint is violated
	 * poscondition: if uniqueConstraintSql does not return any record then the records are updated, else 0
	 */
	public function updateIfUnique($principal, $updateSql, $uniqueConstraintSql, $cnxSettings)
	{
		$this->executionSink()->publishStartOperation("updateIfUnique", $principal);
		try
		{
			if(!is_null($this->selectOne($principal, $uniqueConstraintSql, $cnxSettings)))
			{
				throw new MySqlFacadeException('a same record already exists in database, can not insert duplicate', MySQLFacadeException::MYSQL_NOTUNIQUE);
			}
			$returnValue = $this->update($principal, $updateSql, $cnxSettings);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("updateIfUnique", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("updateIfUnique", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("updateIfUnique", $principal);
		return $returnValue;
	}

	/**
	 * Updates the value of a field in a table.
	 * @param Principal $principal principal running the update
	 * @param FieldSelector $fieldSelector field selector representing the field to update in the database (logical field)
	 * @param String $tableName table name on which to do the update
	 * @param LogExp $fsLogExp field selector log exp to select the records for which to update the field
	 * @param String $newValue the new value to be updated
	 * @param String $updateOp update operation, see constants above. One of SUPDOP_SET, SUPDOP_ADD, SUPDOP_CAT, SUPDOP_DEL
	 * @param FieldSelectorLogExpSqlBuilder $fsLogExpSqlBuilder field selector log exp sql builder to build sql query for select and update
	 * @param FieldSelector $idFieldSelector field selector representing id column
	 * @param DbConnectionSettings $cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * @param String readSep separator regExp used in preg_split when reading existing values in database
	 * @param String writeSep separator to be used when adding new value to existing field
	 * @return the number of records updated
	 * @throws MySqlFacadeException in case of error
	 */
	public function updateField($principal, $fieldSelector, $tableName, $fsLogExp,
								$newValue, $updateOp, $fsLogExpSqlBuilder, $idFieldSelector, $cnxSettings,
								$readSep='/[\\s,;\\/:\\\\]+/', $writeSep=',')
	{
		$this->executionSink()->publishStartOperation("updateField", $principal);
		try
		{
			if(is_null($fieldSelector)) throw new MySqlFacadeException("fieldSelector cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
			if(is_null($tableName)) throw new MySqlFacadeException("tableName cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
			if(is_null($updateOp)) throw new MySqlFacadeException("updateOp cannot be null, should be one of SET,ADD,CAT or DEL", MySqlFacadeException::INVALID_ARGUMENT);
			if($updateOp != SUPDOP_SET &&
				$updateOp != SUPDOP_ADD &&
				$updateOp != SUPDOP_DEL &&
				$updateOp != SUPDOP_CAT) throw new MySqlFacadeException("updateOp should be one of SET,ADD,CAT or DEL", MySqlFacadeException::INVALID_ARGUMENT);
			if(is_null($fsLogExpSqlBuilder)) throw new MySqlFacadeException("fsLogExpSqlBuilder should be a non null instance of FieldSelectorLogExpSqlBuilder", MySqlFacadeException::INVALID_ARGUMENT);
			if(is_null($idFieldSelector)) throw new MySqlFacadeException("idFieldSelector cannot be null", MySqlFacadeException::INVALID_ARGUMENT);

			// if operator is SET then updates in one shot
			if($updateOp == SUPDOP_SET)
			{
				if(is_array($newValue)) $newValue = implode($writeSep, $newValue);
				$fsLogExpSqlBuilder->reset();
				$fsLogExpSqlBuilder->setTableForUpdate($tableName);
				$selectedField = $fsLogExpSqlBuilder->getSqlColumnNameForFieldSelector($fieldSelector);
				$fieldSelectorSqlType = $fsLogExpSqlBuilder->getSqlDataTypeForFieldSelector($fieldSelector);
				$fsLogExpSqlBuilder->updateValue($selectedField, $newValue, $fieldSelectorSqlType);
				if($fsLogExpSqlBuilder->supportsSysInformation()) $fsLogExpSqlBuilder->updateSysUser($principal);
				$fsLogExpSqlBuilder->setWhereClauseFieldSelectorLogExp($fsLogExp);
				$sql = $fsLogExpSqlBuilder->getSql();
				if(!is_null($sql)) $returnValue = $this->update($principal, $sql, $cnxSettings); else $returnValue = 0;
			}
			// else iterates through each row
			else
			{
				// creates sql to select the records to update
				$fsLogExpSqlBuilder->reset();
				$whereClause = $fsLogExpSqlBuilder->buildWhereClause($fsLogExp);
				$tableAlias = $fsLogExpSqlBuilder->getTableAliasForFieldSelector($fieldSelector);
				$idField = $fsLogExpSqlBuilder->getSqlColumnNameForFieldSelector($idFieldSelector);
				$selectedField = $fsLogExpSqlBuilder->getSqlColumnNameForFieldSelector($fieldSelector);
				if(!is_null($tableAlias) || $tableAlias != '')
				{
					$sql = "select $tableAlias.$idField, $tableAlias.$selectedField from $tableName as $tableAlias where $whereClause";
				}
				else
				{
					$sql = "select $idField, $selectedField from $tableName where $whereClause";
				}

				// selects the records to update
				$rows = RowListArrayImpl::createInstance($idField);
				$returnValue = $this->selectAll($principal, $sql, $cnxSettings, $rows);

				if($returnValue > 0)
				{
					$returnValue = 0;
					$fieldSelectorSqlType = $fsLogExpSqlBuilder->getSqlDataTypeForFieldSelector($fieldSelector);
					$idFieldSqlType = $fsLogExpSqlBuilder->getSqlDataTypeForFieldSelector($idFieldSelector);
					// goes through each record
					foreach($rows->getListIterator() as $id => $row)
					{
						$val = $row[$selectedField];
						// changes the value
						$newVal = str_update($val, $newValue, $updateOp, $readSep, $writeSep);
						// updates the value in the database if changed
						$fsLogExpSqlBuilder->reset();
						$fsLogExpSqlBuilder->setTableForUpdate($tableName);
						$fsLogExpSqlBuilder->updateValueIfChanged($selectedField, $newVal, $val, $fieldSelectorSqlType);
						if($fsLogExpSqlBuilder->supportsSysInformation()) $fsLogExpSqlBuilder->updateSysUser($principal);
						$fsLogExpSqlBuilder->setWhereClauseSingleId($idField, $id, $idFieldSqlType);
						$sql = $fsLogExpSqlBuilder->getSql();
						if(!is_null($sql)) $returnValue += $this->update($principal, $sql, $cnxSettings);
					}
				}
			}
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("updateField", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("updateField", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("updateField", $principal);
		return $returnValue;
	}

	/**
	 * Returns a MySQL SQL builder
	 */
	public function getSqlBuilder()
	{
		if(!isset($this->sqlBuilder))
		{
			$this->sqlBuilder = $this->createSqlBuilder();
		}
		$this->sqlBuilder->reset();
		return $this->sqlBuilder;
	}
	/**
	 * Defaults to MySqlQueryBuilder
	 */
	protected function createSqlBuilder()
	{
		return new MySqlQueryBuilder();
	}

	/**
	 * Executes a paged sql select query and creates a new db table with the results
	 * dbTable : a DbTable model specifying the table to be created
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * offset: offset of page start, starting with 0, see MYSQL LIMIT reference
	 * pageSize: number maximum of records per page to return, see MYSQL LIMIT reference
	 *
	 * returns the total number of records that would have been selected if no page was used.
	 * throws MySqlFacadeException in case of error
	 *
	 * postcondition: automatically adds to sql query the SQL_CALC_FOUND_ROWS and LIMIT clauses
	 */
	public function createTableFromSelectPage($principal, $dbTable, $sql, $cnxSettings, $offset, $pageSize) {
		$this->executionSink()->publishStartOperation("createTableFromSelectPage", $principal);
		$n = 0;
		try
		{
			if(!isset($dbTable)) throw new MySqlFacadeException("dbTable cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
			// checks integer validity of offset and pageSize to prevent sql injection
			if(0 > $offset) throw new MySqlFacadeException("offset must be a number greater or equal to 0", MySqlFacadeException::INVALID_ARGUMENT);
			if(0 >= $pageSize) throw new MySqlFacadeException("pageSize must be a number greater or equal to 0", MySqlFacadeException::INVALID_ARGUMENT);

			// adds SQL_CALC_FOUND_ROWS clause
			$sql = substr_replace($sql, 'SELECT SQL_CALC_FOUND_ROWS ', 0, 6);
			// adds LIMIT clause
			$sql .= " LIMIT $offset, $pageSize";

			// CREATE TABLE SELECT
			if($dbTable->isTemporary()) $sql2 = "CREATE TEMPORARY TABLE ";
			else $sql2 = "CREATE TABLE ";
			$sql2 .= $dbTable->getTableNameWithDbName()." ".$dbTable->getTableCreationStatementDetail()." ".$sql;
			$this->query($sql2, $cnxSettings, MySqlFacade::Q_CREATETABLE_FROMSELECT);

			// gets row count
			$idRowCountQuery = $this->query('SELECT FOUND_ROWS() AS NROWS;', $cnxSettings, MySqlFacade::Q_SELECTONE);
			if($row = mysqli_fetch_array($idRowCountQuery, MySqlFacade::RESULT_MODE_ASSOC))
			{
				$n = $row['NROWS'];
				$this->freeMemoryResult($idRowCountQuery);
			}
			else {throw new MySqlFacadeException('expected to select SQL FOUND_ROWS(), but nothing.', MySqlFacadeException::UNEXPECTED_ERROR);}
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("createTableFromSelectPage", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("createTableFromSelectPage", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("createTableFromSelectPage", $principal);
		return $n;
	}
	/**
	 * Executes a paged sql select query and inserts the results in an existing a db table
	 * dbTable : a DbTable model specifying the table where to do the insert
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * offset: offset of page start, starting with 0, see MYSQL LIMIT reference
	 * pageSize: number maximum of records per page to return, see MYSQL LIMIT reference
	 *
	 * returns the total number of records that would have been selected if no page was used.
	 * throws MySqlFacadeException in case of error
	 *
	 * postcondition: automatically adds to sql query the SQL_CALC_FOUND_ROWS and LIMIT clauses
	 */
	public function insertIntoTableFromSelectPage($principal, $dbTable, $sql, $cnxSettings, $offset, $pageSize) {
		$this->executionSink()->publishStartOperation("insertIntoTableFromSelectPage", $principal);
		$n = 0;
		try
		{
			if(!isset($dbTable)) throw new MySqlFacadeException("dbTable cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
			// checks integer validity of offset and pageSize to prevent sql injection
			if(0 > $offset) throw new MySqlFacadeException("offset must be a number greater or equal to 0", MySqlFacadeException::INVALID_ARGUMENT);
			if(0 >= $pageSize) throw new MySqlFacadeException("pageSize must be a number greater or equal to 0", MySqlFacadeException::INVALID_ARGUMENT);

			// adds SQL_CALC_FOUND_ROWS clause
			$sql = substr_replace($sql, 'SELECT SQL_CALC_FOUND_ROWS ', 0, 6);
			// adds LIMIT clause
			$sql .= " LIMIT $offset, $pageSize";

			// INSERT INTO TABLE SELECT
			$sql2 = "INSERT INTO ".$dbTable->getTableNameWithDbName()." ".$sql;
			$this->query($sql2, $cnxSettings, MySqlFacade::Q_INSERT_FROMSELECT);

			// gets row count
			$idRowCountQuery = $this->query('SELECT FOUND_ROWS() AS NROWS;', $cnxSettings, MySqlFacade::Q_SELECTONE);
			if($row = mysqli_fetch_array($idRowCountQuery, MySqlFacade::RESULT_MODE_ASSOC))
			{
				$n = $row['NROWS'];
				$this->freeMemoryResult($idRowCountQuery);
			}
			else {throw new MySqlFacadeException('expected to select SQL FOUND_ROWS(), but nothing.', MySqlFacadeException::UNEXPECTED_ERROR);}
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("insertIntoTableFromSelectPage", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("insertIntoTableFromSelectPage", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("insertIntoTableFromSelectPage", $principal);
		return $n;
	}
	/**
	 * Executes an sql select query and creates a new db table with the results
	 * dbTable : a DbTable model specifying the table to be created
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns the number of records inserted.
	 * throws MySqlFacadeException in case of error
	 */
	public function createTableFromSelectAll($principal, $dbTable, $sql, $cnxSettings) {
		$this->executionSink()->publishStartOperation("createTableFromSelectAll", $principal);
		try
		{
			if(!isset($dbTable)) throw new MySqlFacadeException("dbTable cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
			if($dbTable->isTemporary()) $sql2 = "CREATE TEMPORARY TABLE ";
			else $sql2 = "CREATE TABLE ";
			$sql2 .= $dbTable->getTableNameWithDbName()." ".$dbTable->getTableCreationStatementDetail()." ".$sql;
			$returnValue = $this->query($sql2, $cnxSettings, MySqlFacade::Q_CREATETABLE_FROMSELECT);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("createTableFromSelectAll", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("createTableFromSelectAll", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("createTableFromSelectAll", $principal);
		return $returnValue;
	}
	/**
	 * Executes an sql select query and inserts the results in an existing a db table
	 * dbTable : a DbTable model specifying the table where to do the insert
	 * cnxSettings a DbConnectionSettings object with everything needed to connect to the database
	 * returns the number of records inserted.
	 * throws MySqlFacadeException in case of error
	 */
	public function insertIntoTableFromSelectAll($principal, $dbTable, $sql, $cnxSettings) {
		$this->executionSink()->publishStartOperation("insertIntoTableFromSelectAll", $principal);
		try
		{
			if(!isset($dbTable)) throw new MySqlFacadeException("dbTable cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
			$sql2 = "INSERT INTO ".$dbTable->getTableNameWithDbName()." ".$sql;
			$returnValue = $this->query($sql2, $cnxSettings, MySqlFacade::Q_INSERT_FROMSELECT);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("insertIntoTableFromSelectAll", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("insertIntoTableFromSelectAll", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("insertIntoTableFromSelectAll", $principal);
		return $returnValue;
	}

	/**
	 * Executes a DDL command
	 * ddlSql: Data Definition Language SQL statement like CREATE, DROP, ALTER, TRUNCATE
	 * cnxSettings: a DbConnectionSettings object with everything needed to connect to the database
	 * returns true in case of success else throws MySqlFacadeException in case of error
	 */
	public function execDDLCmd($principal, $ddlSql, $cnxSettings) {
		$this->executionSink()->publishStartOperation("execDDLCmd", $principal);
		try
		{
			$this->query($ddlSql, $cnxSettings, MySqlFacade::Q_DDLCMD);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("execDDLCmd", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("execDDLCmd", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("execDDLCmd", $principal);
		return true;
	}

	/**
	 * Executes all the queries stored into a query list and updates the list with the results.
	 * It uses only one MySql connection to execute sequentially all queries,
	 * therefore it supports transactions and temporary tables.
	 * Be carefull to drop temporary tables at the end because the connection can be reused for other queries.
	 * @param Principal $principal authenticated user performing the operation
	 * @param DbQueryList $queryList a list of queries to execute. Suppors only queries of type :
	 * MySqlFacade::Q_UPDATE, MySqlFacade::Q_INSERTONE, MySqlFacade::Q_INSERTMULTIPLE, MySqlFacade::Q_DELETE,
	 * MySqlFacade::Q_CREATETABLE_FROMSELECT, MySqlFacade::Q_INSERT_FROMSELECT or MySqlFacade::Q_DDLCMD
	 * Doesn't support any SELECT command.
	 * @param DbConnectionSettings $cnxSettings the db connection settings to use.
	 * @throws MySqlFacadeException in case of error.
	 * @return the number of executed queries
	 */
	public function execQueryList($principal, $queryList, $cnxSettings) {
		$this->executionSink()->publishStartOperation("execQueryList", $principal);
		try
		{
			if(!isset($queryList)) throw new MySqlFacadeException('queryList cannot be null', MySqlFacadeException::INVALID_ARGUMENT);
			// validates query list
			$returnValue = 0;
			foreach($queryList->getListIterator() as $query) {
				if(!isset($query)) throw new MySqlFacadeException("query cannot be null", MySqlFacadeException::INVALID_ARGUMENT);
				switch($query->getQueryType()) {
					case MySqlFacade::Q_SELECTALL:
					case MySqlFacade::Q_SELECTFIRST:
					case MySqlFacade::Q_SELECTONE:
						throw new MySqlFacadeException("Invalid query type. Should be one of MySqlFacade::Q_UPDATE, MySqlFacade::Q_INSERTONE, MySqlFacade::Q_INSERTMULTIPLE, MySqlFacade::Q_DELETE, MySqlFacade::Q_CREATETABLE_FROMSELECT, MySqlFacade::Q_INSERT_FROMSELECT or MySqlFacade::Q_DDLCMD", MySqlFacadeException::INVALID_ARGUMENT);
				}
				$s = $query->getSql();
				if(empty($s)) throw new MySqlFacadeException("query SQL statement cannot be empty.", MySqlFacadeException::INVALID_ARGUMENT);
				$returnValue++;
			}
			if($returnValue > 0) {
				// gets a connection
				$mysqlId = $this->connect($cnxSettings);
				// executes query list
				$returnValue = 0;
				foreach($queryList->getListIterator() as $query) {
					$query->setResult($this->query($query->getSql(), $mysqlId, $query->getQueryType()));
					$returnValue++;
				}
			}
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("execQueryList", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("execQueryList", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("execQueryList", $principal);
		return $returnValue;
	}

	/**
	 * Replicates a whole DB table from one database to another one.
	 * Precondition: The destination table should not exist.
	 * Poscondition: The destination table is created and populated with original data.
	 * @param $principal principal doing the replication
	 * @param $dbTable contains the name of the table to replicate and the name of the destination database.
	 * @param $cnxSettings the database connection needed to fetch the original table.
	 * @throws MySqlFacadeException in case of error.
	 */
	public function replicateWholeDbTable($principal, $dbTable, $cnxSettings) {
		$this->executionSink()->publishStartOperation("replicateWholeDbTable", $principal);
		try
		{
			$this->createTableFromSelectAll($principal, $dbTable, "SELECT * FROM `".$dbTable->getTableName()."`", $cnxSettings);
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("replicateWholeDbTable", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("replicateWholeDbTable", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("replicateWholeDbTable", $principal);
	}

	/**
	 * Replicates for one db table only the new records present in source database
	 * and not present in destination database.
	 * Selection is done on all records in source database having a primary key greater than
	 * the biggest primary key in the destination database.
	 * Precondition: The destination table should exist.
	 * Postcondition: Selection of source records are inserted in the existing destination table.
	 * @param $principal doing the replication
	 * @param $dbTable contains the name of the table to replicate and the name of the destination database.
	 * @param $primaryKeyColName the name of the primary key on which new records are selected
	 * @param $cnxSettings the database connection needed to fetch the new records in source database.
	 * @throws MySqlFacadeException in case of error,
	 * in particular code 1146 'table does not exist' if destination table doesn't exist.
	 */
	public function replicateOnlyNewRecords($principal, $dbTable, $primaryKeyColName, $cnxSettings) {
		$this->executionSink()->publishStartOperation("replicateOnlyNewRecords", $principal);
		try
		{
			//1. selects last inserted id
			$lastInsertedId = $this->selectOneValue($principal,
				"select max($primaryKeyColName) from ".$dbTable->getTableNameWithDbName(),
				$cnxSettings);
			//2. inserts new records
			if(isset($lastInsertedId)) {
				$this->insertIntoTableFromSelectAll($principal, $dbTable,
				"select * from `".$dbTable->getTableName()."` where $primaryKeyColName > $lastInsertedId",
				$cnxSettings);
			}
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("replicateOnlyNewRecords", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("replicateOnlyNewRecords", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("replicateOnlyNewRecords", $principal);
	}

	/**
	 * Tries to call replicateOnlyNewRecords, if the method fails with a SQL 1146 code (table does not exist),
	 * then calls replicateWholeTable.
	 */
	public function replicateOnlyNewRecordsCreateTableIfNotExist($principal, $dbTable, $primaryKeyColName, $cnxSettings) {
		$this->executionSink()->publishStartOperation("replicateOnlyNewRecordsCreateTableIfNotExist", $principal);
		try
		{
			//1. tries to insert the new records
			try {
				$this->replicateOnlyNewRecords($principal, $dbTable, $primaryKeyColName, $cnxSettings);
			}
			//2. if table does not exist, then creates it.
			catch(MySqlFacadeException $sqle1) {
				if($sqle1->getMySqlErrorNo() == MySqlFacadeException::MYSQL_ERROR_NO_SUCH_TABLE) {
					$this->replicateWholeDbTable($principal, $dbTable, $cnxSettings);
				}
				else throw $sqle1;
			}
		}
		catch(MySqlFacadeException $sqle)
		{
			$this->executionSink()->publishEndOperationOnError("replicateOnlyNewRecordsCreateTableIfNotExist", $sqle, $principal);
			throw $sqle;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("replicateOnlyNewRecordsCreateTableIfNotExist", $e, $principal);
			throw new MySqlFacadeException('',MySqlFacadeException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("replicateOnlyNewRecordsCreateTableIfNotExist", $principal);
	}

	/**
	 * Executes an sql query
	 * return an integer depending on the queryType:
	 *  - insertOne, insertMultiple: last insert id
	 *  - selectFirst, One, All: idQuery (instance of mysqli_result)
	 *  - update, delete: num affected rows
	 *  - create table from select, insert into table from select: num affected rows
	 *  - ddl cmd : 1
	 *  @param DbConnectionSettings|resource $cnxSettings the DbConnectionSettings used to establish a connection
	 *  or it can also be an existing open MySqlConnection obtained by calling the 'MySqlFacade::connect' method.
	 */
	protected function query($sql, $cnxSettings, $queryType)
	{		
		if($cnxSettings instanceof mysqli) $mysqlId = $cnxSettings;
		else $mysqlId = $this->connect($cnxSettings);

		$idQuery = mysqli_query($mysqlId, $sql);
		if($this->debugLogger()->isEnabled())
		{
			$this->debugLogger()->write("Executes SQL query : ".$sql);
		}
		if($idQuery === false){
			$this->debugLogger()->write("Invalid SQL:".mysqli_error($mysqlId)."\nMySqlErrorNo:".mysqli_errno($mysqlId));
			throw new MySqlFacadeException("Invalid SQL: ".$sql,MySqlFacadeException::MYSQL_SQLERROR, null, mysqli_error($mysqlId),  mysqli_errno($mysqlId));
		}
		$returnValue = 0;
		switch($queryType)
		{
			case MySqlFacade::Q_INSERTONE:
			case MySqlFacade::Q_INSERTMULTIPLE:
				$returnValue = mysqli_insert_id($mysqlId);
				break;
			case MySqlFacade::Q_SELECTFIRST:
			case MySqlFacade::Q_SELECTONE:
			case MySqlFacade::Q_SELECTALL:
				$returnValue = $idQuery;
				break;
			case MySqlFacade::Q_UPDATE:
			case MySqlFacade::Q_DELETE:
			case MySqlFacade::Q_CREATETABLE_FROMSELECT:
			case MySqlFacade::Q_INSERT_FROMSELECT:
				$returnValue = mysqli_affected_rows($mysqlId);
				break;
			case MySqlFacade::Q_DDLCMD:
				$returnValue = 1;
				break;
			default: throw new MySqlFacadeException("Invalid queryType ".$queryType,MySqlFacadeException::INVALID_ARGUMENT);
		}
		return $returnValue;
	}


	// db connection management

	/**
	 * Frees the result memory after having finished to fetch data from a query
	 * this needs to be used only after queries as SELECT, SHOW, EXPLAIN and DESCRIBE
	 */
	protected function freeMemoryResult($idQuery){
		mysqli_free_result($idQuery);
		$this->debugLogger()->write("MySqlFacade freeMemoryResult successful for query");
	}

	/**
	 * Connects to mysql server
	 */
	protected function connect($cnxSettings)
	{
		$returnValue = $this->getCachedDbConnection($cnxSettings);
		if(!isset($returnValue))
		{
			$returnValue = mysqli_connect($cnxSettings->getHost(), $cnxSettings->getUsername(), $cnxSettings->getPassword());
			if(!$returnValue) throw new MySqlFacadeException("Connection to database with: ".$cnxSettings->displayDebug()." failed.", MySqlFacadeException::INVALID_ARGUMENT);
			if(!mysqli_select_db($returnValue, $cnxSettings->getDbName())){
				throw new MySqlFacadeException("No database: ".$cnxSettings->getDbName()." on host: ".$cnxSettings->getHost(), MySqlFacadeException::INVALID_ARGUMENT);
			}
			//set the charset to UTF8
			// 10.08.2016: lets server manage UTF8 details to handle correctly emoticons in text
			// 11.08.2016: regression on datatype Files with htmlArea=1 => force again UTF8
			mysqli_query($returnValue, "SET NAMES utf8;");
			mysqli_query($returnValue, "SET CHARACTER SET utf8;");
			$this->cacheDbConnection($cnxSettings, $returnValue);
		}
		return $returnValue;
	}

	// Cache management

	protected function getCachedDbConnection($cnxSettings)
	{
		if(!isset($this->dbConnectionCache)) return null;
		$key = $this->getDbConnectionCacheKey($cnxSettings);
		$returnValue = $this->dbConnectionCache[$key];
		if(!isset($returnValue)) return null;
		// changes the active database if new one differs from active one
		$dbName = $cnxSettings->getDbName();
		if($this->currentDbCache[$key] != $dbName) {
			if(!mysqli_select_db($returnValue, $dbName)){
				throw new MySqlFacadeException("No database: ".$dbName." on host: ".$cnxSettings->getHost(), MySqlFacadeException::INVALID_ARGUMENT);
			}
			$this->currentDbCache[$key] = $dbName;
			if($this->debugLogger()->isEnabled()) $this->debugLogger()->write('changes current db to '.$dbName);
		}
		if($this->debugLogger()->isEnabled()) $this->debugLogger()->write('found DB connection '.mysqli_thread_id($returnValue).' for '.$dbName);
		return $returnValue;
	}

	protected function cacheDbConnection($cnxSettings, $dbConnection)
	{
		if(is_null($cnxSettings)) return;
		$key = $this->getDbConnectionCacheKey($cnxSettings);
		$this->dbConnectionCache[$key] = $dbConnection;
		$this->currentDbCache[$key] = $cnxSettings->getDbName();
		if($this->debugLogger()->isEnabled()) $this->debugLogger()->write("stores DB connection\n".mysqli_thread_id($dbConnection)."\n".$cnxSettings->displayDebug());
	}

	/**
	 * Returns (hostname/username)
	 */
	protected function getDbConnectionCacheKey($cnxSettings)
	{
		return '('.$cnxSettings->getHost().'/'.$cnxSettings->getUsername().')';
	}

}
