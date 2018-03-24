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
 * A list of ElementP, selected from the database, using the ElementService,
 * and which are pushed into a DataFlow
 * Created by CWE on 23 novembre 2013
 * Modified by Medair (CWE) on 28.04.2017 to attach stamped ElementInfo on fetch
 */
class ElementPListDataFlowConnector implements ElementPList, DataFlowDumpable
{
	private $_debugLogger;
	private $lockedForUse = true;
	private $dataFlowService;
	private $dataFlowContext;
	private $nElements;
	private $instanciatedApiClient;
	private $startEltNumber;
	private $endEltNumber;
	private $currentEltNumber;
	private $calculatedGroupList;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;		
		$this->instanciatedApiClient = false;	
		$this->startEltNumber = 0;
		$this->endEltNumber = 0;
		$this->currentEltNumber = 0;
	}	
	public function freeMemory() {		
		unset($this->inGroupLogExp);
		unset($this->listFilter);
		unset($this->dataFlowService);
		unset($this->dataFlowContext);
		unset($this->calculatedGroupList);
		$this->nElements = 0;	
		$this->lockedForUse = false;	
		unset($this->extApiClient);
		unset($this->authoSStamp);
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstance($inGroupLogExp=null, $listFilter=null) {
		$returnValue = new self();
		$returnValue->reset();
		if(isset($inGroupLogExp)) $returnValue->setInGroupLogExp($inGroupLogExp);
		if(isset($listFilter)) $returnValue->setListFilter($listFilter);
		return $returnValue;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("ElementPListDataFlowConnector");
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
	
	private $injectedApiClient;
	/**
	 * Injects a ready to use GroupBasedWigiiApiClient to be used instead of creating a fresh one.
	 * @param GroupBasedWigiiApiClient $apiClient used only if LinkSelector is set and has no RootConfigSelector defined.
	 */	
	public function setGroupBasedWigiiApiClient($apiClient) {
		$this->injectedApiClient = $apiClient;
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
	
	private $authoSStamp;
	public function setAuthorizationServiceStamp($stamp) {
	    $this->authoSStamp = $stamp;
	}
	
	// Configuration
	
	private $inGroupLogExp;
	/**
	 * Sets the group log exp used to select the elements
	 * @param LogExp $inGroupLogExp a in group logexp
	 */
	public function setInGroupLogExp($inGroupLogExp) {
		$this->inGroupLogExp = $inGroupLogExp;
	}
	
	private $linkSelector;
	/**
	 * Sets the link selector used to select the linked elements or subitems
	 * @param LinkSelector $ls
	 */
	public function setLinkSelector($ls) {
		$this->linkSelector = $ls;
	}
	
	/**
	 * Sets the inGroupLogExp or the linkSelector according to datatype of argument
	 * This method is used by the FuncExpBuilder in case of parametric expressions
	 * @param LogExp|LinkSelector $inGroupLogExpOrLinkSelector
	 */
	public function setInGroupLogExpOrLinkSelector($inGroupLogExpOrLinkSelector) {
		if($inGroupLogExpOrLinkSelector instanceof LogExp) {
			$this->setInGroupLogExp($inGroupLogExpOrLinkSelector);
			$this->setLinkSelector(null);
		}
		else {
			$this->setInGroupLogExp(null);
			$this->setLinkSelector($inGroupLogExpOrLinkSelector);
		}
	}
	
	private $listFilter;
	/**
	 * Sets the list filter used to select the elements
	 * @param ListFilter $listFilter a ListFilter
	 */
	public function setListFilter($listFilter) {
		$this->listFilter = $listFilter;
	}
	/**
	 * Returns the list filter used to select the elements
	 * @return ListFilter
	 */
	public function getListFilter() {
		return $this->listFilter;
	}
	private $maxWigiiBagSize = 100;
	/**
	 * Sets the maximum number of elements that can be stored into one instance of a wigii bag
	 * If the requests has more elements, then several db requests will be done in sequence,
	 * and several wigii bags will be instanciated in sequence.
	 * From a dataflow point of view it is transparent as the dataflow always contains one smooth
	 * flow with all selected elements (according to list filter setting of course)
	 * @param int $nElement the maximum number of elements that will be retrieved in one db request
	 */
	public function setMaxWigiiBagSize($nElement) {
		if($nElement <= 0) throw new DataFlowServiceException("max number of elements should be a positive integer", DataFlowServiceException::INVALID_ARGUMENT);
		$this->maxWigiiBagSize = $nElement;
	}
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {		
		$this->dataFlowService = $dataFlowService;
		$this->dataFlowContext = $dataFlowContext;
		$this->instanciatedApiClient = false;
		// creates ListFilter to select elements in a paged way
		$lf = ListFilter::createInstance();
		$lf->setDesiredPageNumber(1);
		$lf->setPageSize($this->maxWigiiBagSize);		
		$maxElements = -1;
		// clones information coming from user listfilter
		if(isset($this->listFilter)) {
			$lf->setFieldSelectorList($this->listFilter->getFieldSelectorList());
			$lf->setFieldSelectorLogExp($this->listFilter->getFieldSelectorLogExp());
			$lf->setFieldSortingKeyList($this->listFilter->getFieldSortingKeyList());
			$lf->setConfigGroupList($this->listFilter->getConfigGroupList());
			if($this->listFilter->isPaged()) {
				$maxElements = $this->listFilter->getPageSize();
				if($lf->getPageSize() > $maxElements) $lf->setPageSize($maxElements);
				$this->startEltNumber = ($this->listFilter->getDesiredPageNumber()-1)*$maxElements+1;
				$this->endEltNumber = $this->startEltNumber + $maxElements;
			}
			// stores ListFilter into data flow context so that it can be used in next stages
			$dataFlowContext->setAttribute('ListFilter', $this->listFilter);
		}
		// stores FieldSelectorList into data flow context so that it can be used in next stages
		$fsl = $lf->getFieldSelectorList();
		if(isset($fsl)) $dataFlowContext->setAttribute('FieldSelectorList', $fsl);
				
		// checks nature of elements to fetch
		$isSubitem = isset($this->linkSelector) && !isset($this->inGroupLogExp);		
		
		$this->nElements = 0;	
		if($this->startEltNumber > 0) {
			$p = $this->startEltNumber / $lf->getPageSize();
			$lf->setDesiredPageNumber(($p > (int)$p) ? (int)$p+1 : (int)$p);
		}
		$this->currentEltNumber = ($lf->getDesiredPageNumber()-1)*$lf->getPageSize();
		$principal = $dataFlowContext->getPrincipal();
		
		if($isSubitem) {
		    // forbids direct access to subelements if origin is public (because a listFilterExp could apply on root element and we cannot know at this stage)
		    if($dataFlowContext->isOriginPublic()) throw new DataFlowServiceException('Sub-elements cannot be accessed from public space',DataFlowServiceException::FORBIDDEN);
		    
			// sets configuration if defined
			$configSel = $this->linkSelector->getRootConfigSelector();
			$apiClient = $this->injectedApiClient;
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
			$this->dataFlowContext->setAttribute('GroupBasedWigiiApiClient', $apiClient, true);
			$this->instanciatedApiClient = true;			
			$this->dataFlowContext->setAttribute('linkSelector', $this->linkSelector);
			
			$n = $apiClient->getElementService()->getSubElementsForField($principal, $this->linkSelector->getOwnerElementId(), $this->linkSelector->getFieldName(), $this, $lf);
		} 	
		else {
			if(isset($this->linkSelector)) $dataFlowContext->setAttribute('linkSelector', $this->linkSelector);
			
			// Medair(CWE) 13.03.2018 applies listFilterExp if data flow is called from public space
			if($dataFlowContext->isOriginPublic()) {
			    $apiClient = ServiceProvider::getGroupBasedWigiiApiClient($principal, $this->inGroupLogExp);
			    $wbpl = ServiceProvider::getWigiiBPL();
			    
			    // if one group is selected, then centers on eventual group config
			    if($apiClient->getGroupList()->count() == 1) {
			        $gcs = $wbpl->buildConfigSelectorForGroup($principal, reset($apiClient->getGroupList()->getListIterator()));
			        $apiClient->selectGroups($principal, $gcs->getGroupLogExp());
			    }
			    $listFilterExp=(string)$apiClient->getConfigService()->getParameter($principal,$apiClient->getModule(),'listFilterExp');
			    if(!empty($listFilterExp)) {
			        $listFilterExp = $wbpl->evaluateFuncExp($principal, str2fx($listFilterExp));
			        if(isset($listFilterExp)) {
			            if($listFilterExp instanceof LogExp) {
			                $lx = $lf->getFieldSelectorLogExp();
			                if(isset($lx)) $lx = lxAnd($lx,$listFilterExp);
			                else $lx = $listFilterExp;
			                $lf->setFieldSelectorLogExp($lx);
			            }
			            else throw new DataFlowServiceException('listFilterExp is not a valid LogExp', DataFlowServiceException::CONFIGURATION_ERROR);
			        }
			    }
			}
			
			$n = $this->getElementService()->getSelectedElementsInGroups($principal, $this->inGroupLogExp, $this, $lf);
		}
		
		// updates user ListFilter with totalNumberOfObjects if set
		if(isset($this->listFilter)) {
			$this->listFilter->setTotalNumberOfObjects($lf->getTotalNumberOfObjects());
		}
		
		// fetches all the elements until reaching the max
		//fput('ElementPListDataFlowConnector: '.implode(',', array($n, $maxElements, $lf->getPageSize(), $lf->getTotalNumberOfObjects(), $lf->getDesiredPageNumber())));
		while($n < $lf->getTotalNumberOfObjects() &&
				($maxElements < 0 || $n < $maxElements)) {			
			//fput('ElementPListDataFlowConnector: '.implode(',', array($n, $maxElements, $lf->getPageSize(), $lf->getTotalNumberOfObjects(), $lf->getDesiredPageNumber())));
			$lf->setDesiredPageNumber($lf->getDesiredPageNumber()+1);
			//$this->debugLogger()->write("gets next ".$lf->getPageSize()." elements on max of ".($maxElements > 0 ? $maxElements : $lf->getTotalNumberOfObjects()));
			$this->nElements = 0;
			if($isSubitem) {
				$n += $apiClient->getElementService()->getSubElementsForField($principal, $this->linkSelector->getOwnerElementId(), $this->linkSelector->getFieldName(), $this, $lf);
			} 	
			else {
				$n += $this->getElementService()->getSelectedElementsInGroups($principal, $this->inGroupLogExp, $this, $lf);
			}
			if($this->nElements == 0) break;					
		}

		// updates the number of elements to make it readable from calling process
		$this->nElements = $n;
	}
	
	// ElementPList implementation		
	
	public function addElementP($elementP) {
		$this->nElements++;
		$this->currentEltNumber++;
		if($this->startEltNumber == 0 ||
			$this->startEltNumber <= $this->currentEltNumber && 
			$this->currentEltNumber < $this->endEltNumber) {
			// extracts element info and stamps it
			if(!$this->authoSStamp) $this->getAuthorizationService()->getStamp($this, "setAuthorizationServiceStamp");
			if($this->authoSStamp) $elementP->computeElementInfo($this->dataFlowContext->getPrincipal(),null,$this->authoSStamp);
			// pushes down to data flow
			$this->dataFlowService->processDataChunk($elementP, $this->dataFlowContext);
		}
	}
	
	public function createFieldList() {return FieldListArrayImpl::createInstance();}
	
	public function createWigiiBag() {return WigiiBagBaseImpl::createInstance();}
		
	public function getListIterator() {throw new ElementServiceException("The ElementPListDataFlowConnector cannot be iterated. It is a forward only push of elements into the data flow.", ElementServiceException::UNSUPPORTED_OPERATION);}
	
	public function isEmpty() {return ($this->nElements == 0);}
	
	public function count() {return $this->nElements;}
	
	public function notifyCalculatedGroupList($groupList) {
		if(!$this->instanciatedApiClient) {
			$this->dataFlowContext->setAttribute('GroupBasedWigiiApiClient', ServiceProvider::getGroupBasedWigiiApiClient($this->dataFlowContext->getPrincipal(), $groupList), true);
			$this->instanciatedApiClient = true;
			$this->calculatedGroupList = $groupList;
		} 
	}
	
	public function getCalculatedGroupList() {
		return $this->calculatedGroupList;
	}
	
	/**
	 * Converts this ElementPList data flow connector to its FuncExp equivalent
	 * @return FuncExp
	 */
	public function toFx() {
		$lxFxBuilder = TechnicalServiceProvider::getFieldSelectorLogExpFuncExpBuilder();
		$args = array();
		if(isset($this->inGroupLogExp)) $args[] = $lxFxBuilder->logExp2funcExp($this->inGroupLogExp);
		elseif(isset($this->linkSelector)) $args[] = $this->linkSelector->toFx();		
		if(isset($this->listFilter)) $args[] = $this->listFilter->toFx();		
		$lxFxBuilder->freeMemory();
		return fx('elementPList', $args);
	}
}