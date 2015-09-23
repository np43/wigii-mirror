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
	 * date. See http://www.php.net/manual/en/function.date.php
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
	 * strtotime. See http://www.php.net/manual/en/function.strtotime.php
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
	 * explode. See http://php.net/explode
	 */
	public function explode($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new RecordException("args should have at least one value which is the separator", RecordException::INVALID_ARGUMENT);
		if($nArgs < 2) throw new RecordException("args should have at least one value to explode", RecordException::INVALID_ARGUMENT);
		$sep = $this->evaluateArg($args[0]);
		$returnValue = explode($sep, $this->evaluateArg($args[1]));
		return $returnValue;
	}
}