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
 * A data flow activity which delegates stream processing to a func exp
 * Created by CWE on 1er octobre 2013
 */
class FuncExpDFA implements DataFlowActivity
{	
	private $_debugLogger;
	private $context;
	private $state;
	const FUNCEXP_DFA_IDLE = 0;
	const FUNCEXP_DFA_STARTSTREAM = 1;
	const FUNCEXP_DFA_RUNNING = 2;
	const FUNCEXP_DFA_ENDSTREAM = 3;
	const FUNCEXP_DFA_SINGLE_DATA = 4;
	private $funcExpEvaluator;
	private $funcExp;
	private $dataFlowContext;	
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->state = self::FUNCEXP_DFA_IDLE;
	}
	
	public function freeMemory() {
		unset($this->funcExp);
		if(isset($this->funcExpEvaluator) && method_exists($this->funcExpEvaluator, 'freeMemory')) {
			$this->funcExpEvaluator->freeMemory();
		}
		unset($this->funcExpEvaluator);
		unset($this->context);
		unset($this->dataFlowContext);
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FuncExpDFA");
		}
		return $this->_debugLogger;
	}
	
	// configuration
	
	/**
	 * Sets the FuncExp that should be executed on each data chunk
	 * The func exp will receive an array of arguments equal to :
	 * [dataChunk, $this, func exp args...]
	 * @param FuncExp $funcExp the FuncExp instance to be evaluated
	 */
	public function setFuncExp($funcExp) {
		$this->funcExp = $funcExp;
	}
	
	/**
	 * Sets the FuncExpEvaluator to be used to evaluate the attached FuncExp
	 * @param FuncExpEvaluator $funcExpEvaluator a valid running FuncExpEvaluator
	 */
	public function setFuncExpEvaluator($funcExpEvaluator) {
		if(is_null($funcExpEvaluator)) throw new DataFlowServiceException("funcExpEvaluator cannot be null", DataFlowServiceException::INVALID_ARGUMENT);
		$this->funcExpEvaluator = $funcExpEvaluator;
	}
	
	// state
	
	/**
	 * Sets a value in DataFlow context. 
	 * This map is used to store some state during the execution of the data flow.
	 * @param Scalar $key the key under which to store data
	 * @param Any $value the value stored as state
	 */
	public function setValueInContext($key, $value) {
		if(!isset($this->context)) $this->context = array();
		$this->context[$key] = $value;
	}
	
	/**
	 * Gests a value stored in the DataFlow context.
	 * @param Scalar $key the key for which to retrieve data
	 * @return the data or null if not defined
	 */
	public function getValueInContext($key) {
		if(!isset($this->context)) return null;
		else return $this->context[$key];
	}
	
	/**
	 * Returns true if this stream is starting
	 * Equivalent to the event StartStream
	 */
	public function isStartStream() {
		return $this->state == self::FUNCEXP_DFA_STARTSTREAM;
	}
	
	/**
	 * Returns true if this stream is ending
	 * Equivalent to the event EndStream
	 */
	public function isEndStream() {
		return $this->state == self::FUNCEXP_DFA_ENDSTREAM;
	}
	
	/**
	 * Returns true if this stream is running
	 * (between start stream and end stream)
	 */
	public function isRunning() {
		return $this->state == self::FUNCEXP_DFA_RUNNING;
	}
	
	/**
	 * Returns true if this stream has only one data chunk
	 * Equivalent to the call of processWholeData
	 */
	public function isSingleData() {
		return $this->state == self::FUNCEXP_DFA_SINGLE_DATA;
	}
	
	/**
	 * Returns current DataFlow state
	 * @return an integer, one of FUNCEXP_DFA_STARTING, FUNCEXP_DFA_RUNNING, 
	 * FUNCEXP_DFA_ENDING, FUNCEXP_DFA_SINGLE_DATA 
	 */
	public function getState() {
		return $this->state;
	}
	
	// helpers
	
	/**
	 * Returns a reference on the current DataFlowContext object
	 */
	public function getDataFlowContext() {
		return $this->dataFlowContext;
	}

	/**
	 * Writes some data to the output data flow
	 * See DataFlowContext::writeResultToOuput
	 */
	public function writeResultToOutput($resultData) {
		if(!isset($this->dataFlowContext)) throw new DataFlowServiceException("cannot write output when stream is not open", DataFlowServiceException::INVALID_STATE);
		$this->dataFlowContext->writeResultToOutput($resultData, $this);
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		if(isset($this->funcExp)) {
			$this->state = self::FUNCEXP_DFA_STARTSTREAM;
			$this->dataFlowContext = $dataFlowContext;		
			if(!isset($this->funcExpEvaluator)) throw new DataFlowServiceException("funcExpEvaluator has not been set", DataFlowServiceException::CONFIGURATION_ERROR);

			// Adds DataFlow arguments to FuncExp existing arguments
			$args = $this->funcExp->getArguments();
			$dataFlowArgs = array(null, $this);
			if(isset($args)) $this->funcExp->setArguments(array_merge($dataFlowArgs, $args));
			else $this->funcExp->setArguments($dataFlowArgs);
			
			$returnValue = $this->funcExpEvaluator->evaluateFuncExp($this->funcExp, $this);
			if(isset($returnValue)) $dataFlowContext->writeResultToOutput($returnValue, $this);
		}
	}
	
	public function processDataChunk($data, $dataFlowContext) {		
		if(isset($this->funcExp)) {
			$this->state = self::FUNCEXP_DFA_RUNNING;
			$this->dataFlowContext = $dataFlowContext;			
			// Sets DataFlow arguments to FuncExp arguments
			$args = $this->funcExp->getArguments();
			$args[0] = $data; $args[1] = $this;
			$this->funcExp->setArguments($args);
			//$this->debugLogger()->write((is_object($data) ? get_class($data) : json_encode($data)));
			
			$returnValue = $this->funcExpEvaluator->evaluateFuncExp($this->funcExp, $this);
			if(isset($returnValue)) $dataFlowContext->writeResultToOutput($returnValue, $this);
		}
	}
	
	public function endOfStream($dataFlowContext) {
		if(isset($this->funcExp)) {
			$this->state = self::FUNCEXP_DFA_ENDSTREAM;
			$this->dataFlowContext = $dataFlowContext;			
			// Sets DataFlow arguments to FuncExp arguments
			$args = $this->funcExp->getArguments();
			$args[0] = null; $args[1] = $this;
			$this->funcExp->setArguments($args);
			
			$returnValue = $this->funcExpEvaluator->evaluateFuncExp($this->funcExp, $this);
			if(isset($returnValue)) $dataFlowContext->writeResultToOutput($returnValue, $this);
		}
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		if(isset($this->funcExp)) {
			$this->state = self::FUNCEXP_DFA_SINGLE_DATA;
			$this->dataFlowContext = $dataFlowContext;		
			if(!isset($this->funcExpEvaluator)) throw new DataFlowServiceException("funcExpEvaluator has not been set", DataFlowServiceException::CONFIGURATION_ERROR);	

			// Adds DataFlow arguments to FuncExp existing arguments
			$args = $this->funcExp->getArguments();
			$dataFlowArgs = array($data, $this);
			if(isset($args)) $this->funcExp->setArguments(array_merge($dataFlowArgs, $args));
			else $this->funcExp->setArguments($dataFlowArgs);
			//$this->debugLogger()->write((is_object($data) ? get_class($data) : json_encode($data)));
			
			$returnValue = $this->funcExpEvaluator->evaluateFuncExp($this->funcExp, $this);
			if(isset($returnValue)) $dataFlowContext->writeResultToOutput($returnValue, $this);
		}
	}
}