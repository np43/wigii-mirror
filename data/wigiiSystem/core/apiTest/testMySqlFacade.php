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
 * Tests MySqlFacade
 * Created by CWE on 23 juin 09
 */
class Test_MySqlFacade_Select extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_MySqlFacade_Select','MySqlFacade select methods');
	}
	public function run()
	{
		$this->fail('needs to be changed with new list concept');



		$dbAS = ServiceProvider::getDbAdminService();
		$this->assertNotNull('DbAdminService retrieved', $dbAS);
		try
		{
			$dbCS = $dbAS->getDbConnectionSettings(ServiceProvider::getAuthenticationService()->getMainPrincipal());
		}
		catch(DbAdminServiceException $dbASE)
		{
			$this->fail("Message:".$dbASE->getMessage()." Code:".$dbASE->getCode());
		}
		$this->assertNotNull('DbConnectionSettings retrieved', $dbCS);
		//Display the content of DbConnectionSettings:
		//TechnicalServiceProvider::getDebugLogger("Test_MySqlFacade_Select")->write($dbCS);

		$mysql = TechnicalServiceProvider::getMySqlFacade();
		$this->assertNotNull('MySqlFacade created', $mysql);
		$mysql = TechnicalServiceProvider::getMySqlFacade();
		$this->assertNotNull('MySqlFacade retrieved from cache', $mysql);
		try
		{
			$sqlTest = "
				SELECT u.id_user, u.username
				FROM Users u
				WHERE
					u.userCreator = '1' -- AND u.username='laksjflaksj'";
			$result = $mysql->selectFirst(null, $sqlTest, $dbCS);
			$this->assertNotNull('MySqlFacade selectFirst valid result',$result);
			//TechnicalServiceProvider::getDebugLogger("Test_MySqlFacade_Select")->write($result);
		}
		catch(MySqlFacadeException $mysqlFE)
		{
			$this->fail("Message:".$mysqlFE->getMessage()."\n".$mysqlFE->getMySqlErrorMsg()."\nCode:".$mysqlFE->getCode()." / MySqlErrorNo:".$mysqlFE->getMySqlErrorNo());
		}

		try
		{
			$sqlTest = "
				SELECT u.id_user, u.username
				FROM Users u
				WHERE
					u.userCreator = '1' AND u.username='admin'";
			$result = $mysql->selectOne(null, $sqlTest, $dbCS);
			$this->assertNotNull('MySqlFacade selectOne valid result',$result);
			//TechnicalServiceProvider::getDebugLogger("Test_MySqlFacade_Select")->write($result);
		}
		catch(MySqlFacadeException $mysqlFE)
		{
			$this->fail("Message:".$mysqlFE->getMessage()."\n".$mysqlFE->getMySqlErrorMsg()."\nCode:".$mysqlFE->getCode()." / MySqlErrorNo:".$mysqlFE->getMySqlErrorNo());
		}

		try
		{
			$sqlTest = "
				SELECT g.id_group, g.groupname, g.modulename
				FROM Groups g
				WHERE
					TRUE
					-- AND g.modulename = 'Filemanager'
				;";
			$result = $mysql->selectAll(null, $sqlTest, $dbCS, $rowrList);
			$this->assertNotNull('MySqlFacade selectAll valid result',$result);
			//TechnicalServiceProvider::getDebugLogger("Test_MySqlFacade_Select")->write($result);
		}
		catch(MySqlFacadeException $mysqlFE)
		{
			$this->fail("Message:".$mysqlFE->getMessage()."\n".$mysqlFE->getMySqlErrorMsg()."\nCode:".$mysqlFE->getCode()." / MySqlErrorNo:".$mysqlFE->getMySqlErrorNo());
		}

		try
		{
//			$sqlTest = "
//				SELECT g.id_group, g.groupname, g.modulename
//				FROM Groups g
//				WHERE
//					TRUE
//					-- AND g.modulename = 'Filemanager'
//				;";
//			$sqlTest = "
//				UPDATE Groups g
//				SET g.groupname = 'test'
//				WHERE g.groupname = 'testxxxx' AND FALSE
//				;";
			$sqlTest = "
				INSERT INTO Groups
				SET groupname = 'weAreTheTest'
				;
				";
			$result = $mysql->insertOne(null, $sqlTest, $dbCS);
			TechnicalServiceProvider::getDebugLogger("Test_MySqlFacade_Select")->write($result);
			$sqlTest = "
				INSERT INTO Groups
				SET groupname = 'weAreTheTest'
				;
				";
			$result = $mysql->insertOne(null, $sqlTest, $dbCS);
			TechnicalServiceProvider::getDebugLogger("Test_MySqlFacade_Select")->write($result);
			$sqlTest = "
				DELETE FROM Groups
				WHERE groupname = 'weAreTheTest'
				;";
			$result = $mysql->delete(null, $sqlTest, $dbCS);

			$this->assertNotNull('MySqlFacade query valid result',$result);
			TechnicalServiceProvider::getDebugLogger("Test_MySqlFacade_Select")->write($result);

		}
		catch(MySqlFacadeException $mysqlFE)
		{
			$this->fail("Message:".$mysqlFE->getMessage()."\n".$mysqlFE->getMySqlErrorMsg()."\nCode:".$mysqlFE->getCode()." / MySqlErrorNo:".$mysqlFE->getMySqlErrorNo());
		}
	}
}
TestRunner::test(new Test_MySqlFacade_Select());


