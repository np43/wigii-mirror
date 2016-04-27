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
 * Tests UserAdminService
 * Created by CWE on sept 21
 */
class Debug_UserAdminService_getAllUsers extends WigiiApiTest implements UserPList
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_UserAdminService_getAllUsers");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_UserAdminService_getAllUsers','debug UserAdminService getAllUsers method');
	}
	public function run()
	{
		$userAS = ServiceProvider::getUserAdminService();
		$dat = $this->data();
		//$p = $dat->principal($dat->TAdmin1());

		//$this->assertEqual('TAdmin1 owns 10 users', $userAS->getAllUsers($p, $this), 10);

		$p = $dat->principal($dat->admin());

		$listFilter = ListFilter::createInstance();
		$uExp = "groupCreator LIKE %FileManager% && userCreator = 1";
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$uExp = $parser->createLogExpFromString($uExp);
		$listFilter->setFieldSelectorLogExp($uExp);

		$userAS->getAllUsers($p, $this, $listFilter);
	}
	public function addUserP($userP)
	{
		$this->d()->write($userP->getUser()->getUsername().", owner=".$userP->getRights()->isOwner());
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_UserAdminService_getAllUsers());

class Debug_UserAdminService_getAllUserUsers extends WigiiApiTest implements UserURPList
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_UserAdminService_getAllUserUsers");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_UserAdminService_getAllUserUsers','debug UserAdminService getAllUserUsers method');
	}
	public function run()
	{
		$userAS = ServiceProvider::getUserAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$userAS->getAllUserUsers($p, 3, $this);
		$userAS->getAllUserUsers($p, 1, $this);

		$listFilter = ListFilter::createInstance();
		$uExp = "username LIKE %test%";
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$uExp = $parser->createLogExpFromString($uExp);
		$listFilter->setFieldSelectorLogExp($uExp);
		$userAS->getAllUserUsers($p, 1, $this, $listFilter);
	}
	public function addUserURP($userURP)
	{
		$uRight = $userURP->getUserUR();
		//$this->d()->write($userURP->displayDebug());
		$userIsOwner = $uRight->getRights()->isOwner();
		if(!is_null($userURP->getRights())) $principalIsOwner = $userURP->getRights()->isOwner(); else $principalIsOwner = false;
		$this->d()->write($uRight->getUser()->getUsername().", user owner=$userIsOwner, principal owner=$principalIsOwner");
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_UserAdminService_getAllUserUsers());

class Debug_UserAdminService_manageUserUserRight extends WigiiApiTest implements UserURPList
{
	private $foundNewUser;
	private $newUserId;

	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_UserAdminService_manageUserUserRight");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_UserAdminService_manageUserUserRight','debug UserAdminService setUserOwnership and removeUserOwnership methods');
	}
	public function run()
	{
		$userAS = ServiceProvider::getUserAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		// clean up
		try
		{
			$user = $userAS->findUserByName($p, "UUR-test");
			if(isset($user)) $userAS->deleteUser($p, $user->getUser()->getId());
		}
		catch(Exception $e){/*silent*/}

		// creates new User
		$user = User::createInstance();
		$user->setUsername("UUR-test");
		$userAS->persistUser($p, $user);

		// sets user 3 as owner of new created user
		$uur = UUR::createInstance(3, $user->getId());
		$userAS->setUserOwnership($p, $uur->getUser1Id(), $uur->getUser2Id());

		// looks if new ownership link is correctly set
		$this->newUserId = $user->getId();
		$this->foundNewUser = false;
		$userAS->getAllUserUsers($p, 3, $this);
		$this->d()->write($this->foundNewUser);
		$this->assertEqual("new inserted user is correctly linked", $this->foundNewUser, true);

		// removes ownership link
		$this->assertNotNull("user1Id is set", $uur->getUser1Id());
		$this->assertNotNull("user2Id is set", $uur->getUser2Id());
		$userAS->removeUserOwnership($p, $uur->getUser1Id(), $uur->getUser2Id());
		$this->foundNewUser = false;
		$userAS->getAllUserUsers($p, 3, $this);
		$this->assertEqual("new inserted user is correctly unlinked", $this->foundNewUser, false);

		// removes ownership link
		try
		{
			$userAS->removeUserOwnership($p, $p->getAttachedUser()->getId(), $user->getId());
		}
		catch(UserAdminServiceException $uas)
		{
			$this->assertEqual("DATA_INTEGRITY_ERROR is thrown", $uas->getCode(), UserAdminServiceException::DATA_INTEGRITY_ERROR);
		}

		// deletes new user
		$userAS->deleteUser($p, $user->getId());
	}
	public function addUserURP($userURP)
	{
		$this->d()->write($userURP->getUserUR()->getUser()->getId());
		if($userURP->getUserUR()->getUser()->getId() == $this->newUserId) $this->foundNewUser = true;
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_UserAdminService_manageUserUserRight());

class Debug_UserAdminService_getUser extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_UserAdminService_getUser");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_UserAdminService_getUser','debug UserAdminService getUserWithoutDetail method');
	}
	public function run()
	{
		$userAS = ServiceProvider::getUserAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$user = $userAS->getUserWithoutDetail($p, 3);
		$this->d()->write($user->displayDebug());
	}
}

TestRunner::test(new Debug_UserAdminService_getUser());

class Debug_UserAdminService_persistUser extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_UserAdminService_persistUser");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_UserAdminService_persistUser','debug UserAdminService persitUser method');
	}
	public function run()
	{
		$userAS = ServiceProvider::getUserAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		// looks for user TAdmin1
		$TAdmin1 = $userAS->findUserByName($p, $dat->TAdmin1());
		// creates user if does not exist in DB
		$shouldDeleteUser = false;
		if(is_null($TAdmin1))
		{
			$TAdmin1 = User::createInstance($dat->userDetail($dat->TAdmin1()));
			$TAdmin1->setUsername($dat->TAdmin1());
			$TAdmin1->setWigiiNamespace($p->getWigiiNamespace());
			$this->assertEqual('user TAdmin1 is created in database', $userAS->persistUser($p, $TAdmin1), 1);
			// reads again user to fetch rights
			$TAdmin1 = $userAS->getUser($p, $TAdmin1->getId());
			$this->assertNotNull('user TAdmin1 exists in database', $TAdmin1);
			$shouldDeleteUser = true;
		}
		$TAdmin1 = $TAdmin1->getUser();
		// updates description
		$description = $TAdmin1->getDetail()->getDescription();
		$fsl = FieldSelectorListArrayImpl::createInstance();
		$fsl->addFieldSelector("description");
		$TAdmin1->getDetail()->setDescription($description." updated!");
		$TAdmin1->setUsername("admin"); // this should not be updated, if bug then exception
		$userAS->persistUser($p, $TAdmin1, $fsl);
		$TAdmin1 = $userAS->getUser($p, $TAdmin1->getId(), $fsl);
		$this->assertNotNull('user TAdmin1 exists in database', $TAdmin1);
		$TAdmin1 = $TAdmin1->getUser();
		$this->assertEqual("description has been updated", $TAdmin1->getDetail()->getDescription(), $description." updated!");

		// cleanup
		$TAdmin1->getDetail()->setDescription($description);
		$userAS->persistUser($p, $TAdmin1, $fsl);
		if($shouldDeleteUser)
		{
			$userAS->deleteUser($p, $TAdmin1->getId());
		}
	}
}
TestRunner::test(new Debug_UserAdminService_persistUser());