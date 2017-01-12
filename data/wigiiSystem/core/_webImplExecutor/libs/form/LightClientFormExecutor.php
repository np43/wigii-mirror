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
 * Wigii Light Client implementation.
 * A web client which answers to direct urls of type:
 * /WigiiNamespace/Module/c/item/id			: displays element detail 
 * /WigiiNamespace/Module/c/item/id/edit 	: displays edit element form
 * /WigiiNamespace/Module/c/item/id/copy 	: displays copy element form
 * /WigiiNamespace/Module/c/folder 			: shows an expanded groupPanel
 * /WigiiNamespace/Module/c/folder/id 		: shows a listview                     
 * /WigiiNamespace/Module/c/folder/id/add 	: displays add element form
 * /WigiiNamespace/Module/c 				: displays root folders
 * /NoWigiiNamespace/Home/c					: displays accessible namespaces
 * /WigiiNamespace/Home/c					: display accessible modules in namespace
 * /WigiiNamespace/Module/c/list			: list view configuration Activity
 * /WigiiNamespace/Module/c/filter			: list filter Activity
 * /WigiiNamespace/Module/c/user			: user menu Activity
 * 
 * Created by CWE on October 26th 2015.
 * Modified by Medair (LMA) in December 2016.
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
	
	protected function createTrmInstance() {
		$trm = LightClientTRM::createInstance();
		$trm->setConfigService($this->getConfigService());
		$trm->clear();
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
		// 1. tries to handle element request
		if($this->processElementRequest($p, $exec)) return;
		// else Unsupported request
		else throw new FormExecutorException('Unsupported request '.$exec->getCrtRequest(), FormExecutorException::UNSUPPORTED_OPERATION);		
	}
	
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
				//$this->setCorrectionWidth(26);
				$this->setLabelWidth($labelWidth);
				$this->setTotalWidth($totalWidth);
				$trm = $this->getTrm();
				$trm->setP($p);
				$detailR = LightDetailRenderer::createInstance($this->getFormId(), $trm);
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
				//$this->setCorrectionWidth(26);
				$this->setLabelWidth($labelWidth);
				$this->setTotalWidth($totalWidth);
				$trm = $this->getTrm();
				$trm->setP($p);
				$formR = LightFormRenderer::createInstance($this, $this->getFormId(), $trm);
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
				FormExecutorException::throwNotImplemented();
				$returnValue=true;
			}
		}
		return $returnValue;
	}
	
	
	// FormExecutor implementation
	
	protected function doSpecificCheck($p, $exec) {
		// CWE 19.12.2016 stops execution to implement only rendering process for now
		$this->addStateError();
	}
	
	protected function actOnCheckedRecord($p, $exec) {
		/* to be implemented */
	}
	
	protected function doRenderForm($p, $exec) {
		switch($this->getCurrentFlowName()) {
			case ElementEvaluator::ELEMENT_FLOW_READ:
				$this->displayElementDetail($p, $exec);
				break;
			case ElementEvaluator::ELEMENT_FLOW_EDIT:
				$this->displayElementForm($p, $exec);
				break;
		}
	}
	
	// Main rendering functions
		
	public function displayElementDetail($p,$exec) {
		$trm = $this->getTrm();
		$detailR = $trm->getDetailRenderer();
		
		$detailR->resetJsCodeAfterShow();
		$trm->displayRemainingDetails();
		
		$exec->addJsCode($detailR->getJsCodeAfterShow());		
	}	
	public function displayElementForm($p,$exec) {
		$trm = $this->getTrm();
		$formR = $trm->getFormRenderer();
		
		$formR->resetJsCodeAfterShow();
		$trm->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), false);
		$formR->displayFormErrors();
		$trm->displayRemainingForms();
		$trm->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog(), $trm->t("cancel"));

		$trm->addJsCodeAfterFormIsShown($this->getFormId());
		$exec->addJsCode($formR->getJsCodeAfterShow());			
	}
}



