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
 * CalculatedFieldSelectorMap array implementation
 * Created by CWE on 27 janvier 2014
 */
class CalculatedFieldSelectorMapArrayImpl extends ObjectListArrayImpl implements CalculatedFieldSelectorMap
{
	public static function createInstance() {
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}

	// CalculatedFieldSelectorMap implementation
	
	public function setCalculatedFieldSelector($calculatedFieldSelector) {
		$this->objArray[$this->getCalculatedFieldSelectorKey($calculatedFieldSelector)] = $calculatedFieldSelector;
	}
		
	public function setCalculatedFieldSelectorByFieldName($fieldName, $funcExp, $subFieldName=null) {
		$cfs = CalculatedFieldSelector::createInstanceByFieldName($fieldName, $funcExp, $subFieldName);
		$this->setCalculatedFieldSelector($cfs);
	}
	
	public function getFuncExp($fieldSelector) {
		$cfs = $this->objArray[$this->getKeyByFieldSelector($fieldSelector)];
		if(isset($cfs)) return $cfs->getFuncExp();
		else return null;
	}
		
	public function getFuncExpByFieldName($fieldName, $subFieldName=null) {
		$cfs = $this->objArray[$this->getKeyByFieldName($fieldName, $subFieldName)];
		if(isset($cfs)) return $cfs->getFuncExp();
		else return null;
	}
	
	// Implementation
	
	protected function getCalculatedFieldSelectorKey($cfs) {
		if(isset($cfs)) $fs = $cfs->getFieldSelector();
		else $fs = null;
		return $this->getKeyByFieldSelector($fs);
	}
	protected function getKeyByFieldSelector($fs) {
		if(isset($fs)) {
			$fieldName = $fs->getFieldName();
			$subFieldName = $fs->getSubFieldName();
		}
		else {
			$fieldName = '';
			$subFieldName = '';
		}
		return $this->getKeyByFieldName($fieldName, $subFieldName);
	}
	protected function getKeyByFieldName($fieldName, $subFieldName=null) {
		return "($fieldName($subFieldName))";
	}
}