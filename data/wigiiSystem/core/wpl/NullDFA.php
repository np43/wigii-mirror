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
 * A data flow activity which does no transformation on data.
 * Or lets all items flow through the activity, or discards all items.
 * Created by CWE on 27 august 2014
 */
class NullDFA implements DataFlowActivity
{
	private $discardData;
	private $attributes;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->discardData = false;	
	}	
	public function freeMemory() {
		unset($this->attributes);
	}
	
	// configuration
	
	/**
	 * If true, then data is discarded from the flow and not passed any further,
	 * else data flows normally to the next stage without any modification.
	 * Defaults to false.
	 */
	public function setDiscardData($bool) {
		$this->discardData = $bool;
	}
	/**
	 * Injects into the DataFlowContext a pair (key,value) on startOfStream.
	 * Any existing value under this key is replaced.
	 * @param String $key attribute key
	 * @param mixed $val attribute value
	 */
	public function setAttributeInContext($key,$val) {
		if(!isset($key)) throw new DataFlowServiceException('key cannot be null', DataFlowServiceException::INVALID_ARGUMENT);
		if(!isset($this->attributes)) $this->attributes = array();
		$this->attributes[$key] = $val;
	}
	/**
	 * Injects into the DataFlowContext a set of pairs (key,value) on startOfStream.
	 * Any existing value under same keys are replaced.
	 * @param Array $arr an array of pairs (key,value)	 
	 */
	public function setAttributesInContext($arr) {
		if(!is_array($arr)) throw new DataFlowServiceException('arr should be an array', DataFlowServiceException::INVALID_ARGUMENT);
		if(!isset($this->attributes)) $this->attributes = array();
		$this->attributes = array_merge($this->attributes, $arr);
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		// initializes data flow context with injected attributes if any
		if(isset($this->attributes)) $dataFlowContext->setAttributesFromArray($this->attributes);
	}
	public function processDataChunk($data, $dataFlowContext) {
		if(!$this->discardData) $dataFlowContext->writeResultToOutput($data, $this);
	}
	public function endOfStream($dataFlowContext) {/* nothing to do */}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		if(!$this->discardData) $dataFlowContext->writeResultToOutput($data, $this);
	}	
}