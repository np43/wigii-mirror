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
 * A list of Groups (without detail), selected from the database, using the GroupAdminService,
 * and which are pushed into a DataFlow
 * Created by CWE on 28 février 2014
 */
class GroupListDataFlowConnector implements GroupList, DataFlowDumpable
{
	private $_debugLogger;
	private $lockedForUse = true;
	private $dataFlowService;
	private $dataFlowContext;
	private $nGroups;
	private $asStdClass;
	
	// Object lifecycle
	
	public function reset() {
		$this->freeMemory();	
		$this->lockedForUse = true;
		$this->asStdClass = false;		
	}	
	public function freeMemory() {
		unset($this->groupLogExp);
		unset($this->dataFlowService);
		unset($this->dataFlowContext);
		$this->nGroups = 0;	
		$this->lockedForUse = false;	
	}
		
	public function isLockedForUse() {
		return $this->lockedForUse;
	}
	
	public static function createInstance($groupLogExp, $asStdClass=false) {
		$returnValue = new self();
		$returnValue->reset();
		if(isset($inGroupLogExp)) $returnValue->setInGroupLogExp($inGroupLogExp);
		if(isset($listFilter)) $returnValue->setListFilter($listFilter);
		return $returnValue;
	}
	
	// Dependency injection
	
	private function debugLogger()
	{
		if(!isset($this->_debugLogger))
		{
			$this->_debugLogger = DebugLogger::getInstance("GroupListDataFlowConnector");
		}
		return $this->_debugLogger;
	}
	
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
	
	// Configuration
	
	private $groupLogExp;
	/**
	 * Sets the log exp used to select the groups
	 * @param LogExp $groupLogExp a log exp
	 */
	public function setGroupLogExp($groupLogExp) {
		$this->groupLogExp = $groupLogExp;
	}
		
	/**
	 * Sets the output as StdClass instead of Group instances.
	 * If true, then dumps into the data flow StdClass instances with attributes 
	 * id, groupname, wigiiNamespace, module, id_group_parent.
	 * By default, output is Groups (without detail).
	 */
	public function setAsStdClass($bool) {
		$this->asStdClass = $bool;
	}
	
	
	// DataFlowDumpable implementation
	
	public function dumpIntoDataFlow($dataFlowService, $dataFlowContext) {		
		$this->dataFlowService = $dataFlowService;
		$this->dataFlowContext = $dataFlowContext;
		$this->nGroups = 0;
		$this->getGroupAdminService()->getSelectedGroupsWithoutDetail($dataFlowContext->getPrincipal(), $this->groupLogExp, $this);
	}
	
	// GroupList implementation		
	
	public function addGroup($group) {
		$this->nGroups++;
		if($this->asStdClass) {
			$data = (object)array('id' => $group->getId(),
				'wigiiNamespace' => $group->getWigiiNamespace(),
				'module' => $group->getModule(),
				'groupname' => $group->getGroupName(),
				'id_group_parent' => $group->getGroupParentId());
		}
		else $data = $group;
		$this->dataFlowService->processDataChunk($data, $this->dataFlowContext);
	}
	
	public function getListIterator() {throw new GroupAdminServiceException("The GroupListDataFlowConnector cannot be iterated. It is a forward only push of groups into the data flow.", GroupAdminServiceException::UNSUPPORTED_OPERATION);}
	
	public function isEmpty() {return ($this->nGroups == 0);}
	
	public function count() {return $this->nGroups;}	
}