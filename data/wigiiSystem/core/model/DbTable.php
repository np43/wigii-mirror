<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * A pointer on a database table
 * Created by CWE on 23 avril 2013
 */
class DbTable extends Model
{	
	private $dbName;
	private $tableName;
	private $tableCreationStatementDetail;
	private $temporary;
	private $userFriendlySqlColNames = false;
	private $hideDbRights = false;
	
	public static function createInstance($tableName, $dbName='', $temporary=false)
	{
		$returnValue = new DbTable();	
		$returnValue->setTableName($tableName);
		$returnValue->setDbName($dbName);
		$returnValue->setTemporary($temporary);
		return $returnValue;
	}

	public static function createInstanceForSqlCreate($dbName, $tableName, $sqlCreateStatement, $temporary=false) {
		$returnValue = new DbTable();	
		$returnValue->setTableName($tableName);
		$returnValue->setDbName($dbName);
		$returnValue->setTableCreationStatementDetail($sqlCreateStatement);
		$returnValue->setTemporary($temporary);
		return $returnValue;
	}
	
	protected function setDbName($dbName) {
		$this->dbName = $dbName;
	}
	public function getDbName() {
		return $this->dbName;
	}
	
	protected function setTableName($tableName) {
		$this->tableName = $tableName;
	}
	public function getTableName() {
		return $this->tableName;
	}
	
	protected function setTemporary($temporary) {
		$this->temporary = $temporary;
	}
	public function isTemporary() {
		return $this->temporary;
	}
	
	/**
	 * Returns the full qualified table table : dbName.tableName
	 */
	public function getTableNameWithDbName() {
		$dbName = $this->getDbName();
		if('' != $dbName) return "`".$dbName."`.`".$this->getTableName()."`";
		else return "`".$this->getTableName()."`";
	}
	
	/**
	 * Defines some sql details for the create table statement
	 * The MySqlFacade or DbAdminService will use this details when doing a create table.
	 * the generated SQL will be CREATE TABLE $dbName.$tableName $sqlDetails
	 */
	public function setTableCreationStatementDetail($sqlDetails) {
		$this->tableCreationStatementDetail = $sqlDetails;
	}
	
	public function getTableCreationStatementDetail() {
		return $this->tableCreationStatementDetail;
	}
	
	/**
	 * Informs the ElementService to name the SQL columns in the select in a user friendly way.
	 * for instance: `fieldName subFieldName Lang`
	 * If false then uses a technical encoded name.
	 */
	public function setUserFriendlySqlColNames($bool) {
		$this->userFriendlySqlColNames = $bool;
	}
	
	/**
	 * Returns true if the ElementService should name the SQL columns in the select in a user friendly way.	
	 */
	public function hasUserFriendlySqlColNames() {
		return $this->userFriendlySqlColNames;
	}
	
	/**
	 * Calling this method, will hide the DB rights column in the resulting select
	 * The columns EcanWriteElement and EcanShareElement will be calculated, but not displayed.
	 * To prevent calculating DB rights, use a Principal having a defined pRights instead.
	 */
	public function hideDbRightsInSelectedCols($bool) {
		$this->hideDbRights = $bool;
	}
	
	/**
	 * Returns true if the ElementService should hide DB rights calculated colums in the result of the select	 
	 */
	public function shouldHideDbRights() {
		return $this->hideDbRights;
	}
}


