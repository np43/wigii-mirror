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
 * A Subelement selector
 * Created by CWE on 01 october 2014
 */
class SubElementSelector extends Model {
	private $configPath;
	private $module;
	private $includeChildren;
	private $configMap;
	
	/**
	 * Creates an instance of a SubElementSelector given a configuration path, module (including potential children) 
	 * and optional module configuration map
	 *@param LinkSelectorList $configPath the subelement configuration path from root to subelement
	 *@param Module $module the module of the selected subelement
	 *@param boolean $includeChildren if true, then includes all children subelements of given module
	 *@param ModuleFieldSelectorMap $configMap an optional module configuration map which specifies which fields a requested for each parent modules.
	 */
	public static function createInstance($configPath=null,$module=null,$includeChildren=false,$configMap=null) {
		$returnValue = new self();
		if(isset($configPath)) $returnValue->setConfigPath($configPath);
		if(isset($module)) {
			$returnValue->setModule($module);
			$returnValue->setIncludeChildren($includeChildren);
		}
		if(isset($configMap)) $returnValue->setConfigurationMap($configMap);
		return $returnValue;
	}

	/**
	 * Sets the sub element configuration path.
	 * @param LinkSelectorList $linkSelectorList link selector list from root element to subelement.
	 * Only the field name is required for each LinkSelector objects.
	 */
	public function setConfigPath($linkSelectorList) {
		$this->configPath = $linkSelectorList;
	}
	
	/**
	 * Adds a link selector to the subelement configuration path given its field name.
	 * @param String $fieldName
	 * @return LinkSelectorList the actual configuration path
	 */
	public function addLinkSelector($fieldName) {
		if(!isset($this->configPath)) $this->configPath = LinkSelectorListArrayImpl::createInstance();
		$this->configPath->addLinkSelector(LinkSelector::createConfigInstance($fieldName));
	}
	
	/**
	 * Returns the subelement configuration path from root to subelement
	 * @return LinkSelectorList link selector list from root element to subelement.
	 * Only the field name is defined in each LinkSelector objects.
	 */
	public function getConfigPath(){
	    ServiceException::throwNotImplemented();
	}
	
	/**
	 * Sets the module of the selected subelement
	 * @param Module $module
	 */
	public function setModule($module) {
		$this->module = $module;
	}
	/**
	 * @return Module the module of the selected subelement or null if not specified
	 */
	public function getModule() {
		return $this->module;
	}	
	
	/**
	 * If true, then selects all subelements and their children of the specified module,
	 * else only selects the first level of subelements.
	 * @param boolean $bool
	 */
	public function setIncludeChildren($bool) {
		$this->includeChildren = $bool;
	}
	
	/**
	 * @return boolean if true, then all subelements including children are selected, else only first level.
	 */
	public function includeChildren() {
		return $this->includeChildren;
	}
			
	/**
	 * Sets the module configuration map that will be used by the element lazy loader to know which fields
	 * it has to select for each modules when fetching subelements parents.
	 * @param ModuleFieldSelectorMap $configMap
	 */
	public function setConfigurationMap($configMap) {
		$this->configMap = $configMap;
	}
	
	/**
	 *@return ModuleFieldSelectorMap the module configuration map or null if not set.
	 */
	public function getConfigurationMap() {
		return $this->configMap;
	}
	
	/**
	 * Specifies a FieldSelectorList that should be used by the element lazy loader when fetching subelement
	 * parents of the given module.
	 * @param Module|String $module the module to which associate a FieldSelectorList
	 * @param FieldSelectorList $fieldSelectorList the field selector list specifying the fields that should be fetched for this module. 
	 */
	public function setFieldSelectorListByModule($module, $fieldSelectorList) {
		if($module instanceof Module) $moduleName = $module->getModuleName();
		else $moduleName = $module;
		if(!isset($this->configMap)) $this->configMap = ModuleFieldSelectorMapArrayImpl::createInstance();
		$this->configMap->setFieldSelectorList($moduleName, $fieldSelectorList);
	}
	
	/**
	 * Returns the FieldSelectorList that should be used when fetching subelement parents of the given module.
	 * @param Module|String the module for which to get an associated field selector list
	 * @return FieldSelectorList the field selector list associated to this module or null if not set.
	 */
	public function getFieldSelectorListByModule($module) {
		if(isset($this->configMap)) {
			if($module instanceof Module) $moduleName = $module->getModuleName();
			else $moduleName = $module;
			return $this->configMap->getFieldSelectorList($moduleName);
		}
		else return null;
	}
}