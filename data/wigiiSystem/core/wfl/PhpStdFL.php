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
 * PHP standard functions wrapped as FuncExp
 * Created by CWE on 5 decembre 2013
 */
class PhpStdFL extends FuncExpVMAbstractFL
{
	/**
	 * date. See http://www.php.net/date
	 */
	public function date($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("date function takes at least one parameter, the format", FuncExpEvalException::INVALID_ARGUMENT);
		$format = $this->evaluateArg($args[0]);
		if(is_null($format)) throw new FuncExpEvalException("date function takes at least one parameter, the format", FuncExpEvalException::INVALID_ARGUMENT);
		if($nArgs > 1) {
			$timestamp = $this->evaluateArg($args[1]);
		}
		else $timestamp = null;
		if(isset($timestamp)) return date($format, $timestamp);
		else return date($format);
	}
	
	/**
	 * strtotime. See http://www.php.net/strtotime
	 */
	public function strtotime($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("strtotime function takes at least one parameter, the time string", FuncExpEvalException::INVALID_ARGUMENT);
		$timeString = $this->evaluateArg($args[0]);
		if(is_null($timeString)) throw new FuncExpEvalException("strtotime function takes at least one parameter, the time string", FuncExpEvalException::INVALID_ARGUMENT);
		if($nArgs > 1) {
			$timestamp = $this->evaluateArg($args[1]);
		}
		else $timestamp = null;
		if(isset($timestamp)) return strtotime($timeString, $timestamp);
		else return strtotime($timeString);
	}

	/**
	 * Explodes a string given a separator
	 * FuncExp signature : <code>explode(sep,str,index=null)</code><br/>
	 * Where arguments are :
	 * - Arg(0) sep: String. The separator against which to explode the string
	 * - Arg(1) str: String. The string to be exploded
	 * - Arg(2) index: int. If specified, then returns the value found at this index in array resulting from the explode. If not specified, returns the whole array.
	 * @return Array|String	
	 */
	public function explode($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("args should have at least one value which is the separator", FuncExpEvalException::INVALID_ARGUMENT);
		if($nArgs < 2) throw new FuncExpEvalException("args should have at least one value to explode", FuncExpEvalException::INVALID_ARGUMENT);
		$sep = $this->evaluateArg($args[0]);
		$str = $this->evaluateArg($args[1]);
		$returnValue = explode($sep, $str);
		$nValues = count($returnValue);
		if($nArgs > 2) {
			if($nValues <= 0) $returnValue = '';
			else {
				$index = (int)$this->evaluateArg($args[2]);
				if($index < 0) $index = 0;
				elseif($index > ($nValues-1)) $index = ($nValues-1);
				$returnValue = $returnValue[$index];
			}
		}
		return $returnValue;
	}
	/**
	 * Explodes a string given a separator and returns the value specified by the index
	 * FuncExp signature : <code>getExplodedValue(sep,str,index)</code><br/>
	 * Where arguments are :
	 * - Arg(0) sep: String. The separator against which to explode the string
	 * - Arg(1) str: String. The string to be exploded
	 * - Arg(2) index: int. Returns the value found at this index in array resulting from the explode. If index is out of range, returns empty string.
	 * @return String
	 */
	public function getExplodedValue($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<3) $args[]=0;
		return $this->explode($args);
	}
	/**
	 * in_array. See http://www.php.net/in_array
	 */
	public function in_array($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException("in_array function takes two parameters, the value to search and the array", FuncExpEvalException::INVALID_ARGUMENT);
		$value = $this->evaluateArg($args[0]);
		$arr = $this->evaluateArg($args[1]);
		if(is_array($arr)) return in_array($value,$arr);
		else return false;
	}
}