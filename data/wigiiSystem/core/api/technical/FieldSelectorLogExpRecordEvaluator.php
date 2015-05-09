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
 * A class which evaluates a Field Selector LogExp against the values of a record.
 * Returns true, if the log exp returns true.
 * Created by LWR on 06/06/2013
 * Modified by CWE on 11.09.2014 to support multi-valued objects (e.g. MultipleAttributes)
 */
class FieldSelectorLogExpRecordEvaluator implements LogExpVisitor
{
	private $_debugLogger;
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("FieldSelectorLogExpRecordEvaluator");
		}
		return $this->_debugLogger;
	}
	
	protected $record;

	// Object lifecycle

	public function reset($record=null)
	{
		$this->freeMemory();
		$this->record = $record;
	}
	public function freeMemory() {
		unset($this->record);
	}

	// Service implementation

	/**
	 * @param Record $record a record
	 * @param LogExp $fsLogExp a field selector log exp
	 * @return true if record is matching fsLogExp
	 */
	public function evaluate($record, $fsLogExp)
	{
		if(isset($fsLogExp))
		{
			$this->reset($record);
			$returnValue = $fsLogExp->acceptLogExpVisitor($this);
			$this->freeMemory();
			return $returnValue;
		}
	}


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
	protected function getValue($obj){
		return $this->record->getFieldValue($obj->getFieldName(), $obj->getSubFieldName());
	}
	
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
	 * @param Scalar $oVal object value
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