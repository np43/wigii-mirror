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
 * Main FieldList implementation used by all Forms in Wigii WebImpl
 * Created on 17 sept. 09 by LWR
 * Modified by CWE on 02.12.2014 to add dynamic attributes index
 */
class FormFieldList extends Model implements FieldList {

	protected $fields;
	protected $nbFields;
	protected $dynamicAttributeFields;
	
	private $formBag;
	public function setFormBag($var){
		$this->formBag = $var;
		if(isset($this->formBag)){
			//autowired the formFieldList to the wigiiBag
			$this->formBag->setFormFieldList($this);
		}
	}
	public function getFormBag(){ return $this->formBag; }

	public static function createInstance($formBag){
		$ffl = new self();
		$ffl->setFormBag($formBag);
		return $ffl;
	}

	public function getListIterator(){
		return $this->fields;
	}
	public function isEmpty(){
		return count($this->fields)==0;
	}
	public function count(){
		return $this->nbFields;
	}

	/**
	 * Adds a field to the list
	 * throws ListException::ALREADY_EXISTS if we try to put a second time the same field in the list
	 * except if it is a freeText. for that the freeText is just added with the current number
	 */
	public function addField($field){

		if($field->getDataType()==null && isset($this->fields[$field->getFieldName()])){
			$field->setFieldName($field->getFieldName().$this->nbFields);
		}

		if(isset($this->fields[$field->getFieldName()])) throw new ListException($field->getFieldName()." already exist", ListException::ALREADY_EXISTS);
		$this->fields[$field->getFieldName()] = $field;

		//add the info in the bag
		if($this->getFormBag()!=null){
			$fxml = $field->getXml();
			if($fxml["help"]){
				$this->getFormBag()->setHelp((string)$fxml["help"], $field->getFieldName());
			} else if(isset($fxml->help)){
				$this->getFormBag()->setHelp($fxml->help->asXml(), $field->getFieldName());
			}
			if($fxml["disabled"] == "1" || $fxml["isKey"] || $fxml["isUnique"]){
				$this->getFormBag()->setDisabled(true, $field->getFieldName());
			}
			if($fxml["readonly"] == "1"){
				$this->getFormBag()->setReadOnly(true, $field->getFieldName());
			}
			if($fxml["hidden"] == "1"){
				$this->getFormBag()->setHidden(true, $field->getFieldName());
			}
		}

		// indexes dynamic attribute field
		if($fxml["enableDynamicAttributes"] == "1") $this->dynamicAttributeFields[$field->getFieldName()] = $field->getFieldName();
		
		$this->nbFields++;
	}

	public function renameField($fieldName, $newName){
		$f = $this->fields[$fieldName];
		if($f === null) throw new ElementServiceException("Field $fieldName does not exist in the list.", ElementServiceException::NO_CORRESPONDANT_FIELD);
		if($this->fields[$newName] != null) throw new ElementServiceException("Field $fieldName cannot be rename in $newName ($newName field already exists)", ElementServiceException::FIELD_DEFINED_TWICE_IN_CONFIG);
		$f->setFieldName($newName);
		//keep fields order
		$keys = array_keys($this->fields);
		$values = array_values($this->fields);
		$i = array_search($fieldName, $keys);
		$keys[$i] = $newName;
		$values[$i] = $f;
		$this->fields = array_combine($keys, $values);
		$this->getFormBag()->renameField($fieldName, $newName);
		if(isset($this->dynamicAttributeFields) && isset($this->dynamicAttributeFields[$fieldName])) {
			$this->dynamicAttributeFields[$newName] = $newName;
			unset($this->dynamicAttributeFields[$fieldName]);
		}
	}
	public function getField($fieldName){
		$f = $this->fields[$fieldName];
		if($f === null) throw new ElementServiceException("Field $fieldName does not exist in the list.", ElementServiceException::NO_CORRESPONDANT_FIELD);
		return $f;
	}
	public function doesFieldExist($fieldName){
		return $this->fields[$fieldName];
	}
	public function removeField($fieldName) {
		unset($this->fields[$fieldName]);
	}
	/**
	 * Returns an array of field names for which 'enableDynamicAttributes' is true
	 * @return Array or null if no field with dynamic properties
	 */
	public function getDynamicAttributeFields() {
		return $this->dynamicAttributeFields;
	}
	/**
	 * Returns true if at least one field in the list has the property 'enableDynamicAttributes' to true
	 * @return boolean
	 */
	public function hasDynamicAttributeFields() {
		return !empty($this->dynamicAttributeFields);
	}
}



