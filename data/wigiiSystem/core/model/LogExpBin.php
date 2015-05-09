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
 * Binary logical expression
 * Created by CWE on 1 sept. 09
 */
class LogExpBin extends LogExp
{
	private $obj;
	private $operator;
	private $value;

	/**
	 * Left side operand
	 */
	public function getObject()
	{
		return $this->obj;
	}
	/**
	 * Left side operand
	 */
	public function setObject($obj)
	{
		$this->obj = $obj;
	}
	public function getOperator()
	{
		return $this->operator;
	}
	public function setOperator($operator)
	{
		if(!is_string($operator)) throw new ServiceException('operator must be a valid string', ServiceException::INVALID_ARGUMENT);
		$operator = strtoupper($operator);
		switch($operator)
		{
			case '<':
			case '>':
			case '<=':
			case '>=':
			case '=':
			case '!=':
			case 'IN':
			case 'NOTIN':
			case 'LIKE':
			case 'MATCHAGAINST':
			case 'NOTLIKE':
				$this->operator = $operator;
				break;
			default:
				throw new ServiceException("$operator operator is not supported", ServiceException::INVALID_ARGUMENT);
		}
	}
	/**
	 * Right side operand
	 */
	public function getValue()
	{
		return $this->value;
	}
	/**
	 * Right side operand
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	public function acceptLogExpVisitor($logExpVisitor)
	{
		$o = $this->getObject();
		$v = $this->getValue();
		switch($this->getOperator())
		{
			case '<':
				return $logExpVisitor->actOnSmaller($o,$v);
				break;
			case '>':
				return $logExpVisitor->actOnGreater($o,$v);
				break;
			case '<=':
				return $logExpVisitor->actOnSmallerEq($o,$v);
				break;
			case '>=':
				return $logExpVisitor->actOnGreaterEq($o,$v);
				break;
			case '=':
				return $logExpVisitor->actOnEqual($o,$v);
				break;
			case '!=':
				return $logExpVisitor->actOnNotEqual($o,$v);
				break;
			case 'IN':
				return $logExpVisitor->actOnIn($o,$v);
				break;
			case 'NOTIN':
				return $logExpVisitor->actOnNotIn($o,$v);
				break;
			case 'LIKE':
				return $logExpVisitor->actOnLike($o,$v);
				break;
			case 'MATCHAGAINST':
				return $logExpVisitor->actOnMatchAgainst($o,$v);
				break;
			case 'NOTLIKE':
				return $logExpVisitor->actOnNotLike($o,$v);
				break;
		}
	}

	public function negate($clone=false)
	{
		if($clone) $returnValue = LogExp::createBinExp($this->getObject(), $this->getOperator(), $this->getValue());
		else $returnValue = $this;
		switch($this->getOperator())
		{
			case '<':
				$returnValue->setOperator('>');
				break;
			case '>':
				$returnValue->setOperator('<');
				break;
			case '<=':
				$returnValue->setOperator('>=');
				break;
			case '>=':
				$returnValue->setOperator('<=');
				break;
			case '=':
				$returnValue->setOperator('!=');
				break;
			case '!=':
				$returnValue->setOperator('=');
				break;
			case 'IN':
				$returnValue->setOperator('NOTIN');
				break;
			case 'NOTIN':
				$returnValue->setOperator('IN');
				break;
			case 'LIKE':
				$returnValue->setOperator('NOTLIKE');
				break;
			case 'NOTLIKE':
				$returnValue->setOperator('LIKE');
				break;
			case 'MATCHAGAINST':
				$returnValue = LogExp::createNotExp($returnValue);
				break;
		}
		return $returnValue;
	}

	/**
	 * nothing to do for LogExpBin, returns it.
	 */
	public function reduceNegation($clone=false)
	{
		if($clone) return LogExp::createBinExp($this->getObject(), $this->getOperator(), $this->getValue());
		else return $this;
	}
}