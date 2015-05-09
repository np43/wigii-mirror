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
 * A class which converts a log exp to an equivalent func exp
 * Created by CWE on 28 février 2014
 */
class FieldSelectorLogExpFuncExpBuilder implements LogExpVisitor, WigiiExclusiveAccessObject
{
	private $lockedForUse = false; // currently stateless, so object instance can be shared.
	
	// Object lifecycle
	
	public function reset() {}
	public function freeMemory() {}
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	// Service implementation
	
	/**
	 * Converts a log exp to an equivalent func exp
	 * @param LogExp $logExp
	 * @return FuncExp a func exp semantically equivalent to the log exp
	 */
	public function logExp2funcExp($logExp) {
		if(isset($logExp)) return $logExp->acceptLogExpVisitor($this);
		else return null;		
	}

	// LogExp visitor implementation

	public function actOnAndExp($andLogExp)
	{
		$returnValue = FuncExp::createInstance('lxAnd');
		$ops = $andLogExp->getOperands();
		if(isset($ops))
		{			
			foreach($ops as $logExp)
			{				
				$returnValue->addArgument($this->actOnArg($logExp));
			}
		}
		return $returnValue;
	}
	public function actOnOrExp($orLogExp)
	{
		$returnValue = FuncExp::createInstance('lxOr');
		$ops = $orLogExp->getOperands();
		if(isset($ops))
		{
			foreach($ops as $logExp)
			{				
				$returnValue->addArgument($this->actOnArg($logExp));
			}			
		}
		return $returnValue;
	}
	public function actOnNotExp($notLogExp)
	{
		$returnValue = FuncExp::createInstance('lxNot');
		$logExp = $notLogExp->getLogExp();
		if(isset($logExp))
		{			
			$returnValue->addArgument($this->actOnArg($logExp));
		}
		return $returnValue;
	}
	public function actOnSmaller($obj, $val)
	{
		return fx('lxSm', $this->actOnArg($obj), $this->actOnArg($val));
	}
	public function actOnSmallerEq($obj, $val)
	{
		return fx('lxSmEq', $this->actOnArg($obj), $this->actOnArg($val));
	}
	public function actOnGreater($obj, $val)
	{
		return fx('lxGr', $this->actOnArg($obj), $this->actOnArg($val));
	}
	public function actOnGreaterEq($obj, $val)
	{
		return fx('lxGrEq', $this->actOnArg($obj), $this->actOnArg($val));
	}
	public function actOnEqual($obj, $val)
	{
		return fx('lxEq', $this->actOnArg($obj), $this->actOnArg($val));
	}
	public function actOnNotEqual($obj, $val)
	{
		return fx('lxNotEq', $this->actOnArg($obj), $this->actOnArg($val));
	}
	public function actOnIn($obj, $vals)
	{
		return fx('lxIn', $this->actOnArg($obj), $this->actOnArg($vals));
	}
	public function actOnNotIn($obj, $vals)
	{
		return fx('lxNotIn', $this->actOnArg($obj), $this->actOnArg($vals));
	}
	public function actOnLike($obj, $val)
	{
		return fx('lxLike', $this->actOnArg($obj), $this->actOnArg($val));
	}
	public function actOnMatchAgainst($obj, $val)
	{
		throw new FuncExpEvalException('MatchAgainst binary log exp is not supported by func exp', FuncExpEvalException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotLike($obj, $val)
	{
		return fx('lxNotLike', $this->actOnArg($obj), $this->actOnArg($val));
	}	
	public function actOnInGroup($inGroupLogExp)
	{
		if($inGroupLogExp->includeChildrenGroups()) return fx('lxInGR', $this->actOnArg($inGroupLogExp->getGroupSelectionLogExp()));
		else return fx('lxInG', $this->actOnArg($inGroupLogExp->getGroupSelectionLogExp()));
	}
	public function actOnNotInGroup($notInGroupLogExp)
	{
		if($inGroupLogExp->includeChildrenGroups()) return fx('lxNotInGR', $this->actOnArg($inGroupLogExp->getGroupSelectionLogExp()));
		else return fx('lxNotInG', $this->actOnArg($inGroupLogExp->getGroupSelectionLogExp()));
	}
	
	private function actOnArg($arg) {
		if($arg instanceof FieldSelector) {
			if($arg->isElementAttributeSelector()) return fx('fs_e', $arg->getSubFieldName());
			else return fx('fs', $arg->getFieldName(), $arg->getSubFieldName());
		}
		elseif($arg instanceof LogExp) return $arg->acceptLogExpVisitor($this);
		elseif($arg === true) return fx('logTrue');
		elseif($arg === false) return fx('logFalse');
		elseif(is_array($arg)) {
			if(empty($arg)) return fx('newList');
			else {
				$returnValue = FuncExp::createInstance('newMap');
				foreach($arg as $k => $v) {
					$returnValue->addArgument($this->actOnArg($k));
					$returnValue->addArgument($this->actOnArg($v));
				}
				return $returnValue;
			}
		}
		else return $arg;
	}
}