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
 * This class is the main service which links the GUI and the API.
 * It interprets the semantic of the wigii communication protocol.
 * 
 * Created on 24 juil. 09 by LWR
 * Refactored in two classes WigiiExecutor and WigiiCoreExecutor by CWE on November 23rd 2015.
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 * Modified by Medair (CWE) on 24.11.2016 to protect against Cross Site Scripting
 */
class WigiiCoreExecutor {
	
	
	// Object lifecycle
	
	
	private static $singleton;
	
	protected static function getInstance() {
		if (!isset (self :: $singleton)) {
			self :: $singleton = new WigiiExecutor();
		}
		return self :: $singleton;
	}
	
	/**
	 * Registers a WigiiExecutor subclass as current singleton instance
	 */
	protected static function registerSingleInstance($wigiiExecutor) {
		if (isset (self :: $singleton))
			throw new ServiceException("WigiiExecutor singleton has already been set, cannot change it dynamically", ServiceException :: FORBIDDEN);
		self :: $singleton = $wigiiExecutor;
	}
	
	/**
	 * Starts the current WigiiExecutor singleton
	 * Creates a default WigiiExecutor instance if needed
	 * Returns the current started singleton.
	 */
	public static function start() {
		$instance = self :: getInstance();
		$instance->doStart();
		return $instance;
	}
	
	/**
	 * Starts WigiiExecutor
	 */
	protected function doStart() {
		$this->startTechnicalServiceProvider();
		$this->startServiceProvider();
		$this->executionSink()->log("API is started");
		$this->executionSink()->log("WigiiExecutor ready");
		register_shutdown_function(array($this, "exit_handler"));
	}
	/**
	 * Default starts TechnicalServiceProviderWebImpl
	 */
	protected function startTechnicalServiceProvider() {
		TechnicalServiceProviderWebImpl :: start(EXECUTION_SINK_ENABLED, DEBUG_LOGGER_ENABLED, GLOBAL_STATISTIC_ENABLED, ELEMENT_STATISTIC_ENABLED, FILE_STATISTIC_ENABLED);
	}
	/**
	 * Default starts ServiceProviderWebImpl
	 */
	protected function startServiceProvider() {
		ServiceProviderWebImpl :: start(SESSION_CACHE_EXEC_ENABLED, SESSION_CACHE_BUILD_ENABLED, SYS_CONSOLE_ENABLED, $this);
	}
	
	public function exit_handler(){
		$error = error_get_last();
		//it seem that relative path after exit_handler is not working properly, therefore we might need to reset the working directory
		//lookup on http://www.php.net/manual/en/function.register-shutdown-function.php and search for the keyword 'relative'
		$cwd = str_replace(str_replace("../", "", IMPL_PATH)."libs", "", str_replace("\\", "/", dirname(__FILE__)));
		if(file_exists($cwd."www")) chdir($cwd."www");
		else if(file_exists($cwd."web/wigii")) chdir($cwd."web/wigii");
		else if(file_exists($cwd."web")) chdir($cwd."web");
		if($error !== NULL && ($error["type"]==E_ERROR || $error["type"]==E_PARSE || $error["type"]==E_USER_ERROR || $error["type"]==E_RECOVERABLE_ERROR)) {
			$errorLabel=array(E_ERROR=>'Fatal Error',E_PARSE=>'Parse Error',E_USER_ERROR=>'User Error',E_RECOVERABLE_ERROR=>'Recoverable Error');
			$errorMessage=$errorLabel[$error["type"]].": ".$error["message"]." in ".$error["file"]." on line ".$error["line"];
			$this->executionSink()->log($errorMessage);
			//no backtrace is available in shutdown context. //$this->executionSink()->log(alert(debug_backtrace()));
			// signals fatal error to monitoring system
			ServiceProvider::getClientAdminService()->signalFatalError($errorMessage);			
		}
		ServiceProvider::getSystemConsoleService()->logMessageInFile();
	}
	
	
	
	
	
	// System principal management
	
	
	/**
	 * Adds a system principal or a list of system principals to the WigiiExecutor
	 */
	public function addSystemPrincipal($systemPrincipal) {
		if (is_null($systemPrincipal))
			return;
		$this->getSystemPrincipals()->unionPrincipalList($systemPrincipal);
		$this->debugLogger()->write("received " . $systemPrincipal->count() . " system principals.");
	}
	private $systemPrincipals;
	/**
	 * Returns the list of actual system principals owned by the WigiiExecutor
	 */
	protected function getSystemPrincipals() {
		//autowired
		if (!isset ($this->systemPrincipals)) {
			$this->systemPrincipals = PrincipalListArrayImpl :: createInstance();
		}
		return $this->systemPrincipals;
	}
	/**
	 * Gets the root principal
	 */
	protected function getRootPrincipal() {
		$this->executionSink()->publishStartOperation("getRootPrincipal");
		$returnValue = ServiceProvider :: getAuthorizationService()->findRootPrincipal($this->getSystemPrincipals());
		if (is_null($returnValue))
			throw new AuthorizationServiceException("root principal has not been initialized by Service Provider", AuthorizationServiceException :: FORBIDDEN);
		$this->executionSink()->publishEndOperation("getRootPrincipal");
		return $returnValue;
	}
	/**
	 * Gets the public principal
	 */
	protected function getPublicPrincipal() {
		$this->executionSink()->publishStartOperation("getPublicPrincipal");
		$returnValue = ServiceProvider :: getAuthorizationService()->findPublicPrincipal($this->getSystemPrincipals());
		if (is_null($returnValue))
			throw new AuthorizationServiceException("public principal has not been initialized by Service Provider", AuthorizationServiceException :: FORBIDDEN);
		$this->executionSink()->publishEndOperation("getPublicPrincipal");
		return $returnValue;
	}
	
	protected function getPublicPrincipalForActivity($p, $wigiiNamespace, $module, $activity) {
		$this->executionSink()->publishStartOperation("getPublicPrincipalForActivity");
		$p = $this->getPublicPrincipal();
		$p->changeWigiiNamespace($wigiiNamespace);
		$this->executionSink()->publishEndOperation("getPublicPrincipalForActivity");
		return $p;
	}
	protected function getPublicPrincipalForWigiiNamespace($p, $wigiiNamespace, $module) {
		$this->executionSink()->publishStartOperation("getPublicPrincipalForWigiiNamespace");
		$p = $this->getPublicPrincipal();
		$p->changeWigiiNamespace($wigiiNamespace);
		$this->executionSink()->publishEndOperation("getPublicPrincipalForWigiiNamespace");
		return $p;
	}
	
	
	
	
	
	// Dependency injection
	
	
	private $_debugLogger;
	private function debugLogger() {
		if (!isset ($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger :: getInstance("WigiiCoreExecutor");
		}
		return $this->_debugLogger;
	}
	
	private $_executionSink;
	private function executionSink() {
		if (!isset ($this->_executionSink)) {
			$this->_executionSink = ExecutionSink :: getInstance("WigiiCoreExecutor");
		}
		return $this->_executionSink;
	}
	
	private $wigiiEventsDispatcher;
	public function setWigiiEventsDispatcher($wigiiEventsDispatcher) {
		$this->wigiiEventsDispatcher = $wigiiEventsDispatcher;
	}
	
	private $notificationService;
	public function setNotificationService($service) {
		$this->notificationService = $service;
	}
	public function getNotificationService() {
		if (!isset ($this->notificationService)) {
			$this->notificationService = TechnicalServiceProviderWebImpl :: getNotificationService();
			$this->notificationService->setConfigService($this->getConfigurationContext());
			$this->notificationService->setEmailService($this->getEmailService());
		}
		return $this->notificationService;
	}
	
	private $emailService;
	public function getEmailService() {
		if (!isset ($this->emailService)) {
			$this->emailService = TechnicalServiceProvider :: getEmailService();
		}
		return $this->emailService;
	}
	
	/**
	 * Returns an instance of an ElementPolicyEvaluator for the given principal and module.
	 * @param Principal $principal principal executing the request
	 * @param Module $module module for which to get an ElementPolicyEvaluator
	 * @return ElementPolicyEvaluator or null if none is activated for this principal and module.
	 */
	public function getElementPolicyEvaluator($principal, $module) {
		$policyEvaluator = null;
		if(ServiceProvider::getAuthorizationService()->isElementPolicyEvaluatorEnabled($principal, $module)) {
			$policyEvaluatorClassName = (string)$this->getConfigurationContext()->getParameter($principal, $module, 'Element_policyEvaluator');
			$policyEvaluator = ServiceProvider::getElementPolicyEvaluator($principal, $policyEvaluatorClassName);
			if($policyEvaluator instanceof ElementPolicyEvaluator) $policyEvaluator->setWigiiExecutor($this);			 
		}
		return $policyEvaluator;
	}
	
	/**
	 * Returns an instance of a FuncExpEvaluator configured for the context of the given Record.
	 * @param Principal $p principal executing the request
	 * @param ExecutionService $exec current ExecutionService instance
	 * @param Record $rec record for which to get an FuncExpEvaluator. If null, returns a custom ElementEvaluator depending of current module.
	 * @return FuncExpEvaluator
	 */
	public function getFuncExpEvaluator($p, $exec, $rec) {
		// gets a RecordEvaluator based on given record
		if(isset($rec)) {
			// gets RecordEvaluator
			if($rec instanceof Element) $evaluatorClassName = (string)$this->getConfigurationContext()->getParameter($p, $rec->getModule(), "Element_evaluator");
			else $evaluatorClassName = null;
			if(empty($evaluatorClassName)) $evaluatorClassName = (string)$this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Element_evaluator");
			$evaluator = ServiceProvider::getRecordEvaluator($p, $evaluatorClassName);
			// injects the context
			$evaluator->setContext($p, $rec);
		}
		// else gets an ElementEvaluator based on current module
		else {
			// gets RecordEvaluator
			$evaluatorClassName = (string)$this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Element_evaluator");
			$evaluator = ServiceProvider::getRecordEvaluator($p, $evaluatorClassName);
			// injects the context
			$evaluator->setContext($p, null);
		}
		// gets vm
		$returnValue = ServiceProvider::getFuncExpVM($p, $evaluator);
		$returnValue->setFreeParentEvaluatorOnFreeMemory(true);
		$returnValue->setWigiiExecutor($this);
		//$this->debugLogger()->write("instanciated FuncExpEvaluator of class ".get_class($returnValue));
		return $returnValue;
	}
	
	/**
	 * Evaluates a FuncExp in the context of the given Record.
	 * @param Principal $p principal executing the request
	 * @param ExecutionService $exec current ExecutionService instance
	 * @param FuncExp $fx the FuncExp instance to evaluate
	 * @param Record $rec record for which to get an FuncExpEvaluator. If null, returns a custom ElementEvaluator depending of current module.
	 * @return Any FuncExp result
	 */
	public function evaluateFuncExp($p,$exec,$fx,$rec=null) {
		$fxEval = $this->getFuncExpEvaluator($p, $exec, $rec);
		$returnValue = null;
		try {
			$returnValue = $fxEval->evaluateFuncExp($fx, $this);
			$fxEval->freeMemory();
		}
		catch(Exception $e) {
			$fxEval->freeMemory();
			throw $e;
		}
		return $returnValue;
	}
	
	/**
	 * Evaluates a Configuration Parameter which can be either a constant or a FuncExp.
	 * @param Principal $p principal executing the request
	 * @param ExecutionService $exec current ExecutionService instance
	 * @param String $parameter the configuration parameter to evaluate
	 * @param Record $rec record for which to get an FuncExpEvaluator. If null, returns a custom ElementEvaluator depending of current module.
	 * @return Any FuncExp result
	 */
	public function evaluateConfigParameter($p,$exec,$parameter,$rec=null) {
		$parameter=(string)$parameter;
		// checks that parameter is not already resolved
		if(empty($parameter) || is_numeric($parameter)) return $parameter;
		// parses the parameter into a funcExp
		try {
			$parameter = str2fx($parameter);
		}
		catch(StringTokenizerException $ste) {
			// if syntax error, then keeps the parameter as is
			// or we have a real syntax error, that then will be corrected by the user
			// or it is not a funcExp but a constant, in that case keeps the parameter.
			if($ste->getCode() != StringTokenizerException::SYNTAX_ERROR) throw $ste;
		}
		// executes the func exp
		$parameter = $this->evaluateFuncExp($p, $exec, $parameter, $rec);
		// if parameter is a log exp, then solves it against the record
		if($parameter instanceof LogExp && isset($rec)) {
			$parameter = TechnicalServiceProvider::getFieldSelectorLogExpRecordEvaluator()->evaluate($rec, $parameter);
		}
		// if parameter is a data flow, then executes it
		elseif($parameter instanceof DataFlowSelector) {
			// sets adaptative WigiiNamespace
			$currentNamespace = $p->getWigiiNamespace();
			$hasAdaptiveWigiiNamespace = $p->hasAdaptiveWigiiNamespace();
			$p->setAdaptiveWigiiNamespace(true);
			
			// executes data flow
			$parameter = ServiceProvider::getDataFlowService()->processDataFlowSelector($p, $parameter);
			
			// switches back to original WigiiNamespace
			if(!$hasAdaptiveWigiiNamespace) {
				$p->setAdaptiveWigiiNamespace(false);
				$p->bindToWigiiNamespace($currentNamespace);
			}
		}
		// returns the resolved parameter
		if(is_numeric($parameter)) return $parameter;
		elseif(is_string($parameter) && !empty($parameter)) return $parameter;
		else {
			if($parameter) return "1";
			else return "0";
		}
	}
	
	
	
	// Object factories
	
	
	protected function getElementPListHtmlRendererDFAS($params=null) {
		return dfas('ElementPListHtmlRendererDFA', $params);
	}
	
	protected function createDownloadCaptchaTRM() {
		return $this->createTRM();
	}
	
	protected function createElementPListForExport($p, $exec, $action, $listContext, $lcTemp, $separator, $encoding) {
		$configS = $this->getConfigurationContext();
		$elS = ServiceProvider :: getElementService();
		switch ($action) {
			case "PDF" :
				$ell = ElementPListExportExcel :: createInstance($this, $listContext);
				$ell->setFactory("PDF");
				$ell->setLCTemp($lcTemp);
				$ell->setAllOnOnePage(true);
				return $ell;
			case "Excel" :
				$ell = ElementPListExportExcel :: createInstance($this, $listContext);
				$ell->setLCTemp($lcTemp);
				//factory is autoset
				return $ell;
			case "CSVPretty" :
				$ell = ElementPListExportCSVPretty :: createInstance($this, $listContext);
				$ell->setSeparator($separator);
				$ell->setEncoding($encoding);
				return $ell;
			case "CSVRaw" :
				//prepare fieldSelector with all fields + content for integrated files:
				$originalFieldList = FormFieldList :: createInstance(FormBag :: createInstance());
				$importFieldSelectorList = FieldSelectorListArrayWebImpl :: createInstance(false);
				$configS->getFields($p, $exec->getCrtModule(), null, $originalFieldList);
				foreach ($originalFieldList->getListIterator() as $field) {
					if ($field->getDataType() == null)
						continue;
					if ($field->getDataType()->getDataTypeName() == "Files") {
						$fieldXml = $field->getXml();
						if ($fieldXml["htmlArea"] == "1") {
							foreach ($field->getDataType()->getXml()->children() as $subField) {
								$importFieldSelectorList->addFieldSelector($field->getFieldName(), $subField->getName());
							}
						} else {
							$importFieldSelectorList->addFieldSelector($field->getFieldName());
						}
					} else {
						$importFieldSelectorList->addFieldSelector($field->getFieldName());
					}
				}
				//				//add element attribute fields
				//				$importFieldSelectorList->mergeFieldSelectorList($elS->getFieldSelectorListForElementAttributForImport());
				$listContext->setFieldSelectorList($importFieldSelectorList);
				$ell = ElementPListExportCSVRaw :: createInstance($this, $listContext);
				$ell->setSeparator($separator);
				$ell->setEncoding($encoding);
				return $ell;
				//			case "JSON":
				//				$ell = ElementPListExportJSON::createInstance($this, $listContext);
				//				$ell->setEncoding($encoding);
				//				return $ell;
				//			case "XML":
				//				$ell = ElementPListExportXML::createInstance($this, $listContext);
				//				$ell->setEncoding($encoding);
				//				return $ell;
			default :
				throw new ServiceException("Bad action on createElementPListForExport: $action", ServiceException :: INVALID_ARGUMENT);
		}
	}
	
	public function createElementForForm($p, $module, $id) {
		$el = Element :: createInstance($module);
		$el->setId($id);
		$bag = FormBag :: createInstance();
		$ffl = FormFieldList :: createInstance($bag);
		$bag->setFormFieldList($ffl);
		$el->setWigiiBag($bag);
		$el->setFieldList($ffl);
		return $el;
	}
	public function createActivityRecordForForm($p, $activity, $module, $id = null) {
		$act = ActivityRecord :: createInstance($activity, $module);
		$act->setId($id);
		$bag = FormBag :: createInstance();
		$ffl = FormFieldList :: createInstance($bag);
		$bag->setFormFieldList($ffl);
		$act->setWigiiBag($bag);
		$act->setFieldList($ffl);
		$this->getConfigurationContext()->getFields($p, $module, $activity, $act->getFieldList());
		return $act;
	}
	
	public function createTRM($record = null, $isForNotification = false, $isForPrint=false, $isForExternalAccess = false, $isForListView=false, $isForPreviewList=false, $isOutputEnabled = true) {
		$trm = TemplateRecordManager::createInstance($isForNotification, $isForPrint, $isForExternalAccess, $isForListView, $isForPreviewList, $isOutputEnabled);
		$trm->setConfigService($this->getConfigurationContext());
		$trm->setWorkzoneViewDocked($this->isWorkzoneViewDocked());
		$trm->reset($record);
		return $trm;
	}
	
	// ELEMENT forms
	
	protected function createFormExecutor() {
		return BasicFormExecutor :: createInstance($this, null, null, null);
	}
	protected function createLoginFormExecutor($record, $formId, $submitUrl) {
		return LoginFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
	}
	protected function createChangePasswordFormExecutor($record, $formId, $submitUrl) {
		return ChangePasswordFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
	}
	protected function createFeedbackFormExecutor($record, $formId, $submitUrl) {
		return FeedbackFormExecutor :: createInstance($this->getRootPrincipal(), $this, $record, $formId, $submitUrl);
	}
	protected function createEmailingFormExecutor($record, $formId, $submitUrl, $elementIds) {
		return EmailingFormExecutor :: createInstance($this, $record, $formId, $submitUrl, $elementIds);
	}
	protected function createShareMultipleElementFormExecutor($record, $formId, $submitUrl, $rootPrincipal, $elementPAList, $lc) {
		return ShareMultipleElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl, $rootPrincipal, $elementPAList, $lc);
	}
	protected function createTransferMultipleElementFormExecutor($record, $formId, $submitUrl, $rootPrincipal, $elementPAList, $lc) {
		return TransferMultipleElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl, $rootPrincipal, $elementPAList, $lc);
	}
	protected function createAddIndicatorFormExecutor($record, $formId, $submitUrl) {
		return AddIndicatorFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
	}
	protected function createFiltersFormExecutor($record, $formId, $submitUrl) {
		return FiltersFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
	}
	protected function createAddElementFormExecutor($record, $formId, $submitUrl, $listContext) {
		if ($listContext->getCrtView() == "calendar") {
			$r = AddEventFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
		} else {
			$r = AddElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
		}
		$r->setRootPrincipal($this->getRootPrincipal()); //needed for autosharing
		$r->setListContext($listContext);
		return $r;
	}
	protected function createAddSubElementFormExecutor($record, $formId, $submitUrl, $masterElementId, $linkName) {
		$r = AddSubElementFormExecutor::createInstance($this, $record, $formId, $submitUrl, $masterElementId, $linkName);
		return $r;
	}
	protected function createCopyElementFormExecutor($record, $formId, $submitUrl, $listContext) {
		if ($listContext->getCrtView() == "calendar") {
			$r = CopyEventFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
		} else {
			$r = CopyElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
		}
		$r->setRootPrincipal($this->getRootPrincipal()); //needed for autosharing
		$r->setListContext($listContext);
		return $r;
	}
	protected function createCopySubElementFormExecutor($record, $formId, $submitUrl, $masterElementId, $linkName) {
		$r = CopySubElementFormExecutor::createInstance($this, $record, $formId, $submitUrl, $masterElementId, $linkName);
		return $r;
	}
	protected function createAddSubscriptionFormExecutor($groupP, $subscriptionR, $record, $formId, $submitUrl) {
		$fe = AddSubscriptionFormExecutor :: createInstance($this, $groupP, $subscriptionR, $record, $formId, $submitUrl);
		$fe->setRootPrincipal($this->getRootPrincipal()); //needed for autosharing
		return $fe;
	}
	protected function createExternalAccessViewFormExecutor($elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, $formId, $submitUrl) {
		return ExternalAccessViewFormExecutor :: createInstance($this, $elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, $formId, $submitUrl);
	}
	protected function createExternalAccessEditFormExecutor($elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, $formId, $submitUrl) {
		$fe = ExternalAccessEditFormExecutor :: createInstance($this, $elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, $formId, $submitUrl);
		$fe->setRootPrincipal($this->getRootPrincipal()); //needed for autosharing
		return $fe;
	}
	protected function createExternalAccessDeleteFormExecutor($elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, $formId, $submitUrl) {
		return ExternalAccessDeleteFormExecutor :: createInstance($this, $elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, $formId, $submitUrl);
	}
	protected function createDetailElementFormExecutor($record, $elementP, $formId, $submitUrl) {
		$fe = DetailElementFormExecutor :: createInstance($this, $record, $elementP, $formId, $submitUrl);
		return $fe;
	}
	protected function createPrintElementFormExecutor($record, $elementP, $formId, $submitUrl) {
		$fe = PrintElementFormExecutor :: createInstance($this, $record, $elementP, $formId, $submitUrl);;
		$fe->setIsForPrint(true);
		return $fe;
	}
	protected function createEditElementFormExecutor($record, $formId, $submitUrl, $listContext) {
		if ($listContext->getCrtView() == "calendar") {
			$r = EditEventFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
		} else {
			$r = EditElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
		}
		$r->setRootPrincipal($this->getRootPrincipal()); //needed for autosharing
		$r->setListContext($listContext);
		return $r;
	}
	protected function createDeleteElementFormExecutor($record, $formId, $submitUrl, $listContext) {
		$r = DeleteElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
		$r->setRootPrincipal($this->getRootPrincipal()); //needed for autosharing
		$r->setListContext($listContext);
		return $r;
	}
	protected function createLockAndModifyElementFormExecutor($record, $formId, $submitUrl, $listContext, $fieldname) {
		$r = LockAndModifyElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
		$r->setListContext($listContext);
		$r->setFieldname($fieldname);
		$r->setRootPrincipal($this->getRootPrincipal()); //needed for autosharing
		return $r;
	}
	protected function createCheckInAndModifyElementFormExecutor($record, $formId, $submitUrl, $listContext, $fieldname) {
		$r = CheckInAndModifyElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
		$r->setListContext($listContext);
		$r->setFieldname($fieldname);
		$r->setRootPrincipal($this->getRootPrincipal()); //needed for autosharing
		return $r;
	}
	protected function createEditMultipleElementFormExecutor($record, $formId, $submitUrl, $rootPrincipal, $elementPAList) {
		$fe = EditMultipleElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl, $rootPrincipal, $elementPAList);
		return $fe;
	}
	protected function createDeleteMultipleElementFormExecutor($record, $formId, $submitUrl, $rootPrincipal, $elementPAList) {
		$fe = DeleteMultipleElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl, $rootPrincipal, $elementPAList);
		return $fe;
	}
	protected function createShareElementFormExecutor($record, $formId, $submitUrl) {
		return ShareElementFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
	}
	protected function createExportFormExecutor($record, $formId, $submitUrl) {
		$what = $record->getActivity()->getActivityName();
		switch ($what) {
			case "exportExcelCalendar" :
				return ExportExcelCalendarFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
			case "exportExcel" :
				return ExportExcelFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
			case "exportCSV" :
				return ExportCSVFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
			default :
				throw new ServiceException("unknown export: " . $what, ServiceException :: INVALID_ARGUMENT);
		}
	}
	protected function createExportICSFormExecutor($record, $formId, $submitUrl) {
		return ExportICSCalendarFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
	}
	protected function createImportElementInFormExecutor($record, $formId, $submitUrl, $rootPrincipal, $crtSelectedGroupP) {
		return ImportElementInFormExecutor :: createInstance($this, $record, $formId, $submitUrl, $rootPrincipal, $crtSelectedGroupP);
	}
	protected function createUpdateElementInFormExecutor($record, $formId, $submitUrl, $rootPrincipal, $writeGroupPList) {
		return UpdateElementInFormExecutor :: createInstance($this, $record, $formId, $submitUrl, $rootPrincipal, $writeGroupPList);
	}
	protected function createFindDuplicatesInFormExecutor($record, $formId, $submitUrl, $rootPrincipal, $crtSelectedGroupP) {
		return FindDuplicatesInFormExecutor :: createInstance($this, $record, $formId, $submitUrl, $rootPrincipal, $crtSelectedGroupP);
	}
	
	//ADMIN forms
	
	protected function createNewUserFormExecutor($userP, $record, $formId, $submitUrl, $request) {
		return NewUserFormExecutor :: createInstance($this, $userP, $record, $formId, $submitUrl, $request);
	}
	protected function createEditUserFormExecutor($userP, $record, $formId, $submitUrl, $request) {
		return EditUserFormExecutor :: createInstance($this, $userP, $record, $formId, $submitUrl, $request);
	}
	protected function createDeleteUserFormExecutor($userP, $record, $formId, $submitUrl, $request) {
		return DeleteUserFormExecutor :: createInstance($this, $userP, $record, $formId, $submitUrl, $request);
	}
	protected function createNewGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		return NewGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
	}
	protected function createNewCopyGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		return NewCopyGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
	}
	protected function createEditGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		return EditGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
	}
	protected function createDeleteGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		$r = DeleteGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
		$r->setRootPrincipal($this->getRootPrincipal()); //needed for moving group into trashbin
		return $r;
	}
	protected function createEmptyGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		$r = EmptyGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
		$r->setRootPrincipal($this->getRootPrincipal()); //needed for moving group into trashbin
		return $r;
	}
	protected function createConfigEditGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		return ConfigEditGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
	}
	protected function createHtmlContentGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		return HtmlContentGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
	}
	protected function createPortalGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		return PortalGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
	}
	protected function createXmlPublishGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		return XmlPublishGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
	}
	protected function createSubscriptionGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		return SubscriptionGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
	}
	protected function createEmailNotificationGroupFormExecutor($groupP, $record, $formId, $submitUrl, $request) {
		return EmailNotificationGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl, $request);
	}
	protected function createDetailGroupFormExecutor($groupP, $record, $formId, $submitUrl) {
		return DetailGroupFormExecutor :: createInstance($this, $groupP, $record, $formId, $submitUrl);
	}
	protected function createDetailUserFormExecutor($module, $userP, $record, $formId, $submitUrl) {
		return DetailUserFormExecutor :: createInstance($this, $module, $userP, $record, $formId, $submitUrl);
	}
	protected function createConfigEditUserFormExecutor($module, $userP, $record, $formId, $submitUrl, $request) {
		return ConfigEditUserFormExecutor :: createInstance($this, $module, $userP, $record, $formId, $submitUrl, $request);
	}
	protected function createModuleConfigEditFormExecutor($moduleEditorIsNew, $configFileName, $record, $formId, $submitUrl, $request) {
		return ModuleConfigEditFormExecutor :: createInstance($this, $moduleEditorIsNew, $configFileName, $record, $formId, $submitUrl, $request);
	}
	protected function createExternalAccessRequestFormExecutor($groupP, $externalAccessRequestRec, $subscriptionGroupRec, $formId, $submitUrl) {
		return ExternalAccessRequestFormExecutor :: createInstance($this, $groupP, $externalAccessRequestRec, $subscriptionGroupRec, $formId, $submitUrl);
	}
	protected function createModuleEditorNewNamespaceFormExecutor($record, $formId, $submitUrl){
		return ModuleEditorNewNamespaceFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
	}
	protected function createModuleEditorRemoveNamespaceFormExecutor($record, $formId, $submitUrl){
		return ModuleEditorRemoveNamespaceFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
	}
	protected function createModuleEditorRemoveEmailNotificationFormExecutor($record, $formId, $submitUrl){
		return ModuleEditorRemoveEmailNotificationFormExecutor :: createInstance($this, $record, $formId, $submitUrl);
	}
	protected function createElementPListExportXmlInstance($wigiiExecutor, $lc){
		return ElementPListExportXml :: createInstance($wigiiExecutor, $lc);
	}
	
	
	
	
	
	
	// WigiiExecutor state management
	
	private $workzoneViewDocked = null;
	/**
	 * Returns true if Workzone has a docked ListView and ElementDialog, 
	 * Returns false if ElementDialog shows as a popup.	 
	 */
	public function isWorkzoneViewDocked() {
		if(!isset($this->workzoneViewDocked)) {
			$this->workzoneViewDocked = ($this->getConfigurationContext()->getParameter(ServiceProvider::getAuthenticationService()->getMainPrincipal(), ServiceProvider::getExecutionService()->getCrtModule(), "workZoneViewDocked") == '1');
		}
		return $this->workzoneViewDocked;
	}
	public function setWorkzoneViewDocked($bool) {		
		$this->workzoneViewDocked = $bool;
	}
	
	/**
	 * flag to tell to use public principal for the download:
	 */
	private $usePublicPrincipalForDownloadRequest = null;
	public function doesPrincipalForDownloadRequestShouldBePublic() {
		return $this->usePublicPrincipalForDownloadRequest;
	}
	private function usePublicPrincipalForDownloadRequest() {
		$this->usePublicPrincipalForDownloadRequest = true;
	}
	private function resetUsePublicPrincipalForDownloadRequest() {
		$this->usePublicPrincipalForDownloadRequest = false;
	}
	
	private $TempTotalElementsIdsForListView;
	public function getTempTotalElementsIdsForListView(){
		return $this->TempTotalElementsIdsForListView;
	}
	protected function setTempTotalElementsIdsForListView($var){
		$this->TempTotalElementsIdsForListView = $var;
	}
	
	public function persistMainPrincipalSessionContext($p, $exec) {
		$this->executionSink()->publishStartOperation("persistMainPrincipalSessionContext");
		$this->storeWigiiContextInP($p, $exec);
		ServiceProvider :: getAuthenticationService()->persistMainPrincipalSessionContext();
		$this->executionSink()->publishEndOperation("persistMainPrincipalSessionContext");
	}
	
	/**
	 * this method should be called after each userAdminService->calculateAllMergedRoles($p)
	 */
	public function storeAdminAndCalculatedRoleIdsInSession($p){
		$userAS = ServiceProvider::getUserAdminService();
		$sessAS = ServiceProvider::getSessionAdminService();
	
		$defaultWigiiNamespace = (string)$this->getConfigurationContext()->getParameter($p, null, "defaultWigiiNamespace");
		if(!$defaultWigiiNamespace){
			$defaultWigiiNamespace = $p->getRealWigiiNamespace()->getWigiiNamespaceUrl();
		}
	
		//the prefetchAllRoles store in session each role
		$p->refetchAllRoles($userAS->getListFilterForNavigationBar(), UserListForNavigationBarImpl::createInstance($defaultWigiiNamespace));
		return $p->getRoleListener();
	}
	
	/**
	 * List Context management
	 */
	private $listContextList;
	public function getListContext($p, $wigiiNamespace, $module, $name) {
		$this->executionSink()->publishStartOperation("getListContext " . $name);
		$sessAS = ServiceProvider :: getSessionAdminService();
		$exec = ServiceProvider::getExecutionService();
		if (!isset ($this->listContextList)){
			//look in session
			$this->listContextList = $sessAS->getData($this, "listContextList");
			if(!isset($this->listContextList)) $this->listContextList = array();
		}
		$key = $this->getListContextKey($exec->getCrtContext(), $wigiiNamespace, $module, $name);
		$listContext = $this->listContextList[$key];
		if (!isset ($listContext)) {
			//create a new instance
			$listContext = $this->createListContextInstance($p, $module);
			//store the new listContextList it in session
			$this->listContextList[$key] = $listContext;
			$sessAS->storeData($this, "listContextList", $this->listContextList);
		}
		$this->executionSink()->publishEndOperation("getListContext " . $name);
		return $listContext;
	}
	protected function createListContextInstance($p, $module) {
		switch ($module->getModuleName()) {
			default :
				return ListContext :: createInstance($p, $module, $this->getConfigurationContext());
		}
	}
	protected function getListContextKey($contextId, $wigiiNamespace, $module, $name) {
		return "(".$contextId."(".$wigiiNamespace->getWigiiNamespaceUrl()."(".$module->getModuleUrl()."($name))))";
	}
	protected function clearListContext($wigiiNamespace, $module, $name) {
		$this->executionSink()->publishStartOperation("clearListContext " . $name);
		if (isset ($this->listContextList)) {
			$sessAS = ServiceProvider :: getSessionAdminService();
			$exec = ServiceProvider ::getExecutionService();
			unset ($this->listContextList[$this->getListContextKey($exec->getCrtContext(), $wigiiNamespace, $module, $name)]);
			$sessAS->storeData($this, "listContextList", $this->listContextList);
		}
		$this->executionSink()->publishEndOperation("clearListContext " . $name);
	}
	public function clearAllListContext() {
		$this->executionSink()->publishStartOperation("clearAllListContext");
		$sessAS = ServiceProvider :: getSessionAdminService();
		$sessAS->clearData($this, "listContextList");
		unset ($this->listContextList);
		$this->executionSink()->publishEndOperation("clearAllListContext");
	}
	//	public function storeAllListContextInP($p, $exec){
	//		$this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), null);
	//		if(isset($this->listContextList)){
	//			$allListContext = array();
	//			foreach($this->listContextList as $key=>$listContext){
	//				$allListContext[$key] = $listContext->serialize($p);
	//			}
	//			$p->setValueInRoleContext("allListContext", $allListContext);
	//		}
	//	}
	
	/**
	 * Configuration Context management
	 */
	private $configurationContext;
	public function getConfigurationContext() {
		//as getConfigurationContext() is done each time we access to the config, do not log it as publishStartOperation
		//$this->executionSink()->publishStartOperation("getConfigurationContext");
		$sessAS = ServiceProvider :: getSessionAdminService();
		$exec = ServiceProvider::getExecutionService();
		if (!isset ($this->configurationContext)){
			//look in session
			$this->configurationContext = $sessAS->getData($this, "configurationContext");
			if(!isset($this->configurationContext)) $this->configurationContext = array();
		}
		if (!isset ($this->configurationContext[$exec->getCrtContext()])) {
			$this->executionSink()->log("create ConfigurationContext");
			$this->configurationContext[$exec->getCrtContext()] = $this->createConfigurationContextInstance();
			$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
			if ($p->getModuleAccess() != null) {
				$groupS = ServiceProvider :: getGroupAdminService();
				foreach ($p->getModuleAccess() as $module) {
					if ($module->isAdminModule())
						continue;
	
					//autoload the groupList
					$this->configurationContext[$exec->getCrtContext()]->getGroupPList($p, $module);
	
				}
	
			}
	
			$sessAS->storeData($this, "configurationContext", $this->configurationContext);
			$this->executionSink()->log("store ConfigurationContext in session");
		} else {
			$this->executionSink()->log("found ConfigurationContext in session");
		}
		//$this->executionSink()->publishEndOperation("getConfigurationContext");
		return $this->configurationContext[$exec->getCrtContext()];
	}
	protected function createConfigurationContextInstance() {
		//return ConfigurationContextImpl :: createInstance();
		// modified by CWE on February 4th 2014 to support sub elements
		return ConfigurationContextSubElementImpl::createInstance();
	}
	public function clearConfigurationContext() {
		$this->executionSink()->publishStartOperation("clearConfigurationContext");
		$sessAS = ServiceProvider :: getSessionAdminService();
		$sessAS->clearData($this, "configurationContext");
		unset ($this->configurationContext);
		$this->executionSink()->publishEndOperation("clearConfigurationContext");
	}
	
	public function getCurrentConfigContextKey($p, $exec){
		$crtGroupId = $this->getConfigurationContext()->getCrtConfigGroupId($p, $exec);
		return $exec->getCrtWigiiNamespace()->getWigiiNamespaceName().".".$exec->getCrtModule()->getModuleName().".".$crtGroupId;
	}
	
	/**
	 * Admin Context management
	 */
	private $adminContext;
	public function getAdminContext($p) {
		$sessAS = ServiceProvider :: getSessionAdminService();
		$exec = ServiceProvider::getExecutionService();
		if (!isset ($this->adminContext)){
			//look in session
			$this->adminContext = $sessAS->getData($this, "adminContext");
			if(!isset($this->adminContext)) $this->adminContext = array();
		}
		if (!isset ($this->adminContext[$exec->getCrtContext()])) {
			$this->executionSink()->log("createAdminContextInstance");
			$this->adminContext[$exec->getCrtContext()] = $this->createAdminContextInstance();
			//setup working module as first module accessible
			$this->adminContext[$exec->getCrtContext()]->setWorkingModule($p->getFirstNoneAdminAccessibleModule());
			//setting subscreen as first possible screen
			$this->adminContext[$exec->getCrtContext()]->setSubScreen(reset($this->adminContext[$exec->getCrtContext()]->getSubScreens($p)));
	
			$sessAS->storeData($this, "adminContext", $this->adminContext);
			//				eput("create ConfigurationContext");
		} else {
			$this->executionSink()->log("found AdminContextInstance in session");
			//				if($this->configurationContext->getGroupPList() != null){
			//					eput("load ConfigurationContext".current($this->configurationContext->getGroupPList()->getListIterator())->getDbEntity()->getGroupName());
			//				} else {
			//					eput("load ConfigurationContext");
			//				}
		}
		$this->executionSink()->publishEndOperation("getAdminContext");
		return $this->adminContext[$exec->getCrtContext()];
	}
	protected function createAdminContextInstance() {
		return AdminContextImpl :: createInstance($this->getRootPrincipal());
	}
	public function clearAdminContext() {
		$this->executionSink()->publishStartOperation("clearAdminContext");
		$sessAS = ServiceProvider :: getSessionAdminService();
		$sessAS->clearData($this, "adminContext");
		unset ($this->adminContext);
		$this->executionSink()->publishEndOperation("clearAdminContext");
	}
	
	public function clearConfig($includeSharedData=false) {
		$this->executionSink()->publishStartOperation("clearConfig");
		$configS = ServiceProvider :: getConfigService();
		if(method_exists($configS, 'clearSessionData')) $configS->clearSessionData();
		// modified by CWE on February 4th 2014 to clear sub elements config
		ServiceProvider::clearSessionDataOfSubElementConfigService();
		if($includeSharedData) ServiceProvider::getSessionAdminService()->clearAllSharedData();
		$this->executionSink()->publishEndOperation("clearConfig");
	}
	public function clearDico() {
		$this->executionSink()->publishStartOperation("clearDico");
		$sessAS = ServiceProvider :: getSessionAdminService();
		$transS = ServiceProvider :: getTranslationService();
		$sessAS->clearData($transS, "language");
		$sessAS->clearData($transS, "executionModule");
		if(!$transS->isSharedCacheEnabled()) $sessAS->clearData($transS, "dictionary");
		$this->executionSink()->publishEndOperation("clearDico");
	}
	/**
	 * global context management:
	 * this is called only when logout, or just after autoLogin
	 */
	public function clearWigiiContext() {
		$this->executionSink()->publishStartOperation("clearWigiiContext");
		$this->clearAdminContext();
		$this->clearAllListContext();
		$this->clearConfigurationContext();
		$this->clearConfig();
		$this->clearDico();
		$this->executionSink()->publishEndOperation("clearWigiiContext");
	}
	public function storeWigiiContextInP($p, $exec) {
		$this->executionSink()->publishStartOperation("storeWigiiContextInP");
	
		//list Context are not stored in P context
		//$this->storeAllListContextInP($p, $exec);
	
		//store last selected folder id if defined
	
		//clean any unnecessary old context data
		if(!$p->getValueInGeneralContext("contextPrevious4107Cleaned")){
			$p->cleanKeysInRoleContext("adminContext");
			$p->cleanKeysInRoleContext("configurationContext");
			$p->cleanKeysInGeneralContext("adminRolesIds");
			$p->setValueInGeneralContext("contextPrevious4107Cleaned", true);
		}
		$this->executionSink()->publishEndOperation("storeWigiiContextInP");
	}
	
	
	
	
	
	
	// HTML helpers
	
	
	public function getHttpHeader() {
		header("Content-Type: text/html; charset=UTF-8");
	}
	
	protected function displayNotFound($message = null) {
		echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
		echo "\n";
		echo '<html><body><head><title>404 Not Found</title></head>';
		echo '<h2>Page not found</h2>';
		echo '<p>The requested URL was not found on this server.</p>';
		if ($message) {
			echo '<p>' . $message . '</p>';
		}
		echo '</body></html>';
	}
	protected function displayNotAvailable($p, $message = null) {
		echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
		echo "\n";
		echo '<html><body><head><title>403 Forbidden</title></head>';
		echo '<h2>' . ServiceProvider :: getTranslationService()->t($p, "unavailableElement") . '</h2>';
		echo '<p>' . ServiceProvider :: getTranslationService()->t($p, "unavailableElementExplication") . '</p>';
		if ($message) {
			echo '<p>' . $message . '</p>';
		}
		echo '</body></html>';
	}
	public function getSqlTableNameForElements(){
		return "Elements";
	}
	public function displayListOfLockedItems($p, $itemIds, $domId = "confirmationDialog"){
		$lockIds = ServiceProvider::getDbAdminService()->getLocked($p, $this->getSqlTableNameForElements(), $itemIds);
		$transS = ServiceProvider::getTranslationService();
		if($lockIds){
			$message = '<span class="H" onclick="$(\'#searchBar .firstBox input\').val(\'#'.implode(" #", $lockIds).'\'); $(\'#goForSearch\').click();">#'.implode(", #", $lockIds).'</span>';
			$message .= "<br />"."<br />".$transS->t($p, "thoseElementsAreLockedExpl");
			$this->openAsMessage($domId, 350, $transS->t($p, "thoseElementsAreLocked"), $message, null, $transS->t($p, "ok"), null, null, "alert");
		}
	}
	
	protected function includeExportMultipleMenu($p, $exec, $transS, $configS){
		$this->executionSink()->publishStartOperation("includeExportMultipleMenu", $p);
		//does this view include export activities?
		$activity = Activity :: createInstance("exportMenu");
		$act = $configS->mf($p, $exec->getCrtModule(), $activity);
		$responseDiv = 'elementDialog';
		if($this->isWorkzoneViewDocked()) $responseDiv = 'confirmationDialog';

		if (!empty ($act)) {
			foreach ($act->export->attribute as $export) {
			?><div class="export H G SBIB" onclick="update('<?=$responseDiv;?>/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/export/<?=str_replace('#', '', $export);?>');"><?=$transS->t($p, $export . "", $export);?></div><?

			}
			?><?
		}
		$this->executionSink()->publishEndOperation("includeExportMultipleMenu", $p);
	}
	protected function includeExportMenu($p, $exec, $transS, $configS) {
		$this->executionSink()->publishStartOperation("includeExportMenu", $p);

		$activity = Activity :: createInstance("exportMenu");
		$act = $configS->mf($p, $exec->getCrtModule(), $activity);

        if(!isset($configS)) $configS= $this->getConfigurationContext();

        $companyColor = $configS->getParameter($p, null, "companyColor");
        $rCompanyColor = $configS->getParameter($p, null, "companyReverseColor");

		if (!empty ($act)) {
			$options = $optionHelp = null;
			foreach ($act->export->attribute as $export) {
				$options .= "<div class=\"H fB $export\" title=\"".$transS->h($p, $export."_help")."\">";
				$options .= "<a href=\"#".$export."\">".$transS->t($p, $export . "", $export)."</a>";
				$options .= "</div>";
			}
			$JsCode = "" .
					"$(document).ready(function(){
					if($('#searchBar .toolbarBox .exportMenuButton').length==0){ " .
						"$('#searchBar .toolbarBox').append('<div id=\"exportMenuButton\" class=\"exportMenuButton L H \" style=\"color:". $rCompanyColor. "\">".$transS->h($p, "exportElementButton")."</div>');" .
                "$('#searchBar .toolbarBox .exportMenuButton').append('" .
							"<div class=\"cm SBB\" id=\"exportMenu\" style=\"display:none;\" >" .
							"<div class=\"exit SBB\">x</div>" .
							"$options" .
							"</div>" .
							"');";
			if($this->isWorkzoneViewDocked()) {
				$JsCode.= "setListenersToMenu('exportMenuButton', 'exportMenu', 'confirmationDialog', 'export');";
			} else {
				$JsCode.= "setListenersToMenu('exportMenuButton', 'exportMenu', 'elementDialog', 'export');";
			}
			$JsCode.= " }
			 });";
			$exec->addJsCode($JsCode);
		}

		$this->executionSink()->publishEndOperation("includeExportMenu", $p);
	}
	protected function includeRunMenu($p, $exec, $transS, $configS) {
		$this->executionSink()->publishStartOperation("includeRunMenu", $p);

		$activity = Activity :: createInstance("runMenu");
		$act = $configS->ma($p, $exec->getCrtModule(), $activity);

		if (!empty ($act)) {
			$options = $optionHelp = null;
			foreach ($act->dataflows->children() as $dfs) {
				$dfsName = $dfs->getName();
				$options .= "<div class=\"H fB $dfsName\" title=\"".$transS->h($p, $dfsName."_help")."\">";
				$options .= "<a href=\"#".$dfsName."\">".$transS->t($p, $dfsName . "", $dfs)."</a>";
				$options .= "</div>";
			}
			$exec->addJsCode("" .
					"if($('#searchBar .toolbarBox .runMenuButton').length==0){ " .
					"$('#searchBar .toolbarBox').append('<div id=\"runMenuButton\" class=\"runMenuButton L H \">".$transS->h($p, "runMenuButton")."</div>');" .
					"$('#searchBar .toolbarBox .runMenuButton').append('" .
					"<div class=\"cm SBB\" id=\"runMenu\" style=\"display:none;\" >" .
					"<div class=\"exit SBB\">x</div>" .
					"$options" .
					"</div>" .
					"');" .
					"setListenersToMenu('runMenuButton', 'runMenu', 'elementDialog', 'run');".
					" } ");
		}

		$this->executionSink()->publishEndOperation("includeRunMenu", $p);
	}
	protected function includeCoverPage($crtGroupP, $p, $exec, $transS, $configS) {
	    //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start cover page"] = microtime(true);
	    $this->debugLogger()->logBeginOperation("cover page");
        $htmlContent=null;
        $groupPortalAction = $configS->getParameter($p, $exec->getCrtModule(), "Group_portalAction");
        // redirects to portal action
        if(!empty($groupPortalAction)) {
            // evaluates any FuncExp given as a groupPortalAction
            $groupPortalAction = $this->evaluateConfigParameter($p,$exec,$groupPortalAction);
            ?><div id="groupPortalAction" class="portal" style="overflow:hidden; display:none; padding-left:10px; padding-right:10px;"><?
			if($configS->getParameter($p, $exec->getCrtModule(), "Group_portalActionRefreshOnMultipleChange") != "1"){					
				$groupPortalAction =  'groupPortalAction/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/".$groupPortalAction."/".$crtGroupP->getId();
				$exec->addJsCode("update('".$groupPortalAction."');");					
			}
			?></div><?    		
		} 
		// renders group cover page
		else if($crtGroupP->getGroup()->getDetail()!=null){

			$htmlContentRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupHtmlContent"), $exec->getCrtModule());
			$htmlContentRec->getWigiiBag()->importFromSerializedArray($crtGroupP->getGroup()->getDetail()->getHtmlContent(), $htmlContentRec->getActivity());
			$trmHtmlContent = $this->createTRM($htmlContentRec);
			$htmlContent = $trmHtmlContent->doFormatForHtmlText($htmlContentRec->getFieldValue("text"));

			if($htmlContent != null){
				?><div class="portal" style="overflow:hidden; display:none; padding-left:10px; padding-right:10px;"><?
					echo $htmlContent;
				?></div><?
			}
		}
		// shows toggle view button
		if($htmlContent != null || !empty($groupPortalAction)){
			$exec->addJsCode("" .
				"coverPage_toggleList_titleList = '".$transS->h($p, "viewElementsInPortal")."';" .
				"coverPage_toggleList_titleWebsite = '".$transS->h($p, "viewPortalContent")."';" .
				"coverPage_toggleList();" .
				"if($('#searchBar .firstBox #removeFiltersButton.R').length==1) coverPage_toggleList();" .
				"hrefWithSiteroot2js('moduleView>div.portal', 'elementDialog');" .
				"");
		} 
		//remove and hide any previous cover page settings
		else {
			$exec->addJsCode("removeCoverPageItems();");
		}
    	$this->debugLogger()->logEndOperation("cover page");
	}	
	protected function includeGroupPortal($crtGroupP, $p, $exec, $transS, $configS) {
	    $this->debugLogger()->logBeginOperation("group portal");
	    $url = null;
	    //we the group details	    
	    //if detail = null, then do nothing
	    if($crtGroupP->getGroup()->getDetail()!=null){ 	        
	        $portalRec = $this->createActivityRecordForForm($p, Activity::createInstance("groupPortal"), $exec->getCrtModule());
	        $portalRec->getWigiiBag()->importFromSerializedArray($crtGroupP->getGroup()->getDetail()->getPortal(), $portalRec->getActivity());
	        $url = $portalRec->getFieldValue("url", "url");
	        // evaluates any given FuncExp
	        $url = $this->evaluateConfigParameter($p,$exec,$url);
	        if(!empty($url)){
	            $cooKieName = $portalRec->getFieldValue("groupPortalCookieName");
	            if($portalRec->getFieldValue("groupPortalCookieIncludeRoles")){
	                $roleList = $p->getRoleListener()->getRolesPerWigiiNamespaceModule($exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl(), $exec->getCrtModule()->getModuleUrl());
	                if($roleList) $roleList = implode(";", $roleList);
	            } else $roleList = null;
	            if($cooKieName){
	                $result = getExternalCookieConnectionValue($portalRec->getFieldValue("groupPortalCookieSeparator"), $p->getRealUsername(), $portalRec->getFieldValue("groupPortalCookieCustomParameter"), $portalRec->getFieldValue("groupPortalCookieExpiration"), $roleList, $portalRec->getFieldValue("groupPortalCookieEncrypt"), $portalRec->getFieldValue("groupPortalCookieRotationKey"));
	                $exec->addJsCode("$.cookie('".$cooKieName."', '".$result."', { path: '/', domain: '".$portalRec->getFieldValue("groupPortalCookieDomain")."', secure: ".strtolower(put(HTTPS_ON))." }); ");
	            }
	            ?><div class="portal" style="overflow:hidden; "><?
					if(!preg_match('#^(((ht|f)tp(s?))\://)#i', $url)) $url = "http://".$url;
					?><a class="media {type:'html'}" href="<?=$url;?>" ></a><?
				?></div><?
				$exec->addJsCode("$('#moduleView .portal a.media').media();");
			}
		}
		$this->debugLogger()->logEndOperation("group portal");
		return $url;
	}
	protected function prepareSortByOptions($lc, $currentConfigKey, $p, $exec, $transS, $configS, $sessAS) {
	    $fieldSelectorList = $sessAS->getData($this, $currentConfigKey."_sortByFieldselectorList");
	    $sortByOptions = $sessAS->getData($this, $currentConfigKey."_sortByOptions");
	    if($fieldSelectorList==null){
	        //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start sortBy"] = microtime(true);
	        $this->debugLogger()->write("start sortBy");
	        //sortBy
	        //look if a groupBy activity is defined. Otherwise just list all available fields
	        $sortByActivity = Activity :: createInstance("sortBy");
	        $sortByOptions = array();
	        if($configS->ma($p, $exec->getCrtModule(), $sortByActivity)){
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."gather activity config"] = microtime(true);
	            $this->debugLogger()->write("gather activity config");
	            $fieldSelectorList = FieldSelectorListForActivity :: createInstance(false, false, true); //no issue if double time the same
	            $fieldSelectorList->setSelectedLanguages(array ($transS->getLanguage() => $transS->getLanguage()));
	            $configS->getFields($p, $exec->getCrtModule(), $sortByActivity, $fieldSelectorList);
	            $fieldList = FormFieldList :: createInstance(null);
	            $configS->getFields($p, $exec->getCrtModule(), null, $fieldList);
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."config gathered"] = microtime(true);
	            $this->debugLogger()->write("config gathered");
	            $trmTemp = $this->createTRM(null, false, false, false, true, false, false); //we want to be able to buffer the result
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for activity"] = microtime(true);
	            $this->debugLogger()->write("build sortByOptionArray for activity");
	            foreach($fieldSelectorList->getListIterator() as $key=>$fieldSelector){
	                if($fieldSelector->isElementAttributeSelector()) $fieldXml = null;
	                else $fieldXml = $fieldList->getField($fieldSelector->getFieldName())->getXml();
	                $xmlHeader = $fieldSelectorList->getXml($key);
	                $trmTemp->displayHeaderLabel($fieldSelector, $fieldXml, $xmlHeader, true);
	                $label = $trmTemp->getHtmlAndClean();
	                $sortByOptions[$key] = $label;
	            }
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for activity"] = microtime(true);
	            $this->debugLogger()->write("build sortByOptionArray for activity");
	        } else {
	            //create the fieldSelectorList for all fields and for element info
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."gather all config"] = microtime(true);
	            $this->debugLogger()->write("gather all config");
	            $elementFieldsXml = $configS->mf($p, $exec->getCrtModule());
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."config gathered"] = microtime(true);
	            $fieldSelectorList = FieldSelectorListArrayWebImpl::createInstance(false, false);
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for fields"] = microtime(true);
	            foreach($elementFieldsXml->children() as $elementFieldXml){
	                if($lc->byPassDataTypeForSortByFilter($elementFieldXml["type"])) continue;
	                $subFieldNames = $lc->defineDataTypeSubFieldForSortByFilter($elementFieldXml["type"]);
	                foreach($subFieldNames as $subFieldName){
	                    $key = $fieldSelectorList->addFieldSelector($elementFieldXml->getName(), $subFieldName);
	                    $label = $transS->t($p, $elementFieldXml->getName(), $elementFieldXml);
	                    if($subFieldName != "" && $subFieldName != "value") $label .= " ".$transS->t($p, $elementFieldXml["type"]."_".$subFieldName);
	                    $sortByOptions[$key] = $label;
	                }
	            }
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for subFields"] = microtime(true);
	            //add the element infos
	            $subFieldNames = $lc->defineElementSubFieldForSortByFilter();
	            foreach($subFieldNames as $subFieldName){
	                $fieldSelector = FieldSelector::createElementAttributeSelector($subFieldName);
	                $key = $fieldSelectorList->addFieldSelectorInstance($fieldSelector);
	                $label = $transS->t($p, $subFieldName);
	                $sortByOptions[$key] = $label;
	            }
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."sortByOptionArray built"] = microtime(true);
	        }
	        $sessAS->storeData($this, $currentConfigKey."_sortByFieldselectorList", $fieldSelectorList);
	        $sessAS->storeData($this, $currentConfigKey."_sortByOptions", $sortByOptions);
	        //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."sortBy stored in session"] = microtime(true);
	        $this->debugLogger()->write("sortBy stored in session");
	    } 
	    $lc->setSortByFieldSelectorList($fieldSelectorList);
	    return $sortByOptions;
	}
	protected function includeSortByMenu($lc, $sortByOptions, $p, $exec, $transS, $configS) {
	    $exec->addJsCode("" .
	        "$('#searchBar .toolbarBox .sortBy').removeClass('disabledR');" .
	        "$('#searchBar .toolbarBox .sortBy .SBB').removeClass('SBB').addClass('SBIB');" .
	        "$('#searchBar .toolbarBox .sortBy div.disabledBg').removeClass('disabledBg');");
	    if($sortByOptions[$lc->getSortedBy()]){
	        //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start define sortBy value"] = microtime(true);
	        $this->debugLogger()->write("define sortByValue");
	        $exec->addJsCode("" .
	            "$('#searchBar .toolbarBox .sortBy .direction').removeClass('".($lc->isAscending() ? "DESC" : "ASC")."').addClass('".($lc->isAscending() ? "ASC" : "DESC")."');" .
	            "$('#searchBar .toolbarBox .sortBy .value').html('<a href=\"#".$lc->getSortedBy()."\">".str_replace("'", "\\'", $sortByOptions[$lc->getSortedBy()])."</a>');");
	    }
	    if($sortByOptions){
	        //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start loading sortBy menu"] = microtime(true);
	        $this->debugLogger()->write("loading sortBy menu");
	        $options = "";
	        if($lc->isAscending()){
	            $glyphicon = "glyphicon-triangle-top";
	            $order = "ASC";
	        } else{
	            $glyphicon = "glyphicon-triangle-bottom";
	            $order = "DESC";
	        }
	        $selected = $sortByOptions[$lc->getSortedBy()];
            $options .= '<li style="color:red;"><a href="#'.$lc->getSortedBy().'" class="changeOrder '. $order .'" style="margin-left:-17px; color: red; clear:none;" title="'.str_replace("'", "\\'", strip_tags($selected)).'"><span class="glyphicon '. $glyphicon. '" aria-hidden="true"></span>'.str_replace("'", "\\'", strip_tags($selected)).'</a></li>';
	        foreach($sortByOptions as $key=>$label){
                if($label != $selected) $options .= '<li><a href="#'.$key.'" title="'.str_replace("'", "\\'", strip_tags($label)).'">'.str_replace("'", "\\'", strip_tags($label)).'</a></li>';
	        }
	        $exec->addJsCode(
	            "$('#searchBar .toolbarBox .sortBy .value').html('
                ". $options. "'
                );".
	            "setListenersToMenuBsp('searchBar .toolbarBox .sortBy .value', 'sortByMenu', 'moduleView', 'changeSortByKey', null, true);".
	            //add listener to direction
	            "$('#searchBar .toolbarBox .sortBy .direction').click(function(){ " .
	            "update('moduleView/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/changeSortByKey/'+$('a:first', $(this).next()).attr('href').replace('#', '')+'/'+$(this).hasClass('DESC'));" .
	            " });");
	    }
	}
	protected function prepareGroupyByOptions($lc, $currentConfigKey, $p, $exec, $transS, $configS, $sessAS) {
	    $fieldSelectorList = $sessAS->getData($this, $currentConfigKey."_groupByfieldselectorList");
	    $groupByOptions = $sessAS->getData($this, $currentConfigKey."_groupByOptions");
	    if($fieldSelectorList==null){
	        //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start groupBy"] = microtime(true);
	        //look if a groupBy activity is defined. Otherwise just list all available fields
	        $groupByActivity = Activity :: createInstance("groupBy");
	        $groupByOptions = array();
	        if($configS->ma($p, $exec->getCrtModule(), $groupByActivity)){
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."gather activity config"] = microtime(true);
	            $fieldSelectorList = FieldSelectorListForActivity :: createInstance(false, false, true); //no issue if double time the same
	            $fieldSelectorList->setSelectedLanguages(array ($transS->getLanguage() => $transS->getLanguage()));
	            $configS->getFields($p, $exec->getCrtModule(), $groupByActivity, $fieldSelectorList);
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."config gathered"] = microtime(true);
	            $fieldList = FormFieldList :: createInstance(null);
	            $configS->getFields($p, $exec->getCrtModule(), null, $fieldList);
	            $trmTemp = $this->createTRM(null, false, false, false, true, false, false); //we want to be able to buffer the result
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build groupByOptionArray for fields"] = microtime(true);
	            foreach($fieldSelectorList->getListIterator() as $key=>$fieldSelector){
	                if($fieldSelector->isElementAttributeSelector()) $fieldXml = null;
	                else $fieldXml = $fieldList->getField($fieldSelector->getFieldName())->getXml();
	                $xmlHeader = $fieldSelectorList->getXml($key);
	                $trmTemp->displayHeaderLabel($fieldSelector, $fieldXml, $xmlHeader, true);
	                $label = $trmTemp->getHtmlAndClean();
	                $groupByOptions[$key] = $label;
	            }
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build sortByOptionArray for activity"] = microtime(true);
	        } else {
	            //create the fieldSelectorList for all fields and for element info
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."gather all config"] = microtime(true);
	            $elementFieldsXml = $configS->mf($p, $exec->getCrtModule());
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."config gathered"] = microtime(true);
	            $fieldSelectorList = FieldSelectorListArrayWebImpl::createInstance(false, false);
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build groupByOptionArray for fields"] = microtime(true);
	            foreach($elementFieldsXml->children() as $elementFieldXml){
	                if($lc->byPassDataTypeForGroupByFilter($elementFieldXml["type"])) continue;
	                if($elementFieldXml["groupable"]=="0") continue;
	                $subFieldNames = $lc->defineDataTypeSubFieldForGroupByFilter($elementFieldXml["type"]);
	                foreach($subFieldNames as $subFieldName){
	                    $key = $fieldSelectorList->addFieldSelector($elementFieldXml->getName(), $subFieldName);
	                    $label = $transS->t($p, $elementFieldXml->getName(), $elementFieldXml);
	                    if($subFieldName != "" && $subFieldName != "value") $label .= " ".$transS->t($p, $elementFieldXml["type"]."_".$subFieldName);
	                    $groupByOptions[$key] = $label;
	                }
	            }
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."build groupByOptionArray for subFields"] = microtime(true);
	            //add the element infos
	            $subFieldNames = $lc->defineElementSubFieldForGroupByFilter();
	            foreach($subFieldNames as $subFieldName){
	                $fieldSelector = FieldSelector::createElementAttributeSelector($subFieldName);
	                $key = $fieldSelectorList->addFieldSelectorInstance($fieldSelector);
	                $label = $transS->t($p, $subFieldName);
	                $groupByOptions[$key] = $label;
	            }
	            //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."groupByOptionArray built"] = microtime(true);
	        }
	        $sessAS->storeData($this, $currentConfigKey."_groupByfieldselectorList", $fieldSelectorList);
	        $sessAS->storeData($this, $currentConfigKey."_groupByOptions", $groupByOptions);
	        //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."groupBy stored in session"] = microtime(true);
	        $this->debugLogger()->write("groupBy stored in session");
	    } else {
	        //		fput("found ".$currentConfigKey."_groupBy"." in session!");
	    }
	    $lc->setGroupByFieldSelectorList($fieldSelectorList);
	    return $groupByOptions;
	}
	protected function includeGroupByMenu($lc, $groupByOptions, $p, $exec, $transS, $configS) {
	    //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start define groupBy value"] = microtime(true);
	    $exec->addJsCode("" .
	   	    "$('#searchBar .toolbarBox .groupBy').removeClass('disabledR');" .
	   	    "$('#searchBar .toolbarBox .groupBy .SBB').removeClass('SBB').addClass('SBIB');" .
	   	    "$('#searchBar .toolbarBox .groupBy div.disabledBg').removeClass('disabledBg');");
	    $this->debugLogger()->write("start define groupBy value");
	    if($lc->getGroupBy()==null){
	        $exec->addJsCode("$('#searchBar .toolbarBox .groupBy .value').html('<a href=\"#null\">".str_replace("'", "\\'", $transS->t($p, "selectInList"))."</a>');");
	    } else if($groupByOptions[$lc->getGroupBy()]){
	        $exec->addJsCode("$('#searchBar .toolbarBox .groupBy .value').html('<a href=\"#".$lc->getGroupBy()."\">".str_replace("'", "\\'", $groupByOptions[$lc->getGroupBy()])."</a>');");
	    }
	    if($groupByOptions){
	        //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start loading groupBy menu"] = microtime(true);
	        $this->debugLogger()->write("loading groupBy menu");
	        $options = "";
	        $selected = $groupByOptions[$lc->getGroupBy()];
	        //add option to reset the groupBy to nothing
            if($selected) $options .= '<li style="color:red;"><a href="#null" style="margin-left:-20px; color: red; clear:none;" title="'.str_replace("'", "\\'", strip_tags($selected)).'"><span style="margin-right: 3px; margin-top: 3px;" class="glyphicon glyphicon-remove" aria-hidden="true"></span>'.str_replace("'", "\\'", strip_tags($selected)).'</a></li>';
	            
	            foreach($groupByOptions as $key=>$label){
	                if($label != $selected) $options .= '<li><a href="#'.$key.'" title="'.str_replace("'", "\\'", strip_tags($label)).'">'.str_replace("'", "\\'", strip_tags($label)).'</a></li>';
	            }
	            $exec->addJsCode(
	                "$('#searchBar .toolbarBox .groupBy .value').html('
                ". $options. "'
                );".
                "setListenersToMenuBsp('searchBar .toolbarBox .groupBy .value', 'groupByMenu', 'moduleView', 'changeGroupByKey', null, true);");
	    }
	}
	protected function includeAddElementButton($crtGroupP, $p, $exec, $transS, $configS) {
	    if($crtGroupP != null && $crtGroupP->getRights()!=null && $crtGroupP->getRights()->canShareElement()){
	        $crtGroupIsWritable = true;
	    }
	    else {
	        $crtGroupIsWritable = false;
	    }
	    //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start searchBar toolbar addElement"] = microtime(true);
	    $this->debugLogger()->write("start searchBar toolbar addElement");
	    if($crtGroupIsWritable){
	        $exec->addJsCode("" .
	            "$('#searchBar .toolbarBox .addNewElement')" .
	            ".addClass('Green')" .
	            ".removeClass('disabledBg')" .
	            ".unbind('click').click(function(){ ".$exec->getUpdateJsCode($p->getRealUserId(), "'+crtRoleId+'", "'+crtWigiiNamespaceUrl+'", "'+crtModuleName+'", 'elementDialog', 'addElement', "element/add/".$crtGroupP->getId())." })" .
	            ".find('font').addClass('H');");
	    } else {
	        $exec->addJsCode("" .
	            "$('#searchBar .toolbarBox .addNewElement')" .
	            ".removeClass('Green')" .
	            ".addClass('disabledBg')" .
	            ".unbind('click')" .
	            ".find('font').removeClass('H');");
	    }
	}
	protected function includeSwitchViewButton($lc, $p, $exec, $transS, $configS) {
	    $crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
	    $crtModule = $exec->getCrtModule()->getModuleUrl();
	    //$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start searchBar toolbar switchView"] = microtime(true);
	    $moduleTemplates = $lc->getAvailableTemplates($p, $exec->getCrtModule(), $configS);
	    if(count($moduleTemplates)>1){
	        $first = true;
	        foreach($lc->getAvailableTemplates($p, $exec->getCrtModule(), $configS) as $moduleView=>$moduleTemplate){
	            if($lc->getCrtView() == $moduleView) continue;
	            if($first){
	                $exec->addJsCode("" .
	                    "$('#searchBar .toolbarBox .switchView')" .
	                    ".removeClass('disabledR')" .
	                    ".html('".$transS->h($p, $moduleView."View")."')" .
	                    ".unbind('click').click(function(){ update('NoAnswer/$crtWigiiNamespace/$crtModule/switchView/$moduleView'); })" .
	                    ".show();");
	                $first = false;
	            } else {
	                $exec->addJsCode("" .
	                    "$('#searchBar .toolbarBox .switchView:first')" .
	                    ".clone().html('".$transS->h($p, $moduleView."View")."')" .
	                    ".unbind('click').click(function(){ update('NoAnswer/$crtWigiiNamespace/$crtModule/switchView/$moduleView'); })" .
	                    ".insertAfter($('#searchBar .toolbarBox .switchView:first'));");
	            }
	        }
	    } else {
	        $exec->addJsCode("" .
	            "$('#searchBar .toolbarBox .switchView')" .
	            ".addClass('disabledR')" .
	            ".unbind('click')" .
	            ".hide();");
	    }
	}
	/**
	 * Includes Module Help anchor in ListView if configuration parameter WigiiHelp_module is defined
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 */
	protected function includeModuleHelpAnchor($p,$exec) {
		// gets help anchor data
		$helpExp = $this->getModuleHelpAnchor($p, $exec);	
		if(empty($helpExp)) return;	
		// renders help anchor
		?><div class="moduleHelp"><a class="wigiiHelp" <?
			foreach($helpExp as $attr=>$val) {
				if($attr=='content') echo ' href="'.$val.'" ';
				else echo ' '.$attr.'="'.$val.'"';
			}
		?>/></div><?
	}
	/**
	 * Refreshes Module Help anchor in ListView if configuration parameter WigiiHelp_module is defined 
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 */
	protected function refreshModuleHelpAnchor($p,$exec) {
		// gets help anchor data
		$helpExp = $this->getModuleHelpAnchor($p, $exec);
		// help anchor is empty, then clears the moduleHelp div
		if(empty($helpExp)) {
			$exec->addJsCode('$("#searchBar .toolbarBox .moduleHelp").remove();');
		}
		else {
			// else sends a JS notif to refresh help anchor
			// pushes the JS notif using exec->addJsCode to take advantage of the JS cache on client side
			$helpOptions = array();
			foreach($helpExp as $attr=>$val) {
				if($attr=='content') $helpOptions['url'] = $val;
				else {
					$helpOptions[str_replace('data-popup-', '', $attr)] = $val;
				}
			}			
			$helpOptions['target'] = 'searchBar';
			$helpOptions['type'] = 'help';
			$helpOptions = json_encode((object)$helpOptions);
			$exec->addJsCode("wigii().getHelpService().showNotification($.parseJSON('".$helpOptions."'));");
		}
	}
	/**
	 * Interprets the WigiiHelp_module expression and returns an array with all the needed help info
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 * @return Array an array containing the help info
	 */
	protected function getModuleHelpAnchor($p,$exec) {
		$configS = $this->getConfigurationContext();
		// gets help expression
		$helpExp = (string)$configS->getParameter($p, $exec->getCrtModule(), "WigiiHelp_onModule");
		if(empty($helpExp)) return;
		// parses the value into a funcExp
		try {
			$helpExp = str2fx($helpExp);
		}
		catch(StringTokenizerException $ste) {
			// if syntax error, then keeps the helpExp as is because it is a constant.
			if($ste->getCode() != StringTokenizerException::SYNTAX_ERROR) throw $ste;
		}
		// executes the func exp
		if($helpExp instanceof FuncExp) {
			$fxEval = $this->getFuncExpEvaluator($p, $exec, null);
			try {
				$helpExp = $fxEval->evaluateFuncExp($helpExp,$this);	
			}
			catch(Exception $e) {
				if(isset($fxEval)) $fxEval->freeMemory();
				throw $e;
			}
			if(isset($fxEval)) $fxEval->freeMemory();
		}
		// creates help anchor configuration
		if(empty($helpExp)) return;
		elseif(is_string($helpExp)) $helpExp = array('content'=>$helpExp);
		elseif(!is_array($helpExp)) throw new FuncExpEvalException("invalid configuration parameter WigiiHelp_onModule in module ".$exec->getCrtModule()->getModuleName(),FuncExpEvalException::CONFIGURATION_ERROR);
		// renders in SW with default width:800,height:400.
		$helpExp['data-popup-position'] = 'SW';
		if(!$helpExp['data-popup-width']) $helpExp['data-popup-width'] = 800;
		if(!$helpExp['data-popup-height']) $helpExp['data-popup-height'] = 400;
		return $helpExp;
	}
	public function getIconForDialog($iconType) {
		$html = "";
		switch ($iconType) {
			case "forbidden" :
				$html .= '<img src="' . SITE_ROOT_forFileUrl . "images/icones/tango/32x32/status/not-available.png" . '" style="vertical-align:middle;margin:0px 15px 0px 5px;" />';
				break;
			case "done" :
				$html .= '<img src="' . SITE_ROOT_forFileUrl . "images/icones/tango/32x32/status/available.png" . '" style="vertical-align:middle;margin:0px 15px 0px 5px;" />';
				break;
			case "warning" :
				$html .= '<img src="' . SITE_ROOT_forFileUrl . "images/icones/tango/32x32/status/dialog-warning.png" . '" style="vertical-align:middle;margin:0px 15px 0px 5px;" />';
				break;
			case "question" :
				$html .= '<img src="' . SITE_ROOT_forFileUrl . "images/icones/tango/32x32/status/not-known.png" . '" style="vertical-align:middle;margin:0px 15px 0px 5px;" />';
				break;
			case "info" :
				$html .= '<img src="' . SITE_ROOT_forFileUrl . "images/icones/tango/32x32/status/important.png" . '" style="vertical-align:middle;margin:0px 15px 0px 5px;" />';
				break;
			case "alert" :
				$html .= '<img src="' . SITE_ROOT_forFileUrl . "images/icones/tango/32x32/status/exclamation-mark.png" . '" style="vertical-align:middle;margin:0px 15px 0px 5px;" />';
				break;
			case "config" :
				$html .= '<img src="' . SITE_ROOT_forFileUrl . "images/icones/tango/32x32/categories/applications-system.png" . '" style="vertical-align:middle;margin:0px 15px 0px 5px;" />';
				break;
			case "pref" :
				$html .= '<img src="' . SITE_ROOT_forFileUrl . "images/icones/tango/32x32/categories/preferences-system.png" . '" style="vertical-align:middle;margin:0px 15px 0px 5px;" />';
				break;
			case "password" :
				$html .= '<img src="' . SITE_ROOT_forFileUrl . "images/icones/46px/key.png" . '" style="vertical-align:middle;margin:0px 15px 0px 5px;" />';
				break;
			default :
				break;
		}
		return $html;
	}
	
	public function operationSuccessfullMessage($domId, $width, $messageTitle, $messageText, $iconType = "info"){
		$exec = ServiceProvider :: getExecutionService();
		$transS = ServiceProvider :: getTranslationService();
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
		//Class need to have capital C to be compatible with IE7 and 8....
		//$iconType = "ui-icon-".$iconType;
		if($this->isWorkzoneViewDocked()) $domId = 'confirmationDialog';
		$html = '';
		if($messageText || $messageTitle){
			$html .= '<div>';
			$html .= $this->getIconForDialog($iconType);
			if ($messageTitle) $html .= '<strong style="vertical-align:middle;">' . $messageTitle . '</strong>';
			$html .= '</div><br />';
			if ($messageText) $html .= '<div>'.$messageText.'</div>';
		}	
		if ($exec->getIsUpdating() && !$this->isWorkzoneViewDocked()) {
			echo $html;
		} else {
			$exec->addJsCode(' $("#' . $domId . '").html(\'' . str_replace("//", "\/\/", str_replace("'", "\'", $html)) . '\'); ');
		}
		$jsCode = "";	
		$jsCode .= '' .
				'if($("#' . $domId . '").is(":ui-dialog")) { $("#' . $domId . '").dialog("destroy"); } $("#' . $domId . '").dialog({'.
				'width:' . ($width) . ',' .
				'closeOnEscape: false, resizable:false' .
				'}).dialog("moveToTop");' .
				'$("#' . $domId . '").css("min-height", "0").prev().css("display","none");' .
				'if(checkOpenItemTemp_url==null) {setTimeout(function(){ $("#' . $domId . '").dialog("destroy"); }, 1000); }' .
				'else {$("#' . $domId . '").html($("#' . $domId . '").html()+"<br />'.$transS->t($p, "operationDoneWaitNextAction").'");}; ';
		$jsCode .= 'if(isWorkzoneViewDocked() && $(".elementDialog.docked").children().length==0){manageWorkzoneViewDocked(\'hide\')};';		
		$exec->addJsCode($jsCode);
	}
	public function openAsMessage($domId, $width, $messageTitle, $messageText, $okJsCode = null, $okLabel = "Ok", $forceQuitJsCode = null, $forceQuitLabel = null, $iconType = "info") {
		$exec = ServiceProvider :: getExecutionService();
		//Class need to have capital C to be compatible with IE7 and 8....
		//$iconType = "ui-icon-".$iconType;
		$html = '';
		if($messageText || $messageTitle){
			$html .= '<div>';
			$html .= $this->getIconForDialog($iconType);
			if ($messageTitle) $html .= '<strong style="vertical-align:middle;">' . $messageTitle . '</strong>';
			$html .= '</div><br />';
			if ($messageText) $html .= '<div>'.$messageText.'</div>';
		}
		if ($exec->getIsUpdating()) {
			echo $html;
		} else {
			$exec->addJsCode(' $("#' . $domId . '").html(\'' . str_replace("//", "\/\/", str_replace("'", "\'", $html)) . '\'); ');
		}
		$jsCode = "";
		$jsCode .= '' .
				'if($("#' . $domId . '").is(":ui-dialog")) { $("#' . $domId . '").dialog("destroy"); } $("#' . $domId . '").dialog({' .
				'buttons: [' .
				'{' .
				'text: "' . $okLabel . '",' .
				'click: function(){ actOnCancelDialog("' . $domId . '"); $(this).dialog("destroy"); $(window).scrollTop(0); ' . $okJsCode . ' },' .
				'Class: "ok"' .
				'}' .
				'';
		if ($forceQuitJsCode != null) {
			$jsCode .= '
			,{
				text: "' . $forceQuitLabel . '",
				click: function(){ $(this).dialog("destroy"); $(window).scrollTop(0); ' . $forceQuitJsCode . ' },
				Class: "quit"
			}
		';
		}
		$jsCode .= '' .
				'],' .
				'width:' . ($width) . ',' .
				'closeOnEscape: false, resizable:false' .
				'}).dialog("moveToTop");' .
				'$("#' . $domId . '").prev().css("display","none");' .
				'; ';
		$exec->addJsCode($jsCode);
	}
	
	public function openAsConfirmationDialog2B($domId, $width, $okJsCode, $cancelJsCode, $okLabel = "Ok", $cancelLabel = "Cancel", $modal = true, $preventOkClosing=false) {
		$exec = ServiceProvider :: getExecutionService();
		//Class need to have capital C to be compatible with IE7 and 8....
		$exec->addJsCode('' .
				'myPosition = dialogPos["' . $domId . '"]; if(myPosition == null){ myPosition = { my : "center", at: "center" } }' .
				'if($("#' . $domId . '").is(":ui-dialog")) { $("#' . $domId . '").dialog("destroy"); } $("#' . $domId . '").dialog({' .
				'buttons: [' .
				'{' .
				'text: "' . $okLabel . '",' .
				'click: function(){ ' . $okJsCode . ($preventOkClosing ? '' : '	$(this).dialog("destroy"); $(window).scrollTop(0); ').'},' .
				'Class: "ok"' .
				'},' .
				'{' .
				'text: "' . $cancelLabel . '",' .
				'click: function(){ ' . $cancelJsCode . ' actOnCancelDialog("' . $domId . '"); $(this).dialog("destroy"); $(window).scrollTop(0); },' .
				'Class: "cancel"' .
				'}' .
				'],' .
				'dragStop: function(event, ui){ dialogPos["' . $domId . '"] = { my : "left top", at: "left+"+$(this).parent().offset().left+" top+"+$(this).parent().offset().top }; },' .
				'width:' . ($width) . ', position: myPosition,' .
				'modal:' . strtolower(put($modal)) . ', closeOnEscape: false, resizable:false' .
				'}).dialog("moveToTop");' .
				'$("#' . $domId . '").prev().css("display", "none");' .
				'$("#' . $domId . '").parent().find("div.ui-dialog-buttonpane button:last").focus();' .
				'; ');
	}
	public function openAsConfirmationDialog($domId, $width, $okJsCode, $noJsCode, $cancelJsCode, $okLabel = "Ok", $noLabel = "No", $cancelLabel = "Cancel") {
		$exec = ServiceProvider :: getExecutionService();
		//Class need to have capital C to be compatible with IE7 and 8....
		$exec->addJsCode('' .
				'myPosition = dialogPos["' . $domId . '"]; if(myPosition == null){ myPosition = { my : "center", at: "center" }; }' .
				'if($("#' . $domId . '").is(":ui-dialog")) { $("#' . $domId . '").dialog("destroy"); } $("#' . $domId . '").dialog({' .
				'buttons: [' .
				'{' .
				'text: "' . $okLabel . '",' .
				'click: function(){ ' . $okJsCode . '	$(this).dialog("destroy"); $(window).scrollTop(0);},' .
				'Class: "ok"' .
				'},' .
				'{' .
				'text: "' . $noLabel . '",' .
				'click: function(){ ' . $noJsCode . ' $(this).dialog("destroy"); $(window).scrollTop(0);},' .
				'Class: "no"' .
				'},' .
				'{' .
				'text: "' . $cancelLabel . '",' .
				'click: function(){ ' . $cancelJsCode . ' actOnCancelDialog("' . $domId . '"); $(this).dialog("destroy"); $(window).scrollTop(0); },' .
				'Class: "cancel"' .
				'}' .
				'],' .
				'dragStop: function(event, ui){ dialogPos["' . $domId . '"] = { my : "left top", at: "left+"+$(this).parent().offset().left+" top+"+$(this).parent().offset().top }; },' .
				'width:' . ($width) . ', position: myPosition,' .
				'modal:true, closeOnEscape: false, resizable:false' .
				'}).dialog("moveToTop");' .
				'$("#' . $domId . '").prev().css("display", "none");' .
				'; ');
	}
	
	public function openAsDialogForm($domId, $width, $okJsCode = null, $dialogTitle = null, $okLabel = "Ok", $cancelLabel = "Cancel", $cancelJsCode = null, $defaultPosition = "{ my : 'center', at: 'center' }", $closeJsCode = null, $modal = false, $forcePosition = false, $scrollTopOnEnd = false, $preventOkClosing=false) {
		$exec = ServiceProvider :: getExecutionService();
		$transS = ServiceProvider :: getTranslationService();
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
		
		if($this->isWorkzoneViewDocked() && $domId=='elementDialog' && !ServiceProvider::getExecutionService()->getCrtModule()->isAdminModule()) {
			//27.09.2016 added in form position relative to ensure that help popup position is correctly calculated in WigiiAPI.js
			$exec->addJsCode("
					manageWorkzoneViewDocked('show',".$this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "elementTotalWidth").");
					var elementDialogButtons =$('#elementDialog .publicFormBorder').find('button');
					elementDialogButtons.last().click(function(){".$okJsCode."});
					elementDialogButtons.first().click(function(){".$cancelJsCode . ' ' . $closeJsCode . ' actOnCancelDialog("' . $domId . '");  ' . ($scrollTopOnEnd ? ' $(window).scrollTop(0); ' : '')." manageWorkzoneViewDocked('hide')});
					if(!$('#scrollElement').children().first().hasClass('elementDetail')) $('#scrollElement').children().first().css('position','relative');
			".
                '$("#scrollElement").css("max-width", "'. ($width + 17). 'px");
                $("#scrollElement").css("width", "100%");
                margin = (($(window).width()-'. ($width + 17). ')/2);
                $(".ui-dialog").css("left", margin+"px");
                $(window).resize(function(){
                    if($(window).width() < ' . ($width + 17) . '){
                        $(".ui-dialog").css("left", "0px");
                        $(".ui-dialog").css("width", "100%");
                        $(".ui-dialog").css("max-width", '. ($width + 17). ');
                    }else{
                        margin = (($(window).width()-'. ($width + 17). ')/2);
                        $(".ui-dialog").css("left", margin+"px");
                        $(".ui-dialog").css("width", '. ($width + 17). ');
                        
                    }
                 });');
			return true;
		}		
		
		//change the position of feedbackDialog on docked mode
		if($this->isWorkzoneViewDocked() && $domId=='feedbackDialog'){
			$defaultPosition = '{ my : "center", at: "center", of: window }';
		}
		//Class need to have capital C to be compatible with IE7 and 8....
		$exec->addJsCode('' .
				'myPosition = dialogPos["' . $domId . '"]; ' . ($forcePosition ? ' myPosition = ' . $defaultPosition . '; ' : ' if(myPosition == null){ myPosition = ' . $defaultPosition . '; } ') . '' .
				'if($("#' . $domId . '").is(":ui-dialog")) { $("#' . $domId . '").dialog("destroy"); } $("#' . $domId . '").dialog({' .
				'buttons: [' .
				'{' .
				'text: "' . $okLabel . '",' .
				'click: function(){ ' . $okJsCode . ' ' . ($preventOkClosing ? '' : $closeJsCode . ' $(this).dialog("destroy"); ' . ($scrollTopOnEnd ? ' $(window).scrollTop(0); ' : '') ). ' },' .
				'Class: "ok"' .
				'},' .
				'{' .
				'text: "' . $cancelLabel . '",' .
				'click: function(){ ' . $cancelJsCode . ' ' . $closeJsCode . ' actOnCancelDialog("' . $domId . '"); $(this).dialog("destroy"); ' . ($scrollTopOnEnd ? ' $(window).scrollTop(0); ' : '') . '},' .
				'Class: "cancel"' .
				'}' .
				'],' .
				'dragStop: function(event, ui){ dialogPos["' . $domId . '"] = { my : "left top", at: "left+"+$(this).parent().offset().left+" top+"+$(this).parent().offset().top }; },' .
				'title: "' . $dialogTitle . '", width: "100%", position: myPosition,' .
				'closeOnEscape: true, resizable:false, ' .
				'' . ($modal ? 'modal:true, ' : '') . '' .
				'beforeClose: function(){ if(checkOpenItem("' . $domId . '")){ return false; } else { ' . $closeJsCode . ' actOnCloseDialog("' . $domId . '");} },' .
				'}).dialog("moveToTop");' .
				'if($("#' . $domId . '").prop("id")=="elementDialog" || $("#' . $domId . '").prop("id")=="emailingDialog") {'.
				'	$("#' . $domId . '").css("height",getElementDialogScrollHeight("neighbour", $("#elementDialog"))).css("overflow-x","auto");' .
				'};'.
				'resize_scrollArea();');

		        $exec->addJsCode( '
		        ariaForm = "[aria-describedBy=\"' . $domId . '\"]";
		        
		        margin = (($(window).width()-'. ($width + 17). ')/2);
                $(ariaForm).css("left", margin+"px");
                $(ariaForm).css("top", "0px");
                $(ariaForm).css("width", "100%");
                $(ariaForm).css("max-width", '. ($width + 17). ');
        
                if($(window).width() < ' . ($width + 17) . '){
                    $(ariaForm).css("left", "0px");
                    $(ariaForm).css("top", "0px");
                    $(ariaForm).css("width", "100%");
                    $(ariaForm).css("max-width", '. ($width + 17). ');
                }else{
                    margin = (($(window).width()-'. ($width + 17). ')/2);
                    $(ariaForm).css("top", "0px");
                    $(ariaForm).css("left", margin+"px");
                }
        
                $(window).resize(function(){
                    if($(window).width() < ' . ($width + 17) . '){
                        $(ariaForm).css("left", "0px");
                        $(ariaForm).css("top", "0px");
                        $(ariaForm).css("width", "100%");
                        $(ariaForm).css("max-width", '. ($width + 17). ');
                    }else{
                        margin = (($(window).width()-'. ($width + 17). ')/2);
                        $(ariaForm).css("width", "100%");
                        $(ariaForm).css("top", "0px");
                        $(ariaForm).css("left", margin+"px");
                    }
                    if("'. $domId.'" == "multipleDialog"){
                        margin = ($(window).width()-($(ariaForm).width()+23));
                        marginTop = ($(window).height()-($(ariaForm).height()+17));
                        $(ariaForm).css("left", margin+"px");
                        $(ariaForm).css("top", marginTop+"px");
                     }
                });
                
                 if("'. $domId.'" == "multipleDialog"){
                    margin = ($(window).width()-($(ariaForm).width()+23));
                    marginTop = ($(window).height()-($(ariaForm).height()+4));
                    $(ariaForm).css("left", margin+"px");
                    $(ariaForm).css("top", marginTop+"px");
                 }
                ');
		/*$exec->addJsCode('$("#elementDialog")
				.css("height",window.innerHeight-$("#elementDialog").prev().outerHeight(true)-$("#elementDialog").next().outerHeight(true)-30)
				.css("overflow-x","auto")
				.scroll(function() {
					self = $(this);
					if(self.scrollTop()+self.outerHeight(true) == this.scrollHeight) {
						self.next().css("display","none");
					} else {
						self.next().css("display","block");
					}
				}).after("<div class=\"scrollGradient\" style=\"bottom:"+($("#elementDialog").next().outerHeight(true))+"px\"></div>");
				console.log("script executed");					
				
				
				');
		*/
		/*
		 	$("<div />").css({
				    position: "absolute",
				    width: "100%",
				    height: "100%",
				    left: 0,
				    top: 0,
				    zIndex: 1000000,  // to be on the safe side
				    background: "url(/img/loading.gif) no-repeat 50% 50%"
				}).appendTo("#elementDialog"); 
		 */
	
		if ($dialogTitle == null) {
			$exec->addJsCode(' $("#' . $domId . '").prev().css("display","none"); ');
		}
	}
	public function openAsDialogForm3B($domId, $width, $okJsCode = null, $dialogTitle = null, $okLabel = "Ok", $intermediateLabel = "Skip", $cancelLabel = "Cancel", $cancelJsCode = null, $intermediateJsCode = null, $defaultPosition = '{ my : "center", at: "center" }', $closeJsCode = null, $modal = false, $forcePosition = false, $scrollTopOnEnd = false, $closeDialogAfterOk = true, $closeDialogAfterIntermediate = true) {
		$exec = ServiceProvider :: getExecutionService();
		if($this->isWorkzoneViewDocked() && $domId=='elementDialog' && !ServiceProvider::getExecutionService()->getCrtModule()->isAdminModule()) {
			$exec->addJsCode('manageWorkzoneViewDocked("hide");');
			$okJsCode = "$('form', this).submit(); manageWorkzoneViewDocked('clear');";
// 			$cancelJsCode .= 'manageWorkzoneViewDocked("hide");';
// 			$intermediateJsCode .= 'manageWorkzoneViewDocked("hide");';
		};
		$transS = ServiceProvider :: getTranslationService();
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
		//Class need to have capital C to be compatible with IE7 and 8....
		$exec->addJsCode('' .
				'$("#scrollElement").css({"height":"","width":""});'.
				'myPosition = dialogPos["' . $domId . '"]; ' . ($forcePosition ? ' myPosition = ' . $defaultPosition . '; ' : ' if(myPosition == null){ myPosition = ' . $defaultPosition . '; } ') . '' .
				'if($("#' . $domId . '").is(":ui-dialog")) { $("#' . $domId . '").dialog("destroy"); } $("#' . $domId . '").dialog({' .
				'buttons: [' .
				'{' .
				'text: "' . $okLabel . '",' .
				'click: function(){ ' . $okJsCode . ' ' . $closeJsCode . ' ' . ($closeDialogAfterOk ? ' $(this).dialog("destroy"); ' : '') . ' ' . ($scrollTopOnEnd ? ' $(window).scrollTop(0); ' : '') . ' },' .
				'Class: "ok"' .
				'},' .
				'{' .
				'text: "' . $intermediateLabel . '",' .
				'click: function(){ ' . $intermediateJsCode . ' ' . $closeJsCode . ' ' . ($closeDialogAfterIntermediate ? ' $(this).dialog("destroy");' : '') . ' ' . ($scrollTopOnEnd ? ' $(window).scrollTop(0); ' : '') . '},' .
				'Class: "intermediate"' .
				'},' .
				'{' .
				'text: "' . $cancelLabel . '",' .
				'click: function(){ ' . $cancelJsCode . ' ' . $closeJsCode . (!$cancelJsCode ? ' actOnCancelDialog("' . $domId . '"); ' : '').' $(this).dialog("destroy"); ' . ($scrollTopOnEnd ? ' $(window).scrollTop(0); ' : '') . '},' .
				'Class: "cancel"' .
				'}' .
				'],' .
				'dragStop: function(event, ui){ dialogPos["' . $domId . '"] = { my : "left top", at: "left+"+$(this).parent().offset().left+" top+"+$(this).parent().offset().top }; },' .
				'title: "' . $dialogTitle . '", width:' . ($width) . ', position: myPosition,' .
				'closeOnEscape: true, resizable:false, ' .
				'' . ($modal ? 'modal:true, ' : '') . '' .
				'beforeClose: function(){ if(checkOpenItem("' . $domId . '")){ return false; } else { ' . $closeJsCode . ' actOnCloseDialog("' . $domId . '");} }' .
				'}).dialog("moveToTop");' .
				' $(".elementDialog").css("float","none"); ' .
				''.
        ' margin = (($(window).width()-'. ($width + 17). ')/2);
        ariaForm3B = "[aria-describedBy=\"' . $domId . '\"]";
        if($(window).width() < ' . ($width + 17) . '){
            $(ariaForm3B).css("left", "0px");
            $(ariaForm3B).css("width", "100%");
            $(ariaForm3B).css("max-width", '. ($width + 17). ');
        }else{
            margin = (($(window).width()-'. ($width + 17). ')/2);
            $(ariaForm3B).css("left", margin+"px");
            /* $(ariaForm3B).css("height", $(window).height()); */
        }

        $(window).resize(function(){
            if($(window).width() < ' . ($width + 17) . '){
                $(ariaForm3B).css("left", "0px");
                $(ariaForm3B).css("width", "100%");
                $(ariaForm3B).css("max-width", '. ($width + 17). ');
            }else{
                margin = (($(window).width()-'. ($width + 17). ')/2);
                $(ariaForm3B).css("left", margin+"px");
                $(ariaForm3B).css("width", '. ($width + 17). ');
            }
        });

        ');
		if ($dialogTitle == null) {
			$exec->addJsCode(' $("#' . $domId . '").prev().css("display","none"); ');
		}
	}
	public function openAsDialog($domId, $width, $dialogTitle = null, $closeJsCode = null) {
		$exec = ServiceProvider :: getExecutionService();
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();

		if($this->isWorkzoneViewDocked() && $domId=='elementDialog' && !ServiceProvider::getExecutionService()->getCrtModule()->isAdminModule()) {
			$exec->addJsCode("
				if($('#elementDialog').is(':ui-dialog')) {
					$('#elementDialog').dialog('destroy');
				}
				manageWorkzoneViewDocked('show',".$this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "elementTotalWidth").");".
				'$("#scrollElement").css("max-width", "'. ($width + 17). 'px");
                $("#scrollElement").css("width", "100%");
			');
			return true;
		}
		$exec->addJsCode('' .
				'myPosition = dialogPos["' . $domId . '"]; if(myPosition == null){ myPosition = { my : "center", at: "center" }; }' .
				'if($("#' . $domId . '").is(":ui-dialog")) { $("#' . $domId . '").dialog("destroy"); } $("#' . $domId . '").dialog({' .
				'title: "' . $dialogTitle . '", width:' . ($width + 17) . ', position: myPosition,' .
            'dragStop: function(event, ui){ dialogPos["' . $domId . '"] = { my : "left top", at: "left+"+$(this).parent().offset().left+" top+"+$(this).parent().offset().top }; },' .
				'beforeClose: function(){ ' . $closeJsCode . ' actOnCloseDialog("' . $domId . '"); $(this).dialog("destroy"); $(window).scrollTop(0); },' .
				'closeOnEscape: true, resizable:false' .
				'}).dialog("moveToTop")'.
				'; '.
            '
            aria = "[aria-describedBy=\"'.$domId .'\"]";
		        
            margin = (($(window).width()-'. ($width + 17). ')/2);
                  $(aria).css("left", margin+"px");
                  $(aria).css("width", "100%");
                  $(aria).css("max-width", '. ($width + 17). ');
                  
                  
                  if($(window).width() < ' . ($width + 17) . '){
                        $(aria).css("left", "0px");
                        $(aria).css("top", "0px");
                        $(aria).css("width", "100%");
                        $(aria).css("max-width", '. ($width + 17). ');
                    }else{
                        margin = (($(window).width()-'. ($width + 17). ')/2);
                        $(aria).css("left", margin+"px");
                    }
                  
            $(window).resize(function(){
                    if($(window).width() < ' . ($width + 17) . '){
                        $(aria).css("left", "0px");
                        $(aria).css("top", "0px");
                        $(aria).css("width", "100%");
                        $(aria).css("max-width", '. ($width + 17). ');
                    }else{
                        margin = (($(window).width()-'. ($width + 17). ')/2);
                        $(aria).css("left", margin+"px");
                        $(aria).css("width", '. ($width + 17). ');
                    }
                 });');
		$exec->addJsCode('addScrollWithShadow("elementDialog");
						resize_scrollArea();');
		if ($dialogTitle == null) {
			$exec->addJsCode(' $("#' . $domId . '").prev().css("display","none"); ');
		}
	}
	
	/**
	 * Sends some JS code to resize ListView (or BlogView or CalendarView) depending of element size and config parameters :
	 * - minWidth: Integer. Min width of list view (defined in activity)
	 * - GroupList_collapsed: Boolean. If true group panel is always collapsed by default (defined as module parameter)
	 * - ListView_collapsed: Boolean. If true listView (blogView or calendarView) is always collapsed by default (defined as module parameter)
	 */
	public function manageListViewDockedSize() {
		$exec = ServiceProvider :: getExecutionService();
		
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();
		$GroupListCollapsed = ($this->getConfigurationContext()->getParameter($p,$exec->getCrtModule(),'GroupList_collapsed')==1) | false;
		$ListViewCollapsed = ($this->getConfigurationContext()->getParameter($p,$exec->getCrtModule(),'ListView_collapsed')==1) | false;
			
		$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
		$moduleViewXml = $this->getConfigurationContext()->ma($p, $exec->getCrtModule(), Activity::createInstance($lc->getCrtViewActivityName()));
		$minWidth = trim((string)$moduleViewXml['minWidth']);
		
		if(empty($minWidth) || strtoupper($minWidth) == 'NULL') $minWidth = 100;

		$exec->addJsCode("
			$('#groupPanel').data('GroupListCollapsed',".$GroupListCollapsed.");
			$('#moduleView').data('minWidth',".(integer)$minWidth.").data('ListViewCollapsed',".$ListViewCollapsed.");
		");
	}
	
	public function closeStandardsDialogs() {
		$exec = ServiceProvider :: getExecutionService();
		$exec->addJsCode('closeStandardsDialogs();');
	}
	
	public function displayLockMessage($p, $exec, $jsCode, $dbEntityName, $dbEntity) {
		$transS = ServiceProvider :: getTranslationService();
		$dbAdminS = ServiceProvider :: getDbAdminService();
		if ($exec->getIsUpdating()) {
			echo ExecutionServiceImpl :: answerRequestSeparator;
			echo "confirmationDialog";
			echo ExecutionServiceImpl :: answerParamSeparator;
		}
		if(strtolower($dbEntityName)=="elements"){
			$this->displayListOfLockedItems($p, $dbEntity);
		} else {
			if(strtolower($dbEntityName)=="element" && $dbEntity->isSubElement()){
				$transS->setSubExecutionModule($dbEntity->getModule());
			}
			$this->openAsMessage("confirmationDialog", 300, $transS->t($p, $dbEntityName . "Locked"), $transS->t($p, $dbEntityName . "LockedExplanation")." (max ".round($dbAdminS->getLockExpirationMicrosec()/(1000000*3600),1)."h)", $jsCode);
		}
	}
	public function displayNotFoundMessage($p, $exec, $jsCode, $dbEntityName, $dbEntityId) {
		$transS = ServiceProvider :: getTranslationService();
		if ($exec->getIsUpdating()) {
			echo ExecutionServiceImpl :: answerRequestSeparator;
			echo "confirmationDialog";
			echo ExecutionServiceImpl :: answerParamSeparator;
		}
		$this->openAsMessage("confirmationDialog", 300, $transS->t($p, $dbEntityName . "NotFound").": #".$dbEntityId, $transS->t($p, $dbEntityName . "NotFoundExplanation"), $jsCode);
	}
	public function displayNotAvailableMessage($p, $exec, $jsCode, $dbEntityName, $dbEntityId) {
		$transS = ServiceProvider :: getTranslationService();
		if ($exec->getIsUpdating()) {
			echo ExecutionServiceImpl :: answerRequestSeparator;
			echo "confirmationDialog";
			echo ExecutionServiceImpl :: answerParamSeparator;
		}
		$this->openAsMessage("confirmationDialog", 300, $transS->t($p, $dbEntityName . "NotAvailable").": #".$dbEntityId, $transS->t($p, $dbEntityName . "NotAvailableExplanation"), $jsCode);
	}	
	
	/**
	 * fetches the table of elements
	 * Do not define the fieldSelectorList, this will be defined automatically from the config
	 * @param $onlyRows = false, if true only the data rows are generated (only a list of tr)
	 * @param $onlyRowsContent = false, if true only the content of the row is generated (without the tr)
	 */
	protected function getAllElementsInListView($p, $exec, $listContext, $onlyRows = false, $onlyRowsContent = false, $desiredPage = null, $resetGroupBy=false) {
		$this->executionSink()->publishStartOperation("getAllElementsInListView");
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start getAllElementsInListView"] = microtime(true);
		$transS = ServiceProvider :: getTranslationService();
		$configS = $this->getConfigurationContext();
		$elS = ServiceProvider :: getElementService();
	
		//update the listContext to match to the listView parameter
		$fsl = FieldSelectorListForActivity :: createInstance(false, false, true); // Medair (CWE) 01.05.2017 forces subfield value to allow sorting by clicking on headers
		$fsl->setSelectedLanguages(array (
				$transS->getLanguage() => $transS->getLanguage()));
		$configS->getFields($p, $exec->getCrtModule(), Activity :: createInstance("listView"), $fsl);
	
		$fieldList = FormFieldList :: createInstance(null);
		$configS->getFields($p, $exec->getCrtModule(), null, $fieldList);
		foreach ($fieldList->getListIterator() as $field) {
			if ($field->isCalculated() && $field->shouldCalculateOnFetch() && ($fsl->containsField($field->getFieldName()) || ($listContext->getGroupByItemFieldSelector() != null && $listContext->getGroupByItemFieldSelector()->getFieldName() == $field->getFieldName()))) {
				$field->getFuncExpDependencies($fsl);
			}
			if($fsl->containsField($field->getFieldName())) {
				if ($field->getDataType() && $field->getDataType()->getDataTypeName() == "Emails") {
					//if Emails then take all the subfields, there are usefull to display confirmation status, etc.
					$fsl->addFieldSelector($field->getFieldName(), "proofStatus");
					$fsl->addFieldSelector($field->getFieldName(), "proofKey");
					$fsl->addFieldSelector($field->getFieldName(), "proof");
					$fsl->addFieldSelector($field->getFieldName(), "externalConfigGroup");
					$fsl->addFieldSelector($field->getFieldName(), "externalAccessLevel");
					$fsl->addFieldSelector($field->getFieldName(), "externalCode");
					$fsl->addFieldSelector($field->getFieldName(), "externalAccessEndDate");
					$fsl->addFieldSelector($field->getFieldName(), "value");
				}
				elseif ($field->getDataType() && $field->getDataType()->getDataTypeName() == "Files") {
					//if Files then take all the subfields, there are usefull to display download, dates, size, etc.
					$fsl->addFieldSelector($field->getFieldName(), "name");
					$fsl->addFieldSelector($field->getFieldName(), "type");
					$fsl->addFieldSelector($field->getFieldName(), "size");
					$fsl->addFieldSelector($field->getFieldName(), "mime");
					$fsl->addFieldSelector($field->getFieldName(), "date");
					$fsl->addFieldSelector($field->getFieldName(), "user");
					$fsl->addFieldSelector($field->getFieldName(), "username");
					$fsl->addFieldSelector($field->getFieldName(), "version");
					$fsl->addFieldSelector($field->getFieldName(), "textContent");
				}
			}
		}
	
		$listContext->setFieldSelectorList($fsl);
		if($resetGroupBy){
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."reset groupBy"] = microtime(true);
			$this->executionSink()->log("reset groupBy");
			$listContext->setGroupBy("reset");
		}
		$listContext->addGroupByFieldSelector($fieldList);
		$listContext->addSortByFieldSelector($fieldList);
	
		if (!$onlyRows) {
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."recalculate search bar"] = microtime(true);
			$this->executionSink()->log("recalculate search bar");
			$listContext->recalculateSearchBar($p, $this); //to make sure to recalculate from the post and the new config
		}
	
		//creating the list
		$elementPList = ElementPListRowsForElementListImpl :: createInstance($this, $listContext);
		
		$elementPList->setFieldList($fieldList);
		$elementPList->setP($p);
		$elementPList->setExec($exec);
		$elementPList->setElementPolicyEvaluator($this->getElementPolicyEvaluator($p, $exec->getCrtModule()));
	
		if ($onlyRows) {
			$elementPList->doOnlyRows($onlyRowsContent);
		}
		$elementPList->actOnBeforeAddElementP();
	
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start getSelectedElementsInGroups"] = microtime(true);
		$this->executionSink()->log("start getSelectedElementsInGroups");
		if ($listContext->doesGroupListIncludeChildren() || $listContext->getGroupPList()->count() == 1) {
			if($listContext->isGroupByOnlyDuplicates()){
				$groupLogExp = $listContext->getGroupLogExp();
				// remove trashbin if exists
				$trashBinGroup = (string)$configS->getParameter($p, $exec->getCrtModule(), "trashBinGroup");
				if($trashBinGroup && ($groupLogExp instanceof LogExpInGroup)) {
					$groupLogExp = $groupLogExp->reduceNegation(true);
					$lx = $groupLogExp->getGroupSelectionLogExp();
					if(isset($lx) && ($lx instanceof LogExpBin) && ($lx->getOperator() == 'IN')) {
						$ids = $lx->getValue();
						if(is_array($ids)) {
							$ids = array_diff($ids, array($trashBinGroup));
							if(!empty($ids)) {
								$lx->setValue($ids);
							}
						}
					}
				}
				
				// Medair 20.03.2017: computes duplicate Ids and filters on duplicated element
                $duplicatedIds = $listContext->getDuplicatesIds();
                if(empty($duplicatedIds)) {
                    $duplicatedIds = ValueListArrayImpl::createInstance();
                    $elS->findDuplicatesFromSelectedElementsInGroups($p, $groupLogExp, $listContext->getGroupByItemFieldSelector(), $duplicatedIds);
                    $duplicatedIds = $duplicatedIds->getListIterator();
                    $listContext->setDuplicatesIds($duplicatedIds);
                }
                // adds filter on duplicate Ids
                $lx = $listContext->getFieldSelectorLogExp();
                if(!empty($duplicatedIds)) {
                    if(isset($lx)) $lx = lxAnd(lxIn(fs_e('id'), $duplicatedIds), $lx);
                    else $lx = lxIn(fs_e('id'), $duplicatedIds);
                }
                //if no duplicate generate negative where clause to show an empty list
                else {
                    if(isset($lx)) $lx = lxAnd(lxEq(fs_e('id'), null), $lx);
                    else $lx = lxEq(fs_e('id'), null);
                }
                $listContext->setFieldSelectorLogExp($lx);

			}
			else {
				$groupLogExp = $listContext->getGroupLogExp();
			}
			
			$nbRow = $elS->getSelectedElementsInGroups($p, $groupLogExp,
					$elementPList,
					$listContext
			);
			
		} else {
			$nbRow = 0;
		}

		$total = $listContext->getTotalNumberOfObjects();
		$elementPList->actOnFinishAddElementP($total);
	
		$this->setTempTotalElementsIdsForListView($elementPList->getTotalElementsIds());
	
		$this->executionSink()->publishEndOperation("getAllElementsInListView");
	
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end getAllElementsInListView"] = microtime(true);
	
		return array (
				$total,
				$nbRow
		);
	}
	
	/**
	 * fetches the table of elements
	 * Do not define the fieldSelectorList, this will be defined automatically from the config
	 * @param $onlyRows = false, if true only the data rows are generated (only a list of tr)
	 * @param $onlyRowsContent = false, if true only the content of the row is generated (without the tr)
	 */
	protected function getAllElementsInBlogView($p, $exec, $listContext, $onlyRows = false, $onlyRowsContent = false, $desiredPage = null, $resetGroupBy=false) {
		$this->executionSink()->publishStartOperation("getAllElementsInBlogView");
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start getAllElementsInBlogView"] = microtime(true);
		$transS = ServiceProvider :: getTranslationService();
		$configS = $this->getConfigurationContext();
		$elS = ServiceProvider :: getElementService();
	
		//update the listContext to match to the blogView parameter
		$fsl = FieldSelectorListForActivity :: createInstance(false, false, false); //no issue if double time the same
		$fsl->setSelectedLanguages(array (
				$transS->getLanguage() => $transS->getLanguage()));
		$configS->getFields($p, $exec->getCrtModule(), Activity :: createInstance("blogView"), $fsl);
	
		$fieldList = FormFieldList :: createInstance(null);
		$configS->getFields($p, $exec->getCrtModule(), null, $fieldList);
		foreach ($fieldList->getListIterator() as $field) {
			if ($field->isCalculated() && $field->shouldCalculateOnFetch() && ($fsl->containsField($field->getFieldName()) || ($listContext->getGroupByItemFieldSelector() != null && $listContext->getGroupByItemFieldSelector()->getFieldName() == $field->getFieldName()))) {
				$field->getFuncExpDependencies($fsl);
			}
			
			if($fsl->containsField($field->getFieldName())) {
			    if ($field->getDataType() && $field->getDataType()->getDataTypeName() == "Emails") {
			        //if Emails then take all the subfields, there are usefull to display confirmation status, etc.
			        $fsl->addFieldSelector($field->getFieldName(), "proofStatus");
			        $fsl->addFieldSelector($field->getFieldName(), "proofKey");
			        $fsl->addFieldSelector($field->getFieldName(), "proof");
			        $fsl->addFieldSelector($field->getFieldName(), "externalConfigGroup");
			        $fsl->addFieldSelector($field->getFieldName(), "externalAccessLevel");
			        $fsl->addFieldSelector($field->getFieldName(), "externalCode");
			        $fsl->addFieldSelector($field->getFieldName(), "externalAccessEndDate");
			        $fsl->addFieldSelector($field->getFieldName(), "value");
			    }
			    elseif ($field->getDataType() && $field->getDataType()->getDataTypeName() == "Files") {			        
			        //if Files then take all the subfields, there are usefull to display download, dates, size, etc.
			        $fsl->addFieldSelector($field->getFieldName(), "name");
			        $fsl->addFieldSelector($field->getFieldName(), "type");
			        $fsl->addFieldSelector($field->getFieldName(), "size");
			        $fsl->addFieldSelector($field->getFieldName(), "mime");
			        $fsl->addFieldSelector($field->getFieldName(), "date");
			        $fsl->addFieldSelector($field->getFieldName(), "user");
			        $fsl->addFieldSelector($field->getFieldName(), "username");
			        $fsl->addFieldSelector($field->getFieldName(), "version");
			        $fsl->addFieldSelector($field->getFieldName(), "textContent");
			    }			    
			}
		}
	
		$listContext->setFieldSelectorList($fsl);
		if($resetGroupBy){
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."reset groupBy"] = microtime(true);
			$this->executionSink()->log("reset groupBy");
			$listContext->setGroupBy("reset");
		}
		$listContext->addGroupByFieldSelector($fieldList);
		$listContext->addSortByFieldSelector($fieldList);
	
		if (!$onlyRows) {
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."recalculate search bar"] = microtime(true);
			$this->executionSink()->log("recalculate search bar");
			$listContext->recalculateSearchBar($p, $this); //to make sure to recalculate from the post and the new config
		}
	
		//creating the list
		$elementPList = ElementPListRowsForElementBlogImpl :: createInstance($this, $listContext);
		$elementPList->setFieldList($fieldList);
		$elementPList->setP($p);
		$elementPList->setExec($exec);
		$elementPList->setElementPolicyEvaluator($this->getElementPolicyEvaluator($p, $exec->getCrtModule()));
	
		if ($onlyRows) {
			$elementPList->doOnlyRows($onlyRowsContent);
		}
		$elementPList->actOnBeforeAddElementP();
	
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start getSelectedElementsInGroups"] = microtime(true);
		$this->executionSink()->log("start getSelectedElementsInGroups");
		if ($listContext->doesGroupListIncludeChildren() || $listContext->getGroupPList()->count() == 1) {
			if($listContext->isGroupByOnlyDuplicates()){
				$groupLogExp = $listContext->getGroupLogExp();
				// remove trashbin if exists
				$trashBinGroup = (string)$configS->getParameter($p, $exec->getCrtModule(), "trashBinGroup");
				if($trashBinGroup && ($groupLogExp instanceof LogExpInGroup)) {
					$groupLogExp = $groupLogExp->reduceNegation(true);
					$lx = $groupLogExp->getGroupSelectionLogExp();
					if(isset($lx) && ($lx instanceof LogExpBin) && ($lx->getOperator() == 'IN')) {
						$ids = $lx->getValue();
						if(is_array($ids)) {
							$ids = array_diff($ids, array($trashBinGroup));
							if(!empty($ids)) {
								$lx->setValue($ids);
							}
						}
					}
				}
				// Medair 20.03.2017: computes duplicate Ids and filters on duplicated elements
                $duplicatedIds = $listContext->getDuplicatesIds();
                if(empty($duplicatedIds)) {
                    $duplicatedIds = ValueListArrayImpl::createInstance();
                    $elS->findDuplicatesFromSelectedElementsInGroups($p, $groupLogExp, $listContext->getGroupByItemFieldSelector(), $duplicatedIds);
                    $duplicatedIds = $duplicatedIds->getListIterator();
                    $listContext->setDuplicatesIds($duplicatedIds);
                }
                // adds filter on duplicate Ids
                $lx = $listContext->getFieldSelectorLogExp();
                if(!empty($duplicatedIds)) {
                    if(isset($lx)) $lx = lxAnd(lxIn(fs_e('id'), $duplicatedIds), $lx);
                    else $lx = lxIn(fs_e('id'), $duplicatedIds);
                }
                //if no duplicate generate negative where clause to show an empty list
                else {
                    if(isset($lx)) $lx = lxAnd(lxEq(fs_e('id'), null), $lx);
                    else $lx = lxEq(fs_e('id'), null);
                }
                $listContext->setFieldSelectorLogExp($lx);
			}
			else {
				$groupLogExp = $listContext->getGroupLogExp();
			}
			
			$nbRow = $elS->getSelectedElementsInGroups($p, $groupLogExp,
					$elementPList,
					$listContext
			);
			
		} else {
			$nbRow = 0;
		}
	
		$total = $listContext->getTotalNumberOfObjects();
		$elementPList->actOnFinishAddElementP($total);
	
		$this->setTempTotalElementsIdsForListView($elementPList->getTotalElementsIds());
	
		$this->executionSink()->publishEndOperation("getAllElementsInBlogView");
	
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."end getAllElementsInBlogView"] = microtime(true);
	
		return array (
				$total,
				$nbRow
		);
	}
	
	/**
	 * fetches the table of elements
	 * Do not define the fieldSelectorList, this will be defined automatically from the config
	 * fromGetNextElementInCalendar will prevent recalculating searchBar. this should be used
	 * when wanting to get next page of the calendar
	 */
	protected function getAllElementsInCalendarView($p, $exec, $originalListContext, $fromGetNextElementInCalendar = false) {
		$this->executionSink()->publishStartOperation("getAllElementsInCalendarView");
	
		$transS = ServiceProvider :: getTranslationService();
		$configS = $this->getConfigurationContext();
		$elS = ServiceProvider :: getElementService();
	
		//update the listContext to match to the listView parameter
		$fsl = FieldSelectorListForActivity :: createInstance(false, false);
		$fsl->setSelectedLanguages(array (
				$transS->getLanguage() => $transS->getLanguage()));
		$configS->getFields($p, $exec->getCrtModule(), Activity :: createInstance("calendarView"), $fsl);
	
		$fieldList = FormFieldList :: createInstance(null);
		$configS->getFields($p, $exec->getCrtModule(), null, $fieldList);
		foreach ($fieldList->getListIterator() as $field) {
			if ($field->isCalculated() && $field->shouldCalculateOnFetch() && ($fsl->containsField($field->getFieldName()) || ($originalListContext->getGroupByItemFieldSelector() != null && $originalListContext->getGroupByItemFieldSelector()->getFieldName() == $field->getFieldName()))) {
				$field->getFuncExpDependencies($fsl);
			}
		}
	
		$originalListContext->setFieldSelectorList($fsl);
		$originalListContext->addGroupByFieldSelector($fieldList);
		$originalListContext->addSortByFieldSelector($fieldList);
	
		if (!$fromGetNextElementInCalendar) {
			$originalListContext->recalculateSearchBar($p, $this); //to make sure to recalculate from the post and the new config
		}
	
		//for calendar view we need to go with an other list context to be able to add the
		//date filter
		$listContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementCalendar");
		//force display children
		$originalListContext->setGroupPList($originalListContext->getGroupPList(), true);
		$listContext->matchFetchCriteria($originalListContext);
	
		//adding fieldLogExp on period field
		$startDate = $exec->getCrtParameters(0);
		$endDate = (int) $exec->getCrtParameters(1);
		$crtView = (int) $exec->getCrtParameters(2);
		$crtDate = $exec->getCrtParameters(3);
		$timeZoneOffset = (int) $exec->getCrtParameters(4);
		//ajust the timeZone to the current swiss one
		$temp_offset = - (int) date("O") * 36; //gives +100 if GMT+1
		$timeZoneOffset = $timeZoneOffset - $temp_offset;
	
		//substract the timezone from the browser
		$startDate = $startDate - $timeZoneOffset;
		$endDate = $endDate - $timeZoneOffset;
	
		//		fput(date("d.m.Y h:i",$endDate));
		//		fput($crtDate);
		//		fput($timeZoneOffset);
		//		fput($temp_offset);
		if ($crtView)
			$originalListContext->setCrtViewParams($crtView, "crtView");
		if ($crtDate)
			$originalListContext->setCrtViewParams($crtDate, "crtDate");
	
		if ($startDate != null && $endDate != null) {
			//find first TimeRange field or Dates
			foreach ($fsl->getListIterator() as $fs) {
				$field = $fieldList->getField($fs->getFieldName());
				if ($field->getDataType() && $field->getDataType()->getDataTypeName() == "TimeRanges") {
					$fsStartTime = FieldSelector :: createInstance($field->getFieldName(), "begTime");
					$fsStartDate = FieldSelector :: createInstance($field->getFieldName(), "begDate");
					$fsEndTime = FieldSelector :: createInstance($field->getFieldName(), "endTime");
					$fsEndDate = FieldSelector :: createInstance($field->getFieldName(), "endDate");
	
					$dateExp = LogExp :: createAndExp();
					//in search don't look for time. because the view are always full day.
					//adding and on time makes problems as well because the logExp should be a concatenation of date + time, not separate
					$dateExp->addOperand(LogExp :: createSmallerExp($fsStartDate, $endDate));
					$orLogExp = LogExp :: createOrExp();
					$orLogExp->addOperand(LogExp :: createGreaterEqExp($fsEndDate, $startDate));
					$and2LogExp = LogExp :: createAndExp();
					$and2LogExp->addOperand(LogExp :: createEqualExp($fsEndDate, null));
					$and2LogExp->addOperand(LogExp :: createGreaterEqExp($fsStartDate, $startDate));
					$orLogExp->addOperand($and2LogExp);
					$dateExp->addOperand($orLogExp);
	
					$crtLogExp = $listContext->getFieldSelectorLogExp();
					if (isset ($crtLogExp)) {
						if ($crtLogExp instanceof LogExpAnd) {
							$crtLogExp->addOperand($dateExp);
						} else {
							$andExp = LogExp :: createAndExp();
							$andExp->addOperand($crtLogExp);
							$andExp->addOperand($dateExp);
							$listContext->setFieldSelectorLogExp($andExp);
						}
					} else {
						$listContext->setFieldSelectorLogExp($dateExp);
					}
					break;
				} else if ($field->getDataType() && $field->getDataType()->getDataTypeName() == "Dates") {
					$fsDate2 = null;
					foreach ($fsl->getListIterator() as $fs2) {
						$field2 = $fieldList->getField($fs2->getFieldName());
						if ($fs2 == $fs)continue;//skip if same field.
						if ($field2->getDataType() && $field2->getDataType()->getDataTypeName() == "Dates") {
							$fsDate2 = FieldSelector :: createInstance($field2->getFieldName(), "value");
							break 1;
						}
					}
					if($fsDate2) {
						$fsDate1 = FieldSelector :: createInstance($field->getFieldName(), "value");
						//we handle properly every case with two Dates fields even if one is not set.
						$dateExp = lxOr(lxAnd(lxSm($fsDate1,$endDate), lxGrEq($fsDate2, $startDate)),
								lxAnd(lxEq($fsDate1, null),lxSm($fsDate2,$endDate), lxGrEq($fsDate2, $startDate)),
								lxAnd(lxEq($fsDate2, null),lxSm($fsDate1,$endDate), lxGrEq($fsDate1, $startDate)));
						$crtLogExp = $listContext->getFieldSelectorLogExp();
						if (isset ($crtLogExp)) {
							if ($crtLogExp instanceof LogExpAnd) {
								$crtLogExp->addOperand($dateExp);
							} else {
								$andExp = LogExp :: createAndExp();
								$andExp->addOperand($crtLogExp);
								$andExp->addOperand($dateExp);
								$listContext->setFieldSelectorLogExp($andExp);
							}
						} else {
							$listContext->setFieldSelectorLogExp($dateExp);
						}
					} else {
						$fsDate = FieldSelector :: createInstance($field->getFieldName(), "value");
						$dateExp = LogExp :: createAndExp();
						$dateExp->addOperand(LogExp :: createSmallerExp($fsDate, $endDate));
						$dateExp->addOperand(LogExp :: createGreaterEqExp($fsDate, $startDate));
						$crtLogExp = $listContext->getFieldSelectorLogExp();
						if (isset ($crtLogExp)) {
							if ($crtLogExp instanceof LogExpAnd) {
								$crtLogExp->addOperand($dateExp);
							} else {
								$andExp = LogExp :: createAndExp();
								$andExp->addOperand($crtLogExp);
								$andExp->addOperand($dateExp);
								$listContext->setFieldSelectorLogExp($andExp);
							}
						} else {
							$listContext->setFieldSelectorLogExp($dateExp);
						}
					}
					break;
				}
			}
	
		}
	
		//creating the list
		//		echo alert($originalListContext);
		$elementPList = ElementPListItemsForElementCalendar :: createInstance($this, $listContext);
		$elementPList->setHighlight($originalListContext->getTextSearch() != null);
		$elementPList->setTimeZoneOffset($timeZoneOffset);
		$elementPList->setP($p);
		$elementPList->actOnBeforeAddElementP();
	
		$nbRow = $elS->getSelectedElementsInGroups($p, $listContext->getGroupLogExp(), //getGroupList(), $
				$elementPList,
				//$listContext->doesGroupListIncludeChildren(),
				$listContext);
		//		eput($nbRow);
	
	
		$lookForNext = false;
		if ($nbRow == 0 && $originalListContext->getCrtViewParams("redefineSearchCriterias") && $originalListContext->getFieldSelectorLogExp() != null) {
			//						fput("look for next");
			$lookForNext = true;
			//redo the fetch but without the dates limit
			$originalListContext->setSortedBy("reset"); //reset to default sorting by
			//first look in future only
			$listContext->matchFetchCriteria($originalListContext);
			$logExp = LogExp :: createOrExp();
			$startDate = time();
			//TimeRanges case
			if($fsEndDate){
				$logExp->addOperand(LogExp :: createGreaterEqExp($fsEndDate, $startDate));
				$logExp->addOperand(LogExp :: createGreaterEqExp($fsStartDate, $startDate));
			} else {
				$logExp->addOperand(LogExp :: createGreaterEqExp($fsDate, $startDate));
			}
			$crtLogExp = $listContext->getFieldSelectorLogExp();
			if (isset ($crtLogExp)) {
				if ($crtLogExp instanceof LogExpAnd) {
					$crtLogExp->addOperand($logExp);
					//					fput($crtLogExp);
				} else {
					$andExp = LogExp :: createAndExp();
					$andExp->addOperand($crtLogExp);
					$andExp->addOperand($logExp);
					$listContext->setFieldSelectorLogExp($andExp);
					//					fput($andExp);
				}
			} else {
				//					fput($logExp);
				$listContext->setFieldSelectorLogExp($logExp);
			}
			$nbRow = $elS->getSelectedElementsInGroups($p, $listContext->getGroupLogExp(), //getGroupList(), $
					$elementPList,
					//$listContext->doesGroupListIncludeChildren(),
					$listContext);
			//if still not found search in the past as well
			if ($nbRow == 0) {
				$nbRow = $elS->getSelectedElementsInGroups($p, $listContext->getGroupLogExp(), //getGroupList(), $
						$elementPList,
						//$listContext->doesGroupListIncludeChildren(),
						$originalListContext);
			}
		}
	
		//if no result found then display message: ?????
	
		//if there is a text search then add any other events without highlight.
		if (true && !$lookForNext && $originalListContext->getTextSearch() != null) {
	
			$elementPList->setHighlight(false);
			//fetch the reverse events with the same time bound
			$listContext->matchFetchCriteria($originalListContext);
			$listContext->setFieldSelectorLogExp(null);
			$selectExp = $originalListContext->getLogExpOnSelectSearch();
			$noTextExp = $originalListContext->getLogExpOnTextSearch();
			if ($noTextExp)
				$noTextExp = LogExp :: createNotExp($noTextExp);
			//			$groupExp = $originalListContext->getGroupLogExp();
			$dateExp = $dateExp;
			$andExp = LogExp :: createAndExp();
			$one = false; //check if there is at least one existing logExp
			if ($selectExp) {
				$one = true;
				$andExp->addOperand($selectExp);
			}
			if ($noTextExp) {
				$one = true;
				$andExp->addOperand($noTextExp);
			}
			if ($dateExp) {
				$one = true;
				$andExp->addOperand($dateExp);
			}
			if ($one)
				$listContext->setFieldSelectorLogExp($andExp);
			//else keep the logExp empty
	
			$nbRow = $elS->getSelectedElementsInGroups($p, $listContext->getGroupLogExp(), //getGroupList(), $
					$elementPList,
					//$listContext->doesGroupListIncludeChildren(),
					$listContext);
		}
	
		$originalListContext->setCrtViewParams(false, "redefineSearchCriterias");
	
		$elementPList->actOnFinishAddElementP($nbRow);
	
		//fput($originalListContext->getLogExpOnSelectSearch());
	
		$this->executionSink()->publishEndOperation("getAllElementsInCalendarView");
	
		return $nbRow;
	}

		
		
	// HTML templates
		
	// DEPRECATED from V4
	protected function includeTemplateIconsView($p, $exec){
		throw new ServiceException("includeTemplateIconsView is deprecated", ServiceException::DEPRECATED);
	}
	protected function includeTemplateTabsBar($p, $exec){
		throw new ServiceException("includeTemplateTabsBar is deprecated", ServiceException::DEPRECATED);
	}
	protected function includeTemplateUserBar($p, $exec){
		throw new ServiceException("includeTemplateUserBar is deprecated", ServiceException::DEPRECATED);
	}
	protected function includeTemplateGroupSelectorPanel($p, $exec){
		throw new ServiceException("includeTemplateGroupSelectorPanel is deprecated", ServiceException::DEPRECATED);
	}
	protected function detachModule($p, $exec){
		throw new ServiceException("includeTemplateGroupSelectorPanel is deprecated", ServiceException::DEPRECATED);
	}
	
	//SPECIAL Templates
	protected function includeTemplateCompanyBanner($p, $exec){
		include(TEMPLATE_PATH . "banner.php");
	}
	protected function includeTemplateLogin($p, $exec) {
		include (TEMPLATE_PATH . "login.tpl.php");
	}
	protected function includeTemplateHome($p, $exec){
		include(TEMPLATE_PATH . "home.tpl.php");
	}
	//COMMON Templates
	protected function includeTemplateAll($p, $exec){
        include(TEMPLATE_PATH . "all.tpl.php");
	}
	protected function includeTemplateFooterBar($p, $exec){
		include(TEMPLATE_PATH . "footerBar.tpl.php");
	}
	protected function includeTemplateGroupPanel($p, $exec){
		include(TEMPLATE_PATH . "groupSelectorPanel.tpl.php");
	}
	protected function includeTemplateModuleView($p, $exec){
		include(TEMPLATE_PATH . "moduleView.tpl.php");
	}
	protected function includeTemplateNavigation($p, $exec){
	    include(TEMPLATE_PATH . "navigationBar.bsp.php");		
	}
	protected function includeTemplateSearchBar($p, $exec){
	    // 28.11.2017: search bar is included directly into navigation bar.
	    //include(TEMPLATE_PATH . "searchBar.tpl.php");
	}
	protected function includeTemplateWorkZone($p, $exec){
		include(TEMPLATE_PATH . "workZone.tpl.php");
	}
	protected function includeTemplateIndicators($p, $exec){
		include(TEMPLATE_PATH . "indicators.tpl.php");
	}
	
	//ADMIN Templates
	protected function includeTemplateAdminWorkZone($p, $exec) {
		include (TEMPLATE_PATH . "adminWorkZone.tpl.php");
	}
	protected function includeTemplateAdminGroup($p, $exec) {
		include (TEMPLATE_PATH . "adminGroup.tpl.php");
	}
	protected function includeTemplateAdminGroupUser($p, $exec) {
		include (TEMPLATE_PATH . "adminGroupUser.tpl.php");
	}
	protected function includeTemplateAdminModuleEditor($p, $exec) {
		include (TEMPLATE_PATH . "adminModuleEditor.tpl.php");
	}
	protected function includeTemplateAdminSearchBar($p, $exec) {
		include (TEMPLATE_PATH . "adminSearchBar.tpl.php");
	}
	protected function includeTemplateAdminUser($p, $exec) {
		include (TEMPLATE_PATH . "adminUser.tpl.php");
	}
	protected function includeTemplateAdminRole($p, $exec) {
		include (TEMPLATE_PATH . "adminRole.tpl.php");
	}
	protected function includeTemplateAdminUserAdmin($p, $exec) {
		include (TEMPLATE_PATH . "adminUserAdmin.tpl.php");
	}
	protected function includeTemplateAdminUserRole($p, $exec) {
		include (TEMPLATE_PATH . "adminUserRole.tpl.php");
	}
	protected function includeTemplateAdminUserUser($p, $exec) {
		include (TEMPLATE_PATH . "adminUserUser.tpl.php");
	}

		
	
	
	
	// Functional Implementation
	
		

	/**
	 * Returns a WigiiEventsDispatcher used to throw some Wigii events
	 * @return WigiiEventsDispatcher
	 */
	public function throwEvent() {
		$this->executionSink()->log("throwEvent");
		// autowired
		if (!isset ($this->wigiiEventsDispatcher)) {
			//plug the ConfigurationContext in place of ConfigurationService
			$this->wigiiEventsDispatcher = TechnicalServiceProvider :: getWigiiEventsDispatcher();
			$eventSubscriberService = TechnicalServiceProvider :: getEventSubscriberService();
			$notificationService = $this->getNotificationService(); //TechnicalServiceProviderWebImpl::getNotificationService();
			//$notificationService->setConfigService($this->getConfigurationContext());
			$eventSubscriberService->setNotificationService($notificationService);
			$this->wigiiEventsDispatcher->setEventSubscriberService($eventSubscriberService);
		}
		return $this->wigiiEventsDispatcher;
	}
	
	/**
	 * delete the credential cookie
	 */
	public function getJsCodeBeforeLogout($p) {
		$code = ServiceProvider :: getAuthenticationService()->getJsCodeToUnsetWigiiCredentialCookie($p);
		$code .= "
crtElementId = null;
crtElementType = null;
crtRoleId = null;
crtWigiiNamespaceUrl = null;
crtModuleName = null;
crtWorkingModuleName = null;
self.location = '#logout';
invalidCompleteCache();
";
		return $code;
	}

	/**
	 * Pushes some data back to the client in JSON format.
	 * Data can already be a JSON string, or StdClass, Array, DataFlowSelector or a ServiceException instance.
	 * @param Principal $p current principal executing the request
	 * @param ExecutionService $exec
	 * @param String|Array|StdClass|DataFlowSelector|ServiceException $data
	 */
	public function pushJson($p, $exec, $data) {
		if(!isset($data)) return;
		if(!isset($p) || !isset($exec)) throw new ServiceException("principal and execution service cannot be null", ServiceException::INVALID_ARGUMENT);
		
		// converts data to JSON string
		if($data instanceof DataFlowSelector) {
			$data = ServiceProvider::getDataFlowService()->processDataFlowSelector($p, $data);			
		}
		elseif($data instanceof Exception) {			
			$data = $this->convertServiceExceptionToJson($p, $exec, $data);
			// closes any previous open request to ensure correct protocol syntax in case of exceptions
			if ($exec->getIsUpdating() && isset($data)) {
				echo ExecutionServiceImpl :: answerRequestSeparator;
				echo $exec->getIdAnswer();
				echo ExecutionServiceImpl :: answerParamSeparator;
			}
		}
		if(is_array($data) || $data instanceof stdClass) {
			$data = @json_encode($data);
			if(json_last_error() !== JSON_ERROR_NONE) throw new ServiceException('JSON encoding syntax error', ServiceException::INVALID_ARGUMENT);
		}
		elseif(!is_string($data)) throw new ServiceException('data is not a JSON string', ServiceException::INVALID_ARGUMENT);
		
		// pushes JSON string to client
		if ($exec->getIsUpdating()) {
			echo "json";
			echo ExecutionServiceImpl :: answerParamSeparator;
		}		
		echo $data;
	}
	/**
	 * Converts a PHP ServiceException to a StdClass ready to be pushed in JSON to client
	 * @param Principal $p current principal executing the request
	 * @param ExecutionService $exec 
	 * @param ServiceException|Exception $exception the exception to be converted to StdClass
	 * @return StdClass a stdClass instance of the form :
	 * {context: calls getExecutionContext(), exception:{class: exception class, message: error message, code: exception code}}
	 */
	public function convertServiceExceptionToJson($p,$exec,$exception) {
		if(!isset($exception)) return null;
		if($exception instanceof ServiceException) $exception = $exception->getWigiiRootException();		
		$returnValue = array();
		$returnValue['name'] = get_class($exception);
		$returnValue['message'] = $exception->getMessage();
		$returnValue['code'] = $exception->getCode();
		$returnValue = (object)$returnValue;
		
		$returnValue = array('context'=>$this->getExecutionContext($p, $exec),'exception'=>$returnValue);
		return (object)$returnValue;
	}
	/**
	 * Gets an StdClass instance representing the current execution context, ready to be pushed in JSON to client 
	 * @param Principal $p current principal executing the request
	 * @param ExecutionService $exec
	 * @return StdClass a stdClass instance of the form :
	 * {request: sub-url, wigiiNamespace: current namespace, module: current module, action: current action, 
	 *  realUsername: real user name, username: role name, principalNamespace: principal current namespace,
	 *  version: Wigii system version label}
	 */
	public function getExecutionContext($p,$exec) {
		if(!isset($p) || !isset($exec)) throw new ServiceException("principal and execution service cannot be null", ServiceException::INVALID_ARGUMENT);
		$returnValue = array();
		$returnValue['request'] = $exec->getCrtRequest();
		$returnValue['wigiiNamespace'] = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
		$returnValue['module'] = $exec->getCrtModule()->getModuleUrl();
		$returnValue['action'] = $exec->getCrtAction();
		$returnValue['realUsername'] = $p->getRealUsername();
		$returnValue['username'] = $p->getUsername();
		$returnValue['principalNamespace'] = $p->getWigiiNamespace()->getWigiiNamespaceUrl();
		$returnValue['version'] = VERSION_LABEL;
		return (object)$returnValue;
	}
		
	/**
	 * if obtaining lock failed -> display message and return false
	 * else return 1 if shouldUnLock, return 0 if shouldNotUnLock
	 */
	public function lockEntity($p, $exec, $jsCode, $dbEntityName, $dbEntity) {
		$this->executionSink()->log("lockEntity $dbEntityName ".$dbEntity->getId());
		try {
			switch ($dbEntityName) {
				case "elements" :
				case "element" :
					$shouldUnlock = ServiceProvider :: getElementService()->lock($p, $dbEntity);
					break;
				case "user" :
					$shouldUnlock = ServiceProvider :: getUserAdminService()->lock($p, $dbEntity);
					break;
				case "group" :
					$shouldUnlock = ServiceProvider :: getGroupAdminService()->lock($p, $dbEntity);
					break;
			}
			if ($shouldUnlock){
				$this->executionSink()->log("$dbEntityName ".$dbEntity->getId()." has been locked.");
				return 1;
			} else{
				$this->executionSink()->log("$dbEntityName ".$dbEntity->getId()." was already locked in the same session");
				return 0;
			}
		} catch (AuthorizationServiceException $ase) {
			if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
				$this->executionSink()->log("$dbEntityName ".$dbEntity->getId()." is already locked");
				$this->displayLockMessage($p, $exec, $jsCode, $dbEntityName, $dbEntity);
				return false;
			} else
				throw $ase;
		}
	}
	public function unLockEntity($p, $exec, $jsCode, $dbEntityName, $dbEntity) {
		try {
			switch ($dbEntityName) {
				case "elements" :
				case "element" :
					ServiceProvider :: getElementService()->unLock($p, $dbEntity);
					break;
				case "user" :
					ServiceProvider :: getUserAdminService()->unLock($p, $dbEntity);
					break;
				case "group" :
					ServiceProvider :: getGroupAdminService()->unLock($p, $dbEntity);
					break;
			}
			return true;
		} catch (AuthorizationServiceException $ase) {
			if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
				$this->displayLockMessage($p, $exec, $jsCode, $dbEntityName, $dbEntity);
				return false;
			} else
				throw $ase;
		}
	}

	/**
	 * autoSave a subField value in an existing element
	 * fieldSelector: FieldSelector used to update the element. If null, it will be created with fieldName, subFieldName
	 * the $_POST must contain:
	 * 	"autoSaveFieldId"=>html id of the wrapping field in the form (could contain subFields)
	 * 	"autoSaveMesssageTargetId"=>html id of the autoSave message handler (usually the same than autoSaveFieldId, could be different in the case of Blobs, htmlArea)
	 * 	"fieldName_subFieldName" of each subfield of the field => related value
	 */
	protected function autoSave($p, $exec, $elementP, $fieldName, $fsl=null, $isForExternalAccess=false){
		$configS = $this->getConfigurationContext();
		$transS = ServiceProvider :: getTranslationService();
		$elS = ServiceProvider :: getElementService();

		$element = $elementP->getElement();

		if($element->isState_blocked() || $elementP->isParentElementState_blocked()) throw new ServiceException("blockedElementsOperationImpossible", ServiceException::FORBIDDEN);

		if($fsl==null){
			$fsl = FieldSelectorListArrayImpl::createInstance();
			$fsl->addFieldSelector($fieldName);
		}

//		fput($_POST);
//		eput($_FILES);

		$fe = $this->createFormExecutor($this);

		//clone current element instead of reloading it from the DB
		$oldRecord = clone($element);
		$oldRecord->setWigiiBag(clone($element->getWigiiBag()));
		$fe->setRecord($element);

		$field = $element->getFieldList()->getField($fieldName);
		if($isForExternalAccess && $field->getDataType() && $field->getDataType()->getDataTypeName()=="Emails"){
			//if the email is changed, the proofStatus, proofkey and proof should be reset
			//but the external access code must remain to prevent loosing external access while updating the address
			$externalConfigGroup = $element->getFieldValue($fieldName, "externalConfigGroup");
			$externalAccessLevel = $element->getFieldValue($fieldName, "externalAccessLevel");
			$externalAccessEndDate = $element->getFieldValue($fieldName, "externalAccessEndDate");
			$externalCode = $element->getFieldValue($fieldName, "externalCode");
		}

		//check the form, and fill in the element accordingly
		$fe->CheckForm($p, $exec);

		$autoSaveFieldId = $_POST["autoSaveFieldId"];
		$autoSaveMesssageTargetId = $_POST["autoSaveMesssageTargetId"];
		//check if there is some errors
		if($fe->hasError()){
			//if yes, display an error and do not save
			$error = $element->getWigiiBag()->getError($fieldName);
			$exec->addJsCode("" .
				"if($('#$autoSaveMesssageTargetId .fieldError.$fieldName').length){ $('#$autoSaveMesssageTargetId .fieldError.$fieldName').remove(); }" .
				"else if($('#$autoSaveMesssageTargetId .fieldError').length){ $('#$autoSaveMesssageTargetId .fieldError, #$autoSaveMesssageTargetId .label img').remove(); }" .
				"$('#$autoSaveMesssageTargetId').prepend('<div class=\"fieldError $fieldName\">$error</div><div class=\"clear\"></div>');" .
				"$('#$autoSaveMesssageTargetId .autoSaveConfirmation').fadeOut(function(){ $(this).remove(); });" .
				"");
		} else {

			$autoSaveConfirmationText = "";
			$storeFileInWigiiBag = $configS->getParameter($p, null, "storeFileContentIntoDatabase") == "1";

			$newFileFieldSelectorList = $fe->updateHiddenFields($p, $exec, $storeFileInWigiiBag, $oldRecord);
			if($newFileFieldSelectorList) $fsl->mergeFieldSelectorList($newFileFieldSelectorList);

			if($isForExternalAccess && $field->getDataType() && $field->getDataType()->getDataTypeName()=="Emails"){
				//if the email is changed, the proofStatus, proofkey and proof should be reset
				//but the external access code must remain to prevent loosing external access while updating the address
				$element->setFieldValue($externalConfigGroup, $fieldName, "externalConfigGroup");
				$element->setFieldValue($externalAccessLevel, $fieldName, "externalAccessLevel");
				$element->setFieldValue($externalAccessEndDate, $fieldName, "externalAccessEndDate");
				$element->setFieldValue($externalCode, $fieldName, "externalCode");
			}

			$elS->updateElement($p, $element, $fsl);

			$fe->updateFilesOnDisk($p, $exec, $storeFileInWigiiBag, $oldRecord, false);

			//if uploaded a file, then update hidden fields
			if($field->getDataType() && $field->getDataType()->getDataTypeName()=="Files"){
				$exec->addJsCode("" .
					"$('#".str_replace("__", "_", $autoSaveFieldId)."_file_file').val('');" .
					"$('#".str_replace("__", "_", $autoSaveFieldId)."_path_hidden').val('".$element->getFieldValue($fieldName, "path")."');" .
					"$('#".str_replace("__", "_", $autoSaveFieldId)."_type_hidden').val('".$element->getFieldValue($fieldName, "type")."');" .
					"$('#".str_replace("__", "_", $autoSaveFieldId)."_mime_hidden').val('".$element->getFieldValue($fieldName, "mime")."');" .
					"$('#".str_replace("__", "_", $autoSaveFieldId)."_size_hidden').val('".$element->getFieldValue($fieldName, "size")."');" .
					"$('#".str_replace("__", "_", $autoSaveFieldId)."_date_hidden').val('".$element->getFieldValue($fieldName, "date")."');" .
					"$('#".str_replace("__", "_", $autoSaveFieldId)."_user_hidden').val('".$element->getFieldValue($fieldName, "user")."');" .
					"$('#".str_replace("__", "_", $autoSaveFieldId)."_username_hidden').val('".$element->getFieldValue($fieldName, "username")."');" .
					"");
			}

			$autoSaveConfirmationText = $transS->h($p, "autoSaveConfirmation");

			//tooltip seem not to be the good solution here
			//just append a div position absolute class ui-widget
			//add timeout to remove it
			//display it just above the field
			$exec->addJsCode("" .
				"if($('#$autoSaveMesssageTargetId .fieldError.$fieldName').length){ $('#$autoSaveMesssageTargetId .fieldError.$fieldName').remove(); }" .
				"else if($('#$autoSaveMesssageTargetId .fieldError').length){ $('#$autoSaveMesssageTargetId .fieldError, #$autoSaveMesssageTargetId .label img').remove(); }" .
				"$('#$autoSaveMesssageTargetId .autoSaveConfirmation').html('".$autoSaveConfirmationText."');" .
				"$('#$autoSaveMesssageTargetId .autoSaveConfirmation').fadeOut(function(){ $(this).remove(); });" .
				"");
		}
	}

	/**
	 * check-out / check-in message management
	 */
	protected function checkOutInField($p, $exec, $element, $fileField, $checkField, $message, $isCheckOut = true) {
		$this->executionSink()->publishStartOperation("checkOutInField");

		$transS = ServiceProvider :: getTranslationService();
		$oldValue = $element->getFieldValue($checkField->getFieldName());
		$checkFieldXml = $checkField->getXml();
		$result = "";
		if($checkFieldXml["htmlArea"] == "1"){
			$result .= "<p style=&quot;color:#666;&quot;>&gt; ";
		} else {
			$result .= "&gt; ";
		}
		$result .= date("d.m.Y")." ".($p->getRealUser() ? $p->getRealUser()->getUserName() : $transS->t($p, "externalAccessUsernameForJournal"));
		$result .= " ".($isCheckOut ? $transS->t($p, "detailCheckoutButton") : $transS->t($p, "detailCheckinButton"));

		if($checkFieldXml["htmlArea"] == "1"){
			$result .= "</p>";
		} else {
			$result .= "\\\\n";
		}
		if($message){
			if($checkFieldXml["htmlArea"] == "1"){
				$result .= '<p>'.$message.'</p>';
			} else {
				$result .= $message."\\\\n";
			}
		}

		if($checkFieldXml["isJournal"]){ //add message to the front
			if($checkFieldXml["htmlArea"] == "1") $result .= "<p>&nbsp;</p>";
			else $result .= "\\\\n";
			$result = $result.$oldValue;
		} else { //add message to the end
			if($checkFieldXml["htmlArea"] == "1") $result = "<p>&nbsp;</p>".$result;
			else $result = "\\\\n".$result;
			$result = $oldValue.$result;
		}
		$element->setFieldValue($result, $checkField->getFieldName());
		$element->getWigiiBag()->setChanged($checkField->getFieldName());

		$this->executionSink()->publishEndOperation("checkOutInField");
	}

	protected function getAllGroupsToNotifyOnElementDelete($p, $exec, $element){
		$this->executionSink()->publishStartOperation("getAllGroupsToNotifyOnElementDelete", $p);
		$elS = ServiceProvider::getElementService();
		$groupPList = GroupListAdvancedImpl :: createInstance(false);

		//get all group containing element
		$elS->getAllGroupsContainingElement($p, $element, $groupPList);

		//don't care about trashbin, this makes no difference for the notifications

		//remove any limitedWriteGroup
		if($element->getSys_creationUser() == $p->getRealUserId()) return $groupPList->getSGroups();
		//remove any unreadable group
		$this->executionSink()->publishEndOperation("getAllGroupsToNotifyOnElementDelete", $p);
		return $groupPList->getWriteGroups();
	}
	protected function getAllGroupsToNotifyOnElementsDelete($p, $exec, $elementPAList){
		$this->executionSink()->publishStartOperation("getAllGroupsToNotifyOnElementsDelete", $p);
		$elS = ServiceProvider::getElementService();
		$groupAS = ServiceProvider::getGroupAdminService();

		$finalGroupPList = GroupListAdvancedImpl :: createInstance(false);

		//get all group containing created elements
		$groupListOfCreatedElements = GroupListArrayImpl :: createInstance();
		if($elementPAList->getSysCreationUserElementPList($p->getRealUserId())) $elS->getAllGroupsWithoutDetailContainingElements($p, $elementPAList->getSysCreationUserElementPList($p->getRealUserId()), $groupListOfCreatedElements);
		//get all groups containing elements
		$groupListOfElements = GroupListArrayImpl :: createInstance();
		$elS->getAllGroupsWithoutDetailContainingElements($p, $elementPAList, $groupListOfElements);

		//get all writable groups
		$allGroupList = GroupListAdvancedImpl::createInstance(false);
		$fsl = $groupAS->getFieldSelectorListForGroupWithoutDetail();
		$listFilter = ListFilter::createInstance();
		$listFilter->setFieldSelectorList($fsl);
		$groupAS->getAllGroups($p, $exec->getCrtModule(), $allGroupList, $listFilter);

		//remove any groupListOfCreatedElements which are not SGroups
		$groupIdsToDeleteForCreatedElement = null;
		$groupIdsToDeleteForElement = null;
		if($groupListOfCreatedElements->getIds() && $allGroupList->getSGroups()) $groupIdsToDeleteForCreatedElement = array_intersect_key($groupListOfCreatedElements->getIds(), $allGroupList->getSGroups()->getGroupIds());
		if($groupListOfElements->getIds() && $allGroupList->getWriteGroups()) $groupIdsToDeleteForElement = array_intersect_key($groupListOfElements->getIds(), $allGroupList->getWriteGroups()->getGroupIds());
		if($groupIdsToDeleteForCreatedElement && $groupIdsToDeleteForElement) $groupIdsToDelete = array_merge($groupIdsToDeleteForCreatedElement, $groupIdsToDeleteForElement);
		else if($groupIdsToDeleteForCreatedElement) $groupIdsToDelete = $groupIdsToDeleteForCreatedElement;
		else if($groupIdsToDeleteForElement) $groupIdsToDelete = $groupIdsToDeleteForElement;
		else $groupIdsToDelete = array();

		foreach($groupIdsToDelete as $groupId){
			if($finalGroupPList->getItemInList($groupId)) continue;
			$finalGroupPList->addGroupP($allGroupList->getItemInList($groupId));
		}

		$this->executionSink()->publishEndOperation("getAllGroupsToNotifyOnElementsDelete", $p);
		return $finalGroupPList;
	}

	protected function addJournalItem($p, $exec, $transS, $elS, $elementP, $isFromExternalAccess = false){
		$this->executionSink()->publishStartOperation("addJournalItem", $p);
		$configS = $this->getConfigurationContext();

		$element = $elementP->getDbEntity();
		$elementId = $element->getId();

		$fe = $this->createFormExecutor();
		$fe->preventInjectionForm($p, $exec);
		$message = $_POST["addJournalItemMessage"];
		$fieldName = $_POST["journalFieldName"];
		$elementDialogId = $_POST["elementDialogId"];

		$message = str_replace(array('\\n',"=&quot;","&quot;>"), array("\n",'="','">'), $message);

		$fsl = FieldSelectorListArrayImpl :: createInstance();
		$fsl->addFieldSelector($fieldName);
		$element->setFieldValue($message.$element->getFieldValue($fieldName), $fieldName);
		$element->getWigiiBag()->resetChanges();
		$element->getWigiiBag()->setChanged($fieldName); //important for notification!
		try {
			//lock is done in updateElement
			//if allowOnReadOnly then use the RootPrincipal to do the job
			// CWE 10.02.2016: allows adding comments on blocked element if allowOnReadOnly
			$fieldXml = $element->getFieldList()->getField($fieldName)->getXml();
			if($fieldXml["isJournal"]!="1") throw new ServiceException("you must set isJournal attribut to field ".$fieldName, ServiceException::FORBIDDEN);			
			if((!$elementP->getRights()->canWriteElement() || $element->isState_blocked() || $elementP->isParentElementState_blocked()) 
				&& $fieldXml["allowOnReadOnly"]=="1") {
				$elS->updateElement($this->getPublicPrincipal(), $element, $fsl);
			} else {
				if($element->isState_blocked() || $elementP->isParentElementState_blocked()) throw new ServiceException("blockedElementsOperationImpossible", ServiceException::FORBIDDEN);
				$elS->updateElement($p, $element, $fsl);
			}
		} catch (AuthorizationServiceException $ase) {
			if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
				$this->displayLockMessage($p, $exec, null, "element", $elementP);
				//display back the
				echo "<br /><b>".$transS->t($p, "addJournalItemPostBackMessage").":</b><br />";
				echo stripslashes($message);
				$this->executionSink()->publishEndOperationOnError("addJournalItem", $ase, $p);
				return;
			} else {
				$this->executionSink()->publishEndOperationOnError("addJournalItem", $ase, $p);
				throw $ase;
			}
		}
		$this->throwEvent()->updateElement(PWithElementWithGroupPList::createInstance($p, $element, ($configS->getGroupPList($p, $exec->getCrtModule())->count()==1 ? $configS->getGroupPList($p, $exec->getCrtModule()) : null)));

		$message = str_replace(array("\n",'"','&quot;'), array('<br />','\\"','\"'), stripslashes($message));
		if($isFromExternalAccess){
			$exec->addJsCode("$('#externalAccessView_form__$fieldName div.value').prepend(\"".$message."\");");
		} else if($elementDialogId=="moduleView"){
			//refresh line item
			//remove moduleView cache + detail cache
			$exec->addJsCode("$('#$elementDialogId #row_".$elementId."__".$fieldName." div.value').prepend(\"".$message."\");");
			$exec->invalidCache($p, 'elementDialog');
			$exec->invalidCache($p, 'moduleView');
		} else {
			//refresh the detail
			$exec->addRequests($elementDialogId."/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/element/detail/" . $elementId);

		}
		$this->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

		$this->executionSink()->publishEndOperation("addJournalItem", $p);
	}

	private $indicatorListPerModule;
	/**
	 * When manipulating the indicatorList you always need to go through this method to be sure to always get the unique one.
	 */
	public function getIndicatorList($p, $exec) {
		$this->executionSink()->publishStartOperation("getIndicatorList");

		$crtModule = $exec->getCrtModule()->getModuleName();
		if (!isset ($this->indicatorListPerModule))
			$this->indicatorListPerModule = array ();
		if (!isset ($this->indicatorListPerModule[$crtModule])) {
			$configS = $this->getConfigurationContext();
			//uncomment the two following lines to reset the indicators of a user
			//			$p->setValueInRoleContext("indicators_list", null);
			//			$this->persistMainPrincipalSessionContext($p, $exec);
			$indicatorList = $p->getValueInRoleContext("indicators_list");
			if ($indicatorList == null || $indicatorList[$crtModule] == null) {
				$this->indicatorListPerModule[$crtModule] = IndicatorListArrayImpl :: createInstance();
			} else if (is_array($indicatorList[$crtModule])) {
				$list = IndicatorListArrayImpl :: createInstance();
				$fl = FieldListArrayImpl :: createInstance(false, true);
				$configS->getFields($p, $exec->getCrtModule(), null, $fl);
				foreach ($indicatorList[$crtModule] as $indicatorId => $indicator) {
					$fs = FieldSelector :: createInstance($indicator["fieldName"], $indicator["subFieldName"]);
					if ($fs->isElementAttributeSelector()) {
						$list->addIndicator($fs, null, $indicator["function"], $indicator["label"], $indicatorId, $indicator["isRecursive"]);
					} else {
						try {
							$field = $fl->getField($indicator["fieldName"]);
							if (!$field)
								continue;
								$list->addIndicator($fs, $field->getDataType(), $indicator["function"], $indicator["label"], $indicatorId, $indicator["isRecursive"]);
						} catch (Exception $e) {
							continue;
						}
					}
				}
				$this->indicatorListPerModule[$crtModule] = $list;
			} else {
				$e = new ServiceException("invalid indicatorList from P context", ServiceException :: INVALID_ARGUMENT);
				$this->executionSink()->publishEndOperationOnError("getIndicatorList", $e, $p);
				throw $e;
			}
			//lookup if systemIndicators are defined in config
			$indicatorListExp = (string)$configS->getParameter($p, $exec->getCrtModule(), "indicatorListExp");
			if($indicatorListExp){
				$systemIndicatorList = $this->evaluateFuncExp($p, $exec, str2fx($indicatorListExp));
				if($systemIndicatorList){
					$this->indicatorListPerModule[$crtModule]->mergeIndicatorList($systemIndicatorList);
					//force the display of the indicators if defined in config
					$p->setValueInRoleContext("indicators_areShown_".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . '_' . $exec->getCrtModule()->getModuleUrl(), true);
				}
			}
		}
		$this->executionSink()->publishEndOperation("getIndicatorList");
		return $this->indicatorListPerModule[$crtModule];
	}

	public function serializeIndicatorsInContext($p, $exec, $indicatorList = null) {
		$this->executionSink()->publishStartOperation("serializeIndicatorsInContext");

		if ($indicatorList === null)
			$indicatorList = $this->getIndicatorList($p, $exec);
		$indicatorListPerModule = $p->getValueInRoleContext("indicators_list");
		if ($indicatorListPerModule == null)
			$indicatorListPerModule = array ();
		$result = array ();
		foreach ($indicatorList->getListIterator() as $indicatorId => $indicator) {
			//only persit if Indicator is not a systemIndicator
			if(!$indicator->isSystemIndicator()){
				$result[$indicatorId] = array (
				"fieldName" => $indicator->getFieldSelector()->getFieldName(), "subFieldName" => $indicator->getFieldSelector()->getSubFieldName(), "function" => $indicator->getFunction(), "dataType" => ($indicator->getDataType() ? $indicator->getDataType()->getDataTypeName() : null), "label" => $indicator->getLabel(), "id" => $indicator->getId(), "timestamp" => $indicator->getTimestamp(), "value" => $indicator->getValue(),"isRecursive" => $indicator->isRecursive());
			}
		}
		$indicatorListPerModule[$exec->getCrtModule()->getModuleName()] = $result;
		$p->setValueInRoleContext("indicators_list", $indicatorListPerModule);

		$this->executionSink()->publishEndOperation("serializeIndicatorsInContext");
	}

	/**
	 * set the language for the system
	 * looks for the title value
	 * default take the first language of defined title
	 * if several take the lang param in account
	 */
	protected function setLanguageToPBasedOnGroupSubscription($p, $exec, $transS, $groupSubscriptionRecord, $lang){
		$title = array();
		if($groupSubscriptionRecord){
			$title = $groupSubscriptionRecord->getFieldValue("title");
			if($title){
				foreach ($title as $key => $val) {
					if ($val == null)
						unset ($title[$key]);
				}
			}
		}
		if ($title && count($title) == 1 || ($lang == null && $title)) {
			reset($title);
			$transS->setLanguage(key($title));
			$p->setValueInGeneralContext("language", key($title));
		} else if($lang){
			$transS->setLanguage($lang);
			$p->setValueInGeneralContext("language", $lang);
		}
	}

	/**
	 * checks subscription period
	 */
	protected function checkPeriodOnGroupSubscription($p, $exec, $transS, $groupSubscriptionRecord, $form, &$state){
		if($groupSubscriptionRecord){
			$now = time();
			$begDate = $groupSubscriptionRecord->getFieldValue("subscriptionPeriod", "begDate");
			if ($begDate != null) {
				$begDate = strtotime($begDate);
				if ($now < $begDate) {
					$state = "closed";
					$form->addAdditionalText($transS->t($p, "subscriptionPeriodToEarly") . date("d.m.Y", $begDate));
				}
			}
			$endDate = $groupSubscriptionRecord->getFieldValue("subscriptionPeriod", "endDate");
			if ($endDate != null) {
				$endDate = strtotime($endDate);
				//add one day, to include the endDate
				$endDateInc = $endDate +60 * 60 * 24;
				if ($now > $endDateInc) {
					$state = "closed";
					$form->addAdditionalText($transS->t($p, "subscriptionPeriodToLate") . date("d.m.Y", $endDate));
				}
			}
			//			echo $now."<br>";
			//			echo $begDate."<br>";
			//			echo $endDate."<br>";
			if ($state != "closed" && $endDate != null && $now <= $endDateInc){
				$form->addAdditionalText($transS->t($p, "subscriptionPeriodWillEnd") . date("d.m.Y", $endDate));
			}
		}
	}

	/**
	 * checks max subscription number
	 */
	protected function checkMaxSubscriptionOnGroupSubscription($p, $exec, $transS, $groupSubscriptionRecord, $form, $elS, $groupP, &$state){
		if($groupSubscriptionRecord){
			$max = $groupSubscriptionRecord->getFieldValue("maxSubscriptionNb");
			$count = 0;
			if ($max != null) {
				$elementCounter = ElementListCounter :: createInstance();
				$elS->getAllElementsInGroup($p, $groupP->getGroup(), $elementCounter, true);
				$count = $elementCounter->getTotal();
				if ($max <= $count) {
					$state = "closed";
					$form->addAdditionalText($transS->t($p, "subscriptionMaxNbReached") . ": " . ($max)); //changed on the 17/11/2017 by LWR, it is not good to indicate to the public what is the real number of final subscribers, as mybe we are ok to add some manual entries and we don't want that the public sees it (taht for example allow overboocking)   
				}
			}
//			echo $max."<br>";
//			echo $count."<br>";
			if ($state != "closed" && $max != null && $max > $count){
				$form->addAdditionalText(($max - $count) . $transS->t($p, "subscriptionMaxNbLefted"));
			}
		}
	}

	/**
	 * return false if not, return array of emailFields if yes
	 */
	protected function canCrtModuleEmailing($module) {
		$this->executionSink()->publishStartOperation("canCrtModuleEmailing");

		$configS = $this->getConfigurationContext();
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
		$emailFields = $configS->mf($p, $module)->xpath("*[@type='Emails' and @enableForEmailing='1' and not(@hidden='1')]");
		if(!$emailFields){			
		    $emailFields = $configS->mf($p, $module)->xpath("*[@type='Emails' and not(@enableForEmailing='0') and not(@hidden='1')]");
		}
		$this->executionSink()->publishEndOperation("canCrtModuleEmailing");

		if (!is_array($emailFields) || $emailFields == null)
			return false;
		return $emailFields;
	}
	/**
	 * return false if not, return array of emailFields if yes 
	 */
	protected function canCrtModuleUnsubscribeEmails($module) {
		$this->executionSink()->publishStartOperation("canCrtModuleUnsubscribeEmails");

		$configS = $this->getConfigurationContext();
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
		$emailFields = $configS->mf($p, $module)->xpath("*[@type='Emails' and @unsubscrable='1' and not(@isMultiple='1')]");
		$this->executionSink()->publishEndOperation("canCrtModuleUnsubscribeEmails");

		if (!is_array($emailFields) || $emailFields == null)
			return false;
		return $emailFields;
	}
	/**
	 * return false if not, return array of fileFields if yes
	 */
	protected function canCrtModuleDownloading($module) {
		$this->executionSink()->publishStartOperation("canCrtModuleDownloading");

		$configS = $this->getConfigurationContext();
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
		$emailFields = $configS->mf($p, $module)->xpath("*[@type='Files' and @enableForDownloading='1' and not(@hidden='1')]");

		$this->executionSink()->publishEndOperation("canCrtModuleDownloading");

		if (!is_array($emailFields) || $emailFields == null)
			return false;
		return $emailFields;
	}
	//deprecated on 3.04.2012, no more use of this method. To know fields in which we could delete some files on disk
	//we use directly xpath("*[@type='Files'] | [@type='Blobs' and @htmlArea='1'] | [@type='Texts' and @htmlArea='1']")
//	//return false if not, return array of fileFields if yes
//	protected function doesCrtModuleHasFiles($module) {
//		$this->executionSink()->publishStartOperation("doesCrtModuleHasFiles");
//
//		$configS = $this->getConfigurationContext();
//		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
//		$fileFields = $configS->mf($p, $module)->xpath("*[@type='Files']");
//
//		$this->executionSink()->publishEndOperation("doesCrtModuleHasFiles");
//
//		if (!is_array($fileFields) || $fileFields == null)
//			return false;
//		return $fileFields;
//	}
	/**
	 * return false if not, return array of fileFields if yes
	 */
	protected function doesCrtModuleHasHtmlAreaFiles($module) {
		$this->executionSink()->publishStartOperation("doesCrtModuleHasHtmlAreaFiles");

		$configS = $this->getConfigurationContext();
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
		$fileFields = $configS->mf($p, $module)->xpath("*[@type='Files' and @htmlArea='1']");

		$this->executionSink()->publishEndOperation("doesCrtModuleHasHtmlAreaFiles");

		if (!is_array($fileFields) || $fileFields == null)
			return false;
		return $fileFields;
	}
	/**
	 * return false if not, return the first found field if yes
	 * warning a module should not have several isKey field
	 */
	public function doesCrtModuleHasIsKeyField($p, $module) {
		$this->executionSink()->publishStartOperation("doesCrtModuleHasIsKeyField");

		$configS = $this->getConfigurationContext();
		$fields = $configS->mf($p, $module)->xpath("*[@isKey='1']");

		$this->executionSink()->publishEndOperation("doesCrtModuleHasIsKeyField");

		if (!is_array($fields) || $fields == null)
			return false;

		if(count($fields)>1){
			throw new ServiceException("isKey field cannot be defined multiple times in a configuration.", ServiceException::INVALID_ARGUMENT);
		}
		$field = $fields[0];
		switch((string)$field->type){
			case "Addresses":
			case "TimeRanges":
			case "Blobs":
			case "Texts":
			case "Varchars":
				throw new ServiceException("isKey field cannot be ".$field->type.".", ServiceException::INVALID_ARGUMENT);
				break;
			default:
				break;
		}
		return $field;
	}
	public function getSubFieldnameForIsKeyField($isKeyXml){
		switch($isKey->type){
			case "Files":
				$subFieldName = "name";
				break;
			case "Urls":
				$subFieldName = "url";
				break;
			default:
				$subFieldName = "value";
		}
		return $subFieldName;
	}
	/**
	 * return false if not, return array of fields with an email attribut if yes
	 * this cheks only for DataType Attributs, MultipleAttributs and Booleans
	 */
	protected function doesCrtModuleHasFiledsWithEmailAttributForNotification($module) {
		$this->executionSink()->publishStartOperation("doesCrtModuleHasFiledsWithEmailAttributForNotification");

		$configS = $this->getConfigurationContext();
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
		$booleansFields = $configS->mf($p, $module)->xpath("*[@type='Booleans' and @email]");
		$attributsFields = $configS->mf($p, $module)->xpath("*[@type='Attributs' and child::attribute[@email]]");
		$multipleAttributsFields = $configS->mf($p, $module)->xpath("*[@type='MultipleAttributs' and child::attribute[@email]]");
		$r = array ();
		if ($booleansFields != false)
			$r = array_merge($r, $booleansFields);
		if ($attributsFields != false)
			$r = array_merge($r, $attributsFields);
		if ($multipleAttributsFields != false)
			$r = array_merge($r, $multipleAttributsFields);

		$this->executionSink()->publishEndOperation("doesCrtModuleHasFiledsWithEmailAttributForNotification");

		if ($r == null)
			return false;
		return $r;
	}
	/**
	 * return first Emails field with enableForEmailing, or then just first Email field. return false if no Emails 
	 */
	public function doesGroupHasEmailField($p, $exec, $group) {
		$this->executionSink()->publishStartOperation("doesGroupHasEmailField");

		$emailField = $this->getConfigurationContext()->gf($p, $group)->xpath("*[@type='Emails' and @enableForEmailing='1']");
		if (!$emailField)
			$emailField = $this->getConfigurationContext()->gf($p, $group)->xpath("*[@type='Emails' and not(@enableForEmailing='0')]");
		if ($emailField) {
			$emailField = $emailField[0]->getName();
		}

		$this->executionSink()->publishEndOperation("doesGroupHasEmailField");

		return $emailField;
	}

	/**
	 * @param boolean $computeElementPolicy if true, then Element policy is calculated for each element of the list.
	 */
	public function getAllElementIdsInListView($p, $exec, $listContext, $computeElementPolicy=false) {
		$this->executionSink()->publishStartOperation("getAllElementIdsInListView");

		$elS = ServiceProvider :: getElementService();
		if($computeElementPolicy) {
			$policyEval = $this->getElementPolicyEvaluator($p, $exec->getCrtModule());
			if(isset($policyEval)) $policyEval->setExecutionService($exec);
			$elementIdsList = ElementIdsListWebImpl :: createInstanceWithPolicyCalculation($p,
				$exec->getCrtModule(), $this->getConfigurationContext(),
				$policyEval);
		}
		else $elementIdsList = ElementIdsListWebImpl :: createInstance();

		$nbRow = $elS->getSelectedElementsInGroups($p, $listContext->getGroupLogExp(), //getGroupList(), $
			$elementIdsList,
			//$listContext->doesGroupListIncludeChildren(),
			$listContext);

		$this->executionSink()->publishEndOperation("getAllElementIdsInListView");

		return $elementIdsList;
	}
	/**
	 * fetches the emails from a listContext
	 * Do not define the fieldSelectorList, this will be defined automatically from the config
	 * $includMergingInfo should be = false when first displaying the email dialog content
	 */
	protected function getAllEmailsInListView($p, $exec, $listContext, $includeMergingInfo) {
		$this->executionSink()->publishStartOperation("getAllEmailsInListView");

		$elS = ServiceProvider :: getElementService();
		$transS = ServiceProvider :: getTranslationService();

		$emailFields = $this->canCrtModuleEmailing($exec->getCrtModule());
		if ($emailFields === false) {
			throw new ServiceException("Module " . $exec->getCrtModule()->getModuleName() . " is not configured to send emails.", ServiceException :: FORBIDDEN);
		}
		$fl = FieldSelectorListArrayWebImpl :: createInstance(false, false);
		$emailLabel = array();
		foreach ($emailFields as $xmlField) {
			$fl->addFieldSelector($xmlField->getName(), "value");
			if($includeMergingInfo) $fl->addFieldSelector($xmlField->getName(), "proofKey");
			if($includeMergingInfo) $fl->addFieldSelector($xmlField->getName(), "externalAccessLevel");
			if($includeMergingInfo) $fl->addFieldSelector($xmlField->getName(), "externalCode");
			$emailLabel["email"][] = $xmlField->getName();
			if((string)$xmlField["label1"]){
				$emailLabel["label1"][$xmlField->getName()] = (string)$xmlField["label1"];
				if($includeMergingInfo) $fl->addFieldSelector((string)$xmlField["label1"]);
			}
			if((string)$xmlField["label2"]){
				$emailLabel["label2"][$xmlField->getName()] = (string)$xmlField["label2"];
				if($includeMergingInfo) $fl->addFieldSelector((string)$xmlField["label2"]);
			}
			if((string)$xmlField["label3"]){
				$emailLabel["label3"][$xmlField->getName()] = (string)$xmlField["label3"];
				if($includeMergingInfo) $fl->addFieldSelector((string)$xmlField["label3"]);
			}
			if((string)$xmlField["labelSep"]){
				$emailLabel["labelSep"][$xmlField->getName()] = (string)$xmlField["labelSep"];
			}
			if((string)$xmlField["other1"]){
				$emailLabel["other1"][$xmlField->getName()] = (string)$xmlField["other1"];
				if($includeMergingInfo) $fl->addFieldSelector((string)$xmlField["other1"]);
			}
			if((string)$xmlField["other2"]){
				$emailLabel["other2"][$xmlField->getName()] = (string)$xmlField["other2"];
				if($includeMergingInfo) $fl->addFieldSelector((string)$xmlField["other2"]);
			}
			if((string)$xmlField["other3"]){
				$emailLabel["other3"][$xmlField->getName()] = (string)$xmlField["other3"];
				if($includeMergingInfo) $fl->addFieldSelector((string)$xmlField["other3"]);
			}
		}
		if (!$fl->isEmpty()) $listContext->setFieldSelectorList($fl);

		$wigiiBag = WigiiBagExtractEmailsAndMergeDataImpl :: createInstance();
		$wigiiBag->setLabels($emailLabel);
		$fieldList = FieldListArrayImpl::createInstance(true, true);
		$this->getConfigurationContext()->getFields($p, $exec->getCrtModule(), null, $fieldList);
		$wigiiBag->setFieldList($fieldList);
		$wigiiBag->setTRM($this->createTRM());
		$nbRow = $elS->fillWigiiBagWithSelectedElementsInGroups($p, $listContext->getGroupLogExp(), $wigiiBag, $listContext);

		$this->executionSink()->publishEndOperation("getAllEmailsInListView");

		return array ($emailLabel, $fieldList, $wigiiBag->getElementIds(), $wigiiBag->getEmails());
	}
	/**
	 * fetches the files from a listContext
	 * Do not define the fieldSelectorList, this will be defined automatically from the config
	 */
	protected function getAllFilesInListView($p, $exec, $listContext) {
		$this->executionSink()->publishStartOperation("getAllFilesInListView");

		$elS = ServiceProvider :: getElementService();
		$transS = ServiceProvider :: getTranslationService();

		$fileFields = $this->canCrtModuleDownloading($exec->getCrtModule());
		if ($fileFields === false) {
			throw new ServiceException("Module " . $exec->getCrtModule()->getModuleName() . " is not configured to download multiple files.", ServiceException :: FORBIDDEN);
		}
		$fl = FieldSelectorListArrayWebImpl::createInstance(true, false);
		foreach ($fileFields as $xmlField) {
			$fl->addFieldSelector($xmlField->getName(), "name");
			$fl->addFieldSelector($xmlField->getName(), "type");
			$fl->addFieldSelector($xmlField->getName(), "date");
		}
		if (!$fl->isEmpty())
			$listContext->setFieldSelectorList($fl);
		$wigiiBag = WigiiBagExtractFilesImpl :: createInstance();
		$nbRow = $elS->fillWigiiBagWithSelectedElementsInGroups($p, $listContext->getGroupLogExp(), $wigiiBag, $listContext);

		$this->executionSink()->publishEndOperation("getAllFilesInListView");

		return array (
		$wigiiBag->getElementIds(), $wigiiBag->getFilePaths());
	}

	public function updateMultipleSelectionDialogForContext($p, $exec, $listContext) {
		$transS = ServiceProvider :: getTranslationService();

		$exec->addJsCode("" .
			"allMultipleSelectionHaveWriteRights = " . ($listContext->allHaveWriteRights() ? "true" : "false") . ";" .
			"multipleSelectionNb = ".count($listContext->getMultipleSelection()).";" .
			"multipleEnableElementState = ".$listContext->getMultipleEnableElementStateAsInt().";" .
			"multipleElementState = ".$listContext->getMultipleElementStateAsInt(3).";" .
			"multipleElementStateMedi = ".$listContext->getMultipleElementStateAsInt(2).";" .
			"multipleElementStateRest = ".$listContext->getMultipleElementStateAsInt(4).";" .
			"multipleElementStatePerm = ".$listContext->getMultipleElementStateAsInt(1).";" .
			"setSelectionMode(".($listContext->isMultipleSelection() ? "true" : "").");" .
			"".(!$listContext->isMultipleSelection() ? "if( $('#multipleDialog').is(':ui-dialog')) { $('#multipleDialog').dialog('close'); }" : "")."" .
			"");

			$configS = $this->getConfigurationContext();
			if($configS->getParameter($p, $exec->getCrtModule(), "Group_enablePortal") == "1" &&
				$configS->getParameter($p, $exec->getCrtModule(), "Group_portalActionRefreshOnMultipleChange") == "1") {
				$groupPortalAction = $configS->getParameter($p, $exec->getCrtModule(), "Group_portalAction");
				if(!empty($groupPortalAction)) {
					$exec->addRequests('groupPortalAction/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/".$groupPortalAction."/");
				}
			}
	}

	/**
	 * create and send an email with the validation link. The validation link should be in the message. If the message
	 * is null, an automatic basic message is setup containing the validation link.
	 * @param $elementP: ElementP / Element, externalValidationLink has no need of write rights
	 * @param $email = null: WigiiEmail / String, if the email is null a standard one is construct. If the email is a string, then it will be used for the body. The recipients are forced
	 * to be the element field value
	 */
	public function sendExternalValidationLink($p, $exec, $elementP, $fieldName, $code, $email = null, $subject=null) {
		$this->executionSink()->publishStartOperation("sendExternalValidationLink");

		if ($elementP == null)
			throw new ServiceException("elementP cannot be null", ServiceException :: INVALID_ARGUMENT);
		$element = $elementP->getDbEntity();
		if ($element == null)
			throw new ServiceException("element cannot be null", ServiceException :: INVALID_ARGUMENT);
		//check that the validation code is = to the validation code of the element field
		if ($element->getFieldValue($fieldName, "proofKey") != $code)
			throw new ServiceException("proofKey is not equal to code:" . $code, ServiceException :: INVALID_ARGUMENT);
		//the field is contnaining emails
		$recipients = $element->getFieldValue($fieldName, "value");
		if ($recipients == null)
			throw new ServiceException("email field value cannot be null", ServiceException :: INVALID_ARGUMENT);

		$emailS = $this->getEmailService();
		$trm = $this->createTRM();

		if ($email == null) {
			$email = $emailS->getEmailInstance();
		} else
			if (is_string($email)) {
				$body = $email;
				$email = $emailS->getEmailInstance();
				$templatePath = $this->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
				ob_start();
				include($templatePath);
				$body = ob_get_clean();
				$email->setBodyHtml($body);
			}
		//complete empty parameters with standard
		if ($email->getSubjectForDb() == null){
			if($subject) $email->setSubject($subject);
			else $email->setSubject($trm->getValidationEmailSubject());
		}

		if ($email->getBodyHtmlForDb() == null){
			$templatePath = $this->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
			$body = $trm->getValidationEmailHtml(null, $code);
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
			$email->setBodyHtml($body);
		}

		$this->doSendEmail($p, $exec, $elementP, $fieldName, $email);

		//throw sendExternalValidationLink event
		$this->throwEvent()->sendExternalValidationLink(PWithElementWithEmailWithFieldname :: createInstance($p, $element, $email, $fieldName));

		$this->executionSink()->publishEndOperation("sendExternalValidationLink");
	}

	/**
	 * send an email to the field content of an element
	 * * @param $email = null: WigiiEmail / String, if the email is null a standard one is construct. If the email is a string, then it will be used for the body. The recipients are forced
	 * to be the element field value
	 */
	public function doSendEmail($p, $exec, $elementP, $fieldName, $email, $subject=null) {
		$this->executionSink()->publishStartOperation("doSendEmail");

		$emailS = $this->getEmailService();
		$configS = $this->getConfigurationContext();
		$trm = $this->createTRM();

		if (is_string($email)) {
			$body = $email;
			$email = $emailS->getEmailInstance();
			$templatePath = $this->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
			$email->setBodyHtml($body);
		}
		//complete empty parameters with standard
		if ($email->getFrom() == null) {
			$pEmail = $p->getValueInGeneralContext("email");
			if ($pEmail){
				$email->setFrom($pEmail);
			} else {
				$email->setFrom((string)$configS->getParameter($p, null, "emailNotificationFrom"));
			}
		}
		if ($email->getSubjectForDb() == null){
			if($subject) $email->setSubject($subject);
			else $email->setSubject($trm->getBaseEmailSubject());
		}

		//allow to send only to element field content
		$email->clearRecipients();
		//cc the sender
		if((string)$configS->getParameter($p, null, "emailNotificationFrom") != $email->getFrom()) $email->addCC($email->getFrom());
		$email->addTo($elementP->getDbEntity()->getFieldValue($fieldName, "value"));

		//send the message with the email service
		$emailS->send($p, $email);

		$this->executionSink()->publishEndOperation("doSendEmail");
	}

	/**
	 * send an email to the field content of an element
	 * * @param $email = null: WigiiEmail / String, if the email is null a standard one is construct. If the email is a string, then it will be used for the body. The recipients are forced
	 * to be the element field value
	 */
	public function sendEmail($p, $exec, $elementP, $fieldName, $email, $subject=null) {
		$this->executionSink()->publishStartOperation("sendEmail");

		if ($elementP == null)
			throw new ServiceException("elementP cannot be null", ServiceException :: INVALID_ARGUMENT);
		$element = $elementP->getDbEntity();
		if ($element == null)
			throw new ServiceException("element cannot be null", ServiceException :: INVALID_ARGUMENT);
		//the field is contnaining emails
		$recipients = $element->getFieldValue($fieldName, "value");
		if ($recipients == null)
			throw new ServiceException("email field value cannot be null", ServiceException :: INVALID_ARGUMENT);

		$this->doSendEmail($p, $exec, $elementP, $fieldName, $email, $subject);

		//throw sendExternalValidationLink event
		$this->throwEvent()->sendEmail(PWithElementWithEmailWithFieldname :: createInstance($p, $element, $email, $fieldName));

		$this->executionSink()->publishEndOperation("sendEmail");
	}

	/**
	 * enables read only external access and send an email with the access link. The access link should be in the message. If the message
	 * is null, an automatic basic message is setup containing the access link.
	 * @param $elementP: ElementP / Element, externalAccessViewLink must have write rights
	 * @param $email = null: WigiiEmail / String, if the email is null a standard one is construct. If the email is a string, then it will be used for the body. The recipients are forced
	 * to be the element field value
	 */
	public function setExternalAccessViewLink($p, $exec, $elementP, $fieldName, $code, $externalAccessEndDate = null, $email = null, $subject=null) {
		$this->executionSink()->publishStartOperation("setExternalAccessViewLink");

		$configS = $this->getConfigurationContext();
		$elS = ServiceProvider :: getElementService();

		if ($elementP == null)
			throw new ServiceException("elementP cannot be null", ServiceException :: INVALID_ARGUMENT);
		$element = $elementP->getDbEntity();
		if ($elementP->getRights() == null)
			throw new ServiceException("elementP rights cannot be null", ServiceException :: FORBIDDEN);
		if (!$elementP->getRights()->canWriteElement())
			throw new ServiceException("elementP rights must have write right to give external read access", ServiceException :: FORBIDDEN);
		if ($element == null)
			throw new ServiceException("element cannot be null", ServiceException :: INVALID_ARGUMENT);
		//check that the access code is = to the access code of the element field
		if ($element->getFieldValue($fieldName, "externalCode") != $code)
			throw new ServiceException("externalCode is not equal to code:" . $code, ServiceException :: INVALID_ARGUMENT);
		//the field is contnaining emails
		$recipients = $element->getFieldValue($fieldName, "value");
		if ($recipients == null)
			throw new ServiceException("email field value cannot be null", ServiceException :: INVALID_ARGUMENT);

		$fieldXml = $element->getFieldList()->getField($fieldName)->getXml();
		//define the group id for config
		$configGroupId = (string)$fieldXml["externalAccessConfigGroup"];
		if(!empty($configGroupId)) {
			$g = ServiceProvider::getGroupAdminService()->getGroupWithoutDetail($p, $configGroupId);
			if(!isset($g)) throw new ServiceException("Invalid externalAccessConfigGroup ".$configGroupId." for field ".$fieldName, ServiceException::CONFIGURATION_ERROR);
			$configGroupId = $g->getId();
		}
		else {
			$configGroupId = $configS->getGroupPList($p, $exec->getCrtModule());
			if ($configGroupId->count() != 1) {
				$configGroupId = null; //if there is more than one group selected, then take the config of the module
			} else {
				$configGroupId = reset($configGroupId->getListIterator())->getId();
			}
		}

		//update the Email field to give the external read access
		$elS->setExternalAccessForView($p, $element->getId(), $fieldName, $externalAccessEndDate, $configGroupId);

		//send the email
		$emailS = $this->getEmailService();
		$trm = $this->createTRM();

		if ($email == null) {
			$email = $emailS->getEmailInstance();
		} else
			if (is_string($email)) {
				$body = $email;
				$email = $emailS->getEmailInstance();
				$templatePath = $this->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
				ob_start();
				include($templatePath);
				$body = ob_get_clean();
				$email->setBodyHtml($body);
			}
		//complete empty parameters with standard
		if ($email->getSubjectForDb() == null){
			if($subject) $email->setSubject($subject);
			else $email->setSubject($trm->getExternalAccessViewEmailSubject());
		}
		if ($email->getBodyHtmlForDb() == null){
			$templatePath = $this->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
			$body = $trm->getExternalAccessViewEmailHtml(null, $code, $element->getFieldValue($fieldName, "proofKey"), $element->getFieldValue($fieldName, "proofStatus"), false);
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
			$email->setBodyHtml($body);
		}

		$this->doSendEmail($p, $exec, $elementP, $fieldName, $email);

		$this->throwEvent()->setExternalAccessViewLink(PWithElementWithEmailWithFieldname :: createInstance($p, $element, $email, $fieldName));

		$this->executionSink()->publishEndOperation("setExternalAccessViewLink");
	}

	/**
	 * enables read/write external access and send an email with the access link. The access link should be in the message. If the message
	 * is null, an automatic basic message is setup containing the access link.
	 * @param $elementP: ElementP / Element, externalAccessEditLink must have write rights
	 * @param $email = null: WigiiEmail / String, if the email is null a standard one is construct. If the email is a string, then it will be used for the body. The recipients are forced
	 * to be the element field value
	 */
	public function setExternalAccessEditLink($p, $exec, $elementP, $fieldName, $code, $externalAccessEndDate = null, $email = null, $subject=null) {
		$this->executionSink()->publishStartOperation("setExternalAccessEditLink");

		$configS = $this->getConfigurationContext();
		$elS = ServiceProvider :: getElementService();

		if ($elementP == null)
			throw new ServiceException("elementP cannot be null", ServiceException :: INVALID_ARGUMENT);
		$element = $elementP->getDbEntity();
		if ($elementP->getRights() == null)
			throw new ServiceException("elementP rights cannot be null", ServiceException :: FORBIDDEN);
		if (!$elementP->getRights()->canWriteElement())
			throw new ServiceException("elementP rights must have write right to give external edit access", ServiceException :: FORBIDDEN);
		if ($element == null)
			throw new ServiceException("element cannot be null", ServiceException :: INVALID_ARGUMENT);
		//check that the access code is = to the access code of the element field
		if ($element->getFieldValue($fieldName, "externalCode") != $code)
			throw new ServiceException("externalCode is not equal to code:" . $code, ServiceException :: INVALID_ARGUMENT);
		//the field is contnaining emails
		$recipients = $element->getFieldValue($fieldName, "value");
		if ($recipients == null)
			throw new ServiceException("email field value cannot be null", ServiceException :: INVALID_ARGUMENT);

		$fieldXml = $element->getFieldList()->getField($fieldName)->getXml();
		//define the group id for config
		$configGroupId = (string)$fieldXml["externalAccessConfigGroup"];
		if(!empty($configGroupId)) {
			$g = ServiceProvider::getGroupAdminService()->getGroupWithoutDetail($p, $configGroupId);
			if(!isset($g)) throw new ServiceException("Invalid externalAccessConfigGroup ".$configGroupId." for field ".$fieldName, ServiceException::CONFIGURATION_ERROR);
			$configGroupId = $g->getId();
		}
		else {
			$configGroupId = $configS->getGroupPList($p, $exec->getCrtModule());
			if ($configGroupId->count() != 1) {
				$configGroupId = null; //if there is more than one group selected, then take the config of the module
			} else {
				$configGroupId = reset($configGroupId->getListIterator())->getId();
			}
		}			

		//update the Email field to give the external read access
		$elS->setExternalAccessForEdit($p, $element->getId(), $fieldName, $externalAccessEndDate, $configGroupId);

		//send the email
		$emailS = $this->getEmailService();
		$trm = $this->createTRM();

		if ($email == null) {
			$email = $emailS->getEmailInstance();
		} else
			if (is_string($email)) {
				$body = $email;
				$email = $emailS->getEmailInstance();
				$templatePath = $this->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
				ob_start();
				include($templatePath);
				$body = ob_get_clean();
				$email->setBodyHtml($body);
			}
		//complete empty parameters with standard
		if ($email->getSubjectForDb() == null){
			if($subject) $email->setSubject($subject);
			else $email->setSubject($trm->getExternalAccessEditEmailSubject());
		}
		if ($email->getBodyHtmlForDb() == null){
			$templatePath = $this->getConfigurationContext()->getTemplatePath($p, $exec->getCrtModule(), Activity::createInstance("BaseEmail", $exec->getCrtModule()));
			$body = $trm->getExternalAccessEditEmailHtml(null, $code, $element->getFieldValue($fieldName, "proofKey"), $element->getFieldValue($fieldName, "proofStatus"), false);
			ob_start();
			include($templatePath);
			$body = ob_get_clean();
			$email->setBodyHtml($body);
		}

		$this->doSendEmail($p, $exec, $elementP, $fieldName, $email);

		$this->throwEvent()->setExternalAccessEditLink(PWithElementWithEmailWithFieldname :: createInstance($p, $element, $email, $fieldName));

		$this->executionSink()->publishEndOperation("setExternalAccessEditLink");
	}

	/**
	 * disabled external access.
	 * @param $elementP: ElementP / Element, must have write rights
	 * @param $email = null: WigiiEmail / String, if the email is null a standard one is construct. If the email is a string, then it will be used for the body. The recipients are forced
	 * to be the element field value
	 */
	public function stopExternalAccess($p, $exec, $elementP, $fieldName, $code) {
		$this->executionSink()->publishStartOperation("stopExternalAccess");

		$configS = $this->getConfigurationContext();
		$elS = ServiceProvider :: getElementService();

		if ($elementP == null)
			throw new ServiceException("elementP cannot be null", ServiceException :: INVALID_ARGUMENT);
		$element = $elementP->getDbEntity();
		if ($elementP->getRights() == null)
			throw new ServiceException("elementP rights cannot be null", ServiceException :: FORBIDDEN);
		if (!$elementP->getRights()->canWriteElement())
			throw new ServiceException("elementP rights must have write right to give external read access", ServiceException :: FORBIDDEN);
		if ($element == null)
			throw new ServiceException("element cannot be null", ServiceException :: INVALID_ARGUMENT);
		//check that the access code is = to the access code of the element field
		if ($element->getFieldValue($fieldName, "externalCode") != $code)
			throw new ServiceException("externalCode is not equal to code:" . $code, ServiceException :: INVALID_ARGUMENT);
		//the field is containing emails
		$recipients = $element->getFieldValue($fieldName, "value");
		if ($recipients == null)
			throw new ServiceException("email field value cannot be null", ServiceException :: INVALID_ARGUMENT);

		//update the Email field to stop the external read access
		$elS->stopExternalAccess($p, $element->getId(), $fieldName);

		$this->throwEvent()->stopExternalAccessLink(PWithElementWithFieldname :: createInstance($p, $element, $fieldName));

		$this->executionSink()->publishEndOperation("stopExternalAccess");
	}

	/**
	 * Create the XMl code for the 'Access all folders', 'Root folder creator', 'Folder creator' option in Admin role/user popup
	 * @param Principal $p 	 
	 * @param Field $field the field for which xml needs to be changed
	 * @param array $moduleAccess the complete list of Modules
	 * @param array $adminRight the list of module access for the admin 
	 * @param array $userRight the list of module access for the users
	 * @return SimpleXMLElement the XMl code
	 */
	private function createMultipleCheckForAdminForm($p, $field, $moduleAccess, $adminRight, $userRight) {
		$transS = ServiceProvider :: getTranslationService();		
		
		$newXmlCode = simplexml_load_string('<'.$field->getFieldName().'/>');
		foreach ($field->getXml()->attributes() as $attrName=>$attrValue){
			$newXmlCode[$attrName] = $attrValue;
		}
		
		if($moduleAccess) {
			if($adminRight==null) $adminRight = array();
			if($userRight==null) $userRight = array();
			foreach ($moduleAccess as $moduleName => $module) {
				$attrXml = simplexml_load_string("<attribute>$moduleName</attribute>");
				if(!$p->isWigiiNamespaceCreator() && !$adminRight[$moduleName]) $attrXml['disabled']="1";
				$moduleName = tryStr2Xml($transS->t($p, $moduleName));
				if($moduleName instanceof SimpleXMLElement) {
					$attrXml->{'label'} = '';
					simplexml_appendChild($attrXml->{'label'}, $moduleName);
				}
				else $attrXml->{'label'} = $moduleName;
				simplexml_appendChild($newXmlCode, $attrXml); 
			}
		}
		return $newXmlCode;
	}
	
	/**
	 * Create access and folder creator form
	 * @param User $user $user current user 
	 * @param Principal $p
	 * @param Record $userEditRec record user edit form
	 */
	private function createAccessAndFolderCreatorForm($user, $p, $userEditRec){
		// full module access
		$moduleAccess = $p->getModuleAccess();
		$userRight = $user->getDetail()->getModuleAccess();
		if($moduleAccess && $userRight) $moduleAccess = array_merge($moduleAccess, $userRight);
		elseif($userRight) $moduleAccess = $userRight;
		if($moduleAccess) {
			unset($moduleAccess[Module::ADMIN_MODULE]);
			unset($moduleAccess[Module::HELP_MODULE]);			
		}  
		
		// Read all groups in WigiiNamespace
		$adminRight = $p->getReadAllGroupsInWigiiNamespace();					
		$userRight = $user->getDetail()->getReadAllGroupsInWigiiNamespace();			
		$field = $userEditRec->getFieldList()->getField('getReadAllGroupsInWigiiNamespace');
		$field->setXml($this->createMultipleCheckForAdminForm($p, $field, $moduleAccess, $adminRight, $userRight));
			
		// Root Group creator
		$adminRight = $p->getRootGroupCreator();
		$userRight = $user->getDetail()->getRootGroupCreator();			
		$field = $userEditRec->getFieldList()->getField('getRootGroupCreator');
		$field->setXml($this->createMultipleCheckForAdminForm($p, $field, $moduleAccess, $adminRight, $userRight));
			
		// Group creator
		$adminRight = $p->getGroupCreator();
		$userRight = $user->getDetail()->getGroupCreator();
		$field = $userEditRec->getFieldList()->getField('getGroupCreator');
		$field->setXml($this->createMultipleCheckForAdminForm($p, $field, $moduleAccess, $adminRight, $userRight));
	}	
	
	
	// Wigii communication protocol handling
	
	
	/**
	 * Processes the HTTP request
	 * Postcondition: this method never throws an exception, all errors are trapped and displayed correctly to the user
	 */
	public function processAndEnds($started = false) {
		try {
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."start process and ends"] = microtime(true);
			$this->executionSink()->log("start process and ends");
	
			//alias for the ExecutionService
			$exec = ServiceProvider :: getExecutionService();
	
			//starts the Execution service, and load the request
			if (!$started)
				$exec->start();
	
			//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."execution service started"] = microtime(true);
			$this->executionSink()->log("execution service started");
	
			//			if(!$started) eput("start");
	
			//			$started = false;
			$openAnswer = false; //just a flag in case of exception thrown during answering. if this is the case we need to close the answer in the exception management
	
			$hasRelogedIn = false;
			if (!$started) {
				//				if(!$started) eput("try auto login");
				if ($this->doAutoLoginIfNeeded()) {
					//					if(!$started) eput("auto login succeed");
					//after each login we need to clear the configuration context to reload everything
					$this->clearWigiiContext();
					$hasRelogedIn = true;
					// CWE 2015.02.11: clear all requests and force reload
					$rRequests = $exec->getRemainingRequests();
					if(count($rRequests) == 1) {
						$rAction = ($exec->getIsUpdating() ? $rRequests[0][3] : $rRequests[0][2]);
						if($rAction != 'navigate' && $rAction != 'externalAccess'  && $rAction != 'newSubscription' && $rAction != 'validateEmailFromCode' && !$this->shouldByPassHeader($rAction)) {
							//fput($exec->displayDebug());
							$exec->cleanRemainingRequest();
							$exec->addJsCode('self.location.reload();');
						}
					}
				}
			}
	
			//if no request, then display the last module for principal
			$p = $exec->getExecPrincipal();
	
			$this->executionSink()->publishStartOperation("processAndEnds", $p);
	
			$transS = ServiceProvider :: getTranslationService();
			$sessAS = ServiceProvider::getSessionAdminService();
	
			//add request on Fragment only if no request is currently defined
			// CWE 30.01.2015: pushed the fragment management on client side (recoded in header.php in javascript), no need to do it on server side anymore
	
			if(!$started && !$exec->getIsUpdating() && $exec->getRemainingRequests() == null){
				//$exec->addRequestOnFragment();
				$exec->addRequests(WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: HOME_MODULE . "/start");
				//$exec->addRequests(WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: EMPTY_MODULE_URL . "/display/all");
			}
	
			/*
			 if ($exec->getRemainingRequests() == null) {
			 //				if(!$started) eput("no remaining request, add the default");
			 if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) {
			 $exec->addRequests(WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: EMPTY_MODULE_URL . "/display/all");
			 //					if(!$started) eput("add request: /display/all");
			 } else {
			 if($hasRelogedIn){
			 $exec->addRequests(WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: HOME_MODULE . "/start");
			 //						if(!$started) eput("add request: /start");
			 } else {
			 $roleId = $p->getValueInGeneralContext("lastRoleId");
			 // checks role validity
			 if($p->getRoleListener()->getUser($roleId)) {
			 $exec->addRequests(WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: EMPTY_MODULE_URL . "/navigate/user/" . $roleId);
			 }
			 else $exec->addRequests(WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: HOME_MODULE . "/start");
			 }
			 }
			 }
			 */
	
			//we keep in memory the lastAction, just to be able to manage the sending of footer for downloads or JSON
			$lastAction = null;
			$configurationContextSupportsSubElements = ($this->getConfigurationContext() instanceof ConfigurationContextSubElementImpl);
			$byPassedHeader=false;
			while ($exec->loadNextRequest($this)) {
				//				eput($exec->getCrtRequest());
				//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."request ".$exec->getCrtAction()." loaded"] = microtime(true);
				$this->executionSink()->log("request ".$exec->getCrtAction()." loaded");
	
				// modified by CWE on February 4th 2014 to support sub elements
				// injects current configuration key
				if($configurationContextSupportsSubElements) $this->getConfigurationContext()->generateAndSetConfigContextKey($exec);
	
				//reload the p, because it could change during the request (on changing role or login/logout)
				$p = $exec->getExecPrincipal();
				$lastAction = $exec->getCrtAction();
	
				if (!$started) {
					//***********
					//HEADER LOADING
					//***********
					//the header is sent at the first request only except
					//if this specific request needs to define his own specific header
					if (!$this->shouldByPassHeader($exec->getCrtAction())) {
						//send the header only at the first request
						$this->getHttpHeader();
						//load the header
						$SITE_TITLE = $this->getConfigurationContext()->getParameter($p, null, "siteTitle");
						if ($exec->getIdAnswer()=="newDialog"){
							$SITE_TITLE = ServiceProvider :: getTranslationService()->t($p, $exec->getCrtModule()->getModuleName()) . " : " . $SITE_TITLE;
						}
						if ($exec->getCrtAction() == "display" && $exec->getCrtParameters(0) == "detachModule")
							$SITE_TITLE = ServiceProvider :: getTranslationService()->t($p, $exec->getCrtModule()->getModuleName()) . " : " . $SITE_TITLE;
						if ($exec->getIdAnswer()=="newDialog" || !$exec->getIsUpdating()){
                            include_once (IMPL_PATH . "templates/header.php");
                        }
					}
					else $byPassedHeader=true;
					$started = true;
				}
	
				if ($exec->getIsUpdating()) {
					if ($exec->getIdAnswer() != "download" && (!$this->shouldByPassHeader($exec->getCrtAction()) || $exec->getIdAnswer() == "elementPreview")) {
						echo $exec->getIdAnswer();
						echo ExecutionServiceImpl :: answerParamSeparator;
						$openAnswer = true;
					}
				}
				$this->executionSink()->publishStartOperation("executeAction: ".$exec->getCrtAction(), $p);
				$this->executeAction($exec);
				$this->executionSink()->publishEndOperation("executeAction: ".$exec->getCrtAction(), $p);
	
				if ($exec->getIsUpdating() && (!$this->shouldByPassHeader($exec->getCrtAction()) || $exec->getIdAnswer() == "elementPreview")) {
					echo ExecutionServiceImpl :: answerRequestSeparator;
					$openAnswer = false;
				}
	
				//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++." "."request ".$exec->getCrtAction()." ended"] = microtime(true);
				$this->executionSink()->log("request ".$exec->getCrtAction()." ended");
	
			}
	
			//load the footer
			if (!$this->shouldByPassFooter($lastAction) && !$exec->getIsUpdating())
				include_once (IMPL_PATH . "templates/footer.php");
	
		} catch (AuthenticationServiceException $ase) {
			if ($ase->getCode() == AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL ||
				$ase->getCode() == AuthenticationServiceException :: FORBIDDEN_PUBLIC_USER ||
				$ase->getCode() == AuthenticationServiceException :: EXPIRED_PRINCIPAL ||
				$ase->getCode() == AuthenticationServiceException :: EXPIRED_PASSWORD) {
				
				// if authentication exception and current action doesn't have any header sent, then answers with a redirect 302 to main login page.
				if($byPassedHeader && !$exec->getIsUpdating()) {
					header("Location:".SITE_ROOT);
					exit;
				}
				
				//session expired -> need reauthentication
				if ($exec->getIsUpdating() && $openAnswer) {
					echo ExecutionServiceImpl :: answerRequestSeparator;
				}
				if($ase->getCode() == AuthenticationServiceException :: EXPIRED_PASSWORD){
					$_POST["action"] = null; //this is to prevent checking changeOwnPassword form on first display
					$exec->cleanRemainingRequest();
					if ($exec->getIsUpdating()) {
						$exec->addRequests("changePasswordDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/changePassword");
					} else {
						//CWE 14.01.2016: in case of expired password, then shows changePassword dialog once Home/start is called by JS instead of directly on first request,
						//this to avoid having a double change password dialog which shows up (one in mainDiv and the other in changePassword dialog),
						//leading to messy refresh when the user clicks on the OK button.
						//$exec->addRequests($exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/changePassword");
					}
				} elseif ($ase->getCode() == AuthenticationServiceException :: FORBIDDEN_PUBLIC_USER) {
					// logout from public
					ServiceProvider::getAuthenticationService()->logout();
					$this->clearWigiiContext();
					$sessAS->clearData($exec, "lastPrincipalIdContext");
					$_POST["action"] = null;
					// displays login page
					$exec->cleanRemainingRequest();
					$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/":''). $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/all");
				} else {
					if ($exec->getIsUpdating()) {
						//display a dialog box with the login form
						$exec->cleanRemainingRequest();
						$exec->addRequests("elementDialog/" . WigiiNamespace :: EMPTY_NAMESPACE_URL . "/Admin/login");
					} else {
						$exec->addRequests(WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: EMPTY_MODULE_URL . "/display/all");
					}
				}
				/*
				 if (!isset ($_SESSION["RemainingUpdates"])) $_SESSION["RemainingUpdates"] = array ();
				 $_SESSION["RemainingUpdates"][] = (!$exec->getIsUpdating() ? "mainDiv/" : "").$exec->getCrtRequest();
				 */
				//eput($exec->displayDebug());
				//				$exec->addRequests('elementDialog/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl().'/'.$exec->getCrtModule()->getModuleUrl().'/display/login');
				$this->executionSink()->publishEndOperationOnError("processAndEnds", $ase, $p);
				return $this->processAndEnds(true);
			}
			//throw $ase;
			if ($exec->getIsUpdating() && $openAnswer)
				echo ExecutionServiceImpl :: answerRequestSeparator;
			ExceptionSink :: publish($ase);
			if ($this->shouldByPassFooter($lastAction)) {
				$this->executionSink()->publishEndOperationOnError("processAndEnds", $ase, $p);
				throw $ase;
			}
		} catch (Exception $e) {
			if ($exec->getIsUpdating() && $openAnswer)
				echo ExecutionServiceImpl :: answerRequestSeparator;
			ExceptionSink :: publish($e);
			//if there is an exception and we shouldByPassHeaderAndFooter then we need
			//to display it, other wise, we will never see it!
			if ($this->shouldByPassFooter($lastAction)) {
				//				echo "Message:\n";
				//				eput($e->getMessage());
				//				echo "\nCode:\n";
				//				eput($e->getCode());
				$this->executionSink()->publishEndOperationOnError("processAndEnds", $e, $p);
				throw $e;
			}
	
		}
	
		//		//add the wigii version control update:
		//		$updateMessage = "theSystemHasJustBeenUpgraded";
		//		$updateMessageT = $transS->h($p, "theSystemHasJustBeenUpgraded");
		//		if ($updateMessageT == $updateMessage) {
		//			$updateMessage = "The Wigii system needs to upgrade. Please restart your browser.";
		//		} else
		//			$updateMessage = $updateMessageT;
		//		$updateMessage = str_replace("//", '\/\/', $this->getIconForDialog("warning")).'<div style="float:right;width:220px;">'.$updateMessage."</div>";
		//
		//		$exec->addJsCode("
		//f = $('#footerBar span:first a');
		//if(f.length){
		//	if(f.text() != 'f".VERSION_LABEL."'){
		//		$('#confirmationDialog').html('$updateMessage');
		//		$('#confirmationDialog').dialog('destroy').dialog({
		//			buttons: {
		//			'".$transS->h($p, "close")."': function(){ $(this).dialog('destroy'); self.close(); }
		//			},
		//			width:300,
		//			closeOnEscape: false, stack:false, resizable:false, zIndex:9999
		//		});
		//		$('#confirmationDialog').prev().css('display','none');
		//	}
		//}
		//");
	
		//add wigii username in footer
		$exec->addJsCode("$('#pUsername').remove();$('#wigiiVersionLabel').after('<span id=\"pUsername\">".$p->getUsername()."</span>');");
		if(defined('RELOAD_Message')&& RELOAD_Message){
			$exec->addJsCode("" .
					"if($('#footerBar span:first a').length){" .
					"if($('#footerBar span:first a').text() != '".VERSION_LABEL."'){" .
					"if($('#wigiiMaintenanceMessage').length==0){ $('#pUsername').after('<span UNSELECTABLE=\"on\" style=\" -moz-user-select: none; -khtml-user-select: none; unselectable:on; user-select: none; \" ><span id=\"wigiiMaintenanceMessage\" style=\"color:#fff; background-color:#f00; font-weight:bold;font-size:14px\"></span></span>'); } $('#wigiiMaintenanceMessage').html('".(RELOAD_Message ? '&nbsp;&nbsp;'.RELOAD_Message.'&nbsp;&nbsp;' : '')."');" .
					"}" .
					"}");
		}
		$this->executionSink()->publishEndOperation("processAndEnds", $p);
	
		//$GLOBALS["executionTime"][$GLOBALS["executionTimeNb"]++."end processAndEnds"] = microtime(true);
	
		//ends the execution and display errors
		if (!$this->shouldByPassFooter($lastAction)) {
			$exec->end();
		} else {
			//it is important that for the download or the JSON there is nothing else
			//send. Even in the test API done in the index
			exit;
		}
	}
	
	/**
	 * Defines each action on which we need to not send other information before and after the specific action
	 */
	protected function shouldByPassHeader($action) {
		switch ($action) {
			case "download" :
			case "CKEditor" :
			case "exportAndDownload" :
			case "getXmlFeed" :
			case "downloadFromExternalAccess" :
			case "downloadFromExternalAccess" :
			case "getNextElementInList" :
			case "getNextElementInBlog" :
			case "getNextElementInPreviewList" :
            case "getCalendarEvents" :
				//			case "JSON":
				//			case "PDF":
				//			case "Excel"
				//			case "XML":
				//			case "CSVRaw":
				//			case "CSVPretty":
				return true;
				break;
			default :
				return false;
		}
	}
	protected function shouldByPassFooter($action) {
		switch ($action) {
			case "CKEditor" :
			case "getNextElementInList" :
			case "getNextElementInBlog" :
			case "getNextElementInPreviewList" :
			case "getCalendarEvents" :
			case "exportAndDownload" :
				return true;
				break;
			default :
				return false;
		}
	}

    /**
     * Return true if the current action requires a responsive HTML rendering
     * Typcally used in the public access to be compatible with mobile
     */
    public function shouldBeResponsive($action) {
        $exec = ServiceProvider :: getExecutionService();
        switch ($action) {
            case "newSubscription" :
            case "externalAccess" :
            case "externalAccessRequest" :
                return true;
                break;
            case "element":
                if($exec->getCrtParameters(0) == "print") return true;
                break;
            default :
                return false;
        }
    }
	
	protected function doAutoLoginIfNeeded() {
		$this->executionSink()->publishStartOperation("doAutoLoginIfNeeded");
		$authS = ServiceProvider :: getAuthenticationService();
		$exec = ServiceProvider :: getExecutionService();
	
		// try auto login using credentials cookie
		$returnValue = $authS->autoLogin();
		// if not successfull, try auto login as public user
		if(!$returnValue &&
				(strpos($exec->findUrl(), 'JSCode/'.WigiiNamespace::EMPTY_NAMESPACE_URL.'/'.Module::EMPTY_MODULE_URL.'/wakeup')===false) &&
				(strpos($exec->findUrl(), 'loginForm/'.WigiiNamespace::EMPTY_NAMESPACE_URL.'/'.Module::ADMIN_MODULE.'/login')===false)) $returnValue = $authS->autoLoginAsPublic();
		// if logged in, then stores roles in session
		if($returnValue) $this->storeAdminAndCalculatedRoleIdsInSession($authS->getMainPrincipal());
		$this->executionSink()->publishEndOperation('doAutoLoginIfNeeded');
		return $returnValue;
	}
	
	/**
	 * Given an action, finds a WebExecutor class name which can handle it.
	 * @param String $action ExecutionService action in the Wigii communication protocol.
	 * @return String|StdClass|WebExecutor the name of a class implementing WebExecutor interface, or null if not found;
	 * or a StdClass instance of the form {className: String, options: ObjectConfigurator} defining the class name to instantiate and some configuration options,
	 * or an instance of a ready to use WebExecutor
	 */
	protected function findWebExecutorForAction($action) {return null;}
	/**
	 * Runs the WebExecutor on the given http request
	 * @param String|StdClass|WebExecutor $webExecClass the name of the WebExecutor implementation to instanciate and run, or
	 * a StdClass instance of the form {className: String, options: ObjectConfigurator} defining the class name to instantiate and some configuration options,
	 * or an already instanciated WebExecutor class
	 * @param ExecutionService $exec current ExecutionService
	 * @throws AuthenticationServiceException if main principal is minimal.
	 * @throws Exception any exception that occur during WebExecutor execution.
	 */
	protected function runWebExecutor($webExecClass,$exec) {
		if(!isset($webExecClass)) return;
		if(!isset($exec)) throw new ServiceException('ExecutionService cannot be null', ServiceException::INVALID_ARGUMENT);		
		if($webExecClass instanceof WebExecutor) $webExec = $webExecClass;
		else {
			// checks if some configuration options are given
			$options=null;
			if(is_object($webExecClass)) {
				$options = $webExecClass->options;
				$webExecClass = $webExecClass->className;
			}
			// instantiates a configured WebExecutor
			$webExec = ServiceProvider::createWigiiObject($webExecClass);
		}
		if(isset($webExec)) {
			// sets dynamic configuration if provided
			if(isset($options)) {
				$options->configure($webExec);
			}
			
			// injects dependencies
			$webExec->setWigiiExecutor($this);
			$webExec->setConfigService($this->getConfigurationContext());
			
			// checks authorization
			$authS = ServiceProvider :: getAuthenticationService();
			$p = $authS->getMainPrincipal();
			if ($authS->isMainPrincipalMinimal()) {
				// checks if public principal is authorized
				if($webExec->isPublicPrincipalAuthorized()) {
					$p = $this->getPublicPrincipal();
				}
				// checks if MinimalPrincial is authorized
				elseif (!$webExec->isMinimalPrincipalAuthorized()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
			}
					
			// runs WebExecutor
			$webExec->processAndEnds($p,$exec);
		}
	}
	
	/**
	 * Executes the requested action
	 * @param ExecutionService $exec The execution service for this request
	 */
	protected function executeAction($exec) {
		if(false){ //this is just a place to develope some js script without the need to compile js and reload browser to test the result. This section should be then copy paste in js files and the if turned to false
?><script type="text/javascript" >

</script><?
		}
		$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
		switch ($exec->getCrtAction()) {
			case "finishCurrentOperation" :
				$transS = ServiceProvider :: getTranslationService();
				$params = $exec->getCrtParameters();
				$urlToDo = array_search("_do_", $params);
				if ($urlToDo !== false) {
					$urlToDo = implode("/", array_slice($params, $urlToDo +1)); //, $prevUrl-$urlToDo-1));
					$forceQuitJsCode = ' update("' . $urlToDo . '");';
					$this->openAsMessage("confirmationDialog", 250, null, $transS->t($p, "finishCurrentOperation"), null, $transS->t($p, "ok"), $forceQuitJsCode, $transS->t($p, "forceToQuit"), "alert");
				} else {
					$this->openAsMessage("confirmationDialog", 250, null, $transS->t($p, "finishCurrentOperation"), null, $transS->t($p, "ok"), null, null, "alert");
				}
				break;
			case "saveBeforeCloseDialog" :
				$transS = ServiceProvider :: getTranslationService();

				$params = $exec->getCrtParameters();
				$idCurrentDialog = array_search("_id_", $params);
				if ($idCurrentDialog !== false)
					$idCurrentDialog = $params[$idCurrentDialog +1];
				$urlToDo = array_search("_do_", $params);
				//			$prevUrl = array_search("_prev_", $params);
				$urlToDo = implode("/", array_slice($params, $urlToDo +1)); //, $prevUrl-$urlToDo-1));
				//			$prevUrl = implode("/", array_slice($params, $prevUrl+1));

				if ($urlToDo == "undefined" || $urlToDo === "null")
					$urlToDo = null;
				//			if($prevUrl == "undefined" || $prevUrl === "null") $prevUrl = null;
?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/32x32/status/not-known.png";?>" style="float:left;margin:0px 15px 15px 15px;" /><?

				echo $transS->t($p, "doYouWantToSave_$idCurrentDialog");
				//			eput("id:".$idCurrentDialog."\n");
				//			eput("do:".$urlToDo."\n");
				//			eput("prev:".$prevUrl."\n");

				$noJsCode = " $('#" . $idCurrentDialog . "').next().find('button:eq(0)').click(); ";
				//$noJsCode='actOnCloseDialog("'.$idCurrentDialog.'");';
				if ($urlToDo != null) {
					$noJsCode .= ' update("' . $urlToDo . '");';
				}
				//$noJsCode .= '$("#'.$idCurrentDialog.'").dialog("destroy");';

				//we add the request in the stack if we hit ok. like this, when the ok is completed it will be
				$okJsCode = "";
				if ($urlToDo != null) {
					$okJsCode .= ' storeChangeRequest("' . $idCurrentDialog . '", "' . $urlToDo . '"); ';
				}
				$okJsCode .= " $('#" . $idCurrentDialog . "').next().find('button:eq(1)').click(); ";

				//			if($prevUrl != null){
				//
				//			}
				$this->openAsConfirmationDialog("confirmationDialog", 250, $okJsCode, $noJsCode, null);

				break;
			case "toggleSysConsole" :
				$_SESSION["SYS_CONSOLE_ENABLED"] = !$_SESSION["SYS_CONSOLE_ENABLED"];
				//$_SESSION["DEBUG_EXECUTION_ENABLED"] = !$_SESSION["DEBUG_EXECUTION_ENABLED"];
				break;
			case "setBrowser" :
				$exec->setBrowserName($exec->getCrtParameters(0));
				$exec->setBrowserVersion($exec->getCrtParameters(1));
				$this->executionSink()->log("set browser to:" . $exec->getBrowserName() . " " . $exec->getBrowserVersion());
				break;
			case "changeLanguage" :
				ServiceProvider :: getTranslationService()->setLanguage($exec->getCrtParameters(0));
				$p->setValueInGeneralContext("language", $exec->getCrtParameters(0));
				$this->throwEvent()->switchLanguage(PWithUserWithLanguage :: createInstance($p, $p->getAttachedUser(), ServiceProvider :: getTranslationService()->getLanguage()));
				//cancel all the caching
				$exec->addJsCode("invalidCompleteCache();");
				if($exec->getCrtModule()->getModuleName() == "" ||
					$exec->getCrtModule()->getModuleName() == "null" ||
					$exec->getCrtModule()->getModuleName() == Module :: EMPTY_MODULE_URL ||
					$exec->getCrtModule()->getModuleName() == Module :: HOME_MODULE
					){
					$exec->addRequests("mainDiv/".WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: HOME_MODULE . "/start");
				} else {
					$exec->addRequests("mainDiv/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/all");
				}
				//persist context in DB;
				$this->persistMainPrincipalSessionContext($p, $exec);
				break;
			case "toggleAdmin" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule()) {
					$this->getAdminContext($p)->setWorkingModule($exec->getCrtModule());
					$exec->addRequests("mainDiv/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/all");
				} else {
					$exec->addRequests("mainDiv/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $this->getAdminContext($p)->getWorkingModule()->getModuleUrl() . "/display/all");
				}
				//			//persist context in DB; this should be done with the display all
				//			$this->persistMainPrincipalSessionContext($p, $exec);
				break;
			case "setFilterUser" :
				//if a second parameters is set, we switch to the new working module
				if($exec->getCrtParameters(1)!=null){
					$this->getAdminContext($p)->setWorkingModule(ServiceProvider :: getModuleAdminService()->getModule($p, $exec->getCrtParameters(1)));
				}
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				try {
					$this->getAdminContext($p)->setUserFilterFromPost();
					$exec->addRequests("adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/" . $this->getAdminContext($p)->getSubScreen());
				} catch (StringTokenizerException $ace) {
					$transS = ServiceProvider :: getTranslationService();
					echo $transS->t($p, "parseErrorInTextSearch");
					echo "\n(" . $ace->getMessage() . ")";
				}
				//				if($ace->getCode()==StringTokenizerException::SYNTAX_ERROR){
				//					$transS = ServiceProvider::getTranslationService();
				//
				//					echo ExecutionServiceImpl::answerRequestSeparator;
				//					echo "confirmationDialog";
				//					echo ExecutionServiceImpl::answerParamSeparator;
				//					$this->openAsMessage(
				//						"confirmationDialog",
				//						350,
				//						$transS->t($p, "invalidFilterUser"),
				//						$transS->t($p, "invalidFilterUserExplanation"),
				//						"actOnCloseDialog('confirmationDialog');"
				//						);
				////					$exec->addJsCode("$('#adminFilterUser input').val('').focus(); setTimeout(function(){ $('#confirmationDialog').parent('.ui-dialog').fadeOut(1000).one('mouseover', function(){ $(this).stop().css('opacity','1'); }); }, 600);");
				//					break;
				//				} else throw $ace;
				//			}
				break;
			case "setFilterUser2" :
				//if a second parameters is set, we switch to the new working module
				if($exec->getCrtParameters(1)!=null){
					$this->getAdminContext($p)->setWorkingModule(ServiceProvider :: getModuleAdminService()->getModule($p, $exec->getCrtParameters(1)));
				}
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				try {
					$this->getAdminContext($p)->setUser2FilterFromPost();
					$exec->addRequests("adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/" . $this->getAdminContext($p)->getSubScreen());
				} catch (StringTokenizerException $ace) {
					$transS = ServiceProvider :: getTranslationService();
					echo $transS->t($p, "parseErrorInTextSearch");
					echo "\n(" . $ace->getMessage() . ")";
				}
				break;
			case "setFilterGroup" :
				//if a second parameters is set, we switch to the new working module
				if($exec->getCrtParameters(1)!=null){
					$this->getAdminContext($p)->setWorkingModule(ServiceProvider :: getModuleAdminService()->getModule($p, $exec->getCrtParameters(1)));
				}
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				try {
					$this->getAdminContext($p)->setGroupFilterFromPost();
					$exec->addRequests("adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/" . $this->getAdminContext($p)->getSubScreen());
				} catch (StringTokenizerException $ace) {
					$transS = ServiceProvider :: getTranslationService();
					echo $transS->t($p, "parseErrorInTextSearch");
					echo "\n(" . $ace->getMessage() . ")";
				}
				break;
			case "setFilterForGroupUser" :
				//if the first parameter is set, we switch to the new working module
				if($exec->getCrtParameters(0)!=null){
					$tmpArray = explode('<br>',$exec->getCrtParameters(0));
					$this->getAdminContext($p)->setWorkingModule(ServiceProvider :: getModuleAdminService()->getModule($p, $tmpArray[0]));
				}
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				try {
					$this->getAdminContext($p)->setUserFilterFromPost();
					$this->getAdminContext($p)->setGroupFilterFromPost();
					$this->getAdminContext($p)->setSubScreen("adminGroupUser");
					$exec->addRequests("adminSearchBar/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/adminSearchBar");
					$exec->addRequests("adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/" . $this->getAdminContext($p)->getSubScreen());
				} catch (StringTokenizerException $ace) {
					$transS = ServiceProvider :: getTranslationService();
					echo $transS->t($p, "parseErrorInTextSearch");
					echo "\n(" . $ace->getMessage() . ")";
				}
				break;
			case "setFilterForUserRole" :
				//if a second parameters is set, we switch to the new working module
				if($exec->getCrtParameters(1)!=null){
					$this->getAdminContext($p)->setWorkingModule(ServiceProvider :: getModuleAdminService()->getModule($p, $exec->getCrtParameters(1)));
				}
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				try {
					$this->getAdminContext($p)->setUserFilterFromPost();
					$this->getAdminContext($p)->setUser2FilterFromPost();
					$this->getAdminContext($p)->setSubScreen("adminUserRole");
					$exec->addRequests("adminSearchBar/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/adminSearchBar");
					$exec->addRequests("adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/" . $this->getAdminContext($p)->getSubScreen());
				} catch (StringTokenizerException $ace) {
					$transS = ServiceProvider :: getTranslationService();
					echo $transS->t($p, "parseErrorInTextSearch");
					echo "\n(" . $ace->getMessage() . ")";
				}
				break;
			case "setFilterForUserUser" :
				//if a second parameters is set, we switch to the new working module
				if($exec->getCrtParameters(1)!=null){
					$this->getAdminContext($p)->setWorkingModule(ServiceProvider :: getModuleAdminService()->getModule($p, $exec->getCrtParameters(1)));
				}
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				try {
					$this->getAdminContext($p)->setUserFilterFromPost();
					$this->getAdminContext($p)->setUser2FilterFromPost();
					$this->getAdminContext($p)->setSubScreen("adminUserUser");
					$exec->addRequests("adminSearchBar/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/adminSearchBar");
					$exec->addRequests("adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/" . $this->getAdminContext($p)->getSubScreen());
				} catch (StringTokenizerException $ace) {
					$transS = ServiceProvider :: getTranslationService();
					echo $transS->t($p, "parseErrorInTextSearch");
					echo "\n(" . $ace->getMessage() . ")";
				}
				break;
			case "switchAdminTo" :
				//if a second parameters is set, we switch to the new working module
				if($exec->getCrtParameters(1)!=null){
					$this->getAdminContext($p)->setWorkingModule(ServiceProvider :: getModuleAdminService()->getModule($p, $exec->getCrtParameters(1)));
				}
	
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				$ac = $this->getAdminContext($p);
				$ac->setSubScreen($exec->getCrtParameters(0));
				$ac->resetGroupFilter();
				$ac->resetUserFilter();
				$ac->resetUser2Filter();
				//remove any filter content in the GUI
				$exec->addJsCode("" .
					"adminFilterGroup_reset('".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."', '".Module :: ADMIN_MODULE."');" .
					"clearTimeout(adminFilterTextOnBlurTimeout);" .
					"adminFilterUser_reset('".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."', '".Module :: ADMIN_MODULE."');" .
					"clearTimeout(adminFilterTextOnBlurTimeout);" .
					"adminFilterUser2_reset('".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."', '".Module :: ADMIN_MODULE."');" .
					"clearTimeout(adminFilterTextOnBlurTimeout);" .
					"");
				if($exec->getCrtParameters(0) == 'adminUserAdmin')
					$exec->addJsCode("
						if($('#adminGroup').css('visibility')=='visible') {
							tmpText = $('#adminGroup').children().first().children().first().contents()[0].data.split('(');
							$('#adminGroup').children().first().children().first().contents()[0].data = tmpText[0]+' (".ServiceProvider::getTranslationService()->t($p,$exec->getCrtParameters(1)).") ';
						}
					");
				$exec->addRequests("adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/" . $exec->getCrtParameters(0));
				//persist context in DB;
				$this->persistMainPrincipalSessionContext($p, $exec);
				break;
			case "switchModuleInAdminTo" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				$this->getAdminContext($p)->setWorkingModule(ServiceProvider :: getModuleAdminService()->getModule($p, $exec->getCrtParameters(0)));
				$exec->addRequests("mainDiv/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/display/all");
				break;
			case "GroupUserMatrix" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				$action = $exec->getCrtParameters(0);
				switch ($action) {
					case "setUgr" :
						if (!isset ($groupAS))
							$groupAS = ServiceProvider :: getGroupAdminService();
						if (!isset ($userAS))
							$userAS = ServiceProvider :: getUserAdminService();

						$i = 1;
						$workingModuleName = $exec->getCrtParameters($i++);
						$right = $exec->getCrtParameters($i++);
						if ($right == "none")
							$right = "";
						$groupId = $exec->getCrtParameters($i++);
						$groupId = substr($groupId, 3);
						$userId = $exec->getCrtParameters($i++);
						$userId = substr($userId, 3);
						//eput("set ".$right." between group ".$groupId." and user ".$userId);
						try {
							if ($right) {
								$groupAS->setUserRight($p, UGR :: createInstance($groupId, $userId)->setRightsFromLetter($right));
								//adds the moduleAccess if not defined
								$userAS->matchModuleAccessOnRights($p, $userId);
							} else {
								$groupAS->removeUser($p, $groupId, $userId);
								$userAS->matchModuleAccessOnRights($p, $userId);
								//remove moduleAccess if no one left
							}
						} catch (GroupAdminServiceException $gase) {
							if ($gase->getCode() == GroupAdminServiceException :: DATA_INTEGRITY_ERROR) {
								$transS = ServiceProvider :: getTranslationService();
								if ($exec->getIsUpdating()) {
									echo ExecutionServiceImpl :: answerRequestSeparator;
									echo "confirmationDialog";
									echo ExecutionServiceImpl :: answerParamSeparator;
								}
								$this->openAsMessage("confirmationDialog", 300, $transS->t($p, "wasLastGroupAdminParticipant"), $transS->t($p, "wasLastGroupAdminParticipantExplanation"), "actOnCloseDialog('confirmationDialog');");
								//reload matrix
								$exec->addRequests('adminWorkZone/' . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . '/' . $exec->getCrtModule()->getModuleUrl() . '/display/adminGroupUser');
								break;
							} else
								throw $gase;
						}

						break;
					case "doGroupActivity" :
						//take of doGroupActivity
						$params = array_slice($exec->getCrtParameters(), 1);
						//replace the rowID with id
						$params[2] = substr($params[2], 3);
						$params = implode("/", $params);
						$exec->addRequests("elementDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/" . $params);
						break;
					case "doUserActivity" :
						//take of doUserActivity
						$params = array_slice($exec->getCrtParameters(), 1);
						//replace the rowID with id
						$params[2] = substr($params[2], 3);
						$params = implode("/", $params);
						$exec->addRequests("elementDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/" . $params);
						break;
					default :
						throw new ServiceException("invalid request: " . $action . " for " . $exec->getCrtAction(), ServiceException :: INVALID_ARGUMENT);
				}
				break;
			case "UserAdminMatrix" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				$action = $exec->getCrtParameters(0);
				switch ($action) {
					case "setAdminLevel" :
						if (!isset ($userAS))
							$userAS = ServiceProvider :: getUserAdminService();
						$i = 1;
						$workingModuleName = $exec->getCrtParameters($i++);
						$userId = substr($exec->getCrtParameters($i++), 3);
						$adminLevel = substr($exec->getCrtParameters($i++), 3);
						$checked = formatBoolean($exec->getCrtParameters($i++));
						$userP = $userAS->getUser($p, $userId);

						if (substr($adminLevel, 0, 2) == "is") {
							$adminLevel = "set" . substr($adminLevel, 2);
							$userP->getUser()->getDetail()-> {
								$adminLevel }
							($checked);
						} else
							if (substr($adminLevel, 0, 3) == "can") {
								$adminLevel = "set" . $adminLevel;
								$userP->getUser()->getDetail()-> {
									$adminLevel }
								($checked);
							} else
								if (substr($adminLevel, 0, 3) == "get") {
									$moduleArray = $userP->getUser()->getDetail()-> {
										$adminLevel }
									();
									if ($checked) {
										//add the working module
										$moduleArray[$workingModuleName] = ServiceProvider :: getModuleAdminService()->getModule($p, $workingModuleName);
									} else {
										//delete the working module
										unset ($moduleArray[$workingModuleName]);
									}
									$adminLevel = "set" . substr($adminLevel, 3);
									$userP->getUser()->getDetail()-> {
										$adminLevel }
									($moduleArray);
								} else
									throw new ServiceException("invalid adminLevel: " . $adminLevel . " in " . $exec->getCrtAction() . " " . $exec->getCrtParameters(0));

						//update the moduleAccess of this user:
						$tempGroupCreator = $userP->getUser()->getDetail()->getGroupCreator();
						$newModuleAccess = $userAS->getModuleAccessFromRights($p, $userP);
						$fsl = FieldSelectorListArrayImpl :: createInstance();
						if ($newModuleAccess != $userP->getUser()->getDetail()->getModuleAccess()) {
							$fsl->addFieldSelector("moduleAccess");
							$fsl->addFieldSelector("info_resetSessionContext");
							$userP->getUser()->getDetail()->setModuleAccess($newModuleAccess);
							//since 20/08/2013 context only contains information that should not be reset
//							$user->getDetail()->setInfo_resetSessionContext(true);
						}
						if ($tempGroupCreator != $userP->getUser()->getDetail()->getGroupCreator()) {
							$fsl->addFieldSelector("groupCreator");
						}
						//persist the UserP
						$userAS->fillFieldSelectorListForUserAdminRights($fsl);
						try {
							$userAS->persistUser($p, $userP->getUser(), $fsl);
						} catch (AuthorizationServiceException $ase) {
							if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
								$this->displayLockMessage($p, $exec, "update('adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/adminUserAdmin');", "user", $userP);
								break;
							} else
								throw $ase;
						}

						break;
					case "doUserActivity" :
						//take of doUserActivity
						$params = array_slice($exec->getCrtParameters(), 1);
						//replace the rowID with id
						$params[2] = substr($params[2], 3);
						$params = implode("/", $params);
						$exec->addRequests("elementDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/" . $params);
						break;
					default :
						throw new ServiceException("invalid request: " . $action . " for " . $exec->getCrtAction(), ServiceException :: INVALID_ARGUMENT);
				}
				break;
			case "UserUserMatrix" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				$action = $exec->getCrtParameters(0);
				switch ($action) {
					case "setUserUser" :
						if (!isset ($userAS))
							$userAS = ServiceProvider :: getUserAdminService();
						$i = 1;
						$workingModuleName = $exec->getCrtParameters($i++);
						$userId1 = $exec->getCrtParameters($i++);
						$userId1 = substr($userId1, 3);
						$userId2 = $exec->getCrtParameters($i++);
						$userId2 = substr($userId2, 3);
						$checked = formatBoolean($exec->getCrtParameters($i++));

						//eput("set ".$right." between group ".$groupId." and user ".$userId);
						if ($checked)
							$userAS->setUserOwnership($p, $userId1, $userId2);
						else
							$userAS->removeUserOwnership($p, $userId1, $userId2);
						$userAS->matchModuleAccessOnRights($p, $userId1);

						break;
					case "doUserActivity" :
						//take of doUserActivity
						$params = array_slice($exec->getCrtParameters(), 1);
						//replace the rowID with id
						$params[2] = substr($params[2], 3);
						$params = implode("/", $params);
						$exec->addRequests("elementDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/" . $params);
						break;
					default :
						throw new ServiceException("invalid request: " . $action . " for " . $exec->getCrtAction(), ServiceException :: INVALID_ARGUMENT);
				}
				break;
			case "UserRoleMatrix" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				$action = $exec->getCrtParameters(0);
				switch ($action) {
					case "setUserRole" :
						if (!isset ($userAS))
							$userAS = ServiceProvider :: getUserAdminService();
						$i = 1;
						$workingModuleName = $exec->getCrtParameters($i++);
						$userId1 = $exec->getCrtParameters($i++);
						$userId1 = substr($userId1, 3);
						$userId2 = $exec->getCrtParameters($i++);
						$userId2 = substr($userId2, 3);
						$checked = formatBoolean($exec->getCrtParameters($i++));

						//eput("set ".$right." between group ".$groupId." and user ".$userId);
						if ($checked)
							$userAS->setUserRole($p, $userId1, $userId2);
						else
							$userAS->removeUserRole($p, $userId1, $userId2);

						break;
					case "doUserActivity" :
						//take of doUserActivity
						$params = array_slice($exec->getCrtParameters(), 1);
						//replace the rowID with id
						$params[2] = substr($params[2], 3);
						$params = implode("/", $params);
						$exec->addRequests("elementDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . Module :: ADMIN_MODULE . "/" . $params);
						break;
					default :
						throw new ServiceException("invalid request: " . $action . " for " . $exec->getCrtAction(), ServiceException :: INVALID_ARGUMENT);
				}
				break;
			case "groupDetail" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();

				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$groupP = $groupAS->getGroup($p, $groupId);

				$totalWidth = 850;
				$labelWidth = 200;

				$groupEditRec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupDetail"), $exec->getCrtModule());

				//$this->throwEvent()->readElement(PWithElement::createInstance($p, $element));
				$form = $this->createDetailGroupFormExecutor($groupP, $groupEditRec, "detailGroup_form", null);
				$form->setCorrectionWidth(26);

				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);
				$form->ResolveForm($p, $exec, "start");

				if($isFromGroupPanel){
					$this->openAsDialog(
						$exec->getIdAnswer(), $totalWidth,
						$transS->t($p, "groupDetail")." id:".$groupId, "");

				}
				break;
			case "groupNew" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($modAS))
					$modAS = ServiceProvider :: getModuleAdminService();

				$totalWidth = 450;
				$labelWidth = 150;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupParentId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$workingModule = $modAS->getModule($p, $workingModuleName);
				$groupP = GroupP :: createInstance(Group :: createInstance());
				$group = $groupP->getGroup();
				$groupP->getGroup()->setWigiiNamespace($p->getWigiiNamespace()); //this could be redefine in EditGroupFormExecutor if a parent group is defined
				$groupP->getGroup()->setModule($workingModule);
				if($groupParentId && $groupParentId != "0") $groupP->getGroup()->setGroupParentId($groupParentId);

				$groupEditRec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupEdit"), $exec->getCrtModule());
				//build the groupParent menu
				$parentGroupF = $groupEditRec->getFieldList()->getField("groupParent");
				$groupPTreeArrayImpl = GroupPListTreeArrayImpl :: createInstance();
				$inGroupModuleXml = '<groupParent type="Attributs">';
				//allow parentGroup only if RootGroupCreator
				if ($p->getRootGroupCreator($workingModule)) {
					$inGroupModuleXml .= '<attribute><label>--' . $transS->t($p, "rootGroup") . '--</label></attribute>';
				}

				//get all group, only admin
				$groupPTreeArrayImpl->reset();
				$groupAS->getAllGroups($p, $workingModule, $groupPTreeArrayImpl);
				$groupPTreeArrayImpl->cleanOnMarkup("x");
				$selectOptionGroupTree = SelectOptionForGroupTreeRenderer :: createInstance($p->getWigiiNamespace()->getWigiiNamespaceName(), "x");
				$inGroupModuleXml .= $groupPTreeArrayImpl->visitInDepth($selectOptionGroupTree);
				$inGroupModuleXml .= $selectOptionGroupTree->getResult();
				$inGroupModuleXml .= '</groupParent>';
				$parentGroupF->setXml(simplexml_load_string($inGroupModuleXml));

				//set url to refresh on done, depending on context
				if($isFromGroupPanel){
					$request = "groupPanel/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $workingModule->getModuleUrl() . "/display/groupPanel";
				} else {
					$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/" . $this->getAdminContext($p)->getSubScreen();
				}

				$form = $this->createNewGroupFormExecutor($groupP, $groupEditRec, "newGroup_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				if (!$p->isWigiiNamespaceCreator()) {
					$groupEditRec->getWigiiBag()->setDisabled(true, "wigiiNamespace");
					$groupEditRec->setFieldValue($groupP->getGroup()->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
				}
				$form->ResolveForm($p, $exec, $state);

				if($isFromGroupPanel){
					$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/".$group->getGroupParentId(), $exec->getCrtWigiiNamespace(), $workingModule);
				}

				break;
			case "groupNewCopy" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($modAS))
					$modAS = ServiceProvider :: getModuleAdminService();

				$totalWidth = 450;
				$labelWidth = 150;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$groupP = $groupAS->getGroup($p, $groupId);
				$group = $groupP->getGroup();
				$workingModule = $group->getModule();
				$trashBinGroup = (string)ServiceProvider :: getConfigService()->getParameter($p, $group->getModule(), "trashBinGroup");
				if($groupId == $trashBinGroup){
					$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "operationImpossible"), $transS->t($p, "trashBinGroupOperationImpossible"));
					break;
				}

				//			$group->setId(0);

				$groupEditRec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupEdit"), $exec->getCrtModule());
				//build the groupParent menu
				$parentGroupF = $groupEditRec->getFieldList()->getField("groupParent");
				$groupPTreeArrayImpl = GroupPListTreeArrayImpl :: createInstance();
				$inGroupModuleXml = '<groupParent type="Attributs">';
				//allow parentGroup only if RootGroupCreator
				if ($p->getRootGroupCreator($workingModule)) {
					$inGroupModuleXml .= '<attribute><label>--' . $transS->t($p, "rootGroup") . '--</label></attribute>';
				}

				//get all group, only admin
				$groupPTreeArrayImpl->reset();
				$groupAS->getAllGroups($p, $workingModule, $groupPTreeArrayImpl);
				$groupPTreeArrayImpl->cleanOnMarkup("x");
				$selectOptionGroupTree = SelectOptionForGroupTreeRenderer :: createInstance($p->getWigiiNamespace()->getWigiiNamespaceName(), "x");
				$inGroupModuleXml .= $groupPTreeArrayImpl->visitInDepth($selectOptionGroupTree);
				$inGroupModuleXml .= $selectOptionGroupTree->getResult();
				$inGroupModuleXml .= '</groupParent>';
				$parentGroupF->setXml(simplexml_load_string($inGroupModuleXml));

				//set url to refresh on done, depending on context
				if($isFromGroupPanel){
					$request = "groupPanel/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $workingModule->getModuleUrl() . "/display/groupPanel";
				} else {
					$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/" . $this->getAdminContext($p)->getSubScreen();
				}

				$form = $this->createNewCopyGroupFormExecutor($groupP, $groupEditRec, "newCopyGroup_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				if (!$p->isWigiiNamespaceCreator()) {
					$groupEditRec->getWigiiBag()->setDisabled(true, "wigiiNamespace");
					$groupEditRec->setFieldValue($group->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
				}
				$form->ResolveForm($p, $exec, $state);

				if($isFromGroupPanel){
					$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/".$group->getGroupParentId(), $exec->getCrtWigiiNamespace(), $workingModule);
				}

				break;
			case "groupEdit" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();

				$totalWidth = 450;
				$labelWidth = 150;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$groupP = $groupAS->getGroup($p, $groupId);
				$group = $groupP->getGroup();
				$workingModule = $group->getModule();

				$trashBinGroup = (string)ServiceProvider :: getConfigService()->getParameter($p, $group->getModule(), "trashBinGroup");
				if($groupId == $trashBinGroup){
					$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "operationImpossible"), $transS->t($p, "trashBinGroupOperationImpossible"));
					break;
				}
				if (false === $this->lockEntity($p, $exec, "actOnCloseDialog('elementDialog');", "group", $group))
					break;

				$groupEditRec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupEdit"), $exec->getCrtModule());
				//build the groupParent menu
				$parentGroupF = $groupEditRec->getFieldList()->getField("groupParent");
				$groupPTreeArrayImpl = GroupPListTreeArrayImpl :: createInstance();
				$inGroupModuleXml = '<groupParent type="Attributs">';
				//allow parentGroup only if RootGroupCreator
				if ($p->getRootGroupCreator($workingModule)) {
					$inGroupModuleXml .= '<attribute><label>--' . $transS->t($p, "rootGroup") . '--</label></attribute>';
				}

				//get all group, only admin
				$groupPTreeArrayImpl->reset();
				$groupAS->getAllGroups($p, $workingModule, $groupPTreeArrayImpl);
				$groupPTreeArrayImpl->cleanOnMarkup("x");
				//			eput($groupPTreeArrayImpl);
				$selectOptionGroupTree = SelectOptionForGroupTreeRenderer :: createInstance($p->getWigiiNamespace()->getWigiiNamespaceName(), "x", $group->getId());
				$inGroupModuleXml .= $groupPTreeArrayImpl->visitInDepth($selectOptionGroupTree);
				$inGroupModuleXml .= $selectOptionGroupTree->getResult();
				$inGroupModuleXml .= '</groupParent>';
				$parentGroupF->setXml(simplexml_load_string($inGroupModuleXml));


				if($group->getGroupParentId()){
					$groupParentP = $groupAS->getGroup($p, $group->getGroupParentId(), $groupAS->getFieldSelectorListForGroupWithoutDetail());
					if($groupParentP && (!$groupParentP->getRights() || !$groupParentP->getRights()->canModify())){
						$groupEditRec->getWigiiBag()->setDisabled(true, "groupParent");
						$groupEditRec->setFieldValue($group->getGroupParentId(), "groupParent");;
					}
				}

				//set url to refresh on done, depending on context
				if($isFromGroupPanel){
					//invalid cache of old parent
					$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/".$group->getGroupParentId(), $exec->getCrtWigiiNamespace(), $workingModule);
					$request = "groupPanel/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $workingModule->getModuleUrl() . "/display/groupPanel";
				} else {
					$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/" . $this->getAdminContext($p)->getSubScreen();
				}

				$form = $this->createEditGroupFormExecutor($groupP, $groupEditRec, "editGroup_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				if (!$p->isWigiiNamespaceCreator()) {
					$groupEditRec->getWigiiBag()->setDisabled(true, "wigiiNamespace");
					$groupEditRec->setFieldValue($group->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
				}
				$form->ResolveForm($p, $exec, $state);
				//invalid cache of new parent
				if($isFromGroupPanel){
					$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/".$group->getGroupParentId(), $exec->getCrtWigiiNamespace(), $workingModule);
				}
				break;
			case "groupDelete" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($ac))
					$ac = $this->getAdminContext($p);

				$totalWidth = 450;
				$labelWidth = 150;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$groupP = $groupAS->getGroup($p, $groupId, null, true);
				// checks that group exists and that principal has access to group
				if(!isset($groupP) || is_null($groupP->getGroup()->getDetail())) {
					$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "noGroupFound"), $transS->t($p, "noGroupFoundExplanation"));
					break;
				}

				$group = $groupP->getGroup();
				$workingModule = $group->getModule();

				$trashBinGroup = (string)ServiceProvider :: getConfigService()->getParameter($p, $group->getModule(), "trashBinGroup");
				if($groupId == $trashBinGroup){
					$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "operationImpossible"), $transS->t($p, "trashBinGroupOperationImpossible"));
					break;
				}

				// prevents deletion of groups containing blocked elements
				if($group->getDetail()->getNumberOfBlockedElements() > 0) {
					$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "operationImpossible"), $group->getDetail()->getNumberOfBlockedElements().' '.$transS->t($p, "blockedElementsOperationImpossible"));
					break;
				}

				if (false === $this->lockEntity($p, $exec, "actOnCloseDialog('elementDialog');", "group", $group))
					break;

				//set url to refresh on done, depending on context
				if($isFromGroupPanel){
					//invalid cache of old parent
					$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/".$group->getGroupParentId(), $exec->getCrtWigiiNamespace(), $workingModule);
					$request = "groupPanel/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $workingModule->getModuleUrl() . "/display/groupPanel";
				} else {
					$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/" . $this->getAdminContext($p)->getSubScreen();
				}

				$groupEditRec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupDelete"), $exec->getCrtModule());
				$form = $this->createDeleteGroupFormExecutor($groupP, $groupEditRec, "deleteGroup_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null) $state = addslashes($_POST["action"]);
				$form->ResolveForm($p, $exec, $state);

				break;
			case "groupEmpty" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
					if (!$exec->getCrtModule()->isAdminModule())
						throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

					if (!isset ($transS))
						$transS = ServiceProvider :: getTranslationService();
					if (!isset ($groupAS))
						$groupAS = ServiceProvider :: getGroupAdminService();
					if (!isset ($ac))
						$ac = $this->getAdminContext($p);

					$totalWidth = 450;
					$labelWidth = 150;
					$action = $exec->getCrtRequest();

					$i = 0;
					$workingModuleName = $exec->getCrtParameters($i++);
					$groupId = $exec->getCrtParameters($i++);
					$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
					$groupP = $groupAS->getGroup($p, $groupId, null, true);
					// checks that group exists and that principal has access to group
					if(!isset($groupP) || is_null($groupP->getGroup()->getDetail())) {
						$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "noGroupFound"), $transS->t($p, "noGroupFoundExplanation"));
						break;
					}

					$group = $groupP->getGroup();
					$workingModule = $group->getModule();

					// prevents deletion of groups containing blocked elements
					if($group->getDetail()->getNumberOfBlockedElements() > 0) {
						$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "operationImpossible"), $group->getDetail()->getNumberOfBlockedElements().' '.$transS->t($p, "blockedElementsOperationImpossible"));
						break;
					}

					if (false === $this->lockEntity($p, $exec, "actOnCloseDialog('elementDialog');", "group", $group))
						break;

					//set url to refresh on done, depending on context
					if($isFromGroupPanel){
						//invalid cache of group
						$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/".$group->getId(), $exec->getCrtWigiiNamespace(), $workingModule);
						$request = "groupPanel/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $workingModule->getModuleUrl() . "/display/groupPanel";
					} else {
						$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/" . $this->getAdminContext($p)->getSubScreen();
					}

					$groupEditRec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupEmpty"), $exec->getCrtModule());
					$form = $this->createEmptyGroupFormExecutor($groupP, $groupEditRec, "emptyGroup_form", $action, $request);
					$form->setCorrectionWidth(19);
					$form->setLabelWidth($labelWidth);
					$form->setTotalWidth($totalWidth);

					$state = "start";
					if ($_POST["action"] != null) $state = addslashes($_POST["action"]);
					$form->ResolveForm($p, $exec, $state);

					break;
			case "groupConfigEdit" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();

				$totalWidth = 950;
				$labelWidth = 250;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$groupP = $groupAS->getGroup($p, $groupId);
				$group = $groupP->getGroup();

				if (false === $this->lockEntity($p, $exec, null, "group", $group))
					break;

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupConfigEdit"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				$request = "elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId;

				$form = $this->createConfigEditGroupFormExecutor($groupP, $rec, "configEditGroup_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				$form->ResolveForm($p, $exec, $state);

				break;
			case "groupEmailNotification" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();

				$totalWidth = 550;
				$labelWidth = 200;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$groupP = $groupAS->getGroup($p, $groupId);
				$group = $groupP->getGroup();

				if (false === $this->lockEntity($p, $exec, null, "group", $group))
					break;

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupEmailNotification"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				if($isFromGroupPanel){
					$request = "elementDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId."/groupPanel";
				} else {
					$request = "elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId;
				}

				$form = $this->createEmailNotificationGroupFormExecutor($groupP, $rec, "groupEmailNotification_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				//fill the original datas: important to do it here to make any files content available after checking if no change
				$rec->setFieldValue($group->getDetail()->getEmailNotification(), "emails");

				$form->ResolveForm($p, $exec, $state);

				break;
			case "groupPortal" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();

				$totalWidth = 450;
				$labelWidth = 150;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$groupP = $groupAS->getGroup($p, $groupId);
				$group = $groupP->getGroup();

				$trashBinGroup = (string)ServiceProvider :: getConfigService()->getParameter($p, $group->getModule(), "trashBinGroup");
				if($groupId == $trashBinGroup){
					$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "operationImpossible"), $transS->t($p, "trashBinGroupOperationImpossible"));
					break;
				}

				if (false === $this->lockEntity($p, $exec, null, "group", $group))
					break;

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupPortal"), $exec->getCrtModule());
				//set url to refresh on done, depending on context
				if($isFromGroupPanel){
					$request = "moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $workingModuleName . "/groupSelectorPanel/selectGroup/".$group->getId();
				} else {
					$request = "elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId;
				}

				$form = $this->createPortalGroupFormExecutor($groupP, $rec, "portalGroup_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				//fill the original datas: important to do it here to make any files content available after checking if no change
				$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getPortal(), $rec->getActivity());

				$form->ResolveForm($p, $exec, $state);
				break;

			case "groupHtmlContent" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();

				$totalWidth = 950;
				$labelWidth = 250;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$groupP = $groupAS->getGroup($p, $groupId);
				$group = $groupP->getGroup();

				$trashBinGroup = (string)ServiceProvider :: getConfigService()->getParameter($p, $group->getModule(), "trashBinGroup");
				if($groupId == $trashBinGroup){
					$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "operationImpossible"), $transS->t($p, "trashBinGroupOperationImpossible"));
					break;
				}

				if (false === $this->lockEntity($p, $exec, null, "group", $group))
					break;

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupHtmlContent"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				if($isFromGroupPanel){
					$request = "moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $workingModuleName . "/groupSelectorPanel/selectGroup/".$group->getId();
				} else {
					$request = "elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId;
				}

				$form = $this->createHtmlContentGroupFormExecutor($groupP, $rec, "htmlContentGroup_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				//fill the original datas: important to do it here to make any files content available after checking if no change
				$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getHtmlContent(), $rec->getActivity());

				$form->ResolveForm($p, $exec, $state);

				break;
			case "groupXmlPublish" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();

				$totalWidth = 450;
				$labelWidth = 150;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$groupP = $groupAS->getGroup($p, $groupId);
				$group = $groupP->getGroup();

				if (false === $this->lockEntity($p, $exec, null, "group", $group))
					break;

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupXmlPublish"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				if($isFromGroupPanel){
					$request = "elementDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId."/groupPanel";
				} else {
					$request = "elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId;
				}

				$form = $this->createXmlPublishGroupFormExecutor($groupP, $rec, "groupXmlPublish_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				//fill the original datas: important to do it here to make any files content available after checking if no change
				$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getXmlPublish(), $rec->getActivity());

				$form->ResolveForm($p, $exec, $state);

				break;
			case "groupSubscription" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();

				$totalWidth = 650;
				$labelWidth = 250;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$groupId = $exec->getCrtParameters($i++);
				$isFromGroupPanel = $exec->getCrtParameters($i++)=="groupPanel";
				$groupP = $groupAS->getGroup($p, $groupId);
				$group = $groupP->getGroup();

				$trashBinGroup = (string)ServiceProvider :: getConfigService()->getParameter($p, $group->getModule(), "trashBinGroup");
				if($groupId == $trashBinGroup){
					$this->openAsMessage($exec->getIdAnswer(), 250, $transS->t($p, "operationImpossible"), $transS->t($p, "trashBinGroupOperationImpossible"));
					break;
				}

				if (false === $this->lockEntity($p, $exec, null, "group", $group))
					break;

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("groupSubscription"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				if($isFromGroupPanel){
					$request = "elementDialog/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId."/groupPanel";
				} else {
					$request = "elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/groupDetail/" . $workingModuleName . "/" . $groupId;
				}

				$form = $this->createSubscriptionGroupFormExecutor($groupP, $rec, "groupSubscription_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				//fill the original datas: important to do it here to make any files content available after checking if no change
				$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getSubscription(), $rec->getActivity());

				$form->ResolveForm($p, $exec, $state);

				break;
			case "userDetail" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($userAS))
					$userAS = ServiceProvider :: getUserAdminService();
				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$userId = $exec->getCrtParameters($i++);
				$userP = $userAS->getUser($p, $userId);

				$totalWidth = 850;
				$labelWidth = 250;

				$userEditRec = $this->createActivityRecordForForm($p, Activity :: createInstance("userDetail"), $exec->getCrtModule());				
				//$this->throwEvent()->readElement(PWithElement::createInstance($p, $element));
				$form = $this->createDetailUserFormExecutor(ServiceProvider :: getModuleAdminService()->getModule($p, $workingModuleName), $userP, $userEditRec, "detailUser_form", null);
				$form->setCorrectionWidth(26);

				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);
				$form->ResolveForm($p, $exec, "start");

				break;
			case "roleNew" :
			case "userNew" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($userAS))
					$userAS = ServiceProvider :: getUserAdminService();

				$totalWidth = 450;
				$labelWidth = 250;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);

				$userP = UserP :: createInstance(User :: createInstance());
				$userP->getUser()->setWigiiNamespace($exec->getCrtWigiiNamespace());
				$user = $userP->getUser();
				if($exec->getCrtAction() == "roleNew"){
					$user->setRole(true);
				}

				$userEditRec = $this->createActivityRecordForForm($p, Activity :: createInstance(!$user->isRole() ? "userEdit" :	"roleEdit"), $exec->getCrtModule());
				$this->createAccessAndFolderCreatorForm($user, $p, $userEditRec);
				//set url to refresh on done, depending on context
				$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/" . $this->getAdminContext($p)->getSubScreen();

				$form = $this->createNewUserFormExecutor($userP, $userEditRec, "newUser_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				/*
				if (!$p->isWigiiNamespaceCreator()) {
					$userEditRec->setFieldValue($userP->getUser()->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
					$userEditRec->getWigiiBag()->setDisabled(true, "wigiiNamespace");
					$userEditRec->setFieldValue($userP->getUser()->getDetail()->getModuleAccess(), "moduleAccess");
					$userEditRec->getWigiiBag()->setDisabled(true, "moduleAccess");
					$userEditRec->getWigiiBag()->setHidden(true, "moduleAccess");
				}
				*/
				$form->ResolveForm($p, $exec, $state);

				break;
			case "userEdit" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($userAS))
					$userAS = ServiceProvider :: getUserAdminService();

				$totalWidth = 450;
				$labelWidth = 250;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$userId = $exec->getCrtParameters($i++);
				$userP = $userAS->getUser($p, $userId);
				$user = $userP->getUser();

				if (false === $this->lockEntity($p, $exec, "actOnCloseDialog('elementDialog');", "user", $user))
					break;
				//eput($user->displayDebug());

				$userEditRec = $this->createActivityRecordForForm($p, Activity :: createInstance((!$user->isRole() ? "userEdit" : "roleEdit")), $exec->getCrtModule());					
				$this->createAccessAndFolderCreatorForm($user, $p, $userEditRec);

				//set url to refresh on done, depending on context
				$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/" . $this->getAdminContext($p)->getSubScreen();

				$form = $this->createEditUserFormExecutor($userP, $userEditRec, "editUser_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				/*
				if (!$p->isWigiiNamespaceCreator()) {
					$userEditRec->setFieldValue($user->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
					$userEditRec->getWigiiBag()->setDisabled(true, "wigiiNamespace");
					$userEditRec->setFieldValue($userP->getUser()->getDetail()->getModuleAccess(), "moduleAccess");
					$userEditRec->getWigiiBag()->setDisabled(true, "moduleAccess");
					$userEditRec->getWigiiBag()->setHidden(true, "moduleAccess");
				}
				*/
				$form->ResolveForm($p, $exec, $state);

				break;
			case "userDelete" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($userAS))
					$userAS = ServiceProvider :: getUserAdminService();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$userId = $exec->getCrtParameters($i++);
				$userP = $userAS->getUser($p, $userId);

				if (false === $this->lockEntity($p, $exec, null, "user", $userP))
					break;

				$totalWidth = 450;
				$labelWidth = 250;
				$action = $exec->getCrtRequest();

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("userDelete"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				$request = "elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/userDetail/" . $workingModuleName . "/" . $userId;

				$form = $this->createDeleteUserFormExecutor($userP, $rec, "deleteUser_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				$form->ResolveForm($p, $exec, $state);
				break;
			case "roleUserAllocation" :
			case "userRoleAllocation" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule()) throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
				if (!isset ($transS)) $transS = ServiceProvider :: getTranslationService();
				if (!isset ($userAS)) $userAS = ServiceProvider :: getUserAdminService();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$userId = $exec->getCrtParameters($i++);

				if($exec->getCrtAction()=="userRoleAllocation") $roleMapping = true;
				else $roleMapping = false;

				$state = "start";
				if ($_POST["action"] != null) $state = ($_POST["action"]);

				$roleList = UserListArrayImpl::createInstance();
				if($roleMapping){
					$lf = $userAS->getListFilterForRoleList();
					$lf->setFieldSelectorList($userAS->getFieldSelectorListForUserWithoutDetail());
					$userAS->getAllUserRoles($p, $userId, $roleList, $lf);
					$originalIds = $roleList->getIds();
				} else {
					$lf = $userAS->getListFilterForUserList();
					$lf->setFieldSelectorList($userAS->getFieldSelectorListForUserWithoutDetail());
					$userAS->getAllRoleUsers($p, $userId, $roleList, $lf);
					$originalIds = $roleList->getIds();
				}

				if($originalIds) $originalIds = array_combine($originalIds, $originalIds);

				if($state == "start"){
					//fill in the list of users
					$totalWidth = 450;

					$userPRenderer = UserRoleAllocationRenderer::createInstance($p, "adminRole_list");
					$userPRenderer->setOrgRoles($originalIds);
					$userPRenderer->prepareRendering($p, $exec, $this);
					if($roleMapping){
						$lf = $userAS->getListFilterForRoleList();
						$userAS->getAllRoles($p, $userPRenderer, $lf); //, $userAS->getListFilterForUserList());
					} else {
						$lf = $userAS->getListFilterForUserList();
//						$userAS->getAllUsers($p, $userPRenderer, $lf); //, $userAS->getListFilterForUserList());
						//in order to access to the description of all users we need to provide the RootPrincipal
						$userAS->getAllUsersFromSystem($this->getRootPrincipal(), $userPRenderer, $lf); //, $userAS->getListFilterForUserList());
					}
					$userPRenderer->endRendering($p, $exec, $this);

					$this->openAsDialogForm(
						$exec->getIdAnswer(), $totalWidth,
						'
setVis("busyDiv", true);
url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ "'.$exec->getCrtRequest().'";
var myAjax = new jQuery.ajax({
		type: "POST",
		url: encodeURI(url),
		success : parseUpdateResult,
		cache:false,
		data: {
			original: function(){
				checkedFolders = new Array;
				$("#adminRole_list .origS").each(function(){
					checkedFolders[checkedFolders.length] = $(this).attr("id").replace("row", "");
				});
				return checkedFolders.join(",");
			},
			actual: function(){
				checkedFolders = new Array;
				$("#adminRole_list .S").each(function(){
					checkedFolders[checkedFolders.length] = $(this).attr("id").replace("row", "");
				});
				return checkedFolders.join(",");
			},
			action: "check"
		},
		error: errorOnUpdate
	});
onUpdateErrorCounter = 0;
', $transS->t($p, $exec->getCrtAction()),
						$transS->t($p, "ok"), $transS->t($p, "cancel"), null);
					$exec->addJsCode("" .
						"if($('#adminRole_list .S:first').length > 0){" .
						"$('#adminRole_list').scrollTop($('#adminRole_list .S:first').get()[0].offsetTop-$('#adminRole_list').get()[0].offsetTop - 50);" .
						"}");

				} else {
					//get Original roles, keep intersect with original
					$fe = $this->createFormExecutor();
					$fe->preventInjectionForm($p, $exec);
					$requested = explode(",", $_POST["actual"]);
					$original = explode(",", $_POST["original"]);
					if($requested) $requested = array_combine($requested, $requested);
					else $requested = array();
					if($original) $original = array_combine($original, $original);
					else $original = array();
					$merged = array_merge($requested, $original);


					$original = array_intersect_key($original, $originalIds);
					//do the job
					foreach ($merged as $roleId) {
						if ($roleId == null) continue;
						if ($requested[$roleId] && !$original[$roleId]) {
							//add sharing
							if($roleMapping){
								$userAS->setUserRole($p, $userId, $roleId);
							} else {
								$userAS->setUserRole($p, $roleId, $userId);
							}
						} else {
							if (!$requested[$roleId] && $original[$roleId]) {
								//remove sharing
								if($roleMapping){
									$userAS->removeUserRole($p, $userId, $roleId);
								} else {
									$userAS->removeUserRole($p, $roleId, $userId);
								}
							} else {
								//no change
							}
						}
					}

					$this->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

					//reload the user
					$exec->addRequests("elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/userDetail/" . $workingModuleName . "/" . $userId);
				}

				break;
			case "userRights" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule()) throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
				if (!isset ($transS)) $transS = ServiceProvider :: getTranslationService();
				if (!isset ($userAS)) $userAS = ServiceProvider :: getUserAdminService();
				if (!isset ($groupAS)) $groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($configS)) $configS = $this->getConfigurationContext();
				if (!isset ($moduleAS)) $moduleAS = ServiceProvider :: getModuleAdminService();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$workingModule = $moduleAS->getModule($p, $workingModuleName);
				$userId = $exec->getCrtParameters($i++);

				$state = "start";
				if ($_POST["action"] != null) $state = ($_POST["action"]);

				//get allGroups for P
				$groupPTreeArrayImpl = GroupPListTreeArrayImpl::createInstance(); //GroupPTreeWebImpl::createInstance(); //
				$groupAS->getAllGroups($p, $workingModule, $groupPTreeArrayImpl, $groupAS->getListFilterForSelectGroupWithoutDetail());
				$groupPTreeArrayImpl->cleanOnMarkup("r");
				$groupIds = $groupPTreeArrayImpl->getIds();
				if($groupIds!=null) $groupIds = array_combine($groupIds, $groupIds);
				//lookup for userGroupsRights
				$groupRPList = GroupRPListArrayImpl::createInstance();
				$userAS->getAllGroups($p, $userId, $workingModule, $groupIds, $groupRPList, $groupAS->getListFilterForSelectGroupWithoutDetail());

				$original = array();
				if(!$groupRPList->isEmpty()){
					foreach($groupRPList->getListIterator() as $groupRP){
						$original[$groupRP->getId()] = $groupRP->getGroupR()->getRights()->getLetter();
					}
				}
//				if($originalIds) $originalIds = array_combine($originalIds, $originalIds);

				if($state == "start"){
					$totalWidth = 550;

					//display current group Tree
					$groupPTree = GroupPTreeAccessRightsGroupImpl::createInstance($p, $exec, 0, true); //(int)$configS->getParameter($p, $workingModule, "nbOfLevelToExpandOnInit"), true);
					$groupPTree->setOriginalAccessRights($original);
					$groupPTree->start($p, $exec);
					foreach($groupPTreeArrayImpl->getListIterator() as $groupP){
						//eput($groupP->getDbEntity()->getGroupName()." parent: ".put($groupPTreeArrayImpl->getParentGroup($groupP)));
						$groupPTree->addGroupP($groupP, $groupPTreeArrayImpl->getParentGroup($groupP));
					}
					$groupPTree->end($p, $exec);

					$this->openAsDialogForm(
						$exec->getIdAnswer(), $totalWidth,
						'
setVis("busyDiv", true);
url = SITE_ROOT +"Update/"+crtContextId+EXEC_requestSeparator+ "'.$exec->getCrtRequest().'";
actual = new Object;
$("#adminRoleAccessRightGroup_list .selected").each(function(){
	if($(this).hasClass("x")) right = "x";
	else if($(this).hasClass("s")) right = "s";
	else if($(this).hasClass("r")) right = "r";
	else if($(this).hasClass("w")) right = "w";
	else right = "";
	actual[$(this).parent().parent().attr("id").split("_")[1]] = right;
});
var myAjax = new jQuery.ajax({
		type: "POST",
		url: encodeURI(url),
		success : parseUpdateResult,
		cache:false,
		data: {
			actual: actual,
			action: "check"
		},
		error: errorOnUpdate
	});
onUpdateErrorCounter = 0;
', $transS->t($p, "userRightsFor")." ".$transS->t($p, $workingModuleName),
						$transS->t($p, "ok"), $transS->t($p, "cancel"), null);
					$exec->addJsCode("");

				} else {
					//get Original roles, keep intersect with original
					$fe = $this->createFormExecutor();
					$fe->preventInjectionForm($p, $exec);
					$requested = $_POST["actual"];

					$merged = array_merge(($requested ? array_keys($requested) : array()), ($original ? array_keys($original) : array()));

					$merged = array_combine($merged, $merged);

					//do the job
					$lastAdminRightsDetected = array();
					foreach ($merged as $groupId) {
						if ($groupId == null) continue;
						if ($requested[$groupId]!=$original[$groupId]) {
							//set/unset right on change
//							eput("set ".$groupId." = ".$requested[$groupId]);
							$right = $requested[$groupId];
							try {
								if ($right) {
									$groupAS->setUserRight($p, UGR :: createInstance($groupId, $userId)->setRightsFromLetter($right));
								} else {
									$groupAS->removeUser($p, $groupId, $userId);
								}
								//adds the moduleAccess if not defined
								$userAS->matchModuleAccessOnRights($p, $userId);
							} catch (GroupAdminServiceException $gase) {
								if ($gase->getCode() == GroupAdminServiceException :: DATA_INTEGRITY_ERROR) {
									$lastAdminRightsDetected[] = $groupPTreeArrayImpl->getGroupP($groupId)->getDbEntity()->getGroupname();
								} else
									throw $gase;
							}
						} else {
							//no change
						}
					}
					if($lastAdminRightsDetected){
						$transS = ServiceProvider :: getTranslationService();
						if ($exec->getIsUpdating()) {
							echo ExecutionServiceImpl :: answerRequestSeparator;
							echo "confirmationDialog";
							echo ExecutionServiceImpl :: answerParamSeparator;
						}
						$lastAdminRightsDetected = $transS->t($p, "wasSomLastGroupAdminParticipantExplanation")."<br /><br />".implode("<br />", $lastAdminRightsDetected);
						$this->openAsMessage("confirmationDialog", 300, $transS->t($p, "operationDoneSuccessfullyButWasSomLastGroupAdminParticipant"), $lastAdminRightsDetected, "actOnCloseDialog('confirmationDialog');");
						$_POST["action"] = null;
						$exec->addRequests($exec->getCrtRequest());
						break;
					}

					$this->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

					//reload the user
					$exec->addRequests("elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/userDetail/" . $workingModuleName . "/" . $userId);
				}

				break;
			case "userConfigEdit" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($userAS))
					$userAS = ServiceProvider :: getUserAdminService();

				$totalWidth = 950;
				$labelWidth = 250;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$userId = $exec->getCrtParameters($i++);
				$userP = $userAS->getUser($p, $userId);
				$user = $userP->getUser();

				if (false === $this->lockEntity($p, $exec, null, "user", $user))
					break;

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("userConfigEdit"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				$request = "elementDetail/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/userDetail/" . $workingModuleName . "/" . $userId;

				$form = $this->createConfigEditUserFormExecutor(ServiceProvider :: getModuleAdminService()->getModule($p, $workingModuleName), $userP, $rec, "configEditUser_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				$form->ResolveForm($p, $exec, $state);

				break;
			case "wakeup" :
				$i = 0;
				$nbJob = $exec->getCrtParameters(1);
				if ($nbJob == null)
					$nbJob = 1;

				if (!isset ($emailS))
					$emailS = TechnicalServiceProvider :: getEmailService();

				try {
					$p = $this->getRootPrincipal();
					$remainingJobs = 0;

					$emailS->setMaxEmailsToSendPerExecution($nbJob);
					$emailS->setMaxRecipientsPerEmail(100);
					$remainingJobs += $emailS->wakeup($p);

					echo "cronJobsFinishWorkingFunction();"; //this function disable the semaphore not letting do the updates because there is one currently in working. this idea is to prevent the launch of automatic simultaneous wakeup
					echo "$('#cronJobsCursor').text(' - ');";
					echo "$('#cronJobsNb').text($remainingJobs);";
					if ($remainingJobs > 0) {
						//relaunch directly the cronJobs
						echo "if(!globalCronJobsStopper){ cronJobsWorkingFunction(); } else { globalCronJobsStopper = false; }";
					} else {
						//do nothing
					}
				} catch (Exception $e) {
					throw $e;
					$this->displayNotFound();
					break;
				}
				break;
			case "validateEmailFromCode" :
				$i = 0;
				$proofKey = $exec->getCrtParameters($i++);
				$lang = $exec->getCrtParameters($i++);
				if (!isset ($configS)) $configS = $this->getConfigurationContext();
				if (!isset ($transS)) $transS = ServiceProvider :: getTranslationService();
				if (!isset ($elS)) $elS = ServiceProvider :: getElementService();
				if ($lang != null) { $transS->setLanguage($lang); }
				try {
					$p = $this->getPublicPrincipalForWigiiNamespace($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule());
					$elS->validateEmailFromCode($p, $proofKey); //throw an exception if not found
					echo '<div style="';
					echo ' display:table; ';
					echo ' margin-right:auto; margin-left:auto; padding:20px; background-color:#fff; ';
					echo ' " >';
					echo '<h3 style="margin-bottom:10px; margin-top:10px;" >' . $transS->t($p, "validateEmailFromCodeDone") . "</h3>";
					echo '<div class="clear"></div>';
					echo '<p style="margin:5px 0 10px 0;">' . $transS->t($p, "validateEmailFromCodeThankYouText") . "</p>";

					echo '</div>';
				} catch (Exception $e) {
					$this->displayNotFound();
					break;
				}
				break;
			case "unsubscribeEmailFromCode" :
				$i = 0;
				$proofKey = $exec->getCrtParameters($i++);
				$lang = $exec->getCrtParameters($i++);
				if (!isset ($configS)) $configS = $this->getConfigurationContext();
				if (!isset ($transS)) $transS = ServiceProvider :: getTranslationService();
				if (!isset ($elS)) $elS = ServiceProvider :: getElementService();
				if ($lang != null) { $transS->setLanguage($lang); }
				try {
					$p = $this->getPublicPrincipalForWigiiNamespace($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule());
					$elS->unsubscribeEmailFromCode($p, $proofKey);
					echo '<div style="';
					echo ' display:table; ';
					echo ' margin-right:auto; margin-left:auto; padding:20px; background-color:#fff; ';
					echo ' " >';
					echo '<h3 style="margin-bottom:10px; margin-top:10px;" >' . $transS->t($p, "unsubscribeEmailFromCodeDone") . "</h3>";
					echo '</div>';
				} catch (Exception $e) {
					$this->displayNotFound();
					break;
				}
				break;
			case "getExternalAccessMenuContent":
				$fe = $this->createFormExecutor();
				$fe->preventInjectionForm($p, $exec);
				$proofKey = $_POST["proofKey"];
				$externalCode = $_POST["externalCode"];
				$proofStatus = $_POST["proofStatus"];

				$trm = $this->createTrm();
				echo $trm->t("externalAccessMenusendEmailTitle");

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->t("externalAccessMenuEndDate");

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->t("ok");

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->t("cancel");

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->t("subject");

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->t("helloBaseText");

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->getBaseEmailSubject();

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->getValidationEmailHtml(null, $proofKey, false);

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->getValidationEmailSubject();

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->getExternalAccessViewEmailHtml(null, $externalCode, $proofKey, $proofStatus, false);

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->getExternalAccessViewEmailSubject();

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->getExternalAccessEditEmailHtml(null, $externalCode, $proofKey, $proofStatus, false);

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->getExternalAccessEditEmailSubject();

				echo ExecutionServiceImpl :: answerParamSeparator;
				echo $trm->t("areYouSureToStopExternalAccess");

				break;
			case "externalAccessRequest" :
				$i = 0;
				$groupId = $exec->getCrtParameters($i++);
				$lang = $exec->getCrtParameters($i++);

				if (!isset ($configS))
					$configS = $this->getConfigurationContext();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();

				if ($lang != null) {
					$transS->setLanguage($lang);
				}

				try {
					$p = $this->getPublicPrincipalForWigiiNamespace($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule());
					$record = $this->createActivityRecordForForm($p, Activity :: createInstance("groupSubscription"), $exec->getCrtModule());
					$groupP = $groupAS->getGroup($p, $groupId);
					if ($groupP == null || $groupP->getRights() == null || !$groupP->getRights()->canWriteElement()) {
						throw new ServiceException('newSubscription on unreachable group: ' . $groupId, ServiceException :: FORBIDDEN);
					}
					$this->getConfigurationContext()->setGroupPList($p, $exec->getCrtModule(), GroupListAdvancedImpl :: createInstance()->addGroupP($groupP), ($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"));
					//setup the configuration context on the groupId to get configuration
					$record->getWigiiBag()->importFromSerializedArray($groupP->getGroup()->getDetail()->getSubscription(), $record->getActivity());
					//verify GroupSubscription is enabled for this group
					if ($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_enableSubscription") == "0") {
						throw new ServiceException('Group_enableSubscription is 0', ServiceException :: FORBIDDEN);
					}
					if (!$record->getFieldValue("enableGroupSubscription")) {
						throw new ServiceException('newSubscription needs enableGroupSubscription in group detail: ' . $groupP->getId(), ServiceException :: FORBIDDEN);
					}
					//deprecated on 26.03.2012, this is unclear. Instead we will use the customize email message with default value containing the merge field
					if (!$record->getFieldValue("enableExternalAccessRequest")) {
						throw new ServiceException('newSubscription needs enableExternalAccessRequest in group detail: ' . $groupP->getId(), ServiceException :: FORBIDDEN);
					}
				} catch (Exception $e) {
					$this->displayNotFound();
					break;
				}
				//provide the input box

				$totalWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "externalAccessRequestTotalWidth");
				if ($totalWidth == 0)
					$totalWidth = 250;
				$labelWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "externalAccessRequestLabelWidth");
				if ($labelWidth == 0)
					$labelWidth = 100;

				if (!$exec->getIsUpdating()) {
					$action = "mainDiv/" . $exec->getCrtRequest();
				} else {
					$action = $exec->getCrtRequest();
				}
				$externalAccessRequestRec = $this->createActivityRecordForForm($p, Activity :: createInstance("externalAccessRequest"), $exec->getCrtModule());

				$form = $this->createExternalAccessRequestFormExecutor($groupP, $externalAccessRequestRec, $record, "externalAccessRequest_form", $action);
				$form->getTrm()->setP($p);
				$form->setIsDialog(false);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				$form->ResolveForm($p, $exec, $state);

				break;
			case "downloadFromExternalAccess" :
			case "externalAccess" :
				$i = 0;
				$code = $exec->getCrtParameters($i++);
				$action = $exec->getCrtParameters($i++);
				if ($action == null)
					$action = "view";
				$edit = $action == "edit";
				$addJournalItem = $action == "addJournalItem";
				$delete = $action == "delete";
				$isDownload = $action == "download";
				$isUnlock = $action == "unlock";
				$lang = $exec->getCrtParameters($i++);

				if (!isset ($configS))
					$configS = $this->getConfigurationContext();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();

				try {
					$p = $this->getPublicPrincipalForWigiiNamespace($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule());

					//retrieve an elementP from the external code
					list ($elementId, $fieldName, $value, $externalAccessLevel, $externalAccessEndDate, $externalConfigGroup) = $elS->getElementInfoFromExternalCode($p, $code);
//					eput(":$elementId $fieldName $value $externalAccessLevel $externalAccessEndDate $externalConfiGroup:");

					if (($addJournalItem || $edit) && $externalAccessLevel < Emails :: EXTERNAL_ACCESS_EDIT)
						throw new ServiceException("try to edit from external access without sufficient externalAccessLevel", ServiceException :: FORBIDDEN);

					if ($externalAccessEndDate && $externalAccessEndDate < time())
						throw new ServiceException('externalAccessEndDate is reached: ' . date("d.m.Y H:i:s", $externalAccessEndDate), ServiceException :: FORBIDDEN);

					if ($externalConfigGroup) {
						$groupP = $groupAS->getGroup($p, $externalConfigGroup);
						if ($groupP == null) {
							throw new ServiceException('externalAccess on unknown ConfigGroup: ' . $externalConfigGroup, ServiceException :: FORBIDDEN);
						}
						$this->getConfigurationContext()->setGroupPList($p, $exec->getCrtModule(), GroupListAdvancedImpl :: createInstance()->addGroupP($groupP), ($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"));
					} else {
						$this->getConfigurationContext()->setGroupPList($p, $exec->getCrtModule(), null, ($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"));
					}

					$element = $this->createElementForForm($p, $exec->getCrtModule(), $elementId);
					if ($edit || $delete || $action == 'autoSave') { //don't lock addJournalItem as it is a one shot action and will be done in $this->addJournalItem
						if (false === $this->lockEntity($p, $exec, "", "element", $element))
							break;
					} else {
						$elS->unLock($p, $element);
					}

					$groupSubscriptionRecord = null;
					switch ($action) {
						case "unlock" :
							break 2;
						case "edit" :
						case "addJournalItem" :
						case "view" :
						case "delete" :
							$elementP = $elS->fillElementFromExternalCode($p, $code, $element);
							if(($edit || $delete) && ($element->isState_blocked() || $elementP->isParentElementState_blocked())) throw new ServiceException("blockedElementsOperationImpossible", ServiceException::FORBIDDEN);
							$fieldXml = $elementP->getDbEntity()->getFieldList()->getField($fieldName)->getXml();
							//if config group is setup check groupSubscription parameters
							if($groupP){
								$groupSubscriptionRecord = $this->createActivityRecordForForm($p, Activity :: createInstance("groupSubscription"), $exec->getCrtModule());
								$groupSubscriptionRecord->getWigiiBag()->importFromSerializedArray($groupP->getGroup()->getDetail()->getSubscription(), $groupSubscriptionRecord->getActivity());
							}
							break;
						case "download" :
							//set a flag for the download request to use the public principal
							$this->usePublicPrincipalForDownloadRequest();
							$exec->addRequests(($exec->getCrtParameters(4) == "unzipForViewing" ? "elementPreview/" : "") . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/" . $exec->getCrtParameters(1) . "/" . $exec->getCrtParameters(2) . "/" . $exec->getCrtParameters(3) . "/" . $exec->getCrtParameters(4) . "/");
							break 2;
						case "autoSave" :
							$fieldName = $exec->getCrtParameters(3);
							$fsl = FieldSelectorListArrayImpl::createInstance();
							$fsl->addFieldSelector($fieldName);
							$elementP = $elS->fillElementFromExternalCode($p, $code, $element, $fsl);
							$this->autoSave($p, $exec, $elementP, $fieldName, $fsl, true);
							break 2;
					}
				} catch (Exception $e) {
					if ($e->getCode() == ServiceException :: FORBIDDEN) {
						$this->displayNotAvailable($p);
					} else {
						$this->displayNotFound();
					}
//									throw $e;
					break;
				}

                if($configS->getParameter($p, $exec->getCrtModule(), "elementTotalWidthPublic")){
                    $totalWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementTotalWidthPublic");
                }else{
                    $totalWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementTotalWidth");
                }
                if($labelWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementLabelWidthPublic")){
                    $labelWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementLabelWidthPublic");
                }else{
                    $labelWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementLabelWidth");
                }

				if (!$exec->getIsUpdating()) {
					$action = "mainDiv/" . $exec->getCrtRequest();
				} else {
					$action = $exec->getCrtRequest();
				}
				if ($edit) {
					$form = $this->createExternalAccessEditFormExecutor($elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, "externalAccessEdit_form", $action);
				} else if($addJournalItem){
					$this->addJournalItem($p, $exec, $transS, $elS, $elementP, true);
					break;
				} else
					if ($delete) {
						$form = $this->createExternalAccessDeleteFormExecutor($elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, "externalAccessDelete_form", $action);
					} else {
						$form = $this->createExternalAccessViewFormExecutor($elementP, $fieldName, $fieldXml, $externalAccessLevel, $externalAccessEndDate, $groupSubscriptionRecord, "externalAccessView_form", $action);
					}
				$form->getTrm()->setP($p);
				$form->setIsForExternalAccess(true);
				$form->setIsDialog(false);
				//$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);
				if($groupSubscriptionRecord && $groupSubscriptionRecord->getFieldValue("subscriptionEnableCaptcha")){
					$form->setProtectWithCaptcha(true);
				}

				//set the language for the system according to title or lang param
				$this->setLanguageToPBasedOnGroupSubscription($p, $exec, $transS, $groupSubscriptionRecord, $lang);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				//in the case of external access do not check the period and the maxNumber of subscription as there are already
				//subscribed

				$form->ResolveForm($p, $exec, $state);

				break;
			case "getXmlFeed" :
				$i = 0;
				$groupId = $exec->getCrtParameters($i++);
				$xmlCode = $exec->getCrtParameters($i++);
				$isXml = $exec->getCrtParameters($i) == null || $exec->getCrtParameters($i) == "xml"; //if nothing then isXml is by default
				$isDownload = $exec->getCrtParameters($i) == "download"; //this case is when we want to download a file referenced in the xml list. --> the path of files in xml feed is construct with the xml code to check if the document is really available through an xml public feed.
				$isCSVRaw = $exec->getCrtParameters($i) == "csvraw";
				$isCSVBackup = $exec->getCrtParameters($i) == "csvbackup";
				$isCSV = $exec->getCrtParameters($i) == "csv";
				$isICal = $exec->getCrtParameters($i) == "ics";
				$i++;
				if($isDownload){
					$elementId = $exec->getCrtParameters($i++);
					$fieldName = $exec->getCrtParameters($i++);
				} else if($isCSVBackup || $isCSVRaw || $isCSV){
					$isIntegrated = false; //detect if next option is integrated
					if($exec->getCrtParameters($i)=="integrated") { $isIntegrated = true; $i++; }
					if($exec->getCrtParameters($i)=="tab") { $csvSeparator = "	"; $i++; }
					else $csvSeparator = $exec->getCrtParameters($i++);
					if($exec->getCrtParameters($i)=="integrated") { $isIntegrated = true; $i++; }
					$csvEncoding = $exec->getCrtParameters($i++);
					if($exec->getCrtParameters($i)=="integrated") { $isIntegrated = true; $i++; }
				} else if($isICal){
					$fromDate = $exec->getCrtParameters($i++);
				}
				//next parameter is passed to download
				try {
					$p = $this->getPublicPrincipalForWigiiNamespace($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule());
					$record = $this->createActivityRecordForForm($p, Activity :: createInstance("groupXmlPublish"), $exec->getCrtModule());
					if (!isset ($groupAS))
						$groupAS = ServiceProvider :: getGroupAdminService();
					if (!isset ($elS))
						$elS = ServiceProvider :: getElementService();

					$groupP = $groupAS->getGroup($p, $groupId);
					if ($groupP == null || $groupP->getRights() == null) {
						throw new ServiceException('GetXmlFeed on unreachable group: ' . $groupId, ServiceException :: FORBIDDEN);
					}

					//setup the configuration context on the groupId to get configuration
					//We need to get the config for includeCHildrenGroups at a top level. because there is no sense to get the last groupConfig for children when setting a new groupList
					$groupPList = GroupListAdvancedImpl :: createInstance()->addGroupP($groupP);
					$this->getConfigurationContext()->setGroupPList($p, $exec->getCrtModule(), $groupPList, (ServiceProvider :: getConfigService()->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"));

					$record->getWigiiBag()->importFromSerializedArray($groupP->getGroup()->getDetail()->getXmlPublish(), $record->getActivity());
					//verify XmlPublish is enabled for this group
					if ($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_enableXmlPublish") == "0") {
						throw new ServiceException('Group_enableXmlPublish is 0', ServiceException :: FORBIDDEN);
					}
					if (!$record->getFieldValue("enableGroupXmlPublish")) {
						throw new ServiceException('GetXmlFeed needs enableGroupXmlPublish in group detail: ' . $groupP->getId(), ServiceException :: FORBIDDEN);
					}
					if ($record->getFieldValue("xmlPublishCode") !== $xmlCode) {
						throw new ServiceException('GetXmlFeed needs correct code for group: ' . $groupP->getId(), ServiceException :: FORBIDDEN);
					}

					$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "getXmlFeed");
					$lc->resetFetchCriteria($p, $this);
				} catch (Exception $e) {
//					eput($e->getMessage());
//					eput($e->getCode());
//					eput($e);
//					if($e->getPreviousException()){
//						eput($e->getPreviousException()->getMessage());
//						eput($e->getPreviousException()->getCode());
//						eput($e->getPreviousException());
//						if($e->getPreviousException()->getPreviousException()){
//							eput($e->getPreviousException()->getPreviousException()->getMessage());
//							eput($e->getPreviousException()->getPreviousException()->getCode());
//							eput($e->getPreviousException()->getPreviousException());
//						}
//					}
					$this->displayNotFound();
					exit ();
				}
				if ($isDownload) {
					//send the document to the browser
					//check this element is part of the group.
					try {
						//					$checkIds = WigiiBagCheckId::createInstance($elementId);
						//					$elS->fillWigiiBagWithAllElementsInGroup($p, $groupP->getGroup(), $checkIds, ($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"));
						$inGroupLogExp = TechnicalServiceProvider :: getFieldSelectorLogExpParser()->createLogExpFromString("INGR(id = $groupId)");
						$tempLF = ListFilter :: createInstance();
						$tempLF->setFieldSelectorLogExp(LogExp :: createEqualExp(FieldSelector :: createElementAttributeSelector("id"), $elementId));
						$tempFSL = FieldSelectorListArrayImpl :: createInstance();
						$tempFSL->addFieldSelectorInstance(FieldSelector :: createElementAttributeSelector("id"));
						$tempLF->setFieldSelectorList($tempFSL);
						$tempElementPList = ElementPAdvancedListArrayImpl :: createInstance();
						$elS->getSelectedElementsInGroups($p, $inGroupLogExp, $tempElementPList, $tempLF);
						if ($tempElementPList->isEmpty()) {
							throw new ServiceException('GetXmlFeed, download element ' . $elementId . ' but id is not in group ' . $groupId . '', ServiceException :: FORBIDDEN);
						}
						//set a flag for the download request to use the public principal
						$this->usePublicPrincipalForDownloadRequest();
						$exec->addRequests("" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/download/" . $elementId . "/" . $fieldName . ($exec->getCrtParameters($i) ? "/" . $exec->getCrtParameters($i) : ""));
					} catch (Exception $e) {
						$this->displayNotFound();
//						throw $e;
						exit ();
						//					//send the header only at the first request
						//					header("Content-Type: text/html; charset=UTF-8");
						//					//load the header
						//					$SITE_TITLE = $this->getConfigurationContext()->getParameter($p, null, "siteTitle");
						//					include_once(IMPL_PATH . "templates/header.php");
						//					throw $e;
					}
				} else if($isICal){
					try {
						//use root principal to do the csv export
						$exportRec = $this->createActivityRecordForForm($p, Activity :: createInstance("exportExcelCalendar"), $exec->getCrtModule());
						$exportICSFormExecutor = $this->createExportICSFormExecutor($exportRec, null, null);

						//if no date define do today minus 3 month
						if($fromDate == null){
//							$fromDate = date_create();
//							date_timestamp_set($fromDate, $fromDate->getTimestamp()-(30*3*24*3600));
//							$fromDate = $fromDate->format("Y-m-d");
							$fromDate = date("Y-m-d", time()-(30*3*24*3600));
						}
						$exportRec->setFieldValue($fromDate, "excelCalendarYear");

						$sessAS = ServiceProvider::getSessionAdminService();
						$sessAS->storeData($exportRec, "exportRecordWigiiBag", $exportRec->getWigiiBag());

						$exportLC = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "exportElements");
						$elementListLC = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						$tempExportLC = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "TempExportElements");

						//set group context to current group
						$exportLC->setGroupPList($groupPList, true);
						$elementListLC->setGroupPList($groupPList, true);
						$tempExportLC->setGroupPList($groupPList, true);
						//set config group to current group
						$elementListLC->setConfigGroupList($groupPList);
						$exportLC->setConfigGroupList($groupPList);
						$tempExportLC->setConfigGroupList($groupPList);

						$exportICSFormExecutor->export($p, $exec, null, $exportLC, $elementListLC, $tempExportLC);

					} catch (Exception $e) {
//						eput($e);
//						if($e->getPreviousException()){
//							eput($e->getPreviousException());
//							if($e->getPreviousException()->getPreviousException()){
//								eput($e->getPreviousException()->getPreviousException());
//							}
//						}
						$this->displayNotFound();
//						throw $e;
						exit ();
					}
				} else if($isCSVBackup || $isCSVRaw || $isCSV){
					try {
						//use root principal to do the csv export
						$exportRec = $this->createActivityRecordForForm($p, Activity :: createInstance("exportCSV"), $exec->getCrtModule());
						$exportCSVFormExecutor = $this->createExportFormExecutor($exportRec, null, null);
						if($isCSV){
							$exportRec->setFieldValue("csvFormated", "csvFormat");
						}
						if($isCSVRaw){
							$exportRec->setFieldValue("csvRaw", "csvFormat");
						}
						if($isCSVBackup){
							$exportRec->setFieldValue("csvBackup", "csvFormat");
						}
						if(!$csvSeparator)	$exportRec->setFieldValue(",", "csvSeparator");
						else $exportRec->setFieldValue($csvSeparator, "csvSeparator");
						if(!$csvEncoding)	$exportRec->setFieldValue("ISO-8859-1", "csvEncoding");
						else $exportRec->setFieldValue($csvEncoding, "csvEncoding");

						$sessAS = ServiceProvider::getSessionAdminService();
						$sessAS->storeData($exportRec, "exportRecordWigiiBag", $exportRec->getWigiiBag());

						$exportLC = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "exportElements");
						$elementListLC = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						$tempExportLC = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "TempExportElements");

						//set group context to current group
						$exportLC->setGroupPList($groupPList, true);
						$elementListLC->setGroupPList($groupPList, true);
						$tempExportLC->setGroupPList($groupPList, true);
						//set config group to current group
						$elementListLC->setConfigGroupList($groupPList);
						$exportLC->setConfigGroupList($groupPList);
						$tempExportLC->setConfigGroupList($groupPList);

						$exportCSVFormExecutor->export($p, $exec, null, $exportLC, $elementListLC, $tempExportLC, $isIntegrated);

					} catch (Exception $e) {
//						$this->displayNotFound();
						throw $e;
						exit ();
					}
				} else {
					try {
						//generates an xml list file
						//add headers
						header('Cache-Control: no-cache, must-revalidate');
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
						header("Pragma: no-cache");
						header('Pragma: public');
						header('Content-type: ' . typeMime(".xml"));

						//generates the xml
						$elementPListExportXml = $this->createElementPListExportXmlInstance($this, $lc);
						$elementPListExportXml->setModule($exec->getCrtModule());
						$elementPListExportXml->setCrtRequest($exec->getCrtRequest());
						$elementPListExportXml->actOnBeforeAddElementP();

						$tempLF = ListFilter :: createInstance();
						$listViewXml = $this->getConfigurationContext()->mf($p, $exec->getCrtModule(), Activity :: createInstance("listView"));
						if ($listViewXml && $listViewXml->xpath("*[@isDefaultSortingKey=1]")) {
							$field = reset($listViewXml->xpath("*[@isDefaultSortingKey=1]"));
							$tempLF->setFieldSortingKeyList(FieldSortingKeyListArrayImpl :: createInstance());
							if ((string) $field["elementAttribut"]) {
								$tempLF->getFieldSortingKeyList()->addElementSortingKey((string) $field["elementAttribut"], trim((string) $field["defaultSorted"]) != "DESC");
							} else {
								$tempLF->getFieldSortingKeyList()->addFieldSortingKey((string) $field["field"], ((string) $field["subField"] ? (string) $field["subField"] : null), trim((string) $field["defaultSorted"]) != "DESC");
							}
						}

						$elS->getAllElementsInGroup($p, $groupP->getGroup(), $elementPListExportXml, ($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"), $tempLF);
						$elementPListExportXml->actOnFinishAddElementP(null);

					} catch (Exception $e) {
//						throw $e;
						$this->displayNotFound();
					}
					exit ();
				}
				break;
			case "newSubscription" :
				$i = 0;
				$groupId = $exec->getCrtParameters($i++);
				$lang = $exec->getCrtParameters($i++);

				if (!isset ($configS))
					$configS = $this->getConfigurationContext();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();

				try {
					$p = $this->getPublicPrincipalForWigiiNamespace($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule());
					//CWE 2014.06.20: pushed this line after setting the groupList in the ConfigurationContext $record = $this->createActivityRecordForForm($p, Activity :: createInstance("groupSubscription"), $exec->getCrtModule());
					$groupP = $groupAS->getGroup($p, $groupId);
					if ($groupP == null || $groupP->getRights() == null || !$groupP->getRights()->canWriteElement()) {
						throw new ServiceException('newSubscription on unreachable group: ' . $groupId, ServiceException :: FORBIDDEN);
					}
					$this->getConfigurationContext()->setGroupPList($p, $exec->getCrtModule(), GroupListAdvancedImpl :: createInstance()->addGroupP($groupP), ($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"));
					//setup the configuration context on the groupId to get configuration
					$record = $this->createActivityRecordForForm($p, Activity :: createInstance("groupSubscription"), $exec->getCrtModule());
					$record->getWigiiBag()->importFromSerializedArray($groupP->getGroup()->getDetail()->getSubscription(), $record->getActivity());
					//verify GroupSubscription is enabled for this group
					if ($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Group_enableSubscription") == "0") {
						throw new ServiceException('Group_enableSubscription is 0', ServiceException :: FORBIDDEN);
					}
					if (!$record->getFieldValue("enableGroupSubscription")) {
						throw new ServiceException('newSubscription needs enableGroupSubscription in group detail: ' . $groupP->getId(), ServiceException :: FORBIDDEN);
					}

				} catch (Exception $e) {
					$this->displayNotFound();
					break;
				}
				//eput($record);
                if($configS->getParameter($p, $exec->getCrtModule(), "elementTotalWidthPublic")){
                    $totalWidth = $configS->getParameter($p, $exec->getCrtModule(), "elementTotalWidthPublic");
                }else{
                    $totalWidth = $configS->getParameter($p, $exec->getCrtModule(), "elementTotalWidth");
                }
                if($labelWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementLabelWidthPublic")){
                    $labelWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementLabelWidthPublic");
                }else{
                    $labelWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementLabelWidth");
                }
				if (!$exec->getIsUpdating()) {
					$action = "mainDiv/" . $exec->getCrtRequest();
				} else {
					$action = $exec->getCrtRequest();
				}
				$element = Element :: createInstance($exec->getCrtModule());
				$form = $this->createAddSubscriptionFormExecutor($groupP, $record, $element, "addSubscription_form", $action);
				$form->getTrm()->setP($p);
				$form->setIsForExternalAccess(true);
				$form->setIsDialog(false);
				//$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);
				if($record->getFieldValue("subscriptionEnableCaptcha")){
					$form->setProtectWithCaptcha(true);
				}

				//set the language for the system according to title or lang param
				$this->setLanguageToPBasedOnGroupSubscription($p, $exec, $transS, $record, $lang);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				//check period
				$this->checkPeriodOnGroupSubscription($p, $exec, $transS, $record, $form, $state);
				//check max subscription
				$this->checkMaxSubscriptionOnGroupSubscription($p, $exec, $transS, $record, $form, $elS, $groupP, $state);

				//display the formulary
				$form->ResolveForm($p, $exec, $state);

				break;
			case "moduleEditorCalc" :
				// CWE 03.02.2016: FUNCTION IS DEPRECATED. too risky to launch recalculation of all fields without knowing which elements are currently selected.
				// Prefer a more controlled approach through multiple selection or batch.
				throw new ServiceException('This functionality is no more supported. Use multiple modify instead.', ServiceException::DEPRECATED);
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
				if (!$p->isModuleEditor())
					throw new ServiceException('Recalculating data can only be done with a moduleEditor', ServiceException :: FORBIDDEN);
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$workingModule = ServiceProvider :: getModuleAdminService()->getModule($p, $workingModuleName);

				// selects all elements in all groups accessible by principal
				$groupList = GroupListAdvancedImpl :: createInstance();
				$lf = ListFilter :: createInstance();
				$lf->setFieldSelectorList(ServiceProvider :: getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail());
				$groupAS->getAllGroups($p, $workingModule, $groupList, $lf);

				//eput($groupList);
				$inGroupLogExp = LogExp :: createInGroupExp(LogExp :: createInExp(FieldSelector :: createInstance("id"), $groupList->getRootGroups()->getGroupIds()), true);

				// evaluates all calculated fields in all selected elements and updates the database
				$elS->evaluateSelectedElementsInGroups($p, $inGroupLogExp);
				$exec->addJsCode(" actOnCloseDialog('" . $exec->getIdAnswer() . "'); ");
				$this->openAsMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), '', null, $transS->t($p, "ok"), null, null, "done");

				break;
			case "moduleEditorDelete" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
				if (!$p->isWigiiNamespaceCreator())
					throw new ServiceException('deleting config require wigiiNamespace creator rights', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				$configFileName = $exec->getCrtParameters($i++);
				$doDelete = $exec->getCrtParameters($i++);

				if ($doDelete && $configFileName != null) {
					@ unlink(CLIENT_CONFIG_PATH . $configFileName);
					$exec->addRequests("adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/adminModuleEditor");
					$exec->addJsCode("actOnCloseDialog('" . $exec->getIdAnswer() . "');");
				} else {
					$totalWidth = 450;
					$labelWidth = 150;
?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/48x48/status/not-known.png";?>" style="float:left;margin:0px 5px 0px 0px;"/><?

					echo '<span style="line-height:1.7;">';
					echo $transS->t($p, "areYouSureDeleteFile");
					echo "<br>";
					echo "<b>" . $configFileName . "</b>";
					echo "</span>";
					$this->openAsDialogForm($exec->getIdAnswer(), $totalWidth, "update('" .
					$exec->getCrtRequest() . "/doDelete');", $transS->t($p, "moduleEditorDeleteFile"), $transS->t($p, "ok"), $transS->t($p, "cancel"), null);
				}

				break;
			case "moduleEditorNew" :
				if (!$p->isWigiiNamespaceCreator())
					throw new ServiceException('adding config require wigiiNamespace creator rights', ServiceException :: FORBIDDEN);
			case "moduleEditorEdit" :
				if ($exec->getCrtAction() == "moduleEditorNew")
					$moduleEditorIsNew = true;
				if ($exec->getCrtAction() == "moduleEditorEdit")
					$moduleEditorIsNew = false;

				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
				if (!$p->isModuleEditor())
					throw new ServiceException('config edition require module creator rights', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

				$totalWidth = 950;
				$labelWidth = 250;
				$action = $exec->getCrtRequest();

				$i = 0;
				$workingModuleName = $exec->getCrtParameters($i++);
				if ($moduleEditorIsNew) {
					$configFileName = null;
				} else {
					$configFileName = $exec->getCrtParameters($i++);
				}

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("moduleConfigEdit"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/adminModuleEditor";

				$form = $this->createModuleConfigEditFormExecutor($moduleEditorIsNew, $configFileName, $rec, "moduleConfigEdit_form", $action, $request);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				$form->ResolveForm($p, $exec, $state);

				break;
			case "moduleEditorNewNamespace":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
				if (!$p->isModuleEditor())
					throw new ServiceException('moduleEditorNewNamespace require module creator rights', ServiceException :: FORBIDDEN);
				if (!$p->isWigiiNamespaceCreator())
					throw new ServiceException('moduleEditorNewNamespace require WigiiNamespace creator rights', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

				$totalWidth = 450;
				$labelWidth = 200;
				$action = $exec->getCrtRequest();

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("moduleEditorNewNamespace"), $exec->getCrtModule());

				//build the InTab options
				$moduleAccess = $p->getModuleAccess();
				$moduleAttribute = ServiceProvider :: getModuleAdminService()->moduleArrayToString($moduleAccess);
				$moduleAttribute = str_replace(Module :: ADMIN_MODULE . ";", "", $moduleAttribute);
				$moduleAttribute = '<attribute>' . implode("</attribute><attribute>", explode(";", $moduleAttribute)) . '</attribute>';
				$moduleAttribute = str_replace("<attribute>" . $exec->getCrtModule()->getModulename(), "<attribute checked='1'>" . $exec->getCrtModule()->getModulename(), $moduleAttribute);
				$rec->getFieldList()->getField("moduleEditorNewNamespaceTabs")->setXml(simplexml_load_string('<moduleEditorNewNamespaceTabs type="MultipleAttributs" useCheckboxes="1" isInLine="1" useMultipleColumn="2" require="1">' . $moduleAttribute . '</moduleEditorNewNamespaceTabs>'));

				//set url to refresh on done, depending on context
				$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/adminModuleEditor";

				$form = $this->createModuleEditorNewNamespaceFormExecutor($rec, "moduleEditorNewNamespace_form", $action);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				$form->ResolveForm($p, $exec, $state);

				break;
			case "moduleEditorRemoveNamespace":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
				if (!$p->isModuleEditor())
					throw new ServiceException('moduleEditorRemoveNamespace require module creator rights', ServiceException :: FORBIDDEN);
				if (!$p->isWigiiNamespaceCreator())
					throw new ServiceException('moduleEditorRemoveNamespace require WigiiNamespace creator rights', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

				$totalWidth = 450;
				$labelWidth = 200;
				$action = $exec->getCrtRequest();

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("moduleEditorRemoveNamespace"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/adminModuleEditor";

				$form = $this->createModuleEditorRemoveNamespaceFormExecutor($rec, "moduleEditorRemoveNamespace_form", $action);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				$form->ResolveForm($p, $exec, $state);

				break;
			case "moduleEditorRemoveEmailNotification":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!$exec->getCrtModule()->isAdminModule())
					throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
				if (!$p->isModuleEditor())
					throw new ServiceException('moduleEditorRemoveEmailNotification require module creator rights', ServiceException :: FORBIDDEN);

				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

				$totalWidth = 450;
				$labelWidth = 200;
				$action = $exec->getCrtRequest();

				$rec = $this->createActivityRecordForForm($p, Activity :: createInstance("moduleEditorRemoveEmailNotification"), $exec->getCrtModule());

				//set url to refresh on done, depending on context
				$request = "adminWorkZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/adminModuleEditor";

				$form = $this->createModuleEditorRemoveEmailNotificationFormExecutor($rec, "moduleEditorRemoveEmailNotification_form", $action);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				$form->ResolveForm($p, $exec, $state);

				break;
			case "login" :
				if (!isset ($transS))
						$transS = ServiceProvider :: getTranslationService();

				$logRec = ActivityRecord :: createInstance(Activity :: createInstance("login"));
				if ($exec->getIdAnswer() == null)
					$idAnswer = "loginForm";
				else
					$idAnswer = $exec->getIdAnswer();

				$action = $idAnswer . "/" . WigiiNamespace :: EMPTY_NAMESPACE_URL . "/Admin/login";
				$form = $this->createLoginFormExecutor($logRec, "login_form", $action);
				$form->setSubmitLabel($transS->t($p, "login"));
				//			$form->setDottedLineSeparatorBetweenField(false);
				$form->setLabelWidth(200);
				$form->setTotalWidth(450);
				if ($idAnswer == "elementDialog") {
					$form->setCorrectionWidth(19);
					$form->setIsDialog(true);
				} else {
					$form->setIsDialog(false);
				}				
				
				// CWE 03.02.2016 shows captcha if already posted to be able to check value
				if(array_key_exists('captcha_code', $_POST)) $form->setProtectWithCaptcha(true);
				
				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				$form->ResolveForm($p, $exec, $state);

				break;
			case "logout" :
				$sessAS = ServiceProvider::getSessionAdminService();
				//be sure having called before the $this->getJsCodeBeforeLogout($p) to make the autoLogin cookie disabled
				if ($p->isPlayingRole()) {
					$this->throwEvent()->logout(PWithUser :: createInstance($p, $p->getRealUser()));
				} else {
					$this->throwEvent()->logout(PWithUser :: createInstance($p, $p->getAttachedUser()));
				}
				$this->storeWigiiContextInP($p, $exec); //the persist of the session is done in the logout method
				//allow double logout without exception
				try{
					ServiceProvider :: getAuthenticationService()->logout();
				} catch (AuthenticationServiceException $ase){
					if($ase->getCode() != AuthenticationServiceException::FORBIDDEN){
						throw $ase;
					}
				}
				$this->clearWigiiContext();
				$_SESSION["RemainingUpdates"] = null;
				$sessAS->clearData($exec, "lastPrincipalIdContext");
				$exec->addRequests("mainDiv/" . WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/all");
				break;
//			//DEPRECATED
//			case "changeRole" :
//				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
//					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
//
//				$lastAttachedUserId = $p->getUserId(); //used to go back to last one on error
//
//				//			//persist context in DB;
//				//			$this->persistMainPrincipalSessionContext($p, $exec);
//
//				//ServiceProvider::getAuthenticationService()->persistMainPrincipalSessionContext();
//
//				$roleId = $exec->getCrtParameters(0);
//				$lastModule = $exec->getCrtParameters(1); //optional, if not defined then use the lastModule stored in context
//				$do = $exec->getCrtParameters(2); //optional, if defined, then we add the do request to the diplay all
//				if ($do == "do") {
//					$do = implode("/", array_slice($exec->getCrtParameters(), 3));
//				} else {
//					$do = null;
//				}
//
//				//do nothing if try to change principal to the same one
//				if ($roleId == $p->getUserId())
//					break;
//
//				if ($p->isPlayingRole() && $roleId == (string) $p->getRealUser()->getId()) {
//					$p = ServiceProvider :: getAuthenticationService()->backToRealUser($p);
//				} else {
//					$p = ServiceProvider :: getAuthenticationService()->changeToRole($p, $roleId);
//				}
//
//				$this->clearWigiiContext();
//
//				//if the new role has no moduleAccess then go back to realUser and add a message
//				if ($p->getModuleAccess() == null) {
//					//close the NoAnswer part and open a messageDialog
//					echo ExecutionServiceImpl :: answerRequestSeparator;
//					echo "confirmationDialog";
//					echo ExecutionServiceImpl :: answerParamSeparator;
//					$transS = ServiceProvider :: getTranslationService();
//					$this->openAsMessage("confirmationDialog", 350, $transS->t($p, "choosedRoleHasNoModuleAccess"), $transS->t($p, "goBackToLastUserRole"), "update('" .
//					"NoAnswer/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/changeRole/" . $lastAttachedUserId . "');");
//
//					//eput("noModuleAccess for this role, go back to user");
//					//$exec->addRequests();
//					break;
//				}
//
//				//go on last module of the choosen role/user
//				if ($lastModule == null)
//					$lastModule = $p->getValueInRoleContext("lastModule");
//
//				if ($lastModule == null || $p->getModuleAccess($lastModule) == null) {
//					//choose the first module of moduleAccess
//					$lastModule = $p->getFirstNoneAdminAccessibleModule();
//				} else {
//					$lastModule = ServiceProvider :: getModuleAdminService()->getModule($p, $lastModule);
//				}
//
//				//select admin for wigiiNamespaceCreator or moduleEditor
//				//deselect admin for others
//				if ($lastModule->isAdminModule() && !$p->isWigiiNamespaceCreator() && !$p->isModuleEditor()) {
//					$lastModule = $this->getAdminContext($p)->getWorkingModule();
//				}
//				elseif (!$lastModule->isAdminModule() && ($p->isWigiiNamespaceCreator() || $p->isModuleEditor())) {
//					$this->getAdminContext($p)->setWorkingModule($lastModule);
//					$lastModule = ServiceProvider :: getModuleAdminService()->getModule($p, Module :: ADMIN_MODULE);
//					$p->setValueInRoleContext("lastModule", $lastModule->getModuleName());
//				}
//
//				//persist context in DB;
//				$this->persistMainPrincipalSessionContext($p, $exec);
//
//				$exec->addRequests("mainDiv/" . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $lastModule->getModuleUrl() . "/display/all" . ($do ? "/do/" . $do : ""));
//
//				break;
			case "changeSortByKey":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS)) $configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");

				//define the sorting by
                $key = $exec->getCrtParameters(0);
                $ascending = ($exec->getCrtParameters(1) == "true" ? true : ($exec->getCrtParameters(1) == "false" ? false : null));
                if($ascending === null){
                    switch ($key){
                        case "(__element(sys_date))":
                        case "(__element(sys_creationDate))":
                            $ascending = false;
                            break;
                    }
                }

				$lc->setSortedBy($key, $ascending);
				$exec->addJsCode("invalidCache('moduleView');");
				$exec->addRequests('moduleView/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/moduleView");

				break;
			case "changeGroupByKey":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS)) $configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");

				//define the group by
				$lc->setGroupBy(($exec->getCrtParameters(0)==="null" ? null : $exec->getCrtParameters(0)));

				if($exec->getCrtParameters(0)=="reset"){
					//reset folder view
					$lc->setGroupPList($lc->getGroupPList(), false);
				}

				$exec->addJsCode("invalidCache('moduleView');");
				$exec->addRequests('moduleView/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/moduleView");

				break;
			case "saveListViewUIPref":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS)) $configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();
				
				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				$lc->setListViewUIPref($exec->getCrtParameters(0), $exec->getCrtParameters(1), $exec->getCrtParameters(2));
				
				$exec->invalidCache($p, 'moduleView');
				break;
			case "changeGroupByToFindDuplicates":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS)) $configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");

				list($fieldKey, $subFieldKey) = explode(" ", $exec->getCrtParameters(0));

				//set group by this field and limit results to duplicates
				//generally subField value is ignored
				$lc->setGroupBy("($fieldKey($subFieldKey))", true);
				$lc->setGroupPList($lc->getGroupPList(), true);
				$this->getConfigurationContext()->setGroupPList($p, $exec->getCrtModule(), $lc->getGroupPList(), true);

				$exec->addJsCode("invalidCache('moduleView');");
				$exec->addRequests('moduleView/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/moduleView");

//				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
//
//				//define the group by
//				$lc->setGroupBy($exec->getCrtParameters(0));
//				$exec->addRequests('moduleView/'.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/moduleView");

				break;
			case "addIndicator" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS))
					$configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();

				$totalWidth = 550; //$configS->getParameter($p, $exec->getCrtModule(), "addIndicatorTotalWidth");
				$labelWidth = 250; //$configS->getParameter($p, $exec->getCrtModule(), "addIndicatorLabelWidth");;

				$indicatorRec = $this->createActivityRecordForForm($p, Activity :: createInstance("addIndicator"), $exec->getCrtModule());

				//build the field list
				$fl = FieldListArrayImpl :: createInstance(false, true);
				$configS->getFields($p, $exec->getCrtModule(), null, $fl);
				$attribute = '<attribute>none</attribute><attribute optGroupStart="1">' . $transS->t($p, "elementFields");
				$dtList = array (); //used after in jscode to disable invalid function
				//add the element fields
				foreach ($fl->getListIterator() as $field) {
					$attribute .= '</attribute>';
					$dtList[($field->getDataType() ? $field->getDataType()->getDataTypeName() : "")] = $field->getDataType();
					$attribute .= '<attribute class="' . ($field->getDataType() ? $field->getDataType()->getDataTypeName() : "") . '">';
					$attribute .= $field->getFieldName();
					$attribute .= '<label>' . $transS->t($p, $field->getFieldName(), $field->getXml()) . '</label>';
				}
				$attribute .= '</attribute><attribute optGroupEnd="1"/>';
				/**
				 * currently the ElementService is not able to manage element Attribute in a efficient way because of
				 * a bug into the getAllElements with an empty fieldSelector
				 */
				//			$attribute .= '<attribute optGroupStart="1">'.$transS->t($p, "elementAttribute");
				//			//add the element attributes
				//			$fsl = $elS->getFieldSelectorListForElementAttributForImport();
				//			foreach($fsl->getListIterator() as $fs){
				//				$doNotShow = false;
				//				switch($fs->getSubFieldName()) {
				//					case "state_lockedInfo":
				//					case "state_important1Info":
				//					case "state_important2Info":
				//					case "state_hiddenInfo":
				//					case "state_archivedInfo":
				//					case "state_deprecatedInfo":
				//						$doNotShow = true;
				//						break;
				//					}
				//					if($doNotShow) continue;
				//				$attribute .= '</attribute>';
				//				$attribute .= '<attribute>';
				//				$attribute .= $fs->getFieldName()."/".$fs->getSubFieldName();
				//				$attribute .= '<label>'.$transS->t($p, $fs->getSubFieldName()).'</label>';
				//			}
				//			$attribute .= '</attribute><attribute optGroupEnd="1" />';
				$indicatorRec->getFieldList()->getField("indicator_field")->setXml(simplexml_load_string('<indicator_field type="Attributs" require="1">' . $attribute . '</indicator_field>'));

				//build the function list
				$attribute = '<attribute>none';
				foreach (Indicator :: getAvailableFunctions() as $funcId => $funcName) {
					$attribute .= '</attribute>';
					$attribute .= '<attribute>';
					$attribute .= $funcId;
					$attribute .= '<label>' . $transS->t($p, $funcName) . '</label>';
				}
				$attribute .= '</attribute>';
				$indicatorRec->getFieldList()->getField("indicator_function")->setXml(simplexml_load_string('<indicator_function type="Attributs" require="1">' . $attribute . '</indicator_function>'));

				$action = $exec->getCrtRequest();
				$form = $this->createAddIndicatorFormExecutor($indicatorRec, "addIndicator_form", $action);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				//add disable some function on change
				//construct dt if !!!!! This should be repeated as soon as we enable indicators on elementAttribute
				$code = "";
				foreach ($dtList as $dt) {
					$code .= " if(dt == '" . $dt->getDataTypeName() . "'){ ";
					$func = Indicator :: getAvailableFunctionsForDataType($dt);
					foreach ($func as $f => $fname) {
						$code .= " $('option[value=$f]', funcList).removeAttr('disabled');";
					}
					$code .= "} ";
				}
				$exec->addJsCode("" .
					"$('#" . $form->getFormId() . "_indicator_field_value_select').change(function(){" .
						"dt = $(this).find('option[value='+$(this).val()+']').attr('class');" .
						"funcList = $('#" . $form->getFormId() . "_indicator_function_value_select');" .
						"funcList.val('none');" .
						"$('option', funcList).attr('disabled', true);" .
						"$code" .
						"});" .
					"");
				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				$form->ResolveForm($p, $exec, $state);

				break;
			case "closeIndicator" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				$indicatorList = $this->getIndicatorList($p, $exec);
				$indicatorList->removeIndicator($exec->getCrtParameters(0));
				$this->serializeIndicatorsInContext($p, $exec, $indicatorList);
				//persist context in DB;
				$this->persistMainPrincipalSessionContext($p, $exec);
				$exec->addJsCode("invalidCache('moduleView'); resize_elementList();");
				break;
			case "showIndicators" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
					$p->setValueInRoleContext("indicators_areShown_".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . '_' . $exec->getCrtModule()->getModuleUrl(), true);
				$exec->addRequests('indicators/' . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . '/' . $exec->getCrtModule()->getModuleUrl() . '/display/indicators');
				//persist context in DB;
				$this->persistMainPrincipalSessionContext($p, $exec);
				$exec->addJsCode("invalidCache('moduleView'); resize_elementList();");
				break;
			case "closeIndicators" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
					$p->setValueInRoleContext("indicators_areShown_".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . '_' . $exec->getCrtModule()->getModuleUrl(), false);
				$exec->addRequests('indicators/' . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . '/' . $exec->getCrtModule()->getModuleUrl() . '/display/indicators');
				//persist context in DB;
				$this->persistMainPrincipalSessionContext($p, $exec);
				$exec->addJsCode("invalidCache('moduleView'); resize_elementList();");
				break;
			case "evaluateIndicators" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();
				if (!isset ($configS))
					$configS = $this->getConfigurationContext();

				$indicatorList = $this->getIndicatorList($p, $exec);

				if ($indicatorList != null && $indicatorList->count()) {
					//if parameters are setted, then set them to it:
					$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "EvaluateIndicators");
					//take the elementListContext
					$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
					$lc->matchFetchCriteria($elementListContext);
					$trm = $this->createTRM();
					$fl = FieldListArrayImpl :: createInstance(false, true);
					$configS->getFields($p, $exec->getCrtModule(), null, $fl);
					$originalIncludeChildrenGroups = $lc->getGroupLogExp()->includeChildrenGroups();
					foreach ($indicatorList->getListIterator() as $indicatorId => $indicator) {
						//if indicator is recursive, force the evaluation with the subfolders
						if($indicator->isRecursive()){
							$lc->getGroupLogExp()->setIncludeChildrenGroups(true);
						} else {
							$lc->getGroupLogExp()->setIncludeChildrenGroups($originalIncludeChildrenGroups);
						}
						$val = $elS->evaluateIndicatorOnElementsInGroups($p, $indicator, $lc->getGroupLogExp(), $lc);
						if ($indicator->getFieldSelector()->isElementAttributeSelector()) {
							if ($indicator->getFunction() == Indicator :: FUNC_AVG || $indicator->getFunction() == Indicator :: FUNC_MAX || $indicator->getFunction() == Indicator :: FUNC_MIN || $indicator->getFunction() == Indicator :: FUNC_SUM) {
								switch ($indicator->getFieldSelector()->getSubFieldName()) {
									case "sys_creationDate" :
									case "sys_date" :
										$val = $trm->doFormatForDate($val, false, true, true);
								}
							}
						} else {
							$field = $fl->getField($indicator->getFieldSelector()->getFieldName());
							if ($indicator->getFunction() == Indicator :: FUNC_AVG || $indicator->getFunction() == Indicator :: FUNC_MAX || $indicator->getFunction() == Indicator :: FUNC_MIN || $indicator->getFunction() == Indicator :: FUNC_SUM) {
								switch ($indicator->getDataType()->getDataTypeName()) {
									case "Files" :
										$val = $trm->doFormatForFileSize($val);
										break;
									case "Dates" :
									case "TimeRanges" :
										$xml = $field->getXml();
										$val = $trm->doFormatForDate($val, false, $xml["isBirthDate"] == "1", $xml["includeTime"] == "1");
										break;
									case "Numerics" :
										$val = $trm->doFormatForNumeric($val);
										break;
									case "Floats" :
										$val = $trm->doFormatForFloat($val);
									case "Links" :
										$val = $trm->doFormatForLinks($val);
										break;
								}
							}
						}
						echo "$('#$indicatorId span.value').text('" . str_replace("'", "\'", $val) . "');";
					}
				}
				break;
			case "getKeepNotifiedDialogContent" :
			case "changeKeepNotifiedEmail" :
			case "setKeepNotifiedEmail" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				$transS = ServiceProvider :: getTranslationService();
				$configS = $this->getConfigurationContext();
				if ($configS->getParameter($p, $exec->getCrtModule(), "Notification_enable") != "1" || $configS->getParameter($p, $exec->getCrtModule(), "Notification_enableDynamicSubscription") != "1") throw new ServiceException("Notification_enableDynamicSubscription is disabled", ServiceException :: FORBIDDEN);

				//send back the input html with the user email
				$pEmail = null;
				if ($exec->getCrtAction() == "changeKeepNotifiedEmail") {
					//leave pEmail as empty
				} else
					if ($exec->getCrtAction() == "setKeepNotifiedEmail") {
						$fe = $this->createFormExecutor();
						$fe->preventInjectionForm($p, $exec);
						$pEmail = $_POST["emailAccountForP"];
					} else {
						$pEmail = $p->getValueInGeneralContext("email");
					}
				echo $transS->t($p, "keepNotifiedTitle");
				echo ExecutionServiceImpl :: answerParamSeparator;
				$emailIsValid = validateEmail($pEmail); $unauthorizedEmail = false;
				// Medair (CWE) 11.04.2017 checks for authorized direct sender
				if($pEmail && $emailIsValid && defined("EmailService_sendOnBehalfOfUser") && EmailService_sendOnBehalfOfUser) {
				    try {
				        $this->getEmailService()->isEmailAuthorizedDirectSender($p,$pEmail,$p->getRealUsername());
				    }
				    catch(AuthorizationServiceException $ase) {
				        if($ase->getCode() == AuthorizationServiceException::NOT_ALLOWED) {
				            $unauthorizedEmail = true;
				            $emailIsValid = false;
				        }
				        else throw $ase;
				    }
				}
				if ($pEmail && $emailIsValid) {
					echo "emailAdressIsDefined";
					echo ExecutionServiceImpl :: answerParamSeparator;
					echo $transS->t($p, "keepNotifiedEmailIntroduction") . ' <span class="email">' . $pEmail . '</span>&nbsp;(<a href="#">' . $transS->t($p, "changeKeepNotifiedEmailLabel") . '</a>)';
					echo ExecutionServiceImpl :: answerParamSeparator;
					$p->setValueInGeneralContext("email", $pEmail);
					$this->persistMainPrincipalSessionContext($p, $exec);

					$groupAS = ServiceProvider :: getGroupAdminService();
					$gExp = 'module = "' . $exec->getCrtModule()->getModuleName() . '" AND emailNotification LIKE %' . $pEmail . '%';
					$parser = TechnicalServiceProvider :: getFieldSelectorLogExpParser();
					$gExp = $parser->createLogExpFromString($gExp);
					$groupList = GroupListGetReadGroupIds :: createInstance();
					$groupAS->getSelectedGroupsWithoutDetail($p, $gExp, $groupList);
					echo implode(",", $groupList->getResult());
					$exec->addJsCode("$('#organizeDialog .notifiedIntroduction a').click(function(){ actionForKeepNotified('changeKeepNotifiedEmail'); return false; })");
				} else {
					echo "defineANewEmailAdress";
					echo ExecutionServiceImpl :: answerParamSeparator;
					if (($exec->getCrtAction() == "setKeepNotifiedEmail") || !$emailIsValid) {					    
					    echo '<span class="error">' . ($unauthorizedEmail ? $transS->t($p, "unauthorizedEmail"): $transS->t($p, "invalidEmail")) . '</span><br />';
					}
					echo '<label for="KeepNotifiedEmailInput" >' . $transS->t($p, "keepNotifiedEmailInputLabel") . '</label><br /><input id="KeepNotifiedEmailInput" type="text" value="' . $pEmail . '" />';
					$exec->addJsCode("$('#organizeDialog #KeepNotifiedEmailInput').focus().keydown(function(e){if(e.keyCode == 13){ $('#organizeDialog').closest('.ui-dialog').find('.ui-dialog-buttonpane .ok').click(); e.stopPropagation(); }});");
				}
				break;
			case "setKeepNotifiedGroupsForEmail" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				$transS = ServiceProvider :: getTranslationService();
				$configS = $this->getConfigurationContext();
				if ($configS->getParameter($p, $exec->getCrtModule(), "Notification_enable") != "1" || $configS->getParameter($p, $exec->getCrtModule(), "Notification_enableDynamicSubscription") != "1") throw new ServiceException("Notification_enableDynamicSubscription is disabled", ServiceException :: FORBIDDEN);

				$fe = $this->createFormExecutor();
				$fe->preventInjectionForm($p, $exec);
				$pEmail = $p->getValueInGeneralContext("email");
				$originalGroups = $_POST["original"];
				$requestedGroups = $_POST["actual"];
				if (validateEmail($pEmail)) {

					if($originalGroups && $requestedGroups){
						$removeGroups = array_diff($originalGroups, $requestedGroups);
						$addGroups = array_diff($requestedGroups, $originalGroups);
					} else if(!$originalGroups && !$requestedGroups){
						$removeGroups = array();
						$addGroups = array();
					} else if(!$originalGroups){
						$removeGroups = array();
						$addGroups = $requestedGroups;
					} else if(!$requestedGroups){
						$removeGroups = $originalGroups;
						$addGroups = array();
					}

					$groupAS = ServiceProvider :: getGroupAdminService();
//					eput($addGroups);
//					eput($removeGroups);
					if($addGroups) $groupAS->addEmailNotificationToGroups($p, $addGroups, $pEmail);
					if($removeGroups) $groupAS->eraseEmailNotificationToGroups($p, $removeGroups, $pEmail);

					echo ExecutionServiceImpl :: answerRequestSeparator;
					echo "confirmationDialog";
					echo ExecutionServiceImpl :: answerParamSeparator;
					$this->operationSuccessfullMessage("confirmationDialog", 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

				} else {
					throw new ServiceException("email account for principal is not valid: " . $pEmail, ServiceException :: INVALID_ARGUMENT);
				}
				break;
//			case "toggleEmailAlert" :
//				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
//					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
//
//				$configS = $this->getConfigurationContext();
//				if ($configS->getParameter($p, $exec->getCrtModule(), "Notification_enable") != "1" || $configS->getParameter($p, $exec->getCrtModule(), "Notification_enableDynamicSubscription") != "1")
//					throw new ServiceException("Notification_enableDynamicSubscription is disabled", ServiceException :: FORBIDDEN);
//
//				$groupAS = ServiceProvider :: getGroupAdminService();
//				$addToNotification = $exec->getCrtParameters(0);
//				$groupId = $exec->getCrtParameters(1);
//
//				if ($addToNotification) {
//					$groupAS->addToEmailNotification($p, $groupId, $p->getValueInGeneralContext("email"));
//				} else {
//					$groupAS->eraseFromEmailNotification($p, $groupId, $p->getValueInGeneralContext("email"));
//				}
//
//				break;
				//		case "setEmailAccountForPFromEditEmailAlertForm":
				//			$setEmailAccountForPFromEditEmailAlertForm = true;
				//		case "setEmailAccountForP":
				//			if(ServiceProvider::getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction()." need login", AuthenticationServiceException::FORBIDDEN_MINIMAL_PRINCIPAL);
				//
				//			$emailAccountForP = $exec->getCrtParameters(0);
				//
				//			//control of validity of this emailAccount:
				//			if(validateEmail($emailAccountForP)){
				//				$p->setValueInGeneralContext("email", $emailAccountForP);
				//				//persist context in DB;
				//				$this->persistMainPrincipalSessionContext($p, $exec);
				//				$exec->addJsCode(" userManageAlertEmailsInputValue = '".$emailAccountForP."'; ");
				//				if($emailAccountForP != null && $setEmailAccountForPFromEditEmailAlertForm){
				//					//add the code to select each group with this email.
				//					$groupAS = ServiceProvider::getGroupAdminService();
				//					$gExp = 'module = "'.$exec->getCrtModule()->getModuleName().'" AND emailNotification LIKE %'.$emailAccountForP.'%';
				//					$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
				//					$gExp = $parser->createLogExpFromString($gExp);
				//					$jsCode = " $('#userManageAlertEmailsMenu li').removeClass('selected').removeClass('selectedHerited'); ";
				//					$groupList = GroupListSelectGroupInAlertMenuImpl::createInstance();
				////					$groupAS->getSelectedGroupsWithoutDetail($p, $gExp, $groupList);
				//					$lf = ListFilter::createInstance();
				//					$lf->setFieldSelectorLogExp($gExp);
				//					$lf->setFieldSelectorList($groupAS->getFieldSelectorListForGroupWithoutDetail());
				//					$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupList, $lf);
				//					//$groupAS->getSelectedGroups($p, $lf, $groupList);
				//					$jsCode .= $groupList->getJsCode();
				//					$jsCode .= " ";
				//					$jsCode .= " $('#userManageAlertEmailsMenu li.root').scrollTop(0); if($('#userManageAlertEmailsMenu li.selected:first').lenght>0) { $('#userManageAlertEmailsMenu li.root').scrollTop($('#userManageAlertEmailsMenu li.selected:first').offset().top-$('#userManageAlertEmailsMenu li.root').offset().top-50); } ";
				//					$exec->addJsCode($jsCode);
				//				}
				//			} else {
				//				//invalid email
				//				if($setEmailAccountForPFromEditEmailAlertForm){
				//					//add the code to correct the email
				//					$transS = ServiceProvider::getTranslationService();
				//					echo ExecutionServiceImpl::answerRequestSeparator;
				//					echo "confirmationDialog";
				//					echo ExecutionServiceImpl::answerParamSeparator;
				//					$this->openAsMessage(
				//						"confirmationDialog",
				//						350,
				//						$transS->t($p, "invalidEmail"),
				//						$transS->t($p, "emailForEditEmailAlertNeedsToBeValidOrNull"),
				//						"actOnCloseDialog('confirmationDialog');"
				//						);
				//					$exec->addJsCode("setTimeout(function(){ $('#confirmationDialog').parent('.ui-dialog').fadeOut(1000).one('mouseover', function(){ $(this).stop().css('opacity','1'); }); }, 600);");
				//					break;
				//				}
				//			}
				//			break;
			case "giveFeedback" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				if (!isset ($configS))
					$configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

				$totalWidth = (string)$configS->getParameter($p, $exec->getCrtModule(), "sendFeedbackTotalWidth");
				$labelWidth = (string)$configS->getParameter($p, $exec->getCrtModule(), "sendFeedbackLabelWidth");
				if(!$totalWidth) $totalWidth = 450;
				if(!$labelWidth) $labelWidth = 150;

				if ($exec->getCrtParameters(0) == "element") {
					if ($configS->getParameter($p, $exec->getCrtModule(), "FeedbackOnElement_enable") != "1")
						throw new ServiceException("Module " . $exec->getCrtModule()->getModuleName() . " is not configured to send feedbacks on element.", ServiceException :: FORBIDDEN);
					//feedback on an element
					$feedbackRec = $this->createActivityRecordForForm($p, Activity :: createInstance("feedbackOnElement"), $exec->getCrtModule());

					$el = $this->createElementForForm($p, $exec->getCrtModule(), $exec->getCrtParameters(1));
					ServiceProvider :: getElementService()->fillElement($p, $el);
					$feedbackRec->attachRecord($el);

					$this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList")->setCrtSelectedItem($el->getId());
				} else {
					if ($configS->getParameter($p, $exec->getCrtModule(), "FeedbackOnSystem_enable") != "1"){
						throw new ServiceException("Module " . $exec->getCrtModule()->getModuleName() . " is not configured to send feedbacks on system.", ServiceException :: FORBIDDEN);
					}
					//feedback on no selected element --> on the system
					$feedbackRec = $this->createActivityRecordForForm($p, Activity :: createInstance("feedbackOnSystem"), $exec->getCrtModule());
				}

//				if ($p->isPlayingRole()) {
//					$from = "";
//					if ($p->getRealUser()->getWigiiNamespace()->getWigiiNamespaceName() != null)
//						$from .= $p->getRealUser()->getWigiiNamespace()->getWigiiNamespaceName() . " : ";
//					$from .= $p->getRealUser()->getUsername();
//					$from .= ", ";
//					$from .= $p->getUsername();
//				} else {
//					$from = "";
//					if ($p->getAttachedUser()->getWigiiNamespace()->getWigiiNamespaceName() != null)
//						$from .= $p->getAttachedUser()->getWigiiNamespace()->getWigiiNamespaceName() . " : ";
//					$from .= $p->getAttachedUser()->getUsername();
//				}
//				$from .= ", " . $transS->t($p, $exec->getCrtModule()->getModuleName());
//				$feedbackRec->setFieldValue($from, "feedback_from");
//				$feedbackRec->setFieldValue($p->getValueInGeneralContext("email"), "feedback_email");
				//			if($p->getEmailAccount()!= null){
				//				$feedbackRec->getWigiiBag()->setReadonly(true, "feedback_email");
				//			}

				$action = $exec->getCrtRequest();
				$form = $this->createFeedbackFormExecutor($feedbackRec, "giveFeedback_form", $action);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);
				//			$form->setDottedLineSeparatorBetweenField(true);



				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				$form->ResolveForm($p, $exec, $state);

				break;

			//unsubscribe emails is the black list feature, do not mix with the usual unsubscribeEmail that the email owner would do
			case "unsubscribeEmails" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				if (!isset ($configS))
					$configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

				$unsuscribeEmailFields = $this->canCrtModuleUnsubscribeEmails($exec->getCrtModule());
				$unsubscribeEmailsGroupId = (string) $configS->getParameter($p, $exec->getCrtModule(), "UnsubscribeEmailsFolder");

				if($unsubscribeEmailsGroupId == null){
					$this->openAsMessage($exec->getIdAnswer(), 300, null, "UnsubscribeEmailsFolder parameter is empty.");
					break;
				}
				if(!$unsuscribeEmailFields){
					$this->openAsMessage($exec->getIdAnswer(), 300, null, "UnsubscribeEmailFields is empty.");
					break;
				}

				$temp = array();
				foreach($unsuscribeEmailFields as $emailField){
					$temp[$emailField->getName()] = $emailField->getName();
				}
				$unsuscribeEmailFields = $temp;

				if ($exec->getCrtParameters() == null) {
					$totalWidth = 300;
					//ask for confirmation
					?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/32x32/status/not-known.png";?>" style="float:left;margin:5px 15px 15px 15px;" /><?

					echo '<div style="width: 100%; max-width:'.($totalWidth-100).'px;margin:5px;float:left;">'. $transS->t($p, "areYouSureUnsubscribeEmails").'</div><div class="clear"></div>';
					$this->openAsDialogForm($exec->getIdAnswer(), $totalWidth, "update('elementDialog/" .
					$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/unsubscribeEmails/doUnsubscribeEmails');", $transS->t($p, "unsubscribeEmails"), $transS->t($p, "ok"), $transS->t($p, "cancel"));
				} elseif($exec->getCrtParameters(0) == "doUnsubscribeEmails") {

					//lookup for all the unsubscribed emails
					$wigiiBag = WigiiBagExtractUnsubscribeEmailsImpl :: createInstance();
					$lf = ListFilter::createInstance();
					$fl = FieldSelectorListArrayWebImpl :: createInstance(false);
					$fslDependencies = FieldSelectorListArrayWebImpl :: createInstance(false);
					$completeFieldList = FieldListArrayImpl :: createInstance(true, true);
					$configS->getFields($p, $exec->getCrtModule(), null, $completeFieldList);
					foreach($completeFieldList->getListIterator() as $field){
						if($unsuscribeEmailFields[$field->getFieldName()]){
							$fl->addFieldSelector($field->getFieldName(), "value");
							$fl->addFieldSelector($field->getFieldName(), "proofStatus");
							$fl->addFieldSelector($field->getFieldName(), "externalAccessLevel");
							$fl->addFieldSelector($field->getFieldName(), "proof");
						} else if($field->isCalculated()){
							$fslDependencies->reset();
							$field->getFuncExpDependencies($fslDependencies);
							//check if this calculated field include some of the email fields
							$dependent = false;
							foreach($unsuscribeEmailFields as $unsubFieldName){
								if($fslDependencies->containsField($unsubFieldName)){
									$dependent = true;
									break;
								}
							}
							if($dependent){
								$fl->addFieldSelector($field->getFieldName());
								$fl->mergeFieldSelectorList($fslDependencies);
							}
						}
					}
					$lf->setFieldSelectorList($fl);
					$groupLogExp = TechnicalServiceProvider::getFieldSelectorLogExpParser()->createLogExpFromString("ING(id IN (".$unsubscribeEmailsGroupId."))");
					$nbRow = $elS->fillWigiiBagWithSelectedElementsInGroups($this->getRootPrincipal(), $groupLogExp, $wigiiBag, $lf);

					$unsubscribeEmails = $wigiiBag->getEmails();
					$unsubscribeEmails = array_combine($unsubscribeEmails, $unsubscribeEmails);

//					eput($unsubscribeEmails);

					//lookup current selected elements matching those emails
					$elementPAList = ElementPAdvancedListArrayImpl :: createInstance();
					$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
					$mlc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "multipleElementList");
					$mlc->resetFetchCriteria($p, $this);
					//add fieldLogExp to match current emails
					$logExp = LogExp::createOrExp();
					foreach($fl->getListIterator() as $fs){
						if($fs->getSubFieldName()!="value") continue;
						$logExp->addOperand(LogExp::createInExp($fs, $unsubscribeEmails));
					}
					$mlc->setFieldSelectorList($fl);
					$mlc->setFieldSelectorLogExp($logExp);
					$mlc->setConfigGroupList($configS->getRootGroupsInModule($p, $exec->getCrtModule()));
					$nbOfItems = $elS->getSelectedElements($p, $lc->getMultipleSelection(), $elementPAList, $mlc);

					//set status 2 (delete) for each of the emails matching
					$nbOfEmailAddresses = 0;
					if($nbOfItems!=0){
						$unsubscribedInfo = $elS->getUnsubscribeEmailDeprectaedInfo($p);
						foreach($elementPAList->getListIterator() as $elementId=>$elementP){
							$element = $elementP->getDbEntity();
							foreach($unsuscribeEmailFields as $fieldname){
								$value = $element->getFieldValue($fieldname, "value");
								if($unsubscribeEmails[$value]){
									$element->setFieldValue(null, $fieldname, "value");
									$element->setFieldValue(Emails::PROOF_STATUS_DELETED, $fieldname, "proofStatus");
									$element->setFieldValue($value."<br />".$unsubscribedInfo, $fieldname, "proof");
									$element->setFieldValue(Emails::EXTERNAL_ACCESS_STOP, $fieldname, "externalAccessLevel");
									$nbOfEmailAddresses++;
								}
							}
//							$element->setState_deprecated(true);
//							$element->setArrayState_deprecatedInfo($unsubscribedInfo);
						}

						//update the list in DB
						$fl->addElementAttributeSelector("state_deprecated");
						$fl->addElementAttributeSelector("state_deprecatedInfo");

						//do the autocalc on the list
						$evaluatorClassName = (string)$configS->getParameter($p, $exec->getCrtModule(), "Element_evaluator");
						$evaluator = ServiceProvider::getElementEvaluator($p, $evaluatorClassName);
						$elS->evaluateElementList($p, $elementPAList, $evaluator);

						$elS->updateMultipleElement($this->getRootPrincipal(), $p, $elementPAList, $fl);


						//remove cache
						$exec->invalidCache($p, 'elementDialog');
						$exec->invalidCache($p, 'moduleView');
						//update the list in GUI
						$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");

					}

					//send operation successfull
					$totalWidth = 550;
					$expl = $nbOfItems." ".$transS->t($p, "unsubscribeEmailsItemsResult");
					$expl .= '<br />';
					$expl .= $nbOfEmailAddresses." ".$transS->t($p, "unsubscribeEmailsAddressesResult");

					$this->openAsMessage($exec->getIdAnswer(), $totalWidth, $transS->t($p, "operationDoneSuccessfully"), $expl, null, $transS->t($p, "ok"), null, null, "done");

				} else {
					throw new ServiceException("unsubscribeEmails unknown request.", ServiceException :: UNSUPPORTED_OPERATION);
				}
				break;
			case "Emailing" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				if (!isset ($configS))
					$configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

				$totalWidth = (string)$configS->getParameter($p, $exec->getCrtModule(), "sendEmailTotalWidth");
				$labelWidth = (string)$configS->getParameter($p, $exec->getCrtModule(), "sendEmailLabelWidth");
				if(!$totalWidth) $totalWidth = 650;
				if(!$labelWidth) $labelWidth = 200;

				//Emailing is independant of the module

				$EmailingRec = $this->createActivityRecordForForm($p, Activity :: createInstance("Emailing"), null);

				//if parameters are setted, then set them to it:
				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "Emailing");
				if ($exec->getCrtParameters() != null) {
					//fill emails from list
					$groupIds = $exec->getCrtParameters();
					$groupList = GroupListAdvancedImpl :: createInstance(false);
					$groupAS->getSelectedGroups($p, $groupAS->getListFilterForSelectGroupWithoutDetail($groupIds), $groupList);
					$lc->resetFetchCriteria($p, $this);
					$lc->setGroupPList($groupList->getRootGroups(), true);
				} else {
					//else take the elementListContext
					$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
					//fput($elementListContext->getGroupPList());
					if ($elementListContext->getMultipleSelection() != null) {
						$lc->resetFetchCriteria($p, $this);
						//add groupList on all
						$groupList = GroupListAdvancedImpl :: createInstance(false);
						$lf = ListFilter :: createInstance();
						$lf->setFieldSelectorList(ServiceProvider :: getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail());
						$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupList, $lf);
						$lc->setGroupPList($groupList->getRootGroups(), true);
						$lc->setConfigGroupList($elementListContext->getConfigGroupList());
						//make the configS context to what it is to prevent error when multiple configurations
						$configS->setGroupPList($p, $exec->getCrtModule(), $elementListContext->getConfigGroupList(), $configS->doesGroupListIncludeChildren($p, $exec->getCrtModule()));
						//add the multipleSelection criterias in the LogExp
						$lc->addLogExpOnMultipleSelection($elementListContext->getMultipleSelection());
					} else {
						$lc->matchFetchCriteria($elementListContext);
					}
				}
				//force emailing context to take sub folders
				if(!$lc->doesGroupListIncludeChildren()) $lc->setGroupPList($lc->getGroupPList(), true);
				list ($emailLabels, $fieldList, $elementIds, $emails) = $this->getAllEmailsInListView($p, $exec, $lc, $_POST["action"] != null);

				//the to email is filled in in the doRenderForm
				//$EmailingRec->setFieldValue(implode(", ", array_keys($emails["allFields"])), "to_email");
				$EmailingRec->setFieldValue($p->getValueInGeneralContext("email"), "from_email");

				$action = $exec->getCrtRequest();
				$form = $this->createEmailingFormExecutor($EmailingRec, "Emailing_form", $action, $elementIds);
				$form->setEmailLabels($emailLabels);
				$form->setFieldList($fieldList);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);
				$form->setEmails($emails);
				//			$form->setDottedLineSeparatorBetweenField(true);

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				$form->ResolveForm($p, $exec, $state);

				break;
			case "ExportDownload" :
			case "Downloading" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				if (!isset ($configS))
					$configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();
				if (!isset ($groupAS))
					$groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

				//parameters in case of Donwloading is to limit the groups in which we want to look in
				//parameters in case of ExportDownload can be a date in format: yyyy-mm-dd hh:mm:ss and will limit the downloads only to files newer than this particular date.

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "Downloading");
				if ($exec->getCrtParameters() != null && $exec->getCrtAction()=="Downloading") {
					$groupIds = $exec->getCrtParameters();
					$groupList = GroupListAdvancedImpl :: createInstance(false);
					$groupAS->getSelectedGroups($p, $groupAS->getListFilterForSelectGroupWithoutDetail($groupIds), $groupList);
					$lc->resetFetchCriteria($p, $this);
					$lc->setGroupPList($groupList->getRootGroups(), true);
				} else {
					//else take the elementListContext
					$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
					//fput($elementListContext->getGroupPList());
					if ($elementListContext->getMultipleSelection() != null) {
						$lc->resetFetchCriteria($p, $this);
						//add groupList on all
						$groupList = GroupListAdvancedImpl :: createInstance(false);
						$lf = ListFilter :: createInstance();
						$lf->setFieldSelectorList(ServiceProvider :: getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail());
						$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupList, $lf);
						$lc->setGroupPList($groupList->getRootGroups(), true);
						$lc->setConfigGroupList($elementListContext->getConfigGroupList());
						//add the multipleSelection criterias in the LogExp
						$lc->addLogExpOnMultipleSelection($elementListContext->getMultipleSelection());
					} else {
						$lc->matchFetchCriteria($elementListContext);
					}
				}

				list ($elementIds, $files) = $this->getAllFilesInListView($p, $exec, $lc);

				if(count($files)>0){
					//if exportDownload then export excel standard but with links
					if($exec->getCrtAction()=="ExportDownload"){
						$fb = FormBag::createInstance();
						$fb->setValue("excelTitle", null, null, "excelGrouping");
						$fb->setValue(true, null, null, "localLinks");
						ServiceProvider::getSessionAdminService()->storeData(ActivityRecord::createInstance("exportExcel", $exec->getCrtModule()), "exportRecordWigiiBag", $fb);
						$path = SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/exportAndDownload/exportExcel/";
						?><iframe src="<?=$path;?>" id="download-iframe-exportIndex" style="display:none;" /><?
					}
					$downloadFileArray = array();
					$downloadId = 0;
					echo "<i>".$transS->t($p, "clicLinksIfNotDownloading")."</i><br /><br />";

					$atLeastOneFile = false;
					foreach($files as $fieldName=>$ffiles){
						$src = SITE_ROOT.$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/download/";
						?><div id="download-pot-<?=$fieldName;?>"><?
						foreach($ffiles as $elementId=>$dfile){
							if($exec->getCrtParameters(0) && $exec->getCrtParameters(0) > $dfile["date"]) continue; //don't download files note changed from this particular date
							$path = $src.$elementId."/".$fieldName;
							if($exec->getCrtAction()=="ExportDownload"){
								$path .= "/exportDownload";
							}
							?><iframe src="<?=$path;?>" id="download-iframe<?=$downloadId++?>" style="display:none;" /><?
							$downloadFileArray[] = str_replace('//', '\/\/', $path);
							if($exec->getCrtAction()=="Downloading"){
								?><a class="H L" href="<?=$path;?>"><?=$dfile["name"].$dfile["type"]?></a><br /><?
							} else {
								?><a class="H L" href="<?=$path;?>"><?=$elementId."-".$fieldName."-".stripAccents($dfile["name"]).$dfile["type"]."";?></a><br /><?
							}
							$atLeastOneFile = true;
						}
						?></div><?
					}
					if($exec->getCrtParameters(0) && !$atLeastOneFile){
						echo $transS->t($p, "noFileChangesAfterThisDate")." : ".$exec->getCrtParameters(0);
					}
					$this->openAsDialog(
						$exec->getIdAnswer(), 300,
						$transS->t($p, "downloadingElementsTitle"), "$('#".$exec->getIdAnswer()."').html('');");
					$exec->addJsCode("$('#ui-dialog-title-".$exec->getIdAnswer()."').css('font-size', 'large').parent().css('background-color', '#93A4C1');");
				} else {
					$this->openAsMessage(
						$exec->getIdAnswer(), 300,
						$transS->t($p, "noFilesFoundToDownload"), "");
				}

				break;
			case "changePassword" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				$totalWidth = 450;
				$labelWidth = 250;
				//changePassword is independant of the module

				$changePasswordRec = $this->createActivityRecordForForm($p, Activity :: createInstance("changePassword"), null);

				$action = $exec->getCrtRequest();
				if(!$exec->getIsUpdating()){
					$action = "mainDiv/".$action; //add the container, because the action is done through an update url
				}
				$form = $this->createChangePasswordFormExecutor($changePasswordRec, "changePassword_form", $action);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);
				//			$form->setDottedLineSeparatorBetweenField(true);
                $domId = 'changePasswordDialog';
                $exec->addJsCode('' .
                    'myPosition = dialogPos["' . $domId . '"]; if(myPosition == null){ myPosition = { my : "center", at: "center" }; }' .
                    'if($("#' . $domId . '").is(":ui-dialog")) { $("#' . $domId . '").dialog("destroy"); } $("#' . $domId . '").dialog({' .
                    'title: "' . $dialogTitle . '", width:' . ($width + 17) . ', position: myPosition,' .
                    'dragStop: function(event, ui){ dialogPos["' . $domId . '"] = { my : "left top", at: "left+"+$(this).parent().offset().left+" top+"+$(this).parent().offset().top }; },' .
                    'beforeClose: function(){ ' . $closeJsCode . ' actOnCloseDialog("' . $domId . '"); $(this).dialog("destroy"); $(window).scrollTop(0); },' .
                    'closeOnEscape: true, resizable:false' .
                    '}).dialog("moveToTop")'.
                    '; '.
                    '
            aria = "[aria-describedBy=\"'.$domId .'\"]";
            
            width = "450";
		        
            margin = (($(window).width()-width)/2);
                  $(aria).css("left", margin+"px");
                  $(aria).css("width", "100%");
                  $(aria).css("max-width", width);
                  
                  
                  if($(window).width() < width){
                        $(aria).css("left", "0px");
                        $(aria).css("top", "0px");
                        $(aria).css("width", "100%");
                        $(aria).css("max-width", width);
                    }else{
                        margin = (($(window).width()-width)/2);
                        $(aria).css("left", margin+"px");
                    }
                  
            $(window).resize(function(){
                    if($(window).width() < width){
                        $(aria).css("left", "0px");
                        $(aria).css("top", "0px");
                        $(aria).css("width", "100%");
                        $(aria).css("max-width", width);
                    }else{
                        margin = (($(window).width()-width)/2);
                        $(aria).css("left", margin+"px");
                        $(aria).css("width", width);
                    }
                 });');

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				$form->ResolveForm($p, $exec, $state);

				break;
			case "importElementIn" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($groupAS)) $groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($configS)) $configS = $this->getConfigurationContext();

				// Medair (CWE) 05.12.2017 rejects CSV import if CSVImport_enableExp = 0
				if($this->evaluateConfigParameter($p,$exec,(string)$configS->getParameter($p, $exec->getCrtModule(), 'CSVImport_enableExp')) === '0')
				    throw new AuthorizationServiceException("CSV Import is not allowed", AuthorizationServiceException::UNAUTHORIZED);
				    
				$totalWidth = 450;
				$labelWidth = 200;
				    
				//the dialog box parameters is independant of the module
				$importToRec = $this->createActivityRecordForForm($p, Activity :: createInstance("importElementIn"), null);

				$action = $exec->getCrtRequest();
				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				if ($lc->getGroupPList() != null && !$lc->getGroupPList()->isEmpty()) {
					$crtSelectedGroupP = reset($lc->getGroupPList()->getListIterator());
					//				$crtSelectedGroupP = ServiceProvider::getGroupAdminService()->getGroup($p, $crtSelectedGroup->getId());
				}
				$form = $this->createImportElementInFormExecutor($importToRec, "importElementIn_form", $action, $this->getRootPrincipal(), $crtSelectedGroupP);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);
				//			$form->setDottedLineSeparatorBetweenField(true);

				$inGroupIdF = $importToRec->getFieldList()->getField("inGroupId");
				$groupPTreeArrayImpl = GroupPListTreeArrayImpl :: createInstance();
				$inGroupModuleXml = '<inGroupId type="Attributs">';
				//get all group, only admin
				$groupPTreeArrayImpl->reset();
				$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupPTreeArrayImpl);
				$selectOptionGroupTree = SelectOptionForGroupTreeRenderer :: createInstance($p->getWigiiNamespace()->getWigiiNamespaceName(), "s");
				$inGroupModuleXml .= $groupPTreeArrayImpl->visitInDepth($selectOptionGroupTree);
				$inGroupModuleXml .= $selectOptionGroupTree->getResult();
				$inGroupModuleXml .= '</inGroupId>';
				$inGroupIdF->setXml(simplexml_load_string($inGroupModuleXml));

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				$form->ResolveForm($p, $exec, $state);

				break;
			case "updateElementIn" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($groupAS)) $groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($configS)) $configS = $this->getConfigurationContext();
				if (!isset ($transS)) $transS = ServiceProvider :: getTranslationService();

				// Medair (CWE) 05.12.2017 rejects CSV update if CSVUpdate_enableExp = 0
				if($this->evaluateConfigParameter($p,$exec,(string)$configS->getParameter($p, $exec->getCrtModule(), 'CSVUpdate_enableExp')) === '0')
				    throw new AuthorizationServiceException("CSV Update is not allowed", AuthorizationServiceException::UNAUTHORIZED);
				    
				$totalWidth = 450;
				$labelWidth = 200;

				//the dialog box parameters is independant of the module
				$updateToRec = $this->createActivityRecordForForm($p, Activity :: createInstance("updateElementIn"), null);

				$action = $exec->getCrtRequest();
				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");

				//define group list in which we can import
				$inGroupIdF = $updateToRec->getFieldList()->getField("updateInGroupId");
				$groupPTreeArrayImpl = GroupPListTreeArrayImpl :: createInstance();
				$inGroupModuleXml = '<updateInGroupId type="Attributs">';
				$inGroupModuleXml .= '<attribute><label>--' . $transS->t($p, "rootGroup") . '--</label></attribute>';
				//get all group, only admin
				$groupPTreeArrayImpl->reset();
				$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupPTreeArrayImpl, $groupAS->getListFilterForSelectGroupWithoutDetail());
				$selectOptionGroupTree = SelectOptionForGroupTreeRenderer :: createInstance($p->getWigiiNamespace()->getWigiiNamespaceName(), "s");
				$inGroupModuleXml .= $groupPTreeArrayImpl->visitInDepth($selectOptionGroupTree);
				$inGroupModuleXml .= $selectOptionGroupTree->getResult();
				$inGroupModuleXml .= '</updateInGroupId>';
				$inGroupIdF->setXml(simplexml_load_string($inGroupModuleXml));

				$form = $this->createUpdateElementInFormExecutor($updateToRec, "updateElementIn_form", $action, $this->getRootPrincipal(), $groupPTreeArrayImpl->getWriteGroupPList());
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				//define the list of fields we can use as key
				$fieldKeyF = $updateToRec->getFieldList()->getField("fieldKey");
				$fieldKeyXml = '<fieldKey type="Attributs">';
				//add element id
				$fieldKeyXml .= '<attribute>__element id<label>'.str_replace("&", "&amp;", $transS->t($p, "idOfElement")).' (__element id)</label></attribute>';
				$fieldXml = $configS->mf($p, $exec->getCrtModule());
				$allowedFieldForDuplicates = $lc->defineFieldsKeysForUpdate();
				$html2text = new Html2text();
				foreach($fieldXml->children() as $field){
					if(array_key_exists((string)$field['type'], $allowedFieldForDuplicates)){
						$label = $transS->t($p, $field->getName(), $field);
						$subField = $allowedFieldForDuplicates[(string)$field['type']];
						//clean label of any html
						$html2text->setHtml($label);$label = $html2text->getText();//$html2text->clear();
						$label .= ' ('.(string)$field->getName().($subField && $subField!="value" ? ' '.$subField : '').')';
						$fieldKeyXml .= '<attribute>'.(string)$field->getName().($subField ? ' '.$subField : '').'<label>'.$label.'</label></attribute>';
					}
				}
				$fieldKeyXml .= '</fieldKey>';
				$fieldKeyF->setXml(simplexml_load_string($fieldKeyXml));

				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);

				if($state == "start"){
					if ($lc->getGroupPList() != null && !$lc->getGroupPList()->isEmpty()) {
						if($lc->getGroupPList()->count()>1){
							//keep root group as default option
						} else {
							$crtSelectedGroupP = reset($lc->getGroupPList()->getListIterator());
							$form->getRecord()->setFieldValue($crtSelectedGroupP->getId(), "updateInGroupId");
						}
					}
				}
				$form->ResolveForm($p, $exec, $state);

				break;
			case "findDuplicatesIn" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($groupAS)) $groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($configS)) $configS = $this->getConfigurationContext();
				if (!isset ($transS)) $transS = ServiceProvider :: getTranslationService();
				$totalWidth = 450;
				$labelWidth = 200;

				//the dialog box parameters is independant of the module
				$updateToRec = $this->createActivityRecordForForm($p, Activity :: createInstance("findDuplicatesIn"), null);

				$action = $exec->getCrtRequest();
				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				if ($lc->getGroupPList() != null && !$lc->getGroupPList()->isEmpty()) {
					if($lc->getGroupPList()->count()>1){
						//keep root group as default option
					} else {
						$crtSelectedGroupP = reset($lc->getGroupPList()->getListIterator());
					}
				}
				$form = $this->createFindDuplicatesInFormExecutor($updateToRec, "findDuplicatesIn_form", $action, $this->getRootPrincipal(), $crtSelectedGroupP);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

//				//define group list in which we can import
//				$inGroupIdF = $updateToRec->getFieldList()->getField("finDuplicatesInGroupId");
//				$groupPTreeArrayImpl = GroupPListTreeArrayImpl :: createInstance();
//				$inGroupModuleXml = '<finDuplicatesInGroupId type="Attributs">';
//				$inGroupModuleXml .= '<attribute><label>--' . $transS->t($p, "rootGroup") . '--</label></attribute>';
//				//get all group, only admin
//				$groupPTreeArrayImpl->reset();
//				$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupPTreeArrayImpl);
//				$selectOptionGroupTree = SelectOptionForGroupTreeRenderer :: createInstance($p->getWigiiNamespace()->getWigiiNamespaceName(), "r");
//				$inGroupModuleXml .= $groupPTreeArrayImpl->visitInDepth($selectOptionGroupTree);
//				$inGroupModuleXml .= $selectOptionGroupTree->getResult();
//				$inGroupModuleXml .= '</finDuplicatesInGroupId>';
//				$inGroupIdF->setXml(simplexml_load_string($inGroupModuleXml));

				//define the list of fields we can use as key
				$fieldKeyF = $updateToRec->getFieldList()->getField("fieldKey");
				$fieldKeyXml = '<fieldKey type="Attributs">';
				$fieldXml = $configS->mf($p, $exec->getCrtModule());
				$allowedFieldForDuplicates = $lc->defineFieldsToFindDuplicates();
				$emailField = $this->canCrtModuleEmailing($exec->getCrtModule());
				if($emailField){
					$emailField = (string)$emailField[0]->getName();
				}
				$html2text = new Html2text();
				foreach($fieldXml->children() as $field){
					if(array_key_exists((string)$field['type'], $allowedFieldForDuplicates)){
						$label = $transS->t($p, $field->getName(), $field);
						//clean label of any html
						$html2text->setHtml($label);$label = $html2text->getText();//$html2text->clear();
						$subField = $allowedFieldForDuplicates[(string)$field['type']];
						$label .= ' ('.(string)$field->getName().($subField && $subField!="value" ? ' '.$subField : '').')';
						$fieldKeyXml .= '<attribute'.($emailField && $emailField == (string)$field->getName() ? ' checked="1"' : '').'>'.(string)$field->getName().' '.$subField.'<label>'.$label.'</label></attribute>';
					}
				}
				$fieldKeyXml .= '</fieldKey>';
				$fieldKeyF->setXml(simplexml_load_string($fieldKeyXml));


				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				$form->ResolveForm($p, $exec, $state);

				break;
			case "updateElementsInList":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				//add logExp on element
				$elementIds = $exec->getCrtParameters(0);
				$elementIds = explode("-", $elementIds);
				$logExp = LogExp::createInExp(FieldSelector::createElementAttributeSelector("id"), $elementIds);
				$originalLogExp = $lc->getFieldSelectorLogExp();
				//$lc->setDesiredPageNumber(1); //only one result is found
				$lc->resetPagination();

				$lc->setFieldSelectorLogExp($logExp);
				switch ($lc->getCrtView()){
					case "list":
						list ($total, $nbRow) = $this->getAllElementsInListView($p, $exec, $lc, true, true);
						break;
					case "blog":
						list ($total, $nbRow) = $this->getAllElementsInBlogView($p, $exec, $lc, true, true);
						break;
				}

				//add back the original logExp
				$lc->setFieldSelectorLogExp($originalLogExp);

				$exec->addJsCode("$('#moduleView tr.S').removeClass('S');");

				break;
			case "updateElementInList":
			case "addElementInList":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				//add logExp on element
				$elementId = $exec->getCrtParameters(0);
				$logExp = LogExp::createEqualExp(FieldSelector::createElementAttributeSelector("id"), $elementId);
				$originalLogExp = $lc->getFieldSelectorLogExp();
				//$lc->setDesiredPageNumber(1); //only one result is found
				$lc->resetPagination();

				$lc->setFieldSelectorLogExp($logExp);

				$lc->setFieldSelectorLogExp($logExp);
				switch ($lc->getCrtView()){
					case "list":
						list ($total, $nbRow) = $this->getAllElementsInListView($p, $exec, $lc, true, ($exec->getCrtAction()=="updateElementInList"), 1);
						break;
					case "blog":
						list ($total, $nbRow) = $this->getAllElementsInBlogView($p, $exec, $lc, true, ($exec->getCrtAction()=="updateElementInList"), 1);
						break;
				}


				//add back the original logExp
				$lc->setFieldSelectorLogExp($originalLogExp);

				//select the element
				$exec->addJsCode("$('#moduleView tr.S').removeClass('S');$('#moduleView #row_".$elementId."').addClass('S');");

				break;
			case "getNextElementInList" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				$lc->setDesiredPageNumber(addslashes($_POST["page"]));

				list ($total, $nbRow) = $this->getAllElementsInListView($p, $exec, $lc, true, false, addslashes($_POST["page"]));

				//add the information of how new data is loaded:
?>_X_NBNEWROWS_X_<?=$nbRow;?>_X_NBNEWROWS_X_<?

				$exec->flushJsCode();

				break;
			case "getNextElementInBlog" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList"); //keep the same context than the list, ordering, filtering, group by is shared
				$lc->setDesiredPageNumber(addslashes($_POST["page"]));

				list ($total, $nbRow) = $this->getAllElementsInBlogView($p, $exec, $lc, true, false, addslashes($_POST["page"]));

				//add the information of how new data is loaded:
?>_X_NBNEWROWS_X_<?=$nbRow;?>_X_NBNEWROWS_X_<?

				$exec->flushJsCode();

				break;
			case "getNextElementInPreviewList" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				$elS = ServiceProvider::getElementService();
				$sessAS = ServiceProvider::getSessionAdminService();

				$page = addslashes($_POST["page"]);
				$elementId = addslashes($_POST["elementId"]);
				$previewListId = addslashes($_POST["previewListId"]);

				list($sessElementId, $linkName, $linkType, $listFilter, $elementIsBlocked, $query, $elementIsReadonly) = $sessAS->getData($elS, $previewListId."_".$exec->getCrtContext());
				if($sessElementId!=$elementId) throw new ServiceException("getNextElementInPreviewList elementId is not equal to stored context", ServiceException::DATA_INTEGRITY_ERROR);
				$listFilter->setDesiredPageNumber($page);

				$linkType = Links::linkTypeFromString($linkType);
				// link of type query
				if($linkType == Links::LINKS_TYPE_QUERY) {
					// loads element
					$element = $this->createElementForForm($p, $exec->getCrtModule(), $elementId);
					$elementP = $elS->fillElement($p, $element);

					// parses query and creates func exp
					$queryFx = str2fx($query);
					// gets func exp evaluator
					$evalFx = $this->getFuncExpEvaluator($p, $exec, $element);
					$querySource = null;
					try {
						// evaluates query and builds data source object
						$querySource = $evalFx->evaluateFuncExp($queryFx, $this);
						// frees evaluator
						$evalFx->freeMemory();
					}
					catch(Exception $e) {
						$evalFx->freeMemory();
						throw $e;
					}
					// updates list filter if set
					if($querySource instanceof ElementPListDataFlowConnector) {
						$querySourceLf = $querySource->getListFilter();
						if(isset($querySourceLf)) {
							// Medair (CWE) 08.01.2018: merge given fsl with existing one to preserve extra needed calculated fields dependencies
							if(!is_null($listFilter->getFieldSelectorList())) {
							    $querySourceFsl = $querySourceLf->getFieldSelectorList();
							    if($querySourceFsl instanceof FieldSelectorListArrayImpl) $querySourceFsl->mergeFieldSelectorList($listFilter->getFieldSelectorList());
							    else $querySourceLf->setFieldSelectorList($listFilter->getFieldSelectorList());
							}
							if(!is_null($listFilter->getFieldSortingKeyList())) $querySourceLf->setFieldSortingKeyList($listFilter->getFieldSortingKeyList());
							if($listFilter->getPageSize() > 0) {
								$querySourceLf->setPageSize($listFilter->getPageSize());
								$querySourceLf->setDesiredPageNumber($listFilter->getDesiredPageNumber());
							}
						}
						else $querySource->setListFilter($listFilter);
					}
					// executes data flow and builds html
					if(isset($querySource)) {
						$currentNamespace = $p->getWigiiNamespace();
						$adaptiveWigiiNamespace = $p->hasAdaptiveWigiiNamespace();
						$p->setAdaptiveWigiiNamespace(true);
						try {
							$nbRow = ServiceProvider::getDataFlowService()->processDataSource($p, $querySource, dfasl(
									dfas('ElementPListRowsForPreview',
											'setTrm', $this->createTRM(),
											'setP', $p,
											'setExec', $exec,
											'setConfigService', $this->getConfigurationContext(),
											'setFsl', $listFilter->getFieldSelectorList(),
											'setElementId', $element->getId(),
											'setLinkName', $linkName,
											'setLinkType', $linkType,
											'setElementIsBlocked', $elementIsBlocked,
											'setElementIsReadonly', $elementIsReadonly,
											'setPreviewListId', $previewListId,
											'setWidth', null,
											'setUpdateContentOnly', true)
							), false);
						}
						catch(ServiceException $se) {
							// if AuthorizationServiceException::FORBIDDEN then displays an empty table
							if($se->getWigiiRootException()->getCode() == AuthorizationServiceException::FORBIDDEN) {
								$nbRow = 0;
							}
							// else propagates exception
							else throw $se;
						}
						$total = $querySource->getListFilter()->getTotalNumberOfObjects();
						if(method_exists($querySource, 'freeMemory')) $querySource->freeMemory();
						if($adaptiveWigiiNamespace) $p->setAdaptiveWigiiNamespace(false);
						$p->bindToWigiiNamespace($currentNamespace);
					}
				}
				// else subitem or links
				else {
				    $fsl = $listFilter->getFieldSelectorList();
					$elementPList = ElementPListRowsForPreview::createInstance($this->createTRM(), $p, $exec, $this->getConfigurationContext(), $fsl, $sessElementId, $linkName, $elementIsBlocked, $previewListId, $linkType);
					$elementPList->setElementIsReadonly($elementIsReadonly);
					if($linkType == Links::LINKS_TYPE_SUBITEM) {
					    // Medair (CWE) 08.01.2018, adds calculated on fetch dependencies to fsl
				        $listFilter->setFieldSelectorList(ServiceProvider::getWigiiBPL()->buildFslForSubElementWithFxDependencies($p, $sessElementId, $linkName, $fsl));
						$nbRow = $elS->getSubElementsForField($p, $sessElementId, $linkName, $elementPList, $listFilter);
						$total = $listFilter->getTotalNumberOfObjects();
						$listFilter->setFieldSelectorList($fsl);/* restores original field selector list before serialization to session */
					}
					else {
						/* not implemented */
					}
				}

				//add the nb of new rows
				if($nbRow <= 0) $nbRow = 0;
				if($total <= 0) $total = 0;
?>_X_NBNEWROWS_X_<?=$nbRow;?>_X_NBNEWROWS_X_<?=$total;

				break;
			case "getCalendarEvents" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");

				//in calendar view, no need of pagination. The current dates are more important.
				$lc->resetPagination();
				$lc->resetMultipleSelection();
				$lc->setGroupByItemCurrentValue(null);

				header('Pragma: public');
				header('Cache-Control: max-age=0');
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
				header('Content-type: text/plain');

				//add the date filter
				echo "[";
				$nbRow = $this->getAllElementsInCalendarView($p, $exec, $lc, false);
				echo "]";
				break;
			case "CKEditor":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				$param = array();
				parse_str($_SERVER["REQUEST_URI"], $param);
				$callback = $param["CKEditorFuncNum"];

				if(false && $exec->getCrtParameters(1)=="browse"){
					//this is not available in the current implementation!!!
					$url = CLIENT_WEB_URL."lingos.jpg";
					?><script language="JavaScript" type="text/javascript"><?
					?>window.opener.CKEDITOR.tools.callFunction(<?php echo $callback; ?>,<?php echo $url;?>);<?
					?></script><?
				} else {
					$fileData = $_FILES["upload"];
					if($fileData == null) throw new ServiceException("file cannot be null", ServiceException::INVALID_PARAMETER);

					$ext = explode(".", $fileData["name"]);
					if(count($ext) > 1){
						$ext = end($ext);
						//prevent script extensions
						if(preg_match(Files::Script_Extensions,$ext)){
							$ext = "no".$ext.".txt";
						}
						$ext = ".".$ext;
					} else $ext = "";
					$uploadfile = $p->getWigiiNamespace()->getWigiiNamespaceName()."_".time().ipToStr($_SERVER["REMOTE_ADDR"]).$p->getUsername().substr(basename($fileData['name']), 0, 5).$ext;
					$uploadfile = preg_replace('/[^a-zA-Z0-9\.\-\_]/',"",$uploadfile);
					@mkdir(CLIENT_WEB_PATH."imageForHtmlEditor/");
					$uploadfile = CLIENT_WEB_PATH."imageForHtmlEditor/".$uploadfile;
					if (rename($fileData['tmp_name'], $uploadfile)) {
						$this->debugLogger()->write("move ".$fileData['tmp_name']." in ".$uploadfile);
						$old = umask(0000);
						if(chmod($uploadfile, 0666)){
							//$exec->debug("filemod is changed to 666.\n");
							$this->debugLogger()->write("filemod is changed to 666 for ".$uploadfile);
						}
						umask($old);
					} else {
						$this->executionSink()->log("!!!! problem in move_uploaded_file ".$fileData['tmp_name']." in ".$uploadfile." !!!!\n");
						throw new ServiceException("cannot move_uploaded_file ".$fileData['tmp_name']." in ".$uploadfile, ServiceException::UNEXPECTED_ERROR);
					}
					if(isImage("image/".str_replace(".", "", $ext))){
						$srcinfo = getimagesize($uploadfile);
						if($srcinfo[0] > 1000){
							cutImage($uploadfile, $uploadfile, 1000);
						}
					}

					$url = SITE_ROOT_forFileUrl.$uploadfile; //CLIENT_WEB_URL."camera_icon.png";
					$msg = "";
					if($param['responseType'] == 'json'){
						$output = array('uploaded' => 1,'fileName' => $fileData["tmp_name"],'url' => $url);
						$output = json_encode($output);
					} else{
						$output = '<html><body><script type="text/javascript">window.parent.CKEDITOR.tools.callFunction('.$callback.', "'.$url.'","'.$msg.'");</script></body></html>';
					}
					echo $output;
				}


				//eput($exec->getParameters());

				break;

			case "download" :

				switch ($exec->getCrtParameters(0)) {
					case "captcha" :
						$trm = $this->createDownloadCaptchaTRM();
						$trm->getCaptchaFile();
						exit;
					case "checkoutFile" :
						if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
							throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

						$elementId = $exec->getCrtParameters(1);
						$fieldName = $exec->getCrtParameters(2);

						$fe = $this->createFormExecutor();
						$fe->preventInjectionForm($p, $exec);
						$message = $_POST["message"];

						$elS = ServiceProvider :: getElementService();
						//get the element
						$element = Element :: createInstance($exec->getCrtModule());
						$element->setWigiiBag(FormBag :: createInstance());
						$element->setFieldList(FormFieldList :: createInstance($element->getWigiibag()));
						$element->setId($elementId);

						//we need to fetch the whole element because we potentialy don't know which field is linked
						//for the check message
						$elementP = ServiceProvider :: getElementService()->fillElement($p, $element);
						if ($elementP == null || $elementP->getRights() == null) {
							echo "No rights on element " . $elementId . " or dosen't exist.";
							exit ();
						}
						if($element->isState_blocked() || $elementP->isParentElementState_blocked()) throw new ServiceException("blockedElementsOperationImpossible", ServiceException::FORBIDDEN);

						$field = $element->getFieldList()->getField($fieldName);
						$fieldxml = $field->getXml();
						$checkFieldname = (string) $fieldxml["checkField"];

						try {
							$element->setState_locked(true);
							if (!$checkFieldname) {
								//lock the element
								$elS->setState_locked($p, $elementId, true, $message);
							} else {
								$checkField = $element->getFieldList()->getField($checkFieldname);
								//lock the element
								$elS->setState_locked($p, $elementId, true);
								//update the journal with the version changes
								$this->checkOutInField($p, $exec, $element, $field, $checkField, $message, true);
								$fsl = FieldSelectorListArrayImpl :: createInstance();
								$fsl->addFieldSelector($checkFieldname);
								$elS->updateElement($p, $element, $fsl);
							}
							$configS = $this->getConfigurationContext();
							$this->throwEvent()->updateElement(PWithElementWithGroupPList::createInstance($p, $element, ($configS->getGroupPList($p, $exec->getCrtModule())->count()==1 ? $configS->getGroupPList($p, $exec->getCrtModule()) : null)));

						} catch (AuthorizationServiceException $ase) {
							if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
								echo ExecutionServiceImpl :: answerRequestSeparator; //needed to display the message
								$this->displayLockMessage($p, $exec, null, "element", $element);
								break;
							}
						}
						if($exec->getIdAnswer()=="mainDiv"){
							//reload detail
							$exec->addRequests($exec->getIdAnswer()."/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/detail/".$elementId);
						} else {
							//update element in list
							$exec->addJsCode("updateElementInList('" . $element->getId() . "');");
	//						//update the list
	//						echo ExecutionServiceImpl :: answerRequestSeparator; //needed to be able to do next request
	//						$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView");
							//send file for download
							$exec->invalidCache($p, 'elementDialog', "selectElementDetail", "element/detail/" . $elementId);
						}
						$exec->addJsCode(" setVis('busyDiv', false); download('" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/download/" . $elementId . "/" . $fieldName . "'); ");
						break;
					case "moduleEditorZip" :
						if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
							throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
						if (!$exec->getCrtModule()->isAdminModule())
							throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);
						if (!$p->isWigiiNamespaceCreator())
							throw new ServiceException('zip config require wigiiNamespace creator rights', ServiceException :: FORBIDDEN);

						header('Pragma: public');
						header('Cache-Control: max-age=0');
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
						header('Content-type: ' . typeMime(".zip"));
						header('Content-Disposition: attachment; filename="'.CLIENT_NAME.date('_Ymd_His_').'config.zip"');

						$filename = TEMPORARYUPLOADEDFILE_path . $p->getExecutionId() . "moduleEditorZip" . $p->getMicroTime() . ".zip";
						$zip = new ZipArchive();
						if ($zip->open($filename, ZIPARCHIVE :: CREATE) !== TRUE) {
							exit ("cannot open <$filename>\n");
						}
						$dir = scandir(CLIENT_CONFIG_PATH, 0);
						if ($dir) {
							//the two first entry are . and ..
							$dir = array_slice($dir, 2);
							foreach ($dir as $file) {
								$zip->addFile(CLIENT_CONFIG_PATH . $file, $file);
							}
							$zip->close();
						}
						session_write_close();
						readfile($filename);
						unlink($filename);
						exit ();
					case "groupPortal" :
					case "groupHtmlContent" :
					case "groupXmlPublish" :
					case "groupSubscription" :
						if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
							throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
						if (!$exec->getCrtModule()->isAdminModule())
							throw new ServiceException('admin functions can only be access in Admin module', ServiceException :: FORBIDDEN);

						if (!isset ($groupAS))
							$groupAS = ServiceProvider :: getGroupAdminService();

						$fieldName = $exec->getCrtParameters(1);
						$groupId = $exec->getCrtParameters(2);
						$isThumbs = $exec->getCrtParameters(3) === "thumbs";

						$groupP = $groupAS->getGroup($p, $groupId);
						$group = $groupP->getGroup();

						$actRec = $this->createActivityRecordForForm($p, Activity :: createInstance($exec->getCrtParameters(0)), $exec->getCrtModule());

						switch ($exec->getCrtParameters(0)) {
							case "groupPortal" :
								$actRec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getPortal(), $actRec->getActivity());
								break;
							case "groupHtmlContent" :
								$actRec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getHtmlContent(), $actRec->getActivity());
								break;
							case "groupXmlPublish" :
								$actRec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getXmlPublish(), $actRec->getActivity());
								break;
							case "groupSubscription" :
								$actRec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getSubscription(), $actRec->getActivity());
								break;
						}

						$mime = $actRec->getFieldValue($fieldName, "mime");
						$fileName = $actRec->getFieldValue($fieldName, "name") . $actRec->getFieldValue($fieldName, "type");
						$type = $actRec->getFieldValue($fieldName, "type");
						$size = $actRec->getFieldValue($fieldName, "size");
						$path = $actRec->getFieldValue($fieldName, "path");
						$content = $actRec->getFieldValue($fieldName, "content");
						$path = FILES_PATH . $path;

						header('Pragma: public');
						header('Cache-Control: max-age=0');
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
						header('Content-type: ' . $mime);
						if (!$isThumbs && $size)
							header('Content-Length: ' . $size);
						header('Content-Disposition: attachment; filename="' . $fileName . '"');

						session_write_close();
						if ($isThumbs) {
							if (file_exists("./images/preview/prev$type.png")) {
								$path = "./images/preview/prev" . $type . ".png";
							} else {
								$path = "./images/preview/preview.jpg";
							}
							readfile($path);
						} else {
							//if file is empty, then content value can be empty
							if (!file_exists($path)) {
								echo $content;
							} else {
								readfile($path);
							}
						}

						exit;

						break;
					default :
						if ($this->doesPrincipalForDownloadRequestShouldBePublic()) {
							$this->resetUsePublicPrincipalForDownloadRequest();
							$p = $this->getPublicPrincipal();
						} else {
							if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
								throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
						}

						if (!isset ($transS))
							$transS = ServiceProvider :: getTranslationService();
						if(!isset($configS)) $configS = $this->getConfigurationContext();

						$elementId = $exec->getCrtParameters(0);
						$fieldName = $exec->getCrtParameters(1);
						$isThumbs = $exec->getCrtParameters(2) == "thumbs";
						$isIntegrated = $exec->getCrtParameters(2) == "integrated";
						$isUnzipForViewing = $exec->getCrtParameters(2) == "unzipForViewing";
						$isExportDownload = $exec->getCrtParameters(2) == "exportDownload";
						$isPreviousVersion = $exec->getCrtParameters(2) == "previousVersion";
						$isDeletePreviousVersion = $exec->getCrtParameters(2) == "deletePreviousVersion";
						if ($isPreviousVersion || $isDeletePreviousVersion) $previousVersionPrefix = $exec->getCrtParameters(3);
						if ($isUnzipForViewing)
							$deleteUnzipForViewing = $exec->getCrtParameters(3) == "delete";
						else
							$deleteUnzipForViewing = false;

						$element = Element :: createInstance($exec->getCrtModule());
						$element->setWigiiBag(FormBag :: createInstance());
						$element->setFieldList(FormFieldList :: createInstance($element->getWigiibag()));
						$element->setId($elementId);

						//set fieldSelectorList to fetch only this field, and include content and thumbnail subfield
						$fsl = FieldSelectorListArrayImpl :: createInstance();
						$fsl->addFieldSelector($fieldName, "mime");
						$fsl->addFieldSelector($fieldName, "name");
						$fsl->addFieldSelector($fieldName, "path");
						$fsl->addFieldSelector($fieldName, "size");
						$fsl->addFieldSelector($fieldName, "type");
						if ($isThumbs)
							$fsl->addFieldSelector($fieldName, "thumbnail");
						else {
							$fsl->addFieldSelector($fieldName, "textContent");
							$fsl->addFieldSelector($fieldName, "content");
						}

						$elementP = ServiceProvider :: getElementService()->fillElement($p, $element, $fsl);
						if ($elementP == null || $elementP->getRights() == null) {
							$this->displayNotFound();
							exit ();
						}

						//previousVersion download:
						if($isPreviousVersion){
							$dir = $this->createFormExecutor()->getHistoryDir($p, $exec, $element, $fieldName);
							$file = File::list_files($dir, "", $previousVersionPrefix);
							if($file == null) break;
							$fileName = $file[0];
							$path = $dir.$fileName;
							$mime = typeMime(".".end(explode(".", $fileName)));
						} else if($isDeletePreviousVersion){
							$dir = $this->createFormExecutor()->getHistoryDir($p, $exec, $element, $fieldName);
							$file = File::list_files($dir, "", $previousVersionPrefix);
							if($file == null) break;
							$fileName = $file[0];
							$path = $dir.$fileName;
							unlink($path);
							exit;
						} else {

							$field = $element->getFieldList()->getField($fieldName);
							$fieldXml = $field->getXml();

							$mime = $element->getFieldValue($fieldName, "mime");
							$fileName = $element->getFieldValue($fieldName, "name") . $element->getFieldValue($fieldName, "type");
							if($isExportDownload) $fileName = $element->getId()."-".$fieldName."-".stripAccents($fileName);
							$path = $element->getFieldValue($fieldName, "path");
							//if path is null, then put a dummy path to prevent the file_exist returning true because the folder of the filepath exists. this is in the case of Files with htmlArea=1
							if ($fieldXml["htmlArea"] == "1")
								$path = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
							$size = $element->getFieldValue($fieldName, "size");
							$type = $element->getFieldValue($fieldName, "type");
							$content = null;

							$storeFileContentIntoDatabase = $this->getConfigurationContext()->getParameter($p, null, "storeFileContentIntoDatabase") == "1";

							//even if not storeFileContentIntoDatabase we fetch the content to manage compatibility by switching file mode
							if ($isThumbs) {
								$content = $element->getFieldValue($fieldName, "thumbnail");
								$path = FILES_PATH . "tn_" . $path;
								
							}elseif(strstr($path, "box://")){
							
								$crtWigiiNamespaceUrl = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
								$crtModuleUrl = $exec->getCrtModule()->getModuleUrl();
								$id = str_replace("box://", "", $path);																							
								if($isIntegrated){
									$path = "$crtWigiiNamespaceUrl/$crtModuleUrl/box/preview/$id";
								}else{
									$path = "$crtWigiiNamespaceUrl/$crtModuleUrl/box/download/$id";								
								}
								// forwards PublicPrincipal delegation to box
								if(ServiceProvider::getAuthorizationService()->isPublicPrincipal($p)) $this->usePublicPrincipalForDownloadRequest();
								$exec->addRequests($path);
								break;
							} else {
								$this->throwEvent()->downloadFileFromElement(PWithElementWithFieldname :: createInstance($p, $element, $fieldName));
								if ($fieldXml["htmlArea"] == "1") {
									$content = $element->getFieldValue($fieldName, "textContent");
								} else {
									$content = $element->getFieldValue($fieldName, "content");
								}
								$path = FILES_PATH . $path;
							}

							//					eput($content);

							//unzipForViewing
							if ($isUnzipForViewing) {
								$beforeClose = null;
								$downloadPath = SITE_ROOT."useContext/".$exec->getCrtContext().ExecutionServiceImpl::requestSeparator . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/download/" . $elementId . "/" . $fieldName . '/integrated';
								switch ($type) {
									case ".doc":
									case ".docx":
									case ".dot":
									case ".dotx":
									case ".xls":
									case ".xlsx":
									case ".xlsm":
									case ".xlt":
									case ".xltx":
									case ".xltm":
									case ".pps":
									case ".ppsx":
									case ".ppsm":
									case ".ppt":
									case ".pptx":
									case ".pptm":
									case ".msg":
									case ".odt":
									case ".rtf":
									case ".db":
									case ".dbf":
										if($configS->getParameter($p, $exec->getCrtModule(), "useGoogleServiceToPreviewOfficeDocuments")!="1"){
											exit;
										}

										if ($deleteUnzipForViewing) {
											unlink(dirname($_SERVER["SCRIPT_FILENAME"]) . "/" . TEMPORARYUNZIPFORVIEWINGFILE_path . $exec->getCrtParameters(4));
											exit;
										}
										$crtTime = time();
										$publicFilename = $crtTime . md5($elementId . $fieldName).$type;
										$downloadPath = SITE_ROOT_forFileUrl . TEMPORARYUNZIPFORVIEWINGFILE_path . $publicFilename;
										//Cleaning of old Temporary unzip folder:
										$files = File :: list_files(TEMPORARYUNZIPFORVIEWINGFILE_path);
										if ($files == null) $files = array ();
										foreach ($files as $file) {
											//on recherche dans le nom du dossier le timestamp unix qui correpsond au
											//10 premier caractères du nom du foler
											$timeCreated = substr($file, 0, 10);
											//$exec->debug(put($timeUploded));
											if ($crtTime >= $timeCreated +TEMPORARYUNZIPFORVIEWINGFILE_lifetime) {
												//echo "remove:".$dirname;
												unlink(TEMPORARYUNZIPFORVIEWINGFILE_path.$file);
											}
										}
										copy(FILES_PATH.$element->getFieldValue($fieldName, "path"), TEMPORARYUNZIPFORVIEWINGFILE_path . $publicFilename);
										$downloadPath = (HTTPS_ON ? "https" : "http")."://docs.google.com/gview?url=".$downloadPath."&embedded=true";
										$beforeClose = "beforeClose: function(){ update('" . $exec->getCrtRequest() . "/delete/" . $publicFilename . "'); }";

										$type = "html";
										break;
									case ".pdf" :
										$type = "pdf";
										break;
									case ".pdf":
									case ".html":
									case ".htm":
									case ".svg":
									case ".txt":
									case ".csv":
									case ".sql":
									case ".config":
									case ".xml":
									case ".bat":
									case ".dat":
									case ".data":
									case ".jpg" :
									case ".jpeg" :
									case ".gif" :
									case ".png" :
									case ".bmp" :
										$type = "html";
										break;
									case ".zip" :
										$type = "html";
										if ($deleteUnzipForViewing) {
											deleteDirectory(dirname($_SERVER["SCRIPT_FILENAME"]) . "/" . TEMPORARYUNZIPFORVIEWINGFILE_path . $exec->getCrtParameters(4));
											exit;
										} else {
											$crtTime = time();
											$publicUrl = $crtTime . md5($elementId . $fieldName);
											$temporaryUnzipFolder = dirname($_SERVER["SCRIPT_FILENAME"]) . "/" . TEMPORARYUNZIPFORVIEWINGFILE_path;
											$folderPath = $temporaryUnzipFolder . '/' . $publicUrl;
											//Cleaning of old Temporary unzip folder:
											$dirs = File :: list_dirs($temporaryUnzipFolder);
											if ($dirs == null)
												$dirs = array ();
											foreach ($dirs as $dirname) {
												//on recherche dans le nom du dossier le timestamp unix qui correpsond au
												//10 premier caractères du nom du foler
												$timeCreated = substr($dirname, 0, 10);
												//$exec->debug(put($timeUploded));
												if ($crtTime >= $timeCreated +TEMPORARYUNZIPFORVIEWINGFILE_lifetime) {
													//echo "remove:".$dirname;
													deleteDirectory($temporaryUnzipFolder . $dirname, true);
												}
											}
											mkdir($folderPath);
											$zip = new ZipArchive();
											//if content stored in DB then first copy it on the HDD
											if (($content != null && $storeFileContentIntoDatabase) || ($content != null && !file_exists($path))) {
												//create first the zip file in the mkdir:
												$path = $folderPath . '/' . $element->getFieldValue($fieldName, "path");
												$res = fopen($path, "x");
												fwrite($res, $content);
												fclose($res);
												//										 	xattr_set($path, "owner", "lionel");
											}
											if (!extractSafeZipFile($path, $folderPath . '/')) {
												echo "FAILED to unzip!!!!";
											}
											$downloadPath = SITE_ROOT_forFileUrl . TEMPORARYUNZIPFORVIEWINGFILE_path . $publicUrl;
											$beforeClose = "beforeClose: function(){ update('" . $exec->getCrtRequest() . "/delete/" . $publicUrl . "'); }";
										}
										break;
								}
								echo '<a class="media" href="' . $downloadPath . '" ></a>';

								$exec->addJsCode("" .
									"if($('#elementPreview').is(':ui-dialog')) { " .
										"$('#elementPreview').dialog('destroy'); " .
									"} " .
									"$('#elementPreview').dialog({" .
										"width:$('html').width()-100," .
										"height:$(window).height()-100," .
										"position: { my:'center', at:'center' }, closeOnEscape: true, resizable:false," .
										"title:'" . str_replace("'", "\'", str_replace("." . $type, "", $fileName)) . "'," .
										"$beforeClose" .
									"});" .
									"$('#elementPreview a.media').media({ " .
										"type:'$type', width:$('html').width()-130, height:$(window).height()-160 " .
									"});");
								break;
							}

						}

						//standard downlad
						header('Pragma: public');
						header('Cache-Control: max-age=0');
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
						if (!$isThumbs){
							if($type==".svg"){ //if svg we need to define the content type as text/html in order to allow browsers to embed it
								header('Content-type: text/html');
							} else if(
								$type== ".csv" ||
								$type== ".sql" ||
								$type== ".config" ||
								$type== ".bat" ||
								$type== ".dat" ||
								$type== ".data"
								){ //if plain text we need to define the content type as text/plain in order to allow browsers to embed it
								header('Content-type: text/plain');
							} else {
								header('Content-type: ' . $mime);
							}
						}
						if (!$isThumbs && $size && $fieldXml["htmlArea"] != "1") {
							header('Content-Length: ' . $size);
						}
						//for any text file display the content as is
						if (!$isIntegrated && !$isThumbs) {
							header('Content-Disposition: attachment; filename="' . $fileName . '"');
						}

						if (($content != null && $storeFileContentIntoDatabase) || ($content != null && !file_exists($path))) {
							//						 	fput("download file with addslashes: ".md5(addslashes($content)));
							//						 	fput("download file as is: ".md5($content));
							session_write_close();
							if ($fieldXml["htmlArea"] == "1") {
								$content = $this->createTrm()->wrapHtmlFilesWithHeader($content, $filename);
								header('Content-Length: ' . strlen($content));
								echo $content;
							} else {
								echo $content;
							}
						} else {

							//check if the file exist, and if not display a standard preview
							if ($isThumbs) {
								if (!file_exists($path)) {
									if (file_exists("./images/preview/prev$type.png")) {
										$path = "./images/preview/prev" . $type . ".png";
									} else {
										$path = "./images/preview/preview.jpg";
									}
									header('Content-type: ' . 'image/png');
								} else {
									header('Content-type: ' . typeMime($type));
								}
							}
							session_write_close();
							readfile($path);
							//						virtual($path);
						}
						exit;
				}
				break;
			case "exportAndDownload" :
			case "export" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				$configS = $this->getConfigurationContext();

				$what = $exec->getCrtParameters(0);

				$totalWidth = $configS->getParameter($p, $exec->getCrtModule(), "exportTotalWidth");
				$labelWidth = $configS->getParameter($p, $exec->getCrtModule(), "exportLabelWidth");
				;
				if ($totalWidth == 0)
					$totalWidth = 450;
				if ($labelWidth == 0)
					$labelWidth = 150;

				//the dialog box parameters is independant of the module
				$exportRec = $this->createActivityRecordForForm($p, Activity :: createInstance($what), $exec->getCrtModule());

				$action = $exec->getCrtRequest();
				$form = $this->createExportFormExecutor($exportRec, $what . "_form", $action);
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);

				if ($exec->getCrtAction() == "exportAndDownload") {
					//list context
					$exportLC = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "exportElements");
					$elementListLC = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
					$exportLC->setConfigGroupList($elementListLC->getConfigGroupList());
					$tempExportLC = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "TempExportElements");
					$form->export($p, $exec, $state, $exportLC, $elementListLC, $tempExportLC);
					break;
				}
				$state = "start";
				if ($_POST["action"] != null)
					$state = addslashes($_POST["action"]);
				$form->ResolveForm($p, $exec, $state);
				break;
//				/**
//				 * Export urls can be:
//				 * CSV/export/urlencode('separator')/encoding/[includeChildrenGroup:BOOL{/groupId}+]
//				 * separator can be the character you want
//				 * encoding can be: ISO-8859-1 or UTF-8
//				 * if includeChildrenGroup and groupIds are not defined, then the elementList context will be used
//				 */
//			case "JSON" :
//				if (!isset ($headerText))
//					$headerText = 'Content-type: application/json';
//			case "XML" :
//				if (!isset ($headerText))
//					$headerText = 'Content-type: text/xml';
//			case "PDF" :
//				if (!isset ($headerText))
//					$headerText = 'Content-Type: application/pdf';
//			case "Excel" :
//				if (!isset ($headerText))
//					$headerText = ElementPListExportExcel :: getContentTypeHeader();
//			case "CSVRaw" :
//			case "CSVPretty" :
//				if (!isset ($headerText))
//					$headerText = 'Content-type: application/vnd.ms-excel';
//
//				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
//					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
//
//				$what = $exec->getCrtParameters(0);
//
//				if (!isset ($p))
//					$p = ServiceProvider :: getAuthenticationService()->getMainPrincipal();
//				if (!isset ($elS))
//					$elS = ServiceProvider :: getElementService();
//				if (!isset ($groupAS))
//					$groupAS = ServiceProvider :: getGroupAdminService();
//				if (!isset ($transS))
//					$transS = ServiceProvider :: getTranslationService();
//
//				//sends the correct headers:
//				//if you get error here, that means that the action is not setup in the shouldByPassHeader method
//				header('Pragma: public');
//				header('Cache-Control: max-age=0');
//				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
//				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
//				header($headerText);
//
//				switch ($what) {
//					case "export" :
//
//						//filename creation
//						switch ($exec->getCrtAction()) {
//							case "CSVPretty" :
//							case "CSVRaw" :
//								$ext = "csv";
//								break;
//							case "PDF" :
//								$ext = "pdf";
//								break;
//							case "Excel" :
//								$ext = ElementPListExportExcel :: getFileExtensions();
//								break;
//							case "XML" :
//								$ext = "xml";
//								break;
//							case "JSON" :
//								$ext = "json";
//								break;
//						}
//						$fileName = ($exec->getCrtWigiiNamespace()->getWigiiNamespaceName() ? $exec->getCrtWigiiNamespace()->getWigiiNamespaceName() . "_" : "") . $transS->t($p, $exec->getCrtModule()->getModuleUrl()) . "_" . date('Y.m.d') . "." . $ext;
//
//						header('Content-Disposition: attachment; filename="' . $fileName . '"');
//
//						//fetched parameters
//						$i = 1;
//						$separator = urldecode($exec->getCrtParameters($i++));
//						if ($separator === "null")
//							$separator = null;
//						$encoding = urldecode($exec->getCrtParameters($i++));
//
//						//list context
//						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "exportElements");
//
//						//if group parameters are set, then take the parameters
//						if ($exec->getCrtParameters($i) != null) {
//							$includeChildrenGroups = formatBoolean($exec->getCrtParameters($i++));
//							$groupIds = array_slice($exec->getCrtParameters(), $i);
//							$groupList = GroupListAdvancedImpl :: createInstance(false);
//							$groupAS->getSelectedGroups($p, $groupAS->getListFilterForSelectGroupWithoutDetail($groupIds), $groupList);
//							$lc->resetFetchCriteria($p, $this);
//							$lc->setGroupPList($groupList, $includeChildrenGroups);
//						} else {
//							//else take the elementListContext
//							$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
//							if ($elementListContext->getMultipleSelection() != null) {
//								$lc->resetFetchCriteria($p, $this);
//								//							//add groupList on all
//								//							$groupList = GroupListAdvancedImpl::createInstance();
//								//							$lf = ListFilter::createInstance();
//								//							$lf->setFieldSelectorList(ServiceProvider::getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail());
//								//							$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupList, $lf);
//								//							$lc->setGroupPList($groupList);
//								//changed on 24 sept, when multiple select export in excel it is more natural to keep current group filter
//								//the only limitation, is that the user needs to clic on all group if he has some selection in multiple groups
//								$lc->matchFetchCriteria($elementListContext);
//								//add the multipleSelection criterias in the LogExp
//								$lc->addLogExpOnMultipleSelection($elementListContext->getMultipleSelection());
//							} else {
//								$lc->matchFetchCriteria($elementListContext);
//							}
//						}
//
//						//create a temp list context to allow the export change the context to each group
//						$lcTemp = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "TempExportExcelElements");
//
//						$elementPList = $this->createElementPListForExport($p, $exec, $exec->getCrtAction(), $lc, $lcTemp, $separator, $encoding);
//
//						if ($exec->getCrtAction() == "Excel" || $exec->getCrtAction() == "PDF") {
//
//							//loop through the groupTree to make export per group
//							if ($elementListContext->getMultipleSelection() != null) {
//								$elementPList->setAllOnOnePage(true);
//								$groupList = $lc->getGroupPList();
//							} else {
//								$groupList = $lc->getGroupPList();
//							}
//
//							if ($groupList == null || $groupList->isEmpty())
//								return "No group selected";
//							//fetch the groupPTree to be able to loop throug the structure
//							$groupPTree = GroupPListTreeArrayImpl :: createInstance();
//							$lf = ListFilter :: createInstance();
//							$lf->setFieldSelectorList(ServiceProvider :: getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail());
//							$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupPTree, $lf);
//
//							$elementPList->setGroupPList($groupList);
//							$groupPTree->visitInDepth($elementPList);
//
//							$elementPList->saveFile();
//
//						} else {
//							//flat export
//							$elementPList->actOnBeforeAddElementP();
//							$nbRow = $elS->getSelectedElementsInGroups($p, $lc->getGroupLogExp(), $elementPList, $lc);
//
//							$elementPList->actOnFinishAddElementP($nbRow);
//						}
//
//						$this->throwEvent()->exportElements(PWithModuleWithElementPList :: createInstance($p, $exec->getCrtModule(), $elementPList));
//
//						break;
//				}
//				exit;
			case "groupSelectorPanel" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				$groupAS = ServiceProvider :: getGroupAdminService();
				$groupIds = array_slice($exec->getCrtParameters(), 1);
				if ($groupIds[0] == "0") {
//					//select all root groups
//					$groupList = GroupListAdvancedImpl :: createInstance(false);
//					$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupList, $groupAS->getListFilterForSelectRootGroupsWithoutDetail($exec->getCrtModule()));
					$groupList = $this->getConfigurationContext()->getRootGroupsInModule($p, $exec->getCrtModule());
					$groupIds = $groupList->getGroupIds();
				} else {
					$groupList = GroupListAdvancedImpl :: createInstance(false);
					$groupAS->getSelectedGroups($p, $groupAS->getListFilterForSelectGroupWithoutDetail($groupIds), $groupList);
				}
				$transS = ServiceProvider :: getTranslationService();

				if ($groupList->count() == 0 && $exec->getIsUpdating()) {
					echo ExecutionServiceImpl :: answerRequestSeparator;
					echo "confirmationDialog";
					echo ExecutionServiceImpl :: answerParamSeparator;
					$this->openAsMessage("confirmationDialog", 350, $transS->t($p, "noGroupFound") . ": " . ($groupIds ? implode(", ", $groupIds) : ''), $transS->t($p, "noGroupFoundExplanation"), "actOnCloseDialog('confirmationDialog');");
					break;
				}

				//$group = $grS->getGroup($p, $groupId);
				$configS = $this->getConfigurationContext();

				$withChildren = (string) $configS->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") !== "0";
				$doUpdate = true;
				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				if ($exec->getCrtParameters(0) == "selectGroupAndChildrenWithNoUpdate") {
					$withChildren = true;
					$doUpdate = false;
				} else
					if ($exec->getCrtParameters(0) == "selectGroupWithNoUpdate") {
						$withChildren = false;
						$doUpdate = false;
					} else
						if ($exec->getCrtParameters(0) == "selectGroupAndChildren") {
							$withChildren = true;
							$doUpdate = true;
						} else
							if ($exec->getCrtParameters(0) == "selectGroup") {
								$withChildren = false;
								$doUpdate = true;
							} else
								if ($exec->getCrtParameters(0) == "selectGroupAsConfig") {
									$doUpdate = true;
								}

				$this->throwEvent()->selectGroup(PWithGroupPList::createInstance($p, $groupList));

				$lc->setGroupPList($groupList, $withChildren);
				//Medair (LMA, CWE) 28.03.2017
                // Reset showOnlyDuplicates on folder navigation
				$lc->setGroupByOnlyDuplicates(false);
				$this->getConfigurationContext()->setGroupPList($p, $exec->getCrtModule(), $groupList, $withChildren);

				//persist context in DB;
				$this->persistMainPrincipalSessionContext($p, $exec);

				if (!$exec->wasFoundInJSCache() && $doUpdate) {
					//$exec->addRequests("searchBar/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/searchBar");
					$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/" . $exec->getCrtParameters(0));
				}

				break;
			case "filterOnMultipleSelection":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS)) $configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();
				if (!isset ($transS)) $transS = ServiceProvider :: getTranslationService();
				if (!isset ($elS)) $elS = ServiceProvider :: getElementService();

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				if($lc->isMultipleSelection()){
					$lc->setSearchBar($p, $this, array(ListContext::TextSearchField=>"#".implode(" #", $lc->getMultipleSelection())));
					$lc->resetSelectSearchField();
						$lc->resetGroupFilterFields();
					$lc->setGroupPList($configS->getRootGroupsInModule($p, $exec->getCrtModule()), true);
					$configS->setGroupPList($p, $exec->getCrtModule(), $configS->getRootGroupsInModule($p, $exec->getCrtModule()), true);
					$exec->addJsCode("invalidCache('moduleView'); setFiltersButton(true);");
					$exec->addJsCode("$('nav #searchField input:first').val('".addSlashes($lc->getTextSearch())."');");
					$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/");
					$exec->addRequests("groupPanel/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/groupPanel/count");
				}

				break;
			case "removeFilters":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS)) $configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();
				if (!isset ($transS)) $transS = ServiceProvider :: getTranslationService();
				if (!isset ($elS)) $elS = ServiceProvider :: getElementService();

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				$lc->resetCalculatedGroupTreeForSearch();
				$lc->setSearchBar($p, $this, array());
				$lc->resetSelectSearchField();
				$lc->resetGroupFilterFields();
				//reset list context to view folders (to prevent display of the whole element list)
				$lc->setGroupPList($lc->getGroupPList(), false);
				//$exec->addJsCode("invalidCache('moduleView');");
				$exec->addJsCode("invalidCache('moduleView'); setFiltersButton(false);");
				$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/");
				$exec->addRequests("groupPanel/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/groupPanel/");

				break;
			case "filters":
			case "simpleFilters": //when only the input text, (no reset of others filters)
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal()) throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS)) $configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();
				if (!isset ($transS)) $transS = ServiceProvider :: getTranslationService();
				if (!isset ($groupAS)) $groupAS = ServiceProvider :: getGroupAdminService();
				if (!isset ($elS)) $elS = ServiceProvider :: getElementService();
				$totalWidth = 650; //$configS->getParameter($p, $exec->getCrtModule(), "addIndicatorTotalWidth");
				$labelWidth = 200; //$configS->getParameter($p, $exec->getCrtModule(), "addIndicatorLabelWidth");;

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				$filtersRec = $this->createActivityRecordForForm($p, Activity :: createInstance("filters"), $exec->getCrtModule());
				$lc->resetSelectSearchField();
				$lc->resetGroupFilterFields();
				if($lc->getGroupPList()->count()==1) {
					$currentGroupId = reset($lc->getGroupPList()->getListIterator())->getId();
				}
				else $currentGroupId = null;
				
				//build the select filters based on selectSearch activity
				$selectSearchBarActivityName = $lc->getCrtView()."SelectSearchBar";
				if(empty($configS->m($p, $exec->getCrtModule())->$selectSearchBarActivityName)){
						$selectSearchBarActivityName = "selectSearchBar";
						if(empty($configS->m($p, $exec->getCrtModule())->$selectSearchBarActivityName))
						$selectSearchBarActivityName = null;
				}
				// gets search space constraints only if Group_selectAllGroupsOnSearch != 0
				if($configS->getParameter($p, $exec->getCrtModule(), "Group_selectAllGroupsOnSearch") != "0") {
					$includeGroups = (string)$configS->getParameter($p, $exec->getCrtModule(), "Group_includeGroupsOnSearch");
					if(!empty($includeGroups)) {
						$includeGroups = preg_split("/".ValueListArrayMapper::Natural_Separators."/", $includeGroups);
						$includeGroups = array_combine($includeGroups,$includeGroups);
					}
					else $includeGroups = null;		
				}	
				elseif($currentGroupId) $includeGroups = array($currentGroupId=>$currentGroupId);			
				else $includeGroups = null;
				// automatically adds current selected group if includeGroups is not empty.
				if($currentGroupId && !empty($includeGroups)) {
					$includeGroups[$currentGroupId] = $currentGroupId;
				}

				if($configS->getParameter($p, $exec->getCrtModule(), "Group_selectAllGroupsOnSearch") != "0") {
					$excludeGroups = (string)$configS->getParameter($p, $exec->getCrtModule(), "Group_excludeGroupsOnSearch");
					if(!empty($excludeGroups)) {
						$excludeGroups = preg_split("/".ValueListArrayMapper::Natural_Separators."/", $excludeGroups);
						$excludeGroups = array_combine($excludeGroups,$excludeGroups);
					}
					else $excludeGroups = array();
				}
				else $excludeGroups = array();
				// automatically excludes trashbin group if defined
				$trashBinGroup = (string)$configS->getParameter($p, $exec->getCrtModule(), "trashBinGroup");
				if($trashBinGroup) $excludeGroups[$trashBinGroup] = $trashBinGroup;
				// automatically removes current selected group from exclusion
				if($currentGroupId && !empty($excludeGroups) && array_key_exists($currentGroupId, $excludeGroups)) {
					unset($excludeGroups[$currentGroupId]);
				}
				
				if($selectSearchBarActivityName){
					$selectSearchBarActivity = Activity::createInstance($selectSearchBarActivityName);
					// gets advanced search fields
					$fssbf = FiltersSelectSearchBarFieldList::createInstance($this, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), $filtersRec, $lc);
					$configS->getFields($p, $exec->getCrtModule(), $selectSearchBarActivity, $fssbf);
					$fssbf->addReverseAttribute();					
				}

				$action = $exec->getCrtRequest();
				$form = $this->createFiltersFormExecutor($filtersRec, "filters_form", $action);
				$form->setIsSimpleFilters($exec->getCrtAction() =="simpleFilters");
				$form->setCorrectionWidth(19);
				$form->setLabelWidth($labelWidth);
				$form->setTotalWidth($totalWidth);
				$form->setListContext($lc);

				if($exec->getCrtAction() !="simpleFilters"){					
					if($configS->getParameter($p, $exec->getCrtModule(), "Group_selectAllGroupsOnSearch") != "0"){
						
						$field = $filtersRec->getFieldList()->getField("limitFilterInGroup");
						$filtersRec->getWigiiBag()->setHidden(false, "limitFilterInGroup");
						$groupPTreeArrayImpl = GroupPListTreeArrayImpl :: createInstance();
						// limitFilterInGroup
						$inGroupModuleXml = '<limitFilterInGroup type="MultipleAttributs" chosen="1" expand="1">';
						$inGroupModuleXml .= '<attribute>0<label>' . $transS->t($p, "allGroups") . '</label></attribute>';
						//get all read group
						$groupPTreeArrayImpl->reset();
						$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupPTreeArrayImpl, $groupAS->getListFilterForSelectGroupWithoutDetail());
						$groupPTreeArrayImpl->cleanOnMarkup("r");
						//			eput($groupPTreeArrayImpl);
						$selectOptionGroupTree = SelectOptionForGroupTreeRenderer :: createInstance($p->getWigiiNamespace()->getWigiiNamespaceName(), "r");
						$selectOptionGroupTree->setShowFullPathInLabel(true);
						$inGroupModuleXml .= $groupPTreeArrayImpl->visitInDepth($selectOptionGroupTree);
						$inGroupModuleXml .= $selectOptionGroupTree->getResult();
						$inGroupModuleXml .= '</limitFilterInGroup>';
						$field->setXml(simplexml_load_string($inGroupModuleXml));
						// excludeGroups						
						$field = $filtersRec->getFieldList()->getField("excludeGroups");						
						$filtersRec->getWigiiBag()->setHidden(false, "excludeGroups");							
						$inGroupModuleXml = '<excludeGroups type="MultipleAttributs" chosen="1" expand="1">';
						$inGroupModuleXml .= $selectOptionGroupTree->getResult();										
						$inGroupModuleXml .= '</excludeGroups>';							
						$field->setXml(simplexml_load_string($inGroupModuleXml));
					} else {
						$filtersRec->getWigiiBag()->setHidden(true, "groupFilterInGroup");
						$filtersRec->getWigiiBag()->setHidden(true, "limitFilterInGroup");
						$filtersRec->getWigiiBag()->setHidden(true, "excludeGroups");
					}
				}
				$state = "start";
				if ($_POST["action"] != null) $state = addslashes($_POST["action"]);
				if($exec->getCrtAction() == "simpleFilters"){
					//load the other possible existing filters
					$_POST[ListContext::TextAdvancedSearchField] = str_replace(array(";",","), "", addSlashes($lc->getTextAdvancedSearch()));
					$_POST[ListContext::TextGroupSearchField] = addslashes($lc->getTextGroupSearch());
					$_POST[ListContext::ReverseSelectSearchField] = $lc->getReverseSelectSearch();

					// in simple filters only calculates once the group tree for search using include/exclude constraints 
					if(!$lc->hasCalculatedGroupTreeForSearch()) {
						// initializes limitFilterInGroup
						// if includeGroups is defined, then takes those
						if(!empty($includeGroups)) $filterInGroup = $includeGroups;
						// else takes all root groups
						else $filterInGroup = array('0'=>'0');
						$_POST[ListContext::LimitFilterInGroupSearchField] = $filterInGroup;
							
						// initializes excludeGroups if set
						if(!empty($excludeGroups)) {
							$_POST[ListContext::ExcludeGroupsSearchField] = $excludeGroups;
						}
					}
					if($lc->getSelectSearchFields()){
						foreach($lc->getSelectSearchFields() as $selectSearchField=>$fieldName){
							//Date filter
							if(strpos($fieldName,"!TR!")===0){
								$_POST[$selectSearchField."_begDate"] = $lc->getSelectSearchField($selectSearchField."_begDate");
								$_POST[$selectSearchField."_endDate"] = $lc->getSelectSearchField($selectSearchField."_endDate");
							} else if(strpos($fieldName,"!BO!")===0){
								$_POST[$selectSearchField] = $lc->getSelectSearchField($selectSearchField);
							} else {
								$_POST[$selectSearchField] = $lc->getSelectSearchField($selectSearchField);
							}
						}
					}
					if($lc->getSelectGroupFilterFields()){
						foreach($lc->getSelectGroupFilterFields() as $selectGroupFilterField=>$groupLogExpList){
							$_POST[$selectGroupFilterField] = $lc->getSelectSearchField($selectGroupFilterField);
						}
					}
				} else {
					$filtersRec->setFieldValue($lc->getTextSearch(), ListContext::TextSearchField);
					$filtersRec->setFieldValue($lc->getTextAdvancedSearch(), ListContext::TextAdvancedSearchField);
					$filtersRec->setFieldValue($lc->getTextGroupSearch(), ListContext::TextGroupSearchField);
					$filtersRec->setFieldValue($lc->getReverseSelectSearch(), ListContext::ReverseSelectSearchField);

					if($lc->getSelectSearchFields()){
						foreach($lc->getSelectSearchFields() as $selectSearchField=>$fieldName){
							//Date filter
							if(strpos($fieldName,"!TR!")===0){
								$filtersRec->setFieldValue($lc->getSelectSearchField($selectSearchField."_begDate"), $selectSearchField, "begDate");
								$filtersRec->setFieldValue($lc->getSelectSearchField($selectSearchField."_endDate"), $selectSearchField, "endDate");
							} else if(strpos($fieldName,"!BO!")===0){
								$filtersRec->setFieldValue($lc->getSelectSearchField($selectSearchField), $selectSearchField);
							} else {
								$filtersRec->setFieldValue($lc->getSelectSearchField($selectSearchField), $selectSearchField);
							}
						}
					}
					if($lc->getSelectGroupFilterFields()){
						foreach($lc->getSelectGroupFilterFields() as $selectGroupFilterField=>$groupLogExpList){
							$filtersRec->setFieldValue($lc->getSelectSearchField($selectGroupFilterField), $selectGroupFilterField);
						}
					}
					
					// initialize limitFilterInGroup from context if exists, else proposes default using includeRootGroups
					$filterInGroup = $lc->getLimitFilterInGroupForSearch();
					if(empty($filterInGroup)) {						
						// if includeGroups is defined, then propose those
						if(!empty($includeGroups)) $filterInGroup = $includeGroups;
						// else propose all root groups
						else $filterInGroup = array('0'=>'0');
					}
					else $filterInGroup = array_combine($filterInGroup, $filterInGroup);
					$filtersRec->setFieldValue($filterInGroup, ListContext::LimitFilterInGroupSearchField);
					
					// excludeGroups
					$excludeGroupsInSearch = $lc->getExcludeGroupsInSearch();
					if(empty($excludeGroupsInSearch) && !empty($excludeGroups)) $excludeGroupsInSearch = $excludeGroups;
					// removes exclude group which are in include groups
					if(!empty($filterInGroup) && !empty($excludeGroupsInSearch)) {
						$excludeGroupsInSearch = array_combine($excludeGroupsInSearch, $excludeGroupsInSearch);
						$excludeGroupsInSearch = array_diff_key($excludeGroupsInSearch, $filterInGroup); 
					}
					$filtersRec->setFieldValue($excludeGroupsInSearch, ListContext::ExcludeGroupsSearchField);					
				}
				$form->ResolveForm($p, $exec, $state);
				break;
			case "countElementsInGroupPanel":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS)) $configS = $this->getConfigurationContext();
				if($this->getTempTotalElementsIdsForListView()){
					$elS = ServiceProvider::getElementService();
					$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
					// Medair (CWE) 12.12.2016: if list is paged and total number of elements fits in page, then computes distribution of page
					if($lc->isPaged() && $lc->getTotalNumberOfObjects() > 0 && $lc->getTotalNumberOfObjects() <= $lc->getPageSize()) {
						$countData = $elS->countSelectedElementsDistribution($p, $this->getTempTotalElementsIdsForListView());
					}
					// else re-computes distribution of all elements
					else {
						$groupLogExp = $lc->getGroupLogExp()->reduceNegation(true);
						$countData = $elS->countSelectedElementsDistributionInGroups($p, $groupLogExp, $lc);
					}	
					// Medair (CWE) 19.09.2017: if Group_selectAllGroupsOnSearch = 0 and only one group selected, then hides found groups which are not children from current group.
					if($configS->getParameter($p, $exec->getCrtModule(), "Group_selectAllGroupsOnSearch") == "0" && $lc->getGroupPList()->count()==1) {
					    $currentGroupId = reset($lc->getGroupPList()->getListIterator())->getId();
					    $currentGroupSelector = '#group_'.$currentGroupId.' ';
					}
					else $currentGroupId = null;
					$exec->addJsCode("" .
						"$('#groupPanel .found, #groupPanel .hidden, #groupPanel .empty').removeClass('found').removeClass('hidden').removeClass('empty');" .
						"$('#groupPanel .nb').remove();" .
						"$('#groupPanel li').show();" .
						"");
					foreach($countData as $groupId=>$nb){
					    // Medair (CWE) 19.09.2017: if Group_selectAllGroupsOnSearch = 0, only keeps sub-groups of current group
					    if($groupId == $currentGroupId || $currentGroupId==null) $cgs = '';
					    else $cgs = $currentGroupSelector;
					    
						if($nb == null){
						    $exec->addJsCode("$('$cgs#group_$groupId>div span.description').before('<span class=\"R nb empty\"></span>');");
						} else {
						    $exec->addJsCode("$('$cgs#group_$groupId>div span.description').before('<span class=\"R nb\">&nbsp;($nb)</span>');");
						}
					}

					//unfold to each groups containing elements
					//hide each other groups except parents
					$exec->addJsCode("" .
						"$('#groupPanel li:has(>div>span.nb)').addClass('found');" .
						"$('#groupPanel li:not(:has(>div>span.nb))').removeClass('found').addClass('hidden');" .
						"$('#groupPanel li:has(>div>span.nb.empty)').removeClass('found').addClass('hidden');" .
						"$('#groupPanel li:has(li.found)').removeClass('hidden').addClass('empty');" .
						"$('#groupPanel li.found>div').addClass('found');" .
						"$('#groupPanel li.hidden').hide();" .
						"unfoldToSelectedGroup('#groupPanel', 'found');resize_elementList();" .
						"");
				} else {
					$exec->addJsCode("$('#groupPanel li').hide();");
				}
				break;
			case "searchBar" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

				//define a fieldSelectorList
				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				try {
					$lastText = $lc->getTextSearch();
					$lastTextAdvanced = $lc->getTextAdvancedSearch();
					$lastTextGroup = $lc->getTextGroupSearch();
					$lc->setSearchBar($p, $this, $_POST);
					$newText = $lc->getTextSearch();
					$newTextAdvanced = $lc->getTextAdvancedSearch();
					$newTextGroup = $lc->getTextGroupSearch();

					$configS = $this->getConfigurationContext();

					$searchIschanged = ($lastText != $newText || $lastTextAdvanced != $newTextAdvanced || $lastTextGroup != $newTextGroup) && ($newText != null || $newTextAdvanced != null || $newTextGroup != null);

					//if the textSearch has changed, then change the context to all group
					//except if the module has a special configuration for that
					if ($configS->getParameter($p, $exec->getCrtModule(), "Group_selectAllGroupsOnSearch") != "0" && $searchIschanged) {
						$groupAS = ServiceProvider :: getGroupAdminService();
						$withChildren = (string) $configS->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") !== "0";
						$groupList = GroupListAdvancedImpl :: createInstance(false);
						$lf = ListFilter :: createInstance();
						$lf->setFieldSelectorList(ServiceProvider :: getGroupAdminService()->getFieldSelectorListForGroupWithoutDetail());
						$groupAS->getAllGroups($p, $exec->getCrtModule(), $groupList, $lf);
						$lc->setGroupPList($groupList->getRootGroups(), $withChildren);
						$this->getConfigurationContext()->setGroupPList($p, $exec->getCrtModule(), $groupList->getRootGroups(), $withChildren);
						//					eput($lastText." -> ".$newText);
					}
				} catch (StringTokenizerException $e) {
					//echo $e->getMessage();
					$transS = ServiceProvider :: getTranslationService();
					//$exec->addJsCode(" $('#searchBar div.inputTextSearch :input').focus(); "); //".css('color','#f00'); ");
					//				$exec->addJsCode(" $('#searchBar div.inputTextSearch :input').val(searchBar_defaultTextSearch).removeClass('darkGrayFont').addClass('empty grayFont'); ");
					echo $transS->t($p, "parseErrorInTextSearch");
					echo "\n(" . $e->getMessage() . ")";
					break;
				}
				if ($lc->isCrtViewCalendar()) {
					$lc->setCrtViewParams(true, "redefineSearchCriterias");
					if ($configS->getParameter($p, $exec->getCrtModule(), "Group_selectAllGroupsOnSearch") != "0" && $searchIschanged) {
						$exec->addJsCode("selectGroup('" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceName() . "', '" . $exec->getCrtModule()->getModuleName() . "', $('#group_ALL_0'), true, false);");
					}
					$exec->addJsCode("$('#moduleView .calendar').fullCalendar('refetchEvents');");
				} else {
					if ($configS->getParameter($p, $exec->getCrtModule(), "Group_selectAllGroupsOnSearch") != "0" && $searchIschanged) {
						$exec->addJsCode("selectGroup('" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceName() . "', '" . $exec->getCrtModule()->getModuleName() . "', $('#group_ALL_0'), true, false);");
					}
					$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/");
				}
				//			if($_POST["goToListInGroup"]){
				//				//change the context, select the group, and reload the workZone
				//				$exec->addRequests("NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/loadElementListInIconsView/".$_POST["goToListInGroup"]);
				//				$exec->addRequests("NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/loadElementListInIconsView/".$_POST["goToListInGroup"]);
				//			} else {
				//				if($lc->isCrtViewCalendar()){
				//					$lc->setCrtViewParams(true,"redefineSearchCriterias");
				//					$exec->addJsCode("$('#moduleView .calendar').fullCalendar('refetchEvents');");
				//				} else {
				//					$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");
				//				}
				//			}

				break;
			case "unlock" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				$dbEntityName = $exec->getCrtParameters(0);
				$entityId = $exec->getCrtParameters(1);
				switch ($dbEntityName) {
					case "element" :
						ServiceProvider :: getElementService()->unLock($p, DbEntityInstance :: createInstance($entityId));
						break;
					case "user" :
						ServiceProvider :: getUserAdminService()->unLock($p, DbEntityInstance :: createInstance($entityId));
						break;
					case "group" :
						ServiceProvider :: getGroupAdminService()->unLock($p, DbEntityInstance :: createInstance($entityId));
						break;
				}
				//eput("unlock $dbEntityName $entityId ".$p->getExecutionId());
				break;
			case "toggleElementState_deprecated" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS))
					$configS = $this->getConfigurationContext();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();

				$i = 0;
				$elementId = $exec->getCrtParameters($i++);
				if ($elementId == "multiple") {
					throw new ServiceException("toggleElementState dosen't support multiple selection", ServiceException :: INVALID_ARGUMENT);
				}
				$state = $exec->getCrtParameters($i++);
				if ($state == "ownLocked")
					$state = "locked";

				//eput("element ".$elementId." set state ".$state." with ".put($checked));
				//SET ELEMENT STATE
				try {
					$elS-> {
						"toggleState_" . $state }
					($p, $elementId);
				} catch (AuthorizationServiceException $ase) {
					if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
						$this->displayLockMessage($p, $exec, null, "element", DbEntityInstance :: createInstance($elementId));
						break;
					} else
						throw $ase;
				}

				$element = $this->createElementForForm($p, $exec->getCrtModule(), $elementId);
				$elementP = $elS->fillElement($p, $element);
				$this->throwEvent()->updateElementState(PWithElementWithGroupPList :: createInstance($p, $element, ($configS->getGroupPList($p, $exec->getCrtModule())->count()==1 ? $configS->getGroupPList($p, $exec->getCrtModule()) : null)));

				//invalidate the cache of the element detail
				$exec->invalidCache($p, 'elementDialog', "selectElementDetail", "element/detail/" . $elementId);
				//update the element in the list
				$exec->addJsCode("updateElementInList('" . $elementId . "');");
				//invalid the cache of the list
				$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				if ($elementListContext->getGroupPList()->count() == 1) {
					$ids = $elementListContext->getGroupPList()->getIds();
					$ids = reset($ids);
					$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $ids);
				}
				break;
			case "setElementState":
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($transS))
					$transS = ServiceProvider::getTranslationService();
				if (!isset ($configS))
					$configS = $this->getConfigurationContext();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();

				$i = 0;
				$elementId = $exec->getCrtParameters($i++);
				$state = $exec->getCrtParameters($i++);
				if ($state == "ownLocked")
					$state = "locked";
				$checked = formatBoolean($exec->getCrtParameters($i++));

				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				if ($elementId == "multiple") {
					if ($lc->getMultipleSelection() != null) {
						// uses the ElementPolicyEvaluator to update the element state if defined
						$policyEval = $this->getElementPolicyEvaluator($p, $exec->getCrtModule());
						if(isset($policyEval)) {
							$policyEval->setExecutionService($exec);
							$policyEval->setIsMultiple(true);
							// selects the elements
							$policyFsl = $policyEval->getFieldSelectorListForUpdateElementOnSetState($p, $state);
							$mlc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "multipleElementList");
							$mlc->resetFetchCriteria($p, $this);
							$mlc->setConfigGroupList($configS->getGroupPList($p, $exec->getCrtModule()));
							//for multiple operation we just take a preview of the elements, the preview is the listView activity
							$fslForNotif = FieldSelectorListForActivity :: createInstance(false, false);
							$configS->getFields($p, $exec->getCrtModule(), Activity :: createInstance("listView"), $fslForNotif);
							$elementPList = ElementPAdvancedListArrayImpl::createInstance(null,$fslForNotif);
							$policyFsl = FieldSelectorListArrayWebImpl::createInstance(false, true, $policyFsl);
							$policyFsl->mergeFieldSelectorList($fslForNotif);
							$mlc->setFieldSelectorList($policyFsl);
							$nbrows = $elS->getSelectedElements($p, $lc->getMultipleSelection(), $elementPList, $mlc);
							if($nbrows > 0) {
								$effectiveElementPList = ElementPAdvancedListArrayImpl::createInstance();
								$stateInfo = array();
								if($p->getRealUser()){
									$stateInfo["realUsername"] = $p->getRealUser()->getUsername();
									$stateInfo["realUserWigiiNamespace"] = $p->getRealUser()->getWigiiNamespace()->getWigiiNamespaceName();
									$stateInfo["realUserId"] = $p->getRealUser()->getId();
								}
								if($p->getAttachedUser()){
									$stateInfo["username"] = $p->getAttachedUser()->getUsername();
									$stateInfo["userWigiiNamespace"] = $p->getAttachedUser()->getWigiiNamespace()->getWigiiNamespaceName();
									$stateInfo["userId"] = $p->getAttachedUser()->getId();
								}
								$stateInfo["timestamp"] = time();
								$stateInfo["message"] = null;
								$policyFsl = null;
								foreach($elementPList->getListIterator() as $elementP) {
									// initializes default policy from config
									$elementP->enableElementState_locked($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableLockedStatus'));
									$elementP->enableElementState_blocked($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableBlockedStatus'));
									$elementP->enableElementState_important1($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableImportant1Status'));
									$elementP->enableElementState_important2($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableImportant2Status'));
									$elementP->enableElementState_finalized($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableFinalizedStatus'));
									$elementP->enableElementState_approved($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableApprovedStatus'));
									$elementP->enableElementState_dismissed($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableDismissedStatus'));
									$elementP->enableElementState_archived($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableArchivedStatus'));
									$elementP->enableElementState_deprecated($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableDeprecatedStatus'));
									$elementP->enableElementState_hidden($configS->getParameter($p, $exec->getCrtModule(), 'Element_enableHiddenStatus'));
									// checks authorization to change element state
									$policyEval->computeEnableElementState($p, $elementP);
									$element = $elementP->getElement();
									if($elementP->isEnabledElementState($state)) {
										// updates element on state change
										$fsl = $policyEval->updateElementOnSetState($p, $element, $state, $checked);
										// checks fsl integrity to prevent saving an empty field and loosing some info in the db.
										if(!isset($policyFsl)) {
											$policyFsl = FieldSelectorListArrayImpl::createInstance(false);
											if(isset($fsl)) $policyFsl->mergeFieldSelectorList($fsl);
										}
										else {
											$n = $policyFsl->count(); $mismatchError = false;
											if($n > 0 && (!isset($fsl) || $fsl->isEmpty())) $mismatchError = true;
											elseif($n == 0 && isset($fsl) && !$fsl->isEmpty()) $mismatchError = true;
											elseif(isset($fsl)) {
												$policyFsl->mergeFieldSelectorList($fsl);
												if($policyFsl->count() > $n) $mismatchError = true;
											}
											if($mismatchError) throw new ServiceException("Multiple set state: the element policy evaluator '".get_class($policyEval)."' does not update the same fields in all elements. Please correct the 'updateElementOnSetState' method to be consistent for all elements.", ServiceException::DATA_INTEGRITY_ERROR);
										}
										// sets the state
										$element->setState($state, $checked, $stateInfo);
										// adds element to effective list
										$effectiveElementPList->addElementP($elementP);
									}
									// updates element state in list context
									$lc->updateElementStateInMultipleSelection($element->getId(), $elementP->getEnableElementStateAsInt(), $element->getStateAsInt(), false);
								}
								// saves the changes into the db
								if(!isset($policyFsl)) $policyFsl = FieldSelectorListArrayImpl::createInstance(false);
								$policyFsl->addElementAttributeSelector('state_'.$state);
								$policyFsl->addElementAttributeSelector('state_'.$state.'Info');
								if($effectiveElementPList->isEmpty()) throw new AuthorizationServiceException("Elements current state does not authorize the change of element state attribute '$state'.", AuthorizationServiceException::UNAUTHORIZED);
								try{
									$elS->updateMultipleElement($this->getRootPrincipal(), $p, $effectiveElementPList, $policyFsl);
								} catch (AuthorizationServiceException $ase){
									if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
										echo ExecutionServiceImpl :: answerRequestSeparator;
										echo "confirmationDialog";
										echo ExecutionServiceImpl :: answerParamSeparator;
										$this->displayListOfLockedItems($p, $lc->getMultipleSelection());
										break;
									} else
										throw $ase;
								}
							}
						}
						//else sets element state using the default API method
						else {
							try {
								$elementPList = $elS-> {
									"setMultipleState_" . $state }
									($p, $lc->getMultipleSelection(), $checked);
							} catch (AuthorizationServiceException $ase) {
								if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
									echo ExecutionServiceImpl :: answerRequestSeparator;
									echo "confirmationDialog";
									echo ExecutionServiceImpl :: answerParamSeparator;
									$this->displayListOfLockedItems($p, $lc->getMultipleSelection());
									break;
								} else
									throw $ase;
							}
							// updates multiple selection element state in list context
							$lc->updateAllElementStateInMultipleSelection(Element::stateName2int($state), ($checked ? Element::stateName2int($state) : 0), false);
						}

						$this->throwEvent()->updateMultipleElementState(PWithModuleWithElementPListWithState :: createInstance($p, $exec->getCrtModule(), $elementPList, $state));
						//invalid the cache of each element
						//$exec->invalidMultipleCache($p, 'elementDialog', "selectElementDetail", "element/detail/", $lc->getMultipleSelection());
						$exec->invalidCache($p, 'elementDialog');
						$exec->invalidCache($p, 'moduleView');
						// recalculates the multiple element state
						$lc->computeMultipleElementStateInt();
						//reload the list
						$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/");

						if ($exec->getIsUpdating()) {
							echo ExecutionServiceImpl :: answerRequestSeparator;
							echo "confirmationDialog";
							echo ExecutionServiceImpl :: answerParamSeparator;
							$this->operationSuccessfullMessage("confirmationDialog", 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
							break;
						}
					}
				} else {
					//eput("element ".$elementId." set state ".$state." with ".put($checked));
					// uses the ElementPolicyEvaluator to update the element state if defined
					$policyEval = $this->getElementPolicyEvaluator($p, $exec->getCrtModule());
					if(isset($policyEval)) {
						$policyEval->setExecutionService($exec);
						// reads element
						$policyFsl = $policyEval->getFieldSelectorListForUpdateElementOnSetState($p, $state);
						if(isset($policyFsl)) $policyFsl = FieldSelectorListArrayImpl::createInstance(false, true, $policyFsl);
						$element = $this->createElementForForm($p, $exec->getCrtModule(), $elementId);
						$elementP = $elS->fillElement($p, $element, $policyFsl);
						if($element->isSubElement()) {
							$policyEvalSubElement = $this->getElementPolicyEvaluator($p, $element->getModule());
							if(isset($policyEvalSubElement)) {
								$policyEval->freeMemory();
								$policyEval = $policyEvalSubElement;
							}
						}
						// initializes default policy from config
						$elementP->enableElementState_locked($configS->getParameter($p, $element->getModule(), 'Element_enableLockedStatus'));
						$elementP->enableElementState_blocked($configS->getParameter($p, $element->getModule(), 'Element_enableBlockedStatus'));
						$elementP->enableElementState_important1($configS->getParameter($p, $element->getModule(), 'Element_enableImportant1Status'));
						$elementP->enableElementState_important2($configS->getParameter($p, $element->getModule(), 'Element_enableImportant2Status'));
						$elementP->enableElementState_finalized($configS->getParameter($p, $element->getModule(), 'Element_enableFinalizedStatus'));
						$elementP->enableElementState_approved($configS->getParameter($p, $element->getModule(), 'Element_enableApprovedStatus'));
						$elementP->enableElementState_dismissed($configS->getParameter($p, $element->getModule(), 'Element_enableDismissedStatus'));
						$elementP->enableElementState_archived($configS->getParameter($p, $element->getModule(), 'Element_enableArchivedStatus'));
						$elementP->enableElementState_deprecated($configS->getParameter($p, $element->getModule(), 'Element_enableDeprecatedStatus'));
						$elementP->enableElementState_hidden($configS->getParameter($p, $element->getModule(), 'Element_enableHiddenStatus'));
						//if subelement, then checks if parent is blocked
						if($element->isSubElement()) {
							foreach($configS->getCurrentSubElementPathFromRoot()->getListIterator() as $ls) {
								if($ls->isOwnerElementBlocked()) {
									$elementP->setParentElementState_blocked(true);
									break;
								}
							}
						}
						// checks authorization to change element state
						$policyEval->computeEnableElementState($p, $elementP);
						if(!$elementP->isEnabledElementState($state)) throw new AuthorizationServiceException("Element current state does not authorize the change of element state attribute '$state'.", AuthorizationServiceException::UNAUTHORIZED);
						// updates element on state change
						$fsl = $policyEval->updateElementOnSetState($p, $element, $state, $checked);
						if(isset($fsl)) {
							if(!isset($policyFsl)) $policyFsl = FieldSelectorListArrayImpl::createInstance(false);
							$policyFsl->mergeFieldSelectorList($fsl);
						}
						// sets the state
						$stateInfo = array();
						if($p->getRealUser()){
							$stateInfo["realUsername"] = $p->getRealUser()->getUsername();
							$stateInfo["realUserWigiiNamespace"] = $p->getRealUser()->getWigiiNamespace()->getWigiiNamespaceName();
							$stateInfo["realUserId"] = $p->getRealUser()->getId();
						}
						if($p->getAttachedUser()){
							$stateInfo["username"] = $p->getAttachedUser()->getUsername();
							$stateInfo["userWigiiNamespace"] = $p->getAttachedUser()->getWigiiNamespace()->getWigiiNamespaceName();
							$stateInfo["userId"] = $p->getAttachedUser()->getId();
						}
						$stateInfo["timestamp"] = time();
						$stateInfo["message"] = null;
						$element->setState($state, $checked, $stateInfo);
						// saves the changes in the db
						if(isset($policyFsl)) {
							$policyFsl->addElementAttributeSelector('state_'.$state);
							$policyFsl->addElementAttributeSelector('state_'.$state.'Info');
						}
						try {
							$elS->updateElement($p, $element, $policyFsl);
						} catch (AuthorizationServiceException $ase) {
							if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
								$this->displayLockMessage($p, $exec, null, "element", $element);
								break;
							} else
								throw $ase;
						}

						// updates multiple selection if needed
						if($lc->isInMultipleSelection($elementP->getId())) {
							$lc->updateElementStateInMultipleSelection($elementP->getId(), $elementP->getEnableElementStateAsInt(), $elementP->getElement()->getStateAsInt());
						}
					}
					// else sets element state using the default API method
					else {
						try {
							$elS-> {
								"setState_" . $state }
								($p, $elementId, $checked);
						} catch (AuthorizationServiceException $ase) {
							if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
								$this->displayLockMessage($p, $exec, null, "element", DbEntityInstance :: createInstance($elementId));
								break;
							} else
								throw $ase;
						}

						$element = $this->createElementForForm($p, $exec->getCrtModule(), $elementId);
						$elementP = $elS->fillElement($p, $element);

						// updates multiple selection if needed
						if($lc->isInMultipleSelection($elementP->getId())) {
							$lc->updateElementStateInMultipleSelection($elementP->getId(), null, $elementP->getElement()->getStateAsInt());
						}
					}

					$this->throwEvent()->updateElementState(PWithElementWithGroupPListWithState :: createInstance($p, $element, ($configS->getGroupPList($p, $exec->getCrtModule()) != null && $configS->getGroupPList($p, $exec->getCrtModule())->count()==1 ? $configS->getGroupPList($p, $exec->getCrtModule()) : null), $state));

					// if noAnswer
					if($exec->getIdAnswer() == 'NoAnswer') {
						//invalidate the cache of the element detail
						$exec->invalidCache($p, 'elementDialog', "selectElementDetail", "element/detail/" . $elementId);
						if($element && $element->isSubElement()){
							//disable the cache of the element parent --> to reload change on next request
							$exec->invalidCache($p, 'elementDialog', "selectElementDetail",  "element/detail/".$element->getElementParentId());
						}
						//update the element in the list
						$exec->addJsCode("updateElementInList('" . $elementId . "');");
						//invalid the cache of the list
						$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						if ($elementListContext->getGroupPList()->count() == 1) {
							$ids = $elementListContext->getGroupPList()->getIds();
							$ids = reset($ids);
							$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $ids);
						}
					}
					//else reloads the detail + updates the list
					else {
						//$exec->addRequests($exec->getIdAnswer()."/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $element->getModule()->getModuleUrl() . "/element/detail/".$elementId);
						$exec->addRequests($exec->getIdAnswer()."/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/element/detail/".$elementId);
						if($element && $element->isSubElement()){
							//disable the cache of the element parent --> to reload change on next request
							$exec->invalidCache($p, 'elementDialog', "selectElementDetail",  "element/detail/".$element->getElementParentId());
							$rootElementId = $configS->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()->getOwnerElementId();
							$exec->addJsCode("updateElementInList('" . $rootElementId . "');");
						} else {
							$exec->addJsCode("updateElementInList('" . $elementId . "');");
						}
						//					//invalid the cache of the list
						//					$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						//					if ($elementListContext->getGroupPList()->count() == 1) {
						//						$ids = $elementListContext->getGroupPList()->getIds();
						//						$ids = reset($ids);
						//						$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $ids);
						//					}
					}
				}

				break;
			case "autoSave" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();
				$element = $this->createElementForForm($p, $exec->getCrtModule(), $exec->getCrtParameters(0));
				$fieldName = $exec->getCrtParameters(1);
				$fsl = FieldSelectorListArrayImpl::createInstance();
				$fsl->addFieldSelector($fieldName);
				$elementP = $elS->fillElement($p, $element, $fsl);
//				if ($edit || $delete) { //don't lock addJournalItem as it is a one shot action and will be done in $this->addJournalItem
//					if (false === $this->lockEntity($p, $exec, "", "element", $element))
//						break;
//				} else {
//					$elS->unLock($p, $element);
//				}
				$this->autoSave($p, $exec, $elementP, $fieldName, $fsl, false);
				break;
			case "element" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				if (!isset ($configS))
					$configS = $this->getConfigurationContext();
				if (!isset ($elS))
					$elS = ServiceProvider :: getElementService();
				if (!isset ($transS))
					$transS = ServiceProvider :: getTranslationService();

                $totalWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementTotalWidth");
                $labelWidth = 0 + $configS->getParameter($p, $exec->getCrtModule(), "elementLabelWidth");               

				$elementId = $exec->getCrtParameters(1);
				$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");

				// single element management
				if ($elementId != "multiple" && 
				        ($exec->getCrtParameters(0) == "detail" || 
						$exec->getCrtParameters(0) == "print" || 
						$exec->getCrtParameters(0) == "manageEmail" || 
						$exec->getCrtParameters(0) == "addJournalItem" || 
						$exec->getCrtParameters(0) == "edit" || 
						$exec->getCrtParameters(0) == "copy" || 
						$exec->getCrtParameters(0) == "lockAndModify" || 
						$exec->getCrtParameters(0) == "checkInAndModify" || 
						$exec->getCrtParameters(0) == "checkinFile" || 
						$exec->getCrtParameters(0) == "delete" || 
						$exec->getCrtParameters(0) == "toggleElementGroupSharing" || 
						$exec->getCrtParameters(0) == "setGroupsContainingElement" || 
						$exec->getCrtParameters(0) == "restore")) {
					//create the element and fills it with data
					try {
						$element = $this->createElementForForm($p, $exec->getCrtModule(), $elementId);
						$elementP = $elS->fillElement($p, $element);
						// if subelement then gets element width for subelement
						if($element->isSubElement()) {
							$totalWidth = 0 + $configS->getParameter($p, $configS->getCurrentModule(), "elementTotalWidth");
							$labelWidth = 0 + $configS->getParameter($p, $configS->getCurrentModule(), "elementLabelWidth");
							// checks if parent is blocked
							foreach($configS->getCurrentSubElementPathFromRoot()->getListIterator() as $ls) {
								if($ls->isOwnerElementBlocked()) {
									$elementP->setParentElementState_blocked(true);
									break;
								}
							}
						}
					} catch (ServiceException $e) {
						if ($e->getCode() == ServiceException :: FORBIDDEN) {
							//if we try to retrieve an existing element but on which we have no rights, or in a module
							//we have no rights, then just make the elementP as null
							$elementP = ElementP :: createInstance($element);
						} else
							throw $e;
					}

					if ($elementP == null) {
						//the element is deleted
						$this->openAsMessage($exec->getIdAnswer(), $totalWidth - $labelWidth, $transS->t($p, "elementNotFound") . " (" . $transS->t($p, "id") . ": " . $elementId . ")", $transS->t($p, "elementNotFoundExplanation"), "actOnCloseDialog('".$exec->getIdAnswer()."');");
						break;
					} else if($exec->getCrtParameters(0) == "delete" && $configS->getParameter($p, $exec->getCrtModule(),'enableDeleteOnlyForAdmin')=="1" && !$elementP->getRights()->canModify()){
						$this->openAsMessage($exec->getIdAnswer(), $totalWidth - $labelWidth, $transS->t($p, "elementUnreachable") . " (" . $transS->t($p, "id") . ": " . $elementId . ")", $transS->t($p, "elementUnreachableExplanation"), "actOnCloseDialog('".$exec->getIdAnswer()."');");
						break;
					} else if($elementP->getRights() == null ||
							/* by default, allows only read access on blocked element except managed exceptions:
							 * - Medair (CWE) 29.01.2018: allows sharing of blocked elements if Element_Blocked_enableSharing is defined in config 
							 */
							(($element->isState_blocked() || $elementP->isParentElementState_blocked()) &&
							$exec->getCrtParameters(0) != "detail" &&
							$exec->getCrtParameters(0) != "addJournalItem" &&
							$exec->getCrtParameters(0) != "copy" &&
							$exec->getCrtParameters(0) != "print" &&
							$exec->getCrtParameters(0) == "setGroupsContainingElement" && !($this->evaluateConfigParameter($p, $exec, $configS->getParameter($p, $exec->getCrtModule(), "Element_Blocked_enableSharing"))=="1") )
							){
						$this->openAsMessage($exec->getIdAnswer(), $totalWidth - $labelWidth, $transS->t($p, "elementUnreachable") . " (" . $transS->t($p, "id") . ": " . $elementId . ")", $transS->t($p, "elementUnreachableExplanation"), "actOnCloseDialog('".$exec->getIdAnswer()."');");
						break;
					}
					//on edit or on doDelete lock try to lock the element:
					//do not lock on addJournalItem as it is a one shot action. This will be done with the appropriate p in $this->addJournalItem
					if ($exec->getCrtParameters(0) == "edit" || $exec->getCrtParameters(0) == "lockAndModify" || $exec->getCrtParameters(0) == "checkInAndModify" || $exec->getCrtParameters(0) == "checkinFile" || $exec->getCrtParameters(0) == "delete") {
						if (false === $this->lockEntity($p, $exec, "", "element", $element)){ //DbEntityInstance :: createInstance($elementId)))
							break;
						}
					}
					//on detail always unlock the element
					if ($exec->getCrtParameters(0) == "detail") {
						$elS->unLock($p, DbEntityInstance :: createInstance($elementId));
					}
					//add element to the current selected element in list context
					$this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList")->setCrtSelectedItem($elementId);
                    //on delete we need the file fields to do file management
					if ($exec->getCrtParameters(0) == "delete") {
						$fileFields = $configS->mf($p, $element->getModule())->xpath("*[@type='Files'] | *[@type='Blobs' and @htmlArea='1'] | *[@type='Texts' and @htmlArea='1']");
					} else $fileFields = null;
				} 
				// multiple element management
				else if ($elementId == "multiple" && (
							($exec->getCrtParameters(0) == "delete" && $_POST["action"]!=null) ||
							($exec->getCrtParameters(0) == "edit" && $_POST["action"]!=null) ||
							($exec->getCrtParameters(0) == "transfer" && $_POST["action"]!=null) ||
						 	 $exec->getCrtParameters(0) == "setGroupsContainingElements")) {

						$mlc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "multipleElementList");
						//if multiple operation but no multiple selection:
						if ($lc->getMultipleSelection() == null) {
							if ($exec->getIsUpdating()) {
								echo ExecutionServiceImpl :: answerRequestSeparator;
								echo "confirmationDialog";
								echo ExecutionServiceImpl :: answerParamSeparator;
								$this->openAsMessage("confirmationDialog", $totalWidth - $labelWidth, $transS->t($p, "selectAtLeastOneElement"), null, "actOnCloseDialog('confirmationDialog'); actOnCloseDialog('" .
								$exec->getIdAnswer() . "')");
								break;
							}
						}
						//create and fill an ElementPAdvancedList with the current multiple selection
						$mlc->resetFetchCriteria($p, $this);
						//for multiple operation other than edit, we just take a preview of the elements, the preview is the listView activity
						$fsl = FieldSelectorListForActivity :: createInstance(false, false);
						$fslForView = FieldSelectorListForActivity :: createInstance(false, false);
						$configS->getFields($p, $exec->getCrtModule(), Activity :: createInstance("listView"), $fsl);
						//add to the ElementPAList the fieldSelector which contains the values to display
						$fslForView->mergeFieldSelectorList($fsl);
						$fslForView->setSelectedLanguages(array ($transS->getLanguage() => $transS->getLanguage()));
												
						// creates the element list and records if all or at least one element is blocked.
						$elementPAList = ElementPAdvancedListArrayImpl :: createInstance('state_blocked', $fslForView);

						//add fieldSelector subfields for more needed information
						$fieldList = FormFieldList :: createInstance(null);
						$configS->getFields($p, $exec->getCrtModule(), null, $fieldList);
						$fileFields = null;
						foreach ($fieldList->getListIterator() as $field) {
							if ($field->getDataType() != null) $dataTypeName = $field->getDataType()->getDataTypeName();
							else continue;							
							$fieldXml = $field->getXml();
							$fieldName = $field->getFieldName();
							
							if (/* if multiple edit always takes all the fields to ensure correct recalculation of the element (and not only the modified ones) */
								($exec->getCrtParameters(0) == "edit" /*&& $_POST[$fieldName . "_check"] === "on"*/) || 
							 	/* for email notification purpose, takes fields having an email set */
								$dataTypeName == "Booleans" && isset ($fieldXml["email"]) ||
								($dataTypeName == "Attributs" && $fieldXml->xpath("child::attribute[@email]")) ||
								($dataTypeName == "MultipleAttributs" && $fieldXml->xpath("child::attribute[@email]"))
								) {
								//if the fieldSelector is already defined with a subfield
								//then remove the fieldSelector and add this general one								
								if ($fsl->containsField($fieldName)) {
									$fsl->removesField($fieldName);
								}
								$fsl->addFieldSelector($fieldName);
							}
							//always take all the calculated on fetch fields to keep data integrity. For these fields, then also takes the dependencies.
							if ($field->shouldCalculateOnFetch()) {
								$fsl->addFieldSelector($fieldName);
								$field->getFuncExpDependencies($fsl);
							}
							//for Emails field purpose
							if ($dataTypeName == "Emails") {
								if ($fsl->containsField($fieldName)) {
									$fsl->removesField($fieldName);
									$fsl->addFieldSelector($fieldName); //to include all subfields
								}
							}
							//for file management purpose
							if ($dataTypeName == "Files") {
								$fileFields[$fieldName] = $fieldXml;
								if ($exec->getCrtParameters(0) == "delete") {
									//we need the mime and the path. we cannot just add the fieldName without subFields
									//because in the activity fieldSelector there is the subfields, and then the subFIeld == null will be ignored
									$fsl->addFieldSelector($fieldName, "mime");
									$fsl->addFieldSelector($fieldName, "path");
									$fsl->addFieldSelector($fieldName, "textContent"); //to manage uploaded files in online file
								}
							} else if ($dataTypeName == "Blobs" && $fieldXml["htmlArea"]=="1") {
								$fileFields[$fieldName] = $fieldXml;
								if ($exec->getCrtParameters(0) == "delete") {
									$fsl->addFieldSelector($fieldName, "value");
								}
							} else if ($dataTypeName == "Texts" && $fieldXml["htmlArea"]=="1") {
								$fileFields[$fieldName] = $fieldXml;
								if ($exec->getCrtParameters(0) == "delete") {
									$fsl->addFieldSelector($fieldName, "value");
								}
							}
						}

						$mlc->setFieldSelectorList($fsl);
						$mlc->setConfigGroupList($configS->getGroupPList($p, $exec->getCrtModule()));
						// multiple operation needs to have no element blocked, except for managed exceptions:
						// - Medair (CWE) 29.01.2018: allows sharing of blocked elements if Element_Blocked_enableSharing is defined in config
						if($exec->getCrtParameters(0) != "setGroupsContainingElements") $mlc->setFieldSelectorLogExp(lxNotEq(fs_e('state_blocked'), true));
						//sorts according to default sorting key and group by key
						$mlc->setGroupByFieldSelectorList($fsl);
						$mlc->setSortByFieldSelectorList($fsl);
						$mlc->setGroupBy('reset'); $mlc->setSortedBy('reset');
						$nbRows = $elS->getSelectedElements($p, $lc->getMultipleSelection(), $elementPAList, $mlc);
						
						if($elementPAList->atLeastOneHasSpecificAttribut() 
						    && !(($exec->getCrtParameters(0) == "setGroupsContainingElements") && ($this->evaluateConfigParameter($p, $exec, $configS->getParameter($p, $exec->getCrtModule(), "Element_Blocked_enableSharing"))=="1"))
						) throw new AuthorizationServiceException("multiple operation is not authorized on blocked elements.", AuthorizationServiceException::FORBIDDEN);						
						if($exec->getCrtParameters(0) == "delete" && $configS->getParameter($p, $exec->getCrtModule(),'enableDeleteOnlyForAdmin')=="1" && !$elementPAList->allHaveAdminRights()) throw new AuthorizationServiceException("cannot delete elements in non admin groups.", AuthorizationServiceException::FORBIDDEN);
					}


				//select the current item in the list
				switch ($exec->getCrtParameters(0)) {
					case "add" :
					case "copy" :
						if ($elementId == "multiple") {
							throw new ServiceException("add element dosen't support multiple selection", ServiceException :: INVALID_ARGUMENT);
						}
						$action = $exec->getCrtRequest();
						$policyEval = $this->getElementPolicyEvaluator($p, $exec->getCrtModule());
						if ($exec->getCrtParameters(0) == "add") {
							$element = Element :: createInstance($exec->getCrtModule());
							$element->setSys_creationDate(time());
							$element->setSys_date(time());
							$form = $this->createAddElementFormExecutor($element, "addElement_form", $action, $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList"));
						} else {
							//copy
							$element->setId(0);
							$element->getWigiiBag()->loadFromFixedBag(); //recopy the fixed bag as normal bag without elementId
							// empties any hidden fields which have clearOnCopy active
							$hiddenClearOnCopyFields = $configS->mf($p, $element->getModule())->xpath("*[@hidden='1' and @clearOnCopy='1']");
							if($hiddenClearOnCopyFields) {
								$formBag = $element->getWigiiBag();
								foreach($hiddenClearOnCopyFields as $fieldXml) {
									$formBag->emptyFieldValue($fieldXml->getName());
								}
							}
							if(isset($policyEval)) $policyEval->initializeElementStateOnCopy($p, $element);
							$form = $this->createCopyElementFormExecutor($element, "copyElement_form", $action, $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList"));
						}
						$form->setCorrectionWidth(19);
						$form->setLabelWidth($labelWidth);
						$form->setTotalWidth($totalWidth);
						//					$form->setDottedLineSeparatorBetweenField(true);
						$form->setElementPolicyEvaluator($policyEval);

						$state = "start";
						if ($_POST["action"] != null)
							$state = addslashes($_POST["action"]);
						$form->ResolveForm($p, $exec, $state);
						break;
					case "print" :
					case "detail" :
						if ($elementId == "multiple") {
							throw new ServiceException("detail element dosen't support multiple selection", ServiceException :: INVALID_ARGUMENT);
						} else {
							$this->throwEvent()->readElement(PWithElement :: createInstance($p, $element));
							if($exec->getCrtParameters(0)=="detail"){
								$form = $this->createDetailElementFormExecutor($element, $elementP, "detailElement_form", null);
								//$form->setCorrectionWidth(26); //Commented by medair (LMA) No longer applicable with width to 100%
							} else {
								$form = $this->createPrintElementFormExecutor($element, $elementP, "detailElement_form", null);
							}

							$form->setLabelWidth($labelWidth);
							$form->setTotalWidth($totalWidth);
							$form->setElementPolicyEvaluator($this->getElementPolicyEvaluator($p, $element->getModule()));
							$form->ResolveForm($p, $exec, "start");
						}

						break;
					case "edit" :
					case "lockAndModify" :
					case "checkInAndModify" :
						$action = $exec->getCrtRequest();
						if ($elementId == "multiple") {
							$element = Element :: createInstance($exec->getCrtModule());
							$form = $this->createEditMultipleElementFormExecutor($element, "editMultipleElement_form", $action, $this->getRootPrincipal(), $elementPAList);
							$form->setIsMultiple(true);
							$form->setCorrectionWidth(19);
							$form->setLabelWidth($labelWidth);
							$form->setTotalWidth($totalWidth);
							$form->setListContext($mlc);
							$form->setListContextWithMultipleSelection($lc);
							$policyEval = $this->getElementPolicyEvaluator($p, $exec->getCrtModule());
							if(isset($policyEval)) {
								$policyEval->setIsMultiple(true);
								$form->setElementPolicyEvaluator($policyEval);
							}

							$state = "start";
							if ($_POST["action"] != null)
								$state = addslashes($_POST["action"]);
							$form->ResolveForm($p, $exec, $state);
						} else {
							if ($exec->getCrtParameters(0) == "lockAndModify" || ($exec->getCrtParameters(0) == "edit" && $this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Element_enableLockedStatusOnEdit") == "1")) {
								$fieldname = $exec->getCrtParameters(2);
								$form = $this->createLockAndModifyElementFormExecutor($element, "lockAndModifyElement_form", $action, $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList"), $fieldname);
							} else
								if ($exec->getCrtParameters(0) == "checkInAndModify") {
									$fieldname = $exec->getCrtParameters(2);
									$element->setState_locked(false);
									$form = $this->createCheckInAndModifyElementFormExecutor($element, "checkInAndModifyElement_form", $action, $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList"), $fieldname);
									$message = $_POST["message"];

									$field = $element->getFieldList()->getField($fieldname);
									$fieldxml = $field->getXml();
									$checkFieldname = (string) $fieldxml["checkField"];
									if($checkFieldname){
										$checkField = $element->getFieldList()->getField($checkFieldname);
										$this->checkOutInField($p, $exec, $element, $field, $checkField, $message, false);
									}
								} else {
									$form = $this->createEditElementFormExecutor($element, "editElement_form", $action, $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList"));
								}
							$form->setCorrectionWidth(19);
							$form->setLabelWidth($labelWidth);
							$form->setTotalWidth($totalWidth);
							//						$form->setDottedLineSeparatorBetweenField(true);
							$form->setElementPolicyEvaluator($this->getElementPolicyEvaluator($p, $element->getModule()));

							$state = "start";
							if ($_POST["action"] != null)
								$state = addslashes($_POST["action"]);
							$form->ResolveForm($p, $exec, $state);
						}
						break;
					case "delete" :
						$action = $exec->getCrtRequest();
						$labelWidth = 200;
						$totalWidth = 450;
						if ($elementId == "multiple") {
							$elementsDeleteRec = $this->createActivityRecordForForm($p, Activity :: createInstance("elementsDelete"), $exec->getCrtModule());
							$form = $this->createDeleteMultipleElementFormExecutor($elementsDeleteRec, "deleteMultipleElement_form", $action, $this->getRootPrincipal(), $elementPAList);
							$form->setElementPAList($elementPAList);
							$form->setFileFields($fileFields);
							$form->setCorrectionWidth(19);
							$form->setLabelWidth($labelWidth);
							$form->setTotalWidth($totalWidth);
							$form->setListContext($mlc);

							$state = "start";
							if ($_POST["action"] != null)
								$state = addslashes($_POST["action"]);
							$form->ResolveForm($p, $exec, $state);
						} else {
							$elementDeleteRec = $this->createActivityRecordForForm($p, Activity :: createInstance("elementDelete"), $exec->getCrtModule());
							$form = $this->createDeleteElementFormExecutor($elementDeleteRec, "deleteElement_form", $action, $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList"));
							$form->setElementPToDelete($elementP);
							$form->setFileFields($fileFields);
							$form->setCorrectionWidth(19);
							$form->setLabelWidth($labelWidth);
							$form->setTotalWidth($totalWidth);

							$state = "start";
							if ($_POST["action"] != null)
								$state = addslashes($_POST["action"]);
							$form->ResolveForm($p, $exec, $state);
						}
						break;
					case "checkinFile" :
						$elementId = $exec->getCrtParameters(1);
						$fieldName = $exec->getCrtParameters(2);

						$fe = $this->createFormExecutor();
						$fe->preventInjectionForm($p, $exec);
						$message = $_POST["message"];
						$elementDialogId = $_POST["elementDialogId"];

						//update do checkInAndModify
						$exec->addRequests($elementDialogId."/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/element/checkInAndModify/" . $elementId . "/" . $fieldName);
						break;

					case "getGroupsContainingElement" :
						$gl = GroupListAdvancedImpl :: createInstance();
						$element = $this->createElementForForm($p, $exec->getCrtModule(), $elementId);						
						$elS->getAllGroupsContainingElement($p, $element, $gl);
						echo $transS->t($p, "organizeElementTitle");
						echo ExecutionServiceImpl :: answerParamSeparator;
						echo $gl->count();
						echo ExecutionServiceImpl :: answerParamSeparator;
						echo $transS->t($p, "organizeNbSelectedGroups");
						echo ExecutionServiceImpl :: answerParamSeparator;
						echo implode(",", $gl->getIds());
						break;
					case "setGroupsContainingElement" :
						if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();
						
						$fe = $this->createFormExecutor();
						$fe->preventInjectionForm($p, $exec);
						
						$fromDrag = $exec->getCrtParameters(2)=="drag";						
						$requestedSharing = explode(",", $_POST["actual"]);
						if(!empty($requestedSharing)) $requestedSharing = array_combine($requestedSharing, $requestedSharing);
						
						// Medair (CWE) 02.02.2018 blocks sharing to trashbin or any subfolders if element is blocked.
						if($element->isState_blocked()) {
						    $trashBinGroups = ServiceProvider::getWigiiBPL()->groupGetTrashbin($p, $this, wigiiBPLParam('includeSubGroups',true));
						    if(!empty($trashBinGroups) && !empty(array_intersect_key($requestedSharing, $trashBinGroups))) {
						        throw new AuthorizationServiceException("A blocked element cannot be shared in trashbin", AuthorizationServiceException::FORBIDDEN);	
						    }
						}
						
						$originalSharing = explode(",", $_POST["original"]);						
						if(!empty($originalSharing)) $originalSharing = array_combine($originalSharing, $originalSharing);
						// if originalSharing contains 'all' keyword then replaces it with all groups containing elements
						if($originalSharing['all']) {
							$searchContext = true;
							unset($originalSharing['all']);
							$gl = GroupListAdvancedImpl :: createInstance();
							$elS->getAllGroupsContainingElement($p, $element, $gl);
							foreach($gl->getListIterator() as $group) {
								$groupId = $group->getId();
								$originalSharing[$groupId] = $groupId;
							}
						}
						// if drag and drop, then removes from folder and all subfolders
						elseif($fromDrag && !empty($originalSharing)) {
							$gl = GroupListAdvancedImpl :: createInstance();
							// gets groups containing element
							$elS->getAllGroupsContainingElement($p, $element, $gl);
							// intersect groups containing element with folder and subfolders.
							$groupsContainingElement = lxIn(fs('id'),$gl->getGroupIds());
							$gl->reset(); 
							$groupAS->getSelectedGroupsWithChildrenWithoutDetail($p, lxIn(fs('id'),array_keys($originalSharing)), $gl,null,$groupsContainingElement);
							foreach($gl->getListIterator() as $group) {
								$groupId = $group->getId();
								$originalSharing[$groupId] = $groupId;
							}
							// removes destination group from original (occurs when drag and drop in same folder, but removing child sharing).
							$originalSharing = array_diff_key($originalSharing, $requestedSharing);
							
							$searchContext = false;
						}
						else $searchContext = false;
						
						$merged = array_merge($requestedSharing, $originalSharing);
						
						//this loop first go through the requested sharing
						//this means that first the add sharing are done
						//then the unshare are done
						//this is important otherwise we could remove the last sharing and generate an exception
						$changeOccuredInGroup = array ();
						$removeOccuredInGroup = array ();
						$addOccuredInGroup = array ();
						if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();
						foreach ($merged as $groupId) {
							if ($groupId == null)
								continue;
							$group = ServiceProvider :: getGroupAdminService()->getGroupWithoutDetail($p, $groupId);
							if ($requestedSharing[$groupId] && !$originalSharing[$groupId]) {
								//add sharing
								$changeOccuredInGroup[$groupId] = $groupId;
								$addOccuredInGroup[$groupId] = $groupId;
								$elS->shareElement($p, $elementId, array (
									$groupId
								));
								$this->throwEvent()->shareElement(PWithElementWithGroup :: createInstance($p, $element, $group));
							} else
								if (!$requestedSharing[$groupId] && $originalSharing[$groupId]) {
									//remove sharing
									$changeOccuredInGroup[$groupId] = $groupId;
									$removeOccuredInGroup[$groupId] = $groupId;
									$elS->unshareElement($p, $elementId, array (
										$groupId
									));
									$this->throwEvent()->unshareElement(PWithElementWithGroup :: createInstance($p, $elementP->getElement(), $group));
								} else {
									//no change
								}
						}

						$this->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

						// in case of a search refreshes the whole module view to show again correctly the search result
						if($searchContext) {
							$exec->invalidCache($p, 'moduleView');
							$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");
							$exec->addRequests("NoAnswer/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/countElementsInGroupPanel/");
						}
						else {
							//invalid cache in each changed group
							foreach ($changeOccuredInGroup as $groupId) {
								$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $groupId);
								//here, we cannot invalidate the cache of a possible selectGroupAndChildren. To heavy to invalidate each cache of parent groups in case of a listing
							}
	
							//remove current item in list if current group is deleted
							$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
							if ($elementListContext->getGroupPList()->count() == 1) {
								$ids = $elementListContext->getGroupPList()->getIds();
								$ids = reset($ids);
								if ($addOccuredInGroup[$ids]) {
									$exec->addJsCode("addElementInList('" . $elementId . "');");
								}
								if ($removeOccuredInGroup[$ids]) {
									$exec->addJsCode("removeElementInList('" . $elementId . "');");
								}
							}
						}
									
						//invalid cache of element detail
						$exec->invalidCache($p, 'elementDialog', "selectElementDetail", "element/detail/" . $elementId);

						break;
					case "getGroupsContainingElements" :
						$countData = $elS->countSelectedElementsDistribution($p, $lc->getMultipleSelection());
						echo $transS->t($p, "organizeElementsTitle");
						echo ExecutionServiceImpl :: answerParamSeparator;
						echo "originalFolders = {";
						$first = true;
						foreach($countData as $key=>$value){
							if($first) $first = false;
							else echo ", ";
							echo $key.' : '.$value;
						}
						echo "}";
						break;
					case "setGroupsContainingElements" :
						if(!isset($groupAS)) $groupAS = ServiceProvider::getGroupAdminService();

						$fe = $this->createFormExecutor();
						$fe->preventInjectionForm($p, $exec);
						
						$changeToShare = array();						
						if($_POST["changeToSelected"]){
							$changeToShare = explode(",", $_POST["changeToSelected"]);
							if(!empty($changeToShare)) $changeToShare = array_combine($changeToShare, $changeToShare);
							
							// Medair (CWE) 02.02.2018 blocks sharing to trashbin or any subfolders if one element is blocked.
							if($elementPAList->atLeastOneHasSpecificAttribut()) {
							    $trashBinGroups = ServiceProvider::getWigiiBPL()->groupGetTrashbin($p, $this, wigiiBPLParam('includeSubGroups',true));
							    if(!empty($trashBinGroups) && !empty(array_intersect_key($changeToShare, $trashBinGroups))) {
							        throw new AuthorizationServiceException("Blocked elements cannot be shared in trashbin", AuthorizationServiceException::FORBIDDEN);
							    }
							}
							
							//eput("add sharing for ".implode(", ", $changeToShare));
							$groupPList = $elS->addMultipleElementSharing($this->getRootPrincipal(), $p, $elementPAList, $changeToShare);
							$this->throwEvent()->addMultipleElementSharing(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $elementPAList, $groupPList));
						}
						$changeToUnshare = array();
						if($_POST["changeToUnselected"]){
							$changeToUnshare = explode(",", $_POST["changeToUnselected"]);
							if(!empty($changeToUnshare)) $changeToUnshare = array_combine($changeToUnshare, $changeToUnshare);
							
							//eput("remove sharing for ".implode(", ", $changeToUnshare));
							try{
								$groupPList = $elS->removeMultipleElementSharing($this->getRootPrincipal(), $p, $elementPAList, $changeToUnshare);
								$this->throwEvent()->removeMultipleElementSharing(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $elementPAList, $groupPList));
							} catch(ElementServiceException $ese){
								if($ese->getCode()==ElementServiceException::DATA_INTEGRITY_ERROR){
									$this->openAsMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationUnsuccessfull"), $transS->t($p, "someElementsHasBeenPreventedToBeOrphans"), null, $transS->t($p, "ok"), null, null, $iconType = "info");
									$exec->addJsCode("openOrganizeMultipleDialog();");
									break;
								} else {
									throw $ese;
								}
							}
						}
						//important to clear the cancel stack
						$exec->addJsCode("if($('#organizeDialog').is(':ui-dialog')){ $('#organizeDialog').dialog('destroy'); } actOnCloseDialog('organizeDialog');");

						$this->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

						$exec->invalidCache($p, 'elementDialog');
						$exec->invalidCache($p, 'moduleView');

						$crtGroupP = null;
						if($lc->getGroupPList()->count()==1){
							$crtGroupP = reset($lc->getGroupPList()->getListIterator());
						}
						if(!$crtGroupP || ($crtGroupP && ($changeToUnshare[$crtGroupP->getId()] || $changeToShare[$crtGroupP->getId()]))){ //the list needs to be refreshed if current folder is removed
							$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");
						}
						break;
					case "restore" :
						if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
							throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

						if (!isset ($configS))
							$configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();

						//only for subelement
						if (!$element->isSubElement())
							throw new ServiceException("restore is available only sub element", ServiceException :: UNSUPPORTED_OPERATION);

						if ($_POST["action"]!="check")
							throw new ServiceException("restore requires post information (this is a possible Request Forgery attack, please contact your administrator!)", ServiceException :: UNSUPPORTED_OPERATION);

						$elS->renameSubElementsLink($p, $elementId, null, (string)$configS->getParameter($p, null, "deletedSubElementsLinkNamePrefix"));
						$this->throwEvent()->restoreElement(PWithElementWithGroupPList::createInstance($p, $element, ($configS->getGroupPList($p, $exec->getCrtModule())->count()==1 ? $configS->getGroupPList($p, $exec->getCrtModule()) : null)));

						$this->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
						$exec->addRequests("elementDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/detail/".$element->getElementParentId());
						break;
					case "transfer" :
						if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
							throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

						if (!isset ($configS))
							$configS = $this->getConfigurationContext(); //ServiceProvider::getConfigService();

						//only for multiple
						if ($elementId != "multiple")
							throw new ServiceException("share is available only for multiple element selection", ServiceException :: UNSUPPORTED_OPERATION);

						$totalWidth = 450;
						$labelWidth = 200;

						//transferMultipleElement acitivity config is independant of the module
						$transferMultipleElementRec = $this->createActivityRecordForForm($p, Activity :: createInstance("transferMultipleElement"), $exec->getCrtModule());

						$action = $exec->getCrtRequest();
						$form = $this->createTransferMultipleElementFormExecutor($transferMultipleElementRec, "transferMultipleElement_form", $action, $this->getRootPrincipal(), $elementPAList, $lc);
						$form->setCorrectionWidth(19);
						$form->setLabelWidth($labelWidth);
						$form->setTotalWidth($totalWidth);

						$state = "start";
						if ($_POST["action"] != null)
							$state = addslashes($_POST["action"]);
						$form->ResolveForm($p, $exec, $state);

						break;
						/**
						 * manageEmail is the entry point for sending email to the field content with different purpose:
						 * - confirmation email
						 * - external access email in read
						 * - external access email in write
						 * - stop external access
						 */
					case "manageEmails" :
						if (ServiceProvider :: getAuthorizationService()->isPublicPrincipal($p))
								throw new ServiceException("principal cannot be public for manageEmail action", ServiceException :: FORBIDDEN);

						if(!isset($elS)) $elS = ServiceProvider::getElementService();
						if (!isset ($groupAS)) $groupAS = ServiceProvider :: getGroupAdminService();
						
						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						//if multiple operation but no multiple selection:
						if ($lc->getMultipleSelection() == null) {
							break; //do nothing if no multiple selection
						}
						$fe = $this->createFormExecutor();
						$fe->preventInjectionForm($p, $exec);

						$for = $_POST["externalAction"];
						$endDate = $_POST["externalEndDate"];
						$d = $m = $y = $h = $i = $s = null;
						if ($endDate && Dates :: fromString($endDate, $d, $m, $y, $h, $i, $s)) {
							if (($h || $i || $s) && !($h == 0 && $i == 0 && $s == 0)) $time = "$h:$i:$s";
							else $time = "23:59:59"; //end of the day by default
							$endDate = strtotime("$y/$m/$d $time"); //convert in timestamp
						} else if ($endDate) {
							//invalid date
							throw new ServiceException("invalid date for external access end date: " . $endDate, ServiceException :: INVALID_ARGUMENT);
						}											
						// checks requestedAccessLevel
						switch ($for) {
							case "externalAccessMenuViewLink" :
								$requestedAccessLevel = Emails::EXTERNAL_ACCESS_VIEW;
								break;
							case "externalAccessMenuEditLink" :
								$requestedAccessLevel = Emails::EXTERNAL_ACCESS_EDIT;
								break;
							case "externalAccessMenuStop" :
								$requestedAccessLevel = Emails::EXTERNAL_ACCESS_STOP;
								break;
							default :
								throw new ServiceException("invalid manage email action: " . $for, ServiceException :: INVALID_ARGUMENT);
						}
						
						// define the group id for config
						$configGroupList = $this->getConfigurationContext()->getGroupPList($p, $exec->getCrtModule());
						if ($configGroupList->count() != 1) {
							$configGroupList = null; //if there is more than one group selected, then take the config of the module
						}
						
						// prepares email field list
						$emailFieldsXml = $this->canCrtModuleEmailing($exec->getCrtModule());						
						$emailFieldsAccessLevel = array();
						$emailFieldsConfigGroupList = array();	
						$emailReducedAccessLevel = array(Emails::EXTERNAL_ACCESS_STOP=>array(),Emails::EXTERNAL_ACCESS_VIEW=>array());
						$emailReducedAccessLevelCount = 0;				
						foreach ($emailFieldsXml as $xmlField) {							
							$fieldName = $xmlField->getName();
							
							// calculates real access level using maxExternalAccessLevel if defined
							if($xmlField["maxExternalAccessLevel"]=="0") {								
								$emailFieldsAccessLevel[$fieldName] = Emails::EXTERNAL_ACCESS_STOP;
							} elseif($xmlField["maxExternalAccessLevel"]=="1") {								
								$emailFieldsAccessLevel[$fieldName] = Emails::EXTERNAL_ACCESS_VIEW;
							} else $emailFieldsAccessLevel[$fieldName] = $requestedAccessLevel;
							
							// checks if calculated access level is less than requested access level
							$calculatedAccessLevel = $emailFieldsAccessLevel[$fieldName];
							if($calculatedAccessLevel < $requestedAccessLevel) {
								$emailReducedAccessLevel[$calculatedAccessLevel][$fieldName] = $fieldName;
								$emailReducedAccessLevelCount++;
							}

							// define the group id for config
							$externalAccessConfigGroup=(string)$xmlField["externalAccessConfigGroup"];
							if(!empty($externalAccessConfigGroup)) {
								$g = $groupAS->getGroupWithoutDetail($p, $externalAccessConfigGroup); /* only checks for group existence and not principal rights */
								if(!isset($g)) throw new ServiceException("Invalid externalAccessConfigGroup ".$externalAccessConfigGroup." for field ".$fieldName, ServiceException::CONFIGURATION_ERROR);
								$emailFieldsConfigGroupList[$fieldName] = GroupPListArrayImpl::createInstance()->addGroupP(GroupP::createReadInstance($g));
							}
							else $emailFieldsConfigGroupList[$fieldName] = $configGroupList;
						}
						try {
							// stops externalAccess for all fields
							if($requestedAccessLevel == Emails::EXTERNAL_ACCESS_STOP) {
								$elS->stopMultipleExternalAccess($p, $lc->getMultipleSelection(), array_keys($emailFieldsAccessLevel));
							}
							// else if all email fields have a reduced calculated access level throws an exception to inform the user					
							elseif($emailReducedAccessLevelCount == count($emailFieldsAccessLevel)) {
								$errorMessage = "Invalid multiple request. Cannot open external access".($requestedAccessLevel==Emails::EXTERNAL_ACCESS_EDIT?" for edit":'');
								$emailFields = $emailReducedAccessLevel[Emails::EXTERNAL_ACCESS_STOP];
								$sep = '';
								if(!empty($emailFields)) {
									$errorMessage .= " if maxExternalAccessLevel is defined to ".Emails::EXTERNAL_ACCESS_STOP." on ".implode(",", $emailFields).".";
									$sep = ' and';
								}
								$emailFields = $emailReducedAccessLevel[Emails::EXTERNAL_ACCESS_VIEW];
								if(!empty($emailFields)) {
									$errorMessage .= $sep." if maxExternalAccessLevel is defined to ".Emails::EXTERNAL_ACCESS_VIEW." on ".implode(",", $emailFields).".";
								}
								throw new ServiceException($errorMessage,ServiceException :: INVALID_ARGUMENT);
							}						
							// else defines externalAccess for each email field
							else {
								foreach($emailFieldsAccessLevel as $emailField=>$accessLevel) {
									if($accessLevel == Emails::EXTERNAL_ACCESS_VIEW) $elS->setMultipleExternalAccessForView($p, $lc->getMultipleSelection(), $emailField, $endDate, $emailFieldsConfigGroupList[$emailField]);
									elseif($accessLevel == Emails::EXTERNAL_ACCESS_EDIT) $elS->setMultipleExternalAccessForEdit($p, $lc->getMultipleSelection(), $emailField, $endDate, $emailFieldsConfigGroupList[$emailField]);						
								}
							}
							
							$exec->invalidCache($p, 'elementDialog');
							$this->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
						} catch (AuthorizationServiceException $ase){
							if($ase->getCode()==AuthorizationServiceException::OBJECT_IS_LOCKED){
								//lookup locked items and report on it
								$this->displayListOfLockedItems($p, $lc->getMultipleSelection());
							} else {
								throw $ase;
							}
						}
						break;
					case "manageEmail" :
						//check the access to the element
						if (ServiceProvider :: getAuthorizationService()->isPublicPrincipal($p))
							throw new ServiceException("principal cannot be public for manageEmail action", ServiceException :: FORBIDDEN);

						//at this point that means the user is authentified and has access to the element.
						//elementP contains the access right on it. The ConfigurationContext have
						//the current selected groupList.
						$fe = $this->createFormExecutor();
						$fe->preventInjectionForm($p, $exec);

						$code = $_POST["externalCode"];
						$elementId = $_POST["externalElementId"];
						//					$email = $_POST["email"];
						$fieldName = $_POST["externalFieldName"];
						$for = $_POST["externalAction"];
						$message = $_POST["externalMessage"];
						$subject = $_POST["externalSubject"];
						$endDate = $_POST["externalEndDate"];
						$d = $m = $y = $h = $i = $s = null;
						//					eput($code." ".$elementId." ".$fieldName." ".$for);
						//					echo alert($elementP);
						if ($endDate && Dates :: fromString($endDate, $d, $m, $y, $h, $i, $s)) {
							if (($h || $i || $s) && !($h == 0 && $i == 0 && $s == 0))
								$time = "$h:$i:$s";
							else
								$time = "23:59:59"; //end of the day by default
							$endDate = strtotime("$y/$m/$d $time"); //convert in timestamp
						} else {
							if ($endDate) {
								//invalid date
								throw new ServiceException("invalid date for external access end date: " . $endDate, ServiceException :: INVALID_ARGUMENT);
							}
						}

						//email menu should not be linked to enableEmailing or not
//						if($for == "externalAccessMenuViewLink" || $for == "externalAccessMenuEditLink" || $for == "externalAccessMenuStop"){
//							$emailFieldsXml = $this->canCrtModuleEmailing($exec->getCrtModule());
//							$found = false;
//							$limitToRead = false;
//							foreach ($emailFieldsXml as $xmlField) {
//								if($xmlField->getName()==$fieldName){
//									$found = true;
//									if($xmlField["maxExternalAccessLevel"]=="0"){
//										throw new ServiceException("invalid request. Cannot open external access if maxExternalAccessLevel is defined to 0 on " . $xmlField->getName(), ServiceException :: INVALID_ARGUMENT);
//									}
//									if($xmlField["maxExternalAccessLevel"]=="1"){
//										$limitToRead = true;
//									}
//								}
//							}
//							if(!$found){
//								throw new ServiceException("Email field $fieldName is not found in configuration to open external access.", ServiceException :: INVALID_ARGUMENT);
//							}
//						}

						//act on Email field
						try {
							switch ($for) {
								case "externalAccessMenusendEmail" :
									$this->sendEmail($p, $exec, $elementP, $fieldName, $message, $subject);
									break;
								case "externalAccessMenuValidationLink" :
									$this->sendExternalValidationLink($p, $exec, $elementP, $fieldName, $code, $message, $subject);
									break;
								case "externalAccessMenuViewLink" :
									$this->setExternalAccessViewLink($p, $exec, $elementP, $fieldName, $code, $endDate, $message, $subject);
									break;
								case "externalAccessMenuEditLink" :
									if($limitToRead){
										throw new ServiceException("invalid request. Cannot open external edit access if maxExternalAccessLevel is defined to 1 on " . $fieldName, ServiceException :: INVALID_ARGUMENT);
									}
									$this->setExternalAccessEditLink($p, $exec, $elementP, $fieldName, $code, $endDate, $message, $subject);
									break;
								case "externalAccessMenuStop" :
									$this->stopExternalAccess($p, $exec, $elementP, $fieldName, $code, $message);
									break;
								default :
									throw new ServiceException("invalid manage email action: " . $for, ServiceException :: INVALID_ARGUMENT);
							}
						} catch (AuthorizationServiceException $ase) {
							if ($ase->getCode() == AuthorizationServiceException :: OBJECT_IS_LOCKED) {
								$this->displayLockMessage($p, $exec, null, "element", $elementP);
								break;
							} else
								throw $ase;
						}
						$exec->addRequests($exec->getIdAnswer()."/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/element/detail/" . $elementId);

						break;
					case "addJournalItem":
						if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
								throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);

						$this->addJournalItem($p, $exec, $transS, $elS, $elementP, false);
						break;
//					case "switchMultipleSelection":
//						//open/close multiple dialog
//						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
//						if(formatBoolean($exec->getCrtParameters(0))){
//							//$lc->resetMultipleSelection();
//
//							$this->updateMultipleSelectionDialogForContext($p, $exec, $lc);
//						} else {
//							$lc->resetMultipleSelection();
//						}
//						break;
					case "displayMultipleDialog":
						//open/close multiple dialog
						if(!isset($transS)) $transS = ServiceProvider::getTranslationService();

						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						$width = 480;

						$crtWigiiNamespace = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl();
						$crtModule = $exec->getCrtModule()->getModuleUrl();

						//multiple toolBar
						//multiple summary
						?><div class="summary"><?
							?><span class="multipleSelectionNb"></span><?
							echo "&nbsp;".$transS->t($p, "elementsSelected");
						?></div><?
						?><div class="clear"></div><?

						?><div class="toolBar"><?
						//multiple emailing
						$emailFieldsXml = $this->canCrtModuleEmailing($exec->getCrtModule());
						$isEmailing = $emailFieldsXml != null;
						$isExternalAccessRead = false;
						$isExternalAccessEdit = false;
						if($isEmailing){
							// CWE 30.10.2015: if at least one field allows write access, then enable the user to choose write access
							// on server side, when posting, it will do only what it can.
							foreach ($emailFieldsXml as $xmlField) {
								if($xmlField["maxExternalAccessLevel"]=="0"){
									/* nothing to do */
								}
								elseif($xmlField["maxExternalAccessLevel"]=="1"){
									$isExternalAccessRead = true;
								}
								else {
									$isExternalAccessEdit = true;
									$isExternalAccessRead = true;
								}
							}
						}
						if($isEmailing){
							?><div class="emailingMultiple H G SBIB" onclick="update('emailingDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/Emailing');"><img style="margin-top:-10px;" src="<?=SITE_ROOT_forFileUrl;?>images/icones/22x22/iconenvelopewhite22x22.png" /><?=$transS->t($p, "emailingButton");?></div><?
						}
						//multiple external access
						if($isEmailing && $isExternalAccessRead){
							//style="position:absolute;padding-top:0px;margin-top:30px;margin-left:-5px;font-size:x-small;"
							?><div class="multipleExternalAccess H G SBIB onlyWriteRights" onmouseover="showHelp(this, '<?=$transS->h($p, "multipleExternalAccess_help");?>', 30, 'fromCenter', 0, 250, 0);" onmouseout="hideHelp();"><?=$transS->t($p, "multipleExternalAccess");?></div><?
						}

						//unsubscribeEmails
						$unsubscribeEmailsFieldXml = $this->canCrtModuleUnsubscribeEmails($exec->getCrtModule());
						if($unsubscribeEmailsFieldXml!=null){
							?><div class="multipleUnsubscribeEmails H G SBIB onlyWriteRights" onclick="update('emailingDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/unsubscribeEmails');" onmouseover="showHelp(this, '<?=$transS->h($p, "multipleUnsubscribeEmails_help");?>', 30, 'fromCenter', 0, 250, 0);" onmouseout="hideHelp();"><?=$transS->t($p, "multipleUnsubscribeEmails");?></div><?
						}

						if(($isEmailing && $isExternalAccessRead) || $unsubscribeEmailsFieldXml!=null){
							?><div style="margin:0px;padding:0px;clear:both;"></div><?
						}

						//multiple downloading
						if($this->canCrtModuleDownloading($exec->getCrtModule())){
							?><div class="downloadMultiple H G SBIB" onclick="update('downloadingDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/Downloading');"><img src="<?=SITE_ROOT_forFileUrl;?>images/icones/22x22/Icon-download-22.png" /><?=$transS->t($p, "downloadingElements");?></div><?
							//add in url a date parameter to limit files newer than the parameter (format: yyyy-mm-dd hh:mm:ss)
							if(false) { ?><div class="exportDownloadMultiple H G SBIB" onclick="update('downloadingDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/ExportDownload/2012-1-1');"><img src="<?=SITE_ROOT_forFileUrl;?>images/icones/22x22/Icon-download-22.png" /><?=$transS->t($p, "exportDownloadingElements");?></div><? }
						}
						//multiple export
						//Export menu
						$this->includeExportMultipleMenu($p, $exec, $transS, $configS);

						//multiple print
						if(false){
							?><a class="printMultiple H G SBIB" href="<?=SITE_ROOT;?><?=$crtWigiiNamespace;?>/<?=$crtModule;?>/element/print/multiple" target="_blank"><?=$transS->t($p, "printButton");?></a><?
						}

						?></div><?
						?><div class="clear"></div><?

						?><div class="toolBar"><?
							//multiple select all
							?><div class="selectAll H" onclick="selectAllMultipleSelect();"><?=$transS->t($p, "selectMultipleAll");?></div><?
							//multiple clear all
							?><div class="resetAll H " onclick="deselectAllMultipleSelect(); "><?=$transS->t($p, "resetMultipleSelection");?></div><?
							//view selection
							?><div class="viewAll H" onclick="update('NoAnswer/'+crtWigiiNamespaceUrl+'/'+crtModuleName+'/filterOnMultipleSelection');"><?=$transS->t($p, "viewMultipleSelection");?></div><?

							//multiple edit
							?><div class="editMultiple onlyWriteRights H" onclick="update('elementDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/element/edit/multiple');"><?=$transS->t($p, "editElements");?></div><?
							//multiple share deprecated, replaced on 20/07/2012
							if(false) { ?><div class="shareMultiple onlyWriteRights H" onclick="update('elementDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/element/share/multiple');"><?=$transS->t($p, "shareElements");?></div><? }
							//multiple organize
							?><div class="organizeMultiple onlyWriteRights H" onclick="openOrganizeMultipleDialog();"><?=$transS->t($p, "shareElements");?></div><?
							//multiple delete
							$responseDiv = 'elementDialog';
							if($this->isWorkzoneViewDocked()) $responseDiv = 'confirmationDialog';
							?><div class="deleteMultiple onlyWriteRights H" onclick="update('<?=$responseDiv;?>/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/element/delete/multiple/elementDialog');"><?=$transS->t($p, "deleteElements");?></div><?

							if($configS->m($p, $exec->getCrtModule())->transferMultipleElement != null && @count($configS->m($p, $exec->getCrtModule())->transferMultipleElement->children())!=null){
								//multiple transfer
								?><div class="transferMultiple onlyWriteRights H" onclick="update('elementDialog/<?=$crtWigiiNamespace;?>/<?=$crtModule;?>/element/transfer/multiple');"><?=$transS->t($p, "transferElements");?></div><?
							}

							if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableLockedStatus")=="1" ||
								$configS->getParameter($p, $exec->getCrtModule(), "Element_enableBlockedStatus")=="1" ||
								$configS->getParameter($p, $exec->getCrtModule(), "Element_enableImportant1Status")=="1" ||
								$configS->getParameter($p, $exec->getCrtModule(), "Element_enableImportant2Status")=="1" ||
								$configS->getParameter($p, $exec->getCrtModule(), "Element_enableFinalizedStatus")=="1" ||
								$configS->getParameter($p, $exec->getCrtModule(), "Element_enableApprovedStatus")=="1" ||
								$configS->getParameter($p, $exec->getCrtModule(), "Element_enableDimissedStatus")=="1" ||
								$configS->getParameter($p, $exec->getCrtModule(), "Element_enableArchivedStatus")=="1" ||
								$configS->getParameter($p, $exec->getCrtModule(), "Element_enableDeprecatedStatus")=="1" ||
								$configS->getParameter($p, $exec->getCrtModule(), "Element_enableHiddenStatus")=="1"
							){
							?><div class="elementStates onlyWriteRights" style="height:auto;"><div><?=$transS->t($p, "setElementsState");?>:</div><?
								//multiple element state locked
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableLockedStatus")=="1") {
									?><div class="lockedMultiple onlyWriteRights unchecked" href="#locked"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_locked");?></span></div><?
								}
								//multiple element state blocked
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableBlockedStatus")=="1") {
									?><div class="blockedMultiple onlyWriteRights unchecked" href="#blocked"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_blocked");?></span></div><?
								}
								//multiple state important1
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableImportant1Status")=="1") {
									?><div class="important1Multiple onlyWriteRights unchecked" href="#important1"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_important1");?></span></div><?
								}
								//multiple state important2
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableImportant2Status")=="1") {
									?><div class="important2Multiple onlyWriteRights unchecked" href="#important2"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_important2");?></span></div><?
								}
								//multiple state finalized
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableFinalizedStatus")=="1") {
									?><div class="finalizedMultiple onlyWriteRights unchecked" href="#finalized"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_finalized");?></span></div><?
								}
								//multiple state approved
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableApprovedStatus")=="1") {
									?><div class="approvedMultiple onlyWriteRights unchecked" href="#approved"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_approved");?></span></div><?
								}
								//multiple state dismissed
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableDismissedStatus")=="1") {
									?><div class="dismissedMultiple onlyWriteRights unchecked" href="#dismissed"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_dismissed");?></span></div><?
								}
								//multiple state archived
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableArchivedStatus")=="1") {
									?><div class="archivedMultiple onlyWriteRights unchecked" href="#archived"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_archived");?></span></div><?
								}
								//multiple state deprecated
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableDeprecatedStatus")=="1") {
									?><div class="deprecatedMultiple onlyWriteRights unchecked" href="#deprecated"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_deprecated");?></span></div><?
								}
								//multiple state hidden
								if( $configS->getParameter($p, $exec->getCrtModule(), "Element_enableHiddenStatus")=="1") {
									?><div class="hiddenMultiple onlyWriteRights unchecked" href="#hidden"><span class="tag ui-corner-all" style="padding:2px 10px 2px 10px;white-space:nowrap;float:left;margin-bottom:4px;margin-right:5px;"><?=$transS->t($p, "state_hidden");?></span></div><?
								}
							?></div><?
							}
						?></div><?
						?><div class="clear"></div><?


						//show dialog
						$this->openAsDialogForm("multipleDialog", $width, "", " ", $transS->t($p, "viewMultipleSelection"), $transS->t($p, "stopAndCloseMultipleSelectionDialog"), null, '{ my : "right bottom", at: "right-40 bottom+20" }', "setSelectionMode(false);", false, false, false, true);

						$exec->addJsCode("" .
							"setListenerToMultipleDialog(" .
								"'".($isExternalAccessRead ? $transS->h($p, "externalAccessMenusendEmailTitle") : "")."'," .
								"'".($isExternalAccessRead ? $transS->h($p, "multipleExternalAccessMenuView") : "")."'," .
								"'".($isExternalAccessEdit ? $transS->h($p, "multipleExternalAccessMenuEdit") : "")."'," .
								"'".$transS->h($p, "multipleExternalAccessMenuStop")."'," .
								"'".$transS->h($p, "externalAccessMenuEndDate")."'," .
								"'".$transS->h($p, "ok")."'," .
								"'".$transS->h($p, "cancel")."'," .
								"'".$transS->h($p, "areYouSureToStopExternalAccess")."'" .
								");" .
							"");
						$this->updateMultipleSelectionDialogForContext($p, $exec, $lc);

						break;
					case "stopMultipleSelection":
						//open/close multiple dialog
						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						$lc->resetMultipleSelection();
						$this->updateMultipleSelectionDialogForContext($p, $exec, $lc);
						break;
					case "setMultipleSelectionFolder" :
						$configS = $this->getConfigurationContext();
						$groupAS = ServiceProvider::getGroupAdminService();

						//add a complete folder
						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						$groupId = $exec->getCrtParameters(1);
						$add = formatBoolean($exec->getCrtParameters(2));

						//if current context is onlyDuplicates, then the listContext contains in it self the ids
						if($lc->isGroupByOnlyDuplicates()){
							if($add){
								$lc->addItemsToMultipleSelection($lc->getDuplicatesIds(), $lc->getDuplicatesElementEnableStates(), $lc->getDuplicatesElementStates());
							} else {
								$lc->removeItemsFromMultipleSelection($lc->getDuplicatesIds());
							}
							$this->updateMultipleSelectionDialogForContext($p, $exec, $lc);
							break;
						}
						//create multiple list context
						$mlc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "multipleElementList");
						//map on the elementList context
						$mlc->matchFetchCriteria($lc);
						//change groupLog exp to match parameter						
						if($groupId != 0) $mlc->addLogExpOnGroupFilterSearch($groupId, true);
						else $mlc->setGroupPList($configS->getGroupPList($p, $exec->getCrtModule()), true);
// 						$rootGroups = $configS->getRootGroupsInModule($p, $exec->getCrtModule());
// 						$groupLogExp = null;
// 						//force to select all the children as well
// 						$mlc->setGroupPList($configS->getGroupPList($p, $exec->getCrtModule()), true);
// 						if($groupId!=0){
// 							$stringLogExp = "INGR(id IN (".$groupId."))";
// 							$groupLogExp = TechnicalServiceProvider::getFieldSelectorLogExpParser()->createLogExpFromString($stringLogExp);
// 							$mlc->addGroupLogExp($groupLogExp);
// 						}
						
						$fsl = FieldSelectorListArrayWebImpl :: createInstance();
						$fsl->addElementAttributeSelector("id");//CWE 2015.10.07: it is now possible to select only Element details without any fields.

//						echo alert($groupAS->getGroupWithoutDetail($p, $groupId));
						//$fields = $this->getConfigurationContext()->mf($p, $exec->getCrtModule())->xpath("*[@type]");
						//$fsl->addFieldSelector(reset($fields)->getName());
						$mlc->setFieldSelectorList($fsl);
						$mlc->setFieldSortingKeyList(null);
//						echo alert($mlc);
						$elementIdsList = $this->getAllElementIdsInListView($p, $exec, $mlc, true);
						if($add){
							$lc->addItemsToMultipleSelection($elementIdsList->getListIterator(), $elementIdsList->getElementEnableStates(), $elementIdsList->getElementStates());
						} else {
							$lc->removeItemsFromMultipleSelection($elementIdsList->getListIterator());
						}
						$this->updateMultipleSelectionDialogForContext($p, $exec, $lc);
						break;
					case "setMultipleSelection" :
						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						if (formatBoolean($exec->getCrtParameters(2))) {
							$lc->addItemToMultipleSelection($exec->getCrtParameters(1), formatBoolean($exec->getCrtParameters(3)), $exec->getCrtParameters(4), $exec->getCrtParameters(5));
						} else {
							$lc->removeItemFromMultipleSelection($exec->getCrtParameters(1));
						}
						$this->updateMultipleSelectionDialogForContext($p, $exec, $lc);
						break;
//					case "removeListFromMultipleSelection" :
//						//create multiple list context
//						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
//						$mlc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "multipleElementList");
//						//map on the elementList context
//						$mlc->matchFetchCriteria($lc);
//						$fsl = FieldSelectorListArrayWebImpl :: createInstance();
//						$fields = $this->getConfigurationContext()->mf($p, $exec->getCrtModule())->xpath("*[@type]");
//						$fsl->addFieldSelector(reset($fields)->getName());
//						$mlc->setFieldSelectorList($fsl);
//						$elementIdsList = $this->getAllElementIdsInListView($p, $exec, $mlc);
//						$lc->removeItemsFromMultipleSelection($elementIdsList->getListIterator());
//						$this->updateMultipleSelectionDialogForContext($p, $exec, $lc);
//						break;
//					case "resetMultipleSelection" : //only
//						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
//						$lc->resetMultipleSelection();
//						$this->updateMultipleSelectionDialogForContext($p, $exec, $lc);
//						break;
					default :
						throw new ServiceException("unknow element action: " . $exec->getCrtParameters(0), ServiceException :: INVALID_ARGUMENT);
				}
				//			$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
				//			eput($lc->getCrtSelectedItem());
				//			eput($lc->getMultipleSelection());
				break;
				//		case "loadElementListInIconsView":
				//			$authS = ServiceProvider::getAuthenticationService();
				//			$configS = $this->getConfigurationContext();
				//			$p = $authS->getMainPrincipal();
				//			$p->setValueInRoleContext("lastView", WigiiExecutor::ICONS_view);
				//			$exec->addJsCode("loadElementListInIconsViewInGroup();");
				//			if($configS->getParameter($p, $exec->getCrtModule(), "Group_IncludeChildrenGroupsOnSelect") == "1"){
				//				$exec->addRequests("NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/groupSelectorPanel/selectGroupAndChildren/".implode("/", $exec->getCrtParameters())."");
				//			} else {
				//				$exec->addRequests("NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/groupSelectorPanel/selectGroup/".implode("/", $exec->getCrtParameters())."");
				//			}
				//			break;
				//		case "switchContextInListView":
				//			$authS = ServiceProvider::getAuthenticationService();
				//			$p = $authS->getMainPrincipal();
				//			$p->setValueInRoleContext("lastView", WigiiExecutor::LIST_view);
				//			//$exec->addRequests("NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/groupSelectorPanel/selectGroupAndChildren/".implode("/", $exec->getCrtParameters())."");
				//			$exec->addRequests("NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/groupSelectorPanel/selectGroupAndChildrenWithNoUpdate/".implode("/", $exec->getCrtParameters())."");
				//			$exec->addRequests("searchBar/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/searchBar");
				//			$exec->addRequests("workZone/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/workZone/");
				//			break;
				//		case "switchContextInIconsView":
				//			$authS = ServiceProvider::getAuthenticationService();
				//			$p = $authS->getMainPrincipal();
				//			$p->setValueInRoleContext("lastView", WigiiExecutor::ICONS_view);
				//			$exec->addRequests("searchBar/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/searchBar");
				//			$exec->addRequests("workZone/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/workZone/");
				//			break;
			case "subelement" :
				if (ServiceProvider :: getAuthenticationService()->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				$configS = $this->getConfigurationContext();
				$elS = ServiceProvider::getElementService();
				if(!isset($transS)) $transS = ServiceProvider::getTranslationService();

				$masterEltId = $exec->getCrtParameters(1);
				$linkName = $exec->getCrtParameters(2);
				$subEltId = $exec->getCrtParameters(3);
				switch($exec->getCrtParameters(0)) {
					case "add":
						$action = $exec->getCrtRequest();
						$configS->selectSubElementsConfig($p, $masterEltId, $linkName);
						// checks if parent is blocked
						foreach($configS->getCurrentSubElementPathFromRoot()->getListIterator() as $ls) {
							if($ls->isOwnerElementBlocked()) {
								throw new AuthorizationServiceException("adding subitems to blocked elements is not authorized", AuthorizationServiceException::FORBIDDEN);
							}
						}
						$element = Element :: createInstance($configS->getCurrentModule());
						$element->setElementParentId($masterEltId);
						$element->setLinkName($linkName);
						$element->setSys_creationDate(time());
						$element->setSys_date(time());
						$form = $this->createAddSubElementFormExecutor($element, "addElement_form", $action, $masterEltId, $linkName);
						$form->setCorrectionWidth(19);
						$totalWidth = 0 + $configS->getParameter($p, $configS->getCurrentModule(), "elementTotalWidth");
						$labelWidth = 0 + $configS->getParameter($p, $configS->getCurrentModule(), "elementLabelWidth");
						$form->setLabelWidth($labelWidth);
						$form->setTotalWidth($totalWidth);
						$form->setElementPolicyEvaluator($this->getElementPolicyEvaluator($p, $configS->getCurrentModule()));
						$state = "start";
						if ($_POST["action"] != null)
							$state = addslashes($_POST["action"]);
						$form->ResolveForm($p, $exec, $state);
						break;
					case "copy":
						$action = $exec->getCrtRequest();
						//create the element and fills it with data
						try {
							$element = $this->createElementForForm($p, $exec->getCrtModule(), $subEltId);
							$elementP = $elS->fillElement($p, $element);
							// checks if parent is blocked
							foreach($configS->getCurrentSubElementPathFromRoot()->getListIterator() as $ls) {
								if($ls->isOwnerElementBlocked()) {
									$elementP->setParentElementState_blocked(true);
									break;
								}
							}
						} catch (ServiceException $e) {
							if ($e->getCode() == ServiceException :: FORBIDDEN) {
								//if we try to retrieve an existing element but on which we have no rights, or in a module
								//we have no rights, then just make the elementP as null
								$elementP = ElementP :: createInstance($element);
							} else
								throw $e;
						}

						if ($elementP == null) {
							//the element is deleted
							$this->openAsMessage($exec->getIdAnswer(), $totalWidth - $labelWidth, $transS->t($p, "elementNotFound") . " (" . $transS->t($p, "id") . ": " . $elementId . ")", $transS->t($p, "elementNotFoundExplanation"), "actOnCloseDialog('".$exec->getIdAnswer()."');");
							break;
						} else if($elementP->getRights() == null ||
							$elementP->isParentElementState_blocked()){
							$this->openAsMessage($exec->getIdAnswer(), $totalWidth - $labelWidth, $transS->t($p, "elementUnreachable") . " (" . $transS->t($p, "id") . ": " . $elementId . ")", $transS->t($p, "elementUnreachableExplanation"), "actOnCloseDialog('".$exec->getIdAnswer()."');");
							break;
						}

						//add content on Files with htmlArea, for detail, or edit
						$htmlAreaFileFields = $this->doesCrtModuleHasHtmlAreaFiles($exec->getCrtModule());
						$fsl = FieldSelectorListArrayImpl :: createInstance();
						if ($htmlAreaFileFields) {
							foreach ($htmlAreaFileFields as $fieldXml) {
								$fsl->addFieldSelector($fieldXml->getName(), "content");
								//							eput($fieldXml->getName());
							}
						}
						if (!$fsl->isEmpty()) {
							$formFieldList = $element->getFieldList();
							$element->setFieldList(FieldListArrayImpl :: createInstance());
							$elS->fillElement($p, $element, $fsl);
							$element->setFieldList($formFieldList);
						}

						$policyEval = $this->getElementPolicyEvaluator($p, $configS->getCurrentModule());

						//copy
						$element->setId(0);
						$element->getWigiiBag()->loadFromFixedBag(); //recopy the fixed bag as normal bag without elementId
						if(isset($policyEval)) $policyEval->initializeElementStateOnCopy($p, $element);
						$form = $this->createCopySubElementFormExecutor($element, "copyElement_form", $action, $masterEltId, $linkName);
						$form->setCorrectionWidth(19);
						$totalWidth = 0 + $configS->getParameter($p, $configS->getCurrentModule(), "elementTotalWidth");
						$labelWidth = 0 + $configS->getParameter($p, $configS->getCurrentModule(), "elementLabelWidth");
						$form->setLabelWidth($labelWidth);
						$form->setTotalWidth($totalWidth);
						//					$form->setDottedLineSeparatorBetweenField(true);
						$form->setElementPolicyEvaluator($policyEval);

						$state = "start";
						if ($_POST["action"] != null)
							$state = addslashes($_POST["action"]);
						$form->ResolveForm($p, $exec, $state);
						break;
				}
				break;
			case "start" :
				$authS = ServiceProvider :: getAuthenticationService();
				$transS = ServiceProvider :: getTranslationService();
				$nAS = ServiceProvider::getWigiiNamespaceAdminService();
				$configS = $this->getConfigurationContext();
				$mAS = ServiceProvider :: getModuleAdminService();
				if ($authS->isMainPrincipalMinimal())
					throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
				//the role expiration is managed in UserAdminService::calculateAllMergedRoles($p)
				if ($p->passwordExpired()) {
					if ($p->canModifyRealUserPassword()) {
						throw new AuthenticationServiceException($p->getUserlabel() . " password is expired.", AuthenticationServiceException :: EXPIRED_PASSWORD);
					} else {
						throw new AuthenticationServiceException($p->getUserlabel() . " user is expired.", AuthenticationServiceException :: EXPIRED_PRINCIPAL);
					}
					break;
				}

				$this->throwEvent()->navigate(PWithUserIdWithWigiiNamespaceNameWithModuleName :: createInstance($p, $p->getRealUserId(), $exec->getCrtWigiiNamespace()->getWigiiNamespaceName(), $exec->getCrtModule()->getModuleName()));

				//if request is found in cache, does nothing,
				// except if coming from Setup namespace or Dimensions module, in that case, clears config
				if ($exec->wasFoundInJSCache()){
					if($nAS->getSetupWigiiNamespace($p)->getWigiiNamespaceUrl() == $exec->getCrtParameters(0) ||
						Module::DIMENSIONS_MODULE == $exec->getCrtParameters(1)) {
						$this->clearConfig(true);
					}
					break;
				}

				if (!$exec->getIsUpdating() || $exec->getIdAnswer() == "mainDiv") {
					$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/" : "") . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/all");
				} else {
					$exec->addRequests(($exec->getIsUpdating() ? "workZone/" : "") . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/workZone");
				}
				$exec->addJsCode("crtModuleName = '" . $mAS->getHomeModule($p)->getModuleUrl() . "'; setNavigationBarInHomeStateBsp(".$configS->getParameter($p, $mAS->getHomeModule($p), "FeedbackOnSystem_enable").");");
				break;
			case "navigate" :
				$authS = ServiceProvider :: getAuthenticationService();
				$transS = ServiceProvider :: getTranslationService();
				$userAS = ServiceProvider::getUserAdminService();
				$mAS = ServiceProvider :: getModuleAdminService();
				$sessAS = ServiceProvider::getSessionAdminService();
				$nAS = ServiceProvider::getWigiiNamespaceAdminService();
				$configS = $this->getConfigurationContext();
				
				// checks for login
				if ($authS->isMainPrincipalMinimal()){
					// tries to log as public
					if(!$authS->autoLoginAsPublic()) {
						throw new AuthenticationServiceException($exec->getCrtAction() . " need login", AuthenticationServiceException :: FORBIDDEN_MINIMAL_PRINCIPAL);
					}
					else {
						$this->storeAdminAndCalculatedRoleIdsInSession($authS->getMainPrincipal());
					}
				}
				//the role expiration is managed in UserAdminService::calculateAllMergedRoles($p)
				if ($p->passwordExpired()) {
					if ($p->canModifyRealUserPassword()) {
						throw new AuthenticationServiceException($p->getUserlabel() . " password is expired.", AuthenticationServiceException :: EXPIRED_PASSWORD);
					} else {
						throw new AuthenticationServiceException($p->getUserlabel() . " user is expired.", AuthenticationServiceException :: EXPIRED_PRINCIPAL);
					}
					break;
				}

				// Extracts navigation parameters				
				$type = $exec->getCrtParameters(0);
				$typeId = $exec->getCrtParameters(1);				
				$fromRole = $exec->getCrtParameters(2);
				if(!$fromRole) $fromRole = $p->getUserId();
				$fromWigiiNamespace = $exec->getCrtParameters(3);
				if(!$fromWigiiNamespace) $fromWigiiNamespace = $p->getWigiiNamespace()->getWigiiNamespaceUrl();
				$fromModule = $exec->getCrtParameters(4);
				if(!$fromModule) $fromModule = $p->getValueInRoleContext("lastModule");
				$toWorkingModule = $exec->getCrtParameters(5); //available only if crtModule is Admin
				$originalUserId = $p->getUserId();
				
				// Calculates best matching role
				switch($type){
					case "user":
						$roleId = $typeId;
						break;
					case "folder":
						// finds the best matching role for this group
						$roleId = $p->getRoleForGroup($typeId);
						break;
					case "item":
						// finds the best matching role for this element
						$roleId = $p->getRoleForElement($typeId);
						break; 
					default:
						// find the first calculated role of this wigiiNamespace
						$roleList=$p->getRoleListener();
						if(($roleList instanceof UserListForNavigationBarImpl) && !$roleList->isEmpty()) {
							$roleId = $roleList->getCalculatedRoleId($exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl());
						}
						else $roleId=$p->getUserId();						
				}
				// Stops if not matching role
				if(!$roleId) {
					if($p->isRealUserPublic()) {
						throw new AuthenticationServiceException('public access forbidden', AuthenticationServiceException::FORBIDDEN_PUBLIC_USER);
					}
					else {
						$exec->addJsCode("alert('".$transS->h($p, "noRoleFoundForWigiiNamespace").": ".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl().". ".$transS->h($p, "noRoleFoundForWigiiNamespaceExplanation")."');");
						$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/":'').WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: HOME_MODULE . "/start");
					}
					break;
				}

				// If navigating out of the Setup namespace or
				// navigating out of Dimensions module in a namespace different from Setup, 
				// then clears the configuration from the session and shared data
				if($nAS->getSetupWigiiNamespace($p)->getWigiiNamespaceUrl() == $fromWigiiNamespace &&
					$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() != $fromWigiiNamespace ||
					$fromModule == Module::DIMENSIONS_MODULE && $nAS->getSetupWigiiNamespace($p)->getWigiiNamespaceUrl() != $fromWigiiNamespace) {
					$this->clearConfig(true);
				}
				// If navigating out of the Admin module, then clears the configuration from the session
				if ($fromModule == Module :: ADMIN_MODULE && !$exec->getCrtModule()->isAdminModule()){
					// CWE 11.03.2016: clears configuration context to reload properly group configs and dico if changed.
					$this->clearConfigurationContext();
					$this->clearConfig();
					$this->clearDico();
					$userAS->calculateAllMergedRoles($p);
					$this->storeAdminAndCalculatedRoleIdsInSession($p);
					// CWE 05.12.2017: once out of Admin, clears lastModule to not clear again everything next time.
					$p->cleanKeysInRoleContext('lastModule');
				}
				
				// Notifies the navigate event.
				$this->throwEvent()->navigate(PWithUserIdWithWigiiNamespaceNameWithModuleName :: createInstance($p, $roleId, $fromWigiiNamespace . "->" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceName(), $fromModule . "->" . $exec->getCrtModule()->getModuleName()));

				// Switches role
				if ($roleId && $originalUserId != $roleId) {
					$p = $authS->changeToRole($p, $roleId);
				}

				//if the docked view per module is active
				if($this->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "workZoneViewDocked") == '1'){
					$this->setWorkzoneViewDocked(true);
					$exec->addJsCode("wigii().context.isWorkzoneViewDocked = true;");
				
				}else{
					$this->setWorkzoneViewDocked(false);
					$exec->addJsCode("wigii().context.isWorkzoneViewDocked = false;");
				}
				
				// Prepares navigation cache
				if($type == "user" && !$exec->wasFoundInJSCache()) {
					// caches user navigation (navigate cache key depends on destination role id.)
					$cacheKey = $exec->cacheCrtAnswer($p, 'userNavigate');
					$exec->addJsCode("setCurrentNavigateCacheKey('".$cacheKey."')");
					// updates group panel and module view through cache
					$groupPanelCacheKey = $exec->getCurrentCacheLookup($p, 'groupPanel', 'display/groupPanel');
					$exec->addJsCode("if(foundInCache){setTimeout(function() {if((getCache('groupPanel', '".$groupPanelCacheKey."') != null) || (getCache('moduleView', getModuleViewKeyCacheForNavigate()) != null)){".
							"update('workZone/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/workZoneStructure', false, null, function(tabReq, textStatus){".
								"parseUpdateResult(tabReq, textStatus);".
								"updateThroughCache('groupPanel', '".$groupPanelCacheKey."', 'groupPanel/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/groupPanel', false, false);".
					 			"updateThroughCache('moduleView', getModuleViewKeyCacheForNavigate(), 'moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/display/moduleView', false, false);".
							"});".
						"}else{update(url, true);}}, 100);}");
				}

				// In admin context, reset folder filters when changing tabs, or reset all filters if switching admin role
				if($exec->getCrtModule()->isAdminModule()){
					//if change of administrator then reset all context;
					$ac = $this->getAdminContext($p);
					if($originalUserId != $roleId){
						$ac->resetGroupFilter();
						$ac->resetUserFilter();
						$ac->resetUser2Filter();
					} else {
						//this happens when we switch from a tab to an other tab in the admin console
						//reset the group filter as the groups can be different per tab
						$ac->resetGroupFilter();
					}
				}

                //if getting out from Admin, then force going to Home module
                if ($fromModule == Module :: ADMIN_MODULE && !$exec->getCrtModule()->isAdminModule()){
                    $lastModule=$mAS->getHomeModule($p);
                } else {


                    // Checks module access	and calculates module to which navigate and workingModule in case of Admin
                    $lastModule = $exec->getCrtModule()->getModuleName();
                    $workingModule = null;
                    // if lastModule is not null and p has no access to module then error
                    if ($lastModule && $lastModule != Module::HOME_MODULE && !$p->getModuleAccess($lastModule)) {
                        if ($p->isRealUserPublic()) {
                            throw new AuthenticationServiceException('public access forbidden', AuthenticationServiceException::FORBIDDEN_PUBLIC_USER);
                        } else {
                            $exec->addJsCode("alert('" . $transS->h($p, "noModuleAccessFoundForRole") . ": " . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "->" . $transS->t($p, $exec->getCrtModule()->getModuleName()) . ". " . $transS->h($p, "noModuleAccessFoundForRoleExplanation") . "');");
                            $exec->addRequests(($exec->getIsUpdating() ? "mainDiv/" : '') . WigiiNamespace :: EMPTY_NAMESPACE_URL . "/" . Module :: HOME_MODULE . "/start");
                        }
                        break;
                    }
                    // if lastModule is null then gives first accessible module or Home if only Admin rights.
                    if (!$lastModule) {
                        if ($p->getWigiiNamespace()->getWigiiNamespaceName() != WigiiNamespace::EMPTY_NAMESPACE_NAME) $lastModule = $p->getFirstNoneAdminAccessibleModule();
                        if (!$lastModule) $lastModule = Module::HOME_MODULE;
                    }
                    // if lastModule is not null and p has access to module
                    if ($lastModule != Module::HOME_MODULE) {
                        // gets Module object
                        $lastModule = $p->getModuleAccess($lastModule);

                        //When we navigate, the search field should be filled with the last text enter.
                        if (!isset($lc)) $lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
                        $exec->addJsCode("$('nav .firstBox input:first').val('" . $lc->getTextSearch() . "')");
                        if ($lc->getSearchBar()) {
                            $exec->addJsCode("$('#searchField input, #filtersButton, #removeFiltersButton').addClass('R')");
                        } else {
                            $exec->addJsCode("$('#searField input, #filtersButton, #removeFiltersButton').removeClass('R');" .
                                "$('#searchField input').attr('placeholder', '" . $transS->t($p,"defineFilters") . $transS->t($p, $exec->getCrtModule()->getModuleName())."...');");
                        }

                        // if navigating in or to Admin: calculates workingModule
                        if ($lastModule->isAdminModule()) {

                            // if going to admin in same namespace, keeps current module
                            if ($fromWigiiNamespace == $p->getWigiiNamespace()->getWigiiNamespaceUrl() && $toWorkingModule == null) $toWorkingModule = $fromModule;
                            // if going to admin in other namespace retrieves last working module
                            else if ($toWorkingModule == null) $toWorkingModule = $p->getValueInRoleContext("lastWorkingModule");

                            // checks admin working module access
                            if ($toWorkingModule) {
                                $workingModule = $p->getModuleAccess($toWorkingModule);
                            }
                            if (!$workingModule || $workingModule->isAdminModule()) {
                                $workingModule = $p->getFirstNoneAdminAccessibleModule();
                            }

                            // Persists admin working module in context
                            $this->getAdminContext($p)->setWorkingModule($workingModule);
                            $p->setValueInRoleContext("lastWorkingModule", $workingModule->getModuleName());
                        } // if p is bound to a namespace, then forces a non Admin module
                        else if ($p->isPlayingRole() && $p->getAttachedUser()->isCalculatedRole() && $lastModule->isAdminModule()) {
                            $lastModule = $p->getFirstNoneAdminAccessibleModule();
                        }
                        // Persists lastModule in context
                        $p->setValueInRoleContext("lastModule", $lastModule->getModuleName());
                        // Persists principal context in DB
                        $this->persistMainPrincipalSessionContext($p, $exec);
                    } else $lastModule = $mAS->getHomeModule($p);
                }
				// Breaks html generation if found in cache
				if($type == "user" && $exec->wasFoundInJSCache()) {
					break;
				}

				// Navigation code
				$additionalJsCode = null;
				// Direct url access navigation 
				if (!$exec->getIsUpdating() || $exec->getIdAnswer() == "mainDiv") {
					$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/" : "") . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $lastModule->getModuleUrl() . "/display/".($type=="item" || $type=="folder" ? $type."/".$typeId : "all"));
				}
				// Full screen update 
				else if ($type=="item" || $type=="folder" || $type==null){
					//this code is needed to simulate the click made on the element it self.
					//href$= is to limit ie7 bugs making sometimes local links with the full path
					$additionalJsCode = "" .
							"$('#navigateMenu .selected').removeClass('selected');" .
							'$("#navigateMenu a[href$=\'#"+crtWigiiNamespaceUrl.replace(" ", "%20")+"/"+crtModuleName+"\']").addClass("selected");' .
							"";
					$exec->addRequests(($exec->getIsUpdating() ? "mainDiv/" : "") . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $lastModule->getModuleUrl() . "/display/".($type ? $type."/".$typeId : "all"));					
				} 
				// Navigation bar user navigation
				else {
					// Enters Admin console
					if ($lastModule->isAdminModule()) {
						// since 4.602 28.11.2017 the navigation bar doesn't need to be refreshed. it is loaded once at the beginning.
						// $exec->addRequests(($exec->getIsUpdating() ? "navigationBar/" : "") . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $lastModule->getModuleUrl() . "/display/navigationBar/");
						
						// loads admin console
					    $exec->addRequests(($exec->getIsUpdating() ? "workZone/" : "") . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $lastModule->getModuleUrl() . "/display/adminWorkZone/");
					} 
					// Navigates through User modules and namespaces or goes out of Admin 
					else {
					    // since 4.602 28.11.2017 the navigation bar doesn't need to be refreshed. it is loaded once at the beginning.
					    // if($fromModule == null || $fromModule == Module :: ADMIN_MODULE) $exec->addRequests(($exec->getIsUpdating() ? "navigationBar/" : "") . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $lastModule->getModuleUrl() . "/display/navigationBar/");
						
						//load the workZone. This include:
						//	- the group selector pannel
						//	- the moduleView						
						$exec->addRequests(($exec->getIsUpdating() ? "workZone/" : "") . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $lastModule->getModuleUrl() . "/display/workZone/");
					}
				}

				// JS to adjust GUI on client side
                $currentModuleLabel = $transS->t($p, "homePage_".$p->getWigiiNamespace()->getWigiiNamespaceUrl()."_".$lastModule->getModuleUrl());
                if($currentModuleLabel == "homePage_".$p->getWigiiNamespace()->getWigiiNamespaceUrl()."_".$lastModule->getModuleUrl()) $currentModuleLabel = $transS->t($p, $lastModule->getModuleUrl());

				$defaultWigiiNamespaceUrl = (string) $configS->getParameter($p, null, "defaultWigiiNamespace");				
				if(!$defaultWigiiNamespaceUrl){
				    $defaultWigiiNamespaceUrl = $p->getRealWigiiNamespace()->getWigiiNamespaceUrl();
				}
                $exec->addJsCode("
closeStandardsDialogs();
crtModuleLabel = '" . $currentModuleLabel . "';
defaultWigiiNamespaceUrl = '" . $defaultWigiiNamespaceUrl . "';
crtRoleId = '" . $roleId . "';
crtWigiiNamespaceUrl = '" . $p->getWigiiNamespace()->getWigiiNamespaceUrl() . "';
crtModuleName = '" . $lastModule->getModuleUrl() . "';
crtWorkingModuleName = '" . ($workingModule ? $workingModule->getModuleUrl() : '') . "';
$additionalJsCode
".($lastModule->isAdminModule() ? "setNavigationBarInAdminStateBsp();"
: ($lastModule->isHomeModule() ? "setNavigationBarInHomeStateBsp(".$configS->getParameter($p, $lastModule, "FeedbackOnSystem_enable").");" 
: "setNavigationBarNotInHomeStateBsp(".$configS->getParameter($p, $exec->getCrtModule(), "FeedbackOnSystem_enable").");"))
."document.title='".$exec->getCrtWigiiNamespace()->getWigiiNamespaceName()."/".$transS->h($p, $exec->getCrtModule()->getModuleName())." - ".$configS->getParameter($p, null, "siteTitle")."';");
				break;
			case "switchView" :
				$authS = ServiceProvider :: getAuthenticationService();
				$p = $authS->getMainPrincipal();
				$moduleView = $exec->getCrtParameters(0);

				if ($moduleView != null) {
					$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
					$lc->setCrtView($moduleView);
				}

				//$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/".$group->getGroupParentId(), $exec->getCrtWigiiNamespace(), $exec->getCrtModule());
				$exec->addJsCode("invalidCache('moduleView');");
				$exec->addRequests("workZone/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/workZone/");
				break;
			case "display" :
				$authS = ServiceProvider :: getAuthenticationService();
				$transS = ServiceProvider :: getTranslationService();
				$p = $authS->getMainPrincipal();

				//the password expiration or logued requirement process is all done in the navigate entry
				//including the RemainingUpdates management

				switch ($exec->getCrtParameters(0)) {
					case "adminWorkZone" :
						$this->includeTemplateAdminWorkZone($p, $exec);
						break;
					case "adminGroup" :
						$this->includeTemplateAdminGroup($p, $exec);
						break;
					case "adminGroupUser" :
						$this->includeTemplateAdminGroupUser($p, $exec);
						break;
					case "adminUser" :
						$this->includeTemplateAdminUser($p, $exec);
						break;
					case "adminRole" :
						$this->includeTemplateAdminRole($p, $exec);
						break;
					case "adminUserAdmin" :
						$this->includeTemplateAdminUserAdmin($p, $exec);
						break;
					case "adminUserRole" :
						$this->includeTemplateAdminUserRole($p, $exec);
						break;
					case "adminUserUser" :
						$this->includeTemplateAdminUserUser($p, $exec);
						break;
					case "adminModuleEditor" :
						$this->includeTemplateAdminModuleEditor($p, $exec);
						break;
					case "adminSearchBar" :
						$this->includeTemplateAdminSearchBar($p, $exec);
						break;
					case "detachModule" :
						$this->detachModule($p, $exec);
						break;
					case "searchBar" :
						$this->includeTemplateSearchBar($p, $exec);
						break;
					case Module::HOME_MODULE :
						$this->includeTemplateHome($p, $exec);
						break;
					case "workZone" :
					case "workZoneStructure":
						$this->includeTemplateWorkZone($p, $exec);
						break;
					case "groupPanel" :
						$this->includeTemplateGroupPanel($p, $exec);
						break;
					case "groupSelectorPanel" : //deprecated
						$this->includeTemplateGroupSelectorPanel($p, $exec);
						break;
					case "moduleView" :
						$this->includeTemplateModuleView($p, $exec);
						break;
					case "indicators" :
						$this->includeTemplateIndicators($p, $exec);
						break;
					case "navigationBar" :
					    // since 4.602 28.11.2017 the navigation bar doesn't need to be repainted. It is constructed once at the beginning.
						//$this->includeTemplateNavigation($p, $exec);
						break;
					case "adminNavigationBar" :
						$this->includeTemplateAdminNavigation($p, $exec);
						break;
					case "folder" :
					case "item" :
					case "all" :
						$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
						if($exec->getCrtParameters(0)=="folder"){
							//select group in context
							//reset filters
							$lc->resetFetchCriteria($p, $this);
							$crtSelectedGroupId = $exec->getCrtParameters(1);
							$lc->setGroupPList($this->getConfigurationContext()->getGroupPList($p, $exec->getCrtModule(), $crtSelectedGroupId), false);
							//check group availability
							if(!$p->getModuleAccess($exec->getCrtModule()) || !$lc->getGroupPList() || ($crtSelectedGroupId!=0 && ($lc->getGroupPList()->count()>1 || reset($lc->getGroupPList()->getListIterator())->getId()!=$crtSelectedGroupId))){
								$exec->addJsCode("alert('".$transS->h($p, $exec->getCrtParameters(0)."NotFound").": xxx".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."->".$transS->h($p, $exec->getCrtModule()->getModuleName())."->".$exec->getCrtParameters(0)."->".$exec->getCrtParameters(1).". ".$transS->h($p, $exec->getCrtParameters(0)."NotFoundExplanation")."');");
							}
						} else if($exec->getCrtParameters(0) == "item"){
							$elS = ServiceProvider::getElementService();
							$configS = $this->getConfigurationContext();
							//find first group of the item:
							//reset filters
							$lc->resetFetchCriteria($p, $this);
							$elementP = $elS->fillElement($p, $this->createElementForForm($p, $exec->getCrtModule(), $exec->getCrtParameters(1)), $elS->fillFieldSelectorListForElementSignatureAttribute(FieldSelectorListArrayImpl::createInstance()));
							if($elementP && $elementP->getRights()){
								if($elementP->getElement()->isSubElement()){
									$rootElementP = ElementP::createInstance(Element::createInstance(ServiceProvider::getModuleAdminService()->getModule($p, $configS->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()->getModuleName())));
									$rootElementP->getElement()->setId($configS->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()->getOwnerElementId());
									$rootElementP->setRights($elementP->getRights());
									$groupPath = $elS->getGroupsPathContainingElement($p, $rootElementP);
								} else {
									$groupPath = $elS->getGroupsPathContainingElement($p, $elementP);
								}
								if($groupPath){
									reset($groupPath);
									$crtSelectedGroupId = key($groupPath);
									$lc->setGroupPList($this->getConfigurationContext()->getGroupPList($p, $exec->getCrtModule(), $crtSelectedGroupId), false);
									$lc->setCrtSelectedItem($exec->getCrtParameters(1));
									//add request to display details of this detail
									//$exec->addJsCode("update('elementDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/element/detail/".$exec->getCrtParameters(1)."');");
									$exec->addRequests("elementDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleName()."/element/detail/".$exec->getCrtParameters(1));
								}
							} else if(!$elementP){
								$exec->addJsCode("alert('".$transS->h($p, $exec->getCrtParameters(0)."NotFound").": ".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."->".$transS->h($p, $exec->getCrtModule()->getModuleName())."->".$exec->getCrtParameters(1).". ".$transS->h($p, $exec->getCrtParameters(0)."NotFoundExplanation")."');");
							} else {
								$exec->addJsCode("alert('".$transS->h($p, $exec->getCrtParameters(0)."NotAvailable").": ".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."->".$transS->h($p, $exec->getCrtModule()->getModuleName())."->".$exec->getCrtParameters(1).". ".$transS->h($p, $exec->getCrtParameters(0)."NotAvailableExplanation")."');");
							}
//							//fill in the search with the criteria to find the item
//							$lc->setGroupPList($this->getConfigurationContext()->getGroupPList($p, $exec->getCrtModule(), 0, true));
//							//lookup for the item with the sarch bar
//							$lc->setSearchBar($p, $this, array(ListContext::TextSearchField."_value"=>"#".$exec->getCrtParameters(1), "action"=>"check"));
						}

						//close all standard dialog
						$this->closeStandardsDialogs();
						if ($p->getModuleAccess($exec->getCrtModule())) {
							$p->setValueInRoleContext("lastModule", $exec->getCrtModule()->getModuleName());
						}
						//persist context in DB;
						$this->persistMainPrincipalSessionContext($p, $exec);

						try {
							$this->includeTemplateAll($p, $exec);
						} catch (ServiceException $e) {
							if ($e->getCode() == ServiceException :: FORBIDDEN) {
								//try to display a module not accessible -> do nothing
							}
						}
						break;
				}
				break;
			case "find":
				$authS = ServiceProvider :: getAuthenticationService();
				$transS = ServiceProvider :: getTranslationService();
				$p = $authS->getMainPrincipal();
				
				$query = $exec->getCrtParameters(0);
				$businessKey = $exec->getCrtParameters(1);
				$filterOnElements = ($exec->getCrtParameters(2)=='filter');
				
				$strQuery = base64url_decode($query);
				$strBusinessKey = base64url_decode($businessKey);								
																
				$query = str2fx($strQuery);
				$businessKey = str2fx($strBusinessKey);
				// sets Origin as Public
				if($query instanceof FuncExp) $query->setOriginIsPublic();
				if($businessKey instanceof FuncExp) $businessKey->setOriginIsPublic();
				
				// builds query object				
				$query = $this->evaluateFuncExp($p, $exec, $query);
				// separates source from dfasl
				if($query instanceof DataFlowSelector) {
					$source = $query->getSource();
					$dfasl = $query->getDataFlowActivitySelectorList();
				}
				elseif($query instanceof ElementPListDataFlowConnector) {
					$source = $query;
					$dfasl = null;
				}
				else throw new ServiceException('query '.$strQuery.' is not supported.', ServiceException::UNSUPPORTED_OPERATION);
				
				// builds business key selector
				$businessKey = $this->evaluateFuncExp($p, $exec, $businessKey);
				if(!($businessKey instanceof LogExp)) throw new ServiceException('businessKey selector '.$strBusinessKey.' is not a valid log exp.', ServiceException::INVALID_ARGUMENT);
				
				// injects businessKey selector in source
				if($source instanceof ElementPListDataFlowConnector) {
					$lf = $source->getListFilter();
					if(is_null($lf)) {
						$lf = ListFilter::createInstance();
						$source->setListFilter($lf);
					}
					
					// if fsl is not set, then only fetches element.id									
					if(is_null($lf->getFieldSelectorList())) {
						$lf->setFieldSelectorList(fsl(fs_e('id')));
					}					
					
					// creates log exp
					$logExp = $lf->getFieldSelectorLogExp();
					if(is_null($logExp)) {
						$logExp = $businessKey;
					}
					else {
						$logExp = lxAnd($logExp, $businessKey);
					}
					$lf->setFieldSelectorLogExp($logExp);
				}
				else throw new ServiceException('query '.$strQuery.' with a source of class '.get_class($source).' is not supported.', ServiceException::UNSUPPORTED_OPERATION);
				
				// sets a default ElementPListHtmlRendererDFA if not present
				$outputEnabled = false;							
				if(is_null($dfasl)) {
					$htmlRendererDfa = $this->getElementPListHtmlRendererDFAS(array(
						'setOutputEnabled' => $outputEnabled,
						'setRedirectIfOneElement' => !$outputEnabled,
						'setRedirectIfOneGroup' => !$outputEnabled,
						'setFilterOnElements' => !$outputEnabled && $filterOnElements,
						'setListIsNavigable' => true,
						'setWigiiExecutor' => $this
					));
														
					$dfasl = dfasl($htmlRendererDfa);
					$outputEnabled = $htmlRendererDfa->getDataFlowActivityParameter('setOutputEnabled');
					
					// echoes default header
					if($outputEnabled) {
						if ($exec->getIsUpdating()) {
							echo ExecutionServiceImpl :: answerRequestSeparator;
							echo 'mainDiv';
							echo ExecutionServiceImpl :: answerParamSeparator;
						}
						echo '<div style="margin-left:30px;">';
						echo '<h1>Search</h1>';
						echo '<p>';
						echo $strQuery;
						echo '</p>';
						echo '<p>';
						echo $strBusinessKey;
						echo '</p>';
						echo '<p><br/><a href="#';
						echo $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl();
						echo '" onclick="window.location = $(this).attr('."'href'".');window.location.reload();">Go back</a><br/></p><br/>';
						echo '</div>';
					}					
				}
				
				// runs the find data flow
				$p->setAdaptiveWigiiNamespace(true);
				if($outputEnabled) echo '<div style="margin-left:30px;"><h1>Found results</h1>';
				$result = ServiceProvider::getDataFlowService()->processDataSource($p, $source, $dfasl, false);
				
				// show 'no result' page if no elements found
				if(!$outputEnabled && $source instanceof ElementPListDataFlowConnector && $source->isEmpty()) {
					$this->debugLogger()->write('noElements found');					
					$group = $source->getCalculatedGroupList();					
					if(isset($group)) $group = reset($group->getListIterator());
					if(isset($group)) $group = $group->getDbEntity();
					if(isset($group)) {
						$okJsCode = 'update("mainDiv/'.$group->getWigiiNamespace()->getWigiiNamespaceUrl()."/".$group->getModule()->getModuleUrl()."/navigate/folder/".$group->getId().'");';
					}
					else {
						$okJsCode = 'update("NoAnswer/NoWigiiNamespace/Home/start");';
					}
					if ($exec->getIsUpdating()) {
						echo ExecutionServiceImpl :: answerRequestSeparator;
						echo "confirmationDialog";
						echo ExecutionServiceImpl :: answerParamSeparator;
					}
					$this->openAsMessage("confirmationDialog", 300, $transS->t($p, "noResultFound"), $transS->t($p, "noElementMatchSearchCriteria"), $okJsCode);
					$exec->addJsCode('$("#workZone").empty();');
				}				
				// frees memory of source
				if(isset($source) && method_exists($source, 'freeMemory')) $source->freeMemory();
				
				if($outputEnabled) echo '</div>';
				$p->bindToWigiiNamespace($exec->getCrtWigiiNamespace());
				
				// CWE 25.11.2016: cannot trust result as JS code because could have been generated by the query as Cross Site Scripting 
				// -- CANNOT BE TRUSTED -- if result is not empty, assumes it is some js code to send back to client
				// if(!empty($result)) $exec->addJsCode($result);				
				
				break;				
				
			default :
				// looks for a WebExecutor plugin to handle the action.
				$webExecClass = $this->findWebExecutorForAction($exec->getCrtAction());
				// if found, then runs it
				if(isset($webExecClass)) $this->runWebExecutor($webExecClass, $exec);
				// else unknown request
				else {
					/*
					$str = "Unknown request '".$_GET['url']."'";
					$str .= "\nCurrent context".$exec->getCrtContext();
					$str .= "\nCurrent action=".$exec->getCrtAction();
					$str .= "\nCurrent request=".$exec->getCrtRequest();
					$str .= "\nCurrent parameters=".json_encode($exec->getCrtParameters());
					$str .= "\nCurrent module=".(is_object($exec->getCrtModule()) ? $exec->getCrtModule()->getModuleName() : json_encode($exec->getCrtModule()));
					$str .= "\nCurrent namespace=".(is_object($exec->getCrtWigiiNamespace()) ? $exec->getCrtWigiiNamespace()->getWigiiNamespaceName() : json_encode($exec->getCrtWigiiNamespace()));
					$str .= "\nCookie=".$_COOKIE['wigii_anchor'];
					$str .= "\nisUpdating=".$exec->getIsUpdating();
					$str .= "\nExec=".$exec->displayDebug();
					fput($str);
					*/
					// 24.11.2016 protect against Cross Site Scripting by droping quotes in URL
					$exec->addJsCode("alert('Unknown request:\\n" . str_replace(array('"',"'",'%22','%27'),'',$exec->getCrtAction()). "');");
				}
		}
	}
	
	/**
	 * Binds all required JS services to the module view beeing displayed.
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 */
	public function bindJsServicesOnModuleView($p,$exec) {
	    $this->debugLogger()->logBeginOperation('bindJsServicesOnModuleView');
		// binds standard Wigii JS services
		$config = $this->getConfigurationContext();
		$module = $exec->getCrtModule();
		$userModule = isset($module) && $module->isUserModule();
		$lc = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
		// adds JS code after show exp
		if($userModule) {
			$activityName = '';
			switch($lc->getCrtViewActivityName()){
				case 'listView':
					$activityName = 'jsCodeForListExp';
					break;
				case 'calendarView':
					$activityName = 'jsCodeForCalendarExp';
					break;
				case 'blogView':
					$activityName = 'jsCodeForBlogExp';
					break;
			}
			$jsCodeAfterShowExp = (string)$config->getParameter($p, $module, $activityName);
			
			if(!empty($jsCodeAfterShowExp)) {
				// parses FuncExp
				$jsCodeAfterShowExp = str2fx($jsCodeAfterShowExp);
				// executes the func exp
				if($jsCodeAfterShowExp instanceof FuncExp) {
					$fxEval = $this->getFuncExpEvaluator($p, $exec, null);
					try {
						$jsCodeAfterShowExp = $fxEval->evaluateFuncExp($jsCodeAfterShowExp,$this);
					}
					catch(Exception $e) {
						if(isset($fxEval)) $fxEval->freeMemory();
						throw $e;
					}
					if(isset($fxEval)) $fxEval->freeMemory();
					$this->debugLogger()->write($jsCodeAfterShowExp);
					if($jsCodeAfterShowExp != '') {
						$this->debugLogger()->write('jsCodeAfterShowExp = '.$jsCodeAfterShowExp);
						$exec->addJsCode($jsCodeAfterShowExp);
					}
				}
			}
		}
	
		// HelpService on Fields (not in print, not in external access, not in notification)
		$exec->addJsCode("$('#moduleView .wigiiHelp').wigii('bindHelpService');");
		$this->debugLogger()->logEndOperation('bindJsServicesOnModuleView');
	}
}