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
 * Argument validator
 * Created by CWE on 7 sept. 09
 * Updated by Wigii.org (LWR) on 18.04.2017 to be compatible with PHP 5.6
 */
class ArgValidator
{
	public static function getInstance()
	{
		return TechnicalServiceProvider::getArgValidator();
	}

	/**
	 * Asserts that boolExp is true, else throws ServiceException with INVALID_ARGUMENT and failMessage
	 */
	public function assert($boolExp, $failMessage, $errCode=null)
	{
		if(is_null($errCode)) $errCode = ServiceException::INVALID_ARGUMENT;
		if (!$boolExp) throw new ServiceException($failMessage, $errCode);
	}

	/**
	 * Check qu'un string ne contient pas de caractère spéciaux et qu'il a une longueur correcte
	 * accepte les espaces les - et les _ et les . @
	 * par rapport à des valeurs Min et Max.
	 * il accepte aussi les caractères accentués (par défaut false)
	 * @param string $string chaine de caractère à checker
	 * @param int $minChars nombre minimal de caractères pour la chaine
	 * @param int $maxChars nombre maximal de caractères pour la chaine
	 * @return bool true si la chaine est correcte
	 */
	public function checkNoSpecialCharsString($string, $minChars, $maxChars, $acceptAccent = false)
	{
		if($acceptAccent){
			return preg_match('/^['.ACCENT_list.'A-Za-z0-9\@\._\-\s]{'.$minChars.','.$maxChars.'}$/', $string);
		} else {
			return preg_match('/^[A-Za-z0-9\@\._\-\s]{'.$minChars.','.$maxChars.'}$/', $string);
		}
	}

	/**
	 * Asserts NoSpecialCharsString.
	 * failMessage: exception message if NoSpecialCharsString is not ok.
	 * throws ServiceException with INVALID_ARGUMENT if NoSpecialCharsString is not ok.
	 */
	public static function assertNoSpecialCharsString($failMessage, $string, $minChars, $maxChars, $acceptAccent = false, $errCode=null)
	{
		$val = ArgValidator::getInstance();
		$val->assert($val->checkNoSpecialCharsString($string, $minChars, $maxChars, $acceptAccent), $failMessage, $errCode);
	}
	
	/**
	 * Check qu'un string ne contient pas de caractère spéciaux et qu'il a une longueur correcte
	 * accepte les espaces les - et les _ et les .
	 * par rapport à des valeurs Min et Max.
	 * @param string $string chaine de caractère à checker
	 * @param int $minChars nombre minimal de caractères pour la chaine
	 * @param int $maxChars nombre maximal de caractères pour la chaine
	 * @return bool true si la chaine est correcte
	 */
	public function checkAlphaNumSpaceDashUnderscorePoint($string, $minChars, $maxChars){
		return preg_match('/^[A-Za-z0-9\._\-\s]{'.$minChars.','.$maxChars.'}$/', $string);
	}
	/**
	 * Asserts AlphaNumSpaceDashUnderscorePoint.
	 * failMessage: exception message if AlphaNumSpaceDashUnderscorePoint is not ok.
	 * throws ServiceException with INVALID_ARGUMENT if AlphaNumSpaceDashUnderscorePoint is not ok.
	 */
	public static function assertAlphaNumSpaceDashUnderscorePoint($failMessage, $string, $minChars, $maxChars, $errCode=null)
	{
		$val = ArgValidator::getInstance();
		$val->assert($val->checkAlphaNumSpaceDashUnderscorePoint($string, $minChars, $maxChars), $failMessage, $errCode);
	}
	

	/**
	 * Checks that a string length is bounded between minChars and maxChars
	 */
	public function checkBoundedString($string, $minChars, $maxChars)
	{
		$l = strlen($string);
		return ($minChars <= $l) &&  ($l <= $maxChars);
	}
	/**
	 * Asserts that a string length is bounded between minChars and maxChars
	 */
	public static function assertBoundedString($failMessage, $string, $minChars, $maxChars, $errCode=null)
	{
		$val = ArgValidator::getInstance();
		$val->assert($val->checkBoundedString($string, $minChars, $maxChars), $failMessage, $errCode);
	}

	/**
	 * Checks that right1 is smaller or equal to right2
	 */
	public function checkLessOrEqualRight($right1, $right2)
	{
		if(!isset($right1)) return true;
		if(is_array($right1) && !isset($right2)) return (count($right1) == 0);
		if(isset($right1) && !isset($right2)) return false;
		if(is_bool($right1) && is_bool($right2)) return (!$right1 || $right2);
		if(is_array($right1) && is_array($right2)) return (count(array_intersect_key($right1, $right2)) == count($right1));
		return false;
	}
	public static function assertLessOrEqualRight($failMessage, $right1, $right2, $errCode=null)
	{
		$val = ArgValidator::getInstance();
		$val->assert($val->checkLessOrEqualRight($right1, $right2), $failMessage, $errCode);
	}

	/**
	 * Checks that array1 is included in array2
	 */
	public function checkArrayInclusion($array1, $array2)
	{
		if(!is_array($array1) || !is_array($array2)) throw new ServiceException('arguments must be arrays', ServiceException::INVALID_ARGUMENT);
		return (count(array_intersect_key($array1, $array2)) == count($array1));
	}
	public static function assertArrayInclusion($failMessage, $array1, $array2, $errCode=null)
	{
		$val = ArgValidator::getInstance();
		$val->assert($val->checkArrayInclusion($array1, $array2), $failMessage, $errCode);
	}
	
	/**
	 * Checks that the diff keys between array1 and array2 are in array
	 */
	public function assetArrayDiffKeyInclusion($array, $array1, $array2, $failMessage, $errCode=null){
		if($array1 ==null && $array2 == null){
			$this->assert(false, $failMessage, $errCode);
			return;
		} else if($array1 == null && $array2!=null ) {
			$diff = $array2;
		} elseif($array2 == null && $array1!=null){
			$diff = $array1;
		} else {
			$diff1 = array_diff_key($array1, $array2);
			$diff2 = array_diff_key($array2, $array1);
			$diff = array_Merge($diff1, $diff2);
		}
		$this->assert(array_intersect_key($diff, $array)!=null, $failMessage, $errCode);
	}
}