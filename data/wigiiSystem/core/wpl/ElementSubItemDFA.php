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
 * A data flow activity which extracts the sub items of an Element and dumps them into the flow
 * Created by CWE on 28.06.2016
 */
class ElementSubItemDFA implements DataFlowActivity
{			
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
	}	
	public function freeMemory() {
		unset($this->fieldName);
		unset($this->lf);
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("ElementSubItemDFA");
		}
		return $this->_debugLogger;
	}	
	
	// configuration
	
	private $fieldName;
	/**
	 * Sets the field name from which to extract the sub elements
	 * @param String $fieldName
	 */
	public function setFieldName($fieldName) {
		$this->fieldName = $fieldName;
	}

	private $lf;
	/**
	 * Sets the ListFilter to be used to filter sub items
	 * @param ListFilter $lf
	 */
	public function setListFilter($lf) {
		$this->lf = $lf;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		// pushes ListFilter and FieldSelectorList into DataFlowContext
		$dataFlowContext->setAttribute('ListFilter', $this->lf);
		if($this->lf) $fsl = $this->lf->getFieldSelectorList();
		else $fsl = null;
		$dataFlowContext->setAttribute('FieldSelectorList', $fsl);
	}
	public function processDataChunk($data, $dataFlowContext) {
		// extracts root element
		$element = $data->getDbEntity();
		// extracts sub-items
		$subItemSource = elementPList(ls($element->getId(),$this->fieldName), $this->lf);
		$subItemSource->setGroupBasedWigiiApiClient($dataFlowContext->getAttribute('GroupBasedWigiiApiClient'));		
		$dataFlowContext->getDataFlowService()->processDumpableObject($dataFlowContext->getPrincipal(), $subItemSource, dfasl(
			/* sets parent element of each subitem */
			dfas("ElementSetterDFA", "setCalculatedFieldSelectorMap", cfsMap(cfs(fs_e("element_parent"),$element))),
			/* pushes sub elements into dataflow */
			dfas("BridgeDFA","setDataFlowContext",$dataFlowContext)
		));
	}
	public function endOfStream($dataFlowContext) {/* nothing to do */}	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}