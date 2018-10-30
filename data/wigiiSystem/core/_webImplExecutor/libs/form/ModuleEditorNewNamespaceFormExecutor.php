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

/**
 * Wigii Admin Module configuration editor
 * Created on 6/07/2012 by LWR
 * Modified by Wigii.org (LWR) on 19.04.2017 to allow Wigii Namespace creator wizard to handle configuration files containing FuncExp.
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
			$wigiiNamespaceName = trim(preg_split("/[,;]+/", $rec->getFieldValue("moduleEditorNewNamespaceName"))[0]);//take first namespace
			$this->user = $this->createUser($p, $rec->getFieldValue("username"), $rec->getFieldValue("moduleEditorNewNamespaceName"), $rec->getFieldValue("moduleEditorNewNamespaceTabs"));
		}
		return $this->user;
	}
	protected function doSpecificCheck($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$userAS = ServiceProvider::getUserAdminService();
		$wNAS = ServiceProvider::getWigiiNamespaceAdminService();
		$argValid = TechnicalServiceProvider::getArgValidator();
		$rec = $this->getRecord();

		
		//lookup if multinamespace are defined
		$wigiiNamespaceNames = preg_split("/[,;]+/", $rec->getFieldValue("moduleEditorNewNamespaceName"));
		
		//check the syntax of the wigiiNamespace name
		if($wigiiNamespaceNames){
			$aNamespaceWithError = false;
			foreach($wigiiNamespaceNames as $wigiiNamespaceName){
				$wigiiNamespaceName = trim($wigiiNamespaceName);
				$wigiiNamespace = $wNAS->getWigiiNamespace($p, $wigiiNamespaceName);
				if($wigiiNamespaceName && !$argValid->checkAlphaNumSpaceDashUnderscorePoint($wigiiNamespaceName, 2, 64)){
					$aNamespaceWithError = true;
				}
				//if namespace already exist, then only update existing namespace with adding the new selected modules
		// 		//check that the namespace is not existing
		// 		if($wigiiNamespaceName && $userAS->doesWigiiNamespaceExist($p, $wigiiNamespace)){
		// 			$this->addErrorToField($transS->h($p, "wigiiNamespaceAlreadyExist"), "moduleEditorNewNamespaceName");
		// 		}
			}
			if($aNamespaceWithError) $this->addErrorToField($transS->h($p, "wigiiNamespaceWrongFormat"), "moduleEditorNewNamespaceName");
		}
		
		//check the syntax of the username
		$username = $rec->getFieldValue("username");
		
		if($username && !$argValid->checkNoSpecialCharsString($username, USERNAME_minLength, USERNAME_maxLength, false)){
			$this->addErrorToField($transS->h($p, "usernameWrongFormat"), "username");
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
		$wNAS = ServiceProvider::getWigiiNamespaceAdminService();
		$mAS = ServiceProvider::getModuleAdminService();
		$configS = $this->getWigiiExecutor()->getConfigurationContext();
		$userAS = ServiceProvider::getUserAdminService();
		$groupAS = ServiceProvider::getGroupAdminService();
		$rec = $this->getRecord();
		$user = $this->getUser($p);
		$configPrefix = $rec->getFieldValue("moduleEditorNewNamespaceConfigPrefixToUse");
		
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
		
		$wigiiNamespaceNames = preg_split("/[,;]+/", $rec->getFieldValue("moduleEditorNewNamespaceName"));
		$supportName = $rec->getFieldValue("moduleEditorNewNamespaceSupportName");
		$supportEmail = $rec->getFieldValue("moduleEditorNewNamespaceSupportEmail");
		
		$configFileSummary = Array();
		foreach($wigiiNamespaceNames as $wigiiNamespaceName){
			$wigiiNamespaceName = trim($wigiiNamespaceName);
			$wigiiNamespace = $wNAS->getWigiiNamespace($p, $wigiiNamespaceName);
			$moduleAccess = $mAS->formatModuleArray($p, $rec->getFieldValue("moduleEditorNewNamespaceTabs"));
			//create NAdmin role if not existing
			$NAdminId = $userAS->doesUsernameExist($p, "NAdmin@".$wigiiNamespace->getWigiiNamespaceName());
			if(!$NAdminId){
				$nAdmin = $this->createUser($p, "NAdmin@".$wigiiNamespace->getWigiiNamespaceName(), $wigiiNamespace->getWigiiNamespaceName(), array_merge(array("Admin"), array_keys($moduleAccess)));
				$nAdmin->setRole(true);
				//need to add every admin rights
				$nAdmin->getDetail()->setUserCreator(true);
				$nAdmin->getDetail()->setAdminCreator(true);
				$nAdmin->getDetail()->setReadAllUsersInWigiiNamespace(true);
				$nAdmin->getDetail()->setWigiiNamespaceCreator(false);
				$nAdmin->getDetail()->setModuleEditor(true);
			} else {
				$nAdminP = $userAS->getUser($p, $NAdminId);
				$nAdmin = $nAdminP->getDbEntity();
				if(!$rec->getFieldValue("moduleEditorNewNamespaceOverwriteExistingConfig")){
					//only keep the modules not existing already in the nadmin (for the group creation)
					$moduleAccess = array_diff_key($moduleAccess,$nAdmin->getDetail()->getModuleAccess());
				}
			}
			//merge modules for the nadmin
			if($nAdmin->getDetail()->getModuleAccess()){
				$NAdminModuleAccess = array_merge($nAdmin->getDetail()->getModuleAccess(),$moduleAccess);
			} else {
				$NAdminModuleAccess = $moduleAccess;
			}
			$nAdmin->getDetail()->setModuleAccess($NAdminModuleAccess);
			$nAdmin->getDetail()->setGroupCreator($NAdminModuleAccess);
			$nAdmin->getDetail()->setRootGroupCreator($NAdminModuleAccess);
			$nAdmin->getDetail()->setReadAllGroupsInWigiiNamespace($NAdminModuleAccess);
			$userAS->persistUser($p, $nAdmin);
			
			//link user to NAdmin
			//		fput("user ".$user->getUsername()." id ".$user->getId()." nadmin: ".$nAdmin->getId());
			$userAS->setUserOwnership($p, $nAdmin->getId(), $user->getId());
			$userAS->setUserRole($p, $user->getId(), $nAdmin->getId());
			
			//create 1 folder per tab
			
			foreach($moduleAccess as $module){
				//create or get id of root Groups
				$group = $groupAS->getOrCreateRootGroupByName($p, $module, $wigiiNamespace, $transS->t($p, $module->getModuleName()));
				$trash = $groupAS->getOrCreateRootGroupByName($p, $module, $wigiiNamespace, $transS->t($p, "trashbinGroupName"));
				/* before 12.09.2018 the root groups where always created
				 $group = $this->createGroup($p, $transS->t($p, $module->getModuleName()), $module, $wigiiNamespace);
				 $trash = $this->createGroup($p, $transS->t($p, "trashbinGroupName"), $module, $wigiiNamespace);
				 $groupAS->persistGroup($p, $group);
				 $groupAS->persistGroup($p, $trash);
				 */
				
				//link each folder to NAdmin
				$groupAS->setUserRight($p, UGR::createInstance($group->getId(), $nAdmin->getId(), array("canModify"=>true, "canWriteElement"=>true, "canShareElement"=>true)));
				$groupAS->setUserRight($p, UGR::createInstance($trash->getId(), $nAdmin->getId(), array("canModify"=>true, "canWriteElement"=>true, "canShareElement"=>true)));
				//remove link of current superadmin to created folders
				$groupAS->removeUser($p, $group->getId(), $p->getUserId());
				$groupAS->removeUser($p, $trash->getId(), $p->getUserId());
				//copy general config to namespace config
				$generalConfigFile = $configS->getModuleConfigFilename($p, $module, null);
				if($configPrefix){
					$newGeneralConfigFile = str_replace($module->getModuleName()."_config.xml",$configPrefix.$module->getModuleName()."_config.xml",$generalConfigFile);
					if(file_exists($newGeneralConfigFile)) $generalConfigFile = $newGeneralConfigFile;
				}
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
				// Puts all FuncExp into single quotes, then call html_entity_decode.
				// A FuncExp is a string having parenthesis and no quotes inside.
				$fileContent = html_entity_decode(preg_replace('/="([^"\']+[(][^"\']*[)][^"\']*)"/', '=\'$1\'', $fileContent),ENT_QUOTES);
				file_put_contents($namespaceConfigFile,$fileContent);
				$configFileSummary[] = "<br />".$generalConfigFile."<br />".$namespaceConfigFile;
			}
			
			//remove link of current superadmin to nadmin
			if($user->getId() != $p->getUserId()){
				$userAS->removeUserRole($p, $p->getUserId(), $nAdmin->getId());
			}
		}
		

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");
		$this->getWigiiExecutor()->openAsMessage($exec->getIdAnswer(),500, $transS->t($p, "operationDoneSuccessfully"), '<div style="overflow:auto;max-height:300px;">'."Please find bellow the list of the modules added to the namespace:<br/>".put($moduleAccess)."<br /><br />Find bellow the list of configuration files used during the creation process:<br />".put($configFileSummary).'</div>', null, "Ok", null, null, "done");
		//$this->getWigiiExecutor()->operationSuccessfullMessage($exec->getIdAnswer(), 350, $transS->t($p, "operationDoneSuccessfully").put($moduleAccess), "", "done");
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



