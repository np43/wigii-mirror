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
 * A Wigii API client that is centered on some selected groups.
 * ConfigService always returns configuration based on the selected groups.
 * Created by CWE on 3 decembre 2013
 * Modified by CWE on 9 février 2014 to add support of sub elements
 */
class GroupBasedWigiiApiClient {	
	private $_debugLogger;
	private $lockedForUse = true;
	private $groupList = null;
	private $recyclableGroupList;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();
		$this->lockedForUse = true;	
		$this->recyclableGroupList = true;
	}
	
	public function freeMemory() {		
		$this->lockedForUse = false;
		if(isset($this->groupList) && $this->recyclableGroupList) $this->groupList->reset();
		else unset($this->groupList);	
	}
	
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	// Dependency injection	
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("GroupBasedWigiiApiClient");
		}
		return $this->_debugLogger;
	}
		
	/**
	 * Sets mandatory dependencies
	 * @param ElementService $elementService ElementService fresh instance
	 * @param FuncExpBuilder $funcExpBuilder FuncExpBuilder fresh instance
	 */
	public function setMandatoryDependencies($elementService, $funcExpBuilder) {
		$this->setElementService($elementService);
		$this->setFuncExpBuilder($funcExpBuilder);
	}	
	/**
	 * Returns true if mandatory dependencies have already been set
	 * (this method is called by the ServiceProvider after recycling the object to check if 
	 * if needs to inject the dependencies again).
	 */
	public function hasMandatoryDependencies() {
		return (isset($this->eltS) && isset($this->funcExpBuilder));
	}
	private $eltS;		
	public function setElementService($elementService) {
		$this->eltS = $elementService;
		// injects config service
		$this->eltS->setConfigService($this->getConfigService());
		if(isset($this->funcExpBuilder)) $this->funcExpBuilder->setElementService($this->eltS);
	}
	private $configS;
	public function setConfigService($configService) {
		$this->configS = $configService;	
		if(isset($this->gbwacConfigS)) $this->gbwacConfigS->setConfigService($this->configS);	
	}
	private $subElementConfigS;
	public function setSubElementConfigService($subElementConfigService) {
		$this->subElementConfigS = $subElementConfigService;
		if(isset($this->eltS)) $this->subElementConfigS->setElementService($this->eltS);
		if(isset($this->gbwacConfigS)) $this->subElementConfigS->setConfigService($this->gbwacConfigS);
	}
	private $funcExpBuilder;	
	public function setFuncExpBuilder($funcExpBuilder) {
		$this->funcExpBuilder = $funcExpBuilder;
		if(isset($this->eltS)) $this->funcExpBuilder->setElementService($this->eltS);
	}
	
	private $gAS;
	public function setGroupAdminService($groupAdminService) {
		$this->gAS = $groupAdminService;
	}
	protected function getGroupAdminService() {
		// autowired
		if(!isset($this->gAS)) {
			$this->gAS = ServiceProvider::getGroupAdminService();
		}
		return $this->gAS;
	}
	
	// Navigation
	
	/**
	 * Centers the Wigii API client on some selected groups
	 * @param Principal $principal the principal used to select the groups
	 * @param LogExp $groupLogExp the group log exp selection
	 * @return int the number of selected groups
	 */
	public function selectGroups($principal, $groupLogExp) {
		if(isset($this->groupList) && $this->recyclableGroupList) $this->groupList->reset();
		else $this->groupList = GroupListArrayImpl::createInstance();
		$this->recyclableGroupList = true;
		$returnValue = $this->getGroupAdminService()->getSelectedGroupsWithoutDetail($principal, $groupLogExp, $this->groupList);
		// resets sub element config cache
		$this->getConfigService()->reset();		
		// injects group list into config service wrapper		
		$this->gbwacConfigS->setGroupList($this->groupList);
		return $returnValue;
	}
	
	/**
	 * Centers the Wigii API client on some groups given into a list	
	 * @param GroupList $groupList the list of groups
	 */
	public function setGroupList($groupList) {
		$this->groupList = $groupList;
		$this->recyclableGroupList = false;
		// resets sub element config cache
		$this->getConfigService()->reset();
		// injects group list into config service wrapper		
		$this->gbwacConfigS->setGroupList($this->groupList);
	}
	
	/**
	 * Returns a reference on the GroupList mapping the selected groups
	 * @return GroupList
	 */
	public function getGroupList() {
		return $this->groupList;
	}
	
	// Wigii API client
	
	/**
	 * Returns a reference on an ElementService instance
	 * @return ElementService
	 */
	public function getElementService() {
		if(!isset($this->eltS)) throw new ServiceException("mandatory dependency ElementService has not been injected, please addon one", ServiceException::CONFIGURATION_ERROR);
		return $this->eltS;
	}
	
	private $gbwacConfigS;
	/**
	 * Returns a reference on a ConfigService instance
	 * @return ConfigService
	 */
	public function getConfigService() {
		if(!isset($this->gbwacConfigS)) {
			// creates wrapper
			$this->gbwacConfigS = new GBWACConfigService();
			// injects default config service if set
			if(isset($this->configS)) $this->gbwacConfigS->setConfigService($this->configS);
		} 
		if(!isset($this->subElementConfigS)) {
			// autowired
			$this->setSubElementConfigService(ServiceProvider::getSubElementConfigService());			
		}
		return $this->subElementConfigS;
	}
	
	/**
	 * Returns a reference on a FuncExpBuilder
	 * @return FuncExpBuilder
	 */
	public function getFuncExpBuilder() {
		if(!isset($this->funcExpBuilder)) throw new ServiceException("mandatory dependency FuncExpBuilder has not been injected, please addon one", ServiceException::CONFIGURATION_ERROR);
		return $this->funcExpBuilder;
	}	
	
	/**
	 * Returns current WigiiNamespace or empty WigiiNamespace if not centered on groups.
	 * @return WigiiNamespace
	 */
	public function getWigiiNamespace() {
		if(!isset($this->gbwacConfigS)) return ServiceProvider::getWigiiNamespaceAdminService()->getEmptyWigiiNamespaceForDefaultClient();
		else return $this->gbwacConfigS->getWigiiNamespace();
	}
	
	/**
	 * Returns current Module or null if not centered on groups.
	 * @return Module
	 */
	public function getModule() {
		if(isset($this->gbwacConfigS)) $returnValue = $this->gbwacConfigS->getModule();
		else $returnValue = null;
		return $returnValue;
	}
}
/**
 * ConfigService wrapper
 * Created by CWE on 3 decembre 2013
 */
class GBWACConfigService implements ConfigService {

	// Dependency injection	
	private $configS;
	public function setConfigService($configService)
	{
		$this->configS = $configService;
	}
	protected function getConfigService()
	{
		// autowired
		if(!isset($this->configS))
		{
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
	}
	
	private $groupList;
	private $groupListModule;
	private $firstGroup;
	private $oneGroup;
	/**
	 * Sets the GroupList to be used as a configuration context
	 * @param GroupList $groupList
	 */
	public function setGroupList($groupList) {		
		$this->groupList = $groupList;
		// extracts group list module
		$this->groupListModule = null;
		$this->oneGroup = false; $this->firstGroup = null;
		$initialized = false;
		if(isset($this->groupList)) {
			foreach($this->groupList->getListIterator() as $group) {
				if(!$initialized) {
					$this->groupListModule = $group->getModule();
					$this->firstGroup = $group;
					$this->oneGroup = true;
					$initialized = true;
				}
				elseif($this->groupListModule !== $group->getModule()) throw new ConfigServiceException("module should be equal for all groups in groupList", ConfigServiceException::INVALID_ARGUMENT);
				else $this->oneGroup = false;
			}
		}
	}
	
	// Context
	
	public function getWigiiNamespace() {
		if(isset($this->firstGroup)) return $this->firstGroup->getWigiiNamespace();
		else return ServiceProvider::getWigiiNamespaceAdminService()->getEmptyWigiiNamespaceForDefaultClient();
	}
	/**
	 * @return Module or null if not centered on some groups
	 */
	public function getModule() {
		return $this->groupListModule;
	}
	
	// Config service wrapper
		
	public function getParameter($principal, $module, $name) {
		if($module === $this->groupListModule && $this->oneGroup) return $this->getConfigService()->getGroupParameter($principal, $this->firstGroup, $name);
		else return $this->getConfigService()->getParameter($principal, $module, $name);
	}
	public function allowGroupDynamicConfig($principal, $module){
		return $this->getConfigService()->allowGroupDynamicConfig($principal, $module);
	}
	public function getGroupConfigFilename($principal, $group){
		return $this->getConfigService()->getGroupConfigFilename($principal, $group);
	}
	public function doesGroupHasConfigFile($principal, $group){
		return $this->getConfigService()->doesGroupHasConfigFile($principal, $group);
	}
	public function getUserConfigFilename($principal, $module, $user){
		return $this->getConfigService()->getUserConfigFilename($principal, $module, $user);
	}
	public function doesUserHasConfigFile($principal, $module, $user){
		return $this->getConfigService()->doesUserHasConfigFile($principal, $module, $user);
	}
	public function getModuleConfigFilename($principal, $module, $wigiiNamespace){
		return $this->getConfigService()->getModuleConfigFilename($principal, $module, $wigiiNamespace);
	}
	public function listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer){
		return $this->getConfigService()->listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer);
	}
	public function getGroupParameter($principal, $group, $name){
		return $this->getConfigService()->getGroupParameter($principal, $group, $name);
	}
	public function getFields($principal, $module, $activity, $fieldList){
		if($module === $this->groupListModule) {
			if($this->oneGroup) return $this->getConfigService()->getGroupFields($principal, $this->firstGroup, $activity, $fieldList);
			else return $this->getConfigService()->getGroupsFields($principal, $this->groupList, $activity, $fieldList);
		}
		else return $this->getConfigService()->getFields($principal, $module, $activity, $fieldList);
	}
	public function getGroupFields($principal, $group, $activity, $fieldList){
		return $this->getConfigService()->getGroupFields($principal, $group, $activity, $fieldList);
	}
	public function getGroupsFields($principal, $groupList, $activity, $fieldList){
		return $this->getConfigService()->getGroupsFields($principal, $groupList, $activity, $fieldList);
	}
	public function getTemplatePath($principal, $module, $activity){
		if($module === $this->groupListModule && $this->oneGroup) return $this->getConfigService()->getGroupTemplatePath($principal, $this->firstGroup, $activity);
		else return $this->getConfigService()->getTemplatePath($principal, $module, $activity);
	}
	public function getGroupTemplatePath($principal, $group, $activity){
		return $this->getConfigService()->getGroupTemplatePath($principal, $group, $activity);
	}
	public function m($principal, $module){
		if($module === $this->groupListModule && $this->oneGroup) return $this->getConfigService()->g($principal, $this->firstGroup);
		else return $this->getConfigService()->m($principal, $module);
	}
	public function mf($principal, $module, $activity=null){
		if($module === $this->groupListModule && $this->oneGroup) return $this->getConfigService()->gf($principal, $this->firstGroup, $activity);
		else return $this->getConfigService()->mf($principal, $module, $activity);
	}
	public function ma($principal, $module, $activity){
		if($module === $this->groupListModule && $this->oneGroup) return $this->getConfigService()->ga($principal, $this->firstGroup, $activity);
		else return $this->getConfigService()->ma($principal, $module, $activity);
	}
	public function g($principal, $group){
		return $this->getConfigService()->g($principal, $group);
	}
	public function gf($principal, $group, $activity=null){
		return $this->getConfigService()->gf($principal, $group, $activity);
	}
	public function ga($principal, $group, $activity){
		return $this->getConfigService()->ga($principal, $group, $activity);
	}
	public function dt($datatypeName){
		return $this->getConfigService()->dt($datatypeName);
	}
	public function getDataType($datatypeName){
		return $this->getConfigService()->getDataType($datatypeName);
	}
	public function getAdminConfigForClient($principal, $client){
		return $this->getConfigService()->getAdminConfigForClient($principal, $client);
	}
}