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
 * wigii system API base test class
 * Created by CWE on 18 juin 09
 */
class WigiiApiTest
{
	private $testRunner;
	private $testId;
	private $testName;
	private $testDataSet;
	private $rootPrincipal;

	public function __construct($testId, $testName)
	{
		$this->testId = $testId;
		$this->testName = $testName;
	}
	public function getTestId()
	{
		return $this->testId;
	}
	public function getTestName()
	{
		return $this->testName;
	}

	public function setTestRunner($testRunner)
	{
		$this->testRunner = $testRunner;
	}
	protected function getTestRunner()
	{
		return $this->testRunner;
	}

	public function setRootPrincipal($rootPrincipal)
	{
		$this->rootPrincipal = $rootPrincipal;
	}
	/**
	 * Returns Wigii root principal
	 */
	protected function getRootPrincipal()
	{
		return $this->rootPrincipal;
	}

	/**
	 * Returns test data set
	 */
	public function data()
	{
		// autowired
		if(!isset($this->testDataSet))
		{
			$this->testDataSet = $this->getTestRunner()->getTestDataSet();
		}
		return $this->testDataSet;
	}
	public function setTestDataSet($testDataSet)
	{
		$this->testDataSet = $testDataSet;
	}

	// Logical checks

	/**
	 * Asserts that $value is equal to $expectedValue
	 */
	public function assertEqual($assertionDescription, $value, $expectedValue)
	{
		$this->getTestRunner()->testAssertsEqual($this, $assertionDescription, $value, $expectedValue);
	}
	/**
	 * Asserts that $value is different from $expectedValue
	 */
	public function assertNotEqual($assertionDescription, $value, $nonExpectedValue)
	{
		$this->getTestRunner()->testAssertsNotEqual($this, $assertionDescription, $value, $nonExpectedValue);
	}
	/**
	 * Asserts that $value is set
	 */
	public function assertIsSet($assertionDescription, $value)
	{
		$this->getTestRunner()->testAssertsIsSet($this, $assertionDescription, $value);
	}
	/**
	 * Asserts that $value is not set
	 */
	public function assertNotSet($assertionDescription, $value)
	{
		$this->getTestRunner()->testAssertsNotSet($this, $assertionDescription, $value);
	}
	/**
	 * Asserts that $value is not null
	 */
	public function assertNotNull($assertionDescription, $value)
	{
		$this->getTestRunner()->testAssertsNotNull($this, $assertionDescription, $value);
	}
	/**
	 * Asserts that $value is null
	 */
	public function assertNull($assertionDescription, $value)
	{
		$this->getTestRunner()->testAssertsNull($this, $assertionDescription, $value);
	}
	/**
	 * Fails the test with the given message as a reason.
	 */
	public function fail($message)
	{
		$this->getTestRunner()->testFails($this, $message);
	}
}
?>