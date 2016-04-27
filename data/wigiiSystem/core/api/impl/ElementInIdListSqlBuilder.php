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
 * Element in Id list sql builder
 * Created by LWR, on 19 march 2010
 *
 * Creates an sql query to select elements in a list of ids
 */
class ElementInIdListSqlBuilder extends MySqlQueryBuilder
{
	private $_debugLogger;
	protected $sqlElementInIdList;
	protected $sqlBuilt;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementInIdListSqlBuilder");
		}
		return $this->_debugLogger;
	}

	// Object lifecycle

	public static function createInstance()
	{
		$returnValue = new ElementInIdListSqlBuilder();
		$returnValue->reset();
		return $returnValue;
	}
	public function reset()
	{
		parent::reset();
		unset($this->sqlElementInIdList);
		$this->sqlBuilt = false;
	}
	
	// Dependency injection
	
	// Service implementation

	/**
	 * Builds sql for Element in Group selection
	 */
	public function buildSqlForElementInIdList($elementIds)
	{
		if(!$this->sqlBuilt)
		{
			$this->setWhereClauseMultipleId("id_element", $elementIds);
			$this->sqlElementInIdList = "select id_element from Elements where ".$this->getWhereClause();
			$this->sqlBuilt = true;
		}
		return $this->sqlElementInIdList;
	}

	public function getSqlForElementInIdList()
	{
		if(!$this->sqlBuilt) throw new ElementServiceException("sql has not been built yet, please call first buildSqlForElementInIdList method", ElementServiceException::INVALID_STATE);
		return $this->sqlElementInIdList;
	}
	
	public function getNumberOfJoins()
	{
		return 0;
	}
	
}