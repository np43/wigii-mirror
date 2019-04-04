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
 * An evaluation context used in the scope of evaluating calculated fields 
 * for elements fetched by the API and dumped into an ElementPList.
 * Typically the ElementPMapper object will provide such a context. 
 * Created by CWE on 16 février 2014
 */
class ElementPListEvalContext 
{
	private $_debugLogger;
	private $lockedForUse = true;	
	private $valuesInContext;
	private $recyclableValuesInContext;
	private $historizedValues;
	
	// Object lifecycle
		
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}
	
	public function reset()
	{
		$this->freeMemory();
		$this->lockedForUse = true;
	}
	public function freeMemory()
	{
		unset($this->elementOrdinal);
		unset($this->elementPListSize);
		unset($this->totalNumerOfElements);
		unset($this->valuesInContext);
		if(isset($this->recyclableValuesInContext)) {
			foreach($this->recyclableValuesInContext as $obj) {
				$this->recycleObject($obj);
			}
			unset($this->recyclableValuesInContext);
		}
		unset($this->historizedValues);
		$this->lockedForUse = false;		
	}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementPListEvalContext");
		}
		return $this->_debugLogger;
	}
	
	// Context methods
	
	private $elementOrdinal;
	/**
	 * Sets the current evaluated element ordinal. 
	 * This method is called by the ElementPMapper.
	 */
	public function setElementOrdinal($ordinal) {
		$this->elementOrdinal = $ordinal;
	}
	/**
	 * Returns the current evaluated element ordinal (index).
	 * First element of list has an ordinal equal to 1.
	 * @return int the ordinal of the current evaluated element or null if evaluation has not started.
	 */
	public function getElementOrdinal() {
		return $this->elementOrdinal;
	}

	private $elementPListSize;
	/**
	 * Sets the size of the current evaluated elementPList
	 * This method is called by the ElementPMapper. 
	 * @param int $size the size of the list
	 */
	public function setElementPListSize($size) {
		$this->elementPListSize = $size;
	}
	/**
	 * Returns the number of elements contained in the currently evaluated elementPList
	 * @return int the number of elements contained in the list.
	 * If the select in the database was done using a list filter with pagination, 
	 * then the number of elements in the list will always be smaller or equal to the page size.
	 */
	public function getElementPListSize() {
		return $this->elementPListSize;
	}
	
	private $totalNumerOfElements;
	/**
	 * Sets the total number of elements that should have been selected if no pagination
	 * This method is called by the ElementService
	 * @param int $n the total number of elements
	 */
	public function setTotalNumberOfElements($n) {
		$this->totalNumerOfElements = $n;
	}
	
	/**
	 * Returns the total number of elements that would have been selected if no pagination was used.
	 * See method 'getTotalNumberOfObjects' on the Listfilter class.
	 * If no pagination or number of elements smaller than the page size, 
	 * then the method getElementListSize and getTotalNumberOfElements return the same value.
	 * @return int or 0 if list is empty.
	 */
	public function getTotalNumberOfElements() {
		return $this->totalNumerOfElements;
	}
	
	/**
	 * Returns the value of an attribute stored into the evaluation context given a key
	 * @param String|Number $key the key under which the value is stored
	 * @return mixed returns the stored value or null if key is not defined.
	 */
	public function getValueInContext($key) {
		if(is_null($key)) throw new ElementServiceException("key cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(!isset($this->valuesInContext)) return null;
		else return $this->valuesInContext[$key];
	}
	
	/**
	 * Stores the value of an attribute into the evaluation context under a given key.
	 * @param String|Number $key the attribute key
	 * @param mixed $val the attribute value. Accepts null. Any existing value under the given key is replaced.
	 * @param Boolean $recyclable optional boolean. If true, then the method freeMemory (if exists) will be called on value, when the context is recycled,
	 * else no clean up code is executed, relies only on the garbage collection.
	 */
	public function setValueInContext($key, $val, $recyclable=false) {
		if(is_null($key)) throw new ElementServiceException("key cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(!isset($this->valuesInContext)) $this->valuesInContext = array();		
		$this->valuesInContext[$key] = $val;
		if($this->debugLogger()->isEnabled()) $this->debugLogger()->write("sets attribute '$key' with value '".(is_object($val) ? "instance of ".get_class($val) : $val)."'");
		if(!isset($this->recyclableValuesInContext)) $this->recyclableValuesInContext = array();
		$oldVal = $this->recyclableValuesInContext[$key];
		if($oldVal !== $val) $this->recycleObject($oldVal);
		if($recyclable) $this->recyclableValuesInContext[$key] = $val;	
		else unset($this->recyclableValuesInContext[$key]);
	}
	
	/**
	 * Returns an historized value given its key and ordinal.
	 * @param String|Number $key the key under which the values are stored
	 * @param int $ordinal the ordinal number for which we want the value.
	 * Starts with 1 and should be smaller or equal to the actual ordinal.
	 * @return mixed returns the stored value or null if key is not defined.
	 */
	public function getHistorizedValue($key, $ordinal) {
		if(is_null($key)) throw new ElementServiceException("key cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(!isset($this->historizedValues)) return null;		
		if(!(1 <= $ordinal && $ordinal <= $this->elementOrdinal)) throw new ElementServiceException("ordinal should be comprised between 1 and current ordinal which is ".$this->elementOrdinal, ElementServiceException::INVALID_ARGUMENT);
		$history = $this->historizedValues[$key];
		if(empty($history)) return null;
		return $history[$ordinal];
	}
	
	/**
	 * Returns an iterator on the history of a key.
	 * @param String|Number $key the key for which we want to retrieve the history.
	 * @param Boolean $desc defines the direction of the iteration. By default, desc is true, 
	 * meaning the the iterator will go from the current ordinal down to 1,
	 * if false, then the iterator will go from 1 up to the current ordinal.
	 */
	public function getHistorizedValueIterator($key, $desc=true) {
		if(is_null($key)) throw new ElementServiceException("key cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(!isset($this->historizedValues)) return array();
		$history = $this->historizedValues[$key];
		if(!isset($history)) return array();
		if($desc) return array_reverse($history, true);
		else return $history;
	}
	
	/**
	 * Stores a value into the evaluation context under a given key and using the current ordinal
	 * @param String|Number $key the key under which the values are stored
	 * @param mixed $value the attribute value. Accepts null. Any existing value under the given key is replaced.
	 */
	public function setHistorizedValue($key, $value) {
		if(is_null($key)) throw new ElementServiceException("key cannot be null", ElementServiceException::INVALID_ARGUMENT);
		if(!isset($this->historizedValues)) $this->historizedValues = array();
		$history = $this->historizedValues[$key];
		if(!isset($history)) $history = array();
		if(!isset($this->elementOrdinal)) throw new ElementServiceException("elementOrdinal has not been set, please inject one.", ElementServiceException::INVALID_STATE);
		$history[$this->elementOrdinal] = $value;
		$this->historizedValues[$key] = $history;
	}
	
	// Implementation
	
	private function recycleObject($obj) {
		if(isset($obj) && method_exists($obj, "freeMemory")) $obj->freeMemory();
	}
}