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

/* wigii configuration service
 * Created by CWE on 2 juin 09
 */
interface ConfigService
{
	/**
	 * Returns the value of a parameter given its name or null if not defined
	 * principal: authenticated user performing the operation
	 * module: module for which we want to retrieve the parameter value,
	 *         it can be null, in that case it means a global parameter to whole wigii system
	 * throws ConfigServiceException if an error occurs
	 */
	public function getParameter($principal, $module, $name);

	/**
	 * Returns true if config is dynamic at the group level,
	 * false if the config is dynamic at the module level only
	 */
	public function allowGroupDynamicConfig($principal, $module);
	/**
	 * returns group / user config filename without the wigiiNamespace
	 */
	public function getGroupConfigFilename($principal, $group);
	public function doesGroupHasConfigFile($principal, $group);
	public function getUserConfigFilename($principal, $module, $user);
	public function doesUserHasConfigFile($principal, $module, $user);
	public function getModuleConfigFilename($principal, $module, $wigiiNamespace);

	/**
	 * do an actOnFilename on the $filenameRenderer for each filename of a config file for:
	 * - config file only for the wigiiNamespace of the principal
	 * - config file only for the modules the principal can access
	 */
	public function listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer);

	
	/**
	 * Returns the value of a parameter given its name or null if not defined
	 * principal: authenticated user performing the operation
	 * group: group for which we want to retrieve the parameter value, it can not be null.
	 * throws ConfigServiceException if an error occurs
	 */
	public function getGroupParameter($principal, $group, $name);

	/**
	 * Returns a list of fields defined for the given module and the given user activity
	 * principal: authenticated user performing the operation
	 * module: module for which we want the list of defined fields,
	 * activity: a wigii user activity
	 * throws ConfigServiceException if an error occurs
	 */
	public function getFields($principal, $module, $activity, $fieldList);

	/**
	 * Returns a list of fields defined for the given group and the given user activity
	 * principal: authenticated user performing the operation
	 * group: group for which we want to retrieve the list of defined fields, it can not be null.
	 * activity: a wigii user activity
	 * throws ConfigServiceException if an error occurs
	 */
	public function getGroupFields($principal, $group, $activity, $fieldList);

	/**
	 * Returns a list of fields defined for the given groups and the given user activity
	 * principal: authenticated user performing the operation
	 * groupList: list of groups for which we want to retrieve the union of the defined fields.
	 * all groups must be in the same module.
	 * activity: a wigii user activity
	 * throws ConfigServiceException if an error occurs
	 */
	public function getGroupsFields($principal, $groupList, $activity, $fieldList);

	public function getTemplatePath($principal, $module, $activity);
	public function getGroupTemplatePath($principal, $group, $activity);

	/**
	 * Returns the configuration for this module seen by this authenticated user
	 * principal: authenticated user performing the operation
	 * module: module for which we want to retrieve the configuration, it can not be null.
	 * throws ConfigServiceException if an error occurs
	 */
	public function m($principal, $module);

	/**
	 * Returns the fields configuration for this module and activity seen by this authenticated user
	 * principal: authenticated user performing the operation
	 * module: module for which we want to retrieve the configuration, it can not be null.
	 * activity: a wigii user activity
	 * throws ConfigServiceException if an error occurs
	 */
	public function mf($principal, $module, $activity=null);

	/**
	 * Returns the activity complete configuration for this module and activity seen by this authenticated user
	 * principal: authenticated user performing the operation
	 * module: module for which we want to retrieve the configuration, it can not be null.
	 * activity: a wigii user activity
	 * throws ConfigServiceException if an error occurs
	 */
	public function ma($principal, $module, $activity);
	
	/**
	 * Returns the configuration for this group seen by this authenticated user
	 * principal: authenticated user performing the operation
	 * group: group for which we want to retrieve the configuration, it can not be null.
	 * throws ConfigServiceException if an error occurs
	 */
	public function g($principal, $group);

	/**
	 * Returns the fields configuration for this group and activity seen by this authenticated user
	 * principal: authenticated user performing the operation
	 * group: group for which we want to retrieve the configuration, it can not be null.
	 * activity: a wigii user activity
	 * throws ConfigServiceException if an error occurs
	 */
	public function gf($principal, $group, $activity=null);

	/**
	 * Returns the activity complete configuration for this group and activity seen by this authenticated user
	 * principal: authenticated user performing the operation
	 * group: group for which we want to retrieve the configuration, it can not be null.
	 * activity: a wigii user activity
	 * throws ConfigServiceException if an error occurs
	 */
	public function ga($principal, $group, $activity);
	
	/**
	 * Returns the xml configuration for this datatype
	 * datatypeName: datatype name for which we want to retrieve the configuration, it can not be null.
	 * throws ConfigServiceException if an error occurs
	 */
	public function dt($datatypeName);

	/**
	 * Returns a DataType object given its name
	 * datatypeName: datatype name for which we want to retrieve the configuration, it can not be null.
	 * throws ConfigServiceException if an error occurs
	 */
	public function getDataType($datatypeName);



	////////////////////////
	// OPERATION DELEGATION
	////////////////////////



	/**
	 * Returns the Admin module configuration for the given client at the client level.
	 * principal: authenticated user performing the operation
	 * throws ConfigServiceException if an error occurs
	 */
	public function getAdminConfigForClient($principal, $client);
}
