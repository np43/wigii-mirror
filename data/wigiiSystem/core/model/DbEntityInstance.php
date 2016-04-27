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

/* A wigii database entity
 * Created by CWE on 31 mai 09
 * updated by LWR on 25 march 10,
 * 	- renamed in DbEntityInstance
 * 	- added method getDbEntity + implements DbEntity
 * 	- the reason is to be able to grab directly the DB entity through model which has rights added, like GroupP or ElementP
 */
class DbEntityInstance extends Model implements DbEntity
{
	private $id;
	
	public static function createInstance($id=null){
		$r = new self();
		$r->setId($id);
		return $r;
	}

	public function getId()
	{
		return $this->id;
	}
	public function setId($id)
	{
		$this->id = $this->formatValue($id);
		return $this;
	}
	public function getDbEntity(){
		return $this;
	}
	/**
	 * Returns true if DbEntityInstance is a new record not yet inserted in the database
	 * if newId is defined, then if the newId is different than the current id -> return true
	 * this is used for the multiple insert
	 */
	public function isNew($newId = null)
	{
		$id = $this->getId();
		return (is_null($id) || $id == 0 || ($newId !==null && $newId != $id));
	}
}



