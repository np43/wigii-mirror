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

/*
 * Created on 15 sept. 09
 * by LWR
 */
class ShareMultipleElementFormExecutor extends FormExecutor {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("FormExecutor");
		}
		return $this->_executionSink;
	}

	private $rootPrincipal;
	protected function setRootPrincipal($var){$this->rootPrincipal = $var; }
	protected function getRootPrincipal(){return $this->rootPrincipal; }
	private $elementPAList;
	protected function setElementPAList($var){$this->elementPAList = $var; }
	protected function getElementPAList(){return $this->elementPAList; }
	private $generalListContext; //this is to be able to reset the multipleSelection
	protected function setGeneralListContext($var){$this->generalListContext = $var; }
	protected function getGeneralListContext(){return $this->generalListContext; }

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $rootPrincipal=MANDATORY_ARG, $elementPAList=MANDATORY_ARG, $lc=MANDATORY_ARG){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRootPrincipal($rootPrincipal);
		$fe->setElementPAList($elementPAList);
		$fe->setGeneralListContext($lc);
		return $fe;
	}

	protected function setState($state){
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

	protected function actOnCheckedRecord($p, $exec) {
		$transS = ServiceProvider::getTranslationService();
		$elS = ServiceProvider::getElementService();
		$groupAS = ServiceProvider::getGroupAdminService();

		$rec = $this->getRecord();
		$groupId = $this->getRecord()->getFieldValue("inGroupId");
		$groupP = $groupAS->getGroup($p, $groupId, $groupAS->getFieldSelectorListForGroupWithoutDetail());
		$group = $groupP->getGroup();
		$keepInOriginalGroup = $this->getRecord()->getFieldValue("keepInOriginalGroup");
		$moduleName = $this->getRecord()->getFieldValue("inTab");

		//check rights on group
		if(!$groupP->getRights()->canWriteElement()){
			throw new ElementServiceException("move selected elements needs a writable group. groupId: ".$groupId, ElementServiceException::FORBIDDEN);
		}
		//check group is in the right module
		if((!$keepInOriginalGroup && $group->getModule()->getModuleName() != $moduleName) || ($keepInOriginalGroup && $group->getModule()->getModuleName() != $exec->getCrtModule()->getModuleName())){
			throw new ElementServiceException("group: ".$groupId." in module: ".$group->getModule()->getModuleName()." must be in the selected tab: ".$moduleName, ElementServiceException::FORBIDDEN);
		}

		if($this->getState()=="persistAndSkipNotify"){
			$this->getWigiiExecutor()->getNotificationService()->skipNextNotification();
		}
		if($keepInOriginalGroup){
			$groupPList = $elS->addMultipleElementSharing($this->getRootPrincipal(), $p, $this->getElementPAList(), array($groupId=>$groupId));
			$this->getWigiiExecutor()->throwEvent()->addMultipleElementSharing(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $this->getElementPAList(), $groupPList));
		} else if($group->getModule()->getModuleName() == $exec->getCrtModule()->getModuleName()){
			$groupPList = $elS->setMultipleElementSharing($this->getRootPrincipal(), $p, $this->getElementPAList(), array($groupId=>$groupId));
			$this->getWigiiExecutor()->throwEvent()->setMultipleElementSharing(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $this->getElementPAList(), $groupPList));
		} else {
			//move in different tab
			$r = $elS->moveMultipleElementToModule($this->getRootPrincipal(), $p, $this->getElementPAList(), $groupP);
			//clear the multiple selection
			$this->getGeneralListContext()->resetMultipleSelection();
			$this->getWigiiExecutor()->throwEvent()->moveMultipleElementToModule(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $this->getElementPAList(), GroupPListArrayImpl::createInstance()->addGroupP($groupP)));
		}

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

		$exec->invalidCache($p, 'elementDialog');
		$exec->invalidCache($p, 'moduleView');

		if(!$keepInOriginalGroup){ //the list needs to be refreshed if not $keepInOriginalGroup
			$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");
		}
	}

	protected function doSpecificCheck($p, $exec){

		//the inGroup of the chosen module should be filled!
		$transS = ServiceProvider::getTranslationService();
		$keepInOriginalGroup = $this->getRecord()->getFieldValue("keepInOriginalGroup");
		if($keepInOriginalGroup){
			$moduleName = $exec->getCrtModule()->getModuleName();
			$this->getRecord()->setFieldValue($moduleName, "inTab");
		} else {
			$moduleName = $this->getRecord()->getFieldValue("inTab");
		}
		$inGroup = $this->getRecord()->getFieldValue("inGroup".$moduleName);
		$inGroupId = $this->getRecord()->getFieldValue("inGroupId");
		if($inGroup == null || $inGroup === "none" || $inGroupId==null){
			$this->addErrorToField($transS->h($p, "compulsory_field"), "inGroup".$moduleName);
		}

		if($this->getState() == "persistAndNotify"){
			//validate the email
			//if the email is empty (because of no notification needed, validation works)
			if(!validateEmail($this->getWigiiExecutor()->getNotificationService()->getNotificationEmailInputValue()) ||
				!validateEmails($this->getWigiiExecutor()->getNotificationService()->getNotificationToInputValue())
				){
				$this->setState("addMessageToNotification");
			}
		}

		if(!$this->hasError() && $this->getState()=="check"){
			try{
				if($keepInOriginalGroup){
					$eventName = "share";
				} else if($moduleName == $exec->getCrtModule()->getModuleName()){
					$eventName = "setShare";
				} else {
					$eventName = "moveToModule";
				}
				$this->getWigiiExecutor()->getNotificationService()->isNotificationNeededForMultipleElement($p, $exec->getCrtModule(), $eventName, $this->getRecord());
				if($this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Notification_enableMessage")!="0"){
					$this->setState("addMessageToNotification");
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

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();

		$shareMultipleElementRec = $this->getRecord();
		$state = $this->getState();
		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		//$this->renderInForm($p, $exec, $state);
		if($state=="addMessageToNotification"){
			//display the notification preview with the message input box
			$notificationS = $this->getWigiiExecutor()->getNotificationService();
			$groupId = $this->getRecord()->getFieldValue("inGroupId");
			$groupAS = ServiceProvider::getGroupAdminService();
			$groupP = $groupAS->getGroup($p, $groupId, $groupAS->getFieldSelectorListForGroupWithoutDetail());
			$group = $groupP->getGroup();
			$notificationS->displayNotificationForm($p, "moveToModule", "MultipleElement", $exec->getCrtModule(), null, GroupPListArrayImpl::createInstance()->addGroupP($groupP), $this->getElementPAList());
		}

		//disable any file fields. there is no sense doing a multiple operation on a file
		foreach($shareMultipleElementRec->getFieldList()->getListIterator() as $field){
			//hide all the record form
			if($state=="addMessageToNotification"){
				$shareMultipleElementRec->getWigiiBag()->setHidden(true, $field->getFieldName());
			}
			if($field->getDataType() == null) continue;
			if($field->getDataType()->getDataTypeName() == "Files"){
				$shareMultipleElementRec->getWigiiBag()->setHidden(true, $field->getFieldName());
			}
		}

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		if($exec->getBrowserName()=="msie" && $exec->getBrowserVersion()<=7){
			$corr = 2;
		} else $corr = 0;

		if($state=="addMessageToNotification"){
			$this->getWigiiExecutor()->openAsDialogForm3B(
				$exec->getIdAnswer(), 520,
				'$("form", this).submit();', $transS->t($p, "addMessageToNotification"),
				$transS->t($p, "sendNotification"), $transS->t($p, "skipNotification"), $transS->t($p, "back"), ' $("form input[name=\'action\']", this).val("newCheck"); $("form", this).submit(); ', ' $("form input[name=\'action\']", this).val("persistAndSkipNotify"); $("form", this).submit(); ');
			$exec->addJsCode(' $(".ui-dialog-buttonpane .cancel", $("#' . $exec->getIdAnswer() . '").closest(".ui-dialog")).hide(); ');
		} else {
			$this->getWigiiExecutor()->openAsDialogForm(
				$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth()+$corr,
				'$("form", this).submit();', $transS->t($p, "shareMultipleElement"),
				$transS->t($p, "ok"), $transS->t($p, "cancel"), null);
		}

		$exec->addJsCode("" .
			"if($('#".$this->getFormId()."_keepInOriginalGroup_value_checkbox').attr('checked')){" .
				"$('#".$this->getFormId()."_inTab_value_select').attr('disabled', true);" .
				"$('#".$this->getFormId()."_inTab_value_select').val('".$exec->getCrtModule()->getModulename()."');" .
			"}" .
			"$('#".$this->getFormId()."_keepInOriginalGroup_value_checkbox').click(function(){" .
				"$('#".$this->getFormId()."_inTab_value_select').attr('disabled', !!this.checked);" .
				"if(!!this.checked && $('#".$this->getFormId()."_inTab_value_select').val()!='".$exec->getCrtModule()->getModulename()."'){" .
					"$('#".$this->getFormId()."_inTab_value_select').val('".$exec->getCrtModule()->getModulename()."').change();" .
				"}" .
			"});" .
			"$('#".$this->getFormId()."_inTab_value_select').change(function(){" .
				"$('option', this).each(function(){" .
					"$('#".$this->getFormId()."_inGroup'+this.value+'_value_select').val(0).change();" .
					"if($(this).parent().val()==this.value){" .
						"$('#".$this->getFormId()."__inGroup'+this.value+'').show();" .
					"} else {" .
						"$('#".$this->getFormId()."__inGroup'+this.value+'').hide();" .
					"}" .
				"});" .
			"});" .
			"$('#".$this->getFormId()." select[id!=".$this->getFormId()."_inGroup'+$('#".$this->getFormId()."_inTab_value_select').val()+'_value_select]:not(:first)').parent().parent().hide();" .
			"$('#".$this->getFormId()." select:not(:first)').change(function(){" .
				"$('#".$this->getFormId()."_inGroupId_value_text').val($(this).val());" .
			"});" .
			"$('#".$this->getFormId()."__keepInOriginalGroup').after($('#".$this->getFormId()."__inTab'));" .
			"");

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}



