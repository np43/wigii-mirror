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
 * A value list which concatenates the values in a string
 * Created by CWE on 10 janv. 10
 */
class ValueListStringMapper implements ValueList
{
	private $lockedForUse = true;
	private $valueList;
	private $separator;
	private $n;

	// Object lifecycle

	public static function createInstance($separator=',')
	{
		$returnValue = new ValueListStringMapper();
		$returnValue->reset($separator);
		return $returnValue;
	}

	/**
	 * Resets the mapper
	 */
	public function reset($separator=',')
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->n = 0;
		$this->separator = $separator;
	}

	public function freeMemory()
	{
		$this->lockedForUse = false;
		unset($this->valueList);
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	// Service Implementation

	/**
	 * Returns the value list as a string
	 */
	public function getValueListString()
	{
		return $this->valueList;
	}

	// ValueList implementation

	public function addValue($val)
	{
		if(isset($val))
		{
			if(isset($this->valueList))
			{
				$this->valueList .= $this->separator;
			}
			$this->valueList .= $val;
			$this->n++;
		}
	}
	public function getListIterator()
	{
		if(isset($this->valueList)) return explode($this->separator, $this->valueList);
		return null;
	}
	public function isEmpty()
	{
		return !isset($this->valueList);
	}
	public function count()
	{
		return $this->n;
	}
}