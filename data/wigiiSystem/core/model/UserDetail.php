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
 * Detail of a User
 * Accessible only if Principal has read access on this User
 * Created by CWE on 29 août 09
 */
class UserDetail extends Model
{
	private $userlabel; //username with additional info, like prefixed by wigiiNamespace if different than the current wigiiNamespace
	private $password;
	private $passwordHistory;
	private $passwordLength;
	private $passwordLife;
	private $passwordDate;
	private $description;
	private $email;

	private $authenticationMethod;
	private $authenticationServer;
	private $moduleAccess;
	private $userCreator;
	private $adminCreator;
	private $readAllUsersInWigiiNamespace;
	private $groupCreator;
	private $rootGroupCreator;
	private $readAllGroupsInWigiiNamespace;
	private $wigiiNamespaceCreator;
	private $moduleEditor;

	private $canModifyOwnPassword;

	private $info_lastLogin;
	private $info_nbLogin;
	private $info_lastFailedLogin;
	private $info_lastLogout;
	private $info_lastSessionContext;
	private $info_resetSessionContext;
	private $info_nbFailedLogin;

	public static function createInstance($array = null)
	{
		$u = new UserDetail();
		if(is_array($array)){
			if(isset($array["userlabel"])) $u->setUserlabel($array["userlabel"]);
			if(isset($array["password"])) $u->setPassword($array["password"]);
			elseif(isset($array["clearPassword"])) $u->setClearPassword($array["clearPassword"]);
			if(isset($array["passwordHistory"])) $u->setPasswordHistory($array["passwordHistory"]);
			if(isset($array["passwordLength"])) $u->setPasswordLength($array["passwordLength"]);
			if(isset($array["passwordLife"])) $u->setPasswordLife($array["passwordLife"]);
			if(isset($array["passwordDate"])) $u->setPasswordDate($array["passwordDate"]);
			if(isset($array["email"])) $u->setEmail($array["email"], 'value');
			if(isset($array["description"])) $u->setDescription($array["description"]);
			if(isset($array["authenticationServer"])) $u->setAuthenticationServer($array["authenticationServer"]);
			if(isset($array["authenticationMethod"])) $u->setAuthenticationMethod($array["authenticationMethod"]);

			if(isset($array["canModifyOwnPassword"])) $u->setCanModifyOwnPassword($array["canModifyOwnPassword"]);
			if(isset($array["moduleAccess"])) $u->setModuleAccess($array["moduleAccess"]);

			if(isset($array["userCreator"])) $u->setUserCreator($array["userCreator"]);
			if(isset($array["adminCreator"])) $u->setAdminCreator($array["adminCreator"]);
			if(isset($array["readAllUsersInWigiiNamespace"])) $u->setReadAllUsersInWigiiNamespace($array["readAllUsersInWigiiNamespace"]);
			if(isset($array["groupCreator"])) $u->setGroupCreator($array["groupCreator"]);
			if(isset($array["rootGroupCreator"])) $u->setRootGroupCreator($array["rootGroupCreator"]);
			if(isset($array["readAllGroupsInWigiiNamespace"])) $u->setReadAllGroupsInWigiiNamespace($array["readAllGroupsInWigiiNamespace"]);
			if(isset($array["wigiiNamespaceCreator"])) $u->setWigiiNamespaceCreator($array["wigiiNamespaceCreator"]);
			if(isset($array["moduleEditor"])) $u->setModuleEditor($array["moduleEditor"]);

			if(isset($array["info_nbLogin"])) $u->setInfo_nbLogin($array["info_nbLogin"]);
			if(isset($array["info_nbFailedLogin"])) $u->setInfo_nbFailedLogin($array["info_nbFailedLogin"]);
			if(isset($array["info_lastLogin"])) $u->setInfo_lastLogin($array["info_lastLogin"]);
			if(isset($array["info_lastSessionContext"])) $u->setInfo_lastSessionContext($array["info_lastSessionContext"]);
			if(isset($array["info_resetSessionContext"])) $u->setInfo_resetSessionContext($array["info_resetSessionContext"]);
			if(isset($array["info_lastLogout"])) $u->setInfo_lastLogout($array["info_lastLogout"]);
			if(isset($array["info_lastFailedLogin"])) $u->setInfo_lastFailedLogin($array["info_lastFailedLogin"]);
		}
		return $u;
	}

	// Generic accessor

	/**
	 * Returns the value of a user attribute given a fieldSelector
	 */
	public function getAttribute($fieldSelector)
	{
		if(is_null($fieldSelector)) throw new UserAdminServiceException("fieldSelector cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		if(is_object($fieldSelector)) {
			$fName = $fieldSelector->getFieldName();
			$subFName = $fieldSelector->getSubFieldName();
		}
		else {
			$fName = $fieldSelector;
			$subFName = null;
		}
		
		switch($fName)
		{
			case "password" : 				return $this->getPassword();
			case "passwordHistory" :		return $this->getPasswordHistory();
			case "passwordLength" :			return $this->getPasswordLength();
			case "passwordLife" : 			return $this->getPasswordLife();
			case "passwordDate" : 			return $this->getPasswordDate();
			case "email" :					return $this->getEmail($subFName);
			case "description" : 			return $this->getDescription();
			case "canModifyOwnPassword" :	return $this->canModifyOwnPassword();
			case "moduleAccess" : 			return $this->getModuleAccess();
			case "userCreator" : 			return $this->isUserCreator();
			case "adminCreator" : 			return $this->isAdminCreator();
			case "readAllUsersInWigiiNamespace" :return $this->isReadAllUsersInWigiiNamespace();
			case "groupCreator" : 			return $this->getGroupCreator();
			case "rootGroupCreator" :		return $this->getRootGroupCreator();
			case "readAllGroupsInWigiiNamespace":return $this->getReadAllGroupsInWigiiNamespace();
			case "wigiiNamespaceCreator" : 		return $this->isWigiiNamespaceCreator();
			case "moduleEditor" : 			return $this->isModuleEditor();
			case "authenticationMethod" : 	return $this->getAuthenticationMethod();
			case "authenticationServer" : 	return $this->getAuthenticationServer();
			case "needsAdminAccess" :		return $this->needsAdminAccess();
			case "info_lastLogin" : 		return $this->getInfo_lastLogin();
			case "info_nbLogin" :			return $this->getInfo_nbLogin();
			case "info_lastFailedLogin" : 	return $this->getInfo_lastFailedLogin();
			case "info_nbFailedLogin" : 	return $this->getInfo_nbFailedLogin();
			case "info_lastLogout" : 		return $this->getInfo_lastLogout();
			case "info_lastSessionContext": return $this->getInfo_lastSessionContext();
			case "info_resetSessionContext":return $this->getInfo_resetSessionContext();
			default: throw new UserAdminServiceException("invalid user attribute $fName in field selector", UserAdminServiceException::INVALID_ARGUMENT);
		}
	}

	/**
	 * Sets the value of a user attribute given a fieldSelector
	 */
	public function setAttribute($value, $fieldSelector)
	{
		if(is_null($fieldSelector)) throw new UserAdminServiceException("fieldSelector cannot be null", UserAdminServiceException::INVALID_ARGUMENT);
		if(is_object($fieldSelector)) $fName = $fieldSelector->getFieldName();
		else $fName = $fieldSelector;
		$subFName = $fieldSelector->getSubFieldName();
		switch($fName)
		{
			case "password" : 				return $this->setPassword($value);
			case "passwordHistory" :		return $this->setPasswordHistory($value);
			case "passwordLength" :			return $this->setPasswordLength($value);
			case "passwordLife" : 			return $this->setPasswordLife($value);
			case "passwordDate" : 			return $this->setPasswordDate($value);
			case "description" : 			return $this->setDescription($value);
			case "email" :					return $this->setEmail($value, $subFName);
			case "canModifyOwnPassword" :	return $this->setCanModifyOwnPassword($value);
			case "moduleAccess" : 			return $this->setModuleAccess($value);
			case "userCreator" : 			return $this->setUserCreator($value);
			case "adminCreator" : 			return $this->setAdminCreator($value);
			case "readAllUsersInWigiiNamespace" :return $this->setReadAllUsersInWigiiNamespace($value);
			case "groupCreator" : 			return $this->setGroupCreator($value);
			case "rootGroupCreator" :		return $this->setRootGroupCreator($value);
			case "readAllGroupsInWigiiNamespace":return $this->setReadAllGroupsInWigiiNamespace($value);
			case "wigiiNamespaceCreator" : 		return $this->setWigiiNamespaceCreator($value);
			case "moduleEditor" : 			return $this->setModuleEditor($value);
			case "authenticationMethod" : 	return $this->setAuthenticationMethod($value);
			case "authenticationServer" : 	return $this->setAuthenticationServer($value);
			case "info_lastLogin" : 		return $this->setInfo_lastLogin($value);
			case "info_nbLogin" :			return $this->setInfo_nbLogin($value);
			case "info_lastFailedLogin" : 	return $this->setInfo_lastFailedLogin($value);
			case "info_nbFailedLogin" : 	return $this->setInfo_nbFailedLogin($value);
			case "info_lastLogout" : 		return $this->setInfo_lastLogout($value);
			case "info_lastSessionContext": return $this->setInfo_lastSessionContext($value);
			case "info_resetSessionContext":return $this->setInfo_resetSessionContext($value);
			default: throw new UserAdminServiceException("invalid user attribute $fName in field selector", UserAdminServiceException::INVALID_ARGUMENT);
		}
	}

	// Attributes

	public function getUserlabel()
	{
		return $this->userlabel;
	}
	public function getPassword()
	{
		return $this->password;
	}
	public function getPasswordHistory()
	{
		return $this->passwordHistory;
	}
	public function getPasswordLength()
	{
		return $this->passwordLength;
	}
	public function getPasswordLife()
	{
		return $this->passwordLife;
	}
	public function getPasswordDate()
	{
		return $this->passwordDate;
	}
	public function getDescription()
	{
		return $this->description;
	}
	/**
	 * Gets the User email
	 * @param String $subFieldName email subfield name. If not defined, uses value subfield.
	 */
	public function getEmail($subFieldName=null) {
		if(isset($this->email)) return $this->email[($subFieldName)?$subFieldName:'value'];
		else return null;
	}
	public function getAuthenticationMethod()
	{
		return $this->authenticationMethod;
	}
	public function getAuthenticationServer()
	{
		return $this->authenticationServer;
	}
	public function getModuleAccess($moduleName = null)
	{
		if($this->moduleAccess == null) return null;
		if($moduleName != null){
			if(is_object($moduleName)) $moduleName = $moduleName->getModuleName();
			return $this->moduleAccess[$moduleName];
		}
		return $this->moduleAccess;
	}
	public function isUserCreator()
	{
		return $this->userCreator;
	}
	public function isAdminCreator()
	{
		return $this->adminCreator;
	}
	public function isReadAllUsersInWigiiNamespace()
	{
		return $this->readAllUsersInWigiiNamespace;
	}
	public function getGroupCreator($moduleName = null)
	{
		if($this->groupCreator == null) return null;
		if($moduleName != null){
			if(is_object($moduleName)) $moduleName = $moduleName->getModuleName();
			return $this->groupCreator[$moduleName];
		}
		return $this->groupCreator;
	}
	public function getRootGroupCreator($moduleName = null)
	{
		if($this->rootGroupCreator == null) return null;
		if($moduleName != null){
			if(is_object($moduleName)) $moduleName = $moduleName->getModuleName();
			return $this->rootGroupCreator[$moduleName];
		}
		return $this->rootGroupCreator;
	}
	public function getReadAllGroupsInWigiiNamespace($moduleName = null)
	{
		if($this->readAllGroupsInWigiiNamespace == null) return null;
		if($moduleName != null){
			if(is_object($moduleName)) $moduleName = $moduleName->getModuleName();
			return $this->readAllGroupsInWigiiNamespace[$moduleName];
		}
		return $this->readAllGroupsInWigiiNamespace;
	}
	public function isWigiiNamespaceCreator()
	{
		return $this->wigiiNamespaceCreator;
	}
	public function isModuleEditor()
	{
		return $this->moduleEditor;
	}
	/**
	 * Returns true if User needs access to Admin module based on its rights
	 * Admin module without having any group with X make no sense. so only RooGroupCreator
	 * will ask admin module, because potentialy the user could have no X rights on groups in this module.
	 */
	public function needsAdminAccess()
	{
		return
			$this->isUserCreator() ||
			$this->isAdminCreator() ||
			$this->isReadAllUsersInWigiiNamespace() ||
//			$this->getGroupCreator() ||
			$this->getRootGroupCreator() ||
//			$this->getReadAllGroupsInWigiiNamespace() ||
			$this->isWigiiNamespaceCreator() ||
			$this->isModuleEditor();
	}

	/**
	 * return true if user has at least one admin level for the console
	 */
	public function hasAdminLevelForConsole()
	{
		return
		$this->isUserCreator() ||
		$this->isAdminCreator() ||
		$this->isReadAllUsersInWigiiNamespace() ||
		$this->getRootGroupCreator() ||
		$this->isWigiiNamespaceCreator() ||
		$this->isModuleEditor();
	}
	
	/**
	 * return true if user has at least one admin level
	 */
	public function hasAdminLevel()
	{
		return
			$this->isUserCreator() ||
			$this->isAdminCreator() ||
			$this->isReadAllUsersInWigiiNamespace() || //this is not really an admin level
			$this->getGroupCreator() ||
			$this->getRootGroupCreator() ||
			//$this->getReadAllGroupsInWigiiNamespace() || //this is not really an admin level
			$this->isWigiiNamespaceCreator() ||
			$this->isModuleEditor();
	}

	/**
	 * @param $fullModuleAccess will be the moduleAccess used when wigiiNamespace creator or moduleEditor
	 * $intersectWithFullModuleAccess = false, if true the groupCreator etc are intersected with it. This should be done only from the SuperAdmin
	 */
	public function calculateAdminLevelRights($fullModuleAccess, $intersectWithFullModuleAccess=false){
		if($this->isWigiiNamespaceCreator()){
			$this->setReadAllUsersInWigiiNamespace(true);
			$this->setModuleEditor(true);
			$this->setAdminCreator(true);
			$this->setUserCreator(true);
			$this->setModuleAccess($fullModuleAccess);
			unset($fullModuleAccess[Module::ADMIN_MODULE]);
			$this->setReadAllGroupsInWigiiNamespace($fullModuleAccess);
			$this->setRootGroupCreator($fullModuleAccess);
			$this->setGroupCreator($fullModuleAccess);
		} else {
			if($this->isModuleEditor()){
				$this->setAdminCreator(true);
				unset($fullModuleAccess[Module::ADMIN_MODULE]);
				/*
				if($this->getRootGroupCreator()==null){
					$this->setRootGroupCreator($fullModuleAccess);
				} else {
					$this->setRootGroupCreator(array_merge($this->getRootGroupCreator(), $fullModuleAccess));
				}
				if($this->getReadAllGroupsInWigiiNamespace()==null){
					$this->setReadAllGroupsInWigiiNamespace($fullModuleAccess);
				} else {
					$this->setReadAllGroupsInWigiiNamespace(array_merge($this->getReadAllGroupsInWigiiNamespace(), $fullModuleAccess));
				}
				*/
			}
			if($intersectWithFullModuleAccess){
				if($this->getRootGroupCreator()!=null){
					$this->setRootGroupCreator(array_intersect_key($fullModuleAccess, $this->getRootGroupCreator()));
				}
			}
			if($intersectWithFullModuleAccess){
				if($this->getReadAllGroupsInWigiiNamespace()!=null){
					$this->setReadAllGroupsInWigiiNamespace(array_intersect_key($fullModuleAccess, $this->getReadAllGroupsInWigiiNamespace()));
				}
			}
			if($this->isAdminCreator()){
				//force user creator if admin creator
				$this->setUserCreator(true);
			}
			if($this->isUserCreator()){
				//force readAllUsersInWigiiNamespace if user creator
				$this->setReadAllUsersInWigiiNamespace(true);
			}
			if($this->getRootGroupCreator()!=null){
				//force group creator if rootGroupCreator
				if($this->getGroupCreator()==null){
					$this->setGroupCreator($this->getRootGroupCreator());
				} else {
					$this->setGroupCreator(array_merge($this->getRootGroupCreator(), $this->getGroupCreator()));
				}
			}
		}
	}

	/**
	 * returns a merge of each module found in admin rights defined per module
	 */
	public function getModuleAccessOnGroupAdminLevelRights(){
		$moduleAccess = array();
		//group creator is not giving access to the module. only UGR Admin + group creator gives access
		//if($this->getGroupCreator()) $moduleAccess = array_merge($moduleAccess, $this->getGroupCreator());
		if($this->getRootGroupCreator()) $moduleAccess = array_merge($moduleAccess, $this->getRootGroupCreator());
		if($this->getReadAllGroupsInWigiiNamespace()) $moduleAccess = array_merge($moduleAccess, $this->getReadAllGroupsInWigiiNamespace());
		return $moduleAccess;
	}
	public function canModifyOwnPassword()
	{
		return $this->canModifyOwnPassword;
	}
	public function getInfo_lastLogin()
	{
		return $this->info_lastLogin;
	}
	public function getInfo_nbLogin()
	{
		return $this->info_nbLogin;
	}
	public function getInfo_lastFailedLogin()
	{
		return $this->info_lastFailedLogin;
	}
	public function getInfo_lastLogout()
	{
		return $this->info_lastLogout;
	}
	public function getInfo_lastSessionContext()
	{
		return $this->info_lastSessionContext;
	}
	public function getInfo_resetSessionContext()
	{
		return $this->info_resetSessionContext;
	}
	public function getInfo_nbFailedLogin()
	{
		return $this->info_nbFailedLogin;
	}

	public function setUserlabel($var)
	{
		$this->userlabel = $this->formatValue($var);
	}
	public function setPassword($var)
	{
		$this->password = $this->formatValue($var);
	}
	public function setPasswordHistory($var)
	{
		$this->passwordHistory = $this->formatValue($var);
	}
	public function setPasswordLength($var)
	{
		$this->passwordLength = $this->formatValue($var);
	}
	public function setPasswordLife($var)
	{
		$this->passwordLife = $this->formatValue($var);
	}
	public function setPasswordDate($var)
	{
		$this->passwordDate = $this->formatValue($var);
	}
	/**
	 * Hashes given clear password and sets it
	 */
	public function setClearPassword($var)
	{
		if(!defined('PASSWORD_minLength')) define ('PASSWORD_minLength', 3);
		if(!defined('PASSWORD_maxLength')) define ('PASSWORD_maxLength', 32);
		ArgValidator::assertBoundedString('password length must be between '.PASSWORD_minLength.' and '.PASSWORD_maxLength,
											$var, PASSWORD_minLength, PASSWORD_maxLength, UserAdminServiceException::INVALID_PASSWORD);
		$md5 = md5($var);
		if($md5 != $this->getPassword()){
			$this->setPasswordLength(strlen($var));
			if($this->canModifyOwnPassword() || $this->getPasswordDate()==null){
				$this->setPasswordDate(time()); //the date is set the first time but then never more if cannot change password (this is to keep the user expiration even with external authentication)
			}
			$this->setPasswordHistory($md5.";".$this->getPasswordHistory());
		}
		$this->setPassword(md5($var));
	}
	public function setDescription($var)
	{
		$this->description = $this->formatValue($var);
	}
	/**
	 * Sets the User email
	 * @param String $value email value or email subfield value
	 * @param String $subFieldName email subfield name. If not defined, uses value subfield.
	 */
	public function setEmail($value, $subFieldName=null) {
		if(!isset($this->email)) $this->email = array();
		$this->email[($subFieldName)?$subFieldName:'value'] = $value;		
		if($subFieldName == null || $subFieldName == 'value') {		
			$newProofKey = ServiceProvider::getElementService()->getEmailValidationCode($p, $value);
			$this->email['proofKey'] = $newProofKey;
			$this->email['proof'] = null;
			$this->email['proofStatus'] = 0;
		}
	}
	public function setAuthenticationMethod($var)
	{
		$this->authenticationMethod = $this->formatValue($var);
	}
	public function setAuthenticationServer($var)
	{
		$this->authenticationServer = $this->formatValue($var);
	}
	public function setModuleAccess($var)
	{
		$this->moduleAccess = $var;
	}
	public function setUserCreator($var)
	{
		$this->userCreator = $this->formatBoolean($var);
	}
	public function setAdminCreator($var)
	{
		$this->adminCreator = $this->formatBoolean($var);
	}
	public function setReadAllUsersInWigiiNamespace($var)
	{
		$this->readAllUsersInWigiiNamespace = $this->formatBoolean($var);
	}
	public function setGroupCreator($var)
	{
		$this->groupCreator = $var;
	}
	public function setRootGroupCreator($var)
	{
		$this->rootGroupCreator = $var;
	}
	public function setReadAllGroupsInWigiiNamespace($var)
	{
		$this->readAllGroupsInWigiiNamespace = $var;
	}
	public function setWigiiNamespaceCreator($var)
	{
		$this->wigiiNamespaceCreator = $this->formatBoolean($var);
	}
	public function setModuleEditor($var)
	{
		$this->moduleEditor = $this->formatBoolean($var);
	}
	public function setCanModifyOwnPassword($var)
	{
		$this->canModifyOwnPassword = $this->formatBoolean($var);
	}
	public function setInfo_lastLogin($var)
	{
		$this->info_lastLogin = $this->formatValue($var);
	}
	public function setInfo_nbLogin($var)
	{
		$this->info_nbLogin = $this->formatValue($var);
	}
	public function setInfo_lastFailedLogin($var)
	{
		$this->info_lastFailedLogin = $this->formatValue($var);
	}
	public function setInfo_lastLogout($var)
	{
		$this->info_lastLogout = $this->formatValue($var);
	}
	public function setInfo_lastSessionContext($var)
	{
		$this->info_lastSessionContext = $var;
	}
	public function setInfo_resetSessionContext($var)
	{
		$this->info_resetSessionContext = $this->formatBoolean($var);
	}
	public function setInfo_nbFailedLogin($var)
	{
		$this->info_nbFailedLogin = $this->formatValue($var);
	}

	public function passwordExpired(){
		if($this->getPasswordLife() != null && $this->getPasswordDate() != null && (($this->getPasswordDate()+$this->getPasswordLife()*3600*24) < time()) ){
			return true;
		} else {
			return false;
		}
	}
}