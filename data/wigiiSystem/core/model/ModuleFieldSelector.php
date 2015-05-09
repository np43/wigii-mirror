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
 * A Module Field selector
 * Created by CWE on 01 october 2014
 */
class ModuleFieldSelector extends Model
{
	private $moduleName;
	private $fieldSelectorList;
	
	public static function createInstance($moduleName, $fieldSelectorList) {
		$returnValue = new self();
		$returnValue->setModuleName($moduleName);
		$returnValue->setFieldSelectorList($fieldSelectorList);
		return $returnValue;
	}
	
	/**
	 * @return String the module name
	 */
	public function getModuleName() {
		return $this->moduleName;
	}
	/**
	 * @param String $moduleName the module name
	 */
	public function setModuleName($moduleName) {
		$this->moduleName = $moduleName;
	}
	
	/**
	 * @return FieldSelectorList the field selector list
	 */	
	public function getFieldSelectorList() {
		return $this->fieldSelectorList;
	}
	
	/**
	 * @param FieldSelectorList $fieldSelectorList the field selector list
	 */
	public function setFieldSelectorList($fieldSelectorList) {
		$this->fieldSelectorList = $fieldSelectorList;
	}
}