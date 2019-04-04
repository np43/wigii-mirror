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
 * Tests Wigii API test data set and configuration
 * Created by CWE on 5 sept. 09
 */
class Test_WigiiApiTestConfig extends WigiiApiTest
{
	private $_debugLogger;
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Test_WigiiApiTestConfig");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('Test_WigiiApiTestConfig','Wigii API test data set and configuration');
	}


	public function run()
	{
		$data = $this->data();
		$this->assertUserDefined('admin', $data->admin(), $data);
		$this->assertNotNull('admin user can log in', $data->principal($data->admin()));
		$this->assertUserDefined('TAdmin1', $data->TAdmin1(), $data);
	}

	// sub tests
	public function assertUserDefined($expectedUsername, $username, $data)
	{
		$this->d()->write("asserts $expectedUsername is defined");
		$this->assertNotNull('user name is defined', $username);
		$this->assertNotNull('password is defined', $data->password($username));
	}
}
TestRunner::test(new Test_WigiiApiTestConfig());