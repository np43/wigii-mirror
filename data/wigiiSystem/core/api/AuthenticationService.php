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
 * wigii system authentication service interface
 * Created by CWE on 1 juin 09
 * Modified by LWR on 29 july (refactoring in applied service with mainPrincipal concept)
 * Modified by Medair (LMA) on 08.12.2016 to enable wrapping password as ValueObject instance to hide password value in stack-trace.
 */
interface AuthenticationService
{
	/**
	 * Authenticate this user. If successfull, the main principal is matched to this user
	 * rights. Else, the main principal is not changed.
	 * @param String $username the username for the login
	 * @param String|ValueObject $password the password used for login (clear password). Can be either a String or wrapped into a ValueObject.
	 * @param String $clientName Wigii client name to connect to.
	 * @throws AuthenticationServiceException in case of error
	 * @return Principal the main principal
	 */
	public function login($username, $password, $clientName);

	/**
	 * Changes the role of the principal
	 * principal: authenticated user performing the operation
	 * $roleId: the id of the User representing a role to be played by this principal
	 * throws AuthenticationServiceException in case of error
	 */
	public function changeToRole($principal, $roleId);

	/**
	 * Reset the role that the principal was playing
	 * principal: authenticated user performing the operation
	 * throws AuthenticationServiceException in case of error
	 */
	public function backToRealUser($principal);

	/**
	 * Logs out the main Principal (main authenticated user). This will delete the main
	 * principal and put in place an minimal principal. The main Principal logout
	 * informations will be updated
	 * @return void
	 */
	public function logout();

	/**
	 * Get the actual main principal of the service.
	 * @return mainPrincipal
	 */
	public function getMainPrincipal();

	/**
	 * return true if the main Principal is a minimal principal (no user loged in)
	 */
	public function isMainPrincipalMinimal();

	/**
	 * Get a principal with the rights of this public user. This principal is not stored
	 * in session, and is not changing the mainPrincipal. This public principal is stored
	 * in service cache to prevent recreating the same one for the same public user and
	 * to be able to valid this public principal
	 */
	public function getPublicPrincipal($username, $clientName);

	/**
	 * Try to authenticate a username on a pop3 server.
	 * @param String $username
	 * @param String|ValueObject $password
	 * @param Boolean $useSLL = false, if true use an SSL connection with the server
	 * @return boolean, true if authentication success. Debug message are written to
	 * explain the reason of unsuccessful authentication.
	 */
	public function authenticateWithPop3($username, $password, $server, $useSSL = false);

	/**
	 * Validates the principal to be sure that this principal has been created
	 * by this service.
	 * throws AuthenticationServiceException in case of error
	 * in particular throws AuthenticationServiceException(UNAUTHORIZED) if principal is not valid.
	 */
	public function assertPrincipalValid($principal);

	/**
	 * Checks that this principal has been created by this service else return false
	 * throws AuthenticationServiceException in case of error
	 */
	public function isPrincipalValid($principal);

	/**
	 * Returns true if the principal represents this AuthenticationService else false
	 * throws AuthenticationServiceException in case of error
	 */
	public function isAuthenticationService($principal);
	
	/**
	 * Serialize the main principal context in the real user detail
	 * and persist it.
	 * if the main principal is minimal then do nothing
	 */
	public function persistMainPrincipalSessionContext();

//	/**
//	 * Revalids an existing principal issued by another authentication service or authenticates some raw credentials.
//	 * returns a Principal
//	 * throws AuthenticationServiceException in case of error
//	 * by default this method is not supported and always throws AuthenticationServiceException(UNSUPPORTED_OPERATION)
//	 * specific sub classes can implement this method.
//	 */
//	public function reauthenticate($someCredentials=null);
}


