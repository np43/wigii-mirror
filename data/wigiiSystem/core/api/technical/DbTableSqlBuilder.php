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
 * MySql query builder mapped to a single Db table which builds queries based on a LogExp with FieldSelectors 
 * Created by Medair (CWE) on 16.06.2017
 */
class DbTableSqlBuilder extends FieldSelectorLogExpSqlBuilder
{

	// Object lifecycle

    public static function createInstance()
    {
        $returnValue = new DbTableSqlBuilder();
        $returnValue->reset();
        return $returnValue;
    }
    
	public function reset()
	{
		parent::reset();	
	}

	// Configuration
	
	private $dbTableName;
	/**
	 * Sets the mapped db table name
	 * @param String $dbTableName the name of an existing table into the database
	 */
	public function setDbTableName($dbTableName) {
	    $this->dbTableName = $dbTableName;
	}	
	
	private $sqlTypeMap;
	/**
	 * Defines the SQL Type of each fetched column.
	 * If a column is not defined, then default SQL type is VARCHAR
	 * @param Array $map an array with key equal to the column name and value equal to one of MySqlQueryBuilder::SQLTYPE_* or BIGINT, DOUBLE, DECIMAL, BOOLEAN, DATE, DATETIME, INT, LONGBLOB, BLOB, TEXT, TIME, VARCHAR
	 */
	public function setSqlTypeMap($map) {
	    $this->sqlTypeMap = $map;
	}
	
	// Service implementation
	
	/**
	 * Sets the FieldSelectorList to select the columns in the db table
	 * @param FieldSelectorList $fieldSelectorList	
	 */
	public function setFieldSelectorList($fieldSelectorList)
	{
	    MySqlFacadeException::throwNotImplemented();
	}
	
	/**
	 * Sets the LogExp to build the where clause
	 * @param LogExp $logExp
	 */
	public function setFieldSelectorLogExp($logExp) {
	    MySqlFacadeException::throwNotImplemented();
	}
	
	/**
	 * Sets the FieldSortingKey list to build the order by clause
	 * @param FieldSortingKeyList $fieldSortingKeyList
	 */
	public function setFieldSortingKeyList($fieldSortingKeyList)
	{
	    MySqlFacadeException::throwNotImplemented();
	}
	
	/**
	 * Gets the built sql query string
	 * returns null if no SQL query has been built
	 * or if no values have changed compared to original, then no update needed.
	 */
	public function getSql()
	{
	    MySqlFacadeException::throwNotImplemented();
	    return parent::getSql();
	}		
}