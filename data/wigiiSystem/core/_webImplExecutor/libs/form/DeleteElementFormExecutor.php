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
 * Created on 18 sept. 13  by LWR
 * Modified by Medair (CWE) on 15.05.2017 : 
 * - to not delete shared images in CKEditor 
 * - condition the delete of elements based on a logical expression defined in Element_beforeDeleteExp
 */
class DeleteElementFormExecutor extends EditElementFormExecutor {

	private $_debugLogger;
	private $_executionSink;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("DeleteElementFormExecutor");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("DeleteElementFormExecutor");
		}
		return $this->_executionSink;
	}

	//element to delete
	private $elementPToDelete;
	public function setElementPToDelete($var){
		$this->elementPToDelete = $var;
		if($var && $var->getElement()->isSubElement()){
			ServiceProvider::getTranslationService()->setSubExecutionModule($var->getElement()->getModule());
		}
	}
	protected function getElementPToDelete(){ return $this->elementPToDelete; }

	private $record;
	public function getRecord() {
		return $this->record;
	}

	//parent::setRecord link record to TRM. In this implementation we don't want that as
	//the TRM will display the elementDeleteRec
	public function setRecord($record) {
		if(!$this->getTrm()){
			$this->setTrm($this->createTrmInstance());
		}
		$this->getTrm()->reset($record);
		$this->record = $record;
	}

	//fileFields contains the fields with type="Files"
	private $fileFields;
	public function setFileFields($fileFields){ $this->fileFields = $fileFields; }
	protected function getFileFields(){ return $this->fileFields; }

	//record is an elementDelete activity record
	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	//parent::setState is good
	//protected function setState($state){ }

	//parent::goToNextState is good
	//protected function goToNextState(){}

	protected $beforeDeleteExpResult = null;
	
	protected function doSpecificCheck($p, $exec){
	    if(!$this->hasError() && $this->getState()=="check" && !($this instanceof DeleteMultipleElementFormExecutor)){
	        // checks if a deletion is conditioned by an Element_beforeDeleteExp
	        $this->beforeDeleteExpResult = null;
	        $configS = $this->getWigiiExecutor()->getConfigurationContext();
	        $module = ($this->getElementPToDelete()? $this->getElementPToDelete()->getElement()->getModule():$exec->getCrtModule());
	        $beforeDeleteExp = (string)$configS->getParameter($p, $module, "Element_beforeDeleteExp");
	        $beforeDeleteExp = $this->evaluateBeforeDeleteExp($p, $exec, $beforeDeleteExp);
	        if(!$beforeDeleteExp->okToDelete) {
	            $this->beforeDeleteExpResult = $beforeDeleteExp;
	            $this->addStateError();
	        }	        
	    }
	    // calls default checks
	    parent::doSpecificCheck($p, $exec);
	}

	/**
	 * Evaluates the Element_beforeDeleteExp and returns an object authorizing or not the deletion and an optional error message.
	 * @param Principal $p current principal running the deletion process
	 * @param ExecutionService $exec current ExecutionService instance
	 * @param String $beforeDeleteExp beforeDeleteExp FuncExp or 0 or 1.
	 * @throws Exception in case of error
	 * @return StdClass of the form {okToDelete: Boolean, message: String}
	 */
	protected function evaluateBeforeDeleteExp($p,$exec,$beforeDeleteExp) {
	    $transS = ServiceProvider::getTranslationService();
	    $returnValue = null;
	    
	    // null expression always resolves to true
	    if($beforeDeleteExp == null) $returnValue = true;
	    // else converts 0 to false
	    elseif($beforeDeleteExp === '0') $returnValue = false;
	    // and 1 to true
	    elseif($beforeDeleteExp === '1') $returnValue = true;
	    // else should be a FuncExp.
	    else {
	        // if the FuncExp has a syntax error or fails to execute, then deletion is blocked and exception message is added to standard message.
	        try {
	            $beforeDeleteExp = str2fx($beforeDeleteExp);
	            $returnValue = $this->evaluateFuncExp($p, $exec, $beforeDeleteExp, $this->getElementPToDelete()->getDbEntity());
	        }
	        catch(Exception $e) {
	            if($e instanceof ServiceException) $e = $e->getWigiiRootException();
	            $returnValue = (object)array('okToDelete'=>false,'message'=>$e->getMessage().$transS->t($p, "elementCannotBeDeletedEvaluationError"));
	        }
	    }
	    // returns the resolved expression
	    if(!($returnValue instanceof stdClass)) {
	        // if evaluates to true, then OK to delete.
	        if($returnValue) $returnValue = (object)array('okToDelete'=>true,'message'=>null);
	        // else KO to delete and adds a default message
	        else $returnValue = (object)array('okToDelete'=>false,'message'=>$transS->t($p, "elementCannotBeDeletedExplanation"));
	    }
	    // adds default message if explanation is missing
	    elseif(!$returnValue->okToDelete && !$returnValue->message) $returnValue->message = $transS->t($p, "elementCannotBeDeletedExplanation");
	    return $returnValue;
	}
	
	private $elementPAList; //a list with only one elementP item
	protected function getElementPAList(){
		if(!isset($this->elementPAList)){
			$this->elementPAList = ElementPAdvancedListArrayImpl::createInstance();
			$this->elementPAList->addElementP($this->getElementPToDelete());
		}
		return $this->elementPAList;
	}

	protected function getAllGroupsToNotifyOnElementDelete($p, $exec, $element){
		$elS = ServiceProvider::getElementService();
		$groupPList = GroupListAdvancedImpl :: createInstance(false);

		//get all group containing element
		if($element->isSubElement()) {
			$elS->getAllGroupsContainingElement($p,
					Element::createInstance($exec->getCrtModule(),null,null,
							array('id' => $this->getWigiiExecutor()->getConfigurationContext()
									->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()
									->getOwnerElementId())),
					$groupPList);
		}
		else $elS->getAllGroupsContainingElement($p, $element, $groupPList);

		//don't care about trashbin, this makes no difference for the notifications

		//remove any limitedWriteGroup
		if($element->getSys_creationUser() == $p->getRealUserId()) return $groupPList->getSGroups();
		//remove any unreadable group
		return $groupPList->getWriteGroups();
	}

	protected function actOnCheckedRecord($p, $exec) {
		$elS = ServiceProvider::getElementService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$transS = ServiceProvider::getTranslationService();
		$notificationS = $this->getWigiiExecutor()->getNotificationService();

		$elementP = $this->getElementPToDelete();
		$element = $elementP->getElement();
		$elementId = $element->getId();
		$fileFields = $this->getFileFields();

		// if principal deleting the Elements owns the Lock on the Element then first unlocks it to not keep any lock on a unshared or deleted element
		if($element->isState_locked() && $element->getSys_user() == $p->getRealUserId()) {
			$elS->setState_locked($p, $element->getId(), false);
		}
		
		if(!$element->isSubElement()) {
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

			$groupList = $this->getAllGroupsToNotifyOnElementDelete($p, $exec, $element);

			//remove autoSharing
			$gids = ValueListArrayMapper::createInstance(true, ValueListArrayMapper::Natural_Separators, true);
			$element->getLinkedIdGroupInRecord($p, $gids);
			$linkedGids = $gids->getListIterator();
			if($linkedGids == null) $linkedGids = array();
			$linkedGids = array_diff_key($linkedGids, $groupList->getIds());
			if($linkedGids){
				$notificationS->blockNotificationPostingValue();
				$elS->unshareElement($this->getRootPrincipal(), $elementId, $linkedGids);
				$gpl = GroupListArrayImpl::createInstance();
				ServiceProvider :: getGroupAdminService()->getGroupsWithoutDetail($p, $linkedGids, $gpl);
				foreach($gpl->getListIterator() as $group){
					//notification here do not follow the skipNotification as it is a sharing notification and not an update notification
					$this->getWigiiExecutor()->throwEvent()->unshareElement(PWithElementWithGroup :: createInstance($p, $elementP->getElement(), $group));
					$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $group->getId(), $group->getWigiiNamespace(), $group->getModule());
				}
				$notificationS->unblockNotificationPostingValue();
			}
		}
		else {
			$trashBinPrefix = (string)$configS->getParameter($p, null, "deletedSubElementsLinkNamePrefix");
			$moveInTrash = !empty($trashBinPrefix) && (strpos($element->getLinkName(), $trashBinPrefix)!==0);
			$groupList = ($configS->getGroupPList($p, $exec->getCrtModule())->count()==1 ? $configS->getGroupPList($p, $exec->getCrtModule()) : null);
		}

		if($moveInTrash){
			if($element->isSubElement()) {
				$elS->renameSubElementsLink($p, $elementId, $trashBinPrefix.$element->getLinkName());
			}
			else {
				$removeSharingIds = $groupList->getIds();
				unset($removeSharingIds[$trashBinGroupId]);
				$elS->shareElement($this->getRootPrincipal(), $elementId, $trashBinGroupId);
				$elS->updateSys_dateToNow($p, array($elementId=>$elementId));
				if($removeSharingIds) $elS->unshareElement($this->getRootPrincipal(), $elementId, $removeSharingIds, false);
			}
		} else {
			$elS->deleteElement($p, $elementId);
		}

		if (addslashes($_POST['action']) == "persistAndSkipNotify") {
			$notificationS->skipNextNotification();
		}
		if($element->isSubElement()) $this->getWigiiExecutor()->throwEvent()->deleteElement(PWithElementWithGroupPList :: createInstanceForSubElement($p, $element, $configS->getCurrentLinkSelector(), $configS->getCurrentSubElementPathFromRoot()), $groupList);
		else $this->getWigiiExecutor()->throwEvent()->deleteElement(PWithElementWithGroupPList :: createInstance($p, $element, $groupList));

		//check if delete has been really done or only unshare
		$remainingEl = $elS->doesElementExists($p, $elementId);

		$exec->addJsCode("actOnCloseDialog('" . $exec->getIdAnswer() . "');");

		// if subelement then reloads parent
		if($element->isSubElement()) {
			//$parentModuleUrl = ServiceProvider::getModuleAdminService()->getModule($p, $configS->getCurrentSubElementPathFromRoot()->getLastLinkSelector()->getModuleName())->getModuleUrl();
			$exec->addRequests("elementDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/detail/".$element->getElementParentId());
		}
		else {
			$expl = "";
			$title = $transS->t($p, "operationDoneSuccessfully");
			if($moveInTrash){
				$expl .= "";
			} else {
				if($remainingEl != null){
					$expl .= "";
				}
			}

			if($expl){
				$this->getWigiiExecutor()->openAsMessage($exec->getIdAnswer(), $this->getTotalWidth(), $title, $expl, null, $transS->t($p, "ok"), null, null, "done");
			} else {
				$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $title, "", "done");
			}
		}

		$this->getWigiiExecutor()->unLockEntity($p, $exec, null, "element", $element);

		//remove any history files even if not in config
		//if element continues to live in the DB, do not delete anything
		if ($remainingEl == null){
		    rrmdir(FILES_PATH.$element->getId(), true);
		}

		if ($fileFields != null && $remainingEl == null) {
			//the control of the write possibility on those files are done in the API.
			//so if there only one with no write right, they will be an exception
			foreach ($fileFields as $fieldXml) {
				$fieldName = $fieldXml->getName();
				//eput("\n".$fieldName);

				if($fieldXml["type"] == "Files"){
				    /* Medair (CWE) 05.05.2017: do not delete anymore images in CKEditor to avoid loosing any shared images into other elements
					$match = array();
					preg_match_all("(".CLIENT_WEB_PATH."imageForHtmlEditor/[^/]*\.[a-zA-Z]*)", $element->getFieldValue($fieldName, "textContent"), $match);
					if($match && $match[0]) foreach($match[0] as $tempPath) @unlink($tempPath);
                    */
				    
					//for any file field we try to do the delete, no check on parameters
					if (isImage($element->getFieldValue($fieldName, "mime")) && (!@ unlink(FILES_PATH . "tn_" . $element->getFieldValue($fieldName, "path"))))
						$this->executionSink()->log("Unable to delete the thumbnail:" . FILES_PATH . "tn_" . $element->getFieldValue($fieldName, "path"));
					if (!@ unlink(FILES_PATH . $element->getFieldValue($fieldName, "path")))
						$this->executionSink()->log("Unable to delete the upload file:" . FILES_PATH . $element->getFieldValue($fieldName, "path"));

				} 
				/* Medair (CWE) 05.05.2017: do not delete anymore images in CKEditor to avoid loosing any shared images into other elements
				else if ($fieldXml["type"] == "Blobs"){
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
				*/
			}
		}

		if($exec->getCrtParameters(2)=="mainDiv"){
			$exec->addJsCode("window.close();");
		} else {
			if($exec->getIdAnswer() != $exec->getCrtParameters(2)){
				$exec->addJsCode("$('#" . $exec->getCrtParameters(2) . "').parents('.ui-dialog').find('.ui-dialog-titlebar-close').click();");
			}

			if(!$element->isSubElement()) {
				//remove from list
				$exec->addJsCode("removeElementInList('" . $elementId . "');");
				$exec->addJsCode("if(isWorkzoneViewDocked()) manageWorkzoneViewDocked('clear');");
				//refetch events if calendar view:
				$exec->addJsCode("if($('#moduleView .calendar').length){ $('#moduleView .calendar').fullCalendar('refetchEvents');}");

				$exec->invalidCache($p, 'elementDialog', "selectElementDetail", "element/detail/" . $elementId);
				//invalid cache of containing groups
				foreach($groupList->getListIterator() as $groupP){
					$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $groupP->getId());
				}
				//invalid trashBin cache
				if($moveInTrash){
					$exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $trashBinGroupId);
				}
			}
			//invalid element details
			$exec->invalidCache($p, 'elementDialog');
			//$exec->addRequests("moduleView/" . $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl() . "/" . $exec->getCrtModule()->getModuleUrl() . "/display/moduleView/");
		}

	}

	protected function getUnlockUrl($p, $exec){
	    return $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/element/".$this->getElementPToDelete()->getDbEntity()->getId();
	}
	protected function getCancelJsCode($p, $exec, $state){
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		if($exec->getIdAnswer()=="mainDiv"){
			//reload detail if in mainDiv, do not check open items
		    $cancelJsCode = "update('".$exec->getIdAnswer()."/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/detail/".$this->getElementPToDelete()->getDbEntity()->getId()."', true); ";
		} else {
			$cancelJsCode = "update('NoAnswer/".$this->getUnlockUrl($p, $exec)."'); ";
		}
		return $cancelJsCode;
	}
	
	protected function isNotificationNeededForElement($p, $exec){
		//if not throw a NotificationServiceException::NO_NOTIFICATION_NEEDED catched in doSpecificCheck
		$this->getWigiiExecutor()->getNotificationService()->isNotificationNeededForElement($p, $this->getElementPToDelete()->getElement(), "delete");
		return $this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Notification_enableMessage")!="0" || $this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "Notification_enableMessageOnDelete")=="1";
	}

	protected function displayNotificationForm($p, $exec, $state){
		$notificationS = $this->getWigiiExecutor()->getNotificationService();
		$elS = ServiceProvider::getElementService();
		$element = $this->getElementPToDelete()->getElement();
		$groupList = $this->getAllGroupsToNotifyOnElementDelete($p, $exec, $element);
		$notificationS->displayNotificationForm($p, "delete", "Element", $exec->getCrtModule(), $element, $groupList);
		//hide all the record form
		$exec->addJsCode("$('#id".$notificationS->getNotificationMessageInputName()."').parent().nextAll(':not(.clear)').hide();");
	}

	//parent::renderInForm is good
	//protected function renderInForm($p, $exec, $state){ }

	//parent::getUnlockUrl is good
	//protected function getUnlockUrl($p, $exec){ }

	//parent::getCancelJsCode is good
	//protected function getCancelJsCode($p, $exec, $state){ }

	protected function getDialogTitle($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		return $transS->t($p, "deleteElement");
	}
	//parent::openDialog is good
	//protected function openDialog($p, $exec, $state){ }

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();

		$this->getFormRenderer()->resetJsCodeAfterShow();
		$idAnswer = $exec->getIdAnswer();
		if(!$idAnswer) $idAnswer = "mainDiv";
		if($idAnswer=="mainDiv") $this->setIsDialog(false);

		$elementId = $this->getElementPToDelete()->getId();
		$state = $this->getState();

		$this->getTrm()->setState($this->getState());

		$this->renderBeforeForm($p, $exec, $state);

		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), ($idAnswer=="mainDiv" ? false : $this->isDialog()));

		$this->renderInForm($p, $exec, $state);

		//add element to the current selected element in list context
		$this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList")->setCrtSelectedItem($elementId);
		
		// Medair (CWE) 11.05.2017: displays error message coming from Element_beforeDeleteExp if defined
		if($this->hasError() && $this->beforeDeleteExpResult && !$this->beforeDeleteExpResult->okToDelete) {
		  echo '<div style="margin-top:15px;padding-left:45px;position:relative;">';
		  ?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/32x32/emblems/emblem-unreadable.png";?>" style="left:0;position:absolute;margin-right:15px;margin-bottom:15px;" /><?		  
		  echo '<div><b>'.$transS->t($p, "elementCannotBeDeleted").':</b><br/><br />';
		  echo $this->beforeDeleteExpResult->message."</div></div><br /><br />";
		}
		else {
		  ?><img src="<?=SITE_ROOT_forFileUrl."images/icones/tango/32x32/status/not-known.png";?>" style="float:left;margin:5px 15px 15px 15px;" /><?
		  echo '<span style="float:left;margin:15px 15px 15px 0px;">'. $transS->t($p, "areYouSureDeleteElement")."</span><br /><br />";
		}
		
		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog(), $transS->t($p, "cancel"));

		$this->openDialog($p, $exec, $state);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

	}
}



