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
 * ModuleFieldSelectorMap array implementation
 * Created by CWE on 1st october 2014
 */
class ModuleFieldSelectorMapArrayImpl extends ObjectListArrayImpl implements ModuleFieldSelectorMap
{
	public static function createInstance() {
		$returnValue = new self();
		$returnValue->reset();
		return $returnValue;
	}

	// ModuleFieldSelectorMap implementation
	
	public function setModuleFieldSelector($moduleFieldSelector) {
		if(isset($moduleFieldSelector)) {
			$this->objArray[$moduleFieldSelector->getModuleName()] = $moduleFieldSelector;
		}
	}
	
	public function getModuleFieldSelector($moduleName) {
		return $this->objArray[$moduleName];
	}
	
	public function setFieldSelectorList($moduleName, $fieldSelectorList) {
		$mfs = ModuleFieldSelector::createInstance($moduleName, $fieldSelectorList);
		$this->objArray[$moduleName] = $mfs;
	}
	
	public function getFieldSelectorList($moduleName) {
		$mfs = $this->objArray[$moduleName];
		if(isset($mfs)) return $mfs->getFieldSelectorList();
		else return null;
	}
}