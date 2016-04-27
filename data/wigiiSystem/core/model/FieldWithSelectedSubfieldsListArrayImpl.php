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
 * FieldWithSelectedSubfieldsList array implemenation
 * Created by CWE on 14 juin 2013
 */
class FieldWithSelectedSubfieldsListArrayImpl extends ObjectListArrayImpl implements FieldWithSelectedSubfieldsList
{
	// Object lifecycle
	
	/**	
	 * @param $fieldList if set, then calls method fillFromFieldList to fill the list
	 */
	public static function createInstance($fieldList=null, $fieldSelectorList=null) {
		$returnValue = new self();
		$returnValue->reset();
		if(isset($fieldList)) $returnValue->fillFromFieldList($fieldList, $fieldSelectorList);
		return $returnValue;
	}
	
	// Dependency injection
	
	private $listFiller;
	/**
	 * Injects a list filler which will provide an implementation for the method fillFromFieldList
	 * @param listFiller should expose a method fillFromFieldList($fieldWithSelectedSubfieldsList, $fieldList, $fieldSelectorList=null)
	 * see class FieldWithSelectedSubfieldsListFiller
	 */
	public function setListFiller($listFiller) {
		$this->listFiller = $listFiller;
	}
	protected function getListFiller() {
		// auto-wired
		if(!isset($this->listFiller)) {
			$this->listFiller = TechnicalServiceProvider::getFieldWithSelectedSubfieldsListFiller();
		}
		return $this->listFiller;
	}
	
	// Implementation
		
	public function addFieldWithSelectedSubfields($field) {
		if(is_null($field)) throw new ListException("field cannot be null", ListException::INVALID_ARGUMENT);
		$key = $field->getField()->getFieldName();
		if(isset($this->objArray[$key])) throw new ListException("a field with same name $key has already been added", ListException::ALREADY_EXISTS);
		$this->objArray[$key] = $field;
	}
	
	public function fillFromFieldList($fieldList, $fieldSelectorList=null) {
		$this->getListFiller()->fillFromFieldList($this, $fieldList, $fieldSelectorList);
	}
	
	public function getFieldWithSelectedSubfields($fieldName) {
		return $this->objArray[$fieldName];		
	}
}