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
 * RowList RowListForCountElementsInGroups implementation for method ElementService->countElementsInGroups
 * Created by LWR on 17 October 2011
 */
class RowListForCountElementsInGroups extends Model implements RowList
{
	private $countData;
	public function getCountData(){ return $this->countData; }
	public static function createInstance()
	{
		$returnValue = new self();
		$returnValue->countData = array();
		return $returnValue;
	}

	public function getListIterator(){ throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION); }
	public function isEmpty(){ throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION); }
	public function count(){ throw new ServiceException("UNSUPPORTED_OPERATION", ServiceException::UNSUPPORTED_OPERATION); }
	
	public function addRow($row)
	{
		if(is_null($row)) throw new ListException("row cannot be null", ListException::INVALID_ARGUMENT);
		$this->countData[$row["id_group"]] = $row["nbElements"];
	}
}
