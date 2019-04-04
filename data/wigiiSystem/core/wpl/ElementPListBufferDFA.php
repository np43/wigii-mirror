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
 * An ElementPList Buffer data flow activity
 * Created by CWE on 29 April 2015
 */
class ElementPListBufferDFA implements DataFlowActivity
{
	private $elementPList;	
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();		
	}	
	public function freeMemory() {
		unset($this->elementPList);
	}
	
	// configuration
	
	/**
	 * Sets the ElementPList buffer to be filled with the flow
	 * @param ElementPList $elementPList
	 */
	public function setElementPList($elementPList) {
		$this->elementPList = $elementPList;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if(!isset($this->elementPList)) throw new DataFlowServiceException('ElementPList is not set, please inject one to be filled.', DataFlowServiceException::CONFIGURATION_ERROR);
	}
	public function processDataChunk($data, $dataFlowContext) {
		$this->elementPList->addElementP($data);
	}
	public function endOfStream($dataFlowContext) {
		$dataFlowContext->writeResultToOutput($this->elementPList, $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}