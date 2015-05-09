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
 * A data flow activity which does no transformation on data.
 * Or lets all items flow through the activity, or discards all items.
 * Created by CWE on 27 august 2014
 */
class NullDFA implements DataFlowActivity
{
	private $discardData;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
		$this->discardData = false;	
	}	
	public function freeMemory() {/*nothing to do*/}
	
	// configuration
	
	/**
	 * If true, then data is discarded from the flow and not passed any further,
	 * else data flows normally to the next stage without any modification.
	 * Defaults to false.
	 */
	public function setDiscardData($bool) {
		$this->discardData = $bool;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {/* nothing to do */}
	public function processDataChunk($data, $dataFlowContext) {
		if(!$this->discardData) $dataFlowContext->writeResultToOutput($data, $this);
	}
	public function endOfStream($dataFlowContext) {/* nothing to do */}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		if(!$this->discardData) $dataFlowContext->writeResultToOutput($data, $this);
	}	
}