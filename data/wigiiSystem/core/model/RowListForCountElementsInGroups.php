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
 * RowList implementation for method ElementService->countSelectedElementsDistribution
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
