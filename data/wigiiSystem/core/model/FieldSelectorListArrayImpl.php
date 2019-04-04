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
 * FieldSelectorList array implementation.
 * Modified by CWE on 15 April 2014 to implement FieldList interface so that the list can be filled directly by the config service.
 */
class FieldSelectorListArrayImpl extends ObjectListArrayImpl implements FieldSelectorList, FieldList
{
	protected $selectedLanguage;
	protected $errorOnDuplicate;
	protected $addSysInfoFields;
	protected $ignoreNoneDataTypeFields;
	
	public static function createInstance($errorOnDuplicate=true, $addSysInfoFields=true, $copyFromFieldSelectorList=null)
	{
		$returnValue = new self();
		$returnValue->reset($errorOnDuplicate, $addSysInfoFields);
		if(isset($copyFromFieldSelectorList)) {
			$returnValue->mergeFieldSelectorList($copyFromFieldSelectorList);
		}
		return $returnValue;
	}
	
	public static function createInstanceAsFieldList($errorOnDuplicate=true, $addSysInfoFields=true, $ignoreNoneDataTypeFields=true)
	{
		$returnValue = new self();
		$returnValue->reset($errorOnDuplicate, $addSysInfoFields);
		$returnValue->setIgnoreNoneDataTypeField($ignoreNoneDataTypeFields);
		return $returnValue;
	}
		
	public function reset($errorOnDuplicate=null, $addSysInfoFields=null)
	{
		parent::reset();
		if($errorOnDuplicate!==null){
			$this->errorOnDuplicate = $errorOnDuplicate;
		}
		if($addSysInfoFields!==null){
			$this->addSysInfoFields = $addSysInfoFields;
		}
		$this->fieldSummary = null;
		$this->ignoreNoneDataTypeFields = false;
	}

	// FieldSelectorList implementation
	protected $fieldSummary;
	protected function addFieldToSummary($fieldName, $key){
		if(!isset($this->fieldSummary)){
			$this->fieldSummary = array();
		}
		if(!isset($this->fieldSummary[$fieldName])){
			$this->fieldSummary[$fieldName] = array();
		}
		$this->fieldSummary[$fieldName][] = $key;
	}
	public function getFieldSummary(){
		return $this->fieldSummary;
	}
	public function containsField($fieldName){
		if(!isset($this->fieldSummary)) return false;
		if(!isset($this->fieldSummary[$fieldName])) return false;
		return $this->fieldSummary[$fieldName];
	}
	//remove any fieldSelector for this field. Returns the keys deleted
	public function removesField($fieldName){
		if(!isset($this->fieldSummary)) return;
		if(!isset($this->fieldSummary[$fieldName])) return;
		$keys = $this->fieldSummary[$fieldName];
		foreach($keys as $key){
			unset($this->objArray[$key]);
		}
		unset($this->fieldSummary[$fieldName]);
		return $keys;
	}
	//return true if this specific FieldSelector is part of the list
	//use containsField for to be able to know if a field is part of the is part of the list without necessary checking the subfields
	public function containsFieldSelector($fieldName, $subFieldName=null)
	{
		return $this->objArray[$this->getFieldSelectorKey($fieldName, $subFieldName)]!=null;
	}

	public function getFieldSelectorFromKey($key){
		if(!isset($this->objArray)) return null;
		return $this->objArray[$key];
	}

	protected function addFieldSelectorSysInfoSubFields($fieldName){
		$sysInfo = array("sys_user", "sys_username", "sys_date", "sys_creationUser", "sys_creationUsername", "sys_creationDate");
		foreach($sysInfo as $sysSubField){
			$key = $this->getFieldSelectorKey($fieldName, $sysSubField);
			if(isset($this->objArray[$key])) {
				//never is a problem if sys fields already exist
			} else{
				$this->objArray[$key] = FieldSelector::createInstance($fieldName, $sysSubField);
				$this->addFieldToSummary($fieldName, $key);
			}
		}
	}
	public function addFieldSelector($fieldName, $subFieldName=null)
	{
		$key = $this->getFieldSelectorKey($fieldName, $subFieldName);
		if(isset($this->objArray[$key]))
		{
			if($this->errorOnDuplicate) throw new ListException("a field selector with same key $key has already been added", ListException::ALREADY_EXISTS);
		}
		else $this->objArray[$key] = FieldSelector::createInstance($fieldName, $subFieldName);

		//if $subFieldName == null all SubFields are added, so no need to add the specific subFields
		if($this->addSysInfoFields && $fieldName != '__element' && $subFieldName!=null) $this->addFieldSelectorSysInfoSubFields($fieldName);

		$this->addFieldToSummary($fieldName, $key);
		return $key;
	}
	public function addElementAttributeSelector($subFieldName)
	{
		$fs = FieldSelector::createElementAttributeSelector($subFieldName);
		$key = $this->getFieldSelectorKey($fs->getFieldName(), $subFieldName);
		if(isset($this->objArray[$key]))
		{
			if($this->errorOnDuplicate) throw new ListException("a field selector with same key $key has already been added", ListException::ALREADY_EXISTS);
		}
		else $this->objArray[$key] = $fs;

		$this->addFieldToSummary($fs->getFieldName(), $key);
		return $key;
	}
	public function addFieldSelectorInstance($fieldSelector){
		$key = $this->getFieldSelectorKey($fieldSelector->getFieldName(), $fieldSelector->getSubFieldName());
		if(isset($this->objArray[$key]))
		{
			if($this->errorOnDuplicate) throw new ListException("a field selector with same key $key has already been added", ListException::ALREADY_EXISTS);
		}
		else $this->objArray[$key] = $fieldSelector;

		//if $subFieldName == null all SubFields are added, so no need to add the specific subFields
		//if $fieldSelector is element attribute selector the system subfields are managed at field level and require an automatic add
		if($this->addSysInfoFields && !$fieldSelector->isElementAttributeSelector() && $fieldSelector->getSubFieldName()!=null) $this->addFieldSelectorSysInfoSubFields($fieldSelector->getFieldName());

		$this->addFieldToSummary($fieldSelector->getFieldName(), $key);
		return $key;
	}

	//add $fieldSelector to actual objects. if fields are common no problem
	public function mergeFieldSelectorList($fieldSelectorList){
		//to fill correctyl all the different
		$errorOnDuplicate = $this->errorOnDuplicate;
		$this->errorOnDuplicate = false;
		foreach($fieldSelectorList->getListIterator() as $item){
			$this->addFieldSelectorInstance($item);
		}
		$this->errorOnDuplicate = $errorOnDuplicate;
	}

	/**
	 * Fills the FieldSelectorList using all the fields contained in the FieldList.
	 * @param FieldList $fieldList
	 */
	public function fillFromFieldList($fieldList) {
		if(isset($fieldList)) {
			$errorOnDuplicate = $this->errorOnDuplicate;
			$this->errorOnDuplicate = false;
			foreach($fieldList->getListIterator() as $field) {
				$this->addFieldSelector($field->getFieldName());
			}
			$this->errorOnDuplicate = $errorOnDuplicate;
		}
	}
	
	protected function getFieldSelectorKey($fieldName, $subFieldName)
	{
		if(!isset($fieldName)) $fieldName = '';
		return "($fieldName($subFieldName))";
	}

	public function setSelectedLanguages($selectedLanguages)
	{
		$this->selectedLanguage = $selectedLanguages;
	}

	public function getSelectedLanguages()
	{
		return $this->selectedLanguage;
	}
	
	// FieldList implementation
	
	public function addField($field) {
		$dt = $field->getDataType();
		if($dt != null || !$this->ignoreNoneDataTypeFields) {
			if(isset($dt)) $xml = $dt->getXml(); else $xml = null;
			if($this->addSysInfoFields || !isset($xml)) $this->addFieldSelector($field->getFieldName());
			// filters sys info subfields
			else {
				$fName = $field->getFieldName();
				foreach($xml as $subFieldName => $subFieldParams) {					
					if(strpos(strtolower($subFieldName), 'sys_') !== 0) {
						$this->addFieldSelector($fName, $subFieldName);
					}
				}
			}		
		}
	}
	
	/**
	 * Configures the FieldSelectorList to ignore non datatype fields when acting as a FieldList.
	 */
	public function setIgnoreNoneDataTypeField($bool) {
		$this->ignoreNoneDataTypeFields = $bool;
	}
		
	public function getField($fieldName) {
		throw new ListException('method getField is not supported by this implementation, use a FieldListArrayImpl instead', ListException::UNSUPPORTED_OPERATION);
	}
	
	public function doesFieldExist($fieldName) {
		throw new ListException('method doesFieldExist is not supported by this implementation, use a FieldListArrayImpl instead', ListException::UNSUPPORTED_OPERATION);
	}

}