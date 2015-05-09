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
 * IndicatorList array implementation
 */
class IndicatorListArrayImpl extends ObjectListArrayImpl implements IndicatorList
{
	protected $errorOnDuplicate;

	public static function createInstance($errorOnDuplicate=true)
	{
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->errorOnDuplicate = $errorOnDuplicate;
		return $returnValue;
	}

	public function containsIndicator($id){
		return isset($this->objArray[$id]);
	}
	
	public function addIndicator($fieldSelector, $dataType, $func, $label=null, $id=null)
	{
		$ind = Indicator::createInstance($fieldSelector, $dataType, $func, $label, $id);
		$id = $ind->getId();
		if(isset($this->objArray[$id]))
		{
			if($this->errorOnDuplicate) throw new ListException("an indicator with same id $id has already been added", ListException::ALREADY_EXISTS);
		}
		else $this->objArray[$id] = $ind;
		return $id;
	}
	public function addIndicatorInstance($indicator){
		$id = $indicator->getId();
		if(isset($this->objArray[$id]))
		{
			if($this->errorOnDuplicate) throw new ListException("an indicator with same id $id has already been added", ListException::ALREADY_EXISTS);
		}
		else $this->objArray[$id] = $indicator;
		return $id;
	}

	public function getIndicator($id)
	{
		return $this->objArray[$id];
	}
	
	public function removeIndicator($id){
		unset($this->objArray[$id]);
	}
}



