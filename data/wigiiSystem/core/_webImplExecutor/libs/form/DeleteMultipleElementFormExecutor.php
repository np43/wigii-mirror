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
 * Created on 18 sept. 13
 * by LWR
 */
class DeleteMultipleElementFormExecutor extends DeleteElementFormExecutor {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("DeleteMultipleElementFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("DeleteMultipleElementFormExecutor");
		}
		return $this->_executionSink;
	}

	private $elementPAList;
	public function setElementPAList($var){$this->elementPAList = $var; }
	protected function getElementPAList(){return $this->elementPAList; }

	private $record;
	public function getRecord() {
		return $this->record;
	}

	//set record contains the elementsDeleteRec which is necessary to display form with trm
	public function setRecord($record) {
		if(!$this->getTrm()){
			$this->setTrm($this->createTrmInstance());
		}
		$this->getTrm()->reset($record);
		$this->record = $record;
	}

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $rootPrincipal, $elementPAList){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRootPrincipal($rootPrincipal);
		$fe->setElementPAList($elementPAList);
		return $fe;
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

	protected function actOnCheckedRecord($p, $exec) {
		$elS = ServiceProvider::getElementService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$transS = ServiceProvider::getTranslationService();
		$notificationS = $this->getWigiiExecutor()->getNotificationService();

		$remaingGroups = null;
		$fileFields = $this->getFileFields();
		$elementPAList = $this->getElementPAList();

		$trashBinGroupId = (string)$configS->getParameter($p, $exec->getCrtModule(), "trashBinGroup");
		$moveInTrash = false;
		if($trashBinGroupId){
			// checks trashbin validity
			$trashBinGroup = $groupAS->getGroupWithoutDetail($p, $trashBinGroupId);
			if(is_null($trashBinGroup) || $trashBinGroup->getModule() !== $exec->getCrtModule()) {
				throw new GroupAdminServiceException("trashBinGroup $trashBinGroupId is not valid group of module ".$exec->getCrtModule()->getModuleName(), GroupAdminServiceException::CONFIGURATION_ERROR);
			}
			$groupList = GroupListAdvancedImpl :: createInstance();
			$elS->getAllGroupsWithoutDetailContainingElements($p, $this->getElementPAList(), $groupList);
			//delete items only if all items are in the trashbin folder only
			if($groupList->getItemInList($trashBinGroupId) && $groupList->count()==1){
				$moveInTrash = false;
			} else {
				$moveInTrash = true;
			}
		}

		$groupList = $this->getAllGroupsToNotifyOnElementsDelete($p, $exec, $elementPAList);

		//remove autoSharing
		$gids = ValueListLinkedIdGroupImpl::createInstance(true, ValueListArrayMapper::Natural_Separators, true);
		$configS->getFields($p, $exec->getCrtModule(), null, $gids);
		$linkedGids = $gids->getListIterator();
		if($linkedGids == null) $linkedGids = array();
		$linkedGids = array_diff_key($linkedGids, $groupList->getIds());
		if($linkedGids){
			$notificationS->blockNotificationPostingValue();
			$removeSharingGroupPList = $elS->removeMultipleElementSharing($this->getRootPrincipal(), $this->getRootPrincipal(), $elementPAList, $linkedGids);
			$this->getWigiiExecutor()->throwEvent()->removeMultipleElementSharing(PWithModuleWithElementPListWithGroupList::createInstance($p, $exec->getCrtModule(), $elementPAList, $removeSharingGroupPList));
			foreach($removeSharingGroupPList->getListIterator() as $groupP){
				$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $groupP->getId(), $groupP->getGroup()->getWigiiNamespace(), $groupP->getGroup()->getModule());
			}
			$notificationS->unblockNotificationPostingValue();
		}

		if($moveInTrash){
			$removeSharingIds = $groupList->getIds();
			unset($removeSharingIds[$trashBinGroupId]);
			$elS->addMultipleElementSharing($this->getRootPrincipal(), $this->getRootPrincipal(), $elementPAList, $trashBinGroupId);
			$elS->updateSys_dateToNow($p, $elementPAList->getIds());
			if($removeSharingIds) $elS->removeMultipleElementSharing($this->getRootPrincipal(), $p, $elementPAList, $removeSharingIds, false); //allow removing in last writable group
		} else {
			$elementPList = $elS->deleteMultipleElements($this->getRootPrincipal(), $p, $elementPAList);
		}

		$this->getListContext()->resetMultipleSelection();
		$this->getWigiiExecutor()->updateMultipleSelectionDialogForContext($p, $exec, $this->getListContext());

		$remainingElements = $elS->doElementsExist($p, $elementPAList);
		//eput($remainingElements);
		if ($remainingElements != null) {
			//remains some elements
			foreach ($remainingElements as $key => $val) {
				$remainingElements[$key] = "*";
			}
			$remainingElements["footer"] = "* " . $transS->t($p, "elementRemainsInUnWritableGroups");
		}

		$exec->invalidCache($p, 'elementDialog');
		$exec->invalidCache($p, 'moduleView');
		$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/");
		$exec->addJsCode("$('#groupPanel div.highlight').removeClass('highlight');");
		//throw the event only with element which are really deleted
		if (addslashes($_POST['action']) == "persistAndSkipNotify") {
			$notificationS->skipNextNotification();
		}
		$this->getWigiiExecutor()->throwEvent()->deleteMultipleElements(PWithModuleWithElementPListWithGroupListWithArray :: createInstance($p, $exec->getCrtModule(), $elementPAList, $groupList, $remainingElements));

		$exec->addJsCode("actOnCloseDialog('" . $exec->getIdAnswer() . "');");

		$expl = "";
		$title = $transS->t($p, "operationDoneSuccessfully");
		if($moveInTrash){
			$expl .= "";
		} else {
			if ($remainingElements != null) {
				$expl .= $transS->t($p, "elementsRemainsInSomeUnWritableGroups");
				$first = true;
				foreach ($remainingElements as $key => $val) {
					if ($key == "footer")
						continue;
					if ($first)
						$first = false;
					else
						$expl .= ", ";
					$expl .= $key;
				}
			}
		}

		if($expl){
			$this->getWigiiExecutor()->openAsMessage($exec->getIdAnswer(), $this->getTotalWidth(), $title, $expl, null, $transS->t($p, "ok"), null, null, "done");
		} else {
			$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $title, "", "done");
		}

		$this->getWigiiExecutor()->unLockEntity($p, $exec, null, "elements", $elementPAList);

		//remove any history files even if not in config
		foreach ($elementPAList->getListIterator() as $elementP) {
			$element = $elementP->getElement();
			if ($remainingElements != null && $remainingElements[$element->getId()] != null){
				//check if the elementP is really deleted, if not do nothing
			} else {
				rrmdir(FILES_PATH.$element->getId(), true);
			}
		}

		if ($fileFields != null) {
			//the control of the write possibility on those files are done in the API.
			//so if there only one with no write right, they will be an exception
			foreach ($fileFields as $fieldXml) {
				$fieldName = $fieldXml->getName();
				//eput("\n".$fieldName);
				foreach ($elementPAList->getListIterator() as $elementP) {
					$element = $elementP->getElement();
					//check if the elementP is really deleted, if not do nothing
					if ($remainingElements != null && $remainingElements[$element->getId()] != null)
						continue;
					//eput("\ntodo: delete ".FILES_PATH.$element->getFieldValue($fieldName, "path"));
					//for any file field we try to do the delete, no check on parameters
					//delete any CLIENT_WEB_PATH."imageForHtmlEditor/*.*" which are not in the new text

					if($fieldXml["type"] == "Files"){
						//delete possible integrated files in online form
						$match = array();
						preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $element->getFieldValue($fieldName, "textContent"), $match);
						if($match && $match[0]) foreach($match[0] as $tempPath) @unlink($tempPath);

						if (isImage($element->getFieldValue($fieldName, "mime")) && (!@ unlink(FILES_PATH . "tn_" . $element->getFieldValue($fieldName, "path"))))
							$this->executionSink()->log("Unable to delete the thumbnail:" . FILES_PATH . "tn_" . $element->getFieldValue($fieldName, "path"));
						if (!@ unlink(FILES_PATH . $element->getFieldValue($fieldName, "path")))
							$this->executionSink()->log("Unable to delete the upload file:" . FILES_PATH . $element->getFieldValue($fieldName, "path"));

					} else if ($fieldXml["type"] == "Blobs"){
						$match = array();
						preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $element->getFieldValue($fieldName, "value"), $match);
						if($match && $match[0]) foreach($match[0] as $tempPath) @unlink($tempPath);

					} else if ($fieldXml["type"] == "Texts"){
						$match = array();
						$multiLanguageValues = $element->getFieldValue($fieldName);
						if($multiLanguageValues){
							foreach($multiLanguageValues as $key=>$val){
								$match[$key] = array();
								preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $val, $match[$key]);
							}
							foreach($match as $key=>$mat) if($mat && $mat[0]) foreach($mat[0] as $tempPath) @unlink($tempPath);
						}
					}
				}
			}
		}
	}

	protected function isNotificationNeededForElement($p, $exec){
		//if not throw a NotificationServiceException::NO_NOTIFICATION_NEEDED catched in doSpecificCheck
		$this->getWigiiExecutor()->getNotificationService()->isNotificationNeededForMultipleElement($p, $exec->getCrtModule(), "delete");
		return $this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Notification_enableMessage")!="0" || $this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Notification_enableMessageOnDelete")=="1";
	}

	protected function displayNotificationForm($p, $exec, $state){
		$notificationS = $this->getWigiiExecutor()->getNotificationService();
		$elS = ServiceProvider::getElementService();
		$element = $this->getRecord();
		$groupList = $this->getAllGroupsToNotifyOnElementsDelete($p, $exec, $this->getElementPAList());
		$notificationS->displayNotificationForm($p, "delete", "MultipleElement", $exec->getCrtModule(), null, $groupList, $this->getElementPAList());
		//hide all the record form
		$exec->addJsCode("$('#id".$notificationS->getNotificationMessageInputName()."').parent().nextAll(':not(.clear)').hide();");
	}

	protected function getDialogTitle($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		return $transS->t($p, "deleteElements");
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$this->getFormRenderer()->resetJsCodeAfterShow();
		$idAnswer = $exec->getIdAnswer();
		if(!$idAnswer) $idAnswer = "mainDiv";
		if($idAnswer=="mainDiv") $this->setIsDialog(false);

		$elementId = $this->getRecord()->getId();
		$state = $this->getState();

		$this->getTrm()->setState($this->getState());

		$this->renderBeforeForm($p, $exec, $state);

		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), ($idAnswer=="mainDiv" ? false : $this->isDialog()));

		$this->renderInForm($p, $exec, $state);

		?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/32x32/status/not-known.png";?>" style="float:left;margin:5px 15px 15px 15px;" /><?

		echo '<span style="float:left;margin:15px 15px 15px 0px;">'. $transS->t($p, "areYouSureDeleteElements")."</span><br /><br />";

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog(), $transS->t($p, "cancel"));

		$this->openDialog($p, $exec, $state);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

	}

}



