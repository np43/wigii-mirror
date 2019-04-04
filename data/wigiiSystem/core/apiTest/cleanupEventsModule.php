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
 * Removes the Events Module for admin user.
 * Created by CWE on 17 fév. 2010
 */
class CleanupEventsModule extends WigiiApiTest
{
	private $_debugLogger;
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("CleanupEventsModule");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('CleanupEventsModule','Removes Events module for admin user');
	}


	public function run()
	{
		$rootPrincipal = $this->getRootPrincipal();
		$dat = $this->data();
		$dat->principal($dat->admin());
		$userAS = ServiceProvider::getUserAdminService();
		// checks UserAdminService is instance of UserAdminServiceImpl
		$this->assertEqual("UserAdminService instanceof UserAdminServiceImpl", $userAS instanceof UserAdminServiceImpl, true);

		// updates module access
		$mySqlF = TechnicalServiceProvider::getMySqlFacade();
		$dbCS = ServiceProvider::getDbAdminService()->getDbConnectionSettings($rootPrincipal);
		$mySqlF->updateField($rootPrincipal, FieldSelector::createInstance("moduleAccess"), "Users",
			LogExp::createEqualExp(FieldSelector::createInstance("username"), $dat->admin()),
			"Events", SUPDOP_DEL, $userAS->getSqlWhereClauseBuilderForSelectUsers(),
			FieldSelector::createInstance("id"), $dbCS,
			$readSep='/[\\s,;\\/:\\\\]+/', $writeSep=';');

		// checks access to module TestCase for admin user has been removed
		ServiceProvider::getAuthenticationService()->logout();
		$this->assertNull("admin access to Events module has been removed", $dat->principal($dat->admin())->getModuleAccess("Events"));
	}
}
TestRunner::test(new CleanupEventsModule());