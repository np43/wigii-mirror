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
 * IndicatorList array implementation
 * Updated by Wigii.org(Lionel Weber) on October 2017 to support system indicators and recursive indicators
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
	
	public function addIndicator($fieldSelector, $dataType, $func, $label=null, $id=null, $isRecursive=false)
	{
		$ind = Indicator::createInstance($fieldSelector, $dataType, $func, $label, $id, $isRecursive);
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
	
	/**
	 * merge the given indicatorList to the current
	 */
	public function mergeIndicatorList($indicatorList){
		$this->objArray = array_merge($this->objArray, $indicatorList->getListIterator());
	}
}



