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
 * A data flow activity which delegates stream processing to callbacks :
 * or a PHP closure or a specified method on an object.
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by CWE on 14 février 2014
 * Modified by Medair (CWE) on 28.11.2016 to protect against Cross Site Scripting
 */
class CallbackDFA implements DataFlowActivity
{
	private $dataFlowContext;	
	private $context;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();		
	}	
	public function freeMemory() {
		unset($this->startOfStreamCallback);
		unset($this->processDataChunkCallback);
		unset($this->endOfStreamCallback);
		unset($this->processWholeDataCallback);
		unset($this->dataFlowContext);
		unset($this->context);
	}
	
	// configuration
	
	private $startOfStreamCallback;
	/**
	 * Sets the callback that should be called on the startOfStream event.<br/>
	 * The callback signature is : <code>startOfStream(callbackDFA)</code><br/>,
	 * where arguments are :
	 * - Arg(0) callbackDFA: CallbackDFA, a reference to this object that can be used to get the 
	 * DataFlowContext and write output using the writeResultToOutput helper method.
	 * For behavior contract, see startOfStream event in DataFlowActivity interface.
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param mixed $obj an optional object instance which holds the method instance that should be executed.
	 */
	public function setStartOfStreamCallback($method, $obj=null) {
		$this->startOfStreamCallback = CallableObject::createInstance($method, $obj);
	}
	
	private $processDataChunkCallback;
	/**
	 * Sets the callback that should be called on the processDataChunk event.
	 * The callback signature is : <code>processDataChunk(data, callbackDFA)</code><br/>,
	 * where arguments are :
	 * - Arg(0) data: Any, the current chunk of data to be processed in the stream 
	 * - Arg(1) callbackDFA: CallbackDFA, a reference to this object that can be used to get the 
	 * DataFlowContext and write output using the writeResultToOutput helper method.
	 * For behavior contract, see processDataChunk event in DataFlowActivity interface.
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param mixed $obj an optional object instance which holds the method instance that should be executed.
	 */
	public function setProcessDataChunkCallback($method, $obj=null) {
		$this->processDataChunkCallback = CallableObject::createInstance($method, $obj);
	}
	
	private $endOfStreamCallback;
	/**
	 * Sets the callback that should be called on the endOfStream event.
	 * The callback signature is : <code>endOfStream(callbackDFA)</code><br/>,
	 * where arguments are :	 
	 * - Arg(0) callbackDFA: CallbackDFA, a reference to this object that can be used to get the 
	 * DataFlowContext and write output using the writeResultToOutput helper method.
	 * For behavior contract, see endOfStream event in DataFlowActivity interface.
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param mixed $obj an optional object instance which holds the method instance that should be executed.
	 */
	public function setEndOfStreamCallback($method, $obj=null) {
		$this->endOfStreamCallback = CallableObject::createInstance($method, $obj);
	}
	
	private $processWholeDataCallback;
	/**
	 * Sets the callback that should be called on the processWholeData event.
	 * The callback signature is : <code>processWholeData(data, callbackDFA)</code><br/>,
	 * where arguments are :	 
	 * - Arg(0) data: Any, the data to be processed
	 * - Arg(1) callbackDFA: CallbackDFA, a reference to this object that can be used to get the 
	 * DataFlowContext and write output using the writeResultToOutput helper method.
	 * For behavior contract, see processWholeData event in DataFlowActivity interface.
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param mixed $obj an optional object instance which holds the method instance that should be executed.
	 */
	public function setProcessWholeDataCallback($method, $obj=null) {
		$this->processWholeDataCallback = CallableObject::createInstance($method, $obj);
	}
	
	/**
	 * Initializes the context with some pairs of key/value
	 */
	public function initializeContext($arr) {
		if(empty($arr)) return;
		if(!is_array($arr)) throw new DataFlowServiceException("argument should be an array", DataFlowServiceException::INVALID_ARGUMENT);
		if(!isset($this->context)) $this->context = array();		
		$this->context = array_merge($this->context, $arr);
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
		$this->dataFlowContext->writeResultToOutput($resultData, $this);
	}
	
	/**
	 * Sets a value in the CallbackDFA context. 
	 * This map is used to store some state during the execution of the data flow.
	 * @param String|Number $key the key under which to store data
	 * @param mixed $value the value stored as state
	 */
	public function setValueInContext($key, $value) {
		if(!isset($this->context)) $this->context = array();
		$this->context[$key] = $value;
	}
	
	/**
	 * Gets a value stored in the CallbackDFA context.
	 * @param String|Number $key the key for which to retrieve data
	 * @return mixed the data or null if not defined
	 */
	public function getValueInContext($key) {
		if(!isset($this->context)) return null;
		else return $this->context[$key];
	}

	/**
	 * Returns an iterator on the context values
	 * The iterator is compatible with the foreach structure key=>value.
	 */
	public function getContextIterator() {
		return $this->context;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
		$this->dataFlowContext = $dataFlowContext;
		if(isset($this->startOfStreamCallback)) $this->startOfStreamCallback->invoke($this);
	}
	public function processDataChunk($data, $dataFlowContext) {
		if(isset($this->processDataChunkCallback)) $this->processDataChunkCallback->invoke($data, $this);
	}
	public function endOfStream($dataFlowContext) {
		if(isset($this->endOfStreamCallback)) return $this->endOfStreamCallback->invoke($this);
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
		if(isset($this->processWholeDataCallback)) {
			$this->dataFlowContext = $dataFlowContext;
			$this->processWholeDataCallback->invoke($data, $this);
		}
		else {
			$this->startOfStream($dataFlowContext);
			$this->processDataChunk($data, $dataFlowContext);
			return $this->endOfStream($dataFlowContext);
		}
	}	
}