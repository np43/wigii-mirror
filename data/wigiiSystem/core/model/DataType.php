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
 * A wigii DataType
 * Created by CWE on 17 août 09
 */
class DataType
{
	private $dataTypeName;
	private $xml;

	public function __sleep(){
		if($this->xml) $this->xml = $this->xml->asXml();
		$returnValue = array("dataTypeName", "xml");
		// Medair (CWE) 08.10.2018: for PHP 7.1 automatic serialization, ensure that all variables exist with null value by default. If a variable has been destroyed with unset, unserialize will fail.
		foreach($returnValue as $varName) {
		    if(!isset($this->{$varName})) $this->{$varName} = null;
		}
		return $returnValue;
	}
	public function __wakeup(){
		if($this->xml) $this->xml = simplexml_load_string($this->xml);
	}

	public static function createInstance()
	{
		return new DataType();
	}

//	private $_debugLogger;
//	private function debugLogger() {
//		if (!isset ($this->_debugLogger)) {
//			$this->_debugLogger = DebugLogger :: getInstance("DataType_".$this->dataTypeName);
//		}
//		return $this->_debugLogger;
//	}

	public function getDataTypeName()
	{
		return $this->dataTypeName;
	}
	public function setDataTypeName($dataTypeName)
	{
		$this->dataTypeName = $dataTypeName;
	}
	public function getXml()
	{
		return $this->xml;
	}
	public function setXml($xml)
	{
		$this->xml = $xml;
	}

	/**
	 * Accepts a FieldList visitor and passes it the current datatype and field
	 * returns the return value of the visitor
	 */
	public function acceptFieldListVisitor($fieldListVisitor, $linkedField)
	{
		return $fieldListVisitor->actOnField($linkedField, $this);
	}

	/**
	 * Returns the value of a field in an element
	 * subFieldName: the dataType subfield name from which to retrieve the value. If null takes the predefined "value" subfield.
	 * elementId: the ID of the element on which to retrieve the field value, it can be null
	 * wigiiBag: the WigiiBag from which to retrieve values
	 */
	public function getFieldValue($elementId, $wigiiBag, $fieldName, $subFieldName = null)
	{
		return $wigiiBag->getValue($elementId, $this->getDataTypeName(), $fieldName, $subFieldName);
	}
	/**
	 * Sets the value of a field in an element
	 * subFieldName: the dataType subfield name for which to store the value. If null takes the predefined "value" subfield.
	 * elementId: the ID of the element for which to store the field value, it can be null
	 * wigiiBag: the WigiiBag where to store the value
	 */
	public function setFieldValue($value, $elementId, $wigiiBag, $fieldName, $subFieldName = null)
	{
		$wigiiBag->setValue($value, $elementId, $this->getDataTypeName(), $fieldName, $subFieldName);
	}	
	
	private $hasSubfields;
	/**
	 * Returns true if datatype has some subfields other than value and sys_*
	 */
	public function hasSubfields() {
		if(!isset($this->hasSubfields) && isset($this->xml)) {
			//$this->debugLogger()->write('calculates subfields existence');			
			$n = 0;
			foreach($this->xml as $subFieldName => $subFieldParams) {				
				$s = strtolower($subFieldName);
				if(strpos($s, 'sys_') !== 0 && $s != 'value') {
					$this->hasSubfields = true;
				}
				else $n++;
			}
			if($n > 0 && !isset($this->hasSubfields)) $this->hasSubfields = false;						
		}
		return $this->hasSubfields;
	}
	
	private $relativeWeight;
	/**
	 * Returns the relative weight associated to this data type
	 * @return int or null if not set.
	 */
	public function getRelativeWeight() {
		return $this->relativeWeight;
	}
	
	/**
	 * Sets the relative weight associated to this datatype.
	 * @param int $w an integer or null.
	 */
	public function setRelativeWeight($w) {
		$this->relativeWeight = $w;
	}
}