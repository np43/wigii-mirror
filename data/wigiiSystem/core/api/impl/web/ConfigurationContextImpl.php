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
 * The ConfigurationContext is there to interface the configuration service. The aim is to give the good configuration
 * depending on the current selected group in the GUI. The setGroupList is the key method to indicate what is the current
 * state in the GUI regarding the group selection.
 * If all groups are selected, then the general wigiiNamespace config is always chosen.
 * If one specific group is chosen, then we look to the possible specific config of the group.
 * 	- 	here there is one more subtility. Instead of just looking for a config for the current group, we look
 * 		for specific config on the parent groups as well if not found.
 * 	-	be aware than even if the config is took from a parent group, a config in a add/delete style will always refer to
 * 		a more general config. That means you cannot create a specific config on a top group, and then play
 * 		with add/delete on subgroups and think that the config on subgroup will add or delete things on the parent
 * 		config.
 * Created on 22 Dec 2009 by LWR
 */
class ConfigurationContextImpl extends Model implements ConfigurationContext, Serializable {

	private $_debugLogger;
	private $_executionSink;
	private $configS;
	private $authS;
	private $groupAS;
	private $includeChildrenGroupsPerModule;
	private $groupPListPerModule;
	private $rootGroupsPerModule;
	private $rootGroupsPerModuleWithoutTrash;
	private $groupForConfigCache;

	public function __construct(){
		$this->debugLogger()->write("creating instance");
	}	
	public function serialize() {
		return serialize(array("groupPListPerModule" => $this->groupPListPerModule,
			"includeChildrenGroupsPerModule" => $this->includeChildrenGroupsPerModule,
			"rootGroupsPerModule" => $this->rootGroupsPerModule,
			"groupForConfigCache" => $this->groupForConfigCache));
	}
	public function unserialize($serialized) {
		$arr = unserialize($serialized);
		$this->groupPListPerModule = $arr["groupPListPerModule"];
		$this->includeChildrenGroupsPerModule = $arr["includeChildrenGroupsPerModule"];
		$this->rootGroupsPerModule = $arr["rootGroupsPerModule"];
		$this->groupForConfigCache = $arr["groupForConfigCache"];
	}
	public static function createInstance() {
		$returnValue = new self();
		return $returnValue;
	}

	private function debugLogger() {
		if(!isset($this->_debugLogger)) {
			$this->_debugLogger = DebugLogger::getInstance("ConfigurationContextImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink() {
		if(!isset($this->_executionSink)) {
			$this->_executionSink = ExecutionSink::getInstance("ConfigurationContextImpl");
		}
		return $this->_executionSink;
	}

	// dependency injection
	public function setConfigService($configService) {
		$this->configS = $configService;
	}
	protected function getConfigService() {
		// autowired
		if(!isset($this->configS)) {
			$this->configS = ServiceProvider::getConfigService();
		}
		return $this->configS;
	}
	public function setAuthorizationService($authorizationService) {
		$this->authS = $authorizationService;
	}
	protected function getAuthorizationService() {
		// autowired
		if(!isset($this->authS)) {
			$this->authS = ServiceProvider::getAuthorizationService();
		}
		return $this->authS;
	}

	public function setGroupAdminService($groupAS){
		$this->groupAS = $groupAS;
	}
	protected function getGroupAdminService(){
		//autowired
		if(!isset($this->groupAS)){
			$this->groupAS = ServiceProvider::getGroupAdminService();
		}
		return $this->groupAS;
	}


	//Context
	protected function getContextKey($principal, $module){
		return "(".($principal->getWigiiNamespace()!=null?$principal->getWigiiNamespace()->getWigiiNamespaceName():"")."(".$principal->getUserId()."(".($module!=null?$module->getModuleName():"").")))";
	}

	public function setGroupPList($principal, $module, $groupPListWithoutDetail, $includeChildrenGroups = true){
		if(!isset($this->groupPListPerModule)) $this->groupPListPerModule = array();
		if(!isset($this->includeChildrenGroupsPerModule)) $this->includeChildrenGroupsPerModule = array();
		$key = $this->getContextKey($principal, $module);
		$this->groupPListPerModule[$key] = $groupPListWithoutDetail;
		$this->includeChildrenGroupsPerModule[$key] = $includeChildrenGroups;
	}

//	/**
//	 * RootGroupInModuleWithoutTrash is available only
//	 */
//	public function getRootGroupsInModuleWithoutTrash($p, $module){
//		if(!isset($this->rootGroupsPerModuleWithoutTrash)) return null;
//		return $this->rootGroupsPerModuleWithoutTrash[$this->getContextKey($p, $module)];
//	}
//	protected function setRootGroupsInModuleWithoutTrash($rootGroupsWithoutTrash, $p, $module){
//		if(!isset($this->rootGroupsPerModuleWithoutTrash)) $this->rootGroupsPerModuleWithoutTrash = array();
//		$this->rootGroupsPerModuleWithoutTrash[$this->getContextKey($p, $module)] = $rootGroupsWithoutTrash;
//	}
	public function getRootGroupsInModule($p, $module){
		if(!isset($this->rootGroupsPerModule)) return null;
		return $this->rootGroupsPerModule[$this->getContextKey($p, $module)];
	}
	protected function setRootGroupsInModule($rootGroups, $p, $module){
		if(!isset($this->rootGroupsPerModule)) $this->rootGroupsPerModule = array();
		$this->rootGroupsPerModule[$this->getContextKey($p, $module)] = $rootGroups;
	}
	//
	public function getGroupPList($principal, $module, $desiredGroupId=null, $includeChildrenGroups=null){
		if(!isset($this->groupPListPerModule)) $this->groupPListPerModule = array();
		if(!isset($this->includeChildrenGroupsPerModule)) $this->includeChildrenGroupsPerModule = array();

		$authS = $this->getAuthorizationService();
		$key = $this->getContextKey($principal, $module);
		//if principal has no access to module, then no fetch to all groups.
		//if principal is public or root then fetch to all group, because this is allowed
		if(!$module || (is_null($principal->getModuleAccess($module)) && !$authS->isPublicPrincipal($principal) && !$authS->isRootPrincipal($principal))){
			$this->groupPListPerModule[$key] = null;
			$this->includeChildrenGroupsPerModule[$key] = null;
			return null;
		}
		if($this->groupPListPerModule[$key] == null || is_array($this->groupPListPerModule[$key]) || $desiredGroupId!==null){
			$groupLA = GroupListAdvancedImpl::createInstance(false);
			$groupAS = $this->getGroupAdminService();
			$lf = ListFilter::createInstance();
			$lf->setFieldSelectorList($groupAS->getFieldSelectorListForGroupWithoutDetail());
			$groupAS->getAllGroups($principal, $module, $groupLA, $lf);
			if($includeChildrenGroups === null) $includeChildrenGroups = false; //normaly always display subfolders list (whithout the element list) except if a filter is set. DEPRECATED $this->getConfigService()->getParameter($principal, $module, "Group_IncludeChildrenGroupsOnSelect") == "1";
			$selectAllGroupsOnFirstLoad = (string)$this->getConfigService()->getParameter($principal, $module, "Group_selectAllGroupsOnFirstLoad") === "1";
			if($groupLA != null && !$groupLA->isEmpty()){
//				fput($groupLAIterator);
				$this->setRootGroupsInModule($groupLA->getRootGroups(), $principal, $module);
				//treat the case when a folder is defined in parameters
				$readGroups = $groupLA->getReadGroups()->getListIterator();
				if($desiredGroupId){
					if($readGroups[$desiredGroupId]){
						$this->groupPListPerModule[$key] = GroupPListArrayImpl::createInstance()->addGroupP($readGroups[$desiredGroupId]);
					} else {
						//select the root
						$this->groupPListPerModule[$key] = $groupLA->getRootGroups();
					}
				} else if($selectAllGroupsOnFirstLoad || $desiredGroupId === 0){
					if($groupLA->isAllRootGroupsReadable() || $includeChildrenGroups){
						$this->groupPListPerModule[$key] = $groupLA->getRootGroups();
					} else {
						//take the first readable group
						$this->groupPListPerModule[$key] = GroupPListArrayImpl::createInstance()->addGroupP(reset($readGroups));
					}
				} else {
					//select last group or select first group
					if($this->groupPListPerModule[$key]){
						reset($this->groupPListPerModule[$key]);
						$groupId = key($this->groupPListPerModule[$key]);
//						fput($groupId);
					} else $groupId = null;
//					fput($this->groupPListPerModule[$key]);
//					fput($groupId);
//					fput($groupLAIterator);
					if($groupId && $readGroups[$groupId]){
//						fput($groupId);
						$this->groupPListPerModule[$key] = GroupPListArrayImpl::createInstance()->addGroupP($readGroups[$groupId]);
					} else {
						//take the first readable group
						$this->groupPListPerModule[$key] = GroupPListArrayImpl::createInstance()->addGroupP(reset($readGroups));
//						//select root groups, or first readable group
//						if($groupLA->isAllRootGroupsReadable()){
//							$this->groupPListPerModule[$key] = $groupLA->getRootGroups();
//						} else {
//							//take the first readable group
//							$this->groupPListPerModule[$key] = GroupPListArrayImpl::createInstance()->addGroupP(reset($readGroups));
//						}
					}
				}
			} else {
				$this->groupPListPerModule[$key] = null;
			}
			$this->includeChildrenGroupsPerModule[$key] = $includeChildrenGroups;
		}
		return $this->groupPListPerModule[$key];
	}
	public function doesGroupListIncludeChildren($principal, $module){
		if(!isset($this->includeChildrenGroupsPerModule)) return null;
		return $this->includeChildrenGroupsPerModule[$this->getContextKey($principal, $module)];
	}

	/**
	 * given a groupP finds the closest group (in the hierarchy) with a configuration file. If no group with config is find root group is returned;
	 */
	protected function getGroupForConfig($principal, $crtGroupP){
		return $this->doGetGroupForConfig($principal, $crtGroupP, true);
	}
	/**
	 * Given a group finds the closest group (in the hierarchy) having a configuration file. 
	 * If no group with config is found then returns null.
	 * @param Principal $principal authenticated user performing the operation
	 * @param GroupP|Group $crtGroupP the group or groupP from which to check if a configuration file is available.
	 * @return int|Group returns found Group with configuration file or 0 if not found.
	 */
	public function isConfigGroupAvailableForGroup($principal, $crtGroupP){
		return $this->doGetGroupForConfig($principal, $crtGroupP, false);
	}
	protected function doGetGroupForConfig($principal, $crtGroupP, $returnRootGroupIfNull){
		if(!isset($this->groupForConfigCache)){
			$this->groupForConfigCache = array();
		}
		if(isset($this->groupForConfigCache[$crtGroupP->getId()])){
			if($returnRootGroupIfNull || $this->groupForConfigCache[$crtGroupP->getId()]["hasConfig"]){
				return $this->groupForConfigCache[$crtGroupP->getId()]["group"];
			} else {
				return 0;
			}
		}

		$orgId = $crtGroupP->getId();
		$groupAS = $this->getGroupAdminService();
		$crtGroup = $crtGroupP->getDbEntity();
		$hasConfig = null;
		$processedGroupIds = array(); //as we process the hierarchy store each step
		while($crtGroup->getGroupParentId() != null){
			if($this->getConfigService()->doesGroupHasConfigFile($principal, $crtGroup)){
				$hasConfig = true;
				break;
			}
			//look if parentId is in cache
			if(isset($this->groupForConfigCache[$crtGroup->getGroupParentId()])){
				$hasConfig = $this->groupForConfigCache[$crtGroup->getGroupParentId()]["hasConfig"];
				$crtGroup = $this->groupForConfigCache[$crtGroup->getGroupParentId()]["group"];
				break;
			}
			$crtGroup = $groupAS->getGroupWithoutDetail($principal, $crtGroup->getGroupParentId());
			$processedGroupIds[] = $crtGroup->getId();
		}
		//if hasConfig is still not defined (that mean crtGroup->groupParentId = 0)
		//check has config on root group
		if($hasConfig===null){
			if($this->getConfigService()->doesGroupHasConfigFile($principal, $crtGroup)){
				$hasConfig = true;
			} else {
				$hasConfig = false;
			}
		}
		$this->groupForConfigCache[$orgId] = array("hasConfig"=>$hasConfig, "group"=>$crtGroup);
		//cache results of each step
		foreach($processedGroupIds as $groupId){
			$this->groupForConfigCache[$groupId] = array("hasConfig"=>$hasConfig, "group"=>$crtGroup);
		}

		if($returnRootGroupIfNull || $hasConfig){
			return $crtGroup;
		} else {
			return 0;
		}
	}
	public function getCrtConfigGroupId($principal, $exec){
		$module = $exec->getCrtModule();
		$dynConf = $this->allowGroupDynamicConfig($principal, $module);
		if(	$dynConf &&
			$this->getGroupPList($principal, $module) != null &&
			is_object($this->getGroupPList($principal, $module)) &&
			$this->getGroupPList($principal, $module)->count() == 1){
				$crtGroupP = reset($this->getGroupPList($principal, $module)->getListIterator());
				$crtGroupId = $this->isConfigGroupAvailableForGroup($principal, $crtGroupP);
				if($crtGroupId!=null) $crtGroupId = $crtGroupId->getId();
		} else {
			$crtGroupId = 0;
		}
		return $crtGroupId;
	}

	// ConfigService Interface

	public function getParameter($principal, $module, $name){
//		eput($this->groupPListPerModule);
		$dynConf = $this->allowGroupDynamicConfig($principal, $module);
		if(	$dynConf &&
			isset($this->groupPListPerModule) &&
			$this->getGroupPList($principal, $module) != null &&
			is_object($this->getGroupPList($principal, $module)) &&
			$this->getGroupPList($principal, $module)->count() == 1){
			return $this->getConfigService()->getGroupParameter($principal, $this->getGroupForConfig($principal, reset($this->getGroupPList($principal, $module)->getListIterator())), $name);
		} else {
			return $this->getConfigService()->getParameter($principal, $module, $name);
		}
	}


	public function getFields($principal, $module, $activity, $fieldList){
		$dynConf = $this->allowGroupDynamicConfig($principal, $module);
		if(	$dynConf &&
			isset($this->groupPListPerModule) &&
			$this->getGroupPList($principal, $module) != null &&
			is_object($this->getGroupPList($principal, $module)) &&
			$this->getGroupPList($principal, $module)->count() == 1){
			return $this->getConfigService()->getGroupFields($principal, $this->getGroupForConfig($principal, reset($this->getGroupPList($principal, $module)->getListIterator())), $activity, $fieldList);
		} else {
			return $this->getConfigService()->getFields($principal, $module, $activity, $fieldList);
		}
	}

	/**
	 * get the TemplatePath for an activity, depending on the group context.
	 * if no template is define in the client config, then a generic template
	 * is returned based on the activity name.
	 */
	public function getTemplatePath($principal, $module, $activity){
		try{
			$dynConf = $this->allowGroupDynamicConfig($principal, $module);
			if(	$dynConf &&
				isset($this->groupPListPerModule) &&
				$this->getGroupPList($principal, $module) != null &&
				is_object($this->getGroupPList($principal, $module)) &&
				$this->getGroupPList($principal, $module)->count() == 1){
				return $this->getConfigService()->getGroupTemplatePath($principal, $this->getGroupForConfig($principal, reset($this->getGroupPList($principal, $module)->getListIterator())), $activity);
			} else {
				return $this->getConfigService()->getTemplatePath($principal, $module, $activity);
			}
		} catch (ConfigServiceException $e){
			if($e->getCode() == ConfigServiceException::NO_TEMPLATE_FOUND){
				$templatePath = CLIENT_CONFIG_PATH.$activity->getActivityName().".php";
				if(!file_exists($templatePath)){
					$templatePath = CLIENT_CONFIG_PATH."../template/".$activity->getActivityName().".php";
					if(!file_exists($templatePath)) throw $e;
				}
				return $templatePath;
			} else {
				throw $e;
			}
		}
	}

	public function m($principal, $module){
		$dynConf = $this->allowGroupDynamicConfig($principal, $module);
		if(	$dynConf &&
			isset($this->groupPListPerModule) &&
			$this->getGroupPList($principal, $module) != null &&
			is_object($this->getGroupPList($principal, $module)) &&
			$this->getGroupPList($principal, $module)->count() == 1){
			return $this->getConfigService()->g($principal, $this->getGroupForConfig($principal, reset($this->getGroupPList($principal, $module)->getListIterator())));
		} else {
			return $this->getConfigService()->m($principal, $module);
		}
	}
	public function mf($principal, $module, $activity=null){
		$dynConf = $this->allowGroupDynamicConfig($principal, $module);
		if(	$dynConf &&
			isset($this->groupPListPerModule) &&
			$this->getGroupPList($principal, $module) != null &&
			is_object($this->getGroupPList($principal, $module)) &&
			$this->getGroupPList($principal, $module)->count() == 1){
			return $this->getConfigService()->gf($principal, $this->getGroupForConfig($principal, reset($this->getGroupPList($principal, $module)->getListIterator())), $activity);
		} else {
			return $this->getConfigService()->mf($principal, $module, $activity);
		}
	}
	public function ma($principal, $module, $activity){
		$dynConf = $this->allowGroupDynamicConfig($principal, $module);
		if(	$dynConf &&
			isset($this->groupPListPerModule) &&
			$this->getGroupPList($principal, $module) != null &&
			is_object($this->getGroupPList($principal, $module)) &&
			$this->getGroupPList($principal, $module)->count() == 1){
			return $this->getConfigService()->ga($principal, $this->getGroupForConfig($principal, reset($this->getGroupPList($principal, $module)->getListIterator())), $activity);
		} else {
			return $this->getConfigService()->ma($principal, $module, $activity);
		}
	}

	//Config Service as is
	public function dt($datatypeName){
		return $this->getConfigService()->dt($datatypeName);
	}
	public function getDataType($datatypeName){
		return $this->getConfigService()->getDataType($datatypeName);
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
	public function listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer){
		return $this->getConfigService()->listModuleConfigFilesForWigiiNamespace($principal, $filenameRenderer);
	}
	public function getModuleConfigFilename($principal, $module, $wigiiNamespace){
		return $this->getConfigService()->getModuleConfigFilename($principal, $module, $wigiiNamespace);
	}


	public function getGroupParameter($principal, $group, $name){
		return $this->getConfigService()->getGroupParameter($principal, $this->getGroupForConfig($principal, $group), $name);
	}
	public function getGroupFields($principal, $group, $activity, $fieldList){
		return $this->getConfigService()->getGroupFields($principal, $this->getGroupForConfig($principal, $group), $activity, $fieldList);
	}
	public function getGroupsFields($principal, $groupList, $activity, $fieldList){
		//if only one group, then take config of group
		//else take global config
		if($groupList->isEmpty() || $groupList->count()==1){
			return $this->getConfigService()->getGroupFields($principal, $this->getGroupForConfig($principal, reset($groupList->getListIterator())), $activity, $fieldList);
		}
		return $this->getConfigService()->getFields($principal, reset($groupList->getListIterator())->getDbEntity()->getModule(), $activity, $fieldList);
	}
	public function getGroupTemplatePath($principal, $group, $activity){
		return $this->getConfigService()->getGroupTemplatePath($principal, $this->getGroupForConfig($principal, $group), $activity);
	}
	public function g($principal, $group){
		return $this->getConfigService()->g($principal, $this->getGroupForConfig($principal, $group));
	}
	public function gf($principal, $group, $activity=null){
		return $this->getConfigService()->gf($principal, $this->getGroupForConfig($principal, $group), $activity);
	}
	public function ga($principal, $group, $activity){
		return $this->getConfigService()->ga($principal, $this->getGroupForConfig($principal, $group), $activity);
	}
	public function getAdminConfigForClient($principal, $client){
		return $this->getConfigService()->getAdminConfigForClient($principal, $client);
	}
}




