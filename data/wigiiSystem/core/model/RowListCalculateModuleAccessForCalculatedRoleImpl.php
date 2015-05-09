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
 * RowList RowListCalculateModuleAccessForCalculatedRoleImpl implementation for method UserAdminService->calculateAllMergedRole
 * Created by LWR on 6 July 2010
 */
class RowListCalculateModuleAccessForCalculatedRoleImpl extends Model implements RowList
{
	protected $calculatedRole;
	protected $moduleAdminService;
	protected function getModuleAdminService(){ return $this->moduleAdminService; }
	protected $moduleAccess;
	protected $groupCreator;
	public function getModuleAccess($p){
		if($this->groupCreator){
			$this->moduleAccess[] = Module::ADMIN_MODULE;
		}
		return $this->getModuleAdminService()->formatModuleArray($p, $this->moduleAccess);
	}
	public function getGroupCreator($p){
		return $this->getModuleAdminService()->formatModuleArray($p, $this->groupCreator);
	}
	protected $summary;
	public function getSummaryString(){ return $this->summary; }
	
	public static function createInstance($calculatedRole, $moduleAdminService)
	{
		$returnValue = new self();
		$returnValue->calculatedRole = $calculatedRole;
		$returnValue->moduleAdminService = $moduleAdminService;
		return $returnValue;
	}

	public function getListIterator(){ throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION); }
	public function count(){ throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION); }
	
	public function addRow($row)
	{
		if(is_null($row)) throw new ListException("row cannot be null", ListException::INVALID_ARGUMENT);
		if(!isset($this->moduleAccess)) $this->moduleAccess = array();
		$this->moduleAccess[] = $row["moduleName"];
		if($row["groupCreator"]){
			if(!isset($this->groupCreator)) $this->groupCreator = array();
			$this->groupCreator[] = $row["moduleName"];
		}
		$this->summary .= $row["moduleName"].", ".($row["groupCreator"] ? 'isGroupCreator' : 'NULL')."\n";
	}
}
