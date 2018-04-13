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
	 * explode. See http://www.php.net/explode	
	 */
	public function explode($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<2) throw new FuncExpEvalException('explode function takes at least two arguments which are the separator and the string to explode', FuncExpEvalException::INVALID_ARGUMENT);
		$sep = $this->evaluateArg($args[0]);
		$str = $this->evaluateArg($args[1]);
		if($nArgs>2) $limit=$this->evaluateArg($args[2]);
		else $limit=null;		
		
		if(isset($limit)) return explode($sep,$str,$limit);
		else return explode($sep,$str);
	}
	/**
	 * Explodes a string given a separator and returns the value specified by the index
	 * FuncExp signature : <code>getExplodedValue(sep,str,index)</code><br/>
	 * Where arguments are :
	 * - Arg(0) sep: String. The separator against which to explode the string
	 * - Arg(1) str: String. The string to be exploded
	 * - Arg(2) index: int. Returns the value found at this index in array resulting from the explode. If index is higher that last value, returns last value. By default returns first value.
	 * @return String
	 */
	public function getExplodedValue($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<2) throw new FuncExpEvalException('getExplodedValue function takes at least two arguments which are the separator and the string to explode', FuncExpEvalException::INVALID_ARGUMENT);
		$sep = $this->evaluateArg($args[0]);
		$str = $this->evaluateArg($args[1]);
		if($nArgs>2) $index=$this->evaluateArg($args[2]);
		else $index=0;		
		
		$returnValue = explode($sep, $str);
		if($returnValue) $nValues = count($returnValue);
		else $nValues=0;
		
		if($nValues<=0) $returnValue='';
		elseif($index > $nValues-1) $returnValue=$returnValue[$nValues-1];
		elseif($index<=0) $returnValue=$returnValue[0];
		else $returnValue=$returnValue[$index];
				
		return $returnValue;
	}
	/**
	 * Explodes a string given a separator until a given limit and returns the non exploded tail
	 * FuncExp signature : <code>getExplodedTail(sep,str,limit)</code><br/>
	 * Where arguments are :
	 * - Arg(0) sep: String. The separator against which to explode the string
	 * - Arg(1) str: String. The string to be exploded
	 * - Arg(2) limit: int. The number of parts in which to split the string. Example: 2 means a head and a tail, 3 means h1,h2,tail. See explode function for more details.
	 * @example calling getExplodedTail(sep,str,5) is equivalent to call explode(sep,str,5)[4]
	 * @return String
	 */
	public function getExplodedTail($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs<3) throw new FuncExpEvalException('getExplodedTail function takes three arguments which are the separator, the string to explode and the number of parts', FuncExpEvalException::INVALID_ARGUMENT);
		$sep = $this->evaluateArg($args[0]);
		$str = $this->evaluateArg($args[1]);
		$limit=$this->evaluateArg($args[2]);		
	
		$returnValue = explode($sep, $str, $limit);
		if(empty($returnValue)) $returnValue='';
		else $returnValue = end($returnValue);
		return $returnValue;
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
	/**
	 * array_slice. See http://www.php.net/array_slice
	 */
	public function array_slice($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException("array_slice function takes two parameters, the array and the offset", FuncExpEvalException::INVALID_ARGUMENT);
		$arr = $this->evaluateArg($args[0]);
		$offset = $this->evaluateArg($args[1]);
		if($nArgs>2) $length=$this->evaluateArg($args[2]);
		else $length=null;
		if($nArgs>3) $preserve_keys=$this->evaluateArg($args[3]);
		else $preserve_keys=false;
		return array_slice($arr, $offset, $length, $preserve_keys);
	}
	/**
	 * array_values. See http://www.php.net/array_values
	 */
	public function array_values($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if ($nArgs < 1) throw new FuncExpEvalException("array_values function takes one parameters, the array", FuncExpEvalException::INVALID_ARGUMENT);
		$arr = $this->evaluateArg($args[0]);
		return array_values($arr);
	}
	/**
	 * Returns the difference of two arrays
	 * FuncExp signature : <code>array_diff(array1,array2)</code><br/>
	 * Where arguments are :
	 * - Arg(0) array1: Array. The source array
	 * - Arg(1) array2: Array. The array to diff against
	 * @return Array returns an array containing all elements in array1 which are not in array2
	 */
	public function array_diff($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 2) throw new FuncExpEvalException("array_diff function takes two parameters, the array1 and array2", FuncExpEvalException::INVALID_ARGUMENT);
	    $arr1 = $this->evaluateArg($args[0]);
	    if(!is_array($arr1)) throw new FuncExpEvalException('array1 should be an array',FuncExpEvalException::INVALID_ARGUMENT);
	    $arr2 = $this->evaluateArg($args[1]);
	    if(!is_array($arr2)) throw new FuncExpEvalException('array2 should be an array',FuncExpEvalException::INVALID_ARGUMENT);
	    return array_diff($arr1, $arr2);
	}
	/**
	 * Returns the intersection of two arrays
	 * FuncExp signature : <code>array_diff(array1,array2)</code><br/>
	 * Where arguments are :
	 * - Arg(0) array1: Array. The source array
	 * - Arg(1) array2: Array. The array to intersect with
	 * @return Array returns an array containing all elements in array1 which are in array2
	 */
	public function array_intersect($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 2) throw new FuncExpEvalException("array_diff function takes two parameters, the array1 and array2", FuncExpEvalException::INVALID_ARGUMENT);
	    $arr1 = $this->evaluateArg($args[0]);
	    if(!is_array($arr1)) throw new FuncExpEvalException('array1 should be an array',FuncExpEvalException::INVALID_ARGUMENT);
	    $arr2 = $this->evaluateArg($args[1]);
	    if(!is_array($arr2)) throw new FuncExpEvalException('array2 should be an array',FuncExpEvalException::INVALID_ARGUMENT);
	    return array_intersect($arr1, $arr2);
	}
	/**
	 * array_unique. See http://www.php.net/array_unique
	 */
	public function array_unique($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if ($nArgs < 1) throw new FuncExpEvalException("array_unique function takes one parameters, the array", FuncExpEvalException::INVALID_ARGUMENT);
		$arr = $this->evaluateArg($args[0]);
	
		if($nArgs>1) $sort=$this->evaluateArg($args[1]);
		else $sort = SORT_REGULAR;
		return array_unique($arr, $sort);
	}
	/**
	 * array_implode. See http://www.php.net/implode
	 */
	public function array_implode($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if ($nArgs < 1) throw new FuncExpEvalException("array_implode function takes one parameters, the array", FuncExpEvalException::INVALID_ARGUMENT);
		if ($nArgs > 1){
	
			return implode($this->evaluateArg($args[0]), $this->evaluateArg($args[1]));
		}else{
			return implode($this->evaluateArg($args[0]));
		}
	}
	/**
	 * substr. See http://www.php.net/substr
	 */
	public function substr($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 2) throw new FuncExpEvalException("substr function takes at least two parameters, the string and the index from which to extract the sub sting", FuncExpEvalException::INVALID_ARGUMENT);
		$str = $this->evaluateArg($args[0]);
		$index = $this->evaluateArg($args[1]);
		if($nArgs>2) $length=$this->evaluateArg($args[2]);
		else $length=null;
		if(isset($length)) return substr($str, $index, $length);
		else return substr($str, $index);
	}
	/**
	 * strlen. See http://www.php.net/strlen
	 */
	public function strlen($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs>0) {
			$str = $this->evaluateArg($args[0]);
			if($str) return strlen($str);
			else return 0;
		}
		else return 0; 
	}
	/**
	 * str_replace. See http://www.php.net/str_replace
	 */
	public function str_replace($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 3) throw new FuncExpEvalException("str_replace function takes at least three parameters, the search, the replace and the subject", FuncExpEvalException::INVALID_ARGUMENT);
		$search = $this->evaluateArg($args[0]);
		$replace = $this->evaluateArg($args[1]);
		$subject = $this->evaluateArg($args[2]);
		return str_replace($search, $replace, $subject);
	}
	/**
	 * urlencode. See http://www.php.net/urlencode
	 */
	public function urlencode($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new FuncExpEvalException("urlencode function takes at least one parameter, the url string to encode", FuncExpEvalException::INVALID_ARGUMENT);
	    return urlencode($this->evaluateArg($args[0]));
	}
	/**
	 * number_format. See http://www.php.net/number_format
	 * By default takes 2 decimals, uses point to separate decimal part, and no thousand sep.
	 */
	public function number_format($args) {
		$nArgs = $this->getNumberOfArgs($args);
		if($nArgs < 1) throw new FuncExpEvalException("number_format function takes at least one parameter the number", FuncExpEvalException::INVALID_ARGUMENT);
		$number = $this->evaluateArg($args[0]);
		if($nArgs>1) $decimals = $this->evaluateArg($args[1]);
		else $decimals=2;
		if($nArgs>2) $dec_point = $this->evaluateArg($args[2]);
		else $dec_point='.';
		if($nArgs>3) $thousands_sep = $this->evaluateArg($args[3]);
		else $thousands_sep = "";
		return number_format($number,$decimals,$dec_point,$thousands_sep);
	}
	/**
	 * round. See http://www.php.net/round
	 * By default rounds half up to integer
	 */
	public function round($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new FuncExpEvalException("round function takes at least one parameter the number", FuncExpEvalException::INVALID_ARGUMENT);
	    $number = $this->evaluateArg($args[0]);
	    if($nArgs>1) $precision = $this->evaluateArg($args[1]);
	    else $precision=0;
	    if($nArgs>2) $mode = $this->evaluateArg($args[2]);
	    else $mode=PHP_ROUND_HALF_UP;
	    return round($number,$precision,$mode);
	}
	
	/**
	 * json_encode. See http://www.php.net/json_encode
	 */
	public function json_encode($args) {
	    $nArgs = $this->getNumberOfArgs($args);
	    if($nArgs < 1) throw new FuncExpEvalException("json_encode function takes at least one parameter the value to encode", FuncExpEvalException::INVALID_ARGUMENT);
	    $value = $this->evaluateArg($args[0]);
	    if($nArgs>1) $options = $this->evaluateArg($args[1]);
	    else $options=0;
	    if($nArgs>2) $depth = $this->evaluateArg($args[2]);
	    else $depth=512;
	    $returnValue = json_encode($value,$options,$depth);
	    if($returnValue === false) throw new FuncExpEvalException('JSON encode error '.json_last_error().' '.json_last_error_msg(), FuncExpEvalException::INVALID_ARGUMENT);
	    return $returnValue;
	}
}