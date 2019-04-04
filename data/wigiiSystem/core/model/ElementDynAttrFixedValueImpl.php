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
 * An Element Dynamic Attribute implementation storing a fixed value.
 * This is exactly equivalent to extending the element with new named getters.
 * Created by CWE on 11.9.2015
 */
class ElementDynAttrFixedValueImpl implements ElementDynamicAttribute
{
	private $value;
	
	// Object lifecycle
	
	public static function createInstance($value) {
		$returnValue = new self();
		$returnValue->initialize($value);
		return $returnValue;
	}
	
	protected function initialize($value) {
		$this->value = $value;
	}
	
	// Dependency injection
	
	/**
	 * Injects the Element to which this dynamic attribute is attached
	 * @param Element $element
	 */
	public function setElement($element) {/* nothing to do */}
	
	// Accessors
	
	/**
	 * Returns the value stored in this dynamic attribute or null if not set.
	 * @return mixed any type of value (scalar or object) or null if not set.
	 * @throws RecordException if an error occurs while reading the value.
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * Sets the value of this dynamic attribute.
	 * @param mixed $val any type of value (scalar or object).
	 * @throws RecordException if an error occurs while setting the value or
	 * RecordException::UNSUPPORTED_OPERATION if the value cannot be set because its a read only attribute.
	 */
	public function setValue($val) {
		throw new RecordException('fixed value, cannot be changed', RecordException::UNSUPPORTED_OPERATION);
	}
	
	/**
	 * Returns true if this dynamic property is writable,
	 * i.e. that the method setValue is enabled and does not throw a RecordException::UNSUPPORTED_OPERATION.
	 * @return boolean
	 */
	public function isSetValueEnabled() {
		// fixed value --> set not allowed
		return false;
	}
	
	// Element attribute listener
	
	/**
	 * Returns true if this class listens to element standard attribute changes
	 */
	public function listenToElementAttributeChange() {
		// no listener
		return false;
	}
	
	/**
	 * This class is notified that a standard element attribute has changed.
	 * This method is called only if the method listenToElementAttributeChange returns true.
	 * @param String $name the name of the attribute
	 * @param mixed $oldValue the old value of the attribute
	 * @param mixed $newValue the new value of the attribute
	 */
	public function elementSetAttribute($name, $oldValue, $newValue) {/* nothing to do */}
}

