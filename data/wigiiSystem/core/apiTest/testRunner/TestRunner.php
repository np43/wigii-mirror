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
 * wigii system test runner
 * This implementation only supports serial execution of tests
 * Created by CWE on 18 juin 09
 */
class TestRunner
{
	private $_executionSink;
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("TestRunner");
		}
		return $this->_executionSink;
	}

	/**
	 * messages array
	 */
	private $messages;

	private $testDataSet;
	private $rootPrincipal;

	/**
	 * Initializes test runner if not already done.
	 */
	public static function start()
	{
		TestRunnerTechnicalServiceProvider::start();
		if(is_null(TestRunnerTechnicalServiceProvider::getTestRunner()->getRootPrincipal()))
			TestRunnerTechnicalServiceProvider::pushRootPrincipalToTestRunner();
	}

	/**
	 * Runs a test
	 */
	public static function test($test)
	{
		TestRunnerTechnicalServiceProvider::getTestRunner()->runTest($test);
	}

	/**
	 * Changes current test data set with new one
	 */
	public static function changeTestDataSet($testDataSet)
	{
		TestRunnerTechnicalServiceProvider::getTestRunner()->setTestDataSet($testDataSet);
	}

	/**
	 * Runs a test
	 */
	public function runTest($test)
	{
		$test->setTestRunner($this);
		$test->setRootPrincipal($this->getRootPrincipal());
		$this->clearMessages();
		try
		{
			$test->run();
			// test passed
			$this->publishTestPassed($test);
		}
		// test failed
		catch(TestRunnerException $tre)
		{
			switch($tre->getCode())
			{
			case TestRunnerException::EQUAL_ASSERTION_FAILED:
				$this->testFailsEqualAssertion($test, $tre->getAssertionDescription());
				break;
			case TestRunnerException::NOT_EQUAL_ASSERTION_FAILED:
				$this->testFailsNotEqualAssertion($test, $tre->getAssertionDescription());
				break;
			case TestRunnerException::IS_SET_ASSERTION_FAILED:
				$this->testFailsIsSetAssertion($test, $tre->getAssertionDescription());
				break;
			case TestRunnerException::NOT_SET_ASSERTION_FAILED:
				$this->testFailsNotSetAssertion($test, $tre->getAssertionDescription());
				break;
			case TestRunnerException::NOT_NULL_ASSERTION_FAILED:
				$this->testFailsNotNullAssertion($test, $tre->getAssertionDescription());
				break;
			case TestRunnerException::NULL_ASSERTION_FAILED:
				$this->testFailsNullAssertion($test, $tre->getAssertionDescription());
				break;
			case TestRunnerException::TEST_FAILS:
				$this->publishTestFailed($test, $tre->getMessage());
				break;
			}
		}
		catch(Exception $e)
		{
			$this->testFailsWithException($test, $e);
		}
	}

	/**
	 * Sets test data set
	 */
	public function setTestDataSet($testDataSet)
	{
		$this->testDataSet = $testDataSet;
	}
	/**
	 * Returns current test data set
	 */
	public function getTestDataSet()
	{
		return $this->testDataSet;
	}

	public function setRootPrincipal($rootPrincipal)
	{
		$this->rootPrincipal = $rootPrincipal;
	}
	protected function getRootPrincipal()
	{
		return $this->rootPrincipal;
	}

	///////////////////////
	// Test callback functions
	///////////////////////

	public function testAssertsEqual($test, $assertionDescription, $value, $expectedValue)
	{
		if($value == $expectedValue)
		{
			$this->executionSink()->log('test equality : '.$assertionDescription.' has succeeded.');
			return;
		}
		throw new TestRunnerException('',TestRunnerException::EQUAL_ASSERTION_FAILED, $assertionDescription);
	}
	protected function testFailsEqualAssertion($test, $assertionDescription)
	{
		$this->publishTestFailed($test, 'test equality : '.$assertionDescription.' has failed.');
	}
	public function testAssertsNotEqual($test, $assertionDescription, $value, $nonExpectedValue)
	{
		if($value == $nonExpectedValue)
		{
			throw new TestRunnerException('',TestRunnerException::NOT_EQUAL_ASSERTION_FAILED, $assertionDescription);
		}
		$this->executionSink()->log('test inequality : '.$assertionDescription.' has succeeded.');
	}
	protected function testFailsNotEqualAssertion($test, $assertionDescription)
	{
		$this->publishTestFailed($test, 'test inequality : '.$assertionDescription.' has failed.');
	}
	public function testAssertsIsSet($test, $assertionDescription, $value)
	{
		if(isset($value))
		{
			$this->executionSink()->log('test isset : '.$assertionDescription.' has succeeded.');
			return;
		}
		throw new TestRunnerException('',TestRunnerException::IS_SET_ASSERTION_FAILED, $assertionDescription);
	}
	protected function testFailsIsSetAssertion($test, $assertionDescription)
	{
		$this->publishTestFailed($test, 'test isset : '.$assertionDescription.' has failed.');
	}
	public function testAssertsNotSet($test, $assertionDescription, $value)
	{
		if(isset($value))
		{
			throw new TestRunnerException('',TestRunnerException::NOT_SET_ASSERTION_FAILED, $assertionDescription);
		}
		$this->executionSink()->log('test not set : '.$assertionDescription.' has succeeded.');
	}
	protected function testFailsNotSetAssertion($test, $assertionDescription)
	{
		$this->publishTestFailed($test, 'test not set : '.$assertionDescription.' has failed.');
	}
	public function testAssertsNotNull($test, $assertionDescription, $value)
	{
		if(is_null($value))
		{
			throw new TestRunnerException('',TestRunnerException::NOT_NULL_ASSERTION_FAILED, $assertionDescription);
		}
		$this->executionSink()->log('test not null : '.$assertionDescription.' has succeeded.');
	}
	protected function testFailsNotNullAssertion($test, $assertionDescription)
	{
		$this->publishTestFailed($test, 'test not null : '.$assertionDescription.' has failed.');
	}
	public function testAssertsNull($test, $assertionDescription, $value)
	{
		if(is_null($value))
		{
			$this->executionSink()->log('test null : '.$assertionDescription.' has succeeded.');
			return;
		}
		throw new TestRunnerException('',TestRunnerException::NULL_ASSERTION_FAILED, $assertionDescription);
	}
	protected function testFailsNullAssertion($test, $assertionDescription)
	{
		$this->publishTestFailed($test, 'test null : '.$assertionDescription.' has failed.');
	}
	public function testFails($test, $message)
	{
		throw new TestRunnerException($message,TestRunnerException::TEST_FAILS);
	}




	///////////////////////
	// Test result publication
	///////////////////////

	protected function testFailsWithException($test, $exception)
	{
		$this->publishTestFailed($test, 'uncaught exception : '.$exception->__toString());
	}
	protected function publishTestFailed($test, $message)
	{
		$this->publishTestResult($test->getTestId(), false, 'Test '.$test->getTestName().' failed.<br/>'.$message);
	}
	protected function publishTestPassed($test)
	{
		$this->publishTestResult($test->getTestId(), true, 'Test '.$test->getTestName().' succeeded.');
	}
	private function publishTestResult($testId, $passed, $testText)
	{
		echo '<div id="';
		echo 'test.'.$testId;
		echo '"';
		echo 'style="cursor:pointer;background-color:'.($passed ? 'lime':'red').';border-style:solid;border-width:1px;margin-bottom:4px;margin-top:4px;padding:3px"';
		echo 'onclick="';
		echo "if(document.getElementById('test.".$testId.".logs').style.display == 'none'){document.getElementById('test.".$testId.".logs').style.display = 'block'}else{document.getElementById('test.".$testId.".logs').style.display = 'none'}";
		echo '">';
		echo nl2br($testText);
		echo '<div id="test.'.$testId.'.logs" style="background-color:white;display:none;padding:3px">';
		$this->flushMessages();
		echo '</div></div>';
	}





	/////////////////////////////////////
	// Technical services callback functions
	/////////////////////////////////////


	public function writeDebugMessage($message)
	{
		$this->storeMessage($message.'<br/>');
	}
	public function writeExecutionSinkMessage($message)
	{
		$this->storeMessage($message.'<br/>');
	}
	public function writeExceptionSinkMessage($message)
	{
		$this->storeMessage($message.'<br/>');
	}
	private function storeMessage($message)
	{
		if(!isset($this->messages))
		{
			$this->messages = array();
		}
		$this->messages[] = $message;
	}
	private function flushMessages()
	{
		if(isset($this->messages))
		{
			foreach($this->messages as $message)
			{
				echo $message;
			}
			$this->clearMessages();
		}
	}
	private function clearMessages()
	{
		unset($this->messages);
	}
}
?>