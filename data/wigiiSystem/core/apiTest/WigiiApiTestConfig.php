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
 * Wigii API Test data set and configuration
 * Created by CWE on 5 sept. 09
 */
class WigiiApiTestConfig
{
	// CLIENT

	public function clientName()
	{
		return CLIENT_NAME;
	}

	// MODULES

	public function TModule1()
	{
		return 'TModule1';
	}

	public function MContacts()
	{
		return 'Contacts';
	}
	public function MAdmin()
	{
		return 'Admin';
	}

	// USERS

	/**
	 * Returns default Wigii admin user name
	 */
	public function admin()
	{
		return 'admin';
	}

	public function TAdmin1()
	{
		return 'TAdmin1';
	}

	public function TUser()
	{
		return 'TUser';
	}

	// USER DERIVED DATA

	/**
	 * Returns password for the given user name
	 */
	public function password($userName)
	{
		if($userName == $this->admin())
		{
			return 'admin';
		}
		else
		{
			return $userName.'!1';
		}
	}

	public function userDetail($userName)
	{
		$mAS = ServiceProvider::getModuleAdminService();
		$pAdmin = $this->principal($this->admin());

		if($this->TAdmin1() == $userName)
		{
			return array
			(
				'clearPassword' => $this->password($userName),
				'description' =>$userName.' description',
				'authenticationServer' => '',
				'authenticationMethod' =>'usual',
				'canModifyOwnPassword' => 1,
				'moduleAccess' => $mAS->formatModuleArray($pAdmin, array($this->MAdmin(), $this->MContacts())),
				'userCreator' => 1,
				'adminCreator' => 1,
				//'readAllUsersInWigiiNamespace' => 1,
				'groupCreator' => $mAS->formatModuleArray($pAdmin, $this->MContacts()),
				'rootGroupCreator' => $mAS->formatModuleArray($pAdmin, $this->MContacts()),
				//'readAllGroupsInWigiiNamespace' => $mAS->formatModuleArray($pAdmin, $this->MContacts()),
				'wigiiNamespaceCreator' => 0,
				'moduleEditor' => 0
			);
		}
	}
	public function normalUserDetail($principal, $userName)
	{
		$mAS = ServiceProvider::getModuleAdminService();
		return array
		(
			'username'=>$userName,
			'wigiiNamespace'=>$principal->getWigiiNamespace(),
			'clearPassword' => $this->password($userName),
			'description' =>$userName.' description',
			'authenticationServer' => '',
			'authenticationMethod' =>'usual',
			'canModifyOwnPassword' => 1,
			'moduleAccess' => $mAS->formatModuleArray($principal, array($this->MContacts()))
		);
	}
	public function normalAdminDetail($principal, $adminUsername)
	{
		$mAS = ServiceProvider::getModuleAdminService();
		return array
		(
			'username'=>$adminUsername,
			'wigiiNamespace'=>$principal->getWigiiNamespace(),
			'clearPassword' => $this->password($adminUsername),
			'description' =>$adminUsername.' description',
			'authenticationServer' => '',
			'authenticationMethod' =>'usual',
			'canModifyOwnPassword' => 1,
			'moduleAccess' => $mAS->formatModuleArray($principal, array($this->MAdmin(), $this->MContacts())),
			'userCreator' => 1,
			'adminCreator' => 0,
			//'readAllUsersInWigiiNamespace' => 1,
			//'groupCreator' => $mAS->formatModuleArray($principal, $this->MContacts()),
			//'rootGroupCreator' => $mAS->formatModuleArray($principal, $this->MContacts()),
			//'readAllGroupsInWigiiNamespace' => $mAS->formatModuleArray($principal, $this->MContacts()),
			'wigiiNamespaceCreator' => 0,
			'moduleEditor' => 0
		);
	}

	/**
	 * Returns a principal with the given user name (logs into Wigii)
	 */
	public function principal($username, $roleName=null, $password=null)
	{
		$autS = ServiceProvider::getAuthenticationService();
		if(is_null($password)) $password = $this->password($username);
		if(!$autS->isMainPrincipalMinimal())
		{
			if($autS->getMainPrincipal()->getUsername() != $username)
			{
				$autS->logout();
				$autS->login($username, $password, $this->clientName());
			}
		}
		else
		{
			$autS->login($username, $password, $this->clientName());					
		}		
		// changes the role
		if(isset($roleName)) {
			$p = $autS->getMainPrincipal();
			$roleList = UserListArrayImpl::createInstance();
			$uAS = ServiceProvider::getUserAdminService();
			$lf = ListFilter::createInstance();
			$lf->setFieldSelectorLogExp(TechnicalServiceProvider::getFieldSelectorLogExpParser()->createLogExpFromString('username = "'.$roleName.'"'));
			$uAS->getMyRoles($p, $roleList, $lf);
			$role = $roleList->getFirstUser();			
			if(isset($role)) {
				$autS->changeToRole($p, $role->getId());
			}
			else throw new AuthenticationServiceException("invalid role name ".$roleName, AuthenticationServiceException::INVALID_ARGUMENT);
		}
		return $autS->getMainPrincipal();
	}
}