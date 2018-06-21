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
 * FieldList array implementation
 * Created by CWE on 11 oct. 09
 */
class FieldListArrayImpl extends ObjectListArrayImpl implements FieldList
{
	protected $errorOnDuplicate;
	protected $ignoreNoneDataTypeFields;
	public static function createInstance($errorOnDuplicate=true, $ignoreNoneDataTypeFields=false)
	{
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->errorOnDuplicate = $errorOnDuplicate;
		$returnValue->ignoreNoneDataTypeFields = $ignoreNoneDataTypeFields;
		return $returnValue;
	}

	// FieldList implementation

	public function addField($field)
	{
		if(!isset($field)) throw new ListException("field cannot be null", ListException::INVALID_ARGUMENT);
		$key = $field->getFieldName();
		if($this->ignoreNoneDataTypeFields && $field->getDataType()==null) return;

		if(isset($this->objArray[$key])) if($this->errorOnDuplicate) throw new ListException("a field with same name $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $field;
	}

	public function getField($fieldName)
	{
		$f = $this->objArray[$fieldName];
		if($f === null) throw new ElementServiceException("Field $fieldName does not exist in the list.", ElementServiceException::NO_CORRESPONDANT_FIELD);
		return $f;
	}
	public function removeField($fieldName) {
		unset($this->objArray[$fieldName]);
	}
	public function doesFieldExist($fieldName){
		return $this->objArray[$fieldName];
	}
}