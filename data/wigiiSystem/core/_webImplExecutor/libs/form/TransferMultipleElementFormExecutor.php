<?php
/**
 *  This file is part of Wigii.
 *
 *  Wigii is free software: you can redistribute it and\/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Wigii is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Wigii.  If not, see <http:\//www.gnu.org/licenses/>.
 *
 *  @copyright  Copyright (c) 2012 Wigii 		 http://code.google.com/p/wigii/    http://www.wigii.ch
 *  @license    http://www.gnu.org/licenses/     GNU General Public License
 */

/*
 * Created on 11/07/2012
 * by LWR
 */
class TransferMultipleElementFormExecutor extends FormExecutor {

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

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $rootPrincipal, $elementPAList, $lc){
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

		$groupIds = $this->getRecord()->getFieldValue("transfer_predefined");
		$groupIds = array_combine($groupIds, $groupIds);

		//lookup each groups and find out inconsitencies regarding modul$gExp = 'module = "' . $exec->getCrtModule()->getModuleName() . '" AND emailNotification LIKE %' . $pEmail . '%';

		//$this->moveInGroupPList is already defined in doSpecificChecks

		$moveInModule = $this->getRecord()->getFieldValue("transfer_changeModuleAndMove");
		if(!$this->moveInGroupPList->isAllGroupsInSameModule()){
			throw new ElementServiceException("all groups needs to be in the same module", ElementServiceException::FORBIDDEN);
		}
		$moduleName = $this->moveInGroupPList->getModule()->getModuleName();

		//check group is in the right module
		if((!$moveInModule && $moduleName != $exec->getCrtModule()->getModuleName())){
			throw new ElementServiceException("item from ".$exec->getCrtModule()->getModuleName()." cannot be transfered without moving in groups: ".implode(",", $groupIds)." because the tab of those groups is not the same than the current", ElementServiceException::FORBIDDEN);
		}

		if($this->getState()=="persistAndSkipNotify"){
			$this->getWigiiExecutor()->getNotificationService()->skipNextNotification();
		}
		try{

			if(!$moveInModule){
				$groupPList = $elS->addMultipleElementSharing($this->getRootPrincipal(), $this->getRootPrincipal(), $this->getElementPAList(), $groupIds);
				$this->getWigiiExecutor()->throwEvent()->addMultipleElementSharing(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $this->getElementPAList(), $groupPList));
			} else {
				$r = $elS->moveMultipleElementToModule($this->getRootPrincipal(), $p, $this->getElementPAList(), $this->moveInGroupPList->getModule(), $groupIds);
				//clear the multiple selection
				$this->getGeneralListContext()->resetMultipleSelection();
				$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/stopMultipleSelection/");
				$this->getWigiiExecutor()->throwEvent()->moveMultipleElementToModule(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $this->getElementPAList(), $this->moveInGroupPList));
			}
		} catch (AuthorizationServiceException $ase){
			if($ase->getCode() == AuthorizationServiceException::OBJECT_IS_LOCKED){
				$this->getWigiiExecutor()->displayListOfLockedItems($p, $this->getElementPAList()->getIds(), $exec->getIdAnswer());
				$this->setState("check");
				$this->addStateError();
				return;
			} else throw $ase;
		} catch (ElementServiceException $else){
			if($else->getCode() == ElementServiceException::ELEMENT_SHARED_IN_NONE_WRITABLE_GROUPS){
				echo '<font class="R">'.$transS->h($p, "transferNoneWritableGroups").'</font>';
				$this->setState("check");
				$this->addStateError();
				return;
			} else throw $else;

		}

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

		$exec->invalidCache($p, 'elementDialog');
		$exec->invalidCache($p, 'moduleView');

		if($moveInModule){ //the list needs to be refreshed if not $keepInOriginalGroup
			$exec->addRequests("moduleView/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/display/moduleView/");
		}
	}

	private $moveInGroupList;
	protected function doSpecificCheck($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$mAS = ServiceProvider::getModuleAdminService();

		//on error do not continue checks
		if($this->hasError()) return;

		$groupIds = $this->getRecord()->getFieldValue("transfer_predefined");
		$groupIds = array_combine($groupIds, $groupIds);

		//lookup each groups and find out inconsitencies regarding modul$gExp = 'module = "' . $exec->getCrtModule()->getModuleName() . '" AND emailNotification LIKE %' . $pEmail . '%';
		$gExp = 'id IN (' . implode(",", $groupIds) . ')';
		$parser = TechnicalServiceProvider :: getFieldSelectorLogExpParser();
		$gExp = $parser->createLogExpFromString($gExp);
		$modules = $mAS->getModulesFromSelectedGroups($this->getRootPrincipal(), $gExp);
		if($modules && count($modules)>1){
			$this->addErrorToField($transS->h($p, "transferNoneUniqueModule"), "transfer_predefined");
		} else {
			$this->moveInGroupPList = GroupListAdvancedImpl :: createInstance();
			$lf = ListFilter::createInstance();
			$lf->setFieldSelectorLogExp($gExp);
			$lf->setFieldSelectorList($groupAS->getFieldSelectorListForGroupWithoutDetail());
			$groupAS->getSelectedGroups($this->getRootPrincipal(), $lf, $this->moveInGroupPList);
			$moveInModule = $this->getRecord()->getFieldValue("transfer_changeModuleAndMove");

			$moduleName = $this->moveInGroupPList->getModule()->getModuleName();

			//check group is in the right module
			if((!$moveInModule && $moduleName != $exec->getCrtModule()->getModuleName())){
				//add a warning + tick the transfer_changeModuleAndMove
				$exec->addJsCode("$('#".$this->getFormId()."_transfer_changeModuleAndMove_value_checkbox').attr('checked', true);");
				echo '<font class="R changeModule">'.$transS->h($p, "transferChangeModule1")." ".$transS->t($p, $moduleName).$transS->h($p, "transferChangeModule2")." ".$transS->h($p, "transferChangeModule3")."</font><br /><br />";
				$this->addStateError();
				$exec->addJsCode("$('#".$this->getFormId()."__transfer_predefined input').click(function(){ $('#".$this->getFormId()."_transfer_changeModuleAndMove_value_checkbox').attr('checked', false); });");
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
		}


		if(!$this->hasError() && $this->getState()=="check"){
			try{
				if(!$moveInModule){
					$eventName = "share";
				} else {
					$eventName = "setShare";
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
			$groupIds = $this->getRecord()->getFieldValue("transfer_predefined");
			$groupAS = ServiceProvider::getGroupAdminService();
			$groupList = GroupListArrayImpl::createInstance();
			$groupAS->getGroupsWithoutDetail($p, $groupIds, $groupList);
			$notificationS->displayNotificationForm($p, "moveToModule", "MultipleElement", $exec->getCrtModule(), null, $groupList, $this->getElementPAList());
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

			//hide all the record form
			$exec->addJsCode("$('#id".$notificationS->getNotificationMessageInputName()."').parent().nextAll(':not(.clear)').hide();");
		} else {
			$this->getWigiiExecutor()->openAsDialogForm(
				$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth()+$corr,
				'$("form", this).submit();', $transS->t($p, "transferMultipleElement"),
				$transS->t($p, "ok"), $transS->t($p, "cancel"), null);
		}

		$exec->addJsCode("
$('#".$this->getFormId()."__transfer_changeModuleAndMove').hide();
if($('#".$this->getFormId()."_keepInOriginalGroup_value_checkbox').attr('checked')){
	$('#".$this->getFormId()."_inTab_value_select').attr('disabled', true);
	$('#".$this->getFormId()."_inTab_value_select').val('".$exec->getCrtModule()->getModulename()."');
}
$('#".$this->getFormId()."_keepInOriginalGroup_value_checkbox').click(function(){
	$('#".$this->getFormId()."_inTab_value_select').attr('disabled', !!this.checked);
	if(!!this.checked && $('#".$this->getFormId()."_inTab_value_select').val()!='".$exec->getCrtModule()->getModulename()."'){
		$('#".$this->getFormId()."_inTab_value_select').val('".$exec->getCrtModule()->getModulename()."').change();
	}
});
$('#".$this->getFormId()."_inTab_value_select').change(function(){
	$('option', this).each(function(){
		$('#".$this->getFormId()."_inGroup'+this.value+'_value_select').val(0).change();
		if($(this).parent().val()==this.value){
			$('#".$this->getFormId()."__inGroup'+this.value+'').show();
		} else {
			$('#".$this->getFormId()."__inGroup'+this.value+'').hide();
		}
	});
});
$('#".$this->getFormId()." select[id!=".$this->getFormId()."_inGroup'+$('#".$this->getFormId()."_inTab_value_select').val()+'_value_select]:not(:first)').parent().parent().hide();

$('#".$this->getFormId()." select:not(:first)').change(function(){
	$('#".$this->getFormId()."_inGroupId_value_text').val($(this).val());
});
$('#".$this->getFormId()."__keepInOriginalGroup').after($('#".$this->getFormId()."__inTab'));
");

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}



