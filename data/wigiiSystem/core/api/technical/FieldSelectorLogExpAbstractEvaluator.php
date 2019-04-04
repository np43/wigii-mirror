<?php
/**
 *  This file is part of Wigii.
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
 * An abstract class which evaluates a Field Selector LogExp. Subclasses should redefine the getValue(fs) method to fetch the value behind a FieldSelector.
 * Returns true, if the log exp returns true.
 * Created by CWE on 08.09.2015 by refactoring FieldSelectorLogExpRecordEvaluator
 */
abstract class FieldSelectorLogExpAbstractEvaluator implements LogExpVisitor
{

	// LogExp visitor implementation

	
	public function actOnAndExp($andLogExp)
	{
		$ops = $andLogExp->getOperands();
		if(!empty($ops))
		{
			foreach($ops as $logExp) {
				if(!$logExp->acceptLogExpVisitor($this)) return false;
			}
			return true;
		}
	}
	public function actOnOrExp($orLogExp)
	{
		$ops = $orLogExp->getOperands();
		if(!empty($ops))
		{
			foreach($ops as $logExp) {
				if($logExp->acceptLogExpVisitor($this)) return true;
			}
			return false;
		}
	}
	public function actOnNotExp($notLogExp)
	{
		$logExp = $notLogExp->getLogExp();
		if(isset($logExp))
		{
			if($logExp->acceptLogExpVisitor($this)) return false;
			else return true;
		}
	}
	
	/**
	 * @param FieldSelector $obj evaluates a FieldSelector and returns its value
	 * @return mixed
	 */
	abstract protected function getValue($obj);
	
	public function actOnSmaller($obj, $val)
	{
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return null < $val;
			else {
				foreach($oVal as $v) {
					if(!($v < $val)) return false;
				}
				return true;
			}
		}
		else return $oVal < $val;
	}
	
	public function actOnSmallerEq($obj, $val)
	{
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return null <= $val;
			else {
				foreach($oVal as $v) {
					if(!($v <= $val)) return false;
				}
				return true;
			}
		}
		else return $oVal <= $val;
	}
	public function actOnGreater($obj, $val)
	{
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return null > $val;
			else {
				foreach($oVal as $v) {
					if(!($v > $val)) return false;
				}
				return true;
			}
		}
		else return $oVal > $val;
	}
	public function actOnGreaterEq($obj, $val)
	{
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return null >= $val;
			else {
				foreach($oVal as $v) {
					if(!($v >= $val)) return false;
				}
				return true;
			}
		}
		else return $oVal >= $val;
	}
	public function actOnEqual($obj, $val)
	{
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return null == $val;
			else {
				foreach($oVal as $v) {
					if(!($v == $val)) return false;
				}
				return true;
			}
		}
		else return $oVal == $val;
	}
	public function actOnNotEqual($obj, $val)
	{
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return null != $val;
			else {
				foreach($oVal as $v) {
					if(!($v != $val)) return false;
				}
				return true;
			}
		}
		else return $oVal != $val;
	}
	public function actOnIn($obj, $vals)
	{
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return in_array(null, $vals);
			else {
				foreach($oVal as $v) {
					if(!in_array($v, $vals)) return false;
				}
				return true;
			}
		}
		else return in_array($oVal, $vals);
	}
	public function actOnNotIn($obj, $vals)
	{
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return !in_array(null, $vals);
			else {
				foreach($oVal as $v) {
					if(in_array($v, $vals)) return false;
				}
				return true;
			}
		}
		else return !in_array($oVal, $vals);
	}
	public function actOnLike($obj, $val)
	{
		//if $val is in the format %...% then use stripos
		//other wise use preg_match
		if(mb_strpos($val, "%")===0 && mb_strrpos($val, "%")==(mb_strlen($val)-1) && mb_substr_count($val, "%")==2){
			$pattern = str_replace("%", "", $val);
			$t = 0;
		} else if(mb_strpos($val, "%")!==false || mb_strpos($val, "*")!==false || mb_strpos($val, ".")!==false || mb_strpos($val, "[")!==false || mb_strpos($val, "{")!==false){
			$pattern = "/".str_replace("%", ".*", $val)."/i";
			$t = 1;			
		} else {
			$pattern = $val;
			$t = 2;
		}
		
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return $this->doLikePatternMatchOnValue($t, null, $pattern);
			else {
				foreach($oVal as $v) {
					if($this->doLikePatternMatchOnValue($t, $v, $pattern)) return true;
				}
				return false;
			}
		}
		else return $this->doLikePatternMatchOnValue($t, $oVal, $pattern);
	}
	public function actOnNotLike($obj, $val)
	{
		//if $val is in the format %...% then use stripos
		//other wise use preg_match
		if(mb_strpos($val, "%")===0 && mb_strrpos($val, "%")==(mb_strlen($val)-1) && mb_substr_count($val, "%")==2){
			$pattern = str_replace("%", "", $val);
			$t = 0;
		} else if(mb_strpos($val, "%")!==false || mb_strpos($val, "*")!==false || mb_strpos($val, ".")!==false || mb_strpos($val, "[")!==false || mb_strpos($val, "{")!==false){
			$pattern = "/".str_replace("%", ".*", $val)."/i";
			$t = 1;
		} else {
			$pattern = $val;
			$t = 2;
		}
		
		$oVal = $this->getValue($obj);
		if(is_array($oVal)) {
			if(empty($oVal)) return !$this->doLikePatternMatchOnValue($t, null, $pattern);
			else {
				foreach($oVal as $v) {
					if($this->doLikePatternMatchOnValue($t, $v, $pattern)) return false;
				}
				return true;
			}
		}
		else return !$this->doLikePatternMatchOnValue($t, $oVal, $pattern);
	}
	/**
	 * Executes the like pattern matching on a value
	 * @param int $t function type to be used. One of 0=stripos, 1=preg_match, 2=comparison
	 * @param String|Number $oVal object value
	 * @param String $pattern pattern for comparison
	 */
	private function doLikePatternMatchOnValue($t, $oVal, $pattern) {
		switch($t) {
			case 0: return mb_stripos($oVal, $pattern)!==false;
			case 1: return preg_match($pattern, $oVal);
			case 2: return mb_strtolower($pattern)==mb_strtolower($oVal);
		}
	}
	
	public function actOnMatchAgainst($obj, $val)
	{
		throw new RecordException("Binary LogExp MATCHAGAINST is not supported", RecordException::UNSUPPORTED_OPERATION);
	}
	public function actOnInGroup($inGroupLogExp)
	{
		throw new RecordException("LogExp ING is not supported", RecordException::UNSUPPORTED_OPERATION);
	}
	public function actOnNotInGroup($notInGroupLogExp)
	{
		throw new RecordException("LogExp NOTING is not supported", RecordException::UNSUPPORTED_OPERATION);
	}
}