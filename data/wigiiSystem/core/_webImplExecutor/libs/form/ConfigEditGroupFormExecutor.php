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
class ConfigEditGroupFormExecutor extends FormExecutor {

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

	protected function doSpecificCheck($p, $exec){

		$transS = ServiceProvider::getTranslationService();
		$rec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();

		if($rec->getFieldValue("groupConfigFile")){
			libxml_use_internal_errors(true);
			libxml_clear_errors();
			$errors = array();
			simplexml_load_string(stripslashes($rec->getFieldValue("groupConfigFile")));
			$errors = libxml_get_errors();
			libxml_use_internal_errors(false);
			if($errors){
				$errorsToDisplay = "";
				foreach($errors as $key=>$error){
		         	$errorsToDisplay .="<br />Line ".($error->line-1).": ".$error->message;
				}
				$this->addErrorToField($transS->h($p, "invalidXml").$errorsToDisplay, "groupConfigFile");
			}
		}

	}

	protected function actOnCheckedRecord($p, $exec) {

		$groupAS = ServiceProvider::getGroupAdminService();
		$config = $this->getWigiiExecutor()->getConfigurationContext();
		$transS = ServiceProvider::getTranslationService();

		$rec = $this->getRecord();
		$group = $this->getGroupP()->getGroup();

		if(	$this->getGroupP()->getRights()==null ||
			!$p->getGroupCreator($group->getModule()) ||
			//the only limitation on a rootGroup if not rootGroupCreator is the edit and delete
			//(!$p->getRootGroupCreator($group->getModule()) && $group->getGroupParentId()==null) ||
			!$this->getGroupP()->getRights()->canModify()
			){
			throw new ServiceException('editing group config need X right on group + groupCreator', ServiceException::FORBIDDEN);
		}

		$configfile = $config->getGroupConfigFilename($p, $group);
		//if the config is empty then delete the file
		if(!$rec->getFieldValue("groupConfigFile")){
			@unlink($configfile);
		} else {
			file_put_contents($configfile, stripslashes($rec->getFieldValue("groupConfigFile")));
		}

		$groupAS->unLock($p, $group);

		$this->getWigiiExecutor()->throwEvent()->updateGroup(PWithGroup::createInstance($p, $group));

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
		$group = $this->getGroupP()->getGroup();

//		$rec->getWigiiBag()->setHelp($transS->h($p, "authenticationServerHelp"), "authenticationServer");

		$this->getTrm()->setState($this->getState());
		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		if($this->getState() == "start"){
			$configfile = $config->getGroupConfigFilename($p, $group);
			$loaded = false;
			if(file_exists($configfile)){
				$rec->setFieldValue(addslashes(file_get_contents($configfile)), "groupConfigFile");
				$loaded = true;
			}
			//load a default config template
			if(!$loaded){

				//this is no more used now. I keep it here just for history
				$xml = '<?xml version="1.0" standalone="yes"?>
<'.$group->getModule()->getModuleName().'>
	<!-- ****************************************************
	** This is an example config file
	***************************************************** -->
	<!-- Redefine the fields -->
	<fields>
		<firstName type="Strings" require="1" searchable="1" ><label_l01>Firstname</label_l01><label_l02>Prénom</label_l02></firstName>
		<lastName type="Strings" require="1" searchable="1" ><label_l01>Lastname</label_l01><label_l02>Nom</label_l02></lastName>
		<email type="Emails" require="0" searchable="1" ><label>Email</label></email>
	</fields>

	<!-- Redefine the columns in list view -->
	<listView>
		<fields>
			<c1 field="firstName" width="160" />
			<c2 field="lastName" width="160" />
			<c3 field="email" width="null" />
			<c10 elementAttribut="sys_date" width="75" defaultSorted=" DESC " />
		</fields>
	</listView>

	<!-- Redefine the select search bar -->
	<selectSearchBar>
		<fields />
	</selectSearchBar>

	<!-- Adds(change) or Deletes specific nodes or attributes -->
	<!--
	<adds>
		<item node="parameters[\'elementTotalWidth\']" value="300" />
		<item node="parameters[\'elementLabelWidth\']" value="150" />
	</adds>

	<deletes>
		<item node="selectSearchBar->fields->c1" />
	</deletes>
	-->
</'.$group->getModule()->getModuleName().'>';
				//we are no more predefining
				$xml = "";
				$rec->setFieldValue($xml, "groupConfigFile");
			}
		}
		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$cancelJsCode = "" .
			"actOnCloseDialog('".$exec->getIdAnswer()."');" .
			"update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/group/".$group->getId().(array_pop(explode("/",$this->getSubmitUrl()))=="groupPanel" ? "/groupPanel" : "")."');" .
			"";
		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $transS->t($p, $rec->getActivity()->getActivityName()),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode, '{ my : "top", at: "top" }', null, false, true);

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
		
		$exec->addJsCode($this->getFormRenderer()->getJsCodeAfterShow());

		$this->getTrm()->addJsCode("" .
				"function getLineNumber(textarea, indicator) {" .
					"$(indicator).html($(textarea).val().substr(0, $(textarea).get()[0].selectionStart).split('\\n').length);" .
				"} " .
				"$('#".$this->getFormId()."_groupConfigFile_value_textarea').after('line: <span id=\"".$this->getFormId()."_groupConfigFile_value_textarea_lineNumber\">1</span>');" .
				"$('#".$this->getFormId()."_groupConfigFile_value_textarea').keyup(function(){ getLineNumber($(this), $('#".$this->getFormId()."_groupConfigFile_value_textarea_lineNumber')); }).mouseup(function(){ $(this).keyup(); });");

		//the tabby plugin is already called as the config editor has the parameter noWrap=1
	}
}



