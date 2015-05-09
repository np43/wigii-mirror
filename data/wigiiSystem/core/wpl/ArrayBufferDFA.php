<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *  
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/**
 * An Array Buffer data flow activity
 * Created by CWE on 27 janvier 2014
 */
class ArrayBufferDFA implements DataFlowActivity
{
	private $buffer;	
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();		
	}	
	public function freeMemory() {
		unset($this->buffer);
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->buffer = array();
	}
	public function processDataChunk($data, $dataFlowContext) {
		$this->buffer[] = $data;
	}
	public function endOfStream($dataFlowContext) {
		$dataFlowContext->writeResultToOutput($this->buffer, $this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$dataFlowContext->writeResultToOutput(array($data), $this);
	}	
}