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
 * Fetches an existing element and pushes it into a data flow
 * Created by CWE on 28 janvier 2014
 */
class ElementPDataFlowConnector implements DataFlowDumpable
{
	private $_debugLogger;
	private $lockedForUse = true;	
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;				
	}	
	public function freeMemory() {
		unset($this->eltId);
		unset($this->configSel);		
		$this->lockedForUse = false;	
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstance($elementId, $fieldSelectorList=null, $configSelector=null) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setElementId($elementId);
		if(isset($fieldSelectorList)) $this->setFieldSelectorList($fieldSelectorList);
		if(isset($configSelector)) $returnValue->setConfigSelector($configSel);
		return $returnValue;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementPDataFlowConnector");
		}
		return $this->_debugLogger;
	}
	
	private $eltS;
	public function setElementService($elementService)
	{
		$this->eltS = $elementService;
	}
	protected function getElementService()
	{
		// autowired
		if(!isset($this->eltS))
		{
			$this->eltS = ServiceProvider::getElementService();
		}
		return $this->eltS;
	}
	
	private $wigiiNamespaceAS;
	public function setWigiiNamespaceAdminService($wigiiNamespaceAdminService)
	{
		$this->wigiiNamespaceAS = $wigiiNamespaceAdminService;
	}
	protected function getWigiiNamespaceAdminService()
	{
		// autowired
		if(!isset($this->wigiiNamespaceAS))
		{
			$this->wigiiNamespaceAS = ServiceProvider::getWigiiNamespaceAdminService();
		}
		return $this->wigiiNamespaceAS;
	}
	
	// Configuration
	
	private $eltId;
	/**
	 * Sets the element ID we want to fetch
	 */
	public function setElementId($id) {
		$this->eltId = $id;
	}
	
	private $fieldSelectorList;
	/**
	 * Sets an optional field selector list that should be used to filter the fields that are fetched
	 * @param FieldSelectorList $fieldSelectorList
	 */
	public function setFieldSelectorList($fieldSelectorList) {
		$this->fieldSelectorList = $fieldSelectorList;
	}
	
	private $configSel;
	/**
	 * Sets the ConfigurationSelector object used select the desired configuration
	 */
	public function setConfigSelector($configSel) {
		$this->configSel = $configSel;
	}
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {		
		if(!isset($this->eltId)) throw new DataFlowServiceException("element id has not been set", DataFlowServiceException::CONFIGURATION_ERROR);
		$principal = $dataFlowContext->getPrincipal();
		$apiClient = null;
		$eltS = null;
		// sets configuration if defined
		if(isset($this->configSel)) {
			// a wigiiNamespace has been specified --> adapts the Principal if needed
			$confWigiiNamespace = $this->configSel->getWigiiNamespaceName();
			if(isset($confWigiiNamespace)) $confWigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $confWigiiNamespace);
			if(isset($confWigiiNamespace) && $principal->hasAdaptiveWigiiNamespace()) {
				$principal->bindToWigiiNamespace($confWigiiNamespace);
			}
			// a groupLogExp has been specified --> creates a GroupBasedWigiiApiClient centered on theses groups
			$groupExp = $this->configSel->getGroupLogExp();
			if(isset($groupExp)) {
				$apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, $groupExp);
				$groupList = $apiClient->getGroupList();
				// gets wigiiNamespace
				$initialized = false; $firstWigiiNamespace = null;
				$oneWigiiNamespace = true;
				foreach($groupList->getListIterator() as $group)
				{					
					$wigiiNamespace = $group->getWigiiNamespace();					
					if($initialized)
					{
						// checks wigiiNamespace unicity						
						if($wigiiNamespace !== $firstWigiiNamespace) $oneWigiiNamespace = false;
					}
					else
					{
						$firstWigiiNamespace = $wigiiNamespace;
						$initialized = true;
					}
				}
				// adapts wigii namespace if needed
				if(is_null($confWigiiNamespace) && $oneWigiiNamespace && $principal->hasAdaptiveWigiiNamespace()) {
					$principal->bindToWigiiNamespace($firstWigiiNamespace);
				}		
			}
		}
		// if API client is still null, then instantiates one to support sub element if needed.
		if(!isset($apiClient)) $apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, null);
		// initializes element service
		$eltS = $apiClient->getElementService();		
		
		// fetches the elementP
		$element = $this->createElementInstance();
		$element->setId($this->eltId);		
		$element = $eltS->fillElement($principal, $element, $this->fieldSelectorList);
		// sets dataflow context
		$dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $apiClient, true);
		if(isset($this->fieldSelectorList))$dataFlowContext->setAttribute('FieldSelectorList', $this->fieldSelectorList);
		// pushes the elementP into the dataflow
		if(isset($element)) $dataFlowService->processDataChunk($element, $dataFlowContext);
	}	
	
	// Implementation
	
	protected function createElementInstance() {
		return Element::createInstance(null, FieldListArrayImpl::createInstance(), WigiiBagBaseImpl::createInstance());
	}
}