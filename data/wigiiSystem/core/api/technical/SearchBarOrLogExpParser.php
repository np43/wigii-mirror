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
 * Parses a user search bar and creates a LogExp with OR based on a FieldSelectorList
 * Created by LWR 11 oct. 2010
 */
class SearchBarOrLogExpParser extends SearchBarLogExpParser
{
	private $_debugLogger;
	private $_executionSink;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("SearchBarOrLogExpParser");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("SearchBarOrLogExpParser");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}


	// Service implementation

	/**
	 * Creates a LogExp based on the search criteria string using the given FieldSelectorList
	 * search criteria keywords are AND OR NOT ET OU NON && || ! ( ) "
	 * quoted string is considered as one token
	 * parenthesis form groups
	 * token separated by spaces are equivalent to token separated by OR
	 */
	public function createLogExpOnCriteria($fsl, $criteria)
	{
		return parent::createLogExpOnCriteria($fsl, $criteria);
	}

	// Parser implementation
	protected function createOrExpOnCriteria($fsl, $strtok)
	{
		$returnValue = null;
		$firstAndExp = $this->createAndExpOnCriteria($fsl, $strtok);
		if(isset($firstAndExp))
		{
			while($strtok->hasMoreToken() && ($this->consumeTokenIfOr($strtok) || $this->isTokenAnOperand($strtok)))
			{
				$andExp = $this->createAndExpOnCriteria($fsl, $strtok);
				if(is_null($andExp)) $strtok->raiseSyntaxError("AND expression");
				if(is_null($returnValue))
				{
					$returnValue = LogExp::createOrExp();
					$returnValue->addOperand($firstAndExp);
				}
				$returnValue->addOperand($andExp);
			}
			if(is_null($returnValue)) $returnValue = $firstAndExp;
		}
		// we should have a right parenthesis or be at the end, else syntax error
		if($strtok->hasMoreToken() && $strtok->getCurrentToken() !== ')')
		{
			$strtok->raiseSyntaxError("no more tokens or right parenthesis");
		}
		return $returnValue;
	}
	protected function createAndExpOnCriteria($fsl, $strtok)
	{
		$returnValue = null;
		$firstOperand = $this->createOperandOnCriteria($fsl, $strtok);
		if(isset($firstOperand))
		{
			while($strtok->hasMoreToken() && $this->consumeTokenIfAnd($strtok))
			{
				$operand = $this->createOperandOnCriteria($fsl, $strtok);
				if(is_null($operand)) $strtok->raiseSyntaxError("token");
				if(is_null($returnValue))
				{
					$returnValue = LogExp::createAndExp();
					$returnValue->addOperand($firstOperand);
				}
				$returnValue->addOperand($operand);
			}
			if(is_null($returnValue)) $returnValue = $firstOperand;
		}
		// we should have an OR or an Operand, a right parenthesis or be at the end, else syntax error
		if($strtok->hasMoreToken() && $strtok->getCurrentToken() !== ')' && !$this->isTokenAnOr($strtok) && !$this->isTokenAnOperand($strtok))
		{
			$strtok->raiseSyntaxError("no more tokens or right parenthesis");
		}
		return $returnValue;
	}
}