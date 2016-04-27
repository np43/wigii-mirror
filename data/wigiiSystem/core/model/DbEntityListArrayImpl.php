<?php
/**
 *  This file is part of Wigii.
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
 * A list of DbEntities
 * Created by LWR on 14 oct. 10
 */
class DbEntityListArrayImpl implements DbEntityList
{
	protected $objArray;

	// object lifecycle

	public static function createInstance($ids=null)
	{
		$returnValue = new self();
		$returnValue->reset();
		if($ids != null){
			foreach($ids as $id){
				$this->addDbEntity($this->createDbEntityInstance($id));
			}
		}
		return $returnValue;
	}
	public function reset()
	{
		$this->freeMemory();
		$this->objArray = array();
	}
	public function freeMemory()
	{
		unset($this->objArray);
	}

	// ObjectList implementation
	
	public function getListIterator()
	{
		return $this->objArray;
	}

	public function isEmpty()
	{
		if($this->objArray == null) return null;
		return ($this->count() === 0);
	}

	public function count()
	{
		if($this->objArray == null) return null;
		return (count($this->objArray));
	}
	
	public function getIds(){
		if($this->objArray == null) return null;
		return array_keys($this->objArray);
	}
	
	public function getDbEntity($id){
		if($this->objArray == null) return null;
		return $this->objArray[$id];
	}
	public function addDbEntity($dbEntity){
		$this->objArray[$dbEntity->getId()] = $dbEntity;
	}
	protected function createDbEntityInstance($id){
		return DbEntityInstance::createInstance($id);
	}
	public function deleteDbEntity($dbEntity){
		unset($this->objArray[$dbEntity->getId()]);
	}
	public function deleteId($id){
		unset($this->objArray[$id]);
	}
}