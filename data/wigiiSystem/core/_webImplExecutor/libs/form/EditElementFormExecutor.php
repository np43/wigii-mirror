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
 * Created on 15 sept. 09 by LWR
 * Modified by Medair in 2016 for maintenance purposes (see SVN log for details)
 */
class EditElementFormExecutor extends FormExecutor {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("EditElementFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("EditElementFormExecutor");
		}
		return $this->_executionSink;
	}

	private $listContext;
	public function setListContext($lc){ $this->listContext = $lc; }
	protected function getListContext(){ return $this->listContext; }

	private $additionalText = null;
	public function addAdditionalText($var){
		if(!isset($this->additionalText)){
			$this->additionalText = "";
		} else {
			$this->additionalText .= "<br />";
		}
		$this->additionalText .= $var;
	}
	public function getAdditionalText(){
		return $this->additionalText;
	}
	public function resetAdditionalText(){
		$temp = $this->additionalText;
		$this->additionalText = null;
		return $temp;
	}

	private $rootPincipal; //this is needed for the auto sharing to some groups defined in configuration
	public function setRootPrincipal($rootP){ $this->rootPrincipal = $rootP; }
	protected function getRootPrincipal(){ return $this->rootPrincipal; }

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

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

	protected function setState($state){
		$this->executionSink()->log("set state from: ".$this->state." to ".$state);
		switch($state){
			case "start":
				break;
			case "newCheck": //this will provide a again check as after a start
				$this->addStateError(); //this state cannot allow to end the loop
				break;
			case "check":
				break;
			case "addMessageToNotification":
				$this->addStateError(); //this state cannot allow to end the loop
				break;
			case "persistAndSkipNotify":
				break;
			case "persistAndNotify":
				break;
			default: throw FormExecutorException("invalid state: ".$state, FormExecutorException::INVALID_STATE);
		}
		$this->state = $state;
	}
	protected function goToNextState(){

		switch($this->getState()){
			case "start": $this->setState("check"); break;
			case "newCheck": $this->setState("check"); break;
			case "check": $this->setState("check"); break;
			case "addMessageToNotification": $this->setState("persistAndNotify"); break;
			case "persistAndNotify":
			case "persistAndSkipNotify":
				throw new FormExecutorException("no further state after: ".$this->getState(), FormExecutorException::INVALID_STATE);
				break;
			default: throw FormExecutorException("invalid state: ".$this->getState(), FormExecutorException::INVALID_STATE);
		}
		return $this->getState();
	}
	protected function doSpecificCheck($p, $exec){
		$elS = ServiceProvider::getElementService();
		$transS = ServiceProvider::getTranslationService();

		$this->executionSink()->log("Do specific check");

		if($this->getState() == "persistAndNotify"){
			//validate the email
		    $fromEmail= $this->getWigiiExecutor()->getNotificationService()->getNotificationEmailInputValue();
			//if the email is empty (because of no notification needed, validation works)
			if(	!validateEmail($fromEmail) || !validateEmails($this->getWigiiExecutor()->getNotificationService()->getNotificationToInputValue())){
				$this->setState("addMessageToNotification");
			}
			// Medair (CWE) 04.04.2017 checks for authorized direct sender
			elseif(defined("EmailService_sendOnBehalfOfUser") && EmailService_sendOnBehalfOfUser) {
			    try { 
			        $this->getWigiiExecutor()->getEmailService()->isEmailAuthorizedDirectSender($p,$fromEmail,$p->getRealUsername());
			    }
			    catch(AuthorizationServiceException $ase) {
			        if($ase->getCode() == AuthorizationServiceException::NOT_ALLOWED) $this->setState("addMessageToNotification");
			        else throw $ase;
			    }
			}
		}
		if(!$this->hasError() && $this->getState()=="check"){
			try{
				if($this->isNotificationNeededForElement($p, $exec)){
					$this->setState("addMessageToNotification");
					$this->executionSink()->log("Notification is needed");
				}
			} catch (NotificationServiceException $e){
				if ($e->getCode() == NotificationServiceException::NO_NOTIFICATION_NEEDED){
					$this->executionSink()->log("No notification needed: ".$e->getMessage());
					//don't change the state
					return;
				}
				throw $e;
			}
		}
	}

	protected function isNotificationNeededForElement($p, $exec){
		//if not throw a NotificationServiceException::NO_NOTIFICATION_NEEDED catched in doSpecificCheck
		$this->getWigiiExecutor()->getNotificationService()->isNotificationNeededForElement($p, $this->getRecord(), "update");
		return $this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $this->getRecord()->getModule(), "Notification_enableMessage")!="0";
	}

	protected function endActOnCheckedRecord($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();

		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		// if subelement then reloads parent
		if($this->getRecord()->isSubElement()) {
			$exec->addRequests($exec->getIdAnswer()."/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/detail/".$this->getRecord()->getElementParentId());
		}
		// else
		else {
			if($exec->getIdAnswer()=="mainDiv" || $this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "reloadDetailAfterModify")=="1"){
				//reload detail
				$exec->addRequests($exec->getIdAnswer()."/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/detail/".$this->getRecord()->getId());
				// Resets ConfigService in memory cache to recalculate correctly the dynamic attributes.
				ServiceProvider::getConfigService()->freeMemory();
			} else {
				$exec->addJsCode("$('#groupPanel li>div.highlight').removeClass('highlight');");
				$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
			}
		}

		//disable the cache of the element --> to realod change on next request
		$exec->invalidCache($p, 'elementDialog', "selectElementDetail",  "element/detail/".$this->getRecord()->getId());
		if(!$this->getRecord()->isSubElement()){
		    $this->getListContext()->setCrtSelectedItem($this->getRecord()->getId());
		}
		//No more reorder after change, this is peinful for the user! $this->getListContext()->setSortedBy("sys_date", false);

		$this->realoadAfterCheckedRecord($p, $exec);
		// continues interrupted navigation if any.
		$exec->addJsCode("if(checkOpenItemTemp_url!=null){update(checkOpenItemTemp_url, true); checkOpenItemTemp_url=null;}");
	}
	protected function realoadAfterCheckedRecord($p, $exec){
		if($exec->getIdAnswer()!="mainDiv"){
			if($this->getRecord()->isSubElement()){
				$rootElementId = $this->getWigiiExecutor()->getConfigurationContext()->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()->getOwnerElementId();
				$exec->addJsCode("updateElementInList('" . $rootElementId . "');");
			} else {
				$exec->addJsCode("updateElementInList('" . $this->getRecord()->getId() . "');");
			}
		}
		//invalid the cache of the list
		$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
		if($elementListContext->getGroupPList()==null || $elementListContext->getGroupPList()->isEmpty()){
			$elementListContext->setGroupPList($this->getWigiiExecutor()->getConfigurationContext()->getGroupPList($p, $exec->getCrtModule()));
		}
		if ($elementListContext->getGroupPList()->count() == 1) {
			$ids = $elementListContext->getGroupPList()->getIds();
			$ids = reset($ids);
			$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $ids);
		}
		//$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");
	}
	//add javascript to enable autoSave if parameter autoSave="1"
	protected function addAutoSaveJSCode($p, $exec){
		//autoSave work only on edition
		if(!$this->getRecord()->getId()) return;
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$transS = ServiceProvider::getTranslationService();
		if($configS->getParameter($p, $exec->getCrtModule(), "autoSave")=="1" && !$this->isForExternalAccess()){
			$submitUrlForAutoSave = "Update/".$exec->getCrtContext()."/__/NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/autoSave/".$this->getRecord()->getId();
			$exec->addJsCode("setListenerForAutoSave('".$this->getFormId()."', '$submitUrlForAutoSave', '".$transS->h($p, "autoSaveTrigged")."');");
		} else if($this->isForExternalAccess() &&
					($configS->getParameter($p, $exec->getCrtModule(), "autoSaveInPublic")=="1" || ($configS->getParameter($p, $exec->getCrtModule(), "autoSave")=="1" && $configS->getParameter($p, $exec->getCrtModule(), "autoSaveInPublic")!=="0"))
					){
			$request = explode("/__/", $this->getSubmitUrl());
			//second request is after the Update/
			$request[1] = explode("/", $request[1]);
			//take idAnswer/WigiiNamespace/Module/externalAccess/code/
			$request[1] = implode("/", array_slice($request[1], 0, 5));
			//replace idAnswer with NoAnswer
			$request[1] = str_replace("mainDiv", "NoAnswer", $request[1]);
			//add at the end the autoSave actions
			$request[1] .= "/autoSave/".$this->getRecord()->getId();
			$exec->addJsCode("setListenerForAutoSave('".$this->getFormId()."', '".implode("/__/", $request)."', '".$transS->h($p, "autoSaveTrigged")."');");
		}

		if($exec->getCrtParameters(2) == 'autoadd') {
			$idAnswer = $exec->getIdAnswer();
			if(!$idAnswer){
				$idAnswer = "mainDiv";
			}
			if($this->isWorkzoneViewDocked()) {//prepare delete draft toolbar
				$idAnswer = 'searchBar';
				$findSelector = ".find('.middleBox .T').append";	
				$exec->addJsCode("
					var middleBox = initMiddleBox();
					$('#searchBar .middleBox .T').empty();
					middleBox.show();
				");		
			} elseif($idAnswer!="mainDiv"){
				$findSelector = ".parent().find('.ui-dialog-title').after";
			} else {
				$findSelector = ".find('.T div:last').after";
			}
			$exec->addJsCode("
				$('#".$idAnswer."')$findSelector('<a class=\"H el_deleteDraft\" href=\"javascript:update(\\'confirmationDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/delete/".$this->getRecord()->getId()."/$idAnswer\\', false, {action:\\'persistAndSkipNotify\\'});"."\"><span class=\"glyphicon glyphicon-trash\" aria-hidden=\"true\"></span> ".$transS->t($p, "deleteDraft")."</a>');
				resize_groupPanel();
				resize_elementList();
			");
		}
	}
	
	/**
	 * Binds Wigii HelpService on Element add or edit.
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 */
	protected function bindWigiiHelpService($p,$exec) {		
		// doesn't support mainDiv.
		if($exec->getIdAnswer() == 'mainDiv' || !$this->isDialog()) return;
		$helpExp = $this->getWigiiHelpExpression($p, $exec);
		if(empty($helpExp)) return;
		
		// resolves help expression		
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
			$fxEval = $this->getFuncExpEval($p, $exec);
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
		elseif(!is_array($helpExp)) throw new FormExecutorException("invalid configuration parameter WigiiHelp_onAdd or WigiiHelp_onEdit in module ".$exec->getCrtModule()->getModuleName(),FormExecutorException::CONFIGURATION_ERROR);		
		$helpUrl = null;
		$helpOptions = null;
		foreach($helpExp as $attr=>$val) {
			if($attr=='content') $helpUrl = $val;
			else {
				if(!isset($helpOptions)) $helpOptions = TechnicalServiceProvider::createWigiiBPLParameterInstance();
				$helpOptions->setValue(str_replace('data-popup-', '', $attr), $val);
			}
		}
		// adds a JS notification with help url
		$exec->addJsNotif('elementDialog','help',$helpUrl,$helpOptions);
	}
	/**
	 * Gets a WigiiHelp expression attached to module configuration file.
	 * Looks for configuration parameters WigiiHelp_onAdd or WigiiHelp_onEdit
	 * @param Principal $p current principal
	 * @param ExecutionService $exec current request
	 */
	protected function getWigiiHelpExpression($p,$exec) {
		$config = $this->getWigiiExecutor()->getConfigurationContext();		
		if($this->getRecord()->isSubElement()) $module = $this->getRecord()->getModule();
		else $module = $exec->getCrtModule();
		$helpExp = null;
		if($exec->getCrtParameters(2) == 'autoadd') {
			$helpExp = (string)$config->getParameter($p, $module, "WigiiHelp_onAdd");
		}
		else {
			$helpExp = (string)$config->getParameter($p, $module, "WigiiHelp_onEdit");
		}		
		return $helpExp;
	}
	
	protected function actOnCheckedRecord($p, $exec) {
	$elS = ServiceProvider::getElementService ();
	$configS = $this->getWigiiExecutor ()->getConfigurationContext (); // ServiceProvider::getConfigService();
	$transS = ServiceProvider::getTranslationService ();
	
	$this->executionSink ()->log ( "Act on checked record" );
	
	$storeFileInWigiiBag = $configS->getParameter ( $p, null, "storeFileContentIntoDatabase" ) == "1";
	// we need the old Record to manage correctly the Files
	$oldRecord = $this->fetchOldRecord ( $p, $exec, $this->getRecord ()->getId () );
	
	// $fieldSelectorList will contains File content and thumbnail subfield for each File field with a new File
	$newFileFieldSelectorList = $this->updateHiddenFields ( $p, $exec, $storeFileInWigiiBag, $oldRecord );
	$actualFieldSelectorList = $this->getFieldSelectorList ();
	
	if ($actualFieldSelectorList->isEmpty () && $newFileFieldSelectorList != null) {
		// then set the FieldSelectorList to all
		foreach ( $this->getRecord ()->getFieldList ()->getListIterator () as $field ) {
			if ($field->getDataType () != null) {
				$actualFieldSelectorList->addFieldSelector ( $field->getFieldName () );
			}
		}
	}
	if ($newFileFieldSelectorList != null) {
		$actualFieldSelectorList->mergeFieldSelectorList ( $newFileFieldSelectorList );
	}
	
	// remove any doNotPersist fields
	if ($actualFieldSelectorList->isEmpty ()) {
		foreach ( $this->getRecord ()->getFieldList ()->getListIterator () as $field ) {
			if ($field->getDataType () != null) {
				$fieldXml = $field->getXml ();
				if ($fieldXml ["doNotPersist"] == "1" || ! $this->getRecord ()->getWigiiBag ()->isChanged ( $field->getFieldName () )) {
					// ignore
				} else {
					$actualFieldSelectorList->addFieldSelector ( $field->getFieldName () );
					$this->executionSink ()->log ( "Field " . $field->getFieldName () . " has changed." );
				}
			}
		}
	} else {
		foreach ( $actualFieldSelectorList->getListIterator () as $fs ) {
			$fieldXml = $this->getRecord ()->getFieldList ()->getField ( $fs->getFieldName () )->getXml ();
			if ($fieldXml ["doNotPersist"] == "1" || ! $this->getRecord ()->getWigiiBag ()->isChanged ( $fs->getFieldName () )) {
				$actualFieldSelectorList->removesField ( $fs->getFieldName () );
			}
		}
	}
	
	// if nothing to update, then no update is necessary
	// do not updateElement with an emptyFieldSelector otherwise it will
	// change all fields in it
	if (! $actualFieldSelectorList->isEmpty ()) {
		$this->executionSink ()->log ( "Update Element" );
		
		// merges policy evaluator field selector list
		if(isset($this->fieldSelectorListFromPolicyEvaluator)) {
			$fslForUpdate = FieldSelectorListArrayImpl::createInstance(false);
			$fslForUpdate->mergeFieldSelectorList($actualFieldSelectorList);
			$fslForUpdate->mergeFieldSelectorList($this->fieldSelectorListFromPolicyEvaluator);
		}
		else $fslForUpdate = $actualFieldSelectorList;
		
		$elS->updateElement ( $p, $this->getRecord (), $fslForUpdate );
	}
	
	$this->updateFilesOnDisk ( $p, $exec, $storeFileInWigiiBag, $oldRecord, false );
	
	if ($this->getState () == "persistAndSkipNotify") {
		$this->getWigiiExecutor ()->getNotificationService ()->skipNextNotification ();
	}
	
	// if edit after having done auto add (auto save on add), then throw an insert event
	if ($exec->getCrtParameters ( 2 ) == 'autoadd') {
		$this->getWigiiExecutor ()->throwEvent ()->insertElement ( PWithElementWithGroupPList::createInstance ( $p, $this->getRecord (), ($configS->getGroupPList ( $p, $exec->getCrtModule () )->count () == 1 ? $configS->getGroupPList ( $p, $exec->getCrtModule () ) : null) ) );
	} 	// else throws an update event
	else
		$this->getWigiiExecutor ()->throwEvent ()->updateElement ( PWithElementWithGroupPList::createInstance ( $p, $this->getRecord (), ($configS->getGroupPList ( $p, $exec->getCrtModule () )->count () == 1 ? $configS->getGroupPList ( $p, $exec->getCrtModule () ) : null) ) );
		
		// autosharing is available only if not subElement
	if (! $this->getRecord ()->isSubElement ()) {
		// autosharing in new record to other group coming from attribut idGroup
		$gids = ValueListArrayMapper::createInstance ( true, ValueListArrayMapper::Natural_Separators, true );
		$this->getRecord ()->getLinkedIdGroupInRecord ( $p, $gids );
		$newGids = $gids->getListIterator ();
		if ($newGids == null)
			$newGids = array ();
		$gids->reset ( true, ValueListArrayMapper::Natural_Separators, true );
		$oldRecord->getLinkedIdGroupInRecord ( $p, $gids );
		$oldGids = $gids->getListIterator ();
		if ($oldGids == null)
			$oldGids = array ();
			
			// eput("new:");
			// eput($newGids);
			// eput("old:");
			// eput($oldGids);
		if ($newGids && $oldGids) {
			$orgNew = $newGids;
			$newGids = array_diff_key ( $newGids, $oldGids ); // only add the ones which was not set before
			$oldGids = array_diff_key ( $oldGids, $orgNew );
		}
		
		$moveId = $this->getRecord()->getMoveGroupInRecord();
		if($moveId) {
			$moveId = $this->getWigiiExecutor()->evaluateConfigParameter($p, $exec, $moveId, $this->getRecord());
		}

		$this->getWigiiExecutor ()->getNotificationService ()->blockNotificationPostingValue ();
		if ($newGids || $moveId) {
			if($moveId){
				$this->executionSink ()->log ( "Move element in " . $moveId );
				$elS->moveElement ( $this->getRootPrincipal (), $this->getRecord ()->getId (), $moveId );
				$newGids[$moveId] = $moveId;
			} else{
				$this->executionSink ()->log ( "Share element in " . $newGids );
				$elS->shareElement ( $this->getRootPrincipal (), $this->getRecord ()->getId (), $newGids );
			}
			$gpl = GroupListArrayImpl::createInstance ();
			ServiceProvider::getGroupAdminService ()->getGroupsWithoutDetail ( $p, $newGids, $gpl );
			foreach ( $gpl->getListIterator () as $group ) {
				// notification here do not follow the skipNotification as it is a sharing notification and not an update notification
				$this->getWigiiExecutor ()->throwEvent ()->shareElement ( PWithElementWithGroup::createInstance ( $p, $this->getRecord (), $group ) );
				$exec->invalidCache ( $p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $group->getId () );
			}
		}
		if ($oldGids) {
			$this->executionSink ()->log ( "Unshare element from " . $oldGids );
			$elS->unshareElement ( $this->getRootPrincipal (), $this->getRecord ()->getId (), $oldGids );
			$gpl = GroupListArrayImpl::createInstance ();
			ServiceProvider::getGroupAdminService ()->getGroupsWithoutDetail ( $p, $oldGids, $gpl );
			$currentGroups = $configS->getGroupPList ( $p, $exec->getCrtModule () )->getIds ();
			$removeCurrentItemFromList = false;
			foreach ( $gpl->getListIterator () as $group ) {
				// notification here do not follow the skipNotification as it is a sharing notification and not an update notification
				$this->getWigiiExecutor ()->throwEvent ()->unshareElement ( PWithElementWithGroup::createInstance ( $p, $this->getRecord (), $group ) );
				$exec->invalidCache ( $p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $group->getId () );
				unset ( $currentGroups [$group->getId ()] );
			}
			if (! $currentGroups) {
				// if the item is moved out of all the current groups then remove it from the list
				$exec->addJsCode ( "removeElementInList('" . $this->getRecord ()->getId () . "');" );
			}
		}
		$this->getWigiiExecutor ()->getNotificationService ()->unblockNotificationPostingValue ();
	}
	
	$elS->unLock ( $p, $this->getRecord () );
	
	$this->endActOnCheckedRecord ( $p, $exec );
}

	private $elementStatusJsCode;
	protected function renderBeforeForm($p, $exec, $state){

		$element = $this->getRecord();

		if($state!="addMessageToNotification"){
			//before was displaying the toolbar, but now no need of the toolbar in Edit mode
		}
	}
	protected function renderInForm($p, $exec, $state){

		$element = $this->getRecord();

		if($this->getState()=="addMessageToNotification"){
			//display the notification preview with the message input box
			$this->displayNotificationForm($p, $exec, $state);
		}
	}

	protected function displayNotificationForm($p, $exec, $state){
		$notificationS = $this->getWigiiExecutor()->getNotificationService();
		$elS = ServiceProvider::getElementService();
		$element = $this->getRecord();
		$groupList = GroupPListArrayImpl::createInstance();
		if($element->isSubElement()) {
			$elS->getAllGroupsContainingElement($p,
					Element::createInstance($exec->getCrtModule(),null,null,
					array('id' => $this->getWigiiExecutor()->getConfigurationContext()
										->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()
										->getOwnerElementId())),
					$groupList);
		}
		else $elS->getAllGroupsContainingElement($p, $element, $groupList);
		$notificationS->displayNotificationForm($p, "update", "Element", $exec->getCrtModule(), $element, $groupList);
		//hide all the record form
		$exec->addJsCode("$('#id".$notificationS->getNotificationMessageInputName()."').parent().nextAll(':not(.clear)').hide();");
	}

	protected function getUnlockUrl($p, $exec){
		return $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/element/".$this->getRecord()->getId();
	}
	protected function getCancelJsCode($p, $exec, $state){
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$isSubElement = ($this->getRecord() instanceof Element) && $this->getRecord()->isSubElement();

		// if subelement then reloads parent
		if($isSubElement) {
			$parentEltReload = $exec->getIdAnswer()."/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/detail/".$this->getRecord()->getElementParentId();
			if($exec->getIdAnswer()=="mainDiv"){
				//reload detail if in mainDiv, do not check open items
				$cancelJsCode = "update('".$parentEltReload."', true); ";
			} else {
				$cancelJsCode = "update('NoAnswer/".$this->getUnlockUrl($p, $exec)."'); update('".$parentEltReload."', true); ";
			}
		}
		else {
			if($exec->getIdAnswer()=="mainDiv"){
				//reload detail if in mainDiv, do not check open items
				$cancelJsCode = "update('".$exec->getIdAnswer()."/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/detail/".$this->getRecord()->getId()."', true); ";
			} else {
				$cancelJsCode = "update('NoAnswer/".$this->getUnlockUrl($p, $exec)."'); ";
			}
		}

		// if autoSave then refreshes the environment after cancel with same logic than in endActOnCheckedRecord
		$elementListContext = $this->getListContext();
		if($configS->getParameter($p, $exec->getCrtModule(), "autoSave")=="1") {
			//disable the cache of the element --> to reload change on next request
			$cancelJsCode .= $exec->getInvalidCacheJsCode($p, 'elementDialog', "selectElementDetail",  "element/detail/".$this->getRecord()->getId());
			if(!$isSubElement && isset($elementListContext)){
			    $elementListContext->setCrtSelectedItem($this->getRecord()->getId());
			}

			if($exec->getIdAnswer()!="mainDiv"){
				if($isSubElement){
					$rootElementId = $configS->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()->getOwnerElementId();
					$cancelJsCode .= "updateElementInList('" . $rootElementId . "');";
				} else {
					$cancelJsCode .= "updateElementInList('" . $this->getRecord()->getId() . "');";
				}
			}
			//invalid the cache of the list
			if(isset($elementListContext)) {
				if($elementListContext->getGroupPList()==null || $elementListContext->getGroupPList()->isEmpty()){
					$elementListContext->setGroupPList($configS->getGroupPList($p, $exec->getCrtModule()));
				}
				if ($elementListContext->getGroupPList()->count() == 1) {
					$ids = $elementListContext->getGroupPList()->getIds();
					$ids = reset($ids);
					$cancelJsCode .= $exec->getInvalidCacheJsCode($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $ids);
				}
			}
		}

		return $cancelJsCode;
	}
	protected function getOkJsCode(){
		$jsCode = '$("form input:submit", this).click();';
		if($this->isWorkzoneViewDocked()) $jsCode = '$("#'. $this->getFormId().'  input:submit").click();';
		return $jsCode;
	}
	
	protected function getDialogTitle($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		// if auto add (auto save on add) redefines modify in add
		if($exec->getCrtParameters(2) == 'autoadd') {
			return $transS->t($p, "addElement");
		}
		// else
		else return $transS->t($p, "editElement");
	}
	//the state is important, because mybe the current state is already changed when displaying the dialog
	protected function openDialog($p, $exec, $state){
		$transS = ServiceProvider::getTranslationService();

		//we need to add a correction for ie7
		if($exec->getBrowserName()=="msie" && $exec->getBrowserVersion()<=7){
			$corr = 2;
		} else $corr = 0;

		$idAnswer = $exec->getIdAnswer();
		if(!$idAnswer) $idAnswer = "mainDiv";

		if($state=="addMessageToNotification"){
			//in this case we don't take the cancelJsCode, because this step we have only the back option, not the cancelOption
			if($idAnswer!="mainDiv"){
				$this->getWigiiExecutor()->openAsDialogForm3B(
					$exec->getIdAnswer(), 520,
					'$("form input:submit", this).click();', $transS->t($p, "addMessageToNotification"), // replaced $("form", this).submit(); with $("form input:submit", this).click(); to allow autocomplete to work
					$transS->t($p, "sendNotification"), $transS->t($p, "skipNotification"), $transS->t($p, "back"), ' $("form input[name=\'action\']", this).val("newCheck"); $("form", this).submit(); ', ' $("form input[name=\'action\']", this).val("persistAndSkipNotify"); $("form", this).submit(); ' , '{ my : "center", at: "center" }', "$('#groupPanel li>div.highlight').removeClass('highlight');");
				$exec->addJsCode(' $(".ui-dialog-buttonpane .cancel", $("#' . $exec->getIdAnswer() . '").closest(".ui-dialog")).hide(); ');
			} else {
				//add the back option, rename Ok in Notify
				$exec->addJsCode('' .
					'$("#'.$exec->getIdAnswer().' .publicFormBorder").show();' .
					'$("#'.$exec->getIdAnswer().' .publicFormBorder button.cancel").hide();' .
					'$("#'.$exec->getIdAnswer().' .publicFormBorder button.ok span").text("'.$transS->h($p, "sendNotification").'");' .
					'$("#'.$exec->getIdAnswer().' .publicFormBorder button.ok").before(\'<button aria-disabled="false" role="button" class="H intermediate ui-button publicFormBorder ui-corner-all ui-button-text-only" type="button" style="margin-left:10px;float:right;"><span class="ui-button-text">'.$transS->h($p, "skipNotification").'</span></button>\');' .
					'$("#'.$exec->getIdAnswer().' .publicFormBorder button.intermediate").click(function(){' .
						'$(this).parents("form").find("input[name=\'action\']").val("persistAndSkipNotify"); $(this).parents("form").submit();' .
					'});' .
					'$("#'.$exec->getIdAnswer().' div.publicFormBorder").width(510);' .
					'');
			}
		} else {
			if($idAnswer!="mainDiv"){
				$cancelJsCode = $this->getCancelJsCode($p, $exec, $state);
				$cancelJsCode .= "$('#groupPanel li>div.highlight').removeClass('highlight');";
				$this->getWigiiExecutor()->openAsDialogForm(
					$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth()+$corr,
					$this->getOkJsCode() , $this->getDialogTitle($p, $exec), // replaced $("form", this).submit(); with $("form input:submit", this).click(); to allow autocomplete to work
					$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode);
			} else {
				$exec->addJsCode('' .
					'$("#'.$exec->getIdAnswer().' .publicFormBorder button.cancel").click(function(){' .
						''.$this->getCancelJsCode($p, $exec, $state).'' .
					'});' .
					'');
			}
		}
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();


		$this->getFormRenderer()->resetJsCodeAfterShow();
		$idAnswer = $exec->getIdAnswer();
		if(!$idAnswer) $idAnswer = "mainDiv";
		if($idAnswer=="mainDiv") $this->setIsDialog(false);

		$element = $this->getRecord();
		$state = $this->getState();

		$this->executionSink()->log("render form in state: ".$state);

		$this->getTrm()->setState($this->getState());

		$this->renderBeforeForm($p, $exec, $state);

		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), ($idAnswer=="mainDiv" ? false : $this->isDialog()));

		$this->renderInForm($p, $exec, $state);

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->getCaptchaHTML($this);

		if($state=="addMessageToNotification"){ //repost changedAutoSaveField
			//add hidden changedAutoSaveField field
			$changedAutoSaveField = $_POST["changedAutoSaveField"];
			if($changedAutoSaveField){
				foreach($changedAutoSaveField as $changedFieldname=>$val){
					?><input type="hidden"
	name="changedAutoSaveField[<?=$changedFieldname;?>]" value="<?=$val;?>" /><?
				}
			}
		}
		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), ($this->isWorkzoneViewDocked())?false:$this->isDialog(), $transS->t($p, "cancel"));
		if(isset($this->additionalText)){
			$this->getTrm()->put('<div class="clear"></div>');
			$this->getTrm()->put('<p style="margin:5px 0 10px 0;">'.$this->additionalText."</p>");
			$this->getTrm()->put('<div class="clear"></div>');
		}

		$this->openDialog($p, $exec, $state);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

		$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());

		//add Js code for elementStatus (this needs to be done once the dialog is opned, other wise positions are incorrect)
		if($this->elementStatusJsCode!=null) $exec->addJsCode($this->elementStatusJsCode);

		//disable autoSave on add message to notification
		if($state!="addMessageToNotification"){ 
			$this->addAutoSaveJSCode($p, $exec);
			$changedAutoSaveField = $_POST["changedAutoSaveField"];
			//if there is some posted changedAutoSaveField
			//then refill the JS variable changeAutoSaveFieldForSubmit
			if($changedAutoSaveField){
				$changedAutoSaveFieldJsCode = "";
				foreach($changedAutoSaveField as $changedFieldname=>$val){
					$changedAutoSaveFieldJsCode .= "changeAutoSaveFieldForSubmit['changedAutoSaveField[$changedFieldname]']=$val;";
				}
				$exec->addJsCode($changedAutoSaveFieldJsCode);
			}
		}
		
		//adds WigiiHelp icon in dialog title
		if($state!="addMessageToNotification"){
			$this->bindWigiiHelpService($p, $exec);
		}
		// If workzone view is docked :
		// Create middleBox in searchBar if doesn't exist
		// If edit then moves button from dialog box
		// If add then removes all buttons
		// If autosave on edit then renames cancel button as close
		if($this->isWorkzoneViewDocked()) {
			$exec->addJsCode("
					var middleBox = initMiddleBox(true);
			");
			//if we are in autosave mode we remove only button for keep 'delete draft'
			if($config->getParameter($p, $exec->getCrtModule(), "autoSave")=="1") {
				$exec->addJsCode("middleBox.find('.T button').remove();");
			} else { //we remove all
				$exec->addJsCode("middleBox.find('.T').children().remove();");
			}
			
			$exec->addJsCode("if($('div.ui-dialog #elementDialog').length!=1) middleBox.find('.T').append($('.publicFormBorder button').clone(true));");
	
			if($config->getParameter($p, $exec->getCrtModule(), "autoSave")=="1") { //if autosave mode we add close button on modify card
				$exec->addJsCode("
						if(middleBox.find('.T .el_deleteDraft').length==0) {
							var cancelButton = middleBox.find('.T .cancel');
							var height = cancelButton.height();
							var width = cancelButton.width();
						
							cancelButton.text(DIALOG_closeLabel).height(height).width(width).show(); 
						}
				");
			}
			$exec->addJsCode("
				middleBox.show();
				resize_groupPanel();
				resize_elementList();
			");
		}
	}
}



