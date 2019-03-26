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
 * Dumps an array into data flow
 * Created by CWE on 28 février 2014
 * Modified by CWE on 25.03.2019 to allow pairing of key/values as StdClasses
 */
class ArrayDataFlowConnector implements DataFlowDumpable
{
	private $_debugLogger;
	private $lockedForUse = true;	
	private $arr;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;
		$this->pair = false;	
	}	
	public function freeMemory() {
		unset($this->arr);		
		$this->lockedForUse = false;
		unset($this->keyField);
		unset($this->valueField);
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstance($arr) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setArray($arr);
		return $returnValue;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ArrayDataFlowConnector");
		}
		return $this->_debugLogger;
	}
	
	// Configuration
	
	public function setArray($arr) {
		$this->arr = $arr;
	}
	
	// Configuration
	
	private $pair;
	/**
	 * If true, indicates that the flow is a flow of pairs (key,value) represented as StdClass instances
	 * The array keys and values are boxed and stored into a StdClass instance.
	 */
	public function setPair($bool) {
	    $this->pair = $bool;
	    return $this;
	}
	
	private $keyField;
	/**
	 * Defines the name of the field to be used as a key
	 * If pair and not set, then defaults to 'key'
	 * @param String $name field name
	 */
	public function setKeyField($name) {
	    $this->keyField = $name;
	    return $this;
	}
	private $valueField;
	/**
	 * If pairing, then defines the name of the field to be used as a value, defaults to 'value'
	 * @param String $name field name
	 */
	public function setValueField($name) {
	    $this->valueField = $name;
	    return $this;
	}
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {		
		if(!empty($this->arr)) {
			if(is_array($this->arr)) {
			    if($this->pair) {			        
			        if(!isset($this->keyField)) $this->keyField = 'key';
			        if(!isset($this->valueField)) $this->valueField = 'value';
			        foreach($this->arr as $k=>$v) {
			            $dataFlowService->processDataChunk((object)array($this->keyField=>$k,$this->valueField=>$v), $dataFlowContext);
			        }
			    }
			    else {
    				foreach($this->arr as $v) {
    					if($v instanceof DataFlowSelector) $this->dumpSubDataFlow($dataFlowService, $v, $dataFlowContext);
    					else $dataFlowService->processDataChunk($v, $dataFlowContext);
    				}
			    }
			}
			elseif($this->arr instanceof DataFlowSelector) $this->dumpSubDataFlow($dataFlowService, $this->arr, $dataFlowContext);
			else $dataFlowService->processDataChunk($this->arr, $dataFlowContext);
		}
	}
	
	/**
	 * @param DataFlowService $dataFlowService
	 * @param DataFlowSelector $subDataFlowSelector
	 * @param DataFlowContext $dataFlowContext
	 */
	protected function dumpSubDataFlow($dataFlowService, $subDataFlowSelector, $dataFlowContext) {
		$subDataFlowSelector->getDataFlowActivitySelectorList()
			->addDataFlowActivitySelectorInstance(dfas("BridgeDFA",
				"setDataFlowContext", $dataFlowContext,
				"setBridgeToBeginning", true));
		$dataFlowService->processDataFlowSelector($dataFlowContext->getPrincipal(), $subDataFlowSelector);
	}
}