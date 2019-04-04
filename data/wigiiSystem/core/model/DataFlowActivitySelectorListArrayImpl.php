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
 * DataFlowActivitySelectorList array implementation
 * Created by CWE on 28 mai 2013
 * Modified by Medair (CWE) on 28.11.2016 to protect against Cross Site Scripting
 */
class DataFlowActivitySelectorListArrayImpl extends ObjectListArrayImpl implements DataFlowActivitySelectorList
{
	private $originPublic;
	
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}	
	public function addDataFlowActivitySelectorInstance($dataFlowActivitySelector)
	{
		if(!isset($dataFlowActivitySelector)) throw new ListException("dataFlowActivitySelector cannot be null", ListException::INVALID_ARGUMENT);
		// propagates public origin if needed
		if($this->originPublic) $dataFlowActivitySelector->setOriginIsPublic();
		elseif($dataFlowActivitySelector->isOriginPublic()) $this->setOriginIsPublic();
		// adds DataFlowActivitySelector to the list
		$this->objArray[] = $dataFlowActivitySelector;
	}
	
	public function prependDataFlowActivitySelectorInstance($dataFlowActivitySelector)
	{
		if(!isset($dataFlowActivitySelector)) throw new ListException("dataFlowActivitySelector cannot be null", ListException::INVALID_ARGUMENT);
		// propagates public origin if needed
		if($this->originPublic) $dataFlowActivitySelector->setOriginIsPublic();
		elseif($dataFlowActivitySelector->isOriginPublic()) $this->setOriginIsPublic();
		// adds DataFlowActivitySelector to the list
		array_unshift($this->objArray, $dataFlowActivitySelector);
	}
	
	public function addDataFlowActivitySelector($className)
	{
		$this->addDataFlowActivitySelectorInstance(DataFlowActivitySelector::createInstance($className));
	}
	public function prependDataFlowActivitySelector($className)
	{
		$this->prependDataFlowActivitySelectorInstance(DataFlowActivitySelector::createInstance($className));
	}
	
	public function getDataFlowActivitySelector($index) {
		if($index < 0 || $index >= $this->count()) throw new ListException("index $index is out of bound", ListException::OUT_OF_BOUND);
		return $this->objArray[$index];
	}
	
	public function getDataFlowActivitySelectorByClassName($className) {
		$returnValue = null;
		foreach($this->objArray as $dfas) {
			if($dfas->getClassName() == $className) {
				if(isset($returnValue)) {
					if(!is_array($returnValue)) {
						$returnValue = array($returnValue, $dfas);
					}
					else $returnValue[] = $dfas;
				}
				else $returnValue = $dfas;
			}
		}
		return $returnValue;
	}
		
	public function setDataFlowActivityParameterForClass($className, $parameterName, $value) {
		foreach($this->objArray as $dfas) {
			if($dfas->getClassName() == $className) {
				$dfas->setDataFlowActivityParameter($parameterName, $value);
			}
		}
	}
		
	public function configureDataFlowActivitySelectorByClassName($className, $configurator) {
		$config = $configurator->getConfigIterator();
		foreach($this->objArray as $dfas) {
			if($dfas->getClassName() == $className) {
				foreach($config as $key => $value) {
					$dfas->setDataFlowActivityParameter($key, $value);
				}
			}
		}
	}
	
	/**
	 * Marks this DataFlowActivitySelectorList as originating from Public space. Cannot be undone.
	 * Once origin is marked as public, then DataFlowService or any DataFlowActivity implementation are free to stop execution with an DataFlowServiceException::FORBIDDEN (403)
	 */
	public function setOriginIsPublic() {
		if(!$this->originPublic) {
			$this->originPublic = true;
			// propagates public origin to all DataFlowActivitySelectors
			if(!empty($this->objArray)) {
				foreach($this->objArray as $dfas) {
					$dfas->setOriginIsPublic();
				}
			}
		}		
	}
	/**
	 * @return Boolean returns true if this DataFlowActivitySelectorList has been marked as originating from Public space, else false.
	 */
	public function isOriginPublic() {
		return $this->originPublic;
	}
}