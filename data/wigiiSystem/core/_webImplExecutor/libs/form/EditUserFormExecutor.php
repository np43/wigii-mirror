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
 * Created on 15 sept. 09
 * by LWR
 */
class EditUserFormExecutor extends FormExecutor {

	private $userP;
	protected function setUserP($userP){ $this->userP = $userP; }
	protected function getUserP(){ return $this->userP; }

	private $actOnCheckedRecordRequest;
	protected function setActOnCheckedRecordRequest($request){ $this->actOnCheckedRecordRequest = $request; }
	protected function getActOnCheckedRecordRequest(){ return $this->actOnCheckedRecordRequest; }

	public static function createInstance($wigiiExecutor, $userP, $record, $formId, $submitUrl, $actOnCheckedRecordRequest=""){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setUserP($userP);
		$fe->setRecord($record);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setActOnCheckedRecordRequest($actOnCheckedRecordRequest);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){
		$workingModuleName = $this->getWigiiExecutor()->getAdminContext($p)->getWorkingModule()->getModuleName();

		$transS = ServiceProvider::getTranslationService();
		$nsAS = ServiceProvider::getWigiiNamespaceAdminService();
		$userEditRec = $this->getRecord();
		$user = $this->getUserP()->getUser();

		$usernameFieldName = "username";
		$passwordLifeFieldName = "passwordLife";
		if($user->isRole()){
			$usernameFieldName = "rolename";
			$passwordLifeFieldName = "rolePasswordLife";
		}

		if($p->isWigiiNamespaceCreator() && $userEditRec->getFieldValue("moduleAccess")==null){
			$userEditRec->setFieldValue(implode(";", array_keys($p->getModuleAccess())), "moduleAccess");
		}
		if($p->isWigiiNamespaceCreator()){
			$user->getDetail()->setModuleAccess(ServiceProvider::getModuleAdminService()->formatModuleArray($p, $userEditRec->getFieldValue("moduleAccess")));
		}

		//on usual authentication method
		//password required
		if($userEditRec->getFieldValue("authenticationMethod") == "usual"){
			if($userEditRec->getFieldValue("password") == null){
				//if is role is checked then password is filled automatically with md5(time)
				if($user->isRole()){
					$userEditRec->setFieldValue(md5(time()), "password");
					$userEditRec->setFieldValue(true, "passwordHasBeenEdited");
				} else {
					$this->addErrorToField($transS->h($p, "compulsory_field"), "password");
				}
			}
			//change the password if not the empty one
			//try to set the password
			if($userEditRec->getFieldValue("passwordHasBeenEdited")){
				try{
					$user->getDetail()->setClearPassword($userEditRec->getFieldValue("password"));
					// CWE 03.02.2016: on reset password, resets nb of failed logins
					$user->getDetail()->setInfo_nbFailedLogin(0);
				} catch(ServiceException $e){
					$this->addErrorToField($transS->h($p, "invalidPassword"), "password");
				}
			}
		} else {
		//on other authentication method
			//if authenticationServier is empty, then it takes the config default one if defined
			if(!defined("EMAIL_defaultPop3Server") || EMAIL_defaultPop3Server == null){
				//authentication server required
				if($userEditRec->getFieldValue("authenticationServer") == null){
					$this->addErrorToField($transS->h($p, "compulsory_field"), "authenticationServer");
				}
			}
		}

		//other fields should not have any problems
		//eput($userEditRec->getFieldValue("wigiiNamespace"));
		$user->setWigiiNamespace($nsAS->getWigiiNamespace($p, $userEditRec->getFieldValue("wigiiNamespace")));
//		$user->setRole($userEditRec->getFieldValue("isRole"));

		//try to set the username
		//if the username contains the defaultEmailPostfix, delete it
		try{
			$username = $userEditRec->getFieldValue("$usernameFieldName");
			$result = array();
			$allow = '[_a-z0-9-]';
			$defaultEmailPostfix = EMAIL_postfix;
			if(preg_match('/@('.$allow.'+(\.'.$allow.'+)*)(\.'.$allow.'{2,})+$/i', $username, $result)==1){
				if($result[0] == $defaultEmailPostfix){
					$username = str_replace($result[0], "", $username);
				}
			}
			//if role add a @wigiiNamespace if not yet defined
			if($user->isRole()){
				//replace anything after an @
				$atPos = strpos($username, "@");
				if($atPos!==false){
					$username = substr($username, 0, $atPos);
				}
				if($user->getWigiiNamespace()->getWigiiNamespaceName()){
					$username = $username."@".$user->getWigiiNamespace()->getWigiiNamespaceName();
				}
			}
			$user->setUsername($username);
		} catch(ServiceException $e){
			$this->addErrorToField($e->getMessage(), "$usernameFieldName");
		}


		if($p->isWigiiNamespaceCreator()) $user->getDetail()->setWigiiNamespaceCreator($userEditRec->getFieldValue("isWigiiNamespaceCreator"));
		if($p->isModuleEditor()) $user->getDetail()->setModuleEditor($userEditRec->getFieldValue("isModuleEditor"));
		if($p->isReadAllUsersInWigiiNamespace()) $user->getDetail()->setReadAllUsersInWigiiNamespace($userEditRec->getFieldValue("isReadAllUsersInWigiiNamespace"));
		if($p->isAdminCreator()) $user->getDetail()->setAdminCreator($userEditRec->getFieldValue("isAdminCreator"));
		if($p->isUserCreator()) $user->getDetail()->setUserCreator($userEditRec->getFieldValue("isUserCreator"));

		if($p->getReadAllGroupsInWigiiNamespace($workingModuleName)){
			$checked = $userEditRec->getFieldValue("getReadAllGroupsInWigiiNamespace");
			$moduleArray = $user->getDetail()->getReadAllGroupsInWigiiNamespace();
			if($checked) $moduleArray[$workingModuleName] = ServiceProvider::getModuleAdminService()->getModule($p, $workingModuleName);
			else unset($moduleArray[$workingModuleName]);
			$user->getDetail()->setReadAllGroupsInWigiiNamespace($moduleArray);
		}
		if($p->getRootGroupCreator($workingModuleName)){
			$checked = $userEditRec->getFieldValue("getRootGroupCreator");
			$moduleArray = $user->getDetail()->getRootGroupCreator();
			if($checked) $moduleArray[$workingModuleName] = ServiceProvider::getModuleAdminService()->getModule($p, $workingModuleName);
			else unset($moduleArray[$workingModuleName]);
			$user->getDetail()->setRootGroupCreator($moduleArray);
		}
		if($p->getGroupCreator($workingModuleName)){
			$checked = $userEditRec->getFieldValue("getGroupCreator");
			$moduleArray = $user->getDetail()->getGroupCreator();
			if($checked) $moduleArray[$workingModuleName] = ServiceProvider::getModuleAdminService()->getModule($p, $workingModuleName);
			else unset($moduleArray[$workingModuleName]);
			$user->getDetail()->setGroupCreator($moduleArray);
		}

		$user->getDetail()->setCanModifyOwnPassword($userEditRec->getFieldValue("canModifyOwnPassword"));
		//if a life is setted and was not setted before and the passwordDate is null, then
		//add today to the password date.
		if($user->getDetail()->getPasswordLife() == null && $userEditRec->getFieldValue("$passwordLifeFieldName") != null && $user->getDetail()->getPasswordDate() == null){
			$user->getDetail()->setPasswordDate(time());
		}
		//if the passwordLife == "reset" reset the passworDate to today
		if($userEditRec->getFieldValue("$passwordLifeFieldName") === "reset"){
			$user->getDetail()->setPasswordDate(time());
			// CWE 03.02.2016: on reset password, resets nb of failed logins
			$user->getDetail()->setInfo_nbFailedLogin(0);
		}

		$user->getDetail()->setPasswordLife($userEditRec->getFieldValue("$passwordLifeFieldName"));
		if($user->getDetail()->getPasswordLife() <= 0){
			$user->getDetail()->setPasswordLife(null);
		}

		//the other password details are already changed if necessary in the setClearPassword
		$user->getDetail()->setDescription($userEditRec->getFieldValue("description"));
		$user->getDetail()->setAuthenticationMethod($userEditRec->getFieldValue("authenticationMethod"));
		$user->getDetail()->setAuthenticationServer($userEditRec->getFieldValue("authenticationServer"));

	}

	protected function actOnCheckedRecord($p, $exec) {

		$userAS = ServiceProvider::getUserAdminService();
		$fsl = FieldSelectorListArrayImpl::createInstance(false);
		$userAS->fillFieldSelectorListForUserEdit($fsl);
		$userD = $this->getUserP()->getUser()->getDetail();
		if($p->isWigiiNamespaceCreator()){
			$userD->calculateAdminLevelRights($userD->getModuleAccess(), true);
		} else {
			$userD->calculateAdminLevelRights($p->getModuleAccess());
		}
		$user = $this->getUserP()->getUser();
		$usernameFieldName = "username";
		$passwordLifeFieldName = "passwordLife";
		if($user->isRole()){
			$usernameFieldName = "rolename";
			$passwordLifeFieldName = "rolePasswordLife";
		}

		//on new user, then just add from admin level rights
		if($this->getUserP()->getUser()->getId()==null){
			$moduleAccess = $userD->getModuleAccessOnGroupAdminLevelRights();
			$needsAdmin = $userD->needsAdminAccess();
			if($needsAdmin){
				$moduleAccess[Module::ADMIN_MODULE] = Module::ADMIN_MODULE;
			}
			$userD->setModuleAccess(ServiceProvider::getModuleAdminService()->formatModuleArray($p, $moduleAccess));
		//on existing user, upgrade the moduleAccess to match rights
		} else {
			$tempGroupCreator = $this->getUserP()->getUser()->getDetail()->getGroupCreator();
			$newModuleAccess = $userAS->getModuleAccessFromRights($p, $this->getUserP());
			if($newModuleAccess != $this->getUserP()->getUser()->getDetail()->getModuleAccess()){
				$fsl->addFieldSelector("moduleAccess");
				$fsl->addFieldSelector("info_resetSessionContext");
				$userD->setModuleAccess($newModuleAccess);
				//since 20/08/2013 context only contains information that should not be reset
//				$user->getDetail()->setInfo_resetSessionContext(true);
			}
			if($tempGroupCreator != $this->getUserP()->getUser()->getDetail()->getGroupCreator()){
				$fsl->addFieldSelector("groupCreator");
			}
			if($p->isWigiiNamespaceCreator()){
				$fsl->addFieldSelector("moduleAccess");
			}
		}
		$userAS->fillFieldSelectorListForUserAdminRights($fsl);

		$userEditRec = $this->getRecord();

		// CWE 03.02.2016 adds 
		if($userEditRec->getFieldValue("passwordHasBeenEdited") || ($userEditRec->getFieldValue("$passwordLifeFieldName") === "reset")) {
			$fsl->addFieldSelector('info_nbFailedLogin');
		}
		
		try{
			$userAS->persistUser($p, $user, $fsl);
			$exec->addJsCode(" adminUser_crtSelectedUser = '".$user->getId()."'; ");
		} catch(UserAdminServiceException $e){
			if($e->getCode() == UserAdminServiceException::USERNAME_ALREADY_USED){
				$transS = ServiceProvider::getTranslationService();
				$this->addErrorToField($transS->h($p, "usernameAlreadyUsed"), "$usernameFieldName");
				return;
			}
			throw $e;
		}

		$this->getWigiiExecutor()->throwEvent()->updateUser(PWithUser::createInstance($p, $user));

		$userAS->unLock($p, $user);

		//important to clear the cancel stack
		$exec->addJsCode("actOnCloseDialog('".$exec->getIdAnswer()."');");

		if($this->getActOnCheckedRecordRequest()!=null){
			$exec->addRequests($this->getActOnCheckedRecordRequest());
		}
	}

	protected function getDialogTitle($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		return $transS->t($p, "editUser");
	}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$workingModule = $this->getWigiiExecutor()->getAdminContext($p)->getWorkingModule();

		$userEditRec = $this->getRecord();
		$user = $this->getUserP()->getUser();
		$usernameFieldName = "username";
		$passwordLifeFieldName = "passwordLife";
		if($user->isRole()){
			$usernameFieldName = "rolename";
			$passwordLifeFieldName = "rolePasswordLife";
		}

		//eput($user->displayDebug());
		$state = $this->getState();

		$this->getTrm()->setState($this->getState());

		//fill values on start, only if user is an existing user (this is for the case of the herited case of the add)
		if($state=="start" && $user->getId()){
			$userEditRec->setFieldValue($user->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
			$userEditRec->setFieldValue($user->getUsername(), "$usernameFieldName");
			$userEditRec->setFieldValue($user->getDetail()->getPasswordLife(), "$passwordLifeFieldName");
//			$userEditRec->setFieldValue($user->isRole(), "isRole");
			$moduleAccess = $user->getDetail()->getModuleAccess();
			if($moduleAccess !=null){
				$result = array();
				foreach($moduleAccess as $module){
					$result[] = $module->getModuleName();
				}
				$userEditRec->setFieldValue(implode(";", $result), "moduleAccess");
			}
			$userEditRec->setFieldValue(($user->getDetail()->isWigiiNamespaceCreator() ? true : null), "isWigiiNamespaceCreator");
			$userEditRec->setFieldValue(($user->getDetail()->isModuleEditor() ? true : null), "isModuleEditor");
			$userEditRec->setFieldValue(($user->getDetail()->isReadAllUsersInWigiiNamespace() ? true : null), "isReadAllUsersInWigiiNamespace");
			$userEditRec->setFieldValue(($user->getDetail()->isAdminCreator() ? true : null), "isAdminCreator");
			$userEditRec->setFieldValue(($user->getDetail()->isUserCreator() ? true : null), "isUserCreator");
			$userEditRec->setFieldValue(($user->getDetail()->getReadAllGroupsInWigiiNamespace($workingModule) ? true : null), "getReadAllGroupsInWigiiNamespace");
			$userEditRec->setFieldValue(($user->getDetail()->getRootGroupCreator($workingModule) ? true : null), "getRootGroupCreator");
			$userEditRec->setFieldValue(($user->getDetail()->getGroupCreator($workingModule) ? true : null), "getGroupCreator");
			$userEditRec->setFieldValue($user->getDetail()->getDescription(), "description");
			$userEditRec->setFieldValue(($user->getDetail()->canModifyOwnPassword() ? true : null), "canModifyOwnPassword");
			$userEditRec->setFieldValue(($user->getDetail()->getPassword()!=null ? str_repeat("*", ($user->getDetail()->getPasswordLength() ? $user->getDetail()->getPasswordLength() : 4)): ""), "password");
			$userEditRec->setFieldValue($user->getDetail()->getAuthenticationMethod(), "authenticationMethod");
			$userEditRec->setFieldValue($user->getDetail()->getAuthenticationServer(), "authenticationServer");
		}

		$hasOneAdminRight =
			$p->isWigiiNamespaceCreator() ||
			$p->isModuleEditor() ||
			$p->isAdminCreator() ||
			$p->isUserCreator() ||
			$p->isReadAllUsersInWigiiNamespace() ||
			$p->getReadAllGroupsInWigiiNamespace($workingModule) ||
			$p->getRootGroupCreator($workingModule) ||
			$p->getGroupCreator($workingModule)
			;
		$userEditRec->getWigiiBag()->setHidden(!$hasOneAdminRight, "userAdminRights");
		$userEditRec->getWigiiBag()->setHidden(!$p->isWigiiNamespaceCreator(), "isWigiiNamespaceCreator");
		$userEditRec->getWigiiBag()->setHidden(!$p->isModuleEditor(), "isModuleEditor");
		$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator() || !$p->isReadAllUsersInWigiiNamespace(), "isReadAllUsersInWigiiNamespace");
		$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator(), "isAdminCreator");
		$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator(), "isUserCreator");
		$userEditRec->getWigiiBag()->setHidden(!$p->getReadAllGroupsInWigiiNamespace($workingModule), "getReadAllGroupsInWigiiNamespace");
		$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator() || !$p->getRootGroupCreator($workingModule), "getRootGroupCreator");
		$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator() || !$p->getGroupCreator($workingModule), "getGroupCreator");

//		$userEditRec->getWigiiBag()->setHelp($transS->t($p, "isRoleHelp"), "isRole");
		if($user->getDetail()->getPasswordLength() == null){
			$userEditRec->getWigiiBag()->setHelp($transS->t($p, "starsAreNotTheRigthNumber"), "password");
		}
		$userEditRec->getWigiiBag()->setHelp($transS->t($p, "authenticationMethodHelp"), "authenticationMethod");
		$userEditRec->getWigiiBag()->setHelp($transS->t($p, "authenticationServerHelp"), "authenticationServer");


		$this->getTrm()->setFormRenderer($this->getFormRenderer());

		$this->getTrm()->openForm($this->getFormId(), $this->getSubmitUrl(), $this->getTotalWidth(), $this->isDialog());

		$this->getTrm()->displayRemainingForms();

		$this->getTrm()->closeForm($this->getFormId(), $this->goToNextState(), $this->getSubmitLabel(), $this->isDialog());

		$cancelJsCode = "update('NoAnswer/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/unlock/user/".$user->getId()."');";
		$this->getWigiiExecutor()->openAsDialogForm(
			$exec->getIdAnswer(), $this->getTotalWidth()+$this->getCorrectionWidth(),
			'$("form", this).submit();', $this->getDialogTitle($p, $exec),
			$transS->t($p, "ok"), $transS->t($p, "cancel"), $cancelJsCode);

		if(!$user->isRole()){
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
				"$('#".$this->getFormId()."__passwordHasBeenEdited').hide();" .
				"$('#".$this->getFormId()."_authenticationMethod_value_select').change(matchAuthenticationMethod);" .
				"$('#".$this->getFormId()."_password_value_password').keydown(function(e){" .
					"if(e.keyCode != 9){" .
						"$('#".$this->getFormId()."_passwordHasBeenEdited_value_checkbox').attr('checked', true);" .
					"}" .
				"});" .
				"");
		}

		$this->getTrm()->addJsCodeAfterFormIsShown($this->getFormId());
	}
}



