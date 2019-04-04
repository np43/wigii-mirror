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
 * DbAdminServiceImpl->doRecursiveDeleteRows state object
 * Created by CWE on 21 sept. 09
 */
class RecursiveDeleteRowsState
{
	private $idList;

	public static function createInstance()
	{
		$returnValue = new RecursiveDeleteRowsState();
		$returnValue->reset();
		return $returnValue;
	}
	public function reset()
	{
		unset($this->idList);
	}

	/**
	 * Returns a comma separated string with all IDs deleted or marked to be deleted
	 * tableName: the table for which we want the list of IDs
	 */
	public function getDeletedIdList($tableName)
	{
		if(!isset($this->idList)) return null;
		return $this->idList[$tableName];
	}
	/**
	 * Appends to the existing Id list a comma separated string with all IDs deleted or marked to be deleted
	 * tableName: the table for which we want to store the list of IDs
	 */
	public function appendDeletedIdList($tableName, $idList)
	{
		if(!isset($idList)) return;
		if(!isset($this->idList)) $this->idList = array();
		$tIdList = $this->idList[$tableName];
		if(isset($tIdList)) $tIdList .= ', ';
		$tIdList .= $idList;
		$this->idList[$tableName] = $tIdList;
	}
}