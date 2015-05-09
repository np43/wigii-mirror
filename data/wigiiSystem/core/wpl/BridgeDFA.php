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
 * A data flow activity which pushes the data into another dataflow
 * Created by CWE on 21 novembre 2013
 */
class BridgeDFA implements DataFlowActivity
{	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();		
	}	
	public function freeMemory() {
		unset($this->dataFlowContext);
		unset($this->bridgeToBeginning);
	}
	
	
	// configuration
	
	private $dataFlowContext;
	/**
	 * Sets the DataFlowContext of the connected data flow in which
	 * the data should be pushed
	 * @param DataFlowContext $dataFlowContext a valid open data flow
	 */
	public function setDataFlowContext($dataFlowContext) {
		$this->dataFlowContext = $dataFlowContext;
	}
		
	private $bridgeToBeginning;
	/**
	 * If true, then the data is pushed to the beginning of the connected dataflow,
	 * using DataFlowService->processDataChunk method,
	 * else the data is inserted as output in the active data flow activity, using
	 * DataFlowContext->writeResultToOutput.
	 * By default the data is inserted in the active data flow activity
	 * @param boolean $bool
	 */
	public function setBridgeToBeginning($bool) {
		$this->bridgeToBeginning = $bool;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if(!isset($this->dataFlowContext)) throw new DataFlowServiceException("no data flow context has been set", DataFlowServiceException::CONFIGURATION_ERROR);
	}
	public function processDataChunk($data, $dataFlowContext) {
		if($this->bridgeToBeginning) $this->dataFlowContext->getDataFlowService()->processDataChunk($data, $this->dataFlowContext);
		else $this->dataFlowContext->writeResultToOutput($data, $this);	
	}
	public function endOfStream($dataFlowContext) {
		/* nothing to do */
	}
		
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		return $this->endOfStream($dataFlowContext);
	}	
}