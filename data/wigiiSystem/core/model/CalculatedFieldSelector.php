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
 * An association between a FieldSelector and a FuncExp
 * Created by CWE on 27 janvier 2014
 */
class CalculatedFieldSelector extends Model
{
	private $fieldSelector;
	private $funcExp;

	public static function createInstance($fieldSelector, $funcExp) {
		$returnValue = new self();
		$returnValue->setFieldSelector($fieldSelector);
		$returnValue->setFuncExp($funcExp);
		return $returnValue;
	}

	public static function createInstanceByFieldName($fieldName, $funcExp, $subFieldName=null) {
		return self::createInstance(FieldSelector::createInstance($fieldName, $subFieldName), $funcExp);
	}
	
	public function getFieldSelector() {
		return $this->fieldSelector;
	}
	public function setFieldSelector($fieldSelector) {
		$this->fieldSelector = $fieldSelector;
	}
	
	public function getFuncExp() {
		return $this->funcExp;
	}
	public function setFuncExp($funcExp) {
		$this->funcExp = $funcExp;
	}
}