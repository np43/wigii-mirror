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
 * RowList RowListFindFirstCalculatedNameAvailable implementation for method UserAdminService->calculateAllMergedRole
 * Created by LWR on 6 July 2010
 */
class RowListFindFirstCalculatedNameAvailableImpl extends Model implements RowList
{
	protected $propositions;
	protected $returnValue;
	
	public static function createInstance($propositions)
	{
		$returnValue = new self();
		$returnValue->propositions = $propositions;
		$returnValue->returnValue = null;
		return $returnValue;
	}

	public function getListIterator(){ throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION); }
	public function count(){ throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION); }
	
	public function addRow($row)
	{
		if(is_null($row)) throw new ListException("row cannot be null", ListException::INVALID_ARGUMENT);
		
		$key = array_search($row["username"], $this->propositions);
		if($key!==false) unset($this->propositions[$key]);
	}
	public function getRemaningPropositions(){
		return $this->propositions;
	}
	public function getFirstFreeProposition(){
		$r = reset($this->propositions);
		if(!$r) throw new ListException("no proposition available for calculated name. Check random names in UserAdminService->calculateAllMergedRole", ListException::UNEXPECTED_ERROR);
		return $r;
	}
}
