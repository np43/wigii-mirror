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
 * A WplObjectList Buffer data flow activity
 * Created by CWE on 19.01.2016
 */
class WplObjectListBufferDFA implements DataFlowActivity
{
	private $buffer;	
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();		
	}	
	public function freeMemory() {
		unset($this->buffer);
		unset($this->wplObjectName);
	}
	
	// configuration

	/**
	 * Sets the WplObjectList instance to be filled as a buffer. If not set, then a new instance is created. 
	 * @param WplObjectList $wplObjecList
	 */
	public function setWplObjectList($wplObjectList) {
		$this->buffer = $wplObjectList;
	}
	private $wplObjectName;
	/**
	 * Sets the name or class of the objects stored into the WplObjectList
	 * @param String $wplObjectName the name to be used. If not set, then uses default name 'wplObject'
	 */
	public function setWplObjectName($wplObjectName) {
		$this->wplObjectName = $wplObjectName;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if(!isset($this->buffer)) $this->buffer = TechnicalServiceProvider::getWplToolbox()->createWplObjectList($this->wplObjectName);
	}
	public function processDataChunk($data, $dataFlowContext) {
		$this->buffer->addWplObject($data);
	}
	public function endOfStream($dataFlowContext) {
		$dataFlowContext->writeResultToOutput($this->buffer, $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}