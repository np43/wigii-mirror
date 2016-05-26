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
 * An data flow activity which renders an ElementPList as html
 * Created by CWE on 8.7.2015
 */
class ElementPListHtmlRendererDFA implements DataFlowActivity
{	
	protected $outputEnabled;
	protected $redirectIfOneElement;
	protected $redirectIfOneGroup;
	protected $filterOnElements;
	protected $listIsNavigable;
	protected $generateHtml;
	protected $wigiiNamespace;
	protected $nElements;
	protected $lastElement;
	protected $groupList;
	protected $elementIds;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->outputEnabled = false;
		$this->redirectIfOneElement = false;
		$this->redirectIfOneGroup = false;
		$this->filterOnElements=false;
		$this->listIsNavigable = true;
		$this->nElements = 0;
	}	
	public function freeMemory() {
		unset($this->wigiiNamespace);
		unset($this->lastElement);		
		unset($this->groupList);
		unset($this->elementIds);
	}
	
	// dependency injection
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("ElementPListHtmlRendererDFA");
		}
		return $this->_debugLogger;
	}
	
	private $wigiiExecutor;
	/**
	 * Injects WigiiExecutor to be used to help generate some html
	 * @param WigiiExecutor $wigiiExecutor
	 */
	public function setWigiiExecutor($wigiiExecutor) {
		$this->wigiiExecutor = $wigiiExecutor;
	}
	
	// configuration
		
	/**
	 * If true, then html is echoed, else buffered and forwarded to next stage. Defaults to false.
	 */
	public function setOutputEnabled($bool) {
		$this->outputEnabled = $bool;
	}	
	
	/**
	 * If true and list has only one element, then returns js code to navigate to this element. Default to false.
	 */
	public function setRedirectIfOneElement($bool) {
		$this->redirectIfOneElement = $bool;
	}
	
	/**
	 * If true and list contains elements only in one group, then returns js code to navigate to this group
	 */
	public function setRedirectIfOneGroup($bool) {
		$this->redirectIfOneGroup = $bool;
	}
	
	/**
	 * If true and list contains several elements then initializes advanced search on these elements
	 */
	public function setFilterOnElements($bool) {
		$this->filterOnElements = $bool;
	}
	
	public function setListIsNavigable($bool) {
		$this->listIsNavigable = $bool;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		$this->debugLogger()->write('startOfStream');
		$this->generateHtml = $this->outputEnabled || !($this->redirectIfOneElement||$this->redirectIfOneGroup);
		$html = null;
		if($this->generateHtml) {
			$html = "<div>";
		}
		if(isset($html)) {
			if($this->outputEnabled) echo $html;
			else $dataFlowContext->writeResultToOutput($html, $this);
		}
		$this->wigiiNamespace = $dataFlowContext->getAttribute('GroupBasedWigiiApiClient')->getWigiiNamespace();
		// records element ids if redirect is active
		if($this->redirectIfOneElement || $this->redirectIfOneGroup) $this->elementIds = array();
	}
	public function processDataChunk($data, $dataFlowContext) {
		$element = $data->getDbEntity();
		
		// outputs html list
		$html = null;
		if($this->generateHtml) {
			$html = "<p>";
			if($this->listIsNavigable) {
				$html .= '<a target="_blank" href="';
				$html .= SITE_ROOT.'#'.$this->wigiiNamespace->getWigiiNamespaceUrl().'/'.$element->getModule()->getModuleUrl();
				$html .= '/item/'.$element->getId();
				$html .= '">'.$element->getId().'</a>';
			}
			else {
				$html .= $element->getId();
			}
			$html .= "</p>";
		}
		if(isset($html)) {
			if($this->outputEnabled) echo $html;
			else $dataFlowContext->writeResultToOutput($html, $this);
		}
		
		// records elements groups if redirectIfOneGroup
		if($this->redirectIfOneGroup && isset($this->lastElement)) {
			if(!isset($this->groupList)) {
				$lf = lf(ServiceProvider::getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail());
				$this->groupList = GroupPListArrayImpl::createInstance();				
			}
			else {
				$lf = lf(ServiceProvider::getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail(),
						lxIn(fs('id'), $this->groupList->getGroupIds()));
				$this->groupList->reset();
			}
			// gets groups in which element is present, intersected with other elements belonging.
			$dataFlowContext->getAttribute('GroupBasedWigiiApiClient')->getElementService()->getAllGroupsContainingElement(
				$dataFlowContext->getPrincipal(),
				$this->lastElement,
				$this->groupList,
				$lf
			);
		}
		
		// records last element id and counts elements
		$this->nElements++;
		$this->lastElement = $element; 
		
		// records element ids if redirect is active
		if($this->redirectIfOneElement || $this->redirectIfOneGroup) {
			$this->elementIds[$element->getId()] = $element->getId();
		}
	}
	public function endOfStream($dataFlowContext) {
		// outputs html list footer
		$html = null;
		if($this->generateHtml) {
			$html = "</div>";
		}
		if(isset($html)) {
			if($this->outputEnabled) echo $html;
			else $dataFlowContext->writeResultToOutput($html, $this);
		}
		
		$exec = ServiceProvider::getExecutionService();
		$transS = ServiceProvider :: getTranslationService();
		$p = $dataFlowContext->getPrincipal();
		
		// redirects to element if one element
		if($this->redirectIfOneElement && $this->nElements == 1) {
			$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/" : "").$this->wigiiNamespace->getWigiiNamespaceUrl()."/".$this->lastElement->getModule()->getModuleUrl()."/navigate/item/".$this->lastElement->getId());
		}
		// redirects to group if one group
		elseif($this->redirectIfOneGroup && !$this->filterOnElements && isset($this->groupList) && $this->groupList->count() == 1) {
			$group = reset($this->groupList->getListIterator());
			$group = $group->getDbEntity();
			$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/" : "").$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/navigate/folder/".$group->getId());
		}
		// redirects to advanced search if several elements and redirect is active
		elseif($this->redirectIfOneElement || $this->redirectIfOneGroup || $this->filterOnElements) {			
			if($this->nElements > 0) {
				// redirect to advance search.
				$module = $this->lastElement->getModule();
				//$exec->addRequests("workZone/" . $this->wigiiNamespace->getWigiiNamespaceUrl() . "/" . $module->getModuleUrl() . "/display/workZoneStructure/");
				$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/" : "").$this->wigiiNamespace->getWigiiNamespaceUrl() . "/" . $module->getModuleUrl() . "/navigate");
				$exec->addJsCode("$('#workZone #searchBar input:first').val('#".implode(" #", $this->elementIds)."');");
				$exec->addJsCode('setTimeout(function(){$("#goForSearch").click();}, 500);');				
			}
			// no elements found -> redirects to Namespace/Module of search space
			else {
				
				// this code has moved in the WigiiExecutor as the DataFlow is not executed at all if no elements are selected
				
// 				$this->debugLogger()->write('noElements found');
// 				$group = reset($dataFlowContext->getAttribute('GroupBasedWigiiApiClient')->getGroupList()->getListIterator());
// 				$group = $group->getDbEntity();
// 				$okJsCode = 'update("mainDiv/'.$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/navigate/folder/".$group->getId().'");';
// 				//$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/" : "").$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/navigate/folder/".$group->getId());
// 				if ($exec->getIsUpdating()) {
// 					echo ExecutionServiceImpl :: answerRequestSeparator;
// 					echo "confirmationDialog";
// 					echo ExecutionServiceImpl :: answerParamSeparator;
// 				}
// 				$this->wigiiExecutor->openAsMessage("confirmationDialog", 300, $transS->t($p, "noResultFound"), $transS->t($p, "noElementMatchSearchCriteria"), $okJsCode);
// 				$exec->addJsCode('$("#workZone").empty();');
			}
		}
		$this->debugLogger()->write('endOfStream');
	}
	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->startOfStream($dataFlowContext);
		$this->processDataChunk($data, $dataFlowContext);
		$this->endOfStream($dataFlowContext);
	}	
}