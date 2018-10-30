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
 * Created on 27/07/2012
 * by LWR
 */
class ModuleEditorRemoveNamespaceFormExecutor extends FormExecutor {
	
	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor); //important to be in the begining because other setter could use the configurationContext as configService
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}
	
	protected function doSpecificCheck($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$userAS = ServiceProvider::getUserAdminService();
		$wNAS = ServiceProvider::getWigiiNamespaceAdminService();
		$rec = $this->getRecord();
		
		$wigiiNamespaceName = $rec->getFieldValue("moduleEditorRemoveNamespaceName");
		$wigiiNamespace = $wNAS->getWigiiNamespace($p, $wigiiNamespaceName);
		
		//check that the namespace is existing
		if(!$userAS->doesWigiiNamespaceExist($p, $wigiiNamespace)){
			$this->addErrorToField($transS->h($p, "wigiiNamespaceIsNotExisting"), "moduleEditorRemoveNamespaceName");
		}
		
	}
	
	protected function actOnCheckedRecord($p, $exec) {
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$userAS = ServiceProvider::getUserAdminService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$wNAS = ServiceProvider::getWigiiNamespaceAdminService();
		$rec = $this->getRecord();
		
		$wigiiNamespaceName = $rec->getFieldValue("moduleEditorRemoveNamespaceName");
		$wigiiNamespace = $wNAS->getWigiiNamespace($p, $wigiiNamespaceName);
		$moduleAccess = $p->getModuleAccess();
		
		if($rec->getFieldValue("moduleEditorRemoveNamespaceConfirmation")=="moduleEditorRemoveNamespaceDiscard"){
			//important to clear the cancel stack
			$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");
			$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationCancelled"), "", "info");			
			return;
		}
		//remove any users/roles
		$userAS->deleteAllUserRolesFromWigiiNamespace($p, $wigiiNamespace);
		//remove any folders
		$groupAS->deleteAllGroupsFromWigiiNamespace($p, $wigiiNamespace);
		
		//remove any config file defined for the config
		//this will miss any group config file...
		foreach($moduleAccess as $module){
			$namespaceConfigFile = $configS->getModuleConfigFilename($p, $module, $wigiiNamespace);
			@unlink($namespaceConfigFile);
		}
		
		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");
		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");
	}
	
	
	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext();
		$rec = $this->getRecord();
		
		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());
		
		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());
		
		if($this->getState() == "start"){
			
		}
		$this->getTrm()->displayRemainingForms();
		
		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());
		
		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, $rec->getActivity()->getActivityName()),
			$transS->t($p, "ok"), $transS->t($p, "cancel"));
		
		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
		
		$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());
		
		$exec->addJsCode("$('#moduleEditorRemoveNamespace_form__freeText').addClass('R');");
	}
}



