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
 * A module field
 * Created by CWE on 15 août 09
 */
class Field extends Model {
	private $fieldName;
	private $dataType;
	private $xml;
	private $funcExp;
	private $calculationDisabled = false;

	public static function createInstance() {
		return new Field();
	}

	public function __sleep(){
		if($this->xml) $this->xml = $this->xml->asXml();
		$returnValue = array("fieldName", "dataType", "xml", "funcExp", "calculationDisabled");
		// Medair (CWE) 08.10.2018: for PHP 7.1 automatic serialization, ensure that all variables exist with null value by default. If a variable has been destroyed with unset, unserialize will fail.
		foreach($returnValue as $varName) {
		    if(!isset($this->{$varName})) $this->{$varName} = null;
		}
		return $returnValue;
	}
	public function __wakeup(){
		if($this->xml) $this->xml = simplexml_load_string($this->xml);
	}

	public function __clone() {
		//dataType instance is not cloned, we use the same ref object
		//the xml object is cloned to be able to edit it
		$this->xml = simplexml_load_string($this->xml->asXml());
	}

	public function getFieldName() {
		return $this->fieldName;
	}
	public function setFieldName($fieldName) {
		$this->fieldName = $fieldName;
	}

	/**
	 * @return DataType
	 */
	public function getDataType() {
		return $this->dataType;
	}
	public function setDataType($dataType) {
		$this->dataType = $dataType;
	}
	public function getXml() {
		return $this->xml;
	}
	public function setXml($xml) {
		$this->xml = $xml;
	}
	public function getFuncExp() {
		return $this->funcExp;
	}
	public function setFuncExp($funcExp) {
		$this->funcExp = $funcExp;
	}

	/**
	 * Return true if this field is a calculated field
	 * i.e. funcExp is not null
	 */
	public function isCalculated() {
		return isset ($this->funcExp);
	}

	/**
	* Return true if this field is a calculated field and has calculateOnFetch=1 in his config
	*/
	public function shouldCalculateOnFetch() {
		return $this->isCalculated() && $this->xml["calculateOnFetch"]=="1";
	}
	
	/**
	 * Returns true if this field is a calculated field and has calculateInListOnly=1 in this config
	 * @return boolean
	 */
	public function shouldCalculateInListOnly() {
		return $this->isCalculated() && $this->xml["calculateInListOnly"]=="1";
	}

	/**
	 * Sets a flag telling if this field should not be taken into account when
	 * evaluating func exp
	 * @param Boolean $bool if true, then funcExp will not be evaluated, else funcExp is evaluated
	 */
	public function disableCalculation($bool) {
		$this->calculationDisabled = $bool;
	}
	/**
	 * Returns true if this field should not be taken into account when
	 * evaluating func exp	 
	 */
	public function isCalculationDisabled() {
		return $this->calculationDisabled;
	}
	
	/**
	 * Fills a fieldSelectorList with all fieldSelectors contained in funcExp
	 * If field is not a calculated field, does nothing.
	 */
	public function getFuncExpDependencies($fieldSelectorList) {
		if ($this->isCalculated()) {
			$this->getFuncExp()->getDependencies($fieldSelectorList);
		}
	}

	/**
	 * Returns the value of the field
	 * subFieldName: the dataType subfield name from which to retrieve the value. If null takes the predefined "value" subfield.
	 * elementId: the ID of the element on which to retrieve the field value, it can be null
	 * wigiiBag: the WigiiBag from which to retrieve values
	 */
	public function getValue($elementId, $wigiiBag, $subFieldName = null) {
		return $this->getDataType()->getFieldValue($elementId, $wigiiBag, $this->getFieldName(), $subFieldName);
	}
	/**
	 * Sets the value of the field
	 * subFieldName: the dataType subfield name for which to store the value. If null takes the predefined "value" subfield.
	 * elementId: the ID of the element for which to store the field value, it can be null
	 * wigiiBag: the WigiiBag where to store the value
	 */
	public function setValue($value, $elementId, $wigiiBag, $subFieldName = null) {
		$dt = $this->getDataType();
		if(isset($dt)) $this->getDataType()->setFieldValue($value, $elementId, $wigiiBag, $this->getFieldName(), $subFieldName);
		else throw new ServiceException("cannot set a value on Field '".$this->getFieldName()."' which has no DataType (free text)", ServiceException::INVALID_ARGUMENT);
	}

	/**
	 * Accepts a FieldList visitor and forwards it on the datatype
	 * returns the return value of the visitor
	 */
	public function acceptFieldListVisitor($fieldListVisitor) {
		if ($this->getDataType() == null) {
			return $fieldListVisitor->actOnField($this, null);
		} else {
			return $this->getDataType()->acceptFieldListVisitor($fieldListVisitor, $this);
		}
	}
}