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

/**
 * Created by CWE on March 13th 2014
 *
 */
class AddSubElementFormExecutor extends AddElementFormExecutor {
	private $masterElementId;
	private $linkName;

	public function setMasterElementId($masterElementId) {
		$this->masterElementId = $masterElementId;
	}
	protected function getMasterElementId() {
		return $this->masterElementId;
	}
	public function setLinkName($linkName) {
		$this->linkName = $linkName;
	}
	protected function getLinkName() {
		return $this->linkName;
	}

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl, $masterElementId, $linkName){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setMasterElementId($masterElementId);
		$fe->setLinkName($linkName);
		return $fe;
	}
	public function getGroupInWhichToAdd($p, $exec){
		$groupPList = GroupPListArrayImpl::createInstance();
		ServiceProvider::getElementService()->getAllGroupsContainingElement($p,
			Element::createInstance($exec->getCrtModule(),null,null,array('id' => $this->getMasterElementId())),
			$groupPList);
		return $groupPList;
	}
	protected function renderBeforeForm($p, $exec, $state){
		$transS = ServiceProvider::getTranslationService();
		if($this->getState() == "start"){
			$exec->cacheAnswer($p, $exec->getIdAnswer(), "addElement", "subelement/add/".$this->getMasterElementId()."/".$this->getLinkName());
		}
	}

	protected function getWigiiHelpExpression($p,$exec) {
		$config = $this->getWigiiExecutor()->getConfigurationContext();
		$helpExp = (string)$config->getParameter($p, $this->getRecord()->getModule(), "WigiiHelp_onAdd");
		return $helpExp;
	}
	
	public function initializeDefaultValues($p, $exec) {
		parent::initializeDefaultValues($p, $exec);
		// recenters configuration on sub element if needed
		$config = $this->getWigiiExecutor()->getConfigurationContext();
		if($config->getCurrentMasterElementId() != $this->getMasterElementId() ||
			$config->getCurrentFieldName() != $this->getLinkName()) {
			$config->selectSubElementsConfig($p, $this->getMasterElementId(), $this->getLinkName());
		}
	}

	protected function insertElementOnAutosave($p, $exec, $element) {
		ServiceProvider::getElementService()->insertSubElement($p, $this->getMasterElementId(), $this->getLinkName(), $element);
	}

	protected function actOnCheckedRecord($p, $exec) {
		$elS = ServiceProvider::getElementService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$transS = ServiceProvider::getTranslationService();
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
		
		$elS->insertSubElement($p, $this->getMasterElementId(), $this->getLinkName(), $this->getRecord(), $fslForUpdate);
		$this->updateFilesOnDisk($p, $exec, $storeFileInWigiiBag, null, true);

		if($this->getState()=="persistAndSkipNotify"){
			$this->getWigiiExecutor()->getNotificationService()->skipNextNotification();
		}
		$this->getWigiiExecutor()->throwEvent()->insertElement(PWithElementWithGroupPList::createInstanceForSubElement($p, $this->getRecord(),
				$configS->getCurrentLinkSelector(), $configS->getCurrentSubElementPathFromRoot(), $configS->getGroupPList($p, $exec->getCrtModule())->count()==1 ? $configS->getGroupPList($p, $exec->getCrtModule()) : null));

		$elS->unlock($p, $this->getRecord());

		$this->endActOnCheckedRecord($p, $exec);
		// continues interrupted navigation if any.
		$exec->addJsCode("if(checkOpenItemTemp_url!=null){update(checkOpenItemTemp_url, true); checkOpenItemTemp_url=null;}");
	}

	protected function getCancelJsCode($p, $exec, $state){
		// reloads parent
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$parentModuleUrl = ServiceProvider::getModuleAdminService()->getModule($p, $configS->getCurrentSubElementPathFromRoot()->getLastLinkSelector()->getModuleName())->getModuleUrl();
		$parentEltReload = $exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$parentModuleUrl."/element/detail/".$this->getMasterElementId();
		if($exec->getIdAnswer()=="mainDiv"){
			//reload detail if in mainDiv, do not check open items
			$cancelJsCode = "update('mainDiv/".$parentEltReload."', true); ";
		} else {
			// reloads in element dialog
			$cancelJsCode = "update('elementDialog/".$parentEltReload."', true); ";
		}
		return $cancelJsCode;
	}

	protected function realoadAfterCheckedRecord($p, $exec){
		$rootElementId = $this->getWigiiExecutor()->getConfigurationContext()->getCurrentSubElementPathFromRoot()->getFirstLinkSelector()->getOwnerElementId();
		$exec->addJsCode("updateElementInList('" . $rootElementId . "');");
	}
}