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
 * Tests ServiceProvider
 * Created by CWE on 18 juin 09
 */
class Test_ServiceProvider_ServiceCreationAndCaching extends WigiiApiTest
{
	public function __construct()
	{
		parent::__construct('Test_ServiceProvider_ServiceCreationAndCaching','ServiceProvider service creation and caching');
	}
	public function run()
	{
		$d = DebugLogger::getInstance("Test_ServiceProvider_ServiceCreationAndCaching");
		$this->assertNotNull("DebugLogger created",$d);
		$this->assertNotNull("ExecutionSink created",ExecutionSink::getInstance("Test_ServiceProvider_ServiceCreationAndCaching"));
		ExceptionSink::publish(new ServiceException("test exception to test ExceptionSink"));
		$d->write("<br/><br/>=============================<br/><br/>");
		$this->testServiceCreationAndCaching($d, 'ExceptionSink', TechnicalServiceProvider::getExceptionSink(), TechnicalServiceProvider::getExceptionSink());
		$this->testServiceCreationAndCaching($d, 'AuthenticationService', ServiceProvider::getAuthenticationService(), ServiceProvider::getAuthenticationService());
		$this->testServiceCreationAndCaching($d, 'AuthorizationService', ServiceProvider::getAuthorizationService(), ServiceProvider::getAuthorizationService());
		$this->testServiceCreationAndCaching($d, 'ClientAdminService', ServiceProvider::getClientAdminService(), ServiceProvider::getClientAdminService());
		$this->testServiceCreationAndCaching($d, 'ConfigService', ServiceProvider::getConfigService(), ServiceProvider::getConfigService());
		$this->testServiceCreationAndCaching($d, 'DbAdminService', ServiceProvider::getDbAdminService(), ServiceProvider::getDbAdminService());
		$this->testServiceCreationAndCaching($d, 'ElementService', ServiceProvider::getElementService(), ServiceProvider::getElementService());
		$this->testServiceCreationAndCaching($d, 'ExecutionService', ServiceProvider::getExecutionService(), ServiceProvider::getExecutionService());
		$this->testServiceCreationAndCaching($d, 'GroupAdminService', ServiceProvider::getGroupAdminService(), ServiceProvider::getGroupAdminService());
		$this->testServiceCreationAndCaching($d, 'ModuleAdminService', ServiceProvider::getModuleAdminService(), ServiceProvider::getModuleAdminService());
		$this->testServiceCreationAndCaching($d, 'WigiiNamespaceAdminService', ServiceProvider::getWigiiNamespaceAdminService(), ServiceProvider::getWigiiNamespaceAdminService());
		$this->testServiceCreationAndCaching($d, 'SessionAdminService', ServiceProvider::getSessionAdminService(), ServiceProvider::getSessionAdminService());
		$this->testServiceCreationAndCaching($d, 'SystemConsoleService', ServiceProvider::getSystemConsoleService(), ServiceProvider::getSystemConsoleService());
		$this->testServiceCreationAndCaching($d, 'TranslationService', ServiceProvider::getTranslationService(), ServiceProvider::getTranslationService());
		$this->testServiceCreationAndCaching($d, 'UserAdminService', ServiceProvider::getUserAdminService(), ServiceProvider::getUserAdminService());
		$d->write("<br/><br/>=============================<br/><br/>");
		$rootP = $this->getRootPrincipal();
		$this->assertNotNull("root principal exists", $rootP);
		$d->write("root principal is ".$rootP->getUsername()."::".$rootP->getWigiiNamespace()->getWigiiNamespaceName()."::".$rootP->getWigiiNamespace()->getClient()->getClientName());
		$d->write('done');
	}
	private function testServiceCreationAndCaching($d, $serviceName, $serviceFirstTime, $serviceSecondTime)
	{
		$this->assertNotNull("$serviceName created", $serviceFirstTime);
		$this->assertEqual("$serviceName retrieved from cache", $serviceFirstTime === $serviceSecondTime, true);
		$d->write("<br/>-------------------------<br/>");
	}
}
TestRunner::test(new Test_ServiceProvider_ServiceCreationAndCaching());
