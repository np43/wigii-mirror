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
 * Created on 15 sept. 09 by LWR
 * Updated by Medair in 2017 for maintenance purpose (see SVN log for details)
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

	private $multipleUsernames;
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
					$this->addErrorToField($transS->h($p, "invalidPassword")
					.($e->getCode() == UserAdminServiceException::INVALID_PASSWORD?'. '.$e->getMessage():''), "password");
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
			//$username = $userEditRec->getFieldValue("$usernameFieldName");			
			if($user->isRole()){
				$usernames[0] = $userEditRec->getFieldValue("$usernameFieldName");
			} else {
				$usernames = preg_split("/".ValueListArrayMapper::Natural_Separators."/", $userEditRec->getFieldValue("$usernameFieldName"));
			}
			$this->multipleUsernames = array();
			foreach($usernames as $username){
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
				$this->multipleUsernames[$username] = $username;	
			}
			$user->setUsername(reset($this->multipleUsernames));
		} catch(ServiceException $e){
			$this->addErrorToField($e->getMessage(), "$usernameFieldName");
		}


		if($p->isWigiiNamespaceCreator()) $user->getDetail()->setWigiiNamespaceCreator($userEditRec->getFieldValue("isWigiiNamespaceCreator"));
		if($p->isModuleEditor()) $user->getDetail()->setModuleEditor($userEditRec->getFieldValue("isModuleEditor"));
		if($p->isReadAllUsersInWigiiNamespace()) $user->getDetail()->setReadAllUsersInWigiiNamespace($userEditRec->getFieldValue("isReadAllUsersInWigiiNamespace"));
		if($p->isAdminCreator()) $user->getDetail()->setAdminCreator($userEditRec->getFieldValue("isAdminCreator"));
		if($p->isUserCreator()) $user->getDetail()->setUserCreator($userEditRec->getFieldValue("isUserCreator"));
		
		/*
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
		*/
		
		$moduleAccess = array();
		// if superadmin then takes the selected boxes		
		if($p->isWigiiNamespaceCreator()) {
			$checkedModuleArray = $userEditRec->getFieldValue("getReadAllGroupsInWigiiNamespace");					
			if($checkedModuleArray!=null) {
				$checkedModuleArray = array_combine($checkedModuleArray, $checkedModuleArray);
				$checkedModuleArray = ServiceProvider::getModuleAdminService()->formatModuleArray($p, $checkedModuleArray);
				unset($checkedModuleArray[Module::EMPTY_MODULE_NAME]);
				unset($checkedModuleArray[Module::ADMIN_MODULE]);
				if($checkedModuleArray[Module::HELP_MODULE]) unset($checkedModuleArray[Module::HELP_MODULE]);
				$user->getDetail()->setReadAllGroupsInWigiiNamespace($checkedModuleArray);
				$moduleAccess = array_merge($moduleAccess, $checkedModuleArray);
			}
			
			$checkedModuleArray = $userEditRec->getFieldValue("getRootGroupCreator");
			if($checkedModuleArray!=null) {
				$checkedModuleArray = array_combine($checkedModuleArray, $checkedModuleArray);
				$checkedModuleArray = ServiceProvider::getModuleAdminService()->formatModuleArray($p, $checkedModuleArray);
				unset($checkedModuleArray[Module::EMPTY_MODULE_NAME]);
				unset($checkedModuleArray[Module::ADMIN_MODULE]);
				if($checkedModuleArray[Module::HELP_MODULE]) unset($checkedModuleArray[Module::HELP_MODULE]);
				$user->getDetail()->setRootGroupCreator($checkedModuleArray);
				$moduleAccess = array_merge($moduleAccess, $checkedModuleArray);
			}
			
			$checkedModuleArray = $userEditRec->getFieldValue("getGroupCreator");
			if($checkedModuleArray!=null) {
				$checkedModuleArray = array_combine($checkedModuleArray, $checkedModuleArray);
				$checkedModuleArray = ServiceProvider::getModuleAdminService()->formatModuleArray($p, $checkedModuleArray);
				unset($checkedModuleArray[Module::EMPTY_MODULE_NAME]);
				unset($checkedModuleArray[Module::ADMIN_MODULE]);
				if($checkedModuleArray[Module::HELP_MODULE]) unset($checkedModuleArray[Module::HELP_MODULE]);
				$user->getDetail()->setGroupCreator($checkedModuleArray);
				$moduleAccess = array_merge($moduleAccess, $checkedModuleArray);
			}
		}
		// else intersects with principal access
		else {
			$modulesList = $p->getModuleAccess();
			unset($modulesList[Module::ADMIN_MODULE]);
			if($modulesList[Module::HELP_MODULE]) unset($modulesList[Module::HELP_MODULE]);
			
			$moduleArray = $user->getDetail()->getReadAllGroupsInWigiiNamespace();
			$checkedModuleArray = $userEditRec->getFieldValue("getReadAllGroupsInWigiiNamespace");
			if($checkedModuleArray==null) $checkedModuleArray = array();
			foreach ($modulesList as $moduleName=>$module) {
				if($p->isWigiiNamespaceCreator() || $p->getReadAllGroupsInWigiiNamespace($moduleName)) {
					$checked = in_array($moduleName, $checkedModuleArray);
					if($checked)
						$moduleArray[$moduleName] = $module;
					else
						unset($moduleArray[$moduleName]);
				}
			}
			$user->getDetail()->setReadAllGroupsInWigiiNamespace($moduleArray);
			if($moduleArray!=null) $moduleAccess = array_merge($moduleAccess,$moduleArray);
			/*if($p->getReadAllGroupsInWigiiNamespace($workingModuleName)){
			 $checked = $userEditRec->getFieldValue("getReadAllGroupsInWigiiNamespace");
			 $moduleArray = $user->getDetail()->getReadAllGroupsInWigiiNamespace();
			 if($checked) $moduleArray[$workingModuleName] = ServiceProvider::getModuleAdminService()->getModule($p, $workingModuleName);
			 else unset($moduleArray[$workingModuleName]);
			 $user->getDetail()->setReadAllGroupsInWigiiNamespace($moduleArray);
			}*/
			
			$moduleArray = $user->getDetail()->getRootGroupCreator();
			$checkedModuleArray = $userEditRec->getFieldValue("getRootGroupCreator");
			if($checkedModuleArray==null) $checkedModuleArray = array();
			foreach ($modulesList as $moduleName => $module) {
				if($p->isWigiiNamespaceCreator() || $p->getRootGroupCreator($moduleName)){
					$checked = in_array($moduleName, $checkedModuleArray);
					if($checked)
						$moduleArray[$moduleName] = $module;
					else
						unset($moduleArray[$moduleName]);
				}
			}
			$user->getDetail()->setRootGroupCreator($moduleArray);
			if($moduleArray!=null) $moduleAccess = array_merge($moduleAccess,$moduleArray);
			/*
			 if($p->getRootGroupCreator($workingModuleName)){
			 $checked = $userEditRec->getFieldValue("getRootGroupCreator");
			 $moduleArray = $user->getDetail()->getRootGroupCreator();
			 if($checked) $moduleArray[$workingModuleName] = ServiceProvider::getModuleAdminService()->getModule($p, $workingModuleName);
			 else unset($moduleArray[$workingModuleName]);
			 $user->getDetail()->setRootGroupCreator($moduleArray);
			 }
			*/
			
			$moduleArray = $user->getDetail()->getGroupCreator();
			$checkedModuleArray = $userEditRec->getFieldValue("getGroupCreator");
			if($checkedModuleArray==null) $checkedModuleArray = array();
			foreach ($modulesList as $moduleName => $module) {
				if($p->isWigiiNamespaceCreator() || $p->getGroupCreator($moduleName)){
					$checked = in_array($moduleName, $checkedModuleArray);
					if($checked)
						$moduleArray[$moduleName] = $module;
					else
						unset($moduleArray[$moduleName]);
				}
			}
			$user->getDetail()->setGroupCreator($moduleArray);
			if($moduleArray!=null) $moduleAccess = array_merge($moduleAccess,$moduleArray);
			/*if($p->getGroupCreator($workingModuleName)){
			 $checked = $userEditRec->getFieldValue("getGroupCreator");
			 $moduleArray = $user->getDetail()->getGroupCreator();
			 if($checked) $moduleArray[$workingModuleName] = ServiceProvider::getModuleAdminService()->getModule($p, $workingModuleName);
			 else unset($moduleArray[$workingModuleName]);
			 $user->getDetail()->setGroupCreator($moduleArray);
			 }*/
		}
		$user->getDetail()->setModuleAccess($moduleAccess);
		
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
	
	protected function getModuleConfigTemplateFilename($principal){
		$configS = ServiceProvider::getConfigService();
		$file = $configS->getClientConfigFolderPath($principal).'config_moduleTemplate.xml';
		if(!file_exists($file)){
			$file = $configS->getConfigFolderPath($principal).'config_moduleTemplate.xml';
		}	
		return $file;
	}
	
	protected function doesModuleHasConfigFile($principal, $module, $wigiiNamespace){
		$configS = ServiceProvider::getConfigService();
		return file_exists($configS->getModuleConfigFilename($principal, $module, $wigiiNamespace));
	}

	protected function createModuleFile($principal, $module, $templateFilePath, $configFolder){
		$xmlString = file_get_contents($templateFilePath);		
		$xmlString = str_replace('<moduleName>','<'.$module->getModuleName().'>', $xmlString);
		$xmlString = str_replace('</moduleName>','</'.$module->getModuleName().'>', $xmlString);
		$filename = $configFolder.$module->getModuleName().'_config.xml';		
		file_put_contents($filename, $xmlString);
	}
		
	protected function actOnCheckedRecord($p, $exec) {
		$userAS = ServiceProvider::getUserAdminService();
		$fsl = FieldSelectorListArrayImpl::createInstance(false,false);
		$userAS->fillFieldSelectorListForUserEdit($fsl);
		$userD = $this->getUserP()->getUser()->getDetail();
		$userEditRec = $this->getRecord();
		$configS = ServiceProvider::getConfigService();
		/*
		if($p->isWigiiNamespaceCreator()){			
			$userD->calculateAdminLevelRights($userD->getModuleAccess(), true);
		} else {
			$userD->calculateAdminLevelRights($p->getModuleAccess());
		}
		*/
		if($p->isWigiiNamespaceCreator()){
			// prepares moduleAccess based on ticked boxes and add module field
		    $moduleAccess = $userD->getModuleAccess();
		    if($moduleAccess==null) $moduleAccess = array();
			if($userEditRec->getFieldValue('addModuleAccess')!=null) {
				$addModule = ServiceProvider::getModuleAdminService()->formatModuleArray($p, $userEditRec->getFieldValue('addModuleAccess'));
				foreach ($addModule as $module){
					if(!$this->doesModuleHasConfigFile($p, $module, null)){
						$path = $configS->getModuleConfigFilename($p, $module, null);
						$templateFilePath = $this->getModuleConfigTemplateFilename($p);
						if(file_exists($templateFilePath)){
							$this->createModuleFile($p, $module, $templateFilePath, $configS->getClientConfigFolderPath($p));
						}
					}
				}				
				$moduleAccess = array_merge($moduleAccess, $addModule);
				$userD->setModuleAccess($moduleAccess);
				// adds root group creator on new modules
				if($userD->getRootGroupCreator()==null) $userD->setRootGroupCreator($addModule);
				else $userD->setRootGroupCreator(array_merge($addModule, $userD->getRootGroupCreator()));
			}			
			$userD->calculateAdminLevelRights($userD->getModuleAccess());
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
		$moduleAccess = $userD->getModuleAccessOnGroupAdminLevelRights();
				
		$needsAdmin = $userD->needsAdminAccess();
		if($needsAdmin){			
			$moduleAccess[Module::ADMIN_MODULE] = Module::ADMIN_MODULE;						
		}
		if($this->getUserP()->getUser()->getId()==null){
			$userD->setModuleAccess(ServiceProvider::getModuleAdminService()->formatModuleArray($p, $moduleAccess));
		//on existing user, upgrade the moduleAccess to match rights
		} else {
			$tempGroupCreator = $userD->getGroupCreator();
			$newModuleAccess = $userAS->getModuleAccessFromRights($p, $this->getUserP());
			if($newModuleAccess != $userD->getModuleAccess()){
				$fsl->addFieldSelector("moduleAccess");
				$fsl->addFieldSelector("info_resetSessionContext");
				$userD->setModuleAccess(array_merge($moduleAccess, $newModuleAccess));
				//since 20/08/2013 context only contains information that should not be reset
//				$user->getDetail()->setInfo_resetSessionContext(true);
			}
			if($tempGroupCreator != $userD->getGroupCreator()){
				$fsl->addFieldSelector("groupCreator");
			}
			if($p->isWigiiNamespaceCreator()){
				$fsl->addFieldSelector("moduleAccess");
			}
		}
		$userAS->fillFieldSelectorListForUserAdminRights($fsl);

		//save email
		if($userEditRec->getFieldList()->doesFieldExist('email')) {
			//$userInfo = str2array($userD->getInfo_lastSessionContext());
			//$userInfo['generalContext']['email'] = $userEditRec->getFieldValue('principalEmail');
			//$userD->setInfo_lastSessionContext(array2str($userInfo));
			$userD->setEmail($userEditRec->getFieldValue('email'));
		}
		
		// CWE 03.02.2016 adds 
		if($userEditRec->getFieldValue("passwordHasBeenEdited") || ($userEditRec->getFieldValue("$passwordLifeFieldName") === "reset")) {
			$fsl->addFieldSelector('info_nbFailedLogin');
		}
		
		try{
			if(!$this->multipleUsernames) $this->multipleUsernames = array($user->getUsername() => $user->getUsername());
			// if role then only takes first user
			elseif($user->isRole()) {
				$this->multipleUsernames = reset($this->multipleUsernames);
				$this->multipleUsernames = array($this->multipleUsernames => $this->multipleUsernames);
			}
			$count = 0; $userInserted=array();
			foreach($this->multipleUsernames as $username){
				$user->setUsername($username);
				if($count++ > 0) { $user->setId(0);	};
				$userAS->persistUser($p, $user, $fsl);
				$userInserted[$username] = $username;				
			}
			$exec->addJsCode(" adminUser_crtSelectedUser = '".$user->getId()."'; ");
		} catch(UserAdminServiceException $e){
			if($e->getCode() == UserAdminServiceException::USERNAME_ALREADY_USED){
				$transS = ServiceProvider::getTranslationService();
				$this->addErrorToField($transS->h($p, "usernameAlreadyUsed").(count($this->multipleUsernames)>1?" '$username'":''), "$usernameFieldName");
				// replaces form with non yet inserted users
				if(count($userInserted)>0) $userEditRec->setFieldValue(implode(", ",array_diff_key($this->multipleUsernames, $userInserted)), "$usernameFieldName");
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

		//fill values on start
		if($state=="start"){
			// fills all fields for existing user
			if($user->getId()) {
				$userEditRec->setFieldValue($user->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
				$userEditRec->setFieldValue($user->getUsername(), "$usernameFieldName");
				$userEditRec->setFieldValue($user->getDetail()->getPasswordLife(), "$passwordLifeFieldName");
	//			$userEditRec->setFieldValue($user->isRole(), "isRole");
				$moduleAccess = $user->getDetail()->getModuleAccess();
				if($moduleAccess !=null) $userEditRec->setFieldValue(implode(";",array_keys($moduleAccess)),"moduleAccess");
				$userEditRec->setFieldValue(($user->getDetail()->isWigiiNamespaceCreator() ? true : null), "isWigiiNamespaceCreator");
				$userEditRec->setFieldValue(($user->getDetail()->isModuleEditor() ? true : null), "isModuleEditor");
				$userEditRec->setFieldValue(($user->getDetail()->isReadAllUsersInWigiiNamespace() ? true : null), "isReadAllUsersInWigiiNamespace");
				$userEditRec->setFieldValue(($user->getDetail()->isAdminCreator() ? true : null), "isAdminCreator");
				$userEditRec->setFieldValue(($user->getDetail()->isUserCreator() ? true : null), "isUserCreator");
	
				//$userEditRec->setFieldValue(($user->getDetail()->getReadAllGroupsInWigiiNamespace($workingModule) ? true : null), "getReadAllGroupsInWigiiNamespace");
				$readAllGroups = $user->getDetail()->getReadAllGroupsInWigiiNamespace();			
				if($readAllGroups!=null) $readAllGroups = array_keys($readAllGroups);
				$userEditRec->setFieldValue($readAllGroups, "getReadAllGroupsInWigiiNamespace");
				
				//$userEditRec->setFieldValue(($user->getDetail()->getRootGroupCreator($workingModule) ? true : null), "getRootGroupCreator");
				$rootGroupCreator = $user->getDetail()->getRootGroupCreator();
				if($rootGroupCreator!=null) $rootGroupCreator = array_keys($rootGroupCreator);
				$userEditRec->setFieldValue($rootGroupCreator, "getRootGroupCreator");
				
				//$userEditRec->setFieldValue(($user->getDetail()->getGroupCreator($workingModule) ? true : null), "getGroupCreator");
				$groupCreator = $user->getDetail()->getGroupCreator();
				if($groupCreator!=null) $groupCreator = array_keys($groupCreator);
				$userEditRec->setFieldValue($groupCreator, "getGroupCreator");
				
				$userEditRec->setFieldValue($user->getDetail()->getEmail(), "email");
				$userEditRec->setFieldValue($user->getDetail()->getDescription(), "description");
				$userEditRec->setFieldValue(($user->getDetail()->canModifyOwnPassword() ? true : null), "canModifyOwnPassword");
				$userEditRec->setFieldValue(($user->getDetail()->getPassword()!=null ? str_repeat("*", ($user->getDetail()->getPasswordLength() ? $user->getDetail()->getPasswordLength() : 4)): ""), "password");
				$userEditRec->setFieldValue($user->getDetail()->getAuthenticationMethod(), "authenticationMethod");
				$userEditRec->setFieldValue($user->getDetail()->getAuthenticationServer(), "authenticationServer");
			}
			// fills only WigiiNamespace for new user
			else {
				$userEditRec->setFieldValue($p->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
			}
		}
		
		$hasOneAdminRight =
			$p->isWigiiNamespaceCreator() ||
			$p->isModuleEditor() ||
			$p->isAdminCreator() ||
			$p->isUserCreator() ||
			$p->isReadAllUsersInWigiiNamespace() ||
			$p->getReadAllGroupsInWigiiNamespace()!=null ||
			$p->getRootGroupCreator()!=null ||
			$p->getGroupCreator()!=null
			;
		$userEditRec->getWigiiBag()->setHidden(!$hasOneAdminRight, "userAdminRights");
		$userEditRec->getWigiiBag()->setHidden(!$p->isWigiiNamespaceCreator(), "isWigiiNamespaceCreator");
		$userEditRec->getWigiiBag()->setHidden(!$p->isModuleEditor(), "isModuleEditor");
		$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator() || !$p->isReadAllUsersInWigiiNamespace(), "isReadAllUsersInWigiiNamespace");
		$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator(), "isAdminCreator");
		$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator(), "isUserCreator");
		$userEditRec->getWigiiBag()->setHidden($p->getReadAllGroupsInWigiiNamespace()==null, "getReadAllGroupsInWigiiNamespace");
		$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator() || $p->getRootGroupCreator()==null, "getRootGroupCreator");
		//$userEditRec->getWigiiBag()->setHidden(!$p->isAdminCreator() || $p->getGroupCreator()==null, "getGroupCreator"); /* always hidden */

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



