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
 * A data flow activity which extracts all users having access to a flow of groups.
 * Creates a StdClass instance which combines the group information, the user information and the access rights:
 * - id: group ID
 * - groupname: group name
 * - groupPath: optional folder structure path separated by slashes. Active by default.
 * - username: real user name or role name
 * - accessRight: access right coded as a letter (r: read, w: write, x: admin)
 *
 * Created by CWE on 24 août 2018
 */
class UserAccessToFolderDFA implements DataFlowActivity
{
	private $showGroupPath;
	
	// Object lifecycle
		
	public function reset() {
		$this->freeMemory();
		$this->showGroupPath = true;
	}	
	public function freeMemory() {
	}
	
	// Dependency injection
		
	private $gAS;
	public function setGroupAdminService($groupAdminService)
	{
	    $this->gAS = $groupAdminService;
	}
	protected function getGroupAdminService()
	{
	    // autowired
	    if(!isset($this->gAS))
	    {
	        $this->gAS = ServiceProvider::getGroupAdminService();
	    }
	    return $this->gAS;
	}
	
	// configuration
	
	/**
	 * Fills the group path. If false, then only fills group ID and name. Active by default.
	 */
	public function setShowGroupPath($bool) {
		$this->showGroupPath = $bool;
	}
	
	// stream data event handling
	
	public function startOfStream($dataFlowContext) {
		/* nothing to do */
	}
	public function processDataChunk($data, $dataFlowContext) {
	    $p = $dataFlowContext->getPrincipal();
	    // gets group object out of its ID
	    if(!is_object($data)) $data = $this->getGroupAdminService()->getGroupWithoutDetail($p, $data);
	    // transforms Group to stdClass instance
	    if($data instanceof Group || $data instanceof GroupP) $data = $this->group2stdClass($data);
	    // fills in group path
	    if($this->showGroupPath) {
	        $groupPath = $this->getGroupAdminService()->getGroupsPath($p, array($data->id));
	        $data->groupPath = implode('/',reset($groupPath));
	    }
	    
	    // gets user access rights
	    $ugrList = evalfx($p, fx('adminAccessToFolder',$data->id,true));
	    //$ugrList=null;
	    // pushes to data flow group associated with user and access rights
	    if($ugrList && !$ugrList->isEmpty()) {
    	    foreach($ugrList->getListIterator() as $ugr) {
    	        $returnValue = clone $data;
    	        $returnValue->username = $ugr->username;
    	        $returnValue->accessRight = $ugr->accessRight;
    	        $dataFlowContext->writeResultToOutput($returnValue, $this);
    	    }
	    }
	    else {
	        $returnValue = clone $data;
	        $returnValue->username = '';
	        $returnValue->accessRight = '';
	        $dataFlowContext->writeResultToOutput($returnValue, $this);
	    }
	}
	public function endOfStream($dataFlowContext) {
		/* nothing to do */
	}	
	
	// single data event handling
	
	public function processWholeData($data, $dataFlowContext) {
		$this->processDataChunk($data, $dataFlowContext);
	}	
	
	// implementation
	
	private function group2stdClass($group) {
	    $group = $group->getDbEntity();
	    $returnValue = array();
	    $returnValue['id'] = $group->getId();
	    $returnValue['groupname'] = $group->getGroupName();
	    return (object)$returnValue;
	}
}