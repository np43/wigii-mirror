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
 * Fills a ValueList from a single column RowList
 * Created by CWE on 10 janv. 10
 */
class ValueListMapper implements RowList
{
	private $lockedForUse = true;
	private $valueList;
	private $valueCounter;
	private $colName;
	private $colVerified;

	/**
	 * colName = sql column name from which to extract the value,
	 * or numeric index if fetched with MYSQL_NUM mode
	 */
	public static function createInstance($valueList, $colName)
	{
		$returnValue = new ValueListMapper();
		$returnValue->reset($valueList, $colName);
		return $returnValue;
	}

	/**
	 * colName = sql column name from which to extract the value,
	 * or numeric index if fetched with MYSQL_NUM mode
	 */
	public function reset($valueList, $colName)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->valueList = $valueList;
		$this->colName = $colName;
		$this->colVerified = false;
		$this->valueCounter = 0;
	}
	public function freeMemory() {
		unset($this->valueList);
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}

	public function getListIterator()
	{
		return $this->valueList->getListIterator();
	}

	public function isEmpty()
	{
		return ($this->valueCounter == 0);
	}

	public function count()
	{
		return $this->valueCounter;
	}

	public function addRow($row)
	{
		if(isset($row))
		{
			if(!$this->colVerified)
			{
				$this->colVerified = true;
				if(!array_key_exists($this->colName, $row))
				{
					throw new ListException("column name or index '".$this->colName."' is invalid in record", ListException::INVALID_ARGUMENT);
				}
			}
			$this->valueList->addValue($row[$this->colName]);
			$this->valueCounter++;
		}
	}
}