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
 * A data flow activity which extracts the values of a set of Fields
 * Created by CWE on 24.05.2016
 */
class ElementFieldSetDFA implements DataFlowActivity, ValueList
{			
	private $dataFlowContext;
	private $parameter;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
	}	
	public function freeMemory() {
		unset($this->dataFlowContext);
		unset($this->fieldNamePrefix);
		unset($this->hierarchySep);
		if(isset($this->parameter)) $this->parameter->freeMemory();
		unset($this->parameter);
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("ElementFieldSetDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $fieldNamePrefix;
	/**
	 * Sets one or an array of Field name prefix used to filter the Fields to extract
	 * @param String|Array $arr the field name prefix
	 */
	public function setFieldNamePrefix($arr) {
		$this->fieldNamePrefix = $arr;
	}
				
	private $hierarchySep;
	/**
	 * Sets a hierarchy separator used to split the field name in several levels
	 * @param String $sep
	 */
	public function setHierarchySep($sep) {
		$this->hierarchySep = $sep;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {/* nothing to do */}
	public function processDataChunk($data, $dataFlowContext) {
		if(!isset($this->parameter)) {
			$this->parameter = TechnicalServiceProvider::createWigiiBPLParameterInstance();
			$this->parameter->setValue('fieldNamePrefix', $this->fieldNamePrefix);
			$this->parameter->setValue('hierarchySep', $this->hierarchySep);
			$this->parameter->setValue('valueList', $this);
			$this->dataFlowContext = $dataFlowContext;
		}
		$this->parameter->setValue('element', $data->getDbEntity());
		ServiceProvider::getWigiiBPL()->elementExtractFieldSet($dataFlowContext->getPrincipal(), $this, $this->parameter);
	}
	public function endOfStream($dataFlowContext) {/* nothing to do */}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}		
	
	// ValueList implementation
	
	public function addValue($val) {
		// pushes value down in stream
		$this->dataFlowContext->writeResultToOutput($val, $this);
	}
	public function getListIterator() {throw new DataFlowServiceException('Unsupported operation',DataFlowServiceException::UNSUPPORTED_OPERATION);}
	public function isEmpty() {throw new DataFlowServiceException('Unsupported operation',DataFlowServiceException::UNSUPPORTED_OPERATION);}
	public function count() {throw new DataFlowServiceException('Unsupported operation',DataFlowServiceException::UNSUPPORTED_OPERATION);}
}