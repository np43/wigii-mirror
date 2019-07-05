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
 * Wigii Light Client implementation.
 * A web client which answers to update urls of type:
 * /NoWigiiNamespace/Home/c					: starts and displays accessible namespaces
 * /WigiiNamespace/Module/c/item/all		: shows a list view with all elements in module
 * /WigiiNamespace/Module/c/activity/item/all: shows a light view activity with all elements in module
 * /WigiiNamespace/Module/c/item/id			: displays element detail 
 * /WigiiNamespace/Module/c/item/id/edit 	: displays edit element form
 * /WigiiNamespace/Module/c/item/id/copy 	: displays copy element form
 * /WigiiNamespace/Module/c/folder/id 		: shows a list view on folder
 * /WigiiNamespace/Module/c/activity/folder/id: shows a light view activity on folder
 * /WigiiNamespace/Module/c/folder/id/add 	: displays add element form
 * /WigiiNamespace/Module/c/groupPanel		: shows an expanded groupPanel
 * /WigiiNamespace/Module/c/list			: list view configuration Activity
 * /WigiiNamespace/Module/c/filter			: list filter Activity
 * /WigiiNamespace/Module/c/user			: user menu Activity
 * /WigiiNamespace/Module/c 				: displays root folders of module
 * /WigiiNamespace/Home/c					: display accessible modules in namespace
 * 
 * Created by CWE on October 26th 2015.
 * Modified by Medair (LMA) in December 2016.
 * Refactored by Wigii.org (CWE) on 17.06.2019 to direct calls from WigiiApi.js
 */
class LightClientFormExecutor extends WebServiceFormExecutor {
	private $_debugLogger;
	private $_executionSink;
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("LightClientFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("LightClientFormExecutor");
		}
		return $this->_executionSink;
	}	
	
	private $rootPincipal; //this is needed for the auto sharing to some groups defined in configuration
	public function setRootPrincipal($rootP){ $this->rootPrincipal = $rootP; }
	protected function getRootPrincipal(){ return $this->rootPrincipal; }
	
	protected function createTrmInstance() {
		$trm = LightClientTRM::createInstance();
		$trm->setConfigService($this->getConfigService());
		$trm->clear();
		$trm->setOutputEnabled(false);
		return $trm;
	}	

	/**
	 * @return String returns the name of the current flow in which this element is evaluated.
	 * One of ELEMENT_FLOW_READ, ELEMENT_FLOW_ADD, ELEMENT_FLOW_EDIT, ELEMENT_FLOW_DELETE, ELEMENT_FLOW_COPY, ELEMENT_FLOW_DATAFLOW, ELEMENT_FLOW_MULTIPLE_ADD, ELEMENT_FLOW_MULTIPLE_EDIT, ELEMENT_FLOW_MULTIPLE_DELETE, ELEMENT_FLOW_MULTIPLE_COPY, ELEMENT_FLOW_UNSPECIFIED
	 */
	protected function getCurrentFlowName() {
		$returnValue = null;
		$element = $this->getRecord();
		if($element instanceof Element) $returnValue = $element->getDynamicAttributeValue('ctlCurrentFlow');
		return (is_null($returnValue) ? ElementEvaluator::ELEMENT_FLOW_UNSPECIFIED : $returnValue);
	}
	private function setCurrentFlowNameOnElement($element, $flowName) {		
		$element->setDynamicAttribute('ctlCurrentFlow', ElementDynAttrFixedValueImpl::createInstance($flowName), false);
	}
	
	// Light Client implementation
	
	public function isMinimalPrincipalAuthorized() {return true;}
	public function processAndEnds($p,$exec) {	    
	    // stateful service and authorized to cross-origin calls (only with Wigii protocol)
	    if($exec->getIsUpdating()) {
	       header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
	       header("Access-Control-Allow-Credentials: true");
	    }
	    else throw new FormExecutorException('Light client can only be called through update requests', FormExecutorException::UNAUTHORIZED);		
	    // sets language
	    $transS = ServiceProvider::getTranslationService();
	    $exec->addJsCode("crtLanguage = '" . $transS->getLanguage(true) . "';
crtLang = '" . $transS->getLanguage() . "';
crtWigiiNamespaceUrl = '" . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "';
crtModuleName = '" . $exec->getCrtModule()->getModuleUrl() . "';
");
	    // 1. trie to handle Home page
	    if($exec->getCrtWigiiNamespace()->getWigiiNamespaceName()=='' &&
	       $exec->getCrtModule()->isHomeModule() && 
	       !$exec->hasCrtParameters()) $this->startLightClient($p, $exec);
	    // 2. tries to handle list request
	    elseif($this->processListRequest($p, $exec)) return;
		// 3. tries to handle element request
		elseif($this->processElementRequest($p, $exec)) return;
		// else Unsupported request
		else throw new FormExecutorException('Unsupported request '.$exec->getCrtRequest(), FormExecutorException::UNSUPPORTED_OPERATION);		
	}
	
	/**
	 * Starts Light Client
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 */
	protected function startLightClient($p,$exec) {
	    FormExecutorException::throwNotImplemented();
	}
	
	/**
	 * Process list request
	 * /item/all  : shows a list view with all elements in module
	 * /activity/item/all: shows a light view activity with all elements in module
	 * /folder/id : shows a list view on folder
	 * /activity/folder/id: shows a light view activity on folder
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @return boolean true if request has been recognized and processed, else false.
	 */
	protected function processListRequest($p,$exec) {
	    $returnValue = false;
	    // detects list request
	    $args = $exec->getCrtParameters();
	    if(empty($args)) $nArgs = 0; else $nArgs = count($args);
	    if($nArgs > 0) {
	        $id = ValueObject::createInstance();
	        $activity = ValueObject::createInstance();
	        
	        // /item/all
	        if(arrayMatch($args,'item','all')) {
	            $activity->setValue('lightView');
	            $groupLogExp = lxAllGroups($exec->getCrtWigiiNamespace(), $exec->getCrtModule());
	            $returnValue=true;
	        }
	        // /activity/item/all
	        elseif(arrayMatch($args,$activity,'item','all')) {
	            $groupLogExp = lxAllGroups($exec->getCrtWigiiNamespace(), $exec->getCrtModule());
	            $returnValue=true;
	        }
	        // /folder/id
	        elseif(arrayMatch($args,'folder',$id)) {
	            $activity->setValue('lightView');
	            $groupLogExp = lxEq(fs('id'),$id->getValue());
	            $returnValue=true;
	        }
	        // /activity/folder/id
	        elseif(arrayMatch($args,$activity,'folder',$id)) {
	            $groupLogExp = lxEq(fs('id'),$id->getValue());
	            $returnValue=true;
	        }
	        
	        // request is recognized. OK to go further
	        if($returnValue) {
	            // gets light view activity
	            $activity = Activity::createInstance($activity->getValue());
                // gets list context
	            $lc = $this->getWigiiExecutor()->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $activity->getActivityName());
	            if(!$lc->isCrtViewLight()) $lc->setCrtViewToLight();
	            // creates and configures ElementPList
	            $elementPList = ElementPListRowsForLightClientImpl::createInstance($this->getWigiiExecutor(), $lc);
	            
	            FormExecutorException::throwNotImplemented();
	            $elementPList->configureForActivity($p, $exec, $activity);	            
	            // selects elements in db and renders html
	            $elementPList->actOnBeforeAddElementP();
	            ServiceProvider::getElementService()->getSelectedElementsInGroups($p, lxInGR($groupLogExp), $elementPList, $lc);
	            $elementPList->actOnFinishAddElementP($lc->getTotalNumberOfObjects());
	        }
	    }	    
	    return $returnValue;
	}
	
	private $groupIdInWhichToAdd;
	/**
	 * Process Element request
	 * /item/id  : displays element detail
	 * /item/id/edit : displays element edit form
	 * /item/id/copy : displays element copy form
	 * /folder/id/add : displays element add form
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @return boolean true if request has been recognized and processed, else false.
	 */
	protected function processElementRequest($p,$exec) {
		$returnValue = false;
		// detects element request
		$args = $exec->getCrtParameters();
		if(empty($args)) $nArgs = 0; else $nArgs = count($args);
		if($nArgs > 0) {
			$id = ValueObject::createInstance();			
			// /item/id
			if(arrayMatch($args,'item',$id)) {
				// fetches Element
				$elementP = $this->fetchElement($p, $exec, $id->getValue());
				$element = $elementP->getElement();								
				
				// configures rendering process
				$configS = $this->getConfigService();
				$module = $element->getModule();
				$idAnswer = $exec->getIdAnswer();
				if(is_null($idAnswer)) $idAnswer="mainDiv";
				$totalWidth = 0 + $configS->getParameter($p, $module, "elementTotalWidth");
				$labelWidth = 0 + $configS->getParameter($p, $module, "elementLabelWidth");
				$formId = $idAnswer.'_item_'.$element->getId();
				$isWritable = (!is_null($elementP->getRights()) && 
						$elementP->getRights()->canWriteElement() && 
						!($element->isState_blocked() || $elementP->isParentElementState_blocked())
				);
				$this->setCurrentFlowNameOnElement($element, ElementEvaluator::ELEMENT_FLOW_READ);
				$this->setIsDialog(false);
				$this->setFormId($formId);
				$this->setSubmitUrl(null);
				$this->setRecord($element);
				$this->setLabelWidth($labelWidth);
				$this->setTotalWidth($totalWidth);
				$trm = $this->getTrm();
				$trm->setP($p);
				$detailR = LightDetailRenderer::createInstance($this->getFormId(), $trm, $totalWidth, $labelWidth);
				$detailR->setP($p);
				$detailR->setRecordIsWritable($isWritable);
				$this->setDetailRenderer($detailR);
				$trm->setDetailRenderer($detailR);
				
				// launches Form resolution
				$this->ResolveForm($p, $exec, 'start');
				
				$returnValue=true;
			}			
			// /item/id/edit
			elseif(arrayMatch($args,'item',$id,'edit')) {			    
				// fetches Element
				$elementP = $this->fetchElement($p, $exec, $id->getValue());
				if(!$elementP->getRights()->canWriteElement()) throw new AuthorizationServiceException('No rights to edit element '.$elementP->getId(), AuthorizationServiceException::UNAUTHORIZED);
				$element = $elementP->getElement();
				
				// configures rendering process
				$configS = $this->getConfigService();
				$module = $element->getModule();
				$idAnswer = $exec->getIdAnswer();
				$submitUrl = $exec->getCrtRequest();
				if(is_null($idAnswer)) {
					$idAnswer="mainDiv";
					$submitUrl = 'mainDiv/'.$submitUrl;
				}
				$totalWidth = 0 + $configS->getParameter($p, $module, "elementTotalWidth");
				$labelWidth = 0 + $configS->getParameter($p, $module, "elementLabelWidth");
				$formId = $idAnswer.'_item_'.$element->getId().'_edit';
				$isWritable = (!is_null($elementP->getRights()) &&
						$elementP->getRights()->canWriteElement() &&
						!($element->isState_blocked() || $elementP->isParentElementState_blocked())
						);
				$this->setCurrentFlowNameOnElement($element, ElementEvaluator::ELEMENT_FLOW_EDIT);
				$this->setIsDialog(false);
				$this->setFormId($formId);
				$this->setSubmitUrl($submitUrl);
				$this->setRecord($element);
				$this->setLabelWidth($labelWidth);
				$this->setTotalWidth($totalWidth);
				$trm = $this->getTrm();
				$trm->setP($p);
				$formR = LightFormRenderer::createInstance($this, $this->getFormId(), $trm, $totalWidth, $labelWidth);
				$formR->setP($p);
				$this->setFormRenderer($formR);
				$trm->setFormRenderer($formR);
				
				// launches Form resolution
				$state = "start";
				if ($_POST["action"] != null) $state = addslashes($_POST["action"]);
				$this->ResolveForm($p, $exec, $state);
				
				$returnValue=true;
			}
			// /item/id/copy
			elseif(arrayMatch($args,'item',$id,'copy')) {
				FormExecutorException::throwNotImplemented();
				$returnValue=true;
			}
			// /folder/id/add
			elseif(arrayMatch($args,'folder',$id,'add')) {
			    $this->groupIdInWhichToAdd = $id->getValue();
			    $groupP = ServiceProvider::getGroupAdminService()->getGroup($p,$this->groupIdInWhichToAdd);
			    if(is_null($groupP) || is_null($groupP->getRights())) throw new AuthorizationServiceException('Group '.$this->groupIdInWhichToAdd.' is not accessible.', AuthorizationServiceException::NOT_FOUND);
			    if(!$groupP->getRights()->canWriteElement()) throw new AuthorizationServiceException('Cannot insert element in group '.$this->groupIdInWhichToAdd, AuthorizationServiceException::UNAUTHORIZED);
			    
			    // creates new Element			    
			    $element = Element :: createInstance($exec->getCrtModule());
			    
			    // configures rendering process
			    $configS = $this->getConfigService();
			    $module = $element->getModule();
			    $idAnswer = $exec->getIdAnswer();
			    $submitUrl = $exec->getCrtRequest();
			    if(is_null($idAnswer)) {
			        $idAnswer="mainDiv";
			        $submitUrl = 'mainDiv/'.$submitUrl;
			    }
			    $totalWidth = 0 + $configS->getParameter($p, $module, "elementTotalWidth");
			    $labelWidth = 0 + $configS->getParameter($p, $module, "elementLabelWidth");
			    $formId = $idAnswer.'_folder_'.$this->groupIdInWhichToAdd.'_add';
			    $isWritable = true;
			    $this->setCurrentFlowNameOnElement($element, ElementEvaluator::ELEMENT_FLOW_ADD);
			    $this->setIsDialog(false);
			    $this->setFormId($formId);
			    $this->setSubmitUrl($submitUrl);
			    $this->setRecord($element);
			    $this->setLabelWidth($labelWidth);
			    $this->setTotalWidth($totalWidth);
			    $trm = $this->getTrm();
			    $trm->setP($p);
			    $formR = LightFormRenderer::createInstance($this, $this->getFormId(), $trm, $totalWidth, $labelWidth);
			    $formR->setP($p);
			    $this->setFormRenderer($formR);
			    $trm->setFormRenderer($formR);
			    
			    // launches Form resolution
			    $state = "start";
			    if ($_POST["action"] != null) $state = addslashes($_POST["action"]);
			    $this->ResolveForm($p, $exec, $state);
			    
			    $returnValue=true;
			}
		}
		return $returnValue;
	}
	
	
	// FormExecutor implementation
	
	protected function doSpecificCheck($p, $exec) {
		/* no specific check */
	}
	
	protected function actOnCheckedRecord($p, $exec) {
		switch($this->getCurrentFlowName()) {			
			case ElementEvaluator::ELEMENT_FLOW_EDIT:
			case ElementEvaluator::ELEMENT_FLOW_ADD:
				$this->persistElement($p, $exec, false);
				break;
		}
	}
	
	protected function doRenderForm($p, $exec) {	    
		switch($this->getCurrentFlowName()) {
			case ElementEvaluator::ELEMENT_FLOW_READ:
				$this->displayElementDetail($p, $exec);
				break;
			case ElementEvaluator::ELEMENT_FLOW_EDIT:
			case ElementEvaluator::ELEMENT_FLOW_ADD:
				$this->displayElementForm($p, $exec);
				break;
		}
	}
	
	protected function getGroupIdInWhichToAdd($p, $exec){
	    return $this->groupIdInWhichToAdd;
	}
	/**
	 * @return GroupListAdvancedImpl containing the group in which to add the element
	 */
	public function getGroupInWhichToAdd($p, $exec){
	    $groupAS = ServiceProvider::getGroupAdminService();
	    $groupPList = GroupListAdvancedImpl::createInstance();
	    $groupAS->getGroupsWithoutDetail($p, array($this->getGroupIdInWhichToAdd($p, $exec)=>$this->getGroupIdInWhichToAdd($p, $exec)), $groupPList);
	    return $groupPList;
	}
	
	// Main rendering functions
		
	public function displayElementDetail($p,$exec) {
		$trm = $this->getTrm();
		$detailR = $trm->getDetailRenderer();
		
		$detailR->resetJsCodeAfterShow();
		$trm->displayRemainingDetails();
		$trm->put('<div class="clear"></div>');
		echo $trm->getHtmlAndClean();
		$exec->addJsCode($detailR->getJsCodeAfterShow());		
	}	
	public function displayElementForm($p,$exec) {
		$trm = $this->getTrm();
		$formR = $trm->getFormRenderer();
		
		$formR->resetJsCodeAfterShow();
		$trm->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), false);
		$trm->displayRemainingForms();
		$trm->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog(), $trm->t("cancel"));
		$trm->addJsCodeAfterFormIsShown($this->getFormId());
    	echo $trm->getHtmlAndClean();    	
		$exec->addJsCode($formR->getJsCodeAfterShow());	
	}
	
	// Utilities
	
	private $oldRecord;
	/**
	 * Fetches old record from the DB in order to :
	 * - update Files and Emails hidden fields
	 * - enable FuncExp on element save to have access to old record.
	 * @param Principal $p current principal editing the element
	 * @param ExecutionService $exec current ExecutionService
	 * @param int $id the ID of the element beeing edited
	 * @return Element the old element as currently in the database.
	 */
	public function fetchOldRecord($p, $exec, $id){
	    if(!isset($this->oldRecord) || $this->oldRecord->getId()!=$id){
	        $bag = FormBag::createInstance();
	        $ffl = FormFieldList::createInstance($bag);
	        $oldElement = Element::createInstance($exec->getCrtModule(), $ffl, $bag);
	        $oldElement->setId($id);
	        
	        $elS = ServiceProvider::getElementService();
	        $elS->fillElement($p, $oldElement);
	        
	        $formFieldList = $oldElement->getFieldList();
	        //add content on Files with htmlArea
	        $fsl = FieldSelectorListArrayImpl::createInstance();
	        foreach($formFieldList->getListIterator() as $field){
	            if($field->getDataType()!=null){
	                if($field->getDataType()->getDataTypeName()=="Files"){
	                    $fieldXml = $field->getXml();
	                    if($fieldXml["htmlArea"]=="1"){
	                        $fsl->addFieldSelector($field->getFieldName(), "content");
	                    }
	                }
	            }
	        }
	        if(!$fsl->isEmpty()){
	            $oldElement->setFieldList(FieldListArrayImpl::createInstance());
	            $elS->fillElement($p, $oldElement, $fsl);
	            $oldElement->setFieldList($formFieldList);
	        }
	        $this->oldRecord = $oldElement;
	    }
	    return $this->oldRecord;
	}
	
}



