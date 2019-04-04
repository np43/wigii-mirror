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
 * cleanups test data set in database
 * Created by CWE on 5 sept. 09
 */
class CleanupTestDataSet extends WigiiApiTest
{
	private $_debugLogger;
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("CleanupTestDataSet");
		}
		return $this->_debugLogger;
	}
	public function __construct()
	{
		parent::__construct('CleanupTestDataSet','Cleanups Wigii API test data in database');
	}


	public function run()
	{
		$userAS = ServiceProvider::getUserAdminService();
		$dat = $this->data();
		$p = $dat->principal($dat->admin());

		// looks for user TAdmin1
		$TAdmin1 = $userAS->findUserByName($p, $dat->TAdmin1());
		// deletes user if exists in DB
		if(!is_null($TAdmin1))
		{
			$this->assertEqual('user TAdmin1 is deleted from database', $userAS->deleteUser($p, $TAdmin1->getUser()->getId()), 1);
			$this->assertNull('user TAdmin1 does not exist anymore in database', $userAS->getUser($p, $TAdmin1->getUser()->getId()));
		}
	}
}
TestRunner::test(new CleanupTestDataSet());