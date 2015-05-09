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
 * A map of ModuleFieldSelectors.
 * Key is the module name, value is the field selector list
 * Created by CWE on 1st october 2014
 */
interface ModuleFieldSelectorMap extends ObjectList
{
	/**
	 * Sets a ModuleFieldSelector in the map.
	 * Key is the module name, value is the FieldSelectorList.
	 * Any existing field selector list under the same module name is replaced.
	 * @param ModuleFieldSelector $moduleFieldSelector
	 */
	public function setModuleFieldSelector($moduleFieldSelector);
	
	/**
	 * Returns the ModuleFieldSelector associated to the given module name stored into the map
	 * @param String $moduleName the module name
	 * @return ModuleFieldSelector or null if not found
	 */
	public function getModuleFieldSelector($moduleName);
	
	/**
	 * Sets a FieldSelectorList into the map given a module name.
	 * @param String $moduleName the module name
	 * @param FieldSelectorList $fieldSelectorList the associated field selector list
	 */
	public function setFieldSelectorList($moduleName, $fieldSelectorList);
	
	/**
	 * Returns the FieldSelectorList associated to the given module name stored into the map
	 * @param String $moduleName the module name
	 * @return FieldSelectorList or null if not found
	 */
	public function getFieldSelectorList($moduleName);
}