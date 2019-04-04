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

/* wigii GroupService implementation which integrates with wigii web site
 * Created by LWR on 23 oct 2009
 */
class GroupAdminServiceWebImpl extends GroupAdminServiceImpl
{
	private $_debugLogger;
	private $_executionSink;
	private $elementGroupsRenderer;

	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("GroupAdminServiceWebImpl");
		}
		return $this->_debugLogger;
	}
	private function executionSink()
	{
		if(!isset($this->_executionSink))
		{
			$this->_executionSink = ExecutionSink::getInstance("GroupAdminServiceWebImpl");
		}
		return $this->_executionSink;
	}
	public function __construct()
	{
		$this->debugLogger()->write("creating instance");
	}
	
	// service implementation

	public function displayGroupId($p, $group, $wigiiNamespace, $module, $label=null, $onlyLink=false){
		$transS = ServiceProvider::getTranslationService();
		if($label == null) $label = $transS->t($p, "idOfGroup").": ";
		if(!$onlyLink){
			echo '<div class="displayId" ><span class="label" >'.$label.'</span><span class="value" >';
		}
		//echo '<a href="'.SITE_ROOT.$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleName().'/display/all/do/groupSelectorPanel/selectGroupAndChildren/'.$group->getId().'/">'.$group->getGroupName().'</a>';
		//first we select the group
		//then reload the whole module to be sure we are in
		echo '<a href="'.$this->getUrlForGroup($wigiiNamespace, $module, $group).'" >'.($group->getWigiiNamespace()->getWigiiNamespaceName()!=null && $p->getWigiiNamespace()->getWigiiNamespaceName()!=$group->getWigiiNamespace()->getWigiiNamespaceName() ? $group->getWigiiNamespace()->getWigiiNamespaceName()." : " : "").$group->getGroupName().'</a>';
		if(!$onlyLink){
			echo '</span></div>';
		}
	}
	public function getUrlForGroup($wigiiNamespace, $module, $group){
		//this link says to the system to select children as defined in the config
		//return SITE_ROOT.$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleUrl()."/display/all/do/NoAnswer/".$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleUrl()."/groupSelectorPanel/selectGroupAsConfig/".$group->getId()."/_do_/mainDiv/".$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleUrl()."/display/all";
		return SITE_ROOT."#".$wigiiNamespace->getWigiiNamespaceUrl()."/".$module->getModuleUrl()."/folder/".(is_object($group) ? $group->getId() : $group)."";
	}
	
	public function getHtmlToDisplayGroupAsFolder($p, $exec, $group){
		$result = "";
		$result .= '<img src="'.SITE_ROOT_forFileUrl.'images/gui/folder-structure-arrow-right.png" align="absmiddle"/>';
		$result .= '&nbsp;&nbsp;<font style="font-size:normal;">'.$group->getGroupName().'</font>';
		return $result;
	}
	
//	/**
//	 * get listFilters to fetch correct informations in Admin module
//	 */
//	public function getListFilterForGroupList(){
//		
//		$listFilter = ListFilter::createInstance();
//		$fskl = FieldSortingKeyListArrayImpl::createInstance();
//		$fskl->addFieldSortingKey("wigiiNamespace");
//		$fskl->addFieldSortingKey("groupname");
//		$listFilter->setFieldSortingKeyList($fskl);
//		
//		return $listFilter;
//	}
}
