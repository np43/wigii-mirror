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

/**
 * Tests GroupAdminService
 * Created by CWE on sept 22
 */
class Debug_GroupAdminService_getAllGroups extends WigiiApiTest implements GroupPTree
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_getAllGroups");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_getAllGroups','debug GroupAdminService getAllGroups method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());
		//$p = $dat->principal("test", null, "test");
		//$p = $dat->principal("bla", null, "bla");

		$listFilter = ListFilter::createInstance();
		$gExp = 'emailNotification != "" || subscription != ""';
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$gExp = $parser->createLogExpFromString($gExp);
		$listFilter->setFieldSelectorLogExp($gExp);

		//$groupAS->getAllGroups($p, $mas->getModule($p, 'Contacts'), $this, $listFilter);
		$groupAS->getAllGroups($p, $mas->getModule($p, 'Contacts'), $this, null);

		$groupP = $groupAS->getGroup($p, 222);
		$this->assertIsSet('getGroup(222) exists in DB', $groupP);
		$this->assertEqual('getGroup(222) is test56', $groupP->getGroup()->getGroupName(), 'test56');

	}
	public function addGroupP($groupP, $parentGroup)
	{
		$rights = $groupP->getRights();
		if(isset($rights))
		{
			$rights = ', canModify='.$rights->canModify().', canWriteElement='.$rights->canWriteElement().', canShareElement='.$rights->canShareElement();
		}
		else $rights = '';
		if(isset($parentGroup))
		{
			$parentGroup = ", child of ".$parentGroup->getGroupName();
		}
		else $parentGroup = '';
		$group = $groupP->getGroup();
		$this->d()->write($group->getId().":".$group->getGroupName().$rights.$parentGroup);
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_GroupAdminService_getAllGroups());


class Debug_GroupAdminService_persistGroup extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_persistGroup");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_persistGroup','debug GroupAdminService persistGroup method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$module = $mas->getModule($p, 'Contacts');

		$groupP = $groupAS->getGroup($p, 222);
		$this->assertIsSet('getGroup(222) exists in DB', $groupP);
		$this->assertEqual('getGroup(222) is test56', $groupP->getGroup()->getGroupName(), 'test56');

		//$groupAS->deleteGroup($p, 340);

		$parentGroup = $groupP->getGroup();

		$g1 = Group::createInstance();
		$g1->setGroupParentId($parentGroup->getId());
		$g1->setGroupName('cwe_g1');
		$g1->setModule($module);
		$g1->setWigiiNamespace($p->getWigiiNamespace());
		$g1D = GroupDetail::createInstance();
		$g1D->setDescription("test cwe g1");
		$g1D->setHtmlContent("<p>aie aie aie</p>");
		$g1->setDetail($g1D);

		$groupAS->persistGroup($p, $g1);
		$this->assertNotNull("group g1 id has been set", $g1->getId());

		unset($groupP);
		$groupP = $groupAS->getGroup($p, $g1->getId());
		$this->assertIsSet("group g1 has been inserted in db", $groupP);
		$this->assertEqual("id inserted and read are equal", $g1->getId(), $groupP->getGroup()->getId());
		$this->printGroupP($p, $groupP);

		// changes parent
		$g1->setGroupParentId($parentGroup->getGroupParentId());
		$g1->setGroupName('cwe_g1_updated');
		$g1->getDetail()->setDescription("test cwe g1 updated");

		$groupAS->persistGroup($p, $g1);
		unset($groupP);
		$groupP = $groupAS->getGroup($p, $g1->getId());
		$this->assertIsSet("group g1 has been updated in db", $groupP);
		$this->printGroupP($p, $groupP);

		// updates only description
		$fsl = FieldSelectorListArrayImpl::createInstance();
		$fsl->addFieldSelector("description");
		$g1->getDetail()->setDescription("test cwe g1 description updated only");

		$groupAS->persistGroup($p, $g1, $fsl);
		unset($groupP);
		$groupP = $groupAS->getGroup($p, $g1->getId(), $fsl);
		$this->assertIsSet("group g1 has been updated in db", $groupP);
		$this->printGroupP($p, $groupP);

		$groupAS->deleteGroup($p, $g1->getId());

		unset($groupP);
		$groupP = $groupAS->getGroup($p, $g1->getId());
		$this->assertNotSet("group has been deleted", $groupP);
	}
	protected function printGroupP($principal, $groupP)
	{
		$rights = $groupP->getRights();
		if(isset($rights))
		{
			$rights = ', canModify='.$rights->canModify().', canWriteElement='.$rights->canWriteElement().', canShareElement='.$rights->canShareElement();
		}
		else $rights = '';
		$group = $groupP->getGroup();
		$parentGroupId = $group->getGroupParentId();
		if(isset($parentGroupId))
		{
			$parentGroup = ServiceProvider::getGroupAdminService()->getGroupWithoutDetail($principal, $parentGroupId);
			$parentGroup = ", child of ".$parentGroup->getGroupName();
		}
		else $parentGroup = '';
		$this->d()->write($group->getId().":".$group->getGroupName().$rights.$parentGroup);
	}
}
TestRunner::test(new Debug_GroupAdminService_persistGroup());

class Debug_GroupAdminService_setUserRight extends WigiiApiTest implements UserRPList
{
	private $userRPCounter;

	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_setUserRight");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_setUserRight','debug GroupAdminService setUserRight method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$userAS = ServiceProvider::getUserAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$module = $mas->getModule($p, 'Contacts');

		$groupP = $groupAS->getGroup($p, 222);
		$this->assertIsSet('getGroup(222) exists in DB', $groupP);
		$this->assertEqual('getGroup(222) is test56', $groupP->getGroup()->getGroupName(), 'test56');

		// creates user ugrTest1
		$ugrTest1 = User::createInstance($dat->normalUserDetail($p, "ugrTest1"));
		try
		{
			$duplicateUser = $userAS->findUserByName($p, $ugrTest1->getUsername());
			if(isset($duplicateUser))
			{
				$userAS->deleteUser($p, $duplicateUser->getUser()->getId());
			}
		}
		catch(Exception $e){/*does nothing if cleanup fails*/};
		$this->assertEqual('user ugrTest1 is created in database', $userAS->persistUser($p, $ugrTest1), 1);

		// creates share only rights
		$groupId = $groupP->getGroup()->getId();
		$ugr = UGR::createInstance($groupId, $ugrTest1->getId());
		$ugr->setCanShareElement(true);

		// adds participant
		$groupAS->setUserRight($p, $ugr);
		$this->checkAppliedUGROnUser($ugrTest1, $ugr);

		// getAllUsers
		$p = $dat->principal($dat->admin());
		$this->userRPCount = 0;
		$listFilter = ListFilter::createInstance();
		$fsl = FieldSelectorListArrayImpl::createInstance();
		$fsl->addFieldSelector("id");
		$fsl->addFieldSelector("username");
		$fsl->addFieldSelector("wigiiNamespace");
		$listFilter->setFieldSelectorList($fsl);
		$groupAS->getAllUsers($p, $groupId, $this, $listFilter);

		// removes participant
		$groupAS->removeUser($p, $ugr->getGroupId(), $ugr->getUserId());
		$this->checkUserHasNoRightsOnGroup($ugrTest1, $ugr->getGroupId());

		// cleanup user
		$p = $dat->principal($dat->admin());
		$userAS->deleteUser($p, $ugrTest1->getId());
	}
	protected function checkAppliedUGROnUser($user, $ugr)
	{
		$dat = $this->data();

		// logs as user
		$p = $dat->principal($user->getUsername());
		// reads group with rights
		$groupP = ServiceProvider::getGroupAdminService()->getGroup($p, $ugr->getGroupId());
		$this->assertIsSet("group exists in db", $groupP);
		$pRights = $groupP->getRights();
		$this->assertIsSet("user with new UGR can read group", $pRights);
		$val = ArgValidator::getInstance();
		$this->assertEqual("user should have more or equal canModify rights as set UGR", $val->checkLessOrEqualRight($ugr->canModify(), $pRights->canModify()), true);
		$this->assertEqual("user should have more or equal canWriteElement rights as set UGR", $val->checkLessOrEqualRight($ugr->canWriteElement(), $pRights->canWriteElement()), true);
		$this->assertEqual("user should have more or equal canShareElement rights as set UGR", $val->checkLessOrEqualRight($ugr->canShareElement(), $pRights->canShareElement()), true);
	}
	protected function checkUserHasNoRightsOnGroup($user, $groupId)
	{
		$dat = $this->data();

		// logs as user
		$p = $dat->principal($user->getUsername());
		// reads group with rights
		$groupP = ServiceProvider::getGroupAdminService()->getGroup($p, $groupId);
		$this->assertIsSet("group exists in db", $groupP);
		$this->assertNull("user has no rights on group", $groupP->getRights());
	}

	// UserRPList implementation

	public function addUserRP($userRP)
	{
		if(isset($userRP))
		{
			$pRights = $userRP->getRights();
			$userR = $userRP->getUserR();
			$uRights = $userR->getRights();
			$user = $userR->getUser();
			$username = $user->getUsername();
			if(isset($pRights))
			{
				if($pRights->isOwner())
				{
					$isOwner="is owner";
				}
				else
				{
					$isOwner = "is not owner";
				}
				$userlabel = "userlabel=".$user->getDetail()->getUserlabel();
			}
			else
			{
				$isOwner="has no rights";
				$userlabel = "";
			}
			if(isset($uRights))
			{
				$groupId = $uRights->getGroupId();
				$rights = 'canModify='.$uRights->canModify().', canWriteElement='.$uRights->canWriteElement().', canShareElement='.$uRights->canShareElement();
			}
			else $this->fail("user should have a right on group");
			$this->d()->write("user $username ($userlabel) $rights on group $groupId, principal $isOwner");
			$this->assertEqual("UGR->getId equals User->getId", $uRights->getUserId(), $user->getId());
			$this->userRPCount++;
		}
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {return $this->userRPCount;}
}
TestRunner::test(new Debug_GroupAdminService_setUserRight());

class Debug_GroupAdminService_getSelectedGroupsWithoutDetail extends WigiiApiTest implements GroupList
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_getSelectedGroupsWithoutDetail");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_getSelectedGroupsWithoutDetail','debug GroupAdminService getSelectedGroupsWithoutDetail method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$gExp = 'module = Contacts AND groupname LIKE %test%';
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$gExp = $parser->createLogExpFromString($gExp);
		$this->d()->write("parsed group exp is ".$parser->logExpToString($gExp));
		$this->d()->write($gExp->displayDebug());
		$groupAS->getSelectedGroupsWithoutDetail($p, $gExp, $this);
	}
	public function addGroup($group)
	{
		$this->d()->write($group->getId().":".$group->getGroupName());
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_GroupAdminService_getSelectedGroupsWithoutDetail());

class Debug_GroupAdminService_getSelectedGroupsField extends WigiiApiTest implements ValueList
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_getSelectedGroupsField");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_getSelectedGroupsField','debug GroupAdminService getSelectedGroupsField method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$p = $this->getRootPrincipal();
		//$dat = $this->data();
		//$p = $dat->principal($dat->admin());
		$gExp = 'module = Contacts AND groupname LIKE %test%';
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$gExp = $parser->createLogExpFromString($gExp);
		$fs = FieldSelector::createInstance('groupname');
		$groupAS->getSelectedGroupsField($p, $fs, $gExp, 2, $this);
	}
	public function addValue($val)
	{
		$this->d()->write($val);
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_GroupAdminService_getSelectedGroupsField());

class Debug_GroupAdminService_getGroupsField extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_getGroupsField");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_getGroupsField','debug GroupAdminService getGroupsField method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		// fetches group list
		$gExp = 'module = Contacts AND groupname = test';
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$gExp = $parser->createLogExpFromString($gExp);
		$groupList = GroupListArrayImpl::createInstance();
		$groupAS->getSelectedGroupsWithoutDetail($p, $gExp, $groupList);

		// fetches group field
		$p = $this->getRootPrincipal();
		$fs = FieldSelector::createInstance('groupname');
		$value2stringMapper = TechnicalServiceProvider::getValueListStringMapper(';');
		$groupAS->getGroupsField($p, $fs, $groupList, -1, $value2stringMapper);
		$this->d()->write($value2stringMapper->getValueListString());
		TechnicalServiceProvider::recycleValueListStringMapper($value2stringMapper);
	}
}
TestRunner::test(new Debug_GroupAdminService_getGroupsField());

class Debug_GroupAdminService_str_update extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_str_update");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_str_update','debug str_update function');
	}
	public function run()
	{
		$this->assertEqual("str_update ADD ok", str_update("camille.weber@bluewin.ch, toto@swiss.ch;    lionel@ekc.ch", "lionel@ekc.ch:toto@swiss.ch;jean@c.ch", SUPDOP_ADD), "camille.weber@bluewin.ch,toto@swiss.ch,lionel@ekc.ch,jean@c.ch");
		$this->assertEqual("str_update SET ok", str_update("camille.weber@bluewin.ch, toto@swiss.ch", "lionel@ekc.ch", SUPDOP_SET), "lionel@ekc.ch");
		$this->assertEqual("str_update CAT ok", str_update("camille.weber@bluewin.ch, toto@swiss.ch;    lionel@ekc.ch", "lionel@ekc.ch:,  jean@c.ch", SUPDOP_CAT), "camille.weber@bluewin.ch,toto@swiss.ch,lionel@ekc.ch,lionel@ekc.ch,jean@c.ch");
		$this->assertEqual("str_update DEL ok", str_update("camille.weber@bluewin.ch, toto@swiss.ch;    lionel@ekc.ch", "lionel@ekc.ch, 	jean@c.ch, :", SUPDOP_DEL), "camille.weber@bluewin.ch,toto@swiss.ch");
		$this->assertEqual("str_update DEL ok", str_update("camille.weber@bluewin.ch, toto@swiss.ch;    lionel@ekc.ch", "lionel@ekc.ch, 	jean@c.ch, :camille.weber@bluewin.ch, toto@swiss.ch;    lionel@ekc.ch", SUPDOP_DEL), "");
	}
}
TestRunner::test(new Debug_GroupAdminService_str_update());
class Debug_GroupAdminService_emailNotification extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_emailNotification");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_emailNotification','debug GroupAdminService addToEmailNotification and eraseFromEmailNotification methods');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$groupP = $groupAS->getGroup($p, 222);
		$this->assertIsSet('getGroup(222) exists in DB', $groupP);

		// adds email to notification
		$groupAS->addToEmailNotification($p, 222, "test@test.ch, test2@test.ch");
		$this->d()->write($groupAS->getGroup($p, 222)->getGroup()->getDetail()->getEmailNotification());
		$groupAS->addToEmailNotification($p, 222, "test@test.ch, test2@test.ch ;   , : test3@test.ch");
		$this->d()->write($groupAS->getGroup($p, 222)->getGroup()->getDetail()->getEmailNotification());

		// removes email to notification
		$groupAS->eraseFromEmailNotification($p, 222, "test@test.ch, test2@test.ch/test3@test.ch");
		$this->d()->write($groupAS->getGroup($p, 222)->getGroup()->getDetail()->getEmailNotification());
	}
}
TestRunner::test(new Debug_GroupAdminService_emailNotification());

class Debug_GroupAdminService_setGroupDescription extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_setGroupDescription");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_setGroupDescription','debug GroupAdminService setGroupDescription method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$groupP = $groupAS->getGroup($p, 222);
		$this->assertIsSet('getGroup(222) exists in DB', $groupP);

		// sets description
		$groupAS->setGroupDescription($p, 222, "test d'une description accentuée!");
		$this->assertEqual("description is set", $groupAS->getGroup($p, 222)->getGroup()->getDetail()->getDescription(), "test d'une description accentuée!");
	}
}
TestRunner::test(new Debug_GroupAdminService_setGroupDescription());

class Debug_GroupAdminService_getAllUsers extends WigiiApiTest implements UserRPList
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_getAllUsers");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_getAllUsers','debug GroupAdminService getAllUsers method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());
		$listFilter = ListFilter::createInstance();
		$uExp = "username LIKE %Lio%";
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$uExp = $parser->createLogExpFromString($uExp);
		$listFilter->setFieldSelectorLogExp($uExp);
		$groupAS->getAllUsers($p, 216, $this, $listFilter);
	}

	public function addUserRP($userRP)
	{
		$this->d()->write($userRP->getUserR()->getUser()->getUsername());
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_GroupAdminService_getAllUsers());

class Debug_GroupAdminService_getSelectedGroups extends WigiiApiTest implements GroupPList
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupAdminService_getSelectedGroups");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupAdminService_getSelectedGroups','debug GroupAdminService getSelectedGroups method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		//$gExp = 'module = Contacts AND (id_group_parent = 0 OR id_group_parent = NULL)';
		$gExp = 'module = Contacts AND (groupname LIKE test% OR (id_group_parent = 0 OR id_group_parent = NULL))';
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$gExp = $parser->createLogExpFromString($gExp);
		$this->d()->write($parser->logExpToString($gExp));

		// get groups
		$listFilter = ListFilter::createInstance();
		$fieldSelectorList = FieldSelectorListArrayImpl::createInstance();
		$this->assertIsSet('fieldSelectorList is created', $fieldSelectorList);
		$fieldSelectorList->addFieldSelector('groupname');
		$fieldSelectorList->addFieldSelector('emailNotification');

		$listFilter->setFieldSelectorList($fieldSelectorList);
		$this->assertIsSet('list filter has a fieldSelectorList', $listFilter->getFieldSelectorList());
		$this->assertIsSet('fieldSelectorList has an iterator', $listFilter->getFieldSelectorList()->getListIterator());

		$listFilter->setFieldSelectorLogExp($gExp);
		$this->assertIsSet('list filter has a field selector logExp', $listFilter->getFieldSelectorLogExp());

		$pageSize = 5;
		$listFilter->setPageSize($pageSize);
		$this->assertEqual("page size is set to $pageSize", $listFilter->getPageSize(), $pageSize);

		$page = 1; $eltCount = 0;
		$listFilter->setDesiredPageNumber($page);
		$this->assertEqual("page is set to $page", $page, $listFilter->getDesiredPageNumber());
		$groupCount += $groupAS->getSelectedGroups($p, $listFilter, $this);
		$this->d()->write("p.$page retrieved $groupCount groups on a total of ".$listFilter->getTotalNumberOfObjects());
		if($groupCount > 0)
		{
			$page++;
			while(($groupCount < $listFilter->getTotalNumberOfObjects()) && ($page < 100))
			{
				$listFilter->setDesiredPageNumber($page);
				$groupCount += $groupAS->getSelectedGroups($p, $listFilter, $this);
				$this->d()->write("p.$page retrieved $groupCount groups on a total of ".$listFilter->getTotalNumberOfObjects());
				$page++;
			}
		}
	}
	public function addGroupP($groupP)
	{
		$rights = $groupP->getRights();
		if(isset($rights))
		{
			$rights = ', canModify='.$rights->canModify().', canWriteElement='.$rights->canWriteElement().', canShareElement='.$rights->canShareElement();
		}
		else $rights = '';
		$group = $groupP->getGroup();
		$this->d()->write($group->getId().":".$group->getGroupName().$rights.(is_null($group->getDetail()) ? ", no detail available" : ", emailNotification: ".$group->getDetail()->getEmailNotification()));
	}
	public function getListIterator() {$this->fail('unsupported operation');}
	public function isEmpty() {$this->fail('unsupported operation');}
	public function count() {$this->fail('unsupported operation');}
}
TestRunner::test(new Debug_GroupAdminService_getSelectedGroups());

class Debug_GroupListTreeArrayImpl_visitInDepth extends WigiiApiTest implements TreeInDepthVisitor
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_GroupListTreeArrayImpl_visitInDepth");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Debug_GroupListTreeArrayImpl_visitInDepth','debug GroupListTreeArrayImpl visitInDepth method');
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		$gExp = '!module = NULL';
		$parser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$gExp = $parser->createLogExpFromString($gExp);
		$this->d()->write("parsed group exp is ".$parser->logExpToString($gExp));
		$this->d()->write($gExp->displayDebug());
		$gTree = GroupListTreeArrayImpl::createInstance();
		$groupAS->getSelectedGroupsWithoutDetail($p, $gExp, $gTree);
		$gTree->visitInDepth($this);
	}
	public function actOnTreeNode($object, $depth, $numberOfChildren)
	{
		$this->d()->write(str_repeat("--", $depth).$object->getId().":".$object->getGroupParentId().":".$object->getGroupName());
		return true;
	}
	public function actAfterTreeNode($object, $depth, $visitedAllChildren)
	{
		return true;
	}
}
TestRunner::test(new Debug_GroupListTreeArrayImpl_visitInDepth());