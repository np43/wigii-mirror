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
 * A data flow activity which limits the flow of data based on the index of the items in the flow.
 * Items are numbered from 1 to n in the flow.
 * Created by CWE on 27 august 2014
 */
class LimitDFA implements DataFlowActivity
{
	private $itemCount;
	private $startIndex;
	private $stopIndex;
	private $limit;
	private $calculatedStopIndex;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->itemCount = 0;	
		$this->startIndex = 1;
		$this->stopIndex = 0;
		$this->limit = 0;
		$this->calculatedStopIndex = 0;
	}	
	public function freeMemory() {/*nothing to do*/}
	
	// configuration
	
	/**
	 * Sets the start index from which to let pass items through the flow.
	 * Defaults to 1 which is the first item.
	 */
	public function setStartIndex($index) {
		$this->startIndex = $index;
	}
	
	/**
	 * Sets the limit number of items to let pass through the flow.
	 * If smaller or equal to 0 then no limit; defaults to 0.
	 * This property is used in conjunction with the setStopIndex property.
	 * The most restrictive wins.
	 */
	public function setLimit($nbItems) {
		$this->limit = $nbItems;
	}
	
	/**
	 * Sets the last index for which to let pass through the flow.
	 * All items having an index greater than the stop index will be discarded.
	 * If stop index is smaller or equal to 0, then there is no stop index; defaults to 0.
	 * This property is used in conjunction with the setLimit property.
	 * The most restrictive wins.
	 */
	public function setStopIndex($index) {
		$this->stopIndex = $index;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->itemCount = 0;
		if($this->startIndex < 1) $this->startIndex = 1;
		if($this->stopIndex < 0) $this->stopIndex = 0;
		if($this->limit > 0) {
			$this->calculatedStopIndex = $this->startIndex + $this->limit - 1;
		}
		else $this->calculatedStopIndex = 0;
		if($this->calculatedStopIndex > 0 && $this->stopIndex > 0) {
			$this->calculatedStopIndex = ($this->calculatedStopIndex < $this->stopIndex ? $this->calculatedStopIndex : $this->stopIndex);
		}
		elseif($this->stopIndex > 0) $this->calculatedStopIndex = $this->stopIndex;
	}
	public function processDataChunk($data, $dataFlowContext) {
		$this->itemCount++;
		if($this->startIndex <= $this->itemCount &&
			($this->itemCount <= $this->calculatedStopIndex || $this->calculatedStopIndex <= 0)) {
			$dataFlowContext->writeResultToOutput($data, $this);
		}
	}
	public function endOfStream($dataFlowContext) {/* nothing to do */}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$dataFlowContext->writeResultToOutput($data, $this);
	}	
}