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
 * Parses a string and constructs a FieldSelector LogExp
 * Created by CWE on 23 déc. 09
 */
class FieldSelectorLogExpParser implements LogExpVisitor
{
	private $_debugLogger;
	private $_executionSink;
	private $stringTokenizer;
	private $sLogExp;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FieldSelectorLogExpParser");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("FieldSelectorLogExpParser");
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
	 * Parses the input string and creates a FieldSelector LogExp
	 * throws StringTokenizerException in case of error
	 *
	 * Language grammar is :
	 *
	 * fieldSelectorLogExp::=orExp|eof
	 * orExp::=andExp (orOp andExp)*
	 * andExp::=factor (andOp factor)*
	 * factor::=notOp factor|'(' orExp ')'|groupExp|binExp
	 * groupExp::=(ING|NOTING|INGR|NOTINGR)'(' orExp ')'
	 * binExp::=fieldSelector (binOp value|inOp listValue)
	 * fieldSelector::=fieldName(.subFieldName)?
	 * fieldName::=word
	 * subFieldName::=word
	 * orOp::=OR|'||'
	 * andOp::=AND|'&&'
	 * notOp::=NOT|'!'
	 * binOp::='<''='|'<'|'>''='|'>'|'!''='|'='|'LIKE'|'NOTLIKE'
	 * inOp::=IN|NOTIN
	 * listValue::='(' value (',' value)* ')'
	 * value::=NULL|word
	 */
	public function createLogExpFromString($s)
	{
		$this->executionSink()->publishStartOperation("createLogExpFromString");
		try
		{
			$strtok = $this->getStringTokenizer();
			// tokenizes
			$n = $strtok->tokenize($s);
			if($n > 0)
			{
				// parses
				$returnValue = $this->createLogExpFromTokenizer($strtok);
			}
			else $returnValue = null;
			if(isset($strtok)) $this->recycleStringTokenizer($strtok);
		}
		catch(StringTokenizerException $ste)
		{
			if(isset($strtok)) $this->recycleStringTokenizer($strtok);
			$this->executionSink()->publishEndOperationOnError("createLogExpFromString", $ste);
			throw $ste;
		}
		catch(Exception $e)
		{
			if(isset($strtok)) $this->recycleStringTokenizer($strtok);
			$this->executionSink()->publishEndOperationOnError("createLogExpFromString", $e);
			throw new StringTokenizerException('',StringTokenizerException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("createLogExpFromString");
		return $returnValue;
	}

	/**
	 * Transforms a FieldSelector LogExp to a string
	 */
	public function logExpToString($logExp)
	{
		$this->executionSink()->publishStartOperation("logExpToString");
		try
		{
			if(isset($logExp))
			{
				$this->sLogExp = '';
				$logExp->acceptLogExpVisitor($this);
				$returnValue = $this->sLogExp;
			}
			else $returnValue = '';
		}
		catch(StringTokenizerException $ste)
		{
			$this->executionSink()->publishEndOperationOnError("logExpToString", $ste);
			throw $ste;
		}
		catch(Exception $e)
		{
			$this->executionSink()->publishEndOperationOnError("logExpToString", $e);
			throw new StringTokenizerException('',StringTokenizerException::WRAPPING, $e);
		}
		$this->executionSink()->publishEndOperation("logExpToString");
		return $returnValue;
	}

	/**
	 * Creates a FuncExp semantically equivalent to the given log exp in a string form.
	 * @param String $s a log exp into its string form.
	 * @return FuncExp parses the input string and converts the log exp to a func exp using the FieldSelectorLogExpFuncExpBuilder service.
	 */
	public function createFuncExpFromStringLogExp($s) {
		$fxBuilder = TechnicalServiceProvider::getFieldSelectorLogExpFuncExpBuilder();
		$returnValue = $fxBuilder->logExp2funcExp($this->createLogExpFromString($s));
		$fxBuilder->freeMemory();
		return $returnValue;
	}
	
	// Parser implementation

	protected function createLogExpFromTokenizer($strtok)
	{
		if(!$strtok->hasMoreToken()) $returnValue = null;
		elseif($this->isInOrExp($strtok))
		{
			$returnValue = $this->createOrExpFromTokenizer($strtok);
			if($strtok->hasMoreToken()) $strtok->raiseSyntaxError("no more tokens");
		}
		else $strtok->raiseSyntaxError("OR expression");
		return $returnValue;
	}
	protected function isInOrExp($strtok)
	{
		return $this->isInAndExp($strtok);
	}
	protected function createOrExpFromTokenizer($strtok)
	{
		if($this->isInAndExp($strtok))
		{
			$returnValue = null;
			$firstAndExp = $this->createAndExpFromTokenizer($strtok);
			while($this->consumeIfOrOp($strtok))
			{
				if($this->isInAndExp($strtok))
				{
					$andExp = $this->createAndExpFromTokenizer($strtok);
					if(is_null($returnValue))
					{
						$returnValue = LogExp::createOrExp();
						$returnValue->addOperand($firstAndExp);
					}
					$returnValue->addOperand($andExp);
				}
				else $strtok->raiseSyntaxError("AND expression");
			}
			if(is_null($returnValue)) $returnValue = $firstAndExp;
			return $returnValue;
		}
		else $strtok->raiseSyntaxError("AND expression");
	}
	protected function isInAndExp($strtok)
	{
		return $this->isInFactorExp($strtok);
	}
	protected function createAndExpFromTokenizer($strtok)
	{
		if($this->isInFactorExp($strtok))
		{
			$returnValue = null;
			$firstFactor = $this->createFactorExpFromTokenizer($strtok);
			while($this->consumeIfAndOp($strtok))
			{
				if($this->isInFactorExp($strtok))
				{
					$factor = $this->createFactorExpFromTokenizer($strtok);
					if(is_null($returnValue))
					{
						$returnValue = LogExp::createAndExp();
						$returnValue->addOperand($firstFactor);
					}
					$returnValue->addOperand($factor);
				}
				else $strtok->raiseSyntaxError("factor");
			}
			if(is_null($returnValue)) $returnValue = $firstFactor;
			return $returnValue;
		}
		else $strtok->raiseSyntaxError("factor");
	}
	protected function isInFactorExp($strtok)
	{
		return ($this->isNotOp($strtok) ||
				$this->isLeftPar($strtok) ||
				$this->isInGroupExp($strtok) ||
				$this->isInBinExp($strtok));
	}
	protected function createFactorExpFromTokenizer($strtok)
	{
		if($this->consumeIfNotOp($strtok))
		{
			if($this->isInFactorExp($strtok))
			{
				$returnValue = LogExp::createNotExp($this->createFactorExpFromTokenizer($strtok));
			}
			else $strtok->raiseSyntaxError("factor");
		}
		elseif($this->consumeIfLeftPar($strtok))
		{
			if($this->isInOrExp($strtok))
			{
				$returnValue = $this->createOrExpFromTokenizer($strtok);
				if(!$this->consumeIfRightPar($strtok)) $strtok->raiseSyntaxError(')');
			}
			else $strtok->raiseSyntaxError("OR expression");
		}
		elseif($this->isInGroupExp($strtok))
		{
			$returnValue = $this->createGroupExpFromTokenizer($strtok);
		}
		elseif($this->isInBinExp($strtok))
		{
			$returnValue = $this->createBinExpFromTokenizer($strtok);
		}
		else $strtok->raiseSyntaxError("factor");
		return $returnValue;
	}
	protected function isInGroupExp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case "ING":
				case "INGR":
				case "NOTING":
				case "NOTINGR":
					return true;
			}
			return false;
		}
		else return false;
	}
	protected function createGroupExpFromTokenizer($strtok)
	{
		if($strtok->hasMoreToken())
		{
			$token = $strtok->consumeCurrentToken();
			switch($token)
			{
				case "ING": $returnValue = LogExp::createInGroupExp(null, false); break;
				case "INGR": $returnValue = LogExp::createInGroupExp(null, true); break;
				case "NOTING": $returnValue = LogExp::createNotInGroupExp(null, false); break;
				case "NOTINGR": $returnValue = LogExp::createNotInGroupExp(null, true); break;
				default: $strtok->raiseSyntaxError("ING or NOTING or INGR or NOTINGR");
			}
			if(!$this->consumeIfLeftPar($strtok)) $strtok->raiseSyntaxError('(');
			if($this->isInOrExp($strtok))
			{
				$returnValue->setGroupSelectionLogExp($this->createOrExpFromTokenizer($strtok));
			}
			else $strtok->raiseSyntaxError("OR expression");
			if(!$this->consumeIfRightPar($strtok)) $strtok->raiseSyntaxError(')');
			return $returnValue;
		}
		else $strtok->raiseSyntaxError("ING or NOTING or INGR or NOTINGR");
	}
	protected function isInBinExp($strtok)
	{
		return $this->isValue($strtok);
	}
	protected function createBinExpFromTokenizer($strtok)
	{
		if($strtok->hasMoreToken())
		{
			$token = $strtok->getCurrentToken();
			// extracts fieldName and subfieldName
			$fields = array();
			if(preg_match('/^(.*?)(\\.(.*))?$/s',$token,$fields) > 0)
			{
				$strtok->move(1);
				// creates fieldSelector
				$fs = FieldSelector::createInstance($fields[1], $fields[3]);
				// binOp or InOp
				if($this->isBinOp($strtok))
				{
					$op = $this->consumeBinOp($strtok);
					if($this->isValue($strtok))
					{
						return LogExp::createBinExp($fs, $op, $this->consumeValue($strtok));
					}
					else $strtok->raiseSyntaxError("value");
				}
				elseif($this->isInOp($strtok))
				{
					$op = $this->consumeInOp($strtok);
					if($this->consumeIfLeftPar($strtok))
					{
						if($this->isValue($strtok))
						{
							$listValue[] = $this->consumeValue($strtok);
							while($this->consumeIfComma($strtok))
							{
								if($this->isValue($strtok))
								{
									$listValue[] = $this->consumeValue($strtok);
								}
								else $strtok->raiseSyntaxError("value");
							}
							if(!$this->consumeIfRightPar($strtok)) $strtok->raiseSyntaxError(")");
							return LogExp::createBinExp($fs, $op, $listValue);
						}
						else $strtok->raiseSyntaxError("value");
					}
					else $strtok->raiseSyntaxError("(");
				}
				else $strtok->raiseSyntaxError("binary operator");
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
	protected function isOrOp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case "OR":
				case "OU":
					return true;
				case "|":
					if($strtok->peek(1) === "|") return true;
			}
			return false;
		}
		else return false;
	}
	protected function consumeIfOrOp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
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
			}
			return false;
		}
		else return false;
	}
	protected function isAndOp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case "AND":
				case "ET":
					return true;
				case "&":
					if($strtok->peek(1) === "&") return true;
			}
			return false;
		}
		else return false;
	}
	protected function consumeIfAndOp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
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
			}
			return false;
		}
		else return false;
	}
	protected function isNotOp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case "NOT":
				case "NON":
				case "!":
					return true;
			}
			return false;
		}
		else return false;
	}
	protected function consumeIfNotOp($strtok)
	{
		if($this->isNotOp($strtok))
		{
			$strtok->move(1);
			return true;
		}
		else return false;
	}
	protected function isBinOp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case "<":
				case ">":
				case "=":
				case "LIKE":
				case "NOTLIKE":
					return true;
				case "!":
					if($strtok->peek(1) === "=") return true;
			}
			return false;
		}
		else return false;
	}
	protected function consumeBinOp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case "<":
					if($strtok->peek(1) === "=")
					{
						$strtok->move(2);
						return "<=";
					}
					else
					{
						$strtok->move(1);
						return "<";
					}
				case ">":
					if($strtok->peek(1) === "=")
					{
						$strtok->move(2);
						return ">=";
					}
					else
					{
						$strtok->move(1);
						return ">";
					}
				case "!":
					if($strtok->peek(1) === "=")
					{
						$strtok->move(2);
						return "!=";
					}
					else $strtok->raiseSyntaxError("=");
				case "=":
				case "LIKE":
				case "NOTLIKE":
					return $strtok->consumeCurrentToken();
			}
			return $strtok->raiseSyntaxError("binary operator");
		}
		else $strtok->raiseSyntaxError("binary operator");
	}
	protected function isInOp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case "IN":
				case "NOTIN":
					return true;
			}
			return false;
		}
		else return false;
	}
	protected function consumeInOp($strtok)
	{
		if($strtok->hasMoreToken())
		{
			switch($strtok->getCurrentToken())
			{
				case "IN":
				case "NOTIN":
					return $strtok->consumeCurrentToken();
			}
			return $strtok->raiseSyntaxError("binary operator");
		}
		else $strtok->raiseSyntaxError("binary operator");
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
			if($this->isAndOp($strtok)) return false;
			if($this->isOrOp($strtok)) return false;
			if($this->isNotOp($strtok)) return false;
			if($this->isInGroupExp($strtok)) return false;
			if($this->isBinOp($strtok)) return false;
			if($this->isInOp($strtok)) return false;
			if($strtok->isCurrentTokenASymbol()) return false;
			return true;
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

	// LogExpVisitor implementation

	public function actOnAndExp($andLogExp)
	{
		$ops = $andLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			foreach($ops as $logExp)
			{
				if($firstOp) $firstOp = false;
				else
				{
					$this->sLogExp .= " AND ";
				}
				$logExp->acceptLogExpVisitor($this);
			}
		}
	}
	public function actOnOrExp($orLogExp)
	{
		$ops = $orLogExp->getOperands();
		if(isset($ops))
		{
			$firstOp = true;
			$this->sLogExp .= " (";
			foreach($ops as $logExp)
			{
				if($firstOp) $firstOp = false;
				else
				{
					$this->sLogExp .= " OR ";
				}
				$logExp->acceptLogExpVisitor($this);
			}
			$this->sLogExp .= ") ";
		}
	}
	public function actOnNotExp($notLogExp)
	{
		$logExp = $notLogExp->getLogExp();
		if(isset($logExp))
		{
			$this->sLogExp .= " NOT(";
			$logExp->acceptLogExpVisitor($this);
			$this->sLogExp .= ") ";
		}
	}
	public function actOnInGroup($inGroupLogExp)
	{
		$logExp = $inGroupLogExp->getGroupSelectionLogExp();
		if(isset($logExp))
		{
			if($inGroupLogExp->includeChildrenGroups()) $this->sLogExp .= " INGR(";
			else $this->sLogExp .= " ING(";
			$logExp->acceptLogExpVisitor($this);
			$this->sLogExp .= ") ";
		}
	}
	public function actOnNotInGroup($notInGroupLogExp)
	{
		$logExp = $notInGroupLogExp->getGroupSelectionLogExp();
		if(isset($logExp))
		{
			if($notInGroupLogExp->includeChildrenGroups()) $this->sLogExp .= " NOTINGR(";
			else $this->sLogExp .= " NOTING(";
			$logExp->acceptLogExpVisitor($this);
			$this->sLogExp .= ") ";
		}
	}
	public function actOnSmaller($obj, $val)
	{
		$this->binExpToString($obj, '<', $val);
	}
	public function actOnSmallerEq($obj, $val)
	{
		$this->binExpToString($obj, '<=', $val);
	}
	public function actOnGreater($obj, $val)
	{
		$this->binExpToString($obj, '>', $val);
	}
	public function actOnGreaterEq($obj, $val)
	{
		$this->binExpToString($obj, '>=', $val);
	}
	public function actOnEqual($obj, $val)
	{
		$this->binExpToString($obj, '=', $val);
	}
	public function actOnNotEqual($obj, $val)
	{
		$this->binExpToString($obj, '!=', $val);
	}
	public function actOnIn($obj, $vals)
	{
		$this->binExpToString($obj, 'IN', $vals);
	}
	public function actOnNotIn($obj, $vals)
	{
		$this->binExpToString($obj, 'NOTIN', $vals);
	}
	public function actOnLike($obj, $val)
	{
		$this->binExpToString($obj, 'LIKE', $val);
	}
	public function actOnMatchAgainst($obj, $val)
	{
		$this->binExpToString($obj, 'MATCHAGAINST', $val);
	}
	public function actOnNotLike($obj, $val)
	{
		$this->binExpToString($obj, 'NOTLIKE', $val);
	}
	protected function binExpToString($fs, $op, $val)
	{
		if(is_null($fs)) throw new StringTokenizerException("fieldSelector cannot be null", StringTokenizerException::INVALID_ARGUMENT);
		$fieldName = $fs->getFieldName();
		$subFieldName = $fs->getSubFieldName();
		if(isset($subFieldName)) $subFieldName = '.'.$subFieldName;
		if(is_array($val))
		{
			$aVal = '('; $notFirst = false;
			foreach($val as $v)
			{
				if($notFirst) $aVal .= ", ";
				else $notFirst = true;
				$aVal .= $this->valToString($v);
			}
			$aVal .= ')';
			$val = $aVal;
		}
		else $val = $this->valToString($val);
		$this->sLogExp .= $fieldName.$subFieldName.' '.$op.' '.$val;
	}
	protected function valToString($val)
	{
		if(is_null($val)) return "NULL";
		else return '"'.$val.'"';
	}
}