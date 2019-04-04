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
 * A data flow activity which splits an hybrid flow of objects into several channels according to a selector function.
 * A channel can be :
 * - a data flow which processes the data and puts some output in the parent flow
 * - a func exp which is executed (using the input data or not) and whose return value is put in the parent data flow
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by CWE on 28 August 2014
 * Modified by Medair (CWE) on 15.12.2016 to protect against Cross Site Scripting
 */
class MultiChannelDFA extends ObjectDFAWithFuncExpVM
{			
	private $openChannels;
	
	// Object lifecycle
		
	public function reset() {
		parent::reset();		
	}	
	public function freeMemory() {
		unset($this->channelMap);
		unset($this->channelSelectorMethod);
		unset($this->openChannels);
		parent::freeMemory();
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("MultiChannelDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $channelSelectorMethod;
	/**
	 * Sets the selector method that should be executed on each object in the flow in order
	 * to build a logical key used to select the channel.
	 * The method signature takes one argument which is the current object in the data flow and
	 * should return a scalar that will be used as a channel key.
	 * @param FuncExp|FieldSelector|Closure|String $method a FuncExp or a FieldSelector returning the value of the channel key,
	 * or a closure representing the code that should be executed or a string which is an object method name or a function name
	 * @param mixed $object an optional object instance which holds the method instance that should be executed.
	 */
	public function setChannelSelectorMethod($method, $obj=null) {
		if($method instanceof FuncExp || $method instanceof FieldSelector) {
			$this->channelSelectorMethod = $method;
		}
		else $this->channelSelectorMethod = CallableObject::createInstance($method, $obj);
	}
	
	private $channelMap;
	/**
	 * Sets the map describing each channels.
	 * @param array $map an array which keys are the channel keys 
	 * and the values are DataFlowSelectorList or FuncExp or FieldSelector instances.
	 * example: array('passThrough' => dfasl(dfas('NullDFA')))	 
	 */
	public function setChannelMap($map) {
		if(!isset($map)) unset($this->channelMap);
		if(!is_array($map)) throw new DataFlowServiceException("channelMap should be an array", DataFlowServiceException::INVALID_ARGUMENT);
		$this->channelMap = $map;
	}		

	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
	}
	
	// object event handling
				
	protected function processObject($obj, $dataFlowContext) {
		
		// selects the channel
		if($this->channelSelectorMethod instanceof FuncExp || $this->channelSelectorMethod instanceof FieldSelector) {
			$channelKey = $this->evaluateFuncExp($this->channelSelectorMethod);
		}
		elseif(isset($this->channelSelectorMethod)) {
			$channelKey = $this->channelSelectorMethod->invoke($obj);
		}
		else {
			$channelKey = $this->getChannelSelector($obj);
		}
		
		$channelDataFlowContext = null;
		if(isset($channelKey)) {
			if(!isset($this->openChannels)) $this->openChannels = array();
			$channelDataFlowContext = $this->openChannels[$channelKey];		
			
			// opens channel if not done yet
			if(!isset($channelDataFlowContext)) {
				$channelDfasl = null;
				// gets channel expression
				if(isset($this->channelMap)) {
					$channelExp = $this->channelMap[$channelKey];
					// creates channel dfasl
					if($channelExp instanceof DataFlowSelectorList) $channelDfasl = $channelExp;
					else {
						$channelExp = $this->evaluateFuncExp($channelExp);
						if($channelExp instanceof DataFlowActivitySelectorList) $channelDfasl = $channelExp;
						else {
							$channelDfasl = dfasl(dfas('CallbackDFA',
								'setProcessDataChunkCallback', CallableObject::createInstance('channel_processDataChunk', $this),
								'initializeContext', array('firstChannelValue' => $channelExp)));
						}
					}
				}
				// opens the channel
				if(isset($channelDfasl)) {
					// adds the bridge to current flow
					$channelDfasl->addDataFlowActivitySelectorInstance(dfas('BridgeDFA', 'setDataFlowContext', $dataFlowContext));
					
					$channelDataFlowContext = $dataFlowContext->getDataFlowService()->startStream($dataFlowContext->getPrincipal(), $channelDfasl);
					$channelDataFlowContext->setAttribute('channelKey', $channelKey);
					$this->openChannels[$channelKey] = $channelDataFlowContext;
				}
			}
		}
		
		// pushes the data into the channel
		if(isset($channelDataFlowContext)) {
			$channelDataFlowContext->getDataFlowService()->processDataChunk($obj, $channelDataFlowContext);
		}
	}
	
	public function endOfStream($dataFlowContext) {
		// closes all open channels
		if(!empty($this->openChannels)) {
			foreach($this->openChannels as $channelDataFlowContext) {
				$channelDataFlowContext->getDataFlowService()->endStream($channelDataFlowContext);
			}
		}
	}
	
	// implementation
	
	protected function getChannelSelector($data) {
		if(is_scalar($data)) return $data;
		else throw new DataFlowServiceException('can only select a channel based on values, does not support objects. To select a channel based on objects, configure the setChannelSelectorMethod', DataFlowServiceException::UNSUPPORTED_OPERATION);
	}

	/**
	 * Channel processDataChunk callback
	 */
	public function channel_processDataChunk($data, $callbackDFA) {
		// evaluates channel func exp (except for first value which has already been evaluated while setting up the flow)
		$result = $callbackDFA->getValueInContext('firstChannelValue');
		if(isset($result)) $callbackDFA->setValueInContext('firstChannelValue', null);
		else $result = $this->evaluateFuncExp($this->channelMap[$callbackDFA->getDataFlowContext()->getAttribute('channelKey')]);
		
		// pushes result further
		if(isset($result)) $callbackDFA->writeResultToOutput($result);
	}
}