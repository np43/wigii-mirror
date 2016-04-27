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
 * A substream tap data flow activity
 * Processes a list of substream IDs
 * For each substream, asks to a substream source to process the substream
 * If the substream ends with an output, then passes this output to the next stage in the pipe
 * else just forwards the substream ID to the next stage for further processing.
 * The substream is described with an injected DataFlowActivitySelectorList
 * Subclasses can redefine the getSubstreamDescriptor(substreamID) method 
 * to return a specific DataFlowActivitySelectorList per substream instead of the injected one.
 * Created by CWE on 27 juin 2013
 */
class SubstreamTapDFA implements DataFlowActivity
{
	private $substreamSource;
	private $substreamSourceCallback;
	private $substreamDescriptor;
	private $isLastStep;
	
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->substreamSourceCallback = "processSubstream";
		$this->isLastStep = false;
	}	
	public function freeMemory() {
		unset($this->substreamSource);
		unset($this->substreamDescriptor);
	}
	
	
	// configuration
	
	/**
	 * Sets the substream descriptor to be used for each substream
	 * @param $substreamDescriptorDFASL an instance of a DataFlowActivitySelectorList class, 
	 * describing the substream.
	 */
	public function setSubstreamDescriptor($substreamDescriptorDFASL) {
		$this->substreamDescriptor = $substreamDescriptorDFASL;
	}
	
	/**
	 * Returns the descriptor of a substream given its ID
	 * In this implementation, always returns the injected substream descriptor,
	 * but subclasses could reimplement this method to provide different substream
	 * descriptors according to the substream ID
	 */
	protected function getSubstreamDescriptor($substreamId) {
		if(!isset($this->substreamDescriptor)) throw new DataFlowServiceException("substream descriptor has not been set", DataFlowServiceException::CONFIGURATION_ERROR);
		return $this->substreamDescriptor;
	}
	
	/**
	 * Sets the object that will act as a source of substreams.
	 * The substreamTap will call the callbackMethod on the substream source for each data chunk
	 * The substreamSource should then feed the substream with some data.
	 * The callback method name defaults to 'processSubstream'
	 * The signature is callbackMethod(substreamId, substreamDescriptor)
	 * where :
	 * - substreamId is some data identifying the substream (this data is one data chunk in the SubstreamTapDFA)
	 * - substreamDescriptor is an instance of a DataFlowActivitySelectorList describing the substream
	 * The callbackMethod should return the result of the substream if any.
	 * (for DataFlowActivitySelectors : this method also accepts first argument as an array with
	 * two keys key1='substreamSource' value1=$substreamSource, key2='callbackMethod', value2=$callbackMethod)
	 */
	public function setSubstreamSource($substreamSource, $callbackMethod="processSubstream") {
		if(is_null($substreamSource)) throw new DataFlowServiceException("substreamSource cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		if(is_array($substreamSource)) {
			$this->setSubstreamSource($substreamSource['substreamSource'], $substreamSource['callbackMethod']);
		}
		else {
			if(is_null($callbackMethod) || $callbackMethod == '') throw new DataFlowServiceException("callback method cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
			if(!method_exists($substreamSource, $callbackMethod)) throw new DataFlowServiceException("callback method '$callbackMethod' does not exist on substream source of class ".get_class($substreamSource), DataFlowServiceException::INVALID_ARGUMENT);
			$this->substreamSource = $substreamSource;
			$this->substreamSourceCallback = $callbackMethod;
		}
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		// checks configuration
		if(!isset($this->substreamSource)) throw new DataFlowServiceException("substream source has not been set", DataFlowServiceException::CONFIGURATION_ERROR);
		// are we the last step of the pipe ?
		$this->isLastStep = $dataFlowContext->isCurrentStepTheLastStep();		
	}
	public function processDataChunk($data, $dataFlowContext) {
		if(isset($data)) {
			// gets substream descriptor
			$desc = $this->getSubstreamDescriptor($data);
			// processes substream			
			$returnValue = $this->substreamSource->{$this->substreamSourceCallback}($data, $desc);
			// if not last stage in pipe then
			if(!$this->isLastStep) {
				// if we have some output, sends the output
				if(isset($returnValue)) $dataFlowContext->writeResultToOutput($returnValue, $this);
				// else sends the substream ID to next processing stage
				else $dataFlowContext->writeResultToOutput($data, $this);
			}			
		}
	}
	public function endOfStream($dataFlowContext) {
		/*nothing to do*/
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}