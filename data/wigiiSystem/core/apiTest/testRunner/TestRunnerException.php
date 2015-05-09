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
 * wigii system test runner exception
 * Created by CWE on 18 juin 09
 */
class TestRunnerException extends ServiceException
{
	const EQUAL_ASSERTION_FAILED = 2100;
	const NOT_EQUAL_ASSERTION_FAILED = 2101;
	const IS_SET_ASSERTION_FAILED = 2102;
	const NOT_SET_ASSERTION_FAILED = 2103;
	const NOT_NULL_ASSERTION_FAILED = 2104;
	const NULL_ASSERTION_FAILED = 2105;
	const TEST_FAILS = 2106;

	private $assertionDescription;

	public function __construct($message='',$code=parent::UNKNOWN_ERROR,$assertionDescription='')
	{
		parent::__construct($message,$code,null);
		$this->assertionDescription = $assertionDescription;
	}

	public function getAssertionDescription()
	{
		return $this->assertionDescription;
	}
}
?>