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
 * Creates a new Element or sub element and pushes it into a data flow
 * Created by CWE on 28 janvier 2014
 */
class ElementDataFlowConnector implements DataFlowDumpable
{
	private $_debugLogger;
	private $lockedForUse = true;	
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;				
	}	
	public function freeMemory() {
		unset($this->groupId);
		unset($this->linkSelector);		
		$this->lockedForUse = false;	
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstanceForNewElement($groupId, $linkSelector=null) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setGroupId($groupId);
		if(isset($linkSelector)) $returnValue->setLinkSelector($linkSelector);
		return $returnValue;
	}
	public static function createInstanceForNewSubElement($linkSelector) {
		$returnValue = new self();
		$returnValue->reset();
		$returnValue->setLinkSelector($linkSelector);
		return $returnValue;
	}	
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementDataFlowConnector");
		}
		return $this->_debugLogger;
	}	
	
	private $configS;
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
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
	
	private $groupId;
	/**
	 * Sets the group id in which to insert the element
	 */
	public function setGroupId($groupId) {
		$this->groupId = $groupId;
	}
	
	private $linkSelector;
	/**
	 * Sets the link selector which defines the link to be created (subitem or link)
	 * @param LinkSelector $linkSelector
	 */
	public function setLinkSelector($linkSelector) {
		$this->linkSelector = $linkSelector;
	}	
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {		
		if(!isset($this->groupId) && !isset($this->linkSelector)) throw new DataFlowServiceException("groupId and/or linkSelector must be set. None have been.", DataFlowServiceException::CONFIGURATION_ERROR);
		$principal = $dataFlowContext->getPrincipal();
		
		$isSubitem = isset($this->linkSelector) && !isset($this->groupId);
		
		if($isSubitem) {
			// sets configuration of root element if defined
			$configSel = $this->linkSelector->getRootConfigSelector();
			$apiClient = null;
			if(isset($configSel)) {
				// a wigiiNamespace has been specified --> adapts the Principal if needed
				$confWigiiNamespace = $configSel->getWigiiNamespaceName();
				if(isset($confWigiiNamespace)) $confWigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $confWigiiNamespace);
				if(isset($confWigiiNamespace) && $principal->hasAdaptiveWigiiNamespace()) {
					$principal->bindToWigiiNamespace($confWigiiNamespace);
				}
				// a groupLogExp has been specified --> creates a GroupBasedWigiiApiClient centered on theses groups
				$groupExp = $configSel->getGroupLogExp();
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
			// if API client is still null, then instantiates one to support sub elements.
			if(!isset($apiClient)) $apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, null);
			
			// centers API client configuration on subitem
			$apiClient->getConfigService()->selectSubElementsConfig($principal, $this->linkSelector->getOwnerElementId(), $this->linkSelector->getFieldName());
			
			// creates sub element
			$element = $this->createNewSubElement($principal, $apiClient->getConfigService(), $dataFlowContext);
			// sets dataflow context
			$dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $apiClient, true);
			$dataFlowContext->setAttribute('linkSelector', $this->linkSelector);			
		}
		else {
			// creates group based API client
			$apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, lxEq(fs('id'), $this->groupId));
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
			if($oneWigiiNamespace && $principal->hasAdaptiveWigiiNamespace()) {
				$principal->bindToWigiiNamespace($firstWigiiNamespace);
			}	
			// creates element
			$element = $this->createNewElement($principal, $groupList, $dataFlowContext);	
			// sets dataflow context
			$dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $apiClient, true);
			$dataFlowContext->setAttribute('groupId', $this->groupId);
			if(isset($this->linkSelector)) $dataFlowContext->setAttribute('linkSelector', $this->linkSelector);
		}
		
		// pushes the element into the dataflow
		$dataFlowService->processDataChunk($element, $dataFlowContext);	
	}	
	
	// Implementation
		
	/**
	 * Creates a new empty element instance to be inserted based on 
	 * the configuration given by the provided groupList.
	 * @return Element
	 */
	protected function createNewElement($principal, $groupList, $dataFlowContext) {
		if(!isset($groupList) || $groupList->isEmpty()) throw new DataFlowServiceException("groupList cannot be empty", DataFlowServiceException::INVALID_ARGUMENT);
		// retrieves Module using first group in the list
		$module = null;
		foreach($groupList->getListIterator() as $group) {
			$module = $group->getModule();
			break;
		}
		// if Wigii events are active, then creates a FormBag and a FormFieldList
		if($dataFlowContext->areWigiiEventsEnabled()) {
			$wigiiBag= FormBag::createInstance();
			$fieldList = FormFieldList::createInstance($wigiiBag);
			$wigiiBag->setFormFieldList($fieldList);
		} else {
			$fieldList = FieldListArrayImpl::createInstance();
			$wigiiBag = WigiiBagBaseImpl::createInstance();
		}
		// creates element FieldList and fills it with configuration given by grouplist
		$this->getConfigService()->getGroupsFields($principal, $groupList, null, $fieldList);
		return Element::createInstance($module, $fieldList, $wigiiBag);				
	}	
	
	/**
	 * Creates a new empty sub element instance to be inserted based on 
	 * the configuration given by the provided Sub element config Service.
	 * @param ConfigServiceSubElementImpl $subElementConfigService
	 * @return Element
	 */
	protected function createNewSubElement($principal, $subElementConfigService, $dataFlowContext) {
		$module = $subElementConfigService->getCurrentModule();
		
		// if Wigii events are active, then creates a FormBag and a FormFieldList
		if($dataFlowContext->areWigiiEventsEnabled()) {
			$wigiiBag= FormBag::createInstance();
			$fieldList = FormFieldList::createInstance($wigiiBag);
			$wigiiBag->setFormFieldList($fieldList);
		} else {
			$fieldList = FieldListArrayImpl::createInstance();
			$wigiiBag = WigiiBagBaseImpl::createInstance();
		}
		// creates element FieldList and fills it with the configuration of the sub element
		$subElementConfigService->getFields($principal, $module, null, $fieldList);
		return Element::createInstance($module, $fieldList, $wigiiBag);				
	}	
}