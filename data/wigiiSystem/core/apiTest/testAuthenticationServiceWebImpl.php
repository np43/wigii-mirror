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

/**
 * Tests class AuthenticationServiceWebImpl
 * Created by CWE on 18 juin 09
 */

class Test_AuthenticationServiceWebImpl_authenticateUnauthenticate extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_AuthenticationServiceWebImpl_authenticateUnauthenticate','AuthenticationServiceWebImpl login and then logout a user stored in the database');
	}
	public function run()
	{

		$authS = ServiceProvider::getAuthenticationService();
		$this->assertNotNull("AuthenticationService retrieved",$authS);

		if(!$authS->isMainPrincipalMinimal()) $authS->logout();

		$principal = $authS->getMainPrincipal();
		$this->assertNotNull("Main Principal never null",$principal);

		$username = "admin";
		$authS->login($username, "admin", "LOCAL");
		$principal = $authS->getMainPrincipal();

		$this->assertEqual("Principal created and loged. $username = ".$principal->getUsername(),$principal->getUsername(), $username);

		if(!$authS->isMainPrincipalMinimal()) $authS->logout();
		$authS->login("lionel", "test", "LOCAL");

//		$authS->login("admin", "admin", "LOCAL");
//		$principal = $authS->getMainPrincipal();
//		$this->assertNotNull("Principal created and loged",$principal);
//
//		$newModule = "Contacts";
//		$principal->setCrtModule($newModule);
//
//		$authS->logout();
//		$this->assertNotNull("Principal logout",$principal);
//
//		$authS->login("admin", "admin", "LOCAL");
//		$principal = $authS->getMainPrincipal();
//		$this->assertEqual("Principal lastModule changed",$principal->getLastPreference("module")->getModuleName(), $newModule);
//
//		$authS->logout();
//
//		try{
//			//$principal->setCrtModule("Filemanager");
//			$authS->logout();
//			null;
//		}
//		catch(AuthenticationServiceException $authE){
//			if($authE->getCode()==AuthenticationServiceException::FORBIDDEN){
//				//ok;
//				return;
//			} else {
//				throw $authE;
//			}
//		}
//		$this->fail("A FORBIDDEN exception should be thrown.");

	}
}
TestRunner::test(new Test_AuthenticationServiceWebImpl_authenticateUnauthenticate());



class Test_AuthenticationServiceWebImpl_NotPublicUserException extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_AuthenticationServiceWebImpl_NotPublicUserException','AuthenticationServiceWebImpl raise FORBIDDEN exception when trying to get a invalid public user');
	}
	public function run()
	{
		try
		{
			$p_myeventWWW = ServiceProvider::getAuthenticationService()->getPublicPrincipal('admin-error', 'LOCAL');
		}
		catch(AuthenticationServiceException $e)
		{
			if($e->getCode() == AuthenticationServiceException::FORBIDDEN)
			{
				ExceptionSink::publish($e);
				return; // test ok.
			}
		}
		$this->fail('no AuthenticationServiceException was raised with error code FORBIDDEN when requesting a public principal with a none public username');
	}
}
TestRunner::test(new Test_AuthenticationServiceWebImpl_NotPublicUserException());






class Test_AuthenticationServiceWebImpl_PublicPrincipalCaching extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_AuthenticationServiceWebImpl_PublicPrincipalCaching','AuthenticationServiceWebImpl adminWWW valid public user for LOCAL client is cached');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_AuthenticationServiceWebImpl_PublicPrincipalCaching");
		$publicUsername = 'adminWWW';
		$clientName = 'LOCAL';
		$p_myeventWWW = ServiceProvider::getAuthenticationService()->getPublicPrincipal($publicUsername, $clientName);
		$this->assertEqual('getPublicPrincipal is equal to '.$publicUsername.' for client '.$clientName, $p_myeventWWW->getUsername(), $publicUsername);
		$d->write("<br/>-------------------------<br/>");
		$p_myeventWWW = ServiceProvider::getAuthenticationService()->getPublicPrincipal($publicUsername, $clientName);
		$this->assertEqual('getPublicPrincipal is equal to '.$publicUsername.' for client '.$clientName, $p_myeventWWW->getUsername(), $publicUsername);
	}
}
TestRunner::test(new Test_AuthenticationServiceWebImpl_PublicPrincipalCaching());

//
//class Test_AuthenticationServiceWebImpl_WigiiWebAndAPISingleSignOn extends WigiiApiTest
//{
//	public function __construct()
//	{
//		parent::__construct('Test_AuthenticationServiceWebImpl_WigiiWebAndAPISingleSignOn','AuthenticationServiceWebImpl inside authenticated wigii space the current user authentication is forwarded to api, else a UNAUTHORIZED or UNSUPPORTED_OPERATION exception is thrown');
//	}
//	public function run()
//	{
//		global $u;
//		$d = DebugLogger::getInstance("Test_AuthenticationServiceWebImpl_WigiiWebAndAPISingleSignOn");
//		if(isset($u))
//		{
//			if($u->loged)
//			{
//				$d->write("inside authenticated wigii space, so we try to forward authentication to api");
//				$p_api = ServiceProvider::getAuthenticationService()->reauthenticate();
//				$this->assertEqual('wigii user '.$u->usernameCurrentUser.' is API is '.$p_api->getUsername(), $u->usernameCurrentUser, $p_api->getUsername());
//				return; // test ok
//			}
//		}
//		try
//		{
//			$d->write("outside authenticated wigii space, tests UNAUTHORIZED or UNSUPPORTED_OPERATION exception");
//			$as = ServiceProvider::getAuthenticationService();
//			$p_api = $as->reauthenticate();
//		}
//		catch(AuthenticationServiceException $e)
//		{
//			if($e->getCode() == AuthenticationServiceException::UNAUTHORIZED)
//			{
//				$d->write("outside authenticated wigii web space, tests UNAUTHORIZED exception");
//				ExceptionSink::publish($e);
//				return; // test ok.
//			}
//			elseif($e->getCode() == AuthenticationServiceException::UNSUPPORTED_OPERATION && $as instanceof AuthenticationServiceImpl)
//			{
//				$d->write("outside wigii web space, tests UNSUPPORTED_OPERATION exception");
//				ExceptionSink::publish($e);
//				return; // test ok.
//			}
//		}
//		$this->fail('no AuthenticationServiceException was raised with error code UNAUTHORIZED');
//	}
//}
//TestRunner::test(new Test_AuthenticationServiceWebImpl_WigiiWebAndAPISingleSignOn());
?>