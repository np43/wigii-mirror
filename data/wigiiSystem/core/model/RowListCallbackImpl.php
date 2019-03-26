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
 * RowList callback implementation
 * Created by CWE on 23.03.2015
 */
class RowListCallbackImpl implements RowList, WigiiExclusiveAccessObject
{
	private $isLockedForUse = true;
	private $nRows;
	
	// Object lifecycle
	
	/**
	 * Creates a rowlist callback.
	 * The callback signature is : <code>addRow(rowListCallabackImpl, row)</code><br/>,
	 * where arguments are :
	 * - Arg(0) rowListCallbackImpl: a reference to this object that can be used to access the isEmpty or count methods.
	 * - Arg(1) row: Array, the row added to the list.
	 * For behavior contract, see addRow method in RowList interface.
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param mixed $obj an optional object instance which holds the method instance that should be executed.
	 */
	public static function createInstance($method, $obj=null) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setAddRowCallback($method, $obj);
		return $returnValue;
	}
	
	public function reset() {
		$this->freeMemory();		
		$this->isLockedForUse = true;
		$this->nRows = 0;
	}
	
	public function freeMemory() {
		unset($this->addRowCallback);
		$this->isLockedForUse = false;
	}
	
	public function isLockedForUse() {
		return $this->isLockedForUse;
	}
	
	
	// Configuration
	
	private $addRowCallback;
	/**
	 * Sets the callback for the addRow method.
	 * The callback signature is : <code>addRow(rowListCallabackImpl, row)</code><br/>,
	 * where arguments are :
	 * - Arg(0) rowListCallbackImpl: a reference to this object that can be used to access the isEmpty or count methods.
	 * - Arg(1) row: Array, the row added to the list.
	 * For behavior contract, see addRow method in RowList interface.
	 * @param Closure|String $method a closure representing the code that should be executed or
	 * a string which is an object method name or a function name
	 * @param mixed $obj an optional object instance which holds the method instance that should be executed.
	 */
	public function setAddRowCallback($method, $obj=null) {
		$this->addRowCallback = CallableObject::createInstance($method, $obj);
	}
	
	// RowList implementation
	
	public function addRow($row) {
		$this->nRows++;
		if(!isset($this->addRowCallback)) throw new ListException('no callback defined for the addRow method. Please add one, using the setAddRowCallback method.', ListException::CONFIGURATION_ERROR); 
		$this->addRowCallback->invoke($this, $row);
	}

	public function getListIterator() {
		throw new ListException('RowListCallbackImpl does not support iteration. Use RowListArrayImpl instead.', ListException::UNSUPPORTED_OPERATION);
	}
	
	public function isEmpty() {
		return ($this->nRows == 0);
	}
	
	public function count() {
		return $this->nRows;
	}
}
