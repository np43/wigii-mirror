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
 * Tests class ConfigServiceImpl
 * Created by CWE on 18 juin 09
 */
class Test_ConfigServiceImpl_dt extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_ConfigServiceImpl_dt','ConfigServiceImpl gets dt config for authenticated user and caches it');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_ConfigServiceImpl_dt");
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();

		$configsrv = ServiceProvider::getConfigService();
		$this->assertIsSet('ConfigService is set',$configsrv);

		$Strings = $configsrv->dt("Strings");

		foreach($Strings->children() as $fieldName=>$field){
			$this->assertEqual("Strings has a unique value field, found: $fieldName", $fieldName, "value");
			$this->assertEqual("Strings value is type text, found: ".$field["type"], $field["type"], "text");
			$d->write(put($field));
		}

		$Addresses = $configsrv->dt("Addresses");
		foreach($Addresses->children() as $fieldName=>$field){
			$d->write($fieldName);
			$d->write(put($field));
		}

		$Addresses = $configsrv->getDataType("Addresses");
		$d->write($Addresses->getDataTypeName());
		foreach($Addresses->getXml()->children() as $fieldName=>$field){
			$d->write($fieldName);
			$d->write(put($field));
		}
	}
}
TestRunner::test(new Test_ConfigServiceImpl_dt());

class Test_ConfigServiceImpl_mf extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_ConfigServiceImpl_mf','ConfigServiceImpl mf method');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_ConfigServiceImpl_mf");
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();

		$configsrv = ServiceProvider::getConfigService();
		$this->assertIsSet('ConfigService is set',$configsrv);

		$module = ServiceProvider::getModuleAdminService()->getModule($p, "Contacts");
		$x1 = $configsrv->mf($p, $module);
		$d->write($x1);
		$x2 = $configsrv->mf($p, $module);
		$d->write($x2);
		$this->assertEqual("fields xml node is retrieved correctly", $x1->asXml(), $x2->asXml());

		$activity = Activity::createInstance("selectSearchBar");
		$x1 = $configsrv->m($p, $module)->selectSearchBar->fields;
		$d->write($x1);
		$x2 = $configsrv->mf($p, $module, $activity);
		$d->write($x2);
		$this->assertEqual("activity fields xml node is retrieved correctly", $x1->asXml(), $x2->asXml());
	}
}
TestRunner::test(new Test_ConfigServiceImpl_mf());

class Test_ConfigServiceImpl_gf extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_ConfigServiceImpl_gf','ConfigServiceImpl gf method');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_ConfigServiceImpl_gf");
		$p = ServiceProvider::getAuthenticationService()->getMainPrincipal();

		$configsrv = ServiceProvider::getConfigService();
		$this->assertIsSet('ConfigService is set',$configsrv);

		$module = ServiceProvider::getModuleAdminService()->getModule($p, "Contacts");
		$group = Group::createInstance(array("id"=>414, "module"=>$module));
		$x1 = $configsrv->gf($p, $group);
		$d->write($x1);
		$x2 = $configsrv->gf($p, $group);
		$d->write($x2);
		$this->assertEqual("fields xml node is retrieved correctly", $x1->asXml(), $x2->asXml());

		$activity = Activity::createInstance("selectSearchBar");
		$x1 = $configsrv->g($p, $group)->selectSearchBar->fields;
		$d->write($x1);
		$x2 = $configsrv->gf($p, $group, $activity);
		$d->write($x2);
		$this->assertEqual("activity fields xml node is retrieved correctly", $x1->asXml(), $x2->asXml());
	}
}
TestRunner::test(new Test_ConfigServiceImpl_gf());