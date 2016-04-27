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

/*
 * Created on 4 nov. 09
 * by LWR
 */

class FieldSortingKeyListArrayImpl extends ObjectListArrayImpl implements FieldSortingKeyList {

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}

	public function addFieldSortingKey($fieldName, $subFieldName = null, $ascending=true){
		$key = $this->getFieldSortingKeyKey($fieldName, $subFieldName);
		if(isset($this->objArray[$key])) throw new ListException("a field sorting key with same key $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = FieldSortingKey::createInstance($fieldName, $subFieldName, $ascending);
	}
	
	public function addFieldSortingKeyInstance($fieldSortingKey){
		$key = $this->getFieldSortingKeyKey($fieldSortingKey->getFieldName(), $fieldSortingKey->getSubFieldName());
		if(isset($this->objArray[$key])) throw new ListException("a field sorting key with same key $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $fieldSortingKey;
	}

	protected function getFieldSortingKeyKey($fieldName, $subFieldName)
	{
		if(!isset($fieldName)) $fieldName = '';
		if(!isset($subFieldName)) $subFieldName = '';
		return "($fieldName($subFieldName))";
	}

	public function addElementSortingKey($subFieldName, $ascending=true){
		$this->addFieldSortingKey("__element", $subFieldName, $ascending);
	}

	public function isElementSortingKey($fieldSortingKey)
	{
		if(isset($fieldSortingKey))
		{
			return ($fieldSortingKey->getFieldName() === "__element");
		}
		return false;
	}
}



