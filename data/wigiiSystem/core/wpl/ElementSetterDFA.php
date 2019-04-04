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
 * A data flow activity which sets values in a flow of elements.
 * Supports a flow of Element or ElementP instances.
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by CWE on 27 janvier 2014
 * Modified by Medair (CWE) on 15.12.2016 to protect against Cross Site Scripting
 */
class ElementSetterDFA extends ElementDFAWithFuncExpVM
{		
	private $apiClient;
	private $apiClientSupportsSubItems;
	private $isSubitem;
	private $linksCFS;
	private $nLinksCFS;
	private $firstElementInFlow;
	private $areWigiiEventsEnabled;
	
	// Object lifecycle
		
	public function reset() {
		parent::reset();
		$this->linksCFS = array();
		$this->nLinksCFS = 0;
		$this->firstElementInFlow = true;
	}	
	public function freeMemory() {
		unset($this->cfsMap);	
		unset($this->insertOnlyMode);
		unset($this->groupId);
		unset($this->linkSelector);
		unset($this->areWigiiEventsEnabled);
		unset($this->apiClient);
		unset($this->apiClientSupportsSubitems);
		unset($this->isSubitem);
		unset($this->linksCFS);
		parent::freeMemory();
	}

	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("ElementSetterDFA");
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
	
	private $cfsMap;
	/**
	 * Sets the CalculatedFieldSelectorMap to be used to update the element fields.
	 * @param CalculatedFieldSelectorMap $cfsMap
	 */
	public function setCalculatedFieldSelectorMap($cfsMap) {		
		$this->cfsMap = $cfsMap;
	}	
	
	private $insertOnlyMode;
	/**
	 * If true, then elements are always created, 
	 * based on a copy of the input element using the CalculatedFieldSelectorMap. 
	 * By default, insertOnlyMode is desactivated.
	 * If insertOnlyMode is activated, then a groupId should be configured 
	 * or/and a linkSelector should be set to create subItems or linked elements. 
	 */
	public function setInsertOnlyMode($bool) {
		$this->insertOnlyMode = $bool;
	}
	
	private $groupId;
	/**
	 * Sets the group ID that should be used to get the configuration
	 * when creating new elements in insertOnlyMode.
	 */
	public function setGroupId($groupId) {
		$this->groupId = $groupId;
	}
	
	private $linkSelector;
	/**
	 * Sets the link selector used to specify where to attach the element in insert mode.
	 * @param LinkSelector $ls
	 */
	public function setLinkSelector($ls) {
		$this->linkSelector = $ls;
	}	
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
		parent::startOfStream($dataFlowContext);
		$this->areWigiiEventsEnabled = $dataFlowContext->areWigiiEventsEnabled();
		
		// if insert only mode -> prepares configuration to create a new element
		if($this->insertOnlyMode) {
			if(!isset($this->groupId) && !isset($this->linkSelector)) throw new DataFlowServiceException("groupId and/or linkSelector must be set. None have been.", DataFlowServiceException::CONFIGURATION_ERROR);
			$principal = $dataFlowContext->getPrincipal();
			
			$this->isSubitem = isset($this->linkSelector) && !isset($this->groupId);
			
			if($this->isSubitem) {
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
					if(isset($groupExp)) {
						$this->apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, $groupExp);
						$groupList = $this->apiClient->getGroupList();
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
				if(!isset($this->apiClient)) $this->apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, null);
				
				// centers API client configuration on subitem
				$this->apiClient->getConfigService()->selectSubElementsConfig($principal, $this->linkSelector->getOwnerElementId(), $this->linkSelector->getFieldName());

				// sets dataflow context
				$dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $this->apiClient, true);
				$dataFlowContext->setAttribute('linkSelector', $this->linkSelector);			
			}
			else {
				// creates group based API client
				$this->apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, lxEq(fs('id'), $this->groupId));
				$groupList = $this->apiClient->getGroupList();			
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

				// sets dataflow context
				$dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $this->apiClient, true);
				$dataFlowContext->setAttribute('groupId', $this->groupId);
				if(isset($this->linkSelector)) $dataFlowContext->setAttribute('linkSelector', $this->linkSelector);
			}			
		}
		else $this->apiClient = $dataFlowContext->getAttribute('GroupBasedWigiiApiClient');		
		if(isset($this->apiClient)) {			
			$this->apiClientSupportsSubitems = method_exists($this->apiClient->getConfigService(), 'getCurrentSubElementPathFromRoot');
		}
	}
	
	// element event handling
				
	protected function processElement($elementP, $dataFlowContext) {		
		// if insert only mode -> creates a new element and updates it based on the incoming element
		if($this->insertOnlyMode) {			
			$principal = $dataFlowContext->getPrincipal();			
			if($this->isSubitem) {
				// creates sub element
				$element = $this->createNewSubElement($principal, $this->apiClient->getConfigService());			
			}
			else {				
				// creates element
				$element = $this->createNewElement($principal, $this->apiClient->getGroupList());	
			}			
		}
		else $element = $elementP->getDbEntity();
		
		// gets FieldSelectorList from DataFlowContext or creates a new one
		$fieldSelectorList = $dataFlowContext->getAttribute('FieldSelectorList');
		if(!isset($fieldSelectorList)) {
			$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
			$dataFlowContext->setAttribute('FieldSelectorList', $fieldSelectorList);
		}
		
		// updates the element	
		$elementIsNew = $element->isNew();	
		$this->updateElement($element, $fieldSelectorList, $dataFlowContext);
		
		// pushes the element further
		if(!$dataFlowContext->isCurrentStepTheLastStep()) {
			// if element has been inserted, then reads again from the database
			if($elementIsNew != $element->isNew()) {
				// resets the WigiiBag
				$wigiiBag = $element->getWigiiBag();
				if(method_exists($wigiiBag, 'reset')) $wigiiBag->reset();
				else {
					$wigiiBag = $this->createWigiiBagInstance();
					$element->setWigiiBag($wigiiBag);
				}				
				// gets ElementService: injected one, or from data flow context, or default.
				if(isset($this->eltS)) $eltS = $this->eltS;
				else {
					$apiClient = $dataFlowContext->getAttribute('GroupBasedWigiiApiClient');
					if(isset($apiClient)) $eltS = $apiClient->getElementService();
					else $eltS = $this->getElementService();
				}
				// fills the element
				$elementP = $eltS->fillElement($principal, $element, $fieldSelectorList);
				$dataFlowContext->writeResultToOutput($elementP, $this);
			}
			else $dataFlowContext->writeResultToOutput(($this->insertOnlyMode ? $element : $elementP), $this);
		}
		
		$this->firstElementInFlow = false;
	}

	/**
	 * Updates the element using the func exp map and the incoming element
	 * @param Element $element the element to update
	 * @param FieldSelectorList $fieldSelectorList fieldSelectorList to be filled with the updated fields
	 * @param DataFlowContext $dataFlowContext the DataFlowContext that can be used to get the Principal and DataFlowService
	 * according to CalculatedFieldSelectorMap
	 */
	protected function updateElement($element, $fieldSelectorList, $dataFlowContext) {
		// evaluates each func exp found in the map
		if(isset($this->cfsMap)) {										
			foreach($this->cfsMap->getListIterator() as $cfs) {
				$fs = $cfs->getFieldSelector();
				$fx = $cfs->getFuncExp();
				if(isset($fs)) {
					if($fs->isElementAttributeSelector()) {
						$val = (isset($fx)?$this->evaluateFuncExp($fx):null);
						try { $element->setAttribute($val,$fs); }
						catch(Exception $e) {
							// if no dynamic attribute is defined, then creates one
							if(is_null($element->getDynamicAttribute($fs->getSubFieldName()))) {
								$element->setDynamicAttribute($fs->getSubFieldName(), ElementDynAttrMutableValueImpl::createInstance($val));
							}
							else throw $e;
						}
						
						// sets FieldSelector in FieldSelectorList
						if($this->firstElementInFlow && !$fieldSelectorList->containsFieldSelector($fs->getFieldName(), $fs->getSubFieldName())) {
							$fieldSelectorList->addFieldSelectorInstance($fs);
						}
					}
					else {
						$fieldName = $fs->getFieldName();
						$subFieldName = $fs->getSubFieldName();
						$field = $element->getFieldList()->getField($fieldName);
						if(!isset($field)) throw new DataFlowServiceException("field '$fieldName' is not defined in element config.", DataFlowServiceException::CONFIGURATION_ERROR);
						$dt = $field->getDataType();
						
						// isolates fields of type Links for later evaluation
						if($dt instanceof Links) {
							if($this->firstElementInFlow) {						
								$this->linksCFS["($fieldName($subFieldName))"] = $cfs; 
								$this->nLinksCFS++;
							}
						}
						else {
							// evaluates func exp
						    $val = (isset($fx)?$this->evaluateFuncExp($fx):null);
						    // manages MultipleAttributs array
						    if($dt instanceof MultipleAttributs) {
						    	if(is_null($val) || is_array($val)) {
						    		$element->setFieldValue($val, $fieldName);
						    	}
						    	else {
						    		$element->addValueToField($val, $fieldName);
						    	}
						    }
							// sets value in element					
							elseif(is_array($val) && is_null($subFieldName)) {
								// if array, then checks for the presence of subfields
								// if field has subfields, then assumes that the keys of the array are the subfields
								// else stores the array directly as the field value.						
								if(isset($dt) && $dt->hasSubfields()) {
									foreach($val as $k => $v) {
										$element->setFieldValue($v, $fieldName, $k);
									}
								}
								else $element->setFieldValue($val, $fieldName);
							}
							else $element->setFieldValue($val, $fieldName, $subFieldName);
							
							// sets FieldSelector in FieldSelectorList
							if($this->firstElementInFlow && !$fieldSelectorList->containsFieldSelector($fieldName, $subFieldName)) {
								$fieldSelectorList->addFieldSelectorInstance($fs);
							}
						}
					}
				}
			}

			// evaluates fields of type Links
			if($this->nLinksCFS > 0) {
				// inserts element if needed
				if($element->isNew()) {
					$linkSelector = $this->linkSelector;
					if(is_null($linkSelector)) $linkSelector = $dataFlowContext->getAttribute('linkSelector'); 
					$groupId = $this->groupId;
					if(is_null($groupId)) $groupId = $dataFlowContext->getAttribute('groupId');
															
					// gets ElementService: injected one, or from data flow context, or default.
					if(isset($this->eltS)) $eltS = $this->eltS;
					else {						
						if(isset($this->apiClient)) $eltS = $this->apiClient->getElementService();
						else $eltS = $this->getElementService();
					}					
					
					// creates wigii event if should throw events 		
					if($this->areWigiiEventsEnabled) {									
						if(isset($this->apiClient)) $groupList = $this->apiClient->getGroupList();			
						else $groupList = null;
						$wigiiEvent = PWithElementWithGroupPList::createInstance($dataFlowContext->getPrincipal(), $element, $groupList);
						$wigiiEvent->setLinkSelector($linkSelector);
					}
		
					// if subitem
					if($this->isSubitem) {
						$eltS->insertSubElement($dataFlowContext->getPrincipal(), $linkSelector->getOwnerElementId(), $linkSelector->getFieldName(), $element, $fieldSelectorList);
					}
					// else if root element
					elseif(isset($groupId)) {
						$eltS->insertElement($dataFlowContext->getPrincipal(), $element, $groupId, $fieldSelectorList);
					}
					else throw new DataFlowServiceException("needs to insert an element, but no groupId or linkSelector were defined. Please inject one.", DataFlowServiceException::CONFIGURATION_ERROR);
					
					// throws event
					if($this->areWigiiEventsEnabled) {
						// adds subitem path from root if defined
						if($this->apiClientSupportsSubitems) {
							$wigiiEvent->setSubElementPathFromRoot($this->apiClient->getConfigService()->getCurrentSubElementPathFromRoot());
						}
						$dataFlowContext->getWigiiEventsDispatcher()->insertElement($wigiiEvent);
					}
				}
				
				// evaluates data flows
				foreach($this->linksCFS as $cfs) {					
					$fx = $cfs->getFuncExp();
					if(isset($fx)) {						
						// evaluates func exp and gets DataFlowSelector
						$dfs = $this->evaluateFuncExp($fx);
						if(isset($dfs)) {
							if(!$dfs instanceof DataFlowSelector) throw new DataFlowServiceException("FuncExp attached to field '".$fs->toString()."' does not evaluate to a DataFlowSelector instance.", DataFlowServiceException::CONFIGURATION_ERROR);
							// executes DataFlowSelector
							$dataFlowContext->getDataFlowService()->processDataFlowSelector($dataFlowContext->getPrincipal(), $dfs);
						}
					}
				}
			}
		}
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
		return Element::createInstance($module, $fieldList, $this->createWigiiBagInstance());				
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
		return Element::createInstance($module, $fieldList, $this->createWigiiBagInstance());				
	}
	
	protected function createWigiiBagInstance() {
		return WigiiBagBaseImpl::createInstance();
	}
}