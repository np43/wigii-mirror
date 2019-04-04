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
 * A ValueList implementation which delegates addValue method to a callback
 * The List is forward only, not iterable, isEmpty and count are not supported.
 * This class should be used mainly to bridge producer and consumer of values.
 * Created by CWE on 31.03.2016
 */
class ValueListCallbackImpl implements ValueList
{
	private $context;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();
	}
	public function freeMemory() {
		unset($this->addValueCallback);
		unset($this->context);
	}
	
	/**
	 * Creates a ValueListCallbackImpl instance with a given callback for the ValueList::addValue method.
	 * The callback signature is : <code>addValue(val, callbackCtx)</code><br/>,
	 * where arguments are :
	 * - Arg(0) val: Any, the current data added to the ValueList
	 * - Arg(1) callbackCtx: ValueListCallbackImpl, a reference to this object that can be used to read or store an execution context during ValueList fill up.
	 * @param Closure|String $method a closure representing the code that should be executed or
	 * a string which is an object method name or a function name
	 * @param mixed $obj an optional object instance which holds the method instance that should be executed.
	 * @return ValueListCallbackImpl
	 */
	public static function createInstance($method, $obj=null)
	{
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setAddValueCallback($method,$obj);
		return $returnValue;
	}
	
	// configuration	
	
	private $addValueCallback;
	/**
	 * Sets the callback that should be called on the addValue call.
	 * The callback signature is : <code>addValue(val, callbackCtx)</code><br/>,
	 * where arguments are :
	 * - Arg(0) val: Any, the current data added to the ValueList
	 * - Arg(1) callbackCtx: ValueListCallbackImpl, a reference to this object that can be used to read or store an execution context during ValueList fill up.
	 * @param Closure|String $method a closure representing the code that should be executed or
	 * a string which is an object method name or a function name
	 * @param mixed $obj an optional object instance which holds the method instance that should be executed.
	 */
	public function setAddValueCallback($method, $obj=null) {
		$this->addValueCallback = CallableObject::createInstance($method, $obj);
	}	
	
	/**
	 * Initializes the context with some pairs of key/value
	 */
	public function initializeContext($arr) {
		if(empty($arr)) return;
		if(!is_array($arr)) throw new ServiceException("argument should be an array", ServiceException::INVALID_ARGUMENT);
		if(!isset($this->context)) $this->context = array();
		$this->context = array_merge($this->context, $arr);
	}
	
	// helpers
	
	/**
	 * Sets a value in context. This map is used to store some state during the filling of the ValueList.
	 * @param String|Number $key the key under which to store data
	 * @param mixed $value the value stored as state
	 */
	public function setValueInContext($key, $value) {
		if(!isset($this->context)) $this->context = array();
		$this->context[$key] = $value;
	}
	
	/**
	 * Gests a value stored in context.
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
	
	// ValueList implementation
		
	public function addValue($val) {
		if(isset($this->addValueCallback)) $this->addValueCallback->invoke($val,$this);
	}
	public function getListIterator() {throw ServiceException::UNSUPPORTED_OPERATION;}
	public function isEmpty(){throw ServiceException::UNSUPPORTED_OPERATION;}
	public function count(){throw ServiceException::UNSUPPORTED_OPERATION;}
}