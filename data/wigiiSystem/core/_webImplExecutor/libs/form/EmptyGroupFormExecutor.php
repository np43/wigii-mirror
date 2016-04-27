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
 * Created on 23.04.2015
 * by CWE
 */
class EmptyGroupFormExecutor extends FormExecutor {

	private $groupP;
	protected function setGroupP($groupP){ $this->groupP = $groupP; }
	protected function getGroupP(){ return $this->groupP; }

	private $actOnCheckedRecordRequest;
	protected function setActOnCheckedRecordRequest($request){ $this->actOnCheckedRecordRequest = $request; }
	protected function getActOnCheckedRecordRequest(){ return $this->actOnCheckedRecordRequest; }

	private $rootPincipal; //this is needed for moving the folder in the trashbin in case user has no access to the trashbin
	public function setRootPrincipal($rootP){ $this->rootPrincipal = $rootP; }
	protected function getRootPrincipal(){ return $this->rootPrincipal; }
	
	public static function createInstance($wigiiExecutor, $groupP, $record, $formId, $submitUrl, $actOnCheckedRecordRequest=""){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setGroupP($groupP);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setActOnCheckedRecordRequest($actOnCheckedRecordRequest);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){
		//no special check is required
	}

	protected function actOnCheckedRecord($p, $exec) {

		$groupAS = ServiceProvider::getGroupAdminService();
		$transS = ServiceProvider::getTranslationService();

		$groupEditRec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();
		$parentId = $group->getGroupParentId();
		$workingModule = $group->getModule();
		$workingModuleName = $workingModule->getModuleName();
		
		GroupAdminServiceException::throwNotImplemented();
		
		$moveContentToParent = ($groupEditRec->getFieldValue("deleteGroupOption")=="moveChildrenAndContentToParent");
		
		// if trashbin exists and user doesn't want to keep content, then moves group to trashbin
		$trashBinGroupId = (string)$this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $group->getModule(), "trashBinGroup");
		if(!empty($trashBinGroupId) && !$moveContentToParent && $parentId != $trashBinGroupId){
			// checks for delete authorization
			$this->assertPrincipalAuthorizedForEmptyGroup($p, $this->getGroupP());
			
			$rootP = $this->getRootPrincipal();
			// transfers lock to root principal
			ServiceProvider::getDbAdminService()->lock($rootP, 'Groups', $group, true);
			try {
				// moves group to trashbin
				$groupAS->moveGroup($rootP, $group->getId(), $trashBinGroupId);
			}
			catch(Exception $e) {
				if($e instanceof GroupAdminServiceException && $e->getCode() == GroupAdminServiceException::INVALID_ARGUMENT) {
					$e = new GroupAdminServiceException("trashBinGroup $trashBinGroupId is not a valid group. Correct the configuration. Root message is : ".$e->getMessage(), GroupAdminServiceException::CONFIGURATION_ERROR);
				}
				// unlocks
				$this->getWigiiExecutor()->unLockEntity($rootP, $exec, null, "group", $group);
				throw $e;
			}
			// unlocks
			$this->getWigiiExecutor()->unLockEntity($rootP, $exec, null, "group", $group);
		}
		// else deletes group
		else {
			//delete possible files in activities
			$groupActivityWithConfig = array (
				"groupPortal",
				"groupHtmlContent",
				"groupXmlPublish",
				"groupSubscription"
			);
			foreach ($groupActivityWithConfig as $activityName) {
				$rec = $this->getWigiiExecutor()->createActivityRecordForForm($p, Activity :: createInstance($activityName), $exec->getCrtModule());
				switch ($activityName) {
					case "groupPortal" :
						$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getPortal(), $rec->getActivity());
						break;
					case "groupHtmlContent" :
						$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getHtmlContent(), $rec->getActivity());
						break;
					case "groupXmlPublish" :
						$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getXmlPublish(), $rec->getActivity());
						break;
					case "groupSubscription" :
						$rec->getWigiiBag()->importFromSerializedArray($group->getDetail()->getSubscription(), $rec->getActivity());
						break;
				}
				foreach ($rec->getFieldList()->getListIterator() as $field) {
					if ($field->getDataType() != null && $field->getDataType()->getDataTypeName() == "Files") {
						if (isImage($rec->getFieldValue($field->getFieldName(), "mime")))
							@ unlink(FILES_PATH . "tn_" . $rec->getFieldValue($field->getFieldName(), "path"));
						@ unlink(FILES_PATH . $rec->getFieldValue($field->getFieldName(), "path"));
					}
				}
			}
	
			//delete group config if defined
			if ($this->getWigiiExecutor()->getConfigurationContext()->doesGroupHasConfigFile($p, $group)) {
				$configfile = $this->getWigiiExecutor()->getConfigurationContext()->getGroupConfigFilename($p, $group);
				@ unlink($configfile);
			}
			//delete the group and all the content associated to him
			$groupAS->deleteGroup($p, $group->getId(), $moveContentToParent);
		}
		
		$exec->addJsCode("actOnCloseDialog('" . $exec->getIdAnswer() . "');");

		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

		$this->getWigiiExecutor()->throwEvent()->deleteGroup(PWithGroup :: createInstance($p, $group));

		if(array_pop(explode("/",$this->getSubmitUrl()))=="groupPanel"){
			//the timeout is important to let the JS of the request to be executed first, as the setListenersToGroupPanel
			$exec->addJsCode(" setTimeout(function(){ clickOnGroupInGroupPanel(".$parentId.");}, 100); ");
		}

		if($this->getActOnCheckedRecordRequest()!=null){
			$exec->addRequests($this->getActOnCheckedRecordRequest());
		}
	}
	protected function assertPrincipalAuthorizedForEmptyGroup($p, $groupP)
	{
		$autoS = ServiceProvider::getAuthorizationService();
		// checks general authorization based on same rights as deletion
		$autoS->assertPrincipalAuthorized($p, "GroupAdminService", "deleteGroup");
		// check specific rights
		if(is_null($groupP->getRights()) ||
				!$groupP->getRights()->canModify() ||
				!$p->getGroupCreator($groupP->getGroup()->getModule()) ||
				(!$p->getRootGroupCreator($groupP->getGroup()->getModule()) && $groupP->getGroup()->getGroupParentId()==null)
		)
		{
			$autoS->fail($p, "has no right to empty the group ".$groupP->getId());
		}
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$groupAS = ServiceProvider::getGroupAdminService();

		$groupEditRec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();
		$wigiiNamespace = $group->getWigiiNamespace()->getWigiiNamespaceName();
		$state = $this->getState();

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/48x48/status/not-known.png";?>" style="float:left;margin:0px 5px 0px 0px;"/><?

		echo '<span style="line-height:1.7;">';
		echo $transS->t($p, "areYouSureEmptyGroup");
		echo "<br>";
		echo "<b>" . ($wigiiNamespace ? $wigiiNamespace . " : " : "") . $group->getGroupname() . "</b>";
		echo '</span>';
		echo "<br>";

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$cancelJsCode = "update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/group/".$group->getId().(array_pop(explode("/",$this->getSubmitUrl()))=="groupPanel" ? "/groupPanel" : "")."');";

		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $this->getDialogBoxtitle($p),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode);


		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
	public function getDialogBoxTitle($p){
		$transS = ServiceProvider::getTranslationService();
		return $transS->t($p, "emptyGroup");
	}
}



