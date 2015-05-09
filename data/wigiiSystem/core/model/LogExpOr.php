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
 * OR logical expression
 * Created by CWE on 1 sept. 09
 */
class LogExpOr extends LogExp
{
	private $operands;

	public function __construct()
	{
		$this->operands = array();
	}
	public function addOperand($logExp)
	{
		$this->operands[] = $logExp;
	}
	public function getOperands()
	{
		return $this->operands;
	}
	public function acceptLogExpVisitor($logExpVisitor)
	{
		return $logExpVisitor->actOnOrExp($this);
	}

	public function negate($clone=false)
	{
		$returnValue = LogExp::createAndExp();
		foreach($this->operands as $logExp)
		{
			$returnValue->addOperand($logExp->negate($clone));
		}
		return $returnValue;
	}

	public function reduceNegation($clone=false)
	{
		if($clone)
		{
			$returnValue = LogExp::createOrExp();
			foreach($this->operands as $logExp)
			{
				$returnValue->addOperand($logExp->reduceNegation($clone));
			}
			return $returnValue;
		}
		else
		{
			foreach($this->operands as $key => $logExp)
			{
				$this->operands[$key] = $logExp->reduceNegation($clone);
			}
			return $this;
		}
	}
}