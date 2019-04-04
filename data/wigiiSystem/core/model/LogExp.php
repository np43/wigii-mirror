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
 * Logical Expression
 * Created by CWE on 1 sept. 09
 */
abstract class LogExp extends Model
{
	public static function createAndExp()
	{
		return new LogExpAnd();
	}
	public static function createOrExp()
	{
		return new LogExpOr();
	}
	public static function createNotExp($logExp)
	{
		$returnValue = new LogExpNot();
		$returnValue->setLogExp($logExp);
		return $returnValue;
	}
	public static function createBinExp($obj, $op, $val)
	{
		$returnValue = new LogExpBin();
		$returnValue->setObject($obj);
		$returnValue->setOperator($op);
		$returnValue->setValue($val);
		return $returnValue;
	}
	public static function createSmallerExp($obj, $val)
	{
		return LogExp::createBinExp($obj, '<', $val);
	}
	public static function createGreaterExp($obj, $val)
	{
		return LogExp::createBinExp($obj, '>', $val);
	}
	public static function createSmallerEqExp($obj, $val)
	{
		return LogExp::createBinExp($obj, '<=', $val);
	}
	public static function createGreaterEqExp($obj, $val)
	{
		return LogExp::createBinExp($obj, '>=', $val);
	}
	public static function createEqualExp($obj, $val)
	{
		return LogExp::createBinExp($obj, '=', $val);
	}
	public static function createNotEqualExp($obj, $val)
	{
		return LogExp::createBinExp($obj, '!=', $val);
	}
	public static function createInExp($obj, $vals)
	{
		return LogExp::createBinExp($obj, 'IN', $vals);
	}
	public static function createNotInExp($obj, $vals)
	{
		return LogExp::createBinExp($obj, 'NOTIN', $vals);
	}
	public static function createLikeExp($obj, $val)
	{
		return LogExp::createBinExp($obj, 'LIKE', $val);
	}
	public static function createMatchAgainstExp($obj, $val)
	{
		return LogExp::createBinExp($obj, 'MATCHAGAINST', $val);
	}
	public static function createNotLikeExp($obj, $val)
	{
		return LogExp::createBinExp($obj, 'NOTLIKE', $val);
	}
	public static function createInGroupExp($groupSelectionLogExp, $includeChildrenGroups=true)
	{
		$returnValue = new LogExpInGroup();
		$returnValue->setGroupSelectionLogExp($groupSelectionLogExp);
		$returnValue->setIncludeChildrenGroups($includeChildrenGroups);
		return $returnValue;
	}
	public static function createNotInGroupExp($groupSelectionLogExp, $includeChildrenGroups=true)
	{
		$returnValue = new LogExpNotInGroup();
		$returnValue->setGroupSelectionLogExp($groupSelectionLogExp);
		$returnValue->setIncludeChildrenGroups($includeChildrenGroups);
		return $returnValue;
	}

	public abstract function acceptLogExpVisitor($logExpVisitor);

	/**
	 * Returns a LogExp equal to its logical negation (can be the same object).
	 * Applies Morgan transformation and optimisation.
	 * postcondition: if clone = false then original LogExp tree is touched!
	 */
	public abstract function negate($clone=false);

	/**
	 * Reduces the LogExp by applying negation where it appears
	 * postcondition: if clone = false then original LogExp tree is touched!
	 */
	public abstract function reduceNegation($clone=false);
}