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
 * Wigii Bag StdClass implementation
 * Created by CWE on 21.02.2018
 */
class WigiiBagStdClassImpl extends Model implements WigiiBag
{
	private $_debugLogger;
	private $stdClass;
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiBagStdClassImpl");
		}
		return $this->_debugLogger;
	}

	// Object lifecycle

	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	public function reset()
	{
		$this->freeMemory();
		$this->stdClass = (object)array();
		$this->debugLogger()->write("reset wigii bag");
	}
	protected function freeMemory()
	{
		unset($this->stdClass);
	}
	
	// Configuration
	
	private $fieldMapping;
	/**
	 * Sets a mapping table which re-maps element fields to other field names.
	 * @param CalculatedFieldSelectorMap $fieldRemap a cfsMap instance which associates Element FieldSelector to StdClass FieldSelector.
	 */
	public function setFieldMapping($fieldRemap) {
		$this->fieldMapping = $fieldRemap;
	}
	
	private $language;
	/**
	 * Sets a language used to flatten multi-language fields like Texts or Varchars. 
	 * By default, keeps content multi-language into array.
	 * @param String $language an installed language code like l01, l02, etc.
	 */
	public function setLanguage($language) {
		$this->language = $language;
	}	
	
	// WigiiBag implementation 
	
	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		// checks for mapping
		if(isset($this->fieldMapping)) {
			// checks for specific subfield mapping
			$mapping = $this->fieldMapping->getFuncExpByFieldName($fieldName,$subFieldName);
			// if not found, check for general field mapping
			if(!isset($mapping) && isset($subFieldName)) {
				$mapping = $this->fieldMapping->getFuncExpByFieldName($fieldName);
			}
			// if found, then maps specific subfield to new field
			elseif(isset($subFieldName)) $subFieldName = null;
			
			// if map to one field
			if($mapping instanceof FieldSelector) {
				$fieldName = $mapping->getFieldName();
			}
			// if maps to several fields, takes first one
			elseif(is_array($mapping) && !empty($mapping)) {
				$mapping = reset($mapping);
				$fieldName = $mapping->getFieldName();
			}
		}
		
		// element
		$returnValue = $this->stdClass->{'elt_'.$elementId};
		// field
		if(isset($returnValue)) {
			$returnValue = $returnValue->{$fieldName};
		}
		// subfield
		if($subFieldName==null) $subFieldName = 'value';
		if(isset($returnValue)) $returnValue = $returnValue->{$subFieldName};
		// expands multilanguage field
		if(isset($this->language) && ($dataTypeName == 'Varchars' || $dataTypeName == 'Texts') && $subFieldName == 'value' && !is_array($returnValue)) {
			$returnValue = array($this->language=>$returnValue);
		}
		return $returnValue;
	}

	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null) {
		// checks for field mapping
		if(isset($this->fieldMapping)) {
			// checks for specific subfield mapping
			$mapping = $this->fieldMapping->getFuncExpByFieldName($fieldName,$subFieldName);
			// if not found, check for general field mapping
			if(!isset($mapping) && isset($subFieldName)) {
				$mapping = $this->fieldMapping->getFuncExpByFieldName($fieldName);
			}
			// if found, then maps specific subfield to new field
			elseif(isset($subFieldName)) $subFieldName = null;
			
			// if map to one field
			if($mapping instanceof FieldSelector) {
				$this->doSetValue($value, $elementId, $dataTypeName, $mapping->getFieldName(), $subFieldName);
			}
			// if maps to several fields
			elseif(is_array($mapping) && !empty($mapping)) {
				foreach($mapping as $fs) {
					$this->doSetValue($value, $elementId, $dataTypeName, $fs->getFieldName(), $subFieldName);
				}
			}
			// else keeps standard field
			else $this->doSetValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName);
		}
		// else keeps standard field
		else $this->doSetValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName);
	}
	protected function doSetValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		$key = 'elt_'.$elementId;
		// element
		if(is_null($this->stdClass->{$key})) {
			$this->stdClass->{$key} = (object)array();
		}
		$obj = $this->stdClass->{$key};		
		// field
		if(is_null($obj->{$fieldName})) {
			$obj->{$fieldName} = (object)array();
		}
		$obj = $obj->{$fieldName};		
		// subfield
		if($subFieldName==null) $subFieldName = 'value';
		// flattens multilanguage field
		if(isset($this->language) && ($dataTypeName == 'Varchars' || $dataTypeName == 'Texts') && $subFieldName == 'value' && is_array($value)) {
			$value = $value[$this->language];
		}
		$obj->{$subFieldName} = $value;
	}
	
	public function applyOperator($operator, $newValue, $dataTypeName, $fieldName, $subFieldName=null, $lang=null, $field=null){
		$this->validateOperator($operator);
		if(is_null($subFieldName)) $subFieldName = "value";
		if($field) $fieldXml = $field->getXml();
		else $fieldXml = null;
		
		// goes through the list of elements
		foreach($this->stdClass as $key => $element) {
			$elementId = substr($key,4);
			// if field doesn't exist in WigiiBag, then inserts
			if(is_null($element->{$fieldName}) || is_null($element->{$fieldName}->{$subFieldName})) {
				$this->setValue(($lang!=null?array($lang=>$newValue):$newValue), $elementId, $dataTypeName, $fieldName, $subFieldName);
			}
			// else updates value
			else {
				$value = $this->getValue($elementId, $dataTypeName, $fieldName, $subFieldName);
				switch ($operator){
					case SUPDOP_SET:
						//multilanguage fields
						if($lang != null){
							$value[$lang] = $newValue;
							$this->setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName);
						} else {
							$this->setValue($newValue, $elementId, $dataTypeName, $fieldName, $subFieldName);
						}
						break;
					case SUPDOP_SET_IF_NULL:
						//multilanguage fields
						if($lang != null){
							if($value[$lang]==null){
								$value[$lang] = $newValue;
								$this->setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName);
							}
						} else {
							if($value==null){
								$this->setValue($newValue, $elementId, $dataTypeName, $fieldName, $subFieldName);
							}
						}
						break;
					case SUPDOP_ADD:
						if($newValue != null){
							//either concat, either replace if not empty
							switch($dataTypeName){
								case "Addresses":
									//perform an add
									$this->setValue($newValue, $elementId, $dataTypeName, $fieldName, $subFieldName);
									break;
								case "MultipleAttributs":
									//perform a merge
									$newValue2 = array_combine(array_values($newValue), array_values($newValue));
									if($value != null){
										$value = array_merge($value, $newValue2);
									}
									else $value = $newValue2;
									$this->setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName);
									break;
								case "Emails":
									//perform an add
									$this->setValue(str_update($value, $newValue, SUPDOP_ADD, '/'.ValueListArrayMapper::Natural_Separators.'/', ", "), $elementId, $dataTypeName, $fieldName, $subFieldName);
									break;
								case "Blobs":									
									if($fieldXml && $fieldXml["isJournal"]=="1"){
										//in the case of the isJournal the string is already prepared to add new lines in between
										$this->setValue($newValue.$value, $elementId, $dataTypeName, $fieldName, $subFieldName);
									} else {
										//perform a concatenation with at the end
										if($value != null ){
											if($fieldXml &&  $fieldXml["htmlArea"]=="1"){
												$value .= "<p>&nbsp;</p>";
											} else {
												$value .= "\n";
											}
										}
										$this->setValue($value.$newValue, $elementId, $dataTypeName, $fieldName, $subFieldName);
									}
									break;
								case "Numerics":
								case "Floats":
									//perform an addition
									if($value == null ) $value = 0;
									$this->setValue($value+(double)$newValue, $elementId, $dataTypeName, $fieldName, $subFieldName);
									break;
								case "Texts":
									//perform a concatenation with a \n
									if($lang == null) throw new ServiceException("try to perform add operation on a Texts field without defining the lang parameter for field: $fieldName and subField: $subFieldName", ServiceException::INVALID_ARGUMENT);
									if($value[$lang] != null ){
										if($fieldXml && $fieldXml["htmlArea"]=="1"){
											$value[$lang].= "<p></p>";
										} else {
											$value[$lang].= "\n";
										}
									}
									$value[$lang] .= $newValue;
									$this->setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName);
									break;
								default:
									throw new ServiceException("try to perform a forbidden $operator operation on dataType $dataTypeName for field: $fieldName and subField: $subFieldName", ServiceException::INVALID_ARGUMENT);
							}
						}
						break;
				}
			}
		}		
	}

	protected function validateOperator($operator){
		switch ($operator){
			case SUPDOP_SET:
			case SUPDOP_ADD:
			case SUPDOP_SET_IF_NULL:
				return true;
			default:
				throw new ServiceException("invalid operator: $operator", ServiceException::INVALID_ARGUMENT);
		}
	}
	
	/**
	 * Adds the content of the given WigiiBagStdClassImpl to this one.
	 * @param WigiiBagStdClassImpl $wigiiBag
	 */
	public function addWigiiBagStdClassImpl($wigiiBag) {
		if(isset($wigiiBag)) {
			if(!($wigiiBag instanceof WigiiBagStdClassImpl)) throw new ServiceException("wigiiBag is not an instance of WigiiBagStdClassImpl", ServiceException::INVALID_ARGUMENT);
			foreach($wigiiBag->getStdClass() as $k=>$v) {
				$this->stdClass->{$k} = $v;
			}
		}
	}
	
	// Accessors
	
	/**
	 * @return StdClass Returns the StdClass representation of this WigiiBag
	 */
	public function getStdClass() {
		return $this->stdClass;
	}
}