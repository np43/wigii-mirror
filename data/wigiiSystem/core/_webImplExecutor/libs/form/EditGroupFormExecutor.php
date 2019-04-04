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

/*
 * Created on 11 february 10
 * by LWR
 */
class EditGroupFormExecutor extends FormExecutor {

	private $groupP;
	protected function setGroupP($groupP){ $this->groupP = $groupP; }
	protected function getGroupP(){ return $this->groupP; }

	private $actOnCheckedRecordRequest;
	protected function setActOnCheckedRecordRequest($request){ $this->actOnCheckedRecordRequest = $request; }
	protected function getActOnCheckedRecordRequest(){ return $this->actOnCheckedRecordRequest; }

	public static function createInstance($wigiiExecutor, $groupP, $record, $formId, $submitUrl=MANDATORY_ARG, $actOnCheckedRecordRequest=""){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setGroupP($groupP);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setActOnCheckedRecordRequest($actOnCheckedRecordRequest);
		return $fe;
	}

	public function CheckForm($p, $exec) {
		$isInitiatedfromDragDrop = $_POST["isInitiatedFromDragDrop"]==="true";
		//if initiated from drag and drop to not check the complete form as we will only change the groupParentId
		if($isInitiatedfromDragDrop){
			$this->preventInjectionForm($p, $exec);
		} else {
			parent::CheckForm($p, $exec);
		}
	}

	protected function doSpecificCheck($p, $exec){

		$transS = ServiceProvider::getTranslationService();
		$nsAS = ServiceProvider::getWigiiNamespaceAdminService();
		$groupEditRec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();

		$isInitiatedfromDragDrop = $_POST["isInitiatedFromDragDrop"]==="true";
		if($isInitiatedfromDragDrop){
			$group->setGroupParentId($_POST["groupParent_value"]);
		} else {
			//if this action is from the drag and drop, only groupParent is changed

			$groupParentId = $groupEditRec->getFieldValue("groupParent");
			$group->setGroupParentId($groupParentId);

			//try to set the groupname
			try{
				$group->setGroupname($groupEditRec->getFieldValue("groupname"));
			} catch(ServiceException $e){
				$this->addErrorToField($e->getMessage(), "groupname");
			}

			//the redefinition of the wigiiNamespace is possible only with a wigiiNamespace creator.
			//if a parent is defined then it will be the wigiiNamespace of the parentotherwise the wigiiNamespace of the principal is choosed
			//if parent group is defnined then fetch it and define the wigiiNamespace of children as parent group

			if($p->isWigiiNamespaceCreator()) {
				$group->setWigiiNamespace($nsAS->getWigiiNamespace($p, $groupEditRec->getFieldValue("wigiiNamespace")));
			} elseif(!$p->isWigiiNamespaceCreator() && $groupParentId != null){
				$groupParent = ServiceProvider::getGroupAdminService()->getGroupWithoutDetail($p, $groupParentId);
				$group->setWigiiNamespace($groupParent->getWigiiNamespace());
			} elseif(!$p->isWigiiNamespaceCreator() && $groupParentId == null && $group->getGroupParentId()!=null) {
				//if the group is moved as root group and was not a root group before
				//then the wigiiNamespace is updated with the principal one
				$group->setWigiiNamespace($p->getWigiiNamespace());
			} else {
				//dont't change the wigiiNamespace
			}

			//other fields should not have any problems
			$group->getDetail()->setDescription($groupEditRec->getFieldValue("description"));

		}
		if(!$p->getRootGroupCreator($group->getModule()) && $group->getGroupParentId()==null){
			$this->addErrorToField($transS->t($p, "cannotCreateRootGroups"), "groupParent");
			$this->addErrorToField($transS->t($p, "parentGroupShouldBeDefined"), "groupParent");
		}

	}

	protected function actOnCheckedRecord($p, $exec) {

		$groupAS = ServiceProvider::getGroupAdminService();
		$transS = ServiceProvider::getTranslationService();
		
		$groupEditRec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();

		try{
			$groupAS->persistGroup($p, $group);
			$exec->addJsCode(" adminGroup_crtSelectedGroup = '".$group->getId()."'; ");
		} catch(GroupAdminServiceException $e){
			if($e->getCode() == GroupAdminServiceException::GROUPNAME_ALREADY_USED_IN_NAMESPACE){
				//changed the 4 October 2011, the groupname is no more unique per wigiiNamespace
				//this exception should no more happen
				throw $e;
//				$transS = ServiceProvider::getTranslationService();
//				$this->addErrorToField($transS->h($p, "groupnameAlreadyUsed"), "groupname");
//				return;
			}
			throw $e;
		}

		$groupAS->unLock($p, $group);

		$this->getWigiiExecutor()->throwEvent()->updateGroup(PWithGroup::createInstance($p, $group));

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		if($exec->getIdAnswer() != "confirmationDialog"){
			echo ExecutionServiceImpl::answerRequestSeparator;
			echo "confirmationDialog";
			echo ExecutionServiceImpl::answerParamSeparator;
		}
		$this->getWigiiExecutor()->operationSuccessfullMessage("confirmationDialog", 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

		if($this->getActOnCheckedRecordRequest()!=null){
			$exec->addRequests($this->getActOnCheckedRecordRequest());
		}
	}


	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$groupAS = ServiceProvider::getGroupAdminService();

		$groupEditRec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();
		$state = $this->getState();

		//fill values on start
		if($state=="start"){
			$groupEditRec->setFieldValue($group->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
			$groupEditRec->setFieldValue($group->getGroupname(), "groupname");
			$groupEditRec->setFieldValue($group->getDetail()->getDescription(), "description");
			//we add the id in the groupParentId value. JS code will do the rest
			$groupEditRec->setFieldValue($group->getGroupParentId(), "groupParent");
		}
//		if(!$p->isWigiiNamespaceCreator()){
//			$groupEditRec->getWigiiBag()->setDisabled(true, "wigiiNamespace");
//			$groupEditRec->setFieldValue($group->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
//		}

//		$userEditRec->getWigiiBag()->setHelp($transS->h($p, "authenticationServerHelp"), "authenticationServer");

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());


		$this->getTrm()->displayRemainingForms("groupParent");

		$errorOnGroupParent = $groupEditRec->getWigiiBag()->getError("groupParentName");
		if($errorOnGroupParent){
			echo '<br /><font class="redFont" >';
			echo $errorOnGroupParent;
			echo '</font>';
		}

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$cancelJsCode = $this->getCancelJSCode($p, $exec, $group);

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $this->getDialogBoxtitle($p),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode);


		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
		
		$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());
	}
	protected function getCancelJSCode($p, $exec, $group){
		return "update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/group/".$group->getId().(array_pop(explode("/",$this->getSubmitUrl()))=="groupPanel" ? "/groupPanel" : "")."');";
	}
	public function getDialogBoxTitle($p){
		$transS = ServiceProvider::getTranslationService();
		return $transS->t($p, "editGroup");
	}
}



