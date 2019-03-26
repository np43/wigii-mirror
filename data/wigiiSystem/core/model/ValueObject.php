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
 * An object wrapper around a value
 * Created by CWE on 10 decembre 15
 */
class ValueObject
{
	private $value;
	
	// Object lifecycle
	
	/**
	 * Creates a ValueObject instance which does not support arrays
	 * @param mixed $value default value which should not be an array
	 * @return ValueObject
	 */
	public static function createInstance($value=null) {
		$returnValue = new self();
		$returnValue->setSupportsArray(false);
		$returnValue->setValue($value);
		return $returnValue;
	}
	/**
	 * Creates a ValueObject instance which does support arrays
	 * @param string $value
	 * @return ValueObject
	 */
	public static function createInstanceForArrays($value=null) {
		$returnValue = new self();
		$returnValue->setSupportsArray(true);
		$returnValue->setValue($value);
		return $returnValue;
	}
	/**
	 * Resets the ValueObject to a default value
	 * @return ValueObject returns the ValueObject for chaining.
	 */
	public function reset($value=null) {
		$this->setValue($value);
		return $this;
	}
		
	// Dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("ValueObject");
		}
		return $this->_debugLogger;
	}
	
	// Configuration
	
	private $supportArray=false;
	
	/**
	 * Configures the ValueObject to support arrays or not
	 * @param Boolean $bool if true, then this ValueObject can hold arrays, else it can hold only single values or objects.
	 */
	public function setSupportsArray($bool) {
		$this->supportArray = $bool;
	}
	/**
	 * Returns true if this ValueObject supports holding arrays and not only single values
	 */
	public function supportsArray() {
		return $this->supportArray;
	}
	
	// Implementation
	
	
	public function setValue($value) {
		if(is_array($value) && !$this->supportsArray()) throw new ServiceException('this ValueObject does not support arrays', ServiceException::INVALID_ARGUMENT);
		$this->value = $value;
	}
	public function getValue() {
		return $this->value;
	}
	/**
	 * Returns true if value is empty. (calling the PHP empty function)
	 */
	public function isEmpty() {
		return empty($this->value);
	}
	/**
	 * Returns true if value===null
	 */
	public function isNull() {
		return ($this->value===null);
	}
	/**
	 * Returns the value cardinality
	 * If value returns 1, if array returns array count
	 */
	public function count() {
		if(is_array($this->value)) return count($this->value);
		elseif($this->isNull()) return 0;
		else return 1;
	}
}