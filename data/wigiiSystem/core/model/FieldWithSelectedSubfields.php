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
 * A Field with some selected subfields
 * Created by CWE on 28 mai 2013
 */
class FieldWithSelectedSubfields extends Model {
	private $field;
	private $subfields = array();
	
	public static function createInstance($field) {
		if(is_null($field)) throw new ServiceException("field cannot be null", ServiceException::INVALID_ARGUMENT);
		$returnValue = new self();
		$returnValue->setField($field);
		return $returnValue;
	}
	
	/**
	 * @return Field
	 */
	public function getField() {
		return $this->field;
	}
	protected function setField($field) {		
		$this->field = $field;
	}
	
	/**
	 * Adds one specific subfield to the list of selected subfields
	 * @param String $subfieldName the subfield name
	 * @throws ServiceException INVALID_ARGUMENT if subfield name is invalid
	 */
	public function selectSubfield($subfieldName) {
		if(is_null($subfieldName) || $subfieldName == '') throw new ServiceException("subfieldName cannot be null", ServiceException::INVALID_ARGUMENT);
		$dataType = $this->field->getDataType();
		if(is_null($dataType)) throw new ServiceException("Field has no associated datatype, cannot select a subfield", ServiceException::INVALID_ARGUMENT);
		$dbFieldParams = $dataType->getXml()->xpath($subfieldName);
		if(!$dbFieldParams) throw new ServiceException("Subfield $subfieldName is not defined in config of datatype ".$dataType->getDataTypeName(), ServiceException::INVALID_ARGUMENT);		
		$this->subfields[$subfieldName] = $subfieldName;
	}
	
	/**
	 * Adds all subfields from Field datatype to the list of selected subfields	
	 */
	public function selectAllsubfields() {		
		$dataType = $this->field->getDataType();
		if(isset($dataType)) {
			foreach($dataType->getXml() as $dbFieldName => $dbFieldParams)
			{
				$this->subfields[$dbFieldName] = $dbFieldName;
			}
		}
	}
	
	public function getSelectedSubfieldsIterator() {
		return $this->subfields;		
	}
	
	public function hasSelectedSubfields() {
		return ($this->countSelectedSubfields() > 0);
	}
	
	public function countSelectedSubfields() {
		return count($this->subfields);
	}
}