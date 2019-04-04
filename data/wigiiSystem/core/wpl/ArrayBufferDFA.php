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
 * An Array Buffer data flow activity
 * Created by CWE on 27 janvier 2014
 * Modified by CWE on 13.05.2016 to unpair a flow of pairs (key,value)
 */
class ArrayBufferDFA implements DataFlowActivity
{
	private $buffer;	

	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->unpair = false;		
		
	}	
	public function freeMemory() {
		unset($this->buffer);
		unset($this->keyField);
		unset($this->valueField);
	}
	
	// Configuration
	
	private $unpair;
	/**
	 * If true, indicates that the flow is a flow of pairs (key,value) represented as StdClass instances or Elements
	 * These pairs are unpaired and stored into the array as key=>value.
	 * Else, queues the data in the array as it arrives without handling keys.
	 */
	public function setUnpair($bool) {
		$this->unpair = $bool;
	}
	
	private $keyField; 
	/**
	 * Defines the name of the field to be used as a key
	 * If unpair and not set, then defaults to 'key'
	 * @param String $name field name
	 */
	public function setKeyField($name) {
		$this->keyField = $name;
	}
	private $valueField;
	/**
	 * If unpairing, then defines the name of the field to be used as a value, defaults to 'value'
	 * @param String $name field name
	 */
	public function setValueField($name) {
		$this->valueField = $name;
	}
	
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->buffer = array();
		if($this->unpair) {
			if(!isset($this->keyField)) $this->keyField = 'key';
			if(!isset($this->valueField)) $this->valueField = 'value';
		}
	}
	public function processDataChunk($data, $dataFlowContext) {
		if($this->unpair) {
			if(($data instanceof Element) || ($data instanceof ElementP)) {
				$element = $data->getDbEntity();
				$this->buffer[$element->getFieldValue($this->keyField)] = $element->getFieldValue($this->valueField);
			}
			elseif(is_object($data)) {
				$key = $data->{$this->keyField};
				$value = $data->{$this->valueField};
				if(!isset($value) && !property_exists($data, $this->valueField)) $value = $data;
				$this->buffer[$key] = $value;
			}
			else $this->buffer[$data] = $data;
		}
		elseif(isset($this->keyField) && is_object($data)) $this->buffer[$data->{$this->keyField}] = $data;
		else $this->buffer[] = $data;
	}
	public function endOfStream($dataFlowContext) {
		$dataFlowContext->writeResultToOutput($this->buffer, $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$dataFlowContext->writeResultToOutput(array($data), $this);
	}	
}