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
 * Parses a user search bar and creates a LogExp based on a FieldSelectorList
 * Created by CWE on 6 févr. 10
 */
class SearchBarLogExpParser
{
	private $_debugLogger;
	private $_executionSink;
	private $stringTokenizer;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("SearchBarLogExpParser");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("SearchBarLogExpParser");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}

	// Dependency injection

	protected function getStringTokenizer()
	{
		// autowired (uses shared recyclable StringTokenizer)
		if(!isset($this->stringTokenizer))
		{
			return TechnicalServiceProvider::getStringTokenizer();
		}
		else
		{
			$this->stringTokenizer->reset();
			return $this->stringTokenizer;
		}
	}
	protected function recycleStringTokenizer($stringTokenizer)
	{
		if(isset($this->stringTokenizer))
		{
			if(isset($stringTokenizer)) $stringTokenizer->freeMemory();
		}
		else TechnicalServiceProvider::recycleStringTokenizer($stringTokenizer);
	}
	public function setStringTokenizer($stringTokenizer)
	{
		$this->stringTokenizer = $stringTokenizer;
	}

	// Service implementation

	/**
	 * Creates a LogExp based on the search criteria string using the given FieldSelectorList
	 * search criteria keywords are AND OR NOT ET OU NON && || ! ( ) "
	 * quoted string is considered as one token
	 * parenthesis form groups
	 * token separated by spaces are equivalent to token separated by AND
	 */
	public function createLogExpOnCriteria($fsl, $criteria)
	{
		$this->executionSink()->publishStartOperation("createLogExpOnCriteria");
		try
		{
			$strtok = $this->getStringTokenizer();
			// tokenizes
			$n = $strtok->tokenize($criteria);
			if($n > 0)
			{
				// parses
				$returnValue = $this->createOrExpOnCriteria($fsl, $strtok);
			}
			else $returnValue = null;
			if(isset($strtok)) $this->recycleStringTokenizer($strtok);
		}
		catch(StringTokenizerException $ste)
		{
			if(isset($strtok)) $this->recycleStringTokenizer($strtok);
			$this->executionSink()->publishEndOperationOnError("createLogExpOnCriteria", $ste);
			throw $ste;
		}
		catch(Exception $e)
		{
			if(isset($strtok)) $this->recycleStringTokenizer($strtok);
			$this->executionSink()->publishEndOperationOnError("createLogExpOnCriteria", $e);
			throw new StringTokenizerException('',StringTokenizerException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("createLogExpOnCriteria");
		return $returnValue;
	}

	// Parser implementation

	protected function createOrExpOnCriteria($fsl, $strtok)
	{
		$returnValue = null;
		$firstAndExp = $this->createAndExpOnCriteria($fsl, $strtok);
		if(isset($firstAndExp))
		{
			while($strtok->hasMoreToken() && $this->consumeTokenIfOr($strtok))
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
			while($strtok->hasMoreToken() && ($this->consumeTokenIfAnd($strtok) || $this->isTokenAnOperand($strtok)))
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
		// we should have an OR, a right parenthesis or be at the end, else syntax error
		if($strtok->hasMoreToken() && $strtok->getCurrentToken() !== ')' && !$this->isTokenAnOr($strtok))
		{
			$strtok->raiseSyntaxError("no more tokens or right parenthesis");
		}
		return $returnValue;
	}
	protected function createOperandOnCriteria($fsl, $strtok)
	{
		$returnValue = null;
		if($strtok->hasMoreToken())
		{
			if($this->isTokenAnOperand($strtok))
			{
				// if we have a left par
				if($this->consumeTokenIfLeftPar($strtok))
				{
					$returnValue = $this->createOrExpOnCriteria($fsl, $strtok);
					// nested expression cannot be null
					if(is_null($returnValue)) $strtok->raiseSyntaxError("nested expression");
					// consumes limit that should exists
					if(!$this->consumeTokenIfRightPar($strtok)) $strtok->raiseSyntaxError(')');
				}
				// if we have a not
				elseif($this->consumeTokenIfNot($strtok))
				{
					$notExp = $this->createOperandOnCriteria($fsl, $strtok);
					if(is_null($notExp)) $strtok->raiseSyntaxError("negative operand");
					$returnValue = LogExp::createNotExp($notExp);
				}
				// else we have a word
				else
				{
					// at this stage we don't support ponctuation.
					// string with ponctuation should be in quotes.
					// don't support dates, floating numbers, etc.
					if($strtok->isCurrentTokenASymbol()) $strtok->raiseSyntaxError("no ponctuation");
					$token = $strtok->consumeCurrentToken();
					// removes surrounding quotes
					if(isset($token))
					{
						$token = trim($token, '"');
					}
					$returnValue = $this->createLogExpOnToken($fsl, $token);
				}
			}
			// we should be at the end, else syntax error
			else
			{
				$strtok->raiseSyntaxError("no more tokens");
			}
		}
		return $returnValue;
	}
	protected function createLogExpOnToken($fsl, $token)
	{
		if(is_null($fsl)) throw new ServiceException("FieldSelectorList cannot be null", ServiceException::INVALID_ARGUMENT);
		$returnValue = LogExp::createOrExp();
		if(isset($token) && $token !== '')
		{
			$token = "%$token%";
		}
		   foreach($fsl->getListIterator() as $fs)
		   {
			if($fs->isElementAttributeSelector() || $fs->getFieldName()=="id"){
				$token = str_replace("%", "", $token); // the % is good for field values only
			}
			$returnValue->addOperand(LogExp::createLikeExp($fs, $token));
		}
		return $returnValue;
	}

	protected function isTokenAnOperand($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "(":
			case "NOT":
			case "NON":
			case "!":
				return true;
		}
		return $this->isTokenAWord($strtok);
	}
	protected function isTokenAWord($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "AND":
			case "ET":
			case "OR":
			case "OU":
			case "NOT":
			case "NON":
			case "!":
			case "(":
			case ")":
				return false;
			case "&":
				return false; //allow & to be an AND operator as &&
//				if($strtok->peek(1) === "&") return false;
			case "|":
				return false; //allow | to be an OR operator as ||
//				if($strtok->peek(1) === "|") return false;
		}
		return true;
	}

	/**
	 * Returns true if current token is an AND. If true, then consumes it.
	 */
	protected function consumeTokenIfAnd($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "AND":
			case "ET":
				$strtok->move(1);
				return true;
			case "&":
				if($strtok->peek(1) === "&")
				{
					$strtok->move(2);
					return true;
				}
				$strtok->move(1);
				return true;
		}
		return false;
	}
	/**
	 * Returns true if current token is an OR. If true, then consumes it.
	 */
	protected function consumeTokenIfOr($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "OR":
			case "OU":
				$strtok->move(1);
				return true;
			case "|":
				if($strtok->peek(1) === "|")
				{
					$strtok->move(2);
					return true;
				}
				$strtok->move(1);
				return true;
		}
		return false;
	}
	protected function isTokenAnOr($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "OR":
			case "OU":
				return true;
			case "|":
				if($strtok->peek(1) === "|") return true;
				return true;
		}
		return false;
	}
	/**
	 * Returns true if current token is an NOT. If true, then consumes it.
	 */
	protected function consumeTokenIfNot($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "NOT":
			case "NON":
			case "!":
				$strtok->move(1);
				return true;
		}
		return false;
	}
	protected function isTokenANot($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "NOT":
			case "NON":
			case "!":
				return true;
		}
		return false;
	}
	/**
	 * Returns true if current token is a left parenthesis. If true, then consumes it.
	 */
	protected function consumeTokenIfLeftPar($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case "(":
				$strtok->move(1);
				return true;
		}
		return false;
	}
	/**
	 * Returns true if current token is a right parenthesis. If true, then consumes it.
	 */
	protected function consumeTokenIfRightPar($strtok)
	{
		$token = $strtok->getCurrentToken();
		switch($token)
		{
			case ")":
				$strtok->move(1);
				return true;
		}
		return false;
	}
}