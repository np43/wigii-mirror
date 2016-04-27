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
 * Created on 15 sept. 09
 * by LWR
 */
class DetailUserFormExecutor extends FormExecutor {

	private $userP;
	protected function setUserP($userP){ $this->userP = $userP; }
	protected function getUserP(){ return $this->userP; }

	private $module;
	protected function setModule($module){ $this->module = $module; }
	protected function getModule(){ return $this->module; }

	public static function createInstance($wigiiExecutor, $module, $userP, $record, $formId, $submitUrl){
		$fe = new self();
		$fe->setWigiiExecutor($wigiiExecutor);
		$fe->setUserP($userP);
		$fe->setModule($module);
		$fe->setFormId($formId);
		$fe->setSubmitUrl($submitUrl);
		$fe->setRecord($record);
		return $fe;
	}

	protected function doSpecificCheck($p, $exec){}

	protected function actOnCheckedRecord($p, $exec){}

	protected function doRenderForm($p, $exec){
		$transS = ServiceProvider::getTranslationService();
		$workingModule = $this->getWigiiExecutor()->getAdminContext($p)->getWorkingModule();
		$config = $this->getWigiiExecutor()->getConfigurationContext(); //ServiceProvider::getConfigService();
		$userAS = ServiceProvider::getUserAdminService();

		$userP = $this->getUserP();
		$user = $userP->getUser();
		$record = $this->getRecord();

		//check moduleAccess
		$hasNoAccessReason = array();
		$moduleAccess = $user->getDetail()->getModuleAccess();
		if($moduleAccess !=null){
			$result = array();
			foreach($moduleAccess as $module){
				$temp = '<font style="font-weight:bold;">';
				$temp .= $transS->t($p, $module->getModuleName());
				$temp .= '</font>';
				$result[] = $temp;
			}
			$record->setFieldValue(implode(", ", $result), "moduleAccess");
		} else {
			if($user->isRole()){
				$temp = '<font style="font-weight:bold;" class="R">';
				$temp .= $transS->t($p, "noModuleAccess");
				$temp .= '</font>';
				$record->setFieldValue($temp, "moduleAccess");
			}
		}
		//check roleAccess
		$roleList = UserListArrayImpl::createInstance();
		$userAS->getAllUserRoles($p, $user->getId(), $roleList, $userAS->getListFilterForRoleList());
		if(!$roleList->isEmpty()){
			$roles = array();
			foreach($roleList->getListIterator() as $role){
				$temp = "";
				$temp .= '<font style="font-weight:bold;" ';
				if($user->isRole()) $temp .= ' class="R" ';
				$temp .= '>';
				$temp .= $role->getUsername();
				$temp .= '</font>';
				$roles[] = $temp;
			}
			$record->setFieldValue(implode(", ", $roles), "roleAccess");
		} else {
			if(!$user->isRole()){
				$temp = "";
				$temp .= '<font style="font-weight:bold;" class="R">';
				$temp .= $transS->t($p, "noRoleAccess");
				$temp .= '</font>';
				$record->setFieldValue($temp, "roleAccess");
			}
		}
		//check if users are using the role
		if($user->isRole()){
			$userList = UserListArrayImpl::createInstance();
			$userAS->getAllRoleUsers($p, $user->getId(), $userList, $userAS->getListFilterForRoleToCalculateCalculatedRoleList());
			if(!$userList->isEmpty()){
				$users = array();
				foreach($userList->getListIterator() as $tuser){
//					$temp = "";
//					$temp .= '<font style="font-weight:bold;">';
//					$temp .= ($tuser->getWigiiNamespace()->getWigiiNamespaceName()!=null && $tuser->getWigiiNamespace()->getWigiiNamespaceName()!=$p->getWigiiNamespace()->getWigiiNamespaceName() ? $tuser->getWigiiNamespace()->getWigiiNamespaceName()." : " : "").$tuser->getUsername();
//					$temp .= '</font>';
//					$users[] = $temp;
					//since 13/02/2015 users are listed just with their username, in order to be able to do a quick copy paste of the list for another role.
					$users[] = $tuser->getUsername();
				}
				$record->setFieldValue(implode(", ", $users), "userAllocation");
			} else {
				$temp = '<font style="font-weight:bold;" class="R">';
				$temp .= $transS->t($p, "noUserAllocated");
				$temp .= '</font>';
				$record->setFieldValue($temp, "userAllocation");
			}
		}




		//check password expiration
		if($user->getDetail()->passwordExpired()){
			if($user->isRole()) $hasNoAccessReason[] = $transS->t($p, "roleExpiredNoAccess");
			if(!$user->getDetail()->canModifyOwnPassword())$hasNoAccessReason[] = $transS->t($p, "userExpiredNoAccess");
		}

		if($moduleAccess == null && $roleList->isEmpty()){
			$hasNoAccessReason[] = $transS->t($p, "noAccessDefined");
		}

		if($user->isRole()==true || $hasNoAccessReason!=null){
			echo '<fieldset class="isPlayingRole ui-corner-all" style="'.($hasNoAccessReason!=null ? "border-color:#f00;" : "").'" ><legend class="ui-corner-all" style="'.($hasNoAccessReason!=null ? "background-color:#f00;" : "").'" >'.($user->isRole()===true ? $transS->t($p, "isRole")." " : "").($hasNoAccessReason!=null ? implode(", ", $hasNoAccessReason) : "").'</legend>';
		}
		$record->setFieldValue($user->getWigiiNamespace()->getWigiiNamespaceName(), "wigiiNamespace");
		$record->setFieldValue($user->getUsername(), "username");
		$record->setFieldValue(($user->getDetail()->canModifyOwnPassword() ? true : null), "canModifyOwnPassword");
		$record->setFieldValue(($user->getDetail()->getPasswordLife()!=null ? $user->getDetail()->getPasswordLife() : null), "passwordLife"); //$transS->t($p, "neverExpires")
		if($user->getDetail()->getPasswordDate() != null){
			$record->setFieldValue(($user->getDetail()->passwordExpired() ? '<font style="font-weight:bold;" class="R">'.$transS->t($p, "expired").": ".'</font>': "").date("d.m.Y", $user->getDetail()->getPasswordDate()), "passwordDate");
		}
		if($user->getDetail()->isWigiiNamespaceCreator()) $record->setFieldValue(true, "isWigiiNamespaceCreator");
		if($user->getDetail()->isModuleEditor()) $record->setFieldValue(true, "isModuleEditor");
		if($user->getDetail()->isReadAllUsersInWigiiNamespace()) $record->setFieldValue(true, "isReadAllUsersInWigiiNamespace");
		if($user->getDetail()->isAdminCreator()) $record->setFieldValue(true, "isAdminCreator");
		if($user->getDetail()->isUserCreator()) $record->setFieldValue(true, "isUserCreator");
		$moduleAccess = $user->getDetail()->getReadAllGroupsInWigiiNamespace();
		if($moduleAccess !=null){
			$result = array();
			foreach($moduleAccess as $module){
				$result[] = $transS->t($p, $module->getModuleName());
			}
			$record->setFieldValue(implode(", ", $result), "getReadAllGroupsInWigiiNamespace");
		} else {
//			$temp = $transS->t($p, "no");
//			$record->setFieldValue($temp, "getReadAllGroupsInWigiiNamespace");
		}
		$moduleAccess = $user->getDetail()->getRootGroupCreator();
		if($moduleAccess !=null){
			$result = array();
			foreach($moduleAccess as $module){
				$result[] = $transS->t($p, $module->getModuleName());
			}
			$record->setFieldValue(implode(", ", $result), "getRootGroupCreator");
		} else {
//			$temp = $transS->t($p, "no");
//			$record->setFieldValue($temp, "getRootGroupCreator");
		}
		$moduleAccess = $user->getDetail()->getGroupCreator();
		if($moduleAccess !=null){
			$result = array();
			foreach($moduleAccess as $module){
				$result[] = $transS->t($p, $module->getModuleName());
			}
			$record->setFieldValue(implode(", ", $result), "getGroupCreator");
		} else {
//			$temp = $transS->t($p, "no");
//			$record->setFieldValue($temp, "getGroupCreator");
		}
		$record->setFieldValue($user->getDetail()->getDescription(), "description");


		//ConfigEdit
		if($config->doesUserHasConfigFile($p, $this->getModule(), $user)){
			$record->setFieldValue(true, "userDetailConfigEdit");
			$exec->addJsCode("$('#detailUser_form_userDetailConfigEdit input').click(function(){ update('elementDialog/".$exec->getCrtWigiiNamespace()->getWigiiNamespaceUrl()."/".$exec->getCrtModule()->getModuleUrl()."/userConfigEdit/".$workingModule->getModuleUrl()."/'+adminUser_crtSelectedUser); });");
		}

		if(!$user->isRole() || $user->getDetail()->getAuthenticationMethod()!="usual"){
			$record->setFieldValue($user->getDetail()->getAuthenticationMethod(), "authenticationMethod");
		}
		$record->setFieldValue($user->getDetail()->getAuthenticationServer(), "authenticationServer");
		$record->setFieldValue(($user->getDetail()->getInfo_lastLogin()!=null ? date("d.m.Y H:i:s", $user->getDetail()->getInfo_lastLogin()):null), "info_lastLogin");
		$record->setFieldValue($user->getDetail()->getInfo_nbLogin(), "info_nbLogin");
//		$record->setFieldValue($transS->t($p, $user->getDetail()->getInfo_lastContext("lastModule")->getModuleName()), "info_lastModule");
		$record->setFieldValue(($user->getDetail()->getInfo_lastFailedLogin()!=null ? date("d.m.Y H:i:s", $user->getDetail()->getInfo_lastFailedLogin()):null), "info_lastFailedLogin");
		$record->setFieldValue($user->getDetail()->getInfo_nbFailedLogin(), "info_nbFailedLogin");

		$record->setFieldValue($user->getSys_creationUsername(), "sys_creationUsername");
		$record->setFieldValue($user->getSys_username(), "sys_username");
		$record->setFieldValue($user->getSys_date(), "sys_date");
		$record->setFieldValue($user->getSys_creationDate(), "sys_creationDate");

		$this->getTrm()->setDetailRenderer($this->getDetailRenderer()->setP($p));
		$this->getTrm()->displayRemainingDetails();

		if($user->isRole()===true || $hasNoAccessReason!=null){
			echo '</fieldset> ';
		}

	}
}



