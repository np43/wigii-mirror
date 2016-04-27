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
 * Fills a database table given selected db rows
 * Created by CWE on 23 avril 2013
 */
class DbTableFiller extends ElementPMapper implements RecordStructureFactory
{
	private $_debugLogger;	
	private $dbTable;
	private $createTable;
	private $tempTablesToDrop;
	private $nbRows;
	
	private $sqlJoin;
	private $sqlTempTableFields;
	private $sqlJoinFields;
	private $sqlJoinFirstTableName;	
	private $maxSqlJoin = 1;
	private $currentSqlJoin;
	public $fieldCounter;
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("DbTableFiller");
		}
		return $this->_debugLogger;
	}

	// Object life cycle

	/**
	 * @param $dbTable DB table model of the table to be created or inserted
	 * @param $createTable if true then does CREATE TABLE SELECT 
	 * 					   else does INSERT INTO TABLE SELECT 
	 */
	public static function createInstance($principal, $dbTable, $createTable)
	{
		$returnValue = new DbTableFiller();
		$returnValue->reset($principal, $dbTable, $createTable);
		return $returnValue;
	}

	public function reset($principal, $dbTable, $createTable)
	{
		parent::reset($principal, $this);
		$this->dbTable = $dbTable;
		$this->createTable=$createTable;
		$this->nbRows = 0;			
		$this->sqlTempTableFields = array();
		$this->currentSqlJoin = 0;	
		$this->fieldCounter = 0;		
	}

	public function freeMemory()
	{
		$this->tempTablesToDrop = '';
		$this->sqlJoin = '';
		$this->sqlJoinFields = '';
		$this->sqlJoinFirstTableName = '';
		unset($this->sqlTempTableFields);
		parent::freeMemory();		
	}	

	// Implementation
	
	public function shouldCreateTable() {
		return $this->createTable;
	}
	
	public function getDbTable() {
		return $this->dbTable;
	}
	
	public function getNbRows() {
		return $this->nbRows;
	}
	
	/**
	 * Adds a temp table to the list of currently created temp tables
	 * @param $tempTable the table model
	 * @param $nbRows the number of rows contained in the temp table.
	 */
	public function addTempTable($tempTable, $nbRows) {
		$tempTableName = $tempTable->getTableNameWithDbName();
		
		// list of tables to be dropped
		if('' != $this->tempTablesToDrop) $this->tempTablesToDrop .= ', ';		
		$this->tempTablesToDrop .= $tempTableName;
		// number of rows
		if($nbRows > $this->nbRows) $this->nbRows = $nbRows;
				
		if($this->currentSqlJoin < $this->maxSqlJoin) {
			// join fields
			if(count($this->sqlTempTableFields > 0)) {
				if('' != $this->sqlJoinFields) $this->sqlJoinFields .= ', ';
				$this->sqlJoinFields .= $tempTableName.".".implode(", ".$tempTableName.".", $this->sqlTempTableFields);
				$this->sqlTempTableFields = array();
			}
			
			// join tables
			if('' == $this->sqlJoinFirstTableName) {
				// first table
				$this->sqlJoinFirstTableName = $tempTableName;
				$this->sqlJoin = ' FROM '.$tempTableName;
			}
			else {
				// joined table
				$this->sqlJoin .= ' INNER JOIN '.$tempTableName.' ON '.$tempTableName.'.Eid = '.$this->sqlJoinFirstTableName.'.Eid';			
			}
			$this->currentSqlJoin++;
		}			
		$this->fieldCounter = 0;			
	}
	public function getSqlForSelectJoinedTempTables($pRightsFromDb) {
		$returnValue = 'SELECT '.$this->sqlJoinFirstTableName.'.Eid';
		// shows db rights if needed
		if($pRightsFromDb && !$this->dbTable->shouldHideDbRights()) {
			$returnValue .= ', '.$this->sqlJoinFirstTableName.'.EcanWriteElement, '.$this->sqlJoinFirstTableName.'.EcanShareElement';
		}
		// join fields		
		if('' != $this->sqlJoinFields) $returnValue .= ', ';
		$returnValue .= $this->sqlJoinFields;
		// join tables
		$returnValue .= $this->sqlJoin;
		return $returnValue;
	}
	
	public function getSqlForDropTempTables() {
		if('' != $this->tempTablesToDrop) {			
			return "DROP TABLE IF EXISTS ".$this->tempTablesToDrop;
		}
		else return '';
	}

	// ElementPMapper override
	
	/**
	 * Informs the ElementPMapper of a new column in the SQL SELECT query and gives information about its mapping to a field.
	 * sqlSelectColName: SQL column name in the result set
	 * field, dataType, subFieldName, subFieldType, sqlType, lang : mapping information between the result column and the final object.
	 */
	public function selectColForFieldList($sqlSelectColName, $field, $dataType, $subFieldName, $subFieldType, $sqlType, $lang=null)
	{
		parent::selectColForFieldList($sqlSelectColName, $field, $dataType, $subFieldName, $subFieldType, $sqlType, $lang);
		if($this->currentSqlJoin < $this->maxSqlJoin) {
			$this->sqlTempTableFields[] = $sqlSelectColName;
		}		
		$this->fieldCounter++;
	}
	
	// RecordStructureFactory implementation

	public function createFieldList() {return null;}
	public function createWigiiBag() {return null;}
}