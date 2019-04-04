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
 * Class which maps db records containing in column 0 IDs to a comma separated string
 * Created by CWE on 20 sept. 09
 */
class IdListMapper implements RowList
{
	private $lockedForUse = true;
	private $idList;
	private $n;

	public static function createInstance()
	{
		$returnValue = new IdListMapper();
		$returnValue->reset();
		return $returnValue;
	}

	/**
	 * Resets the mapper
	 */
	public function reset()
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		unset($this->idList);
		$this->n = 0;
	}
	public function freeMemory() {
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	/**
	 * Returns the ID list as a comma separated string
	 */
	public function getIdList()
	{
		return $this->idList;
	}

	public function addRow($row)
	{
		if(isset($row))
		{
			if(isset($this->idList))
			{
				$this->idList .= ', ';
			}
			$this->idList .= $row[0];
			$this->n++;
		}
	}
	public function getListIterator()
	{
		if(isset($this->idList)) return explode(', ', $this->idList);
		return null;
	}
	public function isEmpty()
	{
		return !isset($this->idList);
	}
	public function count()
	{
		return $this->n;
	}
}