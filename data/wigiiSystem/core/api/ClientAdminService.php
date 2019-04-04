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
 * Wigii client administration service
 * Created by CWE on 12 juin 09
 * Modified by CWE on 23 mars 14 to add Wigii Setup functions
 * Modified by CWE on 05.02.2016 to add Wigii Monitoring functions
 * Modified by Medair(CWE) on 07.07.2017 to query Wigii Dimensions
 */
interface ClientAdminService
{
	// Client access
	
	/**
	 * Returns the client with the specified name
	 * @param Principal $principal authenticated user performing the operation
	 * @throws ClientAdminServiceException if an error occurs
	 * @return Client
	 */
	public function getClient($principal, $clientName);

	/**
	 * Returns the empty client
	 * @throws ClientAdminServiceException if an error occurs
	 * @return Client
	 */
	public function getEmptyClient();

	/**
	 * Returns default configured client
	 * @throws ClientAdminServiceException if an error occurs
	 * @return Client
	 */
	public function getDefaultClient();
	
	
	// Wigii setup functions
	
	/**
	 * Synchronizes the values of a dimension with a source.
	 * See the module Dimensions for more details about the content.
	 * @param Principal $principal authenticated user performing the operation 
	 * @param DataFlowDumpable|InputDataFlow|ObjectList|Array|DataFlowSelector $source a list of values defining the dimension. Can be a data flow.
	 * @param String|Int $dimension the dimension identifier. If a number, then assumes it is a group id, if a string assumes it is a group name. 
	 * @param Int $parentGroupId defines the parent group containing the dimension to synchronize. 
	 * If the dimension already exists and group name is unique or dimension is a group id then this parameter is not mandatory.
	 * @param Int $checkDeletedValues one of 0, 1, 2. 
	 * If 0=NoCheckForDeletedValues, then no check is done to detect if current dimension has values that do not exist anymore in the source;
	 * If 1=MarkDeletedValuesAsDeprecated, then dimension values that do not exist anymore in the source are marked as 'Deprecated';
	 * If 2=DeleteNonExistingValues, then dimension values that do not exist anymore in the source are deleted.
	 * @param Boolean $markNewValuesAsImportant if true then newly inserted values in the dimension are marked as 'Important1' else no special markup.
	 * @throws ClientAdminServiceException if an error occurs
	 * @return Int the number of values in the dimension.
	 */
	public function syncDimension($principal, $source, $dimension, $parentGroupId=null, 
		$checkDeletedValues=1, $markNewValuesAsImportant=true);
	
	/**
	 * For a given dimension, searches for a specific value based on its label.
	 * Only returns first non-deprecated match. To get several matches, use dimension2df Data Flow connector. 
	 * @param Principal $principal current principal performing the operation
	 * @param String|Int|LogExp $selector The dimension selector. Can be a group id, a group name or a group log exp.
	 * @param String $label dimension label for which to search for a value
	 * @return String dimension value if found, else null. If multiple matches, then takes first match.
	 */
	public function lookupDimensionValueByLabel($principal,$selector,$label);
	
	/**
	 * Returns an array with all the values of a dimension.
	 * @param Principal $principal current principal performing the operation
	 * @param String|Int|LogExp $selector The dimension selector. Can be a group id, a group name or a group log exp.
	 * @param bool $includeDeprecated if true, deprecated values will also be returned. By default they are filtered.
	 * @return Array an array filled with the dimension values (array key is also the value to enable direct value matching)
	 */
	public function getDimensionValues($principal,$selector,$includeDeprecated=false);
	
	/**
	 * Synchronizes the configuration fields mirror with the xml sources.
	 * @param Principal $principal authenticated user performing the operation 
	 * @param Int $groupId the folder id of module WigiiCfgField which acts as a root folder 
	 * for the configuration mirror. Three level of subfolders will be created :
	 * 1. folders having a name equal to WigiiNamespace names
	 * 2. folders having a name equal to Module names
	 * 3. folders having a name equal to Group ids.
	 * The leaves will be Wigii elements of module WigiiCfgField reflecting module configuration fields.
	 * @param String $fileName an optional configuration file name. If set, then synchronizes only the content
	 * of this file (the file must exist in the client configuration folder), 
	 * else synchronizes all the files found in the client configuration folder.
	 * @param Int $checkDeletedFields one of 0, 1, 2. 
	 * If 0=NoCheckForDeletedFields, then no check is done to detect if current module has fields that do not exist anymore in the xml source;
	 * If 1=MarkDeletedFieldsAsDeprecated, then module fields that do not exist anymore in the xml source are marked as 'Deprecated';
	 * If 2=DeleteNonExistingFields, then module fields that do not exist anymore in the xml source are deleted.
	 * @param Boolean $markNewFieldsAsImportant if true then newly inserted fields in the module are marked as 'Important1' else no special markup.
	 * @return int the number of configuration files which where synchronized
	 * @throws ClientAdminServiceException if an error occurs
	 */
	public function syncCfgFields($principal, $groupId, $fileName=null,
		$checkDeletedFields=1, $markNewFieldsAsImportant=true);
	
	
	// Wigii monitoring configuration
	
	/**
	 * Configures the monitoring of this Wigii Client
	 * @param ObjectConfigurator $config a map of configuration options
	 */
	public function configureMonitoring($config);
	
	/**
	 * @param boolean $enabled if true, then turns on monitoring of Fatal Errors for this Client
	*/
	public function monitorFatalError($enabled);
	
	// Wigii signals
	
	/**
	 * Entry point to signal a Fatal Exception or Error to the monitoring system.
	 * This should be called normally in the ExceptionSink class or in any other top level piece of code which handles Fatal errors.
	 * This function does NOT communicate with the end user. It should be used ONLY to signal a fatal error to the monitoring system,
	 * communication with the end user should be done through the ExceptionSink or handeld by the caller himself.
	 * @param Exception|String $exception exception or fatal error string
	 * @return null This function is silent, does not return any value and doesn't throw any exceptions.
	*/
	public function signalFatalError($exception);
}
