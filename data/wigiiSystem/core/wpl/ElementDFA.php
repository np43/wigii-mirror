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
 * Modified by Medair (CWE) on 18.05.2017 to :
 * - always use the trashbin. Real deletion should be configured by explicit subclass.
 * - ensure to have a valid attached ElementInfo before updating or deleting elements.
 * - check if element is not blocked before updating or deleting it.
 * - check that if an Element_beforeDeleteExp parameter is defined, then deletion is authorized
 */
class ElementDFA implements DataFlowActivity, RootPrincipalDFA
{	
	private $apiClient;
	private $apiClientSupportsSubitems;
	protected $areWigiiEventsEnabled;
	private $wigiiEventsDispatcher;
	protected $groupListForEvents;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->reloadElementAfterInsert = true;		
		$this->putInTrashBin = true;
	}	
	public function freeMemory() {
		unset($this->mode);
		unset($this->decisionMethod);
		unset($this->eltS); /* unsets injected element service because it can come from the data flow context */		
		unset($this->configS); /* unsets injected config service because it can come from the data flow context */
		unset($this->apiClient);
		unset($this->apiClientSupportsSubitems);
		unset($this->groupId);
		unset($this->linkSelector);
		unset($this->fieldSelectorList);
		unset($this->ignoreLockedElements);
		unset($this->areWigiiEventsEnabled);
		unset($this->wigiiEventsDispatcher);
		unset($this->groupListForEvents);
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
	
	private $authoS;
	public function setAuthorizationService($authorizationService)
	{
	    $this->authoS = $authorizationService;
	}
	protected function getAuthorizationService()
	{
	    // autowired
	    if(!isset($this->authoS))
	    {
	        $this->authoS = ServiceProvider::getAuthorizationService();
	    }
	    return $this->authoS;
	}
	
	private $translationService;
	public function setTranslationService($translationService){
	    $this->translationService = $translationService;
	}
	protected function getTranslationService(){
	    //autowired
	    if(!isset($this->translationService)){
	        $this->translationService = ServiceProvider::getTranslationService();
	    }
	    return $this->translationService;
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
	
	protected $groupId;
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
	 * If true, then locked or blocked elements are ignored when deleting or updating.
	 * Else, an AuthorizationServiceException::OBJECT_IS_LOCKED or AuthorizationServiceException::NOT_ALLOWED is thrown when trying to update or delete locked or blocked elements.
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
	 * Else elements are deleted normally. By default always uses the trashbin. Only subclass could redefine this parameter.
	 */
	protected function setPutInTrashBin($bool) {
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
		// in that case, takes the provided element service and config service instances
		if(!isset($this->eltS) && isset($this->apiClient)) $this->setElementService($this->apiClient->getElementService());
		if(!isset($this->configS) && isset($this->apiClient)) $this->setConfigService($this->apiClient->getConfigService());

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
		$elementIsNew = $element->isNew();
		
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
			    // if new, then inserts
			    if($elementIsNew) {
    				if($this->ignoreLockedElements) {
    					try {$element = $this->doPersistElement($principal, $element, $fieldSelectorList);}				
    					catch(AuthorizationServiceException $ase) {if($ase->getCode() != AuthorizationServiceException::OBJECT_IS_LOCKED) throw $ase;}
    				}
    				else $element = $this->doPersistElement($principal, $element, $fieldSelectorList);
			    }
				// if element already exists in database, then checks validity of its ElementInfo and prevents updating if blocked
				else {
				    if($this->isElementBlocked($element)) {
				        if(!$this->ignoreLockedElements) throw new AuthorizationServiceException("Cannot update an element having status blocked.", AuthorizationServiceException::NOT_ALLOWED);
				    }
				    else {
				        if($this->ignoreLockedElements) {
				            try {$element = $this->doPersistElement($principal, $element, $fieldSelectorList);}
				            catch(AuthorizationServiceException $ase) {if($ase->getCode() != AuthorizationServiceException::OBJECT_IS_LOCKED) throw $ase;}
				        }
				        else $element = $this->doPersistElement($principal, $element, $fieldSelectorList);
				    }
				}
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
			    // ignores new elements
			    if(!$elementIsNew) {
			        // if element already exists in database, then checks validity of its ElementInfo and prevents deleting if blocked
			        if($this->isElementBlocked($element)) {
			            if(!$this->ignoreLockedElements) throw new AuthorizationServiceException("Cannot delete an element having status blocked.", AuthorizationServiceException::NOT_ALLOWED);
			        }
			        else {
			            // if enableDeleteOnlyForAdmin=1, then prevents deleting element if current principal is not admin
			            if($this->getConfigService()->getParameter($principal, $element->getModule(), 'enableDeleteOnlyForAdmin') == "1" && !$this->isPrincipalAdmin($element, $dataFlowContext)) {
			                if(!$this->ignoreLockedElements) throw new AuthorizationServiceException("Element can only be deleted by Administrators.", AuthorizationServiceException::NOT_ALLOWED);
			            }
			            else {
			                // checks if an Element_beforeDeleteExp is defined in configuration and checks if deletion is possible
			                $beforeDeleteExp = (string)$this->getConfigService()->getParameter($principal, $element->getModule(), "Element_beforeDeleteExp");
			                $beforeDeleteExp = $this->evaluateBeforeDeleteExp($element,$dataFlowContext, $beforeDeleteExp);
			                if(!$beforeDeleteExp->okToDelete) {
			                    if(!$this->ignoreLockedElements) throw new AuthorizationServiceException("Element cannot be deleted: ".$beforeDeleteExp->message, AuthorizationServiceException::NOT_ALLOWED);
			                }			                
			                // else standard deletion of non-locked elements
			                else {
                				if($this->ignoreLockedElements) {
                					try {$this->doDeleteElement($principal, $element);}				
                					catch(AuthorizationServiceException $ase) {if($ase->getCode() != AuthorizationServiceException::OBJECT_IS_LOCKED) throw $ase;}
                				}
                				else $this->doDeleteElement($principal, $element);
			                }
			            }
			        }
			    }
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
		    // computes group list for events
		    if(!isset($this->groupListForEvents)) {
		        // if insert, then builds a group list based on group id
		        if(isset($this->groupId)) {
		            $this->groupListForEvents = GroupListAdvancedImpl::createInstance();
		            $this->getGroupAdminService()->getGroupsWithoutDetail($principal, array($this->groupId=>$this->groupId), $this->groupListForEvents);
		        }
		        // else takes current group list from context
		        elseif(isset($this->apiClient)) $this->groupListForEvents = $this->apiClient->getGroupList();
		        else $this->groupListForEvents = null;
		    }
			$wigiiEvent = PWithElementWithGroupPList::createInstance($principal, $element, $this->groupListForEvents);
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
			$configS = $this->getConfigService();
			
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
	
	/**
	 * Verifies if an element is blocked. It verifies the attached ElementInfo validity and return the isElementBlocked attribute
	 * @param Element $element a valid Element for which the ElementInfo has been calculated.
	 */
	protected function isElementBlocked($element) {
	    // verifies ElementInfo validity
	    if(!isset($element)) throw new DataFlowServiceException('Element cannnot be null', DataFlowServiceException::INVALID_ARGUMENT);	    
	    $elementInfo = $element->getElementInfo();
	    if(!isset($elementInfo)) throw new DataFlowServiceException('Element has no valid ElementInfo attached to it. Please use a trusted source of data.', DataFlowServiceException::DATA_INTEGRITY_ERROR);
	    if(!$this->getAuthorizationService()->isStampValid($elementInfo->getAuthorizationServiceStamp())) throw new DataFlowServiceException('Element has no valid ElementInfo attached to it. Please use a trusted source of data.', DataFlowServiceException::DATA_INTEGRITY_ERROR);
	    // checks if element is blocked
	    return $elementInfo->isElementBlocked();
	}
	
	/**
	 * Verifies if the principal who fetched the element has admin rights on the folder containing the element.
	 * It verifies the attached ElementInfo validity and return the isReaderAdmin attribute.
	 * @param Element $element a valid Element for which the ElementInfo has been calculated.
	 * @param DataFlowContext $dataFlowContext the current data flow context to retrieve current principal
	 */
	protected function isPrincipalAdmin($element, $dataFlowContext) {
	    // verifies ElementInfo validity
	    if(!isset($element)) throw new DataFlowServiceException('Element cannnot be null', DataFlowServiceException::INVALID_ARGUMENT);
	    $elementInfo = $element->getElementInfo();
	    if(!isset($elementInfo)) throw new DataFlowServiceException('Element has no valid ElementInfo attached to it. Please use a trusted source of data.', DataFlowServiceException::DATA_INTEGRITY_ERROR);
	    if(!$this->getAuthorizationService()->isStampValid($elementInfo->getAuthorizationServiceStamp())) throw new DataFlowServiceException('Element has no valid ElementInfo attached to it. Please use a trusted source of data.', DataFlowServiceException::DATA_INTEGRITY_ERROR);
	    
	    $principal = $dataFlowContext->getPrincipal();
	    // if current principal is root then gives admin rights
	    if($this->getAuthorizationService()->isRootPrincipal($principal)) return true;
	    // else current principal should be the element reader and isReaderAdmin should be true
	    else return ($elementInfo->getReaderUsername() == $principal->getUsername() && $elementInfo->isReaderAdmin());
	}
	
	/**
	 * Returns an instance of a FuncExpEvaluator configured for the context of the given Element.
	 * @param Element $element the current element for which to get a FuncExpEvaluator
	 * @param DataFlowContext $dataFlowContext the current data flow context to retrieve current principal and ConfigService
	 * @return FuncExpEvaluator
	 */
	protected function getFuncExpEvaluator($element, $dataFlowContext) {
	    if(!isset($element)) throw new DataFlowServiceException('element cannot be null', DataFlowServiceException::INVALID_ARGUMENT);
	    if(!isset($dataFlowContext)) throw new DataFlowServiceException('dataFlowContext cannot be null', DataFlowServiceException::INVALID_ARGUMENT);
	    $p = $dataFlowContext->getPrincipal();
	    
	    // gets ElementEvaluator
	    $evaluatorClassName = (string)$this->getConfigService()->getParameter($p, $element->getModule(), "Element_evaluator");
	    $evaluator = ServiceProvider::getElementEvaluator($p, $evaluatorClassName);
	    // injects the context
	    $evaluator->setContext($p, $element);
	    // gets vm
	    $returnValue = ServiceProvider::getFuncExpVM($p, $evaluator);
	    $returnValue->setFreeParentEvaluatorOnFreeMemory(true);
	    return $returnValue;
	}
	
	/**
	 * Evaluates the Element_beforeDeleteExp and returns an object authorizing or not the deletion and an optional error message.
	 * @param Element $element the current element for which to get a FuncExpEvaluator
	 * @param DataFlowContext $dataFlowContext the current data flow context to retrieve current principal and ConfigService
	 * @param String $beforeDeleteExp beforeDeleteExp FuncExp or 0 or 1.
	 * @throws Exception in case of error
	 * @return StdClass of the form {okToDelete: Boolean, message: String}
	 */
	protected function evaluateBeforeDeleteExp($element,$dataFlowContext,$beforeDeleteExp) {
	    $transS = $this->getTranslationService();
	    $returnValue = null;
	    
	    // null expression always resolves to true
	    if($beforeDeleteExp == null) $returnValue = true;
	    // else converts 0 to false
	    elseif($beforeDeleteExp === '0') $returnValue = false;
	    // and 1 to true
	    elseif($beforeDeleteExp === '1') $returnValue = true;
	    // else should be a FuncExp.
	    else {
	        // if the FuncExp has a syntax error or fails to execute, then deletion is blocked and exception message is added to standard message.
	        $fxEval = null;
	        try {
	            $beforeDeleteExp = str2fx($beforeDeleteExp);
	            $fxEval = $this->getFuncExpEvaluator($element, $dataFlowContext);
	            $returnValue = $fxEval->evaluateFuncExp($beforeDeleteExp,$this);
	            $fxEval->freeMemory();	            
	        }
	        catch(Exception $e) {
	            if(isset($fxEval)) $fxEval->freeMemory();
	            if($e instanceof ServiceException) $e = $e->getWigiiRootException();
	            $returnValue = (object)array('okToDelete'=>false,'message'=>$e->getMessage().$transS->t($p, "elementCannotBeDeletedEvaluationError"));
	        }
	    }
	    // returns the resolved expression
	    if(!($returnValue instanceof stdClass)) {
	        // if evaluates to true, then OK to delete.
	        if($returnValue) $returnValue = (object)array('okToDelete'=>true,'message'=>null);
	        // else KO to delete and adds a default message
	        else $returnValue = (object)array('okToDelete'=>false,'message'=>$transS->t($p, "elementCannotBeDeletedExplanation"));
	    }
	    // adds default message if explanation is missing
	    elseif(!$returnValue->okToDelete && !$returnValue->message) $returnValue->message = $transS->t($p, "elementCannotBeDeletedExplanation");
	    return $returnValue;
	}
}