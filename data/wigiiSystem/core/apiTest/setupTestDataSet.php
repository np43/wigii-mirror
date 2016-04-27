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
 * Setups the test data set in database
 * Created by CWE on 5 sept. 09
 */
class SetupTestDataSet extends WigiiApiTest
{
	private $_debugLogger;
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("SetupTestDataSet");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('SetupTestDataSet','Setups Wigii API test data in database');
	}


	public function run()
	{
		$userAS = ServiceProvider::getUserAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		// looks for user TAdmin1
		$TAdmin1 = $userAS->findUserByName($p, $dat->TAdmin1());
		// creates user if does not exist in DB
		if(is_null($TAdmin1))
		{
			$TAdmin1 = User::createInstance($dat->userDetail($dat->TAdmin1()));
			$TAdmin1->setUsername($dat->TAdmin1());
			$TAdmin1->setWigiiNamespace($p->getWigiiNamespace());
			$this->assertEqual('user TAdmin1 is created in database', $userAS->persistUser($p, $TAdmin1), 1);
			// reads again user to fetch rights
			$TAdmin1 = $userAS->getUser($p, $TAdmin1->getId());
			$this->assertNotNull('user TAdmin1 exists in database', $TAdmin1);

			// creates administrators
			$adminUsername = $TAdmin1->getUser()->getUsername();
			$this->createAdminUsers($dat->principal($adminUsername), $adminUsername);
			// create users
			$this->adminUsersCreateUsers($adminUsername, $dat->TUser());
		}
	}

	protected function createNormalUsers($principal, $usernamePrefix)
	{
		$userAS = ServiceProvider::getUserAdminService();
		$dat = $this->data();
		for($i = 0; $i < 10; $i++)
		{
			$u = User::createInstance($dat->normalUserDetail($principal, $usernamePrefix.$i));
			$this->assertEqual("user $usernamePrefix.$i is created in database", $userAS->persistUser($principal, $u), 1);
		}
	}
	protected function createAdminUsers($principal, $adminUsernamePrefix)
	{
		$userAS = ServiceProvider::getUserAdminService();
		$dat = $this->data();
		for($i = 0; $i < 10; $i++)
		{
			$u = User::createInstance($dat->normalAdminDetail($principal, $adminUsernamePrefix.$i));
			$this->assertEqual("user $adminUsernamePrefix.$i is created in database", $userAS->persistUser($principal, $u), 1);
		}
	}
	protected function adminUsersCreateUsers($adminUsernamePrefix, $usernamePrefix)
	{
		$dat = $this->data();
		for($i = 0; $i < 10; $i++)
		{
			$p = $dat->principal($adminUsernamePrefix.$i);
			$this->createNormalUsers($p, $usernamePrefix.$i);
		}
	}
}
TestRunner::test(new SetupTestDataSet());