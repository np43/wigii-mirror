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
 * ObjectList array implementation
 * Created by CWE on 10 oct. 09
 */
class ObjectListArrayImpl extends Model implements ObjectList
{
	protected $objArray;

	// object lifecycle

	public static function createInstance()
	{
		$returnValue = new ObjectListArrayImpl();
		$returnValue->reset();
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
		return ($this->count() === 0);
	}

	public function count()
	{
	    if(!empty($this->objArray)) return (count($this->objArray));
	    else return 0;
	}

	/**
	 * Gets first object from the list, return null if empty
	 */
	public function getFirst() {
	    if(empty($this->objArray)) return null;
	    else return reset($this->objArray);
	}
	
	// Utilities for key management

	/**
	 * Returns wigiiNamespace name or '' if null
	 */
	protected function getWigiiNamespaceName($wigiiNamespace)
	{
		if(isset($wigiiNamespace))
		{
			return $wigiiNamespace->getWigiiNamespaceName();
		}
		else return '';
	}

	/**
	 * Returns module name or '' if null
	 */
	protected function getModuleName($module)
	{
		if(isset($module))
		{
			return $module->getModuleName();
		}
		else return '';
	}

	/**
	 * Returns a key to be used by arrays with a name, module and wigiiNamespace
	 */
	protected function getKeyWithNameModuleWigiiNamespace($name, $module, $wigiiNamespace)
	{
		if(!isset($name)) $name = '';
		return "($name(".$this->getModuleName($module)."(".$this->getWigiiNamespaceName($wigiiNamespace).")))";
	}
}