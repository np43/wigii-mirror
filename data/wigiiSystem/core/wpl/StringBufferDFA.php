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
 * A String Buffer data flow activity
 * Created by CWE on 28 mai 2013
 */
class StringBufferDFA implements DataFlowActivity
{
	private $buffer;
	private $sep=""; 
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->sep = "";
	}	
	public function freeMemory() {
		unset($this->buffer);
	}
	
	
	// configuration
	
	/**
	 * Sets the data chunk separator
	 */
	public function setChunkSeparator($sep) {
		$this->sep = $sep;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->buffer = array();
	}
	public function processDataChunk($data, $dataFlowContext) {
		$this->buffer[] = $data;
	}
	public function endOfStream($dataFlowContext) {
		$dataFlowContext->writeResultToOutput(implode($this->sep, $this->buffer), $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$dataFlowContext->writeResultToOutput($data, $this);
	}	
}