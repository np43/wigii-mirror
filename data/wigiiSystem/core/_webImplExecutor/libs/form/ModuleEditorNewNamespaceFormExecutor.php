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
 * Created on 6/07/2012
 * by LWR
 */
class ModuleEditorNewNamespaceFormExecutor extends FormExecutor {

	public static function createInstance($wigiiExecutor, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor); //important to be in the begining because other setter could use the configurationContext as configService
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		return $fe;
	}

	private $user;
	protected function getUser($p){
		if(!isset($this->user)){
			$rec = $this->getRecord();
			$this->user = $this->createUser($p, $rec->getFieldValue("username"), $rec->getFieldValue("moduleEditorNewNamespaceName"), $rec->getFieldValue("moduleEditorNewNamespaceTabs"));
		}
		return $this->user;
	}
	protected function doSpecificCheck($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$userAS = ServiceProvider::getUserAdminService();
		$wNAS = ServiceProvider::getWigiiNamespaceAdminService();
		$rec = $this->getRecord();

		$wigiiNamespaceName = $rec->getFieldValue("moduleEditorNewNamespaceName");
		$wigiiNamespace = $wNAS->getWigiiNamespace($p, $wigiiNamespaceName);
		$username = $rec->getFieldValue("username");

		//check the syntax of the wigiiNamespace name
		if($wigiiNamespaceName && !ArgValidator::checkAlphaNumSpaceDashUnderscorePoint($wigiiNamespaceName, 2, 64)){
			$this->addErrorToField($transS->h($p, "wigiiNamespaceWrongFormat"), "moduleEditorNewNamespaceName");
		}
		//check the syntax of the username
		if($username && !ArgValidator::checkNoSpecialCharsString($username, USERNAME_minLength, USERNAME_maxLength, false)){
			$this->addErrorToField($transS->h($p, "usernameWrongFormat"), "username");
		}
		//check that the namespace is not existing
		if($wigiiNamespaceName && $userAS->doesWigiiNamespaceExist($p, $wigiiNamespace)){
			$this->addErrorToField($transS->h($p, "wigiiNamespaceAlreadyExist"), "moduleEditorNewNamespaceName");
		}
		//check if the user exists that password or authentication server are defined
		if(!$userAS->doesUsernameExist($p, $username)){
			//password or authentication server needs to be filled up
			if($rec->getFieldValue("authenticationServer")==null && $rec->getFieldValue("password")==null){
				$this->addErrorToField($transS->h($p, "newUserNeedsCredentials"), "authenticationMethod");
			}
		}
	}

	protected function createUser($p, $username, $wigiiNamespaceName, $moduleAccess){
		$wNAS = ServiceProvider::getWigiiNamespaceAdminService();
		$mAS = ServiceProvider::getModuleAdminService();
		$wigiiNamespace = $wNAS->getWigiiNamespace($p, $wigiiNamespaceName);
		$user = User::createInstance(array("username"=>$username, "wigiiNamespace"=>$wigiiNamespace), true);
		$userD = $user->getDetail();
		$userD->setModuleAccess($mAS->formatModuleArray($p, $moduleAccess));
		$realUser = ($p->isPlayingRole() ? $p->getRealUser() : $p->getAttachedUser());
		$userD->setDescription("created the ".date("d.m.Y")." by ".$realUser->getWigiiNamespace()->getWigiiNamespaceName().":".$realUser->getUsername());

		return $user;
	}

	protected function createGroup($p, $groupName, $module, $wigiiNamespace){
		$group = Group::createInstance(array("groupname"=>$groupName, "module"=>$module, "wigiiNamespace"=>$wigiiNamespace), true);
		$groupD = $group->getDetail();
		$realUser = ($p->isPlayingRole() ? $p->getRealUser() : $p->getAttachedUser());
		$groupD->setDescription("created the ".date("d.m.Y")." by ".$realUser->getWigiiNamespace()->getWigiiNamespaceName().":".$realUser->getUsername()."\nfor ".$this->getUser($p)->getUsername());
		return $group;
	}

	protected function actOnCheckedRecord($p, $exec) {
		$transS = ServiceProvider::getTranslationService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$userAS = ServiceProvider::getUserAdminService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$rec = $this->getRecord();
		$user = $this->getUser($p);
		$moduleAccess = $user->getDetail()->getModuleAccess();
		$wigiiNamespace = $user->getWigiiNamespace();

		//create user if not existing
		$userId = $userAS->doesUsernameExist($p, $user->getUsername());
		if(!$userId){
			$user->getDetail()->setAuthenticationMethod($rec->getFieldValue("authenticationMethod"));
			if($rec->getFieldValue("authenticationMethod")=="usual") {
				$user->getDetail()->setClearPassword($rec->getFieldValue("password"));
				$user->getDetail()->setCanModifyOwnPassword(true);
			}
			else $user->getDetail()->setAuthenticationServer($rec->getFieldValue("authenticationServer"));
			$userAS->persistUser($p, $user);
			$userId = $user->getId();
		} else {
			$userP = $userAS->getUser($p, $userId);
			$user = $userP->getDbEntity();
		}
		//create NAdmin role
		$nAdmin = $this->createUser($p, "NAdmin@".$wigiiNamespace->getWigiiNamespaceName(), $wigiiNamespace->getWigiiNamespaceName(), array_merge(array("Admin"), array_keys($moduleAccess)));
		$nAdmin->setRole(true);
		//need to add every admin rights
		$nAdmin->getDetail()->setUserCreator(true);
		$nAdmin->getDetail()->setAdminCreator(true);
		$nAdmin->getDetail()->setReadAllUsersInWigiiNamespace(true);
		$nAdmin->getDetail()->setGroupCreator($moduleAccess);
		$nAdmin->getDetail()->setRootGroupCreator($moduleAccess);
		$nAdmin->getDetail()->setReadAllGroupsInWigiiNamespace($moduleAccess);
		$nAdmin->getDetail()->setWigiiNamespaceCreator(false);
		$nAdmin->getDetail()->setModuleEditor(true);
		$userAS->persistUser($p, $nAdmin);

		//link user to NAdmin
//		fput("user ".$user->getUsername()." id ".$user->getId()." nadmin: ".$nAdmin->getId());
		$userAS->setUserOwnership($p, $nAdmin->getId(), $user->getId());
		$userAS->setUserRole($p, $user->getId(), $nAdmin->getId());

		//create 1 folder per tab
		$supportName = $rec->getFieldValue("moduleEditorNewNamespaceSupportName");
		$supportEmail = $rec->getFieldValue("moduleEditorNewNamespaceSupportEmail");
		foreach($moduleAccess as $module){
			$group = $this->createGroup($p, $transS->t($p, $module->getModuleName()), $module, $wigiiNamespace);
			$trash = $this->createGroup($p, $transS->t($p, "trashbinGroupName"), $module, $wigiiNamespace);
			$groupAS->persistGroup($p, $group);
			$groupAS->persistGroup($p, $trash);
			//link each folder to NAdmin
			$groupAS->setUserRight($p, UGR::createInstance($group->getId(), $nAdmin->getId(), array("canModify"=>true, "canWriteElement"=>true, "canShareElement"=>true)));
			$groupAS->setUserRight($p, UGR::createInstance($trash->getId(), $nAdmin->getId(), array("canModify"=>true, "canWriteElement"=>true, "canShareElement"=>true)));
			//remove link of current superadmin to created folders
			$groupAS->removeUser($p, $group->getId(), $p->getUserId());
			$groupAS->removeUser($p, $trash->getId(), $p->getUserId());
			//copy general config to namespace config
			$generalConfigFile = $configS->getModuleConfigFilename($p, $module, null);
			$generalConfig = simplexml_load_file($generalConfigFile);
			if($generalConfig->parameters){
				if(!$generalConfig->parameters["trashBinGroup"]) $generalConfig->parameters->addAttribute("trashBinGroup", $trash->getId());
				else $generalConfig->parameters["trashBinGroup"] = $trash->getId();
			}
			if($supportEmail){
				if($generalConfig->feedbackOnElement->fields->feedback_for){
					$checkedAttr = $generalConfig->feedbackOnElement->fields->feedback_for->xpath("attribute[@checked='1']");
					if($checkedAttr) unset($checkedAttr[0][checked]);
					$suppNode = $generalConfig->feedbackOnElement->fields->feedback_for->addChild("attribute", ($supportName ? $supportName : $supportEmail));
					$suppNode->addAttribute("checked", "1");
					$suppNode->addAttribute("email", $supportEmail);
				}
				if($generalConfig->feedbackOnSystem->fields->feedback_for){
					$checkedAttr = $generalConfig->feedbackOnSystem->fields->feedback_for->xpath("attribute[@checked='1']");
					if($checkedAttr) unset($checkedAttr[0][checked]);
					$suppNode = $generalConfig->feedbackOnSystem->fields->feedback_for->addChild("attribute", ($supportName ? $supportName : $supportEmail));
					$suppNode->addAttribute("checked", "1");
					$suppNode->addAttribute("email", $supportEmail);
				}
			}
			$namespaceConfigFile = $configS->getModuleConfigFilename($p, $module, $wigiiNamespace);
			$fileContent =  $generalConfig->asXml();
			//lookup for funcExp="expression"
			//this should be replaced with funcExp='expression' to prevent xml parsing errors
			$fileContent = preg_replace('/funcExp="(.*)"/', 'funcExp=\'$1\'', $fileContent);
			file_put_contents($namespaceConfigFile,$fileContent);
		}

//		//remove link of current superadmin to nadmin
		if($user->getId() != $p->getUserId()){
			$userAS->removeUserRole($p, $p->getUserId(), $nAdmin->getId());
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

		//add js code to disable password if authenticationMethod normal is selected
		$exec->addJsCode("" .
			"function matchAuthenticationMethod(){" .
				"var isUsual = $('#".$this->getFormId()."_authenticationMethod_value_select').val()=='usual';" .
				"$('#".$this->getFormId()."_authenticationServer_value_text').attr('disabled', isUsual);" .
				"$('#".$this->getFormId()."_password_value_password').attr('disabled', !isUsual);" .
				"$('#".$this->getFormId()." * ').removeClass('lightGray  darkGrayFont');" .
				"$('#".$this->getFormId()." :disabled').addClass('lightGray').parent().prev().addClass('darkGrayFont');" .
			"}" .
			"matchAuthenticationMethod();" .
			"$('#".$this->getFormId()."_authenticationMethod_value_select').change(matchAuthenticationMethod);" .
			"");
	}
}



