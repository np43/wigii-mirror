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

/* wigii AuthenticationService implementation
 * Created by CWE on 2 juin 09
 * Modified by CWE on 5 sept 2013 to take advantage of the Principal bindToRole and bindToRealUser methods
 */
class AuthenticationServiceImpl implements AuthenticationService
{
	private $_debugLogger;
	private $_executionSink;
	private $sessionAS;
	private $configS;
	private $userAS;
	private $wigiiNamespaceAS;
	private $clientAS;
	private $dbAS;
	private $mysqlF;
	private $authenticationServicePrincipal;
	private $sessionCacheEnabled;
	public function setSessionCacheEnabled($sessionCacheEnabled){
		$this->sessionCacheEnabled = $sessionCacheEnabled;
	}
	protected function isSessionCacheEnabled(){
		return $this->sessionCacheEnabled;
	}
	/**
	 * Principal cache, used for registration
	 * map (clientName(username)) -> Principal
	 */
	private $principalCache;
	/**
	 * Main principal
	 */
	protected $mainPrincipal;


	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("AuthenticationServiceImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("AuthenticationServiceImpl");
		}
		return $this->_executionSink;
	}

	/**
	 * Constructs the AuthenticationService instance
	 * potentially registers a list of predefined principals.
	 */
	public function __construct($principalList = null)
	{
		$this->setSessionCacheEnabled(true);
		$this->debugLogger()->write("creating instance");
		$this->registerPrincipals($principalList);
	}

	// dependency injection
	public function setSessionAdminService($sessionAdminService){
		$this->sessionAS = $sessionAdminService;
	}
	protected function getSessionAdminService(){
		// autowired
		if(!isset($this->sessionAS)){
			$this->sessionAS = ServiceProvider::getSessionAdminService();
		}
		return $this->sessionAS;
	}

	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
	}
	public function setUserAdminService($userAdminService)
	{
		$this->userAS = $userAdminService;
	}
	protected function getUserAdminService()
	{
		// autowired
		if(!isset($this->userAS))
		{
			$this->userAS = ServiceProvider::getUserAdminService();
		}
		return $this->userAS;
	}
	public function setWigiiNamespaceAdminService($wigiiNamespaceAdminService)
	{
		$this->wigiiNamespaceAS = $wigiiNamespaceAdminService;
	}
	protected function getWigiiNamespaceAdminService()
	{
		// autowired
		if(!isset($this->wigiiNamespaceAS))
		{
			$this->wigiiNamespaceAS = ServiceProvider::getWigiiNamespaceAdminService();
		}
		return $this->wigiiNamespaceAS;
	}
	public function setClientAdminService($clientAdminService)
	{
		$this->clientAS = $clientAdminService;
	}
	protected function getClientAdminService()
	{
		// autowired
		if(!isset($this->clientAS))
		{
			$this->clientAS = ServiceProvider::getClientAdminService();
		}
		return $this->clientAS;
	}
	public function setDbAdminService($dbAdminService)
	{
		$this->dbAS = $dbAdminService;
	}
	protected function getDbAdminService()
	{
		// autowired
		if(!isset($this->dbAS))
		{
			$this->dbAS = ServiceProvider::getDbAdminService();
		}
		return $this->dbAS;
	}
	public function setMySqlFacade($mysqlFacade)
	{
		$this->mysqlF = $mysqlFacade;
	}
	protected function getMySqlFacade()
	{
		// autowired
		if(!isset($this->mysqlF))
		{
			$this->mysqlF = TechnicalServiceProvider::getMySqlFacade();
		}
		return $this->mysqlF;
	}

	// service implementation

	/**
	 * Get the actual main principal of the service.
	 * @return Principal
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
				$minPrinc = $this->createMinimalPrincipal();
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

	/**
	 * Set the main principal of the service
	 */
	protected function setMainPrincipal($principal){
		$this->assertPrincipalValid($principal);
		$this->mainPrincipal = $principal;
		$this->getSessionAdminService()->storeData($this,"mainPrincipal", $this->mainPrincipal);
	}

	/**
	 * Authenticate this user. If successfull, the main principal is matched to this user
	 * rights. Else, the main principal is not changed.
	 * throws AuthenticationServiceException in case of error
	 * @return Principal the main principal
	 */
	public function login($username, $password, $clientName){
		$this->executionSink()->publishStartOperation("login");
		$returnValue = null;
		try
		{
			//check if there is already a principal loged in
			//there is no sense to do always a logout, because a logout will
			//always put a minimalPrincipal for the mainPrincipal after having
			//logout the mainPrincipal
			if(!$this->isMainPrincipalMinimal()){
				throw new AuthenticationServiceException('A login is attempted and the main principal is not a Minimal Principal. Logout before login.',AuthenticationServiceException::NOT_MINIMAL_PRINCIPAL);
			}
			$returnValue = $this->authenticate($username, $password, $clientName, false);
			//register this principal to make it valid
			$this->registerPrincipal($returnValue);
			//if authentication successfull then store this principal in the mainPrincipal
			$this->setMainPrincipal($returnValue);
		}
		catch (AuthenticationServiceException $authE){
			$this->executionSink()->publishEndOperationOnError("login", $authE);
			throw $authE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("login", $e);
			throw new AuthenticationServiceException('',AuthenticationServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("login");
		return $returnValue;
	}

	/**
	 * Changes the role of the principal
	 * principal: authenticated user performing the operation
	 * $roleId: the id of the User representing a role to be played by this principal
	 * throws AuthenticationServiceException in case of error
	 */
	public function changeToRole($principal, $roleId){
		$this->executionSink()->publishStartOperation("changeToRole");
		try
		{
			if(is_null($principal)) throw new AuthenticationServiceException("principal cannot be null", AuthenticationServiceException::INVALID_ARGUMENT);
			if(!$principal->bindToRole($roleId)) throw new AuthenticationServiceException("principal could not bind to role $roleId", AuthenticationServiceException::UNAUTHORIZED);
			$returnValue = $principal;

			/******* DEPRECATED
			//check if the principal is the mainPrincipal
			//throw an exception if we try to logout when no user loged in
			if($principal == null || $this->isMainPrincipalMinimal() || $principal->getAttachedUser()==null){
				throw new AuthenticationServiceException('Try to changeRole with no main Principal or with a main Principal with no attached user', AuthenticationServiceException::FORBIDDEN);
			}

			//grab the role
			$role = $this->getUserAdminService()->getMyRole($principal, $roleId);

			$returnValue = $this->doChangeToUser($principal, $role);
			*******/
		}
		catch (AuthenticationServiceException $authE){
			$this->executionSink()->publishEndOperationOnError("changeToRole", $authE);
			throw $authE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("changeToRole", $e);
			throw new AuthenticationServiceException('',AuthenticationServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("changeToRole");
		return $returnValue;
	}

	/**
	 * Reset the role that the principal was playing
	 * principal: authenticated user performing the operation
	 * throws AuthenticationServiceException in case of error
	 */
	public function backToRealUser($principal){
		$this->executionSink()->publishStartOperation("backToRealUser");
		try
		{
			if(is_null($principal)) throw new AuthenticationServiceException("principal cannot be null", AuthenticationServiceException::INVALID_ARGUMENT);
			$principal->bindToRealUser();
			$returnValue = $principal;

			/******* DEPRECATED
			//check if the principal is the mainPrincipal
			//throw an exception if we try to logout when no user loged in
			if($principal == null || $this->isMainPrincipalMinimal() || $principal->getAttachedUser()==null){
				throw new AuthenticationServiceException('Try to go backToRealUser with no main Principal or with a main Principal with no attached user', AuthenticationServiceException::FORBIDDEN);
			}

			// gets back the real user from db (to prevent manual changes of rights in the in memory real user)
			$realUserP = $this->getUserAdminService()->getUser($principal, $principal->getRealUserId());
			if(is_null($realUserP)) throw new AuthenticationServiceException("principal real user '".$principal->getRealUsername()."' does not exist anymore in database", AuthenticationServiceException::UNAUTHORIZED);
			$returnValue = $this->doChangeToUser($principal, $realUserP->getUser());
			********/
		}
		catch (AuthenticationServiceException $authE){
			$this->executionSink()->publishEndOperationOnError("backToRealUser", $authE);
			throw $authE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("backToRealUser", $e);
			throw new AuthenticationServiceException('',AuthenticationServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("backToRealUser");
		return $returnValue;
	}

	/******* DEPRECATED
	protected function doChangeToUser($principal, $user){

		//keep temporary the current session context
		$crtSessionContext = $principal->serializeSessionContext();

		if($principal->isPlayingRole() && $user->getId() == $principal->getRealUserId()){
			//back to real user
			$newPrincipal = $this->createPrincipalInstance($user);
		} else {
			//switch to play user/role
			$realUserP = $this->getUserAdminService()->getUser($principal, $principal->getRealUserId());
			if(is_null($realUserP)) throw new AuthenticationServiceException("principal real user '".$principal->getRealUsername()."' does not exist anymore in database", AuthenticationServiceException::UNAUTHORIZED);
			$newPrincipal = $this->createPrincipalInstance($user, $realUserP->getUser());
		}

		//give to the newPrincipal current session context
		$newPrincipal->importSessionContext($crtSessionContext);

		//unregister and destroy the mainPrincipal
		$this->destroyMainPrincipal();

		//register this principal to make it valid
		$this->registerPrincipal($newPrincipal);
		//store this principal in the mainPrincipal
		$this->setMainPrincipal($newPrincipal);

		return $newPrincipal;
	}
	*************/

	/**
	 * Logs out the main Principal (main authenticated user). This will delete the main
	 * principal and put in place an minimal principal. The main Principal logout
	 * informations will be updated
	 * @return void
	 */
	public function logout(){
		$this->executionSink()->publishStartOperation("logout");
		try
		{
			//check if the principal is the mainPrincipal
			//throw an exception if we try to logout when no user loged in
			if($this->getMainPrincipal() == null || $this->isMainPrincipalMinimal() || $this->getMainPrincipal()->getAttachedUser()==null){
				throw new AuthenticationServiceException('Try to logout with no main Principal or with a main Principal with no attached user', AuthenticationServiceException::FORBIDDEN);
			}

			$principal = $this->getMainPrincipal();
			if($principal->isPlayingRole()){
				$realUser = $principal->getRealUser();
			} else {
				$realUser = $principal->getAttachedUser();
			}
			$userd = $realUser->getDetail();
			$userd->setInfo_lastLogout(time());
			$userd->setInfo_lastSessionContext($principal->serializeSessionContext());

			//store in DB the user login informations + session context
			$this->getUserAdminService()->persistUserLoginInformation($this->getAuthenticationServicePrincipal(), $realUser);

			//keept the current clientName to be able to create the MinimalPrincipal
			$clientName = $principal->getWigiiNamespace()->getClient()->getClientName();

			$this->debugLogger()->write("******LOGOUT SUCCEEDED****** ".$principal->getUsername()." in wigiiNamespace ".$principal->getWigiiNamespace()->getWigiiNamespaceName()." for client ".$clientName);

			$this->destroyMainPrincipal();

			//eput($principal->displayDebug());

			//we asks then for the mainPrincipal, this will create a MinimalPrincipal
			//because no MainPrincipal is set
			$minPrincipal = $this->getMainPrincipal();
			$this->registerPrincipal($minPrincipal);
			$this->setMainPrincipal($minPrincipal);

		}
		catch (AuthenticationServiceException $authE){
			$this->executionSink()->publishEndOperationOnError("logout", $authE);
			throw $authE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("logout", $e);
			throw new AuthenticationServiceException('',AuthenticationServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("logout");
	}

	public function persistMainPrincipalSessionContext(){
		if($this->isMainPrincipalMinimal()) return false;
		$p = $this->getMainPrincipal();
		if($p->isPlayingRole()){
			$realUser = $p->getRealUser();
			$p->setValueInGeneralContext("lastRoleId", $p->getUserId());
		} else {
			$realUser = $p->getAttachedUser();
			$p->setValueInGeneralContext("lastRoleId", null);
		}
		$realUser->getDetail()->setInfo_lastSessionContext($p->serializeSessionContext());
		$this->getUserAdminService()->persistUserSessionContext($this->getAuthenticationServicePrincipal(), $realUser);
	}

	/**
	 * Get a principal with the rights of this public user. This principal is not stored
	 * in session, and is not changing the mainPrincipal. This public principal is stored
	 * in service cache to prevent recreating the same one for the same public user and
	 * to be able to valid this public principal
	 */
	public function getPublicPrincipal($username, $clientName){
		$this->executionSink()->publishStartOperation("getPublicPrincipal");
		try
		{
			// firs looks in registered Principal
			if(isset($this->principalCache)){
				$key = $this->getPrincipalCacheKey($username, $clientName);
				$returnValue = $this->principalCache[$key];
			}
			// if not found then :
			if(is_null($returnValue))
			{
				//do the authentication process
				$returnValue = $this->authenticate($username, null, $clientName, true);
				//register this principal to make it valid
				$this->registerPrincipal($returnValue);
			} else {
				$this->debugLogger()->write('Found public principal for '.$username.' '.$clientName);
			}
		}
		catch (AuthenticationServiceException $authE){
			$this->executionSink()->publishEndOperationOnError("getPublicPrincipal", $authE);
			throw $authE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("getPublicPrincipal", $e);
			throw new AuthenticationServiceException('',AuthenticationServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("getPublicPrincipal");

		return $returnValue;
	}

	/**
	 * Try to authenticate a username on a pop3 server.
	 * Throw AuthenticationServiceException if unsuccessful
	 * @param $username string
	 * @param $password string
	 * @param $useSLL = false, boolean, if true use an SSL connection with the server
	 */
	public function authenticateWithPop3($username, $password, $server, $useSSL = false){
		$this->executionSink()->publishStartOperation("authenticateWithPop3");
		try
		{
			//préparation des données pour le login:
			$PHP_AUTH_PW = utf8_decode(stripslashes($password));
			$PHP_AUTH_SERVER = $server;
			$PHP_AUTH_USER = utf8_decode(stripslashes($username));

			if($useSSL)
			$fp = fsockopen("ssl://$PHP_AUTH_SERVER", 995, $errno, $errstr, 10);
			else
			$fp = fsockopen("$PHP_AUTH_SERVER", 110, $errno, $errstr, 10);

			if (!$fp) {
				throw new AuthenticationServiceException("POP3 ERROR with account:$username, [$errno] [$errstr]", AuthenticationServiceException::POP3_AUTHENTICATION_SERVER_CONNECTION_PROBLEM);
			}

			stream_set_blocking($fp, -1); // Turn off blocking

			//Clear the POP server's Banner Text.
			//eg.. '+OK Welcome to <server name> etc etc'
			$trash = fgets($fp, 128); // Trash to hold the banner

			fwrite($fp, "USER $PHP_AUTH_USER\r\n"); // POP3 USER CMD

			$user = fgets($fp, 128);
			$user = preg_replace("#\n#", "", $user);

			$returnValue = false;
			if (preg_match("#^\+OK(.+)#", $user)) {

				fwrite($fp, "PASS $PHP_AUTH_PW\r\n"); // POP3 PASS CMD
				$pass = fgets($fp, 128);
				$pass = preg_replace("#\n#", "", $pass);

				if (preg_match("#^\+OK(.+)#", $pass)) {
					// User has successfully authenticated
					$this->debugLogger()->write("Pop3 login succeeded: loged in with user: $username.");
					fwrite($fp, "QUIT\r\n");
					fclose($fp);
				} else {

					fwrite($fp, "QUIT\r\n");
					fclose($fp);
					throw new AuthenticationServiceException("Pop3 login failed: wrong password for user: $username.", AuthenticationServiceException::INVALID_PASSWORD);
				}

			} else {
				fwrite($fp, "QUIT\r\n");
				fclose($fp);
				throw new AuthenticationServiceException("Pop3 login failed: unknow user: $username.", AuthenticationServiceException::POP3_AUTHENTICATION_UNKNOWN_USERNAME);
			}
			$returnValue = true;
			$this->executionSink()->log('Pop3 login successful for user: '.$username." on pop3 server: ".$server);
		}
		catch (AuthenticationServiceException $authE){
			$this->executionSink()->publishEndOperationOnError("authenticateWithPop3", $authE);
			throw $authE;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("authenticateWithPop3", $e);
			throw new AuthenticationServiceException('',AuthenticationServiceException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("authenticateWithPop3");

		return $returnValue;
	}

	/**
	 * Validates the principal to be sure that this principal has been created
	 * by this service.
	 * throws AuthenticationServiceException in case of error
	 * in particular throws AuthenticationServiceException(UNAUTHORIZED) if principal is not valid.
	 */
	public function assertPrincipalValid($principal)
	{
		if(!$this->isPrincipalValid($principal))
		{
			throw new AuthenticationServiceException('This Principal has not be created by the AuthenticationService',AuthenticationServiceException::UNAUTHORIZED);
		}
	}
	/**
	 * Checks that this principal has been created by this service else return false
	 * throws AuthenticationServiceException in case of error
	 */
	public function isPrincipalValid($principal)
	{
		if(is_null($principal)) throw new AuthenticationServiceException("principal cannot be null", AuthenticationServiceException::INVALID_ARGUMENT);
		$key = $this->getPrincipalCacheKey($principal->getRealUsername(), $principal->getWigiiNamespace()->getClient()->getClientName());
		return ($principal === $this->principalCache[$key]);
	}

	// utils

	/**
	 * This method is a factorisation to allow inheritance on this specific part
	 * @param $p AuthenticationServicePrincipal
	 * @param $password, is not used in this implementation
	 * @param $postFix, is not used in this implementation
	 */
	protected function findUserForClient($p, $username, $password, $postFix, $client){
		return $this->getUserAdminService()->findUserForClient($p, $username, $client);
	}
	/**
	 * Authenticates a user given its username and password and returns a Principal object
	 * username: user name
	 * password: user password / or null in case of public authentication
	 * clientName: client for which the user wants to be autenticated
	 * isPublic = false, if true then do the authentication for a public user
	 * throws AuthenticationServiceException in case of error
	 * FORBIDDEN exception or a WAIT exception (in case of too frequent login failure).
	 */
	protected function authenticate($username, $password, $clientName, $isPublic = false)
	{
		$p = $this->getAuthenticationServicePrincipal();
		// gets the client based on its client name
		$client = $this->getClientAdminService()->getClient($p, $clientName);

		//first we need to look to the username and take out the good informations
		$postFix = null;
		$result = array();
		$allow = '[_a-z0-9-]';
		if(defined("EMAIL_postfix")){
			$defaultEmailPostfix = EMAIL_postfix;
		} else {
			$defaultEmailPostfix = null;
		}
		if(preg_match('/@('.$allow.'+(\.'.$allow.'+)*)(\.'.$allow.'{2,})+$/i', $username, $result)==1){
			//that means the username ends with @yyy.zz
			$postFix = $result[0]; //on prend la totalité du postfix
			//dans le cas ou le postFix correspond au default
			//alors on le supprime du username et l'on efface
			//le postfix. comme cela c'est comme si le user
			//c'était logué sans l'emailpostfix
			//on part du principe que dans un système avec de multiple client
			//il n'y a pas de default emailPostfix
			if($defaultEmailPostfix!=null && $result[0] == $defaultEmailPostfix){
				$username = str_replace($result[0], "", $username);
				$postFix = null;
			}
		}

		//WARNING, the piece of code above will make that pop3 servers authentication unsuccsessfull
		//if the server requires the @domain in the username and an Email_postfix is defined and the email is from this domain

		if($isPublic){
			// is the specified user name a public user name ?
			if(! $this->isPublicUser($username, $client)) {
				throw new AuthenticationServiceException('', AuthenticationServiceException::FORBIDDEN);
			}
		}

		// does the user exist in the database ?
		$user = $this->findUserForClient($p, $username, $password, $postFix, $client);

		//if no user with this name si found
		if(!isset($user)) throw new AuthenticationServiceException('User '.$username." dosen't exist for client ".$client->getClientName(), AuthenticationServiceException::INVALID_USERNAME);
		$user = $user->getUser(); // retrieves User from UserP.
		$userd = $user->getDetail();

		//if a user is found, then begin the authentification checks

		//check if the user is blacklisted
		if(!$this->isNotBlacklisted($user)) throw new AuthenticationServiceException('User '.$username." is unauthorized in Admin config file for client ".$client->getClientName(), AuthenticationServiceException::FORBIDDEN);

		if(!$isPublic){
			//check if the user has not failed to login in to often
			if(!$this->checkLoginFailureFrequency($user)) throw new AuthenticationServiceException('User '.$username." has currently a login failure frequency to high for this client ".$client->getClientName(), AuthenticationServiceException::WAIT);

			//check the login
			try {
				$this->debugLogger()->write("try to authenticate with: ".$user->getUsername());
				$this->doAuthenticate($user, $password, $postFix, $userd->getAuthenticationMethod());
			} catch (AuthenticationServiceException $authE){
				$userd->setInfo_nbFailedLogin($userd->getInfo_nbFailedLogin() + 1);
				$userd->setInfo_lastFailedLogin(time());

				//store in DB the user failure login information
				$this->getUserAdminService()->persistUserLoginInformation($p, $user);
				$this->debugLogger()->write($authE->getCode()." ".$authE->getMessage());
				if($authE->getCode()==AuthenticationServiceException::INVALID_PASSWORD && $userd->getInfo_nbFailedLogin()>2){
					throw new AuthenticationServiceException('Login failed for username: '.$username." for client: ".$client->getClientName()." more than 2 times.", AuthenticationServiceException::INVALID_PASSWORD_MORE_THAN_2_TIMES);
				}
				throw new AuthenticationServiceException('Login failed for username: '.$username." for client: ".$client->getClientName(), $authE->getCode());
			}
		}

		//authentication successful

		//prepare a principal based on $user data.
		$user->setPublic($isPublic);
		$returnValue = $this->createPrincipalInstance($user);
		$userd = $returnValue->getAttachedUser()->getDetail();

//		//set the emailAccount if there is one coming from the login
//		$tempEmailAccount = $username;
//		if($userd->getAuthenticationMethod()!="usual"){
//			//in this case either the username has already the full email
//			//either it use the default email postif
//			if($postFix == null){
//				$tempEmailAccount .= (string)$this->getConfigService()->getAdminConfigForClient($p, $client)->pop3Config["emailPostfix"];
//			}
//		}
//		$allow = '[_a-z0-9-]';
//		$regex_email = '/^'.$allow.'+(\.'.$allow.'+)*@'.$allow.'+(\.'.$allow.'+)*(\.'.$allow.'{2,})+$/i';
//		if(preg_match($regex_email, $tempEmailAccount) != 0){
//			$returnValue->setEmailAccount($tempEmailAccount);
//		}

		$userd->setInfo_nbFailedLogin(0);
		$userd->setInfo_lastFailedLogin(null);
		$userd->setInfo_lastLogin(time());
		$userd->setInfo_nbLogin($userd->getInfo_nbLogin()+1);

		//set clear password to fill  password fields, to make
		//possible the expiration even on authentication different than usual
		if(!$isPublic) $userd->setClearPassword($password);


		//store in DB the user login informations
		$this->getUserAdminService()->persistUserLoginInformation($p, $returnValue->getAttachedUser());

		$this->debugLogger()->write("******".(($isPublic)?"PUBLIC ":"")."LOGIN SUCCEEDED****** ".$returnValue->getUsername()." authenticated in wigiiNamespace ".$returnValue->getWigiiNamespace()->getWigiiNamespaceName()." for client ".$returnValue->getWigiiNamespace()->getClient()->getClientName());

		return $returnValue;
	}

	/**
	 * do the Authentication job with the specified method
	 * throw exception if authentication failed
	 */
	protected function doAuthenticate($user, $password, $postFix, $authenticationMethod){
		$p = $this->getAuthenticationServicePrincipal();
		//do the user authentication
		switch ($authenticationMethod){
			case "usual":
				$this->doUsualAuthentication($user, $password);
				break;
			case "pop3SSL":
				$useSSL = true;
			case "pop3":
				if(!isset($useSSL)) $useSSL = false;
				//if a server is defined then use it, otherwise take the
				//server defined in the configuration
				$server = $user->getDetail()->getAuthenticationServer();
				if($server == null && defined("EMAIL_defaultPop3Server")) $server = EMAIL_defaultPop3Server;
				if(defined("EMAIL_postfix")){
					$defaultEmailPostfix = EMAIL_postfix;
				} else {
					$defaultEmailPostfix = null;
				}
				$this->authenticateWithPop3($user->getUsername().($postFix ? "" : $defaultEmailPostfix), $password, $server, $useSSL);
				break;
			default:
				throw new AuthenticationServiceException('Incorrect authentication method: '.$authenticationMethod.' for user: '.$user->gerUsername()." for client: ".$user->getWigiiNamespace()->getClient()->getClientName(), AuthenticationServiceException::INVALID_ARGUMENT);
		}
		return false;
	}

	protected function doUsualAuthentication($user, $password){
		if($user->getDetail()->getPassword() != md5($password)){
			throw new AuthenticationServiceException('Incorrect password for user: '.$user->getUsername()." for client: ".$user->getWigiiNamespace()->getClient()->getClientName(), AuthenticationServiceException::INVALID_PASSWORD);
			$this->debugLogger()->write('Incorrect password for user: '.$user->getUsername()." for client: ".$user->getWigiiNamespace()->getClient()->getClientName());
			$this->executionSink()->log('Usual login failed for user: '.$user->getUsername()." for client: ".$user->getWigiiNamespace()->getClient()->getClientName());
			return false;
		}
		$this->executionSink()->log('Usual login successful for user: '.$user->getUsername()." for client: ".$user->getWigiiNamespace()->getClient()->getClientName());
	}

	/**
	 * do a check in the config files if the user is blacklisted or not
	 * @param username, string
	 * @return bool, true if user is not blocked
	 */
	protected function isNotBlacklisted($user){
		$p = $this->getAuthenticationServicePrincipal();
		$userName = $user->getUsername();
		$client = $user->getWigiiNamespace()->getClient();
		//contrôle des utilisateurs autorisé et défendu
		$isAuthorized = null;
		if(isset($this->getConfigService()->getAdminConfigForClient($p, $client)->users->authorized->user)){
				$parent = array(); //contient tous ceux que l'on doit prendre les enfants
//				$normal = array(); //contient tous ceux que l'on doit prendre tel quel
				foreach($this->getConfigService()->getAdminConfigForClient($p, $client)->users->authorized->user as $auser){
					//this is important like this we can know if
					//there is a authirized list but that the user is not in it.
					$isAuthorized = false;
					if($auser["username"]==$userName && $auser["onlyChildrenUsers"]!="1"){
						$isAuthorized = true;
						break;
					}
//					if($auser["onlyChildrenUsers"]!="1") $normal[] = $auser["username"];
					if($auser["withChildrenUsers"]=="1") $parent[] = $auser["username"];
				}

				//if the user was not listed in the list
				//and there is parents, so fetch in the DB information
				if($isAuthorized != true && $parent != null){
					//pour les utilisateurs autorisé (si défini)
					//on recherche tous les enfants des utilisateurs autorisé qui peremettes les enfants
					$dbCS = $this->getDbAdminService()->getDbConnectionSettingsForClient($p, $user->getWigiiNamespace()->getClient());
					$sql = $this->getSqlForIsNotBlackListed($parent);
					$rowList = RowListArrayImpl::createInstance("username");
					$this->getMySqlFacade()->selectAll($p, $sql, $dbCS, $rowList);
					$array = $rowList->getListIterator();

					$isAuthorized = $array[$userName]!=null;

				}
		}

		$isForbidden = null;
		if(isset($this->getConfigService()->getAdminConfigForClient($p, $client)->users->forbidden->user)){
			$parent = array(); //contient tous ceux que l'on doit prendre les enfants
//			$normal = array(); //contient tous ceux que l'on doit prendre tel quel
			foreach($this->getConfigService()->getAdminConfigForClient($p, $client)->users->forbidden->user as $fuser){
				$isForbidden == false;
				if($fuser["username"]==$userName && $fuser["onlyChildrenUsers"]!="1"){
					//l'utilisateur est interdit
					$isForbidden = true;
					break;
				}
//				if($fuser["onlyChildrenUsers"]!="1") $normal[] = $fuser["username"];
				if($fuser["withChildrenUsers"]=="1") $parent[] = $fuser["username"];
			}
			if(!$isForbidden && $parent!= null){
				//pour les utilisateurs défendu (si défini)
				//on recherche tous les enfants des utilisateurs défendu qui défende les enfants
				$dbCS = $this->getDbAdminService()->getDbConnectionSettingsForClient($p, $user->getWigiiNamespace()->getClient());
				$sql = $this->getSqlForIsNotBlackListed($parent);
				$rowList = RowListArrayImpl::createInstance("username");
				$this->getMySqlFacade()->selectAll($p, $sql, $dbCS, $rowList);
				$array = $rowList->getListIterator();
				$isForbidden = $array[$userName]!=null;

			}
		}

		if($isForbidden === true){
			$this->debugLogger()->write("User $username is blacklisted.");
			return false;
		}

		if($isAuthorized === true){
			$this->debugLogger()->write("User $username is in the authorized userlist.");
			return true;
		}

		if($isAuthorized === false){
			$this->debugLogger()->write("User $username is not in the defined authorized userlist.");
			return false;
		}

		$this->debugLogger()->write("Authorized userlist doesn't exist, and user $username is not blacklisted.");
		return true;

	}

	protected function getSqlForIsNotBlackListed($parent){
		if($parent==null) $parent = "NULL";
		else $parent = "'".implode("','", $parent)."'";
//		if($normal==null) $normal = "NULL";
//		else $normal = "'".implode("','", $normal)."'";

		return "SELECT DISTINCT u.username
FROM Users_Users uu
LEFT JOIN Users u ON u.id_user = uu.id_user and uu.isOwner
LEFT JOIN Users u2 ON u2.id_user = uu.id_user_owner and uu.isOwner
WHERE u2.username IN ($parent)
;
";

	}

	/**
	 * check if this username has not failed to login in too often
	 * @return FALSE if failed to often!
	 */
	protected function checkLoginFailureFrequency($user){
		$user = $user->getDetail();
//		eput("nb:".$user->getInfo_nbFailedLogin());
//		eput("time:".(time()-$user->getInfo_lastFailedLogin()));
//		eput("calc:".(min(max(($user->getInfo_nbFailedLogin()-4), 0),5) * 60));
		if($user->getInfo_lastFailedLogin() != null && $user->getInfo_nbFailedLogin()!= null &&
//			(time()-$user->getInfo_lastFailedLogin())<(($user->getInfo_nbFailedLogin()-3)*$user->getInfo_nbFailedLogin()*10)){
			$user->getInfo_nbFailedLogin()>2 && (time()-$user->getInfo_lastFailedLogin())<(min(max(($user->getInfo_nbFailedLogin()-4), 0),5) * 60)){ //wait 1 minute after 3 attemps, wait until 5 minutes max between each try after
				return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks if this username is defined a public user
	 * In this implementation checks Admin module configuration file to find a
	 * users->public config node with the given username
	 * return true if username is a public user, else false
	 */
	protected function isPublicUser($username, $client)
	{
		return !is_null($this->getPublicUserConfig($username, $client));
	}

	/**
	 * Creates a Principal instance and attach attachedUser and realUser
	 */
	protected function createPrincipalInstance($attachedUser, $realUser = null)
	{
		$returnValue = Principal::createInstanceFromUser($attachedUser, $realUser);
		return $returnValue;
	}


	/**
	 * Returns a principal representing this AuthenticationService instance.
	 * In this implementation, the AuthenticationService has the following rights :
	 * -
	 */
	protected function getAuthenticationServicePrincipal()
	{
		if(!isset($this->authenticationServicePrincipal))
		{
			$this->authenticationServicePrincipal = Principal::createInstanceFromArray(array(
				"username"=>'AuthenticationServiceImpl',
				"wigiiNamespace"=>$this->getWigiiNamespaceAdminService()->getEmptyWigiiNamespaceForEmptyClient()
				)
			);
			$this->registerPrincipal($this->authenticationServicePrincipal);
		}
		return $this->authenticationServicePrincipal;
	}

	/**
	 * Returns an minimal principal. This is normally used to replace
	 * the mainPrincipal with an empty one
	 */
	protected function createMinimalPrincipal(){
		$p = Principal::createInstanceFromArray(array(
			"username"=>"MinimalPrincipal",
			"wigiiNamespace"=>$this->getWigiiNamespaceAdminService()->getEmptyWigiiNamespaceForEmptyClient()
		));
		return $p;
	}

	public function isMainPrincipalMinimal(){
		$p = $this->getMainPrincipal();
		return (
			$p->getUsername() == "MinimalPrincipal" &&
			$p->getAttachedUser() == null
			&& $p->getWigiiNamespace()->getWigiiNamespaceName() == null
			);
	}

	public function isAuthenticationService($principal)
	{
		return ($principal === $this->getAuthenticationServicePrincipal());
	}

	// principal cache management

	protected function registerPrincipal($principal)
	{
		if(is_null($principal)) return;
		if(!isset($this->principalCache)) $this->principalCache = array();
		$key = $this->getPrincipalCacheKey($principal->getRealUsername(), $principal->getWigiiNamespace()->getClient()->getClientName());
		$this->principalCache[$key] = $principal;
		$this->debugLogger()->write('register principal');
	}
	protected function registerPrincipals($principalList)
	{
		if(isset($principalList))
		{
			foreach($principalList->getListIterator() as $principal)
			{
				$this->registerPrincipal($principal);
			}
		}
	}
	protected function unRegisterPrincipal($principal)
	{
		$key = $this->getPrincipalCacheKey($principal->getRealUsername(), $principal->getWigiiNamespace()->getClient()->getClientName());
		unset($this->principalCache[$key]);
		$this->debugLogger()->write('unregister principal: '.$key);
	}

	protected function destroyMainPrincipal(){
		$this->unRegisterPrincipal($this->mainPrincipal);
		unset($this->mainPrincipal);
		$this->mainPrincipal = null;
//		$this->getSessionAdminService()->storeData($this,"mainPrincipal", null);
		//$this->getSessionAdminService()->storeData($this,"mainPrincipal", $this->mainPrincipal);
		$this->getSessionAdminService()->clearData($this,"mainPrincipal");
		$this->debugLogger()->write('destroy Main Principal');
	}

	/**
	 * Returns (clientName(username))
	 */
	protected function getPrincipalCacheKey($username, $clientName)
	{
		return '('.(is_null($clientName) ? '' : $clientName).'('.(is_null($username) ? '' : $username).'))';
	}

	// Utils

	/**
	 * gets public user configuration as an SimpleXMLNode or null if not found
	 */
	public function getPublicUserConfig($username, $client)
	{
		if(is_null($username) || $username == '') return null;
		$xml = $this->getConfigService()->getAdminConfigForClient($this->getAuthenticationServicePrincipal(), $client);
		if(is_null($xml)) return null;
		$xml = $xml->xpath("users/public/user[@username='".$username."']");
		if(!$xml) return null;
		return $xml[0];
	}
	/**
	 * gets public user configuration as an SimpleXMLNode for given principal
	 * @param Principal $principal
	 * @throws AuthenticationServiceException INVALID_ARGUMENT if principal is not authenticated as a public user
	 */
	public function getPublicUserConfigForPrincipal($principal) {
		if(!isset($principal)) throw new AuthenticationServiceException('principal cannot be null', AuthenticationServiceException::INVALID_ARGUMENT);
		if($principal->isRealUserPublic()) {
			return $this->getPublicUserConfig($principal->getRealUsername(), $principal->getRealWigiiNamespace()->getClient());
		}
		else throw new AuthenticationServiceException('principal has not been authentified as public', AuthenticationServiceException::INVALID_ARGUMENT);
	}
}

