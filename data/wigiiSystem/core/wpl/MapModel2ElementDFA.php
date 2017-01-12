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
 * A data flow activity which maps Wigii Model instances to elements
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by CWE on 10 octobre 2014
 * Modified by Medair (CWE) on 15.12.2016 to protect against Cross Site Scripting
 */
class MapModel2ElementDFA implements DataFlowActivity
{			
	private $dataFlowContext;
	private $_debugLogger;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();	
	}	
	public function freeMemory() {
		unset($this->groupId);
		unset($this->linkSelector);
		unset($this->model2ElementMappingMethod);
		unset($this->dataFlowContext);
	}
	
	// dependency injection
		
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("MapModel2ElementDFA");
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
	
	// configuration
	
	private $groupId;
	/**
	 * Sets the group ID that should be used to get the configuration
	 * when creating new elements in insertOnlyMode.
	 * Or the groupID that should be used to select elements in update mode.
	 * If the inGroupLogExp is set, then it is combined with an OR.
	 */
	public function setGroupId($groupId) {
		$this->groupId = $groupId;
	}
	
	private $linkSelector;
	/**
	 * Sets the link selector specifying where to attach the element.
	 * @param LinkSelector $ls
	 */
	public function setLinkSelector($ls) {
		$this->linkSelector = $ls;
	}
	
	private $model2ElementMappingMethod;
	/**
	 * Sets the method that should be executed on each pair of model/element in the flow in order
	 * to map the current model to the element.
	 * The method signature takes one argument which is the current model in the data flow,
	 * a second argument which is the new element, and
	 * a third argument which is a reference to this object that can be used to get the 
	 * DataFlowContext and write output using the writeResultToOutput helper method.
	 * @param Closure|String $method a closure representing the code that should be executed or
	 * a string which is an object method name or a function name
	 * @param Any $object an optional object instance which holds the method instance that should be executed.
	 */
	public function setModel2ElementMappingMethod($method, $obj=null) {
		$this->model2ElementMappingMethod = CallableObject::createInstance($method, $obj);
	}
	
	// helpers
	
	/**
	 * Returns a reference on the current DataFlowContext object
	 */
	public function getDataFlowContext() {
		return $this->dataFlowContext;
	}
	
	/**
	 * Writes some data to the output data flow
	 * See DataFlowContext::writeResultToOuput
	 */
	public function writeResultToOutput($resultData) {
		$this->dataFlowContext->writeResultToOutput($resultData, $this);
	}
		
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
		if(!isset($this->groupId) && !isset($this->linkSelector)) throw new DataFlowServiceException("No group id or link selector have been set to define where to insert elements, add one using the 'setGroupId' or 'setLinkSelector' method.", DataFlowServiceException::CONFIGURATION_ERROR);
		$this->dataFlowContext = $dataFlowContext;
		
		// checks nature of elements to fetch
		$isSubitem = isset($this->linkSelector) && !isset($this->groupId);
		
		// if subitem then instantiates a GroupBasedWigiiApiClient centered on defined subitems
		// else instantiates a GroupBasedWigiiApiClient centered on the provided group						
		$principal = $dataFlowContext->getPrincipal();
		$apiClient = null;
		
		if($isSubitem) {
			// sets configuration of root element if defined
			$configSel = $this->linkSelector->getRootConfigSelector();			
			if(isset($configSel)) {
				// a wigiiNamespace has been specified --> adapts the Principal if needed
				$confWigiiNamespace = $configSel->getWigiiNamespaceName();
				if(isset($confWigiiNamespace)) $confWigiiNamespace = $this->getWigiiNamespaceAdminService()->getWigiiNamespace($principal, $confWigiiNamespace);
				if(isset($confWigiiNamespace) && $principal->hasAdaptiveWigiiNamespace()) {
					$principal->bindToWigiiNamespace($confWigiiNamespace);
				}						
				// a groupLogExp has been specified --> creates a GroupBasedWigiiApiClient centered on theses groups
				$groupExp = $configSel->getGroupLogExp();
			}
		}
		else {
			if(!isset($this->groupId)) throw new DataFlowServiceException("no groupId has been defined to select configuration. Add one using the 'setGroupId' method.", DataFlowServiceException::CONFIGURATION_ERROR);
			$groupExp = lxEq(fs('id'), $this->groupId);
			$confWigiiNamespace = null;
		}
		
		if(isset($groupExp)) {
			$apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, $groupExp);
			// gets wigiiNamespace
			$initialized = false; $firstWigiiNamespace = null;
			$oneWigiiNamespace = true;
			foreach($apiClient->getGroupList()->getListIterator() as $group)
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
		// if subitem and API client is still null, then instantiates one to support sub elements.
		if($isSubitem) {					
			if(!isset($apiClient)) $apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, null);
			// centers API client configuration on subitem
			if($this->insertOnlyMode) $apiClient->getConfigService()->selectSubElementsConfig($principal, $this->linkSelector->getOwnerElementId(), $this->linkSelector->getFieldName());
		}
		
		// stores api client in context
		$dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $apiClient, true);
		if(isset($this->linkSelector)) $dataFlowContext->setAttribute('linkSelector', $this->linkSelector);
		if(isset($this->groupId)) $dataFlowContext->setAttribute('groupId', $this->groupId);
	}
	public function processDataChunk($data, $dataFlowContext) {
		if(!isset($data)) return;
		$principal = $dataFlowContext->getPrincipal();
		$apiClient = $dataFlowContext->getAttribute('GroupBasedWigiiApiClient');
		$isSubitem = isset($this->linkSelector) && !isset($this->groupId);
		
		// creates a new empty instance
		if($isSubitem) $element = $this->createNewSubElement($principal, $apiClient->getConfigService());
		else $element = $this->createNewElement($principal, $apiClient->getGroupList());				
		
		// maps the new element to the object
		// using the closure if defined
		if(isset($this->model2ElementMappingMethod)) $this->model2ElementMappingMethod->invoke($data, $element, $this);   
		// else calls the protected method
		else $this->mapModel2Element($data, $element, $dataFlowContext) ;
	}
	public function endOfStream($dataFlowContext) {
		/* nothing to do */
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		return $this->endOfStream($dataFlowContext);
	}	
	
	// implementation

	/**
	 * Maps the current model in the data flow to the element.
	 * This method should be implemented in subclasses or
	 * some code injected using the setModel2ElementMappingMethod.
	 * @param Model $model the current model in the dataflow
	 * @param Element $element the new element 
	 * @param DataFlowContext $dataFlowContext the current data flow context, 
	 * that can be used to write some output in the data flow for next steps
	 * @throws DataFlowServiceException in case of error
	 */
	protected function mapModel2Element($model, $element, $dataFlowContext) {
		throw new DataFlowServiceException('the method mapModel2Element should be implemented in a subclass or some code injected using the setModel2ElementMappingMethod.', DataFlowServiceException::UNSUPPORTED_OPERATION);
	}
	
	/**
	 * Creates a new empty element instance to be inserted based on 
	 * the configuration given by the provided groupList.
	 * @return Element
	 */
	protected function createNewElement($principal, $groupList) {
		if(!isset($groupList) || $groupList->isEmpty()) throw new DataFlowServiceException("groupList cannot be empty", DataFlowServiceException::INVALID_ARGUMENT);
		// retrieves Module using first group in the list
		$module = null;
		foreach($groupList->getListIterator() as $group) {
			$module = $group->getModule();
			break;
		}
		// creates element FieldList and fills it with configuration given by grouplist
		$fieldList = FieldListArrayImpl::createInstance(true,true);
		$this->getConfigService()->getGroupsFields($principal, $groupList, null, $fieldList);
		// creates element instance
		return Element::createInstance($module, $fieldList, WigiiBagBaseImpl::createInstance());				
	}
	
	/**
	 * Creates a new empty sub element instance to be inserted based on 
	 * the configuration given by the provided Sub element config Service.
	 * @param ConfigServiceSubElementImpl $subElementConfigService
	 * @return Element
	 */
	protected function createNewSubElement($principal, $subElementConfigService) {
		$module = $subElementConfigService->getCurrentModule();
		// creates element FieldList and fills it with the configuration of the sub element
		$fieldList = FieldListArrayImpl::createInstance(true,true);
		$subElementConfigService->getFields($principal, $module, null, $fieldList);
		// creates element instance
		return Element::createInstance($module, $fieldList, WigiiBagBaseImpl::createInstance());				
	}	
}