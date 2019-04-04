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
 * Parses a string and constructs a FieldSelector FuncExp
 * Created by CWE on 13 avr. 10
 */
class FieldSelectorFuncExpParser
{
	private $_debugLogger;
	private $_executionSink;
	private $stringTokenizer;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FieldSelectorFuncExpParser");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("FieldSelectorFuncExpParser");
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
	 * Parses the input string and creates a FieldSelector FuncExp
	 * throws StringTokenizerException in case of error
	 *
	 * Language grammar is :
	 *
	 * fieldSelectorFuncExp::=funcCall|eof
	 * funcCall::=funcName '(' funcArg? (',' funcArg)* ')'
	 * funcArg::=funcCall|fieldSelector|value
	 * funcName::=word
	 * fieldSelector::=fieldName(.subFieldName)?
	 * fieldName::=word
	 * subFieldName::=word
	 * value::=NULL|'"' word '"'
	 */
	public function createFuncExpFromString($s)
	{
		$this->executionSink()->publishStartOperation("createFuncExpFromString");
		try
		{
			$strtok = $this->getStringTokenizer();
			// tokenizes
			$n = $strtok->tokenize($s);
			if($n > 0)
			{
				// parses
				$returnValue = $this->createFuncExpFromTokenizer($strtok);
			}
			else $returnValue = null;
			if(isset($strtok)) $this->recycleStringTokenizer($strtok);
		}
		catch(StringTokenizerException $ste)
		{
			if(isset($strtok)) $this->recycleStringTokenizer($strtok);
			$this->executionSink()->publishEndOperationOnError("createFuncExpFromString", $ste);
			throw $ste;
		}
		catch(Exception $e)
		{
			if(isset($strtok)) $this->recycleStringTokenizer($strtok);
			$this->executionSink()->publishEndOperationOnError("createFuncExpFromString", $e);
			throw new StringTokenizerException('',StringTokenizerException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("createFuncExpFromString");
		return $returnValue;
	}

	/**
	 * Transforms a FieldSelector FuncExp to a string
	 */
	public function funcExpToString($funcExp)
	{
		$this->executionSink()->publishStartOperation("funcExpToString");
		try
		{
			$returnValue = $this->funcExpToStringRecursive($funcExp);
		}
		catch(StringTokenizerException $ste)
		{
			$this->executionSink()->publishEndOperationOnError("funcExpToString", $ste);
			throw $ste;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("funcExpToString", $e);
			throw new StringTokenizerException('',StringTokenizerException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("funcExpToString");
		return $returnValue;
	}
	protected function funcExpToStringRecursive($funcExp)
	{
		if(isset($funcExp))
		{
			if($funcExp instanceof FieldSelector)
			{
				$returnValue = $funcExp->toString();
			}
			elseif($funcExp instanceof FuncExp)
			{
				$returnValue = $funcExp->getName()."(";
				$notFirst = false;
				foreach($funcExp->getArguments() as $arg)
				{
					if($notFirst) $returnValue .= ", ";
					else $notFirst = true;
					$argResult = $this->funcExpToStringRecursive($arg);
					if($argResult === '') $argResult = "NULL";
					$returnValue .= $argResult;
				}
				$returnValue .= ")";
			}
			elseif($funcExp === true) $returnValue = 'logTrue()';
			elseif($funcExp === false) $returnValue = 'logFalse()';
			elseif(is_null($funcExp)) $returnValue = "NULL";
			elseif(is_array($funcExp) || ($funcExp instanceof stdClass)) {
				if(empty($funcExp)) $returnValue = 'newList()';
				else {
					if($funcExp instanceof stdClass) $returnValue = 'newObject('; 
					else $returnValue = 'newMap(';
					$first = true;
					foreach($funcExp as $k => $v) {
						if($first) $first = false;
						else $returnValue .= ', ';
						$returnValue .= $this->literal2fxstr($k).', '.$this->funcExpToStringRecursive($v);
					}
					$returnValue .= ')';
				}
			}
			elseif(is_object($funcExp)) throw new StringTokenizerException("funcExp can only be an instance of FieldSelector, FuncExp, a literal (string, number), an array or a stdClass instance, no general object", StringTokenizerException::INVALID_ARGUMENT);
			else $returnValue = $this->literal2fxstr($funcExp);
			return $returnValue;
		}
		else return '';
	}
	/**
	 * Transforms a literal to a string compatible with the func exp syntax
	 * @param String|Number $lit
	 */
	protected function literal2fxstr($lit) {
		$s = explode('"', $lit);
		if(count($s) > 1) {
			$returnValue = 'concat("';
			$returnValue .= implode('", txtQuot(), "', $s);
			$returnValue .= '")';
		}
		else $returnValue = '"'.$lit.'"';
		return $returnValue;
	}
	
	// Parser implementation

	protected function createFuncExpFromTokenizer($strtok)
	{
		if(!$strtok->hasMoreToken()) $returnValue = null;
		elseif($this->isInFuncCall($strtok))
		{
			$returnValue = $this->createFuncCallFromTokenizer($strtok);
			if($strtok->hasMoreToken()) $strtok->raiseSyntaxError("no more tokens");
		}
		else $strtok->raiseSyntaxError("funcCall");
		return $returnValue;
	}
	protected function isInFuncCall($strtok)
	{
		if($strtok->hasMoreToken())
		{
			return $this->isInFuncName($strtok) && ($strtok->peek(1) === "(");
		}
		else return false;
	}
	protected function createFuncCallFromTokenizer($strtok)
	{
		if($this->isInFuncName($strtok))
		{
			$funcName = $this->consumeFuncName($strtok);
			if(!$this->consumeIfLeftPar($strtok)) $strtok->raiseSyntaxError('(');
			$returnValue = FuncExp::createInstance($funcName);
			if($this->isInFuncArg($strtok))
			{
				$returnValue->addArgument($this->consumeFuncArg($strtok));
				while($this->consumeIfComma($strtok))
				{
					if($this->isInFuncArg($strtok))
					{
						$returnValue->addArgument($this->consumeFuncArg($strtok));
					}
					else $strtok->raiseSyntaxError("funcArg");
				}
			}
			if(!$this->consumeIfRightPar($strtok)) $strtok->raiseSyntaxError(')');
			return $returnValue;
		}
		else $strtok->raiseSyntaxError("funcName");
	}
	protected function isInFuncName($strtok)
	{
		if($strtok->hasMoreToken())
		{
			return !$strtok->isCurrentTokenASymbol();
		}
		else return false;
	}
	protected function consumeFuncName($strtok)
	{
		return $strtok->consumeCurrentToken();
	}
	protected function isInFuncArg($strtok)
	{
		if($strtok->hasMoreToken())
		{
			return $this->isInFuncCall($strtok) ||
					$this->isValue($strtok) ||
					!$strtok->isCurrentTokenASymbol();
		}
		else return false;
	}
	protected function consumeFuncArg($strtok)
	{
		if($this->isInFuncCall($strtok))
		{
			return $this->createFuncCallFromTokenizer($strtok);
		}
		elseif($this->isValue($strtok))
		{
			return $this->consumeValue($strtok);
		}
		elseif($strtok->hasMoreToken())
		{
			$token = $strtok->getCurrentToken();
			// extracts fieldName and subfieldName
			$fields = array();
			if(preg_match('/^(.*?)(\\.(.*))?$/s',$token,$fields) > 0)
			{
				$strtok->move(1);
				// creates fieldSelector
				return FieldSelector::createInstance($fields[1], $fields[3]);
			}
			else $strtok->raiseSyntaxError("fieldSelector");
		}
		else $strtok->raiseSyntaxError("fieldSelector");
	}
	protected function consumeValue($strtok)
	{
		$value = $strtok->consumeCurrentToken();
		// removes surrounding quotes
		$value = trim($value, '"');
		// extracts NULL keywork
		if($value === "NULL") $value = null;
		return $value;
	}
	protected function isLeftPar($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case "(":
					return true;
			}
			return false;
		}
		else return false;
	}
	protected function consumeIfLeftPar($strtok)
	{
		if($this->isLeftPar($strtok))
		{
			$strtok->move(1);
			return true;
		}
		else return false;
	}
	protected function isRightPar($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case ")":
					return true;
			}
			return false;
		}
		else return false;
	}
	protected function consumeIfRightPar($strtok)
	{
		if($this->isRightPar($strtok))
		{
			$strtok->move(1);
			return true;
		}
		else return false;
	}
	protected function isValue($strtok)
	{
		if($strtok->hasMoreToken())
		{
			$token = $strtok->getCurrentToken();
			if($strtok->isTokenAStringLiteral($token)) return true;
			if($token === 'NULL') return true;
			return false;
		}
		else return false;
	}
	protected function isComma($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case ",":
					return true;
			}
			return false;
		}
		else return false;
	}
	protected function consumeIfComma($strtok)
	{
		if($this->isComma($strtok))
		{
			$strtok->move(1);
			return true;
		}
		else return false;
	}
}