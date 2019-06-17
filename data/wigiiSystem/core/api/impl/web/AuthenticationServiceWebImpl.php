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

/*
 * Created on 30 juil. 09
 * by LWR
 */

class AuthenticationServiceWebImpl extends AuthenticationServiceImpl {

	private $_debugLogger;
	private $_executionSink;
	private $crtClientName;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("AuthenticationServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("AuthenticationServiceWebImpl");
		}
		return $this->_executionSink;
	}

	/**
	 * Constructs the AuthenticationServiceWebImpl instance
	 * potentially registers a list of predefined principals.
	 */
	public function __construct($crtClientName, $principalList = null)
	{
		$this->setCrtClientName($crtClientName);
		$this->debugLogger()->write("creating instance for client: ".$crtClientName);
		$this->registerPrincipals($principalList);
	}

	protected function setCrtClientName($crtClientName){
		$this->crtClientName = $crtClientName;
	}
	protected function getCrtClientName(){
		return $this->crtClientName;
	}

	/**
	 * Main principal
	 * added the CrtClientName in the creation of the MinimalPrincipal
	 */
	public function getMainPrincipal(){
		if(!isset($this->mainPrincipal)){
			//first look in sessionAdminService
			if($this->isSessionCacheEnabled()){
				$sessMainP = $this->getSessionAdminService()->getData($this,"mainPrincipal");
				if(isset($sessMainP)){
					$this->mainPrincipal = $sessMainP;
					$this->registerPrincipal($sessMainP);
					$this->getSessionAdminService()->storeData($this,"mainPrincipal", $this->mainPrincipal);
					$this->debugLogger()->write("MainPrincipal: ".$sessMainP->getUsername()." set from session");
				}
			}
			//if still no mainPrincipal then create a minimalPrincipal
			if(!isset($this->mainPrincipal)){
				$minPrinc = $this->createMinimalPrincipal($this->getCrtClientName());
				$this->registerPrincipal($minPrinc);
				$this->mainPrincipal = $minPrinc;
				$this->getSessionAdminService()->storeData($this,"mainPrincipal", $this->mainPrincipal);
				$this->debugLogger()->write("set MainPrincipal as minimal");
			}
		} else {
				$this->debugLogger()->write("get MainPrincipal as ".$this->mainPrincipal->getUsername());
		}
		return $this->mainPrincipal;
	}
//	public function getMainPrincipal(){
//		if(!isset($this->mainPrincipal)){
//			//first look in sessionAdminService
//			if($this->isSessionCacheEnabled()){
//				$sessMainP = $this->getSessionAdminService()->getData($this,"mainPrincipal");
//				if(isset($sessMainP)){
//					$this->registerPrincipal($sessMainP);
//					$this->mainPrincipal = $sessMainP;
//					$this->getSessionAdminService()->storeData($this,"mainPrincipal", $this->mainPrincipal);
//					$this->debugLogger()->write("MainPrincipal: ".$sessMainP->getUsername()." set from session");
//				}
//			}
//			//if still no mainPrincipal then create a minimalPrincipal
//			if(!isset($this->mainPrincipal)){
//				$minPrinc = $this->createMinimalPrincipal($this->getCrtClientName());
//				$this->registerPrincipal($minPrinc);
//				$this->mainPrincipal = $minPrinc;
//				$this->getSessionAdminService()->storeData($this,"mainPrincipal", $this->mainPrincipal);
//				$this->debugLogger()->write("set MainPrincipal as minimal");
//			}
//		}
//		return $this->mainPrincipal;
//	}

	/**
	 * Returns an minimal principal. This is normally used to replace
	 * the mainPrincipal with an empty one
	 */
	protected function createMinimalPrincipal($clientName=MANDATORY_ARG){
		$p = Principal::createInstanceFromArray(array(
			"username"=>"MinimalPrincipal",
			"wigiiNamespace"=>$this->getWigiiNamespaceAdminService()->getEmptyWigiiNamespaceForClient($this->getAuthenticationServicePrincipal(), $this->getClientAdminService()->getClient($this->getAuthenticationServicePrincipal(), $clientName))
		));
		return $p;
	}

	//remove the condition of empty wigiiNamespace
	public function isMainPrincipalMinimal(){
		$p = $this->getMainPrincipal();
		return (
			$p->getUsername() == "MinimalPrincipal" &&
			$p->getAttachedUser() == null
//			&& $p->getWigiiNamespace()->getWigiiNamespaceName() == null
			);
	}

	/**
	 * *********************
	 * AUTO LOGIN MANAGEMENT
	 * *********************
	 */
	public function getCredentialCookieName(){
		return "c_WigiiCredentials";
	}

	public function getJsCodeToSetWigiiCredentialCookie($p){
		//store login credential in cookie
		$expiration = $this->getConfigService()->getParameter($p, null, "wigiiCredentialCookieLifeTime");
		if($expiration == null) return null;

		if($p->getRealUser()!=null){
			$userCred = $p->getRealUser();
		} else {
			$userCred = $p->getAttachedUser();
		}
		if($userCred == null) return null; //no attached user

		$time = intToString(time(), 15);
		$passMd5 = $userCred->getDetail()->getPassword();
		$username = $userCred->getUsername();
		$rot = strlen($username);
		$result = strRot("wigiiV4", $rot).strRot($time, $rot).strRot($passMd5, $rot).strRot($username, $rot);

		$expiration = 365*10; //the expiration of the cookie is indertemined, because the real time validation is done in the isWigiiCookieValid
		return "$.cookie('".$this->getCredentialCookieName()."', '".$result."', { path: '/', expires: $expiration, secure: ".strtolower(put(HTTPS_ON))." }); ";
	}

	public function getJsCodeToUnsetWigiiCredentialCookie($p){
		return 	"$.cookie('".$this->getCredentialCookieName()."', '', { path: '/', expires: -1, secure: ".strtolower(put(HTTPS_ON))." }); ";
	}

	protected function getWigiiCredentialCookie(){
		if($this->isWigiiCredentialCookieValid()){
			$cookie = $_COOKIE[$this->getCredentialCookieName()];
			$passMd5 = substr($cookie, 22, 32);
			$username = substr($cookie, 54);
			$rot = -strlen($username);
			$username = strRot($username, $rot);
			$passMd5 = strRot($passMd5, $rot);
		} else {
			$passMd5 = null;
			$username = null;
		}
//		fput($username." ".$passMd5);
		return array($username, $passMd5);
	}

	public function isWigiiCredentialCookieValid(){
		$cookie = $_COOKIE[$this->getCredentialCookieName()];
		if($cookie == null) return null;
		$expiration = $this->getWigiiCredentialCookieExpiration();
		if($expiration == null) return null;
		$time = substr($cookie, 7, 15);
		$username = substr($cookie, 54);
		$time = strRot($time, -strlen($username));

//		eput($time." ".strRot($username, -strlen($username))." ".$expiration." ".($time + ($expiration*60*60*24))." ".time());
		if(($time + ($expiration*60*60*24)) < time()){
			return false;
		}
		return true;
	}

	public function getWigiiCredentialCookieExpiration(){
		return $this->getConfigService()->getParameter($this->getMainPrincipal(), null, "wigiiCredentialCookieLifeTime");
	}

	/**
	 * try to autoLogin with cookie information
	 * works only if cookie is valid and main principal is minimal
	 * return true on success, else return false. no exception is thrown
	 */
	public function autoLogin(){

		if(!$this->isMainPrincipalMinimal()) return false;

		list($username, $md5Pass) = $this->getWigiiCredentialCookie();
		if($username == null) return false;

		$p = $this->getAuthenticationServicePrincipal();
		$client = $this->getClientAdminService()->getClient($p, $this->getCrtClientName());
		$user = $this->getUserAdminService()->findUserForClient($p, $username, $client);
		//if no user with this name si found
		if(!isset($user)) return false; //throw new AuthenticationServiceException('User '.$username." doesn't exist for client ".$client->getClientName(), AuthenticationServiceException::FORBIDDEN);
		$user = $user->getUser(); // retrieves User from UserP.
		//check if the user is blacklisted
		if(!$this->isNotBlacklisted($user)) return false; //throw new AuthenticationServiceException('User '.$username." is unauthorized in Admin config file for client ".$client->getClientName(), AuthenticationServiceException::FORBIDDEN);
		//check if md5 is equal to password:
		if($user->getDetail()->getPassword()!=$md5Pass) return false;
		//check if password match minimum length
		if(!defined('PASSWORD_minLength')) define ('PASSWORD_minLength', 8);
		if(!defined('PASSWORD_maxLength')) define ('PASSWORD_maxLength', 32);
		if($user->getDetail()->getPasswordLength()>PASSWORD_maxLength || $user->getDetail()->getPasswordLength()<PASSWORD_minLength) return false;
		$returnValue = $this->createPrincipalInstance($user);
		//check if user is not expired
		if($returnValue->passwordExpired()) return false;
		$userd = $returnValue->getAttachedUser()->getDetail();
		$userd->setInfo_nbFailedLogin(0);
		$userd->setInfo_lastFailedLogin(null);
		$userd->setInfo_lastLogin(time());
		$userd->setInfo_nbLogin($userd->getInfo_nbLogin()+1);
		//store in DB the user login informations
		$this->getUserAdminService()->persistUserLoginInformation($p, $returnValue->getAttachedUser());
		$this->debugLogger()->write("******AUTO LOGIN SUCCEEDED****** ".$returnValue->getUsername()." authenticated in wigiiNamespace ".$returnValue->getWigiiNamespace()->getWigiiNamespaceName()." for client ".$returnValue->getWigiiNamespace()->getClient()->getClientName());
		//register new principal
		$this->registerPrincipal($returnValue);
		$this->setMainPrincipal($returnValue);

		//set last language
		$lastLanguage = $returnValue->getValueInGeneralContext("language");
		if($lastLanguage != null) ServiceProvider::getTranslationService()->setLanguage($lastLanguage);

		//calculate merged roles
		$this->getUserAdminService()->calculateAllMergedRoles($returnValue);

		return true;
	}

	/**
	 * try to autoLogin with a public user for the current client
	 * works only if main principal is minimal and public access is enabled
	 * return true on success, else return false. no exception is thrown
	 */
	public function autoLoginAsPublic() {
		if(!$this->isMainPrincipalMinimal()) return false;
		if($this->isPublicAccessEnabledForClient($this->getCrtClientName())) {
			try {
				$p = $this->loginAsPublic($this->getCrtClientName());
				//calculate merged roles
				$this->getUserAdminService()->calculateAllMergedRoles($p);
			}
			catch(Exception $e) {
				return false;
			}
			return true;
		}
		else return false;
	}
	
	/**
	 * Returns true if public login is enabled for the given client
	 * @param String $clientName the client name
	 * @return boolean 
	 */
	public function isPublicAccessEnabledForClient($clientName) {
		$this->executionSink()->publishStartOperation("isPublicAccessEnabledForClient");
		$returnValue = false;
		try
		{
			$client = $this->getClientAdminService()->getClient($this->getAuthenticationServicePrincipal(), $clientName);
			$returnValue = !is_null($this->getPublicUserForLogin($client));
		}
		catch (AuthenticationServiceException $authE){
			$this->executionSink()->publishEndOperationOnError("isPublicAccessEnabledForClient", $authE);
			throw $authE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("isPublicAccessEnabledForClient", $e);
			throw new AuthenticationServiceException('',AuthenticationServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("isPublicAccessEnabledForClient");
		return $returnValue;
	}
	
	/**
	 * Logs into the system as a public user.
	 * There should be at least one public user with isDefaultLogin=1 declared in the admin config or
	 * a valid username of a public user should be given.
	 * If authentication is successful, then main principal is changed to match the public user.
	 * @param String $clientName the name of the client to login
	 * @param String $username optional public username to use.
	 * @throws AuthenticationServiceException if login failed
	 * @return Principal the main principal
	 */
	public function loginAsPublic($clientName, $username=null) {
		$this->executionSink()->publishStartOperation("loginAsPublic");
		$returnValue = null;
		try
		{			
			if(!$this->isMainPrincipalMinimal()){
				throw new AuthenticationServiceException('A login is attempted and the main principal is not a Minimal Principal. Logout before login.',AuthenticationServiceException::NOT_MINIMAL_PRINCIPAL);
			}
			// if username is null then looks for default public login
			if(!isset($username)) {
				// gets the client based on its client name
				$client = $this->getClientAdminService()->getClient($this->getAuthenticationServicePrincipal(), $clientName);
				$username = $this->getPublicUserForLogin($client);
				if(is_null($username)) throw new AuthenticationServiceException('', AuthenticationServiceException::FORBIDDEN);
			}
			// authenticates as public
			$returnValue = $this->authenticate($username, null, $clientName, true);
			//register this principal to make it valid
			$this->registerPrincipal($returnValue);
			//if authentication successfull then store this principal in the mainPrincipal
			$this->setMainPrincipal($returnValue);
		}
		catch (AuthenticationServiceException $authE){
			$this->executionSink()->publishEndOperationOnError("loginAsPublic", $authE);
			throw $authE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("loginAsPublic", $e);
			throw new AuthenticationServiceException('',AuthenticationServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("loginAsPublic");
		return $returnValue;
	}
	
	/**
	 * Gets the default public user for login.
	 * @return String the username of the first public user with isDefaultLogin=1 or null if not found
	 */
	protected function getPublicUserForLogin($client)
	{
		$xml = $this->getConfigService()->getAdminConfigForClient($this->getAuthenticationServicePrincipal(), $client);
		if(is_null($xml)) return null;
		$xml = $xml->xpath("users/public/user[@isDefaultLogin='1']");
		if(!$xml) return null;
		return (string)$xml[0]['username'];
	}
}


