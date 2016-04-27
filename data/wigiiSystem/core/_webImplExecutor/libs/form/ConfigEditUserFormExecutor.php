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
 * Created on 15 april 2010
 * by LWR
 */
class ConfigEditUserFormExecutor extends FormExecutor {

	private $userP;
	protected function setUserP($userP){ $this->userP = $userP; }
	protected function getUserP(){ return $this->userP; }

	private $actOnCheckedRecordRequest;
	protected function setActOnCheckedRecordRequest($request){ $this->actOnCheckedRecordRequest = $request; }
	protected function getActOnCheckedRecordRequest(){ return $this->actOnCheckedRecordRequest; }

	private $module;
	protected function setModule($module){ $this->module = $module; }
	protected function getModule(){ return $this->module; }

	public static function createInstance($wigiiExecutor, $module, $userP, $record, $formId, $submitUrl, $actOnCheckedRecordRequest=""){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setUserP($userP);
		$fe->setModule($module);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setActOnCheckedRecordRequest($actOnCheckedRecordRequest);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){

		$transS = ServiceProvider::getTranslationService();
		$rec = $this->getRecord();
		$user = $this->getUserP()->getUser();

		if(!$rec->getFieldValue("deleteConfigFile") && $rec->getFieldValue("userConfigFile")==null){
			$this->addErrorToField($transS->t($p, "compulsory_field"), "userConfigFile");
		}

		if($rec->getFieldValue("userConfigFile")){
			libxml_use_internal_errors(true);
			libxml_clear_errors();
			$errors = array();
			simplexml_load_string(stripslashes($rec->getFieldValue("userConfigFile")));
			$errors = libxml_get_errors();
			libxml_use_internal_errors(false);
			if($errors){
				$errorsToDisplay = "";
				foreach($errors as $key=>$error){
		         	$errorsToDisplay .="<br />Line ".($error->line-1).": ".$error->message;
				}
				$this->addErrorToField($transS->h($p, "invalidXml").$errorsToDisplay, "userConfigFile");
			}
		}
	}

	protected function actOnCheckedRecord($p, $exec) {

		$userAS = ServiceProvider::getUserAdminService();
		$config = $this->getWigiiExecutor()->getConfigurationContext();
		$transS = ServiceProvider::getTranslationService();

		$rec = $this->getRecord();
		$user = $this->getUserP()->getUser();

		if(	$this->getUserP()->getRights()==null ||
			!$p->isUserCreator()
			){
			throw new ServiceException('editing user config need ownership on user + userCreator', ServiceException::FORBIDDEN);
		}

		$configfile = $config->getUserConfigFilename($p, $this->getModule(), $user);
		if($rec->getFieldValue("deleteConfigFile")){
			@unlink($configfile);
		} else {
			file_put_contents($configfile, stripslashes($rec->getFieldValue("userConfigFile")));
		}

		$userAS->unLock($p, $user);

		$this->getWigiiExecutor()->throwEvent()->updateUser(PWithUser::createInstance($p, $user));

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully"), "", "done");

		if($this->getActOnCheckedRecordRequest()!=null){
			$exec->addRequests($this->getActOnCheckedRecordRequest());
		}
	}


	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$config = $this->getWigiiExecutor()->getConfigurationContext();
		$rec = $this->getRecord();
		$user = $this->getUserP()->getUser();

//		$rec->getWigiiBag()->setHelp($transS->h($p, "authenticationServerHelp"), "authenticationServer");

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		if($this->getState() == "start"){
			$configfile = $config->getUserConfigFilename($p, $this->getModule(), $user);
			if(file_exists($configfile)){
				$rec->setFieldValue(addslashes(file_get_contents($configfile)), "userConfigFile");
			} else {
				//load a default config template
				$xml = '<?xml version="1.0" standalone="yes"?>
<'.$this->getModule()->getModuleName().'>
	<adds>
		<!-- <item node="parameters[\'elementTotalWidth\']" value="300" /> -->
	</adds>

	<deletes>
		<!-- <item node="selectSearchBar->fields->c1" /> -->
	</deletes>
</'.$this->getModule()->getModuleName().'>';
				$rec->setFieldValue($xml, "userConfigFile");
			}
		}
		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$cancelJsCode = "" .
			"actOnCloseDialog('".$exec->getIdAnswer()."');" .
			"update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/user/".$user->getId()."');" .
			"";
		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, $rec->getActivity()->getActivityName()),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode, '{ my : "top", at: "top" }', null, false, true);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());

		$this->getTrm()->addJsCode("" .
				"function getLineNumber(textarea, indicator) {" .
					"$(indicator).html($(textarea).val().substr(0, $(textarea).get()[0].selectionStart).split('\\n').length);" .
				"} " .
				"$('#".$this->getFormId()."_userConfigFile_value_textarea').after('line: <span id=\"".$this->getFormId()."_userConfigFile_value_textarea_lineNumber\">1</span>');" .
				"$('#".$this->getFormId()."_userConfigFile_value_textarea').keyup(function(){ getLineNumber($(this), $('#".$this->getFormId()."_userConfigFile_value_textarea_lineNumber')); }).mouseup(function(){ $(this).keyup(); });");

		//the tabby plugin is already called as the config editor has the parameter noWrap=1
	}
}



