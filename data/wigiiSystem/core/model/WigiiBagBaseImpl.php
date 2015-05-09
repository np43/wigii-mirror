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
 * Wigii Bag base implementation
 * It contains one direct access index.
 * Created by CWE on 24 oct. 09
 * Modified by CWE  on 27 mai 2014 to implement BulkLoadableWigiiBag
 */
class WigiiBagBaseImpl extends Model implements BulkLoadableWigiiBag
{
	private $_debugLogger;
	// wigii bag storage
	private $idGen;
	private $values;
	private $fixedBag;
	private $loadingFromFixedBag;
	private $loadedFromFixedBag;
	// direct access index
	private $elementIds;
	private $valuesDirectAccessIndex;
	private $valuesFieldIndex;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WigiiBagBaseImpl");
		}
		return $this->_debugLogger;
	}

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
		$this->idGen = 0;
		$this->values = array();
		$this->elementIds = array();
		$this->valuesDirectAccessIndex = array();
		$this->valuesFieldIndex = array();
		$this->loadingFromFixedBag = false;
		$this->loadedFromFixedBag = false;
		$this->debugLogger()->write("reset wigii bag");
	}
	protected function freeMemory()
	{
		unset($this->values);
		unset($this->fixedBag);
		unset($this->elementIds);
		unset($this->valuesDirectAccessIndex);
		unset($this->valuesFieldIndex);
	}

	// Bulk loadable Wigii bag implementation

	public function setFixedBag($wigiiFixedBag, $elementIds) {
		if(!isset($this->fixedBag)) {
			$this->fixedBag = $wigiiFixedBag;
			if(is_array($elementIds)) $this->elementIds = array_merge($this->elementIds, $elementIds);
			else $this->addElementId($elementIds);
		}
		elseif(isset($wigiiFixedBag)) throw new RecordException('A fix bag is already set. Please first reset the bag.', RecordException::INVALID_STATE);
	}
	
	public function acceptsFixedBag() {
		return !isset($this->fixedBag);
		//return false;
	}
	
	/**
	 * Copies the content of the fixed bag into the local bag
	 */
	private function loadFromFixedBag() {
		if(isset($this->fixedBag) && !$this->loadingFromFixedBag) {
			$this->loadingFromFixedBag = true;
			try {
				$this->fixedBag->copyIntoWigiiBag($this);
				$this->loadingFromFixedBag = false;				
			}
			catch(Exception $e) {
				$this->loadingFromFixedBag = false;
				throw $e;
			}
			$this->loadedFromFixedBag = true;
		}
	}
	
	public function getValue($elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		if(is_null($subFieldName)) $subFieldName = "value";
		$wigiiBagKey = $this->getWigiiBagKeyFromDirectAccessIndex($elementId, $dataTypeName, $fieldName, $subFieldName);
		// if we have a fixed bag and no local value --> returns the value in the fixed bag
		if(!isset($wigiiBagKey) && isset($this->fixedBag)) {
			return $this->fixedBag->getValue($elementId, $fieldName, $subFieldName);
		}
		else return $this->getStoredValue($wigiiBagKey);
	}

	public function setValue($value, $elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		if(is_null($subFieldName)) $subFieldName = "value";
		$wigiiBagKey = $this->getWigiiBagKeyFromDirectAccessIndex($elementId, $dataTypeName, $fieldName, $subFieldName);
		// if loadingFromFixedBag then storeValue is authorized only if value does not exist in local bag.
		if(!$this->loadingFromFixedBag || ($this->loadingFromFixedBag && !isset($wigiiBagKey))) {
			if(!isset($wigiiBagKey))
			{
				$wigiiBagKey = $this->getNewWigiiBagKey();
				$this->addElementId($elementId);
				$this->indexWigiiBagValue($wigiiBagKey, $elementId, $dataTypeName, $fieldName, $subFieldName);
			}		
			$this->storeValue($wigiiBagKey, $value);
			//$this->debugLogger()->write("setValue $elementId $fieldName $subFieldName = $value");
		}
	}

	protected function validateOperator($operator){
		switch ($operator){
			case SUPDOP_SET:
			case SUPDOP_ADD:
			case SUPDOP_SET_IF_NULL:
				return true;
			default:
				throw new ServiceException("invalid operator: $operator", ServiceException::INVALID_ARGUMENT);
		}
	}
	public function applyOperator($operator, $newValue, $dataTypeName, $fieldName, $subFieldName=null, $lang=null, $field=null){
		$this->validateOperator($operator);
		if(isset($this->fixedBag) && !$this->loadedFromFixedBag) $this->loadFromFixedBag();
		
		if(is_null($subFieldName)) $subFieldName = "value";
		$keys = $this->getWigiiBagKeysFromFieldIndex($fieldName, $subFieldName);
		if($operator != SUPDOP_SET && $operator != SUPDOP_SET_IF_NULL && $keys == null){
		}
		//if nothing is setup in the WigiiBag for this field+subField then create them
		//if the operator is set or set if null
		if($keys == null && ($operator == SUPDOP_SET || $operator == SUPDOP_SET_IF_NULL)){
			//create entries for each element ids
			if($lang != null){
				$newValue[$lang] = $newValue;
			}
			$elementIds = $this->getElementIds();
			foreach($elementIds as $elementId){
				$this->setValue($newValue, $elementId, $dataTypeName, $fieldName, $subFieldName);
			}
		} else {
			if($keys==null){
				throw new ServiceException("WigiiBag contains no data for field: $fieldName and subField: $subFieldName", ServiceException::INVALID_ARGUMENT);
			}
			foreach($keys as $elementId=>$key){
				$value = $this->getStoredValue($key);
				switch ($operator){
					case SUPDOP_SET:
						//multilanguage fields
						if($lang != null){
							$value[$lang] = $newValue;
							$this->storeValue($key, $value);
						} else {
							$this->storeValue($key, $newValue);
						}
						break;
					case SUPDOP_SET_IF_NULL:
						//multilanguage fields
						if($lang != null){
							if($value[$lang]==null){
								$value[$lang] = $newValue;
								$this->storeValue($key, $value);
							}
						} else {
							if($value==null){
								$this->storeValue($key, $newValue);
							}
						}
						break;
					case SUPDOP_ADD:
						if($newValue != null){
							//either concat, either replace if not empty
							switch($dataTypeName){
								case "Addresses":
									//perform an add
									$this->storeValue($key, $newValue);
									break;
								case "MultipleAttributs":
									//performe a merge
									$newValue = array_combine(array_values($newValue), array_values($newValue));
									if($value != null){
										$value = array_merge($value, $newValue);
									}
									else $value = $newValue;
									$this->storeValue($key, $value);
									break;
								case "Emails":
									//perform an add
									$this->storeValue($key, str_update($value, $newValue, SUPDOP_ADD, '/'.ValueListArrayMapper::Natural_Separators.'/', ", "));
									break;
								case "Blobs":
									$fieldXml = $field->getXml();
									if($fieldXml && $fieldXml["isJournal"]=="1"){
										//in the case of the isJournal the string is already prepared to add new lines in between
										$this->storeValue($key, $newValue.$value);
									} else {
										//perform a concatenation with at the end
										if($value != null ){
											if($fieldXml["htmlArea"]=="1"){
												$value .= "<p>&nbsp;</p>";
											} else {
												$value .= "\n";
											}
										}
										$this->storeValue($key, $value.$newValue);
									}
									break;
								case "Numerics":
								case "Floats":
									//perform an addition
									if($value != null ) $value = 0;
									$this->storeValue($key, $value+(double)$newValue);
									break;
								case "Texts":
									//perform a concatenation with a \n
									if($lang == null) throw new ServiceException("try to perform add operation on a Texts field without defining the lang parameter for field: $fieldName and subField: $subFieldName", ServiceException::INVALID_ARGUMENT);
									if($value[$lang] != null ){
										if($fieldXml && $fieldXml["htmlArea"]=="1"){
											$value .= "<p></p>";
										} else {
											$value .= "\n";
										}
									}
									$value[$lang] .= $newValue;
									$this->storeValue($key, $value);
									break;
								default:
									throw new ServiceException("try to perform a forbidden $operator operation on dataType $dataTypeName for field: $fieldName and subField: $subFieldName", ServiceException::INVALID_ARGUMENT);
							}
						}
						break;
				}
			}
		}
	}

	/**
	 * (extension point)
	 * indexes wigii bag value in one or several index according to the different dimensions
	 * this implementation only indexes it in a DirectAccessIndex based on all dimensions.
	 */
	protected function indexWigiiBagValue($wigiiBagKey, $elementId, $dataTypeName, $fieldName, $subFieldName)
	{
		$this->indexWigiiBagKeyInDirectAccessIndex($wigiiBagKey, $elementId, $dataTypeName, $fieldName, $subFieldName);
		$this->indexWigiiBagKeyInFieldIndex($wigiiBagKey, $elementId, $dataTypeName, $fieldName, $subFieldName);
	}

	// Wigii bag internal access functions

	protected function getStoredValue($wigiiBagKey)
	{

		if(is_null($wigiiBagKey)) return null;
		return $this->values[$wigiiBagKey];
	}
	protected function storeValue($wigiiBagKey, $value)
	{
		if(is_null($wigiiBagKey)) throw new ServiceException("wigiiBagKey cannot be null", ServiceException::INVALID_ARGUMENT);
		$this->values[$wigiiBagKey] = $value;
	}

	// Wigii bag key generator
	protected function getNewWigiiBagKey()
	{
		$this->idGen++;
		return $this->idGen;
	}

	protected function addElementId($elementId)
	{
		$this->elementIds[$elementId] = $elementId;
	}

	protected function getElementIds()
	{
		return $this->elementIds;
	}
	// Direct access index

	protected function getWigiiBagKeyFromDirectAccessIndex($elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		return $this->valuesDirectAccessIndex[$this->createDirectAccessIndexKey($elementId, $dataTypeName, $fieldName, $subFieldName)];
	}
	protected function indexWigiiBagKeyInDirectAccessIndex($wigiiBagKey, $elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		$this->valuesDirectAccessIndex[$this->createDirectAccessIndexKey($elementId, $dataTypeName, $fieldName, $subFieldName)] = $wigiiBagKey;
	}
	private function createDirectAccessIndexKey($elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		return "($elementId($fieldName($subFieldName)))";
	}

	// Field access index (fieldName, subFieldName)

	protected function getWigiiBagKeysFromFieldIndex($fieldName, $subFieldName=null)
	{
		return $this->valuesFieldIndex[$this->createFieldIndexKey($fieldName, $subFieldName)];
	}
	protected function indexWigiiBagKeyInFieldIndex($wigiiBagKey, $elementId, $dataTypeName, $fieldName, $subFieldName=null)
	{
		$key = $this->createFieldIndexKey($fieldName, $subFieldName);
		if(!isset($this->valuesFieldIndex[$key])) $this->valuesFieldIndex[$key] = array();
		$this->valuesFieldIndex[$key][$elementId] = $wigiiBagKey; //fieldName + subFieldName is unique in one element
	}
	private function createFieldIndexKey($fieldName, $subFieldName=null)
	{
		return "($fieldName($subFieldName))";
	}

}