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
 * A data flow activity which boxes a flow of values into a flow of StdClass instances
 * Created by CWE on 27 janvier 2014
 */
class BoxingDFA implements DataFlowActivity
{
	private $fieldName; 
	private $attributes;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->fieldName = "value";
	}	
	public function freeMemory() {
		unset($this->attributes);
	}
	
	
	// configuration
	
	/**
	 * Sets the field name under which the value will be stored into the resulting object
	 */
	public function setFieldName($fieldName) {
		$this->fieldName = $fieldName;
	}
	
	/**
	 * Sets some fixed attributes into the object
	 * @param Array $arr an array of the form (fieldName => value)
	 */
	public function setAttributes($arr) {
		if(!is_array($arr)) throw new DataFlowServiceException("arr should be an array", DataFlowServiceException::INVALID_ARGUMENT);
		$this->attributes = $arr;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		/* nothing to do */
	}
	public function processDataChunk($data, $dataFlowContext) {
		if(isset($this->attributes)) {
			$obj = array_merge($this->attributes, array($this->fieldName => $data));
		}
		else $obj = array($this->fieldName => $data);		
		$dataFlowContext->writeResultToOutput((object)$obj, $this);
	}
	public function endOfStream($dataFlowContext) {
		/* nothing to do */
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->processDataChunk($data, $dataFlowContext);
	}	
}