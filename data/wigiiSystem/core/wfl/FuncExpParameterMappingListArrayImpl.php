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
 * A FuncExpParameterMappingList based on an array
 * Created by CWE on 28 novembre 2013
 */
class FuncExpParameterMappingListArrayImpl extends ObjectListArrayImpl implements FuncExpParameterMappingList
{
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	
	// FuncExpParameterMappingList implementation
	
	public function addFxpMapping($fxpMapping, $key=null) {
		if(!isset($fxpMapping)) throw new ListException('fxpMapping cannot be null', ListException::INVALID_ARGUMENT);
		if(isset($key)) $this->objArray[$key] = $fxpMapping;
		else $this->objArray[] = $fxpMapping;
 	}

	public function addFxpMappingArray($arr) {
		if(!is_array($arr)) throw new ListException('arr should by an array', ListException::INVALID_ARGUMENT);
		$this->objArray = array_merge($this->objArray, $arr);
	}
}