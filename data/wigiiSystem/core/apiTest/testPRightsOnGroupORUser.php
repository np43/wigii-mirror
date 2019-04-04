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
 * tests testPRightsOnGroupORUser
 * Created by LWR on 18 november 2010
 */
class Debug_testPRightsOnGroupORUser extends WigiiApiTest {
	
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_testPRightsOnGroupORUser");
		}
		return $this->_debugLogger;
	}

	public function __construct($testId=null, $testName=null)
	{
		parent::__construct((!isset($testId)?'Debug_testPRightsOnGroupORUser':$testId),(!isset($testName)?'Debug_testPRightsOnGroupORUser':$testName));
		$this->wigiiBag = array();
	}
	public function run()
	{
		$groupAS = ServiceProvider::getGroupAdminService();
		$userAS = ServiceProvider::getUserAdminService();
		$mas = ServiceProvider::getModuleAdminService();
		$authS = ServiceProvider::getAuthenticationService();
		$p = $authS->getMainPrincipal();
		
		$this->assertIsSet('p', $p);
		$this->assertEqual('p is logued with:'.$p->getWigiiNamespace()->getWigiiNamespaceName()." : ".$p->getUsername(), false, $authS->isMainPrincipalMinimal());
		$this->d()->write("user id: ".$p->getUserId());
		$this->d()->write("real user id: ".$p->getRealUserId());
		$this->d()->write("----------------------------------------------------");
		
		$groupId = 601861; //id of a group in board
		$groupP = $groupAS->getGroup($p, $groupId);
		$this->assertIsSet('group exist', $groupP);
		$this->d()->write(put($groupP));
		$this->assertNull('retreive group has no rights', $groupP->getRights());
		
		$userId = 600529; //id of a group in board
		$userP = $userAS->getUser($p, $userId);
		$this->assertIsSet('user exist', $userP);
		$this->d()->write(put($userP));
		$this->assertNull('retreive user has no rights', $userP->getRights());
		return;
		
		$groupAS->getAllGroups($p, $mas->getModule($p, 'Contacts'), $this);
		//$this->firstGroup = $groupAS->getGroup($p, 280)->getGroup();
		$this->assertIsSet('first group is selected', $this->firstGroup);
		$this->d()->write("gets all elements in group ".$this->firstGroup->getId().":".$this->firstGroup->getGroupName());

		$eltS = ServiceProvider::getElementService();
		$eltS->getAllElementsInGroup($p, $this->firstGroup, $this);
	}

}
TestRunner::test(new Debug_testPRightsOnGroupORUser());
