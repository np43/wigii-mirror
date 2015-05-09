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
 * An abstract data flow activity which handles specific events triggered by
 * a DataFlowActivityEventDispatcher
 * Created by CWE on 7 juin 2013
 */
abstract class DataFlowActivityEventHandler implements DataFlowActivity
{	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();		
	}	
	public function freeMemory() {
		unset($this->dfaEventDispatcher);
	}
	
	// dependency injection
	
	private $dfaEventDispatcher;
	
	/**
	 * Sets the attached DataFlowActivityEventDispatcher used to trigger events
	 * which will be consumed back here.
	 * @param $dfaEventDispatcher a DataFlowActivityEventDispatcher instance
	 */
	public function setEventDispatcher($dfaEventDispatcher) {
		$this->dfaEventDispatcher = $dfaEventDispatcher;
		// link this event handler to the dispatcher
		$this->dfaEventDispatcher->setEventHandler($this);
	}
	
	/**
	 * Returns the attached DataFlowActivityEventDispatcher used to trigger events
	 * @param $dataFlowContext the current data flow context in which this activity is executing
	 */
	protected function getEventDispatcher($dataFlowContext) {
		return $this->dfaEventDispatcher;	
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->getEventDispatcher($dataFlowContext)->startOfStream($dataFlowContext);
	}
	
	public function processDataChunk($data, $dataFlowContext) {
		$this->getEventDispatcher($dataFlowContext)->processDataChunk($data, $dataFlowContext);
	}
		
	public function endOfStream($dataFlowContext) {
		$this->getEventDispatcher($dataFlowContext)->endOfStream($dataFlowContext);
	}
	
	
	// single data event handling
		
	public function processWholeData($data, $dataFlowContext) {
		$this->getEventDispatcher($dataFlowContext)->processWholeData($data, $dataFlowContext);
	}	
}