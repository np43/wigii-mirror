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
 * An ElementPList which supports grouping, multiple selection and pagination.
 * Created by Medair (CWE) on 28.09.2017
 * Refactored by Medair (CWE) on 29.09.2017 to manage correct sorting if group by is on a range of values.
 */
abstract class ElementPGroupableSelectablePagedListImpl extends ElementPListWebImplWithWigiiExecutor {
    
    // Object lifecycle
    
    public function reset($wigiiExecutor, $listContext){
        $this->setWigiiExecutor($wigiiExecutor);
        $this->setListContext($listContext);
        $this->refreshGroupByAndSortByInfo();
        return $this;
    }
    protected function refreshGroupByAndSortByInfo() {
    	$listContext = $this->getListContext();
    	$this->_isGroupedBy = ($listContext->getGroupBy() != null && $listContext->getGroupBy() !== 'null');
    	$this->crtGroupByValue = $listContext->getGroupByItemCurrentValue();
    	$this->_isSorted = ($listContext->getSortedBy()!=null);
    }
    
    // Configuration
    
    private $pageSize;
    public function setPageSize($size){ $this->pageSize = $size; }
    protected function getPageSize(){ return $this->pageSize; }
    private $desiredPage;
    public function setDesiredPage($page){ $this->desiredPage = $page; }
    protected function getDesiredPage(){ return $this->desiredPage; }
    
    private $doOnlyRows = false;
    private $doOnlyRowsContent = false;
    public function doOnlyRows($doOnlyRowsContent = false){
        $this->doOnlyRows = true;
        $this->doOnlyRowsContent = $doOnlyRowsContent;
    }
    public function isDoOnlyRows() {return ($this->doOnlyRows == true);}
    public function getDoOnlyRowsContent() {return $this->doOnlyRowsContent;}
    public function getOnlyRows(){
        return $this->doOnlyRows;
    }
    
        
    // Dependency injection
    
    private $_debugLogger;
    private function debugLogger() {
        if (!isset ($this->_debugLogger)) {
            $this->_debugLogger = DebugLogger :: getInstance("ElementPGroupableSelectablePagedListImpl");
        }
        return $this->_debugLogger;
    }	
    
	private $trm_;
	protected function getTRM(){
		if(!isset($this->trm_)){
			$this->trm_ = $this->createTRMInstance();
		}
		return $this->trm_;
	}
	protected function createTRMInstance(){
		return $this->getWigiiExecutor()->createTRM(null, false, false, false, true, false, true);
	}
	public function setTRM($var){ $this->trm_ = $var; }

	private $dflowS;
	/**
	 * Injects a DataFlowService to be used
	 * @param DataFlowService $dataFlowService
	 */
	public function setDataFlowService($dataFlowService)
	{
	    $this->dflowS = $dataFlowService;
	}
	/**
	 * Gets the injected DataFlowService
	 * @return DataFlowService
	 */
	protected function getDataFlowService()
	{
	    // autowired
	    if(!isset($this->dflowS))
	    {
	        $this->dflowS = ServiceProvider::getDataFlowService();
	    }
	    return $this->dflowS;
	}
	
	private $fieldList;
	public function setFieldList($fieldList){$this->fieldList = $fieldList;}
	protected function getFieldList(){ return $this->fieldList; }
	private $p;
	public function setP($p){$this->p = $p;}
	protected function getP(){ return $this->p; }
	private $exec;
	public function setExec($exec){$this->exec = $exec;}
	protected function getExec(){ return $this->exec; }

	
	// Shared state
	
	private $_isGroupedBy;
    protected function isGroupedBy() {return $this->_isGroupedBy;}
	private $_isGroupedByRange=null;
	protected function isGroupedByRange() {return $this->_isGroupedByRange;}
	private $crtGroupByValue;
	protected function getCrtGroupByValue() {return $this->crtGroupByValue;}
	private $groupByValueChanged;
	protected function hasGroupByValueChanged() {return $this->groupByValueChanged;}
	private $_isSorted;
	protected function isSorted() {return $this->_isSorted;}
	
	private $elementIds; //array which stores the elements ids already added
	public function getTotalElementsIds(){
		return $this->elementIds;
	}
	private $globalNb; //numbering for manual paging
	public function getTotalNumberOfElements(){
		return $this->globalNb;
	}
	private $nb;
	public function getNumberOfElementsProcessed(){
		return $this->nb;
	}
	
	private $elementPTransformationDFCtx=null;
	
	// ElementPList implementation
	
	public function addElementP($elementP) {
	    // opens transformation data flow if not yet open
	    if(!isset($this->elementPTransformationDFCtx)) {
	        $this->elementPTransformationDFCtx = $this->getDataFlowService()->startStream($this->getP(), $this->createElementPTransformationFlow($elementP));
	    }
	    // runs the transformation
	    $this->getDataFlowService()->processDataChunk($elementP, $this->elementPTransformationDFCtx);
	}	
	
	/**
	 * Creates the ElementP transformation flow to pre-work incoming ElementP instances before HTML rendering
	 * @param ElementP $elementP first incoming ElementP that can be used as an example to condition the transformation flow.
	 * Other contextual information can be get from the injected Principal, ListContext, ExecutionService and WigiiExecutor.
	 * @return DataFlowActivitySelectorList a data flow activity selector list which transforms incoming ElementP instances towards outgoing ElementP instances.
	 */
	protected function createElementPTransformationFlow($elementP) {
	   // creates an empty transformation flow.    
	   $returnValue = DataFlowActivitySelectorListArrayImpl::createInstance();
	   // Medair(CWE) 26.09.2017: if grouped by range of values and list is sorted, then re-sorts the list to make sure their appear in the right order.
	   $returnValue->addDataFlowActivitySelectorInstance(dfas('CallbackDFA',
	       'setProcessDataChunkCallback',CallableObject::createInstance('sortGroupedElementPCallback',$this),
	       'setEndOfStreamCallback',function($callbackDFA){
	           $groupedByRangeDFCtx = $callbackDFA->getValueInContext('groupedByRangeDFCtx');
	           if(isset($groupedByRangeDFCtx)) {
	               $callbackDFA->getDataFlowContext()->getDataFlowService()->endStream($groupedByRangeDFCtx);
	               $this->debugLogger()->logEndOperation('sortBy groupBy range');
	           }
	       }
	   ));
	   
	   // Entry point ready to welcome additional transformation steps on the flow of incoming ElementP.
	   // $returnValue->addDataFlowActivitySelectorInstance(dfas('...'));
	   
	   // appends the standard bridge to HTML rendering by calling the abstract doAddElementP method
	   $returnValue->addDataFlowActivitySelectorInstance(dfas('CallbackDFA','setProcessDataChunkCallback',CallableObject::createInstance('doAddElementPCallback',$this)));
	   return $returnValue;
	}

	/**
	 * DataFlowActivity callback which correctly sorts a grouped set of Elements.
	 */	
	public function sortGroupedElementPCallback($elementP, $callbackDFA) {
	    $element = $elementP->getElement();
	    $parentCallbackDFA = $callbackDFA;
	    // if grouped by range of values and list is sorted, then re-sorts the list to make sure their appear in the right order.
	    if($this->isGroupedBy() && $this->isSorted()) {
	        $groupByFs = $this->getListContext()->getGroupByItemFieldSelector();
	        $sortByFs = $this->getListContext()->getSortByItemFieldSelector();
	        // if not known yet, guesses if group is by range
	        if($this->_isGroupedByRange===null) {
	            // grouped by range if formatValueFromFS(doRegroupSimilarValue=true) != formatValueFromFS(doRegroupSimilarValue=false)
	            $this->_isGroupedByRange = ($this->getTRM()->formatValueFromFS($groupByFs, $element, true) != $this->getTRM()->formatValueFromFS($groupByFs, $element));
	        }
	        
	        // if group by range, then bufferizes the incoming elements to sort them
	        if($this->isGroupedByRange() && $groupByFs && $sortByFs) {
	            $crtGroupByValue = $this->getTRM()->formatValueFromFS($groupByFs, $element, true);
	            $dfS = $parentCallbackDFA->getDataFlowContext()->getDataFlowService();
	            $groupedByRangeDFCtx = $parentCallbackDFA->getValueInContext('groupedByRangeDFCtx');
	            // if group by value changed, then closes previous data flow
	            if(isset($groupedByRangeDFCtx) && $groupedByRangeDFCtx->getAttribute('crtGroupByValue') != $crtGroupByValue) {
	                $dfS->endStream($groupedByRangeDFCtx);
	                $parentCallbackDFA->setValueInContext('groupedByRangeDFCtx',null);
	                unset($groupedByRangeDFCtx);
	            }
	            // opens transformation dataflow to sort elements in same group by range
	            if(!isset($groupedByRangeDFCtx)) {
	                $this->debugLogger()->logBeginOperation('sortBy groupBy range');
	                $groupedByRangeDFCtx = $dfS->startStream($this->getP(), dfasl(
	                    /* boxes ElementP instances into StdClasses to allow sorting */
	                    dfas('CallbackDFA','setProcessDataChunkCallback',function($data,$callbackDFA) use($sortByFs){
	                        $element = $data->getElement();
	                        if($sortByFs->isElementAttributeSelector()) $sortingKey = $element->getAttribute($sortByFs);
	                        else $sortingKey = $element->getFieldValue($sortByFs->getFieldName(),$sortByFs->getSubfieldName());
	                        
	                        $callbackDFA->writeResultToOutput((object)array(
	                            'elementId'=>$element->getId(),
	                            'sortingKey'=>$sortingKey,
	                            'elementP'=>$data
	                        ));
	                    }),
	                    /* sorts ElementP by sorting key */
	                    dfas('FilterDuplicatesAndSortDFA',
	                        'setObjectSelectorMethod', function($data){
	                           return $data->elementId;
	                        },
	                        'setObjectSortByMethod', function($data){
	                           return $data->sortingKey;
	                        },
	                        'setSortOrder',($this->getListContext()->isAscending()?1:2)
	                    ),
	                    /* unboxes ElementP and pushes it further */
	                    dfas('CallbackDFA','setProcessDataChunkCallback',function($data,$callbackDFA) use($parentCallbackDFA){
	                        $parentCallbackDFA->writeResultToOutput($data->elementP);
	                    })
	                ));
	                $groupedByRangeDFCtx->setAttribute('crtGroupByValue', $crtGroupByValue);
	                $parentCallbackDFA->setValueInContext('groupedByRangeDFCtx',$groupedByRangeDFCtx);
	            }
	            // pushes element into transformation data flow
	            $dfS->processDataChunk($elementP, $groupedByRangeDFCtx);
	        }
	        // pushes element further
	        else $parentCallbackDFA->writeResultToOutput($elementP);
	    }
	    else $parentCallbackDFA->writeResultToOutput($elementP);
	}
	
	
	private $shouldRecalculateMultipleElementState = false;
	/**
	 * DataFlowActivity callback which receives a fetched element from the database and pre-works it before HTML rendering.
	 * Calls subclass doAddElementP implementation to do the HTML rendering.
	 */	
    public function doAddElementPCallback($elementP, $callbackDFA){
		$this->globalNb++;
		$element = $elementP->getElement();
		$elId = $element->getId();
		if($this->elementIds[$elId]!=null) throw new ListException("Id: $elId already exist in the ElementListFrame", ListException::ALREADY_EXIST);
		$this->elementIds[$elId] = $elId;

		//manual paging:
		if($this->getPageSize()!= 0 && $this->getDesiredPage()!=0){
			if(	($this->globalNb <= (($this->getDesiredPage()-1)*$this->getPageSize())) ||
				($this->globalNb > ($this->getDesiredPage()*$this->getPageSize()))
				){
				//do nothing
				return;
			}
		}

		$this->nb++;
		$this->getTRM()->reset($element);

		// groupBy filter management
		if($this->doOnlyRows){
		    if(!is_array($this->doOnlyRows)) $this->doOnlyRows = array();
		    $this->doOnlyRows[] = $elementP->getId();
		}
		if(!$this->doOnlyRowsContent){
		    if($this->isGroupedBy()){
		        $fieldSelector = $this->getListContext()->getGroupByItemFieldSelector();
		        $crtGroupByValue = null;
		        if($fieldSelector){
		            $crtGroupByValue = $this->getTRM()->formatValueFromFS($fieldSelector, $element, true);
		        }
		        //We put all groupBy in upper case in case of a find duplicate
		        if($this->getListContext()->isGroupByOnlyDuplicates()){
		            $crtGroupByValue = trim(strtoupper($crtGroupByValue));
		        }
		        if($this->crtGroupByValue != $crtGroupByValue) {
		            $this->crtGroupByValue = $crtGroupByValue;
		            $this->getListContext()->setGroupByItemCurrentValue($crtGroupByValue);
		            $this->groupByValueChanged = true;
		        }
		        else $this->groupByValueChanged = false;
		    }
		}
		
		$elementEnableState = $this->computeEnableElementState($elementP);
		$elementState = $element->getStateAsInt();
		// updates list context multiple selection with element state
		if($this->getListContext()->isInMultipleSelection($element->getId())) {
		    $this->getListContext()->updateElementStateInMultipleSelection($element->getId(), $elementEnableState, $elementState, false);
		    $this->shouldRecalculateMultipleElementState = true;
		}
		
		//store in the context each duplicates ids to be able to click on the select all checkbox
		if($this->getListContext()->isGroupByOnlyDuplicates()){
		    $isWritable = false;
		    if($elementP->getRights() && $elementP->getRights()->canWriteElement()) $isWritable = true;
		    $this->getListContext()->addDuplicatesId($elementP->getId(), $isWritable, $elementEnableState, $elementState);
		}
		
		// calls subclass HTML rendering method
		$this->doAddElementP($elementP);
	}
	/**
	 * Does the real work of displaying an element in the list
	 */
	protected abstract function doAddElementP($elementP);

	
	private $enableElementStateConfigCache = null;
	/**
	 * Returns an integer encoding the "enableElementState" menu
	 */
	protected function computeEnableElementState($elementP) {
		$p = $this->getP();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		// creates config cache if not already done
		if(!isset($this->enableElementStateConfigCache)) {
			$m =  $this->getExec()->getCrtModule();			
			$this->enableElementStateConfigCache = array(
				'Element_enableLockedStatus' => $configS->getParameter($p, $m, 'Element_enableLockedStatus')=="1",
				'Element_enableBlockedStatus' => $configS->getParameter($p, $m, 'Element_enableBlockedStatus')=="1",
				'Element_enableImportant1Status' => $configS->getParameter($p, $m, 'Element_enableImportant1Status')=="1",
				'Element_enableImportant2Status' => $configS->getParameter($p, $m, 'Element_enableImportant2Status')=="1",
				'Element_enableFinalizedStatus' => $configS->getParameter($p, $m, 'Element_enableFinalizedStatus')=="1",
				'Element_enableApprovedStatus' => $configS->getParameter($p, $m, 'Element_enableApprovedStatus')=="1",
				'Element_enableDismissedStatus' => $configS->getParameter($p, $m, 'Element_enableDismissedStatus')=="1",
				'Element_enableArchivedStatus' => $configS->getParameter($p, $m, 'Element_enableArchivedStatus')=="1",
				'Element_enableDeprecatedStatus' => $configS->getParameter($p, $m, 'Element_enableDeprecatedStatus')=="1",
				'Element_enableHiddenStatus' => $configS->getParameter($p, $m, 'Element_enableHiddenStatus')=="1",
				/* conditional actions */
			    'Element_enableActionDelete' => $configS->getParameter($p, $m, 'enableDeleteOnlyForAdmin')=="1" || ((string)$configS->getParameter($p, $m, 'Element_beforeDeleteExp')==="0"),
			    'Element_enableActionOrganize' => ((string)$configS->getParameter($p, $m, 'Element_Blocked_enableSharing')!="")
			);
		}

		// initializes elementP with default policy
		$elementP->enableElementState_locked($this->enableElementStateConfigCache['Element_enableLockedStatus']);
		$elementP->enableElementState_blocked($this->enableElementStateConfigCache['Element_enableBlockedStatus']);
		$elementP->enableElementState_important1($this->enableElementStateConfigCache['Element_enableImportant1Status']);
		$elementP->enableElementState_important2($this->enableElementStateConfigCache['Element_enableImportant2Status']);
		$elementP->enableElementState_finalized($this->enableElementStateConfigCache['Element_enableFinalizedStatus']);
		$elementP->enableElementState_approved($this->enableElementStateConfigCache['Element_enableApprovedStatus']);
		$elementP->enableElementState_dismissed($this->enableElementStateConfigCache['Element_enableDismissedStatus']);
		$elementP->enableElementState_archived($this->enableElementStateConfigCache['Element_enableArchivedStatus']);
		$elementP->enableElementState_deprecated($this->enableElementStateConfigCache['Element_enableDeprecatedStatus']);
		$elementP->enableElementState_hidden($this->enableElementStateConfigCache['Element_enableHiddenStatus']);
		/* conditional actions */
		$elementP->enableElementAction_delete($this->enableElementStateConfigCache['Element_enableActionDelete'] && (!$elementP->getRights()->canModify() || ((string)$configS->getParameter($p, $elementP->getElement()->getModule(), 'Element_beforeDeleteExp')==="0")));
		$elementP->enableElementAction_organize($this->enableElementStateConfigCache['Element_enableActionOrganize'] && ((string)$configS->getParameter($p, $elementP->getElement()->getModule(), 'Element_Blocked_enableSharing')!=="0"));
		
		// updates policy using the ElementPolicyEvaluator
		$policyEval = $this->getElementPolicyEvaluator();
		if(isset($policyEval)) $policyEval->computeEnableElementState($p, $elementP);

		// converts the policy to a binary representation
		return $elementP->getEnableElementStateAsInt();
	}


	public function actOnBeforeAddElementP(){
		$this->globalNb = 0;
		$this->shouldRecalculateMultipleElementState = false;
		$this->nb = 0;
	}

	public function actOnFinishAddElementP($numberOfObjects){
	    // Closes transformation data flow
	    if(isset($this->elementPTransformationDFCtx)) {
	        $this->getDataFlowService()->endStream($this->elementPTransformationDFCtx);
	        unset($this->elementPTransformationDFCtx);
	    }
        // Multiple dialog management
		if($this->getListContext()->isMultipleSelection()){
			if($this->shouldRecalculateMultipleElementState) $this->getListContext()->computeMultipleElementStateInt();
			if(!$this->doOnlyRows){
				$this->getExec()->addRequests("multipleDialog/".$this->getExec()->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$this->getExec()->getCrtModule()->getModuleName()."/element/displayMultipleDialog");
			}		
		}
	}
}

