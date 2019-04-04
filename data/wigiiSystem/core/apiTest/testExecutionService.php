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
 * Tests ExecutionService
 * Created by LWR on 21 july 09
 */
class Test_ExecutionService_main extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_ExecutionService_main','ExecutionService main methods');
	}
	public function run()
	{

		$debL = DebugLogger::getInstance("Test_ExecutionService_main");

		$eS = ServiceProvider::getExecutionService();
		$authS = ServiceProvider::getAuthenticationService();
		$this->assertNotNull('ExecutionService retrieved', $eS);

		$authS->logout();

		$p = $eS->getExecPrincipal();

		$this->assertEqual("ExecPrincipal need to be a MinimalPrincipal in the begining, found: ".$p->getUsername(), $authS->isMainPrincipalMinimal(), true);

		$authS->login("admin", "admin", "LOCAL");

		$p = $eS->getExecPrincipal();

		$this->assertEqual("ExecPrincipal should be log as admin, found: ".$p->getUsername(), $p->getUsername(), "admin");
		$this->assertEqual("ExecPrincipal souhld not be a MinimalPrincipal after loged as admin, found: ".put($authS->isMainPrincipalMinimal()), $authS->isMainPrincipalMinimal(), false);

//		$this->fail("Test not implemented yet");
	}
}

TestRunner::test(new Test_ExecutionService_main());


