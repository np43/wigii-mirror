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
 * Created on 15 sept. 09 by LWR
 * Modified by CWE on 19.08.2014 to add default values expression evaluation.
 */
class AddElementFormExecutor extends EditElementFormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	private $_debugLogger;
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("AddElementFormExecutor");
		}
		return $this->_debugLogger;
	}

	protected function getGroupIdInWhichToAdd($p, $exec){
		return $exec->getCrtParameters(1);
	}
	/**
	 * @return int current selected groupId or null if several groups or no groups are selected
	 */
	protected function getCurrentSelectedGroup($p,$exec) {
		$elementListContext = $this->getListContext($p, $exec->getCrtWigiiNamespace(), $exec->getCrtModule(), "elementList");
		if (!is_null($elementListContext->getGroupPList()) && $elementListContext->getGroupPList()->count() == 1) {
			$ids = $elementListContext->getGroupPList()->getIds();
			return reset($ids);
		}
	}
	/**
	 * @return GroupListAdvancedImpl containing the group in which to add the element
	 */
	public function getGroupInWhichToAdd($p, $exec){
		$groupAS = ServiceProvider::getGroupAdminService();
		$groupPList = GroupListAdvancedImpl::createInstance();
		$groupAS->getGroupsWithoutDetail($p, array($this->getGroupIdInWhichToAdd($p, $exec)=>$this->getGroupIdInWhichToAdd($p, $exec)), $groupPList);
		return $groupPList;
	}
	protected function renderBeforeForm($p, $exec, $state){
		if($this->getState() == "start"){
			$exec->cacheAnswer($p, $exec->getIdAnswer(), "addElement", "element/add/".$this->getGroupIdInWhichToAdd($p, $exec));
		}
	}
	protected function renderInForm($p, $exec, $state){
		$element = $this->getRecord();

		if($this->getState()=="addMessageToNotification"){
			//display the notification preview with the message input box
			$notificationS = $this->getWigiiExecutor()->getNotificationService();
			$groupPList = $this->getGroupInWhichToAdd($p, $exec);
			$notificationS->displayNotificationForm($p, "insert", "Element", $exec->getCrtModule(), $element, $groupPList);
			//hide all the record form
			$exec->addJsCode("$('#id".$notificationS->getNotificationMessageInputName()."').parent().nextAll(':not(.clear)').hide();");
		}

	}

	protected function getCancelJsCode($p, $exec, $state){
		$cancelJsCode = null;
		return $cancelJsCode;
	}

	protected function getDialogTitle($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		return $transS->t($p, "addElement");
	}
	
	protected function getWigiiHelpExpression($p,$exec) {
		$config = $this->getWigiiExecutor()->getConfigurationContext();		
		$helpExp = (string)$config->getParameter($p, $exec->getCrtModule(), "WigiiHelp_onAdd");
		return $helpExp;
	}

	protected function actOnCheckedRecord($p, $exec) {
		$elS = ServiceProvider::getElementService();
		$transS = ServiceProvider::getTranslationService();

		//the element can be added as well in other groups if defined in an attribut idGroup
		$groupPList = $this->getGroupInWhichToAdd($p, $exec);
		$isKey = $this->getWigiiExecutor()->doesCrtModuleHasIsKeyField($p, $exec->getCrtModule());

		if($isKey){
			$subFieldname = $this->getWigiiExecutor()->getSubFieldnameForIsKeyField($isKey);
			//check if an item already exist with this value:
			$elPl = ElementIdsListWebImpl::createInstance();
			$lf = ListFilter::createInstance();
			$fsl = FieldSelectorListArrayImpl:: createInstance();
			$fsl->addFieldSelector($isKey->getName(), $subFieldname);
			$lf->setFieldSelectorList($fsl);
			$lf->setFieldSelectorLogExp(LogExp :: createEqualExp(FieldSelector :: createInstance($isKey->getName(), $subFieldname), $this->getRecord()->getFieldValue($isKey->getName(), $subFieldname)));
			$elS->getAllElementsInGroup($p, $groupPList->getItemInList($this->getGroupIdInWhichToAdd($p, $exec)), $elPl, ($isKey["isUniqueInGroup"]=="1"?false:true), $lf);
			if($elPl->count()>0){				
				//if the isKey field was found, then update the item
				if($isKey["isKey"]=='1') {
					$this->getRecord()->setId(key($elPl->getListIterator()));
					parent::actOnCheckedRecord($p, $exec);
				}
				// else isUnique=1 or isUniqueInGroup=1, then displays a duplicate error on the unique field
				else {
					$this->addErrorToField($transS->h($p, "isUniqueError"), $isKey->getName());
					$this->setState("check");
				}
				return;
			}
		}

		// CWE 20.06.2019: refactored logic in FormExecutor::doInsertElement method
		$this->doInsertElement($p, $exec);
		/*
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$groupAS = ServiceProvider :: getGroupAdminService();
		$storeFileInWigiiBag = $configS->getParameter($p, null, "storeFileContentIntoDatabase") == "1";
		
		$newFileFieldSelectorList = $this->updateHiddenFields($p, $exec, $storeFileInWigiiBag, null);
		//we need to do this to handle the doNotPersist case (even when dynamics)
		$fsl = FieldSelectorListArrayImpl::createInstance(false);
		foreach($this->getRecord()->getFieldList()->getListIterator() as $field){
			if($field->getDataType()!=null){
				$fsl->addFieldSelector($field->getFieldName());
			}
		}
		if($newFileFieldSelectorList != null){
			$fsl->mergeFieldSelectorList($newFileFieldSelectorList);
		}
		//remove any doNotPersist fields
		foreach($fsl->getListIterator() as $fs){
			$fieldXml = $this->getRecord()->getFieldList()->getField($fs->getFieldName())->getXml();
			if($fieldXml["doNotPersist"]=="1"){
				$fsl->removesField($fs->getFieldName());
			}
		}

		// merges policy evaluator field selector list
		if(isset($this->fieldSelectorListFromPolicyEvaluator)) {
			$fslForUpdate = FieldSelectorListArrayImpl::createInstance(false);
			$fslForUpdate->mergeFieldSelectorList($fsl);
			$fslForUpdate->mergeFieldSelectorList($this->fieldSelectorListFromPolicyEvaluator);
		}
		else $fslForUpdate = $fsl;
		
		$moveId = $this->getRecord()->getMoveGroupInRecord();
		if($moveId) {
			$moveId = $this->getWigiiExecutor()->evaluateConfigParameter($p, $exec, $moveId, $this->getRecord());
			$moveId = explode(";", $moveId); //if the moveId contains multiple groups then move in multiple
			if(is_array($moveId)) $moveId = array_combine($moveId, $moveId);
		}
		
		
		if($moveId){
			$elS->insertElement($p, $this->getRecord(), reset($moveId), $fslForUpdate);
		}else{
			$elS->insertElement($p, $this->getRecord(), $this->getGroupIdInWhichToAdd($p, $exec), $fslForUpdate);
		}
		$this->updateFilesOnDisk($p, $exec, $storeFileInWigiiBag, null, true);

		if($this->getState()=="persistAndSkipNotify"){
			$this->getWigiiExecutor()->getNotificationService()->skipNextNotification();
		}
		$this->getWigiiExecutor()->throwEvent()->insertElement(PWithElementWithGroupPList::createInstance($p, $this->getRecord(), $groupPList));

		//authosharing is done only if no moveId defined
		//autosharing defined in configuration are done on groups that could be not writable
		//lookup if any selected attribut idGroup
        if(!$moveId) {
            $gids = ValueListArrayMapper::createInstance(true, ValueListArrayMapper::Natural_Separators, true);
            $this->getRecord()->getLinkedIdGroupInRecord($p, $gids);
            if($gids && !$gids->isEmpty()){
                $elS->shareElement($this->getRootPrincipal(), $this->getRecord()->getId(), $gids->getListIterator());
                $gpl = GroupListArrayImpl::createInstance();
                $groupAS->getGroupsWithoutDetail($p, $gids->getListIterator(), $gpl);
                $this->getWigiiExecutor()->getNotificationService()->blockNotificationPostingValue();
                foreach($gpl->getListIterator() as $group){
                    //notification here do not follow the skipNotification as it is a sharing notification and not an update notification
                    $this->getWigiiExecutor()->throwEvent()->shareElement(PWithElementWithGroup :: createInstance($p, $this->getRecord(), $group));
                }
                $this->getWigiiExecutor()->getNotificationService()->unblockNotificationPostingValue();
            }
        } else if(count($moveId)>1) {
        	array_shift($moveId); //remove first group as already inserted in that group
        	//in case multiple result in moveId then add sharing
        	$elS->shareElement($this->getRootPrincipal(), $this->getRecord()->getId(), $moveId);
        	$gpl = GroupListArrayImpl::createInstance();
        	$groupAS->getGroupsWithoutDetail($p, $moveId, $gpl);
        	$this->getWigiiExecutor()->getNotificationService()->blockNotificationPostingValue();
        	foreach($gpl->getListIterator() as $group){
        		//notification here do not follow the skipNotification as it is a sharing notification and not an update notification
        		$this->getWigiiExecutor()->throwEvent()->shareElement(PWithElementWithGroup :: createInstance($p, $this->getRecord(), $group));
        	}
        	$this->getWigiiExecutor()->getNotificationService()->unblockNotificationPostingValue();
        }
        */
		
		$elS->unlock($p, $this->getRecord());

		$this->endActOnCheckedRecord($p, $exec);
		// continues interrupted navigation if any.
		$exec->addJsCode("if(checkOpenItemTemp_url!=null){update(checkOpenItemTemp_url, true); checkOpenItemTemp_url=null;}");
	}

	protected function reloadAfterCheckedRecord($p, $exec){
		if(!$this->getIgnoreListRefresh()) $exec->addJsCode("addElementInList('" . $this->getRecord()->getId() . "');");
		//invalid the cache of the list
		$ids = $this->getCurrentSelectedGroup($p,$exec);
		if($ids) $exec->invalidCache($p, 'moduleView', 'groupSelectorPanel', "groupSelectorPanel/selectGroup/" . $ids);		
	}

	public function CheckForm($p, $exec) {
		$isKey = $this->getWigiiExecutor()->doesCrtModuleHasIsKeyField($p, $this->getRecord()->getModule());
		if($isKey) $this->getRecord()->getWigiiBag()->setDisabled(false, $isKey->getName());
		return parent::CheckForm($p, $exec);
	}
	protected function doRenderForm($p, $exec){
		$isKey = $this->getWigiiExecutor()->doesCrtModuleHasIsKeyField($p, $this->getRecord()->getModule());
		if($isKey) $this->getRecord()->getWigiiBag()->setDisabled(false, $isKey->getName());
		if($this->getState() == 'start') {
			// if autosave, then inserts a new element (with default values) and then loads edit request.
			if($this->getWigiiExecutor()->getConfigurationContext()->getParameter($p, $exec->getCrtModule(), "autoSave") == "1") {
				$this->insertElementOnAutosave($p, $exec, $this->getRecord());
				$exec->addRequests($exec->getIdAnswer()."/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/element/edit/".$this->getRecord()->getId()."/autoadd");
				$this->addAutoSaveJSCode($p, $exec);
				$this->reloadAfterCheckedRecord($p, $exec);
				return;
			}
		}
		return parent::doRenderForm($p, $exec);
	}
	protected function insertElementOnAutosave($p, $exec, $element) {
		ServiceProvider::getElementService()->insertElement($p, $element, $this->getGroupIdInWhichToAdd($p, $exec));
	}
}



