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
 * A value list which add the values in a Array (the value is added only if != null)
 * Created by LWR on 11 janv. 10
 */
class ValueListArrayMapper extends Model implements ValueList
{
	const Natural_Separators =  "[ \/,;\:\n\r\t]+";
	private $lockedForUse = true;
	private $valueList;
	private $n;
	private $distinct;
	private $possibleSeparator;
	private $trim;

	// Object lifecycle
	/**
	 * if one of the following parameter is defined, the values added in the ValueListArrayMapper must be
	 * strings only.
	 * @param $distinct : bool = false, define if we want unique values in the final array
	 * @param $possibleSeparator : string = null, define a possible separator in the value added.
	 * a preg_split is done with the possibleSeparator regExp, then values are added as separated items
	 * @param $trim : bool = false, define if a trim function is done on the value
	 */
	public static function createInstance($distinct=false, $possibleSeparator = null, $trim =false)
	{
		$returnValue = new ValueListArrayMapper();
		$returnValue->reset($distinct, $possibleSeparator, $trim);
		return $returnValue;
	}

	/**
	 * Resets the mapper
	 */
	public function reset($distinct=false, $possibleSeparator = null, $trim =false)
	{
		$this->freeMemory();
		$this->lockedForUse = true;
		$this->n = 0;
		$this->distinct = $distinct;
		$this->possibleSeparator = $possibleSeparator;
		$this->trim = $trim;
	}

	public function freeMemory()
	{
		unset($this->valueList);
		$this->lockedForUse = false;
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}

	// Service Implementation

	/**
	 * Returns the value list as a Array
	 */
	public function getValueListArray()
	{
		return $this->valueList;
	}

	// ValueList implementation

	public function addValue($val)
	{
		if(isset($val) && $val != null)
		{
			if(!isset($this->valueList))
			{
				$this->valueList = array();
			}
			if(($this->distinct || $this->trim || $this->possibleSeparator) && !is_string($val)) throw new ValueListException('distinct, trim or possibleSeparator options can be used only with string values', ValueListException::INVALID_ARGUMENT);

			if($this->trim) $val = trim($val);

			if($this->possibleSeparator){
				$val = preg_split("/".$this->possibleSeparator."/", $val);
				foreach($val as $subVal){
					if($subVal == null) continue;
					if($this->distinct){
						if(!isset($this->valueList[$subVal])){
							$this->valueList[$subVal] = $subVal;
							$this->n++;
						}
					} else {
						$this->valueList[] = $subVal;
						$this->n++;
					}
				}
			} else {
				if($this->distinct){
					if(!isset($this->valueList[$val])){
						$this->valueList[$val] = $val;
						$this->n++;
					}
				} else {
					$this->valueList[] = $val;
					$this->n++;
				}
			}
		}
	}
	public function getListIterator()
	{
		if(isset($this->valueList)) return $this->valueList;
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