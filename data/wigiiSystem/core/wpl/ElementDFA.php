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
 * A data flow activity which inserts/updates/deletes a flow of element.
 * Supports a flow of Element or ElementP instances.
 * This DataFlowActivity cannot be called from public space (i.e. caller is located outside of the Wigii instance)
 * Created by CWE on 21 novembre 2013
 * Modified by Medair (CWE) on 15.12.2016 to protect against Cross Site Scripting
 */
class ElementDFA implements DataFlowActivity, RootPrincipalDFA
{	
	private $apiClient;
	private $apiClientSupportsSubitems;
	private $areWigiiEventsEnabled;
	private $wigiiEventsDispatcher;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->reloadElementAfterInsert = true;		
		$this->putInTrashBin = false;
	}	
	public function freeMemory() {
		unset($this->mode);
		unset($this->decisionMethod);
		unset($this->eltS); /* unsets injected element service because it can come from the data flow context */		
		unset($this->apiClient);
		unset($this->apiClientSupportsSubitems);
		unset($this->groupId);
		unset($this->linkSelector);
		unset($this->fieldSelectorList);
		unset($this->ignoreLockedElements);
		unset($this->areWigiiEventsEnabled);
		unset($this->wigiiEventsDispatcher);
		unset($this->trashbinIdCache);
		unset($this->elementPAList);
		unset($this->pRights);
	}

	// Dependency injection
	
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
	
	private $gAS;
	public function setGroupAdminService($groupAdminService) {
		$this->gAS = $groupAdminService;
	}
	protected function getGroupAdminService() {
		// autowired
		if(!isset($this->gAS)) {
			$this->gAS = ServiceProvider::getGroupAdminService();
		}
		return $this->gAS;
	}
	
	private $rootPrincipal;
	public function setRootPrincipal($rootP) {
		$this->rootPrincipal = $rootP;
	}
	protected function getRootPrincipal() {
		return $this->rootPrincipal;
	}
	
	// configuration

	const MODE_PERSIST = 1;
	const MODE_DELETE = 2;
	const MODE_MIXED = 3;
	const MODE_FILTER = 4;
	const MODE_IGNORE = 5;
	private $mode;
	
	/**
	 * Sets the execution mode: one of PERSIST, DELETE or MIXED
	 * if PERSIST then all elements in the flow are persisted (updated if exists, or inserted if new)
	 * if DELETE then all elements in the flow are deleted
	 * if MIXED then the configured closure (or method) is executed to determine what should be done with each element.
	 * @param int $mode the execution mode
	 * @throws DataFlowServiceException if argument is invalid.
	 */
	public function setMode($mode) {
		switch($mode) {
			case self::MODE_PERSIST:
			case self::MODE_DELETE:
			case self::MODE_MIXED:
				$this->mode = $mode;
				break;
			default: throw new DataFlowServiceException("mode should be one of PERSIST, DELETE or MIXED", DataFlowServiceException::INVALID_ARGUMENT);
		}	
	}
	
	private $decisionMethod;	
	/**
	 * Sets the decision method that should be executed on each element in the flow to
	 * determine what should be done with the element.
	 * The method signature takes one argument which is the current element (or elementP) and
	 * a second argument which is the current data flow context. 
	 * The method should return an integer which should be one of :
	 * MODE_PERSIST if the element should be persisted (updated or inserted) and go further in the data flow,
	 * MODE_DELETE if the element should be deleted and not go further in the data flow,
	 * MODE_FILTER if nothing should be done with the element and 
	 * the element should not go further in the data flow,
	 * MODE_IGNORE if nothing should be done with the element and 
	 * the element should go further in the data flow. 
	 * @param Closure|String $method a closure representing the code that should be executed or 
	 * a string which is an object method name or a function name
	 * @param Any $object an optional object instance which holds the method instance that should be executed.
	 */
	public function setDecisionMethod($method, $object=null) {
		$this->decisionMethod = CallableObject::createInstance($method, $obj); 
	}
	
	private $groupId;
	/**
	 * Sets the ID of the group where to insert the elements
	 * @param int $groupId a Group ID
	 */
	public function setGroupId($groupId) {
		$this->groupId = $groupId;
	}
	
	private $linkSelector;
	/**
	 * Sets the link selector describing where to insert the subitems or link the elements
	 * @param LinkSelector $ls
	 */
	public function setLinkSelector($ls) {
		$this->linkSelector = $ls;
	}
	
	private $fieldSelectorList;
	/**
	 * Sets an optional field selector list that should be used to filter
	 * the fields that are persisted
	 * @param FieldSelectorList $fieldSelectorList an optional field selector list that should be passed
	 * to ElementService methods update or insert.
	 */
	public function setFieldSelectorList($fieldSelectorList) {
		$this->fieldSelectorList = $fieldSelectorList;
	}
	
	private $ignoreLockedElements;
	/**
	 * If true, then locked elements are ignored when deleting or updating.
	 * Else, an AuthorizationServiceException::OBJECT_IS_LOCKED is thrown when trying to update or delete locked elements.
	 * False by default.	 
	 */
	public function setIgnoreLockedElements($bool) {
		$this->ignoreLockedElements = $bool;
	}	
	
	private $reloadElementAfterInsert;
	/**
	 * If true, then the inserted element is reloaded from the database before pushing it further into the flow,
	 * else the same incoming element is pushed directly in the flow without reloading from the database.
	 * In that later case, to access the element values in the wigiibag you need to use the method 'getFieldValueBeforeInsert',
	 * instead of the normal 'getFieldValue' method.
	 * By default, reloading is active.
	 */
	public function setReloadElementAfterInsert($bool) {
		$this->reloadElementAfterInsert = $bool;
	}
	
	private $putInTrashBin;
	/**
	 * If true, then deleted elements are put into the trashbin if defined in the configuration (works also for subelements).
	 * Else elements are deleted normally. Defaults to false.
	 */
	public function setPutInTrashBin($bool) {
		$this->putInTrashBin = $bool;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$dataFlowContext->assertOriginIsNotPublic();
		if(!isset($this->mode)) throw new DataFlowServiceException("mode has not been set", DataFlowServiceException::CONFIGURATION_ERROR);

		$this->areWigiiEventsEnabled = $dataFlowContext->areWigiiEventsEnabled();
		if($this->areWigiiEventsEnabled) $this->wigiiEventsDispatcher = $dataFlowContext->getWigiiEventsDispatcher();
		
		// stores API client and capabilities for future use
		$this->apiClient = $dataFlowContext->getAttribute('GroupBasedWigiiApiClient');
		if(isset($this->apiClient)) {			
			$this->apiClientSupportsSubitems = method_exists($this->apiClient->getConfigService(), 'getCurrentSubElementPathFromRoot');
		}
		
		// checks the presence of a GroupBasedWigiiApiClient
		// in that case, takes the provided element service instance
		if(!isset($this->eltS) && isset($this->apiClient)) $this->setElementService($this->apiClient->getElementService());

		// if mode PERSIST or MIXED, then checks for groupId and/or linkSelector, if not set, 
		// then tries to fetch it from the dataflow context
		if($this->mode == self::MODE_PERSIST || $this->mode == self::MODE_MIXED) {
			if(!isset($this->groupId)) { 
				$dfGroupId = $dataFlowContext->getAttribute('groupId');
				if(isset($dfGroupId)) $this->setGroupId($dfGroupId);
			}
			if(!isset($this->linkSelector)) { 
				$dfLinkSelector = $dataFlowContext->getAttribute('linkSelector');
				if(isset($dfLinkSelector)) $this->setLinkSelector($dfLinkSelector);
			}
		}
	}
	public function processDataChunk($data, $dataFlowContext) {
		// if mixed mode, then the action depends of the execution of the decision function
		if($this->mode == self::MODE_MIXED) {
			// uses the closure if defined			
			if(isset($this->decisionMethod)) $action = $this->decisionMethod->invoke($data, $dataFlowContext);
			// else calls the protected method
			else $action = $this->decideActionOnElement($data, $dataFlowContext);
		}	
		// else the action is the current mode
		else $action = $this->mode;
		
		// extracts the element
		$element = $data->getDbEntity();		
		
		// gets FieldSelectorList in configuration or context
		$fieldSelectorList = $this->fieldSelectorList;
		if(is_null($fieldSelectorList)) {
			$fieldSelectorList = $dataFlowContext->getAttribute('FieldSelectorList');
		}
		// clones FieldSelectorList to include sys info fields
		if(isset($fieldSelectorList)) $fieldSelectorList = FieldSelectorListArrayImpl::createInstance(true,true,$fieldSelectorList);
		
		// executes the action on the current element
		$principal = $dataFlowContext->getPrincipal();
		switch($action) {
			case self::MODE_PERSIST:
				$elementIsNew = $element->isNew();
				if($this->ignoreLockedElements) {
					try {$element = $this->doPersistElement($principal, $element, $fieldSelectorList);}				
					catch(AuthorizationServiceException $ase) {if($ase->getCode() != AuthorizationServiceException::OBJECT_IS_LOCKED) throw $ase;}
				}
				else $element = $this->doPersistElement($principal, $element, $fieldSelectorList);
				// pushes the element further
				if(!$dataFlowContext->isCurrentStepTheLastStep()) {
					// if element has been inserted, then reads again from the database
					if($elementIsNew != $element->isNew() && $this->reloadElementAfterInsert) {
						// resets the WigiiBag
						$wigiiBag = $element->getWigiiBag();
						if(method_exists($wigiiBag, 'reset')) $wigiiBag->reset();
						else {
							$wigiiBag = $this->createWigiiBagInstance();
							$element->setWigiiBag($wigiiBag);
						}	
						// resets the FieldList
						$fieldList = $element->getFieldList();
						if(method_exists($fieldList, 'reset')) $fieldList->reset();
						else {
							$fieldList = FieldListArrayImpl::createInstance();
							$element->setFieldList($fieldList);
						}				
						// fills the element
						$elementP = $this->getElementService()->fillElement($principal, $element, $fieldSelectorList);
						$dataFlowContext->writeResultToOutput($elementP, $this);
					}
					else $dataFlowContext->writeResultToOutput($data, $this);
				}
				break;
			case self::MODE_DELETE:
				if($this->ignoreLockedElements) {
					try {$this->doDeleteElement($principal, $element);}				
					catch(AuthorizationServiceException $ase) {if($ase->getCode() != AuthorizationServiceException::OBJECT_IS_LOCKED) throw $ase;}
				}
				else $this->doDeleteElement($principal, $element);
				break;
			case self::MODE_IGNORE:
				if(!$dataFlowContext->isCurrentStepTheLastStep()) $dataFlowContext->writeResultToOutput($data,$this);
				break;
			// case MODE_FILTER :: does not touch element and does not push it any further.
		}		
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
	 * Decides which action should be executed with the element.
	 * Subclass should implement this method or set a closure using the setDecisionMethod
	 * @param Element|ElementP $data current element or elementP in the data flow
	 * @param DataFlowContext $dataFlowContext the current data flow context
	 * @return int should return on of the following action code :
	 * MODE_PERSIST if the element should be persisted (updated or inserted) and go further in the data flow,
	 * MODE_DELETE if the element should be deleted and not go further in the data flow,
	 * MODE_FILTER if nothing should be done with the element and 
	 * the element should not go further in the data flow,
	 * MODE_IGNORE if nothing should be done with the element and 
	 * the element should go further in the data flow. 
	 */
	protected function decideActionOnElement($data, $dataFlowContext) {
		throw new DataFlowServiceException("implement the 'decideActionOnElement' method into a subclass or set a closure using the 'setDecisionMethod'", DataFlowServiceException::CONFIGURATION_ERROR);
	}
	
	/**
	 * Persists the element and returns the persisted element
	 * @param Principal $principal the principal persisting the element
	 * @param Element $element the element that should be persisted
	 * @param FieldSelectorList $fieldSelectorList an optional field selector list to be used
	 * @throws ElementServiceException in case of error
	 * @return Element returns the persisted element
	 */
	protected function doPersistElement($principal, $element, $fieldSelectorList=null) {
		// creates wigii event if should throw events 		
		if($this->areWigiiEventsEnabled) {						
			if(isset($this->apiClient)) $groupList = $this->apiClient->getGroupList();			
			else $groupList = null;
			$wigiiEvent = PWithElementWithGroupPList::createInstance($principal, $element, $groupList);
			$wigiiEvent->setLinkSelector($this->linkSelector);
		}		
		
		// if new element then inserts
		if($element->isNew()) {
			// if sub element
			if(isset($this->linkSelector) && !isset($this->groupId)) {				
				$this->getElementService()->insertSubElement($principal, $this->linkSelector->getOwnerElementId(), $this->linkSelector->getFieldName(), $element, $fieldSelectorList);				
			}
			// else if root element
			else $this->getElementService()->insertElement($principal, $element, $this->groupId, $fieldSelectorList);				
			
			// throws event
			if($this->areWigiiEventsEnabled) {
				// adds subitem path from root if defined
				if($this->apiClientSupportsSubitems) {
					$wigiiEvent->setSubElementPathFromRoot($this->apiClient->getConfigService()->getCurrentSubElementPathFromRoot());
				}
				$this->wigiiEventsDispatcher->insertElement($wigiiEvent);
			}			
		}
		// else updates
		else {
			$this->getElementService()->updateElement($principal, $element, $fieldSelectorList);
			
			// throws event
			if($this->areWigiiEventsEnabled) {
				// adds subitem path from root if defined
				if($this->apiClientSupportsSubitems) {
					$wigiiEvent->setSubElementPathFromRoot($this->apiClient->getConfigService()->getCurrentSubElementPathFromRoot());
				}
				$this->wigiiEventsDispatcher->updateElement($wigiiEvent);	
			}
		}
		return $element;
	}
	
	/**
	 * Deletes the element
	 * @param Principal $principal the principal deleting the element
	 * @param Element $element the element that should be deleted
	 * @throws ElementServiceException in case of error
	 */
	protected function doDeleteElement($principal, $element) {	
		// gets trashbinId if defined
		if($this->putInTrashBin) $trashBinId = $this->getTrashBinIdForElement($principal, $element);
		else $trashBinId = null;

		// move to trashbin if defined
		if(isset($trashBinId)) $this->moveToTrashBin($principal, $element, $trashBinId);
		else $this->getElementService()->deleteElement($principal, $element->getId());
		
		// throws event		
		if($this->areWigiiEventsEnabled) {						
			if(isset($this->apiClient)) $groupList = $this->apiClient->getGroupList();			
			else $groupList = null;
			$wigiiEvent = PWithElementWithGroupPList::createInstance($principal, $element, $groupList);
			$wigiiEvent->setLinkSelector($this->linkSelector);			
			// adds subitem path from root if defined
			if($this->apiClientSupportsSubitems) {
				$wigiiEvent->setSubElementPathFromRoot($this->apiClient->getConfigService()->getCurrentSubElementPathFromRoot());
			}
				
			$this->wigiiEventsDispatcher->deleteElement($wigiiEvent);
		}	
	}
	
	private $trashbinIdCache;
	/**
	 * Returns the trashbin ID for the element or null if not defined
	 * @param Principal $principal the principal executing the operation
	 * @param Element $element the element or subelement which will be deleted
	 * @return String the trashbin group id for the element or the trashbin link name prefix for subelements, or null if not defined.
	 */
	protected function getTrashBinIdForElement($principal, $element) {
		$returnValue = null;
		// looks in cache if already exists
		$cacheKey = '('.$element->getModule()->getModuleName().'('.$element->isSubElement().'))';
		if(isset($this->trashbinIdCache)) $returnValue = $this->trashbinIdCache[$cacheKey];
		
		// if not defined then calculates trashbin Id
		if(!isset($returnValue)) {
			$configS = $this->apiClient->getConfigService();
			
			if($element->isSubElement()) {
				$trashBinPrefix = (string)$configS->getParameter($principal, null, "deletedSubElementsLinkNamePrefix");
				if(!empty($trashBinPrefix) && (strpos($element->getLinkName(), $trashBinPrefix)!==0)) $returnValue = $trashBinPrefix;
				else $returnValue = false;			
			}
			
			else {
				$groupAS = $this->getGroupAdminService();
				$trashBinGroupId = (string)$configS->getParameter($principal, $element->getModule(), "trashBinGroup");
				if($trashBinGroupId){
					// checks trashbin validity
					$trashBinGroup = $groupAS->getGroupWithoutDetail($principal, $trashBinGroupId);
					if(is_null($trashBinGroup) || $trashBinGroup->getModule() !==  $element->getModule()) {
						throw new GroupAdminServiceException("trashBinGroup $trashBinGroupId is not valid group of module ".$element->getModule()->getModuleName(), GroupAdminServiceException::CONFIGURATION_ERROR);
					}
					else $returnValue = $trashBinGroupId;
				}
				else $returnValue = false;
			}
			
			// puts value in cache
			if(!isset($this->trashbinIdCache)) $this->trashbinIdCache = array();
			$this->trashbinIdCache[$cacheKey] = $returnValue;
		}
		
		if($returnValue===false) return null; 
		else {
			//delete element if it is contained only in the trashbin
			if(!$element->isSubElement()) {
				$groupList = GroupListAdvancedImpl::createInstance();
				$this->getElementService()->getAllGroupsWithoutDetailContainingElements($principal, $this->getElementPAList($element), $groupList);
				if($groupList->getItemInList($returnValue) && $groupList->count()==1){
					$returnValue = null;
				}
			}
			return $returnValue;
		}
	}
	private $elementPAList;
	private $pRights;
	// creates a fake ElementPAdvancedList containing the element
	private function getElementPAList($element) {
		if(!isset($this->elementPAList)) {
			$this->elementPAList = ElementPAdvancedListArrayImpl::createInstance();
			$this->pRights = PrincipalRights::createInstance();
			$this->pRights->setCanWriteElement(true);
		}
		else $this->elementPAList->reset();
		$elementP = ElementP::createInstance($element);
		$elementP->setRights($this->pRights);
		$this->elementPAList->addElementP($elementP);
		return $this->elementPAList;
	}
	
	/**
	 * Moves the element into the trashbin.
	 * Precondition: the trashbin id should already be checked calling the method getTrashBinIdForElement
	 * @param Element $element
	 */
	protected function moveToTrashBin($principal, $element, $trashBinId) {
		if(!isset($trashBinId)) throw new DataFlowServiceException('trashBinId cannot be null', DataFlowServiceException::INVALID_ARGUMENT);
		
		if($element->isSubElement()) {
			$newLinkName = $trashBinId.$element->getLinkName();
			$this->getElementService()->renameSubElementsLink($principal, $element->getId(), $newLinkName);
			$element->setLinkName($newLinkName);
		}
		else {
			$elS = $this->getElementService();
			$elementId = $element->getId();
			
			//gets all group containing element
			$groupList = GroupListAdvancedImpl::createInstance(false);
			$elS->getAllGroupsContainingElement($principal, $element, $groupList);
			
			//remove any limitedWriteGroup
			if($element->getSys_creationUser() == $principal->getRealUserId()) $groupList = $groupList->getSGroups();
			//remove any unreadable group
			else $groupList = $groupList->getWriteGroups();
			
			//remove autoSharing
			$gids = ValueListArrayMapper::createInstance(true, ValueListArrayMapper::Natural_Separators, true);
			$element->getLinkedIdGroupInRecord($principal, $gids);
			$linkedGids = $gids->getListIterator();
			if($linkedGids == null) $linkedGids = array();
			$linkedGids = array_diff_key($linkedGids, $groupList->getIds());
			if($linkedGids){
				$elS->unshareElement($this->getRootPrincipal(), $elementId, $linkedGids);
			}
			
			//move to trash
			$removeSharingIds = $groupList->getIds();
			unset($removeSharingIds[$trashBinId]);
			$elS->shareElement($this->getRootPrincipal(), $elementId, $trashBinId);
			$elS->updateSys_dateToNow($principal, array($elementId=>$elementId));
			if($removeSharingIds) $elS->unshareElement($this->getRootPrincipal(), $elementId, $removeSharingIds, false);
		}
	}
}