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
 * WebExecutor base implementation to handle Wigii http requests.
 * Supports GET and POST request,
 * Form management and File uploads,
 * Wigii update protocol and direct http request.
 */
class WebServiceFormExecutor extends FormExecutor implements WebExecutor {
	
	// Dependency injection
	
	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("WebServiceFormExecutor");
		}
		return $this->_debugLogger;
	}
	private $_executionSink;
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("WebServiceFormExecutor");
		}
		return $this->_executionSink;
	}
	
	private $wigiiExecutor;
	public function setWigiiExecutor($wigiiExecutor){$this->wigiiExecutor = $wigiiExecutor;}
	/**
	 * @return WigiiExecutor
	 */
	public function getWigiiExecutor(){return $this->wigiiExecutor; }
	
	private $configService;
	public function setConfigService($configService) {$this->configService = $configService;}
	/**
	 * @return ConfigService
	 */
	public function getConfigService() {return $this->configService;}
	
	
	// WebExecutor implementation
	
	public function isMinimalPrincipalAuthorized() {return false;}
	public function isPublicPrincipalAuthorized()  {return false;}
	
	public function processAndEnds($p,$exec) {
		// by default handles standard element request
		$this->processElementRequest($p, $exec);
	}
	
	/**
	 * Process standard Element request
	 * /item/id  : displays element detail
	 * /item/new : displays new element form
	 * /item/id/edit : displays element edit form
	 * /activity/name : displays activity form
	 * /view/id/name : displays element view  	 
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
			$name = ValueObject::createInstance();
			// /item/id
			if(arrayMatch($args,'item',$id)) {
				$this->displayElementDetail($p, $exec, $id->getValue());
				$returnValue=true;
			}
			// /item/new
			elseif(arrayMatch($args,'item','new')) {
				$this->displayNewElementForm($p, $exec);
				$returnValue=true;
			}			
			// /item/id/edit
			elseif(arrayMatch($args,'item',$id,'edit')) {
				$this->displayElementForm($p, $exec, $id->getValue());
				$returnValue=true;
			}
			// /activity/name
			elseif(arrayMatch($args,'activity',$name)) {
				$this->displayActivityForm($p, $exec, $name->getValue());
				$returnValue=true;
			}
			// /view/id/name
			elseif(arrayMatch($args,'view',$id,$name)) {
				$this->displayElementView($p, $exec, $id->getValue(), $name->getValue());
			}
		}
		return $returnValue;
	}
	/**
	 * Pushes Element detail HTML back to client. 
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @param Element|int $element Filled element to display or ID of element to fetch.
	 * @param WigiiBPLParameter $options a map of options for the display process.
	 */
	public function displayElementDetail($p,$exec,$element,$options=null) {
		/* not yet functional */
		FormExecutorException::throwNotImplemented();
		
		// fetches Element
		if($element instanceof ElementP) $element = $element->getElement();
		if(!($element instanceof Element)) $element = $this->fetchElement($p, $exec, $element, $options)->getElement();
		
		// configures rendering process
		$configS = $this->getConfigService();
		$module = $element->getModule();
		$idAnswer = $exec->getIdAnswer();
		
		$totalWidth = 0;
		if(isset($options)) $totalWidth = 0 + $options->getValue('elementTotalWidth');
		if($totalWidth<=0) $totalWidth = 0 + $configS->getParameter($p, $module, "elementTotalWidth");
		
		$labelWidth = 0;
		if(isset($options)) $labelWidth = 0 + $options->getValue('elementLabelWidth');
		if($labelWidth<=0) $labelWidth = 0 + $configS->getParameter($p, $module, "elementLabelWidth");
		
		$formId = null;
		if(isset($options)) $formId = $options->getValue('formId');
		if(is_null($formId)) $formId = $this->getFormId();
		if(is_null($formId)) $formId = $idAnswer.'_item_'.$element->getId();
		
		// renders element detail		
		$this->setFormId($formId);
		$this->setSubmitUrl(null);
		$this->setRecord($element);
		//$this->setCorrectionWidth(26);
		$this->setLabelWidth($labelWidth);
		$this->setTotalWidth($totalWidth);
		
		$detailR = $this->getDetailRenderer();
		$trm = $this->getTrm();
		$detailR->resetJsCodeAfterShow();
		$detailR->setP($p);
		$detailR->setRecordIsWritable(false);
		$trm->setDetailRenderer($detailR);
		$trm->displayRemainingDetails();
		$exec->addJsCode($detailR->getJsCodeAfterShow());
	}
	/**
	 * Pushes Element form HTML back to client.
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @param Element|int $element Filled element to display or ID of element to fetch.
	 * @param WigiiBPLParameter $options a map of options for the display process.
	 */
	public function displayElementForm($p,$exec,$element,$options=null) {
		/* not yet functional */
		FormExecutorException::throwNotImplemented();
		
		// fetches Element
		if($element instanceof ElementP) $element = $element->getElement();
		if(!($element instanceof Element)) $element = $this->fetchElement($p, $exec, $element, $options)->getElement();
		
		// configures rendering process
		$configS = $this->getConfigService();
		$transS = ServiceProvider::getTranslationService();
		$module = $element->getModule();
		$idAnswer = $exec->getIdAnswer();
		
		$totalWidth = 0;
		if(isset($options)) $totalWidth = 0 + $options->getValue('elementTotalWidth');
		if($totalWidth<=0) $totalWidth = 0 + $configS->getParameter($p, $module, "elementTotalWidth");
		
		$labelWidth = 0;
		if(isset($options)) $labelWidth = 0 + $options->getValue('elementLabelWidth');
		if($labelWidth<=0) $labelWidth = 0 + $configS->getParameter($p, $module, "elementLabelWidth");
		
		$formId = null;
		if(isset($options)) $formId = $options->getValue('formId');
		if(is_null($formId)) $formId = $this->getFormId();
		if(is_null($formId)) $formId = $idAnswer.'_item_'.$element->getId().'_edit';
		
		// renders element detail
		$this->setState('start');	
		$this->setIsDialog(false);	
		$this->setFormId($formId);
		$this->setSubmitUrl(null); /* to be implemented */
		$this->setRecord($element);
		//$this->setCorrectionWidth(19);
		$this->setLabelWidth($labelWidth);
		$this->setTotalWidth($totalWidth);
		
		$formR = $this->getFormRenderer();
		$trm = $this->getTrm();
		$formR->resetJsCodeAfterShow();		
		$trm->setFormRenderer($formR);
		$trm->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), false);
		$trm->displayRemainingForms();
		$trm->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog(), $transS->t($p, "cancel"));
		$trm->addJsCodeAfterFormIsShown($this->getFormId());
		$exec->addJsCode($formR->getJsCodeAfterShow());
	}
	/**
	 * Pushes new Element form HTML back to client.
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @param WigiiBPLParameter $options a map of options for the display process.
	 */
	public function displayNewElementForm($p,$exec,$options=null) {
		FormExecutorException::throwNotImplemented();
	}
	/**
	 * Pushes an Element view HTML back to client.
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @param Element|int $element Filled element to display or ID of element to fetch.
	 * @param Activity|String the Activity or activity name which describes the Element view to display. 
	 * @param WigiiBPLParameter $options a map of options for the display process.
	 */
	public function displayElementView($p,$exec,$element,$viewActivity,$options=null) {
		FormExecutorException::throwNotImplemented();
	}
	/**
	 * Pushes an Activity form HTML back to client.
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @param Activity|String $activity the Activity or activity name which describes the Form to display. 
	 * @param WigiiBPLParameter $options a map of options for the display process.
	 */
	public function displayActivityForm($p,$exec,$activity,$options=null) {
		FormExecutorException::throwNotImplemented();
	}
	
	// FormExecutor implementation
	
	protected function doSpecificCheck($p, $exec){/*nothing to do*/}

	protected function actOnCheckedRecord($p, $exec){/*nothing to do*/}

	protected function doRenderForm($p, $exec){/*nothing to do*/}
	
	// Business delegates
	
	/**
	 * Fetches an Element in the database given its ID
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @param int $id Element ID
	 * @param WigiiBPLParameter $options some options for the fetch process
	 * @return ElementP
	 */
	protected function fetchElement($p,$exec,$id,$options) {
		// prepares fetching parameters
		$param = TechnicalServiceProvider::createWigiiBPLParameterInstance();
		$param->setValue('elementId', $id);
		$param->setValue('wigiiNamespace', $exec->getCrtWigiiNamespace());
		$param->setValue('module', $exec->getCrtModule());
		// fetches element
		$wigiiBPL = ServiceProvider::getWigiiBPL();
		$elementP = $wigiiBPL->elementFetch($p, $this, $param);
		// if element does not exist or is not readable throw a NOT_FOUND exception
		if(is_null($elementP)) throw $this->createElementNotFoundException($p, $id);
		return $elementP;
	}
	
	protected function createElementNotFoundException($p,$elementId) {
		$transS = ServiceProvider::getTranslationService();
		return new ElementServiceException($transS->t($p,'elementUnreachable'),ElementServiceException::NOT_FOUND);
	}
}



