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
 * A data flow activity which reverses the order of the items in the flow
 * Created by CWE on 27 august 2014
 */
class ReverseOrderDFA implements DataFlowActivity
{
	private $buffer;
	private $n;	
	private $duplicateContent;
	private $duplicateMedian;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->duplicateContent = false;	
		$this->duplicateMedian = false;
	}	
	public function freeMemory() {
		unset($this->buffer);
		$this->n = 0;
	}
	
	// configuration
	
	/**
	 * If true, then content is duplicated while reversing the order.
	 * As a result the flow will first get the content in the original order,
	 * then get the content in the reversed order.
	 * If false, then only the content in reverse order is sent in the flow.
	 * Defaults to false.
	 */
	public function setDuplicateContent($bool) {
		$this->duplicateContent = $bool;
	}
	
	/**
	 * If true and duplicate content is true, then the median item is also duplicated.
	 * Else, the median item is not duplicated. Defaults to false.
	 */
	public function setDuplicateMedian($bool) {
		$this->duplicateMedian = $bool;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->buffer = array();
	}
	public function processDataChunk($data, $dataFlowContext) {
		$this->buffer[] = $data;
		$this->n++;
	}
	public function endOfStream($dataFlowContext) {
		if($this->n > 1) {
			if($this->duplicateContent) {
				foreach($this->buffer as $d) {
					$dataFlowContext->writeResultToOutput($d, $this);
				}
				if($this->duplicateMedian) $s = $this->n-1;
				else $s = $this->n-2;
			}
			else $s = $this->n-1;
			for($i=$s;$i>=0;$i--) {
				$dataFlowContext->writeResultToOutput($this->buffer[$i], $this);
			}
		}
		elseif($this->n > 0) $this->processWholeData($this->buffer[0], $dataFlowContext);		
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$dataFlowContext->writeResultToOutput($data, $this);
		if($this->duplicateContent && $this->duplicateMedian) {
			$dataFlowContext->writeResultToOutput($data, $this);
		}
	}	
}