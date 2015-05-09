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

class Debug_ZipArchive extends WigiiApiTest
{
	private function d()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("Debug_ZipArchive");
		}
		return $this->_debugLogger;
	}

	public function __construct()
	{
		parent::__construct('Debug_ZipArchive','test ZipArchive class');
	}
	public function run()
	{
		$zip = new ZipArchive();
		$this->assertEqual("create and overwrite zip", $zip->open('./myzip.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, true);
		$zip->addFile('C:/Users/camille/Documents/Medair/Wigii/trunk/data/wigiiSystem/core/apiTest/cleanupAllLocks.php', 'a/b/c/cleanupAllLocks1.php');
		$zip->addFile('C:/Users/camille/Documents/Medair/Wigii/trunk/data/wigiiSystem/core/apiTest/cleanupAllLocks.php', 'a/b/c/d/cleanupAllLocks2.php');
		$zip->addFile('C:/Users/camille/Documents/Medair/Wigii/trunk/data/wigiiSystem/core/apiTest/cleanupAllLocks.php', 'a/b/cleanupAllLocks3.php');
		$zip->close();
	}
}
TestRunner::test(new Debug_ZipArchive());