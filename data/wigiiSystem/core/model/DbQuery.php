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
 * A database sql query
 * Created by CWE on 7 avril 2014
 */
class DbQuery extends Model
{	
	private $sql;
	private $queryType;
	private $result;
	
	public static function createInstance($sql, $queryType)
	{
		$returnValue = new DbQuery();	
		$returnValue->setSql($sql);
		$returnValue->setQueryType($queryType);
		return $returnValue;
	}

	/**
	 * Sets query SQL string
	 * @param String $sql
	 */
	public function setSql($sql) {
		$this->sql = $sql;
	}
	/**
	 * Returns query SQL string
	 * @return String
	 */
	public function getSql() {
		return $this->sql;
	}
	
	/**
	 * Set query type.
	 * @param int $queryType one of MySqlFacade::Q_UPDATE, MySqlFacade::Q_INSERTONE, 
	 * MySqlFacade::Q_INSERTMULTIPLE, MySqlFacade::Q_DELETE, MySqlFacade::Q_CREATETABLE_FROMSELECT, 
	 * MySqlFacade::Q_INSERT_FROMSELECT or MySqlFacade::Q_DDLCMD
	 */
	public function setQueryType($queryType) {
		$this->queryType = $queryType;		
	}
	/**
	 * Returns query type
	 * @return int see MySqlFacade::Q_UPDATE, MySqlFacade::Q_INSERTONE, 
	 * MySqlFacade::Q_INSERTMULTIPLE, MySqlFacade::Q_DELETE, MySqlFacade::Q_CREATETABLE_FROMSELECT, 
	 * MySqlFacade::Q_INSERT_FROMSELECT or MySqlFacade::Q_DDLCMD
	 */
	public function getQueryType() {
		return $this->queryType;
	}
	
	/**
	 * Sets result of the query execution
	 * @param int $result if update, delete, create table from select, insert from select, 
	 * then equals the number of affected rows, else if insert then the created id, 
	 * else if ddl cmd then 1.
	 */
	public function setResult($result) {
		$this->result = $result;
	}
	
	/**
	 * Returns the result of the query
	 * @return int if update, delete, create table from select, insert from select, 
	 * then equals the number of affected rows, else if insert then the created id, 
	 * else if ddl cmd then 1.
	 */
	public function getResult() {
	    return $this->result;
	}
}


