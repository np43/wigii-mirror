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
 * Created on 23.04.2015 by CWE
 * Modified by Medair (ACA,CWE) on 16.05.2017 to not authorize action if enableDeleteOnlyForAdmin and not admin or if conditional deletion is active (Element_beforeDeleteExp is defined).
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

	protected function doSpecificCheck($p, $exec){
		//no special check is required
	}

	protected function actOnCheckedRecord($p, $exec) {

		$groupAS = ServiceProvider::getGroupAdminService();
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		
		$groupEditRec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();
		$parentId = $group->getGroupParentId();
		$workingModule = $group->getModule();
		$workingModuleName = $workingModule->getModuleName();
				
		// Empty group is not authorized if user has no admin right and enableDeleteOnlyForAdmin=1
		if(!($this->getGroupP()->getRights() && $this->getGroupP()->getRights()->canModify()) 
				&& $configS->getGroupParameter($p, $group, 'enableDeleteOnlyForAdmin')=='1') throw new AuthorizationServiceException("cannot delete elements in non admin groups.", AuthorizationServiceException::FORBIDDEN);
		// Empty group is not authorized if Element_beforeDeleteExp is defined in configuration
		$beforeDeleteExp = (string)$configS->getGroupParameter($p, $group, 'Element_beforeDeleteExp');
		if($beforeDeleteExp!=null && $beforeDeleteExp!=='1') throw new AuthorizationServiceException("cannot delete elements if Element_beforeDeleteExp is defined in configuration.", AuthorizationServiceException::FORBIDDEN);
		// if trashbin exists and user doesn't want to keep content, then moves group to trashbin
		$trashBinGroupId = (string)$configS->getParameter($p, $group->getModule(), "trashBinGroup");
		$groupAS->deleteGroupContent($this->getRootPrincipal(), $p, $group->getId(), $trashBinGroupId);
		
		$exec->addJsCode("actOnCloseDialog('" . $exec->getIdAnswer() . "');");
		$exec->addJsCode('
			invalidCache("moduleView");
			invalidCache("groupPanel");
			update("moduleView/" + crtWigiiNamespaceUrl + "/" + crtModuleName + "/display/moduleView");
		');
		
		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
		$this->getWigiiExecutor()->throwEvent()->deleteGroup(PWithGroup :: createInstance($p, $group));
			
		if($this->getActOnCheckedRecordRequest()!=null){
			$exec->addRequests($this->getActOnCheckedRecordRequest());
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



