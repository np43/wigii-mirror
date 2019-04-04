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
 * Tags an object to have exclusive access.
 * This used by the ServiceProvider::getExclusiveAccessOject method.
 * Created by CWE on 27 february 2014
 */
interface WigiiExclusiveAccessObject
{	
	/**
	 * Resets the state of this object to its default state.
	 * This method is called each time the object gains access for an exclusive session.
	 * This method should set the flag isLockedForUse to true.
	 */
	public function reset();
	
	/**
	 * Frees the memory used by this object so that it can be recycled.
	 * This method should set the flag isLockedForUse to false.
	 * This method should be called explicitely by the programmer to end the exclusive session.
	 */
	public function freeMemory();
	
	/**
	 * Returns true if this object is in use, else false.
	 */
	public function isLockedForUse();
}