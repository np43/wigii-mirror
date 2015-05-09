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
 * FieldSelectorLogExpParser tests
 * Created by CWE on 23 déc. 09
 */
class Test_FieldSelectorLogExpParser extends WigiiApiTest
{
	private $d;

	public function __construct()
	{
		parent::__construct('Test_FieldSelectorLogExpParser','FieldSelectorLogExpParser->createLogExpFromString');
		$this->d = DebugLogger::getInstance("Test_FieldSelectorLogExpParser");
	}
	public function run()
	{
		$s = 'INGR(field1.subfield1 = "toto et titi" || !!!field2 <= 3) && NOTING(field3.subfield2 IN ("A A", B, C) || field4 NOTIN (166, 255, 34))';
		//$s = '!f != 3';
		$fsLogExpParser = TechnicalServiceProvider::getFieldSelectorLogExpParser();
		$this->assertIsSet("FieldSelectorLogExp parser is created", $fsLogExpParser);

		$this->d->write("will create a LogExp from '$s'");
		$logExp = $fsLogExpParser->createLogExpFromString($s);
		$this->assertNotNull("LogExp is created and not null", $logExp);

		$this->d->write($logExp->displayDebug());
		$this->d->write("Created LogExp is: ");
		$this->d->write($fsLogExpParser->logExpToString($logExp));
	}
}
TestRunner::test(new Test_FieldSelectorLogExpParser());