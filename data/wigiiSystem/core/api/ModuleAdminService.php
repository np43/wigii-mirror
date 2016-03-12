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
 * Wigii modules administration service
 * Created by CWE on 13 juin 09
 */
interface ModuleAdminService
{
	/**
	 * Returns an instance of the Admin module
	 * @param Principal $principal authenticated user performing the operation
	 * @throws ModuleAdminServiceException if an error occurs
	 * @return Module
	 */
	public function getAdminModule($principal);

	/**
	 * Returns an instance of the Home module
	 * @param Principal $principal authenticated user performing the operation
	 * @throws ModuleAdminServiceException if an error occurs
	 * @return Module
	 */
	public function getHomeModule($principal);
	
	/**
	 * Returns an instance of the Dimensions module
	 * @param Principal $principal authenticated user performing the operation
	 * @throws ModuleAdminServiceException if an error occurs
	 * @return Module
	 */
	public function getDimensionsModule($principal);
	
	/**
	 * Returns the module with the specified name
	 * @param Principal $principal authenticated user performing the operation
	 * @throws ModuleAdminServiceException if an error occurs
	 * @return Module
	 */
	public function getModule($principal, $moduleName);

	/**
	 * Returns module behind selected groups
	 * precondition: all groups should belong to same module, else MySqlFacadeException is thrown.
	 */
	public function getModuleFromSelectedGroups($principal, $groupLogExp);
	
	/**
	 * Returns array of modules behind selected groups
	 */
	public function getModulesFromSelectedGroups($principal, $groupLogExp);
	
	/**
	 * Returns an array of Module Object. The keys are the moduleName.
	 * modulesNames: can be a semicolon separated string of module names,
	 * 				an array of module names
	 * 				an array of modules
	 * principal: authenticated user performing the operation
	 * throws ModuleAdminServiceException if an error occurs
	 */
	public function formatModuleArray($principal, $moduleNames);

	/**
	 * Returns a semicolon separated string of module names or null
	 * Module names are sorted in alphabetical order
	 */
	public function moduleArrayToString($modules);

	/**
	 * Returns default propagation for module
	 * returns :
	 * 0: no propagation but always read on parents
	 * 1: rights of parents are propagated through children
	 * 2: rights of parents are propagated through children + can read all parents
	 */
	public function getDefaultPropagation($module);
}


